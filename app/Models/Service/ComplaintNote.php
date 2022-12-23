<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class ComplaintNote extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_note';
	public 		$timestamps = false;
}