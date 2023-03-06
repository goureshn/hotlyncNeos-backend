<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Escalation extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_escalation';
	public 		$timestamps = false;

	public static function getSecondaryJobRoles1($dept_id) {
		$dept = DB::table('common_department')
			->where('id', $dept_id)
			->first();

		if( empty($dept) || $dept->dynamic_job_role == 0 )	
			return array();
		
		// get primary job roles which is using in department
		$online_job_roles = DB::table('common_users as cu')
			->where('cu.dept_id', $dept_id)	// same department
			->where('cu.active_status', 1)	// online user
			->groupBy('cu.job_role_id')
			->select(DB::raw('cu.id, cu.job_role_id'))
			->get();

		$online_job_role_ids = [];
		$online_user_ids = [];
		foreach($online_job_roles as $row)
		{
			$online_job_role_ids[] = $row->job_role_id;
			$online_user_ids[] = $row->id;
		}

		// get secondary job roles which is using now
		$online_secondary_job_roles = DB::table('services_secondary_jobrole as sjr')
			->whereIn('user_id', $online_user_ids)
			->groupBy('sjr.job_role_id')
			->select(DB::raw('sjr.job_role_id'))
			->get();

		foreach($online_secondary_job_roles as $row)
		{
			$online_job_role_ids[] = $row->job_role_id;			
		}
			

		// get candidate job roles in same department with level_0
		$escalation_group_list = DB::table('services_task_group as stg')
				->join('services_dept_function as sdf', 'stg.dept_function', '=', 'sdf.id')
				->where('sdf.dept_id', $dept_id)
				->groupBy('stg.dept_function')
				->select(DB::raw('stg.dept_function as escalation_group'))
				->get();

		$escalation_group_ids = [];
		foreach($escalation_group_list as $row)
			$escalation_group_ids[] = $row->escalation_group;		

		// find job role list with level_0 and belong to escalation group list
		$escalation_list = DB::table('services_escalation')
			->whereIn('escalation_group', $escalation_group_ids)
			->where('level', 0)
			->get();

		$candidate_job_role_ids = [];
		foreach($escalation_list as $row)
			$candidate_job_role_ids[] = $row->job_role_id;

		$query = DB::table('common_job_role as jr');

		if( count($candidate_job_role_ids) > 0 )		
			$query->whereIn('jr.id', $candidate_job_role_ids);

		if( count($online_job_role_ids) > 0 )		
			$query->whereNotIn('jr.id', $online_job_role_ids);

		$secondary_job_roles = $query->get();

		return $secondary_job_roles;	
	}

	public static function getSecondaryJobRoles($dept_id, $user_id) {
		$dept = DB::table('common_department')
			->where('id', $dept_id)
			->first();

		if( empty($dept) || $dept->dynamic_job_role == 0 )	
			return array();

		$user_sec_deptfunc = DB::table('services_secondary_jobrole')
            ->where('user_id', $user_id)
            ->get();

		$sec_dept_func_ids = [];
		foreach($user_sec_deptfunc as $row){

			$sec_dept_func_ids[] = $row->job_role_id; 
		}
		
		$dept_func = DB::table('services_dept_function')
			->where('dept_id', $dept_id);
		
		if (count($sec_dept_func_ids) > 0)
			$dept_func->whereNotIn('id',$sec_dept_func_ids );


		$dept_func = $dept_func->get();

		$dept_func_ids = [];
		foreach($dept_func as $row){

			$dept_func_ids[] = $row->id; 
		}

		$select_dept_func = [];

		foreach($dept_func as $data){

		$users = DB::table('common_users as cu')
			->join('services_devices as sd', 'sd.device_id', '=', 'cu.device_id')			
		//	->where('cu.id','!=', $user_id)		
			->where('cu.dept_id', $dept_id)			
			->where('cu.deleted', 0)
			->where('cu.mobile_login', 1)
			->select(DB::raw('cu.*'));

		$data_query = clone $users;

		$data_query->whereRaw('FIND_IN_SET('.$data->id.', sd.dept_func_array_id)');
		$userlist = $data_query->get();

		// if( count($userlist) == 0 )
		// {
		// 	$data_query = clone $users;
		// 	$data_query->whereRaw('FIND_IN_SET('.$data->id.', sd.sec_dept_func)');	
		// 	$userlist = $data_query->get();
		// }
		if( count($userlist) == 0 )
			$select_dept_func[] = $data->id;

		}
		


	
		$query = DB::table('services_dept_function as sdf')
				->where('sdf.dept_id', $dept_id);

		if( count($select_dept_func) > 0 )		
			$query->whereIn('sdf.id', $select_dept_func);

		$secondary_job_roles = $query->select(DB::raw('sdf.*, sdf.function as job_role'))->get();

		return $secondary_job_roles;
		
	}

	public static function isLevel0($property_id, $job_role_id) {
		// get candidate job roles in property with level_0
		$escalation_group_list = DB::table('services_task_group as stg')
				->join('services_dept_function as sdf', 'stg.dept_function', '=', 'sdf.id')
				->join('common_department as cd', 'sdf.dept_id', '=', 'cd.id')
				->where('cd.property_id', $property_id)
				->groupBy('stg.dept_function')
				->select(DB::raw('stg.dept_function as escalation_group'))
				->get();

		$escalation_group_ids = [];
		foreach($escalation_group_list as $row)
			$escalation_group_ids[] = $row->escalation_group;		

		// find job role list with level_0 and belong to escalation group list
		$exist = DB::table('services_escalation')
			->whereIn('escalation_group', $escalation_group_ids)
			->where('job_role_id', $job_role_id)
			->where('level', 0)
			->exists();

		return $exist;	
	}

	public static function getEscalationJobroles($property_id) {
		// get candidate job roles in same department with level_0
		$escalation_group_list = DB::table('services_task_group as stg')
				->join('services_dept_function as sdf', 'stg.dept_function', '=', 'sdf.id')
				->join('common_department as cd', 'sdf.dept_id', '=', 'cd.id')
				->where('cd.property_id', $property_id)
				->groupBy('stg.dept_function')
				->select(DB::raw('stg.dept_function as escalation_group'))
				->get();

		$escalation_group_ids = [];
		foreach($escalation_group_list as $row)
			$escalation_group_ids[] = $row->escalation_group;		

		// find job role list with level_0 and belong to escalation group list
		$escalation_list = DB::table('services_escalation')
			->whereIn('escalation_group', $escalation_group_ids)
			->where('level', '>', 0)
			->get();

		$candidate_job_role_ids = [];
		foreach($escalation_list as $row)
			$candidate_job_role_ids[] = $row->job_role_id;	

		return $candidate_job_role_ids;
	}
}