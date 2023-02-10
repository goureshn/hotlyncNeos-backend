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

class MinibarController extends Controller
{

    function getMinibar(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $ret = array();

        if($setting_group == 'minibar' ) {
            $data = DB::table('property_setting')
                ->where('settings_key', 'minibar_posting_type')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['minibar']['minibar_posting_type']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'minibar_posting_checkout_allow')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['minibar']['minibar_posting_checkout_allow']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'disable_minibar_nopost')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['minibar']['disable_minibar_nopost']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'allow_minibar_post')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['minibar']['allow_minibar_post']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'vat')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['minibar']['vat']);

            $data = DB::table('property_setting')
            ->where('settings_key', 'vat_no')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['minibar']['vat_no']);

            $data = DB::table('property_setting')
            ->where('settings_key', 'muncip_fee')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['minibar']['muncip_fee']);

            $data = DB::table('property_setting')
            ->where('settings_key', 'ser_chrg')
            ->where('property_id', $property_id)
            ->first();
            $this->returnValue($data, $ret['minibar']['ser_chrg']);
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

    function saveMinibar(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $fieldname = $request->get('fieldname' ,'');
        $fieldvalue = $request->get('fieldvalue','');

        $queryTest = DB::table('property_setting')
            ->where('settings_key', $fieldname)
            ->where('property_id', $property_id)
            ->first();

        if (!empty($queryTest)) {
            $data = DB::table('property_setting')
                ->where('settings_key', $fieldname)
                ->where('property_id', $property_id)
                ->update(['value' => $fieldvalue]);
        } else {
            $data = DB::table('property_setting')
                ->where('settings_key', $fieldname)
                ->where('property_id', $property_id)
                ->insert([
                    'property_id' => $property_id,
                    'settings_key' => $fieldname,
                    'value' => $fieldvalue]);
        }

        $ret = array();
        if($data)
            $ret['success'] = '200';

        return Response::json($ret);
    }

}
