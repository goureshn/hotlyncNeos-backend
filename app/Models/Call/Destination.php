<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class Destination extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_destination';
	public 		$timestamps = false;
}