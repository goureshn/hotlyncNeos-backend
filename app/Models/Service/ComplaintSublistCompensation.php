<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;

class ComplaintSublistCompensation extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_sublist_compensation';
	public 		$timestamps = true;
}