<?php

namespace App\Modules;

use App\Models\Common\PropertySetting;
use App\Models\Common\SystemNotification;
use App\Models\IVR\IVRAgentStatusHistory;
use App\Models\Service\Device;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

// php artisan config:cache
// php artisan config:clear

class Functions
{
	//Functions that do not interact with DB
	//------------------------------------------------------------------------------
	public static function get_client_ip() 
	{
	    $ipaddress = '';
	    if (getenv('HTTP_CLIENT_IP'))
	        $ipaddress = getenv('HTTP_CLIENT_IP');
	    else if(getenv('HTTP_X_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	    else if(getenv('HTTP_X_FORWARDED'))
	        $ipaddress = getenv('HTTP_X_FORWARDED');
	    else if(getenv('HTTP_FORWARDED_FOR'))
	        $ipaddress = getenv('HTTP_FORWARDED_FOR');
	    else if(getenv('HTTP_FORWARDED'))
	       $ipaddress = getenv('HTTP_FORWARDED');
	    else if(getenv('REMOTE_ADDR'))
	        $ipaddress = getenv('REMOTE_ADDR');
	    else
	        $ipaddress = 'UNKNOWN';
	    
	    return $ipaddress;
	}

	static function GetLicensePath()
	{
		$license_path = config_path() . "/license.lic";

		return $license_path;
	}

	static function getLicenseInfo()
	{	
		$device_id =  Functions::getDeviceId();
        if( empty($device_id) )       
        {
			return 1;
        }
		
        $key = md5(config('app.key') . 'License');
        $encrypter = new \Illuminate\Encryption\Encrypter( $key, "AES-256-CBC" );
		
		$license_path = Functions::GetLicensePath();
		if( file_exists($license_path) == false )
		{
			return 2;
		}
        $ciphertext = file_get_contents($license_path);
		// dd("hey");
        $plaintext = $encrypter->decrypt( $ciphertext );
		$meta = json_decode($plaintext);

		// check device id and expire date
        if( $meta->device_id != $device_id )       
        {
            return 3;
		}
		
		return $meta;
	}

	static function getDeviceId()
	{
		$device_info_path = config_path() . "/device_info.lic";
		
		if (!file_exists($device_info_path)) 
		{
			$device_id =  Redis::get('device_id');  
			if( !empty($device_id))
				file_put_contents($device_info_path, $device_id);
		}
		else
		{
			$device_id = file_get_contents($device_info_path);
			if( empty($device_id) )
			{
				$device_id =  Redis::get('device_id');
				if( !empty($device_id))
				file_put_contents($device_info_path, $device_id);
			}
		}
		
		return $device_id;
	}

	public static function assignExtension(&$model) 
	{
		if( $model->status != 'Logout' )
		{
			$user = DB::table('common_users as cu')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->where('cu.id', $model->user_id)
					->select(DB::raw('cu.*, cd.property_id'))
					->first();

			if( !empty($user) )
			{
				$list = DB::table('call_center_extension as ce')
						->where('ce.property_id', $user->property_id)
						->whereRaw('NOT EXISTS (
						SELECT  NULL
						FROM    ivr_agent_status_log AS asl
						JOIN common_users as cu ON asl.user_id = cu.id
						JOIN common_department as cd ON cu.dept_id = cd.id
						WHERE ce.extension = asl.extension
						and asl.status != \'Log out\'
						and cd.property_id = ' . $user->property_id . '
						and asl.user_id != ' . $model->user_id . '
					)')
						->select(DB::raw('ce.extension'))
						->get();

				$extension_valid = false;
				foreach($list as $row)
				{
					if( $row->extension == $model->extension )
					{
						$extension_valid = true;
						break;
					}
				}

				if( $extension_valid == false )
				{
					$message = array();

					$message['type'] = 'extension_invalid';
					$message['user_id'] = $model->user_id;

					
						$message['data'] = $model->extension . ' extension is not valid';

					$message['extension'] = $model->extension;

					Redis::publish('notify', json_encode($message));
				}
			}
		}
	}

	static function saveAgentStatusHistory($agent) 
	{
		$prev_history = IVRAgentStatusHistory::orderBy('id', 'desc')
				->where('user_id', $agent->user_id)
				->first();

		if( !empty($prev_history) )
		{
			$prev_history->duration = strtotime($agent->created_at) - strtotime($prev_history->created_at);
			$prev_history->save();
		}

		$agent_history = new IVRAgentStatusHistory();
		$agent_history->user_id = $agent->user_id;
		$agent_history->status = $agent->status;
		$agent_history->extension = $agent->extension;
		$agent_history->ticket_id = $agent->ticket_id;
		$agent_history->created_at = $agent->created_at;
		$agent_history->duration = 0;

		$agent_history->save();
	}

	static function getSiteURL() 
	{
		$http = 'http://';
		if( isset($_SERVER['HTTPS'] ) )
		$http = 'https://';

		$port = $_SERVER['SERVER_PORT'];
		$siteurl = $http . $_SERVER['SERVER_NAME'] . ':' . $port . '/';

		return $siteurl;
	}

	static function CheckLicense()
	{		
		$meta = Functions::getLicenseInfo();

		if( is_numeric($meta) )
			return $meta;
			
        date_default_timezone_set(config('app.timezone'));
		$cur_day = date('Y-m-d');
		$deadline_day = date("Y-m-d", strtotime('30 days', strtotime($meta->end_day)));	
        
        // expire date
        if( $deadline_day < $cur_day )       
        {
			$ts1 = strtotime($deadline_day);
			$ts2 = strtotime($cur_day);

			$seconds_diff = $ts2 - $ts1;
			$day_diff = $seconds_diff / 3600 / 24 + 30;
            return $day_diff;
		}
		
		return $meta;
	}

	static function sendPushMessgeToDeviceWithRedisNodejs($user,$task_id = null, $title, $body, $payload) 
	{
		if( empty($user) )
			return;

		
		$device = Device::where('device_id','=',$user->device_id)->first();
		if(!empty($device))
		{
			$user->os = $device->device_os;
			if( empty($user->mobile) )			
				$user->mobile = $device->number;			
		}
		
		$user_info = DB::table('common_users as cu')
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->where('cu.id', $user->id)
			->select(DB::raw('cd.property_id'))
			->first();

		if( empty($user_info) )
			return;

		$rules = PropertySetting::getNotificationSetting($user_info->property_id);

		$payload['push_confirm_with_sms'] = $rules['push_confirm_with_sms'];
		$payload['push_confirm_duration'] = $rules['push_confirm_duration'];
		$user->app_pin = $rules['app_pin'];
		// $user->app_pin = '181920';
		
		$push_message = array();
		$push_message['type'] = 'push';
		$push_message['to'] = $user;
		$push_message['subject'] = $title;
		$push_message['body'] = $body;
		$push_message['payload'] = $payload;
		if( $user->sound_on == 1 )
			$push_message['sound'] = $user->sound_name;
		else
			$push_message['sound'] = '';

		// save notification for Mobile
		$notification = new SystemNotification();

		$notification->user_id = $user->id;
		if (!empty($task_id)) {
			$notification->ticket_id = $task_id;
		}

		$notification->type = config('constants.MOBILE_PUSH_NOTIFY');
		$notification->header = $payload['header'];
		$notification->property_id = $user_info->property_id;
		$notification->content = $body;
		$notification->notification_id = 0;
		if( !empty($payload) )
			$notification->payload = json_encode($payload);

		$notification->unread_flag = 1;

		$notification->save();

		// Functions::sendPushMessageToDevice('/topics/user_3', $title, $body, $payload);
		
		Redis::publish('notify', json_encode($push_message));
	}
}