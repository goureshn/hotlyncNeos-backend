<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Eng\WorkOrder;
use \Illuminate\Database\QueryException;
use App\Models\Common\PropertySetting;
use App\Models\Common\CommonUser;
use App\Models\Eng\EngRepairRequest;
use App\Models\Eng\EngRepairStaff;
use App\Models\Eng\EngRequest;
use App\Modules\Functions;
use DateInterval;
use Mail;
use DateTime;
use Excel;
use DB;
use Illuminate\Http\Request;
use Redis;
use Response;


class RepairRequestController extends Controller
{

    public function getMaxID(Request $request) {
		$max_id = DB::table('eng_repair_request')
			->select(DB::raw('max(id) as max_id'))
			->first();

		return Response::json($max_id);
    }

    public function repairrequestList(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page * $pageSize;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $property_id = $request->get('property_id', '0');
        $searchtext = $request->get('searchtext','');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');
        $dispatcher = $request->get('dispatcher', '');
        $priority = $request->get('priority', '');

     //   $assigne_ids = $request->get('assigne_ids', '');
        $assigne_ids = $request->get('assigne_ids', '');

        $tempStatus_names = $request->get('status_names', []);

        $status_name = $request->get('status_name', '');

        $status_names = [];
        foreach ($tempStatus_names as $index => $tempStatus_name) {
            $status_names[] = $tempStatus_name['name'];
        }
        $category_ids = $request->get('category_ids', []);
        $dept_ids = $request->get('dept_ids', []);
        $location_ids = $request->get('location_ids', []);
        $equipment_ids = $request->get('equipment_ids', []);
        $equip_ids = $request->get('equip_ids', []);

        $property_list = CommonUser::getPropertyIdsByJobroleids($dispatcher);

        if ($pageSize < 0)
            $pageSize = 20;

        $ret = array();
        $query = DB::table('eng_repair_request as er')
            ->leftJoin('eng_request_category as erc', 'er.category_id', '=', 'erc.id')
            ->leftJoin('eng_request_subcategory as ers', 'er.sub_category_id', '=', 'ers.id')
            ->leftJoin('eng_equip_list as eq', 'er.equipment_id', '=', 'eq.id')
            ->leftJoin('common_users as cu', 'er.requestor_id', '=', 'cu.id')
            ->leftJoin('common_users as cua', 'er.assignee', '=', 'cua.id')
            ->leftJoin('eng_supplier as es', 'er.supplier_id', '=', 'es.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('common_property as cp', 'er.property_id', '=', 'cp.id')
            ->leftjoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->leftJoin('services_location as sl', 'er.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
            ->leftJoin('eng_contracts as ec', 'er.requestor_id', '=', 'ec.id')
            ->leftJoin('eng_tenant as et', 'er.requestor_id', '=', 'et.id')
            ->leftJoin('eng_workorder as ew', function($join) {
                $join->on('ew.request_id', '=', 'er.id');
                $join->on('ew.request_flag','=', DB::raw(1));
            });

        $query->leftJoin('eng_repair_staff as ersf', 'ersf.request_id', '=', 'er.id');

        //    ->where('er.property_id', $property_id)
        $query->whereIn('er.property_id', $property_list)
            ->whereRaw(sprintf("DATE(er.created_at) >= '%s' and DATE(er.created_at) <= '%s'", $start_date, $end_date))
            ->where('er.delete_flag', 0);

        // ->where('time', '>', $last_time);
        $sub_count_query = clone $query;

        if($searchtext != '')
        {
            $query->where(function ($query) use ($searchtext) {
                $value = '%' . $searchtext . '%';
                $query->where('er.id', 'like', $value)
                    ->orWhere('er.repair', 'like', $value)
                    ->orWhere('cp.name', 'like', $value)
                    ->orWhere('cu.first_name', 'like', $value)
                    ->orWhere('eq.name', 'like', $value)
                    ->orWhere('eq.equip_id', 'like', $value)
                    ->orWhere('er.ref_id', 'like', $value)
                    ->orWhere('cu.last_name', 'like', $value);
            });
        }


   //     if( count($assigne_ids) > 0 )
   //         $query->whereIn('er.assignee', $assigne_ids);

        if( !empty($status_names) ){
            $query->whereIn('er.status_name', $status_names);
        }
        

        if (!empty($status_name) && $status_name !== 'All') {
            $query->where('er.status_name', $status_name);
        }

        if( !empty($priority) ){
            $query->where('er.priority', $priority);
        }

        if( count($category_ids) > 0 )
            $query->whereIn('er.category_id', $category_ids);

        if( count($dept_ids) > 0 )
                $query->whereIn('cd.id', $dept_ids);

        if( count($location_ids) > 0 )
            $query->whereIn('er.location_id', $location_ids);

        if( count($equipment_ids) > 0 )
            $query->whereIn('er.equipment_id', $equipment_ids);

        if( count($equip_ids) > 0 )
            $query->whereIn('eq.equip_id', $equip_ids);

        if (!empty($assigne_ids)) {
            $assigne_ids = explode(",", $assigne_ids);

            $query->whereIn('ersf.staff_id', $assigne_ids);
        }

        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->orderBy('er.created_at', 'desc')
            ->select(DB::raw('er.*, erc.name as category_name, eq.equip_id as equip_id, eq.name as equip_name,
                CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
                CONCAT_WS(" ", cua.first_name, cua.last_name) as assignee_name, ec.leasor, et.name as tenant_name,
                es.supplier, ew.id as workorder_id,
                sl.name as location_name, slt.type as location_type,
                cp.name as property_name, ersf.staff_id as staff_id'))
            ->skip($skip)->take($pageSize)
            ->groupBy('er.id')
            ->get();


        foreach($data_list as $row)
        {
            $row->staff_groups =  DB::table('eng_repair_staff as ers')
                ->leftJoin('common_user_group as cug', 'ers.staff_id', '=', 'cug.id')
                ->leftJoin('common_users as cu', 'ers.staff_id', '=', 'cu.id')
                ->where('ers.request_id', $row->id)
                ->select(DB::raw("ers.staff_id as id, ers.staff_type as type,ers.staff_name as text,
                                (CASE WHEN ers.staff_type = 'group' THEN cug.name ELSE CONCAT_WS(\" \", cu.first_name, cu.last_name) END) as name"))
                ->get();

            $row->assignee_name = implode(",", array_map(function($item) {
                return $item->text;
            }, $row->staff_groups->toArray()));

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
						COALESCE(sum(er.status_name = 'Pending'), 0) as pending,
						COALESCE(sum(er.status_name = 'On Hold'), 0) as hold,
						COALESCE(sum(er.status_name = 'In Progress'), 0) as progress,
						COALESCE(sum(er.status_name = 'Completed'), 0) as completed,
						COALESCE(sum(er.status_name = 'Rejected'), 0) as rejected						
						"))
            ->first();

        $ret['subcount'] = $subcount;

        $end = microtime(true);
        // $ret['time'] = $end - $start;

        return Response::json($ret);
    }

    public function repairrequestHistList(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $property_id = $request->get('property_id', '0');


        $user = $request->get('user_id', '');

        if ($pageSize < 0)
        $pageSize = 10;

        $ret = array();
        $query = DB::table('eng_repair_request as er')
            ->leftJoin('eng_request_category as erc', 'er.category_id', '=', 'erc.id')
            ->leftJoin('eng_request_subcategory as ers', 'er.sub_category_id', '=', 'ers.id')
            ->leftJoin('eng_equip_list as eq', 'er.equipment_id', '=', 'eq.id')
            ->leftJoin('common_users as cu', 'er.requestor_id', '=', 'cu.id')
            ->leftJoin('common_users as cua', 'er.assignee', '=', 'cua.id')
            ->leftJoin('eng_supplier as es', 'er.supplier_id', '=', 'es.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('common_property as cp', 'er.property_id', '=', 'cp.id')
            ->leftJoin('services_location as sl', 'er.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
            ->where('er.property_id', $property_id)
            ->where('er.requestor_id', $user);


        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->select(DB::raw('er.*, erc.name as category_name, eq.equip_id as equip_id, eq.name as equip_name,
                CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
                CONCAT_WS(" ", cua.first_name, cua.last_name) as assignee_name, 
                es.supplier,
                sl.name as location_name, slt.type as location_type,
                cp.name as property_name'))
            ->skip($skip)->take($pageSize)
            ->get();

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['code'] = 200;
        $ret['message'] = '';
        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;

        $end = microtime(true);
        // $ret['time'] = $end - $start;

        return Response::json($ret);
    }

    public function createRequest(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");
        $cur_time = date("Y-m-d H:i:s");

        $user_id = $request->get('user_id', 0);

        $input = array();
    //    $input["property_id"] = $request->get('property_id', 0);
        $input["priority"] = $request->get('priority', "Low");
        $input["requestor_id"] = $request->get('requestor_id', 0);
        $input["requestor_type"] = $request->get('requestor_type', '');
        $input["user_id"] = $request->get('user_id', 0);
        $input["location_id"] = $request->get('location_id', 0);

        $input["category_id"] = $request->get('category',0);
        $input["sub_category_id"] = $request->get('sub_category',0);
        $input["equipment_id"] = $request->get('equipment_id', 0);

        $input["repair"] = $request->get('repair',"");
        $input["comments"] = $request->get('description', '');
        $input["attach"] = $request->get('attach', '');

        $property = DB::table('services_location as sl')
            ->where('sl.id', $input["location_id"])
            ->select(DB::raw('sl.property_id'))
            ->first();

        $input["property_id"] = $property->property_id;

        $input["status_name"] = 'Pending'; // Pending status
        $email = $request->get('email', '');
        if( !empty($email) )    // from email
        {
            $requestor = DB::table('common_users')
                ->where('email', $email)
                ->where('deleted', 0)
                ->first();

            if( !empty($requestor) )
                $input["requestor_id"] = $requestor->id;

            $input = $this->analyzeEmailDescription($input);
        }

        // calculate max daily id for selected date
        $daily = DB::table('eng_repair_request')
            ->where('property_id', $input['property_id'])
            ->whereRaw("DATE(created_at) = '$cur_date'")
            ->select(DB::raw('max(daily_id) as max_daily_id'))
            ->first();

        $daily_id = 1;
        if( !empty($daily->max_daily_id) )
            $daily_id = $daily->max_daily_id + 1;

        $input["daily_id"] = $daily_id;

        $input["ref_id"] = sprintf('WR%s%02d', date('Ymd', strtotime($cur_date)), $daily_id);

        $repair_id = DB::table('eng_repair_request')->insertGetId($input);



        $this->sendCreationNotificaiton($repair_id, 0, 0);

        $rules = array();
        $rules['create_workorder_flag'] = false;

        $rules = PropertySetting::getPropertySettings($input["property_id"], $rules);

        if( $rules['create_workorder_flag'] == 1 )
        {
            // create work order from repair request
            date_default_timezone_set(config('app.timezone'));

            $cur_date = date("Y-m-d");

            $workorder = new WorkOrder();
            $workorder->property_id = $input["property_id"];
            $workorder->name = $input["repair"];
            $workorder->user_id = $request->get('user_id', 0);
            $workorder->priority = $input["priority"];
            $workorder->equipment_id = $input["equipment_id"];
            $workorder->location_id = $input["location_id"];
            $workorder->created_date = $cur_time;
        //    $workorder->start_date = $cur_date;
            $workorder->schedule_date = $cur_date;
            $workorder->due_date = $cur_date;
            $workorder->end_date = $cur_date;
            $workorder->description = $input["comments"];

            $workorder->purpose_start_date = $workorder->start_date;
            $workorder->purpose_end_date = $workorder->end_date;

            $workorder->daily_id = WorkOrder::getMaxDailyID($input["property_id"], $cur_date);

            $workorder->status = 'Pending';
            $workorder->work_order_type = 'Repairs';
            $workorder->request_id = $repair_id;
            $workorder->request_flag = 1; //1=requet, 2= workorder, 3= preventive automatically, default =2=workorder
            $workorder->save();

            $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, 0);
        }

        $this->sendRepairRefreshEvent($input['property_id'], 'refresh_page', $input, $user_id);

        $ret = array();

        $ret['code'] = 200;
        $ret['id'] = $repair_id;
        $ret['content'] = $repair_id;

        return Response::json($ret);
    }

    private function analyzeEmailDescription($input)
    {
        if( empty($input) )
            return $input;

        $property_id = $input["property_id"];
        $desc = $input["comments"];
        // analyze location

        $lines = explode("\n", $desc);
        foreach($lines as $row)
        {
            if( empty($row) )
            continue;

            $sub_list = explode(":", $row);
            if( count($sub_list) < 2 )
                continue;

            $item_name = trim($sub_list[0]);
            $item_value = trim($sub_list[1]);

            if( strtolower($item_name) == 'location' )
            {
                // find location
                $loc = DB::table('services_location')
                    ->where('name', $item_value)
                    ->where('property_id', $property_id)
                    ->first();
                if( !empty($loc) )
                    $input["location_id"] = $loc->id;
            }

            if( strtolower($item_name) == 'equipment' )
            {
                // find equipment
                $equip = DB::table('eng_equip_list')
                    ->where('name', $item_value)
                    ->where('property_id', $property_id)
                    ->first();
                if( !empty($equip) )
                    $input["equipment_id"] = $equip->id;
            }

            if( strtolower($item_name) == 'category' )
            {
                // find category
                $category = DB::table('eng_request_category')
                    ->where('name', $item_value)
                    ->where('property_id', $property_id)
                    ->first();
                if( !empty($category) )
                    $input["category_id"] = $category->id;
            }

            if( strtolower($item_name) == 'sub category' ||
                strtolower($item_name) == 'subcategory' )
            {
                // find sub category
                $subcategory = DB::table('eng_request_subcategory')
                    ->where('name', $item_value)
                    ->where('property_id', $property_id)
                    ->first();
                if( !empty($category) )
                    $input["sub_category_id"] = $subcategory->id;
            }
        }

        return $input;
    }

    public function updateRequest(Request $request) {
        $client_id = $request->get('client_id', 4);
     //   $property_id = $request->get('property_id', 4);
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $request_id = $request->get('id',0);
        $user_id = $request->get('user_id', 0);

        $data = EngRepairRequest::getDetail($request_id);

        $input = array();
    //    $input["property_id"] = $request->get('property_id',0);
        $input["schedule_date"] = $request->get('schedule_date', "");
        $input["repair"] = $request->get('repair', "");
        $input["priority"] = $request->get('priority', "");
        $input["category_id"] = $request->get('category',0);
        $input["sub_category_id"] = $request->get('sub_category',0);
        $input["equipment_id"] = $request->get('equipment_id',0);
        $input["location_id"] = $request->get('location_id',0);
        $input["status_name"] = $request->get('status_name', 'Pending'); // Pending status
        // $input["assignee"] = $request->get('assignee', 0);
        $staff_groups = $request->get('staff_groups',[]);
        $input["supplier_id"] = $request->get('supplier_id', 0);
        $input["reject_reason"] = $request->get('reject_reason', '');
        $input["complete_comment"] = $request->get('complete_comment', '');
        $input["estimated_duration"] = $request->get('estimated_duration', '');

        $input["repair"] = $request->get('repair', '');
        $input["comments"] = $request->get('description', '');

        $supplier_flag = $request->get('supplier_flag', false);

        if( $supplier_flag)
            $staff_groups = [];
        else
            $input["supplier_id"] = 0;

        $property = DB::table('services_location as sl')
            ->where('sl.id', $input["location_id"])
            ->select(DB::raw('sl.property_id'))
            ->first();

        $input["property_id"] = $property->property_id;
        $property_id = $property->property_id;

        if( (count($staff_groups) > 0 || $input["supplier_id"] > 0) &&
                $input["status_name"] == 'Pending' )
        {
            $input["status_name"] = 'Assigned';
        }
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $cur_date = date("Y-m-d");

        $status = $input["status_name"];
        if( $status == 'Reopen' )
            $input["status_name"] = 'Pending';

        if( $status == 'Closed' )
            $input["closed_at"] = $cur_time;

        if( $status == 'In Progress' )
            $input["start_date"] = $cur_time;

        if( $status == 'Completed' )
            $input["end_date"] = $cur_time;

        DB::table('eng_repair_request')->where('id', $request_id)->update($input);

        EngRepairStaff::addStaffGroupData($request_id, $staff_groups);

        $ret = array();
        $ret['code'] = 200;
        $ret['id'] = $request_id;
        $ret['content'] = $request_id;

        $staff_group = EngRepairStaff::getStaffGroupData($request->id);

        $workorder = WorkOrder::where('request_id', $request_id)
            ->where('property_id', $property_id)
            ->where('request_flag', 1)
            ->first();

        if(!empty($workorder))
        {
           $workorder->estimated_duration = $input["estimated_duration"];
           $workorder->name = $input["repair"];
           $workorder->description = $input["comments"];
           $workorder->save();

           if ($status == 'Reopen'){
            $workorder->status = 'Pending';
            $workorder->save();
            $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, 0);
           }

           app('App\Http\Controllers\Frontend\EquipmentController')->createStaffFromWorkOrder($property_id, $workorder->id, $staff_group);
           app('App\Http\Controllers\Frontend\EquipmentController')->setWorkorderStaffStatusLog($workorder, 'create workorder', 'workorder', $method);
        }

        $isCreateWO = $request->get('isCreateWO', false);

        $workorderUpdateDeleteFlag = WorkOrder::where('request_id', $request_id)
            ->where('property_id', $property_id)
            ->where('request_flag', 1)
            ->first();
        if(!empty($workorder)){
            if($input['status_name'] == 'Rejected'){
                $workorderUpdateDeleteFlag->delete_flag = 1;
                $workorderUpdateDeleteFlag->save();
            }
            else{
                $workorderUpdateDeleteFlag->delete_flag = 0;
                $workorderUpdateDeleteFlag->save();
            }
        }
        

        if( $isCreateWO && empty($workorder) )
        {
            $workorder = new WorkOrder();
            $ret['createdWO'] = true;
        }
        else
            $ret['createdWO'] = false;
        if( $status != 'Closed' )
        {
            if($ret['createdWO'] == true)
            {
                $workorder->property_id = $input["property_id"];
                $workorder->name = $input["repair"];
                $workorder->user_id = $request->get('user_id', 0);
                $workorder->priority = $input["priority"];
                $workorder->equipment_id = $input["equipment_id"];
                $workorder->location_id = $input["location_id"];
                $workorder->created_date = $cur_time;
            //    $workorder->start_date = $cur_date;
                $workorder->schedule_date = $input["schedule_date"];
                $workorder->due_date = $input["schedule_date"];
                $workorder->end_date = $input["schedule_date"];
                $workorder->estimated_duration = $input["estimated_duration"];
                $workorder->description = $data->comments;

                $workorder->daily_id = WorkOrder::getMaxDailyID($input["property_id"], $cur_date);

                $workorder->purpose_start_date = $workorder->start_date;
                $workorder->purpose_end_date = $workorder->end_date;

                $workorder->status = 'Pending';
                $workorder->work_order_type = 'Repairs';
                $workorder->request_id = $request_id;
                $workorder->request_flag = 1; //1=requet, 2= workorder, 3= preventive automatically, default =2=workorder

                $staff_cost = 0;
                for ($i = 0; $i < count($staff_group); $i++) {
                    $staff_cost += $staff_group[$i]['staff_cost'] ;
                }
                $workorder->staff_cost = $staff_cost;

                $workorder->save();

                $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, 0);

                // if( $data->assignee != $input["assignee"] )
                //     $this->sendWorkorderNotification($staff_group, $workorder);

                if( !empty($workorder) )
                {
                app('App\Http\Controllers\Frontend\EquipmentController')->createStaffFromWorkOrder($property_id, $workorder->id, $staff_group);
                app('App\Http\Controllers\Frontend\EquipmentController')->setWorkorderStaffStatusLog($workorder, 'create workorder', 'workorder', $method);
                }
            }


        }
        /*
            if( !empty($workorder) )
            {
                app('App\Http\Controllers\Frontend\EquipmentController')->createStaffFromWorkOrder($property_id, $workorder->id, $staff_group);
                app('App\Http\Controllers\Frontend\EquipmentController')->setWorkorderStaffStatusLog($workorder, 'create workorder', 'workorder', $method);
            }
        */
        $this->sendUpdateNotificaiton($request_id, $data);

        // if( $input['assignee'] > 0 && $input['assignee'] != $data->assignee )   // changed assigne
        //     $this->sendCreationNotificaiton($request_id, $input['assignee'], 0);

        if( $input['supplier_id'] > 0 && $input['supplier_id'] != $data->supplier_id )   // changed supplier
            $this->sendCreationNotificaiton($request_id, 0, $input['supplier_id']);

        if( $input['status_name'] == 'Completed' && $input['status_name'] != $data->status_name )       // to completed
            $this->sendCompletedNotificaiton($request_id);

        if( $input['status_name'] == 'Rejected' && $input['status_name'] != $data->status_name )       // to rejected
            $this->sendRejectedNotificaiton($request_id);

        if( $input['status_name'] == 'In Progress' && $input['status_name'] != $data->status_name )       // to inprogress
            $this->sendInprogressNotificaiton($request_id);

        if( $input['status_name'] == 'Assigned' && $input['status_name'] != $data->status_name )       // to assigned
            $this->sendAssignedNotificaiton($request_id);

        if( $input['status_name'] == 'Closed' && $input['status_name'] != $data->status_name )       // to closed
            $this->sendClosedNotificaiton($request_id);

        if( $input['status_name'] == 'On Hold' && $input['status_name'] != $data->status_name )       // to onhold
            $this->sendOnHoldNotificaiton($request_id);

        if( $status == 'Reopen')
        {
            $this->sendReopenNotificaiton($request_id);
        }

        $this->sendRepairRefreshEvent($property_id, 'refresh_repair_page', $input, $user_id);

        return Response::json($ret);
    }

    public function updateSummary(Request $request)
    {
        $id = $request->get('id', 0);
        $repair = $request->get('repair', '');

        $repair_request = EngRepairRequest::find($id);
        $repair_request->repair = $repair;

        $repair_request->save();

        $ret['code'] = 200;
        return Response::json($ret);
    }

    public function updateDescription(Request $request)
    {
        $id = $request->get('id', 0);
        $comments = $request->get('comments', '');

        $repair_request = EngRepairRequest::find($id);
        $repair_request->comments = $comments;

        $repair_request->save();

        $ret['code'] = 200;
        return Response::json($ret);
    }

    public function updateEquipment(Request $request)
    {
        $id = $request->get('id', 0);
        $equipment_id = $request->get('equipment_id', 0);

        $repair_request = EngRepairRequest::find($id);
        $repair_request->equipment_id = $equipment_id;

        $repair_request->save();

        $ret['code'] = 200;
        return Response::json($ret);
    }

    public function updateDetail(Request $request)
    {
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);
        $repair = $request->get('repair', '');
        $comments = $request->get('comments', '');
        $equipment_id = $request->get('equipment_id', 0);
        $due_date = $request->get('due_date', '');
        $status = $request->get('status', 'Pending');
        $estimated_duration = $request->get('estimated_duration', '');
        $staff_groups = $request->get('staff_groups', 0);
        $staff_groups = json_decode($staff_groups, true);
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $repair_request = EngRepairRequest::find($id);
        $repair_request->repair = $repair;
        $repair_request->comments = $comments;
        $repair_request->equipment_id = $equipment_id;
        if( !empty($due_date) )
            $repair_request->due_date = $due_date;

        if( count($staff_groups) > 0 && $repair_request->status_name == 'Pending' )
            $repair_request->status_name = 'Assigned';
        else
            $repair_request->status_name = $status;

        $repair_request->estimated_duration = $estimated_duration;

        $repair_request->save();

        EngRepairStaff::addStaffGroupData($id, $staff_groups);
        $this->createWorkorderFromRepair($repair_request, $method);

        $this->sendRepairRefreshEvent($repair_request->property_id, 'refresh_repair_page', $repair_request, $user_id);

        $ret['code'] = 200;
        $ret['content'] = $repair_request->status_name;

        return Response::json($ret);
    }

    public function getRequestorList(Request $request)
    {
        $property_id = $request->get('property_id', 0);
        $dept = $request->get('dept', '');
        $dept_id = $request->get('dept_id', 0);
        $query = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->where('cu.deleted', 0);
//			->select(DB::raw('cu.*,jr.job_role as job_role_name, cd.department, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
//			->get();
        $leasor_query = DB::table('eng_contracts as ec')
                ->select(DB::raw('ec.id, ec.leasor as wholename, ec.leasor_email as job_role_name, ec.leasor_contact as department'))
                ->get();
        foreach( $leasor_query as $leasor){
            $leasor->type  =  'Leasor';
        }

        $tenant_query = DB::table('eng_tenant as et')
                ->select(DB::raw('et.id, et.name as wholename,et.email as job_role_name, et.contact as department'))
                ->get();
        foreach( $tenant_query as $tenant){
            $tenant->type  =  'Tenant';
        }

        $user_query=$query->select(DB::raw('cu.*,jr.job_role as job_role_name, cd.department, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
            ->get();

        foreach( $user_query as $user){
                $user->type  =  'User';
        }
    //	$model1=$leasor_query->select(DB::raw('ec.leasor as wholename, ec.leasor_email, ec.leasor_contact'))
    //			->get();

        $model = array_merge($user_query->toArray(), $leasor_query->toArray(), $tenant_query->toArray());
        $model = array_unique($model, SORT_REGULAR);
        $model = array_merge($model, array());

        $ret['code'] = 200;
        $ret['content'] = $model;

        return Response::json($ret);
    }

    public function getCategoryList(Request $request) {
        $property_id = $request->get('property_id', 0);
        $user_id = $request->get('user_id', '');
        $query = DB::table('eng_request_category as rc');

        $property_list = CommonUser::getPropertyIdsByJobroleids($user_id);

        if(!empty($property_list))
            $query->whereIn('rc.property_id', $property_list);

        $list = $query->select(DB::raw('rc.id, rc.name, rc.name as category_name, rc.created_at'))
            ->get();

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $list;
        $ret['user_id'] = $user_id;

        return Response::json($ret);
    }

    public function getSubcategoryList(Request $request) {
        $category_id = $request->get('category_id', 0);

        $query = DB::table('eng_request_subcategory as rsc')
            ->leftJoin('eng_request_category as rc', 'rsc.category_id', '=', 'rc.id');

        if( $category_id > 0 )
            $query->where('rsc.category_id', $category_id);


        $list = $query->select(DB::raw('rsc.id, rsc.name, rsc.name as subcategory_name,rsc.created_at,rc.name as category_name'))
            ->get();

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function getTenantList(Request $request) {
        $property_id = $request->get('property_id', 0);
        $query = DB::table('eng_tenant as et')
        ->leftJoin('common_users as cu', 'et.added_by', '=', 'cu.id');

        if( $property_id > 0 )
            $query->where('et.property_id', $property_id);

        $list = $query->select(DB::raw('et.id, et.name as tenant_name,et.created_at, et.email, et.contact, CONCAT_WS(" ", cu.first_name, cu.last_name) as added_by'))
            ->get();

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function getTenantListEmail(Request $request) {
        $email = $request->get('email', 0);
        $query = DB::table('eng_tenant as et');
        $flag = 0;
    //    ->leftJoin('common_users as cu', 'et.added_by', '=', 'cu.id');

    //    if( $property_id > 0 )
    //        $query->where('et.property_id', $property_id);

        $list = $query->select(DB::raw('et.id, et.name as tenant_name, et.property_id'))
                ->where('et.email','=', $email)
            ->first();


        $ret = array();
        $ret['code'] = 200;


        if (!empty($list)){
            $flag = 1;
            $ret['content'] = $list;
        }
        else{
            $flag = 0;
            $ret['content'] = '';
        }

        $ret['flag'] = $flag;

        return Response::json($ret);
    }

    public function saveCategory(Request $request) {


        $input = array();
        $input["name"] = $request->get('name', "");
        $input["property_id"] = $request->get('property_id', 4);

        DB::table('eng_request_category')->insert($input);

        $query = DB::table('eng_request_category as rc');
        $list = $query->select(DB::raw('rc.id, rc.name as category_name,rc.created_at'))
            ->get();

        return Response::json($list);
    }

    public function saveSubcategory(Request $request) {
        $input = array();
        $input["name"] = $request->get('name', "");
        $input["property_id"] = $request->get('property_id', 4);
        $input["category_id"] = $request->get('category_id', 0);

        DB::table('eng_request_subcategory')->insert($input);

        $query = DB::table('eng_request_subcategory as rsc')
            ->leftJoin('eng_request_category as rc', 'rsc.category_id', '=', 'rc.id');

        if( $input["category_id"] > 0 )
            $query->where('rsc.category_id', $input["category_id"]);

        $list = $query->select(DB::raw('rsc.id,rsc.name as subcategory_name,rsc.created_at,rc.name as category_name'))
            ->get();

        return Response::json($list);
    }

    public function saveTenant(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $input = array();
        $input["name"] = $request->get('name', "");
        $input["email"] = $request->get('email', "");
        $input["contact"] = $request->get('contact', "");
        $input["property_id"] = $request->get('property_id', 4);
        $input["added_by"] = $request->get('user_id', 4);
        $input["created_at"] = $cur_time;


        $id = DB::table('eng_tenant')->insertGetId($input);

        $query = DB::table('eng_tenant as et')
                ->leftJoin('common_users as cu', 'et.added_by', '=', 'cu.id');
        $list = $query->select(DB::raw('et.id, et.name as tenant_name,et.created_at, et.email, et.contact, CONCAT_WS(" ", cu.first_name, cu.last_name) as added_by'))
            ->get();

        $ret = array();

        $input['id'] = $id;
        $input['type'] = 'Tenant';

        $ret['code'] = 200;
        $ret['list'] = $list;
        $ret['content'] = $input;

        return Response::json($ret);
    }


    public function uploadFiles(Request $request) {

        $output_dir = $_SERVER["DOCUMENT_ROOT"] . '/uploads/repair_request/';
        if(!file_exists($output_dir)) {
            mkdir($output_dir, 0777, true);
        }
        $output_dir = "uploads/repair_request/";

        $ret = array();

        $filekey = 'files';

        $id = $request->get('id', 0);

        // if($request->hasFile($filekey) === false )
        // 	return "No input file";

        //You need to handle  both cases
        //If Any browser does not support serializing of multiple files using FormData()
        $fileCount = count($_FILES[$filekey]["name"]);

        $repair = EngRepairRequest::find($id);

        $path = $repair->attach;
        if( empty($path) )
            $path = '';

        for ($i = 0; $i < $fileCount; $i++)
        {

            $fileName = $_FILES[$filekey]["name"][$i];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $filename1 = "eng_" . $id . '_' . $i . '_' . $fileName;

            $dest_path = $output_dir . $filename1;
            move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);
            if( !empty($path) )
                $path .= '&&';

            $path .=  $dest_path;
        }

        DB::table('eng_repair_request as tt')
            ->where('tt.id', $id)
            ->update(['attach' => $path]);

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $path;

        return Response::json($ret);
    }

    public function updateFiles(Request $request) {

        $ret = array();

        $id = $request->get('id', 0);
        $attach = $request->get('attach', '');

        DB::table('eng_repair_request as tt')
            ->where('tt.id', $id)
            ->update(['attach' => $attach]);

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $attach;

        return Response::json($ret);
    }

    public function updateRepairPath(Request $request)
    {
        $id = $request->get('id', 0);
        $attach = $request->get('attach', '');

        DB::table('eng_repair_request as tt')
            ->where('tt.id', $id)
            ->update(['attach' => $attach]);

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $attach;

        return Response::json($attach);
    }

    private function getRepairCommentList($id)
    {
        $list = DB::table('eng_repair_request_comment as ec')
            ->leftJoin('common_users as cu', 'ec.created_by', '=', 'cu.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->where('ec.repair_id', $id)
            ->select(DB::raw('ec.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, jr.job_role'))
            ->get();

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function getCommentList(Request $request)
    {
        $id = $request->get('id', 0);

        return $this->getRepairCommentList($id);
    }

    public function postComment(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);
        $comment = $request->get('comment', 0);

        $input = array();
        $input["repair_id"] = $id;
        $input["comment"] = $comment;
        $input["created_by"] = $user_id;

        $comment_id = DB::table('eng_repair_request_comment')->insertGetId($input);

        $this->sendCommentNotificaiton($id, $user_id, $comment);

        return $this->getRepairCommentList($id);
    }

    private function getDailyId($repair)
    {
        if( empty($repair) )
            return date('Ymd00');

        return sprintf('%s%02d', date('Ymd', strtotime($repair->created_at)), $repair->daily_id);
    }

    public function getStaffList(Request $request)
	{
		$value = '%' . $request->get('value', '') . '%';
        $client_id = $request->get('client_id', 4);
        $dept_id = $request->get('dept_id', 4);

		$ret = DB::table('common_users as cu')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
			->leftJoin('common_department as de','cu.dept_id','=','de.id')
			->leftJoin('common_property as cp','de.property_id','=','cp.id')
			->whereRaw("(CONCAT(cu.first_name, ' ', cu.last_name) like '" . $value . "' or cu.employee_id like '$value')")
            ->where('cp.client_id', $client_id)
            ->where('cu.dept_id', $dept_id)
			->where('cu.deleted', 0)
			->select(DB::raw('cu.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename , de.department, cp.name as property_name'))
			->get();


		return Response::json($ret);
	}

    public function sendCreationNotificaiton($id, $assignee, $supplier_id)
    {
        $data = EngRepairRequest::getDetail($id);

        // get user list
        $rules = array();
        $rules['eng_user_group_ids']  = 4;
        $rules['eng_contract_expire_days']  = 4;

        $property_id = $data->property_id;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);
        $user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_user_group_ids']);
        $user_list_names = CommonUser::getUserListNamesByEmailFromUserGroup($rules['eng_user_group_ids']);
        $category_user_list = CommonUser::getCategoryUserListByEmailFromUserGroup($data->category_id);
        $user_group_emails = implode(";", array_map(function($item) {
            return $item->email;
        }, $user_list));

        $cat_user_group_emails = implode(";", array_map(function($item) {
            return $item->email;
        }, $category_user_list));


        $info = array();
        $info['name'] = $data->wholename;
        $info['priority'] = $data->priority;
        $info['summary'] = $data->repair;
        $info['description'] = $data->comments;
        $info['location'] = "$data->location_name $data->location_type";
        $info['category_name'] = $data->category_name;
        $info['subcategory_name'] = $data->subcategory_name;
        $info['equip_name'] = "$data->equip $data->equip_name";
        $info['created_at'] = $data->created_at;


        $smtp = Functions::getMailSetting($data->property_id, 'notification_');

        $message = array();
        $message['type'] = 'email';
        $message['subject'] = sprintf("WR%s: [Confirmation] New Request has been created", $this->getDailyId(($data)));

     //   $message['content'] = $email_content;

        $message['smtp'] = $smtp;

        // mobile push message
        $payload = array();
		$payload['table_id'] = $id;
		$payload['table_name'] = 'eng_repair_request';
		$payload['property_id'] = $property_id;
		$payload['notify_type'] = 'repair_request';
        $payload['type'] = 'Repair Rquest Creation';
        $payload['header'] = 'Engineering';

        $msg = $message['subject'];

        if( $assignee > 0 )
        {
            $assignee = CommonUser::find($assignee);
            if( !empty($assignee) )
            {
                $info['name'] = $data->assignee_name;
                $email_content = view('emails.repair_request_reminder', ['info' => $info])->render();
                $message['content'] = $email_content;
                $message['to'] = $assignee->email;
                Redis::publish('notify', json_encode($message));

                Functions::sendPushMessgeToDeviceWithRedisNodejs(
                    $assignee, $id, 'Work Request', $msg, $payload
                );		
                
                
            }
        }
        else if( $supplier_id > 0 )
        {
            $supplier = DB::table('eng_supplier')->where('id', $supplier_id)->first();
            if( !empty($supplier) )
            {
                $info['name'] = $supplier->supplier;
                $email_content = view('emails.repair_request_reminder', ['info' => $info])->render();
                $message['content'] = $email_content;
                $message['to'] = $supplier->email;
                Redis::publish('notify', json_encode($message));
            }
        }
        else
        {
            // send email to engineering department user
            if( !empty($user_group_emails) )
            {
                // echo $email_content;
                $info['name'] =  "All";
                $email_content = view('emails.repair_request_reminder', ['info' => $info])->render();
                $message['content'] = $email_content;
                $message['to'] = $user_group_emails;
                Redis::publish('notify', json_encode($message));

                foreach($user_list_names as $row)
                {
                    Functions::sendPushMessgeToDeviceWithRedisNodejs(
                        $row, $id,'Work Request', $msg, $payload
                    );		
                }
            }

            // send email to engineering category user
            if( !empty($cat_user_group_emails) )
            {
                // echo $email_content;
                $info['name'] =  "All";
                $email_content = view('emails.repair_request_reminder', ['info' => $info])->render();
                $message['content'] = $email_content;
                $message['to'] = $cat_user_group_emails;
                Redis::publish('notify', json_encode($message));

                foreach($category_user_list as $row)
                {
                    Functions::sendPushMessgeToDeviceWithRedisNodejs(
                        $row, $id, 'Work Request', $msg, $payload
                    );		
                }
            }

            // send email to requestor
            if( !empty($data->email) )
            {
                $info['name'] =  $data->wholename;
                $email_content = view('emails.repair_request_reminder_to_requestor', ['info' => $info])->render();
                $message['content'] = $email_content;
                $message['to'] = $data->email;

                // echo $email_content;

                Redis::publish('notify', json_encode($message));

                if( $data->requestor_id > 0 )
                {
                    $requestor = CommonUser::find($data->requestor_id);
                    if( !empty($requestor) )
                    {
                        Functions::sendPushMessgeToDeviceWithRedisNodejs(
                            $requestor, $id, 'Work Request', $msg, $payload
                        );		
                    }
                }
            }
        }
    }

    public function sendUpdateNotificaiton($id, $old)
    {
        $data = EngRepairRequest::getDetail($id);

        $info1 = array();
        $info1['name'] = $data->wholename;


        $info = array();

        if( $old->priority != $data->priority )
            $info['Priority'] = $data->priority;

        if( $old->category_name != $data->category_name )
            $info['Category'] = $data->category_name;

        if( $old->subcategory_name != $data->subcategory_name )
            $info['Sub Category'] = $data->subcategory_name;

        if( $old->equip_name != $data->equip_name )
            $info['Equipment'] = $data->equip_name;

        if( $old->schedule_date != $data->schedule_date )
            $info['Scheduled Date'] = $data->schedule_date;

        if( $old->status_name != $data->status_name )
            $info['Status'] = $data->status_name;

        if( $old->estimated_duration != $data->estimated_duration )
            $info['Estimated Time'] = $data->estimated_duration;

        if( $old->start_date != $data->start_date )
            $info['Start Date'] = $data->start_date;

        if( $old->end_date != $data->end_date )
            $info['End Date'] = $data->end_date;

        if( $old->closed_at != $data->closed_at )
            $info['Closed Date'] = $data->closed_at;



        if( $old->assignee != $data->assignee )
        {
            $assignee = CommonUser::find($data->assignee);
            if( !empty($assignee) )
            {
                $info['Assignee'] = "$assignee->first_name $assignee->last_name";
            }

        }
        $info['Updated Time'] =  date("Y-m-d H:i:s");
        $smtp = Functions::getMailSetting($data->property_id, 'notification_');
   //     $email_content = view('emails.repair_request_change_reminder', ['info' => $info, 'info1' => $info1])->render();

   //     $smtp = Functions::getMailSetting($data->property_id, 'notification_');

        $message = array();
        $message['type'] = 'email';
        $message['subject'] = sprintf("WR%s: [Modification] Work Request has been updated", $this->getDailyId(($data)));
   //     $message['content'] = $email_content;

        $message['smtp'] = $smtp;

        $this->sendEmailToAssigners($data, $message, $info);
    }

    public function sendCommentNotificaiton($id, $created_by, $comment)
    {
        $data = EngRepairRequest::getDetail($id);

        $property_id = $data->property_id;

        $info = array();

        if( $created_by > 0 )
        {
            $created_by_user = CommonUser::find($created_by);
            if( !empty($created_by_user) )
            {
                $info['Commented By'] = "$created_by_user->first_name $created_by_user->last_name";
            }
        }

        $info['Comment'] = $comment;
        $info['Updated Time'] =  date("Y-m-d H:i:s");

        $smtp = Functions::getMailSetting($data->property_id, 'notification_');
     //   $email_content = view('emails.repair_request_change_reminder', ['info' => $info, 'info1' => $info1])->render();

     //   $smtp = Functions::getMailSetting($data->property_id, 'notification_');

        $message = array();
        $message['type'] = 'email';
        $message['subject'] = sprintf("WR%s: [Modification] Work Request has been updated", $this->getDailyId(($data)));
     //   $message['content'] = $email_content;

        $message['smtp'] = $smtp;

        $this->sendEmailToAssigners($data, $message, $info);

         // mobile push message
         $payload = array();
         $payload['table_id'] = $id;
         $payload['table_name'] = 'eng_repair_request';
         $payload['property_id'] = $property_id;
         $payload['notify_type'] = 'repair_request';
         $payload['type'] = 'Repair Request Creation';
         $payload['header'] = 'Engineering';

         $msg = sprintf("WR%s: [Modification] A new comment has been added by %s", $this->getDailyId($data), $info['Commented By'] );

         $staff_group = EngRepairStaff::getStaffGroupData($data->id);
         foreach($staff_group as $row)
        {
            $staff_id = $row['staff_id'];
            $assignee = CommonUser::find($staff_id);

            if( !empty($assignee) )
            {

            //    $assignee_name = "$assignee->first_name $assignee->last_name";

                Functions::sendPushMessgeToDeviceWithRedisNodejs(
                    $assignee, $id, 'Work Request', $msg, $payload
                );	
            }	

        }

    }

    public function sendWOCommentNotificaiton($id, $created_by, $comment)
    {
        $data = EngRepairRequest::getDetail($id);

        // get user list
        $rules = array();
        $rules['wo_comments_notify_flag']  = 4;
        $rules['eng_wo_notify_group']  = 4;

        $property_id = $data->property_id;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);
        $wo_user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_wo_notify_group']);
        $wo_user_group_emails = implode(";", array_map(function($item) {
            return $item->email;
        }, $wo_user_list));

        $info = array();

        if( $created_by > 0 )
        {
            $created_by_user = CommonUser::find($created_by);
            if( !empty($created_by_user) )
            {
                $info['Commented By'] = "$created_by_user->first_name $created_by_user->last_name";
            }
        }

        $info['Comment'] = $comment;
        $info['Updated Time'] =  date("Y-m-d H:i:s");

        $smtp = Functions::getMailSetting($data->property_id, 'notification_');
     //   $email_content = view('emails.repair_request_change_reminder', ['info' => $info, 'info1' => $info1])->render();

     //   $smtp = Functions::getMailSetting($data->property_id, 'notification_');

        $message = array();
        $message['type'] = 'email';
        $message['subject'] = sprintf("WR%s: [Modification] Work Request has been updated", $this->getDailyId(($data)));
     //   $message['content'] = $email_content;

        $message['smtp'] = $smtp;

     //   $this->sendEmailToAssigners($data, $message, $info);

        if( empty($data) || empty($message) )
        return;

        $info1 = array();
        $info1['name'] = $data->wholename;
        $info1['equip_name'] = "$data->equip $data->equip_name";
        $email_content = view('emails.repair_request_change_reminder', ['info' => $info, 'info1' => $info1])->render();
        $message['to'] = $data->email;
        $message['content'] = $email_content;
        Redis::publish('notify', json_encode($message));

        if ($rules['wo_comments_notify_flag'] == 1){
        if( !empty($wo_user_group_emails) )
        {
            // echo $email_content;
            $info1['name'] =  'Agents';
            $info1['equip_name'] = "$data->equip $data->equip_name";
            $email_content = view('emails.repair_request_change_reminder', ['info' => $info, 'info1' => $info1])->render();
            $message['content'] = $email_content;
            $message['to'] = $wo_user_group_emails;
            Redis::publish('notify', json_encode($message));
        }
        }

    }

    private function sendEmailToAssigners($data, $message, $info)
    {
        $info1 = array();
        if( empty($data) || empty($message) )
            return;

        // send email to requestor
        $info1['name'] = $data->wholename;
        $info1['equip_name'] = "$data->equip $data->equip_name";
        $email_content = view('emails.repair_request_change_reminder', ['info' => $info, 'info1' => $info1])->render();
        $message['to'] = $data->email;
        $message['content'] = $email_content;
        Redis::publish('notify', json_encode($message));

        if( $data->supplier_id > 0 )
        {
            $supplier = DB::table('eng_supplier')->where('id', $data->supplier_id)->first();
            if( !empty($supplier) )
            {

                $info1['name'] = $supplier->supplier;
                $info1['equip_name'] = "$data->equip $data->equip_name";
                $email_content = view('emails.repair_request_change_reminder', ['info' => $info, 'info1' => $info1])->render();
                $message['to'] = $supplier->email;
                $message['content'] = $email_content;
                Redis::publish('notify', json_encode($message));
            }
        }
        else
        {
            $staff_group = EngRepairStaff::getStaffGroupData($data->id);
            foreach($staff_group as $row)
            {
                if ($row['staff_type'] == 'group') {
                    $userlist = DB::table('common_user_group_members as cgm')
                        ->leftJoin('common_users as cu', 'cgm.user_id', '=', 'cu.id')
                        ->leftJoin('common_job_role as cr', 'cr.id', '=', 'cu.job_role_id')
                        ->where('cgm.group_id', $row['staff_id'])
                        ->select(DB::raw('cu.*'))
                        ->get();

                    for ($u = 0; $u < count($userlist); $u++) {
                        $assignee = $userlist[$u];
                        $info1['name'] = "$assignee->first_name $assignee->last_name";
                        $info1['equip_name'] = "$data->equip $data->equip_name";
                        $email_content = view('emails.repair_request_change_reminder', ['info' => $info, 'info1' => $info1])->render();
                        $message['to'] = $assignee->email;
                        $message['content'] = $email_content;
                        Redis::publish('notify', json_encode($message));
                    }
                } else { // if staff type is single(one person)
                    $staff_id = $row['staff_id'];
                    $assignee = CommonUser::find($staff_id);

                    if( !empty($assignee) )
                    {
                        $info1['name'] = "$assignee->first_name $assignee->last_name";
                        $info1['equip_name'] = "$data->equip $data->equip_name";
                        $email_content = view('emails.repair_request_change_reminder', ['info' => $info, 'info1' => $info1])->render();
                        $message['to'] = $assignee->email;
                        $message['content'] = $email_content;
                        Redis::publish('notify', json_encode($message));
                    }
                }
            }
        }
    }

    public function sendCompletedNotificaiton($id)
    {
        $data = EngRepairRequest::getDetail($id);

        $rules = array();
        $rules['repair_complete_form_attach']  = 0;
        $rules['repair_complete_form_cc_email']  = '';
        $rules['eng_user_group_ids']  = 4;
        $rules['eng_contract_expire_days']  = 4;

        $property_id = $data->property_id;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);
        $user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_user_group_ids']);
        $user_list_names = CommonUser::getUserListNamesByEmailFromUserGroup($rules['eng_user_group_ids']);
        $category_user_list = CommonUser::getCategoryUserListByEmailFromUserGroup($data->category_id);
        $user_group_emails = implode(";", array_map(function($item) {
            return $item->email;
        }, $user_list));

        ob_start();

        $filename = 'Work Completion Form_' . date('d_M_Y_H_i') . '_' . sprintf('WR%s',$this->getDailyId(($data)));
        $folder_path = public_path() . '/uploads/reports/';
        $path = $folder_path . $filename . '.html';
        $pdf_path = $folder_path . $filename . '.pdf';

        $ip = DB::table('property_setting as ps')
			    ->select(DB::raw('ps.value'))
			    ->where('ps.settings_key', 'hotlync_host')
                ->first();

        $attach = array();

        $attach['font-size'] = '7px';
        $attach['property'] = DB::table('common_property')->where('id', $data->property_id)->first();
        $attach['request_id'] = sprintf('WR%s',$this->getDailyId(($data)));
        $attach['summary'] = $data->repair;
        $attach['location'] = "$data->location_name $data->location_type";
        $attach['equipment'] = "$data->equip $data->equip_name";



        $content = view('frontend.report.repair_pdf', compact('attach'))->render();
        echo $content;
        file_put_contents($path, ob_get_contents());

        ob_clean();

        $info = array();
        $info['id'] = $id;
        $info['property_id'] = $property_id;
        $info['ip'] = $ip->value;
        $info['priority'] = $data->priority;
        $info['summary'] = $data->repair;
        $info['description'] = $data->comments;
        $info['location'] = "$data->location_name $data->location_type";
        $info['category_name'] = $data->category_name;
        $info['subcategory_name'] = $data->subcategory_name;
        $info['equip_name'] = "$data->equip $data->equip_name";
        $info['assignee_name'] = $data->assignee_name;
        $info['scheduled_date'] = $data->schedule_date;
        $info['start_date'] = $data->start_date;
        $info['end_date'] = $data->end_date;
        $info['complete_comment'] = $data->complete_comment;
   //    $email_content = view('emails.repair_request_completed_to_requestor', ['info' => $info])->render();

        $smtp = Functions::getMailSetting($property_id, 'notification_');

        $request = array();
        $message = array();

        $request['subject'] = sprintf("WR%s: [Completed] Work Request has been completed", $this->getDailyId(($data)));
        $message['subject'] = sprintf("WR%s: [Completed] Work Request has been completed", $this->getDailyId(($data)));


        $options = array();
        $options['html'] = $path;
        $options['pdf'] = $pdf_path;
        $options['paperSize'] = array('format' => 'A4', 'orientation' => 'portrait');
        $options['attach_flag'] = $rules['repair_complete_form_attach'];

    //    $message['content'] = $email_content;

        $request['smtp'] = $smtp;
        $message['smtp'] = $smtp;

        // send email to requestor
        if( !empty($data->email) )
        {

            $info['name'] = $data->wholename;
            $email_content = view('emails.repair_request_completed_to_requestor', ['info' => $info])->render();

            if (!empty($rules['repair_complete_form_cc_email'])){
                $message['cc'] = $rules['repair_complete_form_cc_email'];
                $request['cc'] = $rules['repair_complete_form_cc_email'];
            }
            if ($rules['repair_complete_form_attach'] == 1){
            $request['to'] = $data->email;
            $request['content'] =  $email_content;
            $request['html'] = sprintf("WR%s: [Completed] Work Request has been completed", $this->getDailyId(($data)));
            $request['filename'] = $filename . '.pdf';
            $request['options'] = $options;
            $message['type'] = 'report_pdf';
            $message['content'] = $request;


            }
            else{
            $message['to'] = $data->email;
            $message['type'] = 'email';
            $message['content'] = $email_content;
            }

            Redis::publish('notify', json_encode($message));
        }

        // send email to assignee
        if( !empty($data->assignee_email) )
        {

            $info['name'] = $data->assignee_name;
            $email_content = view('emails.repair_request_completed_to_requestor', ['info' => $info])->render();
            $message['content'] = $email_content;
            $message['to'] = $data->assignee_email;
            $message['cc'] = '';
            $message['type'] = 'email';
        //    $message['content'] = $request;

            Redis::publish('notify', json_encode($message));
        }

             // send email to engineering department user
        if( !empty($user_group_emails) )
        {
            // echo $email_content;
            $info['name'] =  "All";
            $email_content = view('emails.repair_request_completed_to_requestor', ['info' => $info])->render();
            $message['content'] = $email_content;
            $message['to'] = $user_group_emails;
            $message['cc'] = '';
            $message['type'] = 'email';
        //    $message['content'] = $request;
            Redis::publish('notify', json_encode($message));

            // mobile push message
            $payload = array();
            $payload['table_id'] = $id;
            $payload['table_name'] = 'eng_repair_request';
            $payload['property_id'] = $property_id;
            $payload['notify_type'] = 'repair_request';
            $payload['type'] = 'Repair Rquest Creation';
            $payload['header'] = 'Engineering';

            $msg = $request['subject'];

            foreach($user_list_names as $row)
            {
                Functions::sendPushMessgeToDeviceWithRedisNodejs(
                    $row, $id, 'Work Request', $msg, $payload
                );		
            }
        }
    }

    public function sendRejectedNotificaiton($id)
    {
        $data = EngRepairRequest::getDetail($id);

         // get user list
         $rules = array();
         $rules['eng_user_group_ids']  = 4;
         $rules['eng_contract_expire_days']  = 4;

        $property_id = $data->property_id;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);
        $user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_user_group_ids']);
        $user_list_names = CommonUser::getUserListNamesByEmailFromUserGroup($rules['eng_user_group_ids']);
        $category_user_list = CommonUser::getCategoryUserListByEmailFromUserGroup($data->category_id);
        $user_group_emails = implode(";", array_map(function($item) {
            return $item->email;
        }, $user_list));

        $info = array();
    //    $info['name'] = $data->wholename;
        $info['priority'] = $data->priority;
        $info['summary'] = $data->repair;
        $info['description'] = $data->comments;
        $info['location'] = "$data->location_name $data->location_type";
        $info['category_name'] = $data->category_name;
        $info['subcategory_name'] = $data->subcategory_name;
        $info['equip_name'] = "$data->equip $data->equip_name";
        $info['assignee_name'] = $data->assignee_name;
        $info['scheduled_date'] = $data->schedule_date;
        $info['reject_reason'] = $data->reject_reason;
    //    $email_content = view('emails.repair_request_rejected_to_requestor', ['info' => $info])->render();

        $smtp = Functions::getMailSetting($property_id, 'notification_');

        $message = array();
        $message['type'] = 'email';
        $message['subject'] = sprintf("WR%s: [Rejected] Work Request has been rejected", $this->getDailyId(($data)));

    //    $message['content'] = $email_content;

        $message['smtp'] = $smtp;

          // mobile push message
          $payload = array();
          $payload['table_id'] = $id;
          $payload['table_name'] = 'eng_repair_request';
          $payload['property_id'] = $property_id;
          $payload['notify_type'] = 'repair_request';
          $payload['type'] = 'Repair Rquest Creation';
          $payload['header'] = 'Engineering';

          $msg = $message['subject'];

          // send email to requestor
          if( !empty($data->email) )
          {

              $info['name'] = $data->wholename;
              $email_content = view('emails.repair_request_rejected_to_requestor', ['info' => $info])->render();
              $message['content'] = $email_content;
              $message['to'] = $data->email;

              Redis::publish('notify', json_encode($message));
          }

            // send email to assignee
            if( !empty($data->assignee_email) )
            {

                $info['name'] = $data->assignee_name;
                $email_content = view('emails.repair_request_rejected_to_requestor', ['info' => $info])->render();
                $message['content'] = $email_content;
                $message['to'] = $data->assignee_email;

                Redis::publish('notify', json_encode($message));
            }

             // send email to engineering department user
             if( !empty($user_group_emails) )
             {
                 // echo $email_content;
                 $info['name'] =  "All";
                 $email_content = view('emails.repair_request_rejected_to_requestor', ['info' => $info])->render();
                 $message['content'] = $email_content;
                 $message['to'] = $user_group_emails;
                 Redis::publish('notify', json_encode($message));

                 foreach($user_list_names as $row)
                 {
                     Functions::sendPushMessgeToDeviceWithRedisNodejs(
                         $row, $id, 'Work Request', $msg, $payload
                     );		
                 }
             }
    }

    public function sendInprogressNotificaiton($id)
    {
        $data = EngRepairRequest::getDetail($id);

         // get user list
         $rules = array();
         $rules['eng_user_group_ids']  = 4;
         $rules['eng_contract_expire_days']  = 4;

        $property_id = $data->property_id;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);
        $user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_user_group_ids']);
        $user_list_names = CommonUser::getUserListNamesByEmailFromUserGroup($rules['eng_user_group_ids']);
        $category_user_list = CommonUser::getCategoryUserListByEmailFromUserGroup($data->category_id);
        $user_group_emails = implode(";", array_map(function($item) {
            return $item->email;
        }, $user_list));

        $info = array();
    //    $info['name'] = $data->wholename;
        $info['priority'] = $data->priority;
        $info['summary'] = $data->repair;
        $info['description'] = $data->comments;
        $info['location'] = "$data->location_name $data->location_type";
        $info['category_name'] = $data->category_name;
        $info['subcategory_name'] = $data->subcategory_name;
        $info['equip_name'] = "$data->equip $data->equip_name";
        $info['assignee_name'] = $data->assignee_name;
        $info['scheduled_date'] = $data->schedule_date;

    //    $email_content = view('emails.repair_request_rejected_to_requestor', ['info' => $info])->render();

        $smtp = Functions::getMailSetting($property_id, 'notification_');

        $message = array();
        $message['type'] = 'email';
        $message['subject'] = sprintf("WR%s: [In Progress] Work Request has been In Progress", $this->getDailyId(($data)));

    //    $message['content'] = $email_content;

        $message['smtp'] = $smtp;


          // mobile push message
          $payload = array();
          $payload['table_id'] = $id;
          $payload['table_name'] = 'eng_repair_request';
          $payload['property_id'] = $property_id;
          $payload['notify_type'] = 'repair_request';
          $payload['type'] = 'Repair Rquest Creation';
          $payload['header'] = 'Engineering';

          $msg = $message['subject'];

          // send email to requestor
          if( !empty($data->email) )
          {

              $info['name'] = $data->wholename;
              $email_content = view('emails.repair_request_inprogress_to_requestor', ['info' => $info])->render();
              $message['content'] = $email_content;
              $message['to'] = $data->email;

              Redis::publish('notify', json_encode($message));
          }

             // send email to engineering department user
             if( !empty($user_group_emails) )
             {
                 // echo $email_content;
                 $info['name'] =  "All";
                 $email_content = view('emails.repair_request_inprogress_to_requestor', ['info' => $info])->render();
                 $message['content'] = $email_content;
                 $message['to'] = $user_group_emails;
                 Redis::publish('notify', json_encode($message));

                 foreach($user_list_names as $row)
                 {
                     Functions::sendPushMessgeToDeviceWithRedisNodejs(
                         $row, $id, 'Work Request', $msg, $payload
                     );		
                 }
             }
    }

    public function sendAssignedNotificaiton($id)
    {
        $data = EngRepairRequest::getDetail($id);

         // get user list
         $rules = array();
         $rules['eng_user_group_ids']  = 4;
         $rules['eng_contract_expire_days']  = 4;

        $property_id = $data->property_id;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);
        $user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_user_group_ids']);
        $user_list_names = CommonUser::getUserListNamesByEmailFromUserGroup($rules['eng_user_group_ids']);
        $category_user_list = CommonUser::getCategoryUserListByEmailFromUserGroup($data->category_id);
        $user_group_emails = implode(";", array_map(function($item) {
            return $item->email;
        }, $user_list));

        $info = array();
    //    $info['name'] = $data->wholename;
        $info['priority'] = $data->priority;
        $info['summary'] = $data->repair;
        $info['description'] = $data->comments;
        $info['location'] = "$data->location_name $data->location_type";
        $info['category_name'] = $data->category_name;
        $info['subcategory_name'] = $data->subcategory_name;
        $info['equip_name'] = "$data->equip $data->equip_name";
        $info['assignee_name'] = $data->assignee_name;
        $info['scheduled_date'] = $data->schedule_date;

    //    $email_content = view('emails.repair_request_rejected_to_requestor', ['info' => $info])->render();

        $smtp = Functions::getMailSetting($property_id, 'notification_');

        $message = array();
        $message['type'] = 'email';
        $message['subject'] = sprintf("WR%s: [Assigned] Work Request has been assigned", $this->getDailyId(($data)));

    //    $message['content'] = $email_content;

        $message['smtp'] = $smtp;

          // mobile push message
          $payload = array();
          $payload['table_id'] = $id;
          $payload['table_name'] = 'eng_repair_request';
          $payload['property_id'] = $property_id;
          $payload['notify_type'] = 'repair_request';
          $payload['type'] = 'Repair Rquest Creation';
          $payload['header'] = 'Engineering';

          $msg = $message['subject'];

          // send email to requestor
          if( !empty($data->email) )
          {

              $info['name'] = $data->wholename;
              $email_content = view('emails.repair_request_assigned_to_requestor', ['info' => $info])->render();
              $message['content'] = $email_content;
              $message['to'] = $data->email;

              Redis::publish('notify', json_encode($message));
          }

             // send email to engineering department user
             if( !empty($user_group_emails) )
             {
                 // echo $email_content;
                 $info['name'] =  "All";
                 $email_content = view('emails.repair_request_assigned_to_requestor', ['info' => $info])->render();
                 $message['content'] = $email_content;
                 $message['to'] = $user_group_emails;
                 Redis::publish('notify', json_encode($message));

                 foreach($user_list_names as $row)
                 {
                     Functions::sendPushMessgeToDeviceWithRedisNodejs(
                         $row, $id, 'Work Request', $msg, $payload
                     );		
                 }
             }
    }

    public function sendOnHoldNotificaiton($id)
    {
        $data = EngRepairRequest::getDetail($id);

         // get user list
         $rules = array();
         $rules['eng_user_group_ids']  = 4;
         $rules['eng_contract_expire_days']  = 4;

        $property_id = $data->property_id;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);
        $user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_user_group_ids']);
        $user_list_names = CommonUser::getUserListNamesByEmailFromUserGroup($rules['eng_user_group_ids']);
        $category_user_list = CommonUser::getCategoryUserListByEmailFromUserGroup($data->category_id);
        $user_group_emails = implode(";", array_map(function($item) {
            return $item->email;
        }, $user_list));

        $workorder = WorkOrder::where('request_id', $id)
            ->where('property_id', $property_id)
            ->where('request_flag', 1)
            ->first();
        if (!empty($workorder)){

           $hold_comment =  DB::table('eng_workorder_status_log')
                            ->where('workorder_id',$workorder->id)
                            ->where('status','=', 'Custom')
                            ->select(DB::raw('max(id) as max_id, description'))
                            ->first();
        }

        $info = array();
    //    $info['name'] = $data->wholename;
        $info['priority'] = $data->priority;
        $info['summary'] = $data->repair;
        $info['description'] = $data->comments;
        $info['location'] = "$data->location_name $data->location_type";
        $info['category_name'] = $data->category_name;
        $info['subcategory_name'] = $data->subcategory_name;
        $info['equip_name'] = "$data->equip $data->equip_name";
        $info['assignee_name'] = $data->assignee_name;
        $info['scheduled_date'] = $data->schedule_date;
        if (!empty($hold_comment)){
        $info['hold_comment'] = $hold_comment->description;
        }
        else{
            $info['hold_comment'] = '';
        }

    //    $email_content = view('emails.repair_request_rejected_to_requestor', ['info' => $info])->render();

        $smtp = Functions::getMailSetting($property_id, 'notification_');

        $message = array();
        $message['type'] = 'email';
        $message['subject'] = sprintf("WR%s: [On Hold] Work Request has been On Hold", $this->getDailyId(($data)));

    //    $message['content'] = $email_content;

        $message['smtp'] = $smtp;


          // mobile push message
          $payload = array();
          $payload['table_id'] = $id;
          $payload['table_name'] = 'eng_repair_request';
          $payload['property_id'] = $property_id;
          $payload['notify_type'] = 'repair_request';
          $payload['type'] = 'Repair Rquest Creation';
          $payload['header'] = 'Engineering';

          $msg = $message['subject'];

          // send email to requestor
          if( !empty($data->email) )
          {

              $info['name'] = $data->wholename;
              $email_content = view('emails.repair_request_onhold_to_requestor', ['info' => $info])->render();
              $message['content'] = $email_content;
              $message['to'] = $data->email;

              Redis::publish('notify', json_encode($message));
          }

             // send email to engineering department user
             if( !empty($user_group_emails) )
             {
                 // echo $email_content;
                 $info['name'] =  "All";
                 $email_content = view('emails.repair_request_onhold_to_requestor', ['info' => $info])->render();
                 $message['content'] = $email_content;
                 $message['to'] = $user_group_emails;
                 Redis::publish('notify', json_encode($message));

                 foreach($user_list_names as $row)
                 {
                     Functions::sendPushMessgeToDeviceWithRedisNodejs(
                         $row, $id, 'Work Request', $msg, $payload
                     );		
                 }
             }
    }

    public function sendClosedNotificaiton($id)
    {
        $data = EngRepairRequest::getDetail($id);

        // get user list
        $rules = array();
        $rules['eng_user_group_ids']  = 4;
        $rules['eng_contract_expire_days']  = 4;

        $property_id = $data->property_id;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);
        $user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_user_group_ids']);
        $user_list_names = CommonUser::getUserListNamesByEmailFromUserGroup($rules['eng_user_group_ids']);
        $category_user_list = CommonUser::getCategoryUserListByEmailFromUserGroup($data->category_id);
        $user_group_emails = implode(";", array_map(function($item) {
            return $item->email;
        }, $user_list));

        $info = array();
    //    $info['name'] = $data->wholename;
        $info['priority'] = $data->priority;
        $info['summary'] = $data->repair;
        $info['description'] = $data->comments;
        $info['location'] = "$data->location_name $data->location_type";
        $info['category_name'] = $data->category_name;
        $info['subcategory_name'] = $data->subcategory_name;
        $info['equip_name'] = "$data->equip $data->equip_name";
        $info['assignee_name'] = $data->assignee_name;
        $info['scheduled_date'] = $data->schedule_date;
        $info['start_date'] = $data->start_date;
        $info['end_date'] = $data->end_date;
        $info['complete_comment'] = $data->complete_comment;
        $info['closed_at'] = $data->closed_at;
    //    $email_content = view('emails.repair_request_closed_to_requestor', ['info' => $info])->render();

        $smtp = Functions::getMailSetting($property_id, 'notification_');

        $message = array();
        $message['type'] = 'email';
        $message['subject'] = sprintf("WR%s: [Closed] Work Request has been closed", $this->getDailyId(($data)));

    //    $message['content'] = $email_content;

        $message['smtp'] = $smtp;

        // mobile push message
        $payload = array();
        $payload['table_id'] = $id;
        $payload['table_name'] = 'eng_repair_request';
        $payload['property_id'] = $property_id;
        $payload['notify_type'] = 'repair_request';
        $payload['type'] = 'Repair Request Creation';
        $payload['header'] = 'Engineering';

        $msg = $message['subject'];

        // send email to requestor
        if( !empty($data->email) )
        {

            $info['name'] = $data->wholename;
            $email_content = view('emails.repair_request_closed_to_requestor', ['info' => $info])->render();
            $message['content'] = $email_content;
            $message['to'] = $data->email;

            Redis::publish('notify', json_encode($message));
        }

        $email_name_list = EngRepairStaff::getStaffGroupEmails($id);

        // send email to assignee
        if( !empty($email_name_list['email_list']) )
        {
            $info['name'] = $email_name_list['assignee_name'];
            $email_content = view('emails.repair_request_closed_to_requestor', ['info' => $info])->render();
            $message['content'] = $email_content;
            $message['to'] = $email_name_list['email_list'];

            Redis::publish('notify', json_encode($message));
        }

        // send email to engineering department user
        if( !empty($user_group_emails) )
        {
            // echo $email_content;
            $info['name'] =  "All";
            $email_content = view('emails.repair_request_closed_to_requestor', ['info' => $info])->render();
            $message['content'] = $email_content;
            $message['to'] = $user_group_emails;
            Redis::publish('notify', json_encode($message));

            foreach($user_list_names as $row)
            {
                Functions::sendPushMessgeToDeviceWithRedisNodejs(
                    $row, $id, 'Work Request', $msg, $payload
                );		
            }
        }
    }

    public function sendReopenNotificaiton($id)
    {
        $data = EngRepairRequest::getDetail($id);

        // get user list
        $rules = array();
        $rules['eng_user_group_ids']  = 4;
        $rules['eng_contract_expire_days']  = 4;

        $property_id = $data->property_id;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);
        $user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_user_group_ids']);
        $user_group_emails = implode(";", array_map(function($item) {
            return $item->email;
        }, $user_list));

        $info = array();
        $info['priority'] = $data->priority;
        $info['summary'] = $data->repair;
        $info['description'] = $data->comments;
        $info['location'] = "$data->location_name $data->location_type";
        $info['category_name'] = $data->category_name;
        $info['subcategory_name'] = $data->subcategory_name;
        $info['equip_name'] = "$data->equip $data->equip_name";
    //    $email_content = view('emails.repair_request_reminder', ['info' => $info])->render();

        $smtp = Functions::getMailSetting($data->property_id, 'notification_');

        $message = array();
        $message['type'] = 'email';
        $message['subject'] = sprintf("WR%s: [Reopened] New Request has been created", $this->getDailyId(($data)));

    //    $message['content'] = $email_content;

        $message['smtp'] = $smtp;

        $payload = array();
		$payload['table_id'] = $id;
		$payload['table_name'] = 'eng_repair_request';
		$payload['property_id'] = $property_id;
		$payload['notify_type'] = 'repair_request';
        $payload['type'] = 'Repair Rquest Creation';
        $payload['header'] = 'Engineering';

        $msg = $message['subject'];


        // send email to engineering department user
        if( !empty($user_group_emails) )
        {
            // echo $email_content;
            $info['name'] =  'All';
            $email_content = view('emails.repair_request_reminder', ['info' => $info])->render();
            $message['content'] = $email_content;
            $message['to'] = $user_group_emails;
            Redis::publish('notify', json_encode($message));
        }

        if( !empty($data->email) )
        {
            $info['name'] =  $data->wholename;
            $email_content = view('emails.repair_request_reminder_to_requestor', ['info' => $info])->render();
            $message['content'] = $email_content;
            $message['to'] = $data->email;

            // echo $email_content;

            Redis::publish('notify', json_encode($message));

            if( $data->requestor_id > 0 )
            {
                $requestor = CommonUser::find($data->requestor_id);
                if( !empty($requestor) )
                {
                    Functions::sendPushMessgeToDeviceWithRedisNodejs(
                        $requestor, $id, 'Work Request', $msg, $payload
                    );		
                }
            }
        }

        if( $data->assignee > 0 )
        {
            $assignee = CommonUser::find($data->assignee);
            if( !empty($assignee) )
            {
                $info['name'] = $data->assignee_name;
                $email_content = view('emails.repair_request_reminder', ['info' => $info])->render();
                $message['content'] = $email_content;
                $message['to'] = $assignee->email;
                Redis::publish('notify', json_encode($message));

                Functions::sendPushMessgeToDeviceWithRedisNodejs(
                    $assignee, $id, 'Work Request', $msg, $payload
                );			
            }
        }

        $workorder = WorkOrder::where('request_id', $id)
            ->where('property_id', $property_id)
            ->where('request_flag', 1)
            ->first();

        if( !empty($workorder) )
        {
            $workorder->status = 'Pending';
            $workorder->save();

            $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, 0);
        }
    }

    public function checkAuth(Request $request)
    {
        $client_id = $request->get('client_id', 0);

        $property =  DB::table('common_property')
            ->where('client_id', $client_id)
            ->first();

        $ret = array();

        if( empty($property) )
        {
            $ret['code'] = 201;
            return Response::json($ret);
        }

        $rules['repair_auth_on'] = "0";

        $rules = PropertySetting::getPropertySettings($property->id, $rules);

        $ret['code'] = 200;
        $ret['auth_on'] = $rules['repair_auth_on'] == "1" ? 1 : 0;

        return Response::json($ret);
    }

    public function exportRepairRequestList(Request $request)
    {
        $property_id = $request->get('property_id', '0');
        $searchtext = $request->get('searchtext','');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');

        $dispatcher = $request->get('dispatcher', '');

        $assigne_ids = $request->get('assigne_ids', '');
   //   $status_name = $request->get('status_names', []);
        $status_names = $request->get('status_names','[]');
        $status_name = json_decode($status_names);
        $category_ids = $request->get('category_ids', []);
        $dept_ids = $request->get('dept_ids', []);
        $location_ids = $request->get('location_ids', []);
        $equipment_ids = $request->get('equipment_ids', []);
        $equip_ids = $request->get('equip_ids', []);

        $property_list = CommonUser::getPropertyIdsByJobroleids($dispatcher);

        $ret = array();
        $query = DB::table('eng_repair_request as er')
            ->leftJoin('eng_request_category as erc', 'er.category_id', '=', 'erc.id')
            ->leftJoin('eng_request_subcategory as ers', 'er.sub_category_id', '=', 'ers.id')
            ->leftJoin('eng_equip_list as eq', 'er.equipment_id', '=', 'eq.id')
            ->leftJoin('common_users as cu', 'er.requestor_id', '=', 'cu.id')
            ->leftJoin('common_users as cua', 'er.assignee', '=', 'cua.id')
            ->leftJoin('eng_supplier as es', 'er.supplier_id', '=', 'es.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('common_property as cp', 'er.property_id', '=', 'cp.id')
            ->leftjoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->leftJoin('services_location as sl', 'er.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
            ->leftJoin('eng_contracts as ec', 'er.requestor_id', '=', 'ec.id')
            ->leftJoin('eng_tenant as et', 'er.requestor_id', '=', 'et.id')
        //    ->where('er.property_id', $property_id)
            ->whereIn('er.property_id', $property_list)
            ->whereRaw(sprintf("DATE(er.created_at) >= '%s' and DATE(er.created_at) <= '%s'", $start_date, $end_date))
            ->where('er.delete_flag', 0);

        // ->where('time', '>', $last_time);
        $sub_count_query = clone $query;

        if($searchtext != '')
        {
            $query->where(function ($query) use ($searchtext) {
                $value = '%' . $searchtext . '%';
                $query->where('er.id', 'like', $value)
                    ->orWhere('er.repair', 'like', $value)
                    ->orWhere('cp.name', 'like', $value)
                    ->orWhere('cu.first_name', 'like', $value)
                    ->orWhere('eq.name', 'like', $value)
                    ->orWhere('eq.equip_id', 'like', $value)
                    ->orWhere('er.ref_id', 'like', $value)
                    ->orWhere('cu.last_name', 'like', $value);
            });
        }

    /*    if( count($assigne_ids) > 0 )
            $query->whereIn('er.assignee', $assigne_ids);

        if( !empty($assigne_ids)){
                $assign_list = explode(',', $assigne_ids);
                $query->whereIn('er.assignee', $assign_list);
            }
            */
/*
        if( $status_name != 'All' )
            $query->where('er.status_name', $status_name);

        if( count($category_ids) > 0 )
            $query->whereIn('er.category_id', $category_ids);
*/
        if( !empty($status_name)){
        //    $status_list = explode(',', $status_name);
            $query->whereIn('er.status_name', $status_name);
        }
/*
        if( count($status_name) > 0 ){
            $query->whereIn('er.status_name', $status_names);
        }
 */
        if( !empty($category_ids)){
                $cat_list = explode(',', $category_ids);
                $query->whereIn('er.category_id', $cat_list);
            }
      /*
        if( count($dept_ids) > 0  )
            $query->whereIn('cd.id', $dept_ids);
*/
        if( !empty($dept_ids)){
                $dept_list = explode(',', $dept_ids);
                $query->whereIn('cd.id', $dept_list);
            }
/*
        if( count($location_ids) > 0 )
            $query->whereIn('er.location_id', $location_ids);
 */
        if( !empty($location_ids)){
                $loc_list = explode(',', $location_ids);
                $query->whereIn('er.location_id', $loc_list);
            }
/*
        if( count($equipment_ids) > 0 )
            $query->whereIn('er.equipment_id', $equipment_ids);
*/
        if( !empty($equipment_ids)){
                $equip_list = explode(',', $equipment_ids);
                $query->whereIn('er.equipment_id', $equip_list);
            }
     /*
        if( count($equip_ids) > 0 )
            $query->whereIn('eq.equip_id', $equip_ids);
     */
        if( !empty($equip_ids)){
                $eq_list = explode(',', $equip_ids);
                $query->whereIn('eq.equip_id', $eq_list);
            }

        $data_query = clone $query;
        $data_list = $data_query
            ->select(DB::raw('er.*, erc.name as category_name, eq.equip_id as equip_id, eq.name as equip_name,
                CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, ec.leasor, et.name as tenant_name,
                CONCAT_WS(" ", cua.first_name, cua.last_name) as assignee_name, 
                es.supplier,
                sl.name as location_name, slt.type as location_type,
                cp.name as property_name'))
            ->get();

            for($i = 0 ; $i < count($data_list) ; $i++) {
                EngRepairRequest::getStaffGroups($data_list[$i]);
            }

            if( !empty($assigne_ids) )
            {

                $assignee_id_list = explode(',', $assigne_ids);

                // assignee filter
                $data_list = array_filter($data_list, function($row) use ($assignee_id_list) {
                    return (count(array_filter($row->assignee_list, function($row1) use ($assignee_id_list) {
                        return in_array($row1->id, $assignee_id_list);
                    })) > 0);
                });

            }
            $data_list = array_merge($data_list, array());


        foreach( $data_list as $data){
            $data->requestor_name = '';
            if ($data->requestor_type == 'User'){
               $data->requestor_name  = $data->wholename;
            }
            if ($data->requestor_type == 'Leasor'){
               $data->requestor_name  = $data->leasor;
            }
            if ($data->requestor_type == 'Tenant'){
               $data->requestor_name  = $data->tenant_name;
            }

            $staff = EngRepairStaff::getStaffGroupEmails($data->id);
            if( !empty($staff['assignee_name']) )
                $data->assignee_name = $staff['assignee_name'];

            $data->comments = DB::table('eng_repair_request_comment as ec')
                            ->leftJoin('common_users as cu', 'ec.created_by', '=', 'cu.id')
                            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
                            ->where('ec.repair_id', $data->id)
                            ->select(DB::raw('ec.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, jr.job_role'))
                            ->get();
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
						COALESCE(sum(er.status_name = 'Pending'), 0) as pending,
						COALESCE(sum(er.status_name = 'On Hold'), 0) as hold,
						COALESCE(sum(er.status_name = 'In Progress'), 0) as progress,
						COALESCE(sum(er.status_name = 'Completed'), 0) as completed,
						COALESCE(sum(er.status_name = 'Rejected'), 0) as rejected						
						"))
            ->first();

        $ret['subcount'] = $subcount;

        // excel report

        $filename = 'Repair_Request_Report_' . $start_date . '_' . $end_date;
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

		Excel::create($filename, function($excel) use ($logo_path, $data) {

			$excel->sheet('Work Request Report', function($sheet) use ($data, $logo_path) {
				$sheet->setOrientation('landscape');

                $row_num = 1;

                $sheet->mergeCells('A' . $row_num . ':K' . $row_num);
                $sheet->cell('A' . $row_num, function($cell) {
				$cell->setValue('Work Request List');
				$cell->setAlignment('center');
				$cell->setFont(array(
						'size'       => '11',
						'bold'       =>  true
				));
			    });

                $row_num += 1;

                $sheet->cell('A' . $row_num . ':M' . $row_num, function ($cell) {
                    $cell->setFontColor('#212121');
                    $cell->setBackground('#ECEFF1');
                    $cell->setAlignment('center');
                    $cell->setFont(array(
                            'family'     => 'Tahoma',
                            'bold'       =>  true
                    ));
                });

                $header_list = ['ID', 'Scheduled Date', 'Requestor', 'Priority', 'Category', 'Summary', 'Status','Created Date', 'Start Date', 'End Date', 'Asset', 'Assignee', 'Comments'];
                $sheet->row($row_num, $header_list);
                foreach($data['datalist'] as $row)
                {
                    $row_num++;

                    $com = '';
                    $lfcr = chr(10);

                    for($p=0; $p < count($row->comments) ;$p++) {
                        $com .= $row->comments[$p]->comment . ' - ' . $row->comments[$p]->wholename . ' - ' . $row->comments[$p]->created_at  . $lfcr;

                    }
/*
                    $row = [
                        'WR' . $this->getDailyId($row),
                        $row->schedule_date,
                        $row->requestor_name,
                        $row->priority,
                        $row->category_name,
                        $row->repair,
                        $row->status_name,
                        $row->created_at,
                        $row->status_name=='In Progress' || $row->status_name=='Completed' ? $row->start_date : '',
                        $row->status_name=='Completed' ? $row->end_date : '',
                        "$row->equip_id - $row->equip_name",
                        $row->supplier_id > 0 ? $row->supplier : $row->assignee_name,
                        $com
                    ];

                    $sheet->row($row_num, $row);
                    */

                    $this->setMergeRowText($sheet,'WR' . $this->getDailyId($row), $row_num, 'A', 0, 30, 30);
                    $this->setMergeRowText($sheet, $row->schedule_date , $row_num, 'B', 0, 30, 30);
                    $this->setMergeRowText($sheet, $row->requestor_name , $row_num, 'C', 0, 30, 30);
                    $this->setMergeRowText($sheet, $row->priority , $row_num, 'D', 0, 30, 30);
                    $this->setMergeRowText($sheet, $row->category_name , $row_num, 'E', 0, 30, 30);
                    $this->setMergeRowText($sheet, $row->repair , $row_num, 'F', 0, 30, 30);
                    $this->setMergeRowText($sheet, $row->status_name , $row_num, 'G', 0, 30, 30);
                    $this->setMergeRowText($sheet, $row->created_at , $row_num, 'H', 0, 30, 30);
                    $this->setMergeRowText($sheet, $row->status_name=='In Progress' || $row->status_name=='Completed' ? $row->start_date : '' , $row_num, 'I', 0, 30, 30);
                    $this->setMergeRowText($sheet, $row->status_name=='Completed' ? $row->end_date : '' , $row_num, 'J', 0, 30, 30);
                    $this->setMergeRowText($sheet, "$row->equip_id - $row->equip_name" , $row_num, 'K', 0, 30, 30);
                    $this->setMergeRowText($sheet, $row->supplier_id > 0 ? $row->supplier : $row->assignee_name , $row_num, 'L', 0, 30, 30);
                    $this->setMergeRowText($sheet, $com , $row_num, 'M', 0, 60, 40);
                }
		    });

		})->export($excel_file_type);

        return Response::json($ret);
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

    public function getMyRepairStatusCount(Request $request)
    {
        $date = $request->get('date', 'D');
        $user_id = $request->get('user_id', 0);

        $assigne_ids = $request->get('assigne_ids', '');
        $category_ids = $request->get('category_ids', '');
        $location_ids = $request->get('location_ids', '');
        $equipment_ids = $request->get('equipment_ids', '');
        $requestor_id = $request->get('requestor_id', 0);

        $assigne_ids = empty($assigne_ids) ? [] : explode(",", $assigne_ids);
        $category_ids = empty($category_ids) ? [] : explode(",", $category_ids);
        $location_ids = empty($location_ids) ? [] : explode(",", $location_ids);
        $equipment_ids = empty($equipment_ids) ? [] : explode(",", $equipment_ids);


        $property_id = CommonUser::getPropertyID($user_id);
        $property_list = CommonUser::getPropertyIdsByJobroleids($user_id);

        $this->getDateRange($date, $start_date, $end_date);

        $select_sql = 'count(*) as total';
        $status_list = Functions::getFieldValueList('eng_repair_request', 'status_name');
        foreach($status_list as $key => $row)
        {
            $select_sql .= ",COALESCE(sum(er.status_name = '$row'), 0) as cnt$key";
        }

        $query = DB::table('eng_repair_request as er')
            ->leftJoin('eng_repair_status as ers', 'er.status', '=', 'ers.id')
        //    ->where('er.property_id', $property_id)
            ->whereIn('er.property_id', $property_list)
            ->whereRaw(sprintf(" ( DATE(er.created_at) >= '%s' AND DATE(er.created_at) <= '%s' )", $start_date, $end_date));

        if( count($assigne_ids) > 0 )
            $query->whereIn('er.assignee', $assigne_ids);

        if( count($category_ids) > 0 )
            $query->whereIn('er.category_id', $category_ids);

        if( count($location_ids) > 0 )
            $query->whereIn('er.location_id', $location_ids);

        if( count($equipment_ids) > 0 )
            $query->whereIn('er.equipment_id', $equipment_ids);

        if( $requestor_id > 0 )
            $query->where('er.requestor_id', $requestor_id);

        $count = $query->select(DB::raw($select_sql))
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

        // $ret['user_id'] = $user_id;

        return Response::json($ret);

    }

    public function getMyRepairList(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));

        $date = $request->get('date', 'D');
        $user_id = $request->get('user_id', 0);
        $mine_flag = $request->get('mine_flag', 0);

        $property_id = CommonUser::getPropertyID($user_id);
        $property_list = CommonUser::getPropertyIdsByJobroleids($user_id);

        $this->getDateRange($date, $start_date, $end_date);

        $ids = $request->get('ids', '0');

        $page_size = $request->get('page_size', 10);
        $status_list = $request->get('status_list', "");
        $orderby = $request->get('orderby', "me");
        $search = $request->get('search', "");

        $assigne_ids = $request->get('assigne_ids', '');
        $category_ids = $request->get('category_ids', '');
        $location_ids = $request->get('location_ids', '');
        $equipment_ids = $request->get('equipment_ids', '');
        $requestor_id = $request->get('requestor_id', 0);

        $assigne_ids = empty($assigne_ids) ? [] : explode(",", $assigne_ids);
        $category_ids = empty($category_ids) ? [] : explode(",", $category_ids);
        $location_ids = empty($location_ids) ? [] : explode(",", $location_ids);
        $equipment_ids = empty($equipment_ids) ? [] : explode(",", $equipment_ids);

        $ret = array();

        $query = DB::table('eng_repair_request as er')
            ->leftJoin('eng_request_category as erc', 'er.category_id', '=', 'erc.id')
            ->leftJoin('eng_request_subcategory as ers', 'er.sub_category_id', '=', 'ers.id')
            ->leftJoin('eng_equip_list as eq', 'er.equipment_id', '=', 'eq.id')
            ->leftJoin('common_users as cu', 'er.requestor_id', '=', 'cu.id')
            ->leftJoin('common_users as cua', 'er.assignee', '=', 'cua.id')
            ->leftJoin('eng_supplier as es', 'er.supplier_id', '=', 'es.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('common_property as cp', 'er.property_id', '=', 'cp.id')
            ->leftJoin('services_location as sl', 'er.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
            ->leftJoin('eng_contracts as ec', 'er.requestor_id', '=', 'ec.id')
            ->leftJoin('eng_tenant as et', 'er.requestor_id', '=', 'et.id')
        //    ->where('er.property_id', $property_id)
            ->whereIn('er.property_id', $property_list)
            ->whereRaw(sprintf("DATE(er.created_at) >= '%s' and DATE(er.created_at) <= '%s'", $start_date, $end_date));


        // status filter
        if( !empty($status_list) )
        {
            $status_array = explode(",", $status_list);
            $query->whereIn('er.status_name', $status_array);
        }

        if( $mine_flag == 1 )
            $query->where('er.assignee', $user_id);

        // search filter
        if( !empty($search) )
        {
            $query->where(function ($query) use ($search) {
                $value = '%' . $search . '%';
                $query->where('er.id', 'like', $value)
                    ->orWhere('er.repair', 'like', $value)
                    ->orWhere('cp.name', 'like', $value)
                    ->orWhere('cu.first_name', 'like', $value)
                    ->orWhere('cu.last_name', 'like', $value);
            });
        }

        if( count($assigne_ids) > 0 )
            $query->whereIn('er.assignee', $assigne_ids);

        if( count($category_ids) > 0 )
            $query->whereIn('er.category_id', $category_ids);

        if( count($location_ids) > 0 )
            $query->whereIn('er.location_id', $location_ids);

        if( count($equipment_ids) > 0 )
            $query->whereIn('er.equipment_id', $equipment_ids);

        if( $requestor_id > 0 )
            $query->where('er.requestor_id', $requestor_id);

        $data_query = clone $query;

        // id list
        $id_list = explode(",", $ids);
        $data_query->whereNotIn('er.id', $id_list);

        $orderby_priority = "FIELD(er.priority,'Urgent','High','Medium','Low')";

        switch($orderby)
        {
            case "due_date":
                $data_query->orderBy('er.due_date', 'desc');
                $data_query->orderByRaw($orderby_priority);
                break;
            case "priority":
                $data_query->orderByRaw($orderby_priority);
                $data_query->orderBy('er.due_date', 'desc');
                break;
        }

       $data_query->orderBy('er.created_at','desc');

        $data_list = $data_query
            ->select(DB::raw('er.*, erc.name as category_name, ers.name as subcategory_name, 
                                eq.equip_id as equip_id, eq.name as equip_name,
                                CONCAT_WS(" ", cu.first_name, cu.last_name) as requestor_name,
                                CONCAT_WS(" ", cua.first_name, cua.last_name) as assignee_name, 
                                ec.leasor, et.name as tenant_name,
                                es.supplier,
                                sl.name as location_name, slt.type as location_type,
                                cp.name as property_name'))
                                ->take($page_size)
                                ->get();
        foreach($data_list as $row)
        {
            $row->ticket_id = $this->getDailyId($row);
            $row->staff_groups =  DB::table('eng_repair_staff as ers')
                ->leftJoin('common_user_group as cug', 'ers.staff_id', '=', 'cug.id')
                ->leftJoin('common_users as cu', 'ers.staff_id', '=', 'cu.id')
                ->where('ers.request_id', $row->id)
                ->select(DB::raw("ers.staff_id as id, ers.staff_type as type, CONCAT(ers.staff_name, '-', ers.staff_type) as text,
                                (CASE WHEN ers.staff_type = 'group' THEN cug.name ELSE CONCAT_WS(\" \", cu.first_name, cu.last_name) END) as name"))
                ->get();

            $row->assignee_name = implode(",", array_map(function($item) {
                return $item->text;
            }, $row->staff_groups));

            if( $row->requestor_type == 'Leasor' )
                $row->requestor_name = $row->leasor;

            if( $row->requestor_type == 'Tenant' )
                $row->requestor_name = $row->tenant_name;
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['code'] = 200;
        $ret['content'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    public function changeRepairStatus(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $id = $request->get('id',0);
        $status = $request->get('status', 'Pending');

        $data = EngRepairRequest::getDetail($id);

        $repair = EngRepairRequest::find($id);

        if( $status == 'Reopen' )
            $repair->status_name = 'Pending';
        else
            $repair->status_name = $status;

        if( $status == 'Closed' )
            $repair->closed_at = $cur_time;

        if( $status == 'In Progress' )
            $repair->start_date = $cur_time;

        if( $status == 'Completed' )
            $repair->end_date = $cur_time;

        $repair->save();

        $ret = array();
        $ret['code'] = 200;

        $this->sendUpdateNotificaiton($id, $data);

        if( $status == 'Completed' && $status != $data->status_name )       // to completed
            $this->sendCompletedNotificaiton($id);

        if( $status == 'Rejected' && $status != $data->status_name )       // to rejected
            $this->sendRejectedNotificaiton($id);

        if( $status == 'In Progress' && $status != $data->status_name )       // to inprogress
            $this->sendInprogressNotificaiton($id);

        if( $status == 'Assigned' && $status != $data->status_name )       // to assigned
            $this->sendAssignedNotificaiton($id);

        if( $status == 'On Hold' && $status != $data->status_name )       // to onhold
            $this->sendOnHoldNotificaiton($id);

        if( $status == 'Closed' && $status != $data->status_name )       // to closed
            $this->sendClosedNotificaiton($id);

        if( $status == 'Reopen')
            $this->sendReopenNotificaiton($id);

        $this->sendRepairRefreshEvent($repair->property_id, 'refresh_repair_page', $repair, 0);

        return Response::json($ret);

    }

    public function changeAssignee(Request $request)
    {
        $id = $request->get('id',0);
        $assignee = $request->get('assignee', 0);
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $data = EngRepairRequest::getDetail($id);

        $repair = EngRepairRequest::find($id);

        $repair->assignee = $assignee;
        if( $assignee > 0 && $repair->status_name == 'Pending' )
            $repair->status_name = 'Assigned';

        $repair->save();

        $this->createWorkorderFromRepair($repair, $method);

        $ret = array();
        $ret['code'] = 200;

        $this->sendUpdateNotificaiton($id, $data);
        if( $assignee > 0 && $assignee != $data->assignee )   // changed assigne
            $this->sendCreationNotificaiton($id, $assignee, 0);

        return Response::json($ret);
    }

    public function changeStaffGroup(Request $request)
    {
        $id = $request->get('id',0);
        $staff_groups = $request->get('staff_groups', 0);
        $staff_groups = json_decode($staff_groups, true);
        $method = Functions::getRequestMethod($request->get('device_id', ''));

        $repair = EngRepairRequest::find($id);

        if( count($staff_groups) > 0 && $repair->status_name == 'Pending' )
            $repair->status_name = 'Assigned';

        $repair->save();

        EngRepairStaff::addStaffGroupData($id, $staff_groups);

        $this->createWorkorderFromRepair($repair, $method);

        $this->sendRepairRefreshEvent($repair->property_id, 'refresh_repair_page', $repair, 0);

        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $staff_groups;
        $ret['id'] = $id;

        return Response::json($ret);
    }

    private function createWorkorderFromRepair($repair, $method)
    {
        if( empty($repair) )
            return;

        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $cur_date = date("Y-m-d");

        // create work order
        $workorder = WorkOrder::where('request_id', $repair->id)
            ->where('property_id', $repair->property_id)
            ->where('request_flag', 1)
            ->first();

        $new_flag = false;
        if( empty($workorder) )
        {
            $workorder = new WorkOrder();
            $new_flag = true;
        }

        $staff_group = EngRepairStaff::getStaffGroupData($repair->id);

        if( $repair->status_name != 'Closed' )
        {
            $workorder->property_id = $repair->property_id;
            $workorder->name = $repair->repair;
            $workorder->user_id = $repair->user_id;
            $workorder->priority = $repair->priority;
            $workorder->equipment_id = $repair->equipment_id;
            $workorder->location_id = $repair->location_id;
            if( $new_flag == true )
            {
                $workorder->created_date = $cur_time;
                $workorder->start_date = $cur_date;
                $workorder->daily_id = WorkOrder::getMaxDailyID($repair->property_id, $cur_date);
                $workorder->status = 'Pending';
                $workorder->work_order_type = 'Repairs';
                $workorder->request_id = $repair->id;
                $workorder->request_flag = 1; //1=requet, 2= workorder, 3= preventive automatically, default =2=workorder
            }
            $workorder->schedule_date = $repair->schedule_date;
            $workorder->due_date = $repair->schedule_date;
            $workorder->end_date = $repair->schedule_date;
            $workorder->estimated_duration = $repair->estimated_duration;
            $workorder->description = $repair->comments;

            $workorder->purpose_start_date = $workorder->start_date;
            $workorder->purpose_end_date = $workorder->end_date;

            $staff_cost = 0;
            for ($i = 0; $i < count($staff_group); $i++) {
                $staff_cost += $staff_group[$i]['staff_cost'] ;
            }
            $workorder->staff_cost = $staff_cost;

            $workorder->save();

            if( $new_flag == true )
                $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, 0);

            if( !empty($workorder) )
            {
                if ($repair->status_name == 'Pending')
               {
                    $workorder->status = 'Pending';
                    $workorder->save();
                    $this->sendWorkorderRefreshEvent($workorder->property_id, 'refresh_workorder_page', $workorder, 0);
               }   
                app('App\Http\Controllers\Frontend\EquipmentController')->createStaffFromWorkOrder($repair->property_id, $workorder->id, $staff_group);
                app('App\Http\Controllers\Frontend\EquipmentController')->setWorkorderStaffStatusLogIndividual($workorder, 'create workorder', 'workorder', $method);
            }
        }
    }

    private function sendWorkorderNotification($staff_group, $workorder)
    {
        if( empty($workorder) )
            return;

        $payload = array();
        $payload['table_id'] = $workorder->id;
        $payload['table_name'] = 'eng_workorder';
        $payload['property_id'] = $workorder->property_id;
        $payload['notify_type'] = 'workorder';
        $payload['type'] = 'Work Order Created';
        $payload['header'] = 'Engineering';

        $message = sprintf('W0-%05d - %s has been created', $workorder->id, $workorder->name);

        foreach($staff_group as $row)
        {
            if ($row['staff_type'] == 'group') {
                $userlist = DB::table('common_user_group_members as cgm')
                    ->leftJoin('common_users as cu', 'cgm.user_id', '=', 'cu.id')
                    ->leftJoin('common_job_role as cr', 'cr.id', '=', 'cu.job_role_id')
                    ->where('cgm.group_id', $row['staff_id'])
                    ->select(DB::raw('cu.*'))
                    ->get();

                for ($u = 0; $u < count($userlist); $u++) {
                    Functions::sendPushMessgeToDeviceWithRedisNodejs(
                            $userlist[$u], $workorder->id, 'Workorder', $message, $payload
                        );		
                }

            } else { // if staff type is single(one person)
                $staff_id = $row['staff_id'];
                $assignee = CommonUser::find($staff_id);

                if( !empty($assignee) )
                {
                    Functions::sendPushMessgeToDeviceWithRedisNodejs(
                                $assignee, $workorder->id, 'Workorder', $message, $payload
                        );				
                }
            }
        }
    }

    public function changeDuration(Request $request)
    {
        $id = $request->get('id',0);
        $estimated_duration = $request->get('estimated_duration', 0);

        $repair = EngRepairRequest::find($id);

        $repair->estimated_duration = $estimated_duration;

        $repair->save();

        $this->sendRepairRefreshEvent($repair->property_id, 'refresh_repair_page', $repair, 0);

        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function changeDuedate(Request $request)
    {
        $id = $request->get('id',0);
        $due_date = $request->get('due_date', '');

        $repair = EngRepairRequest::find($id);
        $repair->due_date = $due_date;
        $repair->save();

        $this->sendRepairRefreshEvent($repair->property_id, 'refresh_repair_page', $repair, 0);

        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function sendMailApprove(Request $request) {
		$property_id = $request->get('property_id', 4);
		$status = $request->get('status', 0);
		$id = $request->get('id', 0);
		$repair =  EngRepairRequest::find($id);
        $ret = array();
		if( $status == 1 )		// close
		{
            if ($repair->status_name == 'Completed'){
            date_default_timezone_set(config('app.timezone'));
            $datetime = date('Y-m-d H:i:s');

            $repair->status_name = "Closed";
            $repair->closed_at = $datetime;
			$repair->save();
			echo sprintf("Work Request WR%s has been Closed.", $this->getDailyId(($repair)));

            $this->sendClosedNotificaiton($id);


            }
            else	{
                echo sprintf("Work Request WR%s has already been Closed.", $this->getDailyId(($repair)));
            }
		}

		if( $status == 2 )		// reopen
		{
			if ($repair->status_name == 'Completed'){

            $repair->status_name = "Pending";
            $repair->save();

            echo sprintf("Work Request WR%s has been Reopened.", $this->getDailyId(($repair)));

            $this->sendReopenNotificaiton($id);

            }
            else{
                echo sprintf("Work Request WR%s cannot be Reopened.", $this->getDailyId(($repair)));
            }


        }

    }

    public function getLocationTotalListData($filter, $client_id, $user_id)
	{
		$ret = array();

        $property_list = CommonUser::getPropertyIdsByJobroleids($user_id);

		$ret = DB::table('services_location as sl')
			->join('common_property as cp', 'sl.property_id', '=', 'cp.id')
			->join('services_location_type as lt', 'sl.type_id', '=', 'lt.id')
			->where('sl.name', 'like', $filter)
			->whereIn('sl.property_id', $property_list)
			->groupBy('sl.name')
			->groupBy('sl.type_id')
		//	->take(10)
			->select(DB::Raw('sl.id, sl.name, sl.property_id, sl.id as lg_id, lt.type, cp.name as property'))
			->get();

		return $ret;
    }

    public function checkRepair(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        // get property list
        $property_list = DB::table('common_property')->get();
        foreach($property_list as $pro)
        {
            $property_id = $pro->id;

            // get completed timeout
            $rules = array();
            $rules['repair_completed_timeout'] = 48;

            $rules = PropertySetting::getPropertySettings($property_id, $rules);

            $end_time = date('Y-m-d H:i:s', strtotime("-" . $rules['repair_completed_timeout'] . " Hours", strtotime($cur_time)));

            $list = EngRepairRequest::where('property_id', $property_id)
                ->where('end_date', '<=', $end_time)
                ->where('status_name', 'Completed')
                ->get();

            foreach($list as $row)
            {
                $row->status_name = 'Closed';
                $row->closed_at = 'Closed';
                $row->save();

                $this->sendClosedNotificaiton($row->id);
            }

            if( count($list) > 0 )
                $this->sendRepairRefreshEvent($property_id, 'refresh_repair_page', $rules, 0);

            echo json_encode($list);
        }
    }

    public function migrateAssignee(Request $request)
    {
        $list = DB::table('eng_repair_request as er')
                    ->join('common_users as cu', 'er.assignee', '=', 'cu.id')
                    ->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
                    ->select(DB::raw('er.id, er.assignee, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, jr.cost'))
                    ->get();

        $staff_type = 'single';

        foreach($list as $row)
        {
            DB::table('eng_repair_staff')
                ->where('request_id', $row->id)
                ->delete();

            $staff_id = $row->assignee;
            $staff_name = $row->wholename;
            $staff_cost = $row->cost;

            DB::table('eng_repair_staff')->insert(['request_id' => $row->id,
                'staff_id' => $staff_id,
                'staff_type' => $staff_type,
                'staff_name' => $staff_name
            ]);

            $workorder = DB::table('eng_workorder')
                ->where('request_flag', 1)
                ->where('request_id', $row->id)
                ->first();

            if( !empty($workorder) )
            {
                DB::table('eng_workorder_staff')
                    ->where('workorder_id', $workorder->id)
                    ->where('staff_id', $staff_id)
                    ->delete();

                DB::table('eng_workorder_staff')->insert(['workorder_id' => $workorder->id,
                    'staff_id' => $staff_id,
                    'staff_name' => $staff_name,
                    'staff_type' => $staff_type,
                    'staff_cost' => $staff_cost,
                ]);

                DB::table('eng_workorder_staff_status')
                    ->where('workorder_id', $workorder->id)
                    ->where('staff_id', $staff_id)
                    ->delete();

                DB::table('eng_workorder_staff_status')->insert(['workorder_id' => $workorder->id,
                    'staff_id' => $staff_id,
                    'staff_cost' => $staff_cost,
                    'status' => 'Pending'
                ]);
            }
        }

        echo json_encode($list);
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

    public function deleteRequest(Request $request)
	{
        $id = $request->get('id', 0);
        $location_id = $request->get('location_id',0);

        $property = DB::table('services_location as sl')
            ->where('sl.id', $location_id)
            ->select(DB::raw('sl.property_id'))
            ->first();
        $property_id = $property->property_id;

        DB::table('eng_repair_request')
            ->where('property_id', $property_id)
			->where('id', $id)
            ->update(['delete_flag' => 1]);

        $workorder = WorkOrder::where('request_id', $id)
            ->where('property_id', $property_id)
            ->where('request_flag', 1)
            ->first();

        if (!empty($workorder)){
             $workorder->delete_flag = 1;
             $workorder->save();
        }


		$ret = array();
		$ret['code'] = 200;

		return Response::json($ret);
	}
}
