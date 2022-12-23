<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class CompensationRequest extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_compensation_request';
	public 		$timestamps = true;
}