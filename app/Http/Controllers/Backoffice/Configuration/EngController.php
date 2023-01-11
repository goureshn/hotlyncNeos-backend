<?php

namespace App\Http\Controllers\Backoffice\Configuration;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Http\Controllers\UploadController;

use App\Models\Common\AdminArea;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;
use App\Models\Common\PropertySetting;
use Excel;
use DB;
use Datatables;
use Response;

class EngController extends Controller
{

    function getStockNotifiGroup(Request $request) {
       $property_id = $request->get('property_id' , 0);

       $data = DB::table('property_setting')
                ->where('settings_key', 'eng_low_stock_notify')
                ->where('property_id', $property_id)
                ->first();
        $ret = array();
        if(!(empty($data))) {
            $groups = explode("," ,$data->value);
            $ret['group'] = $groups;
        }
        return Response::json($ret);
    }

    function saveStockNotifiGroup(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $group  = $request->get('group','');

        $query = DB::table('property_setting')
            ->where('settings_key', 'eng_low_stock_notify')
            ->where('property_id', $property_id);
        $query_data = clone $query;
        $data = $query_data
            ->first();

        if(!empty($data)) {
            $update_query = clone $query;
            $data = $update_query
                ->update(['value' => $group]);
        }else {
            $insert_query = clone  $query;
            $data = $insert_query
                ->insert(['property_id' => $property_id, 'settings_key' => 'eng_low_stock_notify', 'value' => $group,'comment'=>'this is user group id with multi value and classification comma']);
        }
        $ret = array();

        return Response::json($ret);
    }

    public function saveReminderContract(Request $request) {        
        $input = $request->except(['property_id']);
        $property_id = $request->get('property_id', 0);
        
        $sub_domain = '';
        PropertySetting::savePropertySettings($property_id, $input, $sub_domain);

        return Response::json($input);
    }

    function getReminderContract(Request $request) {
        $property_id = $request->get('property_id' , 0);
        
        $rules = array();
        $rules['eng_user_group_ids'] = '';
        $rules['eng_contract_expire_days'] = '';
      
        $rules = PropertySetting::getPropertySettings($property_id, $rules);

        if( empty($rules['eng_user_group_ids']) )
            $rules['user_group_tags'] = [];
        else
        {    
            $rules['user_group_tags'] = DB::table('common_user_group')
                                            ->whereRaw("id in (" . $rules['eng_user_group_ids'] . ")")
                                            ->get();
        }

        return Response::json($rules);
    }

    public function saveImapConfig(Request $request) {
        $input = $request->except(['property_id']);
        $property_id = $request->get('property_id', 0);
        
        $sub_domain = '';
        PropertySetting::savePropertySettings($property_id, $input, $sub_domain);

        return Response::json($input);
    }

    function getImapConfig(Request $request) {
        $property_id = $request->get('property_id' , 0);
        
        $rules = array();
        $rules['eng_imap_host'] = '';
        $rules['eng_imap_user'] = '';
        $rules['eng_imap_pass'] = '';
        $rules['eng_imap_port'] = 993;
        $rules['eng_imap_tls'] = true;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);

        return Response::json($rules);
    }

    public function saveItImapConfig(Request $request) {
        $input = $request->except(['property_id']);
        $property_id = $request->get('property_id', 0);
        
        $sub_domain = '';
        PropertySetting::savePropertySettings($property_id, $input, $sub_domain);

        return Response::json($input);
    }

    function getItImapConfig(Request $request) {
        $property_id = $request->get('property_id' , 0);
        
        $rules = array();
        $rules['it_imap_host'] = '';
        $rules['it_imap_user'] = '';
        $rules['it_imap_pass'] = '';
        $rules['it_imap_port'] = 993;
        $rules['it_imap_tls'] = true;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);

        return Response::json($rules);
    }


    public function saveRepairRequest(Request $request) {        
        $input = $request->except(['property_id']);
        $property_id = $request->get('property_id', 0);
        
        $sub_domain = '';
        PropertySetting::savePropertySettings($property_id, $input, $sub_domain);

        return Response::json($input);
    }

    function getRepairRequest(Request $request) {
        $property_id = $request->get('property_id' , 0);
        
        $rules = array();
        $rules['repair_request_user_group_ids'] = '';
        $rules['repair_auth_on'] = false;
        $rules['create_workorder_flag'] = false;
        $rules['repair_completed_timeout'] = 48;
        $rules['repair_request_equipment_status'] = false;
        
        $rules = PropertySetting::getPropertySettings($property_id, $rules);

        if( empty($rules['repair_request_user_group_ids']) )
            $rules['user_group_tags'] = [];
        else
        {    
            $rules['user_group_tags'] = DB::table('common_user_group')
                                            ->whereRaw("id in (" . $rules['repair_request_user_group_ids'] . ")")
                                            ->get();
        }

        return Response::json($rules);
    }

    public function savePreventiveConfig(Request $request) {        
        $input = $request->except(['property_id']);
        $property_id = $request->get('property_id', 0);
        
        $sub_domain = '';
        PropertySetting::savePropertySettings($property_id, $input, $sub_domain);

        return Response::json($input);
    }

    function getPreventiveConfig(Request $request) {
        $property_id = $request->get('property_id' , 0);
        
        $rules = array();
        $rules['work'] = 0;
        
        $rules = PropertySetting::getPropertySettings($property_id, $rules);

        return Response::json($rules);
    }

    public function saveWorkRequestConfig(Request $request) {
        $input = $request->except(['property_id']);
        $property_id = $request->get('property_id', 0);

        $sub_domain = '';
        PropertySetting::savePropertySettings($property_id, $input, $sub_domain);

        return Response::json($input);
    }

    function getWorkRequestConfig(Request $request) {
        $property_id = $request->get('property_id' , 0);

        $rules = array();
        $rules['work_request_equipment_status'] = 0;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);

        return Response::json($rules);
    }



}
