<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class Csvdata extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_csv_data';
	public 		$timestamps = false;
}