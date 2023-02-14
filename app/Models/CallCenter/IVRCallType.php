<?php

namespace App\Models\CallCenter;

use Illuminate\Database\Eloquent\Model;

class IVRCallType extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'ivr_call_types';
	public 		$timestamps = false;
}