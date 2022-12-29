<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class AdminCall extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_admin_calls';
	public 		$timestamps = false;
}