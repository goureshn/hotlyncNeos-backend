<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;

class ComplaintBriefing extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_briefing';
	public 		$timestamps = false;
}