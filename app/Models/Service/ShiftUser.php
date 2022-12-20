<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Modules\Functions;
use App\Models\Service\LocationGroupMember;

class ShiftUser extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_shift_users';
	public 		$timestamps = false;

	public static function getUserlistOnCurrentShift($job_role_id, $dept_func_id, $task_group_id, $loc_id, $building_id,  $active_check = false ) {
		$loc_group_array = LocationGroupMember::getLocationGroupIds($loc_id);
		$multi_loc_group = 'CONCAT(",", %s, ",") REGEXP ",(' . implode('|', $loc_group_array) . '),"';

		// find sec job role for level = 0
		$escalation = Escalation::where('escalation_group', $dept_func_id)
				->where('level', 0)
				->first();

		$sec_job_role_id = 0;

		if( !empty($escalation) )
			$sec_job_role_id = $escalation->sec_job_role_id;

		$query = DB::table('common_users as cu')
			->join('services_shift_users as su', 'su.user_id', '=', 'cu.id')
			->whereRaw('(FIND_IN_SET('.$task_group_id.', su.task_group_ids) OR su.task_group_ids IS NULL OR su.task_group_ids = "")')
			->where('cu.job_role_id', $job_role_id)		
			->where('cu.deleted', 0)
			->select(DB::raw('cu.*, su.user_id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'));

		if( $active_check == true )
			$query->where('cu.active_status', 1);
	
	
		if (($query->count()) <= 0){

			
			$query = DB::table('common_users as cu')
			->join('services_shift_users as su', 'su.user_id', '=', 'cu.id')
			->whereRaw('(FIND_IN_SET('.$task_group_id.', su.task_group_ids) OR su.task_group_ids IS NULL OR su.task_group_ids = "")')
			->where('cu.job_role_id', $sec_job_role_id)		
			->where('cu.deleted', 0)
			->select(DB::raw('cu.*, su.user_id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'));

			if( $active_check == true )
				$query->where('cu.active_status', 1);
		}

		//  1.	primary dept function	primary location	
		$data_query = clone $query;
		$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', su.dept_func_ids)')			
			->whereRaw(sprintf($multi_loc_group, 'su.location_group_ids'));
			
		$userlist = $data_query->get();
		
		if( count($userlist) > 0 )
			return $userlist;

		// 2	primary dept function	seconday location	
		$data_query = clone $query;
		$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', su.dept_func_ids)')		
			->whereRaw(sprintf($multi_loc_group, 'su.sec_location_group_ids'));				
			
		$userlist = $data_query->get();
		
		if( count($userlist) > 0 )
			return $userlist;

		// 3	primary dept funation	building
		if( $building_id > 0 )
		{
			$data_query = clone $query;
			$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', su.dept_func_ids)')			
				->whereRaw('FIND_IN_SET('.$building_id.', su.building_ids)');
				
			$userlist = $data_query->get();
			
			if( count($userlist) > 0 )
				return $userlist;	
		}

		//  4.	secondary dept function	primary location	
		$data_query = clone $query;
		$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', su.sec_dept_func_ids)')			
			->whereRaw(sprintf($multi_loc_group, 'su.location_group_ids'));
			
		$userlist = $data_query->get();
		
		if( count($userlist) > 0 )
			return $userlist;

		// 2	secondary dept function	seconday location	
		$data_query = clone $query;
		$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', su.sec_dept_func_ids)')			
			->whereRaw(sprintf($multi_loc_group, 'su.sec_location_group_ids'));
			
		$userlist = $data_query->get();
		
		if( count($userlist) > 0 )
			return $userlist;

		// 3	primary dept funation	building		
		if( $building_id > 0 )
		{
			$data_query = clone $query;
			$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', su.sec_dept_func_ids)')			
				->whereRaw('FIND_IN_SET('.$building_id.', su.building_ids)');
				
			$userlist = $data_query->get();
			
			if( count($userlist) > 0 )
				return $userlist;		
		}
		
		return $userlist;
	}

	public static function getDevicelistOnCurrentShift($job_role_id, $dept_func_id, $loc_id, $building_id,  $active_check = false ) {
		$loc_group_array = LocationGroupMember::getLocationGroupIds($loc_id);
		$multi_loc_group = 'CONCAT(",", %s, ",") REGEXP ",(' . implode('|', $loc_group_array) . '),"';

		$query = DB::table('common_users as cu')
			->join('services_devices as sd', 'sd.device_id', '=', 'cu.device_id')			
			// ->where('cu.job_role_id', $job_role_id)			
			->where('cu.deleted', 0)
			->select(DB::raw('cu.*, cu.id as user_id, cu.mobile, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'));

		if( $active_check == true )
			$query->where('cu.active_status', 1);

		if( $job_role_id > 0 )	
			$query->where('cu.job_role_id', $job_role_id);			

		//  1.	primary dept function	primary location	
		$data_query = clone $query;
		$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', sd.dept_func_array_id)')			
			->whereRaw(sprintf($multi_loc_group, 'sd.loc_grp_array_id'));

			
		$userlist = $data_query->get();
		
		if( count($userlist) > 0 )
			return $userlist;

		// 2	primary dept function	seconday location	
		$data_query = clone $query;
		$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', sd.dept_func_array_id)')			
			->whereRaw(sprintf($multi_loc_group, 'sd.sec_loc_grp_id'));

			
		$userlist = $data_query->get();
		
		if( count($userlist) > 0 )
			return $userlist;

		// 3	primary dept funation	building
		if( $building_id > 0 )
		{
			$data_query = clone $query;
			$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', sd.dept_func_array_id)')			
				->whereRaw('FIND_IN_SET('.$building_id.', sd.building_ids)');
				
			$userlist = $data_query->get();
			
			if( count($userlist) > 0 )
				return $userlist;	
		}

		//  4.	secondary dept function	primary location	
		$data_query = clone $query;
		$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', sd.sec_dept_func)')			
			->whereRaw(sprintf($multi_loc_group, 'sd.loc_grp_array_id'));
			
		$userlist = $data_query->get();
		
		if( count($userlist) > 0 )
			return $userlist;

		// 5. secondary dept function	seconday location	
		$data_query = clone $query;
		$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', sd.sec_dept_func)')			
			->whereRaw(sprintf($multi_loc_group, 'sd.sec_loc_grp_id'));
			
		$userlist = $data_query->get();
		
		if( count($userlist) > 0 )
			return $userlist;

		// 6. secondary dept function	building		
		if( $building_id > 0 )
		{
			$data_query = clone $query;
			$data_query->whereRaw('FIND_IN_SET('.$dept_func_id.', sd.sec_dept_func)')			
				->whereRaw('FIND_IN_SET('.$building_id.', sd.building_ids)');
				
			$userlist = $data_query->get();
			
			if( count($userlist) > 0 )
				return $userlist;		
		}

		return $userlist;
	}
}