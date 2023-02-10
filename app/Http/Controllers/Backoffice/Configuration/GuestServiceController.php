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

class GuestServiceController extends Controller
{

    function getGuestService(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $ret = array();

        if($setting_group == 'guestservice' ) {
           

            

            $data = DB::table('property_setting')
            ->where('settings_key', 'muncip_fee')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['guestservice']['muncip_fee']);

            $data = DB::table('property_setting')
            ->where('settings_key', 'ser_chrg')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['guestservice']['ser_chrg']);
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

    function saveGuestService(Request $request) {
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
