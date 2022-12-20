<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CommonUserNotification extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'common_users_notification';
    public 		$timestamps = false;

    public static function addComplaintNotifyCount($user_id, $dept_id) {
		$query = DB::table('common_users as cu')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->where('cu.id', $user_id);

		if( $dept_id > 0 )			
			$query->orWhereRaw(sprintf('(jr.manager_flag = 1 and cu.dept_id = %d)', $dept_id));

		$user_list = $query->select(DB::raw('cu.id'))
			->get();	

		foreach($user_list as $row)
			CommonUserNotification::setNotifyCount($row->id, 'complaint_cnt', -1);
	}

	public static function addComplaintNotifyCountWithJobRole($property_id, $job_role_id) {
		$query = DB::table('common_users as cu')			
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->where('cu.job_role_id', $job_role_id)
			->where('cd.property_id', $property_id);

		$user_list = $query->select(DB::raw('cu.id'))
			->get();	

		foreach($user_list as $row)
			CommonUserNotification::setNotifyCount($row->id, 'complaint_cnt', -1);
	}

	public static function setNotifyCount($user_id, $field, $cnt) {
		$notify = CommonUserNotification::find($user_id);
		if( empty($notify) )
			$notify = new CommonUserNotification();

		$notify->id = $user_id;
		if( $cnt < 0 )
			$notify->$field++;
		else	
			$notify->$field = $cnt;

		$notify->save();

		$message = array();
		$message['type'] = 'mytask_notify';
		$message['user_id'] = $user_id;
		$message['content'] = $notify;

		Redis::publish('notify', json_encode($message));			
	}
}