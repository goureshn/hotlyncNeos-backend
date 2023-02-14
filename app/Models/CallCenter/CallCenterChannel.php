<?php

namespace App\Models\CallCenter;

use Illuminate\Database\Eloquent\Model;

class CallCenterChannel extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'ivr_channels';
	public 		$timestamps = false;
}