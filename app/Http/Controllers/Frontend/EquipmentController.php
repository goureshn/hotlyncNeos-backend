<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Service\ComplaintRequest;
use App\Models\Eng\EquipmentList;
use App\Models\Eng\EquipmentCheckList;
use App\Models\Eng\EquipmentFile;
use App\Models\Eng\PartList;
use App\Models\Eng\WorkOrder;
use App\Models\Eng\EquipmentGroup;
use App\Models\Eng\EngRequest;
use App\Models\Eng\PreventiveList;
use App\Models\Eng\EquipmentPartGroup;
use App\Models\Eng\EquipmentSupplier;
use App\Models\Eng\EquipmentExternalMaintenance;
use App\Models\Eng\EquipmentPreventivePart;
use App\Models\Eng\EquipmentPreventiveStaff;
use App\Models\Eng\EquipmentPreventiveEquipStatus;
use App\Models\Eng\EquipmentCheckListCategory;
use App\Exports\CommonExport;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Imports\CommonImportExcel;

use App\Http\Controllers\Frontend\GuestserviceController;
use Illuminate\Support\Facades\Config;
use App\Models\Common\CommonUser;
use \Illuminate\Database\QueryException;
use App\Models\Common\PropertySetting;


use App\Modules\Functions;
use DateInterval;
use Mail;
use DateTime;
use Excel;
use DB;
use Illuminate\Http\Request;
use Redis;
use Response;
use Curl;
use File;
use Illuminate\Queue\Worker;

use Log;

//define("DUE", 'Due');
//define("OK", 'OK');
//define("RTIRED", 'Retired');
//define("FAULTY", 'Faulty');
//define("BREAK_DOWN", 'Break Down');
//define("OVER_DUE", 'Over Due');


class EquipmentController extends Controller
{
    //get request list page
    public function RequestList(Request $request)
    {
        $start = microtime(true);

        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $property_id = $request->get('property_id', '0');
        $filter = $request->get('filter','Total');
        $filter_value = $request->get('filter_value', '');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');
        $user_id = $request->get('user_id', 0);

        $date = new DateTime($cur_time);
        $date->sub(new DateInterval('P1D'));
        $last_time = $date->format('Y-m-d H:i:s');

        if ($pageSize < 0)
            $pageSize = 20;

        $ret = array();
        $query = DB::table('eng_request as er')
            ->leftJoin('eng_request_category as erc', 'er.category_id', '=', 'erc.id')
            ->leftJoin('eng_request_subcategory as ers', 'er.sub_category_id', '=', 'ers.id')
            ->leftJoin('common_users as cu', 'er.requestor_id', '=', 'cu.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('common_property as cp', 'er.property_id', '=', 'cp.id')
            ->where('er.property_id', $property_id)
            ->whereRaw(sprintf("DATE(er.created_at) >= '%s' and DATE(er.created_at) <= '%s'", $start_date, $end_date));


        // ->where('time', '>', $last_time);
        $sub_count_query = clone $query;
        if($filter != 'Total') {
            $query->where('er.status', $filter);
        }
        if($filter_value != '')
        {
            $query->where(function ($query) use ($filter_value) {
                $value = '%' . $filter_value . '%';
                $query->where('er.id', 'like', $value)
                    ->orWhere('erc.name', 'like', $value)
                    ->orWhere('ers.name', 'like', $value)
                    ->orWhere('er.status', 'like', $value)
                    ->orWhere('cp.name', 'like', $value)
                    ->orWhere('cu.first_name', 'like', $value)
                    ->orWhere('cu.last_name', 'like', $value);
            });
        }

        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->select(DB::raw('er.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cp.name as property_name, erc.name as category_name, ers.name as subcategory_name'))
            ->skip($skip)->take($pageSize)
            ->get();

        $setting = PropertySetting::getComplaintSetting($property_id);

        foreach($data_list as $key => $row) {
            $info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
            if( !empty($info) )
            {
                $data_list[$key]->lgm_name = $info->name;
                $data_list[$key]->lgm_type = $info->type;
                $data_list[$key]->lg_property_id = $info->property_id;
            }
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['code'] = 200;
        $ret['message'] = '';
        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;

        $data_query = clone $sub_count_query;

        $subcount = $data_query
            ->select(DB::raw("
						count(*) as total,
						COALESCE(sum(er.status = 'Pending'), 0) as pending,
						COALESCE(sum(er.status = 'On Hold'), 0) as hold,
						COALESCE(sum(er.status = 'In Progress'), 0) as progress,
						COALESCE(sum(er.status = 'Completed'), 0) as completed,
						COALESCE(sum(er.status = 'Rejected'), 0) as rejected,
						COALESCE(sum(er.status = 'Default'), 0) as defaultval,
						COALESCE(sum(er.status = 'Accepted'), 0) as accepted
						"))
            ->first();

        $ret['subcount'] = $subcount;

        $end = microtime(true);
        $ret['time'] = $end - $start;

        return Response::json($ret);
    }

    //update status in request
    public  function  updateRequest (Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $property_id = $request->get('property_id', 4);
        $user_id = $request->get('user_id', 0);
        $status = $request->get('status', 'Default');
        $updated_at = $cur_time;
        $request_id = $request->get('request_id', 0);
        $requestor_id = $request->get('requestor_id', 0);
        $subject = $request->get('subject','');
        $location = $request->get('location','');
        $category_name= $request->get('category_name','');
        $sub_category_name = $request->get('sub_category_name','');
        $requestor_name = $request->get('requestor_name','');
        DB::table('eng_request')
            ->where('property_id', $property_id)
            ->where('id', $request_id)
            ->update(['user_id' => $user_id,
                'status' => $status,
                'updated_at' => $updated_at]);

        $ret = array();
        //send notification
        $user_data = DB::table('common_users as cu')
            ->where('cu.id', $requestor_id)
            ->first();
        $email = $user_data->email;

        $message = array();
        if($status == 'Rejected') {
            //send email to requestor
            $smtp = Functions::getMailSetting($property_id, 'notification_');

            $message = array();
            $message['type'] = 'email';

            $message['to'] = $email;
            $message['subject'] = $requestor_name."  Request are Rejected";
            $content_message = " Subject: ".$subject;
            $content_message .= "  location: ".$location;
            $content_message .= "  Category: ".$category_name;
            $content_message .= "  Sub Category: ".$sub_category_name;
            $message['content'] = $content_message;
            $message['smtp'] = $smtp;

            Redis::publish('notify', json_encode($message));



        }else {
            $data = (object)array();
            $data->sub_type = 'create_workorder';
            $data->property_id = $property_id;
            $data->content = 'A new workorder was created by request';
            $message['type'] = 'eng_request';
            $message['data'] = $data;

            Redis::publish('notify', json_encode($message));
        }
        return Response::json($message);
    }

    public function requestorHistory(Request $request){

        $requestor_id = $request->get('requestor_id', 0);
        $property_id =  $request->get('property_id', 0);
        $data_list = DB::table('eng_request as er')
            ->leftJoin('eng_request_category as erc', 'er.category_id', '=', 'erc.id')
            ->leftJoin('eng_request_subcategory as ers', 'er.sub_category_id', '=', 'ers.id')
            ->leftJoin('common_users as cu', 'er.requestor_id', '=', 'cu.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('common_property as cp', 'er.property_id', '=', 'cp.id')
            ->where('er.property_id', $property_id)
            ->select(DB::raw('er.*, jr.job_role, erc.name as category_name, ers.name as sub_category_name,  CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cp.name as property_name'))
            ->where('er.requestor_id', $requestor_id)
            ->get();
        foreach($data_list as $key => $row) {
            $info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
            if( !empty($info) )
            {
                $data_list[$key]->lgm_name = $info->name;
                $data_list[$key]->lgm_type = $info->type;
                $data_list[$key]->lg_property_id = $info->property_id;
            }
        }
        $ret = array();
        $ret['datalist'] = $data_list;
        return Response::json($ret);
    }

    public function getEquipGroupList(Request $request)
    {
        $group_name = $request->get('group_name', '');

        $grouplist = DB::table('eng_equip_group')
            ->where('name', 'like', '%' . $group_name . '%')
            ->select(DB::raw('*'))
            ->get();
        return Response::json($grouplist);
    }

    public function getEquipIdList(Request $request)
    {
        $group_name = $request->get('group_name', '');
        $property_id =  $request->get('property_id', '0');

        $grouplist = DB::table('eng_equip_list');

        //    ->where('property_id',$property_id)


        if($property_id > 0  ){
            $grouplist->where('property_id', $property_id);
        }

        $grouplist = $grouplist->where('equip_id', 'like', '%' . $group_name . '%')
                    ->select(DB::raw('equip_id'))
                    ->get();

        return Response::json($grouplist);
    }


    public function getEquipmentOrGroupList(Request $request)
    {
        $name = $request->get('name', '');

        $grouplist = DB::table('eng_equip_group')
            ->where('name', 'like', '%' . $name . '%')
            ->select(DB::raw('id, name, "group" as type '))
            ->get();

        $equiplist = DB::table('eng_equip_list')
            ->where('name', 'like', '%' . $name . '%')
            ->select(DB::raw('id, name, "single" as type '))
            ->get();
        $grouplist = array_merge($grouplist->toArray(),$equiplist->toArray());

        return Response::json($grouplist);
    }

    public function getWRIDList(Request $request)
    {
        $name = $request->get('name', '');



        $wridlist = DB::table('eng_repair_request')
            ->where('ref_id', 'like', '%' . $name . '%')
            ->select(DB::raw('ref_id'))
            ->get();


        return Response::json($wridlist);
    }

    public function getStatisticInfo(Request $request)
    {
        $period = $request->get('period', 'Today');
        $end_date = $request->get('end_date', '');
        $during = $request->get('during', '');

        $dateArr = explode(" ", $end_date);
        $end_date = $dateArr[0] . " 23:59:59";

        $ret = array();
        switch($period)
        {
            case 'Today';
                date_default_timezone_set(config('app.timezone'));
                $end_date = date('Y-m-d');
                $end_date .= " 23:59:59";
                // $ret = $this->getTicketStaticsticsByToday($request);
                $ret = $this->getTicketStaticsticsByDate($end_date, 1, $request);
                break;
            case 'Weekly';
                $ret = $this->getTicketStaticsticsByDate($end_date, 7 ,  $request);
                break;
            case 'Monthly';
                $ret = $this->getTicketStaticsticsByDate($end_date, 30, $request);
                break;
            case 'Custom Days';
                $ret = $this->getTicketStaticsticsByDate($end_date, $during, $request);
                break;
            case 'Yearly';
                $ret = $this->getTicketStaticsticsByYearly($end_date, $request);
                break;
        }

        return Response::json($ret);
    }

    public function getTicketStaticsticsByToday($request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_date_time = date('Y-m-d H:i:s');
        $before_date_time = date('Y-m-d H:i:s',strtotime("-1 days"));

        $ret = array();

        //out of stock and low stock valuw should be value of current date
        //get part_lsit
        $part_query = DB::table('eng_part_list as epl');

        //out of stock()parts
        $today_query = clone $part_query;
        $ret['total_out_of_stock'] = $today_query
            ->whereRaw('epl.quantity = 0')
            ->count();

        //low stock
        $today_query = clone $part_query;
        $ret['total_low_stock'] = $today_query
            ->whereRaw(' epl.quantity != 0 and epl.quantity <= epl.minquantity ')
            ->count();



        //but these below value should be value of today(last 24hours)
        $time_range = sprintf("'%s' < ew.created_date AND ew.created_date <= '%s'", $before_date_time, $cur_date_time);
        $time_range1 = sprintf("'%s' < err.created_at AND err.created_at <= '%s'", $before_date_time, $cur_date_time);
        //get work order
        $query1 = DB::table('eng_repair_request as err')
            ->whereRaw($time_range1);

        $query = DB::table('eng_workorder as ew')
            ->whereRaw($time_range);



        //pending
        $today_query = clone $query1;
        $ret['total_pending'] = $today_query->where('err.status_name', 'Pending')->count();

        //in progress
        $today_query = clone $query1;
        $ret['total_progress'] = $today_query->where('err.status_name', 'In Progress')->count();

        //assigned
        $today_query = clone $query1;
        $ret['total_assigned'] = $today_query->where('err.status_name', 'Assigned')->count();

        $ret['total_inprogress'] = $ret['total_progress'] + $ret['total_assigned'];

        //closed
        $today_query = clone $query1;
        $ret['total_closed'] = $today_query->where('err.status_name', 'Closed')->count();

        //Completed
        $today_query = clone $query1;
        $ret['total_completed'] = $today_query->where('err.status', 'Completed')->count();

        //number of workorder
        $today_query = clone $query;
        $number_of_workorder = $today_query
            ->select(DB::raw("count(*) as cnt,
						SUM(ew.status = 'Pending') AS pending,
						SUM(ew.status =  'On Hold') AS hold, 
						SUM(ew.status =  'In Progress') AS progress,
						SUM(ew.status =  'Completed') AS completed "))
            ->first();
        if(empty($number_of_workorder)) {
            $number_of_workorder['pending'] = 0;
            $number_of_workorder['hold'] = 0;
            $number_of_workorder['progress'] = 0;
            $number_of_workorder['completed'] = 0;
        }
        $ret['number_of_workorder'] = $number_of_workorder;

        //cost of work order completed
        $today_query = clone $query;
        $workorder_type = array('Repairs', 'Requests', 'Preventive' , 'Upgrade','New');
        $cost_of_workorder = array();
        for($i = 0; $i < count($workorder_type)  ; $i++ ) {
            $data = $today_query
                ->where('ew.status','Completed')
                ->where('ew.work_order_type',$workorder_type[$i])
                ->select(DB::raw("(ew.staff_cost+ew.part_cost) as cost"))
                ->first();
            if(empty($data)) $cost_val = 0;
            else if($data->cost == null) $cost_val = 0;
            else $cost_val = $data->cost;

            if($workorder_type[$i] == 'Repairs' ) $cost_of_workorder['repair'] = $cost_val;
            if($workorder_type[$i] == 'Requests' ) $cost_of_workorder['requests'] = $cost_val;
            if($workorder_type[$i] == 'Preventive' ) $cost_of_workorder['preventive'] = $cost_val;
            if($workorder_type[$i] == 'Upgrade' ) $cost_of_workorder['upgrade'] = $cost_val;
            if($workorder_type[$i] == 'New' ) $cost_of_workorder['new'] = $cost_val;
        }
        $ret['cost_of_workorder'] = $cost_of_workorder;

        //number of workorder completed
        $today_query = clone $query;
        $number_of_workorder_completed = $today_query
            ->leftJoin('eng_workorder_staff_status as ews', 'ews.workorder_id', '=', 'ew.id')
            ->leftJoin('common_users as cu', 'cu.id', '=', 'ews.staff_id')
            ->take(10)
            ->groupBy('ews.staff_id')
            ->select(DB::raw("count(*) as cnt,
						SUM(ew.status = 'Pending') AS pending,
						SUM(ew.status = 'On Hold') AS hold,
						SUM(ew.status = 'In Progress') AS progress,
						SUM(ew.status = 'Completed') AS completed, CONCAT_WS(\" \", cu.first_name, cu.last_name) as user"))
            ->get();

        $ret['number_of_workorder_completed'] = $number_of_workorder_completed;

        //priority
        $today_query = clone $query;
        $priority = $today_query
            ->select(DB::raw("
						SUM(ew.priority =  'Low') AS low,
						SUM(ew.priority =  'Medium') AS medium, 
						SUM(ew.priority =  'High') AS high,
						SUM(ew.priority =  'Urgent') AS urgent"))
            ->first();
        if(empty($priority)) {
            $priority['low'] = 0;
            $priority['medium'] = 0;
            $priority['high'] = 0;
            $priority['urgent'] = 0;
        }
        $ret['priority'] = $priority;

        $repair = DB::table('eng_repair_request as err')
                    ->whereRaw($time_range1);
        //status
        $today_query = clone $repair;
        $status = $today_query
            ->select(DB::raw("count(*) as cnt,
						SUM(err.status_name = 'Pending') AS pending,
						SUM(err.status_name =  'Assigned') AS assigned, 
                        SUM(err.status_name =  'Completed') AS completed,
                        SUM(err.status_name =  'Closed') AS closed,
                        SUM(err.status_name =  'Pre-Approved') AS pre_approved,
                        SUM(err.status_name =  'In Progress') AS in_progress,
                        SUM(err.status_name =  'Rejected') AS rejected

						 "))
            ->first();
        if(empty($status)) {
            $status['pending'] = 0;
            $status['assigned'] = 0;
            $status['completed'] = 0;
            $status['closed'] = 0;
            $status['pre_approved'] = 0;
            $status['in_progress'] = 0;
            $status['rejected'] = 0;
        }
        $ret['status'] = $status;

        return $ret;
    }


    public function getTicketStaticsticsByDate($end_date, $during, $request)
    {
        $date = new DateTime($end_date);
        $date->sub(new DateInterval('P' . $during . 'D'));

        $before_date_time =  $date->format('Y-m-d H:i:s');
        $cur_date_time = $end_date;

        $ret = array();
        //out of stock and low stock valuw should be value of current date
        //get part_lsit
        $part_query = DB::table('eng_part_list as epl');

        //out of stock()parts
        $today_query = clone $part_query;
        $ret['total_out_of_stock'] = $today_query
            ->whereRaw('epl.quantity = 0')
            ->count();

        //low stock
        $today_query = clone $part_query;
        $ret['total_low_stock'] = $today_query
            ->whereRaw(' epl.quantity != 0 and epl.quantity <= epl.minquantity ')
            ->count();

        //but these below value should be value of today(last 24hours)
        $time_range = sprintf("'%s' < ew.created_date AND ew.created_date <= '%s'", $before_date_time, $cur_date_time);
        $time_range1 = sprintf("'%s' < err.created_at AND err.created_at <= '%s'", $before_date_time, $cur_date_time);
        //get work order
        $query = DB::table('eng_workorder as ew')
            ->whereRaw($time_range);

         $query1 = DB::table('eng_repair_request as err')
            ->whereRaw($time_range1);

        //pending
        $today_query = clone $query1;
        $ret['total_pending'] = $today_query->where('err.status_name', 'Pending')->count();

        //in progress
        $today_query = clone $query1;
        $ret['total_progress'] = $today_query->where('err.status_name', 'In Progress')->count();

        //assigned
        $today_query = clone $query1;
        $ret['total_assigned'] = $today_query->where('err.status_name', 'Assigned')->count();

        $ret['total_inprogress'] = $ret['total_progress'] + $ret['total_assigned'];

        //closed
        $today_query = clone $query1;
        $ret['total_closed'] = $today_query->where('err.status_name', 'Closed')->count();

        //Completed
        $today_query = clone $query1;
        $ret['total_completed'] = $today_query->where('err.status', 'Completed')->count();

        //number of workorder
        $today_query = clone $query;
        $number_of_workorder = $today_query
            ->select(DB::raw("count(*) as cnt,
						SUM(ew.status = 'Pending') AS pending,
						SUM(ew.status =  'On Hold') AS hold, 
						SUM(ew.status =  'In Progress') AS progress,
						SUM(ew.status =  'Completed') AS completed "))
            ->first();
        if(empty($number_of_workorder)) {
            $number_of_workorder['pending'] = 0;
            $number_of_workorder['hold'] = 0;
            $number_of_workorder['progress'] = 0;
            $number_of_workorder['completed'] = 0;
        }
        $ret['number_of_workorder'] = $number_of_workorder;

        //cost of work order completed
        $today_query = clone $query;
        $workorder_type = array('Repairs', 'Requests', 'Preventive' , 'Upgrade','New');
        $cost_of_workorder = array();
        for($i = 0; $i < count($workorder_type)  ; $i++ ) {
            $data = $today_query
                ->where('ew.status','Completed')
                ->where('ew.work_order_type',$workorder_type[$i])
                ->select(DB::raw("(ew.staff_cost+ew.part_cost) as cost"))
                ->first();
            if(empty($data)) $cost_val = 0;
            else if($data->cost == null) $cost_val = 0;
            else $cost_val = $data->cost;

            if($workorder_type[$i] == 'Repairs' ) $cost_of_workorder['repair'] = $cost_val;
            if($workorder_type[$i] == 'Requests' ) $cost_of_workorder['requests'] = $cost_val;
            if($workorder_type[$i] == 'Preventive' ) $cost_of_workorder['preventive'] = $cost_val;
            if($workorder_type[$i] == 'Upgrade' ) $cost_of_workorder['upgrade'] = $cost_val;
            if($workorder_type[$i] == 'New' ) $cost_of_workorder['new'] = $cost_val;
        }
        $ret['cost_of_workorder'] = $cost_of_workorder;

        //number of workorder completed
        $today_query = clone $query;
        $number_of_workorder_completed = $today_query
            ->leftJoin('eng_workorder_staff_status as ews', 'ews.workorder_id', '=', 'ew.id')
            ->leftJoin('common_users as cu', 'cu.id', '=', 'ews.staff_id')
            ->where('cu.deleted', 0)
            ->take(10)
            ->groupBy('ews.staff_id')
            ->select(DB::raw("count(*) as cnt,
						SUM(ew.status = 'Pending') AS pending,
						SUM(ew.status = 'On Hold') AS hold,
						SUM(ew.status = 'In Progress') AS progress,
						SUM(ew.status = 'Completed') AS completed, CONCAT_WS(\" \", cu.first_name, cu.last_name) as user, cu.id as user_id"))
            ->get();

        $ret['number_of_workorder_completed'] = $number_of_workorder_completed;

        //priority
        $today_query = clone $query;
        $priority = $today_query
            ->select(DB::raw("
						SUM(ew.priority =  'Low') AS low,
						SUM(ew.priority =  'Medium') AS medium, 
						SUM(ew.priority =  'High') AS high,
						SUM(ew.priority =  'Urgent') AS urgent"))
            ->first();
        if(empty($priority)) {
            $priority['low'] = 0;
            $priority['medium'] = 0;
            $priority['high'] = 0;
            $priority['urgent'] = 0;
        }
        $ret['priority'] = $priority;

        $repair = DB::table('eng_repair_request as err')
                    ->whereRaw($time_range1);
        //status
        $today_query = clone $repair;
        $status = $today_query
            ->select(DB::raw("count(*) as cnt,
						SUM(err.status_name = 'Pending') AS pending,
						SUM(err.status_name =  'Assigned') AS assigned, 
                        SUM(err.status_name =  'Completed') AS completed,
                        SUM(err.status_name =  'Closed') AS closed,
                        SUM(err.status_name =  'Pre-Approved') AS pre_approved,
                        SUM(err.status_name =  'In Progress') AS in_progress,
                        SUM(err.status_name =  'Rejected') AS rejected

						 "))
            ->first();
        if(empty($status)) {
            $status['pending'] = 0;
            $status['assigned'] = 0;
            $status['completed'] = 0;
            $status['closed'] = 0;
            $status['pre_approved'] = 0;
            $status['in_progress'] = 0;
            $status['rejected'] = 0;
        }
        $ret['status'] = $status;

        return $ret;
    }

    public function getTicketStaticsticsByYearly($end_date, $request)
    {
        $date = new DateTime($end_date);
        $date->sub(new DateInterval('P1Y'));

        $before_date_time =  $date->format('Y-m-d H:i:s');
        $cur_date_time = $end_date;

        $ret = array();
        //out of stock and low stock valuw should be value of current date
        //get part_lsit
        $part_query = DB::table('eng_part_list as epl');

        //out of stock()parts
        $today_query = clone $part_query;
        $ret['total_out_of_stock'] = $today_query
            ->whereRaw('epl.quantity = 0')
            ->count();

        //low stock
        $today_query = clone $part_query;
        $ret['total_low_stock'] = $today_query
            ->whereRaw(' epl.quantity != 0 and epl.quantity <= epl.minquantity ')
            ->count();

        //but these below value should be value of today(last 24hours)
        $time_range = sprintf("'%s' < ew.created_date AND ew.created_date <= '%s'", $before_date_time, $cur_date_time);
        $time_range1 = sprintf("'%s' < err.created_at AND err.created_at <= '%s'", $before_date_time, $cur_date_time);
        //get work order
        $query = DB::table('eng_workorder as ew')
            ->whereRaw($time_range);

        $query1 = DB::table('eng_repair_request as err')
            ->whereRaw($time_range1);

       //pending
       $today_query = clone $query1;
       $ret['total_pending'] = $today_query->where('err.status_name', 'Pending')->count();

       //in progress
       $today_query = clone $query1;
       $ret['total_progress'] = $today_query->where('err.status_name', 'In Progress')->count();

       //assigned
       $today_query = clone $query1;
       $ret['total_assigned'] = $today_query->where('err.status_name', 'Assigned')->count();

       $ret['total_inprogress'] = $ret['total_progress'] + $ret['total_assigned'];

       //closed
       $today_query = clone $query1;
       $ret['total_closed'] = $today_query->where('err.status_name', 'Closed')->count();

       //Completed
       $today_query = clone $query1;
       $ret['total_completed'] = $today_query->where('err.status', 'Completed')->count();
        //number of workorder
        $today_query = clone $query;
        $number_of_workorder = $today_query
            ->select(DB::raw("count(*) as cnt,
						SUM(ew.status = 'Pending') AS pending,
						SUM(ew.status =  'On Hold') AS hold, 
						SUM(ew.status =  'In Progress') AS progress,
						SUM(ew.status =  'Completed') AS completed "))
            ->first();
        if(empty($number_of_workorder)) {
            $number_of_workorder['pending'] = 0;
            $number_of_workorder['hold'] = 0;
            $number_of_workorder['progress'] = 0;
            $number_of_workorder['completed'] = 0;
        }
        $ret['number_of_workorder'] = $number_of_workorder;

        //cost of work order completed
        $today_query = clone $query;
        $workorder_type = array('Repairs', 'Requests', 'Preventive' , 'Upgrade','New');
        $cost_of_workorder = array();
        for($i = 0; $i < count($workorder_type)  ; $i++ ) {
            $data = $today_query
                ->where('ew.status','Completed')
                ->where('ew.work_order_type',$workorder_type[$i])
                ->select(DB::raw("(ew.staff_cost+ew.part_cost) as cost"))
                ->first();
            if(empty($data)) $cost_val = 0;
            else if($data->cost == null) $cost_val = 0;
            else $cost_val = $data->cost;

            if($workorder_type[$i] == 'Repairs' ) $cost_of_workorder['repair'] = $cost_val;
            if($workorder_type[$i] == 'Requests' ) $cost_of_workorder['requests'] = $cost_val;
            if($workorder_type[$i] == 'Preventive' ) $cost_of_workorder['preventive'] = $cost_val;
            if($workorder_type[$i] == 'Upgrade' ) $cost_of_workorder['upgrade'] = $cost_val;
            if($workorder_type[$i] == 'New' ) $cost_of_workorder['new'] = $cost_val;
        }
        $ret['cost_of_workorder'] = $cost_of_workorder;

        //number of workorder completed
        $today_query = clone $query;
        $number_of_workorder_completed = $today_query
            ->leftJoin('eng_workorder_staff_status as ews', 'ews.workorder_id', '=', 'ew.id')
            ->join('common_users as cu', 'cu.id', '=', 'ews.staff_id')
            ->where('cu.deleted', 0)
            ->take(10)
            ->groupBy('ews.staff_id')
            ->select(DB::raw("count(*) as cnt,
						SUM(ew.status = 'Pending') AS pending,
						SUM(ew.status = 'On Hold') AS hold,
						SUM(ew.status = 'In Progress') AS progress,
						SUM(ew.status = 'Completed') AS completed, CONCAT_WS(\" \", cu.first_name, cu.last_name) as user, cu.id as user_id"))
            ->get();

        $ret['number_of_workorder_completed'] = $number_of_workorder_completed;

        //priority
        $today_query = clone $query;
        $priority = $today_query
            ->select(DB::raw("
						SUM(ew.priority =  'Low') AS low,
						SUM(ew.priority =  'Medium') AS medium, 
						SUM(ew.priority =  'High') AS high,
						SUM(ew.priority =  'Urgent') AS urgent"))
            ->first();
        if(empty($priority)) {
            $priority['low'] = 0;
            $priority['medium'] = 0;
            $priority['high'] = 0;
            $priority['urgent'] = 0;
        }
        $ret['priority'] = $priority;


        $repair = DB::table('eng_repair_request as err')
                    ->whereRaw($time_range1);
        //status
        $today_query = clone $repair;
        $status = $today_query
            ->select(DB::raw("count(*) as cnt,
						SUM(err.status_name = 'Pending') AS pending,
						SUM(err.status_name =  'Assigned') AS assigned, 
                        SUM(err.status_name =  'Completed') AS completed,
                        SUM(err.status_name =  'Closed') AS closed,
                        SUM(err.status_name =  'Pre-Approved') AS pre_approved,
                        SUM(err.status_name =  'In Progress') AS in_progress,
                        SUM(err.status_name =  'Rejected') AS rejected
						 "))
            ->first();
        if(empty($status)) {
            $status['pending'] = 0;
            $status['assigned'] = 0;
            $status['completed'] = 0;
            $status['closed'] = 0;
            $status['pre_approved'] = 0;
            $status['in_progress'] = 0;
            $status['rejected'] = 0;
        }
        $ret['status'] = $status;

        return $ret;
    }

    public function createEquipCheckList(Request $request)
    {
        $input = $request->all();
        $location_group = json_decode($request->get('location','[]'));
        if( $input['id'] <= 0 ) {

            $checklist = new EquipmentCheckList();
            $checklist->property_id = $request->get('property_id','0');
            $checklist->name = $request->get('name','');
            $checklist->equip_group_id = $request->get('equip_group_id','0');
            $checklist->work_order_type = $request->get('work_order_type','Repairs');
            $checklist->save();
            $id = $checklist->id;

            if (!empty($location_group)) {
                for ($i = 0; $i < count($location_group); $i++) {
                    $checklist_id = $id;
                    $location_id = $location_group[$i]->location_id;
                    DB::table('eng_checklist_location')->insert(['checklist_id' => $checklist_id,
                        'location_id' => $location_id
                        ]);
                }
            }
        }
        else {
            $id = $input['id'];
            $checklist =  EquipmentCheckList::find($id);
            $checklist->property_id = $request->get('property_id','0');
            $checklist->name = $request->get('name','');
            $checklist->equip_group_id = $request->get('equip_group_id','0');
            $checklist->work_order_type = $request->get('work_order_type','Repairs');
            $checklist->save();

            if(!empty($location_group)) {
                DB::table('eng_checklist_location')
                    ->where('checklist_id', $id)
                    ->delete();

                for ($i = 0; $i < count($location_group); $i++) {
                    $checklist_id = $id;
                    $location_id = $location_group[$i]->location_id;
                    DB::table('eng_checklist_location')->insert(['checklist_id' => $checklist_id,
                        'location_id' => $location_id
                        ]);
                }
            }
            else
            {
                DB::table('eng_checklist_location')
                    ->where('checklist_id', $input['id'])
                    ->delete();
            }
        }

        $ret = array();

        return Response::json($ret);
    }

    public function deletEquipChecklist(Request $request) {
        $id = $request->get('id', '0');
        $equipment =  EquipmentCheckList::find($id);
        $equipment->delete();
        DB::table('eng_checklist_location')
            ->where('checklist_id', $id)
            ->delete();
        return Response::json('200');
    }

    public function getEngCheckListNames(Request $request) {
        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $property_id = $request->get('property_id', '0');

        if($pageSize < 0 )
            $pageSize = 20;

        $ret = array();

        $query = DB::table('eng_checklist as ec')
            ->leftjoin('eng_equip_group as eg', 'ec.equip_group_id', '=', 'eg.id')
            ->where('ec.property_id', $property_id);

        $data_query = clone $query;

        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->select(DB::raw('ec.*, eg.name as equip_group_name'))
            ->skip($skip)->take($pageSize)
            ->get();

        for($i = 0; $i < count($data_list); $i++ ) {
            $locations = DB::table('eng_checklist_location as el')
                ->join('services_location as sl', 'el.location_id', '=', 'sl.id')
                ->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
                ->where('el.checklist_id', $data_list[$i]->id)
                ->select(DB::raw('sl.*, slt.type'))
                ->get();

            $data_list[$i]->locations = $locations;

            $items = DB::table('eng_checklist_item as ei')
                ->join('eng_checklist_pivot as ep', 'ep.item_id', '=', 'ei.id')
                ->where('ep.checklist_id', $data_list[$i]->id)
                ->select(DB::raw('ei.*'))
                ->get();
            $data_list[$i]->items = $items;
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $check_list_items = DB::table('eng_checklist_item')
            ->get();

        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;
        $ret['check_list_items'] = $check_list_items;

        return Response::json($ret);
    }

    public function postEngCheckListItems(Request $request) {
        $checklist_id = $request->get('checklist_id', 0);
        $items = $request->get('items', 0);

        DB::table('eng_checklist_pivot')
            ->where('checklist_id', $checklist_id)
            ->delete();

        for($i = 0; $i < count($items); $i++) {
            $check_item = DB::table('eng_checklist_item')
                ->where('name', $items[$i]['name'])
                ->first();
            if( empty($check_item) )
            {
                $data = array();
                $data['name'] = $items[$i]['name'];
                $id = DB::table('eng_checklist_item')->insertGetId($data);
            }
            else
                $id = $check_item->id;

            $data = array();
            $data['checklist_id'] = $checklist_id;
            $data['item_id'] = $id;

            DB::table('eng_checklist_pivot')->insert($data);
        }

        $check_list_items = DB::table('eng_checklist_item')
            ->get();
        $ret = array();
        $ret['check_list_items'] = $check_list_items;

        return Response::json($ret);
    }

    //get request max id
    public function getMaxID(Request $request) {
        $max_id = DB::table('eng_request')
            ->select(DB::raw('max(id) as max_id'))
            ->first();

        return Response::json($max_id);
    }

    public function createRequest(Request $request) {
        $client_id = $request->get('client_id', 4);
        $property_id = $request->get('property_id', 4);
        $loc_id = $request->get('loc_id', 0);
        $comment = $request->get('comment', '');
        $priority = $request->get('priority', 1);
        $requestor_id = $request->get('requestor_id',0);
        $category_id = $request->get('category_id',0);
        $sub_category_id = $request->get('sub_category_id',0);
        $subject = $request->get('subject',0);
        $comment = $request->get('comment',0);

        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $cur_date = date("Y-m-d");
        $created_at = $cur_time;

        $eng = new EngRequest();

        $eng->property_id = $property_id;
        $eng->requestor_id = $requestor_id;
        $eng->priority = $priority;
        $eng->loc_id = $loc_id;
        $eng->category_id = $category_id;
        $eng->sub_category_id = $sub_category_id;
        $eng->subject = $subject;
        $eng->comment = $comment;
        $eng->created_at = $created_at;
        $eng->save();
        $id = $eng->id;

        $ret = array();

        $ret['code'] = 200;
        $ret['id'] = $eng->id;

        $ret['message'] = $this->sendNotifyForEng($id);
        $ret['content'] = $eng;

        return Response::json($ret);
    }

    private function sendNotifyForEng($id) {
        $eng = DB::table('eng_request as er')
            ->leftJoin('eng_request_category as erc', 'er.category_id', '=', 'erc.id')
            ->leftJoin('eng_request_subcategory as ers', 'er.sub_category_id', '=', 'ers.id')
            ->join('common_users as cu', 'er.requestor_id', '=', 'cu.id')
            ->join('common_property as cp', 'er.property_id', '=', 'cp.id')
            ->select(DB::raw('er.*, erc.name as category_name, ers.name as sub_category_name,  CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cp.name as property_name'))
            ->where('er.id', $id)
            ->first();

        if( empty($eng) )
            return '';

        // find  manager
        date_default_timezone_set(config('app.timezone'));
        $dayofweek = date('w');

        $date = date('Y-m-d');
        $time = date('H:i:s');
        $datetime = date('Y-m-d H:i:s');


        $location_name = '';
        $info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($eng->loc_id);
        if( !empty($info) )
            $location_name = $info->name . ' - ' . $info->type;

//        $message_content = sprintf('There is a new engineering request E%05d which has been raised by %s for %s in %s',
//            $eng->id, $eng->wholename, $location_name, $eng->property_name);
        $message_content = sprintf('A New engineering request E%05d has been createded',$eng->id);
        //find  engineering manager
        $eng_manager = DB::table('common_users as cu')
            ->leftJoin('common_job_role as cr', 'cu.job_role_id', '=', 'cr.id')
            ->leftJoin('common_permission_members as cm', 'cr.permission_group_id', '=', 'cm.perm_group_id')
            ->leftJoin('common_page_route as cpr', 'cm.page_route_id', '=', 'cpr.id')
            ->where('cpr.name','app.engineering.request')
            ->select(DB::raw('cu.*'))
            ->get();
        for($i=0; $i < count($eng_manager); $i++) {
            $wholename = $eng_manager[$i]->first_name . ' ' . $eng_manager[$i]->last_name;
            $email = $eng_manager[$i]->email;
            $mobile = $eng_manager[$i]->mobile;
            $fcm_key = $eng_manager[$i]->fcm_key;


            // find duty manager on shift
//        $job_roles = PropertySetting::getJobRoles($eng->property_id);
//        $userlist = ShiftGroupMember::getUserlistOnCurrentShift($eng->property_id, $job_roles['dutymanager_job_role'], 0, 0, 0, 0, 0, false, false);
//
//        if( empty($userlist) || count($userlist) < 1 )
//            return $message_content;
//
//        $duty_manager = $userlist[0];

            $eng->sub_type = 'post';        // for web push
            $eng->content = $message_content;

            $info = array();
            $info['wholename'] = $wholename;
            $info['subject'] = $eng->subject;
            $info['requester'] = $eng->wholename;
            $info['location'] = $location_name;
            $info['category'] = $eng->category_name;
            $info['sub_category'] = $eng->sub_category_name;
            $info['comment'] = $eng->comment;
            $info['content'] = $message_content;

            $eng->subject = sprintf('New Engineering request Raised - E%05d', $eng->id);
            $eng->email_content = view('emails.engrequest_create', ['info' => $info])->render();
//            $email = 'goldstarkyg91@gmail.com'; //test
//            if($i == 0)
            $this->sendEngNotification($eng->property_id, $message_content, $eng->comment, $eng, $email, $mobile, $fcm_key, $i);
        }
        return $message_content;
    }

    public function sendEngNotification($property_id, $subject, $content, $data, $email, $mobile, $pushkey, $countnum) {
  //      $complaint_setting = PropertySetting::getComplaintSetting($property_id);

        // check notify mode(email, sms, mobile push)
        //$alarm_mode = $complaint_setting['complaint_notify_mode'];

//        $email_mode = false;
//        $sms_mode = false;
//        $webapp_mode = false;
//        if (strpos($alarm_mode, 'email') !== false) {
//            $email_mode = true;
//        }
//
//        if (strpos($alarm_mode, 'sms') !== false) {
//            $sms_mode = true;
//        }
//
//        if (strpos($alarm_mode, 'webapp') !== false) {
//            $webapp_mode = true;
//        }
        $email_mode = true;
        $sms_mode = false;
        $webapp_mode = true;
        if( $email_mode == true )
        {
            $smtp = Functions::getMailSetting($property_id, 'notification_');

            $message = array();
            $message['type'] = 'email';

            $message['to'] = $email;
            if( !empty($data->subject) )
                $message['subject'] = $data->subject;
            else
                $message['subject'] = $subject;

            if( !empty($data->email_content) )
                $message['content'] = $data->email_content;
            else
                $message['content'] = $content;

            $message['smtp'] = $smtp;

            Redis::publish('notify', json_encode($message));
        }

        if( $sms_mode == true )
        {
            // send sms
            $message = array();
            $message['type'] = 'sms';

            $message['to'] = $mobile;
            $message['content'] = $subject;

            Redis::publish('notify', json_encode($message));
        }

        //send only one time for push message
        if( $webapp_mode == true && $countnum == 0 )
        {
            // send sms
            $message = array();
            $message['type'] = 'eng_request';
            $message['data'] = $data;

            Redis::publish('notify', json_encode($message));
        }


    }

    public function uploadFiles(Request $request) {
        $output_dir = "uploads/equip/";

        if(!File::isDirectory(public_path($output_dir)))
            File::makeDirectory(public_path($output_dir), 0777, true, true);

        $ret = array();

        $filekey = 'files';

        $id = $request->get('id', 0);

        // if($request->hasFile($filekey) === false )
        // 	return "No input file";

        //You need to handle  both cases
        //If Any browser does not support serializing of multiple files using FormData()

        $fileCount = count($_FILES[$filekey]["name"]);
        $path = '';
        for ($i = 0; $i < $fileCount; $i++)
        {
            $fileName = $_FILES[$filekey]["name"][$i];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $filename1 = "eng_" . $id . '_' . $i . '_' . $fileName;

            $dest_path = $output_dir . $filename1;
            move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);
            if( $i > 0 )
                $path .= '|';

            $path .=  $dest_path;
        }

        $eng = EngRequest::find($id);
        if( !empty($eng) )
        {
            $eng->path = $path;
            $eng->save();
        }

        return Response::json($_FILES[$filekey]);
    }

    public function getEquipmentData($filter, $client_id, $property_id, $location_id)
    {
        $ret = array();

        $query = DB::table('eng_equip_list as eg')
            ->where('eg.name','like','%'.$filter.'%')
            ->where('eg.equip_id','like','%'.$filter.'%');

        if($property_id > 0  )
            $query->where('eg.property_id', $property_id);

        if($location_id > 0  )
            $query->where('eg.location_group_member_id', $location_id);

        if( $client_id > 0 )
        {
            $query->join('common_property as cp', 'eg.property_id', '=', 'cp.id')
                ->where('cp.client_id', $client_id);
        }

        $query->leftJoin('services_location as sl', 'eg.location_group_member_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id');

        $list = $query
            ->where('sl.name','like','%'.$filter.'%')
            ->where('slt.type','like','%'.$filter.'%')
            ->select(DB::raw('eg.*, sl.id as loc_id, sl.name as location_name, slt.type as location_type'))
            ->get();

        $ret = $list;
        return $ret;
    }

    public function getEquipmentNameList(Request $request)
    {
        $property_id = $request->get('property_id', 0);

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $this->getEquipmentData('', 0, $property_id, 0);

        return Response::json($ret);
    }

    public function getMaintenanceList(Request $request)
    {
        $name = $request->get('name', '');

        $list = DB::table('eng_external_maintenance')
            ->where('external_maintenance', 'like', '%' . $name . '%')
            ->select(DB::raw('*'))
            ->get();
        return Response::json($list);
    }

    public function getCheckList(Request $request)
    {
        $name = $request->get('name', '');
        $equipment_id = $request->get('equipment_id','0');
        $work_order_type = $request->get('work_order_type','');
        $location_id = $request->get('location_id','0');
        $list = DB::table('eng_checklist as ec')
            ->leftJoin('eng_equip_group_member as em', 'em.group_id', '=', 'ec.equip_group_id')
            ->leftJoin('eng_checklist_location as el', 'el.checklist_id', '=', 'ec.id')
            ->where('ec.name', 'like', '%' . $name . '%')
            ->where('ec.work_order_type', $work_order_type)
            ->where('em.equip_id',$equipment_id)
            // ->where('el.location_id', $location_id)
            ->select(DB::raw('ec.*'))
            ->groupBy('ec.id')
            ->get();
        return Response::json($list);
    }

    public function getCheckListFromPreventive(Request $request)
    {
        $name = $request->get('name', '');
        $id = $request->get('id',0);
        $type = $request->get('type','');
        $equip_groups = array();
        if($type == 'single') {
            $list = DB::table('eng_equip_group_member')
                ->where('equip_id', $id)
                ->select(DB::raw('group_id'))
                ->get();
            for($i = 0 ; $i < count($list) ;$i++) {
                $equip_groups[$i] = $list[$i]->group_id;
            }
        }else {
            $equip_groups[0] = $id;
        }
        $property_id = $request->get('property_id','');
        $list = DB::table('eng_checklist as ec')
            ->where('ec.name', 'like', '%' . $name . '%')
            ->where('ec.property_id', $property_id)
            ->whereIn('ec.equip_group_id', $equip_groups)
            ->select(DB::raw('ec.*'))
            ->get();

        return Response::json($list);
    }

    //get part and part group
    public function getPartGroupList(Request $request)
    {
        $part_group_name = $request->get('part_group_name', '');

        $grouplist = DB::table('eng_part_group')
            ->where('name', 'like', '%' . $part_group_name . '%')
            ->select(DB::raw('*, "group" as type'))
            ->orderBy('name')
            ->get();
        $partlist = DB::table('eng_part_list')
            ->where('name', 'like', '%' . $part_group_name . '%')
            ->select(DB::raw('id, name, "single" as type'))
            ->orderBy('name')
            ->get();
        $grouplist = array_merge($grouplist->toArray(),$partlist->toArray());
        return Response::json($grouplist);
    }
    //pasted from above function because changed from group to single in preventive UI
    public function getOnlyPartList(Request $request)
    {
        $part_group_name = $request->get('part_group_name', '');

        $partlist = DB::table('eng_part_list')
            ->where('name', 'like', '%' . $part_group_name . '%')
            ->select(DB::raw('id, name, quantity, "single" as type'))
            ->orderBy('name')
            ->get();
        return Response::json($partlist);
    }

    // get staff amd get staff group
    public function getStaffGroupList(Request $request)
    {
        $staff_group_name = $request->get('staff_group_name', '');
        $property_id  = $request->get('property_id','');
        $user = $request->get('user_id','');
        $property_list = CommonUser::getPropertyIdsByJobroleids($user);
        $grouplist = DB::table('common_user_group as cg')
            ->leftJoin('common_user_group_members as cgm', 'cg.id', '=', 'cgm.group_id')
            ->leftJoin('common_users as cu', 'cgm.user_id', '=', 'cu.id')
            ->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
            ->where('cg.name', 'like', '%' . $staff_group_name . '%')
            ->where('cg.use_for_eng_teams', 'Y')
            ->select(DB::raw('cg.id, sum(jr.cost) as cost, cg.name, "group" as type, "Team" as label'))
            ->orderBy('cg.name')
            ->groupBy('cg.id')
            ->get();

         //get user group id from property setting
        $eng_dept = DB::table('property_setting')
            ->where('settings_key', 'eng_dept_id')
            ->where('property_id', $property_id)
            ->first();

        $stafflist = DB::table('common_users as cu')
            ->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
            ->leftJoin('common_department as de','cu.dept_id','=','de.id')
            ->whereRaw("CONCAT(cu.first_name, ' ', cu.last_name) like '%" . $staff_group_name . "%'")
         //   ->where('de.property_id', $property_id)
            ->whereIn('de.property_id', $property_list)
            ->groupBy('cu.id')
            ->where('cu.deleted', 0)
            ->where('cu.dept_id', $eng_dept->value)
            ->select(DB::raw('cu.id, jr.cost as cost, CONCAT_WS(" ", cu.first_name, cu.last_name) as name, "single" as type, "Individual" as label, cu.active_status'))
            ->orderBy('name')
            ->get();
        $grouplist = array_merge($grouplist->toArray(),$stafflist->toArray());

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $grouplist;

        return Response::json($ret);
    }

    public function getOnlyPartGroupList(Request $request)
    {
        $part_group_name = $request->get('part_group_name', '');

        $grouplist = DB::table('eng_part_group')
            ->where('name', 'like', '%' . $part_group_name . '%')
            ->select(DB::raw('*'))
            ->orderBy('name')
            ->get();

        return Response::json($grouplist);
    }

    public function getPartNameList(Request $request)
    {
        $part_name = $request->get('part_name', '');
        $property_id = $request->get('property_id','0');

        $list = DB::table('eng_part_list')
            ->where('name', 'like', '%' . $part_name . '%')
            ->where('property_id', $property_id)
            ->select(DB::raw('*'))
            ->get();
        return Response::json($list);
    }

    public function getSupplierList(Request $request)
    {
        $supplier = $request->get('supplier', '');

        $list = DB::table('eng_supplier')
            ->where('supplier', 'like', '%' . $supplier . '%')
            ->select(DB::raw('*'))
            ->get();
        return Response::json($list);
    }

    public function getStatusList(Request $request)
    {
        $list = DB::table('eng_equip_status')
            ->select(DB::raw('*'))
            ->get();
        return Response::json($list);
    }

    public function getEquipmentList(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $property_id = $request->get('property_id', 4);

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');

        $searchtext = $request->get('searchtext', '');
        $location_ids = $request->get('location_ids', '');
        $department_ids = $request->get('department_ids', '');
        $status_ids = $request->get('status_ids', '');
        $equip_group_ids = $request->get('equip_group_ids', '');
        $equip_ids = $request->get('equip_ids', '');


        if ($pageSize < 0)
            $pageSize = 20;

        $ret = array();
        $query = DB::table('eng_equip_list as el')
            ->leftJoin('eng_equip_group_member as eegm', 'el.id', '=', 'eegm.equip_id')
            ->leftJoin('eng_equip_group as eg', 'eegm.group_id', '=', 'eg.id')
            ->leftJoin('eng_part_group as epg', 'el.part_group_id', '=', 'epg.id')
            ->leftJoin('common_department as cd', 'el.dept_id', '=', 'cd.id')
            ->leftJoin('eng_equip_status as et', 'el.status_id', '=', 'et.id')
            ->leftJoin('eng_external_maintenance as em', 'el.external_maintenance_id', '=', 'em.id')
            ->leftJoin('services_location as sl', 'el.location_group_member_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
            ->leftJoin('services_location as sl2', 'el.sec_loc_id', '=', 'sl2.id')
            ->leftJoin('services_location_type as slt2', 'sl2.type_id', '=', 'slt2.id')
            ->leftJoin('eng_supplier as es', 'el.supplier_id', '=', 'es.id');

        // $query->whereRaw(sprintf("DATE(el.maintenance_date) >= '%s' and DATE(el.maintenance_date) <= '%s'", $start_date, $end_date));
        $query->where('el.property_id',$property_id);


        if($searchtext != '') {
            $query->where(function ($sub_query) use ($searchtext) {
                $sub_query->where('el.name','like','%'.$searchtext.'%');
                $sub_query->orWhere('el.id','like','%'.$searchtext.'%');
                $sub_query->orWhere('eg.name','like','%'.$searchtext.'%');
                $sub_query->orWhere('et.status','like','%'.$searchtext.'%');
                $sub_query->orWhere('cd.department','like','%'.$searchtext.'%');
                $sub_query->orWhere('el.manufacture','like','%'.$searchtext.'%');
                $sub_query->orWhere('es.supplier','like','%'.$searchtext.'%');
                $sub_query->orWhere('sl.name','like','%'.$searchtext.'%');
                $sub_query->orWhere('slt.type','like','%'.$searchtext.'%');
                $sub_query->orWhere('el.equip_id','like','%'.$searchtext.'%');
            });
        }

        if( !empty($location_ids) )
        {
            $location_ids = explode(',', $location_ids);
            $query->whereIn('el.location_group_member_id', $location_ids);
        }

        if( !empty($department_ids) )
        {
            $department_ids = explode(',', $department_ids);
            $query->whereIn('el.dept_id', $department_ids);
        }

        if( !empty($status_ids) )
        {
            $status_ids = explode(',', $status_ids);
            $query->whereIn('el.status_id', $status_ids);
        }

        if( !empty($equip_group_ids) )
        {
            $equip_group_ids = explode(',', $equip_group_ids);
            $query->whereIn('eegm.group_id', $equip_group_ids);
        }

        if( !empty($equip_ids) )
        {
            $equip_ids = explode(',', $equip_ids);
            $query->whereIn('el.equip_id', $equip_ids);
        }


        $data_query = clone $query;
        $data_list = $data_query
            ->groupBY('el.id')
            ->orderBy($orderby, $sort)
            ->select(DB::raw("el.*,  slt.type as location_group_type, cd.department, eg.name as group_name, epg.name as part_group_name, 
                            es.supplier,em.email as maintenance_email, em.external_maintenance as external_maintenance_company, et.status,
                            sl.name as location_name, slt.type as location_type,
                            sl2.name as sec_location_name, slt2.type as sec_location_type"))
            ->skip($skip)->take($pageSize)
            ->get();


        for( $i = 0 ; $i < count($data_list ) ; $i++) {
            $equip = DB::table('eng_equip_group_member as em')
                ->leftJoin('eng_equip_group as eg', 'em.group_id', '=', 'eg.id')
                ->where('em.equip_id',$data_list[$i]->id)
                ->select(DB::raw("em.group_id as equip_group_id, em.equip_id, eg.name"))
                ->get();

            $data_list[$i]->equipment_group = $equip;
            $equip_part = DB::table('eng_equip_part_group_member as em')
                ->where('em.equip_id',$data_list[$i]->id)
                ->select(DB::raw("em.part_group_id , em.equip_id, em.type"))
                ->get();
            $equip_part_copy = array();
                for($j = 0 ; $j < count($equip_part) ; $j++ ) {
                    $equip_part_copy[$j] = clone $equip_part[$j];
                    $type = $equip_part[$j]->type;
                    $group_id = $equip_part[$j]->part_group_id;
                    if($type == 'group') {
                        $data = DB::table('eng_part_group')
                            ->where('id',$group_id)
                            ->select(DB::raw("name"))
                            ->first();
                        $equip_part_copy[$j]->name = $data->name;
                    }
                    if($type == 'no group') {
                        $data = DB::table('eng_part_list')
                            ->where('id',$group_id)
                            ->select(DB::raw("name"))
                            ->first();
                        $equip_part_copy[$j]->name = $data->name;
                    }
                }

            $data_list[$i]->part_group = $equip_part_copy;
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    public function getEquipmentInformList(Request $request){
        $equip_id = $request->get('equip_id', 0);

        $ret = array();
        $filelist = DB::table('eng_equip_file')
            ->where('equip_id', $equip_id)
            ->select(DB::raw("*"))
            ->get();
        $ret['filelist'] = $filelist;
        return Response::json($ret);
    }

    public function getPartList(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");


        $property_id = $request->get('property_id', 4);



        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $searchoption = $request->get('searchoption','');
        $searchtext = $request->get('searchtext', '');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');

        if ($pageSize < 0)
            $pageSize = 20;

        $ret = array();
        $query = DB::table('eng_part_list as el')
//            ->leftJoin('eng_equip_group as eg', 'el.equip_group_id', '=', 'eg.id')
            ->leftJoin('eng_part_group as epg', 'el.part_group_id', '=', 'epg.id')
//            ->leftJoin('common_department as cd', 'el.dept_id', '=', 'cd.id')
//            ->leftJoin('eng_equip_status as et', 'el.status_id', '=', 'et.id')
//            ->leftJoin('eng_external_maintenance as em', 'el.external_maintenance_id', '=', 'em.id')
            ->leftJoin('eng_supplier as es', 'el.supplier_id', '=', 'es.id');

//        $query->whereRaw(sprintf("DATE(el.maintenance_date) >= '%s' and DATE(el.maintenance_date) <= '%s'", $start_date, $end_date));
        $query->where('el.property_id',$property_id);
        if($searchtext != '') {
            if($searchoption == 'Manufacture') {
                $query->where('el.manufacture','like','%'.$searchtext.'%');
            }
            if($searchoption == 'Supplier') {
                $query->where('es.supplier','like','%'.$searchtext.'%');
            }
        }


        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->select(DB::raw("el.*, epg.name as part_group_name,es.supplier"))
            ->skip($skip)->take($pageSize)
            ->get();

        for( $i = 0 ; $i < count($data_list ) ; $i++) {
            $part_group = DB::table('eng_part_group_member as em')
                ->leftJoin('eng_part_group as eg', 'eg.id', '=', 'em.part_group_id')
                ->where('em.part_id',$data_list[$i]->id)
                ->select(DB::raw("em.part_group_id , em.part_id, eg.name"))
                ->get();
            $data_list[$i]->part_group = $part_group;
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    public function CreateEquipment(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date('Y-m-d H:i:s');
        $base64_string = $request->get('image_src','') ;
        $image_url = $request->get('image_url','');
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/equip/';
        if(!file_exists($output_file)) {
            mkdir($output_file, 0777);
        }
        if($image_url == '') $image_url = 'default.png';
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/equip/' . $image_url;
        if($base64_string !='') {
            $ifp = fopen($output_file, "wb");
            $data = explode(',', $base64_string);
            fwrite($ifp, base64_decode($data[1]));
            fclose($ifp);
        }
        $ret =array();
        if(!EquipmentList::where('equip_id', $request->get('equip_id', ''))->exists() ) {
            $equipment = new EquipmentList();
            $equipment->property_id = $request->get('property_id','');
            $equipment->name = $request->get('name', '');
            //equip_id = 1001-RM-101-WM,  1001 = id, RM = Equipment Group Code
            //101 = Room no, Wm = part group code
            $equipment->equip_id = $request->get('equip_id','');
            $equipment->description = $request->get('description', '');
            $equipment->critical_flag = $request->get('critical_flag', '0');
            $equipment->external_maintenance = $request->get('external_maintenance', '0');
            $equipment->external_maintenance_id = $request->get('external_maintenance_id', '0');
            $equipment->dept_id = $request->get('dept_id', '0');
            $equipment->life = $request->get('life', '0');
            $equipment->life_unit = $request->get('life_unit', 'days');
            $equipment->equip_group_id = $request->get('equip_group_id', '0');
            $equipment->part_group_id = $request->get('part_group_id', '0');
            $equipment->location_group_member_id = $request->get('location_group_member_id', 0);
            $equipment->sec_loc_id = $request->get('sec_loc_id', 0);
            $equipment->purchase_cost = $request->get('purchase_cost', '0');
            $equipment->purchase_date = $request->get('purchase_date', '0000-00-00');
            $equipment->manufacture = $request->get('manufacture', '');
            $equipment->status_id = $request->get('status_id', '0');
            $equipment->model = $request->get('model', '');
            $equipment->barcode = $request->get('barcode', '0');
            $equipment->warranty_start = $request->get('warranty_start', '0000-00-00');
            $equipment->warranty_end = $request->get('warranty_end', '0000-00-00');
            $equipment->supplier_id = $request->get('supplier_id', '');
            $equipment->image_url = '/uploads/equip/' . $image_url;
            $equipment->maintenance_date = $cur_time;

            $equipment->save();
            $equip_id = $equipment->id;

            $equipment_group = $request->get('equipment_group',[]);
            for($i = 0 ; $i < count($equipment_group) ;$i++) {
                $group_id = $equipment_group[$i]['equip_group_id'];
                if (DB::table('eng_equip_group_member')->where('equip_id', $equip_id)->where('group_id', $group_id)->exists())
                    continue;
                else
                    DB::table('eng_equip_group_member')->insert(['equip_id' => $equip_id, 'group_id' => $group_id]);
            }

            $part_group = $request->get('part_group',[]);
            for($i = 0 ; $i < count($part_group) ;$i++) {
                $part_group_id = $part_group[$i]['part_group_id'];
                $type = $part_group[$i]['type'];
                if (DB::table('eng_equip_part_group_member')
                        ->where('equip_id', $equip_id)
                        ->where('part_group_id', $part_group_id)
                        ->where('type', $type)
                        ->exists())
                    continue;
                else
                    DB::table('eng_equip_part_group_member')->insert(['equip_id' => $equip_id,
                                                                     'part_group_id' => $part_group_id,
                                                                     'type' => $type]);
            }
            $ret['id'] = $equipment->id;
        }
        return Response::json($ret);
    }

    public function createEquipmentFile(Request $request) {
        $base64_string = $request->get('image_src','') ;
        $image_url = $request->get('image_url','');
        $output_dir = $_SERVER["DOCUMENT_ROOT"] . '/uploads/equip/';

        if(!file_exists($output_dir)) {
            mkdir($output_dir, 0777, true);
        }

        $filekey = 'files';

        $id = $request->get('equip_id', '');

        $ret = array();

		$fileCount = count($_FILES[$filekey]["name"]);
		for ($i = 0; $i < $fileCount; $i++)
		{
			$fileName = $_FILES[$filekey]["name"][$i];
			$ext = pathinfo($fileName, PATHINFO_EXTENSION);
			$filename1 = "equip_file_" . $id . '_' . $i . '_' . date('Y-m-d-H-i-s.') . $ext;

			$dest_path = $output_dir . $filename1;
            move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);

            $ret['dest_path'] = $_FILES[$filekey]["tmp_name"];

            $file = new EquipmentFile();
            $file->equip_id = $request->get('equip_id', '');
            $file->name = $request->get('filename', '');
            $file->description = $request->get('filedescription', '');
            $file->path = '/uploads/equip/' . $filename1;

            $file->save();
        }


        $ret['id'] = $id;

        return Response::json($ret);
    }

    public function CreatePart(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date('Y-m-d H:i:s');
        $base64_string = $request->get('image_src','') ;
        $image_url = $request->get('image_url','');
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/part/';
        if(!file_exists($output_file)) {
            mkdir($output_file, 0777);
        }
        if($image_url == '') $image_url = 'default.png';
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/part/' . $image_url;
        if($base64_string !='') {
            $ifp = fopen($output_file, "wb");
            $data = explode(',', $base64_string);
            fwrite($ifp, base64_decode($data[1]));
            fclose($ifp);
        }

        $ret = array();
        if(!PartList::where('name', $request->get('name', ''))->exists() ) {
                    $part = new PartList();
                    $part->property_id = $request->get('property_id', '');
                    $part->name = $request->get('name', '');
                    //equip_id = 1001-RM-101-WM,  1001 = id, RM = Equipment Group Code
                    //101 = Room no, Wm = part group code
                    $part->part_id = $request->get('part_id', '');
                    $part->description = $request->get('description', '');
        //        $part->critical_flag = $request->get('critical_flag', '0');
        //        $part->external_maintenance = $request->get('external_maintenance', '0');
        //        $part->external_maintenance_id = $request->get('external_maintenance_id', '0');
        //        $part->dept_id = $request->get('dept_id', '0');
        //        $part->life = $request->get('life', '0');
        //        $part->life_unit = $request->get('life_unit', 'days');

                    $part->purchase_cost = $request->get('purchase_cost', '0');
                    $part->purchase_date = $request->get('purchase_date', '0000-00-00');
                    $part->manufacture = $request->get('manufacture', '');
        //        $part->status_id = $request->get('status_id', '0');
                    $part->model = $request->get('model', '');
                    $part->barcode = $request->get('barcode', '0');
                    $part->warranty_start = $request->get('warranty_start', '0000-00-00');
                    $part->warranty_end = $request->get('warranty_end', '0000-00-00');
                    $part->supplier_id = $request->get('supplier_id', '');
                    $part->quantity = $request->get('quantity', '0');
                    $part->minquantity = $request->get('minquantity', '0');
                    $part->image_url = '/uploads/part/' . $image_url;;
        //        $part->maintenance_date = $cur_time;

                    $part->save();
                    $part_id = $part->id;
                    $part_group = $request->get('part_group',[]);
                    for($i = 0 ; $i < count($part_group) ;$i++) {
                        $part_group_id = $part_group[$i]['part_group_id'];
                        if (DB::table('eng_part_group_member')
                            ->where('part_id', $part_id)
                            ->where('part_group_id', $part_group_id)
                            ->exists())
                            continue;
                        else
                            DB::table('eng_part_group_member')->insert(['part_id' => $part_id,
                                'part_group_id' => $part_group_id]);
                    }
                    $ret['id'] = $part->id;
        }
        return Response::json($ret);
    }

    public function getImage(Request $request) {
        $image_url = $request->get("image_url",'');
        if($image_url !='') {
            $path = $_SERVER["DOCUMENT_ROOT"] . $image_url;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            return Response::json($base64);
        }else {
            return Response::json('');
        }
    }

    public function updateEquipment(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date('Y-m-d H:i:s');
        $id = $request->get('id',0);
        //delete image
        $equipment =  EquipmentList::find($id);
        $image_url = $equipment->image_url;
        $output_file = $_SERVER["DOCUMENT_ROOT"] .$image_url;
        if(file_exists($output_file) && $image_url != '') unlink($output_file);
        //insert image and update image url
        $base64_string = $request->get('image_src','') ;
        $image_url = $request->get('image_url','');
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/equip/';
        if(!file_exists($output_file)) {
            mkdir($output_file, 0777);
        }
        if($image_url == '') $image_url = 'default.png';
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/equip/' . $image_url;
        if($base64_string !='') {
            $ifp = fopen($output_file, "wb");
            $data = explode(',', $base64_string);
            fwrite($ifp, base64_decode($data[1]));
            fclose($ifp);
        }

        $equipment =  EquipmentList::find($id);

        $equipment->name = $request->get('name', '');
        $equipment->description = $request->get('description', '');
        $equipment->critical_flag = $request->get('critical_flag', '0');
        $equipment->equip_id = $request->get('equip_id','');
        $equipment->external_maintenance = $request->get('external_maintenance', '');
        $equipment->external_maintenance_id = $request->get('external_maintenance_id','0');
        $equipment->dept_id = $request->get('dept_id', '0');
        $equipment->life = $request->get('life', '0');
        $equipment->life_unit = $request->get('life_unit', 'days');
        $equipment->equip_group_id = $request->get('equip_group_id', '0');
        $equipment->part_group_id = $request->get('part_group_id', '0');
        $equipment->location_group_member_id = $request->get('location_group_member_id', '0');
        $equipment->sec_loc_id = $request->get('sec_loc_id', '0');
        $equipment->purchase_cost = $request->get('purchase_cost', '0');
        $equipment->purchase_date = $request->get('purchase_date', '0000-00-00');
        $equipment->manufacture = $request->get('manufacture', '');
        $equipment->status_id = $request->get('status_id', '0');
        $equipment->model = $request->get('model', '');
        $equipment->barcode = $request->get('barcode', '0');
        $equipment->warranty_start = $request->get('warranty_start', '0000-00-00');
        $equipment->warranty_end = $request->get('warranty_end', '0000-00-00');
        $equipment->supplier_id = $request->get('supplier_id', '');
        $equipment->image_url = '/uploads/equip/' . $image_url;
        $equipment->property_id = $request->get('property_id','0');
        $equipment->maintenance_date = $cur_time;

        $equipment->save();
        $equip_id = $id;
        $equipment_group = $request->get('equipment_group',[]);
        DB::table('eng_equip_group_member')->where('equip_id', $equip_id)->delete();
        DB::table('eng_equip_part_group_member')->where('equip_id', $equip_id)->delete();

        for($i = 0 ; $i < count($equipment_group) ;$i++) {
            $group_id = $equipment_group[$i]['equip_group_id'];
            DB::table('eng_equip_group_member')->insert(['equip_id' => $equip_id, 'group_id' => $group_id]);
        }
        $part_group = $request->get('part_group',[]);
        for($i = 0 ; $i < count($part_group) ;$i++) {
            $part_group_id = $part_group[$i]['part_group_id'];
            $type = $part_group[$i]['type'];
            if (DB::table('eng_equip_part_group_member')
                ->where('equip_id', $equip_id)
                ->where('part_group_id', $part_group_id)
                ->where('type', $type)
                ->exists())
                continue;
            else
                DB::table('eng_equip_part_group_member')->insert(['equip_id' => $equip_id,
                    'part_group_id' => $part_group_id,
                    'type' => $type]);
        }
        $ret =array();
        $ret['id'] = $equipment->id;
        return Response::json($ret);
    }

    public function updatePart(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date('Y-m-d H:i:s');
        $id = $request->get('id',0);
        //delete image
        $part =  PartList::find($id);
        $image_url = $part->image_url;
        $output_file = $_SERVER["DOCUMENT_ROOT"] .$image_url;
        if(file_exists($output_file) && $image_url != '') unlink($output_file);
        //insert image and update image url
        $base64_string = $request->get('image_src','') ;
        $image_url = $request->get('image_url','');
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/part/';
        if(!file_exists($output_file)) {
            mkdir($output_file, 0777);
        }
        if($image_url == '') $image_url = 'default.png';
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/part/' . $image_url;
        if($base64_string !='') {
            $ifp = fopen($output_file, "wb");
            $data = explode(',', $base64_string);
            fwrite($ifp, base64_decode($data[1]));
            fclose($ifp);
        }

        $part =  PartList::find($id);

        $part->name = $request->get('name', '');
        $part->description = $request->get('description', '');
//        $part->critical_flag = $request->get('critical_flag', '0');
        $part->part_id = $request->get('part_id','');
//        $part->external_maintenance = $request->get('external_maintenance', '');
//        $part->external_maintenance_id = $request->get('external_maintenance_id','0');
//        $part->dept_id = $request->get('dept_id', '0');
//        $part->life = $request->get('life', '0');
//        $part->life_unit = $request->get('life_unit', 'days');

        $part->purchase_cost = $request->get('purchase_cost', '0');
        $part->purchase_date = $request->get('purchase_date', '0000-00-00');
        $part->manufacture = $request->get('manufacture', '');
//        $part->status_id = $request->get('status_id', '0');
        $part->model = $request->get('model', '');
        $part->barcode = $request->get('barcode', '0');
        $part->warranty_start = $request->get('warranty_start', '0000-00-00');
        $part->warranty_end = $request->get('warranty_end', '0000-00-00');
        $part->supplier_id = $request->get('supplier_id', '');
        $part->image_url = '/uploads/part/' . $image_url;
        $part->property_id = $request->get('property_id','0');
//        $part->maintenance_date = $cur_time;
        $part->quantity = $request->get('quantity', '0');
        $part->minquantity = $request->get('minquantity', '0');

        $part->save();
        $part_id = $id;
        DB::table('eng_part_group_member')->where('part_id', $part_id)->delete();
        $part_group = $request->get('part_group',[]);
        for($i = 0 ; $i < count($part_group) ;$i++) {
            $part_group_id = $part_group[$i]['part_group_id'];
            if (DB::table('eng_part_group_member')
                ->where('part_id', $part_id)
                ->where('part_group_id', $part_group_id)
                ->exists())
                continue;
            else
                DB::table('eng_part_group_member')->insert(['part_id' => $part_id,
                    'part_group_id' => $part_group_id]);
        }
        $ret =array();
        $ret['id'] = $part->id;
        return Response::json($ret);
    }

    public function deleteEquipment(Request $request){
        $id = $request->get('id', '0');
        $equipment =  EquipmentList::find($id);
        $image_url = $equipment->image_url;
        $output_file = $_SERVER["DOCUMENT_ROOT"] .$image_url;
        if(file_exists($output_file)&&$image_url!='') unlink($output_file);

        DB::table('eng_equip_list')
            ->where('id', $id)
            ->delete();
        $equip_id = $id;
        //delete file
        $filedata = DB::table('eng_equip_file')->where('equip_id', $equip_id)->get();
        for($i =0; $i < count($filedata); $i++) {
            $image_url = $filedata->path;
            $output_file = $_SERVER["DOCUMENT_ROOT"] .$image_url;
            if(file_exists($output_file)) unlink($output_file);
        }
        DB::table('eng_equip_file')->where('equip_id', $equip_id)->delete();
        DB::table('eng_equip_group_member')->where('equip_id', $equip_id)->delete();
        DB::table('eng_equip_part_group_member')->where('equip_id', $equip_id)->delete();
        return Response::json('200');
    }

    public function delEquipmentFile(Request $request){
        $id = $request->get('id', '0');
        $file =  EquipmentFile::find($id);
        $image_url = $file->path;
        $output_file = $_SERVER["DOCUMENT_ROOT"] .$image_url;
        if($image_url != '' && file_exists($output_file)) unlink($output_file);

        DB::table('eng_equip_file')
            ->where('id', $id)
            ->delete();
        return Response::json('200');
    }

    public function delEquipmentImage(Request $request){
        $id = $request->get('id', '0');
        $file =  EquipmentList::find($id);
        $image_url = $file->image_url;
        $output_file = $_SERVER["DOCUMENT_ROOT"] .$image_url;
        if($image_url != '' && file_exists($output_file)) unlink($output_file);

    //    DB::table('eng_equip_file')
    //        ->where('id', $id)
    //        ->delete();

        $file->image_url = '/uploads/equip/default.png' ;
        $file->save();
        return Response::json('200');
    }


    public function deletePart(Request $request){
        $id = $request->get('id', '0');
        $part =  PartList::find($id);
        $image_url = $part->image_url;
        $output_file = $_SERVER["DOCUMENT_ROOT"] .$image_url;
        if($image_url != '' && file_exists($output_file)) unlink($output_file);

        DB::table('eng_part_list')
            ->where('id', $id)
            ->delete();
        DB::table('eng_part_group_member')->where('part_id', $id)->delete();
        return Response::json('200');
    }

    public function createGroup(Request $request) {
        $name = $request->get('name','');
        $description = $request->get('description','');
        $code = $request->get('code','');

        if($name != '' ) {
            try {
                $equipment = new EquipmentGroup();
                $equipment->name = $name;
                $equipment->description = $description;
                $equipment->code = $code;
                $equipment->save();
                return Response::json('200');
            }catch (QueryException $e){
                $errorCode = $e->errorInfo[1];
                if($errorCode == 1062){
                    // houston, we have a duplicate entry problem
                }
                return Response::json($errorCode);
            }
        }else {
            return Response::json('400');
        }
    }

    public function createPartGroup(Request $request) {
        $name = $request->get('name','');
        $description = $request->get('description','');
        $code = $request->get('code','');
        if($name != '' ) {
            try {
                $equipment = new EquipmentPartGroup();
                $equipment->name = $name;
                $equipment->description = $description;
                $equipment->code = $code;

                $equipment->save();
                return Response::json('200');
            }catch (QueryException $e) {
                $errorCode = $e->errorInfo[1];
                return Response::json($errorCode);
            }
        }else {
            return Response::json('400');
        }
    }

    public function createSupplier(Request $request) {
        $supplier = $request->get('supplier','');
        $contact = $request->get('contact','');
        $phone = $request->get('phone','');
        $email = $request->get('email','');
        $url = $request->get('url','');

        if($supplier != '' ) {
            try {
                $equipment = new EquipmentSupplier();
                $equipment->supplier = $supplier;
                $equipment->contact = $contact;
                $equipment->phone = $phone;
                $equipment->email = $email;
                $equipment->url = $url;
                $equipment->save();
                return Response::json('200');
            }catch (QueryException $e) {
                $erroCode = $e->errorInfo[1];
                return Response::json($erroCode);
            }
        }else {
            return Response::json('400');
        }
    }

    public function createMaintenance(Request $request) {
        $external_maintenance = $request->get('external_maintenance','');
        $contact = $request->get('contact','');
        $phone = $request->get('phone','');
        $email = $request->get('email','');
        $url = $request->get('url','');

        if($external_maintenance != '' ) {
            try {
                $equipment = new EquipmentExternalMaintenance();
                $equipment->external_maintenance = $external_maintenance;
                $equipment->contact = $contact;
                $equipment->phone = $phone;
                $equipment->email = $email;
                $equipment->url = $url;
                $equipment->save();
                return Response::json('200');
            }catch (QueryException $e) {
                $erroCode = $e->errorInfo[1];
                return Response::json($erroCode);
            }
        }else {
            return Response::json('400');
        }
    }


    public function importExcel(Request $request) {
        $base64_string = $request->get('src','') ;
        $image_url = $request->get('name','');
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/equip/';
        if(!file_exists($output_file)) {
            mkdir($output_file, 0777, true);
        }
        if($image_url != '') {
            $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/equip/' . $image_url;
            if ($base64_string != '') {
                $ifp = fopen($output_file, "wb");
                $data = explode(',', $base64_string);
                fwrite($ifp, base64_decode($data[1]));
                fclose($ifp);
            }
            try {
                $this->parseExcelFile($output_file, $count);
                return Response::json($count);
            }catch (QueryException $e) {
                $errCode = $e->errorInfo[1];
                return Response::json($errCode);
            }
        }else {
            return Response::json('error');
        }
    }

    public function importExcelPart(Request $request) {
        $base64_string = $request->get('src','') ;
        $image_url = $request->get('name','');
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/part/';
        if(!file_exists($output_file)) {
            mkdir($output_file, 0777);
        }
        if($image_url != '') {
            $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/part/' . $image_url;
            if ($base64_string != '') {
                $ifp = fopen($output_file, "wb");
                $data = explode(',', $base64_string);
                fwrite($ifp, base64_decode($data[1]));
                fclose($ifp);
            }
            try {
                $this->parseExcelFilePart($output_file, $count);
                return Response::json($count);
            }catch(QueryException $e) {
                $errCode = $e->errorInfo[1];
                return Response::json($errCode);
            }
        }else {
            return Response::json('error');
        }
    }

    public function getId($table, $param, $param1) {
        if($table == 'eng_external_maintenance') {
            $list = DB::table('eng_external_maintenance')
                ->whereRaw("external_maintenance like '".$param."' " )
                ->select(DB::raw('*'))
                ->first();
            if(!empty($list)) {
                return $list->id;
            }else {
               return  $id = DB::table('eng_external_maintenance')-> insertGetId(array(
                    'external_maintenance' => $param,
                    'contact' => $param,
                ));
            }
        }
        if($table == 'common_department') {
            $list = DB::table('common_department')
                ->whereRaw("department like  '".$param."' " )
                ->select(DB::raw('*'))
                ->first();
            if(!empty($list)) {
                return $list->id;
            }else {
                return 0;
            }
        }

        if($table == 'eng_equip_group') {
            $list = DB::table('eng_equip_group')
                ->whereRaw("name like '".$param."' " )
                ->select(DB::raw('*'))
                ->first();
            if(!empty($list)) {
                return $list->id;
            }else {
                return  $id = DB::table('eng_equip_group')-> insertGetId(array(
                    'name' => $param,
                    'description' => $param,
                    'code' => $param1,
                ));
            }
        }

        if($table == 'eng_part_group') {
            $list = DB::table('eng_part_group')
                ->whereRaw("name like '".$param."' " )
                ->select(DB::raw('*'))
                ->first();
            if(!empty($list)) {
                return $list->id;
            }else {
                return  $id = DB::table('eng_part_group')-> insertGetId(array(
                    'name' => $param,
                    'description' => $param,
                    'code' => $param1,
                ));
            }
        }

        if($table == 'common_property') {
            $list = DB::table('common_property')
                ->whereRaw("name like '".$param."' ")
                ->select(DB::raw('*'))
                ->first();
            if(!empty($list)) {
                return $list->id;
            }else {
               return 0;
            }
        }

        if($table == 'eng_equip_status') {
            $list = DB::table('eng_equip_status')
                ->whereRaw("status like '".$param."' " )
                ->select(DB::raw('*'))
                ->first();
            if(!empty($list)) {
                return $list->id;
            }else {
                return 0;
            }
        }

        if($table == 'eng_supplier') {
            $list = DB::table('eng_supplier')
                ->whereRaw("supplier like  '".$param."' " )
                ->select(DB::raw('*'))
                ->first();
            if(!empty($list)) {
                return $list->id;
            }else {
                return 0;
            }
        }

    }

    public function getLocationid($type_name, $filter, $pro_id)
    {
        $ret = array();
        $location_array = array(
            array('table' => 'common_property', 'type_name' => 'Property', 'field_name' => 'name', 'property_id' => 'lgm.type_id'),
            array('table' => 'common_building', 'type_name' => 'Building', 'field_name' => 'name', 'property_id' => 'lc.property_id'),
            array('table' => 'common_floor', 'type_name' => 'Floor', 'field_name' => 'floor', 'property_id' => 'cb.property_id'),
            array('table' => 'common_room', 'type_name' => 'Room', 'field_name' => 'room', 'property_id' => 'cb.property_id'),
            array('table' => 'common_cmn_area', 'type_name' => 'Common Area', 'field_name' => 'name', 'property_id' => 'cb.property_id'),
            array('table' => 'common_admin_area', 'type_name' => 'Admin Area', 'field_name' => 'name', 'property_id' => 'cb.property_id'),
            array('table' => 'common_outdoor', 'type_name' => 'Outdoor', 'field_name' => 'name', 'property_id' => 'lc.property_id'),
        );
        $i = 0 ;
        if($type_name == 'Property' ) $i = 0;
        if($type_name == 'Building' ) $i = 1;
        if($type_name == 'Floor' ) $i = 2;
        if($type_name == 'Room' ) $i = 3;
        if($type_name == 'Common Area' ) $i = 4;
        if($type_name == 'Admin Area' ) $i = 5;
        if($type_name == 'Outdoor' ) $i = 6;

            $table = $location_array[$i]['table'];
            $type_name = $location_array[$i]['type_name'];
            $field_name = $location_array[$i]['field_name'];
            $property_id = $location_array[$i]['property_id'];

            $query = DB::table('services_location_group_members as lgm')
                ->leftJoin('services_location_group as lg', 'lgm.location_grp', '=', 'lg.id')
                ->leftJoin($table . ' as lc', 'lc.id', '=', 'lgm.type_id' );

            switch( $i )
            {
                case 0:	// Property
                    break;
                case 1:	// Building
                    break;
                case 2:	// Floor
                    $query = $query->join('common_building as cb', 'lc.bldg_id', '=', 'cb.id');
                    break;
                case 3:	// Room
                    $query = $query->join('common_floor as cf', 'lc.flr_id', '=', 'cf.id')
                        ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id');
                    break;
                case 4:	// Common Area
                case 5:	// Common Admin Adrea
                    $query = $query->join('common_floor as cf', 'lc.floor_id', '=', 'cf.id')
                        ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id');
                    break;
                case 5:	// Common Outdoor
                    break;

            }
            $locationlist = $query->where('lgm.type', $type_name)
                ->where('lc.' . $field_name, 'like', $filter)
                //->where('property_id', $pro_id)
                ->select(['lgm.*', 'lg.id as lg_id', 'lc.' . $field_name . ' as name', $property_id . ' as property_id'])
                ->first();
                // ->get();
                
        if(!empty($locationlist))  return $locationlist->id;
        else return 0;
    }

    public function parseExcelFile($path,&$count)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date('Y-m-d H:i:s');
        $rows = Excel::toArray(new CommonImportExcel, $path);
        $rows = [$rows[0]];
        $count = $rows;
        for($i = 0; $i < count($rows); $i++ )
        {
            foreach( $rows[$i] as $key => $data )
            {
                //echo jswon_encode($data);                    
                $inform = array();
                $inform['name'] = $data['equipment_name'];
                $inform['equip_id'] = $data['id'];
                $codes = array();
                if(!empty($data['id'])) {
                    $codes = explode("-", $data['id']);
                }
                $inform['description'] = $data['equipment_name'];
                $critical = $data['critical'];
                if(strtolower($critical) == 'yes') $inform['critical_flag'] = 1;
                if(strtolower($critical) == 'no') $inform['critical_flag'] = 0;
                $external_maintenance  = $data['external_maintenance'] ;
                if(strtolower($external_maintenance) == 'yes') $inform['external_maintenance'] = 1;
                if(strtolower($external_maintenance) == 'no') $inform['external_maintenance'] = 0;
                //get external_maintenance_id  from external maintenance company
                $external_maintenance_company = $data['external_maintenance_company'] ;
                $inform['external_maintenance_id'] = $this->getId('eng_external_maintenance', $external_maintenance_company, '');
                //get dept_id from Department
                $department = $data['department'];
                $inform['dept_id'] = $this->getId('common_department', $department, '');
                $inform['life'] = $data['life'];
                $inform['life_unit'] = $data['life_unit'];
                //get group id from Equipment group
                $equipment_group = $data['equipment_group'];
                $inform['equip_group_id'] = $this->getId('eng_equip_group', $equipment_group, $codes[1]);
                //get part group id from parts group
                $part_group = $data['parts_group'];
                $inform['part_group_id'] = $this->getId('eng_part_group', $part_group, $codes[3]);
                //get group id from Equipment group
                $property = $data['property'];
                $inform['property_id'] = $this->getId('common_property', $property, '');
                //get Location id from location
                $location = $data['location'];
                $locations  = explode(" ", $location);
                $inform['location_group_member_id'] = $this->getLocationid($locations[0], $locations[1], $inform['property_id']);
                $inform['purchase_cost'] = $data['cost'];
                $inform['purchase_date'] =  date('Y-m-d',strtotime($data['purchase_date']));
                $inform['manufacture'] = $data['manufacturer'];
                //get status id from status
                $status = $data['status'];
                $inform['status_id'] = $this->getId('eng_equip_status', $status, '');
                $inform['model'] = $data['model'];
                $inform['barcode'] = $data['barcode'];
                $inform['warranty_start'] =  date('Y-m-d',strtotime($data['warranty_start']));
                $inform['warranty_end'] =  date('Y-m-d',strtotime($data['warranty_end']));
                // get  supplier id  from supplier
                $supplier = $data['supplier'];
                $inform['supplier_id'] = $this->getId('eng_supplier', $supplier, '');
                $inform['maintenance_date'] = $cur_time;

                if( EquipmentList::where('name', $inform['name'])->where('equip_id', $inform['equip_id'])->exists() )
                    continue;
                EquipmentList::create($inform);
            }
        }
    }

    public function parseExcelFilePart($path,&$count)
    {
        // Excel::selectSheets('Part List')->load($path, function($reader) {
            date_default_timezone_set(config('app.timezone'));
            $cur_time = date('Y-m-d H:i:s');
            // $rows = $reader->all()->toArray();
            $rows = Excel::toArray(new CommonImportExcel, $path);
            $rows = [$rows[0]];
            $count = $rows;
            for($i = 0; $i < count($rows); $i++ )
            {
                foreach( $rows[$i] as $data )
                {

                    $inform = array();
                    $inform['name'] = $data['part_name'];
                    $inform['part_id'] = $data['id'];
                    // $codes = array();
                    // if(!empty($data['id'])) {
                    //     $codes = explode("-", $data['id']);
                    // }
                    $inform['description'] = $data['part_name'];
                    // $critical = $data['critical'];
                    // if(strtolower($critical) == 'yes') $inform['critical_flag'] = 1;
                    // if(strtolower($critical) == 'no') $inform['critical_flag'] = 0;
                    // $external_maintenance  = $data['external_maintenance'] ;
                    // if(strtolower($external_maintenance) == 'yes') $inform['external_maintenance'] = 1;
                    // if(strtolower($external_maintenance) == 'no') $inform['external_maintenance'] = 0;
                    // get external_maintenance_id  from external maintenance company
                    // $external_maintenance_company = $data['external_maintenance_company'] ;
                    // $inform['external_maintenance_id'] = $this->getId('eng_external_maintenance', $external_maintenance_company, '');
                    // get dept_id from Department
                    // $department = $data['department'];
                    // $inform['dept_id'] = $this->getId('common_department', $department, '');
                    // $inform['life'] = $data['life'];
                    // $inform['life_unit'] = $data['life_unit'];
                    $inform['quantity'] = $data['quantity'];
                    $inform['minquantity'] = $data['minimum_quantity'];
                    $property = $data['property'];
                    $inform['property_id'] = $this->getId('common_property', $property, '');

                    // get Location id from location
                    // $location = $data['location'];
                    // $locations  = explode(" ", $location);

                    $inform['purchase_cost'] = $data['cost'];
                    $inform['purchase_date'] = date('Y-m-d',strtotime($data['purchase_date']));
                    $inform['manufacture'] = $data['manufacturer'];
                    // get status id from status
                    // $status = $data['status'];
                    // $inform['status_id'] = $this->getId('eng_equip_status', $status, '');
                    $inform['model'] = $data['model'];
                    $inform['barcode'] = $data['barcode'];
                    $inform['warranty_start'] =  date('Y-m-d',strtotime($data['warranty_start']));
                    $inform['warranty_end'] =  date('Y-m-d',strtotime($data['warranty_end']));
                    // get  supplier id  from supplier
                    $supplier = $data['supplier'];
                    $inform['supplier_id'] = $this->getId('eng_supplier', $supplier, '');
                    // $inform['maintenance_date'] = $cur_time;
                    if( $data['id'] != null) {
                        if (PartList::where('name', $inform['name'])->where('part_id', $inform['part_id'])->exists())
                            continue;
                        PartList::create($inform);
                    }
                }
            }
        // });
    }

    public function exceltest(Request $request) {
        $list_count = 2;
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/part/sample.xlsx';
        // try {
            $this->parseExcelFilePart($output_file, $list_count);
        // }catch (QueryException $e) {
        //     $errCode = $e->errorInfo[1];
        //     echo  $errCode;
        // }
    }

    public function getWorkorderList(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $user_id = $request->get('user_id', 4);
        $property_id = $request->get('property_id', 4);

        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'desc');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');
        $date_flag = $request->get('date_flag', 0);
        $dispatcher = $request->get('dispatcher', '');

        // filter
        $searchtext = $request->get('searchtext', '');

        $assignee_ids = $request->get('assignee_ids', '');
        $wr_ids = $request->get('wr_ids', '');
        $location_ids = $request->get('location_ids', '');
        $priority = $request->get('priority', 'All');
        $equip_list = $request->get('equip_list', []);

        $work_order_type = $request->get('work_order_type', 'All');

        $property_list = CommonUser::getPropertyIdsByJobroleids($dispatcher);

        $ret = array();
        $query = DB::table('eng_workorder as ew')
            ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
            ->leftJoin('eng_checklist as ecl', 'ew.checklist_id', '=', 'ecl.id')
            ->leftJoin('eng_repair_request as err', 'ew.request_id', '=', 'err.id')
            ->leftJoin('common_users as cua', 'err.assignee', '=', 'cua.id')
            ->leftJoin('services_location as sl', 'ew.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id');

        if( $date_flag == 0 )
            $query->whereRaw(sprintf(" ( DATE(ew.created_date) >= '%s' AND DATE(ew.created_date) <= '%s' )", $start_date, $end_date));

        if( $date_flag == 1 )
            $query->whereRaw(sprintf(" ( DATE(ew.schedule_date) >= '%s' AND DATE(ew.schedule_date) <= '%s' )", $start_date, $end_date));

   //     $query->where('ew.property_id', $property_id);
        $query->whereIn('ew.property_id', $property_list);
/*
        if( !empty($searchtext) )
        {
            $query->whereRaw("(ew.id LIKE '%%$searchtext%%' OR ew.name LIKE '%%$searchtext%%' OR ew.description LIKE '%%$searchtext%%' OR err.ref_id LIKE '%%$searchtext%%')");
        }
*/
        if($searchtext != '')
        {
            $query->where(function ($query) use ($searchtext) {
                $value = '%' . $searchtext . '%';
                $query->where('ew.id', 'like', $value)
                    ->orWhere('ew.name', 'like', $value)
                    ->orWhere('ew.description', 'like', $value)
                    ->orWhere('err.ref_id', 'like', $value);

            });
        }

        //dont show delete flag
        $query->where('ew.delete_flag','!=', intval("1"));

        // location filter
        if( !empty($location_ids) )
        {
            $location_id_list = explode(',', $location_ids);
            $query->whereIn('ew.location_id', $location_id_list);
        }

        // wrid filter
        if( !empty($wr_ids) )
        {
            $wr_id_list = explode(',', $wr_ids);
            $query->whereIn('err.ref_id', $wr_id_list);
        }

        // priority filter
        if( $priority != 'All' )
            $query->where('ew.priority', $priority);

        // type filter
        if( $work_order_type != 'All' )
            $query->where('ew.work_order_type', $work_order_type);

        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->select(DB::raw('ew.*, DATEDIFF(CURTIME(), ew.start_date) as age_days, eel.image_url as picture,  eel.name as equipment_name, DATE(ew.start_date) as start_date, ecl.name as checklist_name,
                                DATE(ew.end_date) as end_date, CONCAT_WS(" ", cua.first_name, cua.last_name) as assignee_name, err.assignee,
                                sl.name as location_name, slt.type as location_type, err.ref_id' ))
            // ->skip($skip)->take($pageSize)
            ->get();

        for($i = 0 ; $i < count($data_list) ; $i++) {
            WorkOrder::getWorkorderDetailWithStaff($data_list[$i], $user_id);
        }

        if( !empty($assignee_ids) )
        {
            $assignee_id_list = explode(',', $assignee_ids);

            // assignee filter
            $data_list = array_filter($data_list, function($row) use ($assignee_id_list) {
                return (count(array_filter($row->assigne_list, function($row1) use ($assignee_id_list) {
                    return in_array($row1->id, $assignee_id_list);
                })) > 0);
            });

        }

        $data_list = array_merge($data_list->toArray(), array());

        // equipment group filter
        if( count($equip_list) > 0 )
        {
            $data_list = array_filter($data_list, function($row) use ($equip_list) {
                return count(array_filter($equip_list, function($row1) use ($row) {
                    return ($row1['id'] == $row->equipment_id && $row1['type'] == 'single' ||
                                !empty($row->equip_group) && $row1['id'] == $row->equip_group->id && $row1['type'] == 'group' );
                })) > 0;
            });
        }

        if(is_array($data_list)) $data_list = array_merge($data_list, array());
        else $data_list = array_merge($data_list->toArray(), array());

        $ret['datalist'] = $data_list;

        return Response::json($ret);
    }

    /*
     * get workorder list like euqipment id or name
     */
    public function getEquipmentWorkorderList(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $property_id = $request->get('property_id', 4);
        $equipment_id = $request->get('equipment_id','0');
        $part_id = $request->get('part_id','0');



        $ret = array();
        $query = DB::table('eng_workorder as ew')
            ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
            ->leftJoin('eng_checklist as ecl', 'ew.checklist_id', '=', 'ecl.id');
//            ->leftJoin('common_users as cu', 'ew.staff_id', '=', 'cu.id')
//            ->leftJoin('common_job_role as cr', 'cr.id', '=', 'cu.job_role_id');

        if($equipment_id > 0)
            $query->where('ew.equipment_id', $equipment_id) ;

        if($part_id > 0) {
            $query->leftJoin('eng_workorder_part as ewp', 'ew.id', '=', 'ewp.workorder_id')
                    ->where('ewp.part_id', $part_id);
        }

        $query->where('ew.property_id', $property_id);

        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy('ew.end_date')
            ->select(DB::raw("ew.*,  eel.name as equipment_name, eel.location_group_member_id, DATE(ew.start_date) as start_date, ecl.name as checklist_name,
                                DATE(ew.end_date) as end_date, ew.staff_cost+ew.part_cost as staff_cost " ))
                                // cr.cost as staff_cost , CONCAT_WS(\" \", cu.first_name, cu.last_name) as staff_name"))
            ->get();
        for($i = 0 ; $i < count($data_list) ; $i++) {
            $location_group_member_id = $data_list[$i]->location_group_member_id;
            $location = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($location_group_member_id);
            $data_list[$i]->equipment_location = $location;
            $part_group = DB::table('eng_workorder_part')
                ->where('workorder_id',$data_list[$i]->id)
                ->select(DB::raw("workorder_id, part_id, part_name, part_number, part_cost, part_stock, part_number as part_number_original"))
                ->get();
            $data_list[$i]->part_group = $part_group;
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    public function checkWorkorder(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $list = DB::table('eng_preventive_equip_status as es')
            ->join('eng_preventive as ep', 'es.preventive_id', '=', 'ep.id')
            ->whereRaw("DATE(es.next_date) = '$cur_date'")
            ->select(DB::raw("ep.*, es.equip_id, es.last_date"))
            ->get();

        foreach($list as $row)
        {
            // calculate due date from last complete date
            $due_date = date('Y-m-d', strtotime("$row->frequency $row->frequency_unit", strtotime($row->last_date)));

            $workorder = $this->createWorkorderFromPreventive($row, $row->equip_id, $due_date, $method);
        }

        echo json_encode($list);
    }

    private function generateWorkorderFromPreventiveForMobile($preventive, $method, $equip_type, $equip_ids)
    {
        // calculate due date from last start date
        $due_date = date('Y-m-d', strtotime("$preventive->frequency $preventive->frequency_unit", strtotime($preventive->start_date)));

        if ($equip_type === "group") {
            foreach ($equip_ids as $equip_id) {
                $workorder = $this->createWorkorderFromPreventive($preventive, $equip_id, $due_date, $method);
            }

        } else {
            $workorder = $this->createWorkorderFromPreventive($preventive, $preventive->equip_id, $due_date, $method);
        }

        return $workorder;
    }

    private function generateWorkorderFromPreventive($preventive, $method)
    {
        // calculate due date from last start date
        $due_date = date('Y-m-d', strtotime("$preventive->frequency $preventive->frequency_unit", strtotime($preventive->start_date)));

        if($preventive->equip_type == "group")
        {
            $equiplist = DB::table('eng_equip_group_member as egm')
                ->join('eng_equip_list as eel', 'egm.equip_id', '=', 'eel.id')
                ->where('egm.group_id', $preventive->equip_id)
                ->select(DB::raw('eel.id, eel.name'))
                ->get();

            for ($i = 0; $i < count($equiplist); $i++) {
                $workorder = $this->createWorkorderFromPreventive($preventive, $equiplist[$i]->id, $due_date, $method);
            }
        }
        else
        {
            $workorder = $this->createWorkorderFromPreventive($preventive, $preventive->equip_id, $due_date, $method);
        }

        return $workorder;
    }

    public function getEquipListByGroupId(Request $request) {
        $group_id = $request->get("group_id", 0);
        if (empty($group_id)) {
            $ret['code'] = 201;
            $ret['message'] = "There is no group id";
            return Response::json($ret);
        }

        $equipList = DB::table('eng_equip_group_member as egm')
            ->join('eng_equip_list as eel', 'egm.equip_id', '=', 'eel.id')
            ->where('egm.group_id', $group_id)
            ->select(DB::raw('eel.id, eel.name'))
            ->get();

        $ret['code'] = 200;
        $ret['content'] = $equipList;
        $ret['message'] = "";

        return Response::json($ret);
    }

    private function createWorkorderFromPreventive($preventive, $equipment_id, $due_date, $method)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $cur_date = date("Y-m-d");

        $property_id = $preventive->property_id;

        // check if there is work order which is not completed
        $workorder = WorkOrder::where('request_id', $preventive->id)
                    ->where('equipment_id', $equipment_id)
                    ->where('request_flag', 3)
                    ->where('status', '!=', 'Completed')
                    ->first();
        if( !empty($workorder) )
            return $workorder;

        $location_id = 0;
        $equip = EquipmentList::find($equipment_id);
        if( !empty($equip) )
            $location_id = $equip->location_group_member_id;

        $workorder = new WorkOrder();

        $workorder->property_id = $property_id;
        $workorder->name = $preventive->name;
        $workorder->user_id = 0;
        $workorder->description = $preventive->description;
        $workorder->checklist_id = $preventive->checklist_id;
        $workorder->equipment_id = $equipment_id;
        $workorder->location_id = $location_id;
        $workorder->frequency = $preventive->frequency;
        $workorder->frequency_unit = $preventive->frequency_unit;
        $workorder->start_date = $cur_time;
        $workorder->end_date = $cur_time;
        $workorder->purpose_start_date = $cur_time;
        $workorder->created_date = $cur_time;
        $workorder->daily_id = WorkOrder::getMaxDailyID($property_id, $cur_date);

        // $part_group = $request->get('part_group', []);
        $part_group = EquipmentPreventivePart::getPartGroupData($preventive->id);

        $part_cost = 0;
        for ($i = 0; $i < count($part_group); $i++) {
            $part_cost += $part_group[$i]['part_cost'] * $part_group[$i]['part_number'];
        }
        $workorder->part_cost = $part_cost;

        $staff_cost = 0;
        $staff_group = EquipmentPreventiveStaff::getStaffGroupData($preventive->id);
        for ($i = 0; $i < count($staff_group); $i++) {
            $staff_cost += $staff_group[$i]['staff_cost'] ;
        }
        $workorder->staff_cost = $staff_cost;

        $workorder->critical_flag = 0;
        $workorder->status = 'Pending';
        $workorder->work_order_type = 'Preventive';
        $workorder->request_id = $preventive->id;
        $workorder->request_flag = 3;

        if( $workorder->frequency > 0 && $workorder->frequency_unit != 'Weeks' )
        {
            $workorder->purpose_end_date = date('Y-m-d', strtotime("$workorder->frequency $workorder->frequency_unit", strtotime($workorder->start_date)));
        }

        if( $workorder->frequency_unit == 'Weeks' ) // weekly generated
        {
            $next_sunday = date('Y-m-d 12:00:00', strtotime('next sunday', strtotime($workorder->start_date)));
            $workorder->purpose_end_date = $next_sunday;
        }

        $workorder->due_date = $due_date;
        $workorder->schedule_date = $due_date;

        $workorder->save();

        $this->createWorkOrderPart($property_id, $workorder->id, $part_group);
        $this->createStaffFromWorkOrder($property_id, $workorder->id, $staff_group);

        $this->createChecklistForWorkorder($workorder);

        $this->setWorkorderStaffStatusLog($workorder, 'create workorder', 'workorder', $method);

        $this->sendWorkorderDetailToPreventiveUsergroup($workorder, 'Created');

        $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, 0);

        return $workorder;
    }

    public function createWorkorder(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $cur_date = date("Y-m-d");
    //    $property_id =  $request->get('property_id', '');
        $preventive_id = $request->get('request_id', 0);
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $workorder = new WorkOrder();
    //    $workorder->property_id = $property_id;
        $workorder->name = $request->get('name', '');
        $workorder->user_id = $request->get('user_id', 0);
        $workorder->description = $request->get('description', '');
        $workorder->checklist_id = $request->get('checklist_id', '');
        $workorder->location_id = $request->get('location_id', 0);
        $workorder->priority = $request->get('priority', 'Low');
        if($request->get('equipment_id', '0') != 0)
            $workorder->equipment_id = $request->get('equipment_id', '0');
        else
            $workorder->equipment_id = $request->get('equip_id', '0');

        $workorder->frequency = $request->get('frequency', '0');
        $workorder->frequency_unit = $request->get('frequency_unit', 'Days');
        $workorder->schedule_date = $request->get('schedule_date', $cur_time);
        $workorder->due_date = $request->get('schedule_date', $cur_time);
        $workorder->purpose_start_date = $request->get('schedule_date', $cur_time);
        $workorder->purpose_end_date = $request->get('schedule_date', $cur_time);
        $workorder->created_date = $cur_time;

        $property = DB::table('services_location as sl')
            ->where('sl.id', $workorder->location_id)
            ->select(DB::raw('sl.property_id'))
            ->first();
        $property_id = $property->property_id;
        $workorder->daily_id = WorkOrder::getMaxDailyID($property_id, $cur_date);

        // $part_group = $request->get('part_group', []);
        $part_group = EquipmentPreventivePart::getPartGroupData($preventive_id);

        $part_cost = 0;
        for ($i = 0; $i < count($part_group); $i++) {
            $part_cost += $part_group[$i]['part_cost'] * $part_group[$i]['part_number'];
        }

        $workorder->property_id = $property->property_id;
        $workorder->part_cost = $part_cost;
        $staff_cost = 0;
        $staff_group = $request->get('staff_group',[]);
        for ($i = 0; $i < count($staff_group); $i++) {
            $staff_cost += $staff_group[$i]['staff_cost'] ;
        }
        $workorder->staff_cost = $staff_cost;
        $workorder->critical_flag = $request->get('critical_flag', '0');
        $workorder->status = $request->get('status', 'Pending');
        $workorder->work_order_type = $request->get('work_order_type', 'Repairs');
        $workorder->request_id = $preventive_id;
        $workorder->request_flag = $request->get('request_flag',2); //1=requet, 2= workorder, 3= preventive automatically, default =2=workorder

        if( $workorder->frequency > 0 && $workorder->frequency_unit != 'Weeks' )
        {
            $workorder->purpose_end_date = date('Y-m-d', strtotime("$workorder->frequency $workorder->frequency_unit", strtotime($workorder->start_date)));
        }

        if( $workorder->frequency_unit == 'Weeks' ) // weekly generated
        {
            $next_sunday = date('Y-m-d 12:00:00', strtotime('next sunday', strtotime($workorder->start_date)));
            $workorder->purpose_end_date = $next_sunday;
        }

        $workorder->save();

        $this->createWorkOrderPart($property_id, $workorder->id, $part_group);
        $this->createStaffFromWorkOrder($property_id, $workorder->id, $staff_group);

        $this->createChecklistForWorkorder($workorder);

        $this->setWorkorderStaffStatusLog($workorder, 'create workorder', 'workorder', $method);

        $this->sendWorkorderDetailToPreventiveUsergroup($workorder, 'Created');

        $this->sendWorkorderDetailToEngUsergroup($workorder, 'Created');

        $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, 0);

        $ret = array();
        $ret['id'] = $workorder->id;

        return Response::json($part_group);
    }

    public function setWorkOrderStaffStatus($property_id, $workorder_id, $status, $user_id, $staff_id, $staff_cost) {
        date_default_timezone_set(config('app.timezone'));
        $cu_time = date("Y-m-d H:i:s");

        $staff_status = DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $workorder_id)
                ->where('staff_id', $staff_id)
                ->first();
        if( empty($staff_status) )
        {
            DB::table('eng_workorder_staff_status')->insert([
                'workorder_id' => $workorder_id,
                'user_id' => $user_id,
                'status' => $status,
                'start_date' => $cu_time,
                'staff_id' => $staff_id,
                'staff_cost' => $staff_cost,
                'status_flag' => '0'
            ]);

            $staff_status = DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $workorder_id)
                ->where('staff_id', $staff_id)
                ->first();
        }

        //1. if staff 's state is pending, insert table
        if($status == 'Pending')
        {
            DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $workorder_id)
                ->where('staff_id', $staff_id)
                ->update([
                    'user_id' => $user_id,
                    'status' => $status,
                    'start_date' => $cu_time,
                    'staff_cost' => $staff_cost,
                    'status_flag' => '0'
                ]);

            //email send assigned to user, in this case, assigned user is staff.
            $this->sendWorkOrderEmail($property_id, $workorder_id, $staff_id, 'Pending');
        }
        //2. if staff 's status is working, compare What is original status. if original status is hold, reset start_date, end date, status_flag
        //status flag =1 is  hold state.
        if($status == 'In Progress') {
            if($staff_status->status == 'Hold') {
                $end_date = strtotime($staff_status->end_date);
                $cu_date  = strtotime($cu_time);
                $diff_date = $cu_date - $end_date;
                $start_date =  date('Y-m-d H:i:s', $diff_date);
                $end_date = '0000-00-00 00:00:00';
            }else {
                $start_date = $cu_time;
                $end_date = '0000-00-00 00:00:00';
            }

            DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $workorder_id)
                ->where('staff_id', $staff_id)
                ->update([
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'status' =>  'In Progress',
                    'status_flag' => '0' ]);
        }
        //3. if staff 's status is hold, update end date and flag is 1 .
        if($status == 'On Hold') {
            DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $workorder_id)
                ->where('staff_id', $staff_id)
                ->update(['end_date' => $cu_time,
                    'status' =>  'On Hold',
                    'status_flag' => '1' ]);
        }
        //4. if staff 's status is Completed, update end_date with current time.
        if($status == 'Completed') {
            DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $workorder_id)
                ->where('staff_id', $staff_id)
                ->update(['end_date' => $cu_time,
                    'status' =>  'Completed',
                    'status_flag' => '0' ]);

            //email send  to user about completed work order status, in this case, user means agent logined.
            $this->sendWorkOrderEmail($property_id, $workorder_id, $user_id, 'Completed');

        }
    }

    public function setWorkOrderStaffStatusIndividual($property_id, $workorder_id, $status, $user_id, $staff_id, $staff_cost) {
        date_default_timezone_set(config('app.timezone'));
        $cu_time = date("Y-m-d H:i:s");

        $staff_status = DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $workorder_id)
                ->where('staff_id', $staff_id)
                ->first();
        if( empty($staff_status) )
        {
            DB::table('eng_workorder_staff_status')->insert([
                'workorder_id' => $workorder_id,
                'user_id' => $user_id,
                'status' => 'Pending',
                'start_date' => $cu_time,
                'staff_id' => $staff_id,
                'staff_cost' => $staff_cost,
                'status_flag' => '0'
            ]);

            $staff_status = DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $workorder_id)
                ->where('staff_id', $staff_id)
                ->first();
        }

        if($status == 'Pending')
        {
            DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $workorder_id)
                ->where('staff_id', $staff_id)
                ->update([
                    'user_id' => $user_id,
                    'status' => $status,
                    'start_date' => $cu_time,
                    'staff_cost' => $staff_cost,
                    'status_flag' => '0'
                ]);

            //email send assigned to user, in this case, assigned user is staff.
            $this->sendWorkOrderEmail($property_id, $workorder_id, $staff_id, 'Pending');
        }
    }

    public function startWorkorder(Request $request)
    {
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);
        $source = $request->get('source', 'mobile');

        $method = 'Web';

        if( $source == 'web' )
            $staff_id = $request->get('staff_id', 0);
        else
            $staff_id = $user_id;

        $method = Functions::getRequestMethod($request->get('device_id', ''));

        date_default_timezone_set(config('app.timezone'));
        $cu_time = date("Y-m-d H:i:s");

        $query = DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $id)
            ->where('staff_id', $staff_id);

        $ret = array();

        $query1 = clone $query;
        $staff_status = $query1->first();
        if( empty($staff_status) )
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is not assigned to you';
            return Response::json($ret);
        }

        if( $staff_status->status == 'In Progress' ||
            $staff_status->status == 'On Hold')
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is already started';
            return Response::json($ret);
        }

        if( $staff_status->status == 'Completed' )
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is already finished';
            return Response::json($ret);
        }

        $query1 = clone $query;

        $query->update(['start_date' => $cu_time,
                    'status' =>  'In Progress',
                    'status_flag' => '0' ]);

        $workorder = WorkOrder::find($id);
        $prev_status = $workorder->status . '';
        $workorder->status = 'In Progress';
        $workorder->purpose_start_date = $cu_time;
        if(empty($workorder->start_date)){
            $workorder->start_date = $cu_time;
        }
        $workorder->save();

        WorkOrder::getWorkorderDetailWithStaff($workorder, $staff_id);
        $this->setWorkOrderStatusLog($workorder->id, $workorder->name, $workorder->status, $workorder->user_id, $staff_id, 'start workorder', 'workorder', $method);
        $this->updateRelatedEngineeringStatus($workorder, $prev_status);

        $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, $staff_id);

        $ret['code'] = 200;
        $ret['content'] = $workorder;

        return Response::json($ret);
    }

    public function holdWorkorder(Request $request)
    {
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);
        $source = $request->get('source', 'mobile');

        if( $source == 'web' )
            $staff_id = $request->get('staff_id', 0);
        else
            $staff_id = $user_id;

        $method = Functions::getRequestMethod($request->get('device_id', ''));

        date_default_timezone_set(config('app.timezone'));
        $cu_time = date("Y-m-d H:i:s");

        $query = DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $id)
            ->where('staff_id', $staff_id);

        $ret = array();

        $query1 = clone $query;
        $staff_status = $query1->first();
        if( empty($staff_status) )
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is not assigned to you';
            return Response::json($ret);
        }

        if( $staff_status->status == 'Pending')
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is not started';
            return Response::json($ret);
        }

        if( $staff_status->status == 'Completed' )
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is already finished';
            return Response::json($ret);
        }

        $query1 = clone $query;

        $query->update([
                    'status' =>  'On Hold',
                    'status_flag' => '1' ]);

        $workorder = WorkOrder::find($id);
        $prev_status = $workorder->status . '';
        // check work order status and change it to hold
        $hold_count = DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $id)
            ->where('status', 'On Hold')
            ->count();

        $staff_count = DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $id)
            ->count();

        if( $staff_count == $hold_count )
        {
            $workorder->status = 'On Hold';
            $workorder->save();
            $request->attributes->add(['status' => 'On Hold']);
            return $this->changeWorkOrderStatus($request);
        }

        WorkOrder::getWorkorderDetailWithStaff($workorder, $staff_id);
        $this->setWorkOrderStatusLog($workorder->id, $workorder->name, $workorder->status, $workorder->user_id, $staff_id, 'hold workorder', 'workorder', $method);
        $this->updateRelatedEngineeringStatus($workorder, $prev_status);

        $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, $staff_id);

        $ret['code'] = 200;
        $ret['content'] = $workorder;

        return Response::json($ret);
    }

    public function resumeWorkorder(Request $request)
    {
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);
        $source = $request->get('source', 'mobile');

        if( $source == 'web' )
            $staff_id = $request->get('staff_id', 0);
        else
            $staff_id = $user_id;

        $method = Functions::getRequestMethod($request->get('device_id', ''));

        date_default_timezone_set(config('app.timezone'));
        $cu_time = date("Y-m-d H:i:s");

        $query = DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $id)
            ->where('staff_id', $staff_id);

        $ret = array();

        $query1 = clone $query;
        $staff_status = $query1->first();
        if( empty($staff_status) )
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is not assigned to you';
            return Response::json($ret);
        }

        if( $staff_status->status == 'Pending')
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is not started';
            return Response::json($ret);
        }

        if( $staff_status->status == 'Completed' )
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is already finished';
            return Response::json($ret);
        }

        $query1 = clone $query;

        $query->update([
                    'status' =>  'In Progress',
                    'status_flag' => '0' ]);

        $workorder = WorkOrder::find($id);
        $prev_status = $workorder->status . '';
        $workorder->status = 'In Progress';
        $workorder->save();

        WorkOrder::getWorkorderDetailWithStaff($workorder, $staff_id);
        $this->setWorkOrderStatusLog($workorder->id, $workorder->name, $workorder->status, $workorder->user_id, $staff_id, 'resume workorder', 'workorder', $method);
        $this->updateRelatedEngineeringStatus($workorder, $prev_status);

        $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, $staff_id);

        $ret['code'] = 200;
        $ret['content'] = $workorder;

        return Response::json($ret);
    }

    public function finishWorkorder(Request $request)
    {
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);
        $source = $request->get('source', 'mobile');

        if( $source == 'web' )
            $staff_id = $request->get('staff_id', 0);
        else
            $staff_id = $user_id;

        $method = Functions::getRequestMethod($request->get('device_id', ''));

        date_default_timezone_set(config('app.timezone'));
        $cu_time = date("Y-m-d H:i:s");

        $query = DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $id)
            ->where('staff_id', $staff_id);

        $ret = array();

        $query1 = clone $query;
        $staff_status = $query1->first();
        if( empty($staff_status) )
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is not assigned to you';
            return Response::json($ret);
        }

        if( $staff_status->status == 'Pending')
        {
            $ret['code'] = 201;
            $ret['message'] = 'This workorder is not started';
            return Response::json($ret);
        }

        $query1 = clone $query;

        $non_completed_count = DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $id)
            ->where('status', '!=', 'Completed')
            ->count();

        $workorder = WorkOrder::find($id);

        if( $non_completed_count == 1 ) // last person
        {
            $inspected = WorkOrder::isChecklistCompleted($workorder);
            if( $workorder->checklist_id > 0 && $inspected == 0 )
            {
                $ret = array();
                $ret['id'] = $workorder->id;
                $ret['code'] = 201;
                $ret['message'] = "You should confirm checklist";

                return Response::json($ret);
            }

            $complete_comment = $request->get('comment', '');
            if(  empty($complete_comment) )
            {
                $ret = array();
                $ret['code'] = 202;
                $ret['message'] = "You should provide complete comment";

                return Response::json($ret);
            }

            $query->update(['end_date' => $cu_time,
                'status' =>  'Completed',
                'status_flag' => '0' ]);

            $this->setWorkOrderStatusLog($workorder->id, $workorder->name, $workorder->status, $workorder->user_id, $staff_id, 'finish workorder', 'workorder', $method);

            $request->attributes->add(['status' => 'Completed']);
            return $this->changeWorkOrderStatus($request);
        }
        else
        {
            $query->update(['end_date' => $cu_time,
                'status' =>  'Completed',
                'status_flag' => '0' ]);

            $this->setWorkOrderStatusLog($workorder->id, $workorder->name, $workorder->status, $workorder->user_id, $staff_id, 'finish workorder', 'workorder', $method);
        }

        WorkOrder::getWorkorderDetailWithStaff($workorder, $staff_id);

        $ret['code'] = 200;
        $ret['content'] = $workorder;

        return Response::json($ret);
    }

    public function setWorkOrderStatusLog($workorder_id, $workorder_name,$status, $user_id, $staff_id ,$desc, $log_kind, $method) {
        //if workorder 's history. log kind = workorder, else log kind is staff.
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $description = '';
        if($status == 'Pending' ) $description = 'Created';
        if($status == 'In Progress' ) $description = 'In progress';
        if($status == 'On Hold' ) $description = 'On hold';
        if($status == 'Completed' ) $description = 'Completed';
        if($desc == '') $desc = $description.':'.$workorder_name;
        DB::table('eng_workorder_status_log')->insert(['workorder_id' => $workorder_id,
            'user_id' => $user_id,
            'status' => $status,
            'setdate' => $cur_time,
            'staff_id' => $staff_id,
            'description' => $desc,
            'log_kind' => $log_kind,
            'method' => $method
            ]);
    }

    public function createWorkOrderPart($property_id, $workorder_id, $part_group) {
        DB::table('eng_workorder_part')
            ->where('workorder_id', $workorder_id)
            ->delete();

        for($i = 0 ; $i < count($part_group) ; $i++) {
            $parts = array();

            $parts['workorder_id'] = $workorder_id;
            $part_id = $part_group[$i]['part_id'];
            $parts['part_id'] = $part_id;
            $parts['part_name'] = $part_group[$i]['part_name'];
            $parts['part_cost'] = $part_group[$i]['part_cost'];
            $parts['part_number'] = $part_group[$i]['part_number'];
            $parts['part_stock'] = $part_group[$i]['part_stock'];

            DB::table('eng_workorder_part')->insert($parts);
        }
    }

    private function updatePartStockFromWorkOrder($workorder) {
        if( empty($workorder) )
            return;

        $part_list = DB::table('eng_workorder_part as ewp')
            ->where('ewp.workorder_id', $workorder->id)
            ->get();

        foreach($part_list as $row)
        {
            $stock_part = DB::table('eng_part_list')
                    ->where('id', $row->part_id)
                    ->first();

            $part_stock = $stock_part->quantity;
            $min_stock = $stock_part->minquantity;

            $stock = $part_stock - $row->part_number;
            if($stock <= $min_stock) {
                //send notification
                $this->sendStockEmail($workorder->property_id, $row->part_name, $stock, $min_stock);
            }else {
                DB::table('eng_part_list')
                    ->where('id', $row->part_id)
                    ->update(['quantity' => $stock]);
            }
        }
    }

    public function createWorkOrderPartFromOld($workorder, $old) {
        DB::table('eng_workorder_part')
            ->where('workorder_id', $workorder->id)
            ->delete();

        $part_list = DB::table('eng_workorder_part as ewp')
            ->where('ewp.workorder_id', $old->id)
            ->get();

        $part_cost = 0;

        foreach($part_list as $row)
        {
            $parts = array();

            $parts['workorder_id'] = $workorder->id;
            $parts['part_id'] = $row->part_id;
            $parts['part_name'] = $row->part_name;
            $parts['part_number'] = $row->part_number;
            $parts['part_cost'] = $row->part_cost;

            $stock_part = DB::table('eng_part_list')
                    ->where('id', $row->part_id)
                    ->first();

            $part_stock = 0;
            if( !empty($stock_part) )
                $part_stock = $stock_part->quantity;

            $parts['part_stock'] = $part_stock;

            $part_cost += $row->part_number * $row->part_cost;

            DB::table('eng_workorder_part')->insert($parts);
        }

        return $part_cost;
    }

    public function createStaffFromWorkOrder($property_id, $workorder_id, $staff_group) {
        $staff_list = array();
        DB::table('eng_workorder_staff')
            ->where('workorder_id', $workorder_id)
            ->delete();

        for($i = 0 ; $i < count($staff_group) ; $i++) {
            $staff_list[$i]['workorder_id'] = $workorder_id;
            $staff_list[$i]['staff_id'] = $staff_group[$i]['staff_id'];
            $staff_list[$i]['staff_name'] = $staff_group[$i]['staff_name'];
            $staff_list[$i]['staff_cost'] = $staff_group[$i]['staff_cost'];
            $staff_list[$i]['staff_type'] = $staff_group[$i]['staff_type'];
        }

        DB::table('eng_workorder_staff')->insert($staff_list);
    }

    public function createStaffFromOldWorkorder($workorder, $old) {
        $staff_list = array();

        DB::table('eng_workorder_staff')
            ->where('workorder_id', $workorder->id)
            ->delete();

        $staff_group_list = DB::table('eng_workorder_staff')
            ->where('workorder_id', $old->id)
            ->get();

        $staff_cost = 0;
        foreach($staff_group_list as $row)
        {
            $staff['workorder_id'] = $workorder->id;
            $staff['staff_id'] =   $row->staff_id;
            $staff['staff_name'] = $row->staff_name;
            $staff['staff_cost'] = $row->staff_cost;
            $staff['staff_type'] = $row->staff_type;

            $staff_cost += $row->staff_cost;

            $staff_list[] = $staff;
        }

        DB::table('eng_workorder_staff')->insert($staff_list);

        return $staff_cost;
    }

    public function setWorkorderStaffStatusLog($workorder, $desc, $log_kind, $method) {
        $staff_list = DB::table('eng_workorder_staff')
            ->where('workorder_id', $workorder->id)
            ->get();

        $staff_cost = 0;
        $exist_staff_ids = [];

        foreach($staff_list as $row)
        {
            if ($row->staff_type == 'group') {
                $userlist = DB::table('common_user_group_members as cgm')
                    ->leftJoin('common_users as cu', 'cgm.user_id', '=', 'cu.id')
                    ->leftJoin('common_job_role as cr', 'cr.id', '=', 'cu.job_role_id')
                    ->where('cgm.group_id', $row->staff_id)
                    ->select(DB::raw('cu.id, cr.cost,CONCAT_WS(" ", cu.first_name, cu.last_name) as staff_name'))
                    ->get();

                for ($u = 0; $u < count($userlist); $u++) {
                    $staff_id = $userlist[$u]->id;
                    $staff_cost = $userlist[$u]->cost;
                    if($staff_id > 0)
                    {
                        $exist_staff_ids[] = $staff_id;
                        //log for workorder
                        $this->setWorkOrderStatusLog($workorder->id, $workorder->name, $workorder->status, $workorder->user_id, $staff_id, $desc, $log_kind, $method);
                        $this->setWorkOrderStaffStatus($workorder->property_id, $workorder->id, $workorder->status, $workorder->user_id, $staff_id, $staff_cost);
                    }
                }

            } else { // if staff type is single(one person)
                $staff_id = $row->staff_id;
                $staff_cost = $row->staff_cost;
                if($staff_id > 0)
                {
                    $exist_staff_ids[] = $staff_id;
                    $this->setWorkOrderStatusLog($workorder->id, $workorder->name, $workorder->status, $workorder->user_id, $staff_id, $desc, $log_kind, $method);
                    $this->setWorkOrderStaffStatus($workorder->property_id, $workorder->id, $workorder->status, $workorder->user_id, $staff_id, $staff_cost);
                }
            }
        }

        // delete not exist staffs
        $staff_list = DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $workorder->id)
            ->whereNotIn('staff_id', $exist_staff_ids)
            ->delete();
    }

    public function setWorkorderStaffStatusLogIndividual($workorder, $desc, $log_kind, $method) {
        $staff_list = DB::table('eng_workorder_staff')
            ->where('workorder_id', $workorder->id)
            ->get();

        $staff_cost = 0;
        $exist_staff_ids = [];

        foreach($staff_list as $row)
        {
            if ($row->staff_type == 'group') {
                $userlist = DB::table('common_user_group_members as cgm')
                    ->leftJoin('common_users as cu', 'cgm.user_id', '=', 'cu.id')
                    ->leftJoin('common_job_role as cr', 'cr.id', '=', 'cu.job_role_id')
                    ->where('cgm.group_id', $row->staff_id)
                    ->select(DB::raw('cu.id, cr.cost,CONCAT_WS(" ", cu.first_name, cu.last_name) as staff_name'))
                    ->get();

                for ($u = 0; $u < count($userlist); $u++) {
                    $staff_id = $userlist[$u]->id;
                    $staff_cost = $userlist[$u]->cost;
                    if($staff_id > 0)
                    {
                        $exist_staff_ids[] = $staff_id;
                        //log for workorder
                        $this->setWorkOrderStatusLog($workorder->id, $workorder->name, $workorder->status, $workorder->user_id, $staff_id, $desc, $log_kind, $method);
                        $this->setWorkOrderStaffStatusIndividual($workorder->property_id, $workorder->id, $workorder->status, $workorder->user_id, $staff_id, $staff_cost);
                    }
                }

            } else { // if staff type is single(one person)
                $staff_id = $row->staff_id;
                $staff_cost = $row->staff_cost;
                if($staff_id > 0)
                {
                    $exist_staff_ids[] = $staff_id;
                    $this->setWorkOrderStatusLog($workorder->id, $workorder->name, $workorder->status, $workorder->user_id, $staff_id, $desc, $log_kind, $method);
                    $this->setWorkOrderStaffStatusIndividual($workorder->property_id, $workorder->id, $workorder->status, $workorder->user_id, $staff_id, $staff_cost);
                }
            }
        }

        // delete not exist staffs
        $staff_list = DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $workorder->id)
            ->whereNotIn('staff_id', $exist_staff_ids)
            ->delete();
    }

    public function sendWorkOrderEmail($property_id, $workorder_id, $user_id, $status) {
        $title = '';
        $description = '';
        $attached_flag = 0;

        $workorder = DB::table('eng_workorder as ew')
        ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
        ->leftJoin('services_location as sl', 'ew.location_id', '=', 'sl.id')
        ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
        ->where('ew.id', $workorder_id)
        ->select(DB::raw("ew.*,  eel.name as equipment_name, eel.equip_id as eq_id,
                            ew.staff_cost + ew.part_cost as staff_cost,
                            sl.name as location_name, slt.type as location_type
                " ))
        ->first();

        if( empty($workorder) )
            return;

        if ($workorder->checklist_id > 0)
            $attached_flag  = 1;
        else
            $attached_flag = 0;
            

        $staff_group = DB::table('eng_workorder_staff')
            ->where('workorder_id',$workorder->id)
            ->select(DB::raw("workorder_id, staff_id, staff_name, staff_type, staff_cost"))
            ->get();
        $workorder->assigne_list_names = WorkOrder::getAssigneListNames($workorder, $staff_group);

        $info = array();
     //   $info['first_name'] = "$row->first_name $row->last_name";
    //    $info['guest_name'] = $row->last_name;
        $info['name'] = $workorder->name;
        $info['description'] = $workorder->description;
        $info['start_date'] = $workorder->start_date;
        $info['end_date'] = $workorder->end_date;
        $info['due_date'] = $workorder->due_date;
        $info['schedule_date'] = $workorder->schedule_date;
        $info['equipment'] = $workorder->equipment_name;
        $info['equip_id'] = $workorder->eq_id;
        $info['location'] = "$workorder->location_name $workorder->location_type";
        $info['assignee_list'] =  $workorder->assigne_list_names;
        $info['notify'] = 'Created';
        $info['desc'] = sprintf("Work Order - %s has been Assigned.", $workorder->name);

        $workorder_daily_id = WorkOrder::getDailyId($workorder);
        if($status == 'Pending') {
            $title = sprintf("%s: [Confirmation] Work Order has been assigned", $workorder_daily_id);
            $description = sprintf("%s: has been assigned to you." , $workorder_daily_id);
            $info['desc'] = sprintf("Work Order - %s has been Assigned.", $workorder->name);
        }
        if($status == 'Completed' ) {
            $title = sprintf("%s: Work Order Completed", $workorder_daily_id);
            $description = $title;
            $info['desc'] = sprintf("Work Order - %s has been Completed.", $workorder->name);
        }

        if($status == 'Hold' ) {
            $title = sprintf("%s: Work Order is on Hold", $workorder_daily_id);
            $info['desc'] = sprintf("Work Order - %s has been On Hold.", $workorder->name);
            $description = $title;
        }

        $data = $this->getWorkorderCheckListDataForEmail($workorder_id);

        ob_start();

        $filename = 'Workorder_Checklist_' . date('d_M_Y_H_i') . '_' . $data['name'];
        $folder_path = public_path() . '/uploads/reports/';
        $path = $folder_path . $filename . '.html';
        $pdf_path = $folder_path . $filename . '.pdf';

        
        $content = view('frontend.report.workorder_checklist_pdf', compact('data'))->render();
        echo $content;
        file_put_contents($path, ob_get_contents());

        ob_clean();

        $options = array();
        $options['html'] = $path;
        $options['pdf'] = $pdf_path;
        $options['paperSize'] = array('format' => 'A4', 'orientation' => 'portrait');
        $options['attach_flag'] = $attached_flag;

        $user = DB::table('common_users as cu')
                ->where('cu.id', $user_id)
                ->select(DB::raw('cu.*'))
                ->first();
        
        if(!empty($user)) {
            $user_email = $user->email;
            $info['first_name'] = "$user->first_name $user->last_name";
            $email_content = view('emails.workorder_reminder', ['info' => $info])->render();
            $smtp = Functions::getMailSetting($property_id, 'notification_');
            $message = array();
            $request = array();
            $message['subject'] = $title;
            $request['subject'] = $title;
            $message['smtp'] = $smtp;
            $request['smtp'] = $smtp;
            $message['title'] = $title;
            if ($attached_flag == 1  && $status == 'Completed'){
                $request['to'] = $user_email;
                $request['content'] = $email_content;
                $request['html'] = $title;
                $request['filename'] = $filename . '.pdf';
                $request['options'] = $options;
                $message['type'] = 'report_pdf';
                $message['content'] = $request;
            }else{
                
                $message['type'] = 'email';
                $message['to'] = $user_email;
                $message['content'] = $email_content;
                
            }
            if($user_email != '')
                Redis::publish('notify', json_encode($message));


            $payload = array();

            $payload['task_id'] = $workorder_id;
            $payload['table_id'] = $workorder_id;
            $payload['table_name'] = 'eng_workorder';
            $payload['type'] = 'Workorder Notify';
            $payload['header'] = 'Engineering';

            $payload['property_id'] = $property_id;
            $payload['notify_type'] = 'workorder';
            $payload['notify_id'] = $workorder_id;
            $payload['status_id'] = 0;


            Functions::sendPushMessgeToDeviceWithRedisNodejs(
                $user, $workorder_id, $title, $description, $payload
            );
        }
    }

    public function sendWorkOrderEmailForOnHold($property_id, $workorder_id) {

        $description =  " Work Order is holded." ;

        $staff_group = DB::table('eng_workorder_staff')
                ->where('workorder_id', $workorder_id)
                ->select(DB::raw("workorder_id, staff_id, staff_name, staff_type, staff_cost"))
                ->get();

        $email_list = [];

        // get assigneer list
        foreach($staff_group as $row)
        {
            if($row->staff_type == 'group')
            {
                $user_list = DB::table('common_user_group as cg')
                    ->join('common_user_group_members as cgm', 'cg.id', '=', 'cgm.group_id')
                    ->join('common_users as cu', 'cgm.user_id', '=', 'cu.id')
                    ->where('cg.id', $row->staff_id)
                    ->groupBy('cu.email')
                    ->select(DB::raw('cu.email'))
                    ->get();

                foreach($user_list as $row1)
                    $email_list[] = $row1->email;
            }
            else
            {
                $user_list = DB::table('common_users as cu')
                    ->where('cu.id', $row->staff_id)
                    ->select(DB::raw('cu.email'))
                    ->get();

                foreach($user_list as $row1)
                    $email_list[] = $row1->email;

            }
        }

        // echo json_encode($email_list);

        $email_list = array_unique($email_list, SORT_REGULAR);
        $email_list = array_merge($email_list, array());

        $workorder = DB::table('eng_workorder as ew')
        ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
        ->leftJoin('services_location as sl', 'ew.location_id', '=', 'sl.id')
        ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
        ->where('ew.id', $workorder_id)
        ->select(DB::raw("ew.*,  eel.name as equipment_name, eel.equip_id as eq_id,
                            ew.staff_cost + ew.part_cost as staff_cost,
                            sl.name as location_name, slt.type as location_type
                " ))
        ->first();

        if( empty($workorder) )
        return;

        $staff_group = DB::table('eng_workorder_staff')
        ->where('workorder_id',$workorder->id)
        ->select(DB::raw("workorder_id, staff_id, staff_name, staff_type, staff_cost"))
        ->get();
        $workorder->assigne_list_names = WorkOrder::getAssigneListNames($workorder, $staff_group);
        $title = sprintf("%s: Work Order is on Hold", WorkOrder::getDailyId(($workorder)));
        $info = array();
     //   $info['first_name'] = "$row->first_name $row->last_name";
    //    $info['guest_name'] = $row->last_name;
        $info['name'] = $workorder->name;
        $info['description'] = $workorder->description;
        $info['start_date'] = $workorder->start_date;
        $info['end_date'] = '';
        $info['due_date'] = $workorder->due_date;
        $info['schedule_date'] = $workorder->schedule_date;
        $info['equipment'] = $workorder->equipment_name;
        $info['equip_id'] = $workorder->eq_id;
        $info['location'] = "$workorder->location_name $workorder->location_type";
        $info['assignee_list'] =  $workorder->assigne_list_names;
        $info['notify'] = 'On Hold';
        $info['desc'] = sprintf("Work Order - %s has been On Hold.", $workorder->name);

        foreach($email_list as $email)
        {
            if( empty($email) )
                continue;

            $user = DB::table('common_users as cu')
                ->where('cu.email', $email)
                ->select(DB::raw('cu.*'))
                ->first();

            $smtp = Functions::getMailSetting($property_id, 'notification_');
            $info['first_name'] = "$user->first_name $user->last_name";
            $email_content = view('emails.workorder_reminder', ['info' => $info])->render();
            $message = array();
            $message['type'] = 'email';
            $message['to'] = $email;
            $message['subject'] = $title;
            $message['title'] = $title;
            $message['content'] = $email_content;
            $message['smtp'] = $smtp;

            // echo json_encode($message);

            Redis::publish('notify', json_encode($message));
        }
    }

    public function sendStockEmail($property_id, $part_name, $stock, $min_stock)
    {
        $title = "Out of stock notification";
        $description =  sprintf(" Stock of %s is %d.", $part_name, $stock);

        $info = array();
        $info['part_name'] = $part_name;
        $info['stock'] = $stock;

        //get user group id from property setting
        $groups_data = DB::table('property_setting')
            ->where('settings_key', 'eng_low_stock_notify')
            ->where('property_id', $property_id)
            ->first();
        $user_groups = explode(",", $groups_data->value);

        if(!empty($user_groups)) {
            $userlist = DB::table('common_users as cu')
                ->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
                ->leftJoin('common_user_group_members as cm', 'cu.id', '=', 'cm.user_id')
                ->leftJoin('common_user_group as cg', 'cm.group_id', '=', 'cg.id')
                ->whereIn('cg.id', $user_groups)
                ->where('cg.property_id',$property_id)
                ->groupBy('cu.email')
                ->select(DB::raw('cu.*'))
                ->get();

            if(!empty($userlist)) {
                for ($i = 0; $i < count($userlist); $i++) {
                    $user_email = $userlist[$i]->email;
                    $info['username'] = $userlist[$i]->first_name;
                    $smtp = Functions::getMailSetting($property_id, 'notification_');
                    $email_content = view('emails.eng_low_stock_reminder', ['info' => $info])->render();
                    $message = array();
                    $message['type'] = 'email';
                    $message['to'] = $user_email;
                    $message['subject'] = 'Low Stock Notification';
                    $message['title'] = $title;
                    $message['content'] = $email_content;
                    $message['smtp'] = $smtp;
                    if($user_email != '')
                        Redis::publish('notify', json_encode($message));
                }
            }
        }
    }

    public function updateWorkorder(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cu_time = date("Y-m-d H:i:s");
        $id = $request->get('id',0);
        $status = $request->get('status', 'Pending');
        $user_id = $request->get('user_id', 0);
    //    $property_id =  $request->get('property_id', '');
        $location_id =  $request->get('location_id', '');
        $priority =  $request->get('priority', 'Low');
        $inspected =  $request->get('inspected', 0);
        $estimated_duration =  $request->get('estimated_duration', 0);
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $workorder =  WorkOrder::find($id);

        $workorder->name = $request->get('name', '');
        $workorder->priority = $priority;
        $workorder->inspected = $inspected;
        $workorder->location_id = $location_id;

         $property = DB::table('services_location as sl')
            ->where('sl.id', $workorder->location_id)
            ->select(DB::raw('sl.property_id'))
            ->first();
        $property_id = $property->property_id;

        if($status == 'Pending') { //if status is not pending, can not update.
            $workorder->description = $request->get('description', '');
            $workorder->checklist_id = $request->get('checklist_id', '');
            $workorder->equipment_id = $request->get('equipment_id', '0');
            $workorder->work_order_type = $request->get('work_order_type', 'Repairs');
        }

        $workorder->estimated_duration = $estimated_duration;

        $part_group = $request->get('part_group', []);
        $part_cost = 0;
        for ($i = 0; $i < count($part_group); $i++) {
            $part_cost += $part_group[$i]['part_cost'] * $part_group[$i]['part_number'];
        }
        $workorder->part_cost = $part_cost;

        $staff_group = $request->get('staff_group', []);
        $staff_cost = 0;
        for ($i = 0; $i < count($staff_group); $i++) {
            $staff_cost += $staff_group[$i]['staff_cost'];
        }
        $workorder->staff_cost = $staff_cost;

        $workorder->save();

        $this->createStaffFromWorkOrder($property_id, $id, $staff_group);
        $this->setWorkorderStaffStatusLog($workorder, 'update workorder', 'workorder', $method);

        $this->changeWorkOrderDate($request);

    //    $this->sendWorkorderDetailToEngUsergroup($workorder, $status);

        $ret = array();
        $ret['id'] = $workorder->id;
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function updateWorkorderFromMobile(Request $request) {
        date_default_timezone_set(config('app.timezone'));

        $id = $request->get('id',0);
        $estimated_duration = $request->get('estimated_duration', 0);
        $description = $request->get('description', '');
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $workorder =  WorkOrder::find($id);

        $workorder->estimated_duration = $estimated_duration;
        $workorder->description = $description;
        $workorder->save();

        $this->setWorkorderStaffStatusLog($workorder, 'update workorder', 'workorder', $method);

        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function changeWorkOrderStatus(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cu_time = date("Y-m-d H:i:s");

        $user_id = $request->get('user_id', 0);

        $id = $request->get('id',0);
        $status = $request->get('status', 'Pending');
        $property_id = CommonUser::getPropertyID($user_id);
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $workorder =  WorkOrder::find($id);

        $prev_status = $workorder->status . '';

        $inspected = WorkOrder::isChecklistCompleted($workorder);

        if($status == 'Pending') { //if status is not pending, can not update.
            $workorder->start_date = $cu_time;
            $this->sendWorkorderDetailToEngUsergroup($workorder, 'Reopened');
        }

        if($status == 'In Progress' )
        {
            if($workorder->status == 'On Hold') // On-Hold => In Progress
            {
                $diff_date = strtotime($workorder->start_date) - strtotime($workorder->end_date);
                $start_date = date('Y-m-d H:i:s', strtotime($cu_time) + $diff_date);
                $workorder->start_date = $start_date;
            }
            else
                $workorder->start_date = $cu_time;

            if($workorder->status == 'Pending') // Pending => In Progress
            {
                $workorder->purpose_start_date = $cu_time;
            }
        }

        //If Status of work order  is hold, will change start_date and end_date.
        if($status == 'On Hold' ) {
            $this->sendWorkOrderEmailForOnHold($property_id, $id);
            $this->sendWorkorderDetailToEngUsergroup($workorder, 'On Hold');
        }
        $complete_comment = $request->get('comment', '');
        $request_id = $request->get('request_id', '');
        $request_flag = $request->get('request_flag', '');


        if($status == 'Completed' )
        {
            if( $workorder->checklist_id > 0 && $inspected == 0 )
            {
                $ret = array();
                $ret['id'] = $workorder->id;
                $ret['code'] = 201;
                $ret['message'] = "You should confirm checklist";

                return Response::json($ret);
            }
        }

        // $workorder->inspected =  $inspected;
        $workorder->end_date =  $cu_time;
        $workorder->status = $status;

        //get original status
        $start_date = strtotime($workorder->purpose_start_date);
        $end_date  = strtotime($cu_time);
        $duration = $end_date - $start_date;

        $workorder->duration = $duration;


        if( $status == 'Completed'  && empty($complete_comment) && $request_id > 0 && $request_flag == 1   )
        {
            $ret = array();
            $ret['code'] = 202;
            $ret['message'] = "You should provide complete comment";

            return Response::json($ret);
        }

        if ($status == 'Completed' && !empty($complete_comment))
        {
            DB::table('eng_workorder_status_log')->insert(['user_id' => $user_id  ,'workorder_id' => $workorder->id ,'status' => 'Custom' ,'description' => $complete_comment , 'method' => $method]);
        }

        $workorder->save();

        $workorder->complete_comment = $complete_comment;
        $workorder->attach = $request->get('attach', '');

        if($status == 'Completed' )
        {
            EquipmentPreventiveEquipStatus::updateNextDate($workorder);
            $this->sendWorkorderDetailToPreventiveUsergroup($workorder, 'Completed');
            $this->sendWorkorderDetailToEngUsergroup($workorder, 'Completed');
        }

        //update eng_part_list 's stock.
        $this->updatePartStockFromWorkOrder($workorder);
        $this->updateRelatedEngineeringStatus($workorder, $prev_status);
        $this->setWorkorderStaffStatusLog($workorder, 'workorder status', 'workorder', $method);

        WorkOrder::getWorkorderDetailWithStaff($workorder, $user_id);

        $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, 0);

        $ret = array();
        $ret['id'] = $workorder->id;
        $ret['content'] = $workorder;
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function confirmInspected(Request $request) {
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);
        $confirm_inspected = $request->get('confirm_inspected', 0);

        $workorder =  WorkOrder::find($id);

        $inspected = WorkOrder::isChecklistCompleted($workorder);

        $workorder->inspected = $inspected;
        $workorder->confirm_inspected = $confirm_inspected;

        $workorder->save();

        WorkOrder::getWorkorderDetailWithStaff($workorder, $user_id);

        $ret = array();
        $ret['id'] = $workorder->id;
        $ret['content'] = $workorder;
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function flagWorkorder(Request $request) {
        $id = $request->get('id',0);
        $favorite_flag = $request->get('favorite_flag', 0);

        $workorder =  WorkOrder::find($id);
        $workorder->favorite_flag = $favorite_flag;
        $workorder->save();

        $ret = array();
        $ret['id'] = $workorder->id;
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function changeWorkOrderDate(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cu_time = date("Y-m-d H:i:s");

        $id = $request->get('id', 0);
        $schedule_date = $request->get('schedule_date', '');

        $workorder =  WorkOrder::find($id);

        $original_schedule_date = $workorder->schedule_date . '';
        $workorder->schedule_date = $schedule_date;

        $workorder->save();

        if( $workorder->request_flag == 1 )
        {
            // set schedule date
            DB::table('eng_repair_request')
                ->where('id', $workorder->request_id)
                ->update(['schedule_date' => $schedule_date]);

            $this->sendRepairRefreshEvent($workorder->property_id, 'refresh_repair_page', $workorder, 0);
        }

        if( $original_schedule_date != $schedule_date )
            $this->sendWorkorderDetailToPreventiveUsergroup($workorder, 'Schedule Date');

        $ret = array();
        $ret['id'] = $workorder->id;
        $ret['workorder'] = $workorder;
        $ret['code'] = 200;

        return Response::json($ret);
    }

    private function updateRelatedEngineeringStatus($workorder, $prev_status)
    {
        if( empty($workorder) )
            return;

        date_default_timezone_set(config('app.timezone'));
        $cu_time = date("Y-m-d H:i:s");

        $status = $workorder->status;

        //request update(1=request, 2= work order, 3 = preventivr automatically)
        if($workorder->request_flag == 1)  // if original work order was created by request
        {
            $temp_status = $status . '';
            if( $temp_status == 'On Hold' )
                $temp_status = 'In Progress';

            $request_id = $workorder->request_id;
            DB::table('eng_repair_request')
                ->where('id', $request_id)
                ->update(['updated_at' => $cu_time, 'status_name' =>  $status ]);

            if( $status == 'In Progress' && $prev_status == 'Pending' )    // Pending => In Progress
            {
                // set start time
                DB::table('eng_repair_request')
                    ->where('id', $request_id)
                    ->update(['start_date' => $cu_time]);

                app('App\Http\Controllers\Frontend\RepairRequestController')->sendInprogressNotificaiton($request_id);
            }

            if( $status == 'In Progress' )    // Pending => In Progress
            {
                // set end time
                DB::table('eng_repair_request')
                    ->where('id', $request_id)
                    ->update([
                                'status' => 'In Progress',
                            ]);
            }

            if( $status == 'On Hold' )    // Pending => In Progress
            {
                // set end time
                DB::table('eng_repair_request')
                    ->where('id', $request_id)
                    ->update([
                                'status' => 'On Hold',
                            ]);
                app('App\Http\Controllers\Frontend\RepairRequestController')->sendOnHoldNotificaiton($request_id);
            }

            if( $status == 'Completed' )    // Pending => In Progress
            {
                // set end time
                DB::table('eng_repair_request')
                    ->where('id', $request_id)
                    ->update([
                                'end_date' => $cu_time,
                                'complete_comment' => $workorder->complete_comment,
                                'attach' => $workorder->attach,
                            ]);

                app('App\Http\Controllers\Frontend\RepairRequestController')->sendCompletedNotificaiton($request_id);
            }

            $this->sendRepairRefreshEvent($workorder->property_id, 'refresh_repair_page', $workorder, 0);
        }

        if($workorder->request_flag == 3)
        {
            if($workorder->work_order_type = "Preventive")
            {
                if($status == 'Pending')
                    $preventive_status = 1;

                if($status == 'In Progress' )
                    $preventive_status = 3;

                //If Status of work order  is hold, will change start_date and end_date.
                if($status == 'On Hold' )
                    $preventive_status = 3;

                if($status == 'Completed' )
                    $preventive_status = 2;

                DB::table('eng_preventive')
                    ->where('id', $workorder->request_id)
                    ->update(['updated_at' => $cu_time, 'preventive_status' =>  $preventive_status ]);
            }
        }
    }

    public function deleteWorkorder(Request $request){
        $id = $request->get('id', '0');
        DB::table('eng_workorder')
            ->where('id', $id)
            ->delete();
        //restore parts stock with original value because delete work order
        $part_group = $request->get('part_group',[]);
        for($i = 0 ; $i < count($part_group) ; $i++) {
            $id = $part_group[$i]['part_id'];
            $part_number = $part_group[$i]['part_number'];
            $part = PartList::whereId($id)->first();
            if(!empty($part))
                $part->quantity += $part_number ;
            $part->save();
        }

        DB::table('eng_workorder_part')
            ->where('workorder_id', $id)
            ->delete();
        DB::table('eng_workorder_staff')
            ->where('workorder_id', $id)
            ->delete();
        DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $id)
            ->delete();
        DB::table('eng_workorder_status_log')
            ->where('workorder_id', $id)
            ->delete();
        return Response::json('200');
    }

    public function uploadFilesToWorkOrder(Request $request)
    {
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);

        $filekey = 'files';
        $output_dir = "uploads/eng/";

        if(!File::isDirectory(public_path($output_dir)))
            File::makeDirectory(public_path($output_dir), 0777, true, true);

        $fileCount = count($_FILES[$filekey]["name"]);

        $input = array();
        $input['workorder_id'] = $id;
        $input['submitter_id'] = $user_id;

        for ($i = 0; $i < $fileCount; $i++)
        {
            $fileName = $_FILES[$filekey]["name"][$i];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $filename1 = "workorder_" . $id . '_' . $i . '_' . date('Y_m_d_H_i_s') . '.' . $ext;

            $dest_path = $output_dir . $filename1;
            move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);

            $input['filename'] = $fileName;
            $input['path'] = $dest_path;

            DB::table('eng_workorder_files')->insert($input);
        }

        $list = DB::table('eng_workorder_files')
            ->where('workorder_id', $id)
            ->get();

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function deleteFileFromWorkOrder(Request $request) {
        $id = $request->get('id', 0);

        $ret = DB::table('eng_workorder_files')
            ->where('id', $id)
            ->delete();

        $ret = array();
        $ret['code'] = 200;
        $ret['id'] = $id;

        return Response::json($ret);
    }

    public function getWorkOrderDetail(Request $request)
    {
        $cur_time = date("Y-m-d H:i:s");
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);

        $workorder = DB::table('eng_workorder as ew')
           ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
           ->leftJoin('eng_checklist as ecl', 'ew.checklist_id', '=', 'ecl.id')
           ->leftJoin('eng_repair_request as err', 'ew.request_id', '=', 'err.id')
            ->leftJoin('common_users as cua', 'err.assignee', '=', 'cua.id')
            ->leftJoin('common_users as cur', 'err.requestor_id', '=', 'cur.id')
           ->leftJoin('services_location as sl', 'ew.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
           ->where('ew.id',$id)
            ->select(DB::raw('ew.*, eel.name as equipment_name, eel.equip_id as eq_id, DATE(ew.start_date) as start_date, ecl.name as checklist_name,
                                    DATE(ew.end_date) as end_date, ew.staff_cost+ew.part_cost as staff_cost,
                                    CONCAT_WS(" ", cua.first_name, cua.last_name) as assignee_name, 
                                    CONCAT_WS(" ", cur.first_name, cur.last_name) as requestor_name, 
                                    sl.name as location_name, slt.type as location_type'))
            ->first();


        WorkOrder::getWorkorderDetailWithStaff($workorder, $user_id);

        $filelist = array();
        $filelist = $workorder->filelist;



        if ($workorder->request_flag == 1 && !empty($workorder->request_id)){
            $rep_attach = DB::table('eng_repair_request')
                    ->where('id',$workorder->request_id)
                    ->select(DB::raw('attach'))
                    ->first();
            if (!empty($rep_attach))
            {

                $attach = explode('&&',$rep_attach->attach );
                for ($i = 0; $i< count($attach); $i++){
                $filelist[] = array("id" => $i,
					  "workorder_id" => $workorder->id,
					  "submitter_id" => 1,
					  "filename" => 'repair' .  $i . '.png',
					  "path" => $attach[$i],
                      "created_at" => $cur_time);
                }

                $workorder->filelist = $filelist;
            }


        }







        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $workorder;

        return Response::json($ret);
    }

    public function getWorkorderHistoryList(Request $request)
    {
        $workorder_id = $request->get('id', 0);

        $ret = array();

        $workorder = WorkOrder::find($workorder_id);

        $data_list = DB::table('eng_workorder_status_log as el')
            ->leftJoin('common_users as cu', 'el.user_id', '=', 'cu.id')
            ->leftJoin('common_users as cu1', 'el.staff_id', '=', 'cu1.id')
            ->where('el.workorder_id', $workorder_id)
            ->orderBy('el.setdate')
            ->select(DB::raw("el.*, CONCAT_WS(\" \", cu.first_name, cu.last_name) as user_name, CONCAT_WS(\" \", cu1.first_name, cu1.last_name) as staff_name"))
            ->get();

        if ($workorder->request_flag == 1){

        $list = DB::table('eng_repair_request_comment as ec')
            ->leftJoin('eng_repair_request as err', 'ec.repair_id', '=', 'err.id')
            ->leftJoin('common_users as cu', 'ec.created_by', '=', 'cu.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->where('err.id', $workorder->request_id)
            ->select(DB::raw('ec.comment as description, ec.created_at as setdate, CONCAT_WS(" ", cu.first_name, cu.last_name) as user_name'))
            ->get();
        foreach($list as $row){

            $row->status = 'Custom';
            $row->method = 'Web';
        }
        $data_list = array_merge($data_list,$list);
       }



        $ret['code'] = 200;
        $ret['content'] = $data_list;

        return Response::json($ret);
    }

    public function updateWorkOrderHistory(Request $request)
    {
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);
        $workorder_id = $request->get('workorder_id', 0);
        $status = $request->get('status', '');
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $input = $request->except(['id', 'access_token', 'device', 'version', 'client_id', 'property_id', 'hash', 'app_pin', 'sub_domain']);

        $input['user_id'] = $user_id;
        $input['method'] = $method;

        if($id > 0)
        {
            DB::table('eng_workorder_status_log')
               ->where('id',$id)
               ->update(['description'=>$input['description']]);
        }
        else
        {
            DB::table('eng_workorder_status_log')->insert($input);
        }

        $workorder = WorkOrder::where('id', $workorder_id)
                    ->where('request_flag', 1)
                    ->first();

        if (!empty($workorder)){

        if ($workorder->request_id != 0 && $status == 'Custom')
        {
            app('App\Http\Controllers\Frontend\RepairRequestController')->sendWOCommentNotificaiton($workorder->request_id, $user_id, $input['description']);

        }

    }

        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public  function  deleteWorkorderHistory(Request $request)
    {
        $id = $request->get('id',0);
        DB::table('eng_workorder_status_log')
            ->where('id', $id)
            ->delete();
    }

   /*
    * work order staff management
    */
    public function getWorkorderStaffList(Request $request)
    {
        $property_id = $request->get('property_id', 4);
        $workorder_id = $request->get('workorder_id',0);
        $ret = array();
        $query = DB::table('eng_workorder_staff_status as es')
            ->leftJoin('common_users as cu', 'es.staff_id', '=', 'cu.id')
            ->where('es.workorder_id', $workorder_id);

        $data_query = clone $query;
        $data_list = $data_query
            ->select(DB::raw("es.*, CONCAT_WS(\" \", cu.first_name, cu.last_name) as user_name"))
            ->get();
        $ret['datalist'] = $data_list;

        // staff group
        $staff_group = DB::table('eng_workorder_staff')
            ->where('workorder_id', $workorder_id)
            ->select(DB::raw("workorder_id, staff_id, staff_name, staff_type, staff_cost"))
            ->get();

        $ret['staff_group'] = $staff_group;

        return Response::json($ret);
    }
    public function createWorkOrderStaff(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $property_id = $request->get('property_id',0);
        $workorder_id = $request->get('workorder_id',0);
        $staff_id = $request->get('staff_id',0);
        $staff_type = $request->get('staff_type','');
        $staff_cost = $request->get('staff_cost',0);
        $staff_name = $request->get('staff_name',0);

        $staff = array();
        $staff['staff_id'] = $staff_id;
        $staff['staff_name'] = $staff_name;
        $staff['staff_cost'] = $staff_cost;
        $staff['staff_type'] = $staff_type;

        $staffs = DB::table('eng_workorder_staff')
            ->where('workorder_id',  $workorder_id)
            ->select(DB::raw('staff_id, staff_name, staff_cost, staff_type'))
            ->get();

        $staff_group = array();
        for($i = 0 ;$i < count($staffs) ; $i++) {
            $staff_group[$i]['staff_id'] = $staffs[$i]->staff_id;
            $staff_group[$i]['staff_name'] = $staffs[$i]->staff_name;
            $staff_group[$i]['staff_type'] = $staffs[$i]->staff_type;
            $staff_group[$i]['staff_cost'] = $staffs[$i]->staff_cost;
        }
        array_push($staff_group, $staff);

        // 1. read  original such as workroder id  and calculate cost
        $origin = DB::table('eng_workorder_staff_status')
            ->where('workorder_id',  $workorder_id)
            ->select(DB::raw('sum(staff_cost) as cost, workorder_id'))
            ->groupBy('workorder_id')
            ->first();
        if(!empty($origin)) {
            $staff_cost_all = $origin->cost + $staff_cost;
        }else {
            $staff_cost_all = $staff_cost;
        }

        // 2. update staff_id and staff_cost,staff_type in workorder
        DB::table('eng_workorder')
            ->where('id', $workorder_id)
            ->update(['staff_cost' => $staff_cost_all]);
        // 3. insert workroder_staff_status 's state
        // 4. workorder_history

        $this->createStaffFromWorkOrder($property_id, $workorder_id, $staff_group);

        $workorder = WorkOrder::find($workorder_id);

        $this->setWorkorderStaffStatusLogIndividual($workorder, 'create staff', 'staff', $method);

        $ret = array();
        $ret['workorder'] = $workorder;

        return Response::json($ret);
    }

    public function updateWorkOrderStaff(Request $request) {
        $id = $request->get('id',0);
        $property_id = $request->get('property_id' , 4);
        $user_id = $request->get('user_id',0);
        $workorder_id = $request->get('id',0);

        $staff_id = $request->get('staff_id',0);
        $staff_cost = $request->get('staff_cost',0);
        $status = $request->get('status','');

        $staff_status = DB::table('eng_workorder_staff_status')
            ->where('workorder_id', $workorder_id)
            ->where('staff_id', $staff_id)
            ->first();


        if( $status == 'In Progress' )
        {
            if( $staff_status->status == 'Pending' )
                return $this->startWorkorder($request);
            else
                return $this->resumeWorkorder($request);
        }

        if( $status == 'On Hold' )
            return $this->holdWorkorder($request);

        if( $status == 'Completed' )
            return $this->finishWorkorder($request);

        $ret = array();

        return Response::json($ret);
    }

    public  function  deleteWorkorderStaff(Request $request) {
        $id = $request->get('id',0);
        $workorder_id = $request->get('workorder_id', 0);
        $status = $request->get('status','');
        $user_id = $request->get('user_id',0);
        $staff_id = $request->get('staff_id',0);

        $method = Functions::getRequestMethod($request->get('device_id', ''));

        DB::table('eng_workorder_staff_status')
            ->where('id', $id)
            ->delete();
        DB::table('eng_workorder_staff')
            ->where('workorder_id', $workorder_id)
            ->where('staff_id', $staff_id)
            ->delete();

        $method = Functions::getRequestMethod($request->get('device_id', ''));

        //1. delete staff and  repeat the calculation staff_cost
        $origin = DB::table('eng_workorder_staff_status')
            ->where('workorder_id',  $workorder_id)
            ->select(DB::raw('sum(staff_cost) as cost, workorder_id'))
            ->groupBy('workorder_id')
            ->first();
        if(!empty($origin)) {
            $staff_cost = $origin->cost;
        }else {
            $staff_cost = 0;
        }
        //2. change work order staff_cost
        DB::table('eng_workorder')
            ->where('id', $workorder_id)
            ->update(['staff_cost' => $staff_cost]);
        //3. report log
        $this->setWorkOrderStatusLog($workorder_id, '', $status, $user_id, $staff_id , 'delete staff', 'staff', $method );
    }

   /*
    * get preventive maintenance list
    */
   public function getPreventiveMaintenanceList(Request $request) {
        $property_id = $request->get('property_id', 4);

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $searchoption = $request->get('searchoption','');
        $searchtext = $request->get('searchtext', '');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');
        $type_array = $request->get('type_array', []);
        $equip_group_array = $request->get('equip_group_array', []);
        $staff_array = $request->get('staff_array', []);
        $status_array = $request->get('status_array', []);
        $inspection = $request->get('inspection', 0);

        $start = microtime(true);

        if ($pageSize < 0)
            $pageSize = 20;

        $ret = array();
        $query = DB::table('eng_preventive as ep')
            ->leftJoin('eng_checklist as ec', 'ep.checklist_id', '=', 'ec.id')
            ->leftJoin('eng_preventive_status as eps', 'ep.preventive_status', '=', 'eps.id')
            ->leftJoin('eng_equip_group as eeg', 'ep.equip_id', '=', 'eeg.id')
            ->leftJoin('eng_equip_list as el', 'ep.equip_id', '=', 'el.id')
            ->leftJoin('eng_preventive_staff as epst', 'ep.id', '=', 'epst.preventive_id');

        $query->whereRaw(sprintf("DATE(ep.next_date_time) >= '%s' and DATE(ep.next_date_time) <= '%s'", $start_date, $end_date));
        $query->where('ep.property_id', $property_id);
        $query->where('deleted_flag', 0);

        if($searchtext != '') {
            $where = sprintf("ep.name like '%%%s%%' or
                                    ep.type like '%%%s%%' or
                                    ec.name like '%%%s%%'",
                $searchtext, $searchtext, $searchtext
            );
            $query->whereRaw($where);
        }

        // Filter

        // Type Filter
        if( count($type_array) > 0 )
            $query->whereIn('ep.type', $type_array);

        // Equip Group Filter
        $equip_id_array = [];
        $equip_group_id_array = [];
        foreach($equip_group_array as $row)
        {
            if($row['type'] == 'group')
                $equip_group_id_array[] = $row['id'];
            else
                $equip_id_array[] = $row['id'];
        }

        $whereRaw = "(";
        if( count($equip_id_array) > 0 )
        {
            $str = implode(",", $equip_id_array);
            $whereRaw .= "ep.equip_id IN ($str) AND ep.equip_type = 'single'";
        }
        else
        {
            if( count($equip_group_id_array) > 0 )
                $whereRaw .= "1!=1";
            else
                $whereRaw .= "1=1";
        }

        if( count($equip_group_id_array) > 0 )
        {
            $str = implode(",", $equip_group_id_array);
            $whereRaw .= " OR ep.equip_id IN ($str) AND ep.equip_type = 'group'";
        }
        $whereRaw .= ")";

        $query->whereRaw($whereRaw);

        // Staff Filter
        if( count($staff_array) > 0 )
        {
            $whereIn = implode(",", array_map(function($item) {
                return "'" . $item['id'] . $item['type'] . "'";
            }, $staff_array));
            $whereRaw = "CONCAT(epst.staff_id, epst.staff_type) IN ($whereIn)";

            $query->whereRaw($whereRaw);
        }

        // Status Filter
        if( count($status_array) > 0 )
            $query->whereIn('ep.preventive_status', $status_array);

        // inspection filter
        if( $inspection > 0 )
            $query->where('ep.inspection', $inspection);

        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->groupBy('ep.id')
            ->select(DB::raw("ep.*,  ec.name as checklist_name , eps.name as preventive_status_name, 
                                (CASE WHEN ep.equip_type = 'group' THEN eeg.name ELSE el.name END) as equip_name"))
            ->skip($skip)->take($pageSize)
            ->get();

        foreach($data_list as $row)
        {
            $id = $row->id;

            $row->parts =  DB::table('eng_preventive_part as epp')
                ->leftJoin('eng_part_group as epg', 'epp.part_id', '=', 'epg.id')
                ->leftJoin('eng_part_list as epl', 'epp.part_id', '=', 'epl.id')
                ->where('epp.preventive_id', $id)
                ->select(DB::raw("epp.part_id as id, epp.part_type as type, epp.part_quantity as quantity,
                                (CASE WHEN epp.part_type = 'group' THEN epg.name ELSE epl.name END) as name"))
                ->get();

            $row->staffs =  DB::table('eng_preventive_staff as eps')
                ->leftJoin('common_user_group as cug', 'eps.staff_id', '=', 'cug.id')
                ->leftJoin('common_users as cu', 'eps.staff_id', '=', 'cu.id')
                ->where('eps.preventive_id', $id)
                ->select(DB::raw("eps.staff_id as id, eps.staff_type as type, CONCAT(eps.staff_name, '-', eps.staff_type) as text,
                                (CASE WHEN eps.staff_type = 'group' THEN cug.name ELSE CONCAT_WS(\" \", cu.first_name, cu.last_name) END) as name"))
                ->get();

            if( !empty($row->user_group_ids) )
            {
                $row->user_group_tags = DB::table('common_user_group')
                    ->whereRaw("id IN ($row->user_group_ids)")
                    ->get();

                $row->notifiers = implode(",", array_map(function($item) {
                    return $item->name;
                }, $row->user_group_tags->toArray()));
            }
            else
            {
                $row->user_group_tags = [];
                $row->notifiers = '';
            }
       }

       $count_query = clone $query;
       $totalcount = $count_query->count();

       $end = microtime(true);

       $ret['datalist'] = $data_list;
       $ret['totalcount'] = $totalcount;
       $ret['totalcount1'] = count($data_list);
       $ret['time'] = $end - $start;
       $ret['whereRaw'] = $whereRaw;

       return Response::json($ret);
   }

   public function getPreventiveMaintenanceListForMobile(Request $request) {

        $property_id = $request->get('property_id', 4);

        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');

        $start_date = $request->get('start_date',  date("Y-m-d") );
        // 
        $start_date = '2022-01-01';
        $end_date = $request->get('end_date', '');

        $ret = array();
        $query = DB::table('eng_preventive as ep')
            ->leftJoin('eng_checklist as ec', 'ep.checklist_id', '=', 'ec.id')
            ->leftJoin('eng_preventive_status as eps', 'ep.preventive_status', '=', 'eps.id')
            ->leftJoin('eng_equip_group as eeg', 'ep.equip_id', '=', 'eeg.id')
            ->leftJoin('eng_equip_list as el', 'ep.equip_id', '=', 'el.id')
            ->leftJoin('eng_preventive_staff as epst', 'ep.id', '=', 'epst.preventive_id');

        $query->whereRaw(sprintf("DATE(ep.created_at) >= '%s'", $start_date));
        $query->where('ep.property_id', $property_id);
        $query->where('deleted_flag', 0);

        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->groupBy('ep.id')
            ->select(DB::raw("ep.*,  ec.name as checklist_name , eps.name as preventive_status_name, 
                                (CASE WHEN ep.equip_type = 'group' THEN eeg.name ELSE el.name END) as equip_name"))
            ->get();

        foreach($data_list as $row)
        {
            $id = $row->id;

            $row->parts =  DB::table('eng_preventive_part as epp')
                ->leftJoin('eng_part_group as epg', 'epp.part_id', '=', 'epg.id')
                ->leftJoin('eng_part_list as epl', 'epp.part_id', '=', 'epl.id')
                ->where('epp.preventive_id', $id)
                ->select(DB::raw("epp.part_id as id, epp.part_type as type, epp.part_quantity as quantity,
                                (CASE WHEN epp.part_type = 'group' THEN epg.name ELSE epl.name END) as name"))
                ->get();

            $row->staffs =  DB::table('eng_preventive_staff as eps')
                ->leftJoin('common_user_group as cug', 'eps.staff_id', '=', 'cug.id')
                ->leftJoin('common_users as cu', 'eps.staff_id', '=', 'cu.id')
                ->where('eps.preventive_id', $id)
                ->select(DB::raw("eps.staff_id as id, eps.staff_type as type, CONCAT(eps.staff_name, '-', eps.staff_type) as text,
                                (CASE WHEN eps.staff_type = 'group' THEN cug.name ELSE CONCAT_WS(\" \", cu.first_name, cu.last_name) END) as name"))
                ->get();

            if( !empty($row->user_group_ids) )
            {
                $row->user_group_tags = DB::table('common_user_group')
                    ->whereRaw("id IN ($row->user_group_ids)")
                    ->get();

                $row->notifiers = implode(",", array_map(function($item) {
                    return $item->name;
                }, $row->user_group_tags));
            }
            else
            {
                $row->user_group_tags = [];
                $row->notifiers = '';
            }
        }

        $ret['code'] = 200;
        $ret['content'] = $data_list;
        $ret['sync_minibar'] = 0;
        $ret['message'] = '';

        return Response::json($ret);
    }


   public function PreventiveperiodList(Request $request)
   {
       $list = DB::table('eng_equip_maintenance_period as ps')
           ->select(DB::raw('ps.id,  ps.period as period'))
           ->get();

       $ret = array();
       $ret['datalist'] = $list;
       return Response::json($ret);

   }
    public function PreventivestatusList(Request $request)
    {
        $list = DB::table('eng_preventive_status as ps')
            ->select(DB::raw('ps.id,  ps.name as status_name'))
            ->get();

        $ret = array();
        $ret['datalist'] = $list;
        return Response::json($ret);

    }

    //create preventive maintenance
    public function createPreventiveMaintenance(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $created_at = date("Y-m-d H:i:s");
        $input = $request->all();
        $parts = $request->get('parts',[]);
        $staffs = $request->get('staffs',[]);
        $id = $input['id'];
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $preventive =  PreventiveList::find($id);
        if( empty($preventive) )
        {
            $preventive = new PreventiveList();
            $preventive->created_at = $created_at;
        }

        $preventive->property_id = $input['property_id'];
        $preventive->type = $input['type'];
        $preventive->name = $input['name'];
        $preventive->equip_id = $input['equip_id'];
        $preventive->equip_type = $input['equip_type'];
        $preventive->checklist_id = $input['checklist_id'];
        $preventive->description = $input['description'];
        $preventive->start_mode = $input['start_mode'];
        $preventive->frequency = $input['frequency'];
        $preventive->frequency_unit = $input['frequency_unit'];
        $preventive->start_date = $input['start_date'];

        if( $preventive->frequency > 0 && $preventive->frequency_unit != 'Weeks' )
        {
            $preventive->next_date_time = date('Y-m-d', strtotime("$preventive->frequency $preventive->frequency_unit", strtotime($preventive->start_date)));
        }

        if( $preventive->frequency_unit == 'Weeks' ) // weekly generated
        {
            $next_sunday = date('Y-m-d 12:00:00', strtotime('next sunday', strtotime($preventive->start_date)));
            $preventive->next_date_time = $next_sunday;
        }

        $preventive->preventive_status = 1;  //  1 Due , 2 Done , 3 In-Progress

        $preventive->inspection = $input['inspection'];
        $preventive->user_group_ids = $input['user_group_ids'];
        $preventive->sms = $input['sms'];
        $preventive->email = $input['email'];
        $preventive->reminder = $input['reminder'];
        $preventive->save();

        $id = $preventive->id;

        // add preventive parts
        EquipmentPreventivePart::addPartGroupData($id, $parts);

        // staff
        $staff_group = EquipmentPreventiveStaff::addStaffGroupData($id, $staffs);
        $this->sendNotifyFromPreventive($preventive, $staff_group, $input['email'], $input['sms']);

        $workorder = $this->generateWorkorderFromPreventive($preventive, $method);
        $preventive->next_date_time = $workorder->purpose_end_date;

        $ret = array();
        $ret['id'] = $id;

        return Response::json($ret);
    }

    public function createWorkorderManual(Request $request) {
        $preventive = $request->get('pms', array());
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        for( $i = 0; $i < count($preventive); $i++ )
        {
            $pm = $preventive[$i];

            $preventive_id =  PreventiveList::find($pm['id']);

            $workorder = $this->generateWorkorderFromPreventive($preventive_id, $method);
            $preventive_id->next_date_time = $workorder->purpose_end_date;
        }

        return Response::json($preventive);

    }

    public function createWorkorderManualForMobile(Request $request) {
        $preventive_id = $request->get('id', 0);
        $equip_type = $request->get("equip_type", "single");
        $equip_ids = $request->get("equip_ids", "");

        if ($preventive_id === 0) {
            $ret['code'] = 201;
            $ret['message'] = "There is not id";
            return Response::json($ret);
        }

        $equip_ids = explode(",", $equip_ids);

        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $preventive =  PreventiveList::find($preventive_id);

        // $workorder = $this->generateWorkorderFromPreventive($preventive, $method);
        $workorder = $this->generateWorkorderFromPreventiveForMobile($preventive, $method, $equip_type, $equip_ids);
        $preventive->next_date_time = $workorder->purpose_end_date;
        $ret['code'] = 200;
        $ret['content'] = [];
        $ret['message'] = '';

        return Response::json($ret);
    }

    private function sendNotifyFromPreventive($preventive, $staff_group, $email_flag, $sms_flag)
    {
        foreach( $staff_group as $row)
        {
            $userlist = array();
            if($row['staff_type'] == 'group')
            {
                $userlist = DB::table('common_user_group_members as cm')
                    ->leftjoin('common_users as cu', 'cm.user_id', '=', 'cu.id')
                    ->where('group_id', $row['staff_id'])
                    ->where('cu.deleted', 0)
                    ->select(DB::raw('cu.id, cu.mobile, cu.email'))
                    ->get();
            }
            else if($row['staff_type'] == 'single') {
                $userlist = DB::table('common_users as cu')
                    ->where('id', $row['staff_id'])
                    ->where('cu.deleted', 0)
                    ->select(DB::raw('cu.id, cu.mobile, cu.email'))
                    ->get();
            }

            if($email_flag == 1)
            {
                for($e = 0 ; $e < count($userlist) ; $e++) {
                    $this->sendEmailFromPreventive($preventive->property_id, $userlist[$e]->email, $preventive->name);
                }
            }
            if($sms_flag == 1)
            {
                for($s =0 ; $s < count($userlist) ; $s++)
                {
                    $this->sendSMSFromPreventive($userlist[$s]->mobile,  $preventive->name);
                }
            }
        }
    }

    public function deletePreventiveMaintenance(Request $request) {
        $id = $request->get('id', '0');
        $preventive =  PreventiveList::find($id);
        $preventive->deleted_flag = 1;
        $preventive->save();

        // DB::table('eng_preventive_part')
        //     ->where('preventive_id', $id)
        //     ->delete();
        // DB::table('eng_preventive_staff')
        //     ->where('preventive_id', $id)
        //     ->delete();
        return Response::json('200');
    }

    public function sendSMSFromPreventive($number, $preventive_name)
    {
        $message = array();
        $message['type'] = 'sms';
        $message['to'] = $number;
        $message['subject'] = 'Hotlync Notification ';
        $message['content'] = '<b>' . $preventive_name . '</b>  for preventive maintenance is created.';

        Redis::publish('notify', json_encode($message));
    }

    public function sendEmail(Request $request) {
        $to = $request->get('to','');
        $title = $request->get('title','');
        $content = $request->get('content','');
        $property_id = $request->get('property_id','');

        $message = array();
        $smtp = Functions::getMailSetting($property_id, 'notification_');
        $message['smtp'] = $smtp;

        $message['type'] = 'email';
        $message['to'] = $to;
        $message['subject'] = 'HotLync equipment maintenance';
        $message['title'] = $title;
        $message['content'] = $content;
        //$message['attach'] = array($admin_call_path);
        Redis::publish('notify', json_encode($message));
        return Response::json($request);
    }

    public function sendEmailFromPreventive($property_id, $user_email, $preventive_name)
    {
        $smtp = Functions::getMailSetting($property_id, 'notification_');
        $message = array();
        $message['type'] = 'email';
        $message['to'] = $user_email;
        $message['subject'] = 'HotLync Notification';
        $message['title'] = 'You have preventive maintenance';
        $message['content'] = '<b>' . $preventive_name . '</b>  for preventive maintenance is created.';
        $message['smtp'] = $smtp;

        Redis::publish('notify', json_encode($message));
    }

    public function getWorkorderFilterList(Request $request) {
        $user_id = $request->get('user_id', 0);

        $list = ['ALL', "Pending", "In Progress", "On Hold", "Completed"];

        $status_ids = [0, 1];

        $state_list = array();
        foreach($list as $key => $row)
        {
            $state = array('name' => $row, 'id' => ($key - 1));
            if( !empty($status_ids) && in_array($state['id'], $status_ids) )
                $state['checked'] = 1;
            else
                $state['checked'] = 0;
            array_push($state_list, $state);
        }

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $state_list;
        $ret['message'] = '';

        return Response::json($ret);
    }

    private function getDateRange($date, &$start_date, &$end_date)
    {
        date_default_timezone_set(config('app.timezone'));
        $end_date = date("Y-m-d 23:59:59");

        switch($date)
        {
            case 'D':
                $start_date = date('Y-m-d 00:00:00', strtotime('-1 days'));
                break;
            case 'W':
                $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case 'M':
                $start_date = date('Y-m-d 00:00:00', strtotime('-1 months'));
                break;
            case 'Y':
                $start_date = date('Y-m-d 00:00:00', strtotime('-1 years'));
                break;
        }
    }

    private function sendWorkorderDetailToPreventiveUsergroup($workorder, $notify_type)
    {
        if( empty($workorder) )
            return;

        if( $workorder->work_order_type != 'Preventive' )
            return;

        $preventive = PreventiveList::find($workorder->request_id);

        if( empty($preventive) )
            return;

        if( empty($preventive->user_group_ids) )
            return;

        $user_list = DB::table('common_users as cu')
            ->join('common_user_group_members as ugm', 'cu.id', '=', 'ugm.user_id')
            ->whereRaw("ugm.group_id IN ($preventive->user_group_ids)")
            ->select(DB::raw('cu.*'))
            ->groupBy('cu.email')
            ->get();

        Log::info(json_encode($user_list));

        foreach($user_list as $row)
        {
            $this->sendWorkorderDetailToUser($preventive, $workorder, $row, $notify_type);
        }

        return $user_list;
    }

    private function sendWorkorderDetailToUser($preventive, $workorder, $row, $notify_type)
    {
        $workorder = DB::table('eng_workorder as ew')
            ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
            ->leftJoin('services_location as sl', 'ew.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
            ->where('ew.id', $workorder->id)
            ->select(DB::raw("ew.*,  eel.name as equipment_name, eel.equip_id as eq_id,
                                ew.staff_cost + ew.part_cost as staff_cost,
                                sl.name as location_name, slt.type as location_type
                    " ))
            ->first();

        if( empty($workorder) )
            return;

        $staff_group = DB::table('eng_workorder_staff')
            ->where('workorder_id',$workorder->id)
            ->select(DB::raw("workorder_id, staff_id, staff_name, staff_type, staff_cost"))
            ->get();
        $workorder->assigne_list_names = WorkOrder::getAssigneListNames($workorder, $staff_group);

        $info = array();
        $info['first_name'] = "$row->first_name $row->last_name";
        $info['guest_name'] = $row->last_name;
        $info['name'] = $workorder->name;
        $info['description'] = $workorder->description;
        $info['due_date'] = $workorder->due_date;
        $info['start_date'] = $workorder->start_date;
        $info['end_date'] = $workorder->end_date;
        $info['schedule_date'] = $workorder->schedule_date;
        $info['equipment'] = $workorder->equipment_name;
        $info['equip_id'] = $workorder->eq_id;
        $info['location'] = "$workorder->location_name $workorder->location_type";
        $info['assignee_list'] =  $workorder->assigne_list_names;

        $smtp = Functions::getMailSetting($workorder->property_id, 'notification_');



        $message = array();
        $message['type'] = 'email';
        $message['to'] = $row->email;
        switch($notify_type)
        {
            case 'Created':
            $message['subject'] = sprintf("%s: Work Order - %s has been Created", WorkOrder::getDailyId(($workorder)), $workorder->name);
            $info['desc'] = sprintf("Work Order - %s has been Created.", $workorder->name);
                break;
            case 'Schedule Date':
            $message['subject'] = sprintf("%s: Work Order - %s has been Scheduled", WorkOrder::getDailyId(($workorder)), $workorder->name);
            $info['desc'] = sprintf("Work Order - %s has been Scheduled.", $workorder->name);
                break;
            case 'Completed':
            $message['subject'] = sprintf("%s: Work Order - %s has been Completed", WorkOrder::getDailyId(($workorder)), $workorder->name);
            $info['desc'] = sprintf("Work Order - %s has been Completed.", $workorder->name);
                break;
            default:
            $message['subject'] = sprintf("%s: Work Order - %s has been Created", WorkOrder::getDailyId(($workorder)), $workorder->name);
            $info['desc'] = sprintf("Work Order - %s has been Created.", $workorder->name);
                break;
        }

        $email_content = view('emails.preventive_reminder', ['info' => $info])->render();

        $message['content'] = $email_content;
        $message['smtp'] = $smtp;


        // Log::info($email_content);

        Redis::publish('notify', json_encode($message));


        $payload = array();
		$payload['table_id'] = $workorder->id;
		$payload['table_name'] = 'eng_workorder';
		$payload['property_id'] = $preventive->property_id;
		$payload['notify_type'] = 'workorder';
        $payload['type'] = 'Work Order ' . $notify_type;
        $payload['header'] = 'Engineering';

        $message = $message['subject'];
        Functions::sendPushMessgeToDeviceWithRedisNodejs(
                    $row, $workorder->id,'Workorder', $message, $payload
            );
    }

    private function sendWorkorderDetailToEngUsergroup($workorder, $notify_type)
    {
        if( empty($workorder) )
            return;

         // get user list
         $rules = array();
         $rules['eng_user_group_ids']  = 4;
         $rules['eng_contract_expire_days']  = 4;

         $property_id = $workorder->property_id;

         $rules = PropertySetting::getPropertySettings($property_id, $rules);
         $user_group = $rules['eng_user_group_ids'];
    //     $user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_user_group_ids']);
    //     $user_group_emails = implode(";", array_map(function($item) {
    //         return $item->email;
    //     }, $user_list));

        $user_list = DB::table('common_users as cu')
            ->join('common_user_group_members as ugm', 'cu.id', '=', 'ugm.user_id')
            ->whereRaw("ugm.group_id IN ($user_group)")
            ->select(DB::raw('cu.*'))
            ->groupBy('cu.email')
            ->get();

        Log::info(json_encode($user_list));



        foreach($user_list as $row)
        {
            $this->sendWorkorderDetailToEngUser( $workorder, $row, $notify_type);
        }

        return $user_list;
    }

    private function sendWorkorderDetailToEngUser( $workorder, $row, $notify_type)
    {
        $workorder = DB::table('eng_workorder as ew')
            ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
            ->leftJoin('services_location as sl', 'ew.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
            ->where('ew.id', $workorder->id)
            ->select(DB::raw("ew.*,  eel.name as equipment_name, eel.equip_id as eq_id,
                                ew.staff_cost + ew.part_cost as staff_cost,
                                sl.name as location_name, slt.type as location_type
                    " ))
            ->first();

        if( empty($workorder) )
            return;

        if ($workorder->checklist_id > 0)
            $attached_flag  = 1;
        else
            $attached_flag = 0;

        $staff_group = DB::table('eng_workorder_staff')
            ->where('workorder_id',$workorder->id)
            ->select(DB::raw("workorder_id, staff_id, staff_name, staff_type, staff_cost"))
            ->get();
        $workorder->assigne_list_names = WorkOrder::getAssigneListNames($workorder, $staff_group);


        $data = $this->getWorkorderCheckListDataForEmail($workorder->id);

        ob_start();

        $filename = 'Workorder_Checklist_' . date('d_M_Y_H_i') . '_' . $data['name'];
        $folder_path = public_path() . '/uploads/reports/';
        $path = $folder_path . $filename . '.html';
        $pdf_path = $folder_path . $filename . '.pdf';

        
        $content = view('frontend.report.workorder_checklist_pdf', compact('data'))->render();
        echo $content;
        file_put_contents($path, ob_get_contents());

        ob_clean();

        $options = array();
        $options['html'] = $path;
        $options['pdf'] = $pdf_path;
        $options['paperSize'] = array('format' => 'A4', 'orientation' => 'portrait');
        $options['attach_flag'] = $attached_flag;

        $user = DB::table('common_users as cu')
            ->where('cu.email', $row->email)
            ->select(DB::raw('cu.*'))
            ->first();

        $info = array();
        $info['first_name'] = "$user->first_name $user->last_name";
    //    $info['guest_name'] = $row->last_name;
        $info['name'] = $workorder->name;
        $info['description'] = $workorder->description;
        $info['start_date'] = $workorder->start_date;
        $info['end_date'] = $workorder->end_date;
        $info['due_date'] = $workorder->due_date;
        $info['schedule_date'] = $workorder->schedule_date;
        $info['equipment'] = $workorder->equipment_name;
        $info['equip_id'] = $workorder->eq_id;
        $info['location'] = "$workorder->location_name $workorder->location_type";
        $info['assignee_list'] =  $workorder->assigne_list_names;
        $info['notify'] = $notify_type;

        switch($notify_type)
        {
            case 'Created':
                $info['desc'] = sprintf("Work Order - %s has been Created.", $workorder->name);
                break;

            case 'On Hold':
                $info['desc'] = sprintf("Work Order - %s has been On Hold.", $workorder->name);
                break;

            case 'Completed':
                $info['desc'] = sprintf("Work Order - %s has been Completed.", $workorder->name);
                break;

            case 'Reopened':
                $info['desc'] = sprintf("Work Order - %s has been Reopened.", $workorder->name);
                break;

            default:
                $info['desc'] = sprintf("Work Order - %s has been Created.",  $workorder->name);
                break;
        }

        $smtp = Functions::getMailSetting($workorder->property_id, 'notification_');

        $email_content = view('emails.workorder_reminder', ['info' => $info])->render();

        $message = array();
        $message['smtp'] = $smtp;
        switch($notify_type)
        {
            case 'Created':
            $message['subject'] = sprintf("%s: Work Order - %s has been Created", WorkOrder::getDailyId(($workorder)), $workorder->name);
            $message['desc'] = sprintf("Work Order - %s has been Created", $workorder->name);
                break;

            case 'On Hold':
            //    $message['subject'] = sprintf('W0-%05d - %s has been On Hold', $workorder->id, $workorder->name);
                $message['subject'] = sprintf("%s: Work Order - %s has been On Hold", WorkOrder::getDailyId(($workorder)), $workorder->name);
                $message['desc'] = sprintf("Work Order - %s has been On Hold", $workorder->name);
                break;

            case 'Completed':
            $message['subject'] = sprintf("%s: Work Order - %s has been Completed", WorkOrder::getDailyId(($workorder)), $workorder->name);
                break;

            case 'Reopened':
            $message['subject'] = sprintf("%s: Work Order - %s has been Reopened", WorkOrder::getDailyId(($workorder)), $workorder->name);
                break;

            default:
            $message['subject'] = sprintf("%s: Work Order - %s has been Created", WorkOrder::getDailyId(($workorder)), $workorder->name);
                break;
        }

        $request = array();
        $request['subject'] = $message['subject'];
        $request['smtp'] = $smtp;
        if ($attached_flag == 1  && $notify_type == 'Completed'){
            $request['to'] = $row->email;
            $request['content'] = $email_content;
            $request['html'] = $message['subject'];
            $request['filename'] = $filename . '.pdf';
            $request['options'] = $options;
            $message['type'] = 'report_pdf';
            $message['content'] = $request;
        }else{
            
            $message['type'] = 'email';
            $message['to'] = $row->email;
            $message['content'] = $email_content;
        
        }

        // Log::info($email_content);

        Redis::publish('notify', json_encode($message));


        $payload = array();
		$payload['table_id'] = $workorder->id;
		$payload['table_name'] = 'eng_workorder';
		$payload['property_id'] = $workorder->property_id;
		$payload['notify_type'] = 'workorder';
        $payload['type'] = 'Work Order ' . $notify_type;
        $payload['header'] = 'Engineering';

        $message = $message['subject'];
        Functions::sendPushMessgeToDeviceWithRedisNodejs(
                    $row, $workorder->id, 'Workorder', $message, $payload
            );
    }

    public function getMyWorkorderStatusCount(Request $request)
    {
        $date = $request->get('date', 'D');
        $user_id = $request->get('user_id', 0);

        $property_id = CommonUser::getPropertyID($user_id);

        $this->getDateRange($date, $start_date, $end_date);

        $ret = array();

        $select_sql = 'count(*) as total';

        $is_supervisor = CommonUser::isValidModule($user_id, 'mobile.workorder.supervisor');

        if( $is_supervisor )
        {
            // get possible status
            $status_list = Functions::getFieldValueList('eng_workorder', 'status');
            foreach($status_list as $key => $row)
            {
                $select_sql .= ",COALESCE(sum(ew.status = '$row'), 0) as cnt$key";
            }
        }
        else
        {
            // get possible status
            $status_list = Functions::getFieldValueList('eng_workorder_staff_status', 'status');
            foreach($status_list as $key => $row)
            {
                $select_sql .= ",COALESCE(sum(wss.status = '$row'), 0) as cnt$key";
            }
        }

        $query = DB::table('eng_workorder as ew')
            ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
            ->leftJoin('eng_checklist as ecl', 'ew.checklist_id', '=', 'ecl.id');

        if( $is_supervisor == false )
        {
            $query->join('eng_workorder_staff_status as wss', function($join) use ($user_id) {
                $join->on('ew.id', '=', 'wss.workorder_id');
                $join->on('wss.staff_id','=', DB::raw($user_id));
            });
        }

        $count = $query->where('ew.property_id', $property_id)
            ->whereRaw(sprintf(" ( DATE(ew.created_date) >= '%s' AND DATE(ew.created_date) <= '%s' )", $start_date, $end_date))
            ->select(DB::raw($select_sql))
            ->first();

        $list = array();

        $list[] = [
            'name' => 'All',
            'count' => $count->total,
        ];

        foreach($status_list as $key => $row)
        {
            $list[] = [
                'name' => $row,
                'count' => $count->{"cnt$key"},
            ];
        }

        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function getMyWorkorderList(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));

        $date = $request->get('date', 'D');
        $user_id = $request->get('user_id', 0);
        $mine_flag = $request->get('mine_flag', 0);

        $property_id = CommonUser::getPropertyID($user_id);

        $this->getDateRange($date, $start_date, $end_date);

        $ids = $request->get('ids', '0');

        $page_size = $request->get('page_size', 10);
        $status_list = $request->get('status_list', "");
        $type_list = $request->get('type_list', "");
        $favorite_flag = $request->get('favorite_flag', 0);
        $orderby = $request->get('orderby', "me");
        $search = $request->get('search', "");

        $ret = array();

        $query = DB::table('eng_workorder as ew')
            ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
            ->leftJoin('eng_checklist as ecl', 'ew.checklist_id', '=', 'ecl.id')
            // ->join('eng_workorder_staff_status as wss', 'ew.id', '=', 'wss.workorder_id')
            ->leftJoin('eng_repair_request as err', 'ew.request_id', '=', 'err.id')
            ->leftJoin('common_users as cua', 'err.assignee', '=', 'cua.id')
            ->leftJoin('common_users as cur', 'err.requestor_id', '=', 'cur.id')
            ->leftJoin('services_location as sl', 'ew.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
            ->leftJoin('eng_workorder_staff_status as wss', function($join) use ($user_id) {
                $join->on('ew.id', '=', 'wss.workorder_id');
                $join->on('wss.staff_id','=', DB::raw($user_id));
            })
            ->whereRaw(sprintf(" ( DATE(ew.created_date) >= '%s' AND DATE(ew.created_date) <= '%s' )", $start_date, $end_date))
            ->where('ew.property_id', $property_id);

        // status filter
        if( !empty($status_list) )
        {
            $status_array = explode(",", $status_list);
            $is_supervisor = CommonUser::isValidModule($user_id, 'mobile.workorder.supervisor');
            if( $is_supervisor == true )
                $query->whereIn('ew.status', $status_array);
            else
                $query->whereIn('wss.status', $status_array);
        }

        // type filter
        if( !empty($type_list) )
        {
            $type_array = explode(",", $type_list);
            $query->whereIn('ew.work_order_type', $type_array);
        }

        if( $favorite_flag > 0 )
            $query->where('ew.favorite_flag', $favorite_flag);

        if( $mine_flag == 1 )
        {
            $query->where(function ($subquery) use ($user_id) {
                $subquery->where('wss.staff_id', $user_id)
                        ->orWhere('err.assignee', $user_id);
            });
        }


        // search filter
        if( !empty($search) )
            $query->whereRaw("(ew.id = '$search' OR ew.name like '%$search%')");

        $data_query = clone $query;

        // id list
        $id_list = explode(",", $ids);
        if( !empty($ids) )
            $data_query->whereNotIn('ew.id', $id_list);

        $orderby_priority = "FIELD(ew.priority,'Urgent','High','Medium','Low')";

        switch($orderby)
        {
            case "due_date":
                $data_query->orderBy('ew.due_date', 'desc');
                $data_query->orderByRaw($orderby_priority);
                break;
            case "priority":
                $data_query->orderByRaw($orderby_priority);
                $data_query->orderBy('ew.due_date', 'desc');
                break;
        }

        $data_query->orderBy('ew.id');

        $data_list = $data_query
            ->select(DB::raw('ew.*,  eel.name as equipment_name, eel.equip_id as equip_id, ew.location_id, ecl.name as checklist_name,
                                ew.staff_cost + ew.part_cost as staff_cost,
                                DATEDIFF(CURTIME(), ew.start_date) as age_days,
                                DATE(ew.end_date) as end_date, CONCAT_WS(" ", cua.first_name, cua.last_name) as assignee_name, 
                                CONCAT_WS(" ", cur.first_name, cur.last_name) as requestor_name, 
                                sl.name as location_name, slt.type as location_type, wss.staff_id
                                ' ))
            ->take($page_size)
            ->get();

        for($i = 0 ; $i < count($data_list) ; $i++)
        {
            WorkOrder::getWorkorderDetail($data_list[$i]);
        }

        // get work order staff status
        foreach($data_list as $row)
        {
            $staff_status = DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $row->id)
                ->where('staff_id', $user_id)
                ->first();

            if( empty($staff_status) )
                $row->staff_status = 'Not Assigned';
            else
                $row->staff_status = $staff_status->status;
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['code'] = 200;
        $ret['content'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    public function getMyWorkorderListByStaff(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $last24 = date('Y-m-d H:i:s', strtotime(' -1 day'));

        $staff_id = $request->get('staff_id', 0);
        $property_id = $request->get('property_id', 4);
        //$page_size = $request->get('page_size', 0);
        //$filter = $request->get('filter', "");

        $ret = array();

        $query = DB::table('eng_workorder as ew')
            ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
            ->leftJoin('eng_checklist as ecl', 'ew.checklist_id', '=', 'ecl.id')
            ->join('eng_workorder_staff_status as wss', 'ew.id', '=', 'wss.workorder_id')
            ->where('wss.staff_id', $staff_id);

        $query->where('ew.property_id', $property_id);


        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy('ew.id', 'desc')
            ->select(DB::raw("ew.*,  eel.name as equipment_name, ew.location_id, ecl.name as checklist_name,
                                ew.staff_cost+ew.part_cost as staff_cost " ))
            //CONCAT_WS(\" \", cu.first_name, cu.last_name) as staff_name"))

            ->get();


        for($i = 0 ; $i < count($data_list) ; $i++) {
            $location_id = $data_list[$i]->location_id;
            $location = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($location_id);
            $data_list[$i]->equipment_location = $location;
            $part_group = DB::table('eng_workorder_part')
                ->where('workorder_id',$data_list[$i]->id)
                ->select(DB::raw("workorder_id, part_id, part_name, part_number, part_cost, part_stock, part_number as part_number_original"))
                ->get();
            $data_list[$i]->part_group = $part_group;
            $staff_group = DB::table('eng_workorder_staff')
                ->where('workorder_id',$data_list[$i]->id)
                ->select(DB::raw("workorder_id, staff_id, staff_name, staff_type, staff_cost"))
                ->get();
            $data_list[$i]->staff_group = $staff_group;

        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['code'] = 200;
        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    public function getCategoryList(Request $request)
    {
        $checklist_id = $request->get('checklist_id', 0);

        $list = EquipmentCheckListCategory::getCategoryList($checklist_id);
        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function createChecklistCategory(Request $request)
    {
        $checklist_id = $request->get('checklist_id', 0);
        $name = $request->get('name', '');
        $order_id = $request->get('order_id', 0);

        $ret = array();
        $ret['code'] = 200;
        if( empty($name) )
        {
            $ret['code'] = 201;
            $ret['message'] = 'Category name cannot be empty';
            return Response::json($ret);
        }

        // check duplicated name
        $exist = DB::table('eng_checklist_category')
            ->where('name', $name)
            ->where('checklist_id', $checklist_id)
            ->exists();

        if( empty($name) )
        {
            $ret['code'] = 202;
            $ret['message'] = 'Category name cannot be duplicated';
            return Response::json($ret);
        }

        $input = array();

        $input['name'] = $name;
        $input['order_id'] = $order_id;
        $input['checklist_id'] = $checklist_id;

        $id = DB::table('eng_checklist_category')->insertGetId($input);

        $ret['list'] = DB::table('eng_checklist_category')->get();
        $ret['id'] = $id;
        $ret['name'] = $name;

        return Response::json($ret);
    }

    private function getChecklistItem($checklist_id)
    {
        return DB::table('eng_checklist_pivot as a')
                            ->join('eng_checklist_item as b', 'a.item_id', '=', 'b.id')
                            ->leftJoin('eng_checklist_category as c', 'b.category_id', '=', 'c.id')
                            ->where('a.checklist_id', $checklist_id)
                            ->select(DB::raw('b.*, c.name as category_name'))
                            ->get();
    }

    public function createChecklistItem(Request $request)
    {
        $id = $request->get('id', 0);
        $category_id = $request->get('category_id', 0);
        $name = $request->get('name', '');
        $type = $request->get('type', 1);
        $checklist_id = $request->get('checklist_id', 0);
        $order_id = $request->get('order_id', 0);

        $input = array();

        $input['category_id'] = $category_id;
        $input['name'] = $name;
        $input['type'] = $type;

        if( $id > 0 )
            DB::table('eng_checklist_item')->where('id', $id)->update($input);
        else
            $id = DB::table('eng_checklist_item')->insertGetId($input);

        $input = array();
        $input['checklist_id'] = $checklist_id;
        $input['item_id'] = $id;

        $exists = DB::table('eng_checklist_pivot')
            ->where('checklist_id', $checklist_id)
            ->where('item_id', $id)
            ->exists();

        if( $exists == false )
            DB::table('eng_checklist_pivot')->insertGetId($input);

        // set category order
        $input = array();
        $input['order_id'] = $order_id;
        DB::table('eng_checklist_category')
            ->where('id', $category_id)
            ->update($input);

        $ret = array();

        $ret['list'] = WorkOrder::getChecklistItem($checklist_id);

        $ret['code'] = 200;
        $ret['id'] = $id;
        $ret['name'] = $name;

        return Response::json($ret);
    }

    public function getCheckListItemList(Request $request)
    {
        $checklist_id = $request->get('checklist_id', 0);

        $ret = array();

        $ret['code'] = 200;
        $ret['list'] = WorkOrder::getChecklistItem($checklist_id);

        return Response::json($ret);
    }

    public function deleteCheckListItem(Request $request)
    {
        $id = $request->get('id', 0);
        $checklist_id = $request->get('checklist_id', 0);

        DB::table('eng_checklist_pivot')
                ->where('checklist_id', $checklist_id)
                ->where('item_id', '=', $id)
                ->delete();

        $ret = array();

        $ret['code'] = 200;
        $ret['list'] = WorkOrder::getChecklistItem($checklist_id);

        return Response::json($ret);
    }

    private function createChecklistForWorkorder($workorder)
    {
        if( empty($workorder) )
            return;

        $checklist_id = $workorder->checklist_id;

        $checklist_item_list = WorkOrder::getChecklistItem($checklist_id);

        DB::table('eng_workorder_checklist')
            ->where('workorder_id', $workorder->id)
            ->delete();

        foreach($checklist_item_list as $row)
        {
            $input = array();

            $input['workorder_id'] = $workorder->id;
            $input['item_id'] = $row->id;

            DB::table('eng_workorder_checklist')->insertGetId($input);
        }
    }

    public function getChecklistForWorkorder(Request $request)
    {
        $workorder_id = $request->get('workorder_id', 0);

        $workorder = WorkOrder::find($workorder_id);

        $checklist_item_list = WorkOrder::getChecklistItem($workorder->checklist_id);

        foreach($checklist_item_list as $row)
        {
            $exist = DB::table('eng_workorder_checklist')
                ->where('workorder_id', $workorder_id)
                ->where('item_id', $row->id)
                ->exists();

            if( $exist == true )
                continue;

            $input = array();

            $input['workorder_id'] = $workorder->id;
            $input['item_id'] = $row->id;

            DB::table('eng_workorder_checklist')->insertGetId($input);
        }

        $ret = array();

        $ret['code'] = 200;

        $data = array();
        $data['inspected'] = WorkOrder::isChecklistCompleted($workorder) ? 1 : 0;
        $data['list'] = WorkOrder::getChecklistResult($workorder_id);

        $ret['content'] = $data;


        return Response::json($ret);
    }

    public function getWorkorderCheckListDataForReport(Request $request)
    {
        $id = $request->get('id', 0);

        $workorder = WorkOrder::find($id);

        $ret = array();

        $ret['code'] = 200;
        $ret['name'] = sprintf("WO-%05d %s", $id, $workorder->name);
		$ret['report_date'] = date('d M Y');
        $ret['checklist'] = WorkOrder::getChecklistResult($id);

        $path = $_SERVER["DOCUMENT_ROOT"] . '/images/tick.png';
        $data = file_get_contents($path);
        $base64 = 'data:image/png;base64,' . base64_encode($data);

        $ret['tick_icon_base64'] = $base64;

        $path = $_SERVER["DOCUMENT_ROOT"] . '/images/cancel.png';
        $data = file_get_contents($path);
        $base64 = 'data:image/png;base64,' . base64_encode($data);
        $ret['cancel_icon_base64'] = $base64;


        return $ret;
    }

    private function getWorkorderCheckListDataForEmail($id)
    {
     //   $id = $request->get('id', 0);

        $workorder = WorkOrder::find($id);

        $ret = array();

        $ret['code'] = 200;
        $ret['name'] = sprintf("WO-%05d %s", $id, $workorder->name);
		$ret['report_date'] = date('d M Y');
        $ret['checklist'] = WorkOrder::getChecklistResult($id);

        $path = $_SERVER["DOCUMENT_ROOT"] . '/images/tick.png';
        $data = file_get_contents($path);
        $base64 = 'data:image/png;base64,' . base64_encode($data);

        $ret['tick_icon_base64'] = $base64;

        $path = $_SERVER["DOCUMENT_ROOT"] . '/images/cancel.png';
        $data = file_get_contents($path);
        $base64 = 'data:image/png;base64,' . base64_encode($data);
        $ret['cancel_icon_base64'] = $base64;


        return $ret;
    }


    public function updateChecklistForWorkorder(Request $request)
    {
        $workorder_id = $request->get('workorder_id', 0);
        $item_id = $request->get('item_id', 0);

        $item_type = $request->get('item_type', 'Yes/No');

        $yes_no = $request->get('yes_no', 0);
        $reading = $request->get('reading', '');

        $input = array();

        $input['check_flag'] = 1;

        switch($item_type)
        {
            case 'Yes/No':
                $input['yes_no'] = $yes_no;
                break;
            case 'Reading':
                $input['reading'] = $reading;
                break;
        }

        DB::table('eng_workorder_checklist')
                ->where('workorder_id', $workorder_id)
                ->where('item_id', $item_id)
                ->update($input);

        $workorder =  WorkOrder::find($workorder_id);

        // check inspected result
        $inspected = WorkOrder::isChecklistCompleted($workorder);
        $workorder->inspected = $inspected ? 1 : 0;

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $workorder;

        return Response::json($ret);
    }

    public function updateChecklistCommentForWorkorder(Request $request)
    {
        $workorder_id = $request->get('workorder_id', 0);
        $item_id = $request->get('item_id', 0);
        $comment = $request->get('comment', '');

        $input = array();

        $input['check_flag'] = 1;
        $input['comment'] = $comment;

        DB::table('eng_workorder_checklist')
                ->where('workorder_id', $workorder_id)
                ->where('item_id', $item_id)
                ->update($input);

        $ret = array();

        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function updateChecklistAttachForWorkorder(Request $request)
    {
        $workorder_id = $request->get('workorder_id', 0);
        $item_id = $request->get('item_id', 0);
        $attached = $request->get('attached', '');

        $input = array();

        $input['check_flag'] = 1;
        $input['attached'] = $attached;

        DB::table('eng_workorder_checklist')
                ->where('workorder_id', $workorder_id)
                ->where('item_id', $item_id)
                ->update($input);

        $ret = array();

        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function uploadAttachForChecklist(Request $request)
    {
        $id = $request->get('id', 0);

        $filekey = 'files';
        $output_dir = "uploads/eng/";

        if(!File::isDirectory(public_path($output_dir)))
            File::makeDirectory(public_path($output_dir), 0777, true, true);

        $fileCount = count($_FILES[$filekey]["name"]);

        $list = [];

        for ($i = 0; $i < $fileCount; $i++)
        {
            $fileName = $_FILES[$filekey]["name"][$i];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $filename1 = "workorder_" . $id . '_' . $i . '_' . date('Y_m_d_H_i_s') . '.' . $ext;

            $dest_path = $output_dir . $filename1;
            move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);

            $list[] = $dest_path;
        }

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function updateChecklistOneItemForWorkorder(Request $request)
    {
        $workorder_id = $request->get('workorder_id', 0);
        $item_id = $request->get('item_id', 0);

        $item_type = $request->get('item_type', 'Yes/No');
        $yes_no = $request->get('yes_no', 0);
        $reading = $request->get('reading', '');
        $comment = $request->get('comment', '');
        $attached = $request->get('attached', '');

        $input = array();

        $input['check_flag'] = 1;

        switch($item_type)
        {
            case 'Yes/No':
                $input['yes_no'] = $yes_no;
                break;
            case 'Reading':
                $input['reading'] = $reading;
                break;
        }

        $input['comment'] = $comment;
        $input['attached'] = $attached;

        DB::table('eng_workorder_checklist')
                ->where('workorder_id', $workorder_id)
                ->where('item_id', $item_id)
                ->update($input);

        $workorder =  WorkOrder::find($workorder_id);

        // check inspected result
        $inspected = WorkOrder::isChecklistCompleted($workorder);
        $workorder->inspected = $inspected ? 1 : 0;

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $workorder;

        return Response::json($ret);
    }


    public function updateChecklistBatchForWorkorder(Request $request)
    {
        $checklist_str = $request->get('checklist', '');
        $checklist = json_decode($checklist_str);

        Log::info($checklist_str);

        foreach($checklist as $row)
		{
            $workorder_id = $row->workorder_id;
            $item_id = $row->item_id;
            $item_type = $row->item_type;
            $yes_no = $row->yes_no;
            $reading = $row->reading;
            $comment = $row->comment;
            $attached = $row->attached;

            $input = array();

            $input['check_flag'] = 1;

            switch($item_type)
            {
                case 'Yes/No':
                    $input['yes_no'] = $yes_no;
                    break;
                case 'Reading':
                    $input['reading'] = $reading;
                    break;
            }

            $input['comment'] = $comment;
            $input['attached'] = $attached;

            DB::table('eng_workorder_checklist')
                    ->where('workorder_id', $workorder_id)
                    ->where('item_id', $item_id)
                    ->update($input);
        }

        $ret = array();

        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function exportWorkorderList(Request $request)
    {
        $property_id = $request->get('property_id',4);
        $searchtext = $request->get('searchtext','');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');

        $dispatcher = $request->get('dispatcher', '');

        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');

        $assigne_ids = $request->get('assigne_ids', '');
        $wr_ids = $request->get('wr_ids', '');
        $priority = $request->get('priority', '');
        $work_order_type = $request->get('work_order_type', 'All');
        $location_ids = $request->get('location_ids','');
        $equip_list = $request->get('equip_list', []);

        $property_list = CommonUser::getPropertyIdsByJobroleids($dispatcher);

        $ret = array();
        $query = DB::table('eng_workorder as ew')
            ->leftJoin('eng_equip_list as eel', 'ew.equipment_id', '=', 'eel.id')
            ->leftJoin('eng_checklist as ecl', 'ew.checklist_id', '=', 'ecl.id')
            ->leftJoin('eng_repair_request as err', 'ew.request_id', '=', 'err.id')
            ->leftJoin('common_users as cua', 'err.assignee', '=', 'cua.id')
            ->leftJoin('services_location as sl', 'ew.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
            ->leftJoin('common_property as cp', 'ew.property_id', '=', 'cp.id')
        //    ->where('ew.property_id', $property_id)
            ->whereRaw(sprintf("DATE(ew.created_date) >= '%s' and DATE(ew.created_date) <= '%s'", $start_date, $end_date));

         $query->whereIn('ew.property_id', $property_list);
        /*
                if( !empty($searchtext) )
                {
                    $query->whereRaw("(ew.id LIKE '%%$searchtext%%' OR ew.name LIKE '%%$searchtext%%' OR ew.description LIKE '%%$searchtext%%' OR err.ref_id LIKE '%%$searchtext%%')");
                }
        */
         if($searchtext != '')
        {
            $query->where(function ($query) use ($searchtext) {
                $value = '%' . $searchtext . '%';
                $query->where('ew.id', 'like', $value)
                    ->orWhere('ew.name', 'like', $value)
                    ->orWhere('ew.description', 'like', $value)
                    ->orWhere('err.ref_id', 'like', $value);

            });
        }

        // Priority filter
        if( $priority != 'All')
            $query->where('ew.priority', $priority);

        // type filter
        if( $work_order_type != 'All' )
            $query->where('ew.work_order_type', $work_order_type);

         // location filter
         if( !empty($location_ids) )
         {
             $location_id_list = explode(',', $location_ids);
             $query->whereIn('ew.location_id', $location_id_list);
         }

         // wrid filter
        if( !empty($wr_ids) )
        {
            $wr_id_list = explode(',', $wr_ids);
            $query->whereIn('err.ref_id', $wr_id_list);
        }

        $data_query = clone $query;
        $data_list = $data_query
        ->orderBy($orderby, $sort)
        ->select(DB::raw('ew.*, DATEDIFF(CURTIME(), ew.start_date) as age_days, eel.image_url as picture,  eel.name as equipment_name, eel.equip_id as eq_id, ew.start_date, ecl.name as checklist_name,
                            ew.end_date, CONCAT_WS(" ", cua.first_name, cua.last_name) as assignee_name, err.assignee,
                            sl.name as location_name, slt.type as location_type' ))
        // ->skip($skip)->take($pageSize)
        ->get();

      for($i = 0 ; $i < count($data_list) ; $i++) {
            WorkOrder::getWorkorderDetail($data_list[$i]);
        }

        if( !empty($assigne_ids) )
        {
            $assignee_id_list = explode(',', $assigne_ids);

            // assignee filter
            $data_list = array_filter($data_list, function($row) use ($assignee_id_list) {
                return (count(array_filter($row->assigne_list, function($row1) use ($assignee_id_list) {
                    return in_array($row1->id, $assignee_id_list);
                })) > 0);
            });

        }


        $data_list = array_merge(is_array($data_list) ? $data_list : $data_list->toArray() , array());

        // equipment group filter
        if( !empty($equip_list) )
        {
            $data_list = array_filter($data_list, function($row) use ($equip_list) {
                return count(array_filter($equip_list, function($row1) use ($row) {
                    return ($row1['id'] == $row->equipment_id && $row1['type'] == 'single' ||
                                !empty($row->equip_group) && $row1['id'] == $row->equip_group->id && $row1['type'] == 'group' );
                })) > 0;
            });
        }


        $data_list = array_merge($data_list, array());

        foreach( $data_list as $data){

            $data->comments = DB::table('eng_workorder_status_log as ewl')
                            ->leftJoin('common_users as cu', 'ewl.user_id', '=', 'cu.id')
                            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
                            ->where('ewl.workorder_id', $data->id)
                            ->where('ewl.status','=', 'Custom')
                            ->select(DB::raw('ewl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, jr.job_role'))
                            ->get();
        }



        $ret['code'] = 200;
        $ret['message'] = '';
        $ret['datalist'] = $data_list;


        // excel report

        $filename = 'Workorder_Report_' . $start_date . '_' . $end_date;
        $excel_type = $request->get('excel_type', 'excel');

        $excel_file_type = 'csv';
		if($excel_type == 'excel')
			$excel_file_type = config('app.report_file_type');

        $property = DB::table('common_property')->where('id', $property_id)->first();
		if (empty($property)) {
			echo "Property does not exist";
			return;
        }

        $data = $ret;
        $data['start_date'] = $start_date;
        $data['end_date'] = $end_date;


		$logo_path = $property->logo_path;

        $export_data = [];
        $datalist = [];
        $height = [];
        $row_num = 2;
        $style = [
            1    => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            2    => [
                'font' => ['bold' => true, 'family' => 'Tahoma',],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID, 
                    'startColor' => ['argb' => 'ECEFF1']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
        ];

        foreach($data['datalist'] as $row)
        {
            $arr = [];
            $com = '';
            $row_num++;
            $lfcr = chr(10);

            for($p=0; $p < count($row->comments) ;$p++) {
                $com .= $row->comments[$p]->description . ' - ' . $row->comments[$p]->wholename . ' - ' . $row->comments[$p]->setdate  . $lfcr;

            }

            $arr['ID'] = 'WR' . WorkOrder::getDailyId($row);
            $arr['Name'] =  $row->name ;
            $arr['Description'] =  $row->description ;
            $arr['Priority'] =  $row->priority ;
            $arr['Type'] =  $row->work_order_type ;
            $arr['Status'] =  $row->status ;
            $arr['Asset'] =  "$row->eq_id - $row->equipment_name" ;
            $arr['Location'] =  "$row->location_name - $row->location_type" ;
            $arr['Start Date'] =  $row->status=='In Progress' || $row->status=='Completed' ? $row->start_date : '' ;
            $arr['End Date'] =  $row->status=='Completed' ? $row->end_date : '' ;
            $arr['Total Time'] =  $row->time_spent ;
            $arr['Actual Time'] =  $row->hold_time != '' ? gmdate('H:i:s' , $row->actual_time) : $row->time_spent ;
            $arr['Assigned Staff'] =  $row->assigne_list_names ;
            $arr['Comments'] =  $com;

            $style[$row_num] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
            $height[$row_num] = 30;
            array_push($datalist, $arr);
        } 

        $export_data['datalist'] = $datalist;
        $export_data['height'] = $height;
        $export_data['sub_title'] = "Work Order List";

        return Excel::download(new CommonExport('excel.common_export', $export_data, 'Work Order List', $style), 'Work_Order_Report.xlsx');
		// Excel::create($filename, function($excel) use ($logo_path, $data) {

		// 	$excel->sheet('Workorder Report', function($sheet) use ($data, $logo_path) {
		// 		$sheet->setOrientation('landscape');

        //         $row_num = 1;

        //         $sheet->mergeCells('A' . $row_num . ':N' . $row_num);
        //         $sheet->cell('A' . $row_num, function($cell) {
		// 		$cell->setValue('Work Order List');
		// 		$cell->setAlignment('center');
		// 		$cell->setFont(array(
		// 				'size'       => '11',
		// 				'bold'       =>  true
		// 		));
		// 	    });

        //         $row_num += 1;

        //         $sheet->cell('A' . $row_num . ':N' . $row_num, function ($cell) {
        //             $cell->setFontColor('#212121');
        //             $cell->setBackground('#ECEFF1');
        //             $cell->setAlignment('center');
        //             $cell->setFont(array(
        //                     'family'     => 'Tahoma',
        //                     'bold'       =>  true
        //             ));
        //         });

        //         $header_list = ['ID','Name','Description', 'Priority', 'Type', 'Status', 'Asset','Location', 'Start Date', 'End Date', 'Total Time', 'Actual Time','Assigned Staff', 'Comments'];
        //         $sheet->row($row_num, $header_list);
        //         $row_num += 1;

        //         foreach($data['datalist'] as $row)
        //         {
        //             $row_num++;

        //             $com = '';
        //             $lfcr = chr(10);

        //             for($p=0; $p < count($row->comments) ;$p++) {
        //                 $com .= $row->comments[$p]->description . ' - ' . $row->comments[$p]->wholename . ' - ' . $row->comments[$p]->setdate  . $lfcr;

        //             }

        //             $this->setMergeRowText($sheet,'WR' . WorkOrder::getDailyId($row), $row_num, 'A', 0, 30, 30);
        //             $this->setMergeRowText($sheet, $row->name , $row_num, 'B', 0, 30, 30);
        //             $this->setMergeRowText($sheet, $row->description , $row_num, 'C', 0, 30, 30);
        //             $this->setMergeRowText($sheet, $row->priority , $row_num, 'D', 0, 30, 30);
        //             $this->setMergeRowText($sheet, $row->work_order_type , $row_num, 'E', 0, 30, 30);
        //             $this->setMergeRowText($sheet, $row->status , $row_num, 'F', 0, 30, 30);
        //             $this->setMergeRowText($sheet, "$row->eq_id - $row->equipment_name" , $row_num, 'G', 0, 30, 30);
        //             $this->setMergeRowText($sheet, "$row->location_name - $row->location_type" , $row_num, 'H', 0, 30, 30);
        //             $this->setMergeRowText($sheet, $row->status=='In Progress' || $row->status=='Completed' ? $row->start_date : '' , $row_num, 'I', 0, 30, 30);
        //             $this->setMergeRowText($sheet, $row->status=='Completed' ? $row->end_date : '' , $row_num, 'J', 0, 30, 30);
        //             $this->setMergeRowText($sheet, $row->time_spent , $row_num, 'K', 0, 30, 30);
        //             $this->setMergeRowText($sheet, $row->hold_time != '' ? gmdate('H:i:s' , $row->actual_time) : $row->time_spent , $row_num, 'L', 0, 30, 30);
        //             $this->setMergeRowText($sheet, $row->assigne_list_names , $row_num, 'M', 0, 60, 40);
        //             $this->setMergeRowText($sheet, $com , $row_num, 'N', 0, 60, 40);
        //         }
        //     });

		// })->export($excel_file_type);

        // return Response::json($ret);
    }

    private function setMergeRowText($sheet,$value1, $row_num, $col_num, $count, $width, $height) {
		if($count>1)
		{
		$sheet->mergeCells($col_num. $row_num .':'.$col_num. ($row_num+$count-1));
		}
		$sheet->cell($col_num. $row_num, function ($cell) use ($value1) {
			$cell->setValue($value1);
			$cell->setAlignment('center');
			$cell->setValignment('center');

		});
		$sheet->getStyle($col_num . $row_num)->getAlignment()->setWrapText(true);
		$sheet->setWidth($col_num, $width);
		if($count>1)
		{
		$height_row=($height/$count);
		if($count>3 &&($height_row<=12))
		{
		$height_row+=12;
		}
		for($i=$row_num;$i<($row_num+$count);$i++)
		{
		$sheet->setHeight($i, $height_row);
		}
		}
		else
			$sheet->setHeight($row_num, $height);

		// $sheet->mergeCells('C' . $row_num . ':T' . $row_num);
		// $sheet->cell('C' . $row_num, function ($cell) use ($value1) {
			// $cell->setValue($value1);
		// });


	}

    private function sendRepairRefreshEvent($property_id, $type, $info, $user_id)
	{
		$data = array();

		$data['property_id'] = $property_id;
		$data['user_id'] = $user_id;
		$data['sub_type'] = $type;
		$data['info'] = $info;

		// send web push
		$message = array();
		$message['type'] = 'repair_request';
		$message['data'] = $data;

		Redis::publish('notify', json_encode($message));
    }

    private function sendWorkorderRefreshEvent($property_id, $type, $info, $user_id)
	{
		$data = array();

		$data['property_id'] = $property_id;
		$data['user_id'] = $user_id;
		$data['sub_type'] = $type;
		$data['info'] = $info;

		// send web push
		$message = array();
		$message['type'] = 'workorder_status';
		$message['data'] = $data;

		Redis::publish('notify', json_encode($message));
	}

    public function getWOCommentList(Request $request) {
        
        $query = DB::table('eng_workorder_comments as et');
        
        $list = $query->select(DB::raw('et.*'))
            ->get();

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function checkPreventive(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $list = DB::table('eng_preventive_equip_status as es')
            ->join('eng_preventive as ep', 'es.preventive_id', '=', 'ep.id')
        //   ->whereRaw("DATE(es.next_date) = '$cur_date'")
            ->select(DB::raw("ep.*, es.equip_id, es.next_date"))
            ->get();

        

        foreach($list as $row)
        {
            $smtp = Functions::getMailSetting($row->property_id, 'notification_'); 
            if ($row->reminder != 0){
            $end_date = date('Y-m-d', strtotime("$row->reminder days"));

            $user_list = CommonUser::getUserListByEmailFromUserGroup($row->user_group_ids);    
            $user_group_emails = implode(";", array_map(function($item) {
                return $item->email;
            }, $user_list));

            if ($row->equip_type == 'Group'){
                $equip_name = DB::table('eng_equip_group')->where('id',$row->equip_id)->first();
                $row->equip_name = $equip_name->name;
            }else{
                $equip_name = DB::table('eng_equip_list')->where('id',$row->equip_id)->first();
                $row->equip_name = $equip_name->name;
                $row->equip_id = $equip_name->equip_id;
            }

            if ($end_date == $row->next_date){

                $info = array();

                $info['name'] = 'All';
                $info['equip_name'] = $row->equip_name;
                $info['equip_id'] = $row->equip_id;
                $info['equip_type'] = $row->equip_type;        
                $info['next_date'] = $row->next_date;
                $info['pv_name'] = $row->name;
                $info['description'] = $row->description;
                
                $email_content = view('emails.preventive_notify_reminder', ['info' => $info])->render();
        
                $message = array();
                $message['type'] = 'email';
                $message['subject'] = "Preventive Maintenance Reminder" ;      
                $message['content'] = $email_content;
                
                $message['smtp'] = $smtp;

                // send email to user groups
                if( !empty($user_group_emails) )
                {
                    $message['to'] = $user_group_emails;      
                    Redis::publish('notify', json_encode($message)); 
                }

            }
            }
          
        }

        echo json_encode($list);
    }


}
