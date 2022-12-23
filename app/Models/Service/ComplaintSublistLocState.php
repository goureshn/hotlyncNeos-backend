<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Models\Service\ComplaintSublist;
use App\Models\Service\Location;
use App\Modules\Functions;
use DateInterval;
use DateTime;

class ComplaintSublistLocState extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'services_complaint_sublist_loc_state';
	public 		$timestamps = false;
    
    public static function initState($sub_id, $level)
    {
        date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

        $sub = ComplaintSublist::find($sub_id);
        if( empty($sub) )
            return null;

        $loc = Location::find($sub->location_id);
        if( empty($loc) )
            return null;

        $sub_state = new ComplaintSublistLocState();

        $sub_state->sub_id = $sub->id;
        $sub_state->dispatcher = $sub->assignee_id;
        $sub_state->attendant = $sub->submitter_id;
        $sub_state->level = $level;

        $type_id = $loc->type_id;
      
        // calculate end time
        $escalation = DB::table('services_complaint_sublist_loc_escalation')
            ->where('type_id', $type_id)
            ->where('severity_id', $sub->severity)
            ->where('level', $level)            
            ->first();
      
        if( empty($escalation) )    
            return null;

        if( $escalation->max_time > 0 )
        {        
            $sub_state->setStartEndTime($escalation->max_time, $cur_time); 
            $sub_state->save();
        }

        return $sub_state;
    }

    public function setStartEndTime($max_time, $start_time) {
		$this->start_time = $start_time;

		// calculate end time based on escalated time
		$diff = $max_time - $this->elaspse_time;

		// calc max time for xx:xx:00
		$diff = Functions::calcDurationForMinute($start_time, $diff);

		$end_time = new DateTime($start_time);
        $end_time->add(new DateInterval('PT' . $diff . 'S'));
		
		$this->end_time = $end_time->format('Y-m-d H:i:s');		
	}
}