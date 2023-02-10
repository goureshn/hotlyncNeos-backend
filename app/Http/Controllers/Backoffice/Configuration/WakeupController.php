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

class WakeupController extends Controller
{

    function getWakeup(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $ret = array();

        if($setting_group == 'wakeup' ) {
            $data = DB::table('property_setting')
                ->where('settings_key', 'duty_manager_notify')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['wakeup']['duty_manager_notify']);


            $data = DB::table('property_setting')
                ->where('settings_key', 'duty_manager_device')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['wakeup']['duty_manager_device']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'duty_manager')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['wakeup']['duty_manager']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'awu_retry_attemps')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['wakeup']['awu_retry_attemps']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'awu_retry_mins')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['wakeup']['awu_retry_mins']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'awu_max_snooze')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['wakeup']['awu_max_snooze']);


            $data = DB::table('property_setting')
                ->where('settings_key', 'snooze_time')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['wakeup']['snooze_time']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'inprogress_max_wait')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['wakeup']['inprogress_max_wait']);


            $data = DB::table('property_setting')
                ->where('settings_key', 'awu_record_flag')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['wakeup']['awu_record_flag']);

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

    function saveWakeup(Request $request) {
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
