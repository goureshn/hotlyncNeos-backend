<?php

namespace App\Http\Controllers\Backoffice\Configuration;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Common\Property;
use App\Models\Common\PropertySetting;

use DB;
use Response;


class CallCenterController extends Controller
{

    function getCallCenter(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $ret = array();

        if($setting_group == 'call_center' ) {
            $rules = array();
            $rules['idle_duration'] = '';
            $rules['max_idle_duration'] = '';
            $rules['max_wrapup_time'] = '';
            $rules['max_outgoing_wrapup_time'] = '0';
            $rules['no_avail_time'] = '';
            $rules['no_avail_email'] = '';
            $rules['call_to_idle'] = 1;
            $rules['sip_server'] = 'developdxb.myhotlync.com';

            $rules['call_enter_threshold_flag'] = '1';

            $rules['call_center_queue_yellow'] = '5';
            $rules['call_center_queue_red'] = '10';

            $rules['call_center_longest_wait_yellow'] = '5:00';
            $rules['call_center_longest_wait_red'] = '10:00';

            $rules['call_center_estimated_time_yellow'] = '5:00';
            $rules['call_center_estimated_time_red'] = '10:00';
            $rules['call_center_estimated_time_unit'] = 'Day';

            $rules['call_center_average_time_yellow'] = '5:00';
            $rules['call_center_average_time_red'] = '10:00';
            $rules['call_center_average_time_unit'] = 'Day';

            $rules['call_center_average_speed_yellow'] = '5:00';
            $rules['call_center_average_speed_red'] = '10:00';
            $rules['call_center_average_speed_unit'] = 'Day';
            
            $rules['auto_wrapup_flag'] = true;
            $rules['caller_info_save_flag'] = true;
            $rules['call_center_widget'] = true;
            $rules['softphone_enabled'] = true;

            $ret = PropertySetting::getPropertySettings($property_id, $rules);
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

    function saveCallCenter(Request $request) {
        $property_id = $request->get('property_id' , 0);        
        $fieldname = $request->get('fieldname' ,'');
        $fieldvalue = $request->get('fieldvalue','');

        $values = array();
        $values[$fieldname] = $fieldvalue;
        PropertySetting::savePropertySetting($property_id, $values);
        
        $ret = array();
        $ret['code'] = '200';

        return Response::json($ret);
    }

}
