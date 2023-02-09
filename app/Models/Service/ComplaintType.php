<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class ComplaintType extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_type';
	public 		$timestamps = false;
}