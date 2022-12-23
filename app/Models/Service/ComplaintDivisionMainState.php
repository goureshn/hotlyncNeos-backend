<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

use App\Models\Service\ComplaintRequest;
use App\Models\Service\ComplaintMainCategory;
use App\Modules\Functions;
use DateInterval;
use DateTime;
use DB;

class ComplaintDivisionMainState extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'services_complaint_division_main_state';
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

        $category = ComplaintMainCategory::find($complaint->category_id);
        if( empty($category) )
            return null;

        $state = ComplaintDivisionMainState::where('complaint_id', $complaint_id)
                        ->first();
        if( empty($state) )
            $state = new ComplaintDivisionMainState();

        $state->complaint_id = $complaint->id;
        $state->dispatcher = $complaint->requestor_id;
        $state->attendant = 0;
        $state->level = 0;

        // calculate end time
        $escalation = DB::table('services_complaint_division_escalation')
            ->where('division_id', $category->division_id)
            ->where('severity_id', $complaint->severity)
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
        $state = ComplaintDivisionMainState::where('complaint_id', $complaint_id)
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
