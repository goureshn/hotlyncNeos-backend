<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DateInterval;
use DateTime;
use App\Models\Service\Escalation;
use App\Modules\Functions;
use Log;

class TaskState extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_task_state';
	public 		$timestamps = false;

	public function getElapseTime() {
		$timezone = config('app.timezone');
		date_default_timezone_set($timezone);
		$cur_time = date("Y-m-d H:i:s");

		$elapsed = strtotime($cur_time) - strtotime($this->start_time);
		return $this->elaspse_time + $elapsed; 
	}

	public function setStartEndTime($max_time, $start_time) {
		if( $this->status_id == 2 && $this->level > 0 )	// escalated
		{
			$escalation = Escalation::where('escalation_group', $this->escalation_group_id)
					->where('level', $this->level)->select('max_time')->first();
			if( empty($escalation) )
				return;
			$max_time = $escalation->max_time;
		}


		$this->start_time = $start_time;

		// calculate end time based on escalated time
		$diff = $max_time - $this->elaspse_time;

		if( $diff < 0 )
			$diff = 0;

		// calc max time for xx:xx:00
		$diff = Functions::calcDurationForMinute($start_time, $diff);

		$end_time = new DateTime($start_time);
		//if($this->dispatcher > 0 )		// only assigned
			$end_time->add(new DateInterval('PT' . $diff . 'S'));
		//else
			//$end_time->sub(new DateInterval('PT1S'));

		$this->end_time = $end_time->format('Y-m-d H:i:s');		
	}
	public function setStartEndTimewithExtra($max_time, $start_time,$extra) {
		if( $this->status_id == 2 && $this->level > 0 )	// escalated
		{
			$escalation = Escalation::where('escalation_group', $this->escalation_group_id)
					->where('level', $this->level)->select('max_time')->first();
			if( empty($escalation) )
				return;
			$max_time = $escalation->max_time;
			
		}
		$max_time=$max_time+$extra;


		$this->start_time = $start_time;

		// calculate end time based on escalated time
		$diff = $max_time - $this->elaspse_time;

		if( $diff < 0 )
			$diff = 0;

		// calc max time for xx:xx:00
		$diff = Functions::calcDurationForMinute($start_time, $diff);

		$end_time = new DateTime($start_time);
		//if($this->dispatcher > 0 )		// only assigned
			$end_time->add(new DateInterval('PT' . $diff . 'S'));
		//else
			//$end_time->sub(new DateInterval('PT1S'));

		$this->end_time = $end_time->format('Y-m-d H:i:s');		
	}
}
