<?php

namespace App\Models\IVR;

use Illuminate\Database\Eloquent\Model;

class IVRCallQueue extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'ivr_recording_queue';
	public 		$timestamps = false;

}