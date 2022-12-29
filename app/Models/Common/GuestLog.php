<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use DB;

class GuestLog extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_guest_facility_log';
	public 		$timestamps = false;
	
	public static function activeList($guest_id) {
		//$active_list = array();
		
			$active_list = DB::table('common_guest_facility_log as cgf')
			->select(DB::raw('cgf.location'))
			->where('cgf.guest_id',$guest_id)
			->where('cgf.exit_time',NULL)
			->get();
			

		 
		return $active_list;
	}
}