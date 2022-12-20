<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ShiftGroup extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_shift_group';
	public 		$timestamps = false;

	public static function getShiftGroup($dept_id, $job_role_id, $cur_time)
	{
		$time = date('H:i:s', strtotime($cur_time));

		$query = DB::table('services_shift_group as sg')
					->join('services_shifts as sh', 'sg.shift', '=', 'sh.id')
					->whereRaw("(('$time' BETWEEN start_time AND end_time AND start_time < end_time) 
							OR ('$time' NOT BETWEEN end_time AND start_time AND end_time < start_time))");				

		if( $dept_id > 0 )
			$query->where('sg.dept_id', $dept_id);

		if( $job_role_id > 0 )
		{
			$query->where(function ($subquery) use ($job_role_id) {
					$subquery->where('sg.job_role_ids', '[' . $job_role_id . ']')
							->orWhere('sg.job_role_ids', 'like', '[' . $job_role_id . ',%')
							->orWhere('sg.job_role_ids', 'like', '%,' . $job_role_id . ']')
							->orWhere('sg.job_role_ids', 'like', '%,' . $job_role_id . ',%')
							->orWhere('sg.job_role_ids', '0');
				});
		}

		$shift_group = $query
						->select(DB::raw('sg.*, sh.start_time, sh.end_time'))
						->first();

		return $shift_group;				
	}
}