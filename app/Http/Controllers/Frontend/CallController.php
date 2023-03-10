<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Call\CallCenterExtension;
use App\Models\Common\Property;
use App\Models\Common\PropertySetting;
use App\Models\IVR\IVRAgentStatus;
use App\Models\IVR\IVRCallcenterState;
use App\Models\IVR\IVRCallHistory;
use App\Models\IVR\IVRCallProfile;
use App\Models\IVR\IVRCallQueue;
use App\Models\IVR\IVRVoiceRecording;
use App\Modules\Functions;
use App\Models\Common\Room;
use Datatables;
use DateInterval;
use DateTime;
use Excel;
use Maatwebsite\Excel\Classes\PHPExcel;
use DB;
use Illuminate\Http\Request;
use Redis;
use Response;
use App\Models\Call\Destination;
use App\Models\Call\StaffExternal;
use App\Models\Call\GuestExtension;
use App\Models\Call\GroupDestination;
use App\Models\Call\CarrierGroup;
use App\Models\Common\Guest;
use CpChart\Factory\Factory;
use CpChart\Chart\Pie;

define("RINGING", 'Ringing');
define("ABANDONED", 'Abandoned');
define("ANSWERED", 'Answered');

define("MISSED", 'Missed');
define("CALLBACK", 'Callback');
define("FOLLOWUP", 'Modify');
define("HOLD", 'Hold');
define("TRANSFERRED", 'Transferred');
define("DROPPED", 'Dropped');

define("ONLINE", 'Online');
define("AVAILABLE", 'Available');
define("NOTAVAILABLE", 'Not Available');
define("BUSY", 'Busy');
define("ONBREAK", 'On Break');
define("IDLE", 'Idle');
define("WRAPUP", 'Wrapup');
define("OUTGOING", 'Outgoing');
define("LOGOUT", 'Log out');
define("AWAY", 'Away');

class CallController extends Controller
{
	public function generateLockFile(Request $request)
	{
		$lock_file = $_SERVER["DOCUMENT_ROOT"] . '/lock.txt';
		$lock_text = 'Lock File';
		file_put_contents($lock_file, $lock_text);

		return $lock_file . " is generated for locking.";
	}

	public function getAgentStatus(Request $request)
	{
		$agent_id = $request->get('agent_id', 0);

		$agent_status = $this->getAgentStatusData($agent_id);

		if( !empty($agent_status) )
		{
			$ticket = DB::table('ivr_voice_recording')
				->where('id', $agent_status->ticket_id)
				->orderBy('id', 'desc')
				->first();
		
			if( !empty($ticket) )
			{
				$agent_status->ticket = $ticket;
				$caller = $this->getCallerProfile($ticket);

				$agent_status->caller = $caller;
			}
			else
			{
				$agent_status->ticket = array('id' => 0);
				$agent_status->caller = array('id' => 0);
			}

			$rules = array();
			$rules['sip_server'] = 'developdxb.myhotlync.com';
			$rules = PropertySetting::getPropertySettings($agent_status->property_id, $rules);
			$agent_status->sip_server = $rules['sip_server'];
		}

		return Response::json($agent_status);
	}

	public function getAgentStatusData($agent_id)
	{
		$agent_status = DB::table('ivr_agent_status_log as asl')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('asl.user_id', $agent_id)
				->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cd.property_id'))
				->first();

		if( empty($agent_status) )
			return array();
		
		$ticket = DB::table('ivr_voice_recording')
			->where('id', $agent_status->ticket_id)
			->orderBy('id', 'desc')
			->first();

		$call_guest = array();
		if( !empty($ticket) )
		{
			$agent_status->ticket = $ticket;
			$adminext = StaffExternal::where('extension', $ticket->callerid)
						->where('bc_flag', 0)
						->where('enable',1)
						->first();
			$guestext = GuestExtension::where('extension', $ticket->callerid)
						->where('enable',1)
						->first();
			
			if (!empty($adminext))
			{
				$admincall = $this->getStaffProfile($adminext);
				$agent_status->origin = 'Internal';
				$agent_status->admincall = $admincall;
				$agent_status->check = 0;
			}
			elseif (!empty($guestext)) 
			{
				
				$call_guest = $this->getGuestProfile($guestext);
				$agent_status->check = 1;
				$agent_status->origin = 'Internal';
				$room =  DB::table('common_room')->where('id',$guestext->room_id)->select('room')->first();
				$agent_status->room = $room->room;
				$agent_status->guestcall = $call_guest;
			}
			else{
				$caller = $this->getCallerProfile($ticket);
				$agent_status->caller = $caller;
				$agent_status->check = 2;
			}
		}
		else
		{
			$agent_status->ticket = array('id' => 0);
			$agent_status->caller = array('id' => 0);
			$agent_status->guestcall = array('id' => 0);
			$agent_status->admincall = array('id' => 0);
		//	$agent_status->check = 1 ;
		}

		if( empty($agent_status) )
			return array();
		else {
			return $agent_status;
		}
	}

	private function getStaffProfile($adminext) {
		$cur_date = date("Y-m-d");

		$admin = DB::table('call_staff_extn as se')
				->join('call_section as cs', 'se.section_id', '=', 'cs.id')
				->join('common_department as cd', 'cs.dept_id', '=', 'cd.id')
				->select(DB::raw('se.*, cd.department'))
				->where('se.extension' , $adminext->extension)
				->first();
				
		
		return $admin;

	}

	private function getGuestProfile($guestext) {
		$cur_date = date("Y-m-d");

		$guest = DB::table('common_guest as cg')
				->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('common_vip_codes as vc', 'vc.vip_code', '=', 'cg.vip')
				->leftJoin('common_guest_advanced_detail as gad', 'cg.id', '=', 'gad.id')
				->where('cg.room_id' , $guestext->room_id)
				->where('cg.checkout_flag', 'checkin')
				->where('cg.departure','>=', $cur_date)
				->select(DB::raw('cg.*, vc.name as vip_code'))
				->first();
				
		
		return $guest;

	}

	public function  getCallerProfile($ticket){
		$caller = IVRCallProfile::where('callerid', $ticket->callerid)
			->first();
		if(!empty($caller) && !empty($caller->national)) {
			return $caller;
		}

		$destination = Destination::find($ticket->call_origin);

		if( empty($caller) )
			$caller = new IVRCallProfile();

		if( $ticket->call_type == 'Internal' )
			$caller->national = 'Internal';
		else{
			if( !empty($destination) )
				$caller->national = $destination->country;
			else
				$caller->national = 'Unknown';
		}

		$caller->mobile = $ticket->callerid;
		$caller->phone = $ticket->callerid;

		if( $caller->id > 0 )
			$caller->save();

		return $caller;
	}

	public function getSkillGroup() {
		$agentlist = DB::table('ivr_call_center_skill_group')
				->select(DB::raw(' id, group_name as label '))
				->get();
		return Response::json($agentlist);
	}

	public function getAALogs(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$filter = $request->get('filter','Total');
		$filters = json_decode($request->get('filters', '[]'));
		//$period = $filters->period;
		$filtername = $filters->filtername;
		$filtervalue = $filters->filtervalue;
		$searchoption = $request->get('searchoption','');

		$start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');

/*
		if($period == 'Custom Days') {
			$start_date = $filters->start_date;
			$end_date = $filters->end_date;
		}
*/

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();
		$query = DB::table('ivr_auto_attendant as vr')
				->where('vr.property_id', $property_id);

		if($start_date != '')
			$query->whereRaw(sprintf("DATE(vr.start_date_time) >= '%s' and DATE(vr.start_date_time) <= '%s'", $start_date, $end_date));

		/////////////////

		if($filtername == 'call_type') {
			if(count($filtervalue) == 0 ) {
				$query->whereRaw("vr.call_type like '0'");
			}else {
				$query->whereIn('vr.call_type', $filtervalue);
			}
		}
		if($filtername == 'type') {
			if(count($filtervalue) == 0) {
				$query->whereRaw("vr.type like '0'");
			}else {
				$query->whereIn('vr.type', $filtervalue);
			}
		}

		if($filtername =='duration') {
			if(count( $filtervalue) > 0 && $filtervalue[0] != 'All'  ) {
				$query->whereRaw(" TIME_TO_SEC(vr.duration) ".$filtervalue[0]." ".$filtervalue[1]." ");
			}
		}

		if($searchoption !='') {
			$wh = sprintf(" (vr.ext like '%%%s%%' or	
							 vr.callerid like '%%%s%%' or								
							 vr.id like '%%%s%%'
							 )",
				$searchoption, $searchoption, $searchoption, $searchoption,$searchoption
			);
			$query->whereRaw($wh);
		}


		$data_query = clone $query;
		$data_list = $data_query
			->orderBy($orderby, $sort)
			->select(DB::raw('vr.*, SEC_TO_TIME(vr.duration) as dur'))
			->skip($skip)->take($pageSize)
			->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		
		return Response::json($ret);
	}
	
	public function getLogs(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$filter = $request->get('filter','Total');
		$filters = json_decode($request->get('filters', '[]'));
		$period = $filters->period;
		$filtername = $filters->filtername;
		$filtervalue = $filters->filtervalue;
		$searchoption = $request->get('searchoption','');
		$dept_id = $request->get('dept_id', 0);

		if($period == 'Custom Days') {
			$start_date = $filters->start_date;
			$end_date = $filters->end_date;
		}

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();
		$query = DB::table('ivr_voice_recording as vr')
				->leftJoin('ivr_agent_status_log as asl', 'vr.user_id', '=', 'asl.user_id')
				->leftJoin('common_users as cu', 'vr.user_id', '=', 'cu.id')
				->leftJoin('ivr_caller_profile as cp', 'vr.callerid', '=', 'cp.callerid')
				->leftJoin('call_destination as dest', 'vr.call_origin', '=', 'dest.id')
				->leftJoin('ivr_call_center_skill_group as isg', 'vr.skill_group_id', '=', 'isg.id')
			//	->where('isg.dept_id', $dept_id)
				->where('vr.property_id', $property_id);

		$skill_group = DB::table('ivr_call_center_skill_group')
				->where('property_id', $property_id)
				->get();
		$skill_dept_list = '';
		foreach($skill_group as $row){
			$skill_dept_list .= $row->dept_id . ',';
		}

		$skill_array = explode(',', $skill_dept_list);
		if(in_array($dept_id, $skill_array))
		{
			$query->where('isg.dept_id', $dept_id);
		}

		/////////////////
		if($filtername == 'agent') {
			if(count($filtervalue) == 0 || in_array(0, $filtervalue)) {
//				$query->whereRaw('cu.id>0');
			}else {
				$query->whereIn('cu.id', $filtervalue);
			}
		}
		if($filtername == 'type') {
			if(count($filtervalue) == 0) {
				$query->whereRaw("vr.type like '0'");
			}else {
				$query->whereIn('vr.type', $filtervalue);
			}
		}
		if($filtername == 'channel') {
			if(count($filtervalue) == 0) {
				$query->whereRaw("vr.channel like '0'");
			}else {
				$query->whereIn('vr.channel', $filtervalue);
			}
		}
		if($filtername =='duration') {
			if(count( $filtervalue) > 0 && $filtervalue[0] != 'All'  ) {
				$query->whereRaw(" TIME_TO_SEC(vr.duration) ".$filtervalue[0]." ".$filtervalue[1]." ");
			}
		}

		if($filtername == 'tta') {
			if(count( $filtervalue) > 0 && $filtervalue[0] != 'All'  ) {
				$query->whereRaw("TIME_TO_SEC(vr.time_to_answer) ".$filtervalue[0]." ".$filtervalue[1]." ");
			}
		}

		if($searchoption !='') {
			$wh = sprintf(" (cu.first_name like '%%%s%%' or	
							 cu.last_name  like '%%%s%%' or
							 vr.callerid like '%%%s%%' or								
							 dest.country like '%%%s%%' or
							 vr.id like '%%%s%%'
							 )",
				$searchoption, $searchoption, $searchoption, $searchoption,$searchoption
			);
			$query->whereRaw($wh);
		}

		if( $period != '') {
			$query = clone $query;
			switch ($period) {
				case 'Today';
					$query->whereRaw(" DATE(vr.start_date_time) = '" . $cur_date . "'");
					break;
				case 'Weekly';
					$date = new DateTime($cur_date);
					$date->sub(new DateInterval('P' . 7 . 'D'));
					//$date->add(new DateInterval('P1D'));
					$start_date = $date->format('Y-m-d');
					$query->whereRaw("DATE(vr.start_date_time) >= '" . $start_date . "'");
					break;
				case 'Monthly';
					$date = new DateTime($cur_date);
					$date->sub(new DateInterval('P' . 30 . 'D'));
					$start_date = $date->format('Y-m-d');
					$query->whereRaw("DATE(vr.start_date_time) >= '" . $start_date . "'");
					break;
				case 'Custom Days';
					$query->whereRaw("DATE(vr.start_date_time) >= '" . $start_date . "' and DATE(vr.start_date_time) <= '" . $end_date . "'");
					break;
				case 'Yearly';
					$date = new DateTime($cur_date);
					$date->sub(new DateInterval('P' . 365 . 'D'));
					$start_date = $date->format('Y-m-d');
					$query->whereRaw("DATE(vr.start_date_time) >= '" . $start_date . "'");
					break;
			}
		}

		$sub_count_query = clone $query;
		if($filter == 'Answered' || $filter == 'Abandoned' || $filter == 'Missed' || $filter == 'Outgoing' || $filter == 'Dropped') {
			$query->where('vr.dial_status', $filter);
		}
		if($filter == 'Follow') {
			$query->where('vr.follow', 1);
		}
		if($filter == 'Callback') {
			$query->where('vr.callback_flag', '>', 0) ;
		}
		if($filter == 'Total') {

		}

		$data_query = clone $query;
		$data_list = $data_query
			->orderBy($orderby, $sort)
			->select(DB::raw('vr.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, asl.status, CONCAT_WS(" ", cp.firstname, cp.lastname) as caller_name, cp.email, cp.companyname, cp.national, dest.country'))
			->skip($skip)->take($pageSize)
			->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		$data_query = clone $sub_count_query;
		$ret['total_answered_count'] = $data_query
			->where('vr.dial_status', ANSWERED)
			->count();

		// Abandoned
		$data_query = clone $sub_count_query;
		$ret['total_abandoned_count'] = $data_query
			->where('vr.dial_status', ABANDONED)
			->count();

		// Missed
		$data_query = clone $sub_count_query;
		$ret['total_missed_count'] = $data_query
			->where('vr.dial_status', MISSED)
			->count();
	
		// Outgoing
		$data_query = clone $sub_count_query;
		$ret['total_outgoing_count'] = $data_query
			->where('vr.dial_status', OUTGOING)
			->count();

		// Dropped
		$data_query = clone $sub_count_query;
		$ret['total_dropped_count'] = $data_query
			->where('vr.dial_status', DROPPED)
			->count();

		// Total Count
		$data_query = clone $sub_count_query;
		$ret['total_count'] = $data_query
			->where('vr.dial_status','!=', RINGING)
			->count();

		// Queue
		$total_queue_count = DB::table('ivr_recording_queue')->count();
		$ret['total_queue_count'] = $total_queue_count;

		//callback
		$data_query = clone $sub_count_query;
		$ret['total_callback_count'] = $data_query
			->where('vr.callback_flag', '>', 0)
			->count();

		//followup
		$data_query = clone $sub_count_query;
		$ret['total_follow_count'] = $data_query
			->where('vr.follow', 1)
			->count();

		return Response::json($ret);
	}

	public function getCallHistory(Request $request)
	{
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$ticket_id = $request->get('ticket_id', '0');

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();

		$query = DB::table('ivr_call_history as ch')
				->leftJoin('ivr_voice_recording as ivr', 'ch.ticket_id', '=', 'ivr.id')
				->leftJoin('common_users as cu', 'ivr.user_id', '=', 'cu.id')
				->where('ch.ticket_id', $ticket_id);

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('ch.*, ivr.duration, LEFT(ch.created_at, 10) as date, RIGHT(ch.created_at, 8) as time, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->skip($skip)->take($pageSize)
				->get();

		foreach($data_list as $row){
			date_default_timezone_set(config('app.timezone'));
			$cur_time = date("Y-m-d H:i:s");

			if ($row->status == 'Ringing'){

				$answered = DB::table('ivr_call_history as ch')
						//	->where('ch.status', '=', 'Answered')
							->whereIn('ch.status', array(ANSWERED, MISSED, ABANDONED, DROPPED, CALLBACK))
							->where('ch.ticket_id', $row->ticket_id)
							->first();

				if (!empty($answered)){

					$duration1 = strtotime($answered->created_at) - strtotime($row->created_at);
					$row->duration  = gmdate("H:i:s", $duration1);

				}
				else{

					$duration1 = strtotime($cur_time) - strtotime($row->created_at);
					$row->duration  = gmdate("H:i:s", $duration1);
				}

			}

			if ($row->status == 'Queued'){

				$ringing = DB::table('ivr_call_history as ch')
						//	->where('ch.status', '=', 'Ringing')
							->whereIn('ch.status', array(RINGING, MISSED, ABANDONED, DROPPED, ANSWERED, CALLBACK))
							->where('ch.ticket_id', $row->ticket_id)
							->first();

				if (!empty($ringing)){

					$duration1 = strtotime($ringing->created_at) - strtotime($row->created_at);
					$row->duration  = gmdate("H:i:s", $duration1);

				}
				else{

					$duration1 = strtotime($cur_time) - strtotime($row->created_at);
					$row->duration  = gmdate("H:i:s", $duration1);

				}

			}


		}

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function getCallbackList(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));

		$ret = array();

		$query = DB::table('ivr_voice_recording as vr')
				->leftJoin('ivr_agent_status_log as asl', 'vr.user_id', '=', 'asl.user_id')
				->leftJoin('common_users as cu', 'vr.user_id', '=', 'cu.id')
				->leftJoin('common_users as cu2', 'vr.agent_take', '=', 'cu2.id')
				->leftJoin('ivr_caller_profile as cp', 'vr.callerid', '=', 'cp.callerid')
				->where('vr.property_id', $property_id)
				->where('vr.start_date_time', '>', $last24)
				->where('vr.dial_status', CALLBACK);

		$data_query = clone $query;

		$data_list = $data_query
				//->orderBy('vr.start_date_time', 'desc')
				->select(DB::raw('vr.*, CONCAT_WS(" ", cu2.first_name, cu2.last_name) as wholename_taken,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, asl.status, CONCAT_WS(" ", cp.firstname, cp.lastname) as caller_name, cp.email, cp.companyname'))
				->orderByRaw('CASE WHEN vr.callback_flag = 1 THEN 0
										WHEN vr.callback_flag = 2 THEN 1
										WHEN vr.callback_flag = 0 THEN 2 END')->orderBy('vr.start_date_time', 'desc')->get();
		
		foreach($data_list as $row)
		{
			$row->time = date('d/m/y h:i', strtotime($row->start_date_time));
		}								

		$ret['datalist'] = $data_list;

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$query = DB::table('ivr_voice_recording as vr')
				->where('vr.property_id', $property_id);

		$count_query = clone $query;
		$ret['callback'] = $count_query->where('vr.callback_flag', '>', 0)
				->count();
		$ret['callback_take'] = $count_query->where('vr.callback_flag', '=', 1)
				->count();		

		$count_query = clone $query;
		$ret['followup'] = $count_query->where('vr.follow', '>', 0)
				->count();


		return Response::json($ret);
	}
	public function getMissedList(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));
		$dept_id = $request->get('dept_id', '0');
		$ret = array();

		$query = DB::table('ivr_voice_recording as vr')
				->leftJoin('ivr_agent_status_log as asl', 'vr.user_id', '=', 'asl.user_id')
				->leftJoin('common_users as cu', 'vr.user_id', '=', 'cu.id')				
				->leftJoin('common_users as cu2', 'vr.agent_take', '=', 'cu2.id')
				->leftJoin('ivr_caller_profile as cp', 'vr.callerid', '=', 'cp.callerid')
				->leftJoin('ivr_call_center_skill_group as isg', 'vr.skill_group_id', '=', 'isg.id')
				->where('vr.property_id', $property_id)
				->where('vr.start_date_time', '>', $last24)
				->where('isg.dept_id', $dept_id)
				->where('vr.dial_status', MISSED);

		$data_query = clone $query;

		$data_list = $data_query
				//->orderBy('vr.start_date_time', 'desc')
				->select(DB::raw('vr.*, TIME(vr.start_date_time) as time,CONCAT_WS(" ", cu2.first_name, cu2.last_name) as wholename_taken, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, asl.status, CONCAT_WS(" ", cp.firstname, cp.lastname) as caller_name, cp.email, cp.companyname'))
				->orderByRaw('CASE WHEN vr.missed_flag = 1 THEN 0
										WHEN vr.missed_flag = 2 THEN 1
										WHEN vr.missed_flag = 0 THEN 2 END')->orderBy('vr.start_date_time', 'desc')->get();

		foreach($data_list as $row)
		{
			$row->time = date('d/m/y h:i', strtotime($row->start_date_time));
		}									

		$ret['datalist'] = $data_list;

		$count_query = clone $query;
		$totalcount = $count_query->count();

		

		$take_query = clone $query;
		
		$ret['missed_take'] = $take_query->where('vr.missed_flag', '=', 1)
				->count();


		return Response::json($ret);
	}
	public function getAbandonList(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));
		$dept_id = $request->get('dept_id', '0');
		$ret = array();

		$query = DB::table('ivr_voice_recording as vr')
				->leftJoin('ivr_agent_status_log as asl', 'vr.user_id', '=', 'asl.user_id')
				->leftJoin('common_users as cu', 'vr.user_id', '=', 'cu.id')
				->leftJoin('common_users as cu2', 'vr.agent_take', '=', 'cu2.id')
				->leftJoin('ivr_caller_profile as cp', 'vr.callerid', '=', 'cp.callerid')
				->leftJoin('ivr_call_center_skill_group as isg', 'vr.skill_group_id', '=', 'isg.id')
				->where('vr.property_id', $property_id)
				->where('vr.start_date_time', '>', $last24)
				->where('isg.dept_id', $dept_id)
				->where('vr.dial_status', ABANDONED);

		$data_query = clone $query;

		$data_list = $data_query
				//->orderBy('vr.start_date_time', 'desc')
				->select(DB::raw('vr.*, TIME(vr.start_date_time) as time,CONCAT_WS(" ", cu2.first_name, cu2.last_name) as wholename_taken, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, asl.status, CONCAT_WS(" ", cp.firstname, cp.lastname) as caller_name, cp.email, cp.companyname'))
				->orderByRaw('CASE WHEN vr.abandon_flag = 1 THEN 0
										WHEN vr.abandon_flag = 2 THEN 1
										WHEN vr.abandon_flag = 0 THEN 2 END')->orderBy('vr.start_date_time', 'desc')->get();

		foreach($data_list as $row)
		{
			$row->time = date('d/m/y h:i', strtotime($row->start_date_time));
		}									

		$ret['datalist'] = $data_list;

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$take_query = clone $query;
		
		$ret['abandon_take'] = $take_query->where('vr.abandon_flag', '=', 1)
				->count();
		


		return Response::json($ret);
	}
	public function addComment(Request $request, $type){
	 $call = $request->get('call', '0');
	 //echo json_encode($call);
	 $agent = IVRAgentStatus::where('user_id', $call['user_id'])->first();
		if( empty($agent) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'This user is not agent';
			return Response::json($ret);
		}
		$ticket = IVRVoiceRecording::find($call['id']);
		$ticket->comment= $call['comment'];
		$ticket->type= $call['type'];
		$ticket->save();
		$ret['message'] = 'This user is not agent';
		return Response::json($ret);
	}

	public function takeCallback(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', '0');
		$user_id  = $request->get('user_id', '0');
		$ticket_id = $request->get('ticket_id', '0');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ticket = IVRVoiceRecording::find($ticket_id);
		if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ticket';
			return Response::json($ret);
		}

		$agent = IVRAgentStatus::where('user_id', $user_id)->first();
		if( empty($agent) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'This user is not agent';
			return Response::json($ret);
		}

		$agent->status = OUTGOING;
		$agent->created_at = $cur_time;
		$agent->save();
		Functions::saveAgentStatusHistory($agent);

		$ticket->agent_take = $user_id;
		if($ticket->callback_flag==1)
		$ticket->callback_flag = 2;
		elseif($ticket->missed_flag==1)
		$ticket->missed_flag = 2;
		elseif($ticket->abandon_flag==1)
		$ticket->abandon_flag = 2;
		$ticket->save();

		$data = $this->getAgentStatusData($user_id);

		// notify callback event
		$caller = $this->getCallerProfile($ticket);

		$data->caller = $caller;
		$data->ticket = $ticket;

		$message = [
				'type' => 'callback_event',
				'data' => $data
		];

		Redis::publish('notify', json_encode($message));
		//$ret['ticket']=$ticket;
		$ret['code'] = 200;
		$ret['message'] = 'Agent take callback call';

		return Response::json($ticket);
	}

	public function getCallcenterConfig(Request $request)
	{
		$property_id = $request->get('property_id', 0);

		$rules['auto_wrapup_flag'] = true;
		$rules['caller_info_save_flag'] = true;
		$rules['call_center_widget'] = true;
		$rules['softphone_enabled'] = true;
		$rules['sip_server'] = 'dxbd1.myhotlync.com';

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = PropertySetting::getPropertySettings($property_id, $rules);

		return Response::json($ret);
	}

	public function getAgentExtensionList(Request $request) {
		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 0);
		$dept_id = $request->get('dept_id', 0);

		$bExist = false;
		if ($user_id != 0) {
		    $countList = DB::table('call_center_extension')
                ->where('user_id', $user_id)
                ->where('extension', '>', '0')
                ->select(DB::raw('*'))
                ->get();

		    if (count($countList) > 0) {
		        $bExist = true;
            }
        }

		if ($bExist == true) {
            $list = DB::table('call_center_extension as ce')
                ->where('ce.property_id', $property_id)
                ->where('ce.dept_id', $dept_id)
                ->where('ce.user_id', $user_id)
                ->whereRaw('NOT EXISTS (
						SELECT  NULL
						FROM    ivr_agent_status_log AS asl
						JOIN common_users as cu ON asl.user_id = cu.id
						JOIN common_department as cd ON cu.dept_id = cd.id
						WHERE ce.extension = asl.extension						
						and asl.status != \'Log out\'
						and cd.property_id = ' . $property_id . '
						and asl.user_id != ' . $user_id . '
					)')
                ->select(DB::raw('ce.*'))
                ->get();
        } else {
            $list = DB::table('call_center_extension as ce')
                ->where('ce.property_id', $property_id)
                ->where('ce.dept_id', $dept_id)
                ->whereRaw('NOT EXISTS (
						SELECT  NULL
						FROM    ivr_agent_status_log AS asl
						JOIN common_users as cu ON asl.user_id = cu.id
						JOIN common_department as cd ON cu.dept_id = cd.id
						WHERE ce.extension = asl.extension						
						and asl.status != \'Log out\'
						and cd.property_id = ' . $property_id . '
					)')
                ->select(DB::raw('ce.*'))
                ->get();
        }

		return Response::json($list);
	}

	private function getSkillIds($skill_group_ids)
	{
		$skill_ids = [];
		if( !empty($skill_group_ids) )		
		{
			$skill_group_ids = explode(",", $skill_group_ids);
			$skill_group_list = DB::table('ivr_call_center_skill_group')
				->whereIn('id', $skill_group_ids)
				->get();

			foreach($skill_group_list as $row)
			{
				$ids = explode(",", $row->skill_ids);
				$skill_ids = array_merge($skill_ids, $ids);
			}				
		}

		$skill_ids = array_unique($skill_ids, SORT_REGULAR);
		$skill_ids = array_merge($skill_ids, array());

		return $skill_ids;
	}

	public function getStatisticInfo(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$user_id = $request->get('user_id', 0);
		$period = $request->get('period', 'Today');
		$end_date = $request->get('end_date', '');
		$start_date = $request->get('start_date', '');
		$skill_group_ids = $request->get('skill_group_ids', '');
		//$during = $request->get('during', '');
		//echo $during;

		$skill_group_list = DB::table('ivr_call_center_profile')
				->where('user_id', $user_id)
				->select(DB::raw('skill_group'))
				->first();
		
		
		/*
		if(!empty($filter)) {
		//	$user_id = $filter['user_id'];
			$skill_group_id = $filter['skill_group_ids'];
		}else {
			$skill_group_id = $skill_group_ids;
		}
		*/

		if(!empty($skill_group_list)) {
				$skill_group_id = $skill_group_list->skill_group;
		}else {
				$skill_group_id = $skill_group_ids;
		}

		$query = DB::table('ivr_agent_status_log as asl')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->leftJoin('ivr_status_priority as sp', 'asl.status', '=', 'sp.status')
				->leftJoin('call_center_skill as cs', 'asl.skill_id', '=', 'cs.id');

		// get skill_ids
		$skill_ids = [];
		if( !empty($skill_group_id) )		
		{
			$skill_ids = $this->getSkillIds($skill_group_id);
			$skill_group_ids = explode(",", $skill_group_id);
			
			if( count($skill_ids) > 0 )
			{
				$query->join('ivr_agent_skill_level as sk', 'asl.id', '=', 'sk.agent_id')			
					->whereIn('sk.skill_id', $skill_ids);
			}
		}
		else
			$skill_group_ids = [];

		date_default_timezone_set(config('app.timezone'));
		$thirty_days = date('Y-m-d',strtotime("-31 days")); 

		$agent_list = $query->where('cd.property_id', $property_id)
				->where('cu.deleted', 0)
				->whereDate('cu.last_log_in', ">=" , $thirty_days)
				->orderBy('sp.priority', 'asc')
				->orderBy('asl.created_at', 'desc')
				->groupBy('asl.id')
				->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent, cu.picture, cs.name as skill'))
				->get();

		$ret = array();

		switch ($period) {
			case 'Today';
				$ret = $this->getStaticsticsByToday($agent_list, $property_id, $skill_group_ids, $skill_ids);
				break;
			case 'Weekly';
				$ret = $this->getStaticsticsByDate($end_date, 7, $agent_list, $property_id, $skill_group_ids, $skill_ids);
				break;
			case 'Monthly';
				$ret = $this->getStaticsticsByDate($end_date, 30, $agent_list, $property_id, $skill_group_ids, $skill_ids);
				break;
			case 'Custom Days';
				$ret = $this->getStaticsticsByCustomDate($end_date, $start_date, $agent_list, $property_id, $skill_group_ids, $skill_ids);
				break;
			case 'Yearly';
				$ret = $this->getStaticsticsByYearly($end_date, $agent_list, $property_id, $skill_group_ids, $skill_ids);
				break;
		}

		$ret['threshold'] = $this->getCallcenterThresholdSetting($property_id);
		$ret['skill_ids'] = $skill_ids;
		if (!empty($skill_group_list))
			$ret['skill_group'] = $skill_group_list->skill_group;
		else
			$ret['skill_group'] = '';

		return Response::json($ret);
	}

	public function getStaticsticsByToday($agent_list, $property_id, $skill_group_ids, $skill_ids)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$last_24 = date('Y-m-d H:i:s',strtotime("-1 days"));

		$ret = array();

		$time_range = "DATE(ivr.start_date_time) = '" . $cur_date . "'";
		$log_range = "DATE(ash.created_at) = '" . $cur_date . "'";

		$start = microtime(true);
		$query = DB::table('ivr_voice_recording as ivr');		
		$this->getStatisticsTotalCount($query, $time_range, $ret, $skill_group_ids, $skill_ids);

		$end = microtime(true);
		$ret['total_count_time'] = $end - $start;

		$start = microtime(true);
		$ret['hourly_statistics'] = $this->getHourlyStatistics($agent_list, $time_range, $skill_group_ids, $skill_ids);

		$end = microtime(true);
		$ret['hourly_statistics_time'] = $end - $start;

		$start = microtime(true);
		$agent_stat_array = $this->getStatisticsByAgent($agent_list,
				$time_range,
				$log_range, $skill_group_ids, $skill_ids);
		$ret['agent_list'] = $agent_stat_array['agent_list'];
		$ret['summary_status'] = $agent_stat_array['summary_status'];
		
		$end = microtime(true);
		$ret['agent_list_time'] = $end - $start;

		return $ret;
	}

	public function getStaticsticsByDate($end_date, $during, $agent_list,  $property_id, $skill_group_ids, $skill_ids)
	{
		$ret = array();

		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P' . $during . 'D'));

		$query = DB::table('ivr_voice_recording as ivr')
				->where('ivr.property_id', $property_id);

		$datetime = new DateTime($end_date);
		$datetime->sub(new DateInterval('P' . $during . 'D'));
		$start_date = $datetime->format('Y-m-d');

		$time_range = sprintf("DATE(ivr.start_date_time) > '%s' AND DATE(ivr.start_date_time) <= '%s'", $start_date, $end_date);
		$agent_time_range = sprintf("DATE(ash.created_at) > '%s' AND DATE(ash.created_at) <= '%s'", $start_date, $end_date);

		$this->getStatisticsTotalCount($query, $time_range, $ret, $skill_group_ids, $skill_ids);

		$ret['hourly_statistics'] = $this->getHourlyStatistics($agent_list, $time_range, $skill_group_ids, $skill_ids);

		// Agent status
		$agent_stat_array = $this->getStatisticsByAgent($agent_list, $time_range, $agent_time_range, $skill_group_ids, $skill_ids);
		$ret['agent_list'] = $agent_stat_array['agent_list'];
		$ret['summary_status'] = $agent_stat_array['summary_status'];

		return $ret;
	}

	public function getStaticsticsByCustomDate($end_date, $start_date, $agent_list,  $property_id, $skill_group_ids, $skill_ids)
	{
		$ret = array();

		$query = DB::table('ivr_voice_recording as ivr')
				->where('ivr.property_id', $property_id);


		$time_range = sprintf("(ivr.start_date_time) > '%s' AND (ivr.start_date_time) <= '%s'", $start_date, $end_date);
		$agent_time_range = sprintf("(ash.created_at) > '%s' AND (ash.created_at) <= '%s'", $start_date, $end_date);

		$this->getStatisticsTotalCount($query, $time_range, $ret, $skill_group_ids, $skill_ids);

		$ret['hourly_statistics'] = $this->getHourlyStatistics($agent_list, $time_range, $skill_group_ids, $skill_ids);

		// Agent status
		$agent_stat_array = $this->getStatisticsByAgent($agent_list, $time_range, $agent_time_range, $skill_group_ids, $skill_ids);
		$ret['agent_list'] = $agent_stat_array['agent_list'];
		$ret['summary_status'] = $agent_stat_array['summary_status'];

		return $ret;
	}

	public function getStaticsticsByYearly($end_date, $agent_list, $property_id, $skill_group_ids, $skill_ids)
	{
		$ret = array();

		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P1Y'));

		$query = DB::table('ivr_voice_recording as ivr')
				->where('ivr.property_id', $property_id);

		$datetime = new DateTime($end_date);
		$datetime->sub(new DateInterval('P1Y'));
		$start_date = $datetime->format('Y-m-d');

		$time_range = sprintf("DATE(ivr.start_date_time) > '%s' AND DATE(ivr.start_date_time) <= '%s'", $start_date, $end_date);
		$agent_time_range = sprintf("DATE(ash.created_at) > '%s' AND DATE(ash.created_at) <= '%s'", $start_date, $end_date);

		$this->getStatisticsTotalCount($query, $time_range, $ret, $skill_group_ids, $skill_ids);

		$ret['hourly_statistics'] = $this->getHourlyStatistics($agent_list, $time_range, $skill_group_ids, $skill_ids);

		// Agent status
		$agent_stat_array = $this->getStatisticsByAgent($agent_list, $time_range, $agent_time_range, $skill_group_ids, $skill_ids);
		$ret['agent_list'] = $agent_stat_array['agent_list'];
		$ret['summary_status'] = $agent_stat_array['summary_status'];

		return $ret;
	}

	private function getHourlyStatistics($agent_list, $date_range, $skill_group_ids, $skill_ids) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$hourly_statistics = [];
		$query = DB::table('ivr_voice_recording as ivr')
			->whereRaw($date_range);

		if( count($skill_group_ids) > 0 )	
			$query->whereIn('ivr.skill_group_id', $skill_group_ids);
		
		$hourly_statistics['tta'] = array();
		$hourly_statistics['calls'] = array();
		$hourly_statistics['mcalls'] = array();
		$hourly_statistics['waiting'] = array();

		for ($i = 0; $i < 24; $i++) {
			$start_time = sprintf("%02d:00:00", $i);
			$end_time = sprintf("%02d:00:00", $i + 1);

			$time_range = sprintf("TIME(start_date_time) >= '%s' AND TIME(start_date_time) < '%s'", $start_time, $end_time);

			$summary_time_query = clone $query;
			$summary_time = $summary_time_query
				->whereRaw($time_range)
				->select(DB::raw("
						count(*) as calls,
						sum(ivr.dial_status = '". ANSWERED ."') as answered,
						sum(ivr.dial_status = '". ABANDONED ."') as abandoned,
						sum(ivr.dial_status = '". MISSED ."') as missed,
						FLOOR(avg(TIME_TO_SEC(ivr.time_to_answer))) as tta,
						FLOOR(avg(TIME_TO_SEC(ivr.waiting))) as waiting
						"))
				->first();

			if( empty($summary_time->tta) )
				$hourly_statistics['tta'][] =  0;
			else
				$hourly_statistics['tta'][] =  $summary_time->tta;

			if( empty($summary_time->waiting) )
				$hourly_statistics['waiting'][] =  0;
			else
				$hourly_statistics['waiting'][] =  $summary_time->waiting;

			$hourly_statistics['calls'][] =  $summary_time->calls;
			$hourly_statistics['answered'][] =  $summary_time->answered;
			$hourly_statistics['abandoned'][] =  $summary_time->abandoned;
			$hourly_statistics['missed'][] =  $summary_time->missed;
			$hourly_statistics['mcalls'][] =($summary_time->answered)+($summary_time->abandoned)+($summary_time->missed);
		}


		return $hourly_statistics;
	}

	private function getStatisticsTotalCount($query, $time_range, &$ret, $skill_group_ids, $skill_ids) {
		// Answer
		if( count($skill_group_ids) > 0 )	
			$query->whereIn('ivr.skill_group_id', $skill_group_ids);

		$today_query = clone $query;

		$ret['total_answered_count'] = $today_query
				->where('ivr.dial_status', ANSWERED)
				->whereRaw($time_range)
				->count();

		// Abandoned
		$today_query = clone $query;
		$ret['total_abandoned_count'] = $today_query
				->where('ivr.dial_status', ABANDONED)
				->whereRaw($time_range)
				->count();

		// Missed
		$today_query = clone $query;
		$ret['total_missed_count'] = $today_query
				->where('ivr.dial_status', MISSED)
				->whereRaw($time_range)
				->count();
		//Outgoing		
		$today_query = clone $query;
		$ret['total_outgoing_count'] = $today_query
				->where('ivr.dial_status', OUTGOING)
				->whereRaw($time_range)
				->count();

		//Dropped
		$today_query = clone $query;
		$ret['total_dropped_count'] = $today_query
				->where('ivr.dial_status', DROPPED)
				->whereRaw($time_range)
				->count();

		// Total Count
		$today_query = clone $query;
		$ret['total_count'] = $today_query
				->where('ivr.dial_status','!=', RINGING)
				->whereRaw($time_range)
				->count();
				
		// TTA
		// ATT
		$today_query = clone $query;
		$ret['total_tta'] = $today_query
				->whereRaw($time_range)
				->where('ivr.dial_status', ANSWERED)
				->select(DB::raw("SEC_TO_TIME(round(COALESCE(avg(TIME_TO_SEC(ivr.time_to_answer)), 0))) as total_tta, SEC_TO_TIME(round(COALESCE(avg(TIME_TO_SEC(ivr.duration)),0))) as total_att"))
				->first();

		// Queue
		$total_queue_count = DB::table('ivr_recording_queue as irq')
							->leftJoin('ivr_voice_recording as ivr', 'irq.ticket_id', '=', 'ivr.id')
							->whereIn('ivr.skill_group_id', $skill_group_ids)
							->count();
		$ret['total_queue_count'] = $total_queue_count;

		//callback
		$today_query = clone $query;
		$ret['total_callback_count'] = $today_query
			->where('ivr.callback_flag', '>', 0)
			->whereRaw($time_range)
			->count();
		

		//followup
		$today_query = clone $query;
		$ret['total_follow_count'] = $today_query
			->where('ivr.follow', 1)
			->whereRaw($time_range)
			->count();


		// By Classify
		$today_query = clone $query;
	/*
		$ret['by_classify_type'] = $today_query
				->whereRaw($time_range)
				->leftJoin('ivr_call_types as ict', 'ivr.type', 'like', 'ict.label')
				->groupBy('ivr.type')
				->orderBy('cnt', 'DESC')
				->limit(10)
				->select(DB::raw('count(*) as cnt, ivr.type as label'))
				->get();
	*/
		$ret['by_classify_type'] = $today_query
				->whereRaw($time_range)
				->leftJoin('ivr_call_center_skill_group as icsg', 'ivr.skill_group_id', '=', 'icsg.id')
				->groupBy('icsg.group_name')
				->orderBy('cnt', 'DESC')
				->limit(10)
				->select(DB::raw('count(*) as cnt, icsg.group_name as label'))
				->get();

		$today_query = clone $query;
		$ret['by_country_data'] = $today_query
				->whereRaw($time_range)
				->leftJoin('call_destination as dest', 'ivr.call_origin', '=', 'dest.id')
				->where('ivr.call_type', '=', 'International')
				->groupBy('dest.country')
				->orderBy('cnt', 'DESC')
				->limit(5)
				->select(DB::raw('count(*) as cnt, dest.country as label'))
				->get();
		
		// By Original
		$today_query = clone $query;
		$ret['by_call_type'] = $today_query
				->whereRaw($time_range)
				->select(DB::raw("
						sum(ivr.call_type = 'Local') as local,
						sum(ivr.call_type = 'Mobile') as mobile,
						sum(ivr.call_type = 'Internal') as internal,
						sum(ivr.call_type = 'International') as international,
						sum(ivr.call_type = 'National') as national
						"))
				->first();	
	}

	private function getStatisticsByAgent($agent_list, $time_range, $agent_time_range, $skill_group_ids, $skill_ids) {	
		$ret=array();
		
		$query = DB::table('ivr_voice_recording as ivr')					
					->whereRaw($time_range);
		if( count($skill_group_ids) > 0 )	
			$query->whereIn('ivr.skill_group_id', $skill_group_ids);			
			
		$query->groupBy('ivr.user_id');

		$summary_query = clone $query;
		$summary = $summary_query
				->select(DB::raw("ivr.user_id, COALESCE(sum(ivr.dial_status = '". ANSWERED ."'), 0) as answer_count,
							COALESCE(sum(ivr.callback_flag > 0), 0) as callback_count,
							COALESCE(sum(ivr.dial_status = '". MISSED ."'), 0) as missed_count,
							COALESCE(sum(ivr.dial_status = '". ABANDONED ."'), 0) as abandand_count"))
				->get();

		$summary_time_query = clone $query;
		$summary_time = $summary_time_query
				->where('ivr.dial_status', ANSWERED)
				->select(DB::raw("ivr.user_id, SUBSTR(SEC_TO_TIME(round(COALESCE(avg(TIME_TO_SEC(ivr.time_to_answer)), '00:00:00'))),1,8) as avg_time,
				SUBSTR(SEC_TO_TIME(COALESCE(sum(TIME_TO_SEC(ivr.duration)), '00:00:00') / COALESCE(sum(ivr.dial_status = '". ANSWERED ."'), 0)),1,8) as time_call"))
				->get();		

		$query = DB::table('ivr_agent_status_history as ash')					
					->whereRaw($agent_time_range)
					->groupBy('ash.user_id');

		$summary_query = clone $query;
		$summary_status = $summary_query
				->select(DB::raw("ash.user_id,
							COALESCE(sum((ash.status = '". ONLINE ."') * abs(ash.duration)), 0) as online,
							COALESCE(sum((ash.status = '". AVAILABLE ."') * abs(ash.duration)), 0) as available,
							COALESCE(sum((ash.status = '". BUSY  ."') * abs(ash.duration)), 0) as busy,
							COALESCE(sum((ash.status = '". ONBREAK ."') * abs(ash.duration)), 0) as on_break,
							COALESCE(sum((ash.status = '". AWAY ."') * abs(ash.duration)), 0) as away,
							COALESCE(sum((ash.status = '". IDLE ."') * abs(ash.duration)), 0) as idle,
							COALESCE(sum((ash.status = '". WRAPUP ."') * abs(ash.duration)), 0) as wrapup,
							SUBSTR(SEC_TO_TIME(COALESCE(sum((ash.status = '". WRAPUP ."') * abs(ash.duration)), '00:00:00')),1,8) as wrapup_time,
							SUBSTR(SEC_TO_TIME(COALESCE(sum((ash.status != '". BUSY ."' and ash.status != '". LOGOUT ."') * abs(ash.duration)), '00:00:00')),1,8) as aux_dur_time									
							"))
				->get();		
		
		foreach($agent_list as $row) {
			$user_id = $row->user_id;	
			
			$row->answered = 0;
			$row->abandoned = 0;
			$row->callback = 0;
			$row->missed = 0;
			$row->avg_time = '00:00:00';
			$row->time_call = '00:00:00';

			$row->online = 0;
			$row->available = 0;
			$row->busy = 0;
			$row->on_break = 0;
			$row->idle = 0;
			$row->wrapup = 0;
			$row->away = 0;

			foreach($summary as $row1) {
				if($row1->user_id == $user_id)
				{
					$row->answered = $row1->answer_count;
					$row->abandoned = $row1->abandand_count;
					$row->callback = $row1->callback_count;									
					$row->missed = $row1->missed_count;
					break;
				}
			}

			foreach($summary_time as $row1) {
				if($row1->user_id == $user_id)
				{			
					$row->avg_time = $row1->avg_time;
					$row->time_call = $row1->time_call;					
					break;
				}
			}

			foreach($summary_status as $row1) {
				if($row1->user_id == $user_id)
				{			
					$row->online = $row1->online;
					$row->available = $row1->available;
					$row->busy = $row1->busy;
					$row->on_break = $row1->on_break;
					$row->idle = $row1->idle;
					$row->wrapup = $row1->wrapup;
					$row->wrapup_time = $row1->wrapup_time;
					$row->away = $row1->away;
					$row->aux_dur_time = $row1->aux_dur_time;
				break;
				}
			}
		}

        $ret['agent_list'] = $agent_list;
		$ret['summary_status'] = $summary_status;
		

		return $ret;
	}

	private function getCallType($callerid) {
		if(strlen($callerid) <= 5 )
			return 'Internal';

		$adminext = StaffExternal::where('extension', $callerid)
				->where('bc_flag', 0)
				->first();
		$guestext = GuestExtension::where('extension', $callerid)->first();

		if( !empty($adminext) || !empty($guestext) )
			return 'Internal';

		$destination = $this->getCallDestination($callerid);

		if (empty($destination)) // if there is no destination
			return 'Unknown';

		$dest_group = GroupDestination::where('destination_id', $destination->id)->first();
		if (empty($dest_group))        // There is no destination group
			return 'Unknown';

		$carrier_group = CarrierGroup::find($dest_group->carrier_group_id);
		if (empty($carrier_group))
			return 'Unknown';

		$call_type = $carrier_group->call_type;
		if (empty($call_type))
			return 'Unknown';

		return $call_type;
	}

	public function incomingCall(Request $request)
	{
		$caller_id = $request->get('caller_id', 0);
		$channel_id = $request->get('channel_id', 0);
		$channel= $request->get('channel', "Others");
		$skill = $request->get('skill', "");
		$skill_group_id = $request->get('skill_group_id', 0);
		$bridge_id = $request->get('bridge_id', 0);
		$property_id = $request->get('property_id',0);

		if(empty($channel))
			$channel='Others';
			
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");


		$lock_file = $_SERVER["DOCUMENT_ROOT"] . '/lock.txt';
		$fp = fopen($lock_file, "r+");

		# this will wait until lock is acquired
		# for "no waiting" use: LOCK_EX | LOCK_NB
		if (flock($fp, LOCK_EX)) {  // acquire an exclusive lock
			$agentlist = IVRAgentStatus::getAvailbleAgentList($property_id, $skill_group_id, 0);
			
			// add call ticket.
			$voice_recording = new IVRVoiceRecording();

			$voice_recording->end_date_time = '';
			$voice_recording->property_id = $property_id;
			$voice_recording->duration = 0;

			$call_type = $this->getCallType($caller_id);
			$voice_recording->call_type = $call_type;

			$voice_recording->callerid = $caller_id;
			$voice_recording->channel_id = $channel_id;
			$voice_recording->channel = $channel;
			$voice_recording->bridge_id = $bridge_id;
			$voice_recording->skill_group_id = $skill_group_id;

			$destination = $this->getCallDestination($caller_id);
			if( !empty($destination) )
				$voice_recording->call_origin = $destination->id;

			$voice_recording->filepath = '';
			$voice_recording->filename = '';
			$voice_recording->time_to_answer = '';
			$voice_recording->type = 'Other';

			if( empty($agentlist) || count($agentlist) < 1 )		// There is no free agent
			{
				$voice_recording->user_id = 0;
				$voice_recording->start_date_time = $cur_time;
				$voice_recording->ext = '';
				$voice_recording->dial_status = 'Queued';

				$voice_recording->save();

				// call to queue.
				$queue = new IVRCallQueue();
				$queue->callerid = $caller_id;
				$queue->priority = 1;
				$queue->ticket_id = $voice_recording->id;
				$queue->created_at = $cur_time;
				$queue->save();

				$this->checkQueueCount($property_id, 'Add');
				$this->sendQueueChangeEvent($voice_recording->id);
			}
			else
			{
				$agent = IVRAgentStatus::find($agentlist[0]->id);

				$voice_recording->ext = $agent->extension;
				$voice_recording->user_id = $agent->user_id;
				$voice_recording->start_date_time = $cur_time;
				$voice_recording->dial_status = RINGING;

				$voice_recording->save();

				$agent->old_status = $agent->status;
				$agent->status = RINGING;
				
				$agent->created_at = $cur_time;
				$agent->ticket_id = $voice_recording->id;
				$agent->save();

				Functions::saveAgentStatusHistory($agent);
			}

			Functions::saveCallHistory($voice_recording);

			if( $voice_recording->user_id > 0 )
			{
				$data = $this->getAgentStatusData($voice_recording->user_id);
				$caller = $this->getCallerProfile($voice_recording);

				$data->caller = $caller;
				$data->ticket = $voice_recording;


				$message = array();
				$message['type'] = 'incoming';
				$message['data'] = $data;

				Redis::publish('notify', json_encode($message));
			}

			#... SAFE TO WORK HERE
			flock($fp, LOCK_UN);    // release the lock
		} else {
			# only reached in case of a lock is nonblocking or general error
			$voice_recording = "Couldn't get the lock!";
		}

		fclose($fp);

		return Response::json($voice_recording);
	}

	public function incomingCallFromSoftphone(Request $request)
	{
		$user_id = $request->get('user_id', 0);
		$caller_id = $request->get('caller_id', 0);
		$property_id = $request->get('property_id',0);

		$agent = IVRAgentStatus::where('user_id', $user_id)->first();
		if( empty($agent) 
			|| $agent->status == RINGING
			|| $agent->status == BUSY )
		{		
			$ret['id'] = 0;	
			return Response::json($ret);
		}


		$channel='Others';
			
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$lock_file = $_SERVER["DOCUMENT_ROOT"] . '/lock.txt';
		$fp = fopen($lock_file, "r+");

		# this will wait until lock is acquired
		# for "no waiting" use: LOCK_EX | LOCK_NB
		if (flock($fp, LOCK_EX)) {  // acquire an exclusive lock
			// add call ticket.
			$voice_recording = new IVRVoiceRecording();

			$voice_recording->end_date_time = '';
			$voice_recording->property_id = $property_id;
			$voice_recording->duration = 0;

			$call_type = $this->getCallType($caller_id);
			$voice_recording->call_type = $call_type;

			$voice_recording->callerid = $caller_id;
			$voice_recording->channel_id = 0;
			$voice_recording->channel = $channel;
			$voice_recording->bridge_id = 0;
			$voice_recording->skill_group_id = 0;

			$destination = $this->getCallDestination($caller_id);
			if( !empty($destination) )
				$voice_recording->call_origin = $destination->id;

			$voice_recording->filepath = '';
			$voice_recording->filename = '';
			$voice_recording->time_to_answer = '';
			$voice_recording->type = 'Other';

			$voice_recording->ext = $agent->extension;
			$voice_recording->user_id = $agent->user_id;
			$voice_recording->start_date_time = $cur_time;
			$voice_recording->dial_status = RINGING;

			$voice_recording->save();

			$agent->old_status = $agent->status;
			$agent->status = RINGING;
			$agent->created_at = $cur_time;
			$agent->ticket_id = $voice_recording->id;
			$agent->save();

			Functions::saveAgentStatusHistory($agent);

			Functions::saveCallHistory($voice_recording);

			$data = $this->getAgentStatusData($voice_recording->user_id);
			$caller = $this->getCallerProfile($voice_recording);

			$data->caller = $caller;
			$data->ticket = $voice_recording;


			$message = array();
			$message['type'] = 'incoming';
			$message['data'] = $data;

			Redis::publish('notify', json_encode($message));

			#... SAFE TO WORK HERE
			flock($fp, LOCK_UN);    // release the lock
		} else {
			# only reached in case of a lock is nonblocking or general error
			$voice_recording = "Couldn't get the lock!";
		}

		fclose($fp);

		return Response::json($voice_recording);
	}


	public function outgoingCall(Request $request)
	{
		$user_id = $request->get('user_id', 0);
		$caller_id = $request->get('caller_id', 0);
		$channel_id = $request->get('channel_id', 0);
		$channel= $request->get('channel', "Others");
		$skill = $request->get('skill', "");
		$skill_group_id = $request->get('skill_group_id', 0);
		$bridge_id = $request->get('bridge_id', 0);
		$property_id = $request->get('property_id',0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

			
		// add call ticket.
		$voice_recording = new IVRVoiceRecording();

		$voice_recording->end_date_time = '';
		$voice_recording->property_id = $property_id;
		$voice_recording->duration = 0;

		$call_type = $this->getCallType($caller_id);
		$voice_recording->call_type = $call_type;

		$voice_recording->callerid = $caller_id;
		$voice_recording->channel_id = $channel_id;
		$voice_recording->channel = $channel;
		$voice_recording->bridge_id = $bridge_id;
		$voice_recording->skill_group_id = $skill_group_id;

		$destination = $this->getCallDestination($caller_id);
		if( !empty($destination) )
			$voice_recording->call_origin = $destination->id;

		$voice_recording->filepath = '';
		$voice_recording->filename = '';
		$voice_recording->time_to_answer = '';
		$voice_recording->type = 'Other';

			
		$agent = IVRAgentStatus::where('user_id', $user_id)->first();

		if( empty($agent) )		// no free agent
		{
			$voice_recording->user_id = 0;
			$voice_recording->start_date_time = $cur_time;
			$voice_recording->ext = '';
			$voice_recording->dial_status = 'Queued';

			// $voice_recording->save();

			// // call to queue.
			// $queue = new IVRCallQueue();
			// $queue->callerid = $caller_id;
			// $queue->priority = 1;
			// $queue->ticket_id = $voice_recording->id;
			// $queue->created_at = $cur_time;
			// $queue->save();

			// $this->checkQueueCount($property_id, 'Add');
			// $this->sendQueueChangeEvent($voice_recording->id);
		}
		else
		{
			$voice_recording->ext = $agent->extension;
			$voice_recording->user_id = $agent->user_id;
			$voice_recording->start_date_time = $cur_time;
			$voice_recording->dial_status = OUTGOING;

			$voice_recording->save();

			$agent->old_status = $agent->status;
			$agent->status = OUTGOING;
			$agent->created_at = $cur_time;
			$agent->ticket_id = $voice_recording->id;
			$agent->save();

			Functions::saveAgentStatusHistory($agent);

			Functions::saveCallHistory($voice_recording);
		}
			

		if( $voice_recording->user_id > 0 )
		{
			$data = $this->getAgentStatusData($user_id);
			$caller = $this->getCallerProfile($voice_recording);

			$data->caller = $caller;
			$data->ticket = $voice_recording;


			$message = array();
			$message['type'] = 'incoming';
			$message['data'] = $data;

			Redis::publish('notify', json_encode($message));
		}
	
		return Response::json($voice_recording);
	}

	public function redirectIncomingCall(Request $request)
	{
		$ticket_id = $request->get('ticket_id', 0);
		$agent_id = $request->get('agent_id', 0);
		//$skill = $request->get('skill', "");
		//$skill_group_id = $request->get('skill_group_id', 0);
        $extension = $request->get('extension', 0);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		// add call ticket.
		$ticket = IVRVoiceRecording::find($ticket_id);
		if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] ='Invalid Ticket';

			return Response::json($ret);
		}

        $agentlist = DB::table('ivr_agent_status_log as asl')
			->where('asl.user_id', $agent_id)
			->select(DB::raw('asl.*'))
			->get();
    		
		$ticket->ext = $extension;
		$ticket->user_id = $agent_id;
		$ticket->start_date_time = $cur_time;
		$ticket->dial_status = RINGING;

		$ticket->save();

		$agent_save = IVRAgentStatus::find($agentlist[0]->id);
		$agent_save->status = RINGING;
		$agent_save->created_at = $cur_time;
		$agent_save->ticket_id = $ticket->id;
		$agent_save->save();

		Functions::saveAgentStatusHistory($agentlist[0]);
		Functions::saveCallHistory($ticket);


		$data = $this->getAgentStatusData($ticket->user_id);
		$caller = $this->getCallerProfile($ticket);

		$data->caller = $caller;
		$data->ticket = $ticket;

		$message = array();
		$message['type'] = 'incoming';
		$message['data'] = $data;

		Redis::publish('notify', json_encode($message));

		return Response::json($ticket);
	}

	public function agentBusy(Request $request) {
		$ticket_id = $request->get('ticket_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ticket = IVRVoiceRecording::find($ticket_id);
		if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ticket';
			return Response::json($ret);
		}

		$property_id = $ticket->property_id;

		$olduser_id = $ticket->user_id;

		// find Agent
		$agentlist = IVRAgentStatus::getAvailbleAgentList($property_id, $ticket->skill_group_id, $olduser_id);


		if( empty($agentlist) || count($agentlist) < 1 )		// There is no free agent
		{
			$ticket->user_id = 0;
			$ticket->start_date_time = $cur_time;
			$ticket->ext = '';
			$ticket->dial_status = 'Queued';

			$ticket->save();

			// call to queue.
			$queue = new IVRCallQueue();
			$queue->callerid = $ticket->callerid;
			$queue->priority = 1;
			$queue->ticket_id = $ticket->id;
			$queue->created_at = $cur_time;
			$queue->save();

			$this->checkQueueCount($property_id, 'Add');
			$this->sendQueueChangeEvent($ticket->id);
		}
		else
		{
			$agent = IVRAgentStatus::find($agentlist[0]->id);

			$ticket->ext = $agent->extension;
			$ticket->user_id = $agent->user_id;
			$ticket->start_date_time = $cur_time;
			$ticket->dial_status = RINGING;

			$ticket->save();

			$agent->status = RINGING;
			$agent->created_at = $cur_time;
			$agent->ticket_id = $ticket->id;
			$agent->save();

			Functions::saveAgentStatusHistory($agent);
		}

		Functions::saveCallHistory($ticket);

		if( $ticket->user_id > 0 )
		{
			$data = $this->getAgentStatusData($ticket->user_id);
			$caller = $this->getCallerProfile($ticket);

			$data->caller = $caller;
			$data->ticket = $ticket;

			$message = array();
			$message['type'] = 'incoming';
			$message['data'] = $data;

			Redis::publish('notify', json_encode($message));

		}

		if( $olduser_id > 0 )
		{
			$oldagent = IVRAgentStatus::where('user_id',$olduser_id)
					->first();
			if(!empty($oldagent)) {

				$oldagent->status = IDLE;
				$oldagent->created_at = $cur_time;
				$oldagent->save();

				Functions::saveAgentStatusHistory($oldagent);


				$data = $this->getAgentStatusData($olduser_id);
				$caller = $this->getCallerProfile($ticket);

				$data->caller = $caller;
				$data->ticket = $ticket;

				$message = array();
				$message['type'] = 'incoming';
				$message['data'] = $data;
				Redis::publish('notify', json_encode($message));
			}
		}
		return Response::json($ticket);

	}

	public function agentFree(Request $request) {
	
		$ret = array();

		// find Agent
		$agentlist = DB::table('ivr_agent_status_log as asl')
			//->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
			//->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->where('asl.status', AVAILABLE)
			->orderBy('created_at', 'asc')
			->select(DB::raw('asl.*'))
			->get();

		if (!empty($agentlist)){
			$agent = IVRAgentStatus::find($agentlist[0]->id);
		}
		if (!empty($agent))
		{
			$ret['agent_ext'] = $agent->extension;
			$ret['agent_userid'] = $agent->user_id;
		}
		else
		{
			$ret['agent_ext'] = 0;
			$ret['agent_userid'] = 0;
		}
	
		return Response::json($ret);
	}

	public function joinQueue(Request $request) {
		$ticket_id = $request->get('ticket_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ticket = IVRVoiceRecording::find($ticket_id);
		if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ticket';
			return Response::json($ret);
		}
	
		
		// call to queue
		$queue = new IVRCallQueue();
		$queue->callerid = $ticket->callerid;
		$queue->priority = 1;
		$queue->ticket_id = $ticket->id;
		$queue->created_at = $cur_time;
		$queue->save();

		$property_id = $ticket->property_id;

		$this->checkQueueCount($property_id, 'Add');
		$this->sendQueueChangeEvent($ticket->id);

		$query = DB::table('ivr_recording_queue');
		$max_query = clone $query;
		$id=$max_query->max('id');

		$ret['queue_id'] = $id;
		
		return Response::json($ret);

	}

	public function callQueue(Request $request) {

		include '/var/www/hotel/CMS/public/frontpage/tpl/calls/call_queue.html';

	}

	public function callQueueList(Request $request) {

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$dept_id = $request->get('dept_id', '0');
		$user_id = $request->get('user_id', '0');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		if ($pageSize < 0)
			$pageSize = 20;


		$ret = array();

		

		$agent_skill = DB::table('ivr_agent_skill_level as sl')
						->leftJoin('ivr_agent_status_log as asl', 'sl.agent_id', '=', 'asl.id')
						->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
						->select(DB::raw('sl.skill_id'))
						->where('cu.id', $user_id)
						->first();
		$skill = $agent_skill->skill_id;

		$query = DB::table('ivr_recording_queue as irq')
				->leftJoin('ivr_voice_recording as ivr', 'irq.ticket_id', '=', 'ivr.id')
				->leftJoin('ivr_call_center_skill_group as isg', 'ivr.skill_group_id', '=', 'isg.id');
				
		$data_query = clone $query;

		$data_list = $data_query
				->orderBy('irq.order_num')
				->orderBy('irq.priority')
				->orderBy('irq.ticket_id')
				->select(DB::raw('irq.id,irq.callerid,irq.priority,TIME(irq.created_at) as time, irq.created_at, isg.skill_ids,
									SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(\'' . $cur_time . '\', irq.created_at))) as duration
								'))
				->skip($skip)->take($pageSize)
				->get();

		$datalist = [];

		foreach($data_list as $row){

			if ($row->priority == 1){
				$row->priority = 'Normal';
			}
			elseif ($row->priority == 2){
				$row->priority = 'Medium';
			}else{
				$row->priority = 'High';
			}

			$guestext = GuestExtension::where('extension', $row->callerid)->where('enable', 1)->first();
			if (!empty($guestext))
			{
				$guest = Guest::where('room_id', $guestext->room_id)
							->where('checkout_flag', 'checkin')
							->first();
				$room = Room::find($guestext->room_id);
				if (!empty($guest)){
					$row->tickname = $guest->guest_name;
					$row->room = $room->room;
					$row->vip = $guest->vip;
				}
				else
			 	{
					 $row->tickname = 'Vacant';
					 $row->room = $room->room;
					 $row->vip = '';
			 	}
			}else{
				$caller = DB::table('ivr_call_phonebook')
						->where('calledno', $row->callerid)
						->first();

				if( !empty($caller) ){
					$row->tickname = $caller->first_name . ' ' . $caller->last_name;
					$row->room = '';
					$row->vip = 'VIP';
				}
				else{
					$row->tickname = '';
					$row->room = '';
					$row->vip = '';
				}
				
			}
			$skill_ids = explode(",", $row->skill_ids);

			if (in_array($skill,$skill_ids )){

				$datalist[] = $row;
			}
		}
		
		$count_query = clone $query;
		$totalcount = count($datalist);

		$ret['datalist'] = $datalist;
		$ret['totalcount'] = $totalcount;
	

		
		return Response::json($ret);
		
	}

	public function moveUp(Request $request)
	{
		$id = $request->get('id', 0);

		$list = DB::table('ivr_recording_queue as irq')
			->orderBy('irq.order_num')
			->orderBy('irq.priority')
			->orderBy('irq.ticket_id')
			->get();

		$order_num = 1;
		foreach($list as $row)
		{
			if( $row->id == $id )
				break;
			
			$order_num++;
		}

		$num = 1;
		$update_num = 0;

		if( $order_num > 1 )
		{
			foreach($list as $row)
			{			
				if( $num == $order_num - 1 )
					$update_num = $num + 1;
				else if( $num == $order_num )
					$update_num = $num - 1;
				else
					$update_num = $num;

				DB::table('ivr_recording_queue')
					->where('id', $row->id)
					->update(['order_num' => $update_num]);

				$num++;
			}
		}

		$ret = array();
		$ret['code'] = 200;

		return Response::json($ret);
	}

	public function moveDown(Request $request)
	{
		$id = $request->get('id', 0);

		$list = DB::table('ivr_recording_queue as irq')
			->orderBy('irq.order_num')
			->orderBy('irq.priority')
			->orderBy('irq.ticket_id')
			->get();

		$order_num = 1;
		foreach($list as $row)
		{
			if( $row->id == $id )
				break;
			
			$order_num++;
		}

		$num = 1;
		$update_num = 0;

		if( $order_num < count($list) )
		{
			foreach($list as $row)
			{			
				if( $num == $order_num + 1 )
					$update_num = $num - 1;
				else if( $num == $order_num )
					$update_num = $num + 1;
				else
					$update_num = $num;

				DB::table('ivr_recording_queue')
					->where('id', $row->id)
					->update(['order_num' => $update_num]);

				$num++;
			}
		}

		$ret = array();
		$ret['code'] = 200;

		return Response::json($ret);
	}

	public function takeQueueCall(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$caller_id = $request->get('caller_id', '0');
		$agent_id  = $request->get('agent_id', 0);
		$id  = $request->get('id', '0');

		$queue = DB::table('ivr_recording_queue')
					->where('id', '=', $id)
					->first();
	
		$ret = array();

	//	$ticket = IVRVoiceRecording::find($queue->ticket_id);

		$ticket = DB::table('ivr_recording_queue as qu')
					->leftJoin('ivr_voice_recording as ivr' ,'qu.ticket_id','=','ivr.id')
					->where('ivr.id', $queue->ticket_id)
					->select(DB::raw('ivr.*, qu.id as queueid'))
					->first();

		$agent_list = DB::table('ivr_agent_status_log as asl')
						->where('asl.user_id', $agent_id)
						->select(DB::raw('asl.*'))
						->first();

		$agent = IVRAgentStatus::find($agent_list->id);

		$data = array();
		$data['agent'] = $agent;
		$data['ticket'] = $ticket;

		$message = array();
		$message['type'] = 'queue_redirect';
		$message['data'] = $data;
		Redis::publish('notify', json_encode($message));

		DB::table('ivr_recording_queue')
					->where('id', '=', $ticket->queueid)
					->delete();

		$property_id = $ticket->property_id;	

		$this->checkQueueCount($property_id, 'Delete');

		$this->sendQueueChangeEvent($ticket->id);
		
		
	//	$ret['code'] = 200;
	//	$ret['message'] = 'Agent take Queue call';

	//	return Response::json($ticket);
	}

	public function callQueuePriority(Request $request) {

	
		$id = $request->get('id', 0);
		$new_priority = $request->get('priority', '');

		$ret = array();
		if ($new_priority == 'Normal'){
				$priority = 1;
		}
		elseif ($new_priority == 'Medium'){
				$priority = 2;
		}
		else{
				$priority = 3;
		}

		DB::table('ivr_recording_queue')
			->where('id',$id)
			->update(['priority' => $priority]);

		$queue = IVRCallQueue::find($id);

		if( !empty($queue) )			
			$this->sendQueueChangeEvent($queue->ticket_id);
		
		$ret['code'] = 200;
		return Response::json($ret);
	}

	public function abandonedCall(Request $request)
	{
		$ticket_id = $request->get('ticket_id', 0);

		$dropedtime = date('H:i:s',strtotime('00:00:05'));
		$misstime = date('H:i:s',strtotime('00:00:20'));


		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ticket = IVRVoiceRecording::find($ticket_id);
		if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ticket';
			return Response::json($ret);
		}

		$datetime1 = strtotime($cur_time);
		$datetime2 = strtotime($ticket->start_date_time);
		$duration1 = $datetime1 - $datetime2;
		$hours = floor($duration1 / 3600);
		$minutes = floor(($duration1 / 60) % 60);
		$seconds = $duration1 % 60;
		$time_to_answer = $hours . ':' . $minutes . ':' . $seconds;
		$ttatime = date('H:i:s',strtotime($time_to_answer));

		$ticket->time_to_answer = $time_to_answer;

		if($ttatime < $dropedtime){
			$ticket->dial_status = DROPPED;
			$ticket->missed_flag = 0;
		}
		else if($ttatime < $misstime){
			$ticket->dial_status = MISSED;
			$ticket->missed_flag = 1;
		}
		else{
			$ticket->dial_status = ABANDONED;
			$ticket->abandon_flag = 1;
		}

		$ticket->end_date_time = $cur_time;
		$ticket->save();

		Functions::saveCallHistory($ticket);

		$user_id = $ticket->user_id;
		$agent = IVRAgentStatus::where('user_id', $user_id)->first();
		if( !empty($agent) )
		{
			$agent->created_at = $cur_time;
			if($ttatime < $misstime){
				$agent->status = AVAILABLE;
			}
			else{
				$rules = array();
				$rules['call_to_idle'] = 1;
				$rules = PropertySetting::getPropertySettings($ticket->property_id, $rules);

				// get last calls (This means if last 3 calls for agent become abandoned only then his status will change to Idle.)
				$last_calls = IVRVoiceRecording::where('user_id', '=', $agent->user_id)
							->orderBy('start_date_time', 'desc')
							->take($rules['call_to_idle'])
							->get();

				$abandoned_count = 0;
				foreach($last_calls as $row)
				{
					if( $row->dial_status == ABANDONED )
						$abandoned_count++;
				}

				if( $abandoned_count >= $rules['call_to_idle'])
					$agent->status = IDLE;
				else
					$agent->status = AVAILABLE;
			}

			$agent->save();
			Functions::saveAgentStatusHistory($agent);
		}

		$data = $this->getAgentStatusData($user_id);
		$caller = $this->getCallerProfile($ticket);

		$data->caller = $caller;
		$data->ticket = $ticket;

		$message = [
				'type' => 'incoming',
				'data' => $data
		];
		Redis::publish('notify', json_encode($message));

		$this->redirectCallToAvailableAgent($ticket->property_id);

		return Response::json($ticket);
	}

	public function setExcelHeader($sheet) {

	    $sheet->cell('A1:J1', function ($cell) {
	        $cell->setBackground('#dddddd');
            $cell->setFont(array(
                'family'     => 'Tahoma',
                'bold'       =>  true
            ));
		});
		$sheet->cell('A1', function ($cell){
            $cell->setValue('Salutation');
        });

        $sheet->cell('B1', function ($cell){
            $cell->setValue('First Name');
        });

        $sheet->cell('C1', function ($cell){
            $cell->setValue('Last Name');
		});
		
		$sheet->cell('D1', function ($cell){
            $cell->setValue('Nationality');
		});
		
		$sheet->cell('E1', function ($cell){
            $cell->setValue('Company');
		});
		
		$sheet->cell('F1', function ($cell){
            $cell->setValue('Email');
		});
		
		$sheet->cell('G1', function ($cell){
            $cell->setValue('Contact');
		});
		
		$sheet->cell('H1', function ($cell){
            $cell->setValue('Blacklist');
		});
		
		$sheet->cell('I1', function ($cell){
            $cell->setValue('VIP');
        });

        $sheet->cell('J1', function ($cell){
            $cell->setValue('Alternate Number');
        });
    }

    public function getIdFromPhonebook($data, $phoneNumber) {
	    $resultId = -1;

	    foreach ($data as $index => $item) {
	        if ($phoneNumber === $item->calledno) {
	            $resultId = $item->id;
	            break;
            }
        }

	    return $resultId;
    }

    public function addExcelData(Request $request) {
        $data = $request->get('data', []);
	    $user_id = $request->get('user_id', 0);
        $property_id = $request->get('property_id', 0);

        $query = DB::table('ivr_call_phonebook')
            ->where('user_id' , $user_id)
            ->where('property_id', $property_id);
        $currentData = $query->get();

        if (empty($currentData)) {

        } else {
            foreach ($data as $item) {
                $curId = $this->getIdFromPhonebook($currentData, $item['calledno']);

                if ($curId !== -1) {
                    DB::table('ivr_call_phonebook')
                        ->where('id', $curId)
                        ->update([
							'salutation' => $item['salutation'],
                            'first_name' => $item['first_name'],
							'last_name' => $item['last_name'],
							'nationality' => $item['nationality'],
							'company' => $item['company'],
							'email' => $item['email'],
							'alt_no' => $item['alt_no'],
							'blacklist' => $item['blacklist'],
							'vip' => $item['vip'],
                            'calledno' => $item['calledno']
                        ]);
                } else {
                    DB::table('ivr_call_phonebook')
                        ->insert([
							'salutation' => $item['salutation'],
                            'first_name' => $item['first_name'],
							'last_name' => $item['last_name'],
							'nationality' => $item['nationality'],
							'company' => $item['company'],
							'email' => $item['email'],
							'alt_no' => $item['alt_no'],
							'blacklist' => $item['blacklist'],
							'vip' => $item['vip'],
                            'calledno' => $item['calledno'],
                            'user_id' => $user_id,
                            'property_id' => $property_id
                        ]);
                }
            }
        }


        $ret = [];
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function setExcelBody($sheet, $row_number, $dataObj) {
        $sheet->cell('A' . $row_number . ':J' . $row_number, function ($cell) {
            $cell->setFont(array(
                'family'     => 'Tahoma',
                'bold'       =>  false
            ));
        });

        $sheet->cell('A' . $row_number, function ($cell) use ($dataObj) {
           $cell->setValue($dataObj->salutation);
		});
		
		$sheet->cell('B' . $row_number, function ($cell) use ($dataObj) {
			$cell->setValue($dataObj->first_name);
		 });

		 $sheet->cell('C' . $row_number, function ($cell) use ($dataObj) {
			$cell->setValue($dataObj->last_name);
		 });

		 $sheet->cell('D' . $row_number, function ($cell) use ($dataObj) {
			$cell->setValue($dataObj->nationality);
		 });

        $sheet->cell('E' . $row_number, function ($cell) use ($dataObj) {
            $cell->setValue($dataObj->company);
		});
		
		$sheet->cell('F' . $row_number, function ($cell) use ($dataObj) {
			$cell->setValue($dataObj->email);
		 });

		 $sheet->cell('G' . $row_number, function ($cell) use ($dataObj) {
			$cell->setValue($dataObj->calledno);
		 });

		 $sheet->cell('H' . $row_number, function ($cell) use ($dataObj) {
			$cell->setValue($dataObj->blacklist);
		 });

		 $sheet->cell('I' . $row_number, function ($cell) use ($dataObj) {
			$cell->setValue($dataObj->vip);
		 });

        $sheet->cell('J' . $row_number, function ($cell) use ($dataObj) {
            $cell->setValue($dataObj->alt_no);
        });
    }

	public function exportPhonebook(Request $request) {
        ini_set('memory_limit','-1');
        ini_set('max_execution_time', 300);
        ini_set("display_errors", 'off');
        set_time_limit(0);

        $user_id = $request->get('user_id', 0);
        $property_id = $request->get('property_id', 0);

        $data = DB::table('ivr_call_phonebook')
            ->where('user_id', $user_id)
            ->where('property_id', $property_id)
            ->select(DB::raw('salutation,first_name, last_name, nationality, company, email, alt_no, blacklist, vip, calledno'))
            ->orderBy('id', 'desc')
            ->get();

        $filename = 'phonebook';

        $param = $request->all();

        $excel_file_type = 'csv';
        if($param['type'] == 'excel') {
            $excel_file_type = 'xlsx';
        }

        $ret = Excel::create($filename, function ($excel) use ($data) {
            $excel->sheet('Phonebook', function ($sheet) use ($data) {
                $sheet->setOrientation('landscape');

                $this->setExcelHeader($sheet);

                foreach ($data as $index => $dataItem) {
                    $this->setExcelBody($sheet, $index + 2, $dataItem);
                }
            });
        })->export($excel_file_type);
    }

	public function answerCall(Request $request)
	{
		$ticket_id = $request->get('ticket_id', 0);
		//$time_to_answer = $request->get('time_to_answer', 0);
		$bridge_id = $request->get('bridge_id', 0);

		$channel = $request->get('channel', 'Other');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ticket = IVRVoiceRecording::find($ticket_id);
		
		if( $ticket->dial_status == RINGING){
					if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ticket';
			return Response::json($ret);
		}

		$ticket->dial_status = ANSWERED;
		$datetime1 = strtotime($cur_time);
		$datetime2 = strtotime($ticket->start_date_time);
		$duration1 = $datetime1 - $datetime2;
		$hours = floor($duration1 / 3600);
		$minutes = floor(($duration1 / 60) % 60);
		$seconds = $duration1 % 60;
		$time_to_answer = $hours . ':' . $minutes . ':' . $seconds;
		$ticket->time_to_answer = $time_to_answer;
		$ticket->bridge_id = $bridge_id;
		$ticket->channel = $channel;
		$ticket->save();

		Functions::saveCallHistory($ticket);

		$user_id = $ticket->user_id;
		$agent = IVRAgentStatus::where('user_id', $user_id)->first();
		if( !empty($agent) )
		{
			$agent->created_at = $cur_time;
			$agent->status = BUSY;
			$agent->save();

			Functions::saveAgentStatusHistory($agent);
		}

		$data = $this->getAgentStatusData($user_id);
		$caller = $this->getCallerProfile($ticket);

		$data->caller = $caller;
		$data->ticket = $ticket;

		$message = [
				'type' => 'incoming',
				'data' => $data
		];
		Redis::publish('notify', json_encode($message));

		return Response::json($ticket);
	}
}

	public function endCall(Request $request)
	{
		$ticket_id = $request->get('ticket_id', 0);
		$waiting = $request->get('waiting', '00:00:00');
		$filepath = $request->get('filepath', '');
		$filename = $request->get('filename', '');
		//$duration = $request->get('duration', 0);
		//$time_to_answer = $request->get('time_to_answer', 0);
		$wrapup_flag = $request->get('wrapup_flag', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ticket = IVRVoiceRecording::find($ticket_id);
		if( $ticket->dial_status == ANSWERED){
		    if( empty($ticket) ) {
                $ret['code'] = 201;
                $ret['message'] = 'Invalid ticket';
                return Response::json($ret);
    		}

            $datetime1 = strtotime($cur_time);
            $datetime2 = strtotime($ticket->start_date_time);
            $duration1 = $datetime1 - $datetime2;
            $hours = floor($duration1 / 3600);
            $minutes = floor(($duration1 / 60) % 60);
            $seconds = $duration1 % 60;
            $duration  = $hours . ':' . $minutes . ':' . $seconds;

            $ticket->filepath = $filepath;
            $ticket->filename = $filename;
            $ticket->duration = $duration;
            $ticket->end_date_time = $cur_time;
            $ticket->dial_status = ANSWERED;
            //$ticket->time_to_answer = $time_to_answer;
            $ticket->waiting = $waiting;
            $ticket->save();

            $this->checkHandlingTime($ticket->property_id);
            $this->checkAnswerSpeedTime($ticket->property_id);
            if($ticket->dial_status != ANSWERED)
                Functions::saveCallHistory($ticket);

            $user_id = $ticket->user_id;
            $agent = IVRAgentStatus::where('user_id', $user_id)->first();
            $agent_other_calls = IVRVoiceRecording::where('user_id',$user_id)
                ->where('dial_status', ANSWERED)
                ->where('end_date_time','=','0000-00-00 00:00:00')
                ->where('id', '<>', $ticket_id)
                ->get();


            if( !empty($agent) )
            {
                $agent->created_at = $cur_time;
                if( $ticket_id != $agent->ticket_id )	// other calls
                {

                }
                else if( $wrapup_flag == 0 )
                    $agent->status = $agent->old_status;
                else if($agent->status == BUSY && count($agent_other_calls) == 0)
                    $agent->status = WRAPUP;
                    // /$agent->status = AVAILABLE;

                else
                {
                    $agent->status = $agent->old_status;
                }

                $agent->save();

                Functions::saveAgentStatusHistory($agent);
            }

            $data = $this->getAgentStatusData($user_id);
            if( empty($data) )
            {
                $ret['code'] = 201;
                $ret['message'] = 'There is no agent';
                return Response::json($ret);
            }

            $caller = $this->getCallerProfile($ticket);

            $data->caller = $caller;
            $data->ticket = $ticket;

            $message = [
                    'type' => 'incoming',
                    'data' => $data
            ];

            Redis::publish('notify', json_encode($message));

            $this->redirectCallToAvailableAgent($ticket->property_id);

            return Response::json($ticket);
        }
        else if( $ticket->dial_status == RINGING){
            $dropedtime = date('H:i:s',strtotime('00:00:05'));
            $misstime = date('H:i:s',strtotime('00:00:20'));


            date_default_timezone_set(config('app.timezone'));
            $cur_time = date("Y-m-d H:i:s");

            $ret = array();

            $ticket = IVRVoiceRecording::find($ticket_id);
            if( empty($ticket) )
            {
                $ret['code'] = 201;
                $ret['message'] = 'Invalid ticket';
                return Response::json($ret);
            }

            $datetime1 = strtotime($cur_time);
            $datetime2 = strtotime($ticket->start_date_time);
            $duration1 = $datetime1 - $datetime2;
            $hours = floor($duration1 / 3600);
            $minutes = floor(($duration1 / 60) % 60);
            $seconds = $duration1 % 60;
            $time_to_answer = $hours . ':' . $minutes . ':' . $seconds;
            $ttatime = date('H:i:s',strtotime($time_to_answer));

            $ticket->time_to_answer = $time_to_answer;

            if($ttatime < $dropedtime){
                $ticket->dial_status = DROPPED;
               $ticket->missed_flag = 0;
           }
            else if($ttatime < $misstime){
                 $ticket->dial_status = MISSED;
                $ticket->missed_flag = 1;
            }
            else{
                $ticket->dial_status = ABANDONED;
                $ticket->abandon_flag = 1;
            }

            $ticket->end_date_time = $cur_time;
            $ticket->save();

            Functions::saveCallHistory($ticket);

            $user_id = $ticket->user_id;
            $agent = IVRAgentStatus::where('user_id', $user_id)->first();
            if( !empty($agent) )
            {
                $agent->created_at = $cur_time;
                if($ttatime < $misstime){
                    $agent->status = AVAILABLE;
                }
                else{
                    $agent->status = AVAILABLE;
                }

                $agent->save();
                Functions::saveAgentStatusHistory($agent);
            }

            $data = $this->getAgentStatusData($user_id);
            $caller = $this->getCallerProfile($ticket);

            $data->caller = $caller;
            $data->ticket = $ticket;

            $message = [
                    'type' => 'incoming',
                    'data' => $data
            ];
            Redis::publish('notify', json_encode($message));

            $this->redirectCallToAvailableAgent($ticket->property_id);

            return Response::json($ticket);
        }
    }

	public function leaveQueue(Request $request) {
		$ticket_id = $request->get('ticket_id', 0);
		$callback_flag = $request->get('callback_flag', 0);
		$voicemail = $request->get('voicemail', 0);
		//$waiting = $request->get('waiting', '00:00:00');
		$filepath = $request->get('filepath', '');
		$filename = $request->get('filename', '');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();
		$ticket = IVRVoiceRecording::find($ticket_id);
		if( $ticket->dial_status != ANSWERED){
		if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ticket';
			return Response::json($ret);
		}

		$ticket->filepath = $filepath;
		$ticket->filename = $filename;
		$ticket->duration = 0;
		$ticket->end_date_time = $cur_time;

		if($callback_flag == 1){
			$ticket->dial_status = CALLBACK;
		}
		else{
			$ticket->dial_status = MISSED;
			$ticket->missed_flag = 1;
		}
		$ticket->callback_flag = $callback_flag;
		$ticket->voicemail = $voicemail;
		$datetime1 = strtotime($cur_time);
		$datetime2 = strtotime($ticket->start_date_time);
		$duration1 = $datetime1 - $datetime2;
		$hours = floor($duration1 / 3600);
		$minutes = floor(($duration1 / 60) % 60);
		$seconds = $duration1 % 60;
		$waiting = $hours . ':' . $minutes . ':' . $seconds;
		$ticket->waiting = $waiting;
		$ticket->save();

		$this->checkEstmatedWaitingTime($ticket->property_id);

		Functions::saveCallHistory($ticket);

		DB::table('ivr_recording_queue')
			->where('ticket_id', '=', $ticket->id)
			->delete();

		$this->sendQueueChangeEvent($ticket->id);

		$property_id = $ticket->property_id;	
		$this->checkQueueCount($property_id, 'Delete');	

		$data = [];

		// notify callback event
		if( $callback_flag == 1 )
		{
			$caller = $this->getCallerProfile($ticket);

			$data['caller'] = $caller;
			$data['ticket'] = $ticket;

			$message = [
					'type' => 'callback_event',
					'data' => $data
			];

			Redis::publish('notify', json_encode($message));
		}
	}
		return Response::json($ticket);
	}

	public function holdResume(Request $request) {
		$ticket_id = $request->get('ticket_id', 0);
		$status = $request->get('status', 0);

		$ret = array();

		$ticket = IVRVoiceRecording::find($ticket_id);
		if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Ticket';

			return Response::json($ret);
		}

		$user_id = $ticket->user_id;
		if( $user_id < 1 )
		{
			$ret['code'] = 201;
			$ret['message'] = 'There is no agent';

			return Response::json($ret);
		}

		if( $status == HOLD )
			$ticket->dial_status = ANSWERED;
		else
			$ticket->dial_status = HOLD;
		$ticket->save();

		if($ticket->dial_status != ANSWERED)
			Functions::saveCallHistory($ticket);

		$data = $this->getAgentStatusData($user_id);
		if( !empty($data) )
		{
			$caller = $this->getCallerProfile($ticket);

			$data->caller = $caller;
			$data->ticket = $ticket;

			$message = [
					'type' => 'incoming',
					'data' => $data
			];
			Redis::publish('notify', json_encode($message));
		}

		return Response::json($ticket);
	}

	public function mute(Request $request) {
		$ticket_id = $request->get('ticket_id', 0);
		$mute = $request->get('mute', 0);

		$ret = array();

		$ticket = IVRVoiceRecording::find($ticket_id);
		if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Ticket';

			return Response::json($ret);
		}

		$user_id = $ticket->user_id;
		if( $user_id < 1 )
		{
			$ret['code'] = 201;
			$ret['message'] = 'There is no agent';

			return Response::json($ret);
		}

		$ticket->mute_flag = $mute;
		$ticket->save();

		Functions::saveCallHistory($ticket);

		$data = $this->getAgentStatusData($user_id);
		if( !empty($data) )
		{
			$caller = $this->getCallerProfile($ticket);

			$data->caller = $caller;
			$data->ticket = $ticket;

			$message = [
					'type' => 'incoming',
					'data' => $data
			];
			Redis::publish('notify', json_encode($message));
		}

		return Response::json($ticket);
	}


	public function transfer(Request $request) {
		$ticket_id = $request->get('ticket_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ticket = IVRVoiceRecording::find($ticket_id);
		if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Ticket';

			return Response::json($ret);
		}

		$user_id = $ticket->user_id;
		if( $user_id < 1 )
		{
			$ret['code'] = 201;
			$ret['message'] = 'There is no agent';

			return Response::json($ret);
		}

		$ticket->dial_status = TRANSFERRED;
		$ticket->save();

		Functions::saveCallHistory($ticket);

		$agent = IVRAgentStatus::where('user_id', $user_id)->first();
		if( !empty($agent) )
		{
			$agent->created_at = $cur_time;
			$agent->status = AVAILABLE;

			$agent->save();

			Functions::saveAgentStatusHistory($agent);
		}

		$data = $this->getAgentStatusData($user_id);
		if( !empty($data) )
		{
			$caller = $this->getCallerProfile($ticket);

			$data->caller = $caller;
			$data->ticket = $ticket;

			$message = [
					'type' => 'incoming',
					'data' => $data
			];
			Redis::publish('notify', json_encode($message));
		}

		$this->redirectCallToAvailableAgent($ticket->property_id);

		return Response::json($ticket);
	}

	public function hangup(Request $request) {
		$ticket_id = $request->get('ticket_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ticket = IVRVoiceRecording::find($ticket_id);
		if( empty($ticket) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Ticket';

			return Response::json($ret);
		}

		$user_id = $ticket->user_id;
		if( $user_id < 1 )
		{
			$ret['code'] = 201;
			$ret['message'] = 'There is no agent';

			return Response::json($ret);
		}

//		$ticket->dial_status = ANSWERED;
//		$ticket->save();
//
//		Functions::saveCallHistory($ticket);

		$agent = IVRAgentStatus::where('user_id', $user_id)->first();
		if( !empty($agent) )
		{
			$agent->created_at = $cur_time;
			$agent->status = $agent->old_status;
			
			$agent->save();

			Functions::saveAgentStatusHistory($agent);
		}

		$data = $this->getAgentStatusData($user_id);
		if( !empty($data) )
		{
			$caller = $this->getCallerProfile($ticket);

			$data->caller = $caller;
			$data->ticket = $ticket;

			$message = [
					'type' => 'incoming',
					'data' => $data
			];
			Redis::publish('notify', json_encode($message));
		}

		$this->redirectCallToAvailableAgent($ticket->property_id);

		return Response::json($ticket);
	}

	private function getCallDestination($callerid) {
		$country = null;
		for( $i = strlen( $callerid ); $i > 0; --$i ) {
			$searchstr = substr($callerid, 0, $i);
			$destination = Destination::where('code', $searchstr)->first();
			if(!empty($destination))  {
				$country = $destination;
				break;
			}
		}

		return $country;
	}

	public function getCallDetail(Request $request){
		$ticket_id = $request->get('ticket_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$ret = array();

		$data = array();
		
		$ticket = IVRVoiceRecording::find($ticket_id);

		if( !empty($ticket) )
		{
			$callerid = $ticket->callerid;
			$data['extension'] = $callerid;	 
			$data['property_id'] = $ticket->property_id;	 
			$data['channel'] =  $ticket->channel;
			
			$adminext = DB::table('call_staff_extn as cse')
					->leftJoin('call_section as cs', 'cse.section_id', '=', 'cs.id')
					->leftJoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')
					->where('extension', $callerid)
					->where('bc_flag', 0)
					->select(DB::raw('cse.*, cs.section, cd.department'))
					->first();
			$guestext = GuestExtension::where('extension', $callerid)
				->first();

			if( !empty($guestext) )
			{
				$room_id = $guestext->room_id;

				// check if guest checkin
				$guest = DB::table('common_guest as cg')
					->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
					->leftJoin('common_room_type as crt', 'cr.type_id', '=', 'crt.id')
					->where('cg.room_id', $room_id)
					->where('cg.departure', '>=', $cur_date)
					->where('cg.checkout_flag', 'checkin')
					->orderBy('cg.departure', 'desc')
					->orderBy('cg.arrival', 'desc')
					->select(DB::raw('cg.*, cr.room, crt.type as room_type'))
					->first();

				// get room number
				


				if( empty($guest) )
				{
					$last_guest = DB::table('common_guest as cg')
						->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
						->leftJoin('common_room_type as crt', 'cr.type_id', '=', 'crt.id')
						->where('cg.room_id', $room_id)
						->orderBy('cg.departure', 'desc')
						->orderBy('cg.arrival', 'desc')
						->select(DB::raw('cg.*, cr.room, crt.type as room_type'))
						->first();

					// in house call
					$data['call_type'] = 'In-House';	 
					$data['room_number'] = $last_guest->room;
					$data['room_type'] = $last_guest->room_type;

					$data['history'] = DB::table('ivr_voice_recording as ivr')
										->leftJoin('common_users as cu', 'ivr.user_id', '=', 'cu.id')
										->where('ivr.callerid', $callerid)
										->where('start_date_time', '>=', $last_guest->departure)
										->select(DB::raw('ivr.start_date_time, ivr.dial_status, ivr.duration, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))										
										->orderBy('ivr.start_date_time', 'desc')
										->get();
				}	
				else
				{
					// guest call
					$data['call_type'] = 'Guest Call';
					$data['guest_name'] = $guest->guest_name;
					$data['room_number'] = $guest->room;
					$data['room_type'] = $guest->room_type;
					$data['arrival'] = $guest->arrival;
					$data['departure'] = $guest->departure;
					$data['vip_status'] = $guest->vip;
					$data['history'] = DB::table('ivr_voice_recording as ivr')
										->leftJoin('common_users as cu', 'ivr.user_id', '=', 'cu.id')
										->where('ivr.callerid', $callerid)
										->where('start_date_time', '>=', $guest->arrival)
										->select(DB::raw('ivr.start_date_time, ivr.dial_status, ivr.duration, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))										
										->orderBy('ivr.start_date_time', 'desc')
										->get();
				}
				$data['comment'] = DB::table('ivr_voice_recording as ivr')
										->where('ivr.callerid', $callerid)	
										->whereNotNull('ivr.comment')		
										->orderBy('ivr.id', 'desc')				
										->select(DB::raw('ivr.comment'))
										->first();
			}	
			else if( !empty($adminext) )
			{
				$data['call_type'] = 'Admin Call';	
				$data['desc'] = $adminext->description;
				$data['section'] = $adminext->section;
				$data['department'] = $adminext->department;

				$data['history'] = DB::table('ivr_voice_recording as ivr')
										->leftJoin('common_users as cu', 'ivr.user_id', '=', 'cu.id')
										->where('ivr.callerid', $callerid)										
										->select(DB::raw('ivr.start_date_time, ivr.dial_status, ivr.duration, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
										->orderBy('ivr.start_date_time', 'desc')
										->limit(10)
										->get();
			}
			else
			{
				$data['call_type'] = 'Outside Call';	 

				// find user
				$caller = DB::table('common_users')
					->where('mobile', $callerid)
					->first();

				if( !empty($caller) )
				{
					$data['first_name'] = $caller->first_name;
					$data['last_name'] = $caller->last_name;
				}
				else
				{
					$caller = DB::table('ivr_call_phonebook')
						->where('property_id', $ticket->property_id)
						->where('calledno', $callerid)
						->first();

					if( !empty($caller) )
					{
						$data['salutation'] = $caller->salutation;
						$data['first_name'] = $caller->first_name;
						$data['last_name'] = $caller->last_name;
						$data['nationality'] = $caller->nationality;
						$data['company'] = $caller->company;
						$data['email'] = $caller->email;
						$data['alt_no'] = $caller->alt_no;
						$data['blacklist'] = $caller->blacklist;
						$data['vip'] = $caller->vip;
					}	
					elseif (!empty($ticket->bridge_id)){
						$data['first_name'] = $ticket->bridge_id;
						$data['last_name'] = '';
						$data['non_exist_flag'] = 1;
					}
					else
					{
						$data['first_name'] = '';
						$data['last_name'] = '';
						$data['non_exist_flag'] = 1;
					}
				}

				$data['comment'] = DB::table('ivr_voice_recording as ivr')
										->where('ivr.callerid', $callerid)	
										->whereNotNull('ivr.comment')		
										->orderBy('ivr.id', 'desc')				
										->select(DB::raw('ivr.comment'))
										->first();

				$data['history'] = DB::table('ivr_voice_recording as ivr')
										->leftJoin('common_users as cu', 'ivr.user_id', '=', 'cu.id')
										->where('ivr.callerid', $callerid)										
										->select(DB::raw('ivr.start_date_time, ivr.dial_status, ivr.duration, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))										
										->orderBy('ivr.start_date_time', 'desc')
										->get();
			}

		}
		
		$ret['code'] = 200;
		$ret['content'] = $data;

		return Response::json($ret);
	}

    public function removeCallPhonebook(Request $request) {
        $id = $request->get('id', 0);

        $success = true;
        if ($id != 0) {
            DB::table('ivr_call_phonebook')
                ->where('id', $id)
                ->delete();
        } else {
            $success = true;
        }

        $ret = [];
        $ret['status'] = $success;

        return Response::json($ret);
    }

	public function getCallPhonebook(Request $request) {
        $user_id = $request->get('user_id', 0);
        $property_id = $request->get('property_id', 0);

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $searchoption = $request->get('searchoption','');
        if ($pageSize < 0)
            $pageSize = 20;

        $ret = array();

        $query = DB::table('ivr_call_phonebook')
          //  ->where('user_id' , $user_id)
            ->where('property_id', $property_id);

        if($searchoption != '') {
            $where = sprintf(" (id like '%%%s%%' or		
								first_name like '%%%s%%' or								
								last_name like '%%%s%%' or								
								calledno like '%%%s%%' 								
								)",
                $searchoption, $searchoption, $searchoption,  $searchoption
            );
            $query->whereRaw($where);
        }

        $data_query = clone $query;

        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->select(DB::raw('*'))
            ->skip($skip)->take($pageSize)
            ->get();

        $ret['datalist'] = $data_list;
        return Response::json($ret);
    }

	public function addUserToPhonebook(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$calledno1 = $request->get('extension', '');
		$calledno2 = $request->get('calledno', '');
		$calledno = $calledno1 | $calledno2;

		$salutation = $request->get('salutation', '');
		$first_name = $request->get('first_name', '');
		$last_name = $request->get('last_name', '');
		$nationality = $request->get('nationality', '');
		$company = $request->get('company', '');
		$email = $request->get('email', '');
		$alt_no = $request->get('alt_no', '');
		$blacklist = $request->get('blacklist', '');
		$vip = $request->get('vip', '');
		$user_id = $request->get('user_id', 0);
		$id = $request->get('id', 0);

		$message = '';

        $ret = [];
        $ret['code'] = 200;
        $ret['message'] = '';
        $ret['success'] = true;

        if ($id === 0) {
            // check by calledno
            $current = DB::table('ivr_call_phonebook')
                ->where('calledno', $calledno)
                ->first();
            if (!empty($current)) {
                $ret['message'] = "Existing phone number!";
                $ret['success'] = false;
            } else {
                DB::table('ivr_call_phonebook')
                    ->insert([
                        'property_id' => $property_id,
						'calledno' => $calledno,
						'salutation' => $salutation,
                        'first_name' => $first_name,
						'last_name' => $last_name,
						'nationality' => $nationality,
						'company' => $company,
						'email' => $email,
						'alt_no' => $alt_no,
						'blacklist' => $blacklist,
						'vip' => $vip,
                        'user_id'   => $user_id
                    ]);

                $ret['message'] = "Successfully added!";
                $ret['success'] = true;
            }
        } else {
            $current = DB::table('ivr_call_phonebook')
                ->where('calledno', $calledno)
                ->where('id', '!=', $id)
                ->first();

            if (!empty($current)) {
                $ret['message'] = "Existing phone number!";
                $ret['success'] = false;
            } else {
                DB::table('ivr_call_phonebook')
                    ->where('id', $id)
                    ->update([
                        'property_id' => $property_id,
						'calledno' => $calledno,
						'salutation' => $salutation,
                        'first_name' => $first_name,
						'last_name' => $last_name,
						'nationality' => $nationality,
						'company' => $company,
						'email' => $email,
						'alt_no' => $alt_no,
						'blacklist' => $blacklist,
						'vip' => $vip,
                    ]);
                $ret['message'] = "Successfully updated!";
                $ret['success'] = true;
            }
        }

		return Response::json($ret);
	}

	public function changeAgentStatus(Request $request)
	{
		$agent_id = $request->get('agent_id', 0);
		$status = $request->get('status', '');
		$extension = $request->get('extension', '');
		$property_id = $request->get('property_id', '');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$job_roles = PropertySetting::getJobRoles($property_id);

		$agent_status = IVRAgentStatus::where('user_id', $agent_id)->first();
		if( empty($agent_status) ) {
			$agent_flag = DB::table('common_users as cu')
					->where('cu.job_role_id', $job_roles['callcenteragent_job_role'])
					->where('cu.id', $agent_id)
					->exists();
			if( $agent_flag == false )
			{
				return Response::json($agent_status);
			}

			$agent_status = new IVRAgentStatus();
			$agent_status->user_id = $agent_id;
		}

		if( $status != '' )
			$agent_status->status = $status;

		if( $extension != '' )
			$agent_status->extension = $extension;

		$agent_status->created_at = $cur_time;
		Functions::assignExtension($agent_status);

		if($status == LOGOUT)
			$agent_status->extension = '';

		$agent_status->save();

		Functions::saveAgentStatusHistory($agent_status);

		$agent = $this->getAgentStatusData($agent_id);

		$message = [
				'type' => 'changeagentstatus',
				'data' => $agent
		];

		Redis::publish('notify', json_encode($message));

		if($status == AVAILABLE) {
			$this->redirectCallToAvailableAgent($property_id);
		}

		return Response::json($agent_status);
	}

	public function changeAgentExtension(Request $request)
	{
		$agent_id = $request->get('agent_id', 0);
		$extension = $request->get('extension', '');
		$property_id = $request->get('property_id', '');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$agent_status = IVRAgentStatus::where('user_id', $agent_id)->first();
		if( empty($agent_status) ) {
//			$agent_flag = DB::table('common_users as cu')
//					->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
//					->where('jr.job_role', 'Agents')
//					->where('cu.id', $agent_id)
//					->exists();
//			if( $agent_flag == false )
//			{
//				return Response::json($agent_status);
//			}

			$agent_status = new IVRAgentStatus();
			$agent_status->user_id = $agent_id;
		}

		if( $extension != '' )
			$agent_status->extension = $extension;

		Functions::assignExtension($agent_status);

		$agent_status->save();

		return Response::json($agent_status);
	}

	public function saveCallLog(Request $request) {
		$user_id = $request->get('user_id', 0);
		$callerid = $request->get('callerid', 0);
		$national = $request->get('national','');
		$mobile = $request->get('mobile','');
		$company = $request->get('company','');
		$companyname = $request->get('companyname','');
		$address = $request->get('address','');
		$salutation = $request->get('salutation','');
		$firstname = $request->get('firstname', '');
		$lastname = $request->get('lastname', '');
		$email = $request->get('email', '');
		$phone = $request->get('phone', '');
		$spam = $request->get('spam',0);

		$ticket_id = $request->get('ticket_id',0);
		$type = $request->get('type');
		$channel = $request->get('channel','Others');
		$comment = $request->get('comment','');
		$follow = $request->get('follow',0);
		$success = $request->get('success' ,0);
		$confirm = $request->get('confirm','');
		$sendconfirm = $request->get('sendconfirm','');
		if(empty($channel))
		$channel='Others';
		$profile = IVRCallProfile::where('callerid', $callerid)->first();
		if( empty($profile) ) {
			$profile = new IVRCallProfile();
			$profile->callerid = $callerid;
		}

		$profile->national = $national;
		$profile->mobile = $mobile;
		$profile->company = $company;
		$profile->companyname = $companyname;
		$profile->address = $address;
		$profile->salutation =$salutation;
		$profile->firstname = $firstname;
		$profile->lastname = $lastname;
		$profile->email = $email;
		$profile->phone = $phone;
		$profile->spam = $spam;

		$profile->save();

		$ticket = IVRVoiceRecording::find($ticket_id);

		if( !empty($ticket) )
		{
			$ticket->type = $type;
			$ticket->channel = $channel;
			$ticket->comment = $comment;
			$ticket->follow = $follow;
			$ticket->success = $success;
			$ticket->confirm = $confirm;
			$ticket->sendconfirm = $sendconfirm;
			if( $ticket->dial_status == CALLBACK && $ticket->callback_flag == 2 )
				$ticket->callback_flag = 0;

			$ticket->save();
		}

		$agent = IVRAgentStatus::where('user_id', $user_id)->first();
		//echo json_encode($agent);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$prev_status = $agent->status;
		if( !empty($agent) )
		{
			if( $agent->status == WRAPUP || $agent->status == OUTGOING )
			{
				$agent->status = AVAILABLE;
				$agent->created_at = $cur_time;
				$agent->save();

				Functions::saveAgentStatusHistory($agent);	
			}
			

			$data = $this->getAgentStatusData($user_id);

			// notify callback event
			$caller = $this->getCallerProfile($ticket);

			$data->caller = $caller;
			$data->ticket = $ticket;

			$message = array();

			if( $prev_status == OUTGOING )
				$message['type'] = 'callback_event';
			else
				$message['type'] = 'incoming';

			$message['data'] = $data;

			Redis::publish('notify', json_encode($message));

			$this->redirectCallToAvailableAgent($ticket->property_id);
		}

		return Response::json($profile);
	}

	public function updateCallLog(Request $request) {
		$user_id = $request->get('user_id', 0);
		$callerid = $request->get('callerid', 0);
		$national = $request->get('national','');
		$mobile = $request->get('mobile','');
		$company = $request->get('company','');
		$companyname = $request->get('companyname','');
		$address = $request->get('address','');
		$salutation = $request->get('salutation','');
		$firstname = $request->get('firstname', '');
		$lastname = $request->get('lastname', '');
		$email = $request->get('email', '');
		$phone = $request->get('phone', '');
		$spam = $request->get('spam',0);

		$ticket_id = $request->get('ticketid',0);
		$type = $request->get('type');
		$comment = $request->get('comment','');
		$profile = IVRCallProfile::where('callerid', $callerid)->first();
		if( empty($profile) ) {
			$profile = new IVRCallProfile();
			$profile->callerid = $callerid;
		}

		$profile->national = $national;
		$profile->mobile = $mobile;
		$profile->company = $company;
		$profile->companyname = $companyname;
		$profile->address = $address;
		$profile->salutation =$salutation;
		$profile->firstname = $firstname;
		$profile->lastname = $lastname;
		$profile->email = $email;
		$profile->phone = $phone;
		$profile->spam = $spam;

		$profile->save();

		$ticket = IVRVoiceRecording::find($ticket_id);

		if( !empty($ticket) )
		{
			$ticket->type = $type;
			$ticket->comment = $comment;
			$ticket->save();
		}
		
		return Response::json($profile);
	}

	public function getAgentList(Request $request) {
		$property_id = $request->get('property_id', 0);

		$agentlist = DB::table('ivr_agent_status_log as asl')
			->join('common_users as cu', 'asl.user_id', '=', 'cu.id')
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->where('cd.property_id', $property_id)
			->select(DB::raw('asl.user_id as id, CONCAT_WS(" ", cu.first_name, cu.last_name) as label'))
			->get();

		return Response::json($agentlist);
	}

	public function getAgentList1(Request $request) {
		$property_id = $request->get('property_id', 0);

		$size = $request->get('size', 20);
		$pageNumber = $request->get('number', 0);
		$skip = $pageNumber * $size;

		$query = DB::table('ivr_agent_status_log as asl')
			->join('common_users as cu', 'asl.user_id', '=', 'cu.id')
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->leftJoin('ivr_status_priority as sp', 'asl.status', '=', 'sp.status');

		$agentlist = $query->where('cd.property_id', $property_id)
					->orderBy('sp.priority', 'asc')
					->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent'))
            		->skip($skip)->take($size)
					->get();

		$rules = $this->getCallcenterThresholdSetting($property_id);

		$ret['agentlist'] = $agentlist;
		$ret['threshold'] = $rules;

		return Response::json($ret);
	}

	public function getIvrCallTypeList(Request $request) {
		//$property_id = $request->get('property_id', 0);

		$typelist = DB::table('ivr_call_types as ict')
			//->where('cd.property_id', $property_id)
			->select(DB::raw('ict.id,ict.label'))
			->get();

		return Response::json($typelist);
	}
	public function getTypeList() {
		$agentlist = DB::table('ivr_voice_recording')
				->whereRaw(" type != 'null' ")
				->select(DB::raw(' distinct(type) as label '))
				->get();
		return Response::json($agentlist);
	}

	public function getCallTypeList() {
		$agentlist = DB::table('ivr_voice_recording')
				->whereRaw(" call_type != 'null' ")
				->select(DB::raw(' distinct(call_type) as label '))
				->get();
		return Response::json($agentlist);
	}
	
	public function getChannelList() {
		$agentlist = DB::table('ivr_voice_recording')
				->whereRaw(" channel != 'null' ")
				->select(DB::raw(' distinct(channel) as label '))
				->get();
			return Response::json($agentlist);
	}

	public function getAutoTypeList() {
		$agentlist = DB::table('ivr_auto_attendant')
				->whereRaw(" type != 'null' ")
				->select(DB::raw(' distinct(type) as label '))
				->get();
		return Response::json($agentlist);
	}

	public function getAutoCallTypeList() {
		$agentlist = DB::table('ivr_auto_attendant')
				->whereRaw(" call_type != 'null' ")
				->select(DB::raw(' distinct(call_type) as label '))
				->get();
		return Response::json($agentlist);
	}

	public function getAgentCallList(Request $request)
	{
		$user_id = $request->input('user_id', 0);
		$start_date = $request->input('day', '');
		$caller_id = $request->input('caller_id', 0);
		$page = $request->input('page', 0);
		$pageSize = $request->input('pagesize', 20);
		$skip = $page;
		$orderby = $request->input('field', 'ivr.id');
		$sort = $request->input('sort', 'asc');
        $category = $request->input('category','');
		$searchoption = $request->input('searchoption','');

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();

		$query = DB::table('ivr_voice_recording as ivr')
			->leftJoin('ivr_caller_profile as pro', 'ivr.callerid', '=', 'pro.callerid')
			->leftJoin('common_users as cu', 'ivr.user_id', '=', 'cu.id');

		if ($user_id !== 0) {
            $query->where('ivr.user_id' , $user_id);
        }

		if( $caller_id > 0 )
			$query->where('ivr.callerid', $caller_id);
		if( $start_date != '' )
			$query->whereRaw("DATE(ivr.start_date_time) = '" . $start_date . "'") ;
//
		$sub_count_query = clone $query;
//
		if( $category != '' && $category != 'Follow Up' )
			$query->whereRaw("ivr.dial_status = '" . $category . "'") ;

		if( $category == 'Follow Up' )
			$query->whereRaw("ivr.follow = 1") ;

		if($searchoption != '') {
			$where = sprintf(" (ivr.id like '%%%s%%' or		
								ivr.callerid like '%%%s%%' or								
								cu.first_name like '%%%s%%' or								
								cu.last_name like '%%%s%%' 								
								)",
				$searchoption, $searchoption, $searchoption,  $searchoption
			);
			$query->whereRaw($where);
		}


		$data_query = clone $query;

		$data_list = $data_query
			->orderBy($orderby, $sort)
			->select(DB::raw('pro.*, ivr.user_id as user_id, date(ivr.start_date_time) as cudate, time(ivr.start_date_time) as cutime, ivr.id as ticketid, 
				ivr.start_date_time as starttime, ivr.duration,  CONCAT_WS(" ", pro.firstname, pro.lastname) as tickname, 
				CONCAT_WS(" ", cu.first_name, cu.last_name) as agentname , ivr.type, ivr.channel, ivr.success, ivr.confirm, ivr.callerid' ))
			->skip($skip)->take($pageSize)
			->get();

		foreach($data_list as $row){

			$guestext = GuestExtension::where('extension', $row->callerid)->where('enable', 1)->first();
			if (!empty($guestext))
			{
				$guest = Guest::where('room_id', $guestext->room_id)
							->where('checkout_flag', 'checkin')
							->first();
				if (!empty($guest)){
					$row->tickname = $guest->guest_name;
					$row->mobile = $guestext->extension;
				}
				else
			 	{
					 $row->tickname = 'Vacant';
					 $row->mobile = $guestext->extension;
			 	}
			}
			else{
				$caller = DB::table('ivr_call_phonebook')
						->where('calledno', $row->callerid)
						->first();

				if( !empty($caller) ){
					$row->tickname = $caller->first_name . ' ' . $caller->last_name;
					$row->mobile = $caller->calledno;
				}
				else{
					$row->tickname = '';
					$row->mobile = $row->callerid;
					
				}
				
			}
			 


		}

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$subcount = $sub_count_query
				->select(DB::raw("COALESCE(sum(ivr.dial_status = '". ANSWERED ."'), 0) as answered,
								COALESCE(sum(ivr.dial_status = '". MISSED ."'), 0) as missed,
								COALESCE(sum(ivr.dial_status = '". OUTGOING ."'), 0) as outgoing,
								COALESCE(sum(ivr.follow = 1), 0) as followup,
								COALESCE(sum(ivr.dial_status = '". ABANDONED ."'), 0) as abandoned"))
				->first();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;
		$ret['subcount'] = $subcount;
		return Response::json($ret);
	}

	public function checkCallCenterState(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$to_time = strtotime($cur_time);

		$ret = array();

		$agentlist = DB::table('ivr_agent_status_log as asl')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->whereIn('asl.status', array(ONLINE, AVAILABLE, ONBREAK))
				->select(DB::raw('asl.*, cd.property_id'))
				->get();

		// Online to Idle
		foreach($agentlist as $row) {
			$from_time = strtotime($row->created_at);

			$idle_duration = DB::table('property_setting as ps')
				->where('ps.property_id', $row->property_id)
				->where('ps.settings_key', 'idle_duration')
				->select(DB::raw('ps.*'))
				->first();

			if( empty($idle_duration) )
				$duration = 20; // 20min
			else
				$duration = $idle_duration->value;

			if(  $to_time - $from_time > $duration * 60 )	// idle timeout
			{
				$agent = IVRAgentStatus::find($row->id);
				$agent->status = IDLE;
				$agent->created_at = $cur_time;

				$agent->save();

				$data = $this->getAgentStatusData($agent->user_id);

				Functions::saveAgentStatusHistory($agent);

				$message = array();
				$message['type'] = 'idle';
				$message['data'] = $data;

				Redis::publish('notify', json_encode($message));

				$ret[] = $agent;
			}
		}

		// Idle to Logout
		$agentlist = DB::table('ivr_agent_status_log as asl')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->whereIn('asl.status', array(IDLE))
				->select(DB::raw('asl.*, cd.property_id'))
				->get();

		foreach($agentlist as $row) {
			$from_time = strtotime($row->created_at);

			$max_idle_duration = DB::table('property_setting as ps')
					->where('ps.property_id', $row->property_id)
					->where('ps.settings_key', 'max_idle_duration')
					->select(DB::raw('ps.*'))
					->first();

			if( empty($max_idle_duration) )
				$max_duration = 180; // 20min
			else
				$max_duration = $max_idle_duration->value;


			if(  $to_time - $from_time > $max_duration * 60 )	// max idle timeout
			{
				$agent = IVRAgentStatus::find($row->id);
				$agent->status = LOGOUT;
				$agent->created_at = $cur_time;

				$agent->save();

				Functions::saveAgentStatusHistory($agent);

				$data = $this->getAgentStatusData($agent->user_id);

				$message = array();
				$message['type'] = 'changeagentstatus';
				$message['data'] = $data;

				Redis::publish('notify', json_encode($message));

				$ret[] = $agent;
			}
		}

		// Wrapup to Available
		$agentlist = DB::table('ivr_agent_status_log as asl')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->leftJoin('ivr_voice_recording as ivr', 'asl.ticket_id', '=', 'ivr.id')
				->whereIn('asl.status', array(WRAPUP))
				->select(DB::raw('asl.*, ivr.dial_status, cd.property_id'))
				->get();

		foreach($agentlist as $row) {
			$from_time = strtotime($row->created_at);

			$rules = array();
			$rules['max_wrapup_time'] = 20;
			$rules['max_outgoing_wrapup_time'] = 20;

			$rules = PropertySetting::getPropertySettings($row->property_id, $rules);
			
			if( $row->dial_status == OUTGOING )
				$duration = $rules['max_outgoing_wrapup_time'];
			else
				$duration = $rules['max_wrapup_time'];
			
			if(  $to_time - $from_time > $duration * 60 )	// max wrap time out
			{
				$agent = IVRAgentStatus::find($row->id);
				$agent->status = AVAILABLE;
				$agent->created_at = $cur_time;

				$agent->save();

				Functions::saveAgentStatusHistory($agent);

				$data = $this->getAgentStatusData($agent->user_id);

				$message = array();
				$message['type'] = 'changeagentstatus';
				$message['data'] = $data;

				Redis::publish('notify', json_encode($message));

				$ret[] = $agent;

				$this->redirectCallToAvailableAgent($data->property_id);
			}
		}

		$this->checkAgentAvailable();
		$this->checkLongestWaitingCall();

		return Response::json($ret);
	}
	public function checkAgent(Request $request)
	{
		$skill_group_id = $request->get('skill', "");
		$property_id = $request->get('property_id',0);
		$agentlist = IVRAgentStatus::checkAvailbleAgent($property_id, $skill_group_id);
		
		if( empty($agentlist) || count($agentlist) < 1 )		// There is no free agent
		{
			
			$ret['flag'] = 2;
			return Response::json($ret);
			
		
		}


		else 
		{
			$ret['flag'] = 1;
			return Response::json($ret);
			
		}
		
		
	}
	public function checkAgentAvailable() {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$to_time = strtotime($cur_time);

		$property_list = Property::all();

		foreach($property_list as $property) {
			$no_avail_email = DB::table('property_setting as ps')
					->where('ps.property_id', $property->id)
					->where('ps.settings_key', 'no_avail_email')
					->select(DB::raw('ps.*'))
					->first();

			if( empty($no_avail_email) || empty($no_avail_email->value) )
				continue;

			$no_avail_time = DB::table('property_setting as ps')
					->where('ps.property_id', $property->id)
					->where('ps.settings_key', 'no_avail_time')
					->select(DB::raw('ps.*'))
					->first();

			if( empty($no_avail_time) )
				$duration = 20; // 20min
			else
				$duration = $no_avail_time->value;

			$exist = DB::table('ivr_agent_status_log as asl')
					->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
					->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->where('cd.property_id', $property->id)
					->where('asl.status', AVAILABLE)
					->exists();


			$callcenter_state = IVRCallcenterState::where('property_id', $property->id)->first();

			if( $exist == true ) {
				if( empty($callcenter_state) ) {
					$callcenter_state = new IVRCallcenterState();
					$callcenter_state->property_id = $property->id;
				}

				$callcenter_state->no_avail_send_flag = 0;
				$callcenter_state->save();

				continue;
			}

			if( !empty($callcenter_state) && $callcenter_state->no_avail_send_flag == 1 )	// already sent
				continue;

			// get last available and agent
			$last_available_agent = DB::table('ivr_agent_status_history as ash')
				->join('common_users as cu', 'ash.user_id', '=', 'cu.id')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cd.property_id', $property->id)
				->where('ash.status', AVAILABLE)
				->orderBy('id', 'desc')
				->select(DB::raw('ash.*'))
				->first();

			$message = array();
			$message['type'] = 'email';
			$message['to'] = $no_avail_email->value;
			$message['subject'] = 'Hotlync Notification';
			$message['title'] = 'There are currently no available agents to receive calls';

			$message['smtp'] = Functions::getMailSetting($property->id, 'notification_');

			$content = null;

			if( empty($last_available_agent) )		// There is no last available
			{
				$content = 'The last available agent does not exist';
			}
			else
			{
				// find next unavailable time
				$last_unavailable_agent = DB::table('ivr_agent_status_history as ash')
						->join('common_users as cu', 'ash.user_id', '=', 'cu.id')
						->where('ash.user_id', $last_available_agent->user_id)
						->where('ash.id', '>', $last_available_agent->id)
						->select(DB::raw('ash.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
						->first();

				if( !empty($last_unavailable_agent) )
				{
					$from_time = strtotime($last_unavailable_agent->created_at);

					if(  $to_time - $from_time > $duration * 60 )	// max idle timeout
					{
						$content = 'The last available agent was ' . $last_unavailable_agent->wholename . ' at ' . $last_unavailable_agent->created_at;
					}
				}
			}

			if( !empty($content) )
			{
				$info = array(
						'title' => $message['title'],
						'content' => $content,
				);

				$message['content'] = view('emails.reminder', ['info' => $info])->render();

				Redis::publish('notify', json_encode($message));

				// update no avaiable event state

				if( empty($callcenter_state) )
					$callcenter_state = new IVRCallcenterState();
				$callcenter_state->property_id = $property->id;
				$callcenter_state->no_avail_send_flag = 1;
				$callcenter_state->created_at = $cur_time;

				$callcenter_state->save();
			}

		}
	}

	public function checkAutoAttendantState() {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$to_time = strtotime($cur_time);

		$internal = 'Internal';
		$external = 'External';

		$property_list = Property::all();

		foreach($property_list as $property) {
			$auto_attn_email = DB::table('property_setting as ps')
					->where('ps.property_id', $property->id)
					->where('ps.settings_key', 'auto_attn_email')
					->select(DB::raw('ps.*'))
					->first();

			if( empty($auto_attn_email) || empty($auto_attn_email->value) )
				continue;

			$auto_attn_time = DB::table('property_setting as ps')
					->where('ps.property_id', $property->id)
					->where('ps.settings_key', 'auto_attn_time')
					->select(DB::raw('ps.*'))
					->first();

			$last_trigger_int = DB::table('property_setting as ps')
					->where('ps.property_id', $property->id)
					->where('ps.settings_key', 'auto_attn_last_trigger_internal')
					->select(DB::raw('ps.*'))
					->first();

			$last_trigger_ext = DB::table('property_setting as ps')
					->where('ps.property_id', $property->id)
					->where('ps.settings_key', 'auto_attn_last_trigger_external')
					->select(DB::raw('ps.*'))
					->first();

			if( empty($auto_attn_time) )
				$duration = 30; // 20min
			else
				$duration = $auto_attn_time->value;

			$auto_attn_cc_email = DB::table('property_setting as ps')
				->where('ps.property_id', $property->id)
				->where('ps.settings_key', 'auto_attn_cc_email')
				->select(DB::raw('ps.*'))
				->first();

			$query_internal = DB::table('ivr_auto_attendant')->where('property_id', $property->id)->where('type', $internal);
			$max_query_internal = clone $query_internal;
			$last_internal_id=$max_query_internal->max('id');

			$query_external = DB::table('ivr_auto_attendant')->where('property_id', $property->id)->where('type', $external);
			$max_query_external = clone $query_external;
			$last_external_id=$max_query_external->max('id');

			$last_call_internal = DB::table('ivr_auto_attendant as iaa')
					->where('iaa.property_id', $property->id)
					->where('iaa.id', $last_internal_id)
					->first();

			$last_call_external = DB::table('ivr_auto_attendant as iaa')
					->where('iaa.property_id', $property->id)
					->where('iaa.id', $last_external_id)
					->first();

/*
			$callcenter_state = IVRCallcenterState::where('property_id', $property->id)->first();

			if( $exist == true ) {
				if( empty($callcenter_state) ) {
					$callcenter_state = new IVRCallcenterState();
					$callcenter_state->property_id = $property->id;
				}

				$callcenter_state->no_avail_send_flag = 0;
				$callcenter_state->save();

				continue;
			}

			if( !empty($callcenter_state) && $callcenter_state->no_avail_send_flag == 1 )	// already sent
				continue;
*/
/*
			// get last available and agent
			$last_available_agent = DB::table('ivr_agent_status_history as ash')
				->join('common_users as cu', 'ash.user_id', '=', 'cu.id')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cd.property_id', $property->id)
				->where('ash.status', AVAILABLE)
				->orderBy('id', 'desc')
				->select(DB::raw('ash.*'))
				->first();
*/
			$message = array();
			$message['type'] = 'email';
			$message['to'] = $auto_attn_email->value;
			if(!empty($auto_attn_cc_email->value)){
				$message['cc'] = $auto_attn_cc_email->value;
			}
			$message['subject'] = 'Auto Attendant Last Call Warning';
			$message['title'] = 'Auto Attendant Last Call Warning';

			$message['smtp'] = Functions::getMailSetting($property->id, 'notification_');

			$content_internal = null;
			$content_external = null;

	//		if( empty($last_available_agent) )		// There is no last available
	//		{
	//			$content = 'The last available agent does not exist';
	//		}
	//		else
	//		{
				
				if( !empty($last_call_internal->start_date_time) )
				{
					$from_time = strtotime($last_call_internal->start_date_time);

					if(  $to_time - $from_time > $duration * 60 )	// last call threshold check
					{
						$content_internal = 'No Internal Calls have been received for last ' . $duration . ' minutes';
					}
				}

				if( !empty($last_call_external->start_date_time) )
				{
					$from_time = strtotime($last_call_external->start_date_time);

					if(  $to_time - $from_time > $duration * 60 )	// last call threshold check
					{
						$content_external = 'No External Calls have been received for last ' . $duration . ' minutes';
					}
				}
	//		}


			$int_from_time = strtotime($last_trigger_int->value);
			$ext_from_time = strtotime($last_trigger_ext->value);

			if ($to_time - $int_from_time > $duration * 60 ){
	
			if( !empty($content_internal) )
			{
				$info = array(
						'title' => $message['title'],
						'content' => $content_internal,
				);

				$message['content'] = view('emails.auto_attendant', ['info' => $info])->render();

				Redis::publish('notify', json_encode($message));

				// update no avaiable event state
/*
				if( empty($callcenter_state) )
					$callcenter_state = new IVRCallcenterState();
				$callcenter_state->property_id = $property->id;
				$callcenter_state->no_avail_send_flag = 1;
				$callcenter_state->created_at = $cur_time;

				$callcenter_state->save();
				*/

				DB::table('property_setting')
					->where('property_id', $property->id)
					->where('settings_key', 'auto_attn_last_trigger_internal')
					->update(['value' => $cur_time]);

					
			}
			}

			if ($to_time - $ext_from_time > $duration * 60 ){

			if( !empty($content_external) )
			{
				$info = array(
						'title' => $message['title'],
						'content' => $content_external,
				);

				$message['content'] = view('emails.auto_attendant', ['info' => $info])->render();

				Redis::publish('notify', json_encode($message));

				// update no avaiable event state
/*
				if( empty($callcenter_state) )
					$callcenter_state = new IVRCallcenterState();
				$callcenter_state->property_id = $property->id;
				$callcenter_state->no_avail_send_flag = 1;
				$callcenter_state->created_at = $cur_time;

				$callcenter_state->save();
				*/
				DB::table('property_setting')
					->where('property_id', $property->id)
					->where('settings_key', 'auto_attn_last_trigger_external')
					->update(['value' => $cur_time]);

			}
		}

		}

		return Response::json([]);
	}

	public function checkCallBackCall() {

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$to_time = strtotime($cur_time);

		$property_list = Property::all();

		foreach($property_list as $property) {

		$calls = DB::table('ivr_voice_recording as vr')
				->leftJoin('ivr_agent_status_log as asl', 'vr.user_id', '=', 'asl.user_id')
				->leftJoin('common_users as cu', 'vr.user_id', '=', 'cu.id')
				->leftJoin('ivr_caller_profile as cp', 'vr.callerid', '=', 'cp.callerid')
				->leftJoin('call_destination as dest', 'vr.call_origin', '=', 'dest.id')
				->where('vr.callback_flag', 1)
				->where('vr.escalate_flag', 0)
				->where('vr.property_id', $property->id)
				->select(DB::raw('vr.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, asl.status, CONCAT_WS(" ", cp.firstname, cp.lastname) as caller_name, cp.email, cp.companyname, cp.national, dest.country'))
				->get();

		foreach($calls as $call){

			$from_time = strtotime($call->start_date_time);

			$skillgroup = DB::table('ivr_call_center_skill_group')
						->select(DB::raw('email, duration'))
						->where('id', $call->skill_group_id)
						->first();

			
			
			if (!empty($skillgroup->email) && !empty($skillgroup->duration)){

			$message = array();
			$message['type'] = 'email';
			$message['to'] = $skillgroup->email;
			$message['subject'] = 'No CallBack Warning';
			$message['title'] = 'No CallBack Warning';

			$message['smtp'] = Functions::getMailSetting($property->id, 'notification_');

			$content = null;

			if (($to_time - $from_time > $skillgroup->duration * 60)  &&  ($call->agent_take == NULL))	
				{
					$content = 'No Callback has been done by Agents from last ' . $skillgroup->duration . ' minutes';
				

			if( !empty($content) )
			{
				$info = array(
						'title' => $message['title'],
						'content' => $content,
						'agent' => $call->wholename,
						'status' => $call->dial_status,
						'origin' => $call->country,
						'call_time' => $call->start_date_time,
						'callerid' => $call->callerid,
						'type' => $call->type,
						'channel' => $call->channel,
						'call_type' => $call->call_type,
				);

				$message['content'] = view('emails.callback_reminder', ['info' => $info])->render();

				Redis::publish('notify', json_encode($message));

				// update mail sent flag
				$ticket = IVRVoiceRecording::find($call->id);
				$ticket->escalate_flag = 1;
				$ticket->save();
			}	
		}
				
			}

		}
	}	
		return Response::json($calls);
	}

	public function redirectCallToAvailableAgent($property_id) {
		// find free agent
		sleep(5);
		$agentlist = DB::table('ivr_agent_status_log as asl')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('asl.status', AVAILABLE)
				->where('cd.property_id', $property_id)
				->orderBy('created_at', 'asc')
				->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cd.property_id'))
				->get();
		if( empty($agentlist) || count($agentlist) < 1 )
			return;

		// pick queue call
		$ticketlist = DB::table('ivr_recording_queue as qu')
			->leftJoin('ivr_voice_recording as ivr' ,'qu.ticket_id','=','ivr.id')
			->where('ivr.property_id', $property_id)
			//->limit(count($agentlist))
			->orderBy('qu.order_num', 'asc')
			//->orderBy('qu.ticket_id', 'asc')
			->orderBy('qu.priority', 'desc')
			->select(DB::raw('ivr.*, qu.id as queueid'))
			->get();

		if(empty($ticketlist))  //There is no queue
		  return;
		date_default_timezone_set(config('app.timezone'));		
		foreach($ticketlist as $key => $ticket)
		{ 
			$cur_time = date("Y-m-d H:i:s");
			$agentlist = IVRAgentStatus::getAvailbleAgentList($property_id, $ticket->skill_group_id, 0);
			if( count($agentlist) < 1 )
				continue;

			$agent = $agentlist[0];
			$agent = IVRAgentStatus::find($agentlist[0]->id);
			$agent->old_status = $agent->status;
                        $agent->status = RINGING;
                        $agent->created_at = $cur_time;
                        $agent->ticket_id = $ticket->id;
                        $agent->save();

                               Functions::saveAgentStatusHistory($agent);

			$data = array();
			$data['agent'] = $agent;
			$data['ticket'] = $ticket;

			$message = array();
			$message['type'] = 'queue_redirect';
			$message['data'] = $data;
			Redis::publish('notify', json_encode($message));

			DB::table('ivr_recording_queue')
					->where('id', '=', $ticket->queueid)
					->delete();

			$this->checkQueueCount($property_id, 'Delete');
		}

		if( count($ticketlist) > 0 )
		{
			$ticket = $ticketlist[0];
			$this->sendQueueChangeEvent($ticket->id);
		}
	}

	private function sendQueueChangeEvent($ticket_id)
	{
		$ticket = IVRVoiceRecording::find($ticket_id);
		if( empty($ticket) )
			return;
			
		$message = [
			'type' => 'queue_event',
			'data' => $ticket
		];
		Redis::publish('notify', json_encode($message));
	}

	public function fixAgentStatusDuration() {
		Functions::fixAgentStatusDuration();
	}

	public function getStaffList(Request $request)
	{
		$property_id = $request->get('property_id', '0');

		$ret = DB::table('common_users as cu')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
			->leftJoin('common_department as de','cu.dept_id','=','de.id')
			->where('de.property_id', $property_id)
			->select(DB::raw('cu.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename , de.department'))
			->orderby('cu.first_name')
			->get();
		return Response::json($ret);
	}

	public function getSIPContactList(Request $request)
	{
		$property_id = $request->get('property_id', 0);

		$staff_ext_list = DB::table('call_staff_extn as se')
			->join('call_section as cs', 'se.section_id', '=', 'cs.id')
			->join('common_department as cd', 'cs.dept_id', '=', 'cd.id')
			->join('common_building as cb', 'cs.building_id', '=', 'cb.id')
			->where('cb.property_id', $property_id)
			->select(DB::raw('se.description as name, se.extension, cd.department'))
			->get();

		$guest_ext_list = DB::table('call_guest_extn as ge')	
			->join('common_room as cr', 'ge.room_id', '=', 'cr.id')
			->join('common_guest as cg', 'ge.room_id', '=', 'cg.room_id')
			->join('common_building as cb', 'ge.bldg_id', '=', 'cb.id')
			->where('cb.property_id', $property_id)
			->where('cg.checkout_flag', 'checkin')
			->groupBy('ge.room_id')
			->select(DB::raw('cg.guest_name, cr.room, ge.room_id, cr.room, ge.extension, cg.email, cg.arrival, cg.departure, cg.vip'))
			->get();

		$list = [];	
		foreach($staff_ext_list as $row)	
		{
			$list[] = [
				'name' => $row->name,
				'extension' => $row->extension,
				'email' => '',
				'department' => $row->department,
				'type' => 1,
				'period' => '',
				'vip' => '',
			];
		}

		foreach($guest_ext_list as $row)	
		{
			$list[] = [
				'name' => "$row->guest_name - $row->room",
				'extension' => $row->extension,
				'email' => $row->email,
				'department' => 'Guest',
				'type' => 2,
				'period' => date('d M Y', strtotime($row->arrival)) . " ~ " . date('d M Y', strtotime($row->departure)),
				'vip' => ($row->vip == 1 ? 'VIP' : ''),
				'room_id' => $row->room_id,
				'room' => $row->room,
				'property_id' => $property_id 
			];
		}

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $list;

		return Response::json($ret);
	}

	public function testPHPGraph() {
		$property_id = 4;
		date_default_timezone_set(config('app.timezone'));
		$end_date = date("Y-m-d");

		$agent_list = DB::table('ivr_agent_status_log as asl')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->leftJoin('ivr_status_priority as sp', 'asl.status', '=', 'sp.status')
				->where('cd.property_id', $property_id)
				->orderBy('sp.priority', 'asc')
				->orderBy('asl.created_at', 'desc')
				->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent, cu.picture'))
				->get();

		$data = array();

		$data = $this->getStaticsticsByDate($end_date, 30, $agent_list, $property_id, [], []);

		$data['hourly_statistics'] = $this->getHourlyStatistics($agent_list, "");

		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P' . 30 . 'D'));

		$query = DB::table('ivr_voice_recording as ivr')
				->where('ivr.property_id', $property_id);

		$by_status = [];
		for ($i = 0; $i < 30; $i++) {
			$date->add(new DateInterval('P1D'));
			$cur_date = $date->format('Y-m-d');

			// Total count
			$today_query = clone $query;
			$call_count = $today_query
					->whereRaw("DATE(ivr.start_date_time) = '" . $cur_date . "'")
					->select(DB::raw("sum(ivr.dial_status = '". ANSWERED ."') as answered,
								sum(ivr.dial_status = '". ABANDONED ."') as abandoned,
								 sum(ivr.dial_status = '". MISSED ."') as missed,
								sum(ivr.dial_status = '". CALLBACK ."') as callback"))
					->first();

			$by_status[] = $call_count;
		}

		$ret['by_status'] = $by_status;

		$datetime = new DateTime($end_date);
		$datetime->sub(new DateInterval('P' . 30 . 'D'));
		$start_date = $datetime->format('Y-m-d');

		$time_range = sprintf("DATE(ivr.start_date_time) > '%s' AND DATE(ivr.start_date_time) <= '%s'", $start_date, $end_date);
		$agent_time_range = sprintf("DATE(ash.created_at) > '%s' AND DATE(ash.created_at) <= '%s'", $start_date, $end_date);

		$this->getStatisticsTotalCount($query, $time_range, $ret);

		// Agent status
		
		$agent_stat_array = $this->getStatisticsByAgent($agent_list, $time_range, $agent_time_range);
		$ret['agent_list'] = $agent_stat_array['agent_list'];
		$ret['summary_status'] = $agent_stat_array['summary_status'];
		

		$filename = 'Call_Center_Dashboard_By_' .  '_' . date('m_d_y_H_i');
		$folder_path = public_path() . '/uploads/reports/';
		$path = $folder_path . $filename . '.html';
		$pdf_path = $folder_path . $filename . '.pdf';

//		ob_start();

		return view('frontend.report.callcenter.dashboard', compact('data'));

//		echo $content;

//		file_put_contents($path, ob_get_contents());
//
//		ob_clean();
//
//		$req = array();
//		$req['filename'] = $filename;
//		$req['folder_path'] = $folder_path;
//		$req['upload'] = 'uploads/reports/';
//
//		$options = array();
//		$options['html'] = $path;
//		$options['pdf'] = $pdf_path;
//		$options['paperSize'] = array('format' => 'A4', 'orientation' => 'landscape');
//		$req['options'] = $options;
//
//		return Response::json($req);

	}

	private function checkQueueCount($property_id, $add_delete_flag)
	{
		$rules = array();

		$rules['call_enter_threshold_flag'] = '1';
		$rules['call_center_queue_yellow'] = '5';
		$rules['call_center_queue_red'] = '10';
		$rules['no_avail_email'] = '';

		$rules = PropertySetting::getPropertySettings($property_id, $rules);

		if( $rules['call_enter_threshold_flag'] == '0' )
			return;
		
		$yellow = (int)$rules['call_center_queue_yellow'];
		$red = (int)$rules['call_center_queue_red'];

		$total_queue_count = DB::table('ivr_recording_queue as rq')
									->join('ivr_voice_recording as vr', 'rq.ticket_id', '=', 'vr.id')
									->where('vr.property_id', $property_id)
									->count();

		$message = array();
		$message['type'] = 'email';
		$message['to'] = $rules['no_avail_email'];
		$message['subject'] = 'Hotlync Notification';
		$message['smtp'] = Functions::getMailSetting($property_id, 'notification_');
		
		if( $total_queue_count == $yellow )
		{	
			$message['content'] = 'Call Queue Count reached On Warning Level ' . $yellow;
			Redis::publish('notify', json_encode($message));
		}

		if( $total_queue_count == $red )
		{			
			$message['content'] = 'Call Queue Count reached On Error Level ' . $red;
			Redis::publish('notify', json_encode($message));
		}

	}

	private function checkLongestWaitingCall()
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$rules = array();

		$property_list = DB::table('common_property')->get();

		foreach($property_list as $row)
		{
			$property_id = $row->id;

			$rules['call_enter_threshold_flag'] = '1';
			$rules['call_center_longest_wait_yellow'] = '5:00';
			$rules['call_center_longest_wait_red'] = '10:00';
			$rules['no_avail_email'] = '';

			$rules = PropertySetting::getPropertySettings($property_id, $rules);

			if( $rules['call_enter_threshold_flag'] == '0' )
				continue;

			$yellow = (strtotime($rules['call_center_longest_wait_yellow']) - strtotime('0:00')) / (strtotime('0:01') - strtotime('0:00'));
			$red = (strtotime($rules['call_center_longest_wait_red']) - strtotime('0:00')) / (strtotime('0:01') - strtotime('0:00'));		
			
			// get longest waiting call
			$call = DB::table('ivr_voice_recording')
				->where('property_id', $property_id)
				->whereIn('dial_status', array('Queued'))
				->select(DB::raw("COALESCE(max(TIME_TO_SEC(TIMEDIFF('$cur_time', start_date_time))), 0) as max_duration"))
				->first();

			$message = array();
			$message['type'] = 'email';
			$message['to'] = $rules['no_avail_email'];
			$message['subject'] = 'Hotlync Notification';
			$message['smtp'] = Functions::getMailSetting($property_id, 'notification_');


			if( $call->max_duration > $red )	
			{
				if( $call->max_duration - $red <= 60 )
				{
					// send error alarm				
					$message['content'] = 'Error: Current Longest Waiting Call ' . gmdate("H:i:s", $call->max_duration);

					// echo $message['content'];
					Redis::publish('notify', json_encode($message));
				}

			}
			else if( $call->max_duration > $yellow )
			{
				if( $call->max_duration - $red <= 60 )
				{
					// send warning alarm
					$message['content'] = 'Warning: Current Longest Waiting Call ' . gmdate("H:i:s", $call->max_duration);

					// echo $message['content'];
					Redis::publish('notify', json_encode($message));
				}
			}	
		}
	}

	private function checkEstmatedWaitingTime($property_id)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$rules = array();

		$rules['call_enter_threshold_flag'] = '1';		
		$rules['call_center_estimated_time_yellow'] = '5:00';
		$rules['call_center_estimated_time_red'] = '10:00';
		$rules['call_center_estimated_time_unit'] = 'Day';
		$rules['no_avail_email'] = '';

		$rules = PropertySetting::getPropertySettings($property_id, $rules);

		if( $rules['call_enter_threshold_flag'] == '0' )
			return;

		$yellow = (strtotime($rules['call_center_estimated_time_yellow']) - strtotime('0:00')) / (strtotime('0:01') - strtotime('0:00'));
		$red = (strtotime($rules['call_center_estimated_time_red']) - strtotime('0:00')) / (strtotime('0:01') - strtotime('0:00'));		
		
		// get averge waiting call
		$query = DB::table('ivr_voice_recording')
			->where('property_id', $property_id)
			->where('waiting', '>', 0);

		$time_unit = $rules['call_center_estimated_time_unit'];

		if( $time_unit == 'Day' )
		{
			$start_date_time = date('Y-m-d H:i:s', strtotime('-1 Days'));
			$query->where('start_date_time', '>=', $start_date_time);	
		}

		if( $time_unit == 'Hour' )
		{
			$start_date_time = date('Y-m-d H:i:s', strtotime('-1 Hours'));
			$query->where('start_date_time', '>=', $start_date_time);	
		}

		$call = $query->select(DB::raw("COALESCE(AVG(TIME_TO_SEC(waiting)), 0) as avg_waiting"))
			->first();

		$message = array();
		$message['type'] = 'email';
		$message['to'] = $rules['no_avail_email'];
		$message['subject'] = 'Hotlync Notification';
		$message['smtp'] = Functions::getMailSetting($property_id, 'notification_');

		if( $call->avg_waiting > $red )	
		{
			// send error alarm				
			$message['content'] = 'Error: Estimated Waiting Time ' . gmdate("H:i:s", $call->avg_waiting);

			Redis::publish('notify', json_encode($message));			
		}
		else if( $call->avg_waiting > $yellow )
		{
			$message['content'] = 'Warning: Estimated Waiting Time ' . gmdate("H:i:s", $call->avg_waiting);

			echo $message['content'];
			Redis::publish('notify', json_encode($message));			
		}
	}

	private function checkHandlingTime($property_id)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$rules = array();

		$rules['call_enter_threshold_flag'] = '1';		
		$rules['call_center_average_time_yellow'] = '5:00';
		$rules['call_center_average_time_red'] = '10:00';
		$rules['call_center_average_time_unit'] = 'Day';
		$rules['no_avail_email'] = '';

		$rules = PropertySetting::getPropertySettings($property_id, $rules);

		if( $rules['call_enter_threshold_flag'] == '0' )
			return;

		$yellow = (strtotime($rules['call_center_average_time_yellow']) - strtotime('0:00')) / (strtotime('0:01') - strtotime('0:00'));
		$red = (strtotime($rules['call_center_average_time_red']) - strtotime('0:00')) / (strtotime('0:01') - strtotime('0:00'));		
		
		// get averge waiting call
		$query = DB::table('ivr_voice_recording')
			->where('property_id', $property_id)
			->whereIn('dial_status', array(ANSWERED));

		$time_unit = $rules['call_center_average_time_unit'];

		if( $time_unit == 'Day' )
		{
			$start_date_time = date('Y-m-d H:i:s', strtotime('-1 Days'));
			$query->where('start_date_time', '>=', $start_date_time);	
		}

		if( $time_unit == 'Hour' )
		{
			$start_date_time = date('Y-m-d H:i:s', strtotime('-1 Hours'));
			$query->where('start_date_time', '>=', $start_date_time);	
		}

		$call = $query->select(DB::raw("COALESCE(AVG(TIME_TO_SEC(waiting + duration)), 0) as avg_handling"))
			->first();


		$message = array();
		$message['type'] = 'email';
		$message['to'] = $rules['no_avail_email'];
		$message['subject'] = 'Hotlync Notification';
		$message['smtp'] = Functions::getMailSetting($property_id, 'notification_');

		if( $call->avg_handling > $red )	
		{
			// send error alarm				
			$message['content'] = 'Error: Average Handling Time ' . gmdate("H:i:s", $call->avg_handling);

			Redis::publish('notify', json_encode($message));			
		}
		else if( $call->avg_handling > $yellow )
		{
			$message['content'] = 'Error: Average Handling Time ' . gmdate("H:i:s", $call->avg_handling);

			Redis::publish('notify', json_encode($message));			
		}
	}

	private function checkAnswerSpeedTime($property_id)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$rules = array();

		$rules['call_enter_threshold_flag'] = '1';		
		$rules['call_center_average_speed_yellow'] = '5:00';
		$rules['call_center_average_speed_red'] = '10:00';
		$rules['call_center_average_speed_unit'] = 'Day';
		$rules['no_avail_email'] = '';

		$rules = PropertySetting::getPropertySettings($property_id, $rules);

		if( $rules['call_enter_threshold_flag'] == '0' )
			return;

		$yellow = (strtotime($rules['call_center_average_speed_yellow']) - strtotime('0:00')) / (strtotime('0:01') - strtotime('0:00'));
		$red = (strtotime($rules['call_center_average_speed_red']) - strtotime('0:00')) / (strtotime('0:01') - strtotime('0:00'));		
		
		// get averge waiting call
		$query = DB::table('ivr_voice_recording')
			->where('property_id', $property_id)
			->whereIn('dial_status', array(ANSWERED));

		$time_unit = $rules['call_center_average_speed_unit'];

		if( $time_unit == 'Day' )
		{
			$start_date_time = date('Y-m-d H:i:s', strtotime('-1 Days'));
			$query->where('start_date_time', '>=', $start_date_time);	
		}

		if( $time_unit == 'Hour' )
		{
			$start_date_time = date('Y-m-d H:i:s', strtotime('-1 Hours'));
			$query->where('start_date_time', '>=', $start_date_time);	
		}

		$call = $query->select(DB::raw("COALESCE(AVG(TIME_TO_SEC(time_to_answer)), 0) as avg_speed"))
			->first();

		// echo json_encode($call);	

		$message = array();
		$message['type'] = 'email';
		$message['to'] = $rules['no_avail_email'];
		$message['subject'] = 'Hotlync Notification';
		$message['smtp'] = Functions::getMailSetting($property_id, 'notification_');

		if( $call->avg_speed > $red )	
		{
			// send error alarm				
			$message['content'] = 'Error: Average Speed Of Answer ' . gmdate("H:i:s", $call->avg_speed);

			Redis::publish('notify', json_encode($message));			
		}
		else if( $call->avg_speed > $yellow )
		{
			$message['content'] = 'Error: Average Speed Of Answer ' . gmdate("H:i:s", $call->avg_speed);

			Redis::publish('notify', json_encode($message));			
		}
	}

	public function getTimingInfo(Request $request) {
        $property_id = $request->get('property_id', 0);
        $skill_id = $request->get('skill_id', 0);

        $dayArr = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $tempDaysTimingInfo = [
            'type'=> 'all',
            'all_info'=> [
                'type'=> 'hotlync',
                'number'=> ''
            ],
            'time_details'=> [
                [
                    'start_time'=> '00:00',
                    'end_time'=> '23:59',
                    'type'=> 'hotlync',
                    'number'=> '',
                    'old_start_time'=> '00:00',
                    'old_end_time'=> '23:59',
                    'model_start_time'=> "",
                    'model_end_time'=> ""
                ]
            ]
        ];

        $skillList = [];
        $daysTimingArr = [];
        $datesTimingArr = [];
        $datesFlag = false;

        if ($skill_id == 0) { // init call
            $skillResult = DB::table('ivr_call_center_skill')
                ->where('property_id', $property_id)
                ->select(DB::raw('id, name'))
                ->get();

            if (!empty($skillResult)) {
                foreach($skillResult as $skillItem) {
                    $temp = [];
                    $temp['id'] = $skillItem->id;
                    $temp['name'] = $skillItem->name;

                    $skillList[] = $temp;
                }
            }

            if (!empty($skillList)) {
                $skill_id = $skillList[0]['id'];

                $timingResult = DB::table('ivr_call_center_timings')
                    ->where('property_id', $property_id)
                    ->where('skill_id', $skill_id)
                    ->select(DB::raw('days_info, dates_flag, dates_info'))
                    ->first();

                if (empty($timingResult)) {
                    for ($i = 0; $i < 7; $i++) {
                        $insertInfo = $tempDaysTimingInfo;
                        $insertInfo['day'] = $dayArr[$i];
                        $daysTimingArr[] = $insertInfo;
                    }

                    $strDaysTimingArr = json_encode($daysTimingArr);

                    DB::table('ivr_call_center_timings')
                        ->insert(['property_id' => $property_id,
                            'skill_id' => $skill_id,
                            'days_info'=> $strDaysTimingArr
                            ]);
                } else {
                    if (!empty($timingResult->days_info)) {
                        $daysTimingArr = json_decode($timingResult->days_info);
                    }

                    $datesFlag = $timingResult->dates_flag === 1 ? true : false;
                    if (!empty($timingResult->dates_info)) {
                        $datesTimingArr = json_decode($timingResult->dates_info);
                    }
                }
            }
        } else {
            $timingResult = DB::table('ivr_call_center_timings')
                ->where('property_id', $property_id)
                ->where('skill_id', $skill_id)
                ->select(DB::raw('days_info, dates_flag, dates_info'))
                ->first();

            if (empty($timingResult->days_info)) {
                for ($i = 0; $i < 7; $i++) {
                    $insertInfo = $tempDaysTimingInfo;
                    $insertInfo['day'] = $dayArr[$i];
                    $daysTimingArr[] = $insertInfo;
                }

                $strDaysTimingArr = json_encode($timingResult);
                DB::table('ivr_call_center_timings')
                    ->insert(['property_id' => $property_id,
                        'skill_id' => $skill_id,
                        'days_info'=> $strDaysTimingArr]);
            } else {
                $daysTimingArr = json_decode($timingResult->days_info);
                $datesFlag = $timingResult->dates_flag === 1 ? true : false;
                if (!empty($timingResult->dates_info)) {
                    $datesTimingArr = json_decode($timingResult->dates_info);
                }
            }
        }

        $ret = [];
        $ret['skill_list'] = $skillList;
        $ret['days_timing_arr'] = $daysTimingArr;
        $ret['selected_skill_id'] = $skill_id;
        $ret['dates_flag'] = $datesFlag;
        $ret['dates_timing_arr'] = $datesTimingArr;

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function setDaysTimingInfo(Request $request) {
        $property_id = $request->get('property_id', 0);
        $skill_id = $request->get('skill_id', 0);
        $daysInfo = $request->get('days_info', "");

        $ret = [];

        if (empty($daysInfo)) {
            $ret['status'] = 'failed';
        } else {
            try{
                DB::table('ivr_call_center_timings')
                    ->where('property_id', $property_id)
                    ->where('skill_id', $skill_id)
                    ->update(['property_id' => $property_id,
                        'skill_id' => $skill_id,
                        'days_info'=> $daysInfo]);
                $ret['status'] = 'success';
            } catch (\PDOException $exception) {
                $ret['status'] = 'failed';
            }
        }
        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function setDatesTimingInfo(Request $request) {
        $property_id = $request->get('property_id', 0);
        $skill_id = $request->get('skill_id', 0);
        $datesInfo = $request->get('dates_info', "");
        $datesFlag = $request->get('dates_flag', 0);
        $ret = [];

        if (empty($datesInfo)) {
            $ret['status'] = 'failed';
        } else {
            try{
                DB::table('ivr_call_center_timings')
                    ->where('property_id', $property_id)
                    ->where('skill_id', $skill_id)
                    ->update(['property_id' => $property_id,
                        'skill_id' => $skill_id,
                        'dates_info'=> $datesInfo,
                        'dates_flag' => $datesFlag
                    ]);
                $ret['status'] = 'success';
            } catch (\PDOException $exception) {
                $ret['status'] = 'failed';
            }
        }
        return Response::json($ret);
    }

	public function testCheckThreshold(Request $request)
	{
		$this->checkQueueCount(4, 'Add');
		$this->checkAnswerSpeedTime(4);
		$this->checkLongestWaitingCall();
	}

	public function testQueueChangeEvent(Request $request)
	{
		$ticket_id = $request->get('ticket_id', 0);

		$this->sendQueueChangeEvent($ticket_id);
	}

	public function getSkillList(Request $request)
	{
		$property_id = $request->get('property_id', 0);

		$list = DB::table('ivr_call_center_skill')
			->where('property_id', $property_id)
			->get();

		$ret = $list;
		
		return Response::json($ret);
	}

	public function createSkill(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$id = $request->get('id', 0);
		$name = $request->get('name', '');

		$data = ['name' => $name, 'property_id' => $property_id];

		if( $id > 0 )
		{
			DB::table('ivr_call_center_skill')
				->where('id', $id)
				->update($data);
		}
		else
		{
			DB::table('ivr_call_center_skill')
				->insert($data);
		}
	
		$ret = array();
		$ret['code'] = 200;

		return Response::json($ret);
	}

	public function deleteSkill(Request $request)
	{
		$id = $request->get('id', 0);
	
		DB::table('ivr_call_center_skill')
			->where('id', $id)
			->delete();

		$ret = array();
		$ret['code'] = 200;

		return Response::json($ret);
	}


	public function getSkillGroupList(Request $request)
	{
		$property_id = $request->get('property_id', 0);

		$list = DB::table('ivr_call_center_skill_group as ics')
			->leftJoin('common_department as cd', 'ics.dept_id', '=', 'cd.id')
			->where('ics.property_id', $property_id)
			->select(DB::raw('ics.*, cd.department'))
			->get();

		foreach($list as $row)
		{
			if( $row->skill_ids )
			{
				$skill_ids = explode(",", $row->skill_ids);

				$row->skill_tags = DB::table('ivr_call_center_skill')
								->whereIn('id', $skill_ids)
								->get();

				$row->skill_name_list = implode(",", array_map(function($item) {
					return $item->name;
				}, $row->skill_tags));			
			}
			else
			{
				$row->skill_tags = [];
				$row->skill_name_list = '';
			}
			
		}	

		$ret = $list;
		
		return Response::json($ret);
	}

	public function getCurrentTimingInfo(Request $request) {
        $skill_id = $request->get('skill_id', 0);
        $property_id = $request->get('property_id', 0);
        $curDate = date('Y-m-d');
        $curTime = date('H:i');
        $curDayNum = intval(date('w'));

        $ret = [];
        if ($skill_id === 0) {
            $ret['result'] = '';
            return Response::json($ret);
        }

        $timingInfo = DB::table('ivr_call_center_timings')
            ->where('property_id', $property_id)
            ->where('skill_id', $skill_id)
            ->select(DB::raw('days_info, dates_flag, dates_info'))
            ->first();

        if (empty($timingInfo)) {
            $ret['result'] = '';
            return Response::json($ret);
        }

        $datesFlag = $timingInfo->dates_flag;
        $daysInfoArr = json_decode($timingInfo->days_info);
        $datesInfoArr = json_decode($timingInfo->dates_info);

        $result = '';
        $bFind = false;
        if ($datesFlag == 1) {
            foreach ($datesInfoArr as $dayInfo) {
                if ($curDate >= $dayInfo->start_date && $curDate <= $dayInfo->end_date) {
                    if ($dayInfo->type == 'all') {
                        $type = $dayInfo->all_info->type;
                        if ($type === 'hotlync') {
                            $result = 'HotLync';
                        } else {
                            $result = $dayInfo->all_info->number;
                        }
                        $bFind = true;
                        break;
                    } else {
                        foreach ($dayInfo->time_details as $timeDetail) {
                            if ($curTime >= $timeDetail->start_time && $curTime <= $timeDetail->end_time) {
                                $type = $timeDetail->type;
                                if ($type === 'hotlync') {
                                    $result = 'HotLync';
                                } else {
                                    $result = $timeDetail->number;
                                }
                                $bFind = true;
                                break;
                            }
                        }
                    }
                }
            }
        }

        if ($bFind == true) {
            $ret['result'] = $result;
            return Response::json($ret);
        }

        $dayArr = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ($daysInfoArr as $dayInfo) {
            if ($dayInfo->day == $dayArr[$curDayNum]) {
                if ($dayInfo->type == 'all') {
                    $type = $dayInfo->all_info->type;
                    if ($type === 'hotlync') {
                        $result = 'HotLync';
                    } else {
                        $result = $dayInfo->all_info->number;
                    }
                    break;
                } else {
                    foreach ($dayInfo->time_details as $timeDetail) {
                        if ($curTime >= $timeDetail->start_time && $curTime <= $timeDetail->end_time) {
                            $type = $timeDetail->type;
                            if ($type === 'hotlync') {
                                $result = 'HotLync';
                            } else {
                                $result = $timeDetail->number;
                            }
                            break;
                        }
                    }
                }
            }
        }

        $ret['result'] = $result;
        return Response::json($ret);
    }

	public function createSkillGroup(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$id = $request->get('id', 0);
		$group_name = $request->get('group_name', '');
		$skill_ids = $request->get('skill_ids', '');
		$dept = $request->get('dept_id', 0);

		$data = ['group_name' => $group_name, 'property_id' => $property_id, 'skill_ids' => $skill_ids, 'dept_id' => $dept];

		if( $id > 0 )
		{
			DB::table('ivr_call_center_skill_group')
				->where('id', $id)
				->update($data);
		}
		else
		{
			DB::table('ivr_call_center_skill_group')
				->insert($data);
		}
	
		$ret = array();
		$ret['code'] = 200;
		
		return Response::json($ret);
	}

	public function deleteSkillGroup(Request $request)
	{
		$id = $request->get('id', 0);
		
		DB::table('ivr_call_center_skill_group')
			->where('id', $id)
			->delete();
		
		$ret = array();
		$ret['code'] = 200;
		
		return Response::json($ret);
	}

	public function getAgentSkillList(Request $request)
	{
		$property_id = $request->get('property_id', 0);

		$agent_list = DB::table('ivr_agent_status_log as asl')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->leftJoin('ivr_status_priority as sp', 'asl.status', '=', 'sp.status')
				->where('cd.property_id', $property_id)				
				->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent, cu.picture'))
				->get();

		foreach($agent_list as $row)	
		{
			$skill_list = DB::table('ivr_agent_skill_level as asl')
				->leftJoin('ivr_call_center_skill as ccs', 'asl.skill_id', '=', 'ccs.id')
				->where('asl.agent_id', $row->id)
				->select(DB::raw('ccs.*, asl.level'))
				->get();

			$row->skill_list = $skill_list;	

			$row->skill_name_list = implode(",", array_map(function($item) {
				return $item->name . ' - ' . $item->level;
			}, $row->skill_list));			
		}

		return Response::json($agent_list);
	}

	public function getAgentSkillLevelList(Request $request)
	{
		$agent_id = $request->get('agent_id', 0);

		$skill_list = DB::table('ivr_agent_skill_level as asl')
			->leftJoin('ivr_call_center_skill as ccs', 'asl.skill_id', '=', 'ccs.id')
			->where('asl.agent_id', $agent_id)
			->select(DB::raw('asl.*, ccs.name'))
			->get();

		return Response::json($skill_list);
	}

	public function addAgentSkillLevel(Request $request)
	{
		$id = $request->get('id', 0);
		$agent_id = $request->get('agent_id', 0);
		$skill_id = $request->get('skill_id', 0);
		$level = $request->get('level', 0);

		$data = ['agent_id' => $agent_id, 'skill_id' => $skill_id, 'level' => $level];

		if( $id > 0 )
		{
			DB::table('ivr_agent_skill_level')
				->where('id', $id)
				->update($data);
		}
		else
		{
			DB::table('ivr_agent_skill_level')
				->insert($data);
		}
	
		$ret = array();
		$ret['code'] = 200;
		
		return Response::json($ret);
	}

	public function deleteAgentSkillLevel(Request $request)
	{
		$id = $request->get('id', 0);

		DB::table('ivr_agent_skill_level')
			->where('id', $id)
			->delete();
	
	
		$ret = array();
		$ret['code'] = 200;
		
		return Response::json($ret);
	}

	private function getCallcenterThresholdSetting($property_id)
	{
		$rules = array(); 

		$rules['call_center_abandoned_yellow'] = '0';
		$rules['call_center_abandoned_red'] = '0';

		$rules['call_center_acw_dur_yellow'] = '00:00:00';
		$rules['call_center_acw_dur_red'] = '00:00:00';

		$rules['call_center_aux_dur_yellow'] = '00:00:00';
		$rules['call_center_aux_dur_red'] = '00:00:00';

		$rules['call_center_avg_handling_time_yellow'] = '00:00:00';
		$rules['call_center_avg_handling_time_red'] = '00:00:00';

		$rules['call_center_avg_speed_answer_yellow'] = '00:00:00';
		$rules['call_center_avg_speed_answer_red'] = '00:00:00';

		$rules['call_center_current_call_dur_yellow'] = '00:00:00';
		$rules['call_center_current_call_dur_red'] = '00:00:00';

		$rules['call_center_call_on_queue_yellow'] = '0';
		$rules['call_center_call_on_queue_red'] = '0';

		$rules['call_center_longest_waiting_call_yellow'] = '00:00:00';
		$rules['call_center_longest_waiting_call_red'] = '00:00:00';


		$rules = PropertySetting::getPropertySettings($property_id, $rules);

		return $rules;
	}

	public function getThresholdSetting(Request $request)
	{
		$property_id = $request->get('property_id', 0);

		$rules = $this->getCallcenterThresholdSetting($property_id);

		return Response::json($rules);
	}

	public function saveThresholdSetting(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$key = $request->get('key', '');
		$value = $request->get('value', '');

		$rules = array(); 
		$rules[$key] = $value;
		
		PropertySetting::savePropertySetting($property_id, $rules);

		return Response::json($rules);
	}

	public function getDepartmentList(Request $request) {
		$property_id = $request->get('property_id', '0');
		$name = $request->get('department', '');
		
		$query = DB::table('common_department as cd')
			->whereRaw("cd.department like '%$name%'")		
			->where('cd.property_id',$property_id)
			->take(10);

	
		$departlist = $query->select(DB::raw('cd.*'))
			->get();

		
		return Response::json($departlist);
	}

	//store callcenter profile for dashboard of every user
	public function storeCallcenterProfile(Request $request) {

		$user_id = $request->get('user_id', 0);
	
		$skill_ids = $request->get('skill_group_ids','[]');
		

		
			$profile = DB::table('ivr_call_center_profile')
				->where('user_id', $user_id)
				->get();
			if (!empty($profile)) {
				DB::table('ivr_call_center_profile')
					->where('user_id', $user_id)
					->update(['user_id' => $user_id,
						'skill_group' => $skill_ids]);

			} else {
				DB::table('ivr_call_center_profile')
					->insert(['user_id' => $user_id,
						'skill_group' => $skill_ids]);

			}
		
		//after save reload
		$profile = DB::table('ivr_call_center_profile')
			->where('user_id', $user_id)
			->get();
		return Response::json($profile);
		
	}

	public function getSkillGroupListUser(Request $request)
	{
		
		$user_id = $request->get('user_id', 0);
		$profile = DB::table('ivr_call_center_profile')
			->where('user_id', $user_id)
			->select(DB::raw('skill_group'))
			->first();
		$skill_groups = array();

		if (!empty($profile)){

		$skill_groups = explode(",", $profile->skill_group);

		}
		
		$skill_group_name = '';

		for($i = 0; $i < count($skill_groups); $i++){

			$list = DB::table('ivr_call_center_skill_group as ics')
				->where('ics.id', $skill_groups[$i])
				->select(DB::raw('ics.group_name'))
				->first();
			if (!empty($list))
			{
				$skill_group_name .= $list->group_name . ',';   
			}

		}
            

		
		$ret = $skill_group_name;
		
		return Response::json($ret);
	}

	public function directOutgoingCall(Request $request)
	{

		$extension_id = $request->get('extension', '0');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");


		$ext = DB::table('call_center_extension as cce')
				->where('cce.extension', $extension_id)
				->first();

		if (!empty($ext)){

			$agent = IVRAgentStatus::where('extension', $ext->extension)->first();

			if (!empty($agent)){

				if ($agent->status == BUSY){

					$agent->status = AVAILABLE;
					$agent->created_at = $cur_time;
					$agent->save();

					Functions::saveAgentStatusHistory($agent);

					$ticket = IVRVoiceRecording::find($agent->ticket_id);

					$data = $this->getAgentStatusData($agent->user_id);

					$caller = $this->getCallerProfile($ticket);

            		$data->caller = $caller;
            		$data->ticket = $ticket;

            		$message = [
                    	'type' => 'incoming',
                    	'data' => $data
            		];

            		Redis::publish('notify', json_encode($message)); 

					$this->redirectCallToAvailableAgent($ticket->property_id); 
					
				}

				
			}

		}

	}

	//React functions

	public function reactGetAgentStatus(Request $request)
	{
		$agent_id = $request->get('agent_id', 0);

		$agent_status = $this->getAgentStatusData($agent_id);
		if( !empty($agent_status) )
		{
			$ticket = DB::table('ivr_voice_recording')
				->where('id', $agent_status->ticket_id)
				->orderBy('id', 'desc')
				->first();
		
			if( !empty($ticket) )
			{
				$agent_status->ticket = $ticket;
				$caller = $this->getCallerProfile($ticket);

				$agent_status->caller = $caller;
			}
			else
			{
				$agent_status->ticket = array('id' => 0);
				$agent_status->caller = array('id' => 0);
			}

			$rules = array();
			$rules['sip_server'] = 'developdxb.myhotlync.com';
			$rules = PropertySetting::getPropertySettings($agent_status->property_id, $rules);
			$agent_status->sip_server = $rules['sip_server'];
		}

		return Response::json($agent_status);
	}

	//React function ends
}
