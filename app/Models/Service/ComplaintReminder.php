<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class ComplaintReminder extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_reminder';
	public 		$timestamps = false;
}