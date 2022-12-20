<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class TaskList extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_task_list';
	public 		$timestamps = false;
	
	public function taskgroup()
    {
		return $this->belongsToMany(TaskGroup::class, 'services_task_group_members', 'task_list_id', 'task_grp_id' );		        
	}
	
	public static function getMaxTime($task_id)
	{
		
	}
}