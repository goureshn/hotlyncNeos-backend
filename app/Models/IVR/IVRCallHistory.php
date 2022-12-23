<?php

namespace App\Models\IVR;

use Illuminate\Database\Eloquent\Model;

class IVRCallHistory extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'ivr_call_history';
	public 		$timestamps = false;

}