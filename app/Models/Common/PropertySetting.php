<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Functions;
use Illuminate\Support\Facades\DB;

class PropertySetting extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'property_setting';
	public 		$timestamps = false;

	static function getPropertySettings($property_id, $rules) {
		foreach($rules as $key => $value)
		{
			$query = DB::table('property_setting as ps');
			if($property_id > 0)
				$query->where('ps.property_id', $property_id);
				
			$data = $query->where('ps.settings_key', $key)
					->select(DB::raw('ps.value'))
					->first();

			if( empty($data) )
				continue;

			$rules[$key] = $data->value;
		}

		return $rules;
	}

	static function savePropertySettings($property_id, $rules, $sub_domain) {
		foreach($rules as $key => $value)
		{
			$data = DB::table('property_setting')
					->where('property_id', $property_id)
					->where('settings_key', $key)
					->where('sub_domain', $sub_domain)
					->select(DB::raw('value'))
					->first();
			
			$input = array();

			$input['value'] = $value;			

			if( empty($data) )
			{
				$input['property_id'] = $property_id;			
				$input['settings_key'] = $key;			
				$input['sub_domain'] = $sub_domain;			

				DB::table('property_setting')
							->insert($input);
			}
			else
			{
				DB::table('property_setting')
					->where('property_id', $property_id)
					->where('settings_key', $key)
					->where('sub_domain', $sub_domain)
					->update($input);
			}
		}

		return $rules;
	}

	static function getGuestServiceSetting($property_id) {

		$rules = array();
		$rules['send_sms_to_guest'] = 'OFF';

		$rules = PropertySetting::getPropertySettings($property_id, $rules);

		return $rules;
	}

	static function getCentralServerSetting($property_id) {

		$rules = array();
		$rules['support_email'] = 'support@ennovatech.ae';
		$rules['central_flag'] = '1';	
		$rules['central_email'] = 'support@ennovatech.ae';	
		$rules['central_port'] = '8080';
		$rules['central_server'] = '192.168.1.91';
		$rules['central_server_request_time'] = '01:00';

		$rules = PropertySetting::getPropertySettings($property_id, $rules);

		return $rules;
	}


	static function getMailSetting($property_id, $prefix) {
		$smtp = array();

		$smtp['smtp_server'] = 'send.one.com';
		$smtp['smtp_port'] = '465';
		$smtp['smtp_user'] = 'reports@myhotlync.com';
		$smtp['smtp_password'] = 'Hotlync_2@16';
		$smtp['smtp_sender'] = 'jyyblue1987@outlook.com';
		$smtp['smtp_auth'] = '1';
		$smtp['smtp_tls'] = 'ssl';
	
		foreach($smtp as $key => $value)
		{
			$data = DB::table('property_setting as ps')
					->where('ps.property_id', $property_id)
					->where('ps.settings_key', $prefix . $key)
					->select(DB::raw('ps.value'))
					->first();

			if( empty($data) )
				continue;

			$smtp[$key] = $data->value;
		}

		return $smtp;
	}

	static function getSMSSetting($property_id, $prefix) {
		$smtp = array();

		$smtp['sms_host'] = 'http://api.infobip.com/sms/1/text/single';
		$smtp['sms_auth'] = 'dtdu:0B0m9nBf';
		$smtp['sms_from'] = 'Ennovatech';
		
		foreach($smtp as $key => $value)
		{
			$data = DB::table('property_setting as ps')
					->where('ps.property_id', $property_id)
					->where('ps.settings_key', $prefix . $key)
					->select(DB::raw('ps.value'))
					->first();

			if( empty($data) )
				continue;

			$smtp[$key] = $data->value;
		}

		return $smtp;
	}

	static function getServerConfig($property_id) {
		$rules = array();

		$rules['interface_host'] = 'http://192.168.1.253:3000/';
		$rules['hotlync_host'] = 'https://192.168.1.253/';
		$rules['live_host'] = 'http://192.168.1.253:8002/';
		$rules['public_live_host'] = 'http://192.168.1.253:8001/';
		$rules['public_domain'] = 'ngrok.io';
		$rules['export_server'] = 'http://192.168.1.253:8005/';
		$rules['public_url'] = 'http://192.168.1.253/';
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getPasswordSetting() {
		$rules = array();

		$rules['password_lock_attempts'] = 5;
		$rules['password_compare_flag'] = 1;
		
		foreach($rules as $key => $value)
		{
			$data = DB::table('property_setting as ps')
					->where('ps.settings_key', $key)
					->select(DB::raw('ps.value'))
					->first();

			if( empty($data) )
				continue;

			$rules[$key] = $data->value;
		}
		
		return $rules;
	}

	static function getAccountSetting($property_id) {
		$rules = array();

		$rules['login_session_timeout'] = 600;
		$rules['allow_multiple_login'] = 1;
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getJobRoles($property_id) {
		$rules = array();

		$rules['roomattendant_job_role'] = 0;
		$rules['supervisor_job_role'] = 0;
		$rules['callcenteragent_job_role'] = 0;
		$rules['dutymanager_job_role'] = 0;
		$rules['it_dept_id'] = 0;
		$rules['eng_dept_id'] = 0;
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getClassifyRuleSetting($property_id) {
		$rules = array();

		$rules['min_approval_duration'] = 20;
		$rules['min_approval_amount'] = 10;
		$rules['max_unmarked_count'] = 50;
		$rules['max_approver_notify'] = 50;
		$rules['max_close_notify'] = 10;
		$rules['pre_approved_call_types'] = "";

		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getWakeupSetting($property_id) {
		$rules = array();

		$rules['snooze_time'] = 10;
		$rules['duty_manager_notify'] = 'YES';
		$rules['duty_manager_device'] = 'mobile';
		$rules['duty_manager'] = 'jyyblue1987@outlook.com';
		$rules['awu_retry_attemps'] = 5;
		$rules['awu_retry_mins'] = 5;
		$rules['awu_snooze_mins'] = 10;
		$rules['awu_record_flag'] = 'ON';
		$rules['inprogress_max_wait'] = 120;
		$rules['max_wakeup_call'] = 4;
		$rules['max_wakeup_waiting_time'] = 60;

		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getComplaintSetting($property_id) {
		$rules = array();

		$rules['complaint_notify_mode'] = 'email';
		$rules['default_approval_route'] = '1';
		$rules['complaint_forward_flag'] = '1';
		$rules['complaint_same_guest_notify'] = 1;
		$rules['complaint_briefing_summary'] = 1;
		$rules['complaint_approval_job_roles'] = '1';
		$rules['main_complaint_location_mandatory'] = 1;
		$rules['main_complaint_maincategory_mandatory'] = 1;
		$rules['minimum_compensation'] = 1;
		$rules['close_comment'] = 1; 
		$rules['close_maincomplaint_without_subcomplaint'] = 1; 
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getMinibarSetting($property_id) {
		$rules = array();

		$rules['allow_minibar_post'] = 1;
		$rules['minibar_posting_type'] = 'total';
		$rules['disable_minibar_nopost'] = 'Y';
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getSystemTaskListIDs($property_id) {
		$list = DB::table('property_setting as ps')
			->where('ps.property_id', $property_id)
			->where('ps.settings_key', 'like', 'system_task%')
			->get();

		$ids = array();
		foreach($list as $row)
			$ids[] = $row->value;

		return $ids;		
	}

	static function getCleaningRoomSystemTaskType($property_id) {
		$data = DB::table('property_setting as ps')
					->where('ps.property_id', $property_id)
					->where('ps.settings_key', 'system_task_clean_room')
					->select(DB::raw('ps.value'))
					->first();

		if( empty($data) )
			return 0;

		return $data->value;
	}

	static function getCheckMinibarSystemTaskType($property_id) {
		$data = DB::table('property_setting as ps')
					->where('ps.property_id', $property_id)
					->where('ps.settings_key', 'system_task_check_minibar')
					->select(DB::raw('ps.value'))
					->first();

		if( empty($data) )
			return 0;

		return $data->value;
	}
		static function getGSDeviceSetting($property_id) {
		$data = DB::table('property_setting as ps')
					->where('ps.property_id', $property_id)
					->where('ps.settings_key', 'gs_device_based')
					->select(DB::raw('ps.value'))
					->first();

		if( empty($data) )
			return 0;

		return $data->value;
	}

	// static function getHskpSettingTime($property_id) {
	// 	$rules = array();

	// 	$rules['hskp_cleaning_time'] = ['09:00:00', '12:00:00'];
	// 	$rules['vacant_room_cleaning'] = ['00:00:00', '23:59:59'];
	// 	$rules['turn_down_service'] = ['05:00:00', '05:07:00'];
	// 	$rules['due_out_time'] = ['00:00:00', '14:00:00'];

	// 	foreach($rules as $key => $value)
	// 	{
	// 		$data = DB::table('property_setting as ps')
	// 				->where('ps.property_id', $property_id)
	// 				->where('ps.settings_key', $key)
	// 				->select(DB::raw('ps.value'))
	// 				->first();

	// 		if( empty($data) )
	// 			continue;

	// 		$rules[$key] = Functions::getTimeRange($data->value, $value);
	// 	}
		
	// 	return $rules;
	// }

	static function getHskpSettingValue($property_id) {
		$rules = array();

		$rules['due_out_clean'] = 1;
		$rules['max_clean_duration'] = 300;
		$rules['max_turndown_duration'] = 120;
		$rules['daily_auto_room_assign'] = 1;
		$rules['pax_allowance'] = 1;
		$rules['adult_pax_allowance'] = 5;
		$rules['child_pax_allowance'] = 5;
		$rules['hskp_gs_rush_task'] = 0;
		$rules['NA_vacant_dirty'] = 0;
		$rules['NA_inspected_finished'] = 0;
		$rules['NA_roster_clear'] = 0;
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getReportSetting($property_id) {
		$rules = array();

		$rules['schedule_report_subject'] = 'Hotlync Schedule Report';
		$rules['night_audit_guest_report_subject'] = 'Hotlync Night Audit Guest Report';
		$rules['night_audit_admin_report_subject'] = 'Hotlync Night Audit Admin Report';
		$rules['night_audit_report_subject'] = 'LRM Reports';
	
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getNightAuditSetting($property_id) {
		$rules = array();

		$rules['night_audit_file_type'] = 'PDF';
		$rules['night_audit_email_flag'] = 'NO';
		$rules['night_audit_include_mb'] = false;
		$rules['night_audit_recipients'] = '';
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getEmployeeSetting($property_id) {
		$rules = array();

		$rules['auto_sync_employee'] = true;
		$rules['auto_sync_employee_time'] = '01:00';
		$rules['employee_db_type'] = 'MS SQL';
		$rules['employee_db_address'] = '192.168.1.251';
		$rules['employee_db_username'] = 'sa';
		$rules['employee_db_password'] = '123456';
		$rules['employee_db_name'] = 'HEADS_Int';
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getShiftSetting($property_id) {
		$rules = array();

		$rules['dynamic_shift_for_mobile'] = 0;
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}
        static function getAppPin($property_id) {
		$rules = array();

		$rules['app_pin'] = '';

		$rules = PropertySetting::getPropertySettings($property_id, $rules);

		return $rules;
	}
	static function getNotificationSetting($property_id) {
		$rules = array();

		$rules['push_confirm_with_sms'] = 0;
		$rules['push_confirm_duration'] = 60;
		$rules['app_pin'] = '';
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function getMobileSetting($property_id) {
		$rules = array();

		$rules['mobile_app_url'] = Functions::getSiteURL() . 'mobile/hotlync.apk';
		$rules['mobile_app_version'] = '1.0.0';
		$rules['app_pin'] = 0;
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}


	static function getPromotionRejectSetting($property_id) {
		$rules = array();

		$rules['promotion_email_reject_content'] = '';
		$rules['promotion_sms_reject_content'] = '';
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}

	static function savePropertySetting($property_id, $values) {

		foreach($values as $key => $row) {
			$model = PropertySetting::where('property_id', $property_id)
				->where('settings_key', $key)
				->first();

			if( empty($model) )
			{
				$model = new PropertySetting();
				$model->property_id = $property_id;
				$model->settings_key = $key;							
			}	

			$model->value = $row;	
			$model->save();	
		}		
		
	}

	static function isGuestTaskQueued($property_id) {
		$rules = array();

		$rules['task_queued_flag'] = 0;
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules['task_queued_flag'];
	}

	static function isValidEmailDomain($property_id, $email) {
		$ret = array();

		$rules = array();

		$rules['only_allowed_domain_in_email_flag'] = 0;
		$rules['allowed_domain_in_email'] = 'hotlync.com,gmail.com';
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);

		if( $rules['only_allowed_domain_in_email_flag'] == 0 )
		{
			$ret['code'] = 0;
			return $ret;
		}


		if (empty($email) || filter_var($email, FILTER_VALIDATE_EMAIL)) {
		  	
		} else {
		  	$ret['code'] = 205;
			$ret['message'] = 'Invalid Email Address';
			return $ret;
		}
		list($user, $domain) = explode('@', $email);

		$domain_list = explode(',', $rules['allowed_domain_in_email']);

		if (in_array($domain, $domain_list))
		{
			$ret['code'] = 0;
			return $ret;
		}

		$ret['code'] = 205;
		$ret['message'] = "Please use allow email address only:\n" . $rules['allowed_domain_in_email'];
		
		return $ret;
	}

	static function getEngSetting($property_id) {
		$rules = array();

		$rules['eng_equip_mandatory'] = 1;
		$rules['eng_category_mandatory'] = 1;
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules);
		
		return $rules;
	}
}
