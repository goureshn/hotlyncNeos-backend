<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Response;
use Datatables;
use DateTime;
use DateTimeZone;
use DateInterval;
use Redis;
use App\Modules\Functions;
use Illuminate\Support\Facades\Config;
use App\Models\Service\DeftFunction;
use App\Models\Service\Device;
use App\Models\Common\CommonUser;
use App\Models\Common\CommonUserGroup;
use URL;
use App\Models\Service\AlarmMember;
use App\Models\Service\AlarmGroup;
use stdClass;
use App\Models\Common\PropertySetting;
use Ixudra\Curl\Facades\Curl;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

define("SUCCESS", 200);
define("FAIL", 101);

class AlarmController extends Controller
{
	public function getSettingGroup(Request $request)
	{
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();

		$datalist = DB::table('alarm_setting_groups')
			->orderBy($orderby, $sort)
			->select(DB::raw('*'))
			->skip($skip)->take($pageSize)
			->get();
		for ($i = 0; $i < count($datalist); $i++) {
			$group_id = $datalist[$i]->id;
			$user_count = DB::table('alarm_setting_groups_users')->where('group_id', $group_id)->count();			
			$alarm_list = DB::table('alarm_setting_groups_alarms as asga')
								->join('services_alarm_groups as sag', 'asga.alarm_id', '=', 'sag.id')
								->where('asga.group_id', $group_id)
								->select(DB::raw('sag.name'))
								->get();
			$datalist[$i]->user_count = $user_count;
			$datalist[$i]->alarm_list = $alarm_list;
			$datalist[$i]->alarm_count = count($alarm_list);
			$datalist[$i]->notify_type_list = explode(",", $datalist[$i]->default_notifi);
		}
		$totalcount = DB::table('alarm_setting_groups')->count();

		$ret['status'] = '200';
		$ret['datalist'] = $datalist;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function getSettingAlarm(Request $request)
	{
		$property = $request->get('property', 0); 
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$searchtext = $request->get('searchtext', '');

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();

		$querylist = DB::table('services_alarm_groups')
			->where('property',$property);
		if ($searchtext != '') {
			$querylist = $querylist->where('name', 'like', '%' . $searchtext . '%');
		}	
		$datalist = $querylist
			->orderBy($orderby, $sort)
			->select(DB::raw('*'))
			->skip($skip)->take($pageSize)
			->get();		
		$totalcount = $querylist->where('property',$property)->count();

		$ret['status'] = '200';
		$ret['datalist'] = $datalist;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function createSettingGroup(Request $request)
	{

		$input = $request->except('user_ids', 'alarm_ids', 'status', 'default_notifi');
		$user_ids = $request->get('user_ids');
		$alarm_ids = $request->get('alarm_ids');
		$property = $request->get('property');
		$input['max_duration'] = $request->get('max_duration', 10);
		$status = $request->get('status');		
		$status = implode(",", $status);
		$input['status'] = $status;
		$default_notifi = $request->get('default_notifi');
		$default_notifi = implode(",", $default_notifi);
		$input['default_notifi'] = $default_notifi;

		if ($input['id'] <= 0) {
			$group_id = DB::table('alarm_setting_groups')->insertGetId($input);
			$alarms = [];
			for ($i = 0; $i < count($alarm_ids); $i++) {
				$one = array();
				$one['group_id'] = $group_id;
				$one['alarm_id'] = $alarm_ids[$i];
				$one['property'] = $property;
				$alarms[] = $one;
			}
			DB::table('alarm_setting_groups_alarms')->insert($alarms);

			$users = [];
			for ($i = 0; $i < count($user_ids); $i++) {
				$one = array();
				$one['group_id'] = $group_id;
				$one['user_id'] = $user_ids[$i];
				$one['property'] = $property;
				$users[] = $one;
			}
			DB::table('alarm_setting_groups_users')->insert($users);
		} else {
			DB::table('alarm_setting_groups')
				->where('id', $input['id'])
				->update($input);
			$group_id = $input['id'];
			if (count($user_ids) > 0) {
				DB::table('alarm_setting_groups_users')
					->where('group_id', $group_id)
					->where('property', $property)
					->delete();
				for ($u = 0; $u < count($user_ids); $u++) {
					$group_id = $input['id'];
					$user_id = $user_ids[$u];

					$one = array();
					$one['group_id'] 	= $group_id;
					$one['user_id'] 	= $user_id;
					$one['property'] 	= $property;
					DB::table('alarm_setting_groups_users')->insert($one);
				}
			}

			if (count($alarm_ids) > 0) {
				$group_id = $input['id'];

				DB::table('alarm_setting_groups_alarms')
					->where('group_id', $group_id)
					->where('property', $property)
					->delete();

				for ($u = 0; $u < count($alarm_ids); $u++) {
					$group_id = $input['id'];
					$alarm_id = $alarm_ids[$u];

					$one = array();
					$one['group_id'] 	= $group_id;
					$one['alarm_id'] 	= $alarm_id;
					$one['property'] 	= $property;
					DB::table('alarm_setting_groups_alarms')->insert($one);
				}
			}
		}
		$ret = array();
		$ret['status'] = '200';

		return Response::json($ret);
	}

	public function createSettingAlarm(Request $request)
	{
		$input = $request->all();		
		if ($input['id'] <= 0) {
			$input['pref'] = '1';			
			DB::table('services_alarm_groups')->insert($input);
		} else {
			DB::table('services_alarm_groups')
				->where('id', $input['id'])
				->update($input);
		}
		$ret = array();
		$ret['status'] = '200';

		return Response::json($ret);
	}

	public function createAlarmSetting(Request $request) {
		$input = $request->except('alarm_ids', 'user_ids','trigger1_action_types', 'trigger2_action_types');
		$user_ids = $request->get('user_ids');
		$user_ids = implode(",", $user_ids);
		$input['trigger1_sg_users'] = $user_ids;
		$trigger1_action_types = $request->get('trigger1_action_types');
		$trigger1_action = implode(",", $trigger1_action_types);
		if(strlen($trigger1_action) > 0) {
			if($trigger1_action[0] == ',') $trigger1_action = substr($trigger1_action, 1);
		}
		$input['trigger1_action'] = $trigger1_action;
		$trigger2_action_types = $request->get('trigger2_action_types');
		$trigger2_action = implode(",", $trigger2_action_types);
		if(strlen($trigger2_action) > 0) {
			if($trigger2_action[0] == ',')  $trigger2_action = substr($trigger2_action, 1);
		}
		$input['trigger2_action'] = $trigger2_action;
		$status = '200';		
		if ($input['id'] <= 0) {
			$alarm_ids = $request->get('alarm_ids');
			for($i = 0 ; $i < count($alarm_ids) ; $i++ ) {
				$alarm_id = $alarm_ids[$i];
				//$alarm_id = $request->get('alarm_id');
				$alarm = DB::table('alarm_setting')->where('alarm_id', $alarm_id)->first();
				if($alarm) {	
					$status = '201';
				}else {
					$input['alarm_id'] = $alarm_id;					
					$alarm_setting = DB::table('alarm_setting')->insertGetId($input);						
				}
			}
		} else {
			$alarm_setting = DB::table('alarm_setting')
				->where('id', $input['id'])
				->update($input);
		}
		$ret = array();
		$ret['code'] = $status;
		return Response::json($ret);
	}

	public function createSettingDash(Request $request)
	{

		$input = $request->except('user_ids', 'alarm_ids', 'target_alarms');

		$user_ids = $request->get('user_ids');
		$alarm_ids = $request->get('alarm_ids');
		$property = $request->get('property');
		$target_alarms = $request->get('target_alarms');

		if ($input['id'] <= 0) {
			$dash_id = DB::table('alarm_setting_dashboard')->insertGetId($input);
			$alarms = [];
			$one = array();
				$one['dash_id'] = $dash_id;
				$one['alarm_id'] = 0;
				$one['alarm_backcolor'] = '';
				$one['property'] = $property;
				$one['index_number'] = 0;
				$one['target_alarms'] = $target_alarms;
				$alarms[] = $one;
			/*for ($i = 0; $i < count($alarm_ids); $i++) {
				$one = array();
				$one['dash_id'] = $dash_id;
				$one['alarm_id'] = $alarm_ids[$i]['id'];
				$one['alarm_backcolor'] = $alarm_ids[$i]['backcolor'];
				$one['property'] = $property;
				$one['index_number'] = $i;
				$alarms[] = $one;
			}*/
			DB::table('alarm_setting_dashboard_alarm')->insert($alarms);

			$groups = [];
			for ($i = 0; $i < count($user_ids); $i++) {
				$one = array();
				$one['dash_id'] = $dash_id;
				$one['user_id'] = $user_ids[$i];
				$one['property'] = $property;
				$groups[] = $one;
			}
			DB::table('alarm_setting_dashboard_user')->insert($groups);
		} else {
			DB::table('alarm_setting_dashboard')
				->where('id', $input['id'])
				->update($input);
			$dash_id = $input['id'];
			if (count($user_ids) > 0) {
				DB::table('alarm_setting_dashboard_user')
					->where('dash_id', $dash_id)
					->where('property', $property)
					->delete();
				for ($u = 0; $u < count($user_ids); $u++) {
					$dash_id = $input['id'];
					$user_id = $user_ids[$u];

					$one = array();
					$one['dash_id'] 	= $dash_id;
					$one['user_id'] 	= $user_id;
					$one['property'] 	= $property;
					DB::table('alarm_setting_dashboard_user')->insert($one);
				}
			}

			//if (count($alarm_ids) > 0) {
			if ($target_alarms != '') {	
				$dash_id = $input['id'];

				DB::table('alarm_setting_dashboard_alarm')
					->where('dash_id', $dash_id)
					->where('property', $property)
					->delete();
				$one = array();
				$one['dash_id'] = $dash_id;
				$one['alarm_id'] = 0;
				$one['alarm_backcolor'] = '';
				$one['property'] = $property;
				$one['index_number'] = 0;
				$one['target_alarms'] = $target_alarms;
				DB::table('alarm_setting_dashboard_alarm')->insert($one);	
				// for ($u = 0; $u < count($alarm_ids); $u++) {
				// 	$dash_id = $input['id'];
				// 	$alarm_id = $alarm_ids[$u]['id'];
				// 	$alarm_backcolor = $alarm_ids[$u]['backcolor'];

				// 	$one = array();
				// 	$one['dash_id'] 	= $dash_id;
				// 	$one['alarm_id'] 	= $alarm_id;
				// 	$one['alarm_backcolor'] 	= $alarm_backcolor;
				// 	$one['property'] 	= $property;
				// 	$one['index_number'] = $u;
				// 	DB::table('alarm_setting_dashboard_alarm')->insert($one);
				// }
			}
		}
		$ret = array();
		$ret['status'] = '200';

		return Response::json($ret);
	}

	public function deleteSettingGroup(Request $request)
	{
		$id = $request->get('id');
		$property = $request->get('property');

		DB::table('alarm_setting_groups')
			->where('id', $id)
			->delete();

		DB::table('alarm_setting_groups_alarms')
			->where('group_id', $id)
			->where('property', $property)
			->delete();

		DB::table('alarm_setting_groups_users')
			->where('group_id', $id)
			->where('property', $property)
			->delete();

		$ret = array();
		$ret['status'] = 200;

		return Response::json($ret);
	}

	public function deleteAlarmSetting(Request $request) {
		$id = $request->get('id');		
		DB::table('alarm_setting')
			->where('id', $id)
			->delete();

		$ret = array();
		$ret['status'] = 200;

		return Response::json($ret);
	}

	public function deleteSettingDash(Request $request)
	{
		$id = $request->get('id');
		$property = $request->get('property');

		DB::table('alarm_setting_dashboard')
			->where('id', $id)
			->delete();

		DB::table('alarm_setting_dashboard_alarm')
			->where('dash_id', $id)
			->where('property', $property)
			->delete();

		DB::table('alarm_setting_dashboard_user')
			->where('dash_id', $id)
			->where('property', $property)
			->delete();

		$ret = array();
		$ret['status'] = 200;

		return Response::json($ret);
	}

	public function getUsersAlarms(Request $request)
	{
		$group_id = $request->get('group_id');
		$users = DB::table('alarm_setting_groups_users')->where('group_id', $group_id)->pluck('user_id');
		$user_list = Db::table('common_users')
							->whereIn('id', $users)
							->select(DB::raw('*, CONCAT_WS(" ", first_name, last_name,"(",username,")") as wholename'))
							->get();

		$alarms = DB::table('alarm_setting_groups_alarms')->where('group_id', $group_id)->pluck('alarm_id');
		$alarm_list = DB::table('services_alarm_groups')->whereIn('id', $alarms)->get();

		$ret = array();
		$ret['users'] = $user_list;
		$ret['alarms'] = $alarm_list;
		return Response::json($ret);
	}

	public function getSameGroupUsers(Request $request)
	{
		$user_ids = explode(',',$request->get('user_ids'));		
		$user_list = DB::table('common_users')
							->whereIn('id', $user_ids)
							->select(DB::raw('*, CONCAT_WS(" ", first_name, last_name,"(",username,")") as wholename'))
							->get();

		$ret = array();
		$ret['users'] = $user_list;		
		return Response::json($ret);
	}

	public function getGroupsAlarms(Request $request)
	{
		$dash_id = $request->get('dash_id');
		//$groups = DB::table('alarm_setting_dashboard_group')->where('dash_id', $dash_id)->pluck('group_id');
		//$group_list = Db::table('alarm_setting_groups')->whereIn('id', $groups)->get();
		$users = DB::table('alarm_setting_dashboard_user')->where('dash_id', $dash_id)->pluck('user_id');
		$user_list = Db::table('common_users')
							->select(DB::raw('*, CONCAT_WS(" ", first_name, last_name,"(",username,")") as wholename'))
							->whereIn('id', $users)->get();


		$alarms = DB::table('alarm_setting_dashboard_alarm')->where('dash_id', $dash_id)->get();
		$target_alarms = '';
		$target_alarms_arr =array();
		$saved_alarms = array();
		if($alarms) {
			$target_alarms = $alarms[0]->target_alarms;
			$target_alarms_arr = json_decode($target_alarms);
			if($target_alarms_arr) {				
				foreach($target_alarms_arr as $key=>$value) {
					if(empty($value)) continue;					
					$one = new stdClass();				
					$one->dash_id = $dash_id;
					$one->alarm_id = $value[0]->id;
					$one->alarm_backcolor = $value[0]->backcolor;
					$one->property = $alarms[0]->property;
					$one->index_number = $alarms[0]->index_number;
					$saved_alarms[] = $one;
				}
			}
		}
		$alarms = $saved_alarms;		
		$alarm_list = [];
		for ($i = 0; $i < count($alarms); $i++) {
			$alarm_id = $alarms[$i]->alarm_id;
			$alarm_backcolor = $alarms[$i]->alarm_backcolor;
			$alarm = DB::table('services_alarm_groups')->where('id', $alarm_id)->first();
			if ($alarm) {
				$alarm->backcolor = $alarm_backcolor;
				$alarm_list[] = $alarm;
			}
		}
		$origin_alarm_list = [];
		$group_alarms = DB::table('services_alarm_groups')->groupBy('name')->get();
		for ($m = 0; $m < count($group_alarms); $m++) {
			$alarm_id = $group_alarms[$m]->id;
			$exist_flag = false;			
			for ($n = 0; $n < count($alarms); $n++) {
				if ($alarms[$n]->alarm_id == $alarm_id) {
					$exist_flag = true;
				}
			}
			if (!$exist_flag) {
				//unset($group_alarms[$i]);
				//array_splice($group_alarms, $m, 1);  			
				$origin_alarm_list[] = $group_alarms[$m];
			}
		}

		$ret = array();
		$ret['users'] = $user_list;
		$ret['alarms'] = $alarm_list;
		$ret['origin_alarms'] = $origin_alarm_list;
		$ret['target_alarms'] = $target_alarms;
		return Response::json($ret);
	}

	public function getUserGroup(Request $request)
	{
		$property_id = $request->get('property_id', '4');
		$user_id = $request->get('user_id', 0);

		if ($user_id == 0) {
			$model = Db::table('alarm_setting_groups')
				->where('property', $property_id)
				->groupBy('name')
				->get();
		} else {
			$model = Db::table('alarm_setting_groups as asg')
				->leftjoin('alarm_setting_groups_users as asu', 'asg.id', '=', 'asu.group_id')
				->where('asu.property', $property_id)
				->where('asu.user_id', $user_id)
				->groupBy('asu.group_id')
				->select(DB::raw('asg.*'))
				->get();
		}
		return Response::json($model);
	}

	public function getSettingDash(Request $request)
	{
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();

		$datalist = DB::table('alarm_setting_dashboard')
			->orderBy($orderby, $sort)
			->select(DB::raw('*'))
			->skip($skip)->take($pageSize)
			->get();
		
		for ($i = 0; $i < count($datalist); $i++) {
			$alarm_count = 0;
			$dash_id = $datalist[$i]->id;
			$user_count = DB::table('alarm_setting_dashboard_user')->where('dash_id', $dash_id)->count();
			$alarms = DB::table('alarm_setting_dashboard_alarm')->where('dash_id', $dash_id)->first();
			if($alarms) {
				$target_alarms = json_decode($alarms->target_alarms); 
				if(!empty($target_alarms)) {
					foreach($target_alarms as $key=>$val) {
						if(!empty($val[0])) $alarm_count++;
					} 
				}
			}
			$datalist[$i]->user_count = $user_count;
			$datalist[$i]->alarm_count = $alarm_count;
		}
		$totalcount = DB::table('alarm_setting_dashboard')->count();

		$ret['status'] = '200';
		$ret['datalist'] = $datalist;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function getUserDash(Request $request)
	{
		$user_id = $request->get('user_id');
		$property_id = $request->get('property_id');
		if ($user_id == 0) { //admin
			$dash_list = DB::table('alarm_setting_dashboard')->where('property', $property_id)->get();
		} else {
			$dash_list = DB::table('alarm_setting_dashboard as da')
				->leftjoin('alarm_setting_dashboard_user as au', 'au.dash_id', '=', 'da.id')
				->where('au.user_id', $user_id)
				->where('da.property', $property_id)
				->select(DB::raw('da.*'))
				->get();
		}
		$ret = array();
		$ret['status'] = '200';
		$ret['datalist'] = $dash_list;
		return Response::json($ret);
	}

	public function getDashAlarms(Request $request)
	{
		$user_id = $request->get('user_id');
		$property_id = $request->get('property_id');
		$searchtext = $request->get('searchtext','');

		$dash_id = $request->get('dash_id', 0);
		
		//updated module
		$alarm_list = array();
		$dash_alarms = DB::table('alarm_setting_dashboard_alarm')			
			->where('dash_id', $dash_id)
			->where('property', $property_id)
			->first();

		$target_alarms_arr = array();	
		if($dash_alarms) {
			$target_alarms_str = $dash_alarms->target_alarms;
			if(!empty($target_alarms_str)) {
				$target_alarms_arr = json_decode($target_alarms_str);
				if($target_alarms_arr) {				
					foreach($target_alarms_arr as $key=>$value) {
						$one = new stdClass();	
						if(empty($value)) {
							$one->alarm_id = 0;	
							$one->alarm_backcolor = '';
							$one->border =''; 	
						}else {	
							$again = DB::table('services_alarm_groups')->where('id', $value[0]->id )->first();							
							if(!empty($again)) {								
								$value[0]->name = $again->name;
								$value[0]->description = $again->description;
								$value[0]->fa_icon = $again->fa_icon;
								$value[0]->enable = $again->enable;
								$value[0]->icon = $again->icon;
								$value[0]->editable = $again->editable;
							}else {								
								$value[0]->name = "This alarm deleted";
								$value[0]->description = "This alarm deleted";
								$value[0]->enable = 0;
								$value[0]->editable = 0;								
							}							
							$value[0]->alarm_backcolor = $value[0]->backcolor; 				
							$alarm_id = $value[0]->id;
							$value[0]->alarm_id = $alarm_id;						
							$als_one = DB::table('alarm_setting')->where('alarm_id', $alarm_id)->first();
							if($als_one) {
								$value[0]->escalation = true;
								$value[0]->trigger1_action = $als_one->trigger1_action;
								$value[0]->percent = $als_one->percent;
								$value[0]->trigger1_time = $als_one->trigger1_time;
								$value[0]->trigger1_flag = $als_one->trigger1_flag;
								$value[0]->trigger1_loop = $als_one->trigger1_loop;	
								$value[0]->trigger1_duration = $als_one->trigger1_duration;	
								$value[0]->trigger2_action = $als_one->trigger2_action;	
								$value[0]->trigger2_time = $als_one->trigger2_time;	
								$value[0]->trigger2_flag = $als_one->trigger2_flag;	
							}else {
								$value[0]->escalation = false;
								$value[0]->trigger1_action = '';
								$value[0]->percent = '0';
								$value[0]->trigger1_time = '0';
								$value[0]->trigger1_flag = '0';
								$value[0]->trigger1_loop = '0';	
								$value[0]->trigger1_duration = '0';	
								$value[0]->trigger2_action = '';	
								$value[0]->trigger2_time = '0';	
								$value[0]->trigger2_flag = '0';	
							}
							$alarm_count = DB::table('alarm_notifications')
								->where('alarm_id', $alarm_id)
								->where('status', '!=', 3)
								->where('property_id', $property_id)
								->count();
							if($alarm_count) {
								$value[0]->alarm_count = $alarm_count;	
							}else {
								$value[0]->alarm_count = 0;	
							}
							$one = $value[0];
							$one->border ='1px solid #b8b7b7';
							if($one->enable == 0) {
								$one = new stdClass();	
								$one->alarm_id = 0;	
								$one->alarm_backcolor = '';
								$one->border =''; 
							}
						}	
						$target_alarms_arr->$key = $value;
						if($searchtext !='' && !empty($value[0])) {	
							if(strpos(strtolower($value[0]->name), strtolower($searchtext)) !== false){
								//echo "Word Found!";
							} else{
								$one = new stdClass();	
								$one->alarm_id = 0;	
								$one->alarm_backcolor = '';
								$one->border =''; 
							}
						}						
						$alarm_list[] = $one;
					}
				}
			}	
		}		
		
		$ret = array();
		$ret['status'] = '200';
		$ret['datalist'] = $alarm_list;
		return Response::json($ret);
	}

	public function getUserListOfAlarm(Request $request)
	{
		$alarm_id = $request->get('alarm_id', '1');

		$group_ids = DB::table('alarm_setting_groups_alarms')
			->where('alarm_id', $alarm_id)
			->pluck('group_id');

		$users = DB::table('common_users as cu')
			->leftjoin('alarm_setting_groups_users as agu', 'cu.id', '=', 'agu.user_id')
			->whereIn('agu.group_id', $group_ids)
			->groupBy('agu.user_id')
			->select(DB::raw('cu.*, CONCAT_WS(" ", first_name, last_name, "(",username,")") as wholename'))
			->get();

		return Response::json($users);
	}

	
	public function getNotificationType(Request $request)
	{
		$alarm_id = $request->get('alarm_id', '1');
		$user = $request->get('userlist');
		$recv_user = explode(',',$user);
		$notification_types = [];			
		for ($i = 0; $i < count($recv_user); $i++) {
			$recv_user_id = $recv_user[$i];
			$user = DB::table('common_users')->where('id', $recv_user_id)->first();
			$recv_user_name = "";
			if($user) $recv_user_name = $user->first_name." ".$user->last_name;
			$user_ips = DB::table('common_users_ip')->where('user_id', $recv_user_id)->first();
			if($user_ips) $user_ip = $user_ips->address_ip;
			else $user_ip = ''; 

			$group_ids = DB::table('alarm_setting_groups_users')
				->where('user_id', $recv_user_id)
				->pluck('group_id');

			$target_group_ids = DB::table('alarm_setting_groups_alarms')
				->whereIn('group_id', $group_ids)
				->where('alarm_id', $alarm_id)
				->pluck('group_id');

			if (empty($target_group_ids))  continue;
		
			$groups = DB::table('alarm_setting_groups')
				->whereIn('id', $target_group_ids)
				->get();
			for ($g = 0; $g < count($groups); $g++) {
				$type = $groups[$g]->default_notifi;
				$types = explode(',', $type);
				for ($t = 0; $t < count($types); $t++) {
					if (!in_array($types[$t], $notification_types)) {
						$notification_types[] = $types[$t];
					}
				}
			}
		}

		return Response::json($notification_types);
	}

	public function sendAlarm(Request $request)
	{		
		$input = $request->except('recv_users');
		$recv_user = $request->get('recv_users');
		
		$alarm_id 	= $input['alarm_id'];
		$send_user 	= $input['send_user'];		
		$location 	= $input['location'];
		$comment 	= $input['message'];
		$property_id = $input['property_id'];
		$description = $input['description'];
		$alarm_backcolor = $request->get('alarm_backcolor', 'green');
		$notification_type = $request->get('notification_type', '');
		$kind = isset($input['kind']) ? $input['kind'] : 0;

		$notification_origin_types = explode(",", $notification_type);
		
		$acknowledge = '';
		$alarm = DB::table('services_alarm_groups')
		->where('id', $input['alarm_id'])
		->first();
		
		$notification_id = 0;
		if(count($recv_user) > 0) {			
			$one = array();
			$one['alarm_id'] = $alarm_id;
			$one['property_id'] = $property_id;			
			$one['send_user'] =$send_user ;			
			$one['location'] = $location ;
			$one['message'] = $comment ;	
			$one['acknowledge'] = $acknowledge ;
			$one['status'] = 1 ;
			$one['updated_description'] = $description ;
			$one['kind'] = $kind;
			date_default_timezone_set(config('app.timezone'));
			$cur_time = date("Y-m-d H:i:s");
			$one['created_at'] = $cur_time;
			$notification_id = DB::table('alarm_notifications')->insertGetId($one);
		}

		for ($i = 0; $i < count($recv_user); $i++) {
			$recv_user_id = $recv_user[$i]['id'];
			$user = DB::table('common_users')->where('id', $recv_user_id)->first();
			$recv_user_name = "";
			if($user) $recv_user_name = $user->first_name." ".$user->last_name;
		
			$group_ids = DB::table('alarm_setting_groups_users')
				->where('user_id', $recv_user_id)
				->pluck('group_id');

			$target_group_ids = DB::table('alarm_setting_groups_alarms')
				->whereIn('group_id', $group_ids)
				->where('alarm_id', $alarm_id)
				->pluck('group_id');

			$notification_types = [];
			if (empty($target_group_ids))
			{
				$notification_types = $notification_origin_types;
			}
			else
			{
				$groups = DB::table('alarm_setting_groups')
					->whereIn('id', $target_group_ids)
					->get();
				for ($g = 0; $g < count($groups); $g++) {
					$type = $groups[$g]->default_notifi;
					$types = explode(',', $type);
					for ($t = 0; $t < count($types); $t++) {
						if (!in_array($types[$t], $notification_types)) {
							$notification_types[] = $types[$t];
						}
					}
				}
			}

			for ($n = 0; $n < count($notification_types); $n++) {
				$notifi_type = $notification_types[$n];
				$property  = DB::table("common_user_group as cug")
							->leftjoin('common_user_group_members as cgm', 'cug.id', '=', 'cgm.group_id')
							->where('cgm.user_id', $user->id)
							->select(DB::raw('cug.*'))
							->first();
				if($property) 			
					$property_id = $property->property_id;
				$notification = array();
				$notification['notification_id'] = $notification_id;
				$notification['alarm_id'] = $alarm->id;
				$notification['property_id'] = $property_id;
				$notification['notifi_type'] = $notifi_type;						
				$notification['send_user'] = $send_user ;
				$notification['recv_user'] = $recv_user_id ;
				$notification['location'] = $location ;
				$notification['message'] = $comment ;	
				//set acknowledge path api
				//$acknowledge = URL::to('/alarm/changeacknowledge?n='.$notification_id.'&a='.$alarm->id.'&p='.$property_id.'&r='.$recv_user_id.'&l='.$location);
				$acknowledge = URL::to('/alarm/changeacknowledge?n='.$notification_id.'&a='.$alarm->id.'&p='.$property_id.'&r='.$recv_user_id);				
				$notification['acknowledge'] = $acknowledge ;						
				date_default_timezone_set(config('app.timezone'));
				$cur_time = date("Y-m-d H:i:s");
				$date_val = date("d M Y");
				$time_val = date('H:i');
				$notification['created_at'] = $cur_time;
				$anu_id = DB::table('alarm_notifications_user')->insertGetId($notification);
				DB::table('alarm_notifications_user_log')->insert($notification);

				CommonUser::addNotifyCount($property_id, 'app.alarm.dashboard');
				$message = 'ALARM ACTIVATED'."\n";
				$message .= $date_val.' '.$time_val. "\n";
				
				$message .= 'ALARM:' . $alarm->name . "\n"; 
				$message .= $description . "\n" ;
				$message .= 'Location:' . $location."\n";
				if($comment != "") {
					$message .= 'CM:' . $comment  . "\n" ;
				}
				
				switch ($notifi_type) {
					case 'SMS'; //reference old logic in guest service
						$mobile_no = $user->mobile;
						//$mobile_no = '447418314201';
						if (strlen($mobile_no) < 12) {
							if (substr($mobile_no, 0, 1) === '0') {
								$mobile_no = substr($mobile_no, 1, strlen($mobile_no) - 1);
							}
							// Default country code : 971
							$mobile_no = "971" . $mobile_no;
						}
						// $acknowledge = 'https://dxbd1.myhotlync.com/a/ack?n='.$notification_id.'&a='.$alarm->id.'&p='.$property_id.'&r='.$recv_user_id.'&t=s';
						$acknowledge = Functions::getSiteURL() . '/al/' . $anu_id;
						// $message .= 'Acknowledge:<a href="'.$acknowledge.'">Click</a>';
						$message .= 'Acknowledge: '.$acknowledge;
						$this->sendSMS(0, $mobile_no, $message, null);
						break;

					case 'Mobile';//reference old logic in guest service
						$user->mobile = Device::getDeviceNumber($user->device_id);
						$task_id = 1;
						$payload = array();						
						$message .= 'Acknowledge:<a href="'.$acknowledge.'">Click</a>';
						$this->sendMobilePushMessage($alarm->name, $message, $user, $payload);
						break;

					case 'Email';
						// $acknowledge = 'http://dxbd1.myhotlync.com/a/ack?n='.$notification_id.'&a='.$alarm->id.'&p='.$property_id.'&r='.$recv_user_id.'&t=e';
						$acknowledge = Functions::getSiteURL() . '/al/' . $anu_id;
						$info = array();
						$info['status'] = "Active";
						$info['date_val'] = $date_val;
						$info['time_val'] = $time_val;
						$info['status_val'] = 'Alarm Activated';
						$info['alarm_name'] = $alarm->name;
						$info['alarm_description'] = $description;
						$info['location'] = $location;
						$info['comment'] = $comment;
						$info['acknowledge'] = $acknowledge;
						$info['recv_user_name'] = $recv_user_name;
						$message = view('emails.alarm_notification', ['info' => $info])->render();
				
						$smtp = Functions::getMailSetting(4, 'notification_');
						//$message .= 'Acknowledge:<a href="'.$acknowledge.'">Click</a>';
						$this->sendEmail($user->email, 'Hotlync', $message, $smtp, NULL, $alarm->name, 'Activated');
						break;

					case 'IVR';
						$sound_name = 'text_speech_'.$notification_id.'_'.$recv_user_id;
						$this->text_speech($sound_name, $alarm->name, $location , $comment ,$description);					
						$public_path = $this->current_path();
						//$sound_path = $public_path.'/sound/'.$sound_name.'.mp3';
						$sound_path = $public_path.'/sound/'.$sound_name.'.wav';
						$acknowledge = Functions::getSiteURL() . '/al/' . $anu_id;
						$one = array();
						$one['type'] = 'ivr';
						$one['id'] = $notification_id;				
						$one['mobile_no'] = $user->mobile;	
						$one['send_type'] = '0';			
						$one['ack_path']= $acknowledge;
						$one['sound_path'] = $sound_path;
						$one['sound_name'] = $sound_name;
						$one['send_status'] = 'Active';
						Redis::publish('notify', json_encode($one));
						break;

					case 'Webpush';												
						$one = array();
						$one['notification_id'] = $notification_id;
						$one['alarm_id'] = $alarm->id;
						$one['property_id'] = $property_id;
						$one['notifi_type'] = 'webpush';						
						$one['send_user'] = $send_user ;
						$one['recv_user'] = $recv_user_id ;
						$one['location'] = $location ;
						$one['message'] = $comment ;	
						$one['acknowledge'] = $acknowledge ;
						$one['send_type'] = '0';
						$one['send_status'] = 'Active';						
						date_default_timezone_set(config('app.timezone'));
						$cur_time = date("Y-m-d H:i:s");
						$one['created_at'] = $cur_time;
						
						$one['type'] = 'app.alarm.dashboard';
						$one['alarm_name'] = $alarm->name;
						$one['content'] = $message;

						$ret_m = array();
						$ret_m['type'] = 'webpush';
						$ret_m['to'] = $property_id;
						$ret_m['content'] = $one;
						Redis::publish('notify', json_encode($ret_m));

						break;
					case 'Desktop';						
						$one = array();		
						$one['type'] = 'alarm';						
						$one['alarm_name'] = $alarm->name;						
						$one['status_val'] = 'Alarm Activated';
						$one['message'] = $alarm->description;
						$one['location'] = $location;
						$one['comment'] = $comment;
						$one['acknowledge'] = $acknowledge;
						$one['send_type'] = '0'; // if 0, then send acknowledge
						$one['send_status'] = 'Active';
						$one['created_at'] = $cur_time;
						$one['alarm_backcolor'] = $alarm_backcolor;

						$this->sendDesktopNotification($user, $property_id, $one);
						
						break;
					case 'WhatsApp';
						$s_user = DB::table('common_users')->where('id', $send_user)->first();							
						$from = $s_user->mobile;							
						if (strlen($from) < 12) {
							if (substr($from, 0, 1) === '0') {
								$from = substr($from, 1, strlen($from) - 1);
							}
							// Default country code : 971
							$from = "971" . $from;
						}
						$mobile_no = $user->mobile;
						if (strlen($mobile_no) < 12) {
							if (substr($mobile_no, 0, 1) === '0') {
								$mobile_no = substr($mobile_no, 1, strlen($mobile_no) - 1);
							}
							// Default country code : 971
							$mobile_no = "971" . $mobile_no;
						}
						$message .= 'Acknowledge:<a href="'.$acknowledge.'">Click</a>';
						// $from = '15005550006';
						// //$mobile_no = '13233054157' ;
						// $mobile_no = '40728335629' ;
						$this->sendWhatsApp($from, $mobile_no, $message, null);	
						break;
				}
			}
		}


		$ret = array();
		if (empty($recv_user)) {
			$ret['code'] = FAIL;
			$ret['notifi_id'] = 0;
			$ret['message'] = 'There is no selected users.';
			return Response::json($ret);
		}
		$ret['code'] = SUCCESS;
		$ret['notifi_id'] = $notification_id;
		$ret['origin_types'] = $notification_origin_types;
		return Response::json($ret);
	}

	public function current_path() {
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $domain = $_SERVER['HTTP_HOST'];
        $public_path = "";
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $public_path = $protocol.$domain;
        } else {            
            $public_path = $protocol.$domain;
		}		
		return $public_path;
	}

	// public function email_test() {		
	// 	$info = array();
	// 	$info['status'] = "Active";
	// 	$info['date_val'] = "2019 10 11";
	// 	$info['status_val'] = "Alarm Activated";
	// 	$info['alarm_name'] = "Test Alarm Name";
	// 	$info['alarm_description'] = "This is alamr description";
	// 	$info['location'] = "First floor";
	// 	$info['comment'] = "This is comment";
	// 	$info['acknowledge'] = "this is acknowledge";
	// 	return view('emails.alarm_notification', compact('info'));
	// }

	public function sendEscalation(Request $request)
	{		
		$notifi_id = $request->get('notifi_id');
		$alarm_id = $request->get('alarm_id');
		$user_id = $request->get('user_id');
		$notifi_type = $request->get('notifi_type');
		$alarm_backcolor = $request->get('alarm_backcolor', 'green');

		$notification_origin_types = explode(",", $notifi_type);

		$alarm = DB::table('services_alarm_groups')
			->where('id', $alarm_id)
			->first();

		$notifi = DB::table('alarm_notifications')
					->where('id', $notifi_id)
					->first();

		if( $notifi->status == 3 ) // already cleared
		{
			$ret['code'] = FAIL;
			$ret['notifi_id'] = 0;
			$ret['message'] = 'This alarm is already cleared.';

			return Response::json($ret);			
		}
		
		$recv_user = DB::table('alarm_notifications_user')
						->where('notification_id',$notifi_id)
						->get();
		$alarm_setting= DB::table('alarm_setting')->where('alarm_id', $alarm_id)->first();
		$notification_types = [];
		if(!empty($alarm_setting)){
			if($alarm_setting->trigger1_flag == '1') {
				if($alarm_setting->trigger1_sg_flag == '0') {
					$user =  $alarm_setting->trigger1_sg_users;
					$users = explode(',',$user);
					$recv_user = array();
					for($i = 0 ;$i <count($users); $i++) {
						$one = new stdClass();
						$one->recv_user = $users[$i];
						$recv_user[] = $one;		
					}
				}
				if($alarm_setting->trigger1_unack_flag == '1') {
					$recv_user = DB::table('alarm_notifications_user')
					->where('notification_id',$notifi_id)
					->where('status','0')
					->get();
				}
				$type = $alarm_setting->trigger1_action;
				if($type != '') $notification_types =  explode(',',$type);
			}
			if($alarm_setting->trigger2_flag == '1') {
				$type = $alarm_setting->trigger2_action;
				if($type != '') $notification_types =  explode(',',$type);
			}
		}

		$send_user 	= $user_id;		
		$location 	= $notifi->location;
		$comment 	= $notifi->message;
		$property_id= $notifi->property_id;
		$acknowledge = '';
			
		$notification_id = 0;
		if(count($recv_user) > 0) {			
			$one = array();
			$one['alarm_id'] = $alarm_id;
			$one['property_id'] = $property_id;			
			$one['send_user'] = $send_user ;			
			$one['location'] = $location ;			
			$one['message'] = $comment ;	
			$one['acknowledge'] = $acknowledge ;
			$one['status'] = 1 ;
			$one['kind'] = 1 ; //esacaltion						
			date_default_timezone_set(config('app.timezone'));
			$cur_time = date("Y-m-d H:i:s");
			$one['created_at'] = $cur_time;
			// do not create notification again when escalation
			//It should update the current entry for that alarm and change its type to escalation.
			//$notification_id = DB::table('alarm_notifications')->insertGetId($one);
			$notification_id = $notifi_id; 
			DB::table('alarm_notifications')
					->where('id', $notifi_id)
					->update(['status' => 1, 'kind' => 1, 'send_user'=>$send_user,'created_at'=>$cur_time]);
		}
		
		$previous_user = 0;
		for ($i = 0; $i < count($recv_user); $i++) 
		{
			$recv_user_id = $recv_user[$i]->recv_user;
			$user = DB::table('common_users')->where('id', $recv_user_id)->first();
			$recv_user_name = "";
			if($user) $recv_user_name = $user->first_name." ".$user->last_name;
			
			$group_ids = DB::table('alarm_setting_groups_users')
				->where('user_id', $recv_user_id)
				->pluck('group_id');

			$target_group_ids = DB::table('alarm_setting_groups_alarms')
				->whereIn('group_id', $group_ids)
				->where('alarm_id', $alarm_id)
				->pluck('group_id');


			if (empty($target_group_ids))
				$notification_types = $notification_origin_types;
				
			$previous_type = '';
			for ($n = 0; $n < count($notification_types); $n++) 
			{
				$notifi_type = $notification_types[$n];
				$property  = DB::table("common_user_group as cug")
							->leftjoin('common_user_group_members as cgm', 'cug.id', '=', 'cgm.group_id')
							->where('cgm.user_id', $user->id)
							->select(DB::raw('cug.*'))
							->first();
				if($property) 			
					$property_id = $property->property_id;

				$notification = array();
				$notification['notification_id'] = $notification_id;
				$notification['alarm_id'] = $alarm->id;
				$notification['property_id'] = $property_id;
				$notification['notifi_type'] = $notifi_type;						
				$notification['send_user'] = $send_user ;
				$notification['recv_user'] = $recv_user_id ;
				$notification['location'] = $location ;
				$notification['message'] = $comment ;
				$notification['kind'] = 1 ; //esacaltion	
				//set acknowledge path api
				//$acknowledge = URL::to('/alarm/changeacknowledge?n='.$notification_id.'&a='.$alarm->id.'&p='.$property_id.'&r='.$recv_user_id.'&l='.$location);
				$acknowledge = URL::to('/alarm/changeacknowledge?n='.$notification_id.'&a='.$alarm->id.'&p='.$property_id.'&r='.$recv_user_id);
				$notification['acknowledge'] = $acknowledge ;						
				date_default_timezone_set(config('app.timezone'));
				$cur_time = date("Y-m-d H:i:s");
				$date_val = date("d M Y");
				$time_val = date("H:i");
				$notification['created_at'] = $cur_time;
				// do not create notification again when escalation
				//It should update the current entry for that alarm and change its type to escalation.
				//DB::table('alarm_notifications_user')->insert($notification);
				
				DB::table('alarm_notifications_user')
					->where('notification_id', $notification_id)
					->where('recv_user', $recv_user_id)	
					->where('notifi_type', $notifi_type)				
					->update(['kind' => 1, 'created_at'=>$cur_time]);


				DB::table('alarm_notifications_user_log')->insertGetId($notification);					
				
				$anu_model = DB::table('alarm_notifications_user')
					->where('alarm_id', $alarm->id)
					->where('recv_user', $recv_user_id)
					->where('notifi_type', $notifi_type)									
					->where('property_id' , $property_id)
					->where('notification_id', $notification_id)
					->first();
				$anu_id	= 0;
				if( !empty($anu_model) )	
					$anu_id	= $anu_model->id;

				CommonUser::addNotifyCount($property_id, 'app.alarm.dashboard');
				$message = 'ALARM REMINDER'."\n";
				//$message = 'Active:'."\n";
				$message .= $date_val.' '.$time_val. "\n";
				$message .= 'Alarm Activated'."\n";
				$message .= 'ALARM:' . $alarm->name ."\n" ;
				$message .= $alarm->description ."\n" ;
				$message .= ' Location:' . $location."\n";
				if($comment != "") {
					$message .= ' CM:' . $comment  ."\n";
				}
				if($previous_user != $recv_user_id)
				{
					if($previous_type != $notifi_type)
					{
						switch ($notifi_type) 
						{
							case 'SMS'; //reference old logic in guest service
								$mobile_no = $user->mobile;
								if (strlen($mobile_no) < 12) {
									if (substr($mobile_no, 0, 1) === '0') {
										$mobile_no = substr($mobile_no, 1, strlen($mobile_no) - 1);
									}
									// Default country code : 971
									$mobile_no = "971" . $mobile_no;
								}
								// $acknowledge = 'https://dxbd1.myhotlync.com/a/ack?n='.$notification_id.'&a='.$alarm->id.'&p='.$property_id.'&r='.$recv_user_id.'&t=s';
								$acknowledge = Functions::getSiteURL() . '/al/' . $anu_id;
								// $message .= 'Acknowledge:<a href="'.$acknowledge.'">Click</a>';
								$message .= 'Acknowledge: '.$acknowledge;
								$this->sendSMS(0, $mobile_no, $message, null);
								break;

							case 'Mobile';//reference old logic in guest service
								$user->mobile = Device::getDeviceNumber($user->device_id);						
								$task_id = 1;
								$payload = array();							
								$message .= 'Acknowledge:<a href="'.$acknowledge.'">Click</a>';
								$this->sendMobilePushMessage($alarm->name, $message, $user, $payload);
								break;

							case 'Email';
								// $acknowledge = 'https://dxbd1.myhotlync.com/a/ack?n='.$notification_id.'&a='.$alarm->id.'&p='.$property_id.'&r='.$recv_user_id.'&t=e';
								$acknowledge = Functions::getSiteURL() . '/al/' . $anu_id;
								$info = array();
								$info['status'] = "Active";
								$info['date_val'] = $date_val;
								$info['status_val'] = 'Alarm Activated';
								$info['alarm_name'] = $alarm->name;
								$info['alarm_description'] = $alarm->description;
								$info['location'] = $location;
								$info['comment'] = $comment;
								$info['acknowledge'] = $acknowledge;
								$info['recv_user_name'] = $recv_user_name;
								$message = view('emails.alarm_notification', ['info' => $info])->render();
					
								$smtp = Functions::getMailSetting(4, 'notification_');
								//$message .= 'Acknowledge:<a href="'.$acknowledge.'">Click</a>';
								$this->sendEmail($user->email, 'Hotlync', $message, $smtp, NULL, $alarm->name, 'Activated');
								break;

							case 'IVR';							
								$sound_name = 'text_speech_'.$notification_id.'_'.$recv_user_id;							
								$this->text_speech($sound_name, $alarm->name, $location, $comment , $alarm->description);									
								$public_path = $this->current_path();
								//$sound_path = $public_path.'/sound/'.$sound_name.'.mp3';
								$acknowledge = Functions::getSiteURL() . '/al/' . $anu_id;
								$sound_path = $public_path.'/sound/'.$sound_name.'.wav';
								$one = array();
								$one['type'] = 'ivr';
								$one['id'] = $notification_id;		
								$one['mobile_no'] = $user->mobile;
								$one['send_type'] = '0';				
								$one['ack_path']= $acknowledge;
								$one['sound_path'] = $sound_path;
								$one['sound_name'] = $sound_name;
								$one['send_status'] = 'Active';		
								Redis::publish('notify', json_encode($one));
								break;

							case 'Webpush';												
								$one = array();
								$one['notification_id'] = $notification_id;
								$one['alarm_id'] = $alarm->id;
								$one['property_id'] = $property_id;
								$one['notifi_type'] = 'webpush';						
								$one['send_user'] = $send_user ;
								$one['recv_user'] = $recv_user_id ;
								$one['location'] = $location ;
								$one['message'] = $comment ;	
								$one['acknowledge'] = $acknowledge ;
								$one['send_type'] = '0';	
								$one['send_status'] = 'Active';						
								date_default_timezone_set(config('app.timezone'));
								$cur_time = date("Y-m-d H:i:s");
								$one['created_at'] = $cur_time;
								
								$one['type'] = 'app.alarm.dashboard';
								$one['alarm_name'] = $alarm->name;
								$one['content'] = $message;

								$ret_m = array();
								$ret_m['type'] = 'webpush';
								$ret_m['to'] = $property_id;
								$ret_m['content'] = $one;
								Redis::publish('notify', json_encode($ret_m));

								break;
							case 'Desktop';							
								$one = array();		
								$one['type'] = 'alarm';						
								$one['alarm_name'] = $alarm->name;						
								$one['status_val'] = 'Alarm Activated';
								$one['message'] = $alarm->description;
								$one['location'] = $location;
								$one['comment'] = $comment;
								$one['acknowledge'] = $acknowledge;
								$one['send_type'] = '0'; // if 0, then send acknowledge
								$one['send_status'] = 'Active';
								$one['created_at'] = $cur_time;
								$one['alarm_backcolor'] = $alarm_backcolor;
			
								$this->sendDesktopNotification($user, $property_id, $one);
																
								break;
							case 'WhatsApp';
								$s_user = DB::table('common_users')->where('id', $send_user)->first();							
								$from = $s_user->mobile;							
								if (strlen($from) < 12) {
									if (substr($from, 0, 1) === '0') {
										$from = substr($from, 1, strlen($from) - 1);
									}
									// Default country code : 971
									$from = "971" . $from;
								}
								$mobile_no = $user->mobile;
								if (strlen($mobile_no) < 12) {
									if (substr($mobile_no, 0, 1) === '0') {
										$mobile_no = substr($mobile_no, 1, strlen($mobile_no) - 1);
									}
									// Default country code : 971
									$mobile_no = "971" . $mobile_no;
								}
								$message .= 'Acknowledge:<a href="'.$acknowledge.'">Click</a>';
								$this->sendWhatsApp($from, $mobile_no, $message, null);							
								break;
						}
					}
				}
				$previous_type = $notifi_type;
			}

			$previous_user = $recv_user_id;
		}

		$ret = array();
		if (empty($recv_user)) {
			$ret['code'] = FAIL;
			$ret['notifi_id'] = 0;
			$ret['message'] = 'There is no selected users.';
			return Response::json($ret);
		}
		$ret['code'] = SUCCESS;
		$ret['notifi_id'] = $notification_id;
		$ret['notification_types'] = $notification_types;
		$ret['alarm_setting'] = $alarm_setting;
		$ret['recv_user'] = $recv_user;
		$ret['alarm_id'] = $alarm_id;
		return Response::json($ret);
	}

	public function sendSMS($task_id, $number, $content, $payload)
	{
		$message = array();
		$message['type'] = 'sms';

		$message['to'] = $number;

		$message['subject'] = 'Hotlync Notification';
		$message['content'] = $content;
		$message['payload'] = $payload;

		Redis::publish('notify', json_encode($message));
	}

	public function sendWhatsApp($from, $to, $content, $payload)
	{
		$message = array();
		$message['type'] = 'whatsapp';
		$message['from'] = $from;
		$message['to'] = $to;

		$message['subject'] = 'Hotlync Notification';
		$message['content'] = $content;
		$message['payload'] = $payload;

		Redis::publish('notify', json_encode($message));
	}

	public function sendMobilePushMessage($title, $message, $user, $payload)
	{
		$payload['header'] = 'Alarms';
		$result = Functions::sendPushMessgeToDeviceWithRedisNodejs(
			$user,
			0,
			$title,
			$message,
			$payload
		);
	}

	public function sendEmail($email, $title, $content, $smtp, $payload, $alarm_name, $status)
	{
		$subject =' Hotlync Alarms - ['.$alarm_name.'] - '.$status;
		$message = array();
		$message['type'] = 'email';
		$message['to'] = $email;
		$message['subject'] = $subject;
		$message['title'] = $title;
		$message['content'] = $content;
		$message['smtp'] = $smtp;
		$message['payload'] = $payload;
		Redis::publish('notify', json_encode($message));
	}

	public function getAlarmNotifiList(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'created_at');
		$sort = $request->get('sort', 'desc');
		//echo $orderby."====".$sort; return;
        $property_id = $request->get('property_id', '0');

        $searchoption = $request->get('searchoption','');
        $start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$cond = $request->get('cond', 'active');
		$created_ids = $request->get('created_ids', []);
		$status_name = $request->get('status_name', '');
		$alarm_ids = $request->get('alarm_ids', []);
		$dept_id = $request->get('dept_id', 0);
		$dept_flag = $request->get('dept_flag', 'false');

    	$user_id = $request->get('user_id', 0);

        if ($pageSize < 0)
            $pageSize = 20;

        $ret = array();
		$query = DB::table('alarm_notifications as an')
			->leftjoin('services_alarm_groups as sag','an.alarm_id','=','sag.id')
			->leftjoin('common_users as cu','an.send_user', '=', 'cu.id')
			->leftjoin('common_users as cu1','an.clear_user', '=', 'cu1.id')
			->where('an.property_id', $property_id);

		if( count($created_ids) > 0 )
			$query->whereIn('an.send_user', $created_ids);
		if( !empty($alarm_ids) )
			$query->whereIn('an.alarm_id', $alarm_ids);

		if ($dept_flag == false){
			$query->where('cu.dept_id', $dept_id);
		}

		if( !empty($status_name) && $status_name != 'All' )
		{
			switch($status_name)
			{
				case 'Active':
					$status = 1;
					break;
				case 'Update':
					$status = 2;
					break;
				case 'Clear':
					$status = 3;
					break;		
			}
			$query->where('an.status', $status);
		}
			
		if($cond == 'active') 			
			$query = $query->where(function ($query1)  {
								$query1->where('an.status', 1) //active
									->orwhere('an.status', 2); //check
								});

		$query->whereRaw(sprintf("DATE(an.created_at) >= '%s' ", $start_date));
		$query->whereRaw(sprintf("DATE(an.created_at) <= '%s' ", $end_date));

        if( $searchoption != '' )
        {
            $where = sprintf(" (sag.name like '%%%s%%' or								
								an.message like '%%%s%%' or
								an.location like '%%%s%%' 
								)",
                $searchoption, $searchoption, $searchoption 
            );
            $query->whereRaw($where);
        }

        $data_query = clone $query;
		$data_list = $data_query
			//->groupBy('an.alarm_id','an.send_user','an.location')
            ->orderBy($orderby, $sort)
			->select(DB::raw('an.* , sag.name as alarm_name, 
								CONCAT_WS(" ", cu.first_name, cu.last_name) as sender_user_name,
								CONCAT_WS(" ", cu1.first_name, cu1.last_name) as clear_user_name
								'))
            ->skip($skip)->take($pageSize)
			->get();
			
		for($i=0 ; $i < count($data_list) ; $i++) {
			$notification_id = $data_list[$i]->id;
			$alarm_id = $data_list[$i]->alarm_id;
			if($user_id == 0) { // system admin
			    $permissions = [1,2,3]; // acknowledge=1, check/update=2, clear =4
				$data_list[$i]->permission = $permissions;
			}else {
			//get permission to change status
			$get_user_groups = DB::table("alarm_setting_groups_users")
									->where('user_id', $user_id)
									->pluck('group_id');
			$permission = DB::table('alarm_setting_groups as asg')
				->leftjoin('alarm_setting_groups_alarms as asga', 'asg.id','=','asga.group_id')
				->where('asga.alarm_id', $alarm_id)
				->whereIn('asg.id', $get_user_groups)
				->first();
			if($permission) $permissions = explode(',', $permission->status);
			else $permissions = [];
			$data_list[$i]->permission = $permissions;
			}
			//get acknowledge count
			$notify_count = DB::table('alarm_notifications_user')								
								->where('notification_id', $notification_id)								
								->groupBy('recv_user')
								->get();								
			$data_list[$i]->notify_count = count($notify_count);

			$ack_count = DB::table('alarm_notifications_user')								
								->where('notification_id', $notification_id)
								->where('status','>', 0 )
								->groupBy('recv_user')
								->get();								
			$data_list[$i]->ack_count = count($ack_count);
			$sender_id = $data_list[$i]->send_user;
			if($sender_id == 0) {
                $data_list[$i]->sender_user_name = 'Super admin';
            }
		}

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
	}

	
	public function getAlarmUpdateList(Request $request)
    {
    	$notification_id = $request->get('notification_id');
	
		$ret = array();

		// get notification type list
		
		$update_list = DB::table('alarm_notifications_user as anu')
							->leftJoin('common_users as cu', 'anu.recv_user', '=', 'cu.id')
							->leftJoin('common_users as cu1', 'anu.send_user', '=', 'cu1.id')
							->where('anu.notification_id', $notification_id)							
							->where('anu.send_type', 1)
							->groupBy('anu.created_at')
							->orderBy('anu.created_at', 'asc')
							->select(DB::raw('GROUP_CONCAT(anu.recv_user) as update_user_ids, 
											GROUP_CONCAT(anu.notifi_type) as notify_groups,
											CONCAT_WS(" ", cu1.first_name, cu1.last_name) as sender_user_name, 
											anu.created_at, anu.send_flag,
											anu.message' ))										
							->get();

	
		foreach( $update_list as $row)
		{
			if( empty($row->update_user_ids ) )
				$row->update_user_list = '';
			else
			{
				$ids = explode(",", $row->update_user_ids);

				$ids = array_unique($ids);
				$userlist = DB::table('common_users as cu')
					->whereIn("cu.id", $ids)
					->select(DB::raw('GROUP_CONCAT(CONCAT_WS(" ", cu.first_name, cu.last_name)) as user_name_list'))
					->first();

				$row->update_user_list = $userlist->user_name_list;
			}

			if ($row->send_flag == '1')
				$row->send_flag = 'Yes';
			else 
				$row->send_flag = 'No';

		}


		$ret['status'] = '200';
		$ret['datalist'] = $update_list;			

		return Response::json($ret);
	}

	private function getAlarmDetailData(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$id = $request->get('id', 0);
        

		

		$user_id = $request->get('user_id', 0);

		$ret = array();
		
		$query = DB::table('alarm_notifications as an')
			->leftjoin('services_alarm_groups as sag','an.alarm_id','=','sag.id')
			->leftjoin('common_users as cu','an.send_user', '=', 'cu.id')
			->leftjoin('common_users as cu1','an.clear_user', '=', 'cu1.id')
			->where('an.id', $id)
			->where('an.property_id', $property_id);

		

	

        $alarm_log_list = $query
			->select(DB::raw('an.*, sag.name as alarm_name, 
							CONCAT_WS(" ", cu.first_name, cu.last_name) as sender_user_name,
							CONCAT_WS(" ", cu1.first_name, cu1.last_name) as clear_user_name
						'))
			->get();
			
		foreach($alarm_log_list as $notification)
		{
			$notification_id = $notification->id;

			$escalation = DB::table('alarm_setting')
				->where('alarm_id', $notification->alarm_id)
				->first();

			if( empty($escalation) )
				$notification->retry_count = 0;		
			else				
				$notification->retry_count = $escalation->trigger1_loop;		


			$notify_count = DB::table('alarm_notifications_user')								
								->where('notification_id', $notification_id)								
								->groupBy('recv_user')
								->get();								
			$notification->notify_count = count($notify_count);

			$ack_count = DB::table('alarm_notifications_user')								
								->where('notification_id', $notification_id)
								->where('status','>', 0 )
								->groupBy('recv_user')
								->get();								
			$notification->ack_count = count($ack_count);

			$user_list = DB::table('alarm_notifications_user as anu')
							->leftJoin('common_users as cu', 'anu.recv_user', '=', 'cu.id')
							->where('anu.notification_id', $notification_id)							
							->where('anu.send_type', 0)							
							->groupBy('anu.recv_user')
							->orderBy('cu.first_name', 'desc')
							->select(DB::raw('anu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as user_name' ))										
							->get();

			$ack_count = 0;				

			foreach( $user_list as $row)
			{
				$item = DB::table('alarm_notifications_user as anu')
					->where('anu.notification_id', $notification_id)
					->where('anu.recv_user', $row->recv_user)				
					->select(DB::raw('max(anu.status) as max_status'))
					->first();

				$notify_type_list = DB::table('alarm_notifications_user_log')
					->where('notification_id', $notification_id)
					->where('recv_user', $row->recv_user)
					->where('kind', 1)
					->pluck('notifi_type');

				$row->escal_count = 0;
				if( count($notify_type_list) > 0 )
				{
					$count = DB::table('alarm_notifications_user_log')
						->where('notification_id', $notification_id)
						->where('recv_user', $row->recv_user)
						->where('kind', 1)
						->count();

					$row->escal_count = $count / count($notify_type_list);
				}	

				$row->max_status = $item->max_status;

				switch( $item->max_status )
				{
					case 0:
						$row->status_name = 'Active';
						break;
					case 1:
						$row->status_name = 'Acknowledged';
						break;
					case 2:
						$row->status_name = 'Clear';
						break;
					default:
						$row->status_name = 'Unknown';						
						break;
				}

				// get notify type list
				$row->notify_type_list = DB::table('alarm_notifications_user as anu')
					->where('anu.notification_id', $notification_id)
					->where('anu.recv_user', $row->recv_user)	
					->groupBy('anu.notifi_type')										
					->pluck('anu.notifi_type');

				// get ack type list
				$row->ack_type_list = DB::table('alarm_notifications_user as anu')
					->where('anu.notification_id', $notification_id)
					->where('anu.recv_user', $row->recv_user)		
					->where('anu.status', 1)						
					->groupBy('anu.notifi_type')										
					->pluck('anu.notifi_type');


				$ack_data = DB::table('alarm_notifications_user as anu')
					->where('anu.notification_id', $notification_id)
					->where('anu.recv_user', $row->recv_user)		
					->where('anu.status', 1)						
					->select(DB::raw('min(anu.acknowledge_date) as ack_date'))
					->first();
				if( !empty($ack_data) && !empty($ack_data->ack_date))
					$row->ack_date = $ack_data->ack_date;					
				else
					$row->ack_date = '';					
			}

			$notification->user_list = $user_list;

			// update list

			$update_list = DB::table('alarm_notifications_user as anu')
							->leftJoin('common_users as cu', 'anu.recv_user', '=', 'cu.id')
							->leftJoin('common_users as cu1', 'anu.send_user', '=', 'cu1.id')
							->where('anu.notification_id', $notification_id)							
							->where('anu.send_type', 1)							
							->groupBy('anu.created_at')
							->orderBy('anu.created_at', 'asc')
							->select(DB::raw('GROUP_CONCAT(anu.recv_user) as update_user_ids, 
										GROUP_CONCAT(anu.notifi_type) as notify_groups,
										CONCAT_WS(" ", cu1.first_name, cu1.last_name) as sender_user_name, 
										anu.created_at, anu.send_flag,
										anu.message' ))										
							->get();

			foreach($update_list as $row)
			{
				if( empty($row->update_user_ids ) )
					$row->update_user_list = '';
				else
				{
					$ids = explode(",", $row->update_user_ids);

					$ids = array_unique($ids);
					$userlist = DB::table('common_users as cu')
						->whereIn("cu.id", $ids)
						->select(DB::raw('GROUP_CONCAT(CONCAT_WS(" ", cu.first_name, cu.last_name)) as user_name_list'))
						->first();

					$row->update_user_list = $userlist->user_name_list;
				}

				if( empty($row->notify_groups ) )
					$row->notify_groups = '';
				else
				{
					$notify_groups = explode(",", $row->notify_groups);
					$notify_groups = array_unique($notify_groups);
					$row->notify_groups = implode(",", $notify_groups);
				}

				if ($row->send_flag == '1')
					$row->send_flag = 'Yes';
				else 
					$row->send_flag = 'No';
			}				

			$notification->update_list = $update_list;
		}	

		$data = array();
		$data['log_list'] = $alarm_log_list;

		return $data;
	}
	
	private function getAlarmLogListForDetailData(Request $request)
	{
		$property_id = $request->get('property_id', '0');

        $start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$created_ids = $request->get('created_ids', []);
		$status_name = $request->get('status_name', '');
		$alarm_ids = $request->get('alarm_ids', []);

		if( empty($created_ids) )
			$created_ids = [];
		else
			$created_ids = explode(",", $created_ids);

		if( empty($alarm_ids) )
			$alarm_ids = [];
		else
			$alarm_ids = explode(",", $alarm_ids);

		$user_id = $request->get('user_id', 0);
		$dept_id = $request->get('dept_id', 0);
		$dept_flag = $request->get('dept_flag', 'false');

		$ret = array();
		
		$query = DB::table('alarm_notifications as an')
			->leftjoin('services_alarm_groups as sag','an.alarm_id','=','sag.id')
			->leftjoin('common_users as cu','an.send_user', '=', 'cu.id')
			->leftjoin('common_users as cu1','an.clear_user', '=', 'cu1.id')
			->where('an.property_id', $property_id);

		if( count($created_ids) > 0 )
			$query->whereIn('an.send_user', $created_ids);
		if( count($alarm_ids) > 0 )
			$query->whereIn('an.alarm_id', $alarm_ids);

		if ($dept_flag == 'false'){
				$query->where('cu.dept_id', $dept_id);
			}

		if( !empty($status_name) && $status_name != 'All' )
		{
			switch($status_name)
			{
				case 'Active':
					$status = 1;
					break;
				case 'Update':
					$status = 2;
					break;
				case 'Clear':
					$status = 3;
					break;		
			}
			$query->where('an.status', $status);
		}
		
		$query->whereRaw(sprintf("DATE(an.created_at) >= '%s' ", $start_date));
		$query->whereRaw(sprintf("DATE(an.created_at) <= '%s' ", $end_date));

        $alarm_log_list = $query
			->select(DB::raw('an.*, sag.name as alarm_name, 
							CONCAT_WS(" ", cu.first_name, cu.last_name) as sender_user_name,
							CONCAT_WS(" ", cu1.first_name, cu1.last_name) as clear_user_name
						'))
			->get();
			
		foreach($alarm_log_list as $notification)
		{
			$notification_id = $notification->id;

			$escalation = DB::table('alarm_setting')
				->where('alarm_id', $notification->alarm_id)
				->first();

			if( empty($escalation) )
				$notification->retry_count = 0;		
			else				
				$notification->retry_count = $escalation->trigger1_loop;		


			$notify_count = DB::table('alarm_notifications_user')								
								->where('notification_id', $notification_id)								
								->groupBy('recv_user')
								->get();								
			$notification->notify_count = count($notify_count);

			$ack_count = DB::table('alarm_notifications_user')								
								->where('notification_id', $notification_id)
								->where('status','>', 0 )
								->groupBy('recv_user')
								->get();								
			$notification->ack_count = count($ack_count);

			$user_list = DB::table('alarm_notifications_user as anu')
							->leftJoin('common_users as cu', 'anu.recv_user', '=', 'cu.id')
							->where('anu.notification_id', $notification_id)							
							->where('anu.send_type', 0)							
							->groupBy('anu.recv_user')
							->orderBy('cu.first_name', 'desc')
							->select(DB::raw('anu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as user_name' ))										
							->get();

			$ack_count = 0;				

			foreach( $user_list as $row)
			{
				$item = DB::table('alarm_notifications_user as anu')
					->where('anu.notification_id', $notification_id)
					->where('anu.recv_user', $row->recv_user)				
					->select(DB::raw('max(anu.status) as max_status'))
					->first();

				$notify_type_list = DB::table('alarm_notifications_user_log')
					->where('notification_id', $notification_id)
					->where('recv_user', $row->recv_user)
					->where('kind', 1)
					->pluck('notifi_type');

				$row->escal_count = 0;
				if( count($notify_type_list) > 0 )
				{
					$count = DB::table('alarm_notifications_user_log')
						->where('notification_id', $notification_id)
						->where('recv_user', $row->recv_user)
						->where('kind', 1)
						->count();

					$row->escal_count = $count / count($notify_type_list);
				}	

				$row->max_status = $item->max_status;

				switch( $item->max_status )
				{
					case 0:
						$row->status_name = 'Active';
						break;
					case 1:
						$row->status_name = 'Acknowledged';
						break;
					case 2:
						$row->status_name = 'Clear';
						break;
					default:
						$row->status_name = 'Unknown';						
						break;
				}

				// get notify type list
				$row->notify_type_list = DB::table('alarm_notifications_user as anu')
					->where('anu.notification_id', $notification_id)
					->where('anu.recv_user', $row->recv_user)	
					->groupBy('anu.notifi_type')										
					->pluck('anu.notifi_type');

				// get ack type list
				$row->ack_type_list = DB::table('alarm_notifications_user as anu')
					->where('anu.notification_id', $notification_id)
					->where('anu.recv_user', $row->recv_user)		
					->where('anu.status', 1)						
					->groupBy('anu.notifi_type')										
					->pluck('anu.notifi_type');


				$ack_data = DB::table('alarm_notifications_user as anu')
					->where('anu.notification_id', $notification_id)
					->where('anu.recv_user', $row->recv_user)		
					->where('anu.status', 1)						
					->select(DB::raw('min(anu.acknowledge_date) as ack_date'))
					->first();
				if( !empty($ack_data) && !empty($ack_data->ack_date))
					$row->ack_date = $ack_data->ack_date;					
				else
					$row->ack_date = '';					
			}

			$notification->user_list = $user_list;

			// update list

			$update_list = DB::table('alarm_notifications_user as anu')
							->leftJoin('common_users as cu', 'anu.recv_user', '=', 'cu.id')
							->leftJoin('common_users as cu1', 'anu.send_user', '=', 'cu1.id')
							->where('anu.notification_id', $notification_id)							
							->where('anu.send_type', 1)							
							->groupBy('anu.created_at')
							->orderBy('anu.created_at', 'asc')
							->select(DB::raw('GROUP_CONCAT(anu.recv_user) as update_user_ids, 
										GROUP_CONCAT(anu.notifi_type) as notify_groups,
										CONCAT_WS(" ", cu1.first_name, cu1.last_name) as sender_user_name, 
										anu.created_at, anu.send_flag,
										anu.message' ))										
			->get();

			foreach($update_list as $row)
			{
				if( empty($row->update_user_ids ) )
					$row->update_user_list = '';
				else
				{
					$ids = explode(",", $row->update_user_ids);

					$ids = array_unique($ids);
					$userlist = DB::table('common_users as cu')
						->whereIn("cu.id", $ids)
						->select(DB::raw('GROUP_CONCAT(CONCAT_WS(" ", cu.first_name, cu.last_name)) as user_name_list'))
						->first();

					$row->update_user_list = $userlist->user_name_list;
				}

				if( empty($row->notify_groups ) )
					$row->notify_groups = '';
				else
				{
					$notify_groups = explode(",", $row->notify_groups);
					$notify_groups = array_unique($notify_groups);
					$row->notify_groups = implode(",", $notify_groups);
				}

				if ($row->send_flag == '1')
					$row->send_flag = 'Yes';
				else 
					$row->send_flag = 'No';
			}				

			$notification->update_list = $update_list;
		}	

		$data = array();
		$data['log_list'] = $alarm_log_list;

		return $data;
	}

	private function getAlarmLogListForSummaryData(Request $request)
	{
		$property_id = $request->get('property_id', '0');

        $start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$created_ids = $request->get('created_ids', '');
		$status_name = $request->get('status_name', '');
		$alarm_ids = $request->get('alarm_ids', '');

		if( empty($created_ids) )
			$created_ids = [];
		else
			$created_ids = explode(",", $created_ids);


		if( empty($alarm_ids) )
			$alarm_ids = [];
		else
			$alarm_ids = explode(",", $alarm_ids);

		$user_id = $request->get('user_id', 0);
		$dept_id = $request->get('dept_id', 0);
		$dept_flag = $request->get('dept_flag', 'false');


		$ret = array();
		
		$query = DB::table('alarm_notifications as an')
			->leftjoin('services_alarm_groups as sag','an.alarm_id','=','sag.id')
			->leftjoin('common_users as cu','an.send_user', '=', 'cu.id')
			->leftjoin('common_users as cu1','an.clear_user', '=', 'cu1.id')
			->where('an.property_id', $property_id);

		if( count($created_ids) > 0 )
			$query->whereIn('an.send_user', $created_ids);

		if( count($alarm_ids) > 0 )
			$query->whereIn('an.alarm_id', $alarm_ids);

		if ($dept_flag == 'false'){
				$query->where('cu.dept_id', $dept_id);
			}

		if( !empty($status_name) && $status_name != 'All' )
		{
			switch($status_name)
			{
				case 'Active':
					$status = 1;
					break;
				case 'Update':
					$status = 2;
					break;
				case 'Clear':
					$status = 3;
					break;		
			}
			$query->where('an.status', $status);
		}
		
		$query->whereRaw(sprintf("DATE(an.created_at) >= '%s' ", $start_date));
		$query->whereRaw(sprintf("DATE(an.created_at) <= '%s' ", $end_date));

		$alarm_log_list = $query
			->orderBy('an.created_at', 'desc')
			->select(DB::raw('an.*, sag.name as alarm_name, 
							CONCAT_WS(" ", cu.first_name, cu.last_name) as sender_user_name,
							CONCAT_WS(" ", cu1.first_name, cu1.last_name) as clear_user_name
						'))
			->get();

		foreach($alarm_log_list as $notification)
		{
			$notification_id = $notification->id;

			$escalation = DB::table('alarm_setting')
				->where('alarm_id', $notification->alarm_id)
				->first();

			if( empty($escalation) )
				$notification->retry_count = 0;		
			else				
				$notification->retry_count = $escalation->trigger1_loop;		


			$notify_count = DB::table('alarm_notifications_user')								
								->where('notification_id', $notification_id)								
								->groupBy('recv_user')
								->get();								
			$notification->notify_count = count($notify_count);

			$ack_count = DB::table('alarm_notifications_user')								
								->where('notification_id', $notification_id)
								->where('status','>', 0 )
								->groupBy('recv_user')
								->get();
			$notification->ack_count = count($ack_count);

			$receiver_list = DB::table('alarm_notifications_user as anu')
				->leftJoin('common_users as cu', 'anu.recv_user', '=', 'cu.id')
				->where('anu.notification_id', $notification_id)							
				->where('anu.send_type', 0)							
				->groupBy('anu.recv_user')
				->orderBy('cu.first_name', 'desc')
				->select(DB::raw('anu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as user_name' ))										
				->get();

			$notification->sent_to_users = implode(",", array_map(function($item) {
				return $item->user_name . ":" . ($item->status > 0 ? 'Yes' : 'No');
			}, $receiver_list));		
		}	

		$data = array();

		$data['log_list'] = $alarm_log_list;

		return $data;
	}

	
	public function getAlarmLogListData(Request $request)
	{
		$report_by = $request->get('report_by', 'Detail');
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');


		if( $report_by == 'Detail' )
			$data = $this->getAlarmLogListForDetailData($request);
		else
			$data = $this->getAlarmLogListForSummaryData($request);

		$data['report_by'] = $report_by;	 
		if( $report_by == 'Detail' )
		{
			$data['name'] = 'Alarm_Detail_Report_' . $start_date . '_' . $end_date;
			$data['title'] = 'Detail Alarm Report';
		}
		else
		{
			$data['name'] = 'Alarm_Summary_Report_' . $start_date . '_' . $end_date;
			$data['title'] = 'Summary Alarm Report';
		}	

		return $data;	
	}

	public function getAlarmData(Request $request)
	{
		$id = $request->get('id', 0);
		$name = $request->get('name', '');
		
		$data = $this->getAlarmDetailData($request);
		
		
		$data['name'] = 'Alarm_Report_' . $id . '_' . $name;
		$data['title'] = 'Alarm Report';
		

		return $data;	
	}


	public function exportAlarmLogList(Request $request)
    {
        $report_by = $request->get('report_by', 'Detail');        
		$data = $this->getAlarmLogListData($request);
		
        $filename = $data['name'];

        $excel_type = $request->get('excel_type', 'excel');
		$excel_file_type = 'csv';
		if($excel_type == 'excel')
			$excel_file_type = config('app.report_file_type');

        $spreadsheet = new Spreadsheet();

		$sheet = $spreadsheet->getActiveSheet();
		$sheet->setTitle($data['title']);
		
		$status_name = [" ", "Acknowledge", "Update", "Clear"];

		if( $report_by == 'Detail' )
		{
			$sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

			foreach(range('A','E') as $columnID) {
				$sheet->getColumnDimension($columnID)
					->setAutoSize(true);
			}

			$row_num = 1;
			$sheet->mergeCells('A' . $row_num . ':E' . $row_num);		
			$sheet->setCellValue('A' . $row_num, $data['title']);
			
			$style = $sheet->getStyle('A' . $row_num);
			$style->getAlignment()->setHorizontal('center');        
			$style->getFont()->setSize(11);
			$style->getFont()->setBold(true);
			
			foreach($data['log_list'] as $notification)
			{
				$row_num += 2;
				$sheet->mergeCells('B' . $row_num . ':C' . $row_num);
				$sheet->setCellValue('A' . $row_num, 'Alarm Name');
				$sheet->setCellValue('B' . $row_num, $notification->alarm_name);
				$sheet->setCellValue('D' . $row_num, 'Data Generated');
				$sheet->setCellValue('E' . $row_num, date('d M Y', strtotime($notification->created_at)));

				$row_num += 1;
				$sheet->mergeCells('B' . $row_num . ':E' . $row_num);
				$sheet->setCellValue('A' . $row_num, 'Description');
				$sheet->setCellValue('B' . $row_num, $notification->updated_description);

				$row_num += 1;
				$sheet->mergeCells('B' . $row_num . ':E' . $row_num);
				$sheet->setCellValue('A' . $row_num, 'Comment');
				$sheet->setCellValue('B' . $row_num, $notification->message);

				$row_num += 1;		
				$row1 = ['Status', $status_name[$notification->status], '', 'Data & Time', '',];
				$sheet->fromArray($row1, null, "A$row_num");

				$row_num += 1;		
				$row1 = ['Clear Notification', $notification->clear_message, '', 'Cleared By', $notification->clear_user_name,];
				$sheet->fromArray($row1, null, "A$row_num");

				$row_num += 1;		
				$row1 = ['Location', $notification->location];
				$sheet->fromArray($row1, null, "A$row_num");

				$row_num += 1;		
				$row1 = ['Created By', $notification->sender_user_name];
				$sheet->fromArray($row1, null, "A$row_num");

				$row_num += 1;		
				$row1 = ['Created at', date('d M Y H:i:s', strtotime($notification->created_at))];
				$sheet->fromArray($row1, null, "A$row_num");

				$row_num += 1;		
				$row1 = ['No. of users', $notification->notify_count];
				$sheet->fromArray($row1, null, "A$row_num");

				$row_num += 1;		
				$row1 = ['No. of ACK received', $notification->ack_count];
				$sheet->fromArray($row1, null, "A$row_num");

				if( count($notification->update_list) > 0 )
				{
					$row_num += 2; 						
					$row1 = [
						'Update User List',
					];

					$sheet->fromArray($row1, null, "A$row_num");

					$row_num++;		
					$header_list = ['Date and Time', 'Comment', 'Notify Group', 'Users'];
					$sheet->fromArray($header_list, null, "A$row_num");
		
					$style = $sheet->getStyle('A' . $row_num . ':D' . $row_num);
					$style->getAlignment()->setHorizontal('center');                		
					$style->getFont()->setBold(true);
					$style->getFont()->setSize(11);
					$style->getFont()->getColor()->setRGB('ffffff');
					$style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('2c3e50');

					foreach($notification->update_list as $row)
					{
						$row_num++; 
						
						$row1 = [
							date('d M Y H:i:s', strtotime($row->created_at)),
							$row->message,
							$row->notify_groups,
							$row->update_user_list, 						
						];

						$sheet->fromArray($row1, null, "A$row_num");
					}
				}

				$row_num += 2;		
				$header_list = ['Recipient', 'Status', 'Notification Type', 'ACK Type', 'ACK Date&Time',];
				$sheet->fromArray($header_list, null, "A$row_num");

				$style = $sheet->getStyle('A' . $row_num . ':E' . $row_num);
				$style->getAlignment()->setHorizontal('center');                		
				$style->getFont()->setBold(true);
				$style->getFont()->setSize(11);
				$style->getFont()->getColor()->setRGB('ffffff');
				$style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('2c3e50');

				foreach($notification->user_list as $row)
				{
					$row_num++; 
					
					$row1 = [
						$row->user_name,
						$row->status_name . ($row->kind == 1 ? " (Escalation $row->escal_count/$notification->retry_count)" : ""),
						implode(",", $row->notify_type_list),
						$row->max_status >= 1 ? implode(",", $row->ack_type_list) : "",
						$row->max_status >= 1 && !empty($row->ack_date) ? date('d M Y', strtotime($row->ack_date)): "",					
					];

					$sheet->fromArray($row1, null, "A$row_num");
				}
			}
		}
		else
		{
			foreach(range('A','H') as $columnID) {
				$sheet->getColumnDimension($columnID)
					->setAutoSize(true);
			}

			$row_num = 1;
			$sheet->mergeCells('A' . $row_num . ':H' . $row_num);		
			$sheet->setCellValue('A' . $row_num, $data['title']);
			
			$style = $sheet->getStyle('A' . $row_num);
			$style->getAlignment()->setHorizontal('center');        
			$style->getFont()->setSize(11);
			$style->getFont()->setBold(true);

			$prev_date = '';
			foreach($data['log_list'] as $notification)
			{
				$created_date = date('d-M-y', strtotime($notification->created_at));
				if( $created_date != $prev_date)
				{
					$row_num += 2;
					$row1 = [
						$created_date . ' Alarms',						
					];

					$sheet->fromArray($row1, null, "A$row_num");
					$prev_date = $created_date;

					$row_num += 1;		
					$header_list = ['Alarm', 'Time', 'Created by', 'Comment', 'Location', 'Ack', 'Cleared By', 'Cleared On'];
					$sheet->fromArray($header_list, null, "A$row_num");
					$style = $sheet->getStyle('A' . $row_num . ':H' . $row_num);

					$style->getAlignment()->setHorizontal('center');                		
					$style->getFont()->setBold(true);
					$style->getFont()->setSize(11);
					$style->getFont()->getColor()->setRGB('ffffff');
					$style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('2c3e50');
				}

				$row_num++; 
					
				$row1 = [
					$notification->alarm_name,
					date("H:i", strtotime($notification->created_at)),
					$notification->sender_user_name,
					$notification->message,
					$notification->location,					
					"$notification->ack_count/$notification->notify_count",
					$notification->clear_user_name,
					empty($notification->clear_at)  ? '' : date('d-M-y', strtotime($notification->clear_at)),
				];

				$sheet->fromArray($row1, null, "A$row_num");
			}
		}


		Functions::exportExcel($filename, $excel_file_type, $spreadsheet);
    }

	public function getAlarmNotifiUserList(Request $request) {
		$user_id = $request->get('user_id');
		$property_id = $request->get('property_id');
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page * $pageSize;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$notification_id = $request->get('notification_id');
		$search_notify_text = $request->get('search_notify_text', '');

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();

		// get notification type list
		
		$query = DB::table('alarm_notifications_user as anu')
							->leftJoin('common_users as cu', 'anu.recv_user', '=', 'cu.id')
							->where('anu.notification_id', $notification_id)
                            ->where('anu.recv_user', '!=', 0)
							->whereRaw("CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%$search_notify_text%'")
							->groupBy('anu.recv_user');
		$data_query = clone $query;		
		$datalist = $data_query
			->orderBy('cu.first_name', 'desc')
			->select(DB::raw('anu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as user_name' ))			
			->skip($skip)->take($pageSize)
			->get();

		$totalcount = $query->count();
		for($i=0 ; $i < count($datalist) ; $i++) {			
			$alarm_id= $datalist[$i]->alarm_id;
			$get_user_groups = DB::table("alarm_setting_groups_users")
				->where('user_id', $user_id)
				->where('property', $property_id)
				->pluck('group_id');

			$permission = DB::table('alarm_setting_groups as asg')
				->leftjoin('alarm_setting_groups_alarms as asga', 'asg.id','=','asga.group_id')
				->where('asga.alarm_id', $alarm_id)
				->where('asg.property', $property_id)
				->whereIn('asg.id', $get_user_groups)
				->first();

			if($permission) 
				$permissions = explode(',', $permission->status);
			else 
				$permissions = [];

			$datalist[$i]->permission = $permissions;			
		}

		$notification = DB::table('alarm_notifications')
			->where('id', $notification_id)
			->first();

		$escalation = DB::table('alarm_setting')
			->where('alarm_id', $notification->alarm_id)
			->first();

		$log = DB::table('alarm_notifications_user_log')
			->where('notification_id', $notification_id)
			->select(DB::raw('min(created_at) as created_at'))
			->first();	

		foreach( $datalist as $row)
		{
			$item = DB::table('alarm_notifications_user as anu')
				->where('anu.notification_id', $notification_id)
				->where('anu.recv_user', $row->recv_user)				
				->select(DB::raw('max(anu.status) as max_status'))
				->first();

			$notify_type_list = DB::table('alarm_notifications_user_log')
				->where('notification_id', $notification_id)
				->where('recv_user', $row->recv_user)
				->where('kind', 1)
				->pluck('notifi_type');

			$row->escal_count = 0;
			if( count($notify_type_list) > 0 )
			{
				$count = DB::table('alarm_notifications_user_log')
					->where('notification_id', $notification_id)
					->where('recv_user', $row->recv_user)
					->where('kind', 1)
					->count();

				$row->escal_count = $count / count($notify_type_list);
			}	

			switch( $item->max_status )
			{
				case 0:
					$row->status_name = 'Active';
					break;
				case 1:
					$row->status_name = 'Acknowledged';
					break;
				case 2:
					$row->status_name = 'Clear';
					break;
			}

			// get notify type list
			$row->notify_type_list = DB::table('alarm_notifications_user as anu')
				->where('anu.notification_id', $notification_id)
				->where('anu.recv_user', $row->recv_user)											
				->groupBy('anu.notifi_type')										
				->pluck('anu.notifi_type');

			// get ack type list
			$row->ack_type_list = DB::table('alarm_notifications_user as anu')
				->where('anu.notification_id', $notification_id)
				->where('anu.recv_user', $row->recv_user)		
				->where('anu.status', 1)						
				->groupBy('anu.notifi_type')										
				->pluck('anu.notifi_type');

			// get warning list
			$receiver_list = DB::table('alarm_notifications_user as anu')
				->leftJoin('common_users as cu', 'anu.recv_user', '=', 'cu.id')
				->where('anu.notification_id', $notification_id)
				->where('anu.recv_user', $row->recv_user)
				->select(DB::raw('anu.notifi_type, cu.mobile, cu.email'))
				->get();

			$warning_message = '';
			foreach($receiver_list as $row1)
			{
				if( ($row1->notifi_type == 'SMS' || $row1->notifi_type == 'Mobile') 
					&& strlen($row1->mobile) != 12 )
				{
					$warning_message = 'Invalid Phone Number';	
				}
				else
					$mobile = $row1->mobile;

				if( ($row1->notifi_type == 'Email') 
					&& !filter_var($row1->email, FILTER_VALIDATE_EMAIL) )
				{
					$warning_message = 'Email';	
				}					
				else
					$email = $row1->email;
			}	

			$row->warning_message = $warning_message;
			$row->email = isset($email) ? $email : "";
			$row->mobile = isset($mobile) ? $mobile : "";
			$row->notify_status = $notification->status;	

			$ack_data = DB::table('alarm_notifications_user as anu')
				->where('anu.notification_id', $notification_id)
				->where('anu.recv_user', $row->recv_user)		
				->where('anu.status', 1)						
				->select(DB::raw('min(anu.acknowledge_date) as ack_date'))
				->first();
			if( !empty($ack_data) && !empty($ack_data->ack_date) && !empty($log->created_at))
			{
				$row->ack_date = $ack_data->ack_date;
				$duration = strtotime($ack_data->ack_date) - strtotime($log->created_at);
				$row->duration = $duration;
				$row->ack_duration = Functions::getHHMMSSFormatFromSecond($duration);
			}
		}


		$ret['status'] = '200';
		$ret['datalist'] = $datalist;
		$ret['totalcount'] = $totalcount;
		if( !empty($escalation) )
			$ret['retry_count'] = $escalation->trigger1_loop;		
		else
			$ret['retry_count'] = 0;		
			
		if( !empty($log) || !empty($log->created_at) )
			$ret['created_at'] = $log->created_at;

		return Response::json($ret);
	}
	
	public function changeAlarmStatus(Request $request) {        
        $notification_id = $request->get('id');
		$status = $request->get('status');
		$send_condition = $request->get('send_condition');
		$check_message = $request->get('check_message','');
		$clear_message = $request->get('clear_message','');
		$send_flag = $request->get('send_flag','');
		$alarm_backcolor = $request->get('alarm_backcolor', 'green');
		$send_user 	= $request->get('send_user');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$date_val = date("d M Y");
		$time_val = date("H:i");

		$clear_user = -1;
		$clear_at = '';
		if( $status == '3' )
		{
			$clear_user = $send_user;
			$clear_at = $cur_time;
		}

        DB::table('alarm_notifications')
            ->where('id', $notification_id)
			->update(['status' => $status, 
						'clear_user' => $clear_user, 
						'clear_at' => $clear_at, 
						'check_message'=>$check_message, 
						'clear_message'=>$clear_message]
					);

		$description = "";
		$desc = DB::table('alarm_notifications')->where('id', $notification_id)->first();
		if($desc) $description = $desc->updated_description;
		
		if($send_condition == '0') { // if send condition=1, send , else send condition = 0, no send 
			$ret = array();
			$ret['status'] = '200';
			return Response::json($ret);
		}
/*
		if($send_flag == 'no') {
			$ret = array();
			$ret['status'] = '200';
			return Response::json($ret);
		}
*/		

		$send_type = '1';
		if($status == '1') $send_type = 0;// active
		if($status == '2') $send_type = 1;// check/update
		if($status == '3') $send_type = 2;// clear

		//active =1, Check/update=2, Clear=3
		$status_val = '';
		if($status == '1') $status_val = 'Active';
		if($status == '2') $status_val = 'Update';//change from check to update 
		if($status == '3') $status_val = 'Clear';


		$log_message = $check_message;
		if( $status == '3' )
			$log_message = $clear_message;

		$acknowledge = '';

		// get user list
		$notify_user_list = DB::table('alarm_notifications_user as anu')
			->leftJoin('services_alarm_groups as sag', 'anu.alarm_id', '=', 'sag.id')
			->leftJoin('common_users as cu1', 'anu.recv_user', '=', 'cu1.id')						
			->where('anu.notification_id', $notification_id)
			->where('anu.send_type', 0)
			->select(DB::raw('anu.*, sag.name as alarm_name,
						CONCAT_WS(" ", cu1.first_name, cu1.last_name) as recv_user_name,
						cu1.mobile, cu1.email
						'
						))
			->get();

		foreach($notify_user_list as $row)
		{
			$notification = array();
			$notification['notification_id'] = $row->notification_id;
			$notification['alarm_id'] = $row->alarm_id;
			$notification['property_id'] = $row->property_id;
			$notification['notifi_type'] = $row->notifi_type;						
			$notification['send_user'] = $send_user;
			$notification['recv_user'] = $send_flag === 'no' ? 0 : $row->recv_user;
			$notification['location'] = $row->location;
			$notification['message'] = $log_message;
			$notification['send_type'] = $send_type; // 1=check, 2 = clear when change status of notification
			if ($send_flag == 'no')
				$notification['send_flag'] = 0; // 1=notification sent, 0 = no notification
			else
				$notification['send_flag'] = 1;
			$notification['acknowledge'] = '' ;						
			$notification['acknowledge'] = '' ;


			$notification['created_at'] = $cur_time;
			DB::table('alarm_notifications_user')->insert($notification);
			if($send_flag == 'no') {
				$ret = array();
				$ret['status'] = '200';
				return Response::json($ret);
			}
			else
			{

			CommonUser::addNotifyCount($row->property_id, 'app.alarm.dashboard');
			if($status_val == 'Update')   // change from check to update
				$message = 'ALARM UPDATE'."\n"; // change from check to update
			else if ($status_val == 'Clear')
				$message = 'ALARM CLEARED'."\n";
			else
				$message = 'ALARM '.$status_val."\n";

			$message .= $date_val.' '.$time_val. "\n";			
			$message .= 'ALARM:' . $row->alarm_name ."\n" ;
			$message .= $description . "\n" ;
			$message .= 'Location:' . $row->location."\n";

			if($row->message != "") {
				$message .= 'CM:' . $row->message  . "\n" ;
			}

			$user = DB::table('common_users')->where('id', $row->recv_user)->first();

			if ($send_flag === 'no') {
			    continue;
            }

			switch ($row->notifi_type) {
				case 'SMS'; //reference old logic in guest service
					$mobile_no = $row->mobile;						
					if (strlen($mobile_no) < 12) {
						if (substr($mobile_no, 0, 1) === '0') {
							$mobile_no = substr($mobile_no, 1, strlen($mobile_no) - 1);
						}
						// Default country code : 971
						$mobile_no = "971" . $mobile_no;
					}						
					$this->sendSMS(0, $mobile_no, $message, null);
					break;

				case 'Mobile';//reference old logic in guest service
					$task_id = 1;
					$payload = array();									
					$this->sendMobilePushMessage($row->alarm_name, $message, $user, $payload);
					break;

				case 'Email';
					$info = array();
					$info['status'] = $status_val;
					$info['date_val'] = $date_val;
					$info['status_val'] = 'Alarm '.$status_val;
					$info['alarm_name'] = $row->alarm_name;
					$info['alarm_description'] = $description;
					$info['location'] = $row->location;
					$info['comment'] = $row->message;
					$info['recv_user_name'] = $row->recv_user_name;
					//$info['acknowledge'] = $acknowledge;						
					$message = view('emails.alarm_notification', ['info' => $info])->render();

					$smtp = Functions::getMailSetting(4, 'notification_');						
					$this->sendEmail($user->email, 'Hotlync', $message, $smtp, NULL, $row->alarm_name, $status_val);
					break;

				case 'IVR';
					$sound_name = 'text_speech_'.$notification_id.'_'.$row->recv_user;
					$this->text_speech($sound_name, $row->alarm_name, $row->location , $row->message, $description);					
					$public_path = $this->current_path();
					//$sound_path = $public_path.'/sound/'.$sound_name.'.mp3';
					$sound_path = $public_path.'/sound/'.$sound_name.'.wav';
					$one = array();
					$one['type'] = 'ivr';
					$one['id'] = $notification_id;				
					$one['mobile_no'] = $user->mobile;
					$one['send_type'] = '1';				
					$one['ack_path']= '';
					$one['sound_path'] = $sound_path;
					$one['send_status'] = $status_val;						
					Redis::publish('notify', json_encode($one));
					break;

				case 'Webpush';												
					$one = array();
					$one['notification_id'] = $notification_id;
					$one['alarm_id'] = $row->alarm_id;
					$one['property_id'] = $row->property_id;
					$one['notifi_type'] = 'webpush';						
					$one['send_user'] = $send_user;
					$one['recv_user'] = $row->recv_user;
					$one['location'] = $row->location;
					$one['message'] = $row->message;	
					$one['acknowledge'] = '';	
					$one['send_type'] = '1';	
					$one['send_status'] = 'status_val';					
					$one['created_at'] = $cur_time;
					
					$one['type'] = 'app.alarm.dashboard';
					$one['alarm_name'] = $row->alarm_name;
					$one['content'] = $message;

					$ret_m = array();
					$ret_m['type'] = 'webpush';
					$ret_m['to'] = $row->property_id;
					$ret_m['content'] = $one;						
					Redis::publish('notify', json_encode($ret_m));

					break;
				case 'Desktop';
					$one = array();		
					$one['type'] = 'alarm';						
					$one['alarm_name'] = $row->alarm_name;						
					$one['status_val'] = 'Alarm '.$status_val;
					$one['message'] = $description;
					$one['location'] = $row->location;
					$one['comment'] = $row->message;
					$one['acknowledge'] = $acknowledge;
					$one['send_type'] = '0'; // if 0, then send acknowledge
					$one['send_status'] = $status_val;			
					$one['created_at'] = $cur_time;
					$one['alarm_backcolor'] = $alarm_backcolor;

					$this->sendDesktopNotification($user, $row->property_id, $one);
					break;
				case 'WhatsApp';
					$s_user = DB::table('common_users')->where('id', $send_user)->first();							
					$from = $s_user->mobile;							
					if (strlen($from) < 12) {
						if (substr($from, 0, 1) === '0') {
							$from = substr($from, 1, strlen($from) - 1);
						}
						// Default country code : 971
						$from = "971" . $from;
					}
					$mobile_no = $user->mobile;
					if (strlen($mobile_no) < 12) {
						if (substr($mobile_no, 0, 1) === '0') {
							$mobile_no = substr($mobile_no, 1, strlen($mobile_no) - 1);
						}
						// Default country code : 971
						$mobile_no = "971" . $mobile_no;
					}
					$message .= 'Acknowledge:<a href="'.$acknowledge.'">Click</a>';
					$this->sendWhatsApp($from, $mobile_no, $message, null);	
					break;
			}	
		}		
		}

        if($send_flag === 'no') {
            $ret = array();
            $ret['status'] = '200';
            return Response::json($ret);
        }

        $ret = array();
		$ret['status'] = '200';
		$ret['notify_user_list'] = $notify_user_list;
		
        return Response::json($ret);
	}
	
	public function changeAlarmStatusOfUser(Request $request) {		
		$notification_id = $request->get('notification_id');
		$property_id = $request->get('property_id');
		$alarm_id = $request->get('alarm_id');
		$status = $request->get('status');
		$user_id = $request->get('user_id');
		$location = $request->get('location');
        DB::table('alarm_notifications_user')
			->where('alarm_id', $alarm_id)
			->where('recv_user', $user_id)
			->where('location', $location)
			->where('property_id' , $property_id)
			->where('notification_id', $notification_id)
            ->update(['status' => $status]);
        $ret = array();
        $ret['status'] = '200';
        return Response::json($ret);
	}

	public function changeAcknowledge(Request $request) {
		$notification_id = $request->get('n'); // notification id
		$alarm_id = $request->get('a'); // alarm_id
		$property_id = $request->get('p'); // property_id
		$recv_user = $request->get('r'); // receive user
		$type = $request->get('t');
		
		if ($type == 's')
		$type = 'SMS';
		else if ($type == 'e')
		$type = 'Email';
		else
		$type = 'Desktop';
		

		//$location = $request->get('l'); // location
		$status = '1' ;// acknowledge status
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");		
		//delete alarm voice
		//$text_speech = public_path()."/text_speech_".$notification_id.'_'.$recv_user.".mp3";
		$text_speech = public_path()."/text_speech_".$notification_id.'_'.$recv_user.".wav";
		
		if(file_exists($text_speech)) {
            unlink($text_speech);
		}	
			
        $updateAck = DB::table('alarm_notifications_user')
						->where('alarm_id', $alarm_id)
						->where('recv_user', $recv_user)
						->where('notifi_type', $type)
						//->where('location', $location)
						->where('property_id' , $property_id)
						->where('notification_id', $notification_id)
						->update(['status' => $status,'acknowledge_date' => $cur_time]);
			
		$ret = array();
		if($updateAck) $ret['status'] = '200';
		else $ret['status'] = '404';
        return Response::json($ret);
	}

	public function changeAcknowledge1(Request $request, $id) {
		$anu_model = DB::table('alarm_notifications_user')
			->where('id', $id)
			->first();

		$ret = array();

		$ret['status'] = '404';

		if( !empty($anu_model) )
		{			
			$notification_id = $anu_model->notification_id; // notification id
			$alarm_id = $anu_model->alarm_id; // alarm_id
			$property_id = $anu_model->property_id; // property_id
			$recv_user = $anu_model->recv_user; // receive user
			$type = $anu_model->notifi_type;

			date_default_timezone_set(config('app.timezone'));
			$cur_time = date("Y-m-d H:i:s");		

			// check expired setting.
			$alarm_group = DB::table('alarm_setting_groups_alarms as asga')
				->join('alarm_setting_groups as asg', 'asga.group_id', '=', 'asg.id')
				->where('asga.alarm_id', $alarm_id)
				->first();

			if( !empty($alarm_group) )
			{
				$duration = strtotime($cur_time) - strtotime($anu_model->created_at);
				if( $duration > $alarm_group->max_duration * 60 )
				{
					$ret['status'] = '404';
					$ret['message'] = "Link has expired";
        			return Response::json($ret);
				}
			}	
				
			
			$status = '1' ;// acknowledge status			
			$text_speech = public_path()."/text_speech_".$notification_id.'_'.$recv_user.".wav";
			
			if(file_exists($text_speech)) {
				unlink($text_speech);
			}	

			$updateAck = DB::table('alarm_notifications_user')
						->where('id', $id)
						->update(['status' => $status,'acknowledge_date' => $cur_time]);

			if($updateAck) 
			{
				$notification = array();
				$notification['notification_id'] = $anu_model->notification_id;
				$notification['alarm_id'] = $anu_model->alarm_id;
				$notification['property_id'] = $anu_model->property_id;
				$notification['notifi_type'] = $anu_model->notifi_type;						
				$notification['send_user'] = $anu_model->send_user ;
				$notification['recv_user'] = $anu_model->recv_user;
				$notification['location'] = $anu_model->location;
				$notification['message'] = $anu_model->message;	
				$notification['acknowledge'] = $anu_model->acknowledge;						
				$notification['status'] = $status;						
				$notification['acknowledge_date'] = $cur_time;										
				$notification['created_at'] = $cur_time;
				DB::table('alarm_notifications_user_log')->insert($notification);

				$ret['status'] = '200';
				$ret['message'] = "Alarm acknowledged successfully";
			}
			else
			 	$ret['status'] = '404';
		}
			
        return Response::json($ret);
	}

	public function changeImageOfAlarm(Request $request) {
		$id = $request->get('id');
		$model = AlarmGroup::find($id);	
		$input = $request->except(['picture_src','extension']);
		$picture_src = $request->get('picture_src','') ;
		if($picture_src != '') {
			$origin_icon = $model->icon;
			if($origin_icon) {
				$delete_file = public_path() . $origin_icon;
				unlink($delete_file);
			}
			$extension = $request->get('extension');
			$output_file = public_path() . '/uploads/icons/';
			if(!file_exists($output_file)) {
				mkdir($output_file, 0777);
			}		
			$picture = str_random(15) . $extension;;		
			$output_file = public_path() . '/uploads/icons/' . $picture;
			if($picture_src !='') {
				$ifp = fopen($output_file, "wb");
				$data = explode(',', $picture_src);
				fwrite($ifp, base64_decode($data[1]));
				fclose($ifp);
			}
			$input['icon'] = '/uploads/icons/'.$picture;
		}        
		$model->update($input);
		
		return Response::json($model);
	}

	public function getAlarmGroupList(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$val = $request->get('val', '');

		$alarm_groups = DB::table('services_alarm_groups as ag')
				->where('ag.property', $property_id)
				->where('ag.name', 'like', '%' . $val . '%')
				// ->groupBy('name')
				->orderBy('name')
				->get();
		return Response::json($alarm_groups);
	}

	public function getSettingList(Request $request) {
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'al.id');
		$sort = $request->get('sort', 'asc');

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();

		$datalist = DB::table('alarm_setting as al')
			->leftjoin('services_alarm_groups as sag', 'al.alarm_id', '=', 'sag.id')
			->orderBy($orderby, $sort)
			->select(DB::raw('al.*, sag.name as alarm_name'))
			->skip($skip)->take($pageSize)
			->get();				
		$totalcount = DB::table('alarm_setting')->count();

		$ret['status'] = '200';
		$ret['datalist'] = $datalist;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function getAcknow(Request $request) {
		$notifi_id = $request->get('notifi_id', '0');
		$notifi = DB::table('alarm_notifications')
						->where('id', $notifi_id)
						->first();
		$ret = array();						
		if($notifi) {				
			$notifi_list = DB::table('alarm_notifications_user')
								->where('notification_id', $notifi_id)
								->where('send_type', '0') // only sending notification event
								->get();
			$acknow_list = DB::table('alarm_notifications_user')
								->where('notification_id', $notifi_id)
								->where('status', 1)
								->where('send_type', '0') // only sending notification event.
								->get();
			$notifi->notifi_list = $notifi_list;		
			$notifi->ack_list = $acknow_list;
			$percent = 0;
			if(count($notifi_list) > 0) {
				if(count($acknow_list) > 0 ) {
					$percent = round(count($acknow_list)/count($notifi_list) * 100); 
				}
			}
			$notifi->percent = $percent;
		}
		$ret['notifi'] = $notifi;
		return Response::json($ret);
	}
	
	public function getNotifiStatus(Request $request) {
		$notifi_id = $request->get('notifi_id', '0');
		$notifi = DB::table('alarm_notifications')
						->where('id', $notifi_id)
						->first();
		$ret = array();
		$ret['notifi'] = $notifi;
		return Response::json($ret);
	}

	public function text_speech($sound_name, $alarm_name, $location, $comment, $desc) {		
		$public_path = $this->current_path();
        $token_run  = $public_path."/text_speech.php";
        $response   = Curl::to($token_run)
            //->enableDebug(public_path().'/curllog.txt')
            ->withOption('SSL_VERIFYHOST', false)
            ->withData( array( 'sound_name' => $sound_name, 'alarm_name'=> $alarm_name, 'location'=> $location, 'comment'=> $comment,'desc'=>$desc))
            ->returnResponseObject()
            ->post();
        $val =  $response->content;		
	}

	public function sendDesktopNotification($user, $property_id, $data) {
		$message = array();
		$message['type'] = 'desktop_notification';
		$message['to'] = $user->id;
		$message['property_id'] = $property_id;
		$message['content'] = $data;

		Redis::publish('notify', json_encode($message));
	}	

	public function sendMobileserverAlarm(Request $request)
	{
		$ret = array();

		$row = DB::table('common_property')->first();
		if( empty($row) )
		{
			$ret['code'] = 201;
			return Response::json($ret);
		}

		$property_id = $row->id;

		$rules = array();
		$rules['mobileserver_alarm_to_email'] = 'support@ennovatech.ae';
		
		$rules = PropertySetting::getPropertySettings($property_id, $rules); 

		$smtp = Functions::getMailSetting($property_id, 'notification_');

		$message = array();
		$message['type'] = 'email';
		$message['to'] = $rules['mobileserver_alarm_to_email'];
		$message['subject'] = 'Mobile Server Not Reachable';
		$message['content'] = "Mobile Server at $row->name not reachable. Restarting Automatically.";
		$message['smtp'] = $smtp;

		Redis::publish('notify', json_encode($message));
	}
}
