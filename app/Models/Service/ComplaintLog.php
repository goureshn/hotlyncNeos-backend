<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class ComplaintLog extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_log';
	public 		$timestamps = false;
}