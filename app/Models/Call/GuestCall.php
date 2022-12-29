<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class GuestCall extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_guest_call';
	public 		$timestamps = false;
}