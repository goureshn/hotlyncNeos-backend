<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class Allowance extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_allowance';
	public 		$timestamps = false;
}