<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Common\PropertySetting;
use App\Models\Common\CommonUser;


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


class ContractController extends Controller
{

    public function contractList(Request $request)
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
        $searchtext = $request->get('searchtext','');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');
        $user_id = $request->get('user_id', 0);
        $dispatcher = $request->get('dispatcher', 0);

        $date = new DateTime($cur_time);
        $date->sub(new DateInterval('P1D'));
        $last_time = $date->format('Y-m-d H:i:s');

        $property_list = CommonUser::getPropertyIdsByJobroleids($dispatcher);

        if ($pageSize < 0)
            $pageSize = 20;

        $ret = array();
        $query = DB::table('eng_contracts as er')
            ->leftJoin('common_users as cu', 'er.user_id', '=', 'cu.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('common_property as cp', 'er.property_id', '=', 'cp.id')
            ->leftJoin('eng_contract_status as ecs', 'er.status', '=', 'ecs.id')
            ->leftJoin('services_location as sl', 'er.apartment_no', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
        //    ->where('er.property_id', $property_id)
            ->whereIn('er.property_id', $property_list)	
            ->whereRaw(sprintf("DATE(er.created_at) >= '%s' and DATE(er.created_at) <= '%s'", $start_date, $end_date));

        if($searchtext != '')
        {
            $query->where(function ($query) use ($searchtext) {
                $value = '%' . $searchtext . '%';
                $query->where('er.id', 'like', $value)
                    ->orWhere('er.leasor', 'like', $value)
                    ->orWhere('cp.name', 'like', $value)
                    ->orWhere('cu.first_name', 'like', $value)
                    ->orWhere('cu.last_name', 'like', $value)
                    ->orWhere('sl.name', 'like', $value)
                    ->orWhere('slt.type', 'like', $value);
            });
        }

        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->select(DB::raw('er.*, jr.job_role,ecs.status_name, sl.name as location_name, slt.type as location_type, 
                            CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, 
                            cp.name as property_name'))
            ->skip($skip)->take($pageSize)
            ->get();

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['code'] = 200;
        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;
        return Response::json($ret);
    }

    public function createContract(Request $request) {

        $client_id = $request->get('client_id', 4);
        $property_id = $request->get('property_id', 4);

        $input = array();
        $input["property_id"] = $request->get('property_id',0);
        $input["start_date"] = $request->get('start_date', "");
        $input["end_date"] = $request->get('end_date', "");
        $input["leasor"] = $request->get('leasor','');
        $input["apartment_no"] = $request->get('apartment_no',0);
        $input["description"] = $request->get('description','');
        $input["contract_value"] = $request->get('contract_value',0.00);
        $input["leasor_contact"] = $request->get('leasor_contact',"");
        $input["leasor_email"] = $request->get('leasor_email',"");
        $input["age"] = $request->get('age',0);
        $input["additional_members"] = $request->get('additional_members',"");
        $input["reminder"] = $request->get('reminder',false);
        $input["reminder_days"] = $request->get('reminder_days', '');
        $input["user_id"] = $request->get('user_id',0);
        $input["status"] = 4;  // New
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $cur_date = date("Y-m-d");
        $created_at = $cur_time;

        $contract_id = DB::table('eng_contracts')->insertGetId($input);

        DB::table('eng_contracts_log')->insert([
            'contract_id' => $contract_id,
            'action'      => 'created',
            'user_id'     => $input["user_id"],
            'status'      => 4
        ]);

        $ret = array();
        $ret['code'] = 200;
        $ret['id'] = $contract_id;

        return Response::json($ret);
    }

    public function updateContract(Request $request) {

        $client_id = $request->get('client_id', 4);
        $property_id = $request->get('property_id', 4);

        $request_id = $request->get('id',0);

        $contract = DB::table('eng_contracts')->where('id',$request_id)->first();
        $old_value = json_encode($contract);
        $input = array();
        $input["property_id"] = $request->get('property_id',0);
        $input["start_date"] = $request->get('start_date', "");
        $input["end_date"] = $request->get('end_date', "");
        $input["leasor"] = $request->get('leasor','');
        $input["apartment_no"] = $request->get('apartment_no',0);
        $input["description"] = $request->get('description','');
        $input["contract_value"] = $request->get('contract_value',0);
        $input["leasor_contact"] = $request->get('leasor_contact',0);
        $input["leasor_email"] = $request->get('leasor_email',0);
        $input["age"] = $request->get('age',0);
        $input["additional_members"] = $request->get('additional_members',0);
        $input["reminder"] = $request->get('reminder',0);
        $input["reminder_days"] = $request->get('reminder_days', '');
        $input["user_id"] = $request->get('user_id',0);
        $status = $request->get('status',0);
        if($status == 2){
            $input["status"] = 3;  // Renewed
        }

        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $cur_date = date("Y-m-d");
        $created_at = $cur_time;

        DB::table('eng_contracts')->where('id',$request_id)->update($input);

        $contract = DB::table('eng_contracts')->where('id',$request_id)->first();

        DB::table('eng_contracts_log')->insert([
            'contract_id' => $request_id,
            'action'      => 'updated',
            'user_id'     => $input["user_id"],
            'status'      => $contract->status ,
            'comment'     => $old_value,

        ]);

        $ret = array();
        $ret['code'] = 200;
        $ret['id'] = $request_id;

        return Response::json($ret);
    }

    public function checkExpireRemind(Request $request)
    {
		date_default_timezone_set(config('app.timezone'));

        $property_list = DB::table('common_property')->get();

        foreach($property_list as $row)
        {
            $rules = array();
            $rules['eng_contracts_group']  = 4;
           

            $property_id = $row->id;

            $rules = PropertySetting::getPropertySettings($property_id, $rules);

           

            // get user list
            $user_list = CommonUser::getUserListByEmailFromUserGroup($rules['eng_contracts_group']);    
            $user_group_emails = implode(";", array_map(function($item) {
                return $item->email;
            }, $user_list));

            $smtp = Functions::getMailSetting($property_id, 'notification_');   
            $id  = ',';
            $contract_list  = DB::table('eng_contracts as er')
                            ->leftJoin('services_location as sl', 'er.apartment_no', '=', 'sl.id')
                            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
                            ->where('er.property_id', $property_id)
                            ->select(DB::raw('er.*, sl.name as location_name, slt.type as location_type, DATE(er.end_date) as date'))
                            ->get();
      
            foreach($contract_list as $reminder){
                if ($reminder->reminder == 1){

                $expire_days = $reminder->reminder_days;

                if( empty($expire_days) )
                    continue;

                $expire_days_list = explode(',', $expire_days);

                foreach($expire_days_list as $expire_day)
                {
                $end_date = date('Y-m-d', strtotime("$expire_day days"));

                if ($end_date == $reminder->date)
                {

                    $info = array();

                    $info['name'] = 'All';
                    $info['leaser'] = $reminder->leasor;
                    $info['address'] = $reminder->location_name . " - " . $reminder->location_type;        
                    $info['email'] = $reminder->leasor_email;
                    $info['phone'] = $reminder->leasor_contact;        
                    $info['start_date'] = $reminder->start_date;
                    $info['end_date'] = $reminder->end_date;
                    $info['contract_value'] = $reminder->contract_value;
                    $info['description'] = $reminder->description;
                    
                    $email_content = view('emails.eng_contract_expire_reminder', ['info' => $info])->render();
            
                    $message = array();
                    $message['type'] = 'email';
                    $message['subject'] = "Contract Expiry Reminder" ;      
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
            }
           
        
           
            
               
        }
       
        return Response::json($contract_list);
    }

    public function deleteContract(Request $request)
    {
        $id = $request->get('id', 0);
       
        DB::table('eng_contracts')
            ->where('id', $id)
            ->delete();
        
        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

}