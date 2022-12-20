<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Functions;
use App\Models\CommonUser;
use Illuminate\Support\Facades\DB;

class ShiftGroupMember extends Model 
{
	protected $primaryKey = 'user_id'; // or null
	public $incrementing = false;

    protected 	$guarded = [];
	protected 	$table = 'services_shift_group_members';
	public 		$timestamps = false;
	
	public static function getUserlistforEscalation($property_id, $job_role_id, $dept_id, $usergroup_id) {
		$query = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.deleted', 0);
				//->where('cu.active_status', 1);
		
		if( $property_id > 0 )		
			$query->where('cd.property_id', $property_id);

			

		if( $job_role_id > 0 )		
		{
			// if( $second_job_role_check == false )	
			// {
				$query->where('cu.job_role_id', $job_role_id);

				// check job role's department 
				$job_role = DB::table('common_job_role as jr')
				    ->where('jr.id', $job_role_id )
				    ->first();

				if( !empty($job_role) && $job_role->dept_id < 1 )	// job role is relate to multiple department
					$dept_id = 0;    // ignore department	
			// }
			// else  // secondary job roles check
			// {
			// 	$query->join('services_secondary_jobrole as sjr', 'sgm.user_id', '=', 'sjr.user_id')
			// 		->where('sjr.job_role_id', $job_role_id);
			// }
			
		}

		// if( $location_group_id > 0 )
		// {
		// 	$query->where(function ($query) use ($location_group_id) {
		// 			$query->where('sgm.location_grp_id', '[' . $location_group_id . ']')
		// 					->orWhere('sgm.location_grp_id', 'like', '[' . $location_group_id . ',%')
		// 					->orWhere('sgm.location_grp_id', 'like', '%,' . $location_group_id . ']')
		// 					->orWhere('sgm.location_grp_id', 'like', '%,' . $location_group_id . ',%')
		// 					->orWhere('sgm.location_grp_id', '0');
		// 		});
		// }

		// if( $task_group_id > 0 && $second_job_role_check == false )
		// {
		// 	$query->where(function ($subquery) use ($task_group_id) {
		// 			$subquery->where('sgm.task_group_id', '[' . $task_group_id . ']')
		// 					->orWhere('sgm.task_group_id', 'like', '[' . $task_group_id . ',%')
		// 					->orWhere('sgm.task_group_id', 'like', '%,' . $task_group_id . ']')
		// 					->orWhere('sgm.task_group_id', 'like', '%,' . $task_group_id . ',%')
		// 					->orWhere('sgm.task_group_id', '0');
		// 		});
		// }
		
		// if( $dept_id > 0 )
		// {
		// 	$query->where('cu.dept_id', $dept_id);
		// 	// $query->where('sg.dept_id', $dept_id);
		// }

		// if( $user_id > 0 )
		// {
		// 	$query->where('sgm.user_id', $user_id);
		// }

		if( $usergroup_id > 0 )
		{
			$query->join('common_user_group_members as ugm', 'ugm.user_id', '=', 'cu.id')
				->where('ugm.group_id', $usergroup_id);
		}

		//$delegated_query = clone $query;

		// if( $active_check == true )
		// {
		// 	$query->where(function ($query) use ($date) {	// vacation period
		// 		$query->where('sgm.vaca_start_date', '>', $date)
		// 				->orWhere('sgm.vaca_end_date', '<', $date);
		// 	});	
		// }
		
		$userlist = $query->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, 1 as duty'))
				->get();


		// $delegate_ids = [];		
		// $user_ids = [];
		// if( $active_check == false )	// escalated to manager
		// {
			$users=[];
		 	foreach($userlist as $row) {
			

		//$send_mode = 'SMS';
		if( $row->active_status==0 )	// staff is ready for shift
		{
			if($row->contact_pref_bus !='Mobile');
			$users[]=$row;
		}
		else
		$users[]=$row;
	}
	$userlist=$users;	
		// 		$user_ids[] = $row->id;

		// 		if( $row->vaca_start_date <= $date && $date <= $row->vaca_end_date )	// vacation
		// 		{
		// 			$row->duty = 0;
		// 			if( $row->delegated_user_id > 0 )
		// 				$delegate_ids[] = $row->delegated_user_id;
		// 		}
		// 		else
		// 		{
		// 			$row->duty = 1;
		// 		}
		// 	}

		// 	$delegated_list = DB::table('services_shift_group_members as sgm')
		// 		->join('common_users as cu', 'sgm.user_id', '=', 'cu.id')
		// 	    ->whereNotIn('cu.id', $user_ids)
		// 		->whereIn('cu.id', $delegate_ids)
		// 		->select(DB::raw('sgm.*, cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, 1 as duty'))
		// 		->get();

		// 	//$userlist = array_merge($userlist, $delegated_list);	
		// }		

		return $userlist;		
	}

	public static function getUserlistOnCurrentShift($property_id, $job_role_id, $dept_id, $user_id, $usergroup_id, $location_group_id, $task_group_id, $active_check, $second_job_role_check = false) {
		date_default_timezone_set(config('app.timezone'));
		$dayofweek = date('w');

		$date = date('Y-m-d');
		$time = date('H:i:s');
		
		$daylist = [
				'0' => 'Sunday',
				'1' => 'Monday',
				'2' => 'Tuesday',
				'3' => 'Wednesday',
				'4' => 'Thursday',
				'5' => 'Friday',
				'6' => 'Saturday',
		];

		$day = $daylist[$dayofweek];

		return ShiftGroupMember::getUserlistOnShift($property_id, $job_role_id, $dept_id, $user_id, $usergroup_id, $location_group_id, $task_group_id, $active_check, $date, $time, $day, $second_job_role_check);
	}
	public static function getAllDeptUserlistOnCurrentActive($property_id, $dept_id) {

		$query = DB::table('common_users as cu')
						->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
						->where('cu.deleted', 0)
						->where('cd.property_id', $property_id)
						->where('cu.active_status', 1)
						->where('cu.dept_id', $dept_id);


		$userlist = $query->select(DB::raw('cu.*, cu.id as user_id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
						->get();

		return $userlist;
	}

	public static function getUserlistOnShift($property_id, $job_role_id, $dept_id, $user_id, $usergroup_id, $location_group_id, $task_group_id, $active_check, $date, $time, $day, $second_job_role_check = false) {
		$query = DB::table('services_shift_group_members as sgm')
				->join('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
				->join('services_shifts as sh', 'sg.shift', '=', 'sh.id')
				->join('common_users as cu', 'sgm.user_id', '=', 'cu.id')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->whereRaw("(('$time' BETWEEN start_time AND end_time AND start_time < end_time) 
							OR ('$time' NOT BETWEEN end_time AND start_time AND end_time < start_time))")				
				->where('sgm.day_of_week', 'LIKE', '%' . $day . '%')
				->where('cu.deleted', 0);

		if( $property_id > 0 )		
			$query->where('cd.property_id', $property_id);

		if( $active_check == true )
			$query->where('cu.active_status', 1);		

		if( $job_role_id > 0 )		
		{
			if( $second_job_role_check == false )	
			{
				$query->where('cu.job_role_id', $job_role_id);

				// check job role's department 
				$job_role = DB::table('common_job_role as jr')
				    ->where('jr.id', $job_role_id )
				    ->first();

				if( !empty($job_role) && $job_role->dept_id < 1 )	// job role is relate to multiple department
					$dept_id = 0;    // ignore department	
			}
			else  // secondary job roles check
			{
				$query->join('services_secondary_jobrole as sjr', 'sgm.user_id', '=', 'sjr.user_id')
					->where('sjr.job_role_id', $job_role_id);
			}
			
		}

		if( $location_group_id > 0 )
		{
			$query->where(function ($query) use ($location_group_id) {
					$query->where('sgm.location_grp_id', '[' . $location_group_id . ']')
							->orWhere('sgm.location_grp_id', 'like', '[' . $location_group_id . ',%')
							->orWhere('sgm.location_grp_id', 'like', '%,' . $location_group_id . ']')
							->orWhere('sgm.location_grp_id', 'like', '%,' . $location_group_id . ',%')
							->orWhere('sgm.location_grp_id', '0');
				});
		}

		if( $task_group_id > 0 && $second_job_role_check == false )
		{
			$query->where(function ($subquery) use ($task_group_id) {
					$subquery->where('sgm.task_group_id', '[' . $task_group_id . ']')
							->orWhere('sgm.task_group_id', 'like', '[' . $task_group_id . ',%')
							->orWhere('sgm.task_group_id', 'like', '%,' . $task_group_id . ']')
							->orWhere('sgm.task_group_id', 'like', '%,' . $task_group_id . ',%')
							->orWhere('sgm.task_group_id', '0');
				});
		}
		
		if( $dept_id > 0 )
		{
			$query->where('cu.dept_id', $dept_id);
			// $query->where('sg.dept_id', $dept_id);
		}

		if( $user_id > 0 )
		{
			$query->where('sgm.user_id', $user_id);
		}

		if( $usergroup_id > 0 )
		{
			$query->join('common_user_group_members as ugm', 'ugm.user_id', '=', 'cu.id')
				->where('ugm.group_id', $usergroup_id);
		}

		$delegated_query = clone $query;

		if( $active_check == true )
		{
			$query->where(function ($query) use ($date) {	// vacation period
				$query->where('sgm.vaca_start_date', '>', $date)
						->orWhere('sgm.vaca_end_date', '<', $date);
			});	
		}
		
		$userlist = $query->select(DB::raw('sgm.*, cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, 1 as duty'))
				->get();

		$delegate_ids = [];		
		$user_ids = [];
		if( $active_check == false )	// escalated to manager
		{
			foreach($userlist as $row) {
				$user_ids[] = $row->id;

				if( $row->vaca_start_date <= $date && $date <= $row->vaca_end_date )	// vacation
				{
					$row->duty = 0;
					if( $row->delegated_user_id > 0 )
						$delegate_ids[] = $row->delegated_user_id;
				}
				else
				{
					$row->duty = 1;
				}
			}

			$delegated_list = DB::table('services_shift_group_members as sgm')
				->join('common_users as cu', 'sgm.user_id', '=', 'cu.id')
			    ->whereNotIn('cu.id', $user_ids)
				->whereIn('cu.id', $delegate_ids)
				->select(DB::raw('sgm.*, cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, 1 as duty'))
				->get();

			$userlist = array_merge($userlist, $delegated_list);	
		}		

		return $userlist;		
	}

	public static function getUserStatusList($user_ids, $property_id) {
		date_default_timezone_set(config('app.timezone'));
		$dayofweek = date('w');

		$date = date('Y-m-d');
		$time = date('H:i:s');
		
		$daylist = [
				'0' => 'Sunday',
				'1' => 'Monday',
				'2' => 'Tuesday',
				'3' => 'Wednesday',
				'4' => 'Thursday',
				'5' => 'Friday',
				'6' => 'Saturday',
		];

		$day = $daylist[$dayofweek];

		$query = DB::table('services_shift_group_members as sgm')
				->join('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
				->join('services_shifts as sh', 'sg.shift', '=', 'sh.id')
				->join('common_users as cu', 'sgm.user_id', '=', 'cu.id')
				->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')												
				->whereIn('sgm.user_id', $user_ids)
				->where('cu.deleted', 0);


		$online_query = clone $query;

		$online_query
				->where('cu.active_status', 1)
				->whereRaw("(('$time' BETWEEN start_time AND end_time AND start_time < end_time) 
							OR ('$time' NOT BETWEEN end_time AND start_time AND end_time < start_time))")				
				->where(function ($query) use ($date) {	// vacation period
					$query->where('sgm.vaca_start_date', '>', $date)
							->orWhere('sgm.vaca_end_date', '<', $date);
				})				
				->where('sgm.day_of_week', 'LIKE', '%' . $day . '%');		

		$online_userlist = $online_query->select(DB::raw('sgm.*, cu.*, sh.start_time, sh.end_time, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, jr.job_role, "Online" as status'))
				->get();

		$online_ids = [];
		foreach($online_userlist as $row)
			$online_ids[] = $row->user_id;

		$offline_query = clone $query;	

		$offline_userlist = $offline_query->whereNotIn('sgm.user_id', $online_ids)
				->select(DB::raw('sgm.*, sh.start_time, sh.end_time, cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, jr.job_role, "Offline" as status'))
				->get();

		$userlist = [];

		foreach($online_userlist as $row) {
			if( $row->start_time < $row->end_time )
			{
				$row->duration = Functions::getHHMMSSFormatFromSecond(strtotime($time) - strtotime($row->start_time));
			}
			$userlist[] = $row;
		}

		foreach($offline_userlist as $row) {
			$userlist[] = $row;
		}


		return $userlist;		
	}

	public static function getDelegatedUser($user_id) {
		$userlist = ShiftGroupMember::getUserlistOnCurrentShift(0, 0, 0, $user_id, 0, 0, 0, false, false);
		if( count($userlist) != 2 )
			return null;

		$user = $userlist[1];
		
		return $user;
	}

	public static function getUserListWhoDelegateToHim($param) {
		$user_id = $param['user_id'];
		$property_id = $param['property_id'];
		$job_role_id = $param['job_role_id'];
		$dept_id = $param['dept_id'];
		$usergroup_id = $param['usergroup_id'];
		$date = $param['date'];

		$query = DB::table('services_shift_group_members as sgm')
				->join('common_users as cu', 'sgm.user_id', '=', 'cu.id')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.deleted', 0);

		if( $property_id > 0 )		
			$query->where('cd.property_id', $property_id);

		if( $job_role_id > 0 )		
		{
			$query->where('cu.job_role_id', $job_role_id);

			// check job role's department 
			$job_role = DB::table('common_job_role as jr')
			    ->where('jr.id', $job_role_id )
			    ->first();

			if( !empty($job_role) && $job_role->dept_id < 1 )	// job role is relate to multiple department
				$dept_id = 0;    // ignore department	
		}

		if( $dept_id > 0 )
		{
			$query->where('cu.dept_id', $dept_id);		
		}

		if( $user_id > 0 )
		{
			$query->where('sgm.delegated_user_id', $user_id);
		}

		if( $usergroup_id > 0 )
		{
			$query->join('common_user_group_members as ugm', 'ugm.user_id', '=', 'cu.id')
				->where('ugm.group_id', $usergroup_id);
		}

		// $query->where('sgm.vaca_start_date', '<=', $date)
		// 		->where('sgm.vaca_end_date', '>=', $date);
		
		$userlist = $query->select(DB::raw('sgm.*, cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();

		return $userlist;		
	}


public static function getUserListUnderApprover($param) {
		$user_id = $param['user_id'];
		$property_id = $param['property_id'];
		$job_role_id = $param['job_role_id'];
		$dept_id = $param['dept_id'];
		$usergroup_id = $param['usergroup_id'];
		//$date = $param['date'];

		$approver = DB::table('call_managers as cm')
				// ->join('common_users as cu', 'sgm.user_id', '=', 'cu.id')
				// ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				// ->where('cu.deleted', 0)
				->where('cm.property_id',$property_id)
				->where('cm.approver_id',$user_id)->select('cm.*')->first();
		
		//$ids=[];
		if(!empty($approver))
		{
		$ids = explode(',', $approver->classifiers_id);

		$dept= explode(',', $approver->dept_ids);
		
		foreach ($dept as $value) {
			$list=DB::table('common_users as cu')->whereIn( 'cu.dept_id',$dept)->select('cu.id')->get();
		
		}
		foreach ($list as $value) {
			$ids[]=$value->id;
		}
		// if( $property_id > 0 )		
		// 	$query->where('cd.property_id', $property_id);

		// if( $job_role_id > 0 )		
		// {
		// 	$query->where('cu.job_role_id', $job_role_id);

		// 	// check job role's department 
		// 	$job_role = DB::table('common_job_role as jr')
		// 	    ->where('jr.id', $job_role_id )
		// 	    ->first();

		// 	if( !empty($job_role) && $job_role->dept_id < 1 )	// job role is relate to multiple department
		// 		$dept_id = 0;    // ignore department	
		// }

		// if( $dept_id > 0 )
		// {
		// 	$query->where('cu.dept_id', $dept_id);		
		// }

		// if( $user_id > 0 )
		// {
		// 	$query->where('sgm.delegated_user_id', $user_id);
		// }

		// if( $usergroup_id > 0 )
		// {
		// 	$query->join('common_user_group_members as ugm', 'ugm.user_id', '=', 'cu.id')
		// 		->where('ugm.group_id', $usergroup_id);
		// }

		// // $query->where('sgm.vaca_start_date', '<=', $date)
		// // 		->where('sgm.vaca_end_date', '>=', $date);
		
		// $userlist = $query->select(DB::raw('sgm.*, cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
		// 		->get();

		return $ids;
		}
		else return []	;	
	}

	public static function getUserlistforGuestserviceEscalation($property_id, $job_role_id, $dept_id) {
		$query = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.deleted', 0);
				
		if( $property_id > 0 )		
			$query->where('cd.property_id', $property_id);

		if( $job_role_id > 0 )		
		{
			$query->where('cu.job_role_id', $job_role_id);
		}
		
		$userlist = $query->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, 1 as duty'))
				->orderBy('cu.active_status', 'desc')
				->get();

		return $userlist;		
	}
}