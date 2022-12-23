<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;

class ComplaintBriefingRoom extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_briefing_room_list';
	public 		$timestamps = true;
}