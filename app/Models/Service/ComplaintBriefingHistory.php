<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
use Redis;

class ComplaintBriefingHistory extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_briefing_history';
	public 		$timestamps = false;

	public static function addParticipant($property_id, $user_id) {
		$history = ComplaintBriefingHistory::where('property_id', $property_id)
			->orderBy('start_time', 'desc')
			->first();

		if( empty($history) )
			return;

		$participants = explode(',', $history->participants);
		if( empty($participants) || $participants[0] == '' )
			$participants = [];

		if( in_array($user_id, $participants) )
			return;

		$user = DB::table('common_users as cu')
			->where('cu.id', $user_id)
			->first();

		if( empty($user) )
			return;	

		$participants[] = $user_id;

		$history->participants = implode(',', $participants);

		$history->save();		

		// send new participant
		$message = array();
		$message['type'] = 'complaint';			

		$data = array();
		$data['property_id'] = $property_id;
		$data['sub_type'] = 'participant_added';
		$data['message'] = $user->first_name . ' ' . $user->first_name . ' has joined briefing.';
		$data['participant_list'] = ComplaintBriefingHistory::getParticipantsList($property_id);

		$message['data'] = $data;

		Redis::publish('notify', json_encode($message));	
	}

	public static function endBriefing($property_id) {
		$history = ComplaintBriefingHistory::where('property_id', $property_id)
			->orderBy('start_time', 'desc')
			->first();

		if( empty($history) )
			return;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$history->end_time = $cur_time;

		$history->save();		
	}

	public static function getParticipantsList($property_id) {
		$history = ComplaintBriefingHistory::where('property_id', $property_id)
			->orderBy('start_time', 'desc')
			->first();

		if( empty($history) )
			return array();

		$participants = explode(',', $history->participants);
		if( empty($participants) || $participants[0] == '' )
			$participants = [];

		$list = DB::table('common_users as cu')
			->whereIn('cu.id', $participants)
			->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();

		return $list;	
	}
}