<?php

namespace App\Http\Controllers\Backoffice\Configuration;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Controllers\UploadController;

use App\Models\Common\AdminArea;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;

use Excel;
use DB;
use Datatables;
use Response;

class CallAccountController extends Controller
{

    function getCallAccount(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $ret = array();
        if($setting_group == 'call_account' ) {
            $data = DB::table('property_setting')
                ->where('settings_key', 'call_end_setting')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['call_account']['call_end_setting']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'min_approval_duration')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['call_account']['min_approval_duration']);


            $data = DB::table('property_setting')
                ->where('settings_key', 'min_approval_amount')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['call_account']['min_approval_amount']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'max_unmarked_count')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['call_account']['max_unmarked_count']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'pre_approved_call_types')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['call_account']['pre_approved_call_types']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'max_approver_notify')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['call_account']['max_approver_notify']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'max_close_notify')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['call_account']['max_close_notify']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'call_reminder_date')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['call_account']['call_reminder_date']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'check_call_classification_time')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['call_account']['check_call_classification_time']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'max_unmarked_cost')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['call_account']['max_unmarked_cost']);
        }

        if($setting_group == 'night_audit' ) {
            $data = DB::table('property_setting')
                ->where('settings_key', 'night_audit_report_type')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['night_audit']['night_audit_report_type']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'night_audit_report_extensions')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['night_audit']['night_audit_report_extensions']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'night_audit_email_flag')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['night_audit']['night_audit_email_flag']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'night_audit_recipients')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['night_audit']['night_audit_recipients']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'night_audit_include_mb')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['night_audit']['night_audit_include_mb']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'night_audit_file_type')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['night_audit']['night_audit_file_type']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'night_audit_report_subject')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['night_audit']['night_audit_report_subject']);

            $data = DB::table('property_setting')
            ->where('settings_key', 'complaint_in_nightaudit')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['night_audit']['complaint_in_nightaudit']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'night_audit_admin_report_subject')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['night_audit']['night_audit_admin_report_subject']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'night_audit_guest_report_subject')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['night_audit']['night_audit_guest_report_subject']);

            $data = DB::table('property_setting')
            ->where('settings_key', 'last_night_audit')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['night_audit']['last_night_audit']);

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

    function saveCallAccount(Request $request) {
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
