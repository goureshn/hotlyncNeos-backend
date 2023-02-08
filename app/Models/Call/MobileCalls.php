<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class MobileCalls extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_mobile_calls';
	public 		$timestamps = false;
}