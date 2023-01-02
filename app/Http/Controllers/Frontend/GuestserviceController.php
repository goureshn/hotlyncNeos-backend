<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

use App\Models\Common\CommonUser;
use App\Models\Common\CommonUserGroup;
use App\Models\Common\Department;
use App\Models\Common\Guest;
use App\Models\Common\GuestAdvancedDetail;
use App\Models\Common\GuestLog;
use App\Models\Common\GuestSmsTemplate;
use App\Models\Common\PropertySetting;
use App\Models\Common\Room;
use App\Models\Common\SystemNotification;
use App\Models\Service\DeftFunction;
use App\Models\Service\Device;
use App\Models\Service\Escalation;
use App\Models\Service\HskpRoomStatus;
use App\Models\Service\Location;
use App\Models\Service\LocationGroup;
use App\Models\Service\LocationGroupMember;
use App\Models\Service\LocationType;
use App\Models\Service\MinibarRosterList;
use App\Models\Service\MinibarRosterLog;
use App\Models\Service\Priority;
use App\Models\Service\RosterList;
use App\Models\Service\ShiftGroupMember;
use App\Models\Service\ShiftUser;
use App\Models\Service\Task;
use App\Models\Service\TaskGroup;
use App\Models\Service\TaskGroupPivot;
use App\Models\Service\TaskList;
use App\Models\Service\Tasklog;
use App\Models\Service\TaskNotification;
use App\Models\Service\TaskState;
use App\Models\Service\VIPCodes;
use App\Modules\Functions;
use DateInterval;
use DateTime;
use Illuminate\Support\Facades\Config;

use Illuminate\Http\Request;
use DB;
use Response;
use Log;
use Redis;

// define("CLEANING_NOT_ASSIGNED", 100);
// define("CLEANING_PENDING", 0);
// define("CLEAN_RUNNING", 1);
// define("CLEAN_DONE", 2);
// define("CLEAN_DND", 3);
// define("CLEAN_DECLINE", 4);
// define("CLEAN_POSTPONE", 5);
// define("CLEAN_COMPLETE", 7);
// define("CLEAN_PAUSE", 6);

if (!defined('CLEANING_NOT_ASSIGNED')) {
	define("CLEANING_NOT_ASSIGNED", 100);
	define("CLEANING_PENDING", 0);
	define("CLEANING_RUNNING", 1);
	define("CLEANING_DONE", 2);
	define("CLEANING_DND", 3);
	define("CLEANING_REFUSE", 4);
	define("CLEANING_POSTPONE", 5);
	define("CLEANING_PAUSE", 6);
	define("CLEANING_COMPLETE", 7);
	define("CLEANING_DECLINE", 8);
	define("CLEANING_OUT_OF_ORDER", 9);
	define("CLEANING_OUT_OF_SERVICE", 10);
	define("NO_SERVICE", 11);


	define("CLEANING_PENDING_NAME", 'Pending');
	define("CLEAN_RUNNING_NAME", 'Cleaning');
	define("CLEAN_DONE_NAME", 'Done');
	define("CLEAN_DONE_ALT_NAME", 'For Inspection');
	define("CLEAN_DND_NAME", 'DND');
	define("CLEAN_DECLINE_NAME", 'Reject');
	define("CLEAN_POSTPONE_NAME", 'Delay');
	define("CLEAN_PAUSE_NAME", 'Pause');
	define("CLEAN_COMPLETE_NAME", 'Inspected');
}



define("COMPLETEDGS", 0);
define("OPENGS", 1);
define("ESCALATEDGS", 2);
define("TIMEOUTGS", 3);
define("CANCELEDGS", 4);
define("SCHEDULEDGS", 5);
define("UNASSIGNEDGS", 6);

define("STARTED", 0);
define("ASSIGNED", 1);
define("ESCALATED2", 2);
define("UNATTENDED", 3);

define("COMPLETE_APPROVE", 0);
define("ON_ROUTE", 1);
define("REJECTED", 2);
define("RETURNED", 3);
define("PENDING", 4);

define("SUCCESS", 200);
define("FAIL", 101);

define("WEB_SOURCE", 'HotLync Web');
define("MOBILE_SOURCE", 'HotLync Mobile');
define("IVR_SOURCE", 'HotLync IVR');
define("BOT_SOURCE", 'HotLync Bot');
define("ALEXA_SOURCE", 'HotLync Alexa');

if (!defined('VACANT')) {
	define("VACANT", 'Vacant');
	define("OCCUPIED", 'Occupied');
	define("DUE_OUT", 'Due Out');
	define("ARRIVAL", 'Arrival');
	define("OUT_OF_ORDER", 'Out of Order');
}



class GuestserviceController extends Controller
{
	public function getTicketStatisticInfo(Request $request)
	{
		$period = $request->get('period', 'Today');
		$end_date = $request->get('end_date', '');
		$during = $request->get('during', '');
		$property_id = $request->get('property_id', '');
		$user_id = $request->get('user_id', 0);


		$ret = array();
		$dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT_DASHBOARD'));
		if ($dept_id == 0) {
			$dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT'));
		}


		switch ($period) {
			case 'Today';
				$ret = $this->getTicketStaticsticsByToday($property_id, $dept_id, $request);
				break;
			case 'Weekly';
				$ret = $this->getTicketStaticsticsByDate($end_date, 7, $property_id, $dept_id, $request);
				break;
			case 'Monthly';
				$ret = $this->getTicketStaticsticsByDate($end_date, 30, $property_id, $dept_id, $request);
				break;
			case 'Custom Days';
				$ret = $this->getTicketStaticsticsByDate($end_date, $during, $property_id, $dept_id, $request);
				break;
			case 'Yearly';
				$ret = $this->getTicketStaticsticsByYearly($end_date, $property_id, $dept_id, $request);
				break;
		}

		return Response::json($ret);
	}

	public function getTicketStaticsticsByToday($property_id, $dept_id, $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$end_time = date('Y-m-d H:i');

		$start_time = date('Y-m-d H:i', strtotime("-1 days"));

		$query_complaint = DB::table('services_complaint_request');
		//->whereRaw("DATE(created_at) = '" . $cur_date . "'");

		$query = DB::table('services_task as st')
			->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
			->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id');


		if ($dept_id > 0)
			$query->where('st.department_id', $dept_id);
		else
			$query->where('st.property_id', $property_id);

		$ret = array();

		$count_info = array();

		$ticket_info = array();

		$axis_start = date('Y-m-d H:i:s', strtotime("-1 days"));

		for ($i = 0; $i < 13; $i++) {
			$start_sub_time = date('Y-m-d H:i:s', strtotime($axis_start) + 7200 * $i);
			$end_sub_time = date('Y-m-d H:i:s', strtotime($axis_start) + 7200 * ($i + 1));

			$count_query = clone $query;
			$count_query->whereBetween('st.start_date_time', array($start_sub_time, $end_sub_time));

			// Complaint
			$complaint_query = clone $query_complaint;
			$complaint_query->whereBetween('created_at', array($start_sub_time, $end_sub_time));

			$ticket_count = $this->getTotalGuestServiceCount($count_query, $complaint_query);
			$ticket_count['xtime'] = date('H:i', strtotime($axis_start) + 7200 * $i);

			$ticket_info['ticket_count'] = $ticket_count;

			$count_info[$i] = $ticket_info;
		} //////////////////////for end


		$ret['count_info'] = $count_info;
		////////////////

		// total counts

		$count_query = clone $query;
		$count_query->whereBetween('st.start_date_time', array($start_time, $end_time));

		// Complaint
		$complaint_query = clone $query_complaint;
		$complaint_query->whereBetween('created_at', array($start_time, $end_time));

		$total_count = $this->getTotalCountStatistics($count_query, $complaint_query, $request);

		$ret['by_category_count'] = $count_query
			->leftJoin('services_task_category as tg', 'tl.category_id', '=', 'tg.id')
			->groupBy('tg.name')
			->orderBy('cnt', 'DESC')
			->limit(10)
			->select(DB::raw('count(*) as cnt, tg.name as label'))
			->get();


		$ret = array_merge($ret, $total_count);

		return $ret;
	}

	public function getTicketStaticsticsByDate($end_date, $during, $property_id, $dept_id, $request)
	{
		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P' . $during . 'D'));
		$query_complaint = DB::table('services_complaint_request');

		$query = DB::table('services_task as st')
			->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
			->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id');


		if ($dept_id > 0)
			$query->where('st.department_id', $dept_id);
		else
			$query->where('st.property_id', $property_id);

		$ret = array();

		$count_info = array();

		$ticket_info = array();
		$guest_info = array();

		for ($i = 0; $i < $during; $i++) {
			$date->add(new DateInterval('P1D'));

			$cur_date = $date->format('Y-m-d');

			$count_query = clone $query;
			$count_query->whereBetween('st.start_date_time', array($cur_date . ' 00:00:00', $cur_date . ' 23:59:59'));

			// Complaint
			$complaint_query = clone $query_complaint;
			$complaint_query->whereRaw("DATE(created_at) = '" . $cur_date . "'");

			$ticket_count = $this->getTotalGuestServiceCount($count_query, $complaint_query);

			if ($during == 7) {
				$unixTimestamp = strtotime($cur_date);
				$dayOfWeek = date("l", $unixTimestamp);
				$ticket_count['xtime'] =  $dayOfWeek;
			} else {
				$ticket_count['xtime'] = $cur_date;
			}

			$ticket_info['ticket_count'] = $ticket_count;

			$count_info[$i] = $ticket_info;
		}

		$ret['count_info'] = $count_info;

		// top count
		$datetime = new DateTime($end_date);
		$datetime->sub(new DateInterval('P' . $during . 'D'));
		$start_date =  $datetime->format('Y-m-d');
		//$start_date = date('Y-m-d');

		$count_query = clone $query;
		$count_query->whereBetween('st.start_date_time', array($start_date . ' 00:00:00', $end_date . ' 23:59:59'));

		// Complaint
		$complaint_query = clone $query_complaint;
		$time_range_complaint = sprintf("'%s' < DATE(created_at) AND DATE(created_at) <= '%s'", $start_date, $end_date);
		$complaint_query->whereRaw($time_range_complaint);

		$total_count = $this->getTotalCountStatistics($count_query, $complaint_query, $request);

		$ret['by_category_count'] = $count_query
			->leftJoin('services_task_category as tg', 'tl.category_id', '=', 'tg.id')
			->groupBy('tg.name')
			->orderBy('cnt', 'DESC')
			->limit(10)
			->select(DB::raw('count(*) as cnt, tg.name as label'))
			->get();

		$ret = array_merge($ret, $total_count);

		return $ret;
	}

	public function getTicketStaticsticsByYearly($end_date, $property_id, $dept_id, $request)
	{
		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P1Y'));

		$query_complaint = DB::table('services_complaint_request');

		$query = DB::table('services_task as st')
			->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
			->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id');

		if ($dept_id > 0)
			$query->where('st.department_id', $dept_id);
		else
			$query->where('st.property_id', $property_id);

		$ret = array();

		$count_info = array();

		$ticket_info = array();
		$guest_info = array();

		for ($i = 0; $i < 12; $i++) {
			$date->add(new DateInterval('P1M'));

			$cur_month = $date->format('Y-m');
			$next_month = date('Y-m-01 00:00:00', strtotime("1 Months", strtotime($cur_month)));

			$count_query = clone $query;
			$count_query->whereBetween('st.start_date_time', array($cur_month . '-01 00:00:00', $next_month));

			// Complaint
			$complaint_query = clone $query_complaint;
			$complaint_query->whereRaw("DATE_FORMAT(created_at, \"%Y-%m\") = '" . $cur_month . "'");

			$ticket_count = $this->getTotalGuestServiceCount($count_query, $complaint_query);
			$ticket_count['xtime'] =  $date->format('M');

			$ticket_info['ticket_count'] = $ticket_count;

			$count_info[$i] = $ticket_info;
		}

		$ret['count_info'] = $count_info;

		$datetime = new DateTime($end_date);
		$datetime->sub(new DateInterval('P1Y'));
		$start_date =  $datetime->format('Y-m-d');

		$count_query = clone $query;
		$count_query->whereBetween('st.start_date_time', array($start_date . ' 00:00:00', $end_date . ' 23:59:59'));

		// Complaint
		$complaint_query = clone $query_complaint;
		$time_range_complaint = sprintf("'%s' < DATE(created_at) AND DATE(created_at) <= '%s'", $start_date, $end_date);
		$complaint_query->whereRaw($time_range_complaint);

		$total_count = $this->getTotalCountStatistics($count_query, $complaint_query, $request);

		$ret['by_category_count'] = $count_query
			->leftJoin('services_task_category as tg', 'tl.category_id', '=', 'tg.id')
			->groupBy('tg.name')
			->orderBy('cnt', 'DESC')
			->limit(10)
			->select(DB::raw('count(*) as cnt, tg.name as label'))
			->get();

		$ret = array_merge($ret, $total_count);

		return $ret;
	}

	private function getTotalCountStatistics($count_query, $complaint_query, $request)
	{
		$count = $this->getTotalGuestServiceCount($count_query, $complaint_query);

		$ret = array();

		$ret['total'] = $count;

		// By task
		$by_task_count = $this->getGuestServiceCountByTask($count_query, 10);
		$ret['by_task_count'] = $by_task_count;


		//By Department
		$department_list = $this->getDepartmentListWithServices($request);
		$by_dept = array();
		for ($d = 0; $d < count($department_list); $d++) {
			$dept_id = $department_list[$d]->id;

			$dept_query = clone $count_query;
			$dept_query->where('st.department_id', $dept_id);

			$by_dept[$d] = $this->getTotalGuestServiceCount($dept_query, null);

			$by_dept[$d]['department'] = $department_list[$d]->short_code;
		}

		$ret['by_department_count'] = $by_dept;

		return $ret;
	}

	private function getTotalGuestServiceCount($query, $complaint_query)
	{
		$count_query = clone $query;

		$total_count_select = '
						CAST(COALESCE(sum(st.status_id = 0 and st.duration <= st.max_time), 0) AS UNSIGNED) as ontime,
						CAST(COALESCE(sum(st.status_id = 4), 0) AS UNSIGNED) as canceled,
						CAST(COALESCE(sum(st.status_id = 3), 0) AS UNSIGNED) as timeout,
						CAST(COALESCE(sum(st.escalate_flag), 0) AS UNSIGNED) as escalated,
						CAST(COALESCE(sum(st.status_id = 5), 0) AS UNSIGNED) as scheduled,
						CAST(COALESCE(sum((st.status_id = 1 or st.status_id = 2 ) and st.running = 0), 0) AS UNSIGNED) as hold,
						count(*) as total						
					';

		$count = $count_query
			->select(DB::raw($total_count_select))
			->first();

		$ret = array();

		if (empty($count)) {
			$ret['ontime'] = 0;
			$ret['canceled'] = 0;
			$ret['timeout'] = 0;
			$ret['escalated'] = 0;
			$ret['scheduled'] = 0;
			$ret['hold'] = 0;
			$ret['total'] = 0;
		} else {
			$ret['ontime'] = $count->ontime;
			$ret['canceled'] = $count->canceled;
			$ret['timeout'] = $count->timeout;
			$ret['escalated'] = $count->escalated;
			$ret['scheduled'] = $count->scheduled;
			$ret['hold'] = $count->hold;
			$ret['total'] = $count->total;
		}

		if (!empty($complaint_query)) {
			$count_query = clone $complaint_query;
			$count = $count_query->select(DB::raw('count(*) as cnt'))
				->first();

			if (empty($count))
				$ret['complaint'] = 0;
			else
				$ret['complaint'] = $count->cnt;
		}

		return $ret;
	}

	private function getGuestServiceCountByTask($query, $limit)
	{
		$count_query = clone $query;

		$total_count_select = 'tl.task,
						CAST(COALESCE(sum(st.status_id = 0 and st.duration <= st.max_time), 0) AS UNSIGNED) as ontime,
						CAST(COALESCE(sum(st.status_id = 4), 0) AS UNSIGNED) as canceled,
						CAST(COALESCE(sum(st.status_id = 3), 0) AS UNSIGNED) as timeout,
						CAST(COALESCE(sum(st.escalate_flag), 0) AS UNSIGNED) as escalated,
						CAST(COALESCE(sum(st.status_id = 5), 0) AS UNSIGNED) as scheduled,
						CAST(COALESCE(sum((st.status_id = 1 or st.status_id = 2 ) and st.running = 0), 0) AS UNSIGNED) as hold,
						count(*) as cnt						
					';

		$count_list = $count_query
			->where('st.task_list', '!=', '0')
			->groupBy('st.task_list')
			->orderBy('cnt', 'desc')
			->take($limit)
			->select(DB::raw($total_count_select))
			->get();

		return $count_list;
	}

	public function getDepartmentListWithServices(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$user_id = $request->get('user_id', '0');
		$dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT_DASHBOARD'));
		if ($dept_id == 0) {
			$dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT'));
		}

		$query = DB::table('common_department as cd')
			->where('cd.services', 'Y')
			->where('cd.property_id', $property_id);

		if ($dept_id >  0)
			$query->where('cd.id', $dept_id);

		$departlist = $query->select(DB::raw('cd.*'))
			->get();

		return $departlist;
	}

	public function getGSDispatcherList(Request $request) //$property_id, $period
	{
		$property_id = $request->get('property_id', 0);
		$period = $request->get('period', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date('Y-m-d H:i:s');

		$userlist = DB::table('common_users as cu')
			->leftJoin('common_job_role as jb', 'jb.id', '=', 'cu.job_role_id')
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->leftJoin('services_devices as sd', 'cu.device_id', '=', 'sd.device_id')
			->where('cd.services', 'Y')
			->where('cd.property_id', $property_id)
			->where('cu.deleted', 0)
			->where('cu.active_status', 1)
			->select(DB::raw('cu.*, cu.id as user_id, jb.job_role, cu.device_id, 
								COALESCE(sd.name, "Web") as device_name
							'))
			->orderBy('cu.last_log_in', 'desc')
			->get();

		$user_ids = [];
		foreach ($userlist as $row)
			$user_ids[] = $row->id;

		$query = DB::table('services_task as st')
			->whereIn('st.dispatcher', $user_ids)
			->groupBy('st.dispatcher');


		switch ($period) {
			case 'Today';
				$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));
				$query->whereBetween('st.start_date_time', array($last24, $cur_time));
				break;
			case 'Weekly';
				$last7 = date('Y-m-d H:i:s', strtotime("-7 days"));
				$query->whereBetween('st.start_date_time', array($last7, $cur_time));
				break;
			case 'Monthly';
				$lastmonth1 = date('Y-m-d H:i:s', strtotime("-1 months"));
				$query->whereBetween('st.start_date_time', array($lastmonth1, $cur_time));
				break;
			case 'Custom Days';
				break;
			case 'Yearly';
				$lastyears1 = date('Y-m-d H:i:s', strtotime("-1 years"));
				$query->whereBetween('st.start_date_time', array($lastyears1, $cur_time));
				break;
		}

		$count_list = $query
			->select(DB::raw('st.dispatcher, count(*) as total, 
		    				COALESCE(sum(st.status_id = 1 or st.status_id = 2), 0) as actived,
		    				COALESCE(sum(st.escalate_flag), 0) as escalated,
		    				COALESCE(sum(st.status_id = 0 and st.duration <= st.max_time), 0) as on_time '))
			->get();

		foreach ($userlist as $key => $row) {
			foreach ($count_list as $key1 => $row1) {
				if ($row->user_id == $row1->dispatcher) {
					$userlist[$key]->count = $row1;
					break;
				}
			}
			if (empty($userlist[$key]->count)) {
				$userlist[$key]->count = array('actived' => 0, 'on_time' => 0, 'escalated' => 0, 'total' => 0);
			}

			// secondary job role
			$userlist[$key]->secondary_job_roles = DB::table('services_secondary_jobrole as sjr')
				->join('common_job_role as jr', 'sjr.job_role_id', '=', 'jr.id')
				->where('sjr.user_id', $row->id)
				->select(DB::raw('jr.*'))
				->get();
		}

		return $userlist;
	}

	public function getFilterList(Request $request)
	{
		$attendant = $request->get('attendant', 0);
		$property_id = $request->get('property_id', 0);

		$dept_id = CommonUser::getDeptID($attendant, Config::get('constants.GUESTSERVICE_DEPT'));

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$query = DB::table('services_task as st')
			->where('st.property_id', $property_id);
			// ->leftJoin('services_dept_function as df', 'st.dept_func', '=', 'df.id')
			// ->leftJoin('services_type as ty', 'st.type', '=', 'ty.id')
			// ->leftJoin('services_priority as sp', 'st.priority', '=', 'sp.id')
			// ->leftJoin('common_users as cu', 'st.dispatcher', '=', 'cu.id')
			// ->leftJoin('common_users as cu1', 'st.attendant', '=', 'cu1.id')
			// ->leftJoin('common_room as cr', 'st.room', '=', 'cr.id')
			// ->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
			// ->leftJoin('services_complaints as sc', 'st.complaint_list', '=', 'sc.id')
			// ->leftJoin('services_complaint_type as ct', 'sc.type_id', '=', 'ct.id')
			// ->leftJoin('services_compensation as scom', 'st.compensation_id', '=', 'scom.id')
			// ->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id')
			// ->leftJoin('common_guest as cg', 'st.guest_id', '=', 'cg.guest_id')
			// ->leftJoin('common_users as cu2', 'st.user_id', '=', 'cu2.id')
			// ->leftJoin('common_user_group as cug', 'st.group_id', '=', 'cug.id')
		;


		//	$filterlist = array();


		// $today = array();
		// $today['name'] = 'Todays Tickets';
		// $today_query = clone $query;
		// $today['badge'] = $today_query->whereRaw("DATE(st.start_date_time) = '" . $cur_date . "'")->count();
		// array_push($filterlist, $today);

		// $byme = array();
		// $byme['name'] = 'Tickets created by me';
		// $byme_query = clone $query;
		// $byme['badge'] = $byme_query->where('st.attendant', $attendant)->count();
		// array_push($filterlist, $byme);

		// $openticket = array();
		// $openticket['name'] = 'Open Tickets';
		// $openticket_query = clone $query;
		// $openticket['badge'] = $openticket_query->where('st.status_id', OPEN)->count();
		// array_push($filterlist, $openticket);

		// $escalate = array();
		// $escalate['name'] = 'Escalated Tickets';
		// $escalate_query = clone $query;
		// $escalate['badge'] = $escalate_query->where('st.status_id', ESCALATED)->count();
		// array_push($filterlist, $escalate);

		// $bydepartment = array();
		// $bydepartment['name'] = 'By Department';
		// //$bydepartment['badge'] = '2';
		// $bydepartment_query = clone $query;
		// $bydepartment['badge'] = $bydepartment_query->where('st.type', 2)->count();
		// array_push($filterlist, $bydepartment);

		// $allticket = array();
		// $allticket['name'] = 'All Tickets';
		// $allticket_query = clone $query;
		// $allticket['badge'] = $allticket_query->count();
		// array_push($filterlist, $allticket);

		// $guestticket = array();
		// $guestticket['name'] = 'Guest Tickets';
		// $guestticket_query = clone $query;
		// $guestticket['badge'] = $guestticket_query->where('st.type', 1)->count();
		// array_push($filterlist, $guestticket);

		// $urgency = array();
		// $urgency['name'] = 'Urgency';
		// $urgency_query = clone $query;
		// $urgency['badge'] = $urgency_query->whereRaw("(TIME_TO_SEC(st.start_date_time) + st.max_time * 60 - TIME_TO_SEC('" . $cur_time ."') < 120) AND (TIME_TO_SEC(st.start_date_time) + st.max_time * 60 - TIME_TO_SEC('" . $cur_time ."') > 0)")->count();
		// array_push($filterlist, $urgency);

		// $schedule = array();
		// $schedule['name'] = 'Schedule Tickets';
		// $schedule_query = clone $query;
		// $schedule['badge'] = $schedule_query->where('st.status_id', SCHEDULED)->count();
		// array_push($filterlist, $schedule);

		$ret = array();
		$department = DB::table('common_department')
			->where('property_id', $property_id)
			->where('services', 'Y')
			->get();
		//if permission have been  assign, department is null
		if ($dept_id > 0) $department = array();

		$priority = DB::table('services_priority')
			->get();

		$profile = DB::table('services_task_profile')
			->where('user_id', $attendant)
			->first();
		if (!empty($profile))
			$ret['profile'] = $profile;

		//$ret['filterlist'] = $filterlist;
		$ret['department'] = $department;
		$ret['priority'] = $priority;

		return Response::json($ret);
	}

	public function getPriorityList(Request $request)
	{
		// select room list with property id
		$prioritylist = DB::table('services_priority as pr')
			//->where('pr.priority', 'like', '%' . $priority . '%')
			->select(DB::raw('pr.*'))
			->get();

		return Response::json($prioritylist);
	}

	public function getRoomList(Request $request)
	{
		// select room list with property id
		$room = $request->get('room', '1001');
		$property_id = $request->get('property_id', 4);
		$roomlist = DB::table('common_room as cr')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->where('cr.room', 'like', '%' . $room . '%')
			->where('cb.property_id', $property_id)
			->select(DB::raw('cr.*, cf.bldg_id, cb.property_id'))
			->get();

		return Response::json($roomlist);
	}

	public function getLocationList(Request $request)
	{
		$value = '%' . $request->get('location', '') . '%';
		$property_id = $request->get('property_id', 4);

		$ret = $this->getLocationListData($value, $property_id);
		return Response::json($ret);
	}

	public function getLocationListData($filter, $pro_id)
	{
		$ret = DB::table('services_location as sl')
			->join('common_property as cp', 'sl.property_id', '=', 'cp.id')
			->join('services_location_type as lt', 'sl.type_id', '=', 'lt.id')
			->where('sl.property_id', $pro_id)
			->where('sl.name', 'like', $filter)
			->groupBy('sl.name')
			->groupBy('sl.type_id')
			->select(DB::Raw('sl.id, sl.name, sl.property_id, sl.id as lg_id, sl.room_id, lt.type, 
					sl.room_id as type_id,
					cp.name as property'))
			->get();

		return $ret;
	}

	public function getStaffList(Request $request)
	{
		$value = '%' . $request->get('value', '') . '%';
		$property_id = $request->get('property_id', 4);

		$ret = DB::table('common_users as cu')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
			->leftJoin('common_department as de', 'cu.dept_id', '=', 'de.id')
			->whereRaw("CONCAT(cu.first_name, ' ', cu.last_name) like '" . $value . "'")
			->where('cu.deleted', 0)
			->where('de.property_id', $property_id)
			->select(DB::raw('cu.*, jr.job_role, jr.cost, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename , de.department'))
			->get();


		return Response::json($ret);
	}

	public function getDepartmentList(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$user_id = $request->get('property_id', '0');

		$dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT'));

		$query = DB::table('common_department as cd')
			->leftJoin('services_dept_function as sdf', 'cd.default_dept_func_id', '=', 'sdf.id')
			->where('cd.property_id', $property_id);

		if ($dept_id >  0)
			$query->where('cd.id', $dept_id);

		$departlist = $query->select(DB::raw('cd.*, sdf.id as dept_func_id, sdf.function, sdf.default_task_group_id'))
			->get();

		$ret = array();
		$ret['departlist'] = $departlist;
		return Response::json($ret);
	}

	public function getUserGroupList(Request $request)
	{
		$value = '%' . $request->get('value', '') . '%';

		$ret = DB::table('common_user_group as cug')
			->where('cug.name', 'like', $value)
			->orWhere('cug.access_level', 'like', $value)
			->select(DB::raw('cug.*, cug.name as group_name'))
			->get();


		return Response::json($ret);
	}

	public function getSystemTaskList(Request $request)
	{
		$property_id = $request->get('property_id', 1);
		$user_id = $request->get('user_id', 0);
		$user = CommonUser::find($user_id);
		$lang = $user->lang_id;
		$ret = array();

		$ids = PropertySetting::getSystemTaskListIDs($property_id);

		$taslist = DB::table('services_task_list as stl')
			->join('services_task_group_members as tgm', 'tgm.task_list_id', '=', 'stl.id')
			->join('services_task_group as stg', 'tgm.task_grp_id', '=', 'stg.id')
			->join('services_dept_function as sdf', 'stg.dept_function', '=', 'sdf.id')
			->join('common_department as cd', 'sdf.dept_id', '=', 'cd.id')
			->where('cd.property_id', $property_id)
			->whereIn("stl.type", $ids)
			->select(DB::raw('stl.*, cd.property_id'))
			->get();

		foreach ($taslist as  $key => $row) {

			if ($lang != 0 && $row->lang) {
				$languages = json_decode($row->lang);
				foreach ($languages as $key_l => $val_l) {
					if (($val_l->id == $lang) && $val_l->text) {

						$row->task = $val_l->text;
					}
				}
			}
		}
		return Response::json($taslist);
	}

	public function getTaskList(Request $request)
	{
		$property_id = $request->get('property_id', 1);
		$task_name = $request->get('task', '');
		$task_request = $request->get('request', '');
		$dept_func_id = $request->get('dept_func_id', 0);
		$type  = $request->get('type', 0);
		$user_id = $request->get('user_id', 0);
		$by_guest_flag = $request->get('by_guest', 0);
		$tasklist = $this->makeTaskList($property_id, $task_name, $task_request, $dept_func_id, $type, $user_id, $by_guest_flag);


		return Response::json($tasklist);
	}

	public function makeTaskList($property_id, $task_name, $task_request, $dept_func_id, $type, $user_id, $by_guest_flag)
	{
		$user = CommonUser::find($user_id);
		$lang = $user->lang_id;
		$dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT_CREATE'));
		if ($dept_id == 0)
			$dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT'));
		$lang_string = '"id":' . $lang . ',"text":"';
		$where_task = sprintf(
			"task like '%%%s%%' or
								lang like '%%%s%s%%'",
			$task_name,
			$lang_string,
			$task_name
		);

		$start = microtime(true);

		if ($dept_func_id == 0) {
			//echo "Here";
			$query = DB::table('services_task_list as stl')
				->join('services_task_group_members as tgm', 'tgm.task_list_id', '=', 'stl.id')
				->join('services_task_group as stg', 'tgm.task_grp_id', '=', 'stg.id')
				->join('services_dept_function as sdf', 'stg.dept_function', '=', 'sdf.id')
				->join('common_department as cd', 'sdf.dept_id', '=', 'cd.id')
				->where('stl.status', 1)
				->where('cd.property_id', $property_id);

			if ($by_guest_flag == 1)
				$query->where('stg.by_guest_flag', 1);

			if ($dept_id > 0)
				$query->where('sdf.dept_id', $dept_id);

			if ($type > 0)
				$query->whereRaw("(stl.type = 0 or stl.type > 100 or stl.type = $type)");
			$data_query = clone $query;

			$tasklist = $data_query
				->whereRaw($where_task)
				->select(DB::raw('stl.*, stg.by_guest_flag, stg.unassigne_flag, cd.property_id'))
				->get();

			if (!empty($task_request)) {
				$data_request = clone $query;

				$where1 = sprintf(
					"task like '%%%s%%' and 
							task not like '%%%s%%'",
					$task_request,
					$task_name
				);

				// echo $where1;
				$list = $data_request
					->whereRaw($where1)
					->select(DB::raw('stl.*, stg.by_guest_flag, stg.unassigne_flag, cd.property_id'))
					->get();
				foreach ($list as $key => $value) {
					$tasklist[] = $value;
				}
			}
		} else {
			//echo "Not Here";
			$query = DB::table('services_task_list as stl')
				->join('services_task_group_members as tgm', 'tgm.task_list_id', '=', 'stl.id')
				->join('services_task_group as stg', 'tgm.task_grp_id', '=', 'stg.id')
				->join('services_dept_function as sdf', 'stg.dept_function', '=', 'sdf.id')
				->join('common_department as cd', 'sdf.dept_id', '=', 'cd.id')
				->whereRaw($where_task)
				->where('stl.status', 1)
				->where('cd.property_id', $property_id)
				->where('stg.dept_function', $dept_func_id);

			if ($type > 0)
				$query->whereRaw("(stl.type = 0 or stl.type > 100 or stl.type = $type)");

			if ($by_guest_flag == 1)
				$query->where('stg.by_guest_flag', 1);

			$tasklist = $query
				->select(DB::raw('stl.*, stg.unassigne_flag, cd.property_id'))
				->get();
		}

		foreach ($tasklist as  $key => $row) {

			if ($lang != 0 && $row->lang) {
				$languages = json_decode($row->lang);
				if (is_array($languages)) // is_array condition added by tejas
				{
					foreach ($languages as $key_l => $val_l) {
						if (($val_l->id == $lang) && $val_l->text) {
							$row->task = $val_l->text;
						}
					}
				}
			}
		}
		$end = microtime(true);

		return $tasklist;
	}

	public function getTicketList(Request $request)
	{
		$start = microtime(true);

		$input = $request->all();

		$property_id = $request->get('property_id', 0);
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		//$filtername = $request->get('filtername', 'All Tickets');
		$filter = $request->get('filtername', []);
		if (!empty($filter)) {
			$filtername = $filter['ticket'];
			$start_date = $filter['start_date'];
			$end_date = $filter['end_date'];
			$status_ids = json_decode($filter['status_id']);
			$priorities = json_decode($filter['priority']);
			$department_ids = json_decode($filter['department_id']);
			$type_ids = json_decode($filter['type_id']);
		} else {
			$filtername = 'Last 24 Hours';
		}
		//echo $filtername;
		$attendant = $request->get('attendant', 0);
		$lang = $request->get('lang', 0);
		// echo $lang;
		$dispatcher = $request->get('dispatcher', 0);
		$searchoption = $request->get('searchoption', '');

		$dept_id = CommonUser::getDeptID($attendant, Config::get('constants.GUESTSERVICE_DEPT_VIEW'));
		if ($dept_id == 0)
			$dept_id = CommonUser::getDeptID($attendant, Config::get('constants.GUESTSERVICE_DEPT'));

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$yesterday_time = date('Y-m-d 00:00:00', strtotime("-1 days")); // last 24
		$cur_date = date('Y-m-d');
		$yesterday_date = date('Y-m-d', strtotime("-1 days"));

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();

		$query = DB::table('services_task as st')
			->leftJoin('services_dept_function as df', 'st.dept_func', '=', 'df.id')
			->leftJoin('services_type as ty', 'st.type', '=', 'ty.id')
			->leftJoin('services_priority as sp', 'st.priority', '=', 'sp.id')
			->leftJoin('common_users as cu', 'st.dispatcher', '=', 'cu.id')
			->leftJoin('common_users as cu1', 'st.attendant', '=', 'cu1.id')
			->leftJoin('common_room as cr', 'st.room', '=', 'cr.id')
			->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
			->leftJoin('services_complaints as sc', 'st.complaint_list', '=', 'sc.id')
			->leftJoin('services_complaint_type as ct', 'sc.type_id', '=', 'ct.id')
			->leftJoin('services_compensation as scom', 'st.compensation_id', '=', 'scom.id')
			->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id')
			->leftJoin('common_users as cu2', 'st.user_id', '=', 'cu2.id')
			->leftJoin('common_user_group as cug', 'st.group_id', '=', 'cug.id')
			->leftJoin('services_location as sl', 'st.location_id', '=', 'sl.id')
			->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			->leftJoin('services_task_state as ta_st', 'st.id', '=', 'ta_st.task_id')
			//	->leftJoin('common_guest as cg', 'st.guest_id', '=', 'cg.guest_id')
			->leftJoin('common_guest as cg', function ($join) {
				$join->on('st.guest_id', '=', 'cg.guest_id');
				$join->on('st.property_id', '=', 'cg.property_id');
			})

			// ->leftJoin('services_task_feedback as stf', 'stf.task_id', '=', 'st.id')
			->leftJoin('services_devices as sd', 'sd.device_id', '=', 'cu.device_id');

		$query->where('st.property_id', $property_id);
		if ($dept_id > 0)
			$query->where('st.department_id', $dept_id);

		switch ($filtername) {
				//case 'Todays Tickets';
			case 'Last 24 Hours';
				$where = sprintf("st.start_date_time >= '%s'", $yesterday_time);
				break;
			case 'Custom Days';
				if (!empty($start_date))
					$where = sprintf("st.created_time >= '%s' AND st.created_time <= '%s'", $start_date . ' 00:00:00', $end_date . ' 23:59:59');
				break;
			case 'Tickets created by me';
				$where = sprintf("st.attendant = '%d'", $attendant);
				break;
			case 'Open Tickets';
				$where = sprintf("st.status_id = '%d'", OPENGS);
				break;
			case 'Escalated Tickets';
				$where = sprintf("st.status_id = '%d'", ESCALATEDGS);
				break;
			case 'By Department';
				$where = sprintf("st.type = '%d'", 2);
				break;
			case 'All Tickets';
				$where = "st.id != 0";
				break;
			case 'Guest Tickets';
				$where = sprintf("st.type = '%d'", 1);
				break;
			case 'Urgency';
				$where = "(TIME_TO_SEC(st.start_date_time) + st.max_time * 60 - TIME_TO_SEC('" . $cur_time . "') < 120) AND (TIME_TO_SEC(st.start_date_time) + st.max_time * 60 - TIME_TO_SEC('" . $cur_time . "') > 0)";
				break;
			case 'Schedule Tickets';
				$where = sprintf("st.status_id = '%d'", SCHEDULEDGS);
				break;
			case 'My Tasks';
				$where = sprintf("st.dispatcher = '%s' AND (st.status_id = '1' OR st.status_id = '2') AND (st.type = 1 OR st.type = 2 OR st.type = 4)", $dispatcher);
				break;
			case 'Escalations';
				$query->whereExists(function ($query) use ($dispatcher) {
					$query->from('services_task_state as ts')
						->whereRaw('ts.task_id = st.id')
						->where('ts.dispatcher', $dispatcher)
						->where('ts.level', '>', 0)
						->select(DB::raw(1));
				});
				$where = sprintf("st.status_id = '2'");
				break;
			case 'Approvals';
				$where = sprintf("st.dispatcher = '%s' AND (st.status_id = '1' OR st.status_id = '2') AND (st.type = 1 OR st.type = 2 OR st.type = 4)", $dispatcher);
				break;
			case 'Complaints';
				$where = sprintf("(exists (select 1 from services_complaint_state as cs where cs.task_id = st.id AND cs.dispatcher = %d) OR st.dispatcher = '%d') AND (st.status_id = '1' OR st.status_id = '2') AND (st.type = 3)", $dispatcher, $dispatcher);
				break;
			case '':
				$where = sprintf("st.status_id != '%s' AND st.status_id != '%s'", COMPLETEDGS, CANCELEDGS);
				break;
		}

		if ($searchoption == '') {
			if (!empty($status_ids) && count($status_ids) > 0) {
				$query->where(function ($query2) use ($status_ids) {
					for ($i = 0; $i < count($status_ids); $i++) {
						if ($i == 0) {
							if ($status_ids[$i] == 9) {
								$query2->where(function ($query3) use ($status_ids) {
									$query3->where('st.status_id', TIMEOUTGS);
									$query3->where('st.closed_flag', 1);
								});
							} else if ($status_ids[$i] == 3) {
								$query2->where(function ($query3) use ($status_ids) {
									$query3->where('st.status_id', TIMEOUTGS);
									$query3->where('st.closed_flag', 0);
								});
							} else if ($status_ids[$i] == 7) {
								$query2->where(function ($query3) use ($status_ids) {
									$query3->where('st.status_id', TIMEOUTGS);
									$query3->where('st.closed_flag', 1);
								});
							} else {
								$query2->where('st.status_id', $status_ids[$i]);
							}
						} else {
							if ($status_ids[$i] == 9) {
								$query2->orWhere(function ($query3) use ($status_ids) {
									$query3->where('st.status_id', TIMEOUTGS);
									$query3->where('st.closed_flag', 1);
								});
							} else if ($status_ids[$i] == 3) {
								$query2->orWhere(function ($query3) use ($status_ids) {
									$query3->where('st.status_id', TIMEOUTGS);
									$query3->where('st.closed_flag', 0);
								});
							} else if ($status_ids[$i] == 7) {
								$query2->where(function ($query3) use ($status_ids) {
									$query3->where('st.status_id', TIMEOUTGS);
									$query3->where('st.closed_flag', 1);
								});
							} else {
								$query2->orWhere('st.status_id', $status_ids[$i]);
							}
						}
					}
				});
			}

			if (!empty($priorities) && count($priorities) > 0)
				$query->whereIn('st.priority', $priorities);

			if (!empty($department_ids) && count($department_ids) > 0 && $dept_id == 0)
				$query->whereIn('st.department_id', $department_ids);

			if (!empty($type_ids) && count($type_ids) > 0)
				$query->whereIn('st.type', $type_ids);

			$data_query = clone $query;

			if (!empty($start_date))
				$start_date = $start_date . ' 00:00:00';

			if (!empty($end_date))
				$end_date = $end_date . ' 23:59:59';
		} else {
			$data_query = clone $query;

			$where1 = sprintf(
				"df.`function` like '%%%s%%' or
								st.start_date_time like '%%%s%%' or
								CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%%%s%%' or
								CONCAT_WS(\" \", cu1.first_name, cu1.last_name) like '%%%s%%' or
								CONCAT_WS(\" \", cu2.first_name, cu2.last_name) like '%%%s%%' or
								cr.room like '%%%s%%' or
								st.start_date_time like '%%%s%%' or
								tl.task like '%%%s%%' or
								sc.complaint like '%%%s%%' or
								st.quantity like '%%%s%%' or
								cg.guest_name like '%%%s%%' or
								sp.priority like '%%%s%%' or
								cd.department like '%%%s%%' or
								slt.type like '%%%s%%' or
								st.id like '%%%s%%'",
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption,
				$searchoption
			);
		}


		$data_query->whereRaw("(" . $where . ")");
		if (!empty($where1)) {
			$data_query->whereRaw("(" . $where1 . ")");
		}

		$ticketquery = clone $data_query;
		$ticketlist = $ticketquery
			->orderBy($orderby, $sort)
			->select(DB::raw('st.*, df.function, df.gs_device, sp.priority as priority_name, cu.username, 
					CONCAT_WS(" ", cu1.first_name, cu1.last_name) as attendant_name, cr.room, tl.task as task_name, tl.lang, tl.type as task_type, 
					sc.complaint, ct.type as ct_type, scom.compensation, scom.cost, cd.department, cd.short_code as dept_short_code, 
					CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cg.guest_name,
					cu.mobile as device, CONCAT_WS(" ", cu2.first_name, cu2.last_name) as manage_user_name, 
					cu2.mobile as manage_user_mobile, cug.name as manage_user_group, ta_st.elaspse_time,
					ta_st.start_time as evt_start_time, ta_st.end_time as evt_end_time, 1 as cancel_enable,
					sl.name as lgm_name, slt.type as lgm_type,
					(CASE WHEN st.type = 1 THEN CONCAT_WS("", "G", st.id) ELSE (CASE WHEN st.type = 2 THEN CONCAT_WS("", "D", st.id) ELSE (CASE WHEN st.type = 3 THEN CONCAT_WS("", "C", st.id) ELSE (CASE WHEN st.type = 4 THEN CONCAT_WS("", "M", st.id) ELSE (CASE WHEN st.type = 5 THEN CONCAT_WS("", "R", st.id) ELSE 0 END) END) END) END) END) AS typenum, 
					sd.number'))
			->distinct('st.id')
			->skip($skip)->take($pageSize)
			->get();

		foreach ($ticketlist as  $key => $row) {
			if (($row->gs_device) == 1)
				$row->device = $row->number;
			if ($lang != 0 && $row->lang) {
				$languages = json_decode($row->lang);
				foreach ($languages as $key_l => $val_l) {
					if (($val_l->id == $lang) && $val_l->text) {
						$row->task_name = $val_l->text;
					}
				}
			}
		}

		for ($i = 0; $i < count($ticketlist); $i++) {
			// get task group information
			$task_group = DB::table('services_task_group_members as tgm')
				->join('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
				->where('tgm.task_list_id', $ticketlist[$i]->task_list)
				->select(DB::raw('tg.*'))
				->first();

			$ticketlist[$i]->cur_time = $cur_time;

			$ticketlist[$i]->comment_flag = 0;
			if (!empty($task_group)) {
				$ticketlist[$i]->comment_flag = $task_group->comment_flag;
				$ticketlist[$i]->unassigne_flag = $task_group->unassigne_flag;
			} else {
				$ticketlist[$i]->unassigne_flag = 0;
			}
		}

		$count_query = clone $data_query;
		$totalcount = $count_query->whereRaw($where)->count();

		$ret['ticketlist'] = $ticketlist;
		$ret['totalcount'] = $totalcount;
		$ret['where'] = $where;
		$ret['dept_id'] = $dept_id;

		$end = microtime(true);
		$ret['time'] = $end - $start;

		return Response::json($ret);
	}

	//store guestservice profile for ticketlist of every user
	public function storeTaskListProfile(Request $request)
	{

		$user_id = $request->get('user_id', 0);
		$ticket = $request->get('ticket', '');
		$start_date = $request->get('start_date', '0000-00-00');
		$end_date = $request->get('end_date', '0000-00-00');
		$status_id = $request->get('status_id', '[]');
		$priority = $request->get('priority', '[]');
		$department_id = $request->get('department_id', '[]');
		$type_id = $request->get('type_id', '[]');

		if ($ticket != '') {
			$profile = DB::table('services_task_profile')
				->where('user_id', $user_id)
				->get();
			if (!empty($profile)) {
				DB::table('services_task_profile')
					->where('user_id', $user_id)
					->update([
						'user_id' => $user_id,
						'ticket' => $ticket,
						'start_date' => $start_date,
						'end_date' => $end_date,
						'status_id' => $status_id,
						'priority' => $priority,
						'department_id' => $department_id,
						'type_id' => $type_id
					]);
			} else {
				DB::table('services_task_profile')
					->insert([
						'user_id' => $user_id,
						'ticket' => $ticket,
						'start_date' => $start_date,
						'end_date' => $end_date,
						'status_id' => $status_id,
						'priority' => $priority,
						'department_id' => $department_id,
						'type_id' => $type_id
					]);
			}
		}
		//after save reload
		$profile = DB::table('services_task_profile')
			->where('user_id', $user_id)
			->get();
		return Response::json($profile);
	}

	public function getMaxTicketNumber(Request $request)
	{
		$max_id = DB::table('services_task')->max('id');

		$ret = array();
		$ret['max_ticket_no'] = $max_id;

		return Response::json($ret);
	}

	public function getGuestName(Request $request)
	{
		$room_id = $request->get('room_id', '1001');

		$guest = DB::table('common_guest as cg')
			->where('cg.room_id', $room_id)
			->orderBy('cg.id', 'desc')
			->orderBy('cg.arrival', 'desc')
			//->where('cg.checkout_flag', 'checkin')
			->select(['cg.*'])
			->first();


		return Response::json($guest);
	}

	public function getQuickTaskList(Request $request)
	{
		$user_id = $request->get('user_id', 0);
		$type = $request->get('type', 1);
		$property_id = $request->get('property_id', 1);
		$user = CommonUser::find($user_id);
		$lang = $user->lang_id;
		$ret = array();

		$query = DB::table('services_task as st')
			->join('services_task_list as stl', 'st.task_list', '=', 'stl.id')
			->where('st.type', $type)
			->where('st.property_id', $property_id)
			->where('stl.type', '<', 100);

		$tasklist = $query->groupBy('st.task_list')
			->orderBy(DB::raw('count(*)'), 'desc')
			->select(DB::raw('stl.*'))
			->take(10)
			->get();

		foreach ($tasklist as  $key => $row) {
			if ($lang != 0 && $row->lang) {
				$languages = json_decode($row->lang);
				foreach ($languages as $key_l => $val_l) {
					if (($val_l->id == $lang) && $val_l->text) {
						$row->task = $val_l->text;
					}
				}
			}
		}

		$ret = array_merge($ret, $tasklist->toArray());

		$count = count($ret);
		if ($count >= 10)
			return Response::json($ret);

		return Response::json($ret);
	}

	public function getMainTaskList(Request $request)
	{
		$type = $request->get('type', 1);
		$property_id = $request->get('property_id', 1);

		$ret = DB::table('services_task_main')
			->get();

		return Response::json($ret);
	}

	public function getLocationGroup(Request $request)
	{
		// select room list with property id
		$room_id = $request->get('room_id', 0);
		$ret = $this->getLocationGroupIDFromRoom($room_id);
		if (empty($ret))
			return Response::json(array());
		else
			return Response::json($ret);
	}

	private function getLocationGroupIDFromRoom($room_id)
	{
		$location_info = Location::getLocationFromRoom($room_id);

		return $location_info;
	}

	public function getGuestHistoryList(Request $request)
	{
		$guest_id = $request->get('guest_id', 0);
		$ret = array();

		$histlist = DB::table('services_task as st')
			->leftJoin('common_guest as gu', 'st.guest_id', '=', 'gu.guest_id')
			->leftJoin('common_users as cu', 'st.dispatcher', '=', 'cu.id')
			->leftJoin('common_users as cu1', 'st.attendant', '=', 'cu1.id')
			->leftJoin('services_task_list as stl', 'stl.id', '=', 'st.task_list')
			->leftJoin('services_priority as sp', 'st.priority', '=', 'sp.id')
			->leftJoin('services_location as sl', 'st.location_id', '=', 'sl.id')
			->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id')
			->leftJoin('common_guest as cg', function ($join) {
				$join->on('st.guest_id', '=', 'cg.guest_id');
				$join->on('st.property_id', '=', 'cg.property_id');
			})
			->where('gu.guest_id', $guest_id)
			->whereIn('st.type', array(1, 3))
			->select(DB::raw('st.*, 
				sl.name as lgm_name, slt.type as lgm_type, 
				CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cg.guest_name,
				CONCAT_WS(" ", cu1.first_name, cu1.last_name) as attendant_name,
				cd.short_code as dept_short_code,
				sp.priority as priority_name, stl.task as task_name'))
			->orderBy('st.id', 'desc')
			->get();

		$ret['datalist'] = $histlist;
		return Response::json($ret);
	}

	public function getTaskInfo(Request $request)
	{
		$task_id = $request->get('task_id', '0');
		$location_id = $request->get('location_id', '0');
		$ret = $this->getTaskShiftInfo($task_id, $location_id);

		return Response::json($ret);
	}

	private function getTaskShiftInfo($task_id, $location_id)
	{
		// find department function
		$ret = array();
		$model = TaskList::find($task_id);
		$taskgroup = $model->taskgroup;
		if (empty($taskgroup) || count($taskgroup) < 1) {
			$ret['code'] = 201;
			$ret['message'] = 'No task group.';
			return $ret;
		}

		$task = $taskgroup[0];
		return $this->getTaskShiftInfoData($task_id, $task, $location_id);
	}

	private function getTaskShiftInfoData($task_id, $taskgroup, $location)
	{
		$ret = array();

		// // find department function
		// $model = TaskList::find($task_id);

		$task = $taskgroup;
		$ret['taskgroup'] = $task;

		$dept_func_id = $task->dept_function;

		$dept_func = DeftFunction::find($dept_func_id);
		if (empty($dept_func))
			return $ret;

		// find building id
		$building_id = Location::find($location)->building_id;

		// find job role for level = 0
		$escalation = Escalation::where('escalation_group', $dept_func_id)
			->where('level', 0)
			->first();

		$job_role_id = 0;

		if (!empty($escalation))
			$job_role_id = $escalation->job_role_id;

		$ret['deptfunc'] = $dept_func;

		// find department and property
		$department = Department::find($dept_func->dept_id);
		$ret['department'] = $department;

		date_default_timezone_set(config('app.timezone'));
		$datetime = date('Y-m-d H:i:s');

		$shift_group_members = array();

		// find staff list
		if ($taskgroup->reassign_flag == 1 && $taskgroup->reassign_job_role != '') {
			$shift_arr = explode(",", $taskgroup->reassign_job_role);
			foreach ($shift_arr as  $value) {
				// $shift_group_member = ShiftGroupMember::getUserlistOnCurrentShift($department->property_id, $value, $dept_func->dept_id, 0, 0, $location_group_id, $task->id, true, false);
				$shift_group_member = ShiftUser::getUserlistOnCurrentShift($value, $dept_func_id, $taskgroup->id, $location, $building_id, true);
				foreach ($shift_group_member as $row)
					$shift_group_members[] = $row;
			}
			$ret['sels'] = $shift_group_members;
		} else {
			$ret['sels'] = '1';

			$setting = DeftFunction::getGSDeviceSetting($department->id, $dept_func_id);

			if ($setting == 0)	// User Based
			{
				// find staff list
				// $shift_group_members = ShiftGroupMember::getUserlistOnCurrentShift($department->property_id, $job_role_id, $dept_func->dept_id, 0, 0, $location_group_id, $task->id, true, false);
				$shift_group_members = ShiftUser::getUserlistOnCurrentShift($job_role_id, $dept_func_id, $taskgroup->id, $location, $building_id, true);
			} else if ($setting == 1) {
				// $shift_group_members = $this->getUserListDeviceBasedDeptFunc($location_group_id,$location, $department->property_id, $job_role_id, $dept_func);
				$shift_group_members = ShiftUser::getDevicelistOnCurrentShift(0, $dept_func_id, $location, $building_id, true);

				// Log::info(json_encode($shift_group_members));
			} else if ($setting == 2) {
				$loc_type = LocationType::createOrFind('Room');
				$location_room = Location::where('id', $location)
					->where('type_id', $loc_type->id)
					->select('room_id')
					->first();
				if (!empty($location_room))
					$shift_group_members = RosterList::getRosterListFromRoomDeptFunc($dept_func->id, $location_room->room_id);

				Log::info(json_encode($shift_group_members));
			}

			$ret['setting'] = $setting;
		}

		if ($task->unassigne_flag == 1)
			$shift_group_members = [];

		// sort active staff by complete time
		$time = array();
		foreach ($shift_group_members as $key => $row) {
			// calculate max complete time for each staff
			$assigned_flag = Task::whereRaw('DATE(start_date_time) = CURDATE()')
				->where('dispatcher', $row->user_id)
				->where(function ($query) use ($datetime, $task) {
					$query->whereIn('status_id', array(OPENGS, ESCALATEDGS))
						->orWhere(function ($subquery) use ($datetime, $task) {	// vacation period
							$subquery->where('status_id', SCHEDULEDGS)
								->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) < $task->max_time");
						});
				})
				->exists();

			if ($assigned_flag == false)	// free staff
			{
				// calcuate spent time for free staff
				$spent = DB::table('services_task')
					->whereRaw('DATE(start_date_time) = CURDATE()')
					->where('dispatcher', $row->user_id)
					->where(function ($query) use ($datetime, $task) {
						$query->whereIn('status_id', array(COMPLETEDGS, TIMEOUTGS, CANCELEDGS))
							->orWhere(function ($subquery) use ($datetime, $task) {	// vacation period
								$subquery->where('status_id', SCHEDULEDGS)
									->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) >= $task->max_time");
							});
					})
					->select(DB::raw('sum(duration) AS spent'))
					->first();

				if (empty($spent))
					$difftime = 0;
				else
					$difftime = $spent->spent;

				$time[$key] = $difftime;
				$shift_group_members[$key]->spent = $difftime;
				$shift_group_members[$key]->assigned = false;
			} else	// active staff
			{
				// calcuate max complete time for active staff
				$completetime = DB::table('services_task')
					->whereRaw('DATE(start_date_time) = CURDATE()')
					->where('dispatcher', $row->user_id)
					->where(function ($query) use ($datetime, $task) {
						$query->whereIn('status_id', array(OPENGS, ESCALATEDGS))
							->orWhere(function ($subquery) use ($datetime, $task) {	// vacation period
								$subquery->where('status_id', SCHEDULEDGS)
									->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) < $task->max_time");
							});
					})
					->orderBy('complete', 'desc')
					->select(DB::raw('max(TIME_TO_SEC(start_date_time) + max_time) AS complete'))
					->first();

				$time[$key] = $completetime->complete + 60 * 24 * 265;	// 1 year +
				$shift_group_members[$key]->spent = $time[$key];
				$shift_group_members[$key]->assigned = true;
			}
		}

		// Change by tejas
		// array_multisort($time, SORT_ASC, $shift_group_members);
		if (is_array($shift_group_members)) {
			array_multisort($time, SORT_ASC, $shift_group_members);
		} else {
			array_multisort($time, SORT_ASC, $shift_group_members->toArray());
		}

		$ret['staff_list'] = $shift_group_members;

		$ret['check'] = $taskgroup->reassign_flag . ' ' . $taskgroup->reassign_job_role;

		$prioritylist = Priority::all();
		$ret['prioritylist'] = $prioritylist;
		$ret['shift_group_members'] = $shift_group_members;
		$ret['code'] = 200;
		return $ret;
	}

	public function getGuestData(Request $request)
	{
		$room_ids = $request->get('room_ids', []);

		$guests_checkin = [];
		$guests_checkout = [];

		foreach ($room_ids as $room_id) {
			$guest = DB::table('common_guest')
				->where('room_id', $room_id)
				->where('guest_id', '>', 0)
				->orderBy('id', 'desc')
				->orderBy('arrival', 'desc')
				->first();

			if (empty($guest)) {
				continue;
			}
			// get location group
			$guest->location_group = $this->getLocationGroupIDFromRoom($room_id);

			if (!empty($guest)) {
				if ($guest->checkout_flag === 'checkin') {
					$guests_checkin[] = $guest;
				}

				if ($guest->checkout_flag === 'checkout') {
					$guests_checkout[] = $guest;
				}
			}
		}

		$ret = [
			'guests_checkin' => $guests_checkin,
			'guests_checkout' => $guests_checkout
		];

		return Response::json($ret);
	}

	public function getGuestPrevHistoryList(Request $request)
	{
		$guest_ids = $request->get('guest_ids', []);

		$ret = [];

		foreach ($guest_ids as $guest_id) {
			$histlist = DB::table('services_task as st')
				->leftJoin('common_guest as gu', 'st.guest_id', '=', 'gu.guest_id')
				->leftJoin('common_users as cu', 'st.dispatcher', '=', 'cu.id')
				->leftJoin('common_users as cu1', 'st.attendant', '=', 'cu1.id')
				->leftJoin('services_task_list as stl', 'stl.id', '=', 'st.task_list')
				->leftJoin('services_priority as sp', 'st.priority', '=', 'sp.id')
				->leftJoin('services_location as sl', 'st.location_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
				->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id')
				->leftJoin('common_guest as cg', function ($join) {
					$join->on('st.guest_id', '=', 'cg.guest_id');
					$join->on('st.property_id', '=', 'cg.property_id');
				})
				->where('gu.guest_id', $guest_id)
				->whereIn('st.type', array(1, 3))
				->select(DB::raw('st.*, 
				sl.name as lgm_name, slt.type as lgm_type, 
				CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cg.guest_name,
				CONCAT_WS(" ", cu1.first_name, cu1.last_name) as attendant_name,
				cd.short_code as dept_short_code,
				sp.priority as priority_name, stl.task as task_name'))
				->orderBy('st.id', 'desc')
				->get();

			$ret[$guest_id] = $histlist;
		}

		return Response::json($ret);
	}

	public function getTaskInfoFromTask(Request $request)
	{
		$task_id = $request->get('task_id', '0');
		$location_groups = $request->get('location_groups', []);

		// find department function
		$ret = [];
		$model = TaskList::find($task_id);
		$taskgroup = $model->taskgroup;

		if (empty($taskgroup) || count($taskgroup) < 1) {
			$ret['code'] = 201;
			$ret['message'] = 'No task group.';
			return $ret;
		}

		$task = $taskgroup[0];

		$ret['taskgroup'] = $task;

		$dept_func_id = $task->dept_function;

		$dept_func = DeftFunction::find($dept_func_id);
		if (empty($dept_func))
			return $ret;

		$ret['deptfunc'] = $dept_func;

		// find department and property
		$department = Department::find($dept_func->dept_id);
		$ret['department'] = $department;

		date_default_timezone_set(config('app.timezone'));
		$datetime = date('Y-m-d H:i:s');

		$ret['check'] = $task->reassign_flag . ' ' . $task->reassign_job_role;

		$prioritylist = Priority::all();
		$ret['prioritylist'] = $prioritylist;

		// find job role for level = 0
		$escalation = Escalation::where('escalation_group', $dept_func_id)
			->where('level', 0)
			->first();

		$job_role_id = 0;

		if (!empty($escalation))
			$job_role_id = $escalation->job_role_id;

		$ret['location_groups'] = [];

		foreach ($location_groups as $locationKey => $location_group) {
			// find building id

			$tempLocationGroups = [];
			$location_id = $location_group['location_id'];
			$building_id = Location::find($location_id)->building_id;

			$shift_group_members = [];

			// find staff list
			if ($task->reassign_flag == 1 && $task->reassign_job_role != '') {
				$shift_arr = explode(",", $task->reassign_job_role);
				foreach ($shift_arr as  $value) {
					// $shift_group_member = ShiftGroupMember::getUserlistOnCurrentShift($department->property_id, $value, $dept_func->dept_id, 0, 0, $location_group_id, $task->id, true, false);
					$shift_group_member = ShiftUser::getUserlistOnCurrentShift($value, $dept_func_id, $task->id, $location_id, $building_id, true);
					foreach ($shift_group_member as $row)
						$shift_group_members[] = $row;
				}
				$tempLocationGroups['sels'] = $shift_group_members;
			} else {
				$tempLocationGroups['sels'] = '1';

				$setting = DeftFunction::getGSDeviceSetting($department->id, $dept_func_id);

				if ($setting == 0)	// User Based
				{
					// find staff list
					// $shift_group_members = ShiftGroupMember::getUserlistOnCurrentShift($department->property_id, $job_role_id, $dept_func->dept_id, 0, 0, $location_group_id, $task->id, true, false);
					$shift_group_members = ShiftUser::getUserlistOnCurrentShift($job_role_id, $dept_func_id, $task->id, $location_id, $building_id, true);
				} else if ($setting == 1) {
					// $shift_group_members = $this->getUserListDeviceBasedDeptFunc($location_group_id,$location, $department->property_id, $job_role_id, $dept_func);
					$shift_group_members = ShiftUser::getDevicelistOnCurrentShift(0, $dept_func_id, $location_id, $building_id, true);

					// Log::info(json_encode($shift_group_members));
				} else if ($setting == 2) {
					$loc_type = LocationType::createOrFind('Room');
					$location_room = Location::where('id', $location_id)
						->where('type_id', $loc_type->id)
						->select('room_id')
						->first();
					if (!empty($location_room))
						$shift_group_members = RosterList::getRosterListFromRoomDeptFunc($dept_func->id, $location_room->room_id);

					Log::info(json_encode($shift_group_members));
				}

				$ret['setting'] = $setting;
			}

			if ($task->unassigne_flag == 1)
				$shift_group_members = [];

			// sort active staff by complete time
			$time = array();
			foreach ($shift_group_members as $key => $row) {
				// calculate max complete time for each staff
				$assigned_flag = Task::whereRaw('DATE(start_date_time) = CURDATE()')
					->where('dispatcher', $row->user_id)
					->where(function ($query) use ($datetime, $task) {
						$query->whereIn('status_id', array(OPENGS, ESCALATEDGS))
							->orWhere(function ($subquery) use ($datetime, $task) {	// vacation period
								$subquery->where('status_id', SCHEDULEDGS)
									->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) < $task->max_time");
							});
					})
					->exists();

				if ($assigned_flag == false)	// free staff
				{
					// calcuate spent time for free staff
					$spent = DB::table('services_task')
						->whereRaw('DATE(start_date_time) = CURDATE()')
						->where('dispatcher', $row->user_id)
						->where(function ($query) use ($datetime, $task) {
							$query->whereIn('status_id', array(COMPLETEDGS, TIMEOUTGS, CANCELEDGS))
								->orWhere(function ($subquery) use ($datetime, $task) {	// vacation period
									$subquery->where('status_id', SCHEDULEDGS)
										->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) >= $task->max_time");
								});
						})
						->select(DB::raw('sum(duration) AS spent'))
						->first();

					if (empty($spent))
						$difftime = 0;
					else
						$difftime = $spent->spent;

					$time[$key] = $difftime;
					$shift_group_members[$key]->spent = $difftime;
					$shift_group_members[$key]->assigned = false;
				} else	// active staff
				{
					// calcuate max complete time for active staff
					$completetime = DB::table('services_task')
						->whereRaw('DATE(start_date_time) = CURDATE()')
						->where('dispatcher', $row->user_id)
						->where(function ($query) use ($datetime, $task) {
							$query->whereIn('status_id', array(OPENGS, ESCALATEDGS))
								->orWhere(function ($subquery) use ($datetime, $task) {	// vacation period
									$subquery->where('status_id', SCHEDULEDGS)
										->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) < $task->max_time");
								});
						})
						->orderBy('complete', 'desc')
						->select(DB::raw('max(TIME_TO_SEC(start_date_time) + max_time) AS complete'))
						->first();

					$time[$key] = $completetime->complete + 60 * 24 * 265;	// 1 year +
					$shift_group_members[$key]->spent = $time[$key];
					$shift_group_members[$key]->assigned = true;
				}
			}

			if (is_array($shift_group_members)) {
				array_multisort($time, SORT_ASC, $shift_group_members);
			} else {
				array_multisort($time, SORT_ASC, $shift_group_members->toArray());
			}

			$tempLocationGroups['staff_list'] = $shift_group_members;
			$tempLocationGroups['shift_group_members'] = $shift_group_members;

			if (!empty($location_group['guest_id'])) {
				$tempLocationGroups['guest_id'] = $location_group['guest_id'];
			}

			$tempLocationGroups['location_id'] = $location_id;
			if (!empty($location_group['room_name'])) {
				$tempLocationGroups['room_name'] = $location_group['room_name'];
			}

			if (!empty($location_group['room_id'])) {
				$tempLocationGroups['room_id'] = $location_group['room_id'];
			}

			if (!empty($location_group['location_type'])) {
				$tempLocationGroups['location_type'] = $location_group['location_type'];
			}

			$ret['location_groups'][] = $tempLocationGroups;
		}

		$ret['code'] = 200;

		return Response::json($ret);
	}
	// ------------------------ create request ----------------------------------------------------------
	public function createTaskListNew(Request $request)
	{
		$user_id = $request->get('user_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$input = $request->all();

		$noassigned_flag = false;

		$ticket_ids = [];
		$ticket_number_id = [];
		$ret = array();

		$task_group_ids = array();

		$ret['message'] = '';
		$invalid_task_list = array();

		foreach ($input as $key => $row) {

			// re calc max duration for ending xx:xx:00
			$row['created_time'] = $cur_time;
			if ($row['status_id'] != 5) {
				$row['start_date_time'] = $cur_time;
			}

			$max_time = Functions::calcDurationForMinute($row['start_date_time'], $row['max_time']);

			// duration based on priority
			$setting['services_request_moderate_time'] = 0;
			$setting['services_request_high_time'] = 0;
			$setting['hskp_gs_rush_task'] = 0;

			$setting = PropertySetting::getPropertySettings($row['property_id'], $setting);

			if ($row['priority'] == 3) {
				if (!empty($setting['services_request_high_time'])) {
					$max_time1 = $max_time - (($max_time * $setting['services_request_high_time']) / 100);
					$row['max_time'] = $max_time1;
				}
			} elseif ($row['priority'] == 2) {
				if (!empty($setting['services_request_moderate_time'])) {
					$max_time1 = $max_time - (($max_time * $setting['services_request_moderate_time']) / 100);
					$row['max_time'] = $max_time1;
				}
			} else {
				$row['max_time'] = $max_time;
			}
			/*
				if($row['type'] == 2)
				{
					$guest = DB::table('common_guest as cg')
						->where('cg.room_id', $row['room'])
						->orderBy('cg.departure', 'desc')
						->orderBy('cg.arrival', 'desc')
						->where('cg.checkout_flag', 'checkin')
						->select(['cg.*'])
						->first();
					if( empty($guest) )
					{
						$row['guest_id'] = 0;
					
					}
					else
						$row['guest_id'] = $guest->guest_id;
				}
			*/
			$info_list = $row['info_list'];

			foreach ($info_list as $info) {

				foreach ($info as $infoKey => $infoItem) {
					$row[$infoKey] = $infoItem;
				}

				if ($row['type'] == 2) {
					$guest = DB::table('common_guest as cg')
						->where('cg.room_id', $info['room'])
						->orderBy('cg.departure', 'desc')
						->orderBy('cg.arrival', 'desc')
						->where('cg.checkout_flag', 'checkin')
						->select(['cg.*'])
						->first();
					if (empty($guest)) {
						$row['guest_id'] = 0;
					} else
						$row['guest_id'] = $guest->guest_id;
				}

				unset($row['info_list']);

				if ($row['dispatcher'] < 1 && $row['status_id'] != SCHEDULEDGS) {
					// check unassigned task
					$model = TaskList::find($row['task_list']);
					$taskgroup = $model->taskgroup;
					if (empty($taskgroup) || count($taskgroup) < 1 || $taskgroup[0]->unassigne_flag == 0) {
						$noassigned_flag = true;
						$row['status_id'] = OPENGS;
					} else {
						$row['status_id'] = UNASSIGNEDGS;
						// $max_time = Functions::calcDurationForMinute($row['start_date_time'], $taskgroup[0]->start_duration);
						// $row['max_time'] = $max_time;
					}
				}

				if (($row['status_id'] == OPENGS || $row['status_id'] == UNASSIGNEDGS) && $this->isValidTicket($row, $prev) == false) {
					$prev->type = $row['type'];
					$invalid_task_list[] = $prev;
					continue;
				}

				// check running ticket.
				$last_task = null;
				if ($row['dispatcher'] > 0 && $row['status_id'] != SCHEDULEDGS && $row['status_id'] == OPENGS) {
					if ($this->isQueuableTask($row['task_list']))	// check queuable task
						$last_task = $this->getRunningTicket($row['property_id'], $row['dispatcher'], 0, $row['location_id']);
				}

				if (!empty($last_task)) {
					$row['running'] = 0;
					$row['queued_flag'] = 1;
				}

				if ($row['attendant'] == 0) {
					$row['attendant_auto'] = 'Guest Chat';
				}

				$task_info = DB::table('services_task_list as tl')
					->join('services_task_group_members as tgm', 'tl.id', '=', 'tgm.task_list_id')
					->join('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
					->leftJoin('services_task_category as tc', 'tl.category_id', '=', 'tc.id')
					->select(DB::raw('tl.id as task_id, tl.task, tc.name as category_name, tg.reassign_flag, tg.reassign_job_role, tg.dept_function as escalation_group, tg.id as tg_id, tg.dept_function, tg.frequency_notification_flag, tg.frequency_job_role_ids, tg.frequency, tg.period'))
					->where('tl.id', $row['task_list'])
					->first();

				$all_dept_setting = DeftFunction::where('dept_id', $row['department_id'])
					->where('id', $row['dept_func'])
					->first();
				if ((($task_info->reassign_flag == 1) && ($task_info->reassign_job_role != '')) || (!empty($all_dept_setting) && ($all_dept_setting->all_dept_setting == 1))) {
					$row['reassigned_flag'] = 1;
				}

				//$ret['test']=$all_dept_setting->all_dept_setting;
				if($row["custom_message"] === null) $row["custom_message"] = "";
				if($row["feedback_flag"] === null) $row["feedback_flag"] = "";
				$id = DB::table('services_task')->insertGetId($row);

				if ($row['dispatcher'] < 1 && $row['status_id'] != SCHEDULEDGS) {
					$this->sendNoStaffMail($id, $task_info);
				}
				if (!empty($setting['hskp_gs_rush_task']) && ($setting['hskp_gs_rush_task'] == $row['task_list'])) {
					$this->createHSKPTask($row);
				}

				$ticket_ids[] = $id;

				$ticket_number_id[] = array('num' => $key, 'id' => $id);

				$task_group_ids[] = $this->checkFrequency($task_info, $row['location_id'], $row['property_id']);
			}
		}

		$max_id = DB::table('services_task')->max('id');

		$this->createTaskState($ticket_ids, 0, $user_id);
		$this->createSystemTask($ticket_ids);

		if ($noassigned_flag == true)		// no assigned staff
			$this->checkGuestDepartmentTaskState();	// occur escalation

		$ret['max_ticket_no'] = $max_id;
		$ret['count'] = count($ticket_ids);
		$ret['input'] = json_encode($input);
		$ret['task_group_ids'] = $task_group_ids;
		$ret['ticket_number_id'] = $ticket_number_id;

		$ret['invalid_task_list'] = $this->getPrevTaskStateMessage($invalid_task_list);

		return Response::json($ret);
	}

	private function isValidTicket($ticket, &$prev) 
	{
		$task_id = $ticket['task_list'];

		if($ticket['type'] == 1 || $ticket['type'] == 2  )	// guest request
		{
			$task = DB::table('services_task')
					->whereIn('type', array(1, 2))
					->where('location_id', $ticket['location_id'])	// same location
					->where('task_list', $task_id)	// same task
					->whereIn('status_id', array(OPENGS, ESCALATEDGS, UNASSIGNEDGS) )
					->where('queued_flag', 0)
					->first();
			if( !empty($task) )
			{
				$prev = $task;
				return false;
			}
		}

		return true;
	}

	public function isQueuableTask($task_list)
	{
		$model = TaskList::find($task_list);
		$taskgroups = $model->taskgroup;
		if( count($taskgroups) < 1 )
			return false;

		$taskgroup = $taskgroups[0];
		if( $taskgroup->queue_flag == 1 )
			return true;

		return false;
	}

	private function getRunningTicket($property_id, $dispatcher, $task_id, $location_id) 
	{
		$istaskqueued = PropertySetting::isGuestTaskQueued($property_id);
		if( $istaskqueued == 0 )
			return null;

		date_default_timezone_set(config('app.timezone'));
		$last24 = date('Y-m-d H:i:s', strtotime(' -1 day'));

		$last_task = Task::where('dispatcher', $dispatcher)	// same dispatcher
			->whereIn('status_id', array(OPENGS, ESCALATEDGS) )
			->where('running', 1)
			->where('location_id', '!=', $location_id)
			->where('id', '!=', $task_id)
			->where('start_date_time', '>', $last24)
			->first();

		return $last_task;
	}

	private function sendNoStaffMail($task_id,$task_info)
	{
		$cur_time = date("Y-m-d H:i:s");
		$task = Task::find($task_id);
		if(!empty($task_info->tg_id))
		{
		//	$escalation_list = Escalation::where('escalation_group', $task_info->tg_id)
		//		->where('level', '>', 0)->orderBy('level')->get();

		//	$escalation_count = Escalation::where('escalation_group', $task_info->tg_id)
		//		->where('level', '>', 0)->count();

			$escalation_list = Escalation::where('escalation_group', $task_info->escalation_group)
				->where('level', '>', 0)->orderBy('level')->get();

			$escalation_count = Escalation::where('escalation_group', $task_info->escalation_group)
				->where('level', '>', 0)->count();

			// find escalation staff_id
			$assigned_id = -1;
			for($j = 0; $j < count($escalation_list); $j++)
			{
				$escalation = $escalation_list[$j];

				$user_list = $this->getUserListForTicket($task, $escalation->job_role_id, false);

				if( count($user_list) < 1 )
					continue;

				foreach($user_list as $row) { // find duty user
					$user = $row;
					$assigned_id = $row->id;
					break;
				}

				if( $assigned_id >= 0 )
					break;
			}

			if( $assigned_id >= 0 && !empty($escalation) )		// escalated
			{

				$task_notify = $this->saveNotification($assigned_id, $task_id, 'No Staff');
				$user_id = $assigned_id;


				// save log

			}
			else {
				$department = Department::find($task->dept_id);
				if(!empty($department) && $department->default_assignee!=0)
				{
				$task_notify = $this->saveNotification($department->default_assignee, $task_id, 'No Staff');
				$user_id = $department->default_assignee;
				}

			}

		}
		else {
				$department = Department::find($task->dept_id);
				if(!empty($department) && $department->default_assignee!=0)
				$task_notify = $this->saveNotification($department->default_assignee, $task_id, 'No Staff');
				$user_id = $department->default_assignee;
		}
			$log_type = 'Notification';
			$status = 'No Staff Notify';
			if( !empty($task_notify) )
				{
					$method = $task_notify->mode;

					$task_log = new Tasklog();
					$task_log->task_id = $task_id;
					$task_log->user_id = $user_id;
					$task_log->comment = '';
					$task_log->log_type = $log_type;
					$task_log->log_time = $cur_time;
					$task_log->status = $status;
					$task_log->method = $method;
					$task_log->notify_id = $task_notify->id;

					$task_log->save();
				}
	}

	private function getUserListForTicket($task, $job_role_id, $active_check)
	{
		$ret = array();
		if (empty($task))
			return array();

		$model = TaskList::find($task->task_list);
		$taskgroup = $model->taskgroup;
		if( empty($taskgroup) || count($taskgroup) < 1 )
			return array();

		$task_group = $taskgroup[0];

		$dept_func_id = $task_group->dept_function;

		$dept_func = DeftFunction::find($dept_func_id);
		if( empty($dept_func) )
			return $ret;

		// $escalated_user_list = ShiftGroupMember::getUserlistforGuestserviceEscalation($task->property_id, $job_role_id, $dept_func->dept_id);
		// echo $job_role_id, ',', $dept_func_id, ',', $task_group->id, ',', $task->location_id, ',', $active_check;
		$escalated_user_list = ShiftUser::getUserlistOnCurrentShift($job_role_id, $dept_func_id, $task_group->id, $task->location_id, $active_check);

		return $escalated_user_list;
	}

	public function saveNotification($user_id, $task_id, $type)
	{
		if( !($user_id > 0) )
			return;

		$task = $this->getTaskDetail($task_id);
		$dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT_NOTIFY'));
		$dept_func_ids = Device::getSecDeptFunc($user_id);

		// if(!empty($dept_func_ids))
		// {
		// 	$sec_dept_func = '';
		// 	$dept_func_id=explode(',', $dept_func_ids);
		// 	foreach ($dept_func_id as  $value) {
		// 		$dept_func = DeftFunction::find($value);

		// 		if(!empty($dept_func)&&($dept_func->dept_id == $task->department_id))
		// 			$sec_dept_func=	$dept_func;
		// 	}
		// 	if(empty($sec_dept_func))
		// 		return;
		// }
		// if($dept_id != 0)
		// {
		// 	if($dept_id != $task->department_id)
		// 	return;
		// }

		$send_mode = $this->getNotificationMode($user_id);
		$task_notify = new TaskNotification();
		$task_notify->task_id = $task_id;
		$task_notify->user_id = $user_id;
		$task_notify->mode = $send_mode;


		$task_notify->notification = $this->getNotifySMSMessage($task, $user_id, $type,'',0,0,0);
		$task_notify->type = $type;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_notify->send_time = $cur_time;


		$task_user=TaskNotification::checkUserTask($task_id);
		$task_notify->checking= $task_user.'---'.$user_id;
		$task_notify->save();

		// if($task_user!=$user_id)
		{
			if( $task->status_id != COMPLETEDGS && $task->status_id != TIMEOUTGS || $type == 'Forward' )
			{
				$email_content = $this->getNotifyEmailMessage($task, $user_id, $type);

				$new_modes = $this->sendNotification($user_id, $task_id,$task->dept_func, $task->task_list, $type, $task_notify->notification, $email_content, $send_mode, $task_notify->id, 0,0);
				
				if($new_modes === "None")
					$task_notify->mode = $new_modes;
				else
					$task_notify->mode = implode(",", $new_modes);
			}
		}

		return $task_notify;
	}

	public function getTaskDetail($task_id) 
	{
		$task = DB::table('services_task as st')
				->leftJoin('services_dept_function as df', 'st.dept_func', '=', 'df.id')
				->leftJoin('services_type as ty', 'st.type', '=', 'ty.id')
				->leftJoin('services_priority as sp', 'st.priority', '=', 'sp.id')
				->leftJoin('common_users as cu', 'st.dispatcher', '=', 'cu.id')
				->leftJoin('common_users as cu1', 'st.attendant', '=', 'cu1.id')
				->leftJoin('common_job_role as job', 'job.id', '=', 'cu1.job_role_id')
				->leftJoin('common_room as cr', 'st.room', '=', 'cr.id')
				->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
				->leftJoin('services_complaints as sc', 'st.complaint_list', '=', 'sc.id')
				->leftJoin('services_complaint_type as ct', 'sc.type_id', '=', 'ct.id')
				->leftJoin('services_compensation as scom', 'st.compensation_id', '=', 'scom.id')
				->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id')
				//				->leftJoin('common_guest as cg', 'st.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('st.guest_id', '=', 'cg.guest_id');
					$join->on('st.property_id', '=', 'cg.property_id');
				})
				->leftJoin('common_users as cu2', 'st.user_id', '=', 'cu2.id')
				->leftJoin('common_user_group as cug', 'st.group_id', '=', 'cug.id')
				->leftJoin('services_location as sl', 'st.location_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
				->where('st.id', $task_id)
				->select(DB::raw('st.*, df.function, df.gs_device, sp.priority as priority_name, CONCAT_WS(" ", cu.first_name, cu.last_name) as staff_name, cu.username,job.job_role as jobrole_name,
				CONCAT_WS(" ", cu1.first_name, cu1.last_name) as attendant_name, cr.room, tl.task as task_name,
				sc.complaint, ct.type as ct_type, scom.compensation, scom.cost, cd.department, cg.guest_name,
				CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cu.mobile as device,sl.name as location_name, slt.type as location_type,
				CONCAT_WS(" ", cu2.first_name, cu2.last_name) as manage_user_name, cu2.mobile as manage_user_mobile,
				cug.name as manage_user_group, cg.vip, cg.arrival, cg.departure'))
 				->first();

		if( !empty($task) ) {
			if ($task->location_id > 0) {
				$info = $this->getLocationInfo($task->location_id);
				$task->lgm_name = '';
				$task->lgm_type ='';
				if (!empty($info)) {
					$task->lgm_name = $info->name;
					$task->lgm_type = $info->type;
				}
			}
		}

		return $task;
	}

	public function getNotificationMode($user_id)
	{
		date_default_timezone_set(config('app.timezone'));
		$dayofweek = date('w');
		$date = date('Y-m-d');
		$time = date('H:i:s');

		$daylist = [
				'0' => 'Sunday',
				'1' => 'Monday',
				'2' => 'Tuesday',
				'3' => 'Wednesday',
				'4' => 'Thursday',
				'5' => 'Friday',
				'6' => 'Saturday',
		];

		$day = $daylist[$dayofweek];

		$user = CommonUser::find($user_id);
		if( empty($user) )
			return 'None';

		// check user shift
		$userlist = ShiftGroupMember::getUserlistOnCurrentShift(0, 0, 0, $user->id, 0, 0, 0, true, false);

		$exist = true;
		if(empty($userlist) || count($userlist) < 1 )
			$exist = false;

		$send_mode = 'SMS';
		if( $exist )	// staff is ready for shift
		{
			$send_mode = $user->contact_pref_bus;
		}
		else
		{
			$send_mode = $user->contact_pref_nbus;
		}

		if(!empty($user->multimode_pref))
		{
			$send_mode = $send_mode.','.$user->multimode_pref;
		}

		return $send_mode;
	}

	public function getNotifySMSMessage($task, $user_id, $type,$username, $room,$guest_flag,$vip)
	{

		$notification = "Unknown";
		if( !empty($task) )
		{
			$prefix_id = ["G", "D", "C", "M", "R"];
			if($task->type == 1 || $task->type == 4 && $task->subtype == 1 )
			{
				$settings = array();
				$settings['butler_request'] = 0;
				$settings = PropertySetting::getPropertySettings($task->property_id, $settings);

				if($task->task_list==$settings['butler_request'])
				{
					$notification = sprintf("ID:". $prefix_id[$task->type - 1] . "%05d Room:%d Guest:%s %s VIP:%d Priority:%s ",
						$task->id, $task->room, $task->guest_name, $task->vip == 1 ? 'RM:VIP' : '', $task->vip,
						$task->priority_name);
				}
				else
				{
					$notification = sprintf("ID:". $prefix_id[$task->type - 1] . "%05d RN:%d GN:%s TS:%dx%s PR:%s %s VIP:%d, CM:%s",
										$task->id, $task->room, $task->guest_name, $task->quantity, $task->task_name,
										$task->priority_name, $task->vip == 1 ? 'RM:VIP' : '', $task->vip, empty($task->custom_message) ? '' : $task->custom_message);
				}

			}

			if($task->type == 2 || $task->type == 4 && $task->subtype == 2 )
			{
				$notification = sprintf("ID:". $prefix_id[$task->type - 1] . "%05d LN:%s - %s TS:%dx%s PR:%s %s VIP:%d, CM:%s",
						$task->id, $task->lgm_type, $task->lgm_name,  $task->quantity, $task->task_name,
						$task->priority_name, $task->vip == 1 ? 'RM:VIP' : '', $task->vip, empty($task->custom_message) ? '' : $task->custom_message);
			}

			if( $task->status_id == ESCALATEDGS && $type == 'Escalated')
			{
				$task_state = TaskState::where('task_id', $task->id)->first();
				if( !empty($task_state) )
					$notification = 'ESC' . $task_state->level . ' ' . $notification;
			}

			if( $task->status_id == CANCELEDGS )
			{
				$notification = 'CANCELLED ' . $notification;
			}

			if( $type == 'Extend')
			{
				$notification = 'EXTEND ' . $notification;
			}

			if( $type == 'Hold')
			{
				$notification = 'HOLD ' . $notification;
			}

			if( $type == 'Resume')
			{
				$notification = 'RESUME ' . $notification;
			}

			if( $type == 'Forward')
			{
				$notification = 'FORWARD ' . $notification;
			}

			if( $type == 'Timeout')
			{
				$notification = 'TIMEOUT ' . $notification;
			}

			if( $type == 'Timeout_No_Escalation')
			{
				$notification = 'TIMEOUT WITHOUT ESCALATION ' . $notification;
			}

			if( $type == 'Reassign')
			{
				$notification = 'REASSIGN ' . $notification;
			}
			if( $type == 'No Staff')
			{
				$notification = 'No Staff Assigned ' . $notification;
			}

			if( !empty($username))
			{
				$notification = 'ASSIGNED TO  '.$username.' - '. $notification;
			}
		}
		else if($type=='guest' && $guest_flag==1){

			if($vip!='0')
			{
				$vip_code=VIPCodes::getVIPname($vip);
				$notification = 'Guest  '. $username . ' has checked in to Room  '.$room.' VIP: '.$vip_code;
			}
			else
			$notification = 'Guest  '. $username . ' has checked in to Room  '.$room;
		}
		else if($type=='guest' && $guest_flag==2){

			if($vip!='0')

			{
				$vip_code=VIPCodes::getVIPname($vip);
				$notification = 'Guest '. $username. ' has checked out of Room '.$room.' VIP: '.$vip_code;
			}

			else
			$notification = 'Guest '. $username. ' has checked out of Room '.$room;
		}
		else if($type=='guest' && $guest_flag==3){
			$rooms=explode(',', $room);
				if($vip!='0')

				{
					$vip_code=VIPCodes::getVIPname($vip);
					$notification = 'Guest '. $username. ' has been moved from Room '.$rooms[1].' to Room '.$rooms[0].' VIP: '.$vip_code;
				}

				else
				$notification = 'Guest '. $username. ' has been moved from Room '.$rooms[1].' to Room '.$rooms[0];
		}
		else if($type=='guest' && $guest_flag==4){
			if($vip!='0')

			{
				$vip_code=VIPCodes::getVIPname($vip);
				$notification = 'No Post Enabled for Guest '. $username. ' from Room '.$room.' VIP: '.$vip_code;
			}

			else
			$notification = 'No Post Enabled for Guest '. $username. ' from Room '.$room;
		}


		return $notification;
	}

	public function getNotifyEmailMessage($task, $user_id, $type)
	{
		if( empty($task) )
			return;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date('H:i');

		$notification = "Unknown";
		$info = array();

		// get user name
		$info['first_name'] = 'Ennovatech';
		$info['last_name'] = 'Ennovatech';
		$user = CommonUser::find($user_id);
		if( !empty($user) )
		{
			$info['first_name'] = $user->first_name;
			$info['last_name'] = $user->last_name;
		}

		$info['type'] = $type;
		$info['request_time'] = $cur_time;
		$info['expected_time'] = sprintf("%2dmin %02ds", $task->max_time / 60, $task->max_time % 60);

		$cur_time = strtotime(date('Y-m-d H:i:s'));
		$start_time = strtotime($task->start_date_time);
		$elapsed_time = $cur_time - $start_time;

		$info['elapsed_time'] = sprintf("%2dmin %02ds", $elapsed_time / 60, $elapsed_time % 60);


		if( !empty($task) )
		{
			$prefix_id = ["G", "D", "C", "M", "R"];
			$tid = $task->id;
        	switch ($task->type) {
			case 1:
				$task->ticketno1 = sprintf("G%05d", $tid);
				break;
			case 2:
				$task->ticketno1 = sprintf("D%05d", $tid);
				break;
			case 3:
				$task->ticketno1 = sprintf("C%05d", $tid);
				break;
			case 4:
				$task->ticketno1 = sprintf("M%05d", $tid);
				break;
			case 5:
				$task->ticketno1 = sprintf("R%05d", $tid);
				break;
			}
			$info['arrival_time'] = date('d M Y', strtotime($task->arrival));
			if($task->departure == '0000-00-00'){
				$info['departure_time'] = "";
			}else{
				$info['departure_time'] = date('d M Y', strtotime($task->departure));
			}
			$info['duration_time'] = date('H:i:s', $task->duration);
			$status_name = ['Completed', 'Open', 'Escalated','Timeout', 'Canceled', 'Scheduled', 'Unassigned' ];
			$pause_name = ['Paused', '' ];
			$info['status_name'] = '';
			if( $task->type == 1 ||  $task->type == 2 )
			{
				if( $task->queued_flag == 1 && $task->status_id == 1 )
					$info['status_name'] = 'Queued';
				else
					$info['status_name'] =  $status_name[$task->status_id];
			}

			$status_name = ['Resolved', 'Open', 'Escalated','Timeout', 'Canceled', 'Scheduled' ];
			if( $task->type == 3 )
				$info['status_name'] = $status_name[$task->status_id];

			$status_name = ['Completed', 'Open', 'Escalated','Timeout', 'Canceled', 'Scheduled' ];
			if( $task->type == 4 )
				$info['status_name'] =  $status_name[$task->status_id];
			$info['status_color'] = '';
			switch ($task->status_id) {
				case 0:     // completed
					$info['status_color'] = '#4CAF50';
                break;
				case 1:     // open
					$info['status_color'] = '#4CAF50';
                break;
				case 2:     // escalatted
					$info['status_color'] = '#F44336';
                break;
				case 3:     // timeout
					$info['status_color'] = '#F44336';
                break;
				case 4:     // cancel
					$info['status_color'] = '#F44336';
                break;
				case 5:     // cancel
					$info['status_color'] = '#F44336';
                break;
				case 6:     // Assigned
					$info['status_color'] = '#673AB7';
                break;
				case 7:     // waiting escalated
					$info['status_color'] = '#673AB7';
                break;
				case 8:     // unattended
					$info['status_color'] = '#673AB7';
				case 10:     // unattended
					$info['status_color'] = '#673AB7';
                break;

			}
			if($task->type == 1 || $task->type == 4 && $task->subtype == 1 )	// Guest Request
			{
				$info['task'] = $task;
				if($type == 'Escalated'){
					$notification = view('emails.guestrequest_escalated_reminder', ['info' => $info])->render();
				}else{
					$notification = view('emails.guestrequest_reminder', ['info' => $info])->render();
					 }
				}

			if($task->type == 2 || $task->type == 4 && $task->subtype == 2 )
			{
				$info['task'] = $task;
				if($type == 'Escalated'){
										$notification = view('emails.guestrequest_escalated_reminder', ['info' => $info])->render();
									}else {
										$notification = view('emails.departmentrequest_reminder', ['info' => $info])->render();
									}
			}
		}

		return $notification;
	}

	public function sendNotification($user_id, $task_id,$dept_func, $task_list, $type, $message, $email_content, $send_mode, $notify_id, $guest_flag, $broadcast_flag)
	{
		$user = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.id', $user_id)
				->select(DB::raw('cu.*, cd.property_id'))
				->first();

		if (empty($user))
			return $send_mode;

		$smtp = Functions::getMailSetting($user->property_id, 'notification_');

		$payload = array();
		$payload['broadcast_flag']=$broadcast_flag;
		if($type=='guest')
		{
			$payload['guest_id'] = $task_id;
			$payload['table_id'] = $task_id;
			$payload['task_id'] = $task_id;
			$payload['table_name'] = 'guest';

			if($guest_flag==1)
				$payload['type'] = 'Guest Info';
			elseif($guest_flag==2)
				$payload['type'] = 'Guest Info';
			elseif($guest_flag==3)
			{
				$payload['type'] = 'Guest Info';
			}
			elseif($guest_flag==4)
			{
				$payload['type'] = 'Guest Info';
			}
		}
		else
		{
			$payload['task_id'] = $task_id;
			$payload['table_id'] = $task_id;
			$payload['table_name'] = 'services_task';

			$settings = array();
			$settings['butler_request'] = 0;
			$settings = PropertySetting::getPropertySettings($user->property_id, $settings);

			if($task_list==$settings['butler_request'])
				$payload['type'] = 'Butler Request';
			else if($type=='No Staff')
				$payload['type'] = 'No Staff Assigned';
			else if($type=='Reminder')
				$payload['type'] = 'Reminder';
			else if($type=='Escalated')
				$payload['type'] = 'Escalation';
			else
				$payload['type'] = 'Task Assignment';
		}

		$payload['tasklist']=$task_list;
		$payload['ack'] = 1;

		//$payload['table_name'] = 'services_task';

		$payload['property_id'] = $user->property_id;
		$payload['notify_type'] = 'guestservice';
		$payload['notify_id'] = $notify_id;

		$send_modes= explode(',',$send_mode);
		$send_modes = array_values(array_unique($send_modes));
		// echo json_encode($send_modes);

		$new_send_modes = [];

		foreach($send_modes as $send_mode)
		{
			if ($send_mode == 'email'|| $send_mode == 'e-mail') {
				$this->sendEmail($user->email, $type, $email_content, $smtp, $payload, $payload['type'] );
				$new_send_modes[] = 'email';
			}

			if ($send_mode == 'SMS'){
				if($type == 'guest')
				{
					if( empty($user->mobile) )
						$new_send_modes[] = 'SMS (No Mobile Number)';
					else
					{
						$this->sendSMS($task_id, $user->mobile, $message, $payload);
						$new_send_modes[] = 'SMS';
					}
				}
				else {
					$setting = DeftFunction::getGSDeviceSetting($user->dept_id,$dept_func);

					if( $type == 'Escalated' )
						$number = $user->mobile;
					else if($setting == 1 && $type!='Escalated')
						$number = Device::getDeviceNumber($user->device_id);
					else
						$number = $user->mobile;

					if( empty($number) )
						$new_send_modes[] = 'SMS (No Mobile Number)';
					else
					{
						$this->sendSMS($task_id, $number, $message, $payload);
						$new_send_modes[] = 'SMS';
					}
				}
			}


			if ($send_mode == 'Mobile') {
				$setting = DeftFunction::getGSDeviceSetting($user->dept_id,$dept_func);

				if($setting == 1 && $type!='Escalated')
					$user->mobile = Device::getDeviceNumber($user->device_id);
				if($type == 'guest')
				{
					$this->sendGuestInfoMobilePushMessage($task_id, $message, $user, $payload);
				}
				else {
					$this->sendMobilePushMessage($task_id, $message, $user, $payload);
				}

				$new_send_modes[] = 'Mobile';
			}
		}

		return $new_send_modes;
	}

	public function sendEmail($email, $title, $content, $smtp ,$payload,$subject)
	{
		//
		//		Mail::queue('emails.reminder', ['info' => $info], function ($message) use ($info) {
		////			$message->from('hello@app.com', 'Hotlync');
		////
		//			$message->to($info['email'])->subject('Hotlync Notification');
		//		});

		$message = array();
		$message['type'] = 'email';
		$message['to'] = $email;
		$message['subject'] = (!empty($subject))? ('Hotlync Notification - '.$subject) : 'Hotlync Notification';
		$message['title'] = $title;
		$message['content'] = $content;
		$message['smtp'] = $smtp;
		$message['payload'] = $payload;

		Redis::publish('notify', json_encode($message));

		//		return response()->json(['message' => 'Request completed']);
	}

	public function sendSMS($task_id, $number, $content, $payload)
	{
		$message = array();
		$message['type'] = 'sms';

		$message['to'] = $number;

		$message['subject'] = 'Hotlync Notification';
		$message['content'] = $content;
		$message['payload'] = $payload;

		Redis::publish('notify', json_encode($message));
	}

	public function sendGuestInfoMobilePushMessage($task_id, $message, $user, $payload) 
	{
		$payload['header'] = 'Requests';
		$result = Functions::sendPushMessgeToDeviceWithRedisNodejs(
				$user, $task_id, $payload['type'], $message, $payload
		);
	}

	public function sendMobilePushMessage($task_id, $message, $user, $payload) 
	{
		$payload['header'] = 'Requests';
		$result = Functions::sendPushMessgeToDeviceWithRedisNodejs(
				$user, $task_id, $payload['type'], $message, $payload
		);
	}

	private function createHSKPTask($ticket)
	{
		$cur_time = date("Y-m-d H:i:s");

		//$supr_device=($supervisor_device);
		$property_id = $ticket['property_id'];

		$setting['supervisor_job_role']=0;
		$setting=PropertySetting::getPropertySettings($property_id,$setting);
		$roster_sup= RosterList::findSupRosterfromRoom($setting['supervisor_job_role'],$ticket['room']);
		if(!empty($roster_sup))
		{
			$room=Room::find($ticket['room']);
			$message ='A Rush Clean Request has been created for Room '.$room->room;
			// echo $message;
			RosterList::sendRosterNotification($roster_sup, $message);
			$this->highPriorityRoom($room);
		}
	}

	private function highPriorityRoom($room)
	{
		$room_status = HskpRoomStatus::find($room->id);
		$room_status->priority='1';
		$room_status->save();
		//$this->getRoomDetails($room_id,$property_id)
	}

	private function checkFrequency($task_info, $location_id, $property_id)
	{
		if( $task_info->frequency_notification_flag < 1 )	// disable frequency notification
			return;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		if( $task_info->period == 0 )
			return;

		$peroid_past_time = date('Y-m-d H:i:s', strtotime("-" . $task_info->period . " days")); // last 24

		$request_list = DB::table('services_task as st')
			->join('services_task_list as tl', 'st.task_list', '=', 'tl.id')
			->join('services_task_group_members as tgm', 'tl.id', '=', 'tgm.task_list_id')
			->join('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
			->leftJoin('common_users as cu', 'st.dispatcher', '=', 'cu.id')
			->where('st.created_time', '>=', $peroid_past_time)
			->where('st.task_list', $task_info->task_id)
			->where('st.location_id', $location_id)
			->select(DB::raw('st.id, st.closed_flag, st.created_time, CONCAT_WS(" ", cu.first_name, cu.last_name) as assigned, st.status_id, " " as comment'))
			->get();

		$loc = $this->getLocationInfo($location_id);
		$location_name = "";
		if( !empty($loc) )
			$location_name = $loc->name . ' - ' . $loc->type;

		$message = array();

		if( count($request_list) >= $task_info->frequency & $task_info->frequency > 0 )
		{
			// send email notification
			$smtp = Functions::getMailSetting($property_id, 'notification_');

			$frequency_job_role_ids = explode(",", $task_info->frequency_job_role_ids);

			$userlist = DB::table('common_users as cu')
								->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
								->where('cd.property_id', $property_id)
								->whereIn('cu.job_role_id', $frequency_job_role_ids)
								->where('cu.deleted', 0)
								->select(DB::raw('cu.*'))
								->groupBy('cu.email')
								->get();

			foreach($userlist as $user)
			{
				$info = array();
				$info['request_list'] = $request_list;
				$info['first_name'] = $user->first_name;
				$info['last_name'] = $user->last_name;
				$info['task'] = $task_info->task;
				$info['category_name'] = $task_info->category_name;
				$info['frequency'] = count($request_list);
				$info['period'] = $task_info->period;
				$info['location'] = $location_name;
				$info['status_name'] = ['Completed', 'Open', 'Escalated','Timeout', 'Canceled', 'Scheduled', 'Unassigned'];
				$info['request_list'] = $request_list;

				$email_content = view('emails.guestrequest_frequency_period', ['info' => $info])->render();

				$message['type'] = 'email';
				$message['to'] = $user->email;
				$message['subject'] = "Frequent " . $task_info->task . " " . $task_info->category_name . " For " . $location_name;
				$message['content'] = $email_content;
				$message['smtp'] = $smtp;

				Redis::publish('notify', json_encode($message));
			}
		}

		return $task_info;
	}

	public function getLocationInfo($location_id)
	{
		$ret = DB::table('services_location as sl')
			->join('common_property as cp', 'sl.property_id', '=', 'cp.id')
			->join('services_location_type as lt', 'sl.type_id', '=', 'lt.id')
			->where('sl.id', $location_id)
			->select(DB::Raw('sl.*, sl.id as lg_id, sl.name, lt.type, cp.name as property'))
			->first();

		return $ret;
	}

	public function createTaskState($ticket_ids, $source, $created_by)
	{

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		// create task state table
		$tasklist = DB::table('services_task as st')
				->leftJoin('services_task_group_members as tgm', 'st.task_list', '=', 'tgm.task_list_id')
				->leftJoin('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
				->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
				->whereIn('st.id', $ticket_ids)
				->select(DB::raw('st.*, tg.dept_function as escalation_group, tg.user_group, tg.unassigne_flag, tg.id as tg_id, tl.type as task_type'))
				->get();

        $bCallSytemNotification = false;
		
		for($i = 0; $i < count($tasklist); $i++ )
		{
			// task state
			$this->createNewTaskState($tasklist[$i], 0, $tasklist[$i]->tg_id, $tasklist[$i]->escalation_group);

			if($tasklist[$i]->status_id == OPENGS) {
			    if ($bCallSytemNotification == false) {
                    $this->saveSystemNotification($tasklist[$i], "Open");
                    $bCallSytemNotification = true;
                } else {
                    $this->saveSystemNotification($tasklist[$i], "Open", false);
                }
            }

			// send notification
			$task_notify = $this->saveNotification($tasklist[$i]->dispatcher, $tasklist[$i]->id, 'Assignment');//user_id, task_id, type
			
			if($tasklist[$i]->user_group!=NULL)
			{
				if( $tasklist[$i]->dispatcher > 0)
				{
					$user_name = CommonUser::getWholeName($tasklist[$i]->dispatcher);
					$this->broadcast($user_name, $tasklist[$i]->user_group, $tasklist[$i]->id, 'Assignment', 0, 0, 0, 0);
				}
				else	// unassigned
				{
					$this->sendNotifyToUserGroup($tasklist[$i]->user_group, $tasklist[$i]->id, 'Unassigned');
				}
			}


			// save log(Created)
			$task_log = new Tasklog();
			$task_log->task_id = $tasklist[$i]->id;
			$task_log->user_id = $created_by;
			$task_log->comment = $tasklist[$i]->custom_message;
			$task_log->log_type = 'Created';
			$task_log->log_time = $cur_time;
			$task_log->status = 'Created';

			if( $source == 0 )
				$task_log->method = WEB_SOURCE;
			else if( $source == 1 )
				$task_log->method = MOBILE_SOURCE;
			elseif( $source == 2 )
				$task_log->method = IVR_SOURCE;
			elseif( $source == 3 )
				$task_log->method = BOT_SOURCE;
			elseif( $source == 4 )
				$task_log->method = ALEXA_SOURCE;

			$task_log->save();

			// save log(Assignment)
			if( $tasklist[$i]->dispatcher > 0 )
			{
				$task_log = new Tasklog();
				$task_log->task_id = $tasklist[$i]->id;
				$task_log->user_id = $tasklist[$i]->dispatcher;
				$task_log->comment = $tasklist[$i]->custom_message;
				$task_log->log_type = $this->getLogType($tasklist[$i]);
				$task_log->log_time = $cur_time;
				$task_log->status = 'Open';

				$method = '';
				if( !empty($task_notify) )
					$method = $task_notify->mode;

				$task_log->method = $method;
				if( !empty($task_notify) )
					$task_log->notify_id = $task_notify->id;

				$task_log->save();
			}


			if( $tasklist[$i]->type == 2 && $tasklist[$i]->requester_notify_flag == 1 )	// department request and notify
			{
		//				$this->saveNotification($tasklist[$i]->dispatcher, $tasklist[$i]->id, 'Assignment');
			}

		}

		//return $broadcast;
	}

	public function createNewTaskState($task, $level, $task_group_id, $escalation_group_id)
	{
		if( $task_group_id == 0 ) {
			$model = TaskList::find($task->task_list);
			$taskgroups = $model->taskgroup;
			if( empty($taskgroups) || count($taskgroups) < 1 )
				return array();

			$taskgroup = $taskgroups[0];
			$task_group_id = $taskgroup->id;
			$escalation_group_id = $taskgroup->escalation_group;
		}
		else
		{
			$taskgroup = TaskGroup::find($task_group_id);
		}

		DB::table('services_task_state')->where('task_id', $task->id)->delete();

		$task_state = new TaskState();

		$task_state->task_id = $task->id;
		$task_state->task_group_id = $task_group_id;
		$task_state->escalation_group_id = $escalation_group_id;
		$task_state->level = $level;
		$task_state->status_id = $task->status_id;
		$task_state->dispatcher = $task->dispatcher;
		$task_state->attendant = $task->attendant;
		$task_state->running = $task->running;
		$task_state->elaspse_time = 0;

		if( $taskgroup->unassigne_flag == 0 )
			$task_state->setStartEndTime($task->max_time, $task->start_date_time);
		else
			$task_state->setStartEndTime($taskgroup->start_duration, $task->start_date_time);


		$task_state->save();
	}

	public function saveSystemNotification($task, $type, $isRefresh = true) 
	{
		$notification = new SystemNotification();

		$taskinfo = $this->getTaskDetail($task->id);

		$notification->type = 'app.guestservice.notify';
		$notification->header = 'Requests';
		$notification->property_id = $task->property_id;

		$content = 'Unknown Notification';

		$action = "opened";
		if( $type == 'Complete' )
			$action = "completed";
		if( $type == 'Open' )
			$action = "opened";
		if( $type == 'Cancel' )
			$action = "canceled";
		if( $type == 'Schedule' )
			$action = "scheduled";
		if( $type == 'Extended' )
			$action = "extended";
		if( $type == 'Hold' )
			$action = "put on Hold";
		if( $type == 'Resume' )
			$action = "resumed";
		if( $type == 'Escalated' )
			$action = "escalated";
		if( $type == 'Timeout' )
			$action = "timeout";
		if( $type == 'Closed' )
			$action = "closed";

		if( $type == 'Assigned' )
			$action = "assigned";

		if( $type == 'Waiting Escalated' )
			$action = "waiting escalated";

		if( $type == 'Unattended' )
			$action = "unattended";

		$which = 'Guest';
		$where = 'Room';
		if( $taskinfo->type == 1 || $taskinfo->type == 4 && $taskinfo->subtype == 1 ) {
			$which = 'Guest';
			$where = 'Room ' . $taskinfo->room;
		}
		if( $taskinfo->type == 2 || $taskinfo->type == 4 && $taskinfo->subtype == 2 ) {
			$which = 'Department';
			$where = 'Location: ' . $taskinfo->lgm_name . ' - ' . $taskinfo->lgm_type;
		}
		if( $taskinfo->type == 3 ) {
			$which = 'Complaint';
			$where = 'Room ' . $taskinfo->room;
		}

		$content = sprintf('%s Request Ticket for %s is %s', $which, $where, $action);
		if(!empty($taskinfo->department_id))
		{
			$notification->content = $content;
			$notification->notification_id = $task->id;

			date_default_timezone_set(config('app.timezone'));
			$cur_time = date("Y-m-d H:i:s");
			$notification->created_at = $cur_time;
			$notification->dept_id=$taskinfo->department_id;
			$notification->save();

			CommonUser::addNotifyCount($task->property_id, 'app.guestservice.notify');

			$message = array();
			$message['type'] = 'webpush';
			$message['to'] = $task->property_id;
			$message['content'] = $notification;

            $message['isRefresh'] = $isRefresh;

            Redis::publish('notify', json_encode($message));
		}
	}

	public function broadcast($user_name, $user_group, $task_id, $type, $room, $old_room, $guest_flag,$vip)
	{
		$ret = [];
		$vip = strtolower($vip);
		 if( ! ($user_group > 0) )
			 return;

		$user_groups = CommonUserGroup::find($user_group);

		if($guest_flag == 0)
			$task = $this->getTaskDetail($task_id);

		elseif($guest_flag==3)
		{
			if(!empty($user_groups))
			{
				$setting = array();
				$setting['room_change'] = 0;
				$setting = PropertySetting::getPropertySettings($user_groups->property_id,$setting);
				$tasklist = explode(',', $setting['room_change']);

				if(!empty($tasklist))
				{
					foreach ($tasklist as  $value) {
						$arr = array();

						$arr['user_id']=0;
						$arr['user']='';
						$arr['property_id']=$user_groups->property_id;
						$arr['room'] = $room;
						$arr['task_id'] = $value;
						$arr['quantity']=1;
						$arr['type']=1;
						$arr['start_date_time']=date('Y-m-d H:i:s');
						$arr['picture_path']='';
						$arr['priority']=1;
						$arr['comment'] ='Guest has been moved from Room '.$old_room.' to Room '.$room;
						$arr['location_type']='Room';
						$arr['status_id'] = 1;
						$arr['checklist_id'] = 0;

						$ret[] = $this->createTaskAll($arr,3);
					}

				}
				$task = '';
				$guest_id='G'.$task_id;
				$task_id=$guest_id;
			}
		}
		else
		{
			$task = '';
			$guest_id = 'G'.$task_id;
			$task_id = $guest_id;
		}

		$flag = 1;

        $loc_arr = [];

		if(!empty($user_groups) && $room > 0)
		{
			if(!empty($user_groups->vip))
			{
				$vip_arr=explode(',',$user_groups->vip);

				$vip_codes = VIPCodes::getcodes($vip_arr);
			}

			$loc_arr = explode(',', $user_groups->location_group);

			if( (!empty($vip_codes)&&(in_array($vip, $vip_codes))) || empty($user_groups->vip))
			{
				$loc_type = LocationType::createOrFind('Room');
				$room_loc = DB::table('services_location as st')
					->join('common_room as cr', 'st.room_id', '=', 'cr.id')
					->where('st.type_id', $loc_type->id)
					->where('st.property_id', $user_groups->property_id)
					->where('cr.room', $room)
					->first();

				$location_arr = [];

				if( !empty($room_loc) )
				{
					$loc_group_list = DB::table('services_location_group_members as lgm')
						->where('loc_id', $room_loc->id)
						->get();

					foreach($loc_group_list as $row)
					{
						$location_arr[] = $row->location_grp;
					}
				}

				if(!empty($loc_arr))
				{
					$flag=0;
					if(!empty($location_arr))
					{
						foreach ($location_arr as $row) {
							if(in_array($row, $loc_arr))
							{
								$flag = 1;
							}
						}
					}
				}
				else {
					$flag = 1;
				}
			}
			else
			{
				$flag = 0;
			}
		}

		if($flag == 1)
		{
			$user_group_members= DB::table('common_user_group_members as cugm')
				->join('common_user_group as cug', 'cugm.group_id', '=', 'cug.id')
				->where('cug.id', $user_group)
				->select(DB::raw('cug.*, cugm.user_id'))
				->get();
				
			if(!empty($user_group_members->toArray()))
			{
				$send_mode = $user_group_members[0]->group_notification_type;

				foreach($user_group_members as $key => $value)
				{
					if(($guest_flag!=0) ||(!empty($task) && ($task->dispatcher!=($value->user_id))))
					{
						$task_notify = new TaskNotification();
						$task_notify->task_id = $task_id;
						$task_notify->mode = $send_mode;
						if($old_room==0)
							$task_notify->notification = $this->getNotifySMSMessage($task, $value->user_id, $type, $user_name, $room, $guest_flag, $vip);
						else {
							$task_notify->notification = $this->getNotifySMSMessage($task, $value->user_id, $type, $user_name, $room.','.$old_room, $guest_flag, $vip);
						}

						$email_content = $this->getNotifyEmailMessage($task, $value->user_id, $type);

						$task_notify->type = $type;
						date_default_timezone_set(config('app.timezone'));
						$cur_time = date("Y-m-d H:i:s");

						$task_notify->send_time = $cur_time;
						$task_notify->user_id = $value->user_id;


						$task_user=Tasklog::checkUserTask($task_id);
						$task_notify->checking= $vip;
						$task_notify->save();

						if($task_user!=($value->user_id))
						{
							if(!empty($task) &&( $task->status_id != COMPLETEDGS && $task->status_id != TIMEOUTGS || $type == 'Forward') )
							{

								$this->sendNotification($value->user_id, $task_id,$task->dept_func, $task->task_list, $type, $task_notify->notification, $email_content, $send_mode, $task_notify->id, $guest_flag,1);
							}
							else if($guest_flag>0)
							{

								$this->sendNotification($value->user_id, $guest_id, '','', $type, $task_notify->notification, $email_content, $send_mode, $task_notify->id,$guest_flag,1);
							}
						}
					}
				}
			}
		}
	}

	public function createTaskAll($info, $method_flag)
	{

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$user = $info['user'];
		$ret = array();


		if ($info['location_type'] != "Room") {
			$room_id = 0;
		} else {
			$room = DB::table('common_room as cr')
				->where('cr.room', $info['room'])
				->select(DB::raw('cr.*'))
				->first();

			if (empty($room)) {
				$ret['code'] = 201;
				$ret['message'] = 'This room does not exist';

				return $ret;
			}

			$room_id = $room->id;
		}

		// get location grp
		if ($info['location_type'] != "Room") {
			$location_id = $info['location_id'];
		} else {
			$location_info = $this->getLocationGroupIDFromRoom($room_id);
			$location_id = $location_info->id;
		}

		$task_info_query = DB::table('services_task_list as tl')
			->join('services_task_group_members as tgm', 'tl.id', '=', 'tgm.task_list_id')
			->join('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
			->join('services_dept_function as df', 'tg.dept_function', '=', 'df.id')
			->leftJoin('services_task_category as tc', 'tl.category_id', '=', 'tc.id')
			->where('tl.status', 1);
		if (empty($info['task_name'])) {
			$task_info = $task_info_query->where('tl.id', $info['task_id']);
		} else {
			$lang_string = '"text":"';
			$where_task = sprintf(
				"task = '%s' or
									lang like '%%%s%s\"%%'",
				$info['task_name'],
				$lang_string,
				$info['task_name']
			);

			$task_info = $task_info_query->whereRaw($where_task);
		}

		$task_info = $task_info->select(DB::raw('tl.id as task_id, tl.task as task_name, tg.reassign_flag,tg.reassign_job_role, tg.max_time, tg.dept_function as dept_func, df.dept_id, 
												tl.task, tc.name as category_name, tg.dept_function as escalation_group, tg.start_duration, tg.unassigne_flag, tg.user_group, tg.id as tg_id, tg.dept_function, tg.frequency_notification_flag, tg.frequency_job_role_ids, tg.frequency, tg.period'))
			->first();


		if (empty($task_info)) {
			$ret['code'] = 202;
			$ret['message'] = 'Invalid Task Name';

			return $ret;
		}

		$info['task_name'] = $task_info->task_name;
		if ($info['location_type'] != "Room") {
			$shift = $this->getTaskShiftInfo($task_info->task_id, $location_id);
		} else {
			$shift = $this->getTaskShiftInfo($task_info->task_id, $location_info->id);
		}

		if (empty($shift['staff_list']) || count($shift['staff_list']) <= 0)
			$dispatcher = 0;
		else
			$dispatcher = $shift['staff_list'][0]->id;

		$status_id = $info['status_id'];

		// calc max time for ending xx:xx:00
		if ($status_id == OPENGS) {
			if ($task_info->unassigne_flag == 1) {
				$dispatcher = 0;
				$status_id = UNASSIGNEDGS;
				$max_time = Functions::calcDurationForMinute($info['start_date_time'], $task_info->start_duration);
			} else {
				$max_time = Functions::calcDurationForMinute($info['start_date_time'], $task_info->max_time);
			}
		} else
			$max_time = Functions::calcDurationForMinute($info['start_date_time'], $task_info->max_time);


		$ticket = array();

		$ticket['task_list'] = $task_info->task_id;

		if ($info['location_type'] == "Room" && $info['type'] == 1) {
			$guest = DB::table('common_guest as cg')
				->where('cg.room_id', $room_id)
				->orderBy('cg.id', 'desc')
				->orderBy('cg.arrival', 'desc')
				//->where('cg.checkout_flag', 'checkin')
				->select(['cg.*'])
				->first();
			//echo $room_id.' '.json_encode($guest);
			if (empty($guest) || $guest->checkout_flag == 'checkout')
				$info['type'] = 2;
		}


		$ticket['type'] = $info['type'];
		$ticket['room'] = $room_id;
		$ticket['dispatcher'] = $dispatcher;
		if ($info['location_type'] != "Room") {
			$ticket['location_id'] = $location_id;
		} else {
			$ticket['location_id'] = $location_info->id;
		}

		//if()
		$setting['default_alexa_task'] = 0;
		$setting = PropertySetting::getPropertySettings($info['property_id'], $setting);
		$check_id = $setting['default_alexa_task'];
		if ($method_flag == 4) {
			$setting['default_alexa_task'] = 0;
			$setting = PropertySetting::getPropertySettings($info['property_id'], $setting);
			$check_id = $setting['default_alexa_task'];
			if ($check_id != $info['task_id'] && ($this->isValidTicket($ticket, $prev) == false)) {
				$ret['code'] = 201;

				$ret['message'] = 'You cannot create multiple ticket with same task for same room';
				$ret['test'] = $ret['message'];
				return $ret;
			}
		} elseif (($status_id == OPENGS || $status_id == UNASSIGNEDGS)  && $this->isValidTicket($ticket, $prev) == false) {
			$ret['code'] = 201;

			$ret['message'] = 'You cannot create multiple ticket with same task for same room';
			$ret['test'] = $ret['message'];
			return $ret;
		}
		$queued_flag = 0;
		$running = 1;
		$last_task = null;



		// check running ticket.
		if ($dispatcher > 0) {
			if ($this->isQueuableTask($task_info->task_id))	// check queuable task
				if ($info['location_type'] != "Room") {
					$last_task = $this->getRunningTicket($info['property_id'], $dispatcher, 0, $location_id);
				} else {
					$last_task = $this->getRunningTicket($info['property_id'], $dispatcher, 0, $location_info->id);
				}
		}

		if (!empty($last_task)) {
			$queued_flag = 1;
			$running = 0;
		}

		if ($task_info->unassigne_flag == 1)
			$running = 0;

		$task = new Task();
		$task->property_id = $info['property_id'];
		$task->dept_func = $task_info->dept_func;
		// if($method_flag==1)
		// $task->department_id = $dept_id;
		// else
		$task->department_id = $task_info->dept_id;


		$task->subtype = 0;
		$task->priority = $info['priority'];
		$task->start_date_time = $info['start_date_time'];
		$task->created_time = $cur_time;
		$task->end_date_time = '0000-00-00 00:00:00';
		$task->dispatcher = $dispatcher;
		$task->finisher = 0;
		$task->attendant = $info['user_id'];
		$task->source = $method_flag;

		if ($info['user_id'] == 0) {
			if ($method_flag == 3)
				$task->attendant_auto = 'Hotlync';
			else
				$task->attendant_auto = 'Guest';
		}

		$task->room = $room_id;
		$task->task_list = $task_info->task_id;
		if ($info['location_type'] != "Room") {
			$task->location_id = $location_id;
		} else {
			$task->location_id = $location_info->id;
		}
		$setting['services_request_moderate_time'] = 0;
		$setting['services_request_high_time'] = 0;
		$setting = PropertySetting::getPropertySettings($info['property_id'], $setting);
		if ($info['priority'] == 3) {
			if (!empty($setting['services_request_high_time'])) {
				$max_time1 = $max_time - (($max_time * $setting['services_request_high_time']) / 100);
				$task->max_time = $max_time1;
			}
		} elseif ($info['priority'] == 2) {
			if (!empty($setting['services_request_moderate_time'])) {
				$max_time1 = $max_time - (($max_time * $setting['services_request_moderate_time']) / 100);
				$task->max_time = $max_time1;
			}
		} else {
			$task->max_time = $max_time;
		}
		//  $task->max_time = $max_time;
		$task->quantity = $info['quantity'];

		if ($method_flag == 2 || $method_flag == 3 || $method_flag == 4) {
			if ($info['type'] == 1) {
				$guest = DB::table('common_guest as cg')
					->where('cg.room_id', $room_id)
					->orderBy('cg.departure', 'desc')
					->orderBy('cg.arrival', 'desc')
					->where('cg.checkout_flag', 'checkin')
					->select(['cg.*'])
					->first();
				if (empty($guest)) {
					$task->guest_id = 0;
					$info['type'] = 2;
				} else
					$task->guest_id = $guest->guest_id;
			} else
				$task->guest_id = 0;


			$task->requester_id = 0;
			$task->requester_name = '';
			$task->requester_job_role = '';
			$task->requester_notify_flag = 0;
			$task->requester_mobile = '';
			$task->requester_email = '';
		} else {
			if ($info['type'] == 1 || $info['type'] == 2) {
				$guest = DB::table('common_guest as cg')
					->where('cg.room_id', $room_id)
					->orderBy('cg.departure', 'desc')
					->orderBy('cg.arrival', 'desc')
					->where('cg.checkout_flag', 'checkin')
					->select(['cg.*'])
					->first();
				if (empty($guest))
					$task->guest_id = 0;
				else
					$task->guest_id = $guest->guest_id;
			} else
				$task->guest_id = 0;

			$task->requester_id = $info['user_id'];
			$task->requester_name = $user->wholename;
			$task->requester_job_role = $user->job_role_id;
			$task->requester_notify_flag = 0;
			$task->requester_mobile = $user->mobile;
			$task->requester_email = $user->email;
		}

		$all_dept_setting = DeftFunction::where('dept_id', $task->department_id)
			->where('id', $task_info->dept_func)
			->first();

		if ((($task_info->reassign_flag == 1) && ($task_info->reassign_job_role != '')) || (!empty($all_dept_setting) && ($all_dept_setting->all_dept_setting == 1))) {
			$task->reassigned_flag = 1;
		}

		$task->type = $info['type'];
		$task->custom_message = $info['comment'];
		$task->picture_path = $info['picture_path'];
		$task->compensation_id = 0;
		$task->compensation_status = 1;
		$task->status_id = $status_id;
		$task->queued_flag = $queued_flag;
		$task->running = $running;
		$task->ack = 2;

		if ($info['checklist_id'] != 0) {

			$task->checklist_flag = 1;
			$task->checklist_id = $info['checklist_id'];
		}

		$task->save();


		$this->checkFrequency($task_info, $location_id, $info['property_id']);


		$ticket_ids = [];
		$ticket_ids[] = $task->id;

		$this->createTaskState($ticket_ids, $method_flag, $info['user_id']);
		$this->createSystemTask($ticket_ids);


		if ($task->status_id == OPENGS)
			$this->saveSystemNotification($task, "Open");


		if (!empty($last_task)) {
			$last_task->follow_id = $task->id;
			$last_task->save();
		}

		if ($dispatcher < 1) // no assigned staff
			$this->checkGuestDepartmentTaskState();	// occur escalation

		$ret['code'] = 200;
		$ret['message'] = 'Task is created successfully';
		$ret['test'] = round(($task->max_time / 60));
		$ret['content'] = $task;
		$ret['user'] = $info['user_id'];
		$ret['task'] = $task;

		return $ret;
	}

	public function createSystemTask($ticket_ids)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$tasklist = DB::table('services_task as st')
			->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
			->whereIn('st.id', $ticket_ids)
			->where('tl.type', '>', 100)	// system task
			->select(DB::raw('st.*, tl.type as task_type'))
			->get();

		foreach ($tasklist as $row) {
			$clean_room_task_type = PropertySetting::getCleaningRoomSystemTaskType($row->property_id);
			if ($clean_room_task_type == $row->task_type)		// cleaning room system task setting
			{
				// Clean room system task max_time=room_type.maxtime
				$max_time = DB::table('common_room_type as crt')
					->leftJoin('common_room as cr', 'cr.type_id', '=', 'crt.id')
					->where('cr.id', '=', $row->room)
					->select(DB::raw('crt.max_time'))
					->get();

				$max_time = ($max_time[0]->max_time) * 60;
				$task = Task::find($row->id);
				$task->max_time = $max_time;
				$task->save();

				$task_state = TaskState::where('task_id', $row->id)->first();
				if (!empty($task_state)) {
					$task_state->setStartEndTime($task->max_time, $task_state->start_time);
					$task_state->save();
				}

				$room_status = HskpRoomStatus::find($row->room);
				if (!empty($room_status))	// there is room
				{
					$room_status->rush_flag = 1;
					$room_status->save();
				}
			}
		}

		// check rush clean task
		$tasklist = DB::table('services_task as st')
			->whereIn('st.id', $ticket_ids)
			->select(DB::raw('st.*'))
			->get();

		foreach ($tasklist as $row) {
			$settings = PropertySetting::getHskpSettingValue($row->property_id);
			if ($settings['hskp_gs_rush_task'] != $row->task_list)
				continue;

			$loc = DB::table('services_location')->where('id', $row->location_id)->first();
			if (empty($loc))
				continue;

			$room_status = HskpRoomStatus::find($loc->room_id);
			if (!empty($room_status))	// there is room
			{
				$room_status->rush_flag = 1;
				$room_status->save();

				RosterList::sendRushCleanNotification($room_status);
			}
		}
	}

	public function checkGuestDepartmentTaskState()
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		// echo $cur_time;

		// remove all complete, timeout, canceled ticket.
		DB::table('services_task_state')->whereIn('status_id', array(COMPLETEDGS, TIMEOUTGS, CANCELEDGS))->delete();

		// check repeated ticket
		$repeated_count = $this->checkRepeatTickets($cur_date, $cur_time);

		$non_exist_ids = array();

		// check scheduled ticket
		$scheduled_count = $this->checkScheduledTickets($cur_date, $cur_time, $non_exist_ids);

		// check current running ticket.
		$runnning_count = $this->checkCurrentRunningTicket($cur_date, $cur_time, $non_exist_ids);

		// check hold ticket
		$hold_ticket_count = $this->checkOldRunningTicket($cur_date, $cur_time);

		DB::table('services_task_state')->whereIn('id', $non_exist_ids)->delete();

		$ret = array('Repeated' => $repeated_count, 'Running' => $runnning_count, 'Scheduled' => $scheduled_count, 'Hold_Timeout' => $hold_ticket_count);

		return json_encode($ret);
	}

	private function checkRepeatTickets($cur_date, $cur_time)
	{

		// find repeated ticket before 1 day

		$yesterday = new DateTime($cur_time);
		$yesterday->sub(new DateInterval('P1D'));
		$yesterday = $yesterday->format('Y-m-d H:i:s');

		$repeat_list = DB::table('services_task as st')
			//	->leftJoin('common_guest as cg', 'st.guest_id', '=', 'cg.guest_id')
			->leftJoin('common_guest as cg', function ($join) {
				$join->on('st.guest_id', '=', 'cg.guest_id');
				$join->on('st.property_id', '=', 'cg.property_id');
			})
			->where('st.repeat_flag', 1)
			->where('st.status_id', '!=', SCHEDULEDGS)
			->where('st.start_date_time', '<=', $yesterday)
			->select(DB::raw('st.*, cg.departure, cg.checkout_flag'))
			->get();

		foreach ($repeat_list as $row) {
			$end_date = $row->repeat_end_date;

			// check until checkout
			if ($row->until_checkout_flag == 1) {
				// end date
				$end_date = $row->departure;
			}

			if ($cur_date <= $end_date)	// today is not passed to end date
			{
				// create new repeat task
				$task = new Task();

				$task->property_id = $row->property_id;
				$task->dept_func = $row->dept_func;
				$task->department_id = $row->department_id;
				$task->type = $row->type;
				$task->subtype = $row->subtype;
				$task->priority = $row->priority;
				$string = $row->start_date_time;
				$next_date = $cur_date . ' ' . date('H:i:s', strtotime($row->start_date_time));
				$task->start_date_time = $next_date;
				$task->created_time = $row->start_date_time;
				$task->end_date_time = '0000-00-00 00:00:00';

				$dispatcher = $this->getStaffForTicket($row->id);

				$task->dispatcher = $dispatcher;
				$task->finisher = $row->finisher;
				$task->attendant = $row->attendant;
				$task->attendant_auto = $row->attendant_auto;
				$task->room = $row->room;
				$task->task_list = $row->task_list;
				$task->location_id = $row->location_id;

				$max_time = Functions::calcDurationForMinute($task->start_date_time, $row->max_time);
				$task->max_time = $max_time;

				$task->quantity = $row->quantity;
				$task->guest_id = $row->guest_id;
				$task->requester_id = $row->requester_id;
				$task->requester_name = $row->requester_name;
				$task->requester_job_role = $row->requester_job_role;
				$task->requester_notify_flag = $row->requester_notify_flag;
				$task->requester_mobile = $row->requester_mobile;
				$task->requester_email = $row->requester_email;
				//$task->custom_message = $task_list->custom_message;
				$task->compensation_id = 0;
				$task->compensation_status = 1;

				$task->status_id = OPENGS;

				// check running ticket.
				$last_task = null;
				if ($dispatcher > 0 && $task->status_id  == OPENGS) {
					if ($this->isQueuableTask($task->task_list))	// check queuable task
						$last_task = $this->getRunningTicket($task->property_id, $dispatcher, 0, $task->location_id);
				}

				if (!empty($last_task)) {
					$task->running = 0;
					$task->queued_flag = 1;
				} else {
					$task->running = 1;
					$task->queued_flag = 0;
				}

				$task->picture_path = $row->picture_path;
				$task->ack = $row->ack;
				$task->repeat_flag = $row->repeat_flag;
				$task->repeat_end_date = $row->repeat_end_date;
				$task->until_checkout_flag = $row->until_checkout_flag;
				$task->save();

				$status = '';

				if ($task->status_id == OPENGS) {
					$this->saveSystemNotification($task, 'Open');
					$status = 'Open';
				}


				// save and send notification
				$this->saveNotification($task->dispatcher, $task->id, 'Assignment');

				// change task state
				$this->createNewTaskState($task, 0, 0, 0);

				// save log
				$task_log = new Tasklog();
				$task_log->task_id = $task->id;
				$task_log->user_id = $task->dispatcher;
				$task_log->comment = 'System';
				$task_log->log_type = 'Assigned';
				$task_log->log_time = $cur_time;
				$task_log->status = $status;
				$task_log->method = 'Automatic';

				$task_log->save();
			}

			// update old ticket with repeat flag = 1;
			$task = Task::find($row->id);
			$task->repeat_flag = 0;
			$task->save();
		}

		return count($repeat_list);
	}

	public function getStaffForTicketURL(Request $request)
	{
		$task_id = $request->get('task_id', 0);
		return Response::json($this->getStaffForTicket($task_id));
	}

	private function getStaffForTicket($task_id)
	{
		$task = Task::find($task_id);
		if (empty($task))
			return 0;

		$task_shift = $this->getTaskShiftInfo($task->task_list, $task->location_id);

		if (empty($task_shift))
			return 0;

		$staff_list = $task_shift['shift_group_members'];
		if (empty($staff_list) || count($staff_list) < 1)
			return 0;

		return $staff_list[0]->user_id;
	}

	private function checkScheduledTickets($cur_date, $cur_time, &$non_exist_ids)
	{
		$task_state_list = DB::table('services_task as st')
			->leftJoin('common_guest as cg', function ($join) {
				$join->on('st.guest_id', '=', 'cg.guest_id');
				$join->on('st.property_id', '=', 'cg.property_id');
			})
			->where('st.status_id', SCHEDULEDGS)
			->where('st.start_date_time', '<=', $cur_time)
			->whereRaw("DATE(st.start_date_time) = '$cur_date'")
			->select(DB::raw('st.*, cg.departure, cg.checkout_flag'))
			->get();


		foreach ($task_state_list as $row) {
			// update service_task
			$task = Task::find($row->id);
			if (empty($task)) {
				array_push($non_exist_ids, $row->id);
				continue;
			}

			$dispatcher = $this->getStaffForTicket($row->id);
			$task->dispatcher = $dispatcher;
			$task->status_id = OPENGS;

			// check running ticket.
			$last_task = null;
			if ($dispatcher > 0 && $task->status_id  == OPENGS) {
				if ($this->isQueuableTask($task->task_list))	// check queuable task
					$last_task = $this->getRunningTicket($task->property_id, $dispatcher, 0, $task->location_id);
			}

			if (!empty($last_task)) {
				$task->running = 0;
				$task->queued_flag = 1;
			} else {
				$task->running = 1;
				$task->queued_flag = 0;
			}

			$task->repeat_flag = 0;

			$task->save();

			if ($task->status_id == OPENGS)
				$this->saveSystemNotification($task, 'Open');

			// save and send notification
			$this->saveNotification($task->dispatcher, $row->id, 'Assignment');

			// change task state
			$this->createNewTaskState($task, 0, 0, 0);

			// save log
			$task_log = new Tasklog();
			$task_log->task_id = $row->id;
			$task_log->user_id = $task->dispatcher;
			$task_log->comment = 'System';
			$task_log->log_type = 'Modify(Scheduled -> Open)';
			$task_log->log_time = $cur_time;
			$task_log->status = 'Open';
			$task_log->method = 'Automatic';

			$task_log->save();

			if ($row->repeat_flag == 0)
				continue;

			// make repeat task for scheduled
			$end_date = $row->repeat_end_date;

			// check until checkout
			if ($row->until_checkout_flag == 1) {
				// end date
				$end_date = $row->departure;
			}

			if ($cur_date <= $end_date || empty($end_date))	// today is not passed to end date or end date is null
			{
				// create new repeat task
				$task = new Task();

				$task->property_id = $row->property_id;
				$task->dept_func = $row->dept_func;
				$task->department_id = $row->department_id;
				$task->type = $row->type;
				$task->subtype = $row->subtype;
				$task->priority = $row->priority;
				$string = $row->start_date_time;
				$next_date = date('Y-m-d', strtotime('1 days', strtotime($row->start_date_time))) . ' ' . date('H:i:s', strtotime($row->start_date_time));
				$task->start_date_time = $next_date;
				$task->created_time = $row->start_date_time;
				$task->end_date_time = '0000-00-00 00:00:00';

				$task->dispatcher = 0;
				$task->finisher = $row->finisher;
				$task->attendant = $row->attendant;
				$task->attendant_auto = $row->attendant_auto;
				$task->room = $row->room;
				$task->task_list = $row->task_list;
				$task->location_id = $row->location_id;

				$max_time = Functions::calcDurationForMinute($task->start_date_time, $row->max_time);
				$task->max_time = $max_time;

				$task->quantity = $row->quantity;
				$task->guest_id = $row->guest_id;
				$task->requester_id = $row->requester_id;
				$task->requester_name = $row->requester_name;
				$task->requester_job_role = $row->requester_job_role;
				$task->requester_notify_flag = $row->requester_notify_flag;
				$task->requester_mobile = $row->requester_mobile;
				$task->requester_email = $row->requester_email;
				//$task->custom_message = $task_list->custom_message;
				$task->compensation_id = 0;
				$task->compensation_status = 1;

				$task->status_id = SCHEDULED;
				$task->running = 0;
				$task->repeat_flag = 1;
				$task->repeat_end_date = $row->repeat_end_date;

				$task->picture_path = $row->picture_path;
				$task->ack = $row->ack;
				$task->until_checkout_flag = $row->until_checkout_flag;

				$task->save();

				// save log
				$task_log = new Tasklog();
				$task_log->task_id = $task->id;
				$task_log->user_id = $task->dispatcher;
				$task_log->comment = 'System';
				$task_log->log_type = 'Scheduled';
				$task_log->log_time = $cur_time;
				$task_log->status = 'Scheduled';
				$task_log->method = 'Automatic';

				$task_log->save();
			}
		}

		return count($task_state_list);
	}

	private function checkCurrentRunningTicket($cur_date, $cur_time, &$non_exist_ids)
	{

		$task_state_list = DB::table('services_task_state')
			->whereRaw("((status_id = 1 or status_id = 2) and running = 1 and (end_time <= '$cur_time' or dispatcher = 0)) or(status_id = 6 && end_time <= '$cur_time')")	// running open or escalated or unassigned
			->get();

		for ($i = 0; $i < count($task_state_list); $i++) {
			$task_state = $task_state_list[$i];

			$this->escalateTicket($task_state, $cur_time, $non_exist_ids);
		}

		return count($task_state_list);
	}

	private function escalateTicket($task_state, $cur_time, &$non_exist_ids)
	{
		// update service_task
		$task = Task::find($task_state->task_id);
		$extra_time = 0;
		$nostaff_escalation_setting = DeftFunction::find($task->dept_func);
		if (empty($task)) {
			array_push($non_exist_ids, $task_state->id);
			return;
		}

		if ($task->dispatcher == 0 && ($nostaff_escalation_setting->escalation_setting == 0 || $task->status_id == ESCALATEDGS) && ($task_state->end_time > $cur_time)) {
			return;
		} elseif ($task->dispatcher == 0 && ($nostaff_escalation_setting->escalation_setting == 1) && ($task_state->end_time > $cur_time)) {
			$startTime = new DateTime($task_state->end_time);
			$endTime = new DateTime($cur_time);
			$duration = $startTime->diff($endTime); //$duration is a DateInterval object
			$extra_time = (($duration->h) * 60 * 60) + (($duration->i) * 60) + $duration->s;
			//echo $task_state->end_time - $cur_time;
		}

		$escalation_list = Escalation::where('escalation_group', $task_state->escalation_group_id)
			->where('level', '>', $task_state->level)->orderBy('level')->get();

		$escalation_count = Escalation::where('escalation_group', $task_state->escalation_group_id)
			->where('level', '>', 0)->count();

		$method = '';
		$status = '';

		// find escalation staff_id
		$user_list = array();

		for ($j = 0; $j < count($escalation_list); $j++) {
			$escalation = $escalation_list[$j];

			if ($escalation->device_type == 0)	// User based
			{
				$user_list = $this->getUserListForTicket($task, $escalation->job_role_id, false);
			} else if ($escalation->device_type == 1)	// Device based
			{
				$user_list = $this->getUserListForTicketDeviceBased($task, $escalation->job_role_id, false);
			}

			// echo json_encode($escalation) . ": " . count($user_list) . "<br/>";

			if (count($user_list) > 0)
				break;
		}


		//echo $task->id.'Here'.$task_state->end_time.', Curr:'. $cur_time. 'Assigned:'.;

		// echo json_encode($user_list);
		// return;

		if (count($user_list) > 0 && !empty($escalation))		// escalated
		{
			if ($task->status_id != ESCALATEDGS) {
				$task->status_id = ESCALATEDGS;
				$task->escalate_flag = 1;
				$task->running = 1;

				$this->saveSystemNotification($task, 'Escalated');
				$task->save();
			}
			$this->saveMobileNotification($task, 'Escalated', $user_list[0]->id);

			// change task state
			$this->upgradeEscalationLevel($task_state->id, $escalation, $cur_time, $extra_time);

			// save and send notification
			foreach ($user_list as $row) {
				$user_id = $row->user_id;

				$task_notify = $this->saveEscalationNotification($user_id, $task_state, $escalation);

				// save log
				$log_type = 'Notification';
				$status = 'Escalated ' . $escalation->level . '/' . $escalation_count;
				$method = "";
				if (!empty($task_notify))
					$method = $task_notify->mode;

				// save log
				$task_log = new Tasklog();
				$task_log->task_id = $task_state->task_id;
				$task_log->user_id = $user_id;
				$task_log->comment = '';
				$task_log->log_type = $log_type;
				$task_log->log_time = $cur_time;
				$task_log->status = $status;
				$task_log->method = $method;
				if (!empty($task_notify))
					$task_log->notify_id = $task_notify->id;

				$task_log->save();
			}
		} else {
			// change task to time_out
			$task->end_date_time = $cur_time;
			$task->status_id = TIMEOUTGS;
			$task->running = 0;

			$this->saveMobileNotification($task, 'Timeout', 0);

			// remove task state
			$task_state_model = TaskState::find($task_state->id);
			if (!empty($task_state_model)) {
				$task->duration = $task->duration + $task_state_model->getElapseTime();
				$task_state_model->delete();
			}

			$task->save();

			$this->saveSystemNotification($task, 'Timeout');

			// save and send notification to attendant
			$this->saveNotification($task->attendant, $task->id, 'Timeout');

			if ($task_state->level == 0) { // if there is no escalation
				// send notification to default assignee
				$dept = DB::table('common_department')
					->where('id', $task->department_id)
					->first();

				if (!empty($dept) && $dept->default_assignee > 0) {
					$this->saveNotification($dept->default_assignee, $task->id, 'Timeout_No_Escalation');
					$user_id = $dept->default_assignee;
				} else
					$user_id = 0;

				$log_type = 'Timeout_No_Escalation';
			} else {
				$user_id = $task->attendant;
				$log_type = 'Timeout';
			}

			// save log
			$status = 'Timeout';
			if (!empty($task_notify))
				$method = $task_notify->mode;

			if ($task->type == 4)		// managed task
			{
				// follow task
				$this->followTask($task->follow_id);
			}

			$this->startNextTask($task->property_id, $task->id, $task->dispatcher, $task->location_id);

			// save log
			$task_log = new Tasklog();
			$task_log->task_id = $task_state->task_id;
			$task_log->user_id = $user_id;
			$task_log->comment = '';
			$task_log->log_type = $log_type;
			$task_log->log_time = $cur_time;
			$task_log->status = $status;
			$task_log->method = $method;
			if (!empty($task_notify))
				$task_log->notify_id = $task_notify->id;

			$task_log->save();
		}
	}

	private function getUserListForTicketDeviceBased($task, $job_role_id, $active_check)
	{
		$ret = array();
		if (empty($task))
			return array();

		$model = TaskList::find($task->task_list);
		$taskgroup = $model->taskgroup;
		if (empty($taskgroup) || count($taskgroup) < 1)
			return array();

		$task_group = $taskgroup[0];

		$dept_func_id = $task_group->dept_function;

		$dept_func = DeftFunction::find($dept_func_id);
		if (empty($dept_func))
			return $ret;

		// find building id
		$building_id = Location::find($task->location_id)->building_id;

		// $escalated_user_list = $this->getUserListDeviceBasedJobRole($location_group_id,$task->location_id,$task->property_id, $job_role_id, $dept_func);
		$escalated_user_list = ShiftUser::getDevicelistOnCurrentShift($job_role_id, $dept_func_id, $task->location_id, $building_id, $active_check);

		return $escalated_user_list;
	}

	public function saveMobileNotification($task, $type, $user_id)
	{
		if ($type != 'Comment') {
			if ($task->source != 1)
				return;
		}
		$title = 'Create Task';

		switch ($type) {
			case 'Created':
				$title = 'Created Request';
				break;
			case 'Hold':
				$title = 'Holded Request';
				break;
			case 'Completed':
				$title = 'Completed Request';
				break;
			case 'Escalated':
				$title = 'Escalated Request';
				break;
			case 'Timeout':
				$title = 'Timeout Request';
				break;
			case 'Comment':
				$title = 'Comment Changed';
				break;
		}
		$task_id = $task->id;
		$taskinfo = $this->getTaskDetail($task->id);

		// Location Name
		$loc_name = $taskinfo->lgm_type . ' ' . $taskinfo->lgm_name;

		$username = '';

		$user = CommonUser::find($task->attendant);
		$dispatcher = CommonUser::find($task->dispatcher);

		// user
		if ($type == 'Created') {
			$dispatcher = CommonUser::find($task->dispatcher);
			$username = 'not assigned';
			if (!empty($dispatcher))
				$username = sprintf('assigned to %s %s', $dispatcher->first_name, $dispatcher->last_name);
		} else if ($type == 'Hold' || $type == 'Completed') {
			$user_by = CommonUser::find($user_id);
			if (!empty($user_by))
				$username = sprintf('%s %s', $user_by->first_name, $user_by->last_name);
		} else if ($type == 'Escalated') {
			$user_by = CommonUser::find($user_id);
			if (!empty($user_by))
				$username = sprintf('%s %s', $user_by->first_name, $user_by->last_name);
		}

		$body = sprintf(
			'Request %s for location %s has been created and %s',
			$taskinfo->task_name,
			$loc_name,
			$username
		);

		switch ($type) {
			case 'Created':
				$body = sprintf(
					'Request %s for location %s has been created and %s',
					$taskinfo->task_name,
					$loc_name,
					$username
				);
				break;
			case 'Hold':
				$body = sprintf(
					'Request %s for location %s has been put on Hold by %s',
					$taskinfo->task_name,
					$loc_name,
					$username
				);
				break;
			case 'Completed':
				$body = sprintf(
					'Request %s for location %s has been completed by %s',
					$taskinfo->task_name,
					$loc_name,
					$username
				);
				break;
			case 'Escalated':
				$body = sprintf(
					'Request %s for location %s has been escalated to %s',
					$taskinfo->task_name,
					$loc_name,
					$username
				);
				break;
			case 'Timeout':
				$body = sprintf(
					'Request %s for location %s has been timeout',
					$taskinfo->task_name,
					$loc_name
				);
				break;
			case 'Comment':
				$body = sprintf(
					'Comment has been changed for Request %s for location %s',
					$taskinfo->task_name,
					$loc_name
				);
				break;
		}

		$payload = array();

		$payload["type"] = $title;
		$payload["header"] = 'Requests';

		Functions::sendPushMessgeToDeviceWithRedisNodejs($user, $task->id, $title, $body, $payload);
		if ($type == 'Comment') {
			Functions::sendPushMessgeToDeviceWithRedisNodejs($dispatcher, $task->id, $title, $body, $payload);
		}
	}

	public function upgradeEscalationLevel($id, $escalation, $cur_time, $extra_time)
	{
		$task_state_model = TaskState::find($id);
		if (empty($task_state_model))
			return;

		$task = Task::find($task_state_model->task_id);
		if (!empty($task)) {
			$task->duration = $task->duration + $task_state_model->getElapseTime();
			$task->save();
		}

		$task_state_model->level = $escalation->level;
		$task_state_model->status_id = ESCALATEDGS;
		$task_state_model->elaspse_time = 0;
		$task_state_model->running = 1;

		$task_state_model->setStartEndTimewithExtra($escalation->max_time, $cur_time, $extra_time);

		$task_state_model->save();
	}

	public function saveEscalationNotification($user_id, $task_state, $escalation)
	{
		$type = 'Escalated';
		$task_notify = new TaskNotification();

		if (!($user_id > 0)) {
			$task_notify->mode = 'No User';
			return $task_notify;
		}

		$user = CommonUser::find($user_id);
		if (empty($user)) {
			$task_notify->mode = 'Invalid User';
			return $task_notify;
		}

		$task_id = $task_state->task_id;
		$level = $escalation->level;

		$task_notify->task_id = $task_id;
		$task_notify->user_id = $user_id;

		$task = $this->getTaskDetail($task_id);
		if (empty($task)) {
			$task_notify->mode = 'Invalid Task';
			return $task_notify;
		}

		// get escalation level's notify type
		$notify_type = $escalation->notify_type;
		if (empty($notify_type)) {
			$task_notify->mode = 'Escalation Notification is not configured for level ' . $level;
			return $task_notify;
		}

		$sms_message = $this->getNotifySMSMessage($task, $user_id, $type, '', 0, 0, 0);
		$email_content = $this->getNotifyEmailMessage($task, $user_id, $type);

		$notify_list = explode(',', $notify_type);
		$notify_active_list = [];
		$notify_error_list = [];
		foreach ($notify_list as $row) {
			switch ($row) {
				case 'Email':
					if (empty($user->email)) {
						$notify_error_list[] = 'no email configured';
					} else {
						// Send Email
						$this->sendNotification($user_id, $task_id, $task->dept_func, $task->task_list, $type, $sms_message, $email_content, 'email', $task_notify->id, 0, 0);
						// echo $email_content;
						$notify_active_list[] = 'Email';
					}
					break;
				case 'SMS':
					if (empty($user->mobile)) {
						$notify_error_list[] = 'no mobile number configured';
					} else {
						// Send SMS
						$this->sendNotification($user_id, $task_id, $task->dept_func, $task->task_list, $type, $sms_message, $email_content, 'SMS', $task_notify->id, 0, 0);
						// echo 'SMS - ' . $sms_message;
						$notify_active_list[] = 'SMS';
					}
					break;
				case 'Mobile':
					if ($user->active_status != 1) {
						$notify_error_list[] = 'no user logged in';
					} else {
						$this->sendNotification($user_id, $task_id, $task->dept_func, $task->task_list, $type, $sms_message, $email_content, 'Mobile', $task_notify->id, 0, 0);
						// echo 'Mobile - ' .  $sms_message;
						$notify_active_list[] = 'Mobile';
					}
					break;
			}
		}

		if (count($notify_active_list) > 0)
			$task_notify->mode = implode(',', $notify_active_list);
		else
			$task_notify->mode = implode(',', $notify_error_list);

		$task_notify->notification = $this->getNotifySMSMessage($task, $user_id, $type, '', 0, 0, 0);
		$task_notify->type = $type;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_notify->send_time = $cur_time;

		$task_user = Tasklog::checkUserTask($task_id);
		$task_notify->checking = $task_user . '---' . $user_id;
		$task_notify->save();


		return $task_notify;
	}

	public function followTask($task_id)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task = Task::find($task_id);
		if (empty($task))
			return;

		$task->start_date_time = $cur_time;
		$task->running = 1;
		$task->save();


		if ($task->subtype == 1 || $task->subtype == 2) {
			// task state
			$this->createNewTaskState($task, 0, 0, 0);

			// send notification
			$this->saveNotification($task->dispatcher, $task->id, 'Assignment');

			// save log(Assignment)
			if ($task->dispatcher > 0) {
				$task_log = new Tasklog();
				$task_log->task_id = $task->id;
				$task_log->user_id = $task->dispatcher;
				$task_log->comment = $task->custom_message;
				$task_log->log_type = 'Assignment';
				$task_log->log_time = $cur_time;
				if ($task->status_id == OPENGS)
					$task_log->status = 'Open';

				$task_log->method = 'Following';

				$task_log->save();
			}


			if ($task->type == 2 && $task->requester_notify_flag == 1)	// department request and notify
			{
				//	$this->saveNotification($tasklist[$i]->dispatcher, $tasklist[$i]->id, 'Assignment');
			}
		} else if ($task->subtype == 6)		// managed task
		{
			$this->processOtherRequest($task, 'Assignment');
		}
	}

	public function processOtherRequest($task, $message)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		if (empty($task))
			return;

		if ($task->is_group == 'Y')	// group
		{
			$users = DB::table('common_users as cu')
				->join('common_user_group_members as ugm', 'cu.id', '=', 'ugm.user_id')
				->where('ugm.group_id', $task->group_id)
				->where('cu.deleted', 0)
				->select(DB::raw('cu.*'))
				->get();

			if (!empty($users)) {
				for ($j = 0; $j < count($users); $j++) {
					$this->saveNotification($users[$j]->id, $task->id, $message);
				}
			}

			// save log
			$task_log = new Tasklog();
			$task_log->task_id = $task->id;
			$task_log->user_id = $task->group_id;
			$task_log->comment = $task->custom_message;
			$task_log->log_type = $message;
			$task_log->log_time = $cur_time;
			if ($task->status_id == OPENGS)
				$task_log->status = 'Open';

			$task_log->method = 'Following';

			$task_log->save();
		} else {
			// send notification
			$this->saveNotification($task->user_id, $task->id, $message);

			// save log
			$task_log = new Tasklog();
			$task_log->task_id = $task->id;
			$task_log->user_id = $task->user_id;
			$task_log->comment = $task->custom_message;
			$task_log->log_type = $message;
			$task_log->log_time = $cur_time;

			if ($task->status_id == OPENGS)
				$task_log->status = 'Open';

			$task_log->method = 'Following';

			$task_log->save();
		}
	}

	public function startNextTask($property_id, $task_id, $dispatcher, $location_id)
	{
		$istaskqueued = PropertySetting::isGuestTaskQueued($property_id);
		if ($istaskqueued == 0)
			return -1;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		// check running ticket.
		$last_task = null;
		if ($dispatcher > 0)
			$last_task = $this->getRunningTicket($property_id, $dispatcher, $task_id, $location_id);

		if (!empty($last_task))
			return -1;

		$task_list = DB::table('services_task as st')
			->leftJoin('services_task_state as sts', 'st.id', '=', 'sts.task_id')
			->where('st.dispatcher', $dispatcher)
			->where('st.queued_flag', 1)
			->where('st.id', '!=', $task_id)
			->whereIn('st.status_id', array(OPENGS, ESCALATEDGS))
			->orderBy('sts.elaspse_time', 'desc')
			->orderBy('st.priority', 'desc')
			->select(DB::raw('st.*'))
			->get();

		foreach ($task_list as $row) {
			$task = Task::find($row->id);
			if ($this->isValidTicket($task, $prev) == false)
				continue;

			$task->running = 1;
			$task->queued_flag = 0;
			$task->save();

			$ret = $this->resumeTaskState($task);
			if ($ret == 0)
				break;
		}

		return 0;
	}

	public function resumeTaskState($task)
	{
		$task_state = TaskState::where('task_id', $task->id)->first();
		if (empty($task_state))
			return -1;

		if ($task_state->running == 1)	// already resume
			return -1;

		if ($task_state->status_id != OPENGS &&  $task_state->status_id != ESCALATEDGS)
			return -1;

		// check running ticket.
		$last_task = null;
		if ($task->dispatcher > 0 && $task->status_id != SCHEDULEDGS) {
			if ($this->isQueuableTask($task->task_list))	// check queuable task
				$last_task = $this->getRunningTicket($task->property_id, $task->dispatcher, $task->id, $task->location_id);
		}

		if (!empty($last_task)) {
			// change task priority
			$task->queued_flag = 1;
			$task->running = 0;
			$task->save();

			return -1;
		}
		$task_state->running = 1;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_state->setStartEndTime($task->max_time, $cur_time);

		$task_state->save();

		return 0;
	}

	private function checkOldRunningTicket($cur_date, $cur_time)
	{
		$non_exist_ids = array();
		$setting = array();

		$property_list = DB::table('common_property')
			->get();

		foreach ($property_list as $row1) {
			$tasklist = DB::table('services_task_state as sts')
				->join('services_task as st', 'sts.task_id', '=', 'st.id')
				->leftJoin('services_task_group_members as tgm', 'st.task_list', '=', 'tgm.task_list_id')
				->leftJoin('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
				->whereIn('st.status_id', array(1, 2))	// Open Ticket
				->where('st.property_id', $row1->id)
				->where('sts.running', 0)
				->select(DB::raw('sts.*, tg.hold_timeout'))
				->get();

			foreach ($tasklist as $key => $row) {
				$hold_timeout = $row->hold_timeout;
				$peroid_past_time = date('Y-m-d H:i:s', strtotime("-" . $hold_timeout . " minutes")); // last 24

				if ($row->start_time <= $peroid_past_time) {
					$this->escalateTicket($row, $cur_time, $non_exist_ids);
				}
			}
		}

		return count($non_exist_ids);
	}

	public function sendNotifyToUserGroup($user_group, $task_id, $type)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$prefix_id = ["G", "D", "C", "M", "R"];
		$task = $this->getTaskDetail($task_id);

		$user_group_members = DB::table('common_user_group_members as cugm')
			->join('common_user_group as cug', 'cugm.group_id', '=', 'cug.id')
			->where('cug.id', $user_group)
			->select(DB::raw('cug.*, cugm.user_id'))
			->get();

		foreach ($user_group_members as $key => $value) {
			$send_mode = $value->group_notification_type;

			$task_notify = new TaskNotification();
			$task_notify->task_id = $task_id;
			$task_notify->mode = $send_mode;

			$task_notify->notification = sprintf(
				"ID:" . $prefix_id[$task->type - 1] . "%05d Room:%d Guest:%s %s VIP:%d Priority:%s is %s",
				$task->id,
				$task->room,
				$task->guest_name,
				$task->vip == 1 ? 'RM:VIP' : '',
				$task->vip,
				$task->priority_name,
				$type
			);

			$email_content = $this->getNotifyEmailMessage($task, $value->user_id, $type);

			$task_notify->type = $type;

			$task_notify->send_time = $cur_time;
			$task_notify->user_id = $value->user_id;
			$task_notify->checking = 0;
			$task_notify->save();

			$this->sendNotification($value->user_id, $task_id, '', '', '', $task_notify->notification, $email_content, $send_mode, $task_notify->id, 0, 1);
		}
	}

	public function getLogType($task)
	{
		if ($task->status_id == OPENGS)		// Open
			return 'Assignment';

		if ($task->status_id == SCHEDULEDGS)		// Scheduled
			return 'Scheduled';

		return 'Assignment';
	}

	private function getPrevTaskStateMessage($invalid_task_list)
	{
		foreach ($invalid_task_list as $row) {
			$task = TaskList::find($row->task_list);
			$task_name = $task->task;
			$location = Location::getLocationInfo($row->location_id);

			$row->message = 'Create Task Error';

			if ($row->type == 1)	// guest request
			{
				$room_number = $location->name;
				$ticket_no = sprintf('G%05d', $row->id);

				if ($row->running == 1)	// running
					$row->message = sprintf('%s is already opened for Room %s', $task_name, $room_number);
				else		// hold
					$row->message = sprintf('%s failed to create for Room %s as same request %s is on hold.', $task_name, $room_number, $ticket_no);
			}

			if ($row->type == 2)	// Department request
			{
				$loc_name = $location->name;
				$loc_type = $location->type;
				$ticket_no = sprintf('D%05d', $row->id);

				if ($row->running == 1)	// running
					$row->message = sprintf('%s is already created for %s - %s', $task_name, $loc_type, $loc_name);
				else		// hold
					$row->message = sprintf('%s failed to create for Location %s %s as same request %s is on hold.', $task_name, $loc_type, $loc_name, $ticket_no);
			}
		}

		return $invalid_task_list;
	}

	public function createTaskList(Request $request)
	{

		$user_id = $request->get('user_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$input = $request->all();

		$noassigned_flag = false;

		$ticket_ids = [];
		$ticket_number_id = [];
		$ret = array();

		$task_group_ids = array();

		$ret['message'] = '';
		$invalid_task_list = array();

		foreach($input as $key => $row) {

			// re calc max duration for ending xx:xx:00
			$row['created_time'] = $cur_time;

			if($row['status_id'] != 5)
			{
				$row['start_date_time'] = $cur_time;
			}

			$max_time = Functions::calcDurationForMinute($row['start_date_time'], $row['max_time']);

			// duration based on priority
			$setting['services_request_moderate_time']=0;
			$setting['services_request_high_time']=0;
			$setting['hskp_gs_rush_task'] = 0;


            $setting = PropertySetting::getPropertySettings($row['property_id'],$setting);

			if ($row['priority'] == 3){
				if (!empty($setting['services_request_high_time'])){
					$max_time1 = $max_time - (($max_time * $setting['services_request_high_time'])/100);
					$row['max_time'] = $max_time1;
				}
			}elseif ($row['priority'] == 2){
				if (!empty($setting['services_request_moderate_time'])){
					$max_time1 = $max_time - (($max_time * $setting['services_request_moderate_time'])/100);
					$row['max_time'] = $max_time1;
				}
			}else{
				$row['max_time'] = $max_time;
			}

			if( $row['dispatcher'] < 1 && $row['status_id'] != SCHEDULEDGS  )
			{
				// check unassigned task
				$model = TaskList::find($row['task_list']);
				$taskgroup = $model->taskgroup;
				if( empty($taskgroup) || count($taskgroup) < 1 || $taskgroup[0]->unassigne_flag == 0 ){
					$noassigned_flag = true;
					$row['status_id'] = OPENGS;
				}
				else
				{
					$row['status_id'] = UNASSIGNEDGS;
					// $max_time = Functions::calcDurationForMinute($row['start_date_time'], $taskgroup[0]->start_duration);
					// $row['max_time'] = $max_time;
				}
			}

			if( ($row['status_id'] == OPENGS || $row['status_id'] == UNASSIGNEDGS) && $this->isValidTicket($row, $prev) == false )
			{
				$prev->type = $row['type'];
				$invalid_task_list[] = $prev;
				continue;
			}

			// check running ticket.
			$last_task = null;
			if( $row['dispatcher'] > 0 && $row['status_id'] != SCHEDULEDGS && $row['status_id'] == OPENGS ) {
				if( $this->isQueuableTask($row['task_list']) )	// check queuable task
					$last_task = $this->getRunningTicket($row['property_id'], $row['dispatcher'], 0, $row['location_id']);
			}

			if( !empty($last_task) ) {
				$row['running'] = 0;
				$row['queued_flag'] = 1;
			}

			if($row['attendant'] == 0) {
                $row['attendant_auto'] = 'Guest Chat';
            }

			$task_info = DB::table('services_task_list as tl')
				->join('services_task_group_members as tgm', 'tl.id', '=', 'tgm.task_list_id')
				->join('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
				->leftJoin('services_task_category as tc', 'tl.category_id', '=', 'tc.id')
				->select(DB::raw('tl.id as task_id, tl.task, tc.name as category_name, tg.reassign_flag, tg.reassign_job_role, tg.dept_function as escalation_group, tg.id as tg_id, tg.dept_function, tg.frequency_notification_flag, tg.frequency_job_role_ids, tg.frequency, tg.period'))
				->where('tl.id',$row['task_list'])
				->first();

			$all_dept_setting = DeftFunction::where('dept_id', $row['department_id'])
			 	->where('id', $row['dept_func'])
			 	->first();
			if((($task_info->reassign_flag==1) && ($task_info->reassign_job_role!=''))||(!empty($all_dept_setting) &&($all_dept_setting->all_dept_setting==1)))
			{
				$row['reassigned_flag']=1;
			}

			if($row['type'] == 2)
			{
				$guest = DB::table('common_guest as cg')
					->where('cg.room_id', $row['room'])
					->orderBy('cg.departure', 'desc')
					->orderBy('cg.arrival', 'desc')
					->where('cg.checkout_flag', 'checkin')
					->select(['cg.*'])
					->first();
				if( empty($guest) )
				{
					$row['guest_id'] = 0;

				}
				else
					$row['guest_id'] = $guest->guest_id;
			}


			//$ret['test']=$all_dept_setting->all_dept_setting;
			if($row["custom_message"] === null) $row["custom_message"] = "";
			if($row["feedback_flag"] === null) $row["feedback_flag"] = "";
			$id = DB::table('services_task')->insertGetId($row);

			if( $row['dispatcher'] < 1 && $row['status_id'] != SCHEDULEDGS )
			{
				$this->sendNoStaffMail($id,$task_info);
			}
			if(!empty($setting['hskp_gs_rush_task']) && ($setting['hskp_gs_rush_task']==$row['task_list']) )
			{
				$this->createHSKPTask($row);
			}
			$ticket_ids[] = $id;

			$ticket_number_id[] = array('num' => $key, 'id' => $id);

			$task_group_ids[] = $this->checkFrequency($task_info, $row['location_id'], $row['property_id']);
		}

		$max_id = DB::table('services_task')->max('id');

		$this->createTaskState($ticket_ids, 0, $user_id);
		$this->createSystemTask($ticket_ids);

		if( $noassigned_flag == true )		// no assigned staff
			$this->checkGuestDepartmentTaskState();	// occur escalation

		$ret['max_ticket_no'] = $max_id;
		$ret['count'] = count($ticket_ids);
		$ret['input'] = json_encode($input);
		$ret['task_group_ids'] = $task_group_ids;
		$ret['ticket_number_id'] = $ticket_number_id;

		$ret['invalid_task_list'] = $this->getPrevTaskStateMessage($invalid_task_list);

		return Response::json($ret);
	}

	public function uploadFiles(Request $request) 
	{
        $output_dir = "uploads/picture/";
        $filekey = 'files';
        $id = $request->get('id', 0);

		$fileCount = count($_FILES[$filekey]["name"]);

		$path_array = array();
        for ($i = 0; $i < $fileCount; $i++)
        {
            $fileName = $_FILES[$filekey]["name"][$i];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
			$filename1 = "pic_".$fileName. "_".time().".".strtolower($ext);


            $dest_path = $output_dir . $filename1;
            move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);

			$path_array[] = $dest_path;
        }

        $model = Task::find($id);
        if( !empty($model) )
        {
            $model->picture_path = json_encode($path_array);
            $model->save();
        }

        return Response::json($model);
	}

	public function changeTask(Request $request)
	{
		$task_id = $request->get('task_id', 0);
		$status_id = $request->get('status_id', OPENGS);
		$original_status_id = $request->get('original_status_id', OPENGS);
		$max_time = $request->get('max_time', 10);
		$user_id = $request->get('user_id', 0);
		$comment = $request->get('comment', '');
		$log_type = $request->get('log_type', '');
		$start_date_time = $request->get('start_date_time', '');
		$repeat_flag = $request->get('repeat_flag', 0);
		$repeat_end_date = $request->get('repeat_end_date', '');
		$until_checkout_flag = $request->get('until_checkout_flag', 0);
		$cost = $request->get('cost', 0);

		$source = $request->get('source', 0);	// default web
		if($status_id == 100 && ($log_type!='Canceled')){
			$task = Task::find($task_id);

			$task->custom_message	= $comment;
			$task->save();
			$ret = array();
			$ret['code'] = 200;
			$ret['message'] = 'Successfully commented.';
			return Response::json($ret);
		}
		$status = '';
		$method = '';

		$start1 = microtime(true);

		// update task
		$task = Task::find($task_id);
		if (empty($task)) {
			$ret = array();
			$ret['code'] = 400;
			$ret['ticket'] = $this->getTicketDetail($task_id);
			$ret['message'] = 'Ticket data does not exist';
			return Response::json($ret);
		}

		if( $task->status_id != $original_status_id )
		{
			$ret = array();
			$ret['code'] = 202;
			$ret['ticket'] = $this->getTicketDetail($task_id);
			$ret['message'] = 'Ticket data is not synced';
			return Response::json($ret);
		}

		$start2 = microtime(true);


		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task->status_id = $status_id;

		$save_log_type = $log_type;
		$notify_user_id = $user_id;	// operator

		$task_notify = array();
		if ($status_id == COMPLETEDGS)    // Complete action
		{
			$ret = $this->checkCompleteComment($task, $comment);

			if( !empty($ret) )
				return Response::json($ret);

			$task_notify = $this->changeTaskToComplete($task_id, $user_id, $cur_time, $comment, $cost);	// what task, by who, when complete

			$save_log_type = 'Completed';
			$status = 'Completed';
		}
		else if ($status_id == CANCELEDGS || ($log_type=='Canceled') )    // cancel action
		{
			$task_notify = $this->changeTaskToCancel($task_id, $cur_time, $comment);	// what task, when, why reason
			$save_log_type = 'Canceled';
			$status = 'Canceled';
		}
		else if ($status_id == OPENGS)    // Open
		{
			$assign_id = $request->get('assign_id', 0);
			if( $log_type == 'Assigned' && empty($assign_id) )	// Schedule to Open
			{
				$task_notify = $this->changeTaskFromScheduleToOpen($task_id, $cur_time, $max_time);	// what task, when to start, while during
				if( !empty($task_notify) )
					$notify_user_id = $task_notify->user_id;	// runner

				$save_log_type = 'Modify(Scheduled -> Open)';
				$status = 'Open';
			}
			else if( $log_type == 'Assigned' )	// Open, Escalated to Reassign
			{
				$assign_id = $request->get('assign_id', 0);
				$assigned= CommonUser::find($assign_id);
				$log='Assigned To '.$assigned->first_name.' '.$assigned->last_name;
				//echo $log;
				$task_notify = $this->changeTaskFromReassign($task_id, $cur_time, $assign_id, $max_time, $log);	// what task, when to start, while during
				$save_log_type = 'Modify('.$log.')';
				$status = 'Open';

				$notify_user_id = $user_id;	// assigner
			}
			else if( $log_type == 'Started' )	// Assigned to Open
			{
				$task_notify = $this->changeTaskFromAssignedToStarted($task_id, $cur_time, $max_time);	// what task, when to start, while during
				$save_log_type = 'Modify(Started)';
				$status = 'Open';

				if( !empty($task_notify) )
					$notify_user_id = $task_notify->user_id;	// runner
			}
			else if( $log_type == 'On-Hold' )	// Open to Hold
			{
				$task_notify = $this->changeTaskFromOpenToHold($task_id, $cur_time, $comment, $user_id);	// what task, when to hold, why reason
				$save_log_type = 'Modify(Hold)';
				$status = 'Open';
			}
			else if( $log_type == 'Resume' )	// Hold to Open
			{
				$task_notify = $this->changeTaskOpenToResume($task_id, $cur_time);	// what task, when to resume
				$save_log_type = 'Modify(Resume)';
				$status = 'Open';
			}
			else if( $log_type == 'Extended' )	// Open to Extension
			{
				$extend = $max_time;
				$task_notify = $this->changeTaskToExtended($task_id, $extend + $task->max_time);	// what task, while during
				$save_log_type = sprintf('Modify(Extend %dmins)', round($extend / 60, 0));
				$status = 'Open';
			}
			else if( $log_type == 'Reassigned' || $log_type == 'Reassigned To')	// Open, Escalated to Reassign
			{
				$assign_id = $request->get('assign_id', 0);
				$assigned= CommonUser::find($assign_id);
				$log='Reassigned To '.$assigned->first_name.' '.$assigned->last_name;

				$task_notify = $this->changeTaskFromReassign($task_id, $cur_time, $assign_id, $max_time,$log);	// what task, when to start, while during
				$save_log_type = 'Modify('.$log.')';
				$status = 'Open';

				$notify_user_id = $user_id;	// assigner
			}
			else if( $log_type == 'Assigned To' )	// Open, Escalated to Reassign
			{
				$assign_id = $request->get('assign_id', 0);
				$assigned= CommonUser::find($assign_id);
				$log='Assigned To '.$assigned->first_name.' '.$assigned->last_name;
				//echo $log;
				$task_notify = $this->changeTaskFromReassign($task_id, $cur_time, $assign_id, $max_time, $log);	// what task, when to start, while during
				$save_log_type = 'Modify('.$log.')';
				$status = 'Open';

				$notify_user_id = $user_id;	// assigner
			}
		}
		else if ($status_id == ESCALATEDGS)    // Escalated
		{
			if( $log_type == 'On-Hold' )	// Escalated to Hold
			{
				$task_notify = $this->changeTaskFromEscalatedToHold($task_id, $cur_time, $comment, $user_id);	// what task, when to hold, why reason
				$save_log_type = 'Modify(Hold)';
				$status = 'Escalated';
			}
			else if( $log_type == 'Resume' )	// Hold to Escalated
			{
				$task_notify = $this->changeTaskEscalatedToResume($task_id, $cur_time);	// what task, when to resume
				$save_log_type = 'Modify(Resume)';
				$status = 'Escalated';

				// if( !empty($task_notify) )
				// 	$notify_user_id = $task_notify->user_id;	// runner
			}
		}
		else if ($status_id == SCHEDULEDGS)	// Scheduled
		{
			$task_notify = $this->changeTaskToSchedule($task_id, $start_date_time, $max_time, $repeat_flag, $until_checkout_flag, $repeat_end_date);	// what task, when to start, during
			$save_log_type = 'Modify(Scheduled)';
			$status = 'Scheduled';
		}
		else if ($status_id == TIMEOUTGS)    // cancel action
		{
			if($log_type!='Comment')
			{
			$task_notify = $this->closeOnlyTask($task_id, $user_id, $cur_time);	// what task, by who, when complete
			$save_log_type = 'Modify(Closed)';
			$status = 'Closed';
			}
		}
		else
		{
			$task_notify = $task;
		}

		if( ($status_id == OPENGS || $status_id == ESCALATEDGS) && $task->running == 0 )
			$status = 'Hold';

		if( $source == 0 )
			$method = WEB_SOURCE;
		if( $source == 1 )
			$method = MOBILE_SOURCE;
		elseif( $source == 2 )
			$method = IVR_SOURCE;
		elseif( $source == 3 )
			$method = BOT_SOURCE;
		elseif( $source == 4 )
			$method = ALEXA_SOURCE;

		$start3 = microtime(true);

		// add service_task_log
		$task_log = new Tasklog();
		$task_log->task_id = $task->id;
		$task_log->user_id = $notify_user_id;
		$task_log->comment = $comment;
		$task_log->log_type = $save_log_type;
		$task_log->log_time = $cur_time;
		$task_log->status = $status;
		$task_log->method = $method;

		$task_log->save();

		$ret = array();
		$ret['code'] = 200;
		$ret['ticket'] = $this->getTicketDetail($task_id);
		$ret['task_notify'] = $task_notify;
		$ret['message'] = 'Task is changed successfully';

		$start4 = microtime(true);
		$ret['times'] = [$start2 - $start1, $start3 - $start2, $start4 - $start3];

		return Response::json($ret);
	}

	public function getTicketDetail($id)
	{
		$ticket = DB::table('services_task as st')
				->leftJoin('services_dept_function as df', 'st.dept_func', '=', 'df.id')
				->leftJoin('services_type as ty', 'st.type', '=', 'ty.id')
				->leftJoin('services_priority as sp', 'st.priority', '=', 'sp.id')
				->leftJoin('common_users as cu', 'st.dispatcher', '=', 'cu.id')
				->leftJoin('common_users as cu1', 'st.attendant', '=', 'cu1.id')
				->leftJoin('common_room as cr', 'st.room', '=', 'cr.id')
				->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
				->leftJoin('services_complaints as sc', 'st.complaint_list', '=', 'sc.id')
				->leftJoin('services_complaint_type as ct', 'sc.type_id', '=', 'ct.id')
				->leftJoin('services_compensation as scom', 'st.compensation_id', '=', 'scom.id')
				->leftJoin('common_department as cd', 'df.dept_id', '=', 'cd.id')
			//				->leftJoin('common_guest as cg', 'st.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('st.guest_id', '=', 'cg.guest_id');
					$join->on('st.property_id', '=', 'cg.property_id');
				})
				->leftJoin('common_users as cu2', 'st.user_id', '=', 'cu2.id')
				->leftJoin('common_user_group as cug', 'st.group_id', '=', 'cug.id')
				->leftJoin('services_task_state as ta_st','st.id','=','ta_st.task_id')
				->where('st.id', $id)
				->select(DB::raw('st.*, df.function, sp.priority as priority_name, cu.username, cu1.username as attendant_name, cr.room, 
				tl.task as task_name, sc.complaint, ct.type as ct_type, scom.compensation, scom.cost, cd.department, cg.guest_name,
				 CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cu.mobile as device, 
				 CONCAT_WS(" ", cu2.first_name, cu2.last_name) as manage_user_name, cu2.mobile as manage_user_mobile, 
				 cug.name as manage_user_group, ta_st.elaspse_time,	ta_st.start_time as evt_start_time, ta_st.end_time as evt_end_time'))
				->first();

		if( $ticket->location_id > 0 )
		{
			$info = $this->getLocationInfo($ticket->location_id);
			if( !empty($info) ) {
				$ticket->lgm_name = $info->name;
				$ticket->lgm_type = $info->type;
			}
		}

		return $ticket;
	}

	private function checkCompleteComment($task, $comment) 
	{
		if( empty($task) )
			return null;

		$model = TaskList::find($task->task_list);
		$taskgroup = $model->taskgroup;
		if( empty($taskgroup) || count($taskgroup) < 1 || ($taskgroup[0]->comment_flag == 0 || $taskgroup[0]->comment_flag == 2) )  // no need to input comment	or comment option
		{
			return null;
		}
		else 	// should input comment
		{
			if( empty($comment) )	// empty comment
			{
				$ret = array();
				$ret['code'] = 201;
				$ret['message'] = 'When complete this ticket, you should provide comment.';

				return $ret;
			}
		}

		return null;
	}

	public function changeTaskToComplete($task_id, $user_id, $cur_time, $comment, $cost)
	{
		$ret = array();

		$task = Task::find($task_id);

		$task->status_id	= COMPLETEDGS;		// set complete state
		$task->finisher = $user_id;		// set finisher(most equal dispatcher)
		$task->end_date_time = $cur_time;	// finish time
		$task->running = 0;				// set running to 0
		$task->closed_flag = 1;
		$task->cost = $cost;


		$task_state = TaskState::where('task_id', $task_id)->first();
		if( !empty($task_state) )
			$task->duration = $task->duration + $task_state->getElapseTime();

		$task->save();

		$this->saveMobileNotification($task, 'Completed', $user_id);

		// add services_task_notifications
		$task_notify = $this->saveNotification($user_id, $task->id, 'Completed');		// send notify to finisher

		// update services_task_state table
		// remove task state with this task id
		if( $task->type == 1 || $task->type == 2 || $task->type == 4 )
			DB::table('services_task_state')->where('task_id', $task_id)->delete();
		if( $task->type == 3 )
			DB::table('services_complaint_state')->where('task_id', $task_id)->delete();

		$this->saveSystemNotification($task, 'Complete');

		// start queued task
		$this->startNextTask($task->property_id, $task->id, $task->dispatcher, $task->location_id);

		return $task_notify;
	}

	public function changeTaskToCancel($task_id, $cur_time, $comment)
	{
		$ret = array();

		$task = Task::find($task_id);

		// update services_task
		$task->status_id	= CANCELEDGS;		// set cancel state
		$task->end_date_time = $cur_time;	// cancel time
		$task->running = 0;
		$task->custom_message = $comment; // set running to 0

		$task_state = TaskState::where('task_id', $task_id)->first();
		if( !empty($task_state) )
			$task->duration = $task->duration + $task_state->getElapseTime();

		$task->save();

		// add services_task_notifications
		$task_notify = $this->saveNotification($task->dispatcher, $task->id, 'Cancelled');	// send notify to staff

		// update services_task_state
		// remove task state with this task id
		DB::table('services_task_state')->where('task_id', $task_id)->delete();

		$this->saveSystemNotification($task, 'Cancel');

		$this->startNextTask($task->property_id, $task_id, $task->dispatcher, $task->location_id);

		return $task_notify;
	}

	public function changeTaskFromScheduleToOpen($task_id, $start_time, $max_time)
	{
		$ret = array();

		$task = Task::find($task_id);

		$dispatcher = $this->getStaffForTicket($task_id);

		// update services_task
		$task->status_id	= OPENGS;		// set sechduel task
		$task->start_date_time = $start_time;
		$task->max_time = $max_time;
		$task->dispatcher = $dispatcher;
		$task->running = 1;
		$task->save();

		// add services_task_notifications
		$task_notify = $this->saveNotification($task->dispatcher, $task->id, 'Assignment');	// send notify to staff

		// update services_task_state
		$this->createNewTaskState($task, 0, 0, 0);

		$this->saveSystemNotification($task, 'Open');

		return $task_notify;
	}

	public function changeTaskFromReassign($task_id, $start_time, $assign_id, $max_time,$log)
	{
		$ret = array();

		// remove old tracking task state
		TaskState::where('task_id', $task_id)->delete();

		$max_time = Functions::calcDurationForMinute($start_time, $max_time);

		$task = Task::find($task_id);

		$old_dispatcher = $task->dispatcher;

		// update services_task
		$task->status_id	= OPENGS;		// set open task
		$task->start_date_time = $start_time;
		$task->max_time = $max_time;
		$task->dispatcher = $assign_id;
		$task->duration = 0;

		// check running ticket.
		$last_task = null;
		if( $task->dispatcher > 0 )
		{
			if( $this->isQueuableTask($task->task_list) )	// check queuable task
				$last_task = $this->getRunningTicket($task->property_id, $task->dispatcher, $task->id, $task->location_id);
		}

		if( !empty($last_task) )
		{
			// change task priority
			$task->queued_flag = 1;
			$task->running = 0;
		}
		else
		{
			$task->running = 1;
			$task->queued_flag = 0;
		}

		$task->save();

		// add services_task_notifications
		$task_notify = $this->saveNotification($task->dispatcher, $task->id, $log);	// send notify to staff

		// send notify old dispatcher
		$this->saveNotification($old_dispatcher, $task->id, $log);	// send notify to staff

		// update services_task_state
		$this->createNewTaskState($task, 0, 0, 0);

		$this->saveSystemNotification($task, $log);

		return $task_notify;
	}

	public function changeTaskFromAssignedToStarted($task_id, $start_time, $max_time)
	{
		$ret = array();

		// remove old tracking task state
		$task_state = TaskState::where('task_id', $task_id)->delete();

		$max_time = Functions::calcDurationForMinute($start_time, $max_time);

		$task = Task::find($task_id);

		// update services_task
		$task->status_id	= OPENGS;		// set sechduel task
		$task->start_date_time = $start_time;
		$task->max_time = $max_time;

		// check running ticket.
		$last_task = null;
		if( $task->dispatcher > 0 )
		{
			if( $this->isQueuableTask($task->task_list) )	// check queuable task
				$last_task = $this->getRunningTicket($task->property_id, $task->dispatcher, $task->id, $task->location_id);
		}

		if( !empty($last_task) )
		{
			// change task priority
			$task->queued_flag = 1;
			$task->running = 0;
		}
		else
		{
			$task->running = 1;
			$task->queued_flag = 0;
		}

		$task->save();

		// add services_task_notifications
		$task_notify = $this->saveNotification($task->dispatcher, $task->id, 'Started');	// send notify to staff

		// update services_task_state
		$this->createNewTaskState($task, 0, 0, 0);

		$this->saveSystemNotification($task, 'Started');

		return $task_notify;
	}

	public function changeTaskFromOpenToHold($task_id, $cur_time, $comment, $user_id)
	{
		$ret = array();

		$task = Task::find($task_id);

		// update services_task
		$task->running = 0;
		//	$task->custom_message = $comment;
		$task->save();

		$this->saveMobileNotification($task, 'Hold', $user_id);

		// add services_task_notifications
		$task_notify = $this->saveNotification($task->dispatcher, $task->id, 'Hold');	// send notify to staff

		// update services_task_state
		$this->holdTaskState($task);

		$this->saveSystemNotification($task, 'Hold');

		return $task_notify;
	}

	public function holdTaskState($task)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_state = TaskState::where('task_id', $task->id)->first();
		if( empty($task_state) )
			return array();

		if( $task_state->running != 1 )	// already hold
			return array();

		if( $task_state->status_id != OPENGS &&  $task_state->status_id != ESCALATEDGS )
			return array();

		$task_state->elaspse_time = $task_state->getElapseTime();
		$task_state->start_time = $cur_time; // added to display the stop status when hold event handler
		$task_state->running = 0;

		$task_state->save();

		$this->startNextTask($task->property_id, $task->id, $task->dispatcher, $task->location_id);
	}

	public function changeTaskOpenToResume($task_id, $cur_time)
	{
		$ret = array();

		$task = Task::find($task_id);

		// update services_task
		$task->running = 1;
		$task->hold_reminder_flag = 0;
		$task->save();

		// add services_task_notifications
		$task_notify = $this->saveNotification($task->dispatcher, $task->id, 'Resume');	// send notify to staff

		// update services_task_state
		$ret = $this->resumeTaskState($task);

		if( $ret == 0 )
			$this->saveSystemNotification($task, 'Resume');

		return $task_notify;
	}

	public function changeTaskToExtended($task_id, $max_time)
	{
		$ret = array();

		$task = Task::find($task_id);

		// update services_task
		$task->status_id	= OPENGS;		// set sechduel task
		$task->max_time = $max_time;
		$task->running = 1;
		$task->save();

		// add services_task_notifications
		$task_notify = $this->saveNotification($task->dispatcher, $task->id, 'Extend');	// send notify to staff

		// update services_task_state
		$task_state = TaskState::where('task_id', $task_id)->first();
		if( !empty($task_state) )
		{
			$task_state->setStartEndTime($task->max_time, $task_state->start_time);
			$task_state->save();
		}

		$this->saveSystemNotification($task, 'Extended');

		return $task_notify;
	}

	public function changeTaskFromEscalatedToHold($task_id, $cur_time, $comment, $user_id)
	{
		$ret = array();

		$task = Task::find($task_id);

		// update services_task
		$task->running = 0;
		$task->custom_message = $comment;
		$task->save();

		$this->saveMobileNotification($task, 'Hold', $user_id);

		// add services_task_notifications
		$task_notify = $this->saveNotification($task->dispatcher, $task->id, 'Hold');	// send notify to staff

		// update services_task_state
		$this->holdTaskState($task);

		$this->saveSystemNotification($task, 'Hold');

		return $task_notify;
	}

	public function changeTaskEscalatedToResume($task_id, $cur_time)
	{
		$ret = array();

		$task = Task::find($task_id);

		// update services_task
		$task->running = 1;
		$task->hold_reminder_flag = 0;
		$task->save();

		// add services_task_notifications
		$task_notify = $this->saveNotification($task->dispatcher, $task->id, 'Resume');	// send notify to staff

		// update services_task_state

		$ret = $this->resumeTaskState($task);

		if( $ret == 0 )
			$this->saveSystemNotification($task, 'Resume');

		return $task_notify;
	}

	public function changeTaskToSchedule($task_id, $start_date_time, $max_time, $repeat_flag, $until_checkout_flag, $repeat_end_date)
	{
		$ret = array();

		$task = Task::find($task_id);

		// update services_task
		$task->status_id	= SCHEDULEDGS;		// set sechduel task
		$task->start_date_time = $start_date_time;
		$task->max_time = $max_time;
		$task->running = 0;
		$task->repeat_flag = $repeat_flag;
		$task->until_checkout_flag = $until_checkout_flag;
		$task->repeat_end_date = $repeat_end_date;

		$task->save();

		// add services_task_notifications
		$task_notify = $this->saveNotification($task->dispatcher, $task->id, 'Scheduled');	// send notify to staff

		// update services_task_state
		$this->createNewTaskState($task, 0, 0, 0);

		$this->saveSystemNotification($task, 'Scheduled');

		return $task_notify;
	}

	public function closeOnlyTask($task_id, $user_id, $cur_time)
	{
		$ret = array();

		$task = Task::find($task_id);

		$task->finisher = $user_id;		// set finisher(most equal dispatcher)

		$task->running = 0;				// set running to 0
		$task->closed_flag = 1;

		$task->duration = $task->duration + strtotime($cur_time) - strtotime($task->end_date_time);
		$task->end_date_time = $cur_time;	// finish time

		$task->save();

		// add services_task_notifications
		$task_notify = $this->saveNotification($user_id, $task->id, 'Closed');		// send notify to finisher

		$this->saveSystemNotification($task, 'Closed');

		return $task_notify;
	}

	public function changeFeedback(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_id = $request->get('task_id', 0);
		$user_id = $request->get('user_id', 0);


		$feedback_flag = $request->get('feedback_flag', 0);
		$choice = $request->get('choice', 0);
		$comment = $request->get('comment', '');

		$ret = array();

		$task = Task::find($task_id);
		if (empty($task)) {
			$ret = array();
			$ret['code'] = 400;
			$ret['ticket'] = $this->getTicketDetail($task_id);
			$ret['message'] = 'Ticket data does not exist';
			return Response::json($ret);
		}

		$task->feedback_flag = $feedback_flag;
		$task->feedback_type = $choice;
		$task->guest_feedback = $comment;
		if($comment!='')
		{
			$task->feedback_closed_flag = 1;
		}

		$task->save();

		$task_log = new Tasklog();
		$task_log->task_id = $task_id;
		$task_log->user_id = $user_id;
		$task_log->comment = $comment;
		$task_log->log_type = 'Feedback';
		$task_log->log_time = $cur_time;
		$task_log->status = 'Feedback';
		$task_log->method = 'Web';

		$task_log->save();

		$ret['code'] = 200;
		$ret['ticket'] = $this->getTicketDetail($task_id);
		$ret['message'] = 'Task is changed successfully';

		return Response::json($ret);
	}

	public function getTaskMessage(Request $request) 
	{
		$task_id = $request->get('task_id' ,0) ;

		$messagelist = DB::table('services_task_log as stl')
			->leftJoin('common_users as cu', 'stl.user_id', '=', 'cu.id')
			->where('stl.task_id', $task_id )
			->orderBy('stl.id','desc')
			->select(DB::raw('stl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();

		//		$notificationlist = DB::table('services_task_notifications as stn')
		//            ->leftJoin('common_users as cu', 'stn.user_id', '=', 'cu.id')
		//            ->where('stn.task_id', $task_id )
		//            ->orderBy('stn.id','desc')
		//            ->select(DB::raw('stn.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
		//            ->get();
		$ret = array();
		$ret['code'] = 200;
		$ret['messagelist'] = $messagelist;
		//		$ret['notificationlist'] = $notificationlist;

		return Response::json($ret);
	}

	public function getNotificationHistoryList(Request $request)
	{
		$task_id = $request->get('task_id', 0);
		$page = $request->get('page', 1);
		$pageSize = $request->get('pagesize', 20);
		$skip = $pageSize * ($page - 1);
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$ticketlist = DB::table('services_task_notifications as tn')
				->leftJoin('services_task as st', 'tn.task_id', '=', 'st.id')
				->leftJoin('common_users as cu', 'tn.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->where('tn.task_id', $task_id)
				->orderBy($orderby, $sort)
				->select(DB::raw('tn.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as staff'))
				->skip($skip)->take($pageSize)
				->get();

		$totalcount = DB::table('services_task_notifications as tn')
				->leftJoin('services_task as st', 'tn.task_id', '=', 'st.id')
				->leftJoin('common_users as cu', 'tn.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->where('tn.task_id', $task_id)
				->count();

		$ret['datalist'] = $ticketlist;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}
	public function updateGuestFeedback(Request $request) 
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);
		$message = $request->get('guest_feedback', '');

		$task = Task::find($id);
		if( !empty($task) )
		{
			$task->guest_feedback = $message;
			$task->save();
		}

		$task->save();

		$task_log = new Tasklog();
		$task_log->task_id = $id;
		$task_log->user_id = $user_id;
		$task_log->comment = $message;
		$task_log->log_type = 'Feedback';
		$task_log->log_time = $cur_time;
		$task_log->status = 'Feedback';
		$task_log->method = 'Web';

		$task_log->save();

		return Response::json($task);
	}

	public function getTaskInfoWithReassign(Request $request)
	{
		$task_id = $request->get('task_id', '0');
		$location_id = $request->get('location_id', '0');

		$ret = $this->getTaskShiftInfoReassign($task_id, $location_id,0);

		return Response::json($ret);
	}

	private function getTaskShiftInfoReassign($task_id, $location_id,$assign_flag) 
	{

		// find department function
		$ret = array();
		$model = TaskList::find($task_id);
		$taskgroup = $model->taskgroup;
		if( empty($taskgroup) || count($taskgroup) < 1 ){
			$ret['code'] = 201;
			$ret['message'] = 'No task group';
			return $ret;
		}

		$task = $taskgroup[0];
		return $this->getTaskShiftInfoDataReassign($task_id, $task, $location_id,$assign_flag);
	}

	private function getTaskShiftInfoDataReassign($task_id, $taskgroup, $location, $assign_flag)
	{
		$ret = array();

		// // find department function
		// $model = TaskList::find($task_id);

		$task = $taskgroup;
		$ret['taskgroup'] = $task;

		$dept_func_id = $task->dept_function;

		$dept_func = DeftFunction::find($dept_func_id);
		if( empty($dept_func) )
			return $ret;

		// find building id
		$building_id = Location::find($location)->building_id;

		// find job role for level = 0
		$escalation = Escalation::where('escalation_group', $dept_func_id)
				->where('level', 0)
				->first();

		$job_role_id = 0;

		if( !empty($escalation) )
			$job_role_id = $escalation->job_role_id;


		$ret['deptfunc'] = $dept_func;

		// find department and property
		$department = Department::find($dept_func->dept_id);
		$ret['department'] = $department;

		date_default_timezone_set(config('app.timezone'));
		$datetime = date('Y-m-d H:i:s');

		// find staff list

		//Check if gs_device_based
		$all_dept_setting = DeftFunction::where('dept_id', $department->id)
				->where('id', $dept_func_id)
				->first();


		if($assign_flag==0 && ($all_dept_setting->all_dept_setting)==0)
		{
			$setting = DeftFunction::getGSDeviceSetting($department->id,$dept_func_id);
			if($setting == 0)
			{
				// find staff list
				$shift_group_members = ShiftUser::getUserlistOnCurrentShift($job_role_id, $dept_func_id, $task->id, $location, $building_id, true);
			}
			elseif ($setting == 1) {
				$shift_group_members = ShiftUser::getDevicelistOnCurrentShift(0, $dept_func_id, $location, $building_id, true);
			}
			elseif ($setting == 2) {
				$loc_type = LocationType::createOrFind('Room');
				$location_room = Location::where('id', $location)
						->where('type_id', $loc_type->id)
						->select('room_id')
						->first();
				if( !empty($location_room) )
					$shift_group_members = RosterList::getRosterListFromRoomDeptFunc($dept_func->id, $location_room->room_id);
			}
		}
		else {
			$shift_group_members = ShiftGroupMember::getAllDeptUserlistOnCurrentActive($department->property_id, $dept_func->dept_id);
		}

		// sort active staff by complete time
		$time = array();
		foreach ($shift_group_members as $key => $row)
		{
			// calculate max complete time for each staff
			$assigned_flag = Task::whereRaw('DATE(start_date_time) = CURDATE()')
					->where('dispatcher', $row->user_id)
					->where(function ($query) use ($datetime, $task) {
						$query->whereIn('status_id', array(OPENGS, ESCALATEDGS))
								->orWhere(function ($subquery) use ($datetime, $task) {	// vacation period
									$subquery->where('status_id', SCHEDULEDGS)
											->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) < $task->max_time");
								});
					})
					->exists();

			if( $assigned_flag == false )	// free staff
			{
				// calcuate spent time for free staff
				$spent = DB::table('services_task')
						->whereRaw('DATE(start_date_time) = CURDATE()')
						->where('dispatcher', $row->user_id)
						->where(function ($query) use ($datetime, $task) {
							$query->whereIn('status_id', array(COMPLETEDGS, TIMEOUTGS, CANCELEDGS))
									->orWhere(function ($subquery) use ($datetime, $task) {	// vacation period
										$subquery->where('status_id', SCHEDULEDGS)
												->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) >= $task->max_time");
									});
						})
						->select(DB::raw('sum(duration) AS spent'))
						->first();

				if( empty($spent) )
					$difftime = 0;
				else
					$difftime = $spent->spent;

				$time[$key] = $difftime;
				$shift_group_members[$key]->spent = $difftime;
				$shift_group_members[$key]->assigned = false;
			}
			else	// active staff
			{
				// calcuate max complete time for active staff
				$completetime = DB::table('services_task')
						->whereRaw('DATE(start_date_time) = CURDATE()')
						->where('dispatcher', $row->user_id)
						->where(function ($query) use ($datetime, $task) {
							$query->whereIn('status_id', array(OPENGS, ESCALATEDGS))
									->orWhere(function ($subquery) use ($datetime, $task) {	// vacation period
										$subquery->where('status_id', SCHEDULEDGS)
												->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) < $task->max_time");
									});
						})
						->orderBy('complete', 'desc')
						->select(DB::raw('max(TIME_TO_SEC(start_date_time) + max_time) AS complete'))
						->first();

				$time[$key] = $completetime->complete + 60 * 24 * 265;	// 1 year +
				$shift_group_members[$key]->spent = $time[$key];
				$shift_group_members[$key]->assigned = true;
			}
		}
		
		array_multisort($time, SORT_ASC, $shift_group_members->toArray());

		$ret['staff_list'] = $shift_group_members;
		$ret['reassign_flag'] = 1;
		// $ret['staff_list'] = array();

		$prioritylist = Priority::all();
		$ret['code'] = 200;
		$ret['prioritylist'] = $prioritylist;
		$ret['shift_group_members'] = $shift_group_members;

		return $ret;
	}

	public function resendMessage(Request $request)
	{
		$task_id = $request->get('id' ,0);
		$user_id = $request->get('dispatcher',0);
		$type = 'Assignment';
		$task_notify = $this->saveNotification($user_id, $task_id, $type);

		return Response::json($task_notify);
	}

	public function getRepeatedList(Request $request)
	{
		$start = microtime(true);

		$input = $request->all();

		$property_id = $request->get('property_id', 0);
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');

		//echo $filtername;
		$attendant = $request->get('attendant', 0);
		$lang = $request->get('lang', 0);
		// echo $lang;
		$dispatcher = $request->get('dispatcher', 0);
		$searchoption = $request->get('searchoption', '');

		$dept_id = CommonUser::getDeptID($attendant, Config::get('constants.GUESTSERVICE_DEPT_VIEW'));
		if($dept_id == 0)
			$dept_id = CommonUser::getDeptID($attendant, Config::get('constants.GUESTSERVICE_DEPT'));

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$yesterday_time = date('Y-m-d 00:00:00',strtotime("-1 days")); // last 24
		$cur_date = date('Y-m-d');
		$yesterday_date = date('Y-m-d',strtotime("-1 days"));

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('services_task as st')
				->leftJoin('services_dept_function as df', 'st.dept_func', '=', 'df.id')
				->leftJoin('services_type as ty', 'st.type', '=', 'ty.id')
				->leftJoin('services_priority as sp', 'st.priority', '=', 'sp.id')
				->leftJoin('common_users as cu', 'st.dispatcher', '=', 'cu.id')
				->leftJoin('common_users as cu1', 'st.attendant', '=', 'cu1.id')
				->leftJoin('common_room as cr', 'st.room', '=', 'cr.id')
				->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
				->leftJoin('services_complaints as sc', 'st.complaint_list', '=', 'sc.id')
				->leftJoin('services_complaint_type as ct', 'sc.type_id', '=', 'ct.id')
				->leftJoin('services_compensation as scom', 'st.compensation_id', '=', 'scom.id')
				->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id')
				->leftJoin('common_users as cu2', 'st.user_id', '=', 'cu2.id')
                ->leftJoin('common_user_group as cug', 'st.group_id', '=', 'cug.id')
				->leftJoin('services_location as sl', 'st.location_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
				->leftJoin('services_task_state as ta_st', 'st.id', '=', 'ta_st.task_id')
			//				->leftJoin('common_guest as cg', 'st.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('st.guest_id', '=', 'cg.guest_id');
					$join->on('st.property_id', '=', 'cg.property_id');
						})

				// ->leftJoin('services_task_feedback as stf', 'stf.task_id', '=', 'st.id')
				->leftJoin('services_devices as sd', 'sd.device_id', '=', 'cu.device_id')
				->where('st.status_id' , 5)
				->orwhere('st.repeat_flag' , 1);



			$data_query = clone $query;

			$where = sprintf("df.`function` like '%%%s%%' or
								st.start_date_time like '%%%s%%' or
								CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%%%s%%' or
								CONCAT_WS(\" \", cu1.first_name, cu1.last_name) like '%%%s%%' or
								CONCAT_WS(\" \", cu2.first_name, cu2.last_name) like '%%%s%%' or
								cr.room like '%%%s%%' or
								st.start_date_time like '%%%s%%' or
								tl.task like '%%%s%%' or
								sc.complaint like '%%%s%%' or
								st.quantity like '%%%s%%' or
								cg.guest_name like '%%%s%%' or
								sp.priority like '%%%s%%' or
								cd.department like '%%%s%%' or
								slt.type like '%%%s%%' or
								st.id like '%%%s%%'",
					$searchoption, $searchoption, $searchoption, $searchoption,
					$searchoption, $searchoption, $searchoption,$searchoption,
					$searchoption, $searchoption, $searchoption, $searchoption,
					$searchoption,
					$searchoption,
					$searchoption,
					$searchoption);

		$ticketquery= clone $data_query;
		$ticketlist = $ticketquery
			->orderBy($orderby, $sort)
			->select(DB::raw('st.*, df.function, df.gs_device, sp.priority as priority_name, cu.username, 
					CONCAT_WS(" ", cu1.first_name, cu1.last_name) as attendant_name, cr.room, tl.task as task_name, tl.lang, tl.type as task_type, 
					sc.complaint, ct.type as ct_type, scom.compensation, scom.cost, cd.department, cd.short_code as dept_short_code, 
					CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cg.guest_name,
					cu.mobile as device, CONCAT_WS(" ", cu2.first_name, cu2.last_name) as manage_user_name, 
					cu2.mobile as manage_user_mobile, cug.name as manage_user_group, ta_st.elaspse_time,
					ta_st.start_time as evt_start_time, ta_st.end_time as evt_end_time, 1 as cancel_enable,
					sl.name as lgm_name, slt.type as lgm_type,
					(CASE WHEN st.type = 1 THEN CONCAT_WS("", "G", st.id) ELSE (CASE WHEN st.type = 2 THEN CONCAT_WS("", "D", st.id) ELSE (CASE WHEN st.type = 3 THEN CONCAT_WS("", "C", st.id) ELSE (CASE WHEN st.type = 4 THEN CONCAT_WS("", "M", st.id) ELSE (CASE WHEN st.type = 5 THEN CONCAT_WS("", "R", st.id) ELSE 0 END) END) END) END) END) AS typenum, 
					sd.number'))
			->distinct('st.id')
			->skip($skip)->take($pageSize)
			->get();

		foreach($ticketlist as  $key => $row)
		{
			if(($row->gs_device)==1)
			 	$row->device=$row->number;
			if($lang!=0 && $row->lang)
			{
				$languages=json_decode($row->lang);
				foreach ($languages as $key_l=>$val_l)
				{
					if(($val_l->id == $lang) && $val_l->text )
					{
						$row->task_name=$val_l->text;
					}
				}
			}
		}

		for($i = 0; $i < count($ticketlist); $i++ )
		{
			// get task group information
			$task_group = DB::table('services_task_group_members as tgm')
				->join('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
				->where('tgm.task_list_id', $ticketlist[$i]->task_list)
				->select(DB::raw('tg.*'))
				->first();

			$ticketlist[$i]->cur_time = $cur_time;

			$ticketlist[$i]->comment_flag = 0;
			if( !empty($task_group) )
			{
				$ticketlist[$i]->comment_flag = $task_group->comment_flag;
				$ticketlist[$i]->unassigne_flag = $task_group->unassigne_flag;
			}
			else
			{
				$ticketlist[$i]->unassigne_flag = 0;
			}
		}

		$count_query = clone $data_query;
		$totalcount = $count_query->count();

		$ret['ticketlist'] = $ticketlist;
		$ret['totalcount'] = $totalcount;
		//	$ret['where'] = $where;
		$ret['dept_id'] = $dept_id;

		$end = microtime(true);
		$ret['time'] = $end - $start;

		return Response::json($ret);
	}

	public function cancelRepeat(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_id = $request->get('task_id', 0);
		$user_id = $request->get('user_id', 0);





		$ret = array();

		$task = Task::find($task_id);
		if (empty($task)) {
			$ret = array();
			$ret['code'] = 400;
			$ret['ticket'] = $this->getTicketDetail($task_id);
			$ret['message'] = 'Ticket data does not exist';
			return Response::json($ret);
		}

		$task->repeat_flag = 0;

		$task->save();

		$task_log = new Tasklog();
		$task_log->task_id = $task_id;
		$task_log->user_id = $user_id;
		$task_log->comment = 'Repeat has been Canceled';
		$task_log->log_type = 'Cancel Repeat';
		$task_log->log_time = $cur_time;
		$task_log->status = 'Cancel Repeat';
		$task_log->method = 'Web';

		$task_log->save();

		$ret['code'] = 200;
		$ret['ticket'] = $this->getTicketDetail($task_id);
		$ret['message'] = 'Task is changed successfully';

		return Response::json($ret);
	}

	public function getLocationTotalListData($filter, $client_id)
	{
		$ret = DB::table('services_location as sl')
			->join('common_property as cp', 'sl.property_id', '=', 'cp.id')
			->join('services_location_type as lt', 'sl.type_id', '=', 'lt.id')
			->where('sl.name', 'like', $filter)
			->select(DB::Raw('sl.id, sl.name, sl.property_id, sl.id as lg_id, lt.type, cp.name as property'))
			->groupBy('sl.name')
			->groupBy('sl.type_id')
			->get();

		return $ret;
	}

	public function addTask(Request $request) 
	{
		$property_id = $request->get("task" ,'');
		$user_id = $request->get("user_id" ,'');
		$task = $request->get("task" ,'');
		$type = $request->get('type', 0);

		$task_list = new TaskList();
		$task_list->task = $task;
		$task_list->type = $type;
		$task_list->created_by = $user_id;
		$task_list->user_created = 1;
		$task_list->save();

		$task_list->property_id = $property_id;

		$ret = array();
		$ret['task'] = $task_list;

		return Response::json($ret);
	}

	public function createManagedTaskList(Request $request)
	{
		$user_id = $request->get('user_id', 0);
		$source = $request->get('source', 0);

		$input = $request->all();

		$prev_max_id = DB::table('services_task')->max('id');

		$noassigned_flag = false;

		foreach($input as $row) {


			if( !empty($row['dispatcher']) && $row['dispatcher'] < 1 )
				$noassigned_flag = true;
			$row['created_time'] = date('Y-m-d H:i:s');
            DB::table('services_task')->insert($row);
		}

		$max_id = DB::table('services_task')->max('id');

		$this->createManagedTaskState($prev_max_id + 1, $max_id, $source, $user_id);

		if( $noassigned_flag == true )
			$this->checkGuestDepartmentTaskState();

		$ret = array();
		$ret['max_ticket_no'] = $max_id;
		$ret['count'] = $max_id - $prev_max_id;

		return Response::json($ret);
	}

	public function createManagedTaskState($start, $end, $source, $created_by)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		// create task state table
		$tasklist = DB::table('services_task as st')
				->leftJoin('services_task_group_members as tgm', 'st.task_list', '=', 'tgm.task_list_id')
				->leftJoin('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
				->whereBetween('st.id', array($start, $end))
				->select(DB::raw('st.*, tg.dept_function as escalation_group, tg.id as tg_id'))
				->get();

		for($i = 0; $i < count($tasklist); $i++ )
		{
			$task = Task::find($tasklist[$i]->id);
			if( $i < count($tasklist) - 1 ) {
				$task->follow_id = $tasklist[$i + 1]->id;
			}
			if( $i == 0 )
				$task->running = 1;
			else {
				$task->running = 0;
			}

			$task->save();

			if( $i == 0 )
			{
				if( $task->subtype == 1 || $task->subtype == 2 )
				{
					// task state
					$this->createNewTaskState($tasklist[$i], 0, $tasklist[$i]->tg_id, $tasklist[$i]->escalation_group);

					// send notification
					$task_notify = $this->saveNotification($tasklist[$i]->dispatcher, $tasklist[$i]->id, 'Assignment');

					// save log(Created)
					$task_log = new Tasklog();
					$task_log->task_id = $tasklist[$i]->id;
					$task_log->user_id = $created_by;
					$task_log->comment = $tasklist[$i]->custom_message;
					$task_log->log_type = 'Created';
					$task_log->log_time = $cur_time;
					$task_log->status = 'Created';

					if( $source == 0 )
					$task_log->method = WEB_SOURCE;
					else if( $source == 1 )
					$task_log->method = MOBILE_SOURCE;
					elseif( $source == 2 )
					$task_log->method = IVR_SOURCE;
					elseif( $source == 3 )
					$task_log->method = BOT_SOURCE;
					elseif( $source == 4 )
					$task_log->method = ALEXA_SOURCE;

					$task_log->save();

					// save log(Assignment)
					if( $tasklist[$i]->dispatcher > 0 )
					{
						$task_log = new Tasklog();
						$task_log->task_id = $tasklist[$i]->id;
						$task_log->user_id = $tasklist[$i]->dispatcher;
						$task_log->comment = $tasklist[$i]->custom_message;
						$task_log->log_type = $this->getLogType($tasklist[$i]);
						$task_log->log_time = $cur_time;
						if( $tasklist[$i]->status_id == OPENGS )
							$task_log->status = 'Open';

						if( !empty($task_notify) )
							$method = $task_notify->mode;
						$task_log->method = $method;

						if( !empty($task_notify) )
							$task_log->notify_id = $task_notify->id;

						$task_log->save();
					}


					if( $tasklist[$i]->type == 2 && $tasklist[$i]->requester_notify_flag == 1 )	// department request and notify
					{
						//	$this->saveNotification($tasklist[$i]->dispatcher, $tasklist[$i]->id, 'Assignment');
					}
				}
				else if( $task->subtype == 6 )		// managed task
				{
					$this->processOtherRequest($task, 'Group Assignment');
				}
			}
		}
	}

	public function getGuestInfoList(Request $request) 
	{
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$checkout_flag = $request->get('checkout_flag', 'All');
		$searchoption = $request->get('searchoption','');
		$filter = $request->get('filter','');

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('common_guest as cg')
				->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('common_vip_codes as vc', 'vc.vip_code', '=', 'cg.vip')
				->leftJoin('common_guest_advanced_detail as gad', 'cg.id', '=', 'gad.id')
				->where('cb.property_id', $property_id);

		// get building ids
		$user_id = $request->get('user_id', 0);
		$building_ids = CommonUser::getBuildingIds($user_id);

		if( !empty($building_ids) )
		{
			$building_ids = explode(',', $building_ids);
			$query->whereIn('cf.bldg_id', $building_ids);
		}

		if( $filter != 'Total' )
			{
			if( $filter == 1 )	// On Route
				$query->where('cg.checkout_flag', 'checkin');
			if( $filter == 2 )
				$query->where('cg.checkout_flag', 'checkout');
			if( $filter == 3 )
				$query->where('cg.fac_flag', 1);
			}


		if($searchoption == '') {

			if( $checkout_flag != 'All')
				$query->where('checkout_flag', $checkout_flag);

		}else {
			if( $checkout_flag != 'All')
				$query->where('checkout_flag', $checkout_flag);
			$where = sprintf(" (cr.room like '%%%s%%' or								
								cg.guest_name like '%%%s%%' or								
								cg.arrival like '%%%s%%' or
								cg.departure like '%%%s%%' or
								cg.vip like '%%%s%%' or
								vc.name like '%%%s%%' or
								cg.guest_id like '%%%s%%')",
				$searchoption, $searchoption, $searchoption, $searchoption, $searchoption, $searchoption,$searchoption
			);
			$query->whereRaw($where);

		}


		$data_query = clone $query;

		$detail = new GuestAdvancedDetail();
		$attrs = array_slice($detail->getTableColumns(), 4, -2);

		$column = '';
        foreach($attrs as $i => $key)
        {
        	if($i > 0)
        		$column .= ',';
        	$column .=  'gad.' . $key;
        }

		$alarm_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('cg.*, vc.name as vip_name, cb.name as building, cr.room, ' . $column))
				->skip($skip)->take($pageSize)
				->get();

				foreach($alarm_list as $key => $row) {
			$alarm_list[$key]->active_fac = GuestLog::activeList($row->id);
		}

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['guestlist'] = $alarm_list;
		$ret['totalcount'] = $totalcount;
		$ret['column'] = $column;

		return Response::json($ret);

	}

	public function getGuestSMSHisotry(Request $request) 
	{
		$guest_id = $request->get('id' , 0) ;
		$history_list = DB::table('common_guest_sms_history as cg')
			->leftJoin('common_users as cu', 'cg.user_id', '=', 'cu.id')
			->where('cg.guest_id', $guest_id)
			->select(DB::raw('cg.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as username'))
			->get();
		$ret = array();
		$ret['history'] = $history_list;
		return Response::json($ret);
	}

	public function sendGuestSMS(Request $request) 
	{
		$guest_id = $request->get('id',0);
		$property_id = $request->get('property_id' , 0);
		$user_id = $request->get('user_id' , 0);
		$data = $this->sendToGuestSMS($guest_id,$property_id, $user_id);

		return Response::json($data);
	}

	public function sendToGuestSMS($guest_id, $property_id, $user_id) 
	{

		$send_flag = true ;
		$guest_data = DB::table('common_guest')
			->where('id', $guest_id)
			->where('property_id', $property_id)
			->select(DB::raw('*'))
			->first();
		if(empty($guest_data))
			return '0';

		$mobile = $guest_data->mobile;
		$guest_name = $guest_data->guest_name;
		$ack = $guest_data->ack;
		if ($user_id == 0) {
			if ($ack == 2) $send_flag = false;
		}

		if( $send_flag == false )
			return '0';

		$settings = PropertySetting::getGuestServiceSetting($property_id);
		$sms_flag = $settings['send_sms_to_guest'];

		if($sms_flag == 'ON' ) {

			$property = DB::table('common_property')
				->where('id', $property_id)
				->select(DB::raw('*'))
				->first();
			$property_name = $property->name;

			$guest = (object)array();
			$guest->guest_name = $guest_name;
			$guest->mobile = $mobile;
			$guest->property_name = $property_name;
			$data = array();
			$data['property_id'] = $property_id;
			$data['guest'] = $guest;
			$content = GuestSmsTemplate::generateTemplate($data);

			date_default_timezone_set(config('app.timezone'));
			$datetime = date('Y-m-d H:i:s');

			if (!empty($mobile)) {
				$input = array();
				$input['guest_id'] = $guest_id;
				$input['number'] = $mobile;
				$input['status'] = 0;
				$input['user_id'] = $user_id;
				$input['created_at'] = $datetime;
				$id = DB::table('common_guest_sms_history')
					->insertGetId($input);
				$payload = array();
				$payload['ack'] = 1;
				//$payload['table_name'] = 'common_guest';
				$payload['table_name'] = 'common_guest_sms_history,common_guest';
				$payload['property_id'] = $property_id;
				$payload['notify_type'] = 'guestdetail';
				$payload['notify_id'] = $guest_id;//for guest information refresh
				$payload['table_id'] = $id . ',' . $guest_id;

				$this->sendSMS(0, $mobile, $content, $payload);

				if($user_id != 0) {
					return '200';
				}else {
					return '0';
				}
			}else {
				return '0';
			}
		}else {
			return '0';
		}
	}

	public function getGuestLogList(Request $request) 
	{
        $page = $request->get('page', 1);
        $pageSize = $request->get('pagesize', 20);
        $skip = ($page - 1) * $pageSize;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $property_id = $request->get('property_id', '0');
        $checkout_flag = $request->get('checkout_flag', 'All');
        $searchoption = $request->get('searchoption','');
        $filter = $request->get('filter','');
        $guest_id = $request->get('guest_id', -1);

        if($pageSize < 0 )
            $pageSize = 20;

        $ret = array();

        $query = DB::table('common_guest_log')
            ->where('guest_id', $guest_id)
            ->where('property_id', $property_id);


        if( $filter != 'Total' )
        {
            if( $filter == 1 )	// On Route
                $query->where('checkout_flag', 'checkin');
            if( $filter == 2 )
                $query->where('checkout_flag', 'checkout');
            if( $filter == 3 )
                $query->where('fac_flag', 1);
        }

        if ($searchoption !== '') {
	        $where = sprintf(" (								
								guest_name like '%%%s%%' or	
								first_name like '%%%s%%' or							
								arrival like '%%%s%%' or
								departure like '%%%s%%')",
                $searchoption, $searchoption, $searchoption, $searchoption
            );
            $query->whereRaw($where);
        }


        $data_query = clone $query;

        $detail = new GuestAdvancedDetail();
        $attrs = array_slice($detail->getTableColumns(), 4, -2);

        $column = '';
        foreach($attrs as $i => $key)
        {
            if($i > 0)
                $column .= ',';
            $column .=  'gad.' . $key;
        }

        $alarm_list = $data_query
            ->orderBy($orderby, $sort)
            ->select(DB::raw('*'))
            ->skip($skip)->take($pageSize)
            ->get();

        foreach($alarm_list as $key => $row) {
            $alarm_list[$key]->active_fac = GuestLog::activeList($row->id);
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['guestlist'] = $alarm_list;
        $ret['totalcount'] = $totalcount;
        $ret['column'] = $column;

        return Response::json($ret);
    }

	public function getFacilityTotalListData($filter, $client_id)
	{
		$ret = array();

		$property_list = DB::table('common_property')
			->where('client_id', $client_id)
			->get();

		foreach($property_list as $property) {
			$locationlist = $this->getFacilityListData($filter, $property->id);
			$ret = array_merge($ret, $locationlist->toArray());
		}

		return $ret;
	}

	public function getFacilityListData($filter, $pro_id)
	{
		$ret = DB::table('services_location as sl')
			->join('common_property as cp', 'sl.property_id', '=', 'cp.id')
			->join('services_location_type as lt', 'sl.type_id', '=', 'lt.id')
			->where('sl.property_id', $pro_id)
			->where('sl.name', 'like', $filter)
			->whereRaw("(lt.type = 'Common Area' OR lt.type = 'Outdoor')")
			->select(DB::Raw('sl.*, sl.id as lg_id, sl.name, lt.type, cp.name as property'))
			->get();

		return $ret;
	}

	public function facilityLog(Request $request) 
	{
		$guest_id = $request->get('id' , 0);
		$user_id = $request->get('user_id' , 0);
		$entry = $request->get('entry_time', 0);
		$kids = $request->get('quantity_2', 0);
		$adults = $request->get('quantity_1', 0);
		$extra = $request->get('quantity_3', 0);
		$comment = $request->get('comment', '');
		$location = $request->get('location', '');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");


		$guest_log = new GuestLog();
		$guest_log->guest_id=$guest_id;
		$guest_log->user_id=$user_id;
		$guest_log->entry_time = $cur_date . ' ' .$entry;
		$guest_log->kids=$kids;
		$guest_log->adults=$adults;
		$guest_log->extra=$extra;
		$guest_log->comment=$comment;
		$guest_log->location=$location;
		$guest_log->save();

		if($guest_log->exit_time == NULL)
		{
			$guest = Guest::find($guest_id);
			$guest->fac_flag=1;
			$guest->save();
		}

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $guest_log->id;

		return Response::json($ret);
	}

	public function getfacilityLog(Request $request) 
	{
		$guest_id = $request->get('id' , 0) ;
		$history_list = DB::table('common_guest_facility_log as cg')
			->leftJoin('common_users as cu', 'cg.user_id', '=', 'cu.id')
			->where('cg.guest_id', $guest_id)
			->select(DB::raw('cg.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as username'))
			->get();
		$ret = array();
		$ret['history'] = $history_list;
		return Response::json($ret);
	}

	public function guestExit(Request $request) 
	{
		$id = $request->get('id', 0) ;
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$guest_log = GuestLog::find($id);
		$guest_log->exit_time=$cur_time;
		$guest_log->save();
		$active_list=array();
		$active_list = GuestLog::activeList($guest_log->guest_id);
		
		if(empty($active_list))
		{
		    $guest = Guest::find($guest_log->guest_id);
			$guest->fac_flag=0;
			$guest->save();
		}

		$ret = array();
		$ret['id'] = $guest_log->id;
		return Response::json($ret);
	}

	public function getTablecheck(Request $request)
	{
		$testshopref = $request->get('testshopref', '');
		$testdate = $request->get('testdate', '');

		$ch = curl_init();


		//	$url1 = `https://api.tablesolution.com/ts_api/shops/$testshopref/reservations/by_date/$testdate`;
		//	$url3 = `https://api.tablesolution.com/ts_api/shops/5fd1bd793b3cbc0023b28952/reservations/by_date/2021-06-10`;

		$url6 = 'https://api.tablesolution.com/ts_api/v2/shops/5fd1bd793b3cbc0023b28952/reservations';
		$url2 = sprintf('https://api.tablesolution.com/ts_api/v2/shops/%s/reservations',$testshopref);

		$url7 = 'https://api.tablesolution.com/ts_api/v2/shops/5fd1bd793b3cbc0023b28952/syncrequests';


		$headers = [
			'Content-Type: application/json',
			'Authorization: 9QNYROG1SLG98YERBM9OUZ56IDTP5D55UD1PG9BW',
		];


		curl_setopt($ch,CURLOPT_URL,$url6);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		//	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$output=curl_exec($ch);

		curl_close($ch);




		return Response::json($output);
	}

	public function getTablecheckupdate(Request $request)
	{

		$testrefid = $request->get('testrefid', '');
		$status = $request->get('status', '');

		$ch = curl_init();
		$data = array("status" => $status);

		$data_string = json_encode($data);
		$url = sprintf('https://api.tablesolution.com/ts_api/v2/shops/5fd1bd793b3cbc0023b28952/reservations/%s',$testrefid);
		$url2 = 'https://api.tablesolution.com/ts_api/v2/shops/5fd1bd793b3cbc0023b28952/reservations/60c1d05a8be2db003c591331';
		$url3 = 'https://api.tablesolution.com/ts_api/v2/shops/5fd1bd793b3cbc0023b28952/pos_journals/<journal_id>';

		$headers = [
			'Content-Type: application/json',
			'Authorization: 9QNYROG1SLG98YERBM9OUZ56IDTP5D55UD1PG9BW',
		];

		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$output=curl_exec($ch);

		curl_close($ch);

		return Response::json($output);
	}

	public function getTablecheckWalkin(Request $request)
	{

		date_default_timezone_set(config('app.timezone'));

		$testadultno = $request->get('testadultno', '');
		$testduration = $request->get('testduration', 0);
		$testtabname = $request->get('testtabname', '');
		$status = $request->get('status', '');
		$cur_time = date("Y-m-d\TH:i:sP");
		//	$iso_time = date_format(date_create('17 Oct 2008'), 'c');




		$ch = curl_init();

		$data = array("status" => $status,
					  "duration" => $testduration * 3600,
					  "num_people_adult" => $testadultno,
					  "table_name" => [$testtabname],
					  "source" => 'walk_in',
					  "start_date" => $cur_time);


		$data_string = json_encode($data);


		/*
		$url = sprintf('https://api.tablesolution.com/ts_api/v2/shops/5fd1bd793b3cbc0023b28952/reservations/%s',$testrefid);
		$url2 = 'https://api.tablesolution.com/ts_api/v2/shops/5fd1bd793b3cbc0023b28952/reservations/60c1d05a8be2db003c591331';
		$url3 = 'https://api.tablesolution.com/ts_api/v2/shops/5fd1bd793b3cbc0023b28952/pos_journals/<journal_id>';

		$url4 = 'https://api.tablesolution.com/ts_api/v2/shops/5fd1bd793b3cbc0023b28952/reservations/res_00019';


		$headers = [
			'Content-Type: application/json',
			'Authorization: 9QNYROG1SLG98YERBM9OUZ56IDTP5D55UD1PG9BW',
		];


		curl_setopt($ch,CURLOPT_URL,$url4);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$output=curl_exec($ch);

		curl_close($ch);
		*/

		return Response::json($data);
	}

	public function getReservationList(Request $request)
	{
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', 0);
		$searchoption = $request->get('searchtext', '');

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('common_guest_reservation as cgr')
				->leftJoin('common_room as cr', 'cgr.room_id', '=', 'cr.id')
				->where('cgr.property_id', $property_id);

		// get building ids
		if( !empty($searchoption) )
		{
			$where = sprintf(" (cr.room like '%%%s%%' or								
								cgr.guest_name like '%%%s%%' or								
								cgr.start_date like '%%%s%%' or
								cgr.end_date like '%%%s%%' or
								cgr.res_id like '%%%s%%')",
				$searchoption, $searchoption, $searchoption, $searchoption, $searchoption
			);

			$query->whereRaw($where);
		}

		$data_query = clone $query;

		$list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('cgr.*, cr.room'))
				->skip($skip)->take($pageSize)
				->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['list'] = $list;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function createReservation(Request $request)
	{
		$input = $request->except(['room']);

		$ret = array();
		$ret['code'] = 200;

		$id = $request->get('id', 0);
		$room_id = $request->get('room_id', 0);
		if( $room_id > 0 )
		{
			if($input['status'] == 'Booking' )
				$input['status'] = 'Arrival';
		}

		if( $id > 0 )	// update
		{
			DB::table('common_guest_reservation')
				->where('id', $id)
				->update($input);
		}
		else
		{
			DB::table('common_guest_reservation')
				->insert($input);
		}

		return Response::json($ret);
	}

	public function getGuestSmsTemplate(Request $request) 
	{
		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 0);

		$ret = array();

		$model = DB::table('common_guest_sms_template')
			->where('property_id', $property_id)
			->first();

		if( empty($model) )
			$ret['template'] = '';
		else
			$ret['template'] = $model->template;

		$ret['temp_item_list'] = GuestSmsTemplate::getTemplateElementList();

		$ret['code'] = 200;

		return Response::json($ret);
	}

	public function saveGuestSmsTemplate(Request $request) 
	{
		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 0);
		$template = $request->get('template', '');

		$ret = array();

		$model = GuestSmsTemplate::where('property_id', $property_id)
			->first();

		if( empty($model) )
		{
			$model = new GuestSmsTemplate();
			$model->property_id = $property_id;
		}

		$model->template = $template;
		$model->modified_by = $user_id;
		$model->save();

		$ret['code'] = 200;

		return Response::json($ret);
	}

	public function getAWCRoomList(Request $request)
	{
		// select room list with property id
		$cur_date = date("Y-m-d");
		$room = $request->get('room', '1001');
		$property_id = $request->get('property_id', 4);
		$roomlist = DB::table('common_room as cr')
					->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
					->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
					->join('common_guest as cg', function($join) use ($cur_date) {
						$join->on('cr.id', '=', 'cg.room_id');
						$join->on('cg.departure','>=',DB::raw($cur_date));
						$join->on('cg.checkout_flag','=', DB::raw("'checkin'"));
					})
					->where('cr.room', 'like', '%' . $room . '%')
					->where('cb.property_id', $property_id)
					->select(DB::raw('cr.*, cb.property_id'))
					->get();

		return Response::json($roomlist);
	}

	public function getInformationForShift(Request $request)
	{
		$dept_id = $request->get('dept_id', 0);

		$users = DB::table('common_users as cu')
				->where('dept_id', $dept_id)
				->where('cu.deleted', 0)
				->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();

		$dept_funcs = DB::table('services_dept_function as sdf')
				->where('dept_id', $dept_id)
				->get();

		$shifts = DB::table('services_shift_group as sg')
				->join('services_shifts as sh', 'sg.shift', '=', 'sh.id')
				->where('sg.dept_id', $dept_id)
				->select(['sg.*', 'sh.name as shname'])
				->get();

		$location_groups = DB::table('services_location_group as slg')
				->get();

		$shift_group_member = DB::table('services_shift_group_members as sgm')
				->join('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
				->join('common_users as cu', 'sgm.user_id', '=', 'cu.id')
				->join('services_shifts as sh', 'sg.shift', '=', 'sh.id')
				->where('sg.dept_id', $dept_id)
				->select(DB::raw('sgm.*, sh.name as shname, sh.start_time, sh.end_time, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();

		$task_group_list = DB::table('services_task_group as tg')
				->join('services_dept_function as df', 'tg.dept_function', '=', 'df.id')
				->where('df.dept_id', $dept_id)
				->select('tg.*')
				->get();


		$ret = array();
		$ret['staff_list'] = $users;
		$ret['dept_func'] = $dept_funcs;
		$ret['location_group'] = $location_groups;
		$ret['shifts'] = $shifts;
		$ret['shift_group_member'] = $shift_group_member;
		$ret['task_group_list'] = $task_group_list;

		return Response::json($ret);
	}

	public function getTaskgrouplist(Request $request)
	{
		$dept_func_list = $request->get('dept_func_list', array());

		$task_group_list = DB::table('services_task_group')
				->whereIn('dept_function', $dept_func_list)
				->get();

		return Response::json($task_group_list);
	}

	public function createShiftGroupList(Request $request)
	{
		$dept_id = $request->get('dept_id', 0);
		$shift = $request->get('shift_id', 0);
		$staff_list = $request->get('staff_list', 0);
		$location_group_ids = $request->get('location_group_list', array());
		$task_group_ids = $request->get('task_group_list', array());
		$day_of_week = $request->get('day_of_week', array());
		$vaca_start_date = $request->get('vaca_start_date', '');
		$vaca_end_date = $request->get('vaca_end_date', '');

		$shift_group = DB::table('services_shift_group')
				->where('dept_id', $dept_id)
				->where('shift', $shift)
				->first();

		if( empty($shift_group) )
			return Response::json($dept_id);

		for($i = 0; $i < count($staff_list); $i++)
		{
			$shift_group_member = ShiftGroupMember::find($staff_list[$i]);
			if( empty($shift_group_member) )
				$shift_group_member = new ShiftGroupMember();

			$shift_group_member->user_id = 	$staff_list[$i];
			$shift_group_member->shift_group_id = $shift_group->id;
			$shift_group_member->device_id = 1;
			$shift_group_member->location_grp_id = json_encode($location_group_ids);
			$shift_group_member->task_group_id = json_encode($task_group_ids);
			$shift_group_member->day_of_week = $day_of_week;
			$shift_group_member->vaca_start_date = $vaca_start_date;
			$shift_group_member->vaca_end_date = $vaca_end_date;

			$shift_group_member->save();
		}

		return $this->getInformationForShift($request);
	}

	public function getAlarmListTen(Request $request) 
	{
		$property_id = $request->get('property_id', 0);
		$ret = array();

		$alarm_list = DB::table('services_alarm_groups as ag')
				->whereNotNull('ag.pref')
				->where('ag.enable',1)
				->select(DB::raw('ag.*, ag.pref,ag.name as group_name'))
				->get();

		$ret['alarmlist'] = $alarm_list;

		return Response::json($ret);
	}

	public function getAlarmList(Request $request) 
	{
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', 0);

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('services_alarms_notifications as an')
				->leftJoin('services_alarm_groups as ag', 'an.notification_group', '=', 'ag.id')
				->leftJoin('common_users as cu', 'an.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id');
		//	$where = sprintf('an.notification_group = %d', $alarm_group_id);

		$data_query = clone $query;

		$alarm_list = $data_query
				->where('cd.property_id', $property_id)
				->orderBy($orderby, $sort)
				->select(DB::raw('an.*, ag.name as group_name, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->skip($skip)->take($pageSize)
				->get();

		$count_query = clone $query;
		$totalcount = $count_query
				->where('cd.property_id', $property_id)
				->count();

		$ret['alarmlist'] = $alarm_list;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);

	}

	public function getAlarmGroupList(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$val = $request->get('val', '');

		$alarm_groups = DB::table('services_alarm_groups as ag')
				->where('ag.property', $property_id)
				->where('ag.name', 'like', '%' . $val . '%')
				->get();

		return Response::json($alarm_groups);
	}

	public function sendAlarm(Request $request)
	{
		$input = $request->all();
		$id = DB::table('services_alarms_notifications')->insertGetId($input);

		$alarm_group_id = $request->get('notification_group', 0);
		$comment = $request->get('message', '');
		$location = $request->get('loc_id', '');

		$info = $this->getLocationInfo($location);
					if( !empty($info) )
					{
						$lgm_name = $info->name;
						$lgm_type = $info->type;

					}

		$member_list = DB::table('common_users as cu')
		    ->leftJoin('common_user_group_members as cgm', 'cgm.user_id', '=', 'cu.id')
			->leftJoin('services_alarm_members as am', 'am.user_id', '=', 'cgm.group_id')
			->where('am.alarm_group', '=', $request->get('notification_group', 0))
			->where('cu.deleted', 0)
			->select(DB::raw('cu.mobile,cu.email,cu.contact_pref_bus,cu.id'))
			->get();

		$alarm = DB::table('services_alarm_groups')
			->where('id', $alarm_group_id)
			->first();

		if( empty($alarm) )
		{
			$ret['code'] = FAIL;
			$ret['message'] = 'There is no valid alarm';
			return Response::json($ret);
		}

		$ret = array();
		if( empty($member_list) )
		{
			$ret['code'] = FAIL;
			$ret['message'] = 'There is no member';
			return Response::json($ret);
		}

		$number_array = "";
		$user_id_array = "";
		for($i = 0; $i < count($member_list); $i++)
		{
			if( $i > 0 )
            {
                $number_array .= "|";
                $user_id_array .= "|";
            }


			$mobile_no = $member_list[$i]->mobile;
            if(strlen($mobile_no) < 12)
            {
                if(substr($mobile_no, 0, 1) === '0')
                {
                    $mobile_no = substr($mobile_no, 1, strlen($mobile_no) - 1);
                }
                // Default country code : 971
                $mobile_no = "971".$mobile_no;

            }
			$number_array .= $mobile_no;
            $user_id_array .= $member_list[$i]->id;
		}

		$message = 'ALARM:' . $alarm->name ."\n". $alarm->description ."\n". ' CM:' . $comment ."\n";

		$this->sendSMS(0, $number_array, $message, null);
      	//  $this->sendDesktopNotification(0, $user_id_array, $message, null);

		//$send_mode = 'SMS';
		//foreach($member_list as $key => $user)
		//{

		//	$send_mode = $user->contact_pref_bus;
		//
		//	if($send_mode=="SMS")
		//	{
		//		$this->sendSMS(0, $number_array, $message, null);
		//	}
		//	else if($send_mode=="e-mail")
		//	{
		//		// echo json_encode($user);
		//		$smtp = Functions::getMailSetting(4, '');
		//		// $this->sendEmail($user->email, $type, $message, $smtp, $payload, $alarm->name );
		//		$this->sendEmail($user->email, 'Hotlync', $message, $smtp,NULL,$alarm->name );

		//	}
		//	else if($send_mode=="Mobile")
		//	{
				// $setting = DeftFunction::getGSDeviceSetting($user->dept_id,$dept_func);
		
				// if($setting == 1 && $type!='Escalated')
				// {
				// 	$user->mobile = Device::getDeviceNumber($user->device_id);
				// }
		//	}
		//}

		$ret['code'] = SUCCESS;
		$ret['count'] = count(($member_list));
		return Response::json($ret);
	}

	public function getGuestFacilityList(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page * $pageSize;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $searchtext = $request->get('searchtext','');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');
        $guest_type = $request->get('guest_type', 'All');
        $room_ids = $request->get('room_ids', []);

        if ($pageSize < 0)
            $pageSize = 20;

        $ret = array();
        $query = DB::table('services_guest_facility as gf')
				->leftJoin('common_room as cr', 'gf.room_id', '=', 'cr.id')
				->leftJoin('common_guest as gp', 'gf.guest_id', '=', 'gp.guest_id')
				->leftJoin('common_guest_facility as cgf', 'gf.guest_id', '=', 'cgf.id');


        $query->whereRaw(sprintf("DATE(gf.created_at) >= '%s' and DATE(gf.created_at) <= '%s'", $start_date, $end_date));


        if($searchtext != '')
        {
            $query->where(function ($query) use ($searchtext) {
                $value = '%' . $searchtext . '%';
                $query->where('gf.id', 'like', $value)
                    ->orWhere('gf.guest_id', 'like', $value)
					->orWhere('gf.guest_type', 'like', $value)
					->orWhere('gf.table_no', 'like', $value)
					->orWhere('cr.room', 'like', $value)
                    ->orWhere('gp.guest_name', 'like', $value)
                    ->orWhere('gp.first_name', 'like', $value)
                    ->orWhere('gp.email', 'like', $value)
					->orWhere('gp.mobile', 'like', $value)
                    ->orWhere('gp.vip', 'like', $value)
					->orWhere('cgf.guest_name', 'like', $value)
                    ->orWhere('cgf.first_name', 'like', $value)
                    ->orWhere('cgf.email', 'like', $value)
                    ->orWhere('cgf.mobile', 'like', $value);

            });
        }

		if( $guest_type != 'All' )
            $query->where('gf.guest_type', $guest_type);

		 // room filter
        if( !empty($room_ids) )
        {
            $room_id_list = explode(',', $room_ids);
            $query->whereIn('gf.room_id', $room_id_list);
        }

        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->orderBy('gf.created_at', 'desc')
            ->select(DB::raw('gf.*, cr.room'))
            ->skip($skip)->take($pageSize)
            ->groupBy('gf.id')
            ->get();


        foreach($data_list as $row)
        {
            if ($row->guest_type == 'In-House'){
				$guest = DB::table('common_guest as cg')
						->where('cg.guest_id', $row->guest_id)
						->first();

			$row->guest_name = $guest->guest_name;
			$row->vip = $guest->vip;
			$row->stay = $guest->arrival . ' to ' . $guest->departure;
			$row->email = $guest->email;
			$row->mobile = $guest->mobile;


			}else{

				$guest = DB::table('common_guest_facility as cg')
						->where('cg.id', $row->guest_id)
						->first();
				$row->guest_name = $guest->guest_name;
				$row->vip = 'NA';
				$row->stay = 'NA';
				$row->email = $guest->email;
				$row->mobile = $guest->mobile;
				$row->room = 'NA';

			}

        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['code'] = 200;
        $ret['message'] = '';
        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;



        $end = microtime(true);

        return Response::json($ret);
    }

	public function createGuestFacility(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");
        $cur_time = date("Y-m-d H:i:s");

    	//    $user_id = $request->get('user_id', 0);

        $input = array();
        $input["room_id"] = $request->get('room_id', 0);
        $input["guest_id"] = $request->get('guest_id', 0);
        $input["guest_type"] = $request->get('guest_type', '');
        $input["table_no"] = $request->get('table', 0);
        $input["bmeal"] = $request->get('bmeal', 0);
		$input["adult"] = $request->get('adult', 0);
        $input["child"] = $request->get('child', 0);
		$input["remark"] = $request->get('remarks', '');

		$ret = array();

		$table = DB::table('services_guest_facility')
				 ->where('table_no', $input["table_no"])
				 ->where('exit_flag','!=', 1)
				 ->first();

		if (!empty($table)){

			if ($table->guest_type == 'In-House'){
				$guest = DB::table('common_guest as cg')
						->leftJoin('common_room as cr', 'cg.room_id', '=', 'cr.id')
						->where('cg.guest_id', $table->guest_id)
						->select(DB::raw('cg.*, cr.room'))
						->first();


				$ret['code'] = 203;
				$ret['message'] = 'The table ' . $input["table_no"] . ' has already been occupied by an In-House Guest ' . $guest->guest_name . ' from Room ' . $guest->room;


			}else{

				$guest = DB::table('common_guest_facility as cg')
						->where('cg.id', $table->guest_id)
						->first();

				$ret['code'] = 203;
				$ret['message'] = 'The table ' . $input["table_no"] . ' has already been occupied by a Walkin Guest ' . $guest->guest_name;

			}

			return Response::json($ret);

		}





		if ($input["guest_type"] == 'Walkin'){

			$guest = array();

			$guest["guest_name"] = $request->get('guest_name', '');
			$guest["first_name"] = $request->get('first_name', '');
			$guest["email"] = $request->get('email', '');
			$guest["mobile"] = $request->get('mobile', '');
			$guest["adult"] = $request->get('adult', 0);
			$guest["child"] = $request->get('child', 0);

			$guest_id = DB::table('common_guest_facility')->insertGetId($guest);

			$input["guest_id"] = $guest_id;
			$input["room_id"] = 0;

		}

        $facility_id = DB::table('services_guest_facility')->insertGetId($input);



        $ret['code'] = 200;
        $ret['id'] = $facility_id;
        $ret['content'] = $facility_id;

        return Response::json($ret);
    }

	public function exitGuest(Request $request)
	{
		$id = $request->get('id', 0);
		$cur_time = date("Y-m-d H:i:s");

		$model =DB::table('services_guest_facility')->where('id', $id);

		$model->update(['exit_flag' => 1,'exit_time' => $cur_time]);

		return Response::json($model);
	}

	private function getDeptIdsFromUserId($user_id) 
	{
        //        get dept ids from user_id
        $deptList = DB::table('common_users')
            ->where('id', $user_id)
            ->groupBy('dept_id')
            ->select('dept_id')
            ->get();

        $deptIds = [];
        foreach ($deptList as $deptItem) {
            $deptIds[] = $deptItem->dept_id;
        }

        return $deptIds;
    }

	public function getGuestChatSettingInfo(Request $request) 
	{
        $property_id = $request->get('property_id', 4);

        $warning_time = 0;
        $critical_time = 0;
        $job_role_ids = '';
        $end_chat = '';
        $no_answer = '';
        $accept_chat = '';

        $result = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_warning_time')
            ->select(['value'])
            ->first();

        if (!empty($result)) {
            $warning_time = $result->value;
        }

        $result = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_critical_time')
            ->select(['value'])
            ->first();

        if (!empty($result)) {
            $critical_time = $result->value;
        }

        $result = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_job_role_ids')
            ->select(['value'])
            ->first();

        if (!empty($result)) {
            $job_role_ids = $result->value;
        }

        // end chat
        $result = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_end_chat')
            ->select(['value'])
            ->first();

        if (!empty($result)) {
            $end_chat = $result->value;
        }

        // no answer
        $result = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_no_answer')
            ->select(['value'])
            ->first();

        if (!empty($result)) {
            $no_answer = $result->value;
        }

        // accept_chat
        $result = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_accept_chat')
            ->select(['value'])
            ->first();

        if (!empty($result)) {
            $accept_chat = $result->value;
        }

        $ret = [
            'warning_time' => $warning_time,
            'critical_time' => $critical_time,
            'job_role_ids' => $job_role_ids,
            'end_chat' => $end_chat,
            'no_answer' => $no_answer,
            'accept_chat' => $accept_chat
        ];

        return Response::json($ret);
    }

	public function getJobRoleList(Request $request) 
	{
	    $property_id = $request->get('property_id', 4);

	    $job_roles = DB::table('common_job_role')
            ->where('property_id', $property_id)
            ->select(['id', 'job_role as label'])
            ->orderBy('job_role')
            ->get();

	    return Response::json($job_roles);
    }

	public function saveGuestChatSettingInfo(Request $request) 
	{
        $property_id = $request->get('property_id', 4);
        $warning_time = $request->get('warning_time', 0);
        $critical_time = $request->get('critical_time', 0);
        $job_role_ids = $request->get('job_role_ids', '');
        $end_chat = $request->get('end_chat', '');
        $no_answer = $request->get('no_answer', '');
        $accept_chat = $request->get('accept_chat', '');

        $ret = [
            'success' => true,
            'message' => ''
        ];

        $count = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_warning_time')
            ->count();

        if ($count < 1) {
            DB::table('property_setting')
                ->insert(['property_id' => $property_id, 'settings_key' => 'guestchat_setting_warning_time', 'value' => $warning_time]);
        } else {
            DB::table('property_setting')
                ->where('property_id', $property_id)
                ->where('settings_key', 'guestchat_setting_warning_time')
                ->update(['value' => $warning_time]);
        }

        $count = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_critical_time')
            ->count();

        if ($count < 1) {
            DB::table('property_setting')
                ->insert(['property_id' => $property_id, 'settings_key' => 'guestchat_setting_critical_time', 'value' => $critical_time]);
        } else {
            DB::table('property_setting')
                ->where('property_id', $property_id)
                ->where('settings_key', 'guestchat_setting_critical_time')
                ->update(['value' => $critical_time]);
        }

        $count = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_job_role_ids')
            ->count();

        if ($count < 1) {
            DB::table('property_setting')
                ->insert(['property_id' => $property_id, 'settings_key' => 'guestchat_setting_job_role_ids', 'value' => $job_role_ids]);
        } else {
            DB::table('property_setting')
                ->where('property_id', $property_id)
                ->where('settings_key', 'guestchat_setting_job_role_ids')
                ->update(['value' => $job_role_ids]);
        }

        // end chat
        $count = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_end_chat')
            ->count();

        if ($count < 1) {
            DB::table('property_setting')
                ->insert(['property_id' => $property_id, 'settings_key' => 'guestchat_setting_end_chat', 'value' => $end_chat]);
        } else {
            DB::table('property_setting')
                ->where('property_id', $property_id)
                ->where('settings_key', 'guestchat_setting_end_chat')
                ->update(['value' => $end_chat]);
        }

        // no answer
        $count = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_no_answer')
            ->count();

        if ($count < 1) {
            DB::table('property_setting')
                ->insert(['property_id' => $property_id, 'settings_key' => 'guestchat_setting_no_answer', 'value' => $no_answer]);
        } else {
            DB::table('property_setting')
                ->where('property_id', $property_id)
                ->where('settings_key', 'guestchat_setting_no_answer')
                ->update(['value' => $no_answer]);
        }

        // accept chat
        $count = DB::table('property_setting')
            ->where('property_id', $property_id)
            ->where('settings_key', 'guestchat_setting_accept_chat')
            ->count();

        if ($count < 1) {
            DB::table('property_setting')
                ->insert(['property_id' => $property_id, 'settings_key' => 'guestchat_setting_accept_chat', 'value' => $accept_chat]);
        } else {
            DB::table('property_setting')
                ->where('property_id', $property_id)
                ->where('settings_key', 'guestchat_setting_accept_chat')
                ->update(['value' => $accept_chat]);
        }

        return Response::json($ret);
	}

	public function changeMinibarTaskToComplete($room_id, $user_id, $from)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$cur_time = date("Y-m-d H:i:s");

		$room_info = Room::getPropertyBuildingFloor($room_id);

		if( empty($room_info) )
			return array();

		$check_minibar_task_type = PropertySetting::getCheckMinibarSystemTaskType($room_info->property_id);

		$ret = array();

		$tasklist = DB::table('services_task as st')
				->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
				->where('tl.type', $check_minibar_task_type)	// system task
				->where('room', $room_id)
				->whereRaw("DATE(st.start_date_time) = '$cur_date'")
				->whereIn('st.status_id', array(OPENGS, ESCALATEDGS))
				->select(DB::raw('st.*'))
				->get();

		$ids = [];

		foreach($tasklist as $row)
		{
			$task = Task::find($row->id);
			if( empty($task) )
				continue;

			$ids[] = $row->id;

			$task->status_id	= COMPLETEDGS;		// set complete state
			$task->finisher = $user_id;		// set finisher(most equal dispatcher)
			$task->end_date_time = $cur_time;	// finish time
			$task->running = 0;				// set running to 0
			$task->custom_message = 'Minibar Items posted from ' . $from; // set message
			$task->save();

			// add services_task_notifications
			$task_notify = $this->saveNotification($task->attendant, $task->id, 'Completed');		// send notify to finisher

			// update services_task_state table
			// remove task state with this task id
			if( $task->type == 1 || $task->type == 2 || $task->type == 4 )
				DB::table('services_task_state')->where('task_id', $row->id)->delete();
			if( $task->type == 3 )
				DB::table('services_complaint_state')->where('task_id', $row->id)->delete();

			$this->saveSystemNotification($task, 'Complete');

			// save log
			$task_log = new Tasklog();
			$task_log->task_id = $task->id;
			$task_log->user_id = $user_id;
			$task_log->comment = $task->custom_message;
			$task_log->log_type = 'Completed';
			$task_log->log_time = $cur_time;
			$task_log->status = 'Completed';
			$task_log->method = $from;

			if( !empty($task_notify) )
				$task_log->notify_id = $task_notify->id;

			$task_log->save();
		}

		return $ids;
	}

	private function getRoomDetails($room_id,$property_id)
	{
		$cur_date = date("Y-m-d");
		$hskproom_status = HskpRoomStatus::find($room_id);

		if(empty($hskproom_status)) {
			$vacant_dirty = DB::table('services_hskp_status as hs')
				->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
				->where('cb.property_id', $property_id)
				->where('hs.status', 'Vacant Dirty')
				->select(DB::raw('hs.*'))
				->first();

			$occupied_dirty = DB::table('services_hskp_status as hs')
				->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
				->where('cb.property_id', $property_id)
				->where('hs.status', 'Occupied Dirty')
				->select(DB::raw('hs.*'))
				->first();

			$vacant_dirty_ids = array();
			$occupied_dirty_ids = array();

			$guest = DB::table('common_guest')
				->where('room_id', $room_id)
				->where('departure', '>=', $cur_date)
				->where('checkout_flag', 'checkin')
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

			$hskp_room_status = new HskpRoomStatus();

			$hskp_room_status->id = $room_id;
			$hskp_room_status->property_id = $property_id;

			if (!empty($guest)) {
				array_push($occupied_dirty_ids, $room_id);
				$hskp_room_status->occupancy = OCCUPIED;
			} else {
				array_push($vacant_dirty_ids, $room_id);
				$hskp_room_status->occupancy = VACANT;
			}

			if (!empty($guest) && $guest->pre_checkin == 1)
				$hskp_room_status->arrival = 1;
			else
				$hskp_room_status->arrival = 0;

			if (!empty($guest) && $guest->departure == $cur_date)
				$hskp_room_status->due_out = 1;
			else
				$hskp_room_status->due_out = 0;

			$hskp_room_status->working_status = CLEANING_NOT_ASSIGNED;
			$hskp_room_status->priority = 0;    // Highest
			$hskp_room_status->save();

			// save turn down service status
			if( $hskp_room_status->occupancy == OCCUPIED && $hskp_room_status->due_out == 0)	// checkin and not due out
			{
				$hskp_turndown_status = new HskpTurndownStatus();
				$hskp_turndown_status->id = $room_id;
				$hskp_turndown_status->property_id = $property_id;
				$hskp_turndown_status->working_status = CLEANING_NOT_ASSIGNED;

				$hskp_turndown_status->save();
			}

			if( !empty($vacant_dirty) )
			{
				DB::table('common_room')
					->whereIn('id', $vacant_dirty_ids)
					->update(['hskp_status_id' => $vacant_dirty->id]);
			}

			if( !empty($occupied_dirty) )
			{
				DB::table('common_room')
					->whereIn('id', $occupied_dirty_ids)
					->update(['hskp_status_id' => $occupied_dirty->id]);
			}
		}

		$roomlist = DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
			->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
			->leftJoin('common_guest as cg', function($join) use ($cur_date) {
					$join->on('cr.id', '=', 'cg.room_id');
					$join->on('cg.departure','>=',DB::raw($cur_date));
					$join->on('cg.checkout_flag','=', DB::raw("'checkin'"));
				})
			->leftJoin('common_guest_remark_log as grl', function($join) use ($cur_date) {
					$join->on('grl.room_id', '=', 'cg.room_id');
					$join->on('grl.guest_id', '=', 'cg.guest_id');
					$join->where('grl.expire_date','>=', DB::raw($cur_date));
					//$join->on('grl.expire_date','>=', 'grl.created_on');


				})
			->where('cr.id', '=',  $room_id)
			->where('cb.property_id', $property_id)
			->select(DB::raw('cr.*,cf.floor, cb.property_id, hs.status, grl.remark, cg.pref,cg.adult,cg.chld, rt.type as room_type, rs.working_status, rs.room_status, rs.td_flag, rs.td_working_status,rs.priority, cg.checkout_flag, rs.occupancy'))
			->first();

		return $roomlist;
	}
}
