<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class TaskGroup extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_task_group';
	public 		$timestamps = false;
	
	public function tasklist()
    {
		return $this->belongsToMany(TaskList::class, 'services_task_group_members', 'task_grp_id', 'task_list_id' );		        
    }
}