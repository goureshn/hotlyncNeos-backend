<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class BCCall extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_bc_calls';
	public 		$timestamps = false;
}