<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommonUser extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_users';
	public 		$timestamps = false;
	protected $hidden = [
			'password'
	];

	public static function getPropertyIdsByJobrole($id) {
		$user = DB::table('common_users as cu')				
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.id', $id)
				->select(DB::raw('cu.*, cd.property_id'))
				->first();

		if(empty($user) )
			return array();		

		$list = DB::table('common_property_jobrole_pivot')
			->where('job_role_id', $user->job_role_id)
			->where('property_id', '!=', $user->property_id)
			->select(DB::raw('property_id'))
			->get();

		$ids = [$user->property_id];
		foreach($list as $row)
			$ids[] = $row->property_id;

		return $ids;
	}

	public static function isValidModule($user_id, $permission) {
		if( empty($permission) )
			return true;

		return DB::table('common_users as cu')				
				->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')				
				->join('common_permission_members as pm', 'jr.permission_group_id', '=', 'pm.perm_group_id')
				->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')				
				->where('cu.id', $user_id)
				->where('cu.deleted',0)				
				->where('pr.name', $permission)				
				->exists();
	}

	public static function getDeptID($id, $permission) {
		if(empty($permission) )
			return 0;

		$user = DB::table('common_users as cu')				
				->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->join('common_perm_group as pg', 'jr.permission_group_id', '=', 'pg.id')
				->where('cu.id', $id)
				->select(DB::raw('cu.dept_id, jr.permission_group_id, jr.job_role, jr.manager_flag'))
				->first();

		if(empty($user) )
			return 0;		

		$valid = DB::table('common_permission_members as pm')
				->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
				->where('pm.perm_group_id', $user->permission_group_id)
				->where('pr.name', $permission)				
				->exists();

		if( $valid == false )
			return 0;

		return $user->dept_id;		
	}
}

