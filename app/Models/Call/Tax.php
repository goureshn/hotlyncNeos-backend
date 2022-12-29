<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_tax';
	public 		$timestamps = false;
}