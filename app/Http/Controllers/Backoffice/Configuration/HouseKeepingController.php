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

class HouseKeepingController extends Controller
{

    function getHouseKeeping(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $ret = array();

        if($setting_group == 'housekeeping' ) {
            

            $data = DB::table('property_setting')
                ->where('settings_key', 'max_turndown_duration')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['housekeeping']['max_turndown_duration']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'turn_down_service')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['housekeeping']['turn_down_service']);

            $data = DB::table('property_setting')
            ->where('settings_key', 'hskp_inspection')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['housekeeping']['hskp_inspection']);

            $data = DB::table('property_setting')
            ->where('settings_key', 'hskp_cleaning_time')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['housekeeping']['hskp_cleaning_time']);

            $data = DB::table('property_setting')
            ->where('settings_key', 'housekeeping_dept_id')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['housekeeping']['housekeeping_dept_id']);

            $data = DB::table('property_setting')
            ->where('settings_key', 'supervisor_job_role')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['housekeeping']['supervisor_job_role']);
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

    function saveHouseKeeping(Request $request) {
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
