<?php

namespace App\Http\Controllers\Backoffice\Configuration;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Controllers\UploadController;

use App\Models\Common\AdminArea;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;
use App\Modules\Functions;

use Excel;
use DB;
use Datatables;
use Response;

class ReportController extends Controller
{
    function getUserList(Request $request) 
    {
        $filter = $request->get('filter', '');

        $userlist = DB::table('common_users as cu')
                        ->whereRaw("CONCAT_WS(' ', first_name, last_name) like '%".$filter."%'")
                        ->where('deleted', 0)
                        ->select(DB::raw("cu.*, CONCAT_WS(' ', first_name, last_name) as fullname"))                                                
                        ->get();

        $ret = array();

        foreach($userlist as $row)
        {
            $ret[] = $row->fullname;
        }

        
        return Response::json($ret);
    }
    function getReport(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $ret = array();

        if($setting_group == 'report' ) {
           
            $data = DB::table('property_setting')
                ->where('settings_key', 'guest_fac_report_recipients')
                ->where('property_id', $property_id)
                ->first();            
            $this->returnUserName($data, $ret['report']['guest_fac_report_recipients']);

            $ret['report']['email'] = Functions::getUserEmailArray($data->value, ";");

            $data = DB::table('property_setting')
                ->where('settings_key', 'guest_fac_report_time_start')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['report']['guest_fac_report_time_start']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'guest_fac_report_time_interval')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['report']['guest_fac_report_time_interval']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'guest_feedback_report_time_start')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['report']['guest_feedback_report_time_start']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'guest_feedback_report_interval')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['report']['guest_feedback_report_interval']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'guest_feedback_report_recipients')
                ->where('property_id', $property_id)
                ->first();
            $this->returnUserName($data, $ret['report']['guest_feedback_report_recipients']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'complaint_report_recipients')
                ->where('property_id', $property_id)
                ->first();
            $this->returnUserName($data, $ret['report']['complaint_report_recipients']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'complaint_report_time_start')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['report']['complaint_report_time_start']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'complaint_report_time_interval')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['report']['complaint_report_time_interval']);


        }

        return Response::json($ret);
    }

    function returnValue($data, &$return_value ) {
        if(!empty($data)) {
            $return_value = $data->value;
        }else{
            $return_value = "There is no value.";
        }
    }

    function returnUserName($data, &$return_value ) {
        if( !empty($data) )
        {
            $userlist = DB::table('common_users')
                ->whereRaw("FIND_IN_SET(id, '".$data->value."')")
                ->where('deleted', 0)
                ->select(DB::raw("CONCAT_WS(' ', first_name, last_name) as fullname"))
                ->get();
            
            foreach($userlist as $row)
            {
                $return_value[] = array('text' => $row->fullname);
            }
        }
    }

    function saveUserSetting(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $fieldname = $request->get('fieldname' ,'');
        $fieldvalue = $request->get('fieldvalue','');

        $value_list = explode(",", $fieldvalue);
        $user_id_list = [];
        foreach($value_list as $row)
        {
            $user = DB::table('common_users')
                ->whereRaw("CONCAT_WS(' ', first_name, last_name) = '".$row."'")
                ->where('active_status', 1)
                ->where('deleted', 0)
                ->first();

            if( !empty($user) )
            {
                $user_id_list[] = $user->id;
            }    
        }

        $value = implode(",", $user_id_list);

        $data = DB::table('property_setting')
            ->where('settings_key', $fieldname)
            ->where('property_id', $property_id)
            ->update(['value' => $value]);

        $ret = array();
        if($data)  
            $ret['success'] = '200';

        return Response::json($ret);
    }

    function saveReport(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $fieldname = $request->get('fieldname' ,'');
        $fieldvalue = $request->get('fieldvalue','');
        $data = DB::table('property_setting')
            ->where('settings_key', $fieldname)
            ->where('property_id', $property_id)
            ->update(['value' => $fieldvalue]);
        $ret = array();
        if($data)  $ret['success'] = '200';

        return Response::json($ret);
    }

}
