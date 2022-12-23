<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class CompensationItem extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_compensation';
	public 		$timestamps = false;
}