<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Models\Common\Department;
use App\Models\Common\CommonJobrole;
use App\Models\Common\Property;
use DB;
use Redis;

class CommonUser extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_users';
	public 		$timestamps = false;
	protected $hidden = [
			'password'
	];

	public function department()
    {
		return $this->belongsTo(Department::class, 'dept_id');
    }

   	public function job_role()
    {
		return $this->belongsTo(CommonJobrole::class, 'job_role_id');
    }

	public static function getWholeName($id) {
		$user = DB::table('common_users as cu')
			->where('cu.id', $id)
			->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->first();
		if(!empty($user)) return $user->wholename;
		else return false;
	}

	public static function getUserName($username) {
		$users = DB::table('common_users as cu')
			->where('cu.username', $username)
			->select(DB::raw('cu.*'))
			->first();
		if(!empty($users)) return true;
		else return false;
	}

	public static function getPin($login_pin) {
		$users = DB::table('common_users as cu')
			->where('cu.login_pin', $login_pin)
			->select(DB::raw('cu.*'))
			->first();
		if(!empty($users)) 
			return true;
		else 
			return false;
	}

	public static function getIVRPassword($ivr_password, $user_id = "0") {

		$ivr = DB::table('common_users as cu')
			->where('cu.ivr_password', $ivr_password)
			->where('cu.id','!=',$user_id)
			->select(DB::raw('cu.*'))
			->first();
		if(!empty($ivr)) {
			if($ivr->ivr_password == '0'){
				return false;
			}
			else{
				$ivrDuplicate = DB::table('common_users as cu')
				->where('cu.ivr_password', $ivr_password)
				->where('cu.id','!=',$user_id)
				->select(DB::raw('cu.*'))
				->get();
				
				if(count($ivrDuplicate) >= 1){
					return true;
				}
				else{					
					return false;
				}
				
			}
		}
		else {
			return false;
		}
	}
	public static function getUserList($property_id, $dept_id) {
		$users = DB::table('common_users as cu')
		        ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
		        ->where('cd.property_id', $property_id)
			    ->where('cu.dept_id', $dept_id)
				->select(DB::raw('cu.*'))				
				->get();

		// if(!empty($users)) 
			return $users;
		// else 
		// 	return false;
	}

	public static function getUserListByEmail($property_id, $dept_id) {
		$users = DB::table('common_users as cu')
		        ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
		        ->where('cd.property_id', $property_id)
				->where('cu.dept_id', $dept_id)
				->groupBy('cu.email')
				->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))				
				->get();

		return $users;		
	}

	public static function getUserListByEmailFromUserGroup($user_group_ids) {
		if( empty($user_group_ids) )
			return [];

		$user_list = DB::table('common_users as cu')
            ->join('common_user_group_members as ugm', 'cu.id', '=', 'ugm.user_id')
            ->whereRaw("ugm.group_id IN ($user_group_ids)")
            ->select(DB::raw('cu.*'))
            ->groupBy('cu.email')
			->get();
			
		return $user_list;	
	}

	public static function getUserListNamesByEmailFromUserGroup($user_group_ids) {
		if( empty($user_group_ids) )
			return [];

		$user_list = DB::table('common_users as cu')
            ->join('common_user_group_members as ugm', 'cu.id', '=', 'ugm.user_id')
            ->whereRaw("ugm.group_id IN ($user_group_ids)")
            ->select(DB::raw('cu.*'))
         //   ->groupBy('cu.email')
			->get();
			
		return $user_list;	
	}

	public static function getCategoryUserListByEmailFromUserGroup($category) {

		if( empty($category) )
			return [];

		$user_list = DB::table('common_users as cu')
			->join('common_user_group_members as ugm', 'cu.id', '=', 'ugm.user_id')
			->join('eng_request_category as ec', 'ugm.group_id', '=', 'ec.usergroup')
			->where('ec.id', $category)
            ->select(DB::raw('cu.*'))
            ->groupBy('cu.email')
			->get();
			
		return $user_list;	
	}

	public static function addNotifyCount($property_id, $permission_name) {
		$users = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->join('common_permission_members as pm', 'pm.perm_group_id', '=', 'jr.permission_group_id')
				->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
				->where('pr.name', $permission_name)
				->where('cd.property_id', $property_id)
				->select(DB::raw('cu.id'))
				->get();


		if( !empty($users) )
		{
			$count = 0;
			$ids = [];
			foreach($users as $row) {
				$ids[] = $row->id;				
			}

			$data = ['unread' => 'unread + 1'];		
			DB::table('common_users')
			    ->whereIn('id', $ids)
				->update($data);
		}

	}

	public static function getPropertyID($id) 
	{		
		$user = DB::table('common_users as cu')				
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.id', $id)
				->select(DB::raw('cu.id, cd.property_id'))
				->first();

		if(empty($user) )
			return 0;		

		return $user->property_id;		
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
	public static function getDeptIDfromMobile($id, $permission) {
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


		return $user->dept_id;
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

	public static function getPropertyIdsByJobroleids($id) {
		$user = DB::table('common_users as cu')				
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.id', $id)
				->select(DB::raw('cu.*, cd.property_id'))
				->first();

		if(empty($user) )
			return array();		

		$list = DB::table('common_property_jobrole_pivot')
			->where('job_role_id', $user->job_role_id)
		//	->where('property_id', '!=', $user->property_id)
			->select(DB::raw('property_id'))
			->get();

		$ids = [];
		foreach($list as $row)
			$ids[] = $row->property_id;

		return $ids;
	}

	public static function getDeptIdsByJobrole($id) {
		$user = DB::table('common_users as cu')				
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.id', $id)
				->select(DB::raw('cu.*'))
				->first();

		if(empty($user) )
			return array();		

		$list = DB::table('services_complaint_subcomplaint_jobrole_dept_pivot')
			->where('job_role_id', $user->job_role_id)
			->where('dept_id', '!=', $user->dept_id)
			->select(DB::raw('dept_id'))
			->get();

		$ids = [$user->dept_id];
		foreach($list as $row)
			$ids[] = $row->dept_id;

		return $ids;
	}

	public static function getProertyIdsByClient($client_id) {
		return Property::where('client_id', $client_id)
		    ->select(DB::raw('id'))->get()->lists('id');
	}

	public static function getBuildingIds($id) {
		$user = DB::table('common_users as cu')				
				->where('cu.id', $id)
				->select(DB::raw('cu.building_ids'))
				->first();
	
		if(empty($user) )
			return 0;		
	
		return $user->building_ids;		
	}

	public function save(array $options = array())
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date('Y-m-d H:i:s');

	    $changed = $this->isDirty() ? $this->getDirty() : false;

	    $agent_id = Redis::get('agent_id_' . $this->id);

	    // Do stuff here
	    if($changed)
	    {
	        foreach($changed as $field => $attr)
	        {
	        	if( $field == 'deleted' || $field == 'lock' || $field == 'picture' || $field == 'access_token' || $field == 'active_status' || $field == 'deleted_comment')
	        		continue;
	        	
	        	$olddata = $this->getOriginal($field);
	        	$message = $field . ': ' . $olddata . ' to ' . $attr;
	        	if( $field == 'password' )
	        		$message = 'Password is changed';

	            DB::table('common_user_transaction')
				->insert(['user_id' => $this->id, 'action' => 'update', 'detail' => $message, 'created_at' => $cur_time,'agent_id' => $agent_id]);
	        }
	    }

	    // before save code
	    parent::save();

	}
}

