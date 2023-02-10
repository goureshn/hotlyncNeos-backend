<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class Carrier extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_carrier';
	public 		$timestamps = false;
}