<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

use App\Models\Service\ComplaintRequest;
use App\Modules\Functions;
use DateInterval;
use DateTime;
use DB;

class ComplaintMainState extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'services_complaint_main_state';
    public 		$timestamps = false;

    public static function initState($complaint_id)
    {
        date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

        $complaint = ComplaintRequest::find($complaint_id);
        if( empty($complaint) )
            return null;

        $complaint->escalation_flag = 0;			
        $complaint->timeout_flag = 0;		
        $complaint->save();    

        $state = ComplaintMainState::where('complaint_id', $complaint_id)
                        ->first();
        if( empty($state) )
            $state = new ComplaintMainState();

        $state->complaint_id = $complaint->id;
        $state->dispatcher = $complaint->requestor_id;
        $state->attendant = 0;
        $state->level = 0;

        // calculate end time
        $escalation = DB::table('services_complaint_status')
            ->where('status', $complaint->status)
            ->first();

        if( empty($escalation) )    
            return null;

        if( $escalation->max_time > 0 )
        {
            $state->setStartEndTime($escalation->max_time, $cur_time); 
            $state->save();
        }

        return $state;
    }

    public static function deleteState($complaint_id)
    {
        $state = ComplaintMainState::where('complaint_id', $complaint_id)
                        ->first();
        if( !empty($state) )
            $state->delete();
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
