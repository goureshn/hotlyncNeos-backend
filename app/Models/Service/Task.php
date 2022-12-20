<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class Task extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_task';
	public 		$timestamps = false;
}