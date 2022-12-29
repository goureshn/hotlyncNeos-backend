<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class Whitelist extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_whitelist';
	public 		$timestamps = false;
}