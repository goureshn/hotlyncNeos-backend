<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class ComplaintFlag extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_flag';
	public 		$timestamps = false;
}