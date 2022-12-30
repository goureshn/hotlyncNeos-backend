<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Common\CommonUser;
use App\Models\Common\Guest;
use App\Models\Common\PropertySetting;
use App\Models\Common\Room;
use App\Models\Common\SystemNotification;
use App\Models\Service\Wakeup;
use App\Models\Service\WakeupLog;
use App\Modules\Functions;

use DateInterval;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Redis;
use Response;

define("PENDING", 'Pending');
define("INPROGRESS", 'In-Progress');
define("SUCCESS", 'Success');
define("FAIL", 'Failed');
define("BUSY", 'Busy');
define("SNOOZE", 'Snooze');
define("UNANSWER", 'No Answer');
define("CANCELED", 'Canceled');
define("NOTCONFIRM", 'Not Confirmed');
define("WAITING", 'Waiting');

class WakeupController extends Controller
{
	public function getList(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$filter = $request->get('filter','Total');
		$searchoption = $request->get('searchoption','');
		$start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');

		$date = new DateTime($cur_time);
		$date->sub(new DateInterval('P1D'));
		$last_time = $date->format('Y-m-d H:i:s');

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();
		$query = DB::table('services_awu as awu')
				->leftJoin('common_room as cr', 'awu.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->leftJoin('call_guest_extn as ge', 'awu.extension_id', '=', 'ge.id')
//				->leftJoin('common_guest as cg', 'awu.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('awu.guest_id', '=', 'cg.guest_id');
					$join->on('awu.property_id', '=', 'cg.property_id');
				})
				->where('awu.property_id', $property_id);

		$query->whereRaw(sprintf("DATE(awu.time) >= '%s' and DATE(awu.time) <= '%s'", $start_date, $end_date));

		// get building ids
		$user_id = $request->get('user_id', 0);
		$building_ids = CommonUser::getBuildingIds($user_id);

		if( !empty($building_ids) )
		{
			$building_ids = explode(',', $building_ids);
			$query->whereIn('cf.bldg_id', $building_ids);
		}

		$sub_count_query = clone $query;
		if($filter != 'Total' ) {
			$query->where('awu.status', $filter);
		}

		if( $searchoption != '' )
		 {
			$where = sprintf(" (cr.room like '%%%s%%' or								
								ge.extension like '%%%s%%' or
								cg.guest_name like '%%%s%%'
								)",
				$searchoption, $searchoption,$searchoption
			);
			$query->whereRaw($where);
		}

		$data_query = clone $query;
		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('awu.*, cr.room'))
				->skip($skip)->take($pageSize)
				->get();

		Guest::getGuestList($data_list);

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		$data_query = clone $sub_count_query;

		$subcount = $data_query
				->select(DB::raw("
						count(*) as total,
						COALESCE(sum(awu.status = '". SUCCESS . "'), 0) as success,
						COALESCE(sum(awu.status = '" . FAIL . "'), 0) as failed,
						COALESCE(sum(awu.status = '". UNANSWER . "'), 0) as unanswer,
						COALESCE(sum(awu.status = '". SNOOZE . "'), 0) as snooze,
						COALESCE(sum(awu.status = '". PENDING . "'), 0) as pending
						"))
				->first();

		$ret['subcount'] = $subcount;

		return Response::json($ret);
	}

	public function create(Request $request) {
		$input = $request->all();

		$guest = Guest::where('room_id', $input['room_id'])
				->orderBy('id', 'desc')
			->orderBy('arrival', 'desc')
				->first();

		$ret['code'] = 200;
		$ret['checkin_flag'] = $guest;

		if( empty($guest) || $guest->checkout_flag != 'checkin' )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Room not check in on HotLync';
			return Response::json($ret);
		}

		$set_time = new DateTime($input['time']);
		$input['set_time'] = $set_time->format('H:i:s');

		$extension = DB::table('call_guest_extn')
				->where('room_id', $input['room_id'])
				->where('primary_extn', 'Y')
				->first();

		if( empty($extension) )
		{
			$extension = DB::table('call_guest_extn')
					->where('room_id', $input['room_id'])
					->first();
		}

		if( empty($extension) )
		{
			$ret['code'] = 202;
			$ret['message'] = 'Extension is not valid';
			return Response::json($ret);
		}

		// $old_wakeup = $this->checkSameWakeup($input['time'], $input['repeat_flag'], $guest, 0);

		// if( !empty($old_wakeup) )
		// {
		// 	$ret['code'] = 202;
		// 	$ret['message'] = 'There is already another wakeup call set by ' . $old_wakeup->set_by;
		// 	return Response::json($ret);
		// }

		$input['extension_id'] = $extension->id;

		$id = DB::table('services_awu')->insertGetId($input);

		$log = new WakeupLog();
		$log->awu_id = $id;
		$log->status = 'Created';
		$log->set_by = $input['set_by'];
		$log->set_by_id = $input['set_by_id'];

		$log->save();

		$wakeup = Wakeup::find($id);

		$this->sendWakeupStatusToOpera($wakeup);

		return Response::json($ret);
	}

	private function checkSameWakeup($time, $repeat_flag, $guest, $old_id) {
		$date = new DateTime($time);
		$start_date = $date->format('Y-m-d');

		if( $repeat_flag != 1 )
			$end_date = $start_date;
		else {
			if( $guest->departure < $start_date )
				$end_date = $start_date;
			else
				$end_date = $guest->departure;
		}

		$old_wakeup = DB::table('services_awu')
			->where('room_id', $guest->room_id)
			->whereNotIn('status', array(SUCCESS, FAIL, CANCELED))
			->whereRaw(sprintf("DATE(time) between '%s' and '%s'", $start_date, $end_date))
			->where('id', '!=', $old_id)
			->first();

		if( !empty($old_wakeup) )
			return $old_wakeup;

		// check repeat flag
		$wakeup_list = DB::table('services_awu')
				->where('room_id', $guest->room_id)
				->whereNotIn('status', array(SUCCESS, FAIL, CANCELED))
				->where('repeat_flag', 1)
				->where('id', '!=', $old_id)
				->get();

		foreach( $wakeup_list as $row )
		{
			$old_guest = DB::table('common_guest')
				->where('guest_id', $row->guest_id)
				->where('room_id', $row->room_id)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

			if( empty($old_guest) || $old_guest->checkout_flag != 'checkin')
				continue;

			if( $end_date < $old_guest->arrival || $start_date > $old_guest->departure )
				continue;

			$old_wakeup = $row;
			break;
		}

		return $old_wakeup;
	}

	public function update(Request $request) {
		$input = $request->all();

		$id = $request->get('id', 0);

		$guest = Guest::where('room_id', $input['room_id'])
				->where('guest_id', $input['guest_id'])
				->orderBy('id', 'desc')
			->orderBy('arrival', 'desc')
				->first();

		$ret['code'] = 200;
		$ret['checkin_flag'] = $guest;

		if( empty($guest) || $guest->checkout_flag != 'checkin' )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Room not check in on HotLync';
			return Response::json($ret);
		}

		// $old_wakeup = $this->checkSameWakeup($input['time'], $input['repeat_flag'], $guest, $id);

		// if( !empty($old_wakeup) )
		// {
		// 	$ret['code'] = 202;
		// 	$ret['message'] = 'There is already another wakeup call set by ' . $old_wakeup->set_by;
		// 	return Response::json($ret);
		// }


		$set_time = new DateTime($input['time']);
		$input['set_time'] = $set_time->format('H:i:s');

		$wakeup = Wakeup::find($id);
		$wakeup->status = CANCELED;
		$this->sendWakeupStatusToOpera($wakeup);

		DB::table('services_awu')
				->where('id', $id)
				->update($input);

		$wakeup = Wakeup::find($id);

		if( !empty($wakeup) )
		{
			$log = new WakeupLog();
			$log->awu_id = $id;
			$log->status = 'Changed';
			$log->set_by = $input['set_by'];
			$log->set_by_id = $input['set_by_id'];

			$log->save();
		}

		$wakeup->status = PENDING;
		$this->sendWakeupStatusToOpera($wakeup);

		return Response::json($ret);
	}

	public function cancel(Request $request) {
		$id = $request->get('id', 0);
		$set_by = $request->get('set_by', '');
		$set_by_id = $request->get('set_by_id', 0);

		$wakeup = Wakeup::find($id);
		$this->cancelWakeupCall($wakeup, $set_by_id, $set_by);

		return Response::json($wakeup);
	}

	public function getLogs(Request $request)
	{
		$awu_id = $request->get('id', 0);
		$page = $request->get('page', 1);
		$pageSize = $request->get('pagesize', 20);
		$skip = $pageSize * ($page - 1);
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('services_awu_logs as al')
				->leftJoin('services_awu as awu', 'al.awu_id', '=', 'awu.id')
				->where('al.awu_id', $awu_id);

		$dataquery = clone $query;

		$ticketlist = $dataquery->orderBy($orderby, $sort)
				->select(DB::raw("awu.*, al.status AS al_status_by, al.timestamp, al.attempts as attempt_log, al.set_by as action_by, al.record_path"))
				->orderBy('al.id', 'desc')
				->skip($skip)->take($pageSize)
				->get();

		$countquery = clone $query;
		$totalcount = $countquery->count();

		$ret['datalist'] = $ticketlist;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function triggerWakeupCall(Request $request)
	{
		$id = $request->get('id', 0);

		$wakeup = Wakeup::find($id);

		if( empty($wakeup) ||($wakeup->status == SUCCESS))		// invalid wakeup
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ID: ' . $id;
			return Response::json($ret);
		}

		$guestinfo = DB::table('common_guest')
				->where('room_id', $wakeup->room_id)
				->where('guest_id', $wakeup->guest_id)
				->orderBy('id', 'desc')
				->first();

		if( empty($guestinfo) || $guestinfo->checkout_flag != 'checkin' )	// invalid wakeup
		{
			$ret['code'] = 202;
			$ret['message'] = 'Guest does not checkin this room';
			return Response::json($ret);
		}

		return $this->sendWakeupCall($wakeup, $guestinfo, false);
	}



	public function checkWakeupCalls(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_list = DB::table('common_property')->get();

		foreach($property_list as $key => $row) {
			$this->checkInprogressCalls($row->id);
			$this->checkWaitingCalls($row->id);
		}

		// trigger wakeup call
		$awu_list = DB::table('services_awu')
				->whereIn('status', array(PENDING, BUSY, SNOOZE, UNANSWER, NOTCONFIRM))
				->where('time', '<=', $cur_time)   // passed ticket
				->orderby('time', 'asc')
				->get();

		foreach($awu_list as $key => $row) {
			// check guest checkin
			$guestinfo = DB::table('common_guest')
				->where('room_id', $row->room_id)
				->where('guest_id', $row->guest_id)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

			$wakeup = Wakeup::find($row->id);
			if( empty($guestinfo) || $guestinfo->checkout_flag != 'checkin' )	// invalid wakeup
			{
				$this->cancelWakeupCall($wakeup, 0, 'Checkout');

				continue;
			}

			echo 'ID: ' . $wakeup->id . ', Room ID: ' . $row->room_id . ', Guest ID: ' . $row->guest_id . ' ';
			if( $row->status == PENDING )
				$this->sendWakeupCall($wakeup, $guestinfo, true);
			else
				$this->sendWakeupCall($wakeup, $guestinfo, false);
		}

	}
	
	public function checkInprogressCalls($property_id)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$wakeup_setting = PropertySetting::getWakeupSetting($property_id);

		// check timeout inprogess wakeup
		$past_time = new DateTime($cur_time);
		$past_time->sub(new DateInterval('PT' . $wakeup_setting['inprogress_max_wait'] . 'S'));
		$past_time = $past_time->format('Y-m-d H:i:s');

		$awu_list = DB::table('services_awu')
				->where('status', INPROGRESS)
				->where('time', '<=', $past_time)   // passed ticket
				->where('property_id', $property_id)
				->get();

		echo 'Inprogress Max Time = ' . $wakeup_setting['inprogress_max_wait'] .  '</br>';		

		foreach($awu_list as $key => $row) {
			// check guest checkin
			$guestinfo = DB::table('common_guest')
				->where('room_id', $row->room_id)
				->where('guest_id', $row->guest_id)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

			$wakeup = Wakeup::find($row->id);
			if( empty($guestinfo) || $guestinfo->checkout_flag != 'checkin' )	// invalid wakeup
			{
				$this->cancelWakeupCall($wakeup, 0, 'Checkout');

				continue;
			}

			echo 'ID: ' . $wakeup->id . ', Room ID: ' . $row->room_id . ', Guest ID: ' . $row->guest_id . ' ';
			if($this->checkInprogressRetryCount($wakeup, $guestinfo, $cur_time) == 0)
				$this->sendWakeupCall($wakeup, $guestinfo, false);
		}
	}

	private function checkInprogressRetryCount($wakeup, $guestinfo, $cur_time) {
		$wakeup_setting = PropertySetting::getWakeupSetting($wakeup->property_id);
		if( empty($wakeup_setting) )
			return -1;

		$ret = 0;
		
		$wakeup->attempts++;
		if( $wakeup->attempts > $wakeup_setting['awu_retry_attemps'] )	// max retry count;
		{
			$ret = -1;
			$wakeup->status = FAIL;
		}
		else {			
			$wakeup->time = $cur_time;
		}
		// if( $wakeup->status == FAIL ) {
		// 	$log= WakeupLog::where('awu_id',$wakeup->id)->orderBy('id', 'desc')->first();
		// 	$wakeup->fail_reason=$log->status;
		// }
		$wakeup->save();

		// save fail log
		if( $wakeup->status == FAIL ) {
			$log = new WakeupLog();
			$log->awu_id = $wakeup->id;
			$log->status = FAIL . '(Cause: Guest did not confirm ' . $wakeup->attempts . ' attempts)';
			$log->attempts = $wakeup->attempts;
			$log->set_by_id = 0;
			$log->set_by = 'Hotlync';

			$log->save();

			$this->sendFailAlarm($wakeup, $wakeup_setting);
			$this->sendUpdatedWakeup($wakeup);
			$this->sendWakeupStatusToOpera($wakeup);
			$this->checkWaitingCalls($wakeup->property_id);
		}

		return $ret;
	}

	private function checkWaitingCalls($property_id) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$wakeup_setting = PropertySetting::getWakeupSetting($property_id);

		// check timeout waiting wakeup
		$past_time = new DateTime($cur_time);
		$past_time->sub(new DateInterval('PT' . $wakeup_setting['max_wakeup_waiting_time'] .'S'));
		$past_time = $past_time->format('Y-m-d H:i:s');

		$awu_list = DB::table('services_awu')
				->where('status', WAITING)				
				->where('time', '<=', $past_time)   // passed ticket
				->get();

		foreach($awu_list as $key => $row) {
			$wakeup = Wakeup::find($row->id);
			$this->changeToFailedStatus($wakeup,$row->id);
		}

		// check max lines
		if( $this->isValidWakeupLine($property_id) == false )
			return;

		// trigger wakeup call
		$awu_list = DB::table('services_awu')
				->where('status', WAITING)
				->orderby('time', 'asc')
				->get();


		foreach($awu_list as $key => $row) {
			// check guest checkin
			$guestinfo = DB::table('common_guest')
				->where('room_id', $row->room_id)
				->where('guest_id', $row->guest_id)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

			$wakeup = Wakeup::find($row->id);
			if( empty($guestinfo) || $guestinfo->checkout_flag != 'checkin' )	// invalid wakeup
			{
				$this->cancelWakeupCall($wakeup, 0, 'Checkout');

				continue;
			}

			echo 'ID: ' . $wakeup->id . ', Room ID: ' . $row->room_id . ', Guest ID: ' . $row->guest_id . ' ';
			$this->sendWakeupCall($wakeup, $guestinfo, false);
		}

	}

	public function wakeupFromIVR(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$extension = $request->get('extension', '');
		$time = $request->get('time', '00:00');
		$flag = $request->get('flag', '0');

		$ret['code'] = 1; // success

		if( $flag == 0 )	// cancel
		{
			return Response::json($ret);
		}

		$extension = DB::table('call_guest_extn')
				->where('extension', $extension)
				->first();

		// check extension is valid
		if( empty($extension) )
		{
			$ret['code'] = 4; // invalid extension
			$ret['message'] = 'Invalid Extension';
			return Response::json($ret);
		}

		$room = DB::table('common_room as cr')
			->leftJoin('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->leftJoin('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->where('cr.id', $extension->room_id)
			->select(DB::raw('cr.*, cb.property_id'))
			->first();

		// check room is valid
		if( empty($room) )
		{
			$ret['code'] = 3; // invalid room
			$ret['message'] = 'Invalid Room';
			return Response::json($ret);
		}

		$guest = DB::table('common_guest')
			->where('room_id', $extension->room_id)
			->orderBy('departure', 'desc')
			->orderBy('arrival', 'desc')
			->first();

		// check guest is checkin
		if( empty($guest) || $guest->checkout_flag != 'checkin' )
		{
			$ret['code'] = 5; // checkout room
			return Response::json($ret);
		}

		// check time
		$time = new DateTime($time);
		if( $cur_time >= $time->format('Y-m-d H:i:s') )	// past time
		{
			$ret['code'] = 2; // invalid time
			return Response::json($ret);
		}

		$awu = new Wakeup();

		$awu->property_id = $room->property_id;
		$awu->room_id = $extension->room_id;
		$awu->guest_id = $guest->guest_id;
		$awu->extension_id = $extension->id;
		$awu->time = $time->format('Y-m-d H:i:s');
		$awu->set_time = $time->format('H:i:s');
		$awu->status = PENDING;
		$awu->set_by = $guest->guest_name;
		$awu->set_by_id = 0;
		$awu->attempts = 0;
		$awu->repeat_flag = 0;

		$awu->save();

		$log = new WakeupLog();
		$log->awu_id = $awu->id;
		$log->status = 'Created';
		$log->set_by = $awu->set_by;
		$log->set_by_id = 0;

		$log->save();

		$this->sendUpdatedWakeup($awu);

		$this->sendWakeupStatusToOpera($awu);

		return Response::json($ret);
	}

	private function isValidWakeupLine($property_id) {
		$wakeup_setting = PropertySetting::getWakeupSetting($property_id);

		// check max call count
		$progress_count = DB::table('services_awu')
				->where('status', INPROGRESS)
				->where('property_id', $property_id)   // passed ticket
				->count();

		if( $progress_count >= $wakeup_setting['max_wakeup_call'] )		
			return false;
		return true;
	}

	private function sendWakeupCall($wakeup, $guest, $duplicate_flag) {
		$wakeup_setting = PropertySetting::getWakeupSetting($wakeup->property_id);

		$valid = true;

		if($wakeup->status != INPROGRESS)
			$valid = $this->isValidWakeupLine($wakeup->property_id);

		if( $valid == false )		
		{
			if( $wakeup->status != WAITING )
			{
				$wakeup->status = WAITING;
				$wakeup->save();

				$log = new WakeupLog();
				$log->awu_id = $wakeup->id;
				$log->status = $wakeup->status;
				$log->attempts = $wakeup->attempts;
				$log->set_by_id = 0;
				$log->set_by = 'Hotlync';

				$log->save();

				$this->sendUpdatedWakeup($wakeup);	
			}						
		}
		else
		{
			// update state to busy
			if($wakeup->status == PENDING || $wakeup->status == BUSY || $wakeup->status == SNOOZE || $wakeup->status == UNANSWER || $wakeup->status == NOTCONFIRM || $wakeup->status == WAITING )
			{
				$wakeup->status = INPROGRESS;
				$wakeup->save();
			}

			$extension = DB::table('call_guest_extn')
					->where('id', $wakeup->extension_id)
					->first();

			// compare time
			$wakeup_time = new DateTime($wakeup->time);
			$only_time = $wakeup_time->format('H:i:s');
			if( '00:00:00' <= $only_time && $only_time < '12:00:00' )
				$greeting_flag = 1;
			else if( $only_time < '18:00:00' )
				$greeting_flag = 2;
			else
				$greeting_flag = 3;


			$message = array();
			$message['type'] = 'wakeup';
			$message['data'] = $wakeup;
			$message['guest'] = $guest;
			$message['extension'] = $extension;
			$message['greeting_flag'] = $greeting_flag;
			$message['wakeup_setting'] = $wakeup_setting;

			Redis::publish('notify', json_encode($message));

			$log = new WakeupLog();
			$log->awu_id = $wakeup->id;
			if( $wakeup->attempts > 0 )
				$log->status = $wakeup->status . ' (Retry ' . $wakeup->attempts . ')';
			else
				$log->status = $wakeup->status;

			$log->attempts = $wakeup->attempts;
			$log->set_by_id = 0;
			$log->set_by = 'Hotlync';

			$log->save();

			$this->sendUpdatedWakeup($wakeup);
		}

		// duplicate new wakeup
		if( $duplicate_flag == true && $wakeup->repeat_flag == 1 )
		{
			$cur_date = date('Y-m-d');
			if( $wakeup->until_checkout_flag == 0 && $cur_date <= $wakeup->repeat_end_date )				
				$this->duplicateWakeup($wakeup, 0, 1);

			if( $wakeup->until_checkout_flag == 1 )
				$this->duplicateWakeup($wakeup, 0, 1);
		}
	}

	public function successWakeup(Request $request) {
		$id = $request->get('id', 0);
		$filepath = $request->get('filepath', '');
		$filename = $request->get('filename', '');

		$wakeup = Wakeup::find($id);
		$wakeup->fail_reason='';
		$ret = $this->changeWakeupStatus($wakeup, SUCCESS);

		$guest = $this->getGuestInfo($wakeup);

		// save log
		$log = new WakeupLog();
		$log->awu_id = $wakeup->id;
		$log->status = 'SUCCESS';
		$log->record_path = $filepath . $filename;
		
		if( !empty($guest) )
			$log->set_by = $guest->guest_name;
		
		$log->set_by_id = 0;

		$log->save();

		$this->sendUpdatedWakeup($wakeup);
		$this->sendWakeupStatusToOpera($wakeup);

		$this->checkWaitingCalls($wakeup->property_id);

		return Response::json($ret);
	}

	public function busyWakeup(Request $request) {
		$id = $request->get('id');
		$status = $request->get('status', BUSY);
		$filepath = $request->get('filepath', '');
		$filename = $request->get('filename', '');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();
		$ret['code'] = 200;

		$wakeup = Wakeup::find($id);
		if( empty($wakeup) ||($wakeup->status == SUCCESS))		// invalid wakeup
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ID: ' . $id;
			return Response::json($ret);
		}
		$wakeup->fail_reason=$status;
		$wakeup_setting = PropertySetting::getWakeupSetting($wakeup->property_id);
		if( empty($wakeup_setting) )
		{
			$ret['code'] = 202;
			$ret['message'] = 'Invalid Property ID: ' . $wakeup->property_id;
			return Response::json($ret);
		}

		if( $wakeup->attempts >= $wakeup_setting['awu_retry_attemps'] )
		{
			$ret['code'] = 203;
			$ret['message'] = 'Exceed Retry Count: ' . $wakeup_setting['awu_retry_attemps'];

			$wakeup->status = FAIL;
		}
		else {
			$wakeup->attempts++;
			$wakeup->status = $status;

			$cur_time = date("Y-m-d H:i:s");

			$date = new DateTime($cur_time);
			$date->add(new DateInterval('PT' . $wakeup_setting['awu_retry_mins'] . 'M'));
			$wakeup->time = $date->format('Y-m-d H:i:s');
		}
		
		$wakeup->save();

		// save status log
		$log = new WakeupLog();
		$log->awu_id = $wakeup->id;
		$log->status = $status;
		$log->attempts = $wakeup->attempts;
		$log->record_path = $filepath . $filename;
		$log->set_by_id = 0;

		$guest = $this->getGuestInfo($wakeup);

		if( !empty($guest) )
			$log->set_by = $guest->guest_name;

		$log->save();

		// save fail log
		if( $wakeup->status == FAIL ) {
			$log = new WakeupLog();
			$log->awu_id = $wakeup->id;
			$log->status = FAIL . '(Cause: Guest did not confirm ' . $wakeup->attempts . ' attempts)';
			$log->attempts = $wakeup->attempts;
			$log->set_by_id = 0;
			$log->set_by = 'Hotlync';

			$log->save();

			$this->sendFailAlarm($wakeup, $wakeup_setting);
		}

		$this->sendUpdatedWakeup($wakeup);

		$this->sendWakeupStatusToOpera($wakeup);

		$this->checkWaitingCalls($wakeup->property_id);

		return Response::json($ret);
	}

	private function getGuestInfo($wakeup) {
		
	}

	public function snoozeWakeup(Request $request) {
		$id = $request->get('id', 0);
		$filepath = $request->get('filepath', '');
		$filename = $request->get('filename', '');

		$wakeup = Wakeup::find($id);
		if( empty($wakeup) ||($wakeup->status == SUCCESS))		// invalid wakeup
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ID: ' . $id;
			return Response::json($ret);
		}
		$wakeup->attempts++;
		$ret = $this->changeWakeupStatus($wakeup, SNOOZE);

		// duplicate snoozed call
		$wakeup_setting = PropertySetting::getWakeupSetting($wakeup->property_id);

		$this->changeWakeupTime($wakeup, 'PT' . $wakeup_setting['snooze_time'] . 'M');

		$log = new WakeupLog();
		$log->awu_id = $wakeup->id;
		$log->status = 'Snoozed';
		$log->record_path = $filepath . $filename;
		$log->set_by_id = 0;

		$guest = $this->getGuestInfo($wakeup);

		if( !empty($guest) )
			$log->set_by = $guest->guest_name;

		$log->save();

		$this->sendUpdatedWakeup($wakeup);

		$this->sendWakeupStatusToOpera($wakeup);

		$this->checkWaitingCalls($wakeup->property_id);

		return Response::json($ret);
	}

	public function changeToFailedStatus($wakeup,$id) {
		$ret = array();
		$ret['code'] = 200;
		if( empty($wakeup) ||($wakeup->status == SUCCESS))		// invalid wakeup
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ID: ' . $id;
			return $ret;
		}
		$prev_status = $wakeup->status;

		
		$wakeup_setting = PropertySetting::getWakeupSetting($wakeup->property_id);
		if( empty($wakeup_setting) )
		{
			$ret['code'] = 202;
			$ret['message'] = 'Invalid Property ID: ' . $wakeup->property_id;
			return $ret;
		}

		$wakeup->status = FAIL;
		if( $prev_status == INPROGRESS )
		$wakeup->fail_reason = FAIL . '(Cause: Call In Progress for more than ' . $wakeup_setting['inprogress_max_wait'] . ' s)';
		else if( $prev_status == WAITING )
		$wakeup->fail_reason = FAIL . '(Cause: Insufficient Lines. Call waiting for more than ' . $wakeup_setting['max_wakeup_waiting_time'] . ' s)';
		else
		$wakeup->fail_reason = FAIL . '(Cause: Guest did not confirm ' . $wakeup->attempts . ' attempts)';
		
		$wakeup->save();

		

		// save log
		$log = new WakeupLog();
		$log->awu_id = $wakeup->id;
		if( $prev_status == INPROGRESS )
			$log->status = FAIL . '(Cause: Call In Progress for more than ' . $wakeup_setting['inprogress_max_wait'] . ' s)';
		else if( $prev_status == WAITING )
			$log->status = FAIL . '(Cause: Insufficient Lines. Call waiting for more than ' . $wakeup_setting['max_wakeup_waiting_time'] . ' s)';
		else
			$log->status = FAIL . '(Cause: Guest did not confirm ' . $wakeup->attempts . ' attempts)';

		$log->set_by_id = 0;
		$log->set_by = 'Hotlync';

		$log->save();

		$this->sendFailAlarm($wakeup, $wakeup_setting, $prev_status);

		$this->sendUpdatedWakeup($wakeup);

		$this->sendWakeupStatusToOpera($wakeup);

		return $ret;
	}

	public function failWakeup(Request $request) {
		$id = $request->get('id');

		$ret = array();
		$ret['code'] = 200;

		$wakeup = Wakeup::find($id);
		

		$this->changeToFailedStatus($wakeup,$id);

		$this->checkWaitingCalls($wakeup->property_id);

		return Response::json($wakeup);
	}

	private function cancelWakeupCall($wakeup, $set_by_id, $set_by)
	{
		if(($wakeup->status == SUCCESS))		// invalid wakeup
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ID: ' . $wakeup->id;
			return $ret;
		}
		$wakeup->status = CANCELED;
		$wakeup->repeat_flag = 0;

		$wakeup->save();

		$log = new WakeupLog();
		$log->awu_id = $wakeup->id;
		$log->status = $wakeup->status;
		$log->set_by_id = $set_by_id;
		$log->set_by = $set_by;

		$log->save();

		$this->sendUpdatedWakeup($wakeup);

		$this->sendWakeupStatusToOpera($wakeup);
	}

	private function duplicateWakeup($wakeup, $attempts, $repeat_flag) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$interval = 'P1D';

		$new_wakeup = new Wakeup();
		$new_wakeup->property_id = $wakeup->property_id;
		$new_wakeup->room_id = $wakeup->room_id;
		$new_wakeup->guest_id = $wakeup->guest_id;
		$new_wakeup->extension_id = $wakeup->extension_id;

		$date = new DateTime($cur_time);
		$date->add(new DateInterval($interval));
		$new_wakeup->time = $date->format('Y-m-d H:i:s');

		$new_wakeup->set_time = $wakeup->set_time;

		$new_wakeup->status = PENDING;
		$new_wakeup->set_by = $wakeup->set_by;
		$new_wakeup->set_by_id = $wakeup->set_by_id;
		$new_wakeup->attempts = $attempts;
		$new_wakeup->repeat_flag = $repeat_flag;
		$new_wakeup->until_checkout_flag = $wakeup->until_checkout_flag;
		$new_wakeup->repeat_end_date = $wakeup->repeat_end_date;

		$new_wakeup->save();

		$log = new WakeupLog();
		$log->awu_id = $new_wakeup->id;
		$log->status = $new_wakeup->status;
		$log->set_by = $new_wakeup->set_by;
		$log->set_by_id = $new_wakeup->set_by_id;

		$log->save();

		$this->sendWakeupStatusToOpera($new_wakeup);
	}

	private function changeWakeupTime($wakeup, $interval) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$date = new DateTime($cur_time);
		$date->add(new DateInterval($interval));
		$wakeup->time = $date->format('Y-m-d H:i:s');

		$wakeup->save();
	}

	private function changeWakeupStatus($wakeup, $status)
	{
		$ret = array();

		if( empty($wakeup) )		// invalid wakeup
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid ID: ' . $wakeup->id;
			return $ret;
		}

		$wakeup->status = $status;
		$wakeup->save();

		$ret['code'] = 200;

		return $ret;
	}

	private function sendFailAlarm($wakeup, $wakeup_setting, $prev_status = '')
	{
		if( $wakeup_setting['duty_manager_notify'] != 'YES' )
			return;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("H:i");

		$alarm_mode = $wakeup_setting['duty_manager_device'];

		$to = explode ("|", $wakeup_setting['duty_manager']);

//		$guest = DB::table('common_guest')
//				->where('guest_id', $wakeup->guest_id)
//				->first();

		$room = Room::find($wakeup->room_id);

		$subject = 'Room # ' . $room->room . ' Wake up Call Failure';
		if( $prev_status == WAITING )
			$content = 'Cause: Insufficient Lines. Call waiting for more than ' . $wakeup_setting['max_wakeup_waiting_time'] . ' s';
		else
			$content = 'Wake up Call has failed for Room ' . $room->room . ' Reason:'.$wakeup->fail_reason.'. Please take appropriate action. Alarm Time : ' . $wakeup->set_time;
		

		$mobile_mode = false;
		$email_mode = false;
		if (strpos($alarm_mode, 'mobile') !== false) {
		    $mobile_mode = true;
		}

		if (strpos($alarm_mode, 'email') !== false) {
		    $email_mode = true;
		}


		if( $mobile_mode == true )
		{
			// send sms
			$message = array();
			$message['type'] = 'sms';
			if( count($to) > 1 )
			{
				$message['to'] = $to[1];
				$message['content'] = $content;

				Redis::publish('notify', json_encode($message));	
			}			
		}
		
		if( $email_mode == true )
		{
			$smtp = Functions::getMailSetting($wakeup->property_id, 'notification_');

			$message = array();
			$message['type'] = 'email';
			if( count($to) > 0 )
			{
				$message['to'] = $to[0];
				$message['subject'] = $subject;
				$message['title'] = $subject;
				$message['content'] = $content;
				$message['smtp'] = $smtp;

				Redis::publish('notify', json_encode($message));
			}			
		}

		$this->saveSystemNotification($wakeup, $content, FAIL);

		foreach ($to as $key => $value) {
			$log = new WakeupLog();
			$log->awu_id = $wakeup->id;
			$log->status = sprintf('Escalated(%s)', $value);
			$log->set_by_id = 0;
			$log->set_by = 'Hotlync';

			$log->save();
		}		
	}

	private function sendUpdatedWakeup($wakeup) {
		$message = array();
		$message['type'] = 'wakeup_event';
		$message['data'] = $wakeup;

		Redis::publish('notify', json_encode($message));
	}

	private function sendWakeupStatusToOpera($wakeup) {
		$property_id = $wakeup->property_id;
	
		$room = DB::table('common_room as cr')
				->leftJoin('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->where('cr.id', $wakeup->room_id)
				->select(DB::raw('cr.*, cf.bldg_id'))
				->first();

		$src_config = array();
		$src_config['src_property_id'] = $property_id;
		$src_config['src_build_id'] = $room->bldg_id;
		$src_config['accept_build_id'] = array();

		$ret = array();
		$ret['property_id'] = $property_id;
		$ret['src_config'] = $src_config;

		$set_date = new DateTime($wakeup->time);
		$set_time = new DateTime($wakeup->set_time);
		$format = '';
		switch($wakeup->status)
		{
			case PENDING:
				$format = 'WR|RN%s|DA%s|TI%s|';
				break;
			case CANCELED:
				$format = 'WC|RN%s|DA%s|TI%s|';
				break;
			case SUCCESS:
				$format = 'WA|RN%s|DA%s|TI%s|ASOK|';
				break;
			case SNOOZE:
				$format = 'WA|RN%s|DA%s|TI%s|';
				break;
			case BUSY:
				if( $wakeup->attempts > 0 )
					$format = 'WA|RN%s|DA%s|TI%s|';
				else
					$format = 'WA|RN%s|DA%s|TI%s|';
				break;
			case UNANSWER:
				if( $wakeup->attempts > 0 )
					$format = 'WA|RN%s|DA%s|TI%s|';
				else
					$format = 'WA|RN%s|DA%s|TI%s|';
				break;
			case FAIL:
				$format = 'WA|RN%s|DA%s|TI%s|ASUR|';
				break;
			case SNOOZE:
				break;
		}

		$ret['msg'] = sprintf($format, $room->room, $set_date->format('ymd'), $set_time->format('His') );
		
		Functions::sendMessageToInterface('interface_hotlync', $ret);
	}

	public function saveSystemNotification($wakeup, $content, $action) {
		$notification = new SystemNotification();

		$notification->type = 'app.guestservice.wakeup';
		$notification->header = 'Alarms';
		$notification->property_id = $wakeup->property_id;

		$notification->content = $content;
		$notification->notification_id = $wakeup->id;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$notification->created_at = $cur_time;
		$notification->save();

		CommonUser::addNotifyCount($wakeup->property_id, 'app.guestservice.wakeup');

		$message = array();
		$message['type'] = 'webpush';
		$message['to'] = $wakeup->property_id;
		$message['content'] = $notification;

		Redis::publish('notify', json_encode($message));
	}

	public function getRoomList(Request $request)
	{
		$guest_group = $request->get('guest_group', '');
		
		
		$model = DB::table('common_guest as cg')
						->leftJoin('common_room as cr', 'cg.room_id', '=', 'cr.id')
						->where('cg.guest_group', $guest_group)
						->where('cg.checkout_flag', 'checkin')
						->select('cr.room')
						->get();
		

		return Response::json($model);
	}

	public function getGuestGroups(Request $request)
	{
		$guest_group = $request->get('guest_group', '');

		$guest = DB::table('common_guest as cg')
			->where('cg.guest_group' ,'!=', 0)
			->select(['cg.guest_group'])
			->where('cg.guest_group', 'like', '%' . $guest_group . '%')
			->where('cg.checkout_flag','checkin')
			->distinct()
			->take(10)
			->get();


		return Response::json($guest);
	}


	public function createMultiple(Request $request) {
		$input = $request->all();

		if ($input['selected'] == 0){
				$rooms = json_decode($request->get('room', '[]'));
		}
		else{
				$rooms = $request->get('room', '');
		}

		$roomlist = DB::table('common_room')
					->whereIn('room',$rooms)
					->select('id')
					->get();

		$set_time = new DateTime($input['time']);
		$input['set_time'] = $set_time->format('H:i:s');	
		
		$ret = array();
		
		for($i = 0; $i < count($roomlist); $i++){
		$extension = DB::table('call_guest_extn')
				->where('room_id', $roomlist[$i]->id)
				->where('primary_extn', 'Y')
				->first();

		if( empty($extension) )
		{
			$extension = DB::table('call_guest_extn')
					->where('room_id',$roomlist[$i]->id)
					->first();
		}

		if( empty($extension) )
		{
			$ret['code'] = 202;
			$ret['message'] = 'Extension is not valid';
			return Response::json($ret);
		}

		$input['extension_id'] = $extension->id;
		$guest = $guest = DB::table('common_guest as cg')
						->where('cg.room_id', $roomlist[$i]->id)
						->orderBy('cg.id', 'desc')
						->orderBy('cg.arrival', 'desc')
						->where('cg.checkout_flag', 'checkin')
						->select(['cg.guest_id'])
						->first();

		$query = DB::table('services_awu');
		$data_query = clone $query;

		$upd_list = $data_query
					->insert(['property_id' => $input['property_id'],
					'room_id' => $roomlist[$i]->id,
					'guest_id' => $guest->guest_id, 
					'time'=> $input['time'],
					'set_time'=>$input['set_time'],
					'extension_id'=>$input['extension_id'],
					'status'=>$input['status'],
					'set_by'=>$input['set_by'],
					'repeat_flag'=>$input['repeat_flag'],
					'repeat_end_date'=>$input['repeat_end_date'],
					'until_checkout_flag'=>$input['until_checkout_flag']
					]);

		$ret['code'] = 200;
		


        $id = DB::table('services_awu')->max('id');



		$log = new WakeupLog();
		$log->awu_id = $id;
		$log->status = 'Created';
		$log->set_by = $input['set_by'];
		$log->set_by_id = $input['set_by_id'];

		$log->save();

		$wakeup = Wakeup::find($id);

		$this->sendWakeupStatusToOpera($wakeup);


		}
		return Response::json($ret);
		
	}

	
}
