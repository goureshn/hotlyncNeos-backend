<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class AlarmMember extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_alarm_members';
	public 		$timestamps = false;
}