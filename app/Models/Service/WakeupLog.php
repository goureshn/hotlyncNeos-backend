<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class WakeupLog extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_awu_logs';
	public 		$timestamps = false;
}