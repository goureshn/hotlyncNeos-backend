<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class AlarmGroup extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_alarm_groups';
	public 		$timestamps = false;
}