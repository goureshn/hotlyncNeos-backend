<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_shifts';
	public 		$timestamps = false;
}