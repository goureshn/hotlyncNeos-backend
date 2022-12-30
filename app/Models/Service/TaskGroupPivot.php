<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class TaskGroupPivot extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_task_group_members';
	public 		$timestamps = false;
}