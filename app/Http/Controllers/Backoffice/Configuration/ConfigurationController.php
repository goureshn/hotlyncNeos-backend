<?php

namespace App\Http\Controllers\Backoffice\Configuration;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Common\Property;
use App\Models\Common\PropertySetting;

use DB;
use Response;


class ConfigurationController extends Controller
{

    function getConfigValue(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $ret = array();

        if($setting_group == 'lnf' ) {
            $rules = array();
            $rules['found_user_group_ids'] = '';
            $rules['inquiry_user_group_ids'] = '';

            $ret = PropertySetting::getPropertySettings($property_id, $rules);

            if( !empty($ret['found_user_group_ids']) )
                $ret['found_user_group_ids'] = DB::table('common_user_group')
                                                ->whereRaw("id IN (" . $ret['found_user_group_ids'] . ")")
                                                ->get();
            else
                $ret['found_user_group_ids'] = [];

            if( !empty($ret['inquiry_user_group_ids']) )
                $ret['inquiry_user_group_ids'] = DB::table('common_user_group')
                                                ->whereRaw("id IN (" . $ret['inquiry_user_group_ids'] . ")")
                                                ->get();
            else
                $ret['inquiry_user_group_ids'] = [];

        }

        return Response::json($ret);
    }

    function saveConfigValue(Request $request) {
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

    public function saveChatbotSettingInfo(Request $request) {
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

    public function getChatbotSettingInfo(Request $request) {
        $property_id = $request->get('property_id' , 0);

        $data = DB::table('property_setting')
            ->where('settings_key', 'chatbot_limit_time')
            ->where('property_id', $property_id)
            ->first();

        $ret = [];

        if (!empty($data)) {
            $ret['chatbot_limit_time'] = $data->value;
        } else {
            $ret['chatbot_limit_time'] = $data->value;
        }

        return Response::json($ret);
    }

}
