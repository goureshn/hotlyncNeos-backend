<?php

namespace App\Http\Controllers\Backoffice\Configuration;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Common\PropertySetting;

use DB;
use Response;

class GeneralController extends Controller
{

    function getGeneral(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $ret = array();

        if($setting_group == 'password_setting' ) {
            $data = DB::table('property_setting')
                ->where('settings_key', 'password_compare_flag')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['password_setting']['password_compare_flag']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'password_minimum_length')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['password_setting']['password_minimum_length']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'password_expire_date')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['password_setting']['password_expire_date']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'last_use_password')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['password_setting']['last_use_password']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'password_type')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['password_setting']['password_type']);
        }

        if($setting_group == 'account_setting' ) {
            $data = DB::table('property_setting')
                ->where('settings_key', 'login_session_timeout')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['account_setting']['login_session_timeout']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'password_lock_attempts')
                ->where('property_id', $property_id)
                ->first();

            $this->returnValue($data, $ret['account_setting']['password_lock_attempts']);    

            $data = DB::table('property_setting')
                ->where('settings_key', 'only_allowed_domain_in_email_flag')
                ->where('property_id', $property_id)
                ->first();

            $this->returnValue($data, $ret['account_setting']['only_allowed_domain_in_email_flag']);   

            $data = DB::table('property_setting')
                ->where('settings_key', 'allowed_domain_in_email')
                ->where('property_id', $property_id)
                ->first();

            $this->returnValue($data, $ret['account_setting']['allowed_domain_in_email']); 
            
            $data = DB::table('property_setting')
                ->where('settings_key', 'allow_multiple_login')
                ->where('property_id', $property_id)
                ->first();

            $this->returnValue($data, $ret['account_setting']['allow_multiple_login']);      
        }

        if($setting_group == 'site_directory') {
            $rules = array();
            $rules['interface_host'] = 'http://127.0.0.1:3000/';
            $rules['hotlync_host'] = 'http://127.0.0.1/';
            $rules['live_host'] = 'http://127.0.0.1:8001/';
            $rules['public_live_host'] = 'http://127.0.0.1:8001/';
            $rules['public_domain'] = 'http://127.0.0.1:8001/';
            $rules['export_server'] = 'http://127.0.0.1:8001/';
            $rules['public_url'] = 'http://127.0.0.1:8001/';
            $rules['hotlync_internal_host'] = 'http://127.0.0.1/';
            $rules['mobile_host'] = 'http://127.0.0.1:8008/';
            $rules['low_free_size'] = 500;
            $rules['low_free_notify'] = 1;
            $rules['low_free_emails'] = '';

            $ret['site_directory'] = PropertySetting::getPropertySettings($property_id, $rules);            
        }

        if($setting_group == 'notification') {
            $data = DB::table('property_setting')
                ->where('settings_key', 'notification_smtp_server')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['notification']['notification_smtp_server']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'notification_smtp_user')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['notification']['notification_smtp_user']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'notification_smtp_sender')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['notification']['notification_smtp_sender']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'notification_smtp_password')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['notification']['notification_smtp_password']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'notification_smtp_port')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['notification']['notification_smtp_port']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'notification_smtp_tls')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['notification']['notification_smtp_tls']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'sms_gateway_settings')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['notification']['sms_gateway_settings']);

        }

        if($setting_group == 'smtp') {
            $data = DB::table('property_setting')
                ->where('settings_key', 'smtp_server')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['smtp']['smtp_server']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'smtp_user')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['smtp']['smtp_user']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'smtp_sender')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['smtp']['smtp_sender']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'smtp_password')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['smtp']['smtp_password']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'smtp_port')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['smtp']['smtp_port']);

            $data = DB::table('property_setting')
                ->where('settings_key', 'smtp_tls')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['smtp']['smtp_tls']);


        }
        if($setting_group == 'currency') {
            $data = DB::table('property_setting')
                ->where('settings_key', 'currency')
                ->where('property_id', $property_id)
                ->first();
            $this->returnValue($data, $ret['currency']['currency']);
        }

        if($setting_group == 'mobileserver') {
            $rules = array();
            $rules['mobileserver_alarm_to_email'] = 'support@ennovatech.ae';
            
            $ret['mobileserver'] = PropertySetting::getPropertySettings($property_id, $rules);  
        }
        
        if($setting_group == 'soundfile') {
                        $data = DB::table('property_setting')
                            ->where('settings_key', 'soundfile')
                            ->where('property_id', $property_id)
                            ->first();
                        //$ret['soundfile']['soundfile'] = public_path().$data->value;
                        $this->returnValue($data, $ret['soundfile']['soundfile']);
                        //$ret['soundfile']['soundfile'] = public_path().$ret['soundfile']['soundfile'];
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

    function saveGeneral(Request $request) {
        $property_id = $request->get('property_id' , 0);
        $setting_group = $request -> get('setting_group' ,'');
        $fieldname = $request->get('fieldname' ,'');
        $fieldvalue = $request->get('fieldvalue','');
        if($fieldname == 'soundfile')
        {
            if($request->hasFile('file') === false )
            {
                $ret = array();
                $ret['success'] = '200';
            }
            else
            {
                $output_dir = "sound/";
                $fileName = $_FILES['file']["name"];
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $filename1 = "noti_".time().".".strtolower($ext);

                $dest_path = $output_dir . $filename1;
                move_uploaded_file($_FILES['file']["tmp_name"], $dest_path);

                    $values = array($fieldname => '/'.$dest_path);

                PropertySetting::savePropertySetting($property_id, $values);
                
                $ret = array();
                $ret['success'] = '200';
            }
            }else
            {
                $values = array($fieldname => $fieldvalue);
                PropertySetting::savePropertySetting($property_id, $values);

                $ret = array();
                $ret['success'] = '200';
            }
           return Response::json($ret);
    }

    public function getSettingValue(Request $request)
    {
        $property_id = $request->get('property_id', 0);
        $input = $request->all();

        $setting = PropertySetting::getPropertySettings($property_id, $input);

        return Response::json($setting);
    }

}