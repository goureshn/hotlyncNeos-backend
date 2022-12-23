<?php

namespace App\Models\Common;
use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use DB;

class CronLogs extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_cron_logs';
	public 		$timestamps = false;

	static function checkDuplicates($time, $cron_data) {
		$end_time = new DateTime($time);
		$end_time->sub(new DateInterval('PT1M'));
		$end_time = $end_time->format('Y-m-d H:i:s');

		$data = DB::table('common_cron_logs as cl')
				->where('cl.details', $cron_data)
				->whereBetween('cl.start_date_time', array($end_time,$time))
				->get();

		if( !empty($data) )
			return false;

        $new_cron = new CronLogs();

		$new_cron->start_date_time = $time;
		$new_cron->details = $cron_data;
        $new_cron->send_flag='1';
        $new_cron->save();

		return true;
	}
}
