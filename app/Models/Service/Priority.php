<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class Priority extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_priority';
	public 		$timestamps = false;
}