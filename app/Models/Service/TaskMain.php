<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

use DB;

class TaskMain extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_task_list_main';
	public 		$timestamps = false;
	
	// public function taskgroup()
    // {
	// 	return $this->belongsToMany('App\Models\Service\TaskGroup', 'services_task_group_members', 'task_list_id', 'task_grp_id' );		        
	// }

	public function tasklist()
    {
		$ids = $this->task_ids;
		$list = DB::table('services_task_list')
			->whereRaw("FIND_IN_SET(id, '$ids')")			
			->list();

		return $list;	
    }
}