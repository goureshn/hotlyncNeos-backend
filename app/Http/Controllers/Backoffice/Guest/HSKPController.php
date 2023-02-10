<?php

namespace App\Http\Controllers\Backoffice\Guest;

use App\Models\Common\CommonUser;
use App\Models\Common\Room;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Common\Building;
use App\Models\Service\HskpStatus;
use App\Models\Service\HskpStatusLog;
use App\Models\Service\HskpRoomStatus;
use App\Models\Service\HskpTurndownStatus;
use App\Models\Common\PropertySetting;
use App\Models\Service\ShiftGroupMember;
use App\Models\Service\Test;
use App\Models\Common\Guest;
use App\Models\Service\RosterList;
use App\Models\Service\Device;
use App\Models\Service\RosterLog;
use App\Models\Common\CommonJobrole;

use DB;
use Response;
use Datatables;
use DateTime;
use DateInterval;
use App\Modules\Functions;
use Carbon\Carbon;

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
define("SLEEPOUT", 12);

define("CLEANING_PENDING_NAME", 'Pending');
define("CLEANING_RUNNING_NAME", 'Cleaning');
define("CLEANING_DONE_NAME", 'Done');
define("CLEANING_DND_NAME", 'DND (Do not Disturb)');
define("CLEANING_POSTPONE_NAME", 'Delay');
define("CLEANING_PAUSE_NAME", 'Pause');
define("CLEANING_COMPLETE_NAME", 'Inspected');
define("CLEANING_REFUSE_NAME", 'Refused');
define("CLEANING_DECLINE_NAME", 'Reject');

define("CLEANING_OUT_OF_ORDER_NAME", 'OOO (Out of Order)');
define("CLEANING_OUT_OF_SERVICE_NAME", 'OOS (Out of Service)');

if (!defined('VACANT')) {
    define("VACANT", 'Vacant');
	define("OCCUPIED", 'Occupied');
	define("DUE_OUT", 'Due Out');
	define("ARRIVAL", 'Arrival');
	define("OUT_OF_ORDER", 'Out of Order');
	define("OUT_OF_SERVICE", 'Out of Service');
}

define("DIRTY", 'Dirty');
define("CLEAN", 'Clean');
define("INSPECTED", 'Inspected');

class HSKPController extends Controller
{
   	public function index(Request $request)
    {
		$datalist = DB::table('services_hskp_status as hskp')			
						->leftJoin('common_building as cb', 'hskp.bldg_id', '=', 'cb.id')
						->select(['hskp.*', 'cb.name as cbname']);

		return DataTables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('edit', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="job_role!=\'SuperAdmin\'&&edit_flag==0" ng-click="onShowEditRow('.$data->id.')">
						<span class="glyphicon glyphicon-pencil"></span>
					</button></p>';
				})
				->addColumn('delete', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-disabled="job_role!=\'SuperAdmin\'" ng-click="onDeleteRow('.$data->id.')">
						<span class="glyphicon glyphicon-trash"></span>
					</button></p>';
				})
				->rawColumns(['checkbox', 'edit', 'delete'])				
				->make(true);
    }

    public function create()
    {
		$step = '5';
		
		$buildlist = Building::lists('name', 'id');
		$model = new HskpStatus();
		
		return view('backoffice.wizard.guestservice.hskpcreate', compact('model', 'buildlist', 'step'));
    }

    public function store(Request $request)
    {
    	$model = new HskpStatus();

		$model->bldg_id = $request->bldg_id ?? '0';
		$model->status = $request->status ?? '';
		$model->pms_code = $request->pms_code ?? '0';
		
		$check_diff_ivr_code = $request->chk_ivr_flag ?? '0';
		$ivr_code = $request->pms_code ?? '0';
		if( $check_diff_ivr_code != 0 )
			$ivr_code = $request->ivr_code ?? '0';
		
		$model->ivr_code = $ivr_code;
		
		$typelist = $model->getTypeList();
		$type = $typelist[$request->type_id ?? '1'];
		
		$model->type = $type;
		$model->description = $request->description ?? '';
		$model->save();
		
		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		return $this->create();	
    }
	
	public function storeng(Request $request)
    {
    	$input = $request->except('id');
		foreach ($input as $key => $value) {
			if($value === null) $input[$key] = "";
		}
		
		$model = HskpStatus::create($input);
		
		return Response::json($model);			
    }

    public function show($id)
    {
        $model = HskpStatus::find($id);	
		
		return Response::json($model);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $model = HskpStatus::find($id);	
		if( empty($model) )
			$model = new HskpStatus();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = HskpStatus::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		return $this->index($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $model = HskpStatus::find($id);
		$model->delete();

		return $this->index($request);
    }

	public function getHskpLogs(Request $request) {
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$filter_value = $request->get('filter_value', '');

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('services_hskp_log as hl')
				->join('common_room as cr', 'hl.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->leftJoin('services_room_status as rs', 'rs.id', '=', 'cr.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->leftJoin('services_hskp_status as hs', 'hl.hskp_id', '=', 'hs.id')
				->join('common_users as cu', 'hl.user_id', '=', 'cu.id')
				->where('cb.property_id', $property_id);

		// get building ids
		$user_id = $request->get('user_id', 0);
		$building_ids = CommonUser::getBuildingIds($user_id);

		if( !empty($building_ids) )
		{
			$building_ids = explode(',', $building_ids);
			$query->whereIn('cf.bldg_id', $building_ids);
		}


		if($filter_value != '')
		{
			$query->where(function ($sub_query) use ($filter_value) {	
					$value = '%' . $filter_value . '%';
					$sub_query->where('hl.id', 'like', $value)
						->orWhere('cr.room', 'like', $value)
						->orWhere('hs.status', 'like', $value)
						->orWhere('cu.first_name', 'like', $value)
						->orWhere('cu.last_name', 'like', $value);				
				});
		}
		
		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('hl.*, cr.room,IFNULL( hs.status, "Default") as status , 
								hs.ivr_code, hs.pms_code, hs.type, hs.description, 
								CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->skip($skip)->take($pageSize)
				->get();

		foreach ($data_list as $key => $value) 
		{
			if($value->td_flag == 0)
			{
				switch ($value->state) 
				{
					case CLEANING_PENDING:	// 0
						$value->clean_state=CLEANING_PENDING_NAME;
						break;
					case CLEANING_RUNNING:	// 1
						$value->clean_state=CLEANING_RUNNING_NAME;
						break;
					case CLEANING_DONE:		// 2
						$value->clean_state = CLEANING_DONE_NAME;						
						break;
					case CLEANING_DND:		// 3
						$value->clean_state=CLEANING_DND_NAME;
						break;
					case CLEANING_DECLINE:	// 4
						$value->clean_state=CLEANING_DECLINE_NAME;
						break;				
					case CLEANING_POSTPONE:	// 5
						$value->clean_state=CLEANING_POSTPONE_NAME;
						break;
					case CLEANING_COMPLETE:	// 7
						$value->clean_state=CLEANING_COMPLETE_NAME;
						break;
					case CLEANING_PAUSE:	// 6
						$value->clean_state=CLEANING_PAUSE_NAME;
						break;	
					case CLEANING_DECLINE:	// 8
						$value->clean_state=CLEANING_PAUSE_NAME;
						break;	
					case 101:	// Rush Clean
						$value->clean_state='Rush Clean';
						break;		
					default:
						# code...
						break;
				}
			}
			else 
			{
				switch ($value->td_state) 
				{
					case CLEANING_PENDING:
						$value->clean_state='TD '.CLEANING_PENDING_NAME;
						break;
					case CLEANING_RUNNING:
						$value->clean_state='TD '.CLEANING_RUNNING_NAME;
						break;
					case CLEANING_DONE:
						$value->clean_state='TD '.CLEANING_DONE_NAME;
						break;
					case CLEANING_DND:
						$value->clean_state='TD '.CLEANING_DND_NAME;
						break;
					case CLEANING_DECLINE:
						$value->clean_state='TD '.CLEANING_DECLINE_NAME;
						break;				
					case CLEANING_POSTPONE:
						$value->clean_state='TD '.CLEANING_POSTPONE_NAME;
						break;
					case CLEANING_COMPLETE:
						$value->clean_state='TD '.CLEANING_COMPLETE_NAME;
						break;
					case CLEANING_PAUSE:
						$value->clean_state='TD '.CLEANING_PAUSE_NAME;
						break;	
					default:
						# code...
						break;
				}
			}
		}
		
		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function getHskpLogsForMobile(Request $request) {
		$property_id = $request->get('property_id', '0');
		date_default_timezone_set(config('app.timezone'));
		$last24 = date("Y-m-d H:i:s", strtotime("-1 Days"));

		$ret = array();

		$query = DB::table('services_hskp_log as hl')
				->join('common_room as cr', 'hl.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->leftJoin('services_room_status as rs', 'rs.id', '=', 'cr.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->leftJoin('services_hskp_status as hs', 'hl.hskp_id', '=', 'hs.id')
				->join('common_users as cu', 'hl.user_id', '=', 'cu.id')
				->where('cb.property_id', $property_id);

		// get building ids
		$user_id = $request->get('user_id', 0);
		$building_ids = CommonUser::getBuildingIds($user_id);

		if( !empty($building_ids) )
		{
			$building_ids = explode(',', $building_ids);
			$query->whereIn('cf.bldg_id', $building_ids);
		}

		$data_list = $query
				->where('hl.created_at', '>=', $last24)
				->orderBy('hl.id', 'desc')
				->select(DB::raw('hl.*, cr.room,IFNULL( hs.status, "Default") as status , 
								hs.ivr_code, hs.pms_code, hs.type, hs.description, 
								CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))				
				->get();

		foreach ($data_list as $key => $value) 
		{
			if($value->td_flag == 0)
			{
				switch ($value->state) 
				{
					case CLEANING_PENDING:	// 0
						$value->clean_state=CLEANING_PENDING_NAME;
						break;
					case CLEANING_RUNNING:	// 1
						$value->clean_state=CLEANING_RUNNING_NAME;
						break;
					case CLEANING_DONE:		// 2
						$value->clean_state = CLEANING_DONE_NAME;						
						break;
					case CLEANING_DND:		// 3
						$value->clean_state=CLEANING_DND_NAME;
						break;
					case CLEANING_DECLINE:	// 4
						$value->clean_state=CLEANING_DECLINE_NAME;
						break;				
					case CLEANING_POSTPONE:	// 5
						$value->clean_state=CLEANING_POSTPONE_NAME;
						break;
					case CLEANING_COMPLETE:	// 7
						$value->clean_state=CLEANING_COMPLETE_NAME;
						break;
					case CLEANING_PAUSE:	// 6
						$value->clean_state=CLEANING_PAUSE_NAME;
						break;	
					case CLEANING_DECLINE:	// 8
						$value->clean_state=CLEANING_PAUSE_NAME;
						break;	
					case 101:	// Rush Clean
						$value->clean_state='Rush Clean';
						break;		
					default:
						# code...
						break;
				}
			}
			else 
			{
				switch ($value->td_state) 
				{
					case CLEANING_PENDING:
						$value->clean_state='TD '.CLEANING_PENDING_NAME;
						break;
					case CLEANING_RUNNING:
						$value->clean_state='TD '.CLEANING_RUNNING_NAME;
						break;
					case CLEANING_DONE:
						$value->clean_state='TD '.CLEANING_DONE_NAME;
						break;
					case CLEANING_DND:
						$value->clean_state='TD '.CLEANING_DND_NAME;
						break;
					case CLEANING_DECLINE:
						$value->clean_state='TD '.CLEANING_DECLINE_NAME;
						break;				
					case CLEANING_POSTPONE:
						$value->clean_state='TD '.CLEANING_POSTPONE_NAME;
						break;
					case CLEANING_COMPLETE:
						$value->clean_state='TD '.CLEANING_COMPLETE_NAME;
						break;
					case CLEANING_PAUSE:
						$value->clean_state='TD '.CLEANING_PAUSE_NAME;
						break;	
					default:
						# code...
						break;
				}
			}
		}
		
		$ret['code'] = 200;
		$ret['content'] = $data_list;

		return Response::json($ret);
	}

	public function getStatisticInfo(Request $request)
	{
		$period = $request->get('period', 'Today');
		$end_date = $request->get('end_date', '');
		$during = $request->get('during', '');

		$ret = array();
		switch($period)
		{
			case 'Today';
				$ret = $this->getStaticsticsByToday($request);
				break;
			case 'Weekly';
				$ret = $this->getStaticsticsByDate($end_date, 7);
				break;
			case 'Monthly';
				$ret = $this->getStaticsticsByDate($end_date, 30);
				break;
			case 'Custom Days';
				$ret = $this->getStaticsticsByDate($end_date, $during);
				break;
			case 'Yearly';
				$ret = $this->getStaticsticsByDate($end_date, 365);
				break;
		}

		return Response::json($ret);
	}

	public function getStaticsticsByToday(Request $request)
	{		
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$room_status = DB::table('common_room as cr')
						->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
						->leftJoin('services_room_status as hl', 'cr.id', '=', 'hl.id')
						->leftJoin('services_devices as sd', 'hl.device_ids','=', 'sd.device_id')
						->leftJoin('common_users as cu', 'sd.device_id', '=', 'cu.device_id');
						// ->whereRaw("DATE(hl.start_time) = '" . $cur_date . "'");

		// get building ids
		$user_id = $request->get('user_id', 0);
		$building_ids = CommonUser::getBuildingIds($user_id);

		if( !empty($building_ids) )
		{
			$building_ids = explode(',', $building_ids);
			$room_status->whereIn('cf.bldg_id', $building_ids);
		}
				

		$total_count = $this->getTotalHskpCount($room_status);

		$ret = array();

		// By revenue
		$today_query = clone $room_status;
		$by_status_count = $today_query
				->groupBy('status')
				->orderBy('status')
				->select(DB::raw('count(*) as cnt, CONCAT_WS(" ", hl.occupancy, hl.room_status) as status'))
				->get();


		$ret['by_status_count'] = $by_status_count;

		// By Posted by User
		$today_query = clone $room_status;
		$by_user_count = $today_query
				->groupBy('wholename')
				->orderBy('wholename')
				->select(DB::raw('count(*) as cnt, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();


		$ret['by_user_count'] = $by_user_count;

		$ret['total'] = $total_count;
		// $ret['building_ids'] = $building_ids;

		return $ret;
	}

	public function getStaticsticsByDate($end_date, $during)
	{
		$query = DB::table('services_hskp_log as hl')
				->leftJoin('services_hskp_status as hs', 'hl.hskp_id', '=', 'hs.id')
				->leftJoin('common_users as cu', 'hl.user_id', '=', 'cu.id');
		

		$ret = array();

		// By task
		$datetime = new DateTime($end_date);
		$datetime->sub(new DateInterval('P' . $during . 'D'));
		$start_date = $datetime->format('Y-m-d');

		$time_range = sprintf("'%s' < DATE(hl.created_at) AND DATE(hl.created_at) <= '%s'", $start_date, $end_date);
		$time_range1 = sprintf("'%s' < DATE(hl.updated_at) AND DATE(hl.updated_at) <= '%s'", $start_date, $end_date);

	//	$total_query = clone $query;
	//	$total_query->whereRaw($time_range);

		$room_status = DB::table('services_room_status as hl')
				->whereRaw($time_range1);

		$ret['time_range'] = $time_range;

		$total_count = $this->getTotalHskpCount($room_status);

		$ret['total'] = $total_count;

		// By revenue
		$today_query = clone $query;
		$by_status_count = $today_query
				->whereRaw($time_range)
				->groupBy('hl.hskp_id')
				->orderBy('hl.hskp_id')
				->select(DB::raw('count(*) as cnt, hs.status'))
				->get();

		$ret['by_status_count'] = $by_status_count;

		// By Department
		$today_query = clone $query;
		$by_user_count = $today_query
				->whereRaw($time_range)
				->groupBy('hl.user_id')
				->orderBy('hl.user_id')
				->select(DB::raw('count(*) as cnt, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();

		$ret['by_user_count'] = $by_user_count;

		return $ret;
	}

	public function createTriggerTask(Request $request)
	{
		$input = $request->all();

		if( $input['id'] <= 0 )
			DB::table('services_trigger_task')->insert($input);
		else {
			DB::table('services_trigger_task')
					->where('id', $input['id'])
					->update($input);
		}

		$ret = array();

		return Response::json($ret);
	}

	public function activeTriggerTask(Request $request)
	{
		$attendant = $request->get('attendant', '0');
		$property_id = $request->get('property_id', '0');
		$active = $request->get('active', '0');
		$id = $request->get('id', '0');

		if( $id == 0 )
		{
			DB::table('services_trigger_task as tt')
					->where('tt.attendant', $attendant)
					->update(['active' => $active]);	// active state
		}
		else
		{
			DB::table('services_trigger_task as tt')
					->where('tt.id', $id)
					->update(['active' => $active]);	// active state
		}

		$ret = array();

		return Response::json($ret);
	}

	public function deleteTriggerTask(Request $request)
	{
		$id = $request->get('id');
		DB::table('services_trigger_task')
				->where('id', $id)
				->delete();

		$ret = array();

		return Response::json($ret);
	}

	public function getTriggerTaskList(Request $request) {
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$attendant = $request->get('attendant', '0');
		$property_id = $request->get('property_id', '0');

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('services_trigger_task as tt')
				->join('services_task_list as tl', 'tt.task_id', '=', 'tl.id')
				->where('tt.attendant', $attendant);

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('tt.*, tl.id as task_id, tl.task as task_name'))
				->skip($skip)->take($pageSize)
				->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$room_type = DB::table('common_room_type as rt')
//				->join('common_building as cb', 'rt.bldg_id', '=', 'cb.id')
//				->where('cb.property_id', $property_id)
				->select(DB::raw('rt.*'))
				->get();

		$room_status = DB::table('services_hskp_status as hs')
				->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
				->where('cb.property_id', $property_id)
				->select(DB::raw('hs.*'))
				->groupBy('hs.status')
				->get();


		$guest_type = DB::table('common_guest_type as gt')
				->where('gt.property_id', $property_id)
				->select(DB::raw('gt.*'))
				->get();

		$user_group = DB::table('common_user_group as ug')
				->where('ug.property_id', $property_id)
				->select(DB::raw('ug.*'))
				->get();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		$ret['room_type'] = $room_type;
		$ret['room_status'] = $room_status;
		$ret['guest_type'] = $guest_type;
		$ret['user_group'] = $user_group;

		return Response::json($ret);
	}

	
	public function deleteCheckList(Request $request)
	{
		$id = $request->get('id');

		DB::table('services_checklist_pivot')
				->where('name_id', $id)
				->delete();

		DB::table('services_checklist')
				->where('id', $id)
				->delete();

		$ret = array();

		return Response::json($ret);
	}

	public function getCheckListNames(Request $request) {
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$dept_id = $request->get('dept_id', '0');
		$property_id = $request->get('property_id', '0');

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('services_checklist as cl')
				->join('common_job_role as jr', 'cl.job_role_id', '=', 'jr.id')
				->where('cl.dept_id', $dept_id);

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('cl.*, jr.id as jr_id, jr.job_role'))
				->skip($skip)->take($pageSize)
				->get();

		for($i = 0; $i < count($data_list); $i++ ) {
			$items = DB::table('services_checklist_item as ci')
					->join('services_checklist_pivot as cp', 'cp.item_id', '=', 'ci.id')
					->where('cp.name_id', $data_list[$i]->id)
					->select(DB::raw('ci.*'))
					->get();
			$data_list[$i]->items = $items;
		}

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$room_type = DB::table('common_room_type as rt')
//				->join('common_building as cb', 'rt.bldg_id', '=', 'cb.id')
//				->where('cb.property_id', $property_id)
				->select(DB::raw('rt.*'))
				->get();

		$room_status = DB::table('services_hskp_status as hs')
				->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
//				->where('cb.property_id', $property_id)
				->select(DB::raw('hs.*'))
				->get();

		$job_roles = DB::table('common_job_role as jr')
				->where('jr.dept_id', $dept_id)
				->select(DB::raw('jr.*'))
				->get();


		$check_list_items = DB::table('services_checklist_item')
				->get();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		$ret['room_type'] = $room_type;
		$ret['job_roles'] = $job_roles;
		$ret['check_list_items'] = $check_list_items;

		return Response::json($ret);
	}

	public function getRoomtypeList(Request $request) {
		$job_role_id = $request->get('id', '0');

		$checklist = DB::table('services_checklist')
			->where('job_role_id', $job_role_id)
			->get();

		$selected_ids = array();
		if( !empty($checklist) )
		{
			for($i = 0; $i < count($checklist); $i++) {
				$room_type_ids = json_decode($checklist[$i]->room_type);
				$selected_ids = array_merge($selected_ids, $room_type_ids);
			}
		}


		// get room type for that job role id
		$room_type = DB::table('common_room_type as rt')
				->whereNotIn('id', $selected_ids)
				->select(DB::raw('rt.*'))
				->get();

		return Response::json($room_type);
	}

	public function getShiftList(Request $request){
		$property_id = $request->get('property_id', 0);
		$dept_id = $request->get('dept_id', 0);

		$shifts = DB::table('services_shift_group as sg')
				->join('services_shifts as sh', 'sg.shift', '=', 'sh.id')
				->where('sg.dept_id', $dept_id)
				->select(['sg.*', 'sh.name as shname'])
				->get();

		$floors = DB::table('common_floor as cf')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->where('cb.property_id', $property_id)
				->select(DB::raw('cf.*, cb.name, CONCAT_WS(" - ", cb.name, cf.floor) as floor_name'))
				->get();

		$ret = array();
		$ret['shifts'] = $shifts;
		$ret['floors'] = $floors;

		return Response::json($ret);
	}

	public function getRoomShiftList(Request $request){
		$property_id = $request->get('property_id', 0);
		$dept_id = $request->get('dept_id', 0);

		$job_roles = PropertySetting::getJobRoles($property_id);

		$shifts = DB::table('services_shifts as sh')
				->where('sh.property_id', $property_id)
				->select(DB::raw('sh.*, sh.name as item_name'))
				->get();

		$attendantlist = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.job_role_id', $job_roles['roomattendant_job_role'])
				->where('cu.deleted', 0)
				->where('cd.property_id', $property_id)
				->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as item_name'))
				->get();

		$supervisorlist = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.job_role_id', $job_roles['supervisor_job_role'])
				->where('cu.deleted', 0)
				->where('cd.property_id', $property_id)
				->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as item_name'))
				->get();

		$ret = array();
		$ret['shifts'] = $shifts;
		$ret['attendant_list'] = $attendantlist;
		$ret['supervisor_list'] = $supervisorlist;

		return Response::json($ret);
	}

	// public function getAvailableRoomList(Request $request){
	// 	$floor_list = $request->get('floors', array());
	// 	$selected = $request->get('selected', array());
	// 	$dispatcher = $request->get('dispatcher', 0);

	// 	$shift_info = DB::table('services_shift_group_members')
	// 		->where('user_id', $dispatcher)
	// 		->first();

	// 	if( empty($shift_info) )
	// 		return Response::json(array());

	// 	$assigned = DB::table('services_room_assignment as ra')
	// 		->join('services_shift_group_members as sgm', 'ra.dispatcher', '=', 'sgm.user_id')
	// 		->where('ra.dispatcher', '!=', $dispatcher) // different dispatcher
	// 		->where('sgm.shift_group_id', $shift_info->shift_group_id)
	// 		->select(DB::raw('ra.room_id'))
	// 		->get();

	// 	$assinged_id = array();
	// 	if( !empty($assigned) )
	// 	{
	// 		for($i = 0; $i < count($assigned); $i++ )
	// 		{
	// 			$assinged_id[$i] = $assigned[$i]->room_id;
	// 		}
	// 	}

	// 	$room_list = DB::table('common_room as cr')
	// 		->whereIn('flr_id', $floor_list)
	// 		->whereNotIn('id', $assinged_id)
	// 		->whereNotIn('id', $selected)
	// 		->select(DB::raw('cr.*'))
	// 		->get();

	// 	$ret = array();
	// 	$ret['room_list'] = $room_list;
	// 	$ret['unavaible'] = $assigned;

	// 	return Response::json($ret);
	// }

	public function changeRoomAssignment(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$dispatcher = $request->get('dispatcher', 0);
		$room_ids = $request->get('room_ids', 0);

		DB::table('services_room_assignment')
				->where('dispatcher', $dispatcher)
				->delete();

		for($i = 0; $i < count($room_ids); $i++) {
			$data = array();
			$data['dispatcher'] = $dispatcher;
			$data['room_id'] = $room_ids[$i];

			DB::table('services_room_assignment')->insert($data);

			$room_id = $room_ids[$i];

			$room_info = DB::table('common_room as cr')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*'))
				->first();

			$hskp_log = new HskpStatusLog();

			$hskp_log->room_id = $room_id;
			$hskp_log->hskp_id = $room_info->hskp_status_id;
			$hskp_log->user_id = $dispatcher;
			$hskp_log->state = CLEANING_PENDING;	// Pending

			$hskp_log->created_at = $cur_time;

			$hskp_log->save();

            // Functions::sendHskpStatusChangeWithRoom($room_id);
		}
		
		if( count($room_ids) > 0 )
		{
			$data = Room::getPropertyBuildingFloor($room_ids[0]);
			if( !empty($data) )
				Functions::sendHskpStatusChangeToProperty($data->property_id);
		}
		
		return Response::json(array());
	}

	public function getAssignedRoomListToStaff(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$dispatcher = $request->get('dispatcher', 0);
		$property_id = $request->get('property_id', 0);

		$ret = array();

		$query = DB::table('services_room_status as rs')
				->join('common_room as cr', 'rs.id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
				->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
				// ->leftJoin('common_guest_advanced_detail as gad', 'rs.id', '=', 'gad.id')
				->where('rs.dispatcher', $dispatcher);

		$count_query = clone $query;
		$data_query = clone $query;

		$data_list = $data_query				
				->orderby('rs.start_time')
				->orderby('rs.priority')
				->orderby('rs.id')
				->select(DB::raw('cr.*, hs.status, rt.max_time, rt.type as room_type, cf.floor,
					rs.dispatcher, rs.room_status, rs.occupancy, rs.working_status, rs.rush_flag, rs.arrival, rs.due_out, rs.priority, rs.start_time, rs.end_time
					'))
				->get();

		$vacant = VACANT;
		$occupied = OCCUPIED;
		$dirty = DIRTY;
		$clean = CLEAN;
		$sub_count = $count_query
			->select(DB::raw("
					sum(rs.occupancy = '$vacant') as check_out,
					sum(rs.occupancy = '$occupied') as check_in,
					sum(rs.rush_flag = 1) as rush_clean,
					sum(rs.room_status = '$dirty') as dirty,
					sum(rs.room_status = '$clean') as clean,
					sum(rs.due_out = 1) as due_out,
					sum(rs.arrival = 1) as arrival
					"))
			->first();

		Guest::getGuestDetail($data_list);

		$ret['code'] = 200;
		$ret['datalist'] = $data_list;
		$ret['sub_count'] = $sub_count;
		$hskp_setting_value = PropertySetting::getHskpSettingValue($property_id);
		$ret['hskp_setting_value'] = $hskp_setting_value;

		return Response::json($ret);
	}

	public function getAssignedRoomListToStaffForTurndown(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$dispatcher = $request->get('dispatcher', 0);
		$property_id = $request->get('property_id', 0);

		$ret = array();

		$query = DB::table('services_room_turndown_status as rs')
				->join('common_room as cr', 'rs.id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
				->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
				// ->leftJoin('common_guest_advanced_detail as gad', 'rs.id', '=', 'gad.id')
				->where('rs.dispatcher', $dispatcher);

		$count_query = clone $query;
		$data_query = clone $query;

		$data_list = $data_query				
				->orderby('rs.start_time')				
				->orderby('rs.id')
				->select(DB::raw('cr.*, hs.status, rt.turn_down as max_time, rt.type as room_type, cf.floor,
					rs.dispatcher, rs.working_status, rs.rush_flag, rs.start_time, rs.end_time
					'))
				->get();

		Guest::getGuestDetail($data_list);

		$vacant = VACANT;
		$occupied = OCCUPIED;
		$pending = CLEANING_PENDING;
		$done = CLEANING_DONE;
		$sub_count = $count_query
			->select(DB::raw("
					COALESCE(sum(rs.rush_flag = 1), 0) as rush_clean,
					COALESCE(sum(rs.working_status = '$pending'), 0) as pending,
					COALESCE(sum(rs.working_status = '$done'), 0) as done
					"))
			->first();

		$ret['code'] = 200;
		$ret['datalist'] = $data_list;
		$ret['sub_count'] = $sub_count;
		$hskp_setting_value = PropertySetting::getHskpSettingValue($property_id);
		$ret['hskp_setting_value'] = $hskp_setting_value;

		return Response::json($ret);
	}

	public function getAssignedRoomList(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$user_id = $request->get('user_id', 0);
		$filter = $request->get('filter', "");

		// check job role
		$user = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.id', $user_id)
				->select(DB::raw('cu.*, cd.property_id'))
				->first();

		$query = DB::table('services_room_status as rs')
				->join('common_room as cr', 'rs.id', '=', 'cr.id')
				->leftJoin('common_room_type as crt', 'cr.type_id', '=', 'crt.id')
				->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id');

		$job_roles = PropertySetting::getJobRoles($user->property_id);		
			
		if( $user->job_role_id == $job_roles['roomattendant_job_role'] )
		{
			$assigned = $query
				->where('rs.dispatcher', $user_id);
		}
		else if( $user->job_role_id == $job_roles['supervisor_job_role'] )
		{
			
		}

		$start = microtime(true);

		$assigned = $query				
				->orderBy('rs.start_time')
				->select(DB::raw("cr.*, hs.status as hskp_status, crt.type as room_type,
					rs.dispatcher, rs.room_status, rs.occupancy, rs.working_status, rs.rush_flag, rs.arrival, rs.due_out, rs.priority, rs.start_time, rs.end_time, 
					(select count(*) from common_guest where room_id = rs.id and departure >= '$cur_date' and checkout_flag = 'checkin') guest_cnt,
					(select guest_name from common_guest where room_id = rs.id and departure >= '$cur_date' and checkout_flag = 'checkin' order by created_at limit 1) guest_name"))
				->get();	


		for($i = 0; $i < count($assigned); $i++) {						
			// $guest_list = DB::table('common_guest as cg')
			// 	->where('cg.room_id', $assigned[$i]->id)
			// 	->where('cg.departure', '>=', $cur_date)
			// 	->where('cg.checkout_flag', 'checkin')
			// 	->orderBy('cg.created_at', 'desc')
			// 	->get();

			if( $assigned[$i]->guest_cnt < 1 )		// thre is no checkin 
			{
				$assigned[$i]->guest_name = 'Vacant';					
			} 	
			else
			{
				if($assigned[$i]->guest_cnt > 1)
					$assigned[$i]->guest_name .= ' - ' . ($assigned[$i]->guest_cnt - 1) . 'pax';
			}

			
			switch ($assigned[$i]->working_status) {
				case CLEANING_PENDING:
					$assigned[$i]->cleaning_state = 'Pending';
					break;
				case CLEANING_RUNNING:
					$assigned[$i]->cleaning_state = 'Cleaning';
					break;
				case CLEANING_DONE:
					$assigned[$i]->cleaning_state = 'Done';
					break;
				case CLEANING_DND:
					$assigned[$i]->cleaning_state = 'DND';
					break;	
				case CLEANING_DECLINE:
					$assigned[$i]->cleaning_state = 'Declined';
					break;		
				case CLEANING_POSTPONE:
					$assigned[$i]->cleaning_state = 'Postponed';
					break;	
				case CLEANING_COMPLETE:
					$assigned[$i]->cleaning_state = 'Inspected';
					break;				
				default:
					$assigned[$i]->cleaning_state = 'Pending';
					break;
			}
			
		}
		$end = microtime(true);



		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = $user_id;
		$ret['time'] = $end - $start;

		if(!empty($filter))
		{
			$filterlist = explode(",", $filter);
			if( in_array("ALL", $filterlist) )
				$ret['content'] = $assigned;
			else
			{
				$filtered_room_list = array();
				foreach($assigned as $row)
				{
					if( !empty($row->progress) )
					{						
						if( $row->working_status == CLEANING_PENDING && in_array(CLEANING_PENDING_NAME, $filterlist) )
							array_push($filtered_room_list, $row);
						else if( $row->working_status == CLEANING_POSTPONE && in_array(CLEANING_POSTPONE_NAME, $filterlist) )
							array_push($filtered_room_list, $row);
						else if( $row->working_status == CLEANING_DONE && in_array(CLEANING_DONE_NAME, $filterlist) )
							array_push($filtered_room_list, $row);
						else if( $row->working_status == CLEANING_DND && in_array(CLEANING_DND_NAME, $filterlist) )
							array_push($filtered_room_list, $row);
						else if( $row->working_status == CLEANING_DECLINE && in_array(CLEANING_DECLINE_NAME, $filterlist) )
							array_push($filtered_room_list, $row);
					}					
					else
					{
						if( in_array(CLEANING_PENDING_NAME, $filterlist) )
							array_push($filtered_room_list, $row);
					}
				}

				$ret['content'] = $filtered_room_list;				
			}

		}
		else
			$ret['content'] = $assigned;
		

		return Response::json($ret);
	}

	public function getAssignedRoomListForTurndown(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$user_id = $request->get('user_id', 0);

		$assigned = DB::table('services_room_turndown_status as rs')
				->join('common_room as cr', 'rs.id', '=', 'cr.id')
				->leftJoin('common_room_type as crt', 'cr.type_id', '=', 'crt.id')
				->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
				->where('rs.dispatcher', $user_id)
				->orderBy('rs.start_time')				
				->select(DB::raw('cr.*, hs.status as hskp_status, crt.type as room_type,
					rs.dispatcher, rs.working_status, rs.rush_flag, rs.start_time, rs.end_time'))
				->get();

		for($i = 0; $i < count($assigned); $i++) {						
			$guest_list = DB::table('common_guest as cg')
				->where('cg.room_id', $assigned[$i]->id)
				->where('cg.departure', '>=', $cur_date)
				->where('cg.checkout_flag', 'checkin')
				->orderBy('cg.departure', 'desc')
				->orderBy('cg.arrival', 'desc')
				->get();

			if( count($guest_list) < 1 )		// thre is no checkin 
			{
				$assigned[$i]->guest_name = 'Vacant';					
			} 	
			else
			{
				$assigned[$i]->guest_name = $guest_list[0]->guest_name;					
				if(count($guest_list) > 1)
					$assigned[$i]->guest_name .= ' - ' . (count($guest_list) - 1) . 'pax';
			}

			
			switch ($assigned[$i]->working_status) {
				case CLEANING_PENDING:
					$assigned[$i]->cleaning_state = 'Pending';
					break;
				case CLEANING_RUNNING:
					$assigned[$i]->cleaning_state = 'Cleaning';
					break;
				case CLEANING_DONE:
					$assigned[$i]->cleaning_state = 'Done';
					break;				
			}
			
		}

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = $user_id;
		$ret['content'] = $assigned;
		
		return Response::json($ret);
	}

	public function getAttedantRoomCount(Request $request)
	{
		$property_id = $request->get('property_id', '0');		
		$device_id = $request->get('device_id', '');
		$user_id = $request->get('user_id', '0');		
		
		$roster_ids = RosterList::getRosterIds($device_id, $user_id);		
		$hskp_role = 'Attendant';

		$ret = $this->getAssignedRoomCount($property_id, $roster_ids, $hskp_role, [], []);

		return Response::json($ret);
	}

	public function getSupervisorRoomCount(Request $request)
	{
		$property_id = $request->get('property_id', '0');		
		$device_id = $request->get('device_id', '');
		$user_id = $request->get('user_id', '0');		

		$roster_ids = RosterList::getRosterIds($device_id, $user_id);	
		$hskp_role = 'Supervisor';

		$ret = $this->getAssignedRoomCount($property_id, $roster_ids, $hskp_role, [], []);

		return Response::json($ret);
	}

	public function getRosterRoomCount(Request $request)
	{
		$device_id = $request->get('device_id', '');
		$user_id = $request->get('user_id', 0);
		$roster_id = $request->get('roster_id', 0);
		$hskp_role = $request->get('hskp_role', 'Attendant');
		$property_id = CommonUser::getPropertyID($user_id);		

		$roster_ids = [$roster_id];

		$binded_attendant_ids = [];
		$binded_supervisor_ids = [];
		if( $hskp_role == 'Attendant')
			$binded_supervisor_ids = RosterList::getRosterIds($device_id, $user_id);	
		if( $hskp_role == 'Supervisor')
			$binded_attendant_ids = RosterList::getRosterIds($device_id, $user_id);		

		$ret = $this->getAssignedRoomCount($property_id, $roster_ids, $hskp_role, $binded_attendant_ids, $binded_supervisor_ids);

		return Response::json($ret);
	}

	public function getAssignedRoomCount($property_id, $roster_ids, $hskp_role, $binded_attendant_ids, $binded_supervisor_ids)
	{
		if( $hskp_role == 'Supervisor' )	
				$roster = RosterList::where('id', $roster_ids)->select('location_list')->first();
		if(( $hskp_role == 'Attendant' ) && count($binded_supervisor_ids) > 0 )
				$roster = RosterList::where('id', $binded_supervisor_ids)->select('location_list')->first();
		
		if (!empty($roster)){
			$location = json_decode($roster->location_list);
		}
		if (empty($location))
		 	$location = [];
			 
		$query = DB::table('services_room_working_status')
				->where('status_id', '!=', CLEANING_NOT_ASSIGNED);

		if( $hskp_role == 'Attendant' )		
			$query->orderBy('attendant_order');

		if( $hskp_role == 'Supervisor' )		
			$query->orderBy('supervisor_order');	
	
		$status_list = $query->get();
			
		$select_sql = "count(*) as total";
		foreach($status_list as $row)
		{
			$select_sql .= ",COALESCE(SUM(rs.working_status = $row->status_id), 0) as $row->status_name";			
		}	

		$query = DB::table('services_room_status as rs')
				->join('common_room as cr', 'rs.id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
				->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id');

		if( $hskp_role == 'Attendant' )
			$query->whereIn('rs.attendant_id', $roster_ids);

		if(($hskp_role == 'Supervisor' ) && count($location) > 0)
			$query->whereIn('rs.supervisor_id', $roster_ids);	
			
		if( count($binded_attendant_ids) > 0 )
			$query->whereIn('rs.attendant_id', $binded_attendant_ids);

		if( (count($binded_supervisor_ids) > 0 ) && count($location) > 0)
			$query->whereIn('rs.supervisor_id', $binded_supervisor_ids);	

		$status_count = $query->where('cb.property_id', $property_id)
				->select(DB::raw($select_sql))
				->first();

	
		
		
		$myrooms[] = ['name' => 'All', 'count' => $status_count->total];
		foreach($status_list as $row)
		{
			$myrooms[] = ['name' => $row->status_name, 'count' => $status_count->{$row->status_name}];
		}

		$ret = array();
		$ret['code'] = 200;
		$ret['roster_ids'] = $roster_ids;
  		$ret['content'] = $myrooms;
		$ret['sortby'] = [
			['name' => 'Room'],
			['name' => 'Floor'],
			['name' => 'Room Type'],
			['name' => 'Occupancy'],
			['name' => 'Cleaning Status']
		];
		
		return ($ret);
	}


	public function getRoomListForHskp(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$device_id = $request->get('device_id', '');
		$user_id = $request->get('user_id', 0);
		$hskp_role = $request->get('hskp_role', 'Attendant');
		$filters = $request->get('filter', 'All');
		$sortby = $request->get('sortby', '');
		
		$ret = array();
		
		$roster_ids = RosterList::getRosterIds($device_id, $user_id);

		$room_list = HskpRoomStatus::getRoomListForRosterList($roster_ids, $hskp_role, $filters, $sortby);	
		
		if($hskp_role == 'Attendant')
		{
			foreach($room_list as $row)
			{
				// get supervisor's checklist is valid	
				$checklist = DB::table('services_hskp_log')
					->where('room_id', $row->id)
					->whereIn('state', array(CLEANING_COMPLETE, CLEANING_DECLINE) )					
					->orderBy('id', 'desc')
					->first();			

				if( empty($checklist) || $checklist->check_num == 0 )	
					$row->supervisor_check_num = 0;
					if(!empty($checklist) && !empty($checklist->reason)) {
						$row->reason = $checklist->reason;
					} else {
						$row->reason = "";
					}
				}
			} else if($hskp_role == 'Supervisor') {
			foreach($room_list as $row)
			{
				// get supervisor's checklist is valid	
				$checklist = DB::table('services_hskp_log')
					->where('room_id', $row->id)
					->where('state', CLEANING_DONE)
					->orderBy('id', 'desc')
					->first();			

				if( empty($checklist) || $checklist->check_num == 0 )	
					$row->attendant_check_num = 0;
					
					if(!empty($checklist) && !empty($checklist->reason)) {
						$row->reason = $checklist->reason;
					} else {
						$row->reason = "";
					}
			}
		}
		
		$ret['content'] = $room_list;
		$ret['code'] = 200;
		
		return Response::json($ret);
	}

	public function getRoomListForMobileRoster(Request $request)
	{
		$device_id = $request->get('device_id', '');
		$property_id = $request->get('property_id', '');
		$user_id = $request->get('user_id', 0);
		$roster_id = $request->get('roster_id', 0);
		$hskp_role = $request->get('hskp_role', 'Attendant');
		$filters = $request->get('filter', 'All');
		$sortby = $request->get('sortby', '');

		$binded_attendant_ids = [];
		$binded_supervisor_ids = [];
		if( $hskp_role == 'Attendant')
			$binded_supervisor_ids = RosterList::getRosterIds($device_id, $user_id);	
		if( $hskp_role == 'Supervisor')
			$binded_attendant_ids = RosterList::getRosterIds($device_id, $user_id);	
		
		$ret = array();
		
		$room_list = HskpRoomStatus::getRoomListForRosterWithBinded($roster_id, $hskp_role, $binded_attendant_ids, $binded_supervisor_ids, $filters, $sortby);	

		HskpRoomStatus::updateRoomCredits($room_list, $property_id);		

		$ret['content'] = $room_list;
		$ret['code'] = 200;
		
		return Response::json($ret);
	}

	public function getAttendantListForSupervisor(Request $request)
	{
		$device_id = $request->get('device_id', '');
		$user_id = $request->get('user_id', 0);

		$property_id = CommonUser::getPropertyID($user_id);

		$ret = array();
		
		$roster_ids = RosterList::getRosterIds($device_id, $user_id);

		$query = DB::table('services_room_status as srs')
						->join('services_roster_list as rs', 'srs.attendant_id', '=', 'rs.id')
						->join('services_devices as sd', 'sd.id', '=', 'rs.device')				
						->leftJoin('common_users as cu', 'sd.device_id', '=', 'cu.device_id')
						->leftJoin('common_users as cu1', 'rs.user_id', '=', 'cu1.id')
						->where('srs.attendant_id', '>', 0);

		$own_query = clone $query;		
		$total_query = clone $query;		

		$own_query->whereIn('srs.supervisor_id', $roster_ids);							
		$total_query->where('srs.property_id', $property_id);

		$query_list = [$own_query, $total_query];
		
		foreach($query_list as $row)
		{			
			$row->groupBy('srs.attendant_id')
				->select(DB::raw('sd.name as device_name, 
								CASE WHEN rs.user_id > 0 THEN CONCAT_WS(" ", cu1.first_name, cu1.last_name)
									ELSE CONCAT_WS(" ", cu.first_name, cu.last_name) 
								END as wholename, 
								sd.device_id, srs.attendant_id,
								count(srs.id) as room_count,							
								COALESCE(sum(srs.working_status = 2 OR srs.working_status = 7), 0) as done_rooms,				
								(CASE WHEN sum(srs.td_flag = 1) > 0 THEN 1 ELSE 0 END) as td_flag
								'));
		}

		$attendant_list = $own_query->get();
		if( count($attendant_list) == 0 )
			$attendant_list = $total_query->get();
	
		$ret['content'] = $attendant_list;

		$ret['code'] = 200;
		$ret['message'] = '';

		return Response::json($ret);
	}

	public function getAssignedSummary(Request $request)
	{
		$device_id = $request->get('device_id', '');
		$user_id = $request->get('user_id', '0');		
		$hskp_role = $request->get('hskp_role', 'Attendant');		
		
		$hskp_role = 'Attendant';
		// both device based and user based
		$roster_list = DB::table('services_roster_list as rs')
				->join('services_devices as sd', 'sd.id', '=', 'rs.device')				
				->where('sd.device_id',$device_id)
				->orWhere('rs.user_id', $user_id)
				->select(DB::raw('rs.*'))
				->get();

		$roster_ids = [];
		foreach($roster_list as $row)
			$roster_ids[] = $row->id;			

		$roster_ids = [];
		foreach($roster_list as $row)
			$roster_ids[] = $row->id;			

		$query = DB::table('services_room_status as rs')
					->join('common_room as cr', 'rs.id', '=', 'cr.id');

		if( $hskp_role == 'Attendant' )
			$query->whereIn('rs.attendant_id', $roster_ids);

		if( $hskp_role == 'Supervisor' )
			$query->whereIn('rs.supervisor_id', $roster_ids);	

		$select_sql = "COALESCE(SUM(cr.credits), 0) as credits, 
						COALESCE(SUM(rs.occupancy = 'Vacant'), 0) as checkout,
						COALESCE(SUM(rs.occupancy = 'Occupied'), 0) as checkin
						";	

		$temp_query = clone $query;				
		$total_summary = $temp_query->select(DB::raw($select_sql))
						->first();	

		// finished room				
		$temp_query = clone $query;				

		$temp_query->whereIn('rs.room_status', array('Clean', 'Inspected'));
		$finished_summary = $temp_query->select(DB::raw($select_sql))
						->first();		

		// cleanning time				
		$temp_query = clone $query;
		$temp_query->whereIn('rs.working_status', array(CLEANING_DONE, CLEANING_COMPLETE));
		$time = $temp_query->select(DB::raw("SEC_TO_TIME(COALESCE(SUM(TIME_TO_SEC(TIMEDIFF(end_time, start_time))), 0)) as total_time"))
							->first();	

		$request = DB::table('services_task as st')
					->where('st.dispatcher', $user_id)
					->whereIn('status_id', array(1, 2))
					->count();
					
		$other_count = [
					'request' => $request,
					'linen' => 10,
					'minibar' => 0,
					'lostfound' => 0,
					];

		$summary = array();
		$summary['total'] = $total_summary;
		$summary['finished'] = $finished_summary;
		$summary['others'] = $other_count;
		$summary['total_time'] = $time->total_time;

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $summary;

		return Response::json($ret);
	}

	public function getHskpInfoWithRoom(Request $request) {
		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 0);
		$room = $request->get('room', 0);

		$ret = array();
		$ret['code'] = 200;
		
		$room_info = Room::getPropertyBuildingFloorFromRoom($room, $property_id);
		if( empty($room_info) )
		{
			$ret['code'] = 201;
			return Response::json($ret);
		}

		return $this->getHskpInfoData($room_info, $user_id); 
	}

	public function getHskpInfo(Request $request) {
		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);

		$ret = array();
		$ret['code'] = 200;
		
		$room_info = Room::getPropertyBuildingFloor($room_id);
		if( empty($room_info) )
		{
			$ret['code'] = 201;
			return Response::json($ret);
		}

		return $this->getHskpInfoData($room_info, $user_id); 
	}

	public function getHskpInfoData($room_info, $user_id) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$room_id = $room_info->id;

		$user = CommonUser::find($user_id);
		$job_role_id = $user->job_role_id;

		$room_type = $room_info->type_id;

		$checklist = DB::table('services_checklist as cl')
				->where('job_role_id', $job_role_id)
				->where(function ($query) use ($room_type) {
					$query->where('cl.room_type', '[' . $room_type . ']')
							->orWhere('cl.room_type', 'like', '[' . $room_type . ',%')
							->orWhere('cl.room_type', 'like', '%,' . $room_type . ']')
							->orWhere('cl.room_type', 'like', '%,' . $room_type . ',%');
				})
				->first();

		$datalist = array();

		if( !empty($checklist) )
		{
			$datalist = DB::table('services_checklist_pivot as cp')
					->join('services_checklist_item as ci', 'cp.item_id', '=', 'ci.id')
					->where('cp.name_id', $checklist->id)
					->select(DB::raw('ci.*'))
					->get();
		}

		$guest = DB::table('common_guest')
			->where('room_id', $room_id)
			->where('departure', '>=', $cur_date)
			->orderBy('departure', 'desc')
			->orderBy('arrival', 'desc')
			->first();

		$hskp_status = DB::table('services_hskp_log')
				->where('room_id', $room_id)
				->where('user_id', $user_id)
				->orderBy('id', 'desc')
				->first();

		$content = array();

		$content['checklist'] = $datalist;
		$content['guest'] = $guest;
		$content['hskp_status'] = $hskp_status;
		$content['room_info'] = $room_info;

		$ret = array();
		$ret['code'] = 200;

		$ret['content'] = $content;
		$ret['message'] = '';

		return $ret;
	}

	public function getMyRoomList(Request $request) {
		$user_id = $request->get('user_id', 0);

		$user = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.id', $user_id)
				->select(DB::raw('cu.*, cd.property_id'))
				->first();


		$roomlist = DB::table('common_room as cr')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->where('cb.property_id', $user->property_id)
				->select(DB::raw('cr.*'))
				->get();

		$ret['code'] = 200;
		$ret['content'] = $roomlist;
		$ret['message'] = '';

		return Response::json($ret);
	}

	private function getRoomDetailInfo($room_id) {
		$room_info = DB::table('common_room as cr')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*, cb.property_id, hs.status as hskp_status'))
				->first();

		$progress = DB::table('services_hskp_log')
				->where('room_id', $room_info->id)
				->orderBy('id', 'desc')
				->first();

		$room_info->progress = $progress;
	}

	public function getRoomInfo(Request $request) {
		$room = $request->get('room', '1001');
		$room_info = DB::table('common_room as cr')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
				->where('cr.room', $room)
				->select(DB::raw('cr.*, cb.property_id, hs.status as hskp_status'))
				->first();

		$ret = array();
		if( empty($room_info) )
		{
			$ret['code'] = 100;
			$ret['content'] = '';
			$ret['message'] = 'There is no such room';

			return Response::json($ret);
		}

		$progress = DB::table('services_hskp_log')
			->where('room_id', $room_info->id)
			->orderBy('id', 'desc')
			->first();

		$room_info->progress = $progress;


		$ret['code'] = 200;
		$ret['content'] = $room_info;
		$ret['message'] = '';

		return Response::json($ret);
	}

	public function startRoomStatusChange(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$user_id = $request->get('user_id', 0);
		$device_id = $request->get('device_id', 0);
		$room_id = $request->get('room_id', 0);

		$ret = array();

		$room_status = HskpRoomStatus::find($room_id);
		if( empty($room_status) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Room does not exist.';

			return Response::json($ret);
		}

		$roster = RosterList::find($room_status->attendant_id);
		if( empty($roster) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Roster does not exist.';

			return Response::json($ret);
		}

		$cleanning_room_id = RosterList::getCurrentCleaningRoomID($roster->id);

		if($cleanning_room_id > 0)
 		{			
			$ret['code'] = 501;
			$ret['message'] = 'Room cleaning already in progress.';

			return Response::json($ret);			
		}
		
		$diff = 0;
		if($room_status->td_flag==1)
		{
			$workstatus = $room_status->td_working_status;
			if($workstatus != CLEANING_PAUSE)
			{
				$room_status->td_start_time = $cur_time;
				$room_status->updated_at = $cur_time;
			}
			else
			{
				$start1 = strtotime($room_status->td_start_time);
				$end1 = strtotime($room_status->updated_at);
				$diff = $end1 - $start1;
				$nowtime = strtotime($cur_time);
				$diff = $nowtime - $diff;

				$cur_time = date("Y-m-d H:i:s", $diff);
				$room_status->td_start_time = $cur_time;
			}
			$room_status->td_working_status = CLEANING_RUNNING;
		}
		else
		{
			$workstatus = $room_status->working_status;
			if($workstatus != CLEANING_PAUSE)
			{
				$room_status->start_time = $cur_time;
				$room_status->updated_at = $cur_time;
			}
			else
			{
				$start1 = strtotime($room_status->start_time);
				$end1 = strtotime($room_status->updated_at);
				$diff = $end1 - $start1;
				$nowtime = strtotime($cur_time);
				$diff = $nowtime - $diff;

				$cur_time = date("Y-m-d H:i:s", $diff);
				$room_status->start_time = $cur_time;
			}	
			$room_status->working_status = CLEANING_RUNNING;
		}

		$room_status->device_ids = $device_id;
		$room_status->dispatcher = $user_id;
		$room_status->save();

		$room_status->working_status = CLEANING_RUNNING;
		$room_status->start_time = $cur_time;


		$hskp_log = new HskpStatusLog();
		$hskp_log->method = 'Mobile';
		$hskp_log->room_id = $room_id;		
		$hskp_log->user_id = $user_id;
		$hskp_log->device_id = $device_id;
		if($room_status->td_flag==1)
		{
			$hskp_log->td_state = CLEANING_RUNNING;
			$hskp_log->td_flag =1;
		}
		else
			$hskp_log->state = CLEANING_RUNNING;
			
		if($workstatus != CLEANING_PAUSE)
			$hskp_log->created_at = $cur_time;

		$hskp_log->save();
		$hskp_log->state = CLEANING_RUNNING;
		
		Functions::sendHskpStatusChangeWithRoom($room_id);

		$room_status = HskpRoomStatus::getRoomStatus($room_status->id);		
		$room_status->current_cleaning = $cleanning_room_id;

		if($room_status->td_flag==1)
			$room_status->cleaning_state = 'TD Cleaning';
		
		$ret['code'] = 200;		
		$ret['content'] = $room_status;
		$ret['diff'] = $diff;
		$ret['start_time'] = $cur_time;
		$ret['message'] = '';

		return Response::json($ret);
	}

	public function postDNDStatus(Request $request) {
		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);

		$guest = DB::table('common_guest')
				->where('room_id', $room_id)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

		$user = CommonUser::find($user_id);
		$hskp_status = DB::table('services_hskp_log')
				->where('room_id', $room_id)
				->orderBy('id', 'desc')
				->first();

		$room_info = DB::table('common_room as cr')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*'))
				->first();

		if( !empty($guest) && $guest->checkout_flag == 'checkin' )
		{
			// already check in

		}

		$hskp_log = new HskpStatusLog();
		$hskp_log->method = 'Mobile';
		$hskp_log->room_id = $room_id;
		$hskp_log->hskp_id = $room_info->hskp_status_id;
		$hskp_log->user_id = $user_id;
		$hskp_log->state = CLEANING_DND;	// dnd

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$hskp_log->created_at = $cur_time;

		$hskp_log->save();

        Functions::sendHskpStatusChangeWithRoom($room_id);

		$ret['code'] = 200;
		$ret['content'] = $hskp_log;
		$ret['message'] = 'Room has been changed to Do not disturb';

		return Response::json($ret);
	}

	public function postStopCleaning(Request $request) {
		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);
		$reason = $request->get('reason', "");


		$room_info = DB::table('common_room as cr')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*'))
				->first();

		$hskp_log = new HskpStatusLog();
		$hskp_log->method = 'Mobile';
		$hskp_log->room_id = $room_id;
		$hskp_log->hskp_id = $room_info->hskp_status_id;
		$hskp_log->user_id = $user_id;
		$hskp_log->state = CLEANING_DECLINE;
		$hskp_log->reason = $reason;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$hskp_log->created_at = $cur_time;

		$hskp_log->save();

        Functions::sendHskpStatusChangeWithRoom($room_id);

		$ret['code'] = 200;
		$ret['content'] = $hskp_log;

		$ret['message'] = 'Room ' . $room_info->room .  '\'s cleaning has been stopped';

		return Response::json($ret);
	}

	public function changehskpstatus(Request $request){
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$room_id = $request->get('room_id', 0);
		$user_id = $request->get('user_id', 0);
		$room_status = $request->get('room_status', "");
		$method = $request->get('room_status', 'Mobile');

		if($room_status == 'Partial' )
			$room_status = 'Inspected';

		$statuses = explode(' ', $room_status);
		$hskp_room_status = HskpRoomStatus::find($room_id);
		if( !empty($hskp_room_status) )
		{	
			if(($statuses[0] == 'Inspected')||($statuses[0] == 'Dirty')||($statuses[0] == 'Clean'))         
			{
				$hskp_room_status->room_status = $statuses[0];
			}
			else
			{       
				$test= new Test();
				$test->func='changehskpstatus';
				$test->details=json_encode($request->all());
				$test->created_at=$cur_time;
				$test->save();  
			}

			if($statuses[0] == 'Inspected'){
				$hskp_room_status->working_status = CLEANING_COMPLETE;
			}
			else if($statuses[0] == 'Dirty'){
				$hskp_room_status->working_status = CLEANING_PENDING;
			}
			else if( $statuses[0] == 'Clean' )
			{
				$hskp_room_status->working_status = CLEANING_DONE;
			}

			$hskp_room_status->save();

			$room = Room::getPropertyBuildingFloor($room_id);
			$property_id = $room->property_id;

			$hskp_status_name = "$hskp_room_status->occupancy $statuses[0]";

			// get complete status id
			$hskp_info = DB::table('services_hskp_status as hs')
					->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
					->where('hs.status', $hskp_status_name)
					->where('cb.property_id', $property_id)
					->select(DB::raw('hs.*'))
					->first();

			if( empty($hskp_info) )
			{
				$ret = array();
				$ret['code'] = 201;				
				$ret['message'] = "Room Status is invalid " . $hskp_status_name;

				return Response::json($ret);
			}

			$this->changeRoomHskpStatus($user_id, $room_id, $hskp_info, $hskp_info->status, $method);

			$ret = array();
			$ret['code'] = 200;
			$room_status = HskpRoomStatus::getRoomStatus($hskp_room_status->id);	
			$ret['content'] = $room_status;
			$ret['room_status'] = $statuses[0];
			$ret['message'] = "Room Status for room $room->room has been posted successfully";
			$ret['hskp_room_status'] = HskpRoomStatus::getRoomStatus($room_id);
		}else{
			$ret = array();
			$ret['code'] = 201;
			$ret['content'] = "";
			$ret['room_status'] = $statuses[0];
			$ret['message'] = 'Room is not assigned.';
		}

        Functions::sendHskpStatusChangeWithRoom($room_id);

		return Response::json($ret);
	}
	public function completeRoomStatusChange(Request $request) {
		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);

		$guest = DB::table('common_guest')
				->where('room_id', $room_id)
				->orderBy('id', 'desc')
				->first();

		$user = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->where('cu.id', $user_id)
				->select(DB::raw('cu.*, cd.property_id, jr.permission_group_id'))
				->first();

		$guest_exist = 'Vacant';
		if( !empty($guest) && $guest->checkout_flag == 'checkin' )
			$guest_exist = 'Occupied';


		// get complete status id
		$hskp_info = DB::table('services_hskp_status as hs')
			->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
			->where('hs.status', $guest_exist . ' Clean')
			->where('cb.property_id', $user->property_id)
			->select(DB::raw('hs.*'))
			->first();
        $room_status=$guest_exist . ' Clean';

                    if(empty($hskp_info))
        {
        $hskp_info = DB::table('services_hskp_status as hs')
                    ->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
                    ->where('hs.status', 'Clean '.$guest_exist )
                    ->where('cb.property_id', $user->property_id)
                    ->select(DB::raw('hs.*'))
                    ->first();
        $room_status='Clean '.$guest_exist;
        }

		return $this->changeRoomHskpStatus($user_id, $room_id, $hskp_info, $room_status,'Mobile');
	}

	public function postHskpStatusManually(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);
		$room_status = $request->get('room_status', '');

		$guest = DB::table('common_guest')
				->where('room_id', $room_id)
				->where('departure', '>=', $cur_date)
				->where('checkout_flag', 'checkin')
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

		$user = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->where('cu.id', $user_id)
				->select(DB::raw('cu.*, cd.property_id, jr.permission_group_id'))
				->first();

		$guest_exist = 'Vacant';
		if( !empty($guest) )
			$guest_exist = 'Occupied';

		$ret = array();

		//if (strpos($room_status, $guest_exist) === false) {
	//		$ret['code'] = 100;
	//		$ret['content'] = '';
	//		$ret['message'] = $room_status . ' status does not match';
//
//			return Response::json($ret);
//		}
        //echo $room_status;
		// get complete status id
		$hskp_info = DB::table('services_hskp_status as hs')
				->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
				->where('hs.status', $room_status)
				->where('cb.property_id', $user->property_id)
				->select(DB::raw('hs.*'))
				->first();
        if(empty($hskp_info))
        {
			$statuses = explode(' ', $room_status);

			$hskp_info = DB::table('services_hskp_status as hs')
							->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
							->where('hs.status', $statuses[1].' '.$statuses[0])
							->where('cb.property_id', $user->property_id )
							->select(DB::raw('hs.*'))
							->first();
			$room_status = $statuses[1].' '.$statuses[0];
        }

		return $this->changeRoomHskpStatus($user_id, $room_id, $hskp_info, $room_status,"Manual Post");
	}

	private function changeRoomHskpStatus($user_id, $room_id, $hskp_info, $room_status, $method) {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$cur_time = date("Y-m-d H:i:s");

		if( empty($hskp_info) )
		{
			$ret['code'] = 100;
			$ret['content'] = '';
			$ret['message'] = $room_status . ' status does not exist';

			return Response::json($ret);
		}

		$user = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.id', $user_id)
				->select(DB::raw('cu.*, cd.property_id'))
				->first();

		if( empty($user) )
		{
			$ret = array();
			$ret['code'] = 201;
			$ret['content'] = '';
			$ret['message'] = 'User property does not exist';

			return Response::json($ret);
		}


		$room_info = DB::table('common_room as cr')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*, cf.bldg_id'))
				->first();

		$ret = array();

		$room_state = HskpRoomStatus::find($room_id);
		$state = CLEANING_DONE;
		
		if( !empty($room_state) )
		{
			if($room_state->td_flag!=1)
			{
				if ($room_status == 'Vacant Clean') {
					$room_state->working_status = CLEANING_DONE; 
					$room_state->rush_flag = 0;
					$room_state->end_time = $cur_time;
					$room_state->room_status = CLEAN;					
				}

				if ($room_status == 'Vacant Dirty') {
					$room_state->working_status = CLEANING_PENDING; 
					$room_state->start_time = $cur_time;
					$room_state->room_status = DIRTY;
				}

				if ($room_status == 'Vacant Inspected') {
					$room_state->working_status = CLEANING_COMPLETE; 				
					$room_state->room_status = INSPECTED;
					$setting['hskp_inspection_flag'] = 0;
					$setting = PropertySetting::getPropertySettings($user->property_id,$setting);
					if($setting['hskp_inspection_flag']==1)
						$room_state->show_credit = 1;
				}

				if ($room_status == 'Occupied Clean') {
					$room_state->working_status = CLEANING_DONE; 
					$room_state->end_time = $cur_time;
					$room_state->rush_flag = 0;
					$room_state->room_status = CLEAN;
				}

				if ($room_status == 'Occupied Dirty') {
					$room_state->working_status = CLEANING_PENDING; 
					$room_state->start_time = $cur_time;
					$room_state->room_status = DIRTY;
				}

				if ($room_status == 'Occupied Inspected') {
					$room_state->working_status = CLEANING_COMPLETE; 				
					$room_state->room_status = INSPECTED;
				}

				$room_state->save();

				$state = $room_state->working_status;	
			}
			else {
				if ($room_status == 'Vacant Clean') {
					$room_state->td_working_status = CLEANING_DONE; 
					$room_state->td_end_time = $cur_time;
					$room_state->room_status = CLEAN;
				}

				if ($room_status == 'Vacant Dirty') {
					$room_state->td_working_status = CLEANING_PENDING; 
					$room_state->td_start_time = $cur_time;
					$room_state->room_status = DIRTY;
				}

				if ($room_status == 'Vacant Inspected') {
					$room_state->td_working_status = CLEANING_COMPLETE; 				
					$room_state->room_status = INSPECTED;
				}

				if ($room_status == 'Occupied Clean') {
					$room_state->td_working_status = CLEANING_DONE; 
					$room_state->td_end_time = $cur_time;
					$room_state->room_status = CLEAN;
				}

				if ($room_status == 'Occupied Dirty') {
					$room_state->td_working_status = CLEANING_PENDING; 
					$room_state->td_start_time = $cur_time;
					$room_state->room_status = DIRTY;
				}

				if ($room_status == 'Occupied Inspected') {
					$room_state->td_working_status = CLEANING_COMPLETE; 				
					$room_state->room_status = INSPECTED;
				}
				
				$td_state = $room_state->td_working_status;
				$room_state->save();

				$room_state->working_status = $room_state->td_working_status;
				$room_state->start_time = $room_state->td_start_time;
				$room_state->end_time = $room_state->td_end_time;
			}

			$state = $room_state->working_status;			
		}

		$hskp_log = new HskpStatusLog();
		$hskp_log->method = $method;
		$hskp_log->room_id = $room_id;
		$hskp_log->hskp_id = $hskp_info->id;
		$hskp_log->user_id = $user_id;
		
		$hskp_log->state = $state;
		if(!empty($room_state) && $room_state->td_flag==1)
		{
			$hskp_log->td_state = $td_state;
			$hskp_log->td_flag = 1;
		}
		$hskp_log->created_at = $cur_time;
		if( $room_state->working_status == CLEANING_DONE )
		{
			if( HskpRoomStatus::isActiveChecklist($room_id, 'Attendant' ) )
				$hskp_log->check_num = $room_state->attendant_check_num + 1;		
		}

		if( $room_state->working_status == CLEANING_COMPLETE )
		{
			if( HskpRoomStatus::isActiveChecklist($room_id, 'Supervisor' ) )
				$hskp_log->check_num = $room_state->supervisor_check_num + 1;				
		}

		RosterList::setRosterIdsForHskpLog($hskp_log, $room_state);			
		
		$hskp_log->save();

		// update room status
		$room = Room::find($room_id);
		$room->hskp_status_id = $hskp_info->id;

		$room->save();

		if($hskp_log->td_flag != 1)
		{			
			$data = array();
			$data['property_id'] = $user->property_id;
			$data['msg'] = sprintf("RE|RN%d|RS%s",
					$room_info->room, $hskp_info->pms_code);

			$src_config = array();
			$src_config['src_property_id'] = $user->property_id;
			$src_config['src_build_id'] = $room_info->bldg_id;
			$src_config['accept_build_id'] = array();

			$data['src_config'] = $src_config;
			
			Functions::sendMessageToInterface('interface_hotlync', $data);			
		}

		$this->sendNotificationForCleaningStatusChange($user, $room_info, $room_status, $room_state);

		$ret['code'] = 200;
		if( !empty($room_state) )
		{
			if($room_state->td_flag!=1)
			{
				if( $room_state->working_status == CLEANING_PENDING )
					$room_state->cleaning_state = 'Pending';		
				if( $room_state->working_status == CLEANING_DONE )
					$room_state->cleaning_state = 'Done';
				if( $room_state->working_status == CLEANING_COMPLETE )
					$room_state->cleaning_state = 'Inspected';
			}
			else {
				if( $room_state->working_status == CLEANING_PENDING )
					$room_state->cleaning_state = 'TD Pending';		
				if( $room_state->working_status == CLEANING_DONE )
					$room_state->cleaning_state = 'TD Done';
				if( $room_state->working_status == CLEANING_COMPLETE )
					$room_state->cleaning_state = 'TD Inspected';
			}
		}

        Functions::sendHskpStatusChangeWithRoom($room_id);

		$ret['content'] = $room_state;		
		$ret['message'] = 'Room ' . $room_info->room .  ' has been to ' . $room_status;

		return Response::json($ret);
	}

	public function sendNotificationForCleaningStatusChange($user, $room_info, $room_status, $room_state)
	{
		$job_roles = PropertySetting::getJobRoles($user->property_id);
		
		$location_info = DB::table('services_location_group_members as a')
			->join('services_location as sl', 'a.loc_id', '=', 'sl.id')
			->where('sl.room_id', $room_info->id)
			->select(DB::raw('a.*'))
			->first();

		$location_group_id = 0;		
		if( !empty($location_info) )
			$location_group_id = $location_info->location_grp;

		$payload = array();
		$payload['table_id'] = $room_info->id;
		$payload['table_name'] = 'common_room';
		$payload['property_id'] = $user->property_id;
		$payload['notify_type'] = 'housekeeping';		
		
		if (strpos($room_status, 'Clean') == true) { // clean status
			$userlist = ShiftGroupMember::getUserlistOnCurrentShift($user->property_id, $job_roles['supervisor_job_role'], 0, 0, 0, $location_group_id, 0, true, false);

			$payload['type'] = 'Room Cleaned';	
			$payload['header'] = 'Housekeeping';		
			$message = $room_info->room . ' is cleaned, Please inpect it.';

			// send mobile push to Floor Supervisor on Current Shift
			foreach( $userlist as $row )
			{
				Functions::sendPushMessgeToDeviceWithRedisNodejs(
							$row, 'Hotlync Housekeeping', $message, $payload
					);				
			}

			return $payload;
		}

		return array();
	}

	public function testSupervisorNotification(Request $request) 
	{
		$property_id = 4;
		$user_id = 3;
		$room_id = 46;
		$room_status = 'Vacant Clean';

		$user = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.id', $user_id)
				->select(DB::raw('cu.*, cd.property_id'))
				->first();

		$room_info = DB::table('common_room as cr')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*, cf.bldg_id'))
				->first();		

		$ret = $this->sendNotificationForCleanStatus($user, $room_info, $room_status);

		return Response::json($ret);
	}

//	http://192.168.1.253:8894/api/posthskpstatufromivr?username=goureshn&room=5104&pms_code=2
	public function postHskpStatusFromIVR(Request $request) {
		$username = $request->get('username', 0);
		$room = $request->get('room', "0");
		$property_id = $request->get('property_id', "0");
		$pms_code = $request->get('pms_code', 0);

		$ret = array();

		$room = DB::table('common_room as cr')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->where('cb.property_id', $property_id)
				->where('cr.room', $room)
				->select(DB::raw('cr.*, cf.bldg_id'))
				->first();

		if( empty($room) )
		{
			$ret = array();
			$ret['code'] = 201;
			$ret['message'] = 'Room ' . $room . ' does not exist';

			return Response::json($ret);
		}

		$user = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->where('cu.username', $username)
				->select(DB::raw('cu.*, cd.property_id, jr.permission_group_id'))
				->first();

		if( empty($user) )
		{
			$ret = array();
			$ret['code'] = 201;
			$ret['message'] = 'User property does not exist';

			return Response::json($ret);
		}

		$guest = DB::table('common_guest')
				->where('room_id', $room->id)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

		$hskp_info = DB::table('services_hskp_status as hs')
			->where('bldg_id', $room->bldg_id)
			->where('pms_code', $pms_code)
			->first();

		if(empty($hskp_info) )
		{
			$ret = array();
			$ret['code'] = 201;
			$ret['message'] = 'PMS code is not exist';

			return Response::json($ret);
		}

		$room_status = $hskp_info->status;

//		$guest_exist = 'Vacant';
//		if( !empty($guest) && $guest->checkout_flag == 'checkin' )
//			$guest_exist = 'Occupied';
//
//		if (strpos($room_status, $guest_exist) === false) {
//			$ret['code'] = 100;
//			$ret['content'] = '';
//			$ret['message'] = $room_status . ' status does not match';
//
//			return Response::json($ret);
//		}

		return $this->changeRoomHskpStatus($user->id, $room->id, $hskp_info, $room_status,'IVR');
	}

//	http://192.168.1.253/api/posthskpstatufromivrwithextension?user_id=1&extension=3112&ivr_code=2
	public function postHskpStatusFromIVRWithExtension(Request $request) {
		$user_id = $request->get('user_id', 0);
		$extension = $request->get('extension', "0");
		$ivr_code = $request->get('ivr_code', 0);

		$ret = array();

		$room = DB::table('call_guest_extn as ge')
				->join('common_room as cr', 'ge.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->where('ge.extension', $extension)
				->select(DB::raw('cr.*, cf.bldg_id'))
				->first();

		if( empty($room) )
		{
			$ret = array();
			$ret['code'] = 201;
			$ret['message'] = 'Extension ' . $extension . ' is not valid';

			return Response::json($ret);
		}

		$user = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->where('cu.id', $user_id)
				->select(DB::raw('cu.*, cd.property_id, jr.permission_group_id'))
				->first();

		if( empty($user) )
		{
			$ret = array();
			$ret['code'] = 201;
			$ret['message'] = 'User property does not exist';

			return Response::json($ret);
		}

		$guest = DB::table('common_guest')
				->where('room_id', $room->id)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

		$hskp_info = DB::table('services_hskp_status as hs')
			->where('bldg_id', $room->bldg_id)
			->where('ivr_code', $ivr_code)
			->first();

		if(empty($hskp_info) )
		{
			$ret = array();
			$ret['code'] = 201;
			$ret['message'] = 'IVR code is not exist';

			return Response::json($ret);
		}

		$room_status = $hskp_info->status;

		return $this->changeRoomHskpStatus($user->id, $room->id, $hskp_info, $room_status,'IVR EXT');
	}

	public function getHskpStatusByRoom(Request $request) {
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'cr.id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');

		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		if($pageSize < 0 )
			$pageSize = 20;

		$ret = array();

		$query = DB::table('services_room_status as rs')
				->join('common_room as cr', 'cr.id', '=', 'rs.id')
				->leftJoin('common_users as cu', 'rs.dispatcher', '=', 'cu.id')
				->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
				->where('rs.property_id', $property_id);

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, 'asc')
				->select(DB::raw('cr.*, hs.status, CONCAT_WS(" ", cu.first_name, cu.last_name) as assigne_to,
					rs.working_status as state, rs.dispatcher as assigne_id, rs.start_time, rs.end_time'))
				->skip($skip)->take($pageSize)
				->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);
	}

	public function getHskpStatusByFloor(Request $request) {
		$property_id = $request->get('property_id', '0');
		$floor_ids = $request->get('floor_ids', [0]);
		$filter = $request->get('filter','');

		$bldg_tags = [];
		$floor_tags = [];
		$attendant_tags = [];
		$supervisor_tags = [];
		$occupancy_tags = [];
		$status_tags = [];
		$rush = 0;
		
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$cur_time = date("Y-m-d H:i:s");

		if(!empty($filter)) {
			if(!empty($filter['bldg_tags']))
			{
				foreach ($filter['bldg_tags'] as $key => $value) {
					$bldg_tags[]=$value['id'];
				}
			}

			if(!empty($filter['floor_tags']))
			 {
				foreach ($filter['floor_tags'] as $key => $value) {
					$floor_tags[]=$value['id'];
				}
			}

			if(!empty($filter['attendant_tags']))
			{
				foreach ($filter['attendant_tags'] as $key => $value) {
					$attendant_tags[]=$value['id'];
				}
			}

			if(!empty($filter['supervisor_tags']))
			{
				foreach ($filter['supervisor_tags'] as $key => $value) {
					$supervisor_tags[]=$value['id'];
				}
			}

			if(!empty($filter['status_tags']))
			{
				foreach ($filter['status_tags'] as $key => $value) {
					$status_tags[] = $value['status_id'];
				}
			}


			if(!empty($filter['rush'])) 
				$rush = ($filter['rush']==true) ? 1 : 0;
		}

		
		
		$ret = array();

		$query = DB::table('common_floor as cf')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->where('cb.property_id', $property_id);

		if( count($floor_tags )>0)
			$query->whereIn('cf.id', $floor_tags);

		if( count($bldg_tags )>0)
        {
            $query->whereIn('cb.id', $bldg_tags);
		}
		
		$floor_list = $query
				->select(DB::raw('cf.*, cb.name, CONCAT_WS(" - ", cb.name, cf.floor) as floor_name'))
				->orderBy('cf.id','asc')
				->get();
		$ret['roomcount'] = 0;

		for($j = 0; $j < count($floor_list); $j++)
		{
			$floor = $floor_list[$j];
			$query1 = DB::table('services_room_status as rs')
						->join('common_room as cr', 'cr.id', '=', 'rs.id')
						->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
						->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
						->leftJoin('services_room_working_status as srws', 'rs.working_status', '=', 'srws.status_id')
						->leftJoin('services_roster_list as rl', 'rs.attendant_id', '=', 'rl.id')
						->leftJoin('services_devices as sd', 'rl.device', '=', 'sd.id')
						->leftJoin('common_users as cu', 'sd.device_id', '=', 'cu.device_id')						
						->leftJoin('common_users as cu1', 'rl.user_id', '=', 'cu1.id')
						->leftJoin('common_guest as cg', function($join) use ($cur_date) {
							$join->on('cr.id', '=', 'cg.room_id');
							$join->on('cg.departure','>=', DB::raw($cur_date));
							$join->on('cg.checkout_flag','=', DB::raw("'checkin'"));
						})
						->leftJoin('common_guest_remark_log as grl', function($join) use ($cur_date) {
							$join->on('grl.room_id', '=', 'cg.room_id');
							$join->on('grl.guest_id', '=', 'cg.guest_id');
							$join->where('grl.expire_date','>=', DB::raw($cur_date));					
						})
						->where('cr.flr_id', $floor->id);

			if( count($attendant_tags) > 0 )
				$query1->whereIn('rs.attendant_id', $attendant_tags);

			if( count($supervisor_tags ) > 0 )
				$query1->whereIn('rs.supervisor_id', $supervisor_tags);	

			if( count($occupancy_tags )>0)
				$query1->whereIn('hs.status', $occupancy_tags);

			if( count($status_tags )>0)
				$query1->whereIn('rs.working_status', $status_tags);

			if( $rush==1)
				$query1->where('rs.rush_flag', 1);

			if(!empty($filter['search_room'])) 
			{
				$search_room = $filter['search_room'];
				$query1->where('cr.room', 'LIKE', "%$search_room%");
			}
		

			$data_query1 = clone $query1;
			$data_list =$data_query1
					->groupBy('cr.id')
					->select(DB::raw('cr.*, hs.status, grl.remark, cg.guest_id,
						(CASE WHEN rl.user_id > 0 THEN rl.user_id ELSE cu.id END) as assigne_id , 
						(CASE WHEN rl.user_id > 0 THEN CONCAT_WS(" ", cu1.first_name, cu1.last_name) ELSE CONCAT_WS(" ", cu.first_name, cu.last_name) END) as assigne_to, 					
						rs.room_status, rs.occupancy,
						rs.working_status as state, rs.service_state, srws.status_name, rs.comment, rt.type, sd.device_id, rl.user_id as hskp_user_id, 
						rs.dispatcher , rs.start_time, rs.end_time, rs.rush_flag, rs.schedule,
						CASE WHEN linen_date = \'' . $cur_date . '\' THEN 1 ELSE 0 END as linen_change,
						cg.checkout_flag, cg.vip, cg.adult as adult, cg.chld as chld, cg.pref'))
					->get();
			$data_query2 = clone $query1;
			$data_counts = $data_query2
					->groupBy('rs.working_status')
					->select(DB::raw('rs.working_status as state_name ,count(*) as state_count'))
					->get();		
			foreach ($data_list as $key => $value) 
			{
				if((($value->start_time)<=$cur_time) ) //&& (($value->end_time)>=$cur_time)
				{
					$val1=new DateTime($value->end_time); 
					$val2=new DateTime($value->start_time);
					$val3=$val2->diff($val1);
					$value->duration=$val3->format('%H:%I:%S');
				}
				else{
					$value->duration="00:00:00";
				}
				
				$value->floor_name = $floor->description;
			}

			$floor_list[$j]->room_list = $data_list;
			$ret['roomcount'] = $ret['roomcount'] + count($data_list);
			$floor_list[$j]->state_list = $data_counts;
		}
		$ret['datalist'] = $floor_list;
		$ret['totalcount'] = count($floor_list);

		$ret['attendant_list'] = DB::table('services_room_status as rs')				
				->join('services_roster_list as rl', 'rs.attendant_id', '=', 'rl.id')			
				->leftJoin('services_devices as sd', 'rl.device', '=', 'sd.id')
				->leftJoin('common_users as cu', 'sd.device_id', '=', 'cu.device_id')
				->leftJoin('common_users as cu1', 'rl.user_id', '=', 'cu1.id')
				->where('rs.property_id', $property_id)
				->select(DB::raw('rl.*, 
							CASE WHEN rl.user_id > 0 THEN CONCAT_WS(" ", cu1.first_name, cu1.last_name) ELSE CONCAT_WS(" ", cu.first_name, cu.last_name) END as roster_name'))
				->groupBy('rl.id')	
				->get();

		$ret['supervisor_list'] = DB::table('services_room_status as rs')				
				->join('services_roster_list as rl', 'rs.supervisor_id', '=', 'rl.id')			
				->leftJoin('services_devices as sd', 'rl.device', '=', 'sd.id')
				->leftJoin('common_users as cu', 'sd.device_id', '=', 'cu.device_id')
				->leftJoin('common_users as cu1', 'rl.user_id', '=', 'cu1.id')
				->where('rs.property_id', $property_id)
				->select(DB::raw('rl.*, 
							CASE WHEN rl.user_id > 0 THEN CONCAT_WS(" ", cu1.first_name, cu1.last_name) ELSE  CONCAT_WS(" ", cu.first_name, cu.last_name) END as roster_name'))
				->groupBy('rl.id')	
				->get();

		$ret = $this->getRoomCountBasedCleanning($ret);				
		
		return Response::json($ret);
	}

	private function getRoomCountBasedCleanning($ret)
	{
		// get room count based on status
		$status_list = DB::table('services_room_working_status')
			->where('status_id', '!=', CLEANING_NOT_ASSIGNED)
			->orderBy('attendant_order')
			->get();

		$select_sql = "count(*) as total";
		foreach($status_list as $row)
		{
			$select_sql .= ",COALESCE(SUM(rs.working_status = $row->status_id), 0) as $row->status_name";			
		}		

		$data = DB::table('services_room_status as rs')
			->select(DB::raw($select_sql))
			->first();

		$room_count_list = [];	
		$cleaning_color = [
			'#68aee6',	// Cleaning
			'#e77c6e',	// Pending
			'#673AB7',	// Rejected
			'#827717',	// Pause
			'#FF9800',	// Delay
			'#e65100',	// DND
			'#607d8b',	// Rejected
			'#0e8064',	// Finished
			'#22c064',	// Inspected
			'#FF0000',  // OOO
			'#df235c',  // No Service
			'#0761b4',  // SleepOut
			'#FFFFFF',	// Total
		];

		foreach($status_list as $key => $row)
		{
			$room_count_list[] = ['status_name' => $row->status_name, 'count' => $data->{$row->status_name}, 'state' => $row->status_id, 'color' => $cleaning_color[$key]];
		}	

		$room_count_list[] = ['status_name' => 'Total', 'count' => $data->total, 'state' => -1, 'color' => '#FFFFFF'];

		$ret['cleaning_room_count'] = $room_count_list;
			
		return $ret;
	}

	public function getHskpStatusByStaff(Request $request) {
		$property_id = $request->get('property_id', '0');
		$ids = $request->get('ids', '0');

		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$job_roles = PropertySetting::getJobRoles($property_id);

		$ret = array();

		$query = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.job_role_id', $job_roles['roomattendant_job_role'])
				->where('cd.property_id', '=', $property_id);

		if( $ids[0] != 0 )
			$query->whereIn('cu.id', $ids);

		$staff_list = $query
				->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as title'))
				->get();

		$dirty = DIRTY;
		$clean = CLEAN;	
		$dnd = CLEANING_DND;	

		for($j = 0; $j < count($staff_list); $j++)
		{
			$staff = $staff_list[$j];
			$data_list = DB::table('services_room_status as rs')
					->join('common_room as cr', 'cr.id', '=', 'rs.id')
					->join('common_users as cu', 'rs.dispatcher', '=', 'cu.id')
					->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
					->where('rs.dispatcher', $staff->id)
					->select(DB::raw('cr.*, hs.status, CONCAT_WS(" ", cu.first_name, cu.last_name) as assigne_to, rs.room_status,
						rs.working_status as state, rs.dispatcher as assigne_id, rs.start_time, rs.end_time'))
					->get();			
			
			$staff_list[$j]->room_list = $data_list;

			$subcount = DB::table('services_room_status as rs')
					->where('rs.dispatcher', $staff->id)
					->select(DB::raw("
						sum(rs.room_status = '$clean') as clean,
						sum(rs.room_status = '$dirty') as dirty,
						sum(rs.working_status = $dnd) as dnd
						"))
					->first();			

			$staff_list[$j]->clean = $subcount->clean;
			$staff_list[$j]->dirty = $subcount->dirty;
			$staff_list[$j]->dnd = $subcount->dnd;
			$complete_ratio = 0;

			if( count($data_list) > 0 )
				$complete_ratio = 100 * $clean / count($data_list);
			$staff_list[$j]->complete_ratio = $complete_ratio;
		}

		$ret['datalist'] = $staff_list;
		$ret['totalcount'] = count($staff_list);

		return Response::json($ret);
	}

	public function getHskpStatusByShift(Request $request) {
		$property_id = $request->get('property_id', '0');
		$ids = $request->get('ids', '0');

		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");


		$ret = array();

		$query = DB::table('services_shifts as sh')
				->where('sh.property_id', $property_id);

		if( $ids[0] != 0 )
			$query->whereIn('sh.id', $ids);

		$shift_list = $query
				->select(DB::raw('sh.*, sh.name as title'))
				->get();

		$dirty = DIRTY;
		$clean = CLEAN;	
		$dnd = CLEANING_DND;	

		for($j = 0; $j < count($shift_list); $j++)
		{
			$shift = $shift_list[$j];
			$data_list = DB::table('services_room_status as rs')
					->join('common_room as cr', 'cr.id', '=', 'rs.id')
					->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
					->join('services_shift_group_members as sgm', 'rs.dispatcher', '=', 'sgm.user_id')
					->join('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
					->join('common_users as cu', 'rs.dispatcher', '=', 'cu.id')
					->where('sg.shift', $shift->id)
					->select(DB::raw('cr.*, hs.status, CONCAT_WS(" ", cu.first_name, cu.last_name) as assigne_to, rs.room_status,
						rs.working_status as state, rs.dispatcher as assigne_id, rs.start_time, rs.end_time'))
					->get();

			$shift_list[$j]->room_list = $data_list;
			
			$subcount = DB::table('services_room_status as rs')
					->join('services_shift_group_members as sgm', 'rs.dispatcher', '=', 'sgm.user_id')
					->join('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
					->where('sg.shift', $shift->id)
					->select(DB::raw("
						sum(rs.room_status = '$clean') as clean,
						sum(rs.room_status = '$dirty') as dirty,
						sum(rs.working_status = $dnd) as dnd
						"))
					->first();			

			$shift_list[$j]->clean = $subcount->clean;
			$shift_list[$j]->dirty = $subcount->dirty;
			$shift_list[$j]->dnd = $subcount->dnd;

			$complete_ratio = 0;
			if( count($data_list) > 0 )
				$complete_ratio = 100 * $clean / count($data_list);
			$shift_list[$j]->complete_ratio = $complete_ratio;
		}

		$ret['datalist'] = $shift_list;
		$ret['totalcount'] = count($shift_list);

		return Response::json($ret);
	}


	public function getRoomList(Request $request) {
		$property_id = $request->get('property_id', '0');
//		$dept_id = $request->get('dept_id', '0');
		$floor_list = $request->get('floors', array());
		$room_name = $request->get('room_name', array());
		$room_category = $request->get('room_category', '0');
		$building_id = $request->get('building_id', '0');
		$dispatcher = $request->get('dispatcher', 0);

		$ret = array();

		$query = DB::table('services_room_status as rs')
				->join('common_room as cr', 'rs.id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')				
				->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
				// ->leftJoin('common_guest_advanced_detail as gad', 'rs.id', '=', 'gad.id')				
				->where('cb.property_id', $property_id)
				->where('rs.dispatcher', '<>', $dispatcher)
				->where('rs.working_status', '<>', CLEANING_RUNNING);

		$count_query = clone $query;		

		if( $building_id > 0 )
			$query->where('cf.bldg_id', $building_id);

		if( count($floor_list) )
			$query->whereIn('flr_id', $floor_list);

		if( !empty($room_name) )
			$query->where('cr.room', 'like', '%' . $room_name . '%');

		if( !empty($room_category) )
		{
			if( $room_category == 'check_in' )
				$query->where('rs.occupancy', OCCUPIED );
			if( $room_category == 'check_out' )
				$query->where('rs.occupancy', VACANT );
			if( $room_category == 'due_out' )
				$query->where('rs.due_out', 1 );
			if( $room_category == 'arrival' )
				$query->where('rs.arrival', 1 );
			if( $room_category == 'rush_clean' )
				$query->where('rs.rush_flag', 1 );
			if( $room_category == 'clean' )
				$query->where('rs.room_status', CLEAN );
			if( $room_category == 'dirty' )
				$query->where('rs.room_status', DIRTY );
		}

		$data_query = clone $query;

		$data_list = $data_query
				->select(DB::raw('cr.*, hs.status, rt.max_time, rt.type as room_type, cf.floor, 
					rs.dispatcher, rs.room_status, rs.occupancy, rs.working_status, rs.rush_flag, rs.arrival, rs.due_out, rs.priority, rs.start_time, rs.end_time
					
					'))
				->orderBy('rs.rush_flag', 'desc')
				->orderby('rs.dispatcher')
				->orderBy('rs.id', 'asc')				
				->get();

		Guest::getGuestDetail($data_list);		
		
		$vacant = VACANT;
		$occupied = OCCUPIED;
		$dirty = DIRTY;
		$clean = CLEAN;
		$sub_count = $count_query
			->select(DB::raw("
					sum(rs.occupancy = '$vacant') as check_out,
					sum(rs.occupancy = '$occupied') as check_in,
					sum(rs.rush_flag = 1) as rush_clean,
					sum(rs.room_status = '$dirty') as dirty,
					sum(rs.room_status = '$clean') as clean,
					sum(rs.due_out = 1) as due_out,
					sum(rs.arrival = 1) as arrival
					"))
			->first();

		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['sub_count'] = $sub_count;

		return Response::json($ret);
	}

	public function getRoomListForTurndown(Request $request) {
		$property_id = $request->get('property_id', '0');
//		$dept_id = $request->get('dept_id', '0');
		$floor_list = $request->get('floors', array());
		$room_name = $request->get('room_name', array());
		$building_id = $request->get('building_id', '0');
		$dispatcher = $request->get('dispatcher', 0);

		$ret = array();

		$query = DB::table('services_room_turndown_status as rs')
				->join('common_room as cr', 'rs.id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')				
				->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
				// ->leftJoin('common_guest_advanced_detail as gad', 'rs.id', '=', 'gad.id')
				->where('cb.property_id', $property_id)
				->where('rs.dispatcher', '<>', $dispatcher)
				->where('rs.working_status', '<>', CLEANING_RUNNING);

		$count_query = clone $query;		

		if( $building_id > 0 )
			$query->where('cf.bldg_id', $building_id);

		if( count($floor_list) )
			$query->whereIn('flr_id', $floor_list);

		if( !empty($room_name) )
			$query->where('cr.room', 'like', '%' . $room_name . '%');

		$data_query = clone $query;

		$data_list = $data_query
				->select(DB::raw('cr.*, hs.status, rt.turn_down as max_time, rt.type as room_type, cf.floor,
					rs.dispatcher, rs.working_status, rs.rush_flag, rs.start_time, rs.end_time					
					'))
				->get();

		Guest::getGuestDetail($data_list);

		$vacant = VACANT;
		$occupied = OCCUPIED;
		$pending = CLEANING_PENDING;
		$done = CLEANING_DONE;
		$sub_count = $count_query
			->select(DB::raw("
					COALESCE(sum(rs.rush_flag = 1), 0) as rush_clean,
					COALESCE(sum(rs.working_status = '$pending'), 0) as pending,
					COALESCE(sum(rs.working_status = '$done'), 0) as done
					"))
			->first();

		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['sub_count'] = $sub_count;

		return Response::json($ret);
	}

	public function getAttendantList(Request $request) {
		$property_id = $request->get('property_id', '0');
		$dept_id = $request->get('dept_id', '0');
		$shift = $request->get('shift', '0');
		$name = $request->get('name', '');

		$job_roles = PropertySetting::getJobRoles($property_id);

		if( $shift <= 0 )
		{
			$attendantlist = DB::table('common_users as cu')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->where('cu.job_role_id', $job_roles['roomattendant_job_role'])
					->where('cd.property_id', '=', $property_id)
					->where('cu.deleted', 0)
					->whereRaw("CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%$name%'")
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
										(select count(1) from services_room_status as rs where rs.dispatcher = cu.id) as assigned_count'))
					->get();

			$supervisorlist = DB::table('common_users as cu')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->where('cu.job_role_id', $job_roles['supervisor_job_role'])
					->where('cd.property_id', '=', $property_id)
					->where('cu.deleted', 0)
					->whereRaw("CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%$name%'")
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->get();
		}
		else
		{

			$attendantlist = DB::table('services_shift_group_members as sgm')
					->join('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
					->join('common_users as cu', 'sgm.user_id', '=', 'cu.id')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->where('cu.job_role_id', $job_roles['roomattendant_job_role'])
					->where('sg.shift', '=', $shift)
					->where('cu.deleted', 0)
					->where('cd.property_id', '=', $property_id)
					->whereRaw("CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%$name%'")
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
										(select count(1) from services_room_status as rs where rs.dispatcher = cu.id) as assigned_count'))
					->get();

			$supervisorlist = DB::table('services_shift_group_members as sgm')
					->join('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
					->join('common_users as cu', 'sgm.user_id', '=', 'cu.id')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->where('cu.job_role_id', $job_roles['supervisor_job_role'])
					->where('sg.shift', '=', $shift)
					->where('cu.deleted', 0)
					->where('cd.property_id', '=', $property_id)
					->whereRaw("CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%$name%'")
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->get();
		}

		$ret = array();
		$ret['attendant_list'] = $attendantlist;
		$ret['supervisor_list'] = $supervisorlist;

		return Response::json($ret);
	}

	public function getAttendantListForTurndown(Request $request) {
		$property_id = $request->get('property_id', '0');
		$dept_id = $request->get('dept_id', '0');
		$shift = $request->get('shift', '0');
		$name = $request->get('name', '');

		$job_roles = PropertySetting::getJobRoles($property_id);

		if( $shift <= 0 )
		{
			$attendantlist = DB::table('common_users as cu')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->where('cu.job_role_id', $job_roles['roomattendant_job_role'])
					->where('cu.deleted', 0)
					->where('cd.property_id', '=', $property_id)
					->whereRaw("CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%$name%'")
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
										(select count(1) from services_room_turndown_status as rs where rs.dispatcher = cu.id) as assigned_count'))
					->get();
		
		}
		else
		{

			$attendantlist = DB::table('services_shift_group_members as sgm')
					->join('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
					->join('common_users as cu', 'sgm.user_id', '=', 'cu.id')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->where('cu.job_role_id', $job_roles['roomattendant_job_role'])
					->where('cu.deleted', 0)
					->where('sg.shift', '=', $shift)
					->where('cd.property_id', '=', $property_id)
					->whereRaw("CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%$name%'")
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
										(select count(1) from services_room_turndown_status as rs where rs.dispatcher = cu.id) as assigned_count'))
					->get();
			
		}

		$ret = array();
		$ret['attendant_list'] = $attendantlist;
		
		return Response::json($ret);
	}

	public function createRoomAssignment(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$dispatcher = $request->get('dispatcher', 186);
		$assigned_list = $request->get('assigned_list', [8]);
		$property_id = $request->get('property_id', 4);

		if( count($assigned_list) < 1)
			return $this->getAssignedRoomListToStaff($request);

		$hskp_ids = HskpStatus::getHskpStatusIDs($property_id);

		// find hskp setting values
		$hskp_setting_time = PropertySetting::getHskpSettingTime($property_id);
		$hskp_setting_value = PropertySetting::getHskpSettingValue($property_id);

		// find attendant list
		$attendantlist = DB::table('common_users as cu')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->leftJoin('services_shift_group_members as sgm', 'sgm.user_id', '=', 'cu.id')
					->leftJoin('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
					->where('cd.property_id', '=', $property_id)
					->where('cu.id', $dispatcher)			
					// ->limit(2)	
					->select(DB::raw('cu.id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, 
						sg.shift
						'))
					->get();

		// find shift list
		$list = DB::table('services_shifts as sh')->get();			
		$shift_list = array();
		foreach($list as $row) {
			$shift_list[$row->id] = $row;
		}

		foreach($attendantlist as $user) {
			$user->time_range_list = array();
			$user->duration = 0;
		}

		// find excepted room list
		$except_list = HskpRoomStatus::where('dispatcher', $dispatcher)
			->whereNotIn('id', $assigned_list )
			->get();

		foreach($except_list as $room) {
			$room->dispatcher = 0;
			$room->start_time = '00:00:00';
			$room->end_time = '00:00:00'; 

			$this->updateRoomStatus($room->id, 0, $room->start_time, $room->end_time, $hskp_ids, $cur_date, $cur_time);
		}

		// find hskp ruuning or done rooms
		$running_list = HskpRoomStatus::whereIn('working_status', array(CLEANING_RUNNING, CLEANING_DONE))
			->where('dispatcher', $dispatcher)
			->whereIn('id', $assigned_list )
			->orderby('start_time', 'desc')
			->get();

		$current_room_ids = [];
		$user = $attendantlist[0];
		foreach($running_list as $row)		
		{
			$current_room_ids[] = $row->id;
			$user->time_range_list[] = [$row->start_time, $row->end_time];
		}

		$user->time_range_list = Functions::sortTimeInterval($user->time_range_list);
		$user->duration = Functions::calcTotalTime($user->time_range_list);		

		// find ids
		$orderby_ids = 'FIELD(rs.id';
		foreach($assigned_list as $row) {
			$orderby_ids .= ',' . $row;	
		}
		$orderby_ids .= ')';

		// find Check in Room but Not due out
		$checkin_list = DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
			->where('rs.occupancy', OCCUPIED)
			->where('rs.due_out', 0)
			->whereIn('rs.id', $assigned_list)
			->whereNotIn('rs.id', $current_room_ids)
			->orderBy('rush_flag', 'desc')
			->orderByRaw($orderby_ids)
			->select(DB::raw('rs.id, cr.room, rt.max_time'))
			->get();

		// find Due Out rooms
		$dueout_list = DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
			->where('rs.property_id', $property_id)
			->where('rs.due_out', 1)
			->whereIn('rs.id', $assigned_list)
			->whereNotIn('rs.id', $current_room_ids)			
			->orderByRaw($orderby_ids)
			->select(DB::raw('rs.id, cr.room, rt.max_time'))
			->get();
				
		// find Check in Room but Not due out
		$checkout_list = DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
			->where('rs.property_id', $property_id)
			->where('rs.occupancy', VACANT)			
			->whereIn('rs.id', $assigned_list)
			->whereNotIn('rs.id', $current_room_ids)			
			->orderByRaw($orderby_ids)
			->select(DB::raw('rs.id, cr.room, rt.max_time'))
			->get();			

		// first assign checkin room in hskp cleaning time and vacant_room_cleaning.
		$assign_intervals = [];
		$assign_intervals[] = $hskp_setting_time['hskp_cleaning_time'];

		$this->assignRoomListToInterval($checkin_list, $attendantlist, $assign_intervals, $hskp_setting_value, $shift_list, $hskp_ids);

		// second assign due_out room after due out time
		$assign_intervals = [];
		if( $hskp_setting_value['due_out_clean'] == 1 )	// must assign after due out time
			$assign_intervals[] = [$hskp_setting_time['due_out_time'][1], '23:59:59']; 
		else
			$assign_intervals[] = ['00:00:00', '23:59:59']; 

		$this->assignRoomListToInterval($dueout_list, $attendantlist, $assign_intervals, $hskp_setting_value, $shift_list, $hskp_ids);

		// third assign checkout room after vacant room cleaning
		$assign_intervals = [];
		$assign_intervals[] = $hskp_setting_time['vacant_room_cleaning']; 
		$this->assignRoomListToInterval($checkout_list, $attendantlist, $assign_intervals, $hskp_setting_value, $shift_list, $hskp_ids);

		Functions::sendHskpStatusChangeToProperty($property_id);

		return $this->getAssignedRoomListToStaff($request);
	}

	public function createRoomAssignmentForTurndown(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$dispatcher = $request->get('dispatcher', 186);
		$assigned_list = $request->get('assigned_list', [8]);
		$property_id = $request->get('property_id', 4);

		if( count($assigned_list) < 1)
			return $this->getAssignedRoomListToStaffForTurndown($request);

		// find hskp setting values
		$hskp_setting_time = PropertySetting::getHskpSettingTime($property_id);
		$hskp_setting_value = PropertySetting::getHskpSettingValue($property_id);

		// find attendant list
		$attendantlist = DB::table('common_users as cu')
					->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->leftJoin('services_shift_group_members as sgm', 'sgm.user_id', '=', 'cu.id')
					->leftJoin('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
					->where('cd.property_id', '=', $property_id)
					->where('cu.id', $dispatcher)			
					// ->limit(2)	
					->select(DB::raw('cu.id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, 
						sg.shift
						'))
					->get();

		// find shift list
		$list = DB::table('services_shifts as sh')->get();			
		$shift_list = array();
		foreach($list as $row) {
			$shift_list[$row->id] = $row;
		}

		foreach($attendantlist as $user) {
			$user->time_range_list = array();
			$user->duration = 0;
		}

		// find excepted room list
		$except_list = HskpTurndownStatus::where('dispatcher', $dispatcher)
			->whereNotIn('id', $assigned_list )
			->get();

		foreach($except_list as $room) {
			$room->dispatcher = 0;
			$room->start_time = '00:00:00';
			$room->end_time = '00:00:00'; 

			$this->updateRoomTurndownStatus($room->id, 0, $room->start_time, $room->end_time, $hskp_ids, $cur_date, $cur_time);
		}

		// done rooms
		$running_list = HskpTurndownStatus::whereIn('working_status', array(CLEANING_DONE))
			->where('dispatcher', $dispatcher)
			->whereIn('id', $assigned_list )
			->orderby('start_time', 'desc')
			->get();

		$current_room_ids = [];
		$user = $attendantlist[0];
		foreach($running_list as $row)		
		{
			$current_room_ids[] = $row->id;
			$user->time_range_list[] = [$row->start_time, $row->end_time];
		}

		$user->time_range_list = Functions::sortTimeInterval($user->time_range_list);
		$user->duration = Functions::calcTotalTime($user->time_range_list);		

		// find ids
		$orderby_ids = 'FIELD(rs.id';
		foreach($assigned_list as $row) {
			$orderby_ids .= ',' . $row;	
		}
		$orderby_ids .= ')';

		// find Check in Room but Not due out
		$checkin_list = DB::table('services_room_turndown_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
			->whereIn('rs.id', $assigned_list)
			->whereNotIn('rs.id', $current_room_ids)
			->orderBy('rush_flag', 'desc')
			->orderByRaw($orderby_ids)
			->select(DB::raw('rs.id, cr.room, rt.turn_down as max_time'))
			->get();

		// first assign checkin room in vacant_room_cleaning.
		$assign_intervals = [];
		$assign_intervals[] = $hskp_setting_time['vacant_room_cleaning'];

		$this->assignRoomListToIntervalForTurndown($checkin_list, $attendantlist, $assign_intervals, $hskp_setting_value, $shift_list);

		Functions::sendHskpStatusChangeToProperty($property_id);

		return $this->getAssignedRoomListToStaffForTurndown($request);
	}

	public function createRoomAssignmentWithAuto(Request $request) {		
		$property_id = $request->get('property_id', 0);
		return $this->createRoomAssignmentWithAuto_Proc($property_id);
	}

	public function createRoomAssignmentWithAuto_Proc($property_id) 
	{
		$start = microtime(true);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");		
		$only_time = date("H:i:s");

		
		$hskp_ids = HskpStatus::getHskpStatusIDs($property_id);

		// find hskp setting values
		$hskp_setting_time = PropertySetting::getHskpSettingTime($property_id);
		$hskp_setting_value = PropertySetting::getHskpSettingValue($property_id);

		$job_roles = PropertySetting::getJobRoles($property_id);

		// find attendant list
		$attendantlist = DB::table('common_users as cu')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->leftJoin('services_shift_group_members as sgm', 'sgm.user_id', '=', 'cu.id')
					->leftJoin('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
					->where('cu.job_role_id', '=', $job_roles['roomattendant_job_role'])
					->where('cu.deleted', 0)
					->where('cd.property_id', '=', $property_id)	
					// ->where('cu.id', 1)			
					// ->limit(2)	
					->select(DB::raw('cu.id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, 
						sg.shift
						'))
					->get();

		// find shift list
		$list = DB::table('services_shifts as sh')->get();			
		$shift_list = array();
		foreach($list as $row) {
			$shift_list[$row->id] = $row;
		}

		foreach($attendantlist as $user) {
			$user->time_range_list = array();
			$user->duration = 0;
		}

		// find Check in Room but Not due out
		$checkin_list = DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
			->where('rs.property_id', $property_id)
			->where('rs.occupancy', OCCUPIED)
			->where('rs.due_out', 0)
			->orderBy('rush_flag', 'desc')
			->select(DB::raw('rs.id, cr.room, rt.max_time'))
			->get();

		// find Due Out rooms
		$dueout_list = DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
			->where('rs.property_id', $property_id)
			->where('rs.due_out', 1)			
			->select(DB::raw('rs.id, cr.room, rt.max_time'))
			->get();
				
		// find Check in Room but Not due out
		$checkout_list = DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
			->where('rs.property_id', $property_id)
			->where('rs.occupancy', VACANT)						
			->select(DB::raw('rs.id, cr.room, rt.max_time'))
			->get();			

		// first assign checkin room in hskp cleaning time and vacant_room_cleaning.
		$assign_intervals = [];
		$assign_intervals[] = $hskp_setting_time['hskp_cleaning_time'];
		// $assign_intervals[] = $hskp_setting_time['turn_down_service'];

		$this->assignRoomListToInterval($checkin_list, $attendantlist, $assign_intervals, $hskp_setting_value, $shift_list, $hskp_ids);

		// second assign due_out room after due out time
		$assign_intervals = [];
		if( $hskp_setting_value['due_out_clean'] == 1 )	// must assign after due out time
			$assign_intervals[] = [$hskp_setting_time['due_out_time'][1], '23:59:59']; 
		else
			$assign_intervals[] = ['00:00:00', '23:59:59']; 

		$this->assignRoomListToInterval($dueout_list, $attendantlist, $assign_intervals, $hskp_setting_value, $shift_list, $hskp_ids);

		// third assign checkout room after vacant room cleaning
		$assign_intervals = [];
		$assign_intervals[] = $hskp_setting_time['vacant_room_cleaning']; 
		$this->assignRoomListToInterval($checkout_list, $attendantlist, $assign_intervals, $hskp_setting_value, $shift_list, $hskp_ids);

		$end = microtime(true);
		
		return Response::json($attendantlist);
	}

	public function createRoomAssignmentWithAutoForTurndown(Request $request) {		
		$property_id = $request->get('property_id', 0);
		return $this->createRoomAssignmentWithAutoForTurndown_Proc($property_id);
	}

	public function createRoomAssignmentWithAutoForTurndown_Proc($property_id) 
	{
		$start = microtime(true);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");		
		$only_time = date("H:i:s");

		// find hskp setting values
		$hskp_setting_time = PropertySetting::getHskpSettingTime($property_id);
		$hskp_setting_value = PropertySetting::getHskpSettingValue($property_id);

		$job_roles = PropertySetting::getJobRoles($property_id);

		// find attendant list
		$attendantlist = DB::table('common_users as cu')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->leftJoin('services_shift_group_members as sgm', 'sgm.user_id', '=', 'cu.id')
					->leftJoin('services_shift_group as sg', 'sgm.shift_group_id', '=', 'sg.id')
					->where('cu.job_role_id', '=', $job_roles['roomattendant_job_role'])
					->where('cu.deleted', 0)
					->where('cd.property_id', '=', $property_id)	
					// ->where('cu.id', 1)			
					// ->limit(2)	
					->select(DB::raw('cu.id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, 
						sg.shift
						'))
					->get();

		// find shift list
		$list = DB::table('services_shifts as sh')->get();			
		$shift_list = array();
		foreach($list as $row) {
			$shift_list[$row->id] = $row;
		}

		foreach($attendantlist as $user) {
			$user->time_range_list = array();
			$user->duration = 0;
		}

		// find Check in Room but Not due out
		$checkin_list = DB::table('services_room_turndown_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
			->where('rs.property_id', $property_id)
			->orderBy('rush_flag', 'desc')
			->select(DB::raw('rs.id, cr.room, rt.turn_down as max_time'))
			->get();


		// first assign checkin room in hskp cleaning time and vacant_room_cleaning.
		$assign_intervals = [];
		$assign_intervals[] = $hskp_setting_time['turn_down_service'];
		
		$this->assignRoomListToIntervalForTurndown($checkin_list, $attendantlist, $assign_intervals, $hskp_setting_value, $shift_list);

		$end = microtime(true);
		
		return Response::json($attendantlist);
	}
	private function assignRoomListToInterval($room_list, $attendantlist, $assign_intervals, $hskp_setting_value, $shift_list, $hskp_ids) {
		return;
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$only_time = date("H:i:s");
		$cur_date = date("Y-m-d");
		$next_date = date("Y-m-d", strtotime(' +1 day'));

		$max_clean_duration = $hskp_setting_value['max_clean_duration'];
		$adult_pax_allowance = 0;
		if($hskp_setting_value['pax_allowance'] == 1)
			$adult_pax_allowance = $hskp_setting_value['adult_pax_allowance'];
		
		foreach($room_list as $room) 
		{
			$min_duration = 100000000000;
			$min_user_id = 0;
			$min_valid_start_time = '';

			foreach($attendantlist as $user) 
			{
				if( $user->duration / 60 + $room->max_time + $adult_pax_allowance > $max_clean_duration )
					continue;

				$shift = $shift_list[$user->shift];
				$shift_start = $shift->start_time;
				$shift_end = $shift->end_time;

				$check_interval = array();
				$start = $shift_start;

				// iterate time interval for a attedant
				for($i = 0; $i < count($user->time_range_list); $i++ )
				{
					$check_interval[] = [$start, $user->time_range_list[$i][0]];
					$start = $user->time_range_list[$i][1];					 
				} 

				$check_interval[] = [$start, $shift_end];

				$check_interval = Functions::rearrangeTimeIntervalWithCurrentTime($check_interval, $only_time);

				$canbe_assigned = false;
				$valid_start_time = '';

				// shift <-> hskp cleaning time
				foreach($assign_intervals as $item) {
					$check_start = $item[0];
					$check_end = $item[1];

					foreach($check_interval as $interval) {
						$start_time = max($interval[0], $check_start);
						$end_time = min($interval[1], $check_end);

						$gap = strtotime($end_time) - strtotime($start_time);
						if( $gap < ($room->max_time + $adult_pax_allowance) * 60 )	// avaliable time is less than room max time
						{
							// cannot assigned
						}
						else // available time range exist.
						{
							$canbe_assigned = true;
							$valid_start_time = $start_time;	
							break;												
						}
					}

					if( $canbe_assigned == true )
						break;
				}
				
				if( $canbe_assigned && $user->duration < $min_duration)	// find small duration user with can be assigned
				{
					$min_duration = $user->duration;
					$min_valid_start_time = $valid_start_time;
					$min_user_id = $user->id;
				}
			}

			if($min_user_id == 0)	// not valid user
			{
				$room->dispatcher = 0;
				$room->start_time = '00:00:00';
				$room->end_time = '00:00:00'; 

				$this->updateRoomStatus($room->id, $min_user_id, $room->start_time, $room->end_time, $hskp_ids, $cur_date, $cur_time);

				continue;
			}

			foreach($attendantlist as $user) 
			{
				if($user->id == $min_user_id)
				{
					$room->dispatcher = $min_user_id;
					$room->start_time = $min_valid_start_time;
					$room->end_time = Functions::addMinute($room->start_time, $room->max_time + $adult_pax_allowance); 

					$user->time_range_list[] = [$room->start_time, $room->end_time];							
					$user->time_range_list = Functions::sortTimeInterval($user->time_range_list);
					$user->duration = Functions::calcTotalTime($user->time_range_list);		

					$save_date = $cur_date;
					if( $room->start_time < $only_time )
						$save_date = $next_date;

					$this->updateRoomStatus($room->id, $min_user_id, $room->start_time, $room->end_time, $hskp_ids, $save_date, $cur_time);

					break;
				}
			}	
		}	
		
		Functions::sendHskpStatusChangeToProperty(0);
	}

	private function assignRoomListToIntervalForTurndown(&$room_list, &$attendantlist, $assign_intervals, $hskp_setting_value, $shift_list) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$only_time = date("H:i:s");
		$cur_date = date("Y-m-d");
		$next_date = date("Y-m-d", strtotime(' +1 day'));

		$max_turndown_duration = $hskp_setting_value['max_turndown_duration'];
		
		foreach($room_list as $room) 
		{
			$min_duration = 100000000000;
			$min_user_id = 0;
			$min_valid_start_time = '';

			foreach($attendantlist as $user) 
			{
				if( $user->duration / 60 + $room->max_time > $max_turndown_duration )
					continue;

				$shift = $shift_list[$user->shift];
				$shift_start = $shift->start_time;
				$shift_end = $shift->end_time;

				$check_interval = array();
				$start = $shift_start;

				// iterate time interval for a attedant
				for($i = 0; $i < count($user->time_range_list); $i++ )
				{
					$check_interval[] = [$start, $user->time_range_list[$i][0]];
					$start = $user->time_range_list[$i][1];					 
				} 

				$check_interval[] = [$start, $shift_end];

				$check_interval = Functions::rearrangeTimeIntervalWithCurrentTime($check_interval, $only_time);

				$canbe_assigned = false;
				$valid_start_time = '';

				// shift <-> hskp cleaning time
				foreach($assign_intervals as $item) {
					$check_start = $item[0];
					$check_end = $item[1];

					foreach($check_interval as $interval) {
						$start_time = max($interval[0], $check_start);
						$end_time = min($interval[1], $check_end);

						$gap = strtotime($end_time) - strtotime($start_time);
						if( $gap < ($room->max_time) * 60 )	// avaliable time is less than room max time
						{
							// cannot assigned
						}
						else // available time range exist.
						{
							$canbe_assigned = true;
							$valid_start_time = $start_time;	
							break;												
						}
					}

					if( $canbe_assigned == true )
						break;
				}
				
				if( $canbe_assigned && $user->duration < $min_duration)	// find small duration user with can be assigned
				{
					$min_duration = $user->duration;
					$min_valid_start_time = $valid_start_time;
					$min_user_id = $user->id;
				}
			}

			if($min_user_id == 0)	// not valid user
			{
				$room->dispatcher = 0;
				$room->start_time = '00:00:00';
				$room->end_time = '00:00:00'; 

				$this->updateRoomTurndownStatus($room->id, $min_user_id, $room->start_time, $room->end_time, $cur_date, $cur_time);

				continue;
			}

			foreach($attendantlist as $user) 
			{
				if($user->id == $min_user_id)
				{
					$room->dispatcher = $min_user_id;
					$room->start_time = $min_valid_start_time;
					$room->end_time = Functions::addMinute($room->start_time, $room->max_time); 

					$user->time_range_list[] = [$room->start_time, $room->end_time];							
					$user->time_range_list = Functions::sortTimeInterval($user->time_range_list);
					$user->duration = Functions::calcTotalTime($user->time_range_list);		

					$save_date = $cur_date;
					if( $room->start_time < $only_time )
						$save_date = $next_date;

					$this->updateRoomTurndownStatus($room->id, $min_user_id, $room->start_time, $room->end_time, $cur_date, $cur_time);

					break;
				}
			}	
		}			
	}

	private function updateRoomStatus($room_id, $dispatcher, $start_time, $end_time, $hskp_ids, $cur_date, $cur_time) {
		// save room status
		$room_status = HskpRoomStatus::find($room_id);

		if( empty($room_status) )
			return;

		$room = Room::find($room_id);
		if( empty($room) )
			return;

		if( $dispatcher > 0 )
		{
			$room_status->dispatcher = $dispatcher;
			$room_status->priority = 1;
			$room_status->working_status = CLEANING_PENDING;			
	        
		}
		else
		{
			$room_status->dispatcher = 0;
			$room_status->priority = 0;
			$room_status->working_status = CLEANING_NOT_ASSIGNED;
		}

		$room_status->start_time = $cur_date . ' ' . $start_time;
		$room_status->end_time = $cur_date . ' ' . $end_time;

		$room_status->save();

		// find 
		if( $room_status->occupancy == 'Vacant' )
            $room->hskp_status_id = $hskp_ids[0];                
        else
            $room->hskp_status_id = $hskp_ids[1];

		$room->save();
		
		if($dispatcher > 0)
		{
			$hskp_log = new HskpStatusLog();

			$hskp_log->room_id = $room->id;
			$hskp_log->hskp_id = $room->hskp_status_id;
			$hskp_log->user_id = $dispatcher;
			$hskp_log->state = CLEANING_PENDING;	
			$hskp_log->created_at = $cur_time;

			$hskp_log->save();
            // Functions::sendHskpStatusChangeWithRoom($room->id);
		}


	}

	private function updateRoomTurndownStatus($room_id, $dispatcher, $start_time, $end_time, $cur_date, $cur_time) {
		// save room status
		$room_status = HskpTurndownStatus::find($room_id);

		if( empty($room_status) )
			return;

		$room = Room::find($room_id);
		if( empty($room) )
			return;

		if( $dispatcher > 0 )
		{
			$room_status->dispatcher = $dispatcher;
			$room_status->working_status = CLEANING_PENDING;			
	        
		}
		else
		{
			$room_status->dispatcher = 0;
			$room_status->working_status = CLEANING_NOT_ASSIGNED;
		}

		$room_status->start_time = $cur_date . ' ' . $start_time;
		$room_status->end_time = $cur_date . ' ' . $end_time;

		$room_status->save();

		if($dispatcher > 0)
		{
			$hskp_log = new HskpStatusLog();

			$hskp_log->room_id = $room->id;
			$hskp_log->hskp_id = $room->hskp_status_id;
			$hskp_log->user_id = $dispatcher;
			$hskp_log->state = CLEANING_PENDING;	
			$hskp_log->created_at = $cur_time;

			$hskp_log->save();

			// Functions::sendHskpStatusChangeWithRoom($room->id);

        }

	}


	public function refuseRoom(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);
		$reason = $request->get('reason', "");

		$room_info = DB::table('common_room as cr')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*'))
				->first();

		$room_status = HskpRoomStatus::find($room_id);
		if( !empty($room_status) )
		{
			if($room_status->td_flag!=1)
			{
				$room_status->working_status = CLEANING_REFUSE;
				$room_status->updated_at = $cur_time;
			}
			else
			{
				$room_status->td_working_status = CLEANING_REFUSE;
				$room_status->updated_at = $cur_time;
			}

			$room_status->save();
			$room_status->working_status = CLEANING_REFUSE;
			if($room_status->td_flag==1)
				$room_status->cleaning_state = 'TD Refused';
			else
				$room_status->cleaning_state = 'Refused';
		}

		$hskp_log = new HskpStatusLog();
		$hskp_log->method = 'Mobile';
		$hskp_log->room_id = $room_id;
		$hskp_log->hskp_id = $room_info->hskp_status_id;
		$hskp_log->user_id = $user_id;
		if($room_status->td_flag==1)
		{
			$hskp_log->td_state = CLEANING_REFUSE;
			$hskp_log->td_flag =1;
		}
		else
			$hskp_log->state = CLEANING_REFUSE;
			
		$hskp_log->created_at = $cur_time;
		$hskp_log->reason = $reason;

		$hskp_log->save();
		$hskp_log->state = CLEANING_REFUSE;

        Functions::sendHskpStatusChangeWithRoom($room_id);

		$ret['code'] = 200;
		$ret['content'] = $room_status;
		$ret['message'] = 'Room status has been changed to Refused';

		return Response::json($ret);

	}
		
	public function declineRoomSupervisor(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);
		$reason = $request->get('reason', "");

		$user = CommonUser::find($user_id);

		$room = Room::find($room_id);
	
		$room_status = HskpRoomStatus::find($room_id);
		
		if( !empty($room_status) )
		{
			if($room_status->td_flag!=1)
			{
				$room_status->working_status = CLEANING_DECLINE;
				$room_status->updated_at = $cur_time;
			}
			else
			{
				$room_status->td_working_status = CLEANING_DECLINE;
				$room_status->updated_at = $cur_time;
			}

			$room_status->room_status = 'Dirty';	
			$room_status->save();
			$room_status->working_status = CLEANING_DECLINE;
			if($room_status->td_flag==1)
				$room_status->cleaning_state = 'TD Reject';
			else
				$room_status->cleaning_state = 'Reject';
		}

	
		$hskp_status_id = $room_status->getHskpStatusId();

		$hskp_log = new HskpStatusLog();
		$hskp_log->method = 'Mobile';
		$hskp_log->room_id = $room_id;
		$hskp_log->hskp_id = $hskp_status_id;
		$hskp_log->user_id = $user_id;
		if($room_status->td_flag==1)
		{
			$hskp_log->td_state = CLEANING_DECLINE;
			$hskp_log->td_flag =1;
		}
		else
			$hskp_log->state = CLEANING_DECLINE;	

		$hskp_log->created_at = $cur_time;
		$hskp_log->reason = $reason;

		if( HskpRoomStatus::isActiveChecklist($room_id, 'Supervisor' ) )
		{
			$hskp_log->check_num = $room_status->supervisor_check_num;	
		}

		RosterList::setRosterIdsForHskpLog($hskp_log, $room_status);
		
		$hskp_log->save();
		$hskp_log->state = CLEANING_DECLINE;

        Functions::sendHskpStatusChangeWithRoom($room_id);

		$message="Inspection for Room ".$room->room." has been rejected by Supervisor: ".$user->first_name.' '.$user->last_name.", due to: ".$reason;
		$roster = RosterList::find($room_status->attendant_id);
		RosterList::sendRosterNotification($roster, $message);
	       
		$ret['code'] = 200;
		$ret['content'] = $room_status;
		$ret['message'] = $message;

		return Response::json($ret);

	}

	public function dndRoom(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);

		$guest = DB::table('common_guest as cg')
				->where('cg.room_id', $room_id)
				->where('cg.departure', '>=', $cur_date)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

		if( empty($guest) )		
		{
			$ret['code'] = 201;
			$ret['message'] = 'No Guest Checked In yet';

			return Response::json($ret);
		}

		$room_info = DB::table('common_room as cr')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*'))
				->first();

		$room_status = HskpRoomStatus::find($room_id);
		if( !empty($room_status) )
		{
			if($room_status->td_flag!=1)
			{
			$room_status->working_status = CLEANING_DND;
			$room_status->updated_at = $cur_time;
			
			}
			else
			{
				$room_status->td_working_status = CLEANING_DND;
				$room_status->updated_at = $cur_time;
			}
			$room_status->save();

			$room_status->working_status = CLEANING_DND;
			if($room_status->td_flag==1)
			$room_status->cleaning_state = 'TD DND';
			else
			$room_status->cleaning_state = 'DND';

		
		}

		$hskp_log = new HskpStatusLog();
		$hskp_log->method = 'Mobile';
		$hskp_log->room_id = $room_id;
		$hskp_log->hskp_id = $room_info->hskp_status_id;
		$hskp_log->user_id = $user_id;
		if($room_status->td_flag==1)
		{
			$hskp_log->td_state = CLEANING_DND;
			$hskp_log->td_flag =1;
		}
		else
			$hskp_log->state = CLEANING_DND;
			
		$hskp_log->created_at = $cur_time;

		$hskp_log->save();
		$hskp_log->state = CLEANING_DND;

        Functions::sendHskpStatusChangeWithRoom($room_id);

		$ret['code'] = 200;
		$ret['content'] = $room_status;
		$ret['message'] = 'Room status has been changed to Do Not Disturb';

		return Response::json($ret);

	}
	public function finishRoom(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$user_id = $request->get('user_id', 0);		
		$room_id = $request->get('room_id', 0);

		$ret = array();

		$room_status = HskpRoomStatus::find($room_id);
		if( empty($room_status) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Room does not exist.';

			return Response::json($ret);
		}

		$room_status->end_time = $cur_time;
		$room_status->save();

		$roster = RosterList::find($room_status->attendant_id);
		if( empty($roster) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Roster does not exist.';

			return Response::json($ret);
		}

		$roster->save();
	
		$room_info = Room::getPropertyBuildingFloor($room_id);		
		
		if(empty($room_info)){
			
		}
		else
		{			
			$property_id = $room_info->property_id;
		
			$hskp_status_name = "$room_status->occupancy Clean";

			// get complete status id
			$hskp_info = DB::table('services_hskp_status as hs')
					->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
					->where('hs.status', $hskp_status_name)
					->where('cb.property_id', $property_id)
					->select(DB::raw('hs.*'))
					->first();

			$this->changeRoomHskpStatus($user_id, $room_id, $hskp_info, $hskp_info->status, 'Mobile');
		}

		if( $room_status->supervisor_id > 0)
		{
			$supervisor = DB::table('services_roster_list')
								->where('id', $room_status->supervisor_id)
								->first();
			if( !empty($supervisor) && $supervisor->id > 0 )					
			{
				$user = CommonUser::find($user_id);
				if( !empty($user) )
					$message = $user->first_name.' '.$user->last_name.' has finished cleaning Room '.$room_info->room;			
				else
					$message = 'Someone has finished cleaning Room '.$room_info->room;			

				RosterList::sendRosterNotification($supervisor, $message);
			}
		}
		
		$ret['code'] = 200;
		$ret['content'] = HskpRoomStatus::getRoomStatus($room_status->id);
		$ret['updated_at'] = $cur_time;
		$ret['working_status'] = CLEANING_DONE;		
		$ret['message'] = 'Room has been changed to finish';

		return Response::json($ret);
	}

	public function pauseRoom(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$device_id = $request->get('device_id', 0);
		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);

		$guest = DB::table('common_guest as cg')
				->where('cg.room_id', $room_id)
				->where('cg.departure', '>=', $cur_date)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();
		$device=Device::where('device_id',$device_id)->first();
 		$roster=RosterList::where('device',$device->id)->first();		
		$roster->save();
	

		$room_info = DB::table('common_room as cr')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*'))
				->first();

		$room_status = HskpRoomStatus::find($room_id);
		if( !empty($room_status) )
		{
			if($room_status->td_flag != 1)
				$room_status->working_status = CLEANING_PAUSE;
			else
				$room_status->td_working_status = CLEANING_PAUSE;
		
			$room_status->updated_at = $cur_time;
			$room_status->end_time = $cur_time;
			$room_status->room_status = "Dirty";

			$room_status->save();

			$room_status->working_status = CLEANING_PAUSE;
			if($room_status->td_flag==1)
				$room_status->cleaning_state = 'TD Pause';
			else
				$room_status->cleaning_state = 'Pause';
		}

		$hskp_log = new HskpStatusLog();
		$hskp_log->method = 'Mobile';
		$hskp_log->room_id = $room_id;
		$hskp_log->hskp_id = $room_info->hskp_status_id;
		$hskp_log->user_id = $user_id;
		if($room_status->td_flag==1)
		{
			$hskp_log->td_state = CLEANING_PAUSE;
			$hskp_log->td_flag =1;
		}
		else
			$hskp_log->state = CLEANING_PAUSE;
		
		$hskp_log->created_at = $cur_time;

		$hskp_log->save();
		$hskp_log->state = CLEANING_PAUSE;

        Functions::sendHskpStatusChangeWithRoom($room_id);

		$ret['code'] = 200;
		$ret['content'] = $room_status;
		$ret['updated_at'] = $cur_time;
		$ret['message'] = 'Room has been changed to pause';

		return Response::json($ret);

	}

	public function sleepoutRoom(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$device_id = $request->get('device_id', 0);
		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);

		$guest = DB::table('common_guest as cg')
				->where('cg.room_id', $room_id)
				->where('cg.departure', '>=', $cur_date)
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();
		$device=Device::where('device_id',$device_id)->first();
 		$roster=RosterList::where('device',$device->id)->first();		
		$roster->save();
	

		$room_info = DB::table('common_room as cr')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*'))
				->first();

		$room_status = HskpRoomStatus::find($room_id);
		if( !empty($room_status) )
		{
			if($room_status->td_flag != 1)
				$room_status->working_status = SLEEPOUT;
			else
				$room_status->td_working_status = SLEEPOUT;
		
			$room_status->updated_at = $cur_time;
			$room_status->end_time = $cur_time;
		//	$room_status->room_status = "Dirty";

			$room_status->save();

			$room_status->working_status = SLEEPOUT;
			if($room_status->td_flag==1)
				$room_status->cleaning_state = 'TD Sleep Out';
			else
				$room_status->cleaning_state = 'Sleep Out';
		}

		$hskp_log = new HskpStatusLog();
		$hskp_log->method = 'Mobile';
		$hskp_log->room_id = $room_id;
		$hskp_log->hskp_id = $room_info->hskp_status_id;
		$hskp_log->user_id = $user_id;
		if($room_status->td_flag==1)
		{
			$hskp_log->td_state = SLEEPOUT;
			$hskp_log->td_flag =1;
		}
		else
			$hskp_log->state = SLEEPOUT;
		
		$hskp_log->created_at = $cur_time;

		$hskp_log->save();
		$hskp_log->state = SLEEPOUT;

        Functions::sendHskpStatusChangeWithRoom($room_id);

		$ret['code'] = 200;
		$ret['content'] = $room_status;
		$ret['updated_at'] = $cur_time;
		$ret['message'] = 'Room has been changed to Sleepout';

		return Response::json($ret);

	}

	public function postponeRoom(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		
		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);
		$time = $request->get('time', 0);

		$room_info = DB::table('common_room as cr')
				->where('cr.id', $room_id)
				->select(DB::raw('cr.*'))
				->first();

		// save postponed time
		$start_time = new DateTime($cur_time);
		$start_time->add(new DateInterval('PT' . $time . 'M'));		
		$start_time = $start_time->format("Y-m-d H:i:s");

		$room_status = HskpRoomStatus::find($room_id);
		if( !empty($room_status) )
		{
			if($room_status->td_flag!=1)
			{
				$room_status->working_status = CLEANING_POSTPONE;
				$room_status->start_time = $start_time;
				$room_status->updated_at = $cur_time;
			}
			else
			{
				$room_status->td_working_status = CLEANING_POSTPONE;
				$room_status->td_start_time = $start_time;
				$room_status->updated_at = $cur_time;
			}
			
			$room_status->save();
			$room_status->working_status = CLEANING_POSTPONE;
			$room_status->start_time = $start_time;
			if($room_status->td_flag==1)
				$room_status->cleaning_state = 'TD Delay';
			else
				$room_status->cleaning_state = 'Delay';
		}

		$hskp_log = new HskpStatusLog();
		$hskp_log->method = 'Mobile';
		$hskp_log->room_id = $room_id;
		$hskp_log->hskp_id = $room_info->hskp_status_id;
		$hskp_log->user_id = $user_id;
		if($room_status->td_flag==1)
		{
			$hskp_log->td_state = CLEANING_POSTPONE;
			$hskp_log->td_flag =1;
		}
		else
			$hskp_log->state = CLEANING_POSTPONE;
			
		$hskp_log->created_at = $cur_time;

		$hskp_log->save();
			$hskp_log->state = CLEANING_POSTPONE;

        Functions::sendHskpStatusChangeWithRoom($room_id);

		$ret['code'] = 200;
		$ret['content'] = $room_status;
		$ret['message'] = 'Room status has been changed to Postponed';

		return Response::json($ret);
	}

	public function testNightAudit(Request $request) {
		$property_id = $request->get('property_id', 4);
		$this->nightAudit($property_id);

	}

	private function initHskpRoomListStatus($property_id)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$yesterday = date('Y-m-d', strtotime("-1 days"));

		DB::table('services_room_turndown_status')
			->where('property_id', $property_id)
			->delete();

		// all room status to dirty
        $roomlist = DB::table('common_room as cr')
            ->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
            ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
            ->where('cb.property_id', $property_id)
            ->select(DB::raw('cr.*'))
            ->get();

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
        for($i = 0; $i < count($roomlist); $i++ )
        {
            $room = $roomlist[$i];
            $guest = DB::table('common_guest')
                ->where('room_id', $room->id)
                ->where('departure', '>=', $cur_date)
                ->where('checkout_flag', 'checkin')
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();
				
			$hskproom_status = HskpRoomStatus::find($room->id);
			if(empty($hskproom_status)) {
				$hskp_room_status = new HskpRoomStatus();

				$hskp_room_status->id = $room->id;
				$hskp_room_status->property_id = $property_id;

				if (!empty($guest)) {
					array_push($occupied_dirty_ids, $room->id);
					$hskp_room_status->occupancy = OCCUPIED;
				} else {
					array_push($vacant_dirty_ids, $room->id);
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
					$hskp_turndown_status->id = $room->id;
					$hskp_turndown_status->property_id = $property_id;
					$hskp_turndown_status->working_status = CLEANING_NOT_ASSIGNED;

					$hskp_turndown_status->save();
				}
			}
        }

        Guest::addOccupancyData($property_id, $yesterday);

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
		
		echo json_encode($vacant_dirty_ids);
        echo '</br>';
        echo json_encode($occupied_dirty_ids);
	}

	private function resetHskpRoomListStatus($property_id)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		// delete all room status info 		
		$hskp_setting_value = PropertySetting::getHskpSettingValue($property_id);


		if ($hskp_setting_value['NA_roster_clear'] == 1){ // clear Roster
		DB::table('services_room_status')
			->where('property_id', $property_id)
			->update([
				'attendant_id' => 0,	// remove attendant's room list
				'rush_flag' => 0,	// Rush Clean should also be reset.
				]);
		
		DB::table('services_room_status')
				->where('property_id', $property_id)
				->whereNotIn('working_status', array(CLEANING_DONE, CLEANING_COMPLETE))
				->update(['room_status' => 'Dirty',
						'attendant_id' => 0,
						'working_status' => CLEANING_NOT_ASSIGNED,
						'td_working_status' => CLEANING_NOT_ASSIGNED,
						'td_flag'=> 0]);
		}
		else{
			DB::table('services_room_status')
			->where('property_id', $property_id)
			->update([
				'rush_flag' => 0	// Rush Clean should also be reset.
				]);
		
		DB::table('services_room_status')
				->where('property_id', $property_id)
				->whereNotIn('working_status', array(CLEANING_DONE, CLEANING_COMPLETE))
				->where('attendant_id', '!=', 0)
				->update(['room_status' => 'Dirty',
						'working_status' => CLEANING_PENDING,
						'td_working_status' => CLEANING_PENDING,
						'td_flag'=> 0]);

		DB::table('services_room_status')
				->where('property_id', $property_id)
				->whereNotIn('working_status', array(CLEANING_DONE, CLEANING_COMPLETE))
				->where('attendant_id', 0)
				->update(['room_status' => 'Dirty',
						'working_status' => CLEANING_NOT_ASSIGNED,
						'td_working_status' => CLEANING_NOT_ASSIGNED,
						'td_flag'=> 0]);

		}
		$query = DB::table('services_room_status')
			->where('property_id', $property_id)
			->whereIn('working_status', array(CLEANING_COMPLETE));

		if( $hskp_setting_value['NA_inspected_finished'] == 1)	// Inspected to Finished
		{
			$query->where('occupancy', 'Vacant')						
					->update([
							'working_status' => CLEANING_DONE
							]);
		}

		// Finished to Unassigned				
		$query = DB::table('services_room_status')
			->where('property_id', $property_id)
			->whereIn('working_status', array(CLEANING_DONE, CLEANING_COMPLETE));

		if( $hskp_setting_value['NA_vacant_dirty'] == 1)	// only Ocuppied => Dirty
			$query->where('occupancy', 'Occupied');

		if ($hskp_setting_value['NA_roster_clear'] == 1){
		$query->update(['room_status' => 'Dirty',
					'working_status' => CLEANING_NOT_ASSIGNED,
					'td_working_status' => CLEANING_NOT_ASSIGNED,
					'td_flag'=> 0]);
		}else{
			$query->update(['room_status' => 'Dirty',
					'working_status' => CLEANING_PENDING,
					'td_working_status' => CLEANING_PENDING,
					'td_flag'=> 0]);
		}

		//No Service based on Schedule
		$roomlist = DB::table('services_room_status')
					->where('property_id', $property_id)
					->get();

		foreach($roomlist as $row){


			$schedule = DB::table('services_hskp_schedule')
						->where('id', $row->schedule)
						->select(DB::raw('days'))
						->first();

			$date = Carbon::createFromFormat('Y-m-d', $cur_date)->format('l');

			if (!empty($schedule)){

				$schedule_days = explode(',', $schedule->days);
	
				if (!in_array($date, $schedule_days)){
	
					DB::table('services_room_status')
						->where('property_id', $property_id)
						->where('id', $row->id)
						->update([
						'working_status' => NO_SERVICE,
						'td_working_status' => NO_SERVICE
						]);

				}
			}	

			// set Due out and Arrival
			$guest = DB::table('common_guest')
                ->where('room_id', $row->id)
                ->where('departure', '>=', $cur_date)
                ->where('checkout_flag','checkin')
				->orderBy('departure', 'desc')
				->orderBy('arrival', 'desc')
				->first();

			if (!empty($guest) && $guest->departure == $cur_date)
				HskpRoomStatus::where('id', $row->id)->where('property_id', $property_id)->update(['due_out' => 1]);
			else
				HskpRoomStatus::where('id', $row->id)->where('property_id', $property_id)->update(['due_out' => 0]);

			if (!empty($guest) && $guest->pre_checkin == 1)
				HskpRoomStatus::where('id', $row->id)->where('property_id', $property_id)->update(['arrival' => 1]);
			else
				HskpRoomStatus::where('id', $row->id)->where('property_id', $property_id)->update(['arrival' => 0]);
			
		}

		//Full clean date

		$query = HskpRoomStatus::getRoomStatusQuery1();
		$data_query = HskpRoomStatus::getRoomStatusQuery2($query);

		$data_list = $data_query
                ->groupBy('cr.room')
				->orderBy('cr.room')
				->get();
		foreach($data_list as $row){

			$rule = DB::table('services_hskp_rules')
						->where('room_type_id', $row->room_type_id)
						->where('vip_id', $row->vip_id)
						->select(DB::raw('days'))
						->first();

			if (!empty($rule)){
					
					$days = $rule->days;
					$next_full = date('Y-m-d', strtotime("$days days", strtotime($row->full_clean_date)));

					if ($next_full == $cur_date){

						DB::table('services_room_status')
							->where('property_id', $property_id)
							->where('id', $row->id)
							->update([
								'full_clean_date' => $cur_date
							]);
					}
			}
		}

		// update credits
		RosterList::resetRoomCredits();			
	
        if( $hskp_setting_value['daily_auto_room_assign'] == 1 )
        	$this->createRoomAssignmentWithAuto_Proc($property_id);
	}

	public function setLinenHskpRoomList($property_id)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$room_status_list = DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_room_type as crt', 'cr.type_id', '=', 'crt.id')
			->where('rs.property_id', $property_id)
			->where('crt.linen_change', '>', 0)
			->where('rs.occupancy', 'Occupied')
			->whereRaw("(rs.linen_date IS NULL OR rs.linen_date = '' OR rs.linen_date < '$cur_date')")
			->select(DB::raw('rs.*, crt.linen_change'))
			->get();

		$room_id = 0;
		foreach($room_status_list as $row)
		{
			$room_id = $row->id;

			$linen_next_date = $cur_date;
			if( !empty($row->linen_date) && $row->linen_date != '1970-01-01' )				
			{
				$linen_next_date = date('Y-m-d', strtotime("$row->linen_date days", strtotime($row->linen_date)));
			}	

			DB::table('services_room_status')
				->where('id', $room_id)
				->update([
					'linen_date' => $linen_next_date
				]);

			// echo $linen_next_date;	
		}	

		Functions::sendHskpStatusChangeWithRoom($room_id);

		// echo json_encode($room_status_list);
	}

	public function nightAudit($property_id) {	
		$this->initHskpRoomListStatus($property_id);
		$this->resetHskpRoomListStatus($property_id);
		$this->setLinenHskpRoomList($property_id);
	}
	
	private function getTotalHskpCount($query) 
	{
		$count_query = clone $query;

		$total_count_select = '
						CAST(COALESCE(sum(hl.working_status = 0 ), 0) AS UNSIGNED) as pending,
						CAST(COALESCE(sum(hl.working_status = 100 ), 0) AS UNSIGNED) as unassigned,
						CAST(COALESCE(sum(hl.working_status = 1), 0) AS UNSIGNED) as progress,
						CAST(COALESCE(sum(hl.working_status = 2), 0) AS UNSIGNED) as complete,
						CAST(COALESCE(sum(hl.working_status = 3), 0) AS UNSIGNED) as dnd,
						CAST(COALESCE(sum(hl.working_status = 4), 0) AS UNSIGNED) as decline,
						CAST(COALESCE(sum(hl.working_status = 5), 0) AS UNSIGNED) as postpone,
						CAST(COALESCE(sum(hl.working_status = 6), 0) AS UNSIGNED) as inspected,
						CAST(COALESCE(sum(hl.working_status = 7), 0) AS UNSIGNED) as pause,
						count(*) as total						
					';

		$count = $count_query					    
				->select(DB::raw($total_count_select))
				->first();

		$ret = array();
				
		if( empty($count) )
		{
			$ret['pending'] = 0;
			$ret['unassigned'] = 0;
			$ret['progress'] = 0;
			$ret['complete'] = 0;
			$ret['dnd'] = 0;
			$ret['inspected'] = 0;
			$ret['decline'] = 0;
			$ret['pause'] = 0;
			$ret['postpone'] = 0;
			$ret['total'] = 0;
		}	
		else
		{
			$ret['pending'] = $count->pending;
			$ret['unassigned'] = $count->unassigned;
			$ret['progress'] = $count->progress;
			$ret['complete'] = $count->complete;
			$ret['dnd'] = $count->dnd;
			$ret['inspected'] = $count->inspected;
			$ret['decline'] = $count->decline;
			$ret['pause'] = $count->pause;
			$ret['postpone'] = $count->postpone;
			$ret['total'] = $count->total;
		}

		
		return $ret;
	}

	public function getStaffListForSameDepartment(Request $request)
	{
		$user_id = $request->get('user_id', 0);

		$user = CommonUser::find($user_id);

		$ret = [];
		if( empty($user) )
			return Response::json($ret);

		$userlist = DB::table('services_devices as sd')
			->leftJoin('common_users as cu', 'cu.device_id', '=', 'sd.device_id')
			->where('cu.deleted', 0)
			->where('cu.active_status', 1)
			->where('cu.dept_id', $user->dept_id)
			->select(DB::raw('cu.id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();

		return Response::json($userlist);	
	}

	public function getServiceStateList(Request $request) {
        $resultList = DB::table('services_room_status')
            ->select(DB::raw('service_state as id, service_state as label'))
            ->where('service_state', '!=', 'Available')
            ->groupBy('service_state')
            ->get();

        return Response::json($resultList);
    }

	public function getHskpAttendnatRosterList(Request $request)
	{
		$property_id = $request->get('property_id', 0);

		$list = DB::table('services_room_status as rs')
			->join('services_roster_list as rl', 'rs.attendant_id', '=', 'rl.id')
			->leftJoin('services_devices as sd', 'rl.device', '=', 'sd.id')
			->leftJoin('common_users as cu', 'sd.device_id', '=', 'cu.device_id')
			->leftJoin('common_users as cu1', 'rl.user_id', '=', 'cu1.id')
			->where('rs.property_id', $property_id)
			->select(DB::raw('rs.*, 
						CASE WHEN rs.user_id > 0 THEN CONCAT_WS(" ", cu1.first_name, cu1.last_name) ELSE sd.name END as roster_name'))
			->groupBy('rs.id')			
			->get();

		return Response::json($list);	
	}


	public function getHskpAttendantUserList(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$device_flag = $request->get('device_flag', 0);
		
		if( $device_flag == 1 )
		{			
			$dept_func = DB::table('services_dept_function')
				->where('hskp_role', 'Attendant')
				->select(DB::raw('*, id as dept_func_id'))
				->first();

			$dept_func_id = 0;
			if( !empty($dept_func))
				$dept_func_id = $dept_func->id;	
				
			$query = DB::table('services_devices as sd')
				->leftJoin('common_users as cu', 'cu.device_id', '=', 'sd.device_id')
				->whereRaw("FIND_IN_SET($dept_func_id, sd.dept_func_array_id)");
		}
		else
		{
			$job_role = DB::table('common_job_role')				
				->where('hskp_role', 'Attendant')
				->select(DB::raw('*'))
				->first();

			$job_role_id = 0;
			if( !empty($job_role))
				$job_role_id = $job_role->id;	
				
			$query = DB::table('common_users as cu')	
						->where('cu.job_role_id', $job_role_id);
		}

		$userlist = $query
			->where('cu.deleted', 0)
			// ->where('cu.active_status', 1)				
			->select(DB::raw('cu.id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();

		return Response::json($userlist);	
	}

	public function updateCleaningState(Request $request)
	{
		$cleaning_state = $request->get('cleaning_state', 0);

		$ret = array();

		switch( $cleaning_state )
		{
			case 'Cleaning':				
        		return $this->startRoomStatusChange($request);
				break;
			case 'Finished':
				return $this->finishRoom($request);
				break;	
			case 'Inspected':
				$request->attributes->add(['room_status' => 'Inspected']);
				return $this->changehskpstatus($request);
				break;	
		}

		$ret['code'] = 201;

		return Response::json($ret);
	}

	public function reassignRosterToToom(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 0);
		$room_id = $request->get('room_id', 0);
		$assigner_id = $request->get('assigner_id', 0);
		$device_flag = $request->get('device_flag', 0);

		$hskp_role = 'Attendant';

		$ret = array();
		$ret['code'] = 201;

		// remove old room assign information
		// find room status
		$room_status = HskpRoomStatus::find($room_id);
		if(empty($room_status) )
			return Response::json($ret);

		// find new staff name and id
		$query = DB::table('common_users as cu');
		if( $device_flag == 1 )
		{
			$query->join('services_devices as sd', 'cu.device_id', '=', 'sd.device_id')
				->join('services_roster_list as sr', 'sd.id', '=', 'sr.device');
		}
		else
		{
			$query->join('services_roster_list as sr', 'cu.id', '=', 'sr.user_id');
		}

		$new_staff = $query
				->where('cu.id', $assigner_id)				
				->select(DB::raw('sr.*, cu.first_name, cu.last_name'))
				->first();

		if( empty($new_staff) )
			$roster_name = 'Unassigned';				
		else
			$roster_name = $new_staff->first_name . ' ' . $new_staff->last_name;	
	
		// find old attendant
		$old_attendant = RosterList::find($room_status->attendant_id);

		$rosterlog = new RosterLog();
		$rosterlog->device = $room_status->device_ids;
		$rosterlog->roster_id = $room_status->attendant_id;
		$rosterlog->updated_by = $user_id;
		$rosterlog->casual_staff_name = 'New Staff Name';
		$rosterlog->generic_id = $assigner_id;
		$rosterlog->time = $cur_time;
		if( !empty($old_attendant) )
		{
			$rosterlog->begin_date_time = $old_attendant->begin_date_time;
			$rosterlog->end_date_time= $old_attendant->end_date_time;		
			$rosterlog->total_credits = RosterList::getTotalCredits($hskp_role, $old_attendant->id, 0, $property_id);
		}
		$rosterlog->supervisor_id = 0;
		$rosterlog->save();
		
		if( $room_status->working_status == 100 )
			$room_status->working_status = 0;

		if( empty($new_staff) )	// Unassigned
		{
			$room_status->attendant_id = 0;
			$room_status->working_status = 100;
		}	
		else
		{
			$room_status->attendant_id = $new_staff->id;

			$message = $roster_name.' has been updated with new rooms.';
			RosterList::sendRosterNotification($new_staff, $message);
		}
		
		$room_status->save();
		
		Functions::sendHskpStatusChangeToProperty($property_id);
						
		$ret['code'] = 200;
		$ret['message'] = 'Roster is changed successfully';
		$ret['content'] = HskpRoomStatus::getRoomStatus($room_id);		

		return Response::json($ret);		
	}


	public function updateServiceState(Request $request) {
		$room_id = $request->get('room_id', 0);
		$service_state = $request->get('service_state', 'Available');
		$comment = $request->get('comment', '');

		$ret = array();
		$ret['code'] = 201;

		// remove old room assign information
		// find room status
		$room_status = HskpRoomStatus::find($room_id);
		if(empty($room_status) )
			return Response::json($ret);
			
		$room_status->service_state = $service_state;		
		$room_status->comment = $comment;
		$room_status->save(); 
		
		$ret['code'] = 200;
		$ret['message'] = 'Service State is changed successfully';
		$ret['content'] = $room_status;

		return Response::json($ret);		
	}

	public function updateRoomSchedule(Request $request) {
		$cur_date = date("Y-m-d");
		$room_id = $request->get('room_id', 0);
		$schedule = $request->get('schedule', '');
		$comment = $request->get('comment', '');

		$ret = array();
		$ret['code'] = 201;

		// remove old room assign information
		// find room status
		$room_status = HskpRoomStatus::find($room_id);
		if(empty($room_status) )
			return Response::json($ret);

		if ($schedule  ==  0){
			$room_status->schedule = NULL;	
		} else{	
			$room_status->schedule = $schedule;		
		}
		$room_status->comment = $comment;
		$room_status->save(); 

		if ($schedule != 0){

		$scheduler = DB::table('services_hskp_schedule')
						->where('id', $schedule)
						->select(DB::raw('days'))
						->first();

		$date = Carbon::createFromFormat('Y-m-d', $cur_date)->format('l');

		if (!empty($scheduler)){

			$scheduler_days = explode(',', $scheduler->days);

			if (!in_array($date, $scheduler_days)){

			/*	DB::table('services_room_status')
				//	->where('property_id', $property_id)
					->where('id', $room_id)
					->update([
					'working_status' => NO_SERVICE,
					'td_working_status' => NO_SERVICE
					]);
*/
					$room_status = HskpRoomStatus::find($room_id);
					if(empty($room_status) )
						return Response::json($ret);
						
					$room_status->working_status = NO_SERVICE;		
					$room_status->td_working_status = NO_SERVICE;
					$room_status->save(); 

			} else {

				$room_status = HskpRoomStatus::find($room_id);
					if(empty($room_status) )
						return Response::json($ret);
						
					$room_status->working_status = CLEANING_PENDING;		
					$room_status->td_working_status = CLEANING_PENDING;
					$room_status->save(); 

			}
		}

		}else{
			$room_status = HskpRoomStatus::find($room_id);
					if(empty($room_status) )
						return Response::json($ret);
						
					$room_status->working_status = CLEANING_PENDING ;		
					$room_status->td_working_status = CLEANING_PENDING;
					$room_status->save();
		}
		
		$ret['code'] = 200;
		$ret['message'] = 'Room Schedule is changed successfully';
		$ret['content'] = $room_status;

		return Response::json($ret);		
	}

	public function updateRoomDiscrepancy(Request $request) {
		$room_id = $request->get('room_id', 0);
		$new_adult = $request->get('adult', 0);
		$new_chld = $request->get('chld', 0);
		$user = $request->get('user_id', '0');

		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$ret = array();
		$ret['code'] = 201;

		
		$room_status = HskpRoomStatus::find($room_id);
		if(empty($room_status) )
			return Response::json($ret);

		$guest = DB::table('common_guest as cg')
				->where('cg.room_id',$room_id)
				->where('cg.departure','>=', $cur_date)
				->where('cg.checkout_flag', '=','checkin')
				->first();


		if(empty($guest) )
			return Response::json($ret);
		

		DB::table('services_hskp_room_discrepancy')
			->insert([
				'room_id' => $room_id,
				'profile_id' => $guest->profile_id,
				'user_id' => $user,
				'adult' => $new_adult,
				'child' => $new_chld,
				'created_at' => $cur_time
			]);
			
		
		
		$ret['code'] = 200;
		$ret['message'] = 'Room Discrepancy is changed successfully';
		$ret['content'] = $room_status;

		return Response::json($ret);		
	}

	public function updateRushClean(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$user_id = $request->get('user_id', 0);
		$room_id = $request->get('room_id', 0);
		$rush_flag = $request->get('rush_flag', 1);
		$method = $request->get('method', 'Supervisor');
		
		$ret = array();
		$ret['code'] = 201;

		// find room status
		$room_status = HskpRoomStatus::find($room_id);
		if(empty($room_status) )
			return Response::json($ret);

		$room_status->rush_flag = $rush_flag;
		$room_status->save();

		if( $rush_flag == 1 )
		{			
			$notify_message = RosterList::sendRushCleanNotification($room_status);

			$hskp_status_name = "$room_status->occupancy $room_status->room_status";
			$hskp_info = DB::table('services_hskp_status as hs')					
					->where('hs.status', $hskp_status_name)					
					->select(DB::raw('hs.*'))
					->first();

			$hskp_log = new HskpStatusLog();
			$hskp_log->method = $method;
			$hskp_log->room_id = $room_id;
			$hskp_log->hskp_id = $hskp_info->id;
			$hskp_log->user_id = $user_id;
			
			$hskp_log->state = 101;	// rush flag
			$hskp_log->created_at = $cur_time;

			$hskp_log->save();

			$ret['notify_message'] = $notify_message;
		}

		$ret['code'] = 200;
		$ret['message'] = "Room is at the top.";
		
		return Response::json($ret);
	}

	public function getHskpDeptFuncList(Request $request)
	{
		$list = DB::table('services_dept_function')
			->whereIn('hskp_role', array('Attendant', 'Supervisor'))
			->select(DB::raw('*, id as dept_func_id'))
			->get();

		return Response::json($list);	
	}

	public function getHskpJobRoleList(Request $request)
	{
		$list = DB::table('common_job_role')
			->whereIn('hskp_role', array('Attendant', 'Supervisor'))
			->select(DB::raw('*'))
			->get();

		return Response::json($list);	
	}

	public function getHskpUserList(Request $request) {
		$property_id = $request->get('property_id', 0);
		$job_role_id = $request->get('job_role_id', 0);
		$name = $request->get('name', '');
        $active_flag = $request->get('active_flag', 0);
        
		$hskp_role = CommonJobRole::getHskpRole($job_role_id);

        $query = DB::table('common_users as cu')
				->leftJoin('services_roster_list as rs','cu.id', '=', 'rs.user_id');				

		if( $hskp_role == 'Attendant' )
			$query->leftJoin('services_room_status as b', 'rs.id', '=', 'b.attendant_id');
		
		if( $hskp_role == 'Supervisor' )
			$query->leftJoin('services_room_status as b', 'rs.id', '=', 'b.supervisor_id');	

		if( $active_flag == 1 )
			$query->where('cu.active_status', 1);	
		
		$user_list = $query->where('cu.job_role_id', $job_role_id)
				->whereRaw("cu.first_name like '%$name%'")
				->where('cu.deleted', 0)
				->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
							rs.supervisor_id, count(b.id) as room_count, rs.id as roster_id'))				
				->orderBy('wholename')			
				->groupBy('cu.id')
				->get();

		foreach($user_list as $row)
		{
			if(empty($row->roster_id))
			{
				$roster = new RosterList();

				$roster->user_id = $row->id;
				$roster->repeat_flag = 0;		
				$roster->name = $row->wholename;
				$roster->total_credits = 0;
				
				$roster->save();

				$row->roster_id = $roster->id;
			}

			$row->total_credits = RosterList::getTotalCredits($hskp_role, $row->roster_id, 0, $property_id);				
		}		

		$ret = array();
		$ret['user_list'] = $user_list;
        
		return Response::json($ret);
	}

	public function getRoomListForRoster(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$job_role_id = $request->get('job_role_id', 0);
		$hskp_user_id = $request->get('hskp_user_id', '');

		$ret = array();

		$tm1 = microtime(true);

		$roster = RosterList::where('user_id', $hskp_user_id)->first();
		
		if( empty($roster) )
		{		
			$roster = new RosterList();
			
			$roster->user_id = $hskp_user_id;
			$roster->repeat_flag = 0;			
			$roster->begin_date_time = '0000-00-00 00:00:00';
			$roster->end_date_time = '0000-00-00 00:00:00';			
			$roster->total_credits = 0;			
			
			$roster->save();
		}

		$tm2 = microtime(true);		

		
		
		$hskp_role = CommonJobRole::getHskpRole($job_role_id);
		$roster->locations = HskpRoomStatus::getRoomListForRoster($roster->id, $hskp_role, "All", "");	
		
		HskpRoomStatus::updateRoomCredits($roster->locations, $property_id);	
		
		$tm3 = microtime(true);
		$ret['roster'] = $roster;
		$ret['exe_time'] = ($tm2 - $tm1);		

    	$ret['code'] = 200;
		$ret['message'] = '';

		return Response::json($ret);
	}

	public function getChecklistGroupList(Request $request)
	{
		$checklist_id = $request->get('checklist_id', 0);

		$list = DB::table('services_hskp_checklist_group')
					->where('checklist_id', $checklist_id)
					->get();


		return Response::json($list);
	}

	private function getCheckListListProc($hskp_role, $room_type_id)
	{
		if ($hskp_role == 'All' && $room_type_id == 0){

			$list = DB::table('services_hskp_checklist_list as hcl')
					->join('common_room_type as rt', 'hcl.room_type_id', '=', 'rt.id')
				//	->where('hcl.hskp_role', $hskp_role)
				//	->where('hcl.room_type_id', $room_type_id)
					->select(DB::raw('hcl.*, rt.type as room_type'))
					->get();

		} else if($hskp_role == 'All' && $room_type_id != 0) {

			$list = DB::table('services_hskp_checklist_list as hcl')
					->join('common_room_type as rt', 'hcl.room_type_id', '=', 'rt.id')
				//	->where('hcl.hskp_role', $hskp_role)
					->where('hcl.room_type_id', $room_type_id)
					->select(DB::raw('hcl.*, rt.type as room_type'))
					->get();
		}elseif($hskp_role != 'All' && $room_type_id == 0){

			$list = DB::table('services_hskp_checklist_list as hcl')
					->join('common_room_type as rt', 'hcl.room_type_id', '=', 'rt.id')
					->where('hcl.hskp_role', $hskp_role)
				//	->where('hcl.room_type_id', $room_type_id)
					->select(DB::raw('hcl.*, rt.type as room_type'))
					->get();
		}
		else{
		$list = DB::table('services_hskp_checklist_list as hcl')
					->join('common_room_type as rt', 'hcl.room_type_id', '=', 'rt.id')
					->where('hcl.hskp_role', $hskp_role)
					->where('hcl.room_type_id', $room_type_id)
					->select(DB::raw('hcl.*, rt.type as room_type'))
					->get();
		}

		return $list;			
	}
	public function getCheckListList(Request $request)
	{
		$hskp_role = $request->get('hskp_role', 'Attendant');
		$room_type_id = $request->get('room_type_id', 0);

		$list = $this->getCheckListListProc($hskp_role, $room_type_id);

		return Response::json($list);
	}

	public function createCheckList(Request $request)
	{
		$name = $request->get('name', '');
		$room_type_id = $request->get('room_type_id', 0);
		$hskp_role = $request->get('hskp_role', 'Attendant');
		$active = $request->get('active', true);

		// check duplicated name
		$query = DB::table('services_hskp_checklist_list')
			->where('room_type_id', $room_type_id)
			->where('hskp_role', $hskp_role);
		
		$check_query = clone $query;	
		$exists = $check_query
			->where('name', $name)
			->exists();

		$ret = array();	
		if( $exists == true )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Duplicated Name';

			return Response::json($ret);
		}	

		$update_query = clone $query;	

		if($active == 'true')
		{
			$update_query->update([
				'active' => 'false'
			]);
		}

		DB::table('services_hskp_checklist_list')
			->insert([
				'name' => $name,
				'room_type_id' => $room_type_id,
				'hskp_role' => $hskp_role,
				'active' => $active,
			]);

		$ret['code'] = 200;

		$list = $this->getCheckListListProc($hskp_role, $room_type_id);
		$ret['list'] = $list;

		return Response::json($ret);
	}

	public function updateCheckList(Request $request)
	{
		$id = $request->get('id', 0);
		$name = $request->get('name', '');
		$active = $request->get('active', 'true');

		// find checklist
		$item = DB::table('services_hskp_checklist_list')
					->where('id', $id)
					->first();

		if( empty($item) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Checklist';

			return Response::json($ret);
		}			

		$room_type_id = $item->room_type_id;
		$hskp_role = $item->hskp_role;

		// check duplicated name
		$query = DB::table('services_hskp_checklist_list')
			->where('room_type_id', $room_type_id)
			->where('hskp_role', $hskp_role);
		
		$check_query = clone $query;	
		$exists = $check_query
			->where('id', '!=', $id)
			->where('name', $name)
			->exists();

		$ret = array();	
		if( $exists == true )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Duplicated Name';

			return Response::json($ret);
		}	

		$update_query = clone $query;	

		if($active == 'true')
		{
			$update_query->update([
				'active' => 'false'
			]);
		}

		DB::table('services_hskp_checklist_list')
			->where('id', $id)
			->update([
				'name' => $name,
				'active' => $active,
			]);

		$ret['code'] = 200;
		$ret['active'] = $active;

		$list = $this->getCheckListListProc($hskp_role, $room_type_id);
		$ret['list'] = $list;

		return Response::json($ret);
	}

	public function createChecklistGroup(Request $request)
	{
		$name = $request->get('name', '');
		$checklist_id = $request->get('checklist_id', 0);

		$ret = array();
		if( empty($name) )
		{	
			$ret['code'] = 201;
			return Response::json($ret);
		}

		$exists =  DB::table('services_hskp_checklist_group')
			->where('name', $name)
			->where('checklist_id', $checklist_id)
			->exists();

		if( $exists == true )
		{	
			$ret['code'] = 202;
			$ret['message'] = 'Already Exists';
			return Response::json($ret);
		}	

		DB::table('services_hskp_checklist_group')
				->insert([
							'name' => $name,
							'checklist_id' => $checklist_id,
						]);
				
		$ret['code'] = 200;
		$ret['list'] = DB::table('services_hskp_checklist_group')
							->where('checklist_id', $checklist_id)
							->get();

		return Response::json($ret);
	}

	public function getCheckList(Request $request)
	{
		$checklist_id = $request->get('checklist_id');
		
		$query = DB::table('services_hskp_checklist as hc')
					->join('services_hskp_checklist_group as hg', 'hc.group_id', '=', 'hg.id')
					->where('hc.checklist_id', $checklist_id);

		$temp_query = clone $query;		
		$group_list = $temp_query
						->groupBy('hc.group_id')
						->orderBy('hc.group_order')
						->select(DB::raw('hg.*'))
						->get();

		foreach($group_list as $row)
		{
			$temp_query = clone $query;		
			$row->items = $this->getChecklistItemList($checklist_id, $row->id);
		}

		return Response::json($group_list);
	}

	public function createChecklistWithGroup(Request $request)
	{
		$group_id = $request->get('group_id', 0);
		$checklist_id = $request->get('checklist_id', 0);		

		$exists = DB::table('services_hskp_checklist')
			->where('checklist_id', $checklist_id)			
			->where('group_id', $group_id)
			->exists();

		if( $exists == false )
		{
			$data = DB::table('services_hskp_checklist')
				->where('checklist_id', $checklist_id)				
				->select(DB::raw('max(group_order) as group_order'))
				->first();

			$group_order = 0;	
			if( !empty($data) )
				$group_order = $data->group_order + 1;

			DB::table('services_hskp_checklist')
				->insert([
					'checklist_id' => $checklist_id,					
					'group_id' => $group_id,
					'item_id' => 0,
					'group_order' => $group_order
				]);
		}

		return $this->getCheckList($request);
	}

	public function deleteChecklistWithGroup(Request $request)
	{
		$group_id = $request->get('group_id', 0);
		$checklist_id = $request->get('checklist_id', 0);
		
		DB::table('services_hskp_checklist')
			->where('checklist_id', $checklist_id)			
			->where('group_id', $group_id)
			->delete();

		return $this->getCheckList($request);
	}


	public function getChecklistItems(Request $request)
	{
		$list = DB::table('services_checklist_item')->get();
		return Response::json($list);
	}

	public function addChecklistItemToGroup(Request $request)
	{
		$group_id = $request->get('group_id', 0);
		$checklist_id = $request->get('checklist_id', 0);
		$item_id = $request->get('item_id', 0);
		$item_name = $request->get('item_name', 0);
		$weight = $request->get('weight', 1);

		if( $item_id == 0 )
		{
			$item = DB::table('services_checklist_item')
				->where('name', $item_name)
				->first();

			if( empty($item) )	// not exist item
			{
				// add item
				$item_id = DB::table('services_checklist_item')
					->insertGetId(
						[
							'name' => $item_name,
							'weight' => $weight
						]
					);
			}	
			else
				$item_id = $item->id;
		}
		
		DB::table('services_checklist_item')
			->where('id', $item_id)
			->update(
				[
					'weight' => $weight
				]
			);


		$exists = DB::table('services_hskp_checklist')
			->where('checklist_id', $checklist_id)			
			->where('group_id', $group_id)
			->where('item_id', $item_id)
			->exists();

		if( $exists == false )
		{
			$data = DB::table('services_hskp_checklist')
				->where('checklist_id', $checklist_id)
				->where('group_id', $group_id)
				->select(DB::raw('max(item_order) as item_order'))
				->first();

			$item_order = 0;	
			if( !empty($data) )
				$item_order = $data->item_order + 1;

			DB::table('services_hskp_checklist')
				->insert([
					'checklist_id' => $checklist_id,					
					'group_id' => $group_id,
					'item_id' => $item_id,
					'item_order' => $item_order,
				]);
		}

		$item_list = $this->getChecklistItemList($checklist_id, $group_id);
		$total_item_list = DB::table('services_checklist_item')->get();

		$ret = array();
		$ret['item_list'] = $item_list;
		$ret['total_item_list'] = $total_item_list;


		return Response::json($ret);
	}

	public function removeChecklistItemFromGroup(Request $request)
	{
		$group_id = $request->get('group_id', 0);
		$checklist_id = $request->get('checklist_id', 0);		
		$item_id = $request->get('item_id', 0);
	
		DB::table('services_hskp_checklist')
			->where('checklist_id', $checklist_id)
			->where('group_id', $group_id)
			->where('item_id', $item_id)
			->delete();

		$item_list = $this->getChecklistItemList($checklist_id, $group_id);
		
		$ret = array();
		$ret['item_list'] = $item_list;
		
		return Response::json($ret);
	}

	public function reorderChecklistItem(Request $request)
	{
		$checklist_id = $request->get('checklist_id', 0);
		$group_id = $request->get('group_id', 0);
		$id_list = $request->get('id_list', '');		
		
		$id_list = explode(",", $id_list);

		$item_order = 0;
		foreach($id_list as $item_id)
		{
			DB::table('services_hskp_checklist')
				->where('checklist_id', $checklist_id)
				->where('group_id', $group_id)
				->where('item_id', $item_id)
				->update(['item_order' => $item_order]);
				
			$item_order++;
		}
		
		$item_list = $this->getChecklistItemList($checklist_id, $group_id);
		
		$ret = array();
		$ret['item_list'] = $item_list;
		
		return Response::json($ret);
	}

	public function reorderChecklistGroup(Request $request)
	{
		$checklist_id = $request->get('checklist_id', 0);
		$group_id = $request->get('group_id', 0);
		$group_id_list = $request->get('group_id_list', '');		
		
		$group_id_list = explode(",", $group_id_list);

		$group_order = 0;
		foreach($group_id_list as $group_id)
		{
			DB::table('services_hskp_checklist')
				->where('checklist_id', $checklist_id)
				->where('group_id', $group_id)				
				->update(['group_order' => $group_order]);
				
			$group_order++;
		}
		
		$ret = array();
		
		return Response::json($ret);
	}
	
	
	private function getChecklistItemList($checklist_id, $group_id)
	{
		$item_list = DB::table('services_hskp_checklist as hc')					
					->join('services_checklist_item as hi', 'hc.item_id', '=', 'hi.id')
					->where('hc.checklist_id', $checklist_id)					
					->where('group_id', $group_id)					
					->select(DB::raw('hi.*'))
					->orderBy('hc.item_order')
					->get();

		return $item_list;			
	}

	private function getChecklistResultData($room_id, $hskp_role, $checklist)
	{
		$check_num = $this->getHskpCheckNum($room_id, $hskp_role);

		$query = DB::table('services_hskp_checklist_logs')
					->where('room_id', $room_id)
					->where('hskp_role', $hskp_role)				
					->where('check_num', $check_num);		

		$data = array();			

		$inspected = 1;			
		foreach($checklist as $row)
		{
			$item_id = $row->item_id;
			$group_id = $row->group_id;

			$check_query = clone $query;
			$log = $check_query->where('item_id', $item_id)
					->where('group_id', $group_id)
					->first();

			if( empty($log) )
			{
				$row->check_flag = 0;
				$row->result = 0;
				$row->comment = '';
				$row->path = '';
				$inspected = 0;			
			}
			else
			{
				$row->check_flag = 1;
				$row->result = $log->result;
				$row->comment = $log->comment;
				$row->path = $log->path;
			}
		}

		$data['inspected'] = $inspected;
		$data['list'] = $checklist;

		return $data;
	}

	public function getCheckListItemsForMobile(Request $request)
	{
		$room_id = $request->get('room_id', 0);
		$checklist_id = $request->get('checklist_id', 0);
		$hskp_role = $request->get('hskp_role', 'Attendant');
		$list_flag = $request->get('list_flag', 1);

		$checklist = DB::table('services_hskp_checklist as hc')
						->join('services_hskp_checklist_group as hg', 'hc.group_id', '=', 'hg.id')
						->join('services_checklist_item as ci', 'hc.item_id', '=', 'ci.id')
						->where('hc.checklist_id', $checklist_id)
						->orderBy('hc.group_order')
						->orderBy('hc.item_order')
						->select(DB::raw('hg.name as category_name, hc.item_id, hc.group_id, ci.name as item_name, hc.checklist_id'))
						->get();

		$data = $this->getChecklistResultData($room_id, $hskp_role, $checklist);

		if( $list_flag == 0 )
			$data['list'] = array();

		$ret['code'] = 200;
		$ret['content'] = $data;

		return Response::json($ret);
	}

	public function getCheckListDataForReport(Request $request)
	{
		$id = $request->get('id', 0);

		$hskp_log = DB::table('services_hskp_log as hl')
						->leftJoin('common_users as cu', 'hl.attendant_id', '=', 'cu.id')
						->leftJoin('common_users as cu1', 'hl.supervisor_id', '=', 'cu1.id')
						->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as attendant_name,
											CONCAT_WS(" ", cu1.first_name, cu1.last_name) as supervisor_name'))
						->first();

		$group_list = $this->getCheckListData($request);

		$ret = array();

		$ret['room'] = $request->get('room', '');
		$ret['attendant_name'] = $hskp_log->attendant_name;
		$ret['supervisor_name'] = $hskp_log->supervisor_name;
		$ret['report_date'] = date('d M Y');
		$ret['group_list'] = $group_list;

		$path = $_SERVER["DOCUMENT_ROOT"] . '/images/tick.png';		
		$data = file_get_contents($path);
		$base64 = 'data:image/png;base64,' . base64_encode($data);

		$ret['tick_icon_base64'] = $base64;

		$path = $_SERVER["DOCUMENT_ROOT"] . '/images/cancel.png';		
		$data = file_get_contents($path);
		$base64 = 'data:image/png;base64,' . base64_encode($data);
		$ret['cancel_icon_base64'] = $base64;

		return $ret;
	}

	private function getCheckListData(Request $request)
	{
		$room_id = $request->get('room_id', 0);
		$check_num = $request->get('check_num', 0);
		$hskp_role = $request->get('hskp_role', 'Attendant');

		$query = DB::table('services_hskp_checklist_logs as hcl')
					->join('services_hskp_checklist_group as hg', 'hcl.group_id', '=', 'hg.id')
					->where('hcl.room_id', $room_id)
					->where('hcl.check_num', $check_num)
					->where('hcl.hskp_role', $hskp_role);

		// get check list group	
		$temp_query = clone $query;		

		$group_list = $temp_query
						->groupBy('hcl.group_id')
						->select(DB::raw('hg.*'))
						->get();

		foreach($group_list as $row)
		{		
			$temp_query = clone $query;
			
			$row->items = $temp_query
					->join('services_checklist_item as ci', 'hcl.item_id', '=', 'ci.id')
					->where('group_id', $row->id)					
					->select(DB::raw('hcl.*, ci.name as item_name'))
					->get();					
		}

		return $group_list;
	}

	public function getCheckListResult(Request $request)
	{
		$group_list = $this->getCheckListData($request);

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $group_list;

		return Response::json($ret);
	}

	private function getHskpCheckNum($room_id, $hskp_role)
	{
		// get room status
		$room_status = HskpRoomStatus::find($room_id);

		if( empty($room_status) )
			return 0;
		
		if( $hskp_role == 'Attendant')	
		{
			$check_num = $room_status->attendant_check_num + 1;
		}
		if( $hskp_role == 'Supervisor')	
		{
			$check_num = $room_status->supervisor_check_num + 1;
		}

		return $check_num;
	}

	public function postChecklistItems(Request $request)
	{
		$room_id = $request->get('room_id', 0);
		$hskp_role = $request->get('hskp_role', 'Attendant');
		$checklist_str = $request->get('checklist', '');

		// Log::info($checklist_str);
		$checklist = json_decode($checklist_str);
		
		$check_num = $this->getHskpCheckNum($room_id, $hskp_role);

		$query = DB::table('services_hskp_checklist_logs')
					->where('room_id', $room_id)
					->where('hskp_role', $hskp_role)				
					->where('check_num', $check_num);		

		foreach($checklist as $row)
		{
			$item_id = $row->item_id;
			$group_id = $row->group_id;
			$result = $row->result;
			$comment = $row->comment;
			$path = $row->path;

			// Log::info($path);

			$check_query = clone $query;
			$exists = $check_query->where('item_id', $item_id)
					->where('group_id', $group_id)
					->exists();

			if( $exists == false )
			{
				DB::table('services_hskp_checklist_logs')
					->insert([
						[
							'room_id' => $room_id,
							'hskp_role' => $hskp_role,
							'check_num' => $check_num,
							'item_id' => $item_id,
							'group_id' => $group_id,
							'result' => $result,
							'comment' => $comment,
							'path' => $path
						]
					]);				
			}		
			else
			{
				$update_query = clone $query;
				$update_query->where('item_id', $item_id)
						->where('group_id', $group_id)
						->update([
							'result' => $result,
							'comment' => $comment,
							'path' => $path
						]);		
			}
		}

		$request->attributes->add(['list_flag' => 0]); // no need list
		return $this->getCheckListItemsForMobile($request);
	}

	public function submitChecklistItems(Request $request)
	{
		$room_id = $request->get('room_id', 0);
		$hskp_role = $request->get('hskp_role', 'Attendant');
		$room_status = $request->get('room_status', "");
		
		if( $room_status == 'Inspected' || $room_status == 'Partial' )
		{
			$room_status = 'Inspected';
			$this->changehskpstatus($request);
		}
		else if( $room_status == 'Clean' )
		{
			$this->changehskpstatus($request);
		}
		else
		{
			// Log::info($room_status);
			$this->declineRoomSupervisor($request);
		}


		$ret = array();

		$check_num = $this->getHskpCheckNum($room_id, $hskp_role);
		if( $check_num < 1 )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Room';
			
			return Response::json($ret);
		}

		// get room status
		$room_status = HskpRoomStatus::find($room_id);
		
		if( $hskp_role == 'Attendant')	
			$room_status->attendant_check_num = $check_num;			
	
		if( $hskp_role == 'Supervisor')	
			$room_status->supervisor_check_num = $check_num;
	
		$room_status->save();

		$ret['code'] = 200;
		$ret['content'] = $room_status;

		return Response::json($ret);
	}

	public function getRoomHistory(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$last_week = date("Y-m-d", strtotime("-7 days"));

		$room_id = $request->get('room_id', 0);

		$ret = array();

		// get guest log
		$checkinout_log = DB::table('common_guest_log as cgl')
			->leftJoin('common_room as cr', 'cgl.room_id', '=', 'cr.id')
			->where('cgl.room_id', $room_id)
			->where('cgl.created_at', '>=', $last_week)
			->select(DB::raw("cgl.id, cgl.guest_id, cgl.arrival, cgl.departure,
						cgl.action as type, 
						CASE WHEN cgl.action = 'checkin' THEN CONCAT(cgl.guest_name, ' checked in at ', cgl.arrival)
							WHEN cgl.action = 'checkout' THEN CONCAT(cgl.guest_name, ' checked out at ', cgl.departure)
							WHEN cgl.action = 'roomchange' THEN CONCAT(cgl.guest_name, ' is moved to ', cr.room) 
						END as content, 
						cgl.created_at"))
			->get();

		foreach($checkinout_log as $row)
		{
			if($row->type == 'checkout')	// checkout
			{
				// find roomchange
				$room_change = DB::table('common_guest_log as cgl')
					->leftJoin('common_room as cr', 'cgl.room_id', '=', 'cr.id')
					->where('cgl.guest_id', $row->guest_id)
					->where('cgl.arrival', $row->arrival)
					->where('cgl.departure', $row->departure)
					->where('cgl.id', '>', $row->id)
					->select(DB::raw("cgl.*, cr.room"))
					->first();

				if( !empty($room_change))
				{
					$row->type = 'Room Move';
					$row->content = "$room_change->guest_name is moved to $room_change->room";
				}	
			}

			if($row->type == 'roomchange')	// checkout
			{
				// find roomchange
				$room_change = DB::table('common_guest_log as cgl')
					->leftJoin('common_room as cr', 'cgl.room_id', '=', 'cr.id')
					->where('cgl.guest_id', $row->guest_id)
					->where('cgl.arrival', $row->arrival)
					->where('cgl.departure', $row->departure)
					->where('cgl.id', '<', $row->id)
					->orderBy('cgl.id', 'desc')
					->select(DB::raw("cgl.*, cr.room"))
					->first();

				if( !empty($room_change))
				{
					$row->type = 'Room Move';
					$row->content = "$room_change->guest_name is moved from $room_change->room";
				}	
			}
		}	

		// get cleaning history
		$cleaning_log = DB::table('services_hskp_log as hl')
			->leftJoin('common_users as cu', 'hl.user_id', '=', 'cu.id')
			->leftJoin('services_hskp_status as hs', 'hl.hskp_id', '=', 'hs.id')
			->where('hl.created_at', '>=', $last_week)
			->where('hl.room_id', $room_id)
			->select(DB::raw('hs.status as type, CONCAT(cu.first_name, " ", cu.last_name, " - ", hl.method) as content, hl.created_at, hl.method, hl.state'))
			->get();
		
		foreach($cleaning_log as $row)
		{
			if( $row->state == 101 )
			{
				$row->type = 'Rush Clean';				
			}
		}	

		// $list = array_unique($tag_list, SORT_REGULAR);
		$list = array_merge($checkinout_log->toArray(), $cleaning_log->toArray());

		
		usort($list, function($a, $b) {
			return $a->created_at < $b->created_at;
		});

		$ret['code'] = 200;
		$ret['list'] = $list;

		return Response::json($ret);
	}

	public function createScheduleList(Request $request)
	{
		$name = $request->get('name', '');
		$code = $request->get('code', '');
		$days = $request->get('days', '[]');
		$user_id = $request->get('user_id', '1');
		

		// check duplicated name
		$query = DB::table('services_hskp_schedule')
			->where('code', $code);
			
		
		$check_query = clone $query;	
		$exists = $check_query
			->where('name', $name)
			->exists();

		$ret = array();	
		if( $exists == true )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Duplicated Name';

			return Response::json($ret);
		}	

		

		DB::table('services_hskp_schedule')
			->insert([
				'name' => $name,
				'code' => $code,
				'days' => $days,
				'created_by' => $user_id,
			]);

		$ret['code'] = 200;

		$list = DB::table('services_hskp_schedule')->get();
		$ret['list'] = $list;

		return Response::json($ret);
	}

	public function getScheduleList(Request $request)
	{
	//	$hskp_role = $request->get('hskp_role', 'Attendant');
	//	$room_type_id = $request->get('room_type_id', 0);

		$list = DB::table('services_hskp_schedule as shs')
					->leftjoin('common_users as cu', 'shs.created_by', '=', 'cu.id')
					->select(DB::raw('shs.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->get();

		return Response::json($list);
	}

	public function deleteSchedule(Request $request)
	{
		$id = $request->get('id', 0);
	
		DB::table('services_hskp_schedule')
			->where('id', $id)			
			->delete();

			return $this->index($request);
	}

	public function updateSchedule(Request $request)
	{
		$id = $request->get('id', 0);
		$name = $request->get('name', '');
		$code = $request->get('code', '');
		$days = $request->get('days', '[]');

		// find checklist
		$item = DB::table('services_hskp_schedule')
					->where('id', $id)
					->first();

		if( empty($item) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Schedule';

			return Response::json($ret);
		}			

		// check duplicated name
		$query = DB::table('services_hskp_schedule')
				->where('code', $code);
		
		$check_query = clone $query;	
		$exists = $check_query
			->where('id', '!=', $id)
			->where('name', $name)
			->exists();

		$ret = array();	
		if( $exists == true )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Duplicated Name';

			return Response::json($ret);
		}	

		$update_query = clone $query;	

		DB::table('services_hskp_schedule')
			->where('id', $id)
			->update([
				'name' => $name,
				'code' => $code,
				'days' => $days,
			]);

		

		$ret['code'] = 200;
		$list = DB::table('services_hskp_schedule')->get();
		$ret['list'] = $list;

		return Response::json($ret);
	}


	public function createRuleList(Request $request)
	{
		$days = $request->get('days', '1');
		$room_type_id = $request->get('room_type_id', 0);
		$vip_id = $request->get('vip_id', '');
		$user_id = $request->get('user_id', '1');
		

		// check duplicated name
		$query = DB::table('services_hskp_rules')
			->where('room_type_id', $room_type_id);
		//	->where('vip_id', $vip_id);
			
		
		$check_query = clone $query;	
		$exists = $check_query
			->where('vip_id', $vip_id)
			->where('days', $days)
			->exists();

		$ret = array();	
		if( $exists == true )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Duplicated Entry';

			return Response::json($ret);
		}	

		

		DB::table('services_hskp_rules')
			->insert([
				'days' => $days,
				'room_type_id' => $room_type_id,
				'vip_id' => $vip_id,
				'created_by' => $user_id,
			]);
		// apply the rule to previously checkin rooms with vip and room type
		$this->applyRuleforCheckinRooms($vip_id, $room_type_id, $days);

		$ret['code'] = 200;

		$list = DB::table('services_hskp_rules')
				->where('vip_id', $vip_id)
				->where('room_type_id', $room_type_id)->get();
		$ret['list'] = $list;

		return Response::json($ret);
	}

	private function applyRuleforCheckinRooms($vip_id, $room_type_id, $days) {

		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		
		$roomlist = DB::table('common_guest as cg')
					->leftJoin('common_room as cr', 'cg.room_id', '=', 'cr.id')
					->leftJoin('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
					->leftJoin('common_vip_codes as cvc', 'cg.vip', '=', 'cvc.vip_code')
					->where('cvc.id', $vip_id)
					->where('rt.id', $room_type_id)
					->where('cg.checkout_flag', 'checkin')
					->where('cg.departure', '>=', $cur_date)
					->select(DB::raw('cg.arrival, cg.room_id'))
					->get();

		foreach($roomlist as $row){

			$room = HskpRoomStatus::find($row->room_id);

			$next_full = date('Y-m-d', strtotime("$days days", strtotime($row->arrival)));

			$diff_in_days = (int)((strtotime($cur_date) - strtotime($row->arrival))/86400);

			while($next_full < $cur_date) {
				
				$next_full = date('Y-m-d', strtotime("$days days", strtotime($next_full)));
			  }
			
			  DB::table('services_room_status')
					
					->where('id', $row->room_id)
					->update([
								'full_clean_date' => $next_full
							]);

		}


	}

	public function getRuleList(Request $request)
	{
		$vip_id = $request->get('vip_id', 0);
		$room_type_id = $request->get('room_type_id', 0);

		$list = DB::table('services_hskp_rules as shr')
					->leftjoin('common_users as cu', 'shr.created_by', '=', 'cu.id')
					->leftjoin('common_room_type as crt', 'shr.room_type_id', '=', 'crt.id')
					->leftjoin('common_vip_codes as cvc', 'shr.vip_id', '=', 'cvc.id')
					->where('shr.vip_id', $vip_id)
					->where('shr.room_type_id', $room_type_id)
					->select(DB::raw('shr.*, cvc.name as vip_name, crt.type as room_type,  CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->get();

		return Response::json($list);
	}

	public function deleteRule(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$id = $request->get('id', 0);
		$vip_id = $request->get('vip_id', 0);
		$room_type_id = $request->get('room_type_id', 0);


		// find checklist
		$item = DB::table('services_hskp_rules')
					->where('id', $id)
					->first();

		$roomlist = DB::table('common_guest as cg')
					->leftJoin('common_room as cr', 'cg.room_id', '=', 'cr.id')
					->leftJoin('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
					->leftJoin('common_vip_codes as cvc', 'cg.vip', '=', 'cvc.vip_code')
					->where('cvc.id', $vip_id)
					->where('rt.id', $room_type_id)
					->where('cg.checkout_flag', 'checkin')
					->where('cg.departure', '>=', $cur_date)
					->select(DB::raw('cg.arrival, cg.room_id'))
					->get();

		foreach($roomlist as $row)
		{

			HskpRoomStatus::where('id', $row->room_id)->update(
                ['full_clean_date' => null]
            );

		}

		
		DB::table('services_hskp_rules')
			->where('id', $id)			
			->delete();

		return $this->index($request);
	}

	public function updateRule(Request $request)
	{
		$id = $request->get('id', 0);
		$room_type_id = $request->get('room_type_id', '');
		$vip_id = $request->get('vip_id', '');
		$days = $request->get('days', 0);

		// find checklist
		$item = DB::table('services_hskp_rules')
					->where('id', $id)
					->first();

		if( empty($item) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Rule';

			return Response::json($ret);
		}			

		// check duplicated name
		$query = DB::table('services_hskp_rules')
			->where('room_type_id', $room_type_id);
		//	->where('vip_id', $vip_id);
		
		$check_query = clone $query;	
		$exists = $check_query
			->where('id', '!=', $id)
			->where('vip_id', $vip_id)
			->where('days', $days)
			->exists();

		$ret = array();	
		if( $exists == true )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Duplicated Entry';

			return Response::json($ret);
		}	

		$update_query = clone $query;	

		DB::table('services_hskp_rules')
			->where('id', $id)
			->update([
				'room_type_id' => $room_type_id,
				'vip_id' => $vip_id,
				'days' => $days,
			]);

		// update the rule to previously checkin rooms with vip and room type
		$this->applyRuleforCheckinRooms($vip_id, $room_type_id, $days);
		

		$ret['code'] = 200;
		$list = DB::table('services_hskp_rules')
				->where('vip_id', $vip_id)
				->where('room_type_id', $room_type_id)->get();
		$ret['list'] = $list;

		return Response::json($ret);
	}
	public function publicAreaQRCodeGenerator(Request $request)
    {
		$liveServerURLQuery = DB::table('property_setting')
			->where('settings_key','live_host')
			->select('value')
			->first();

		$id = $request->get('id', 0);

		$task = DB::table('services_hskp_public_area_task as pat')
			->leftJoin('services_location as sl','sl.id','=','pat.location_id')
			->where('pat.id', $id)
			->select('pat.id','sl.name')
			->first();

		$taskName = str_replace(" ","_",$task->name);

		$taskIdData = json_encode(array(
			'id' => $task->id,
			'name' => $taskName
		));

		$liveServerURL = $liveServerURLQuery->value . "generateQRCode?id=" . $taskIdData ;
		$response_URI = file_get_contents($liveServerURL);

		return Response($response_URI);
		//return response()->download($response);
	}
	public function publicAreaGetTasks(Request $request){
		$publicAreaTaskQuery = DB::table('services_hskp_public_area_task')->get();
		return Response::json($publicAreaTaskQuery);
	}
	public function publicAreaGetTasksByMainId(Request $request){
		$id = $request->get('id', 0);
		if($id != 0){
			$publicAreaTaskQuery = DB::table('services_hskp_public_area_task as pat')
			->leftJoin('services_location as sl','sl.id','=','pat.location_id')
			->where('pat.task_main_id', $id)
			->select('pat.id','sl.name','pat.time_out','pat.status','pat.location_id','pat.task_main_id')
			->get();
			return Response::json($publicAreaTaskQuery);
		}else{
			$ret['code'] = '500';
			$ret['message'] = 'Invalid Main Task Id';
			return Response::json($ret);
		}
		
	}
	public function publicAreaGetLocationsWithIds(Request $request){
		$location_id = $request->get('location_id', '');
		$location_id = explode(",",$location_id);
		$locations = DB::table('services_location')
		->whereIn('id',$location_id)
		->get();

		return Response::json($locations);
	}

	public function publicAreaGetTasksMain(Request $request){
		$publicAreaTaskQuery = DB::table('services_hskp_public_area_task_main')->get();
		return Response::json($publicAreaTaskQuery);
	}
	public function publicAreaAddTaskMain(Request $request){

		$name = $request->get('name', '');

		$update_at = new DateTime();	
		$update_at = $update_at->format("Y-m-d H:i:s");

		DB::insert('insert into services_hskp_public_area_task_main (name, updated_at) values (?, ?)', [$name, $update_at]);

		$ret['code'] = 200;
		return Response::json($ret);
	}

	public function publicAreaEditTaskMain(Request $request){

		$id = $request->get('id', 0);
		$name = $request->get('name', '');

		$update_at = new DateTime();	
		$update_at = $update_at->format("Y-m-d H:i:s");

		if($id != 0){
			DB::table('services_hskp_public_area_task_main')
				->where('id', $id)
				->update(['name' => $name, 'updated_at' => $update_at]);
		}
		
		$ret['code'] = 200;
		return Response::json($ret);
	}
	public function publicAreaAddTask(Request $request){

		$main_task_id = $request->get('main_task_id','0');
		// $start_time = $request->get('start_time', '');
		// $end_time = $request->get('end_time', '');
		$time_out = $request->get('time_out', 0);
		$location_id = $request->get('location_id', '');

		$update_at = new DateTime();	
		$update_at = $update_at->format("Y-m-d H:i:s");

		// $start_time_dt =  new DateTime($start_time);
		// $start_time = $start_time_dt->format("H:i:s");
		// $end_time_dt =  new DateTime($end_time);
		// $end_time = $end_time_dt->format("H:i:s");

		DB::insert('insert into services_hskp_public_area_task (task_main_id, location_id, time_out, updated_at) values (?, ?, ?, ?)', [$main_task_id, $location_id, $time_out, $update_at]);

		$ret['code'] = 200;
		return Response::json($ret);
	}
	public function publicAreaEditTask(Request $request){

		$id = $request->get('id', 0);
		//$name = $request->get('name', '');
		// $start_time = $request->get('start_time', '');
		// $end_time = $request->get('end_time', '');
		$time_out = $request->get('time_out', 0);
		$location_id = $request->get('location_id', '');

		$update_at = new DateTime();	
		$update_at = $update_at->format("Y-m-d H:i:s");

		// $start_time_dt =  new DateTime($start_time);
		// $start_time = $start_time_dt->format("H:i:s");
		// $end_time_dt =  new DateTime($end_time);
		// $end_time = $end_time_dt->format("H:i:s");

		if($id != 0){
			DB::table('services_hskp_public_area_task')
				->where('id', $id)
				->update(['location_id' => $location_id, 'time_out' => $time_out]);
		}
		
		$ret['code'] = 200;
		return Response::json($ret);
	}
	public function publicAreaEditTaskActive(Request $request){
		$id = $request->get('id', 0);
		$status = $request->get('status', 0);

		if($status == true){
			$status = 1;
		}

		if($id != 0){
			DB::table('services_hskp_public_area_task')->where('id',$id)->update(array(
				'status'=>$status
			));
		}
		
		$ret['code'] = 200;
		return Response::json($ret);
	}
	public function publicAreaQRCodeAddLog(Request $request){
		$task_obj = $request->get('task_id', 0);
		$task_obj_json = json_decode($task_obj, true);
		$user_id = $request->get('user_id', 0);
		
		$task_id = $task_obj_json['id'];

		if($task_id == 0){
			$ret['code'] = 500;
			$ret['message'] = $task_obj[0];
		}
		else if($user_id == 0){
			$ret['code'] = 500;
			$ret['message'] = 'Invalid User';
		}
		else{

			$currentDate = date("Y-m-d");
			$update_at = new DateTime();	
			$update_at = $update_at->format("Y-m-d H:i:s");
			//return Response::json($currentDate);
			// $addedCheckQuery = DB::table('services_hskp_public_area_task_log')
			// 	->whereRaw("DATE(created_at) = ?", [$currentDate])
			// 	->where('task_id', $task_id)
			// 	->first();
			
			// if($addedCheckQuery){
			// 	$ret['code'] = 200;
			// 	$ret['message'] = 'Task was completed, Overwriting previous Task Entry';

			// 	DB::table('services_hskp_public_area_task_log')
			// 		->whereRaw("DATE(created_at) = ?", [$currentDate])
			// 		->where('task_id', $task_id)
			// 		->delete();

			// 	DB::insert('insert into services_hskp_public_area_task_log (task_id, user_id) values (?, ?)', [$task_id, $user_id]);
			// }
			// else{
				// $ret['code'] = 200;
				// $ret['message'] = 'Task Log Added';

				// DB::insert('insert into services_hskp_public_area_task_log (task_id, user_id) values (?, ?)', [$task_id, $user_id]);
			//}


			$addedCheckQuery = DB::table('services_hskp_public_area_task_log')
				//->whereRaw("DATE(created_at) = ?", [$currentDate])
				->where('task_id', $task_id)
				->where('user_id', $user_id)
				->whereNULL('end_time')
				->first();

			if($addedCheckQuery){
				DB::table('services_hskp_public_area_task_log')
				->where('task_id', $task_id)
				->where('user_id', $user_id)
				->whereNULL('end_time')
				->update(['end_time' => $update_at]);

				$ret['message'] = 'Task Log Updated';
			}else{
				DB::insert('insert into services_hskp_public_area_task_log (task_id, user_id, start_time) values (?, ?, ?)', [$task_id, $user_id, $update_at]);

				$ret['message'] = 'Task Log Added';
			}

			$ret['code'] = 200;
			// $ret['message'] = 'Task Log Added';

			//DB::insert('insert into services_hskp_public_area_task_log (task_id, user_id) values (?, ?)', [$task_id, $user_id]);
		}
		
		return Response::json($ret);
	}

	private function publicAreaReport($data){

		$model = DB::table('common_property')->first();

		$logo_path = '';
		if (!empty($model)) {
			$logo_path = $model->logo_path;
		}
		$logo_path_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";

		$datetime = new DateTime();
		$datetimeStr = $datetime->format('Y-m-d H:i:s');
		$datetimeYMD = $datetime->format('Y-m-d');

		$dtForWeek = new DateTime();
		$oneWeekAgoInterval = new DateInterval('P1W');
		$oneWeekAgo = $dtForWeek->sub($oneWeekAgoInterval);
		$oneWeekAgoFormat = $oneWeekAgo->format('Y-m-d H:i:s');

		$dtForMonth = new DateTime();
		$oneMonthAgoInterval = new DateInterval('P1M');
		$oneMonthAgo = $dtForMonth->sub($oneMonthAgoInterval);
		$oneMonthAgoFormat = $oneMonthAgo->format('Y-m-d H:i:s');


		//Template
		$returnData = "<div style='text-align:center;'>";

		$returnData .= "<div style='width:100%;'><div style='text-align:left;'><img style='width:150px' src='" .$logo_path_url . $logo_path. "' /></div></div>";
		$returnData .= "<div style=' text-align:right;'>";
		$returnData .= "<p>Report On: <b>". $datetimeStr ."</b></p>";
		if($data['reportDuration'] == "Day"){
			$returnData .= "<p>Duration: <b>". $datetimeStr ." - ". $datetimeStr ."</b></p>";
		}else if($data['reportDuration'] == "Week"){
			$returnData .= "<p>Duration: <b>". $oneWeekAgoFormat ." - ". $datetimeStr ."</b></p>";
		}else if($data['reportDuration'] == "Month"){
			$returnData .= "<p>Duration: <b>". $oneMonthAgoFormat ." - ". $datetimeStr ."</b></p>";
		}else{
			
		}
		
		$returnData .= "<p>Property: <b>". $model->name ."</b></p>";
		$returnData .= "</div>";

		$returnData .= "<table style='width:100%' >";

		if($data['reportBy'] == "User"){

			$returnData .= "<tr><td colspan='4' style='text-align:center;'>Report By ".$data['reportBy']."</td></tr>";

			if($data['reportDuration'] == "Day"){
				$users = DB::table('common_users as cu')
				->join('services_hskp_public_area_task_log as tl','cu.id','=','tl.user_id')
				->where('tl.created_at', 'like', '%' . $datetimeYMD . '%')
				->select('cu.id', 'cu.first_name', 'cu.last_name')
				->groupBy('cu.id')
				->get();
			}
			else if($data['reportDuration'] == "Week"){
				$users = DB::table('common_users as cu')
				->join('services_hskp_public_area_task_log as tl','cu.id','=','tl.user_id')
				->select('cu.id', 'cu.first_name', 'cu.last_name')
				->whereBetween('tl.created_at', [$oneWeekAgoFormat, $datetimeStr])
				->groupBy('cu.id')
				->get();
			}
			else if($data['reportDuration'] == "Month"){
				$users = DB::table('common_users as cu')
				->join('services_hskp_public_area_task_log as tl','cu.id','=','tl.user_id')
				->select('cu.id', 'cu.first_name', 'cu.last_name')
				->whereBetween('tl.created_at', [$oneMonthAgoFormat, $datetimeStr])
				->groupBy('cu.id')
				->get();
			}
			else{
				$users = DB::table('common_users as cu')
				->join('services_hskp_public_area_task_log as tl','cu.id','=','tl.user_id')
				->select('cu.id', 'cu.first_name', 'cu.last_name')
				->groupBy('cu.id')
				->get();
			}
			
			if($users){
				foreach($users as $user){

					if($data['reportDuration'] == "Day"){
						$taskList = DB::table('services_hskp_public_area_task as t')
						->leftJoin('services_location as sl', 't.location_id', '=', 'sl.id')
						->leftJoin('services_hskp_public_area_task_log as tl','t.id','=','tl.task_id')
						->selectRaw('t.id, sl.name, tl.start_time, tl.end_time,tl.created_at as created_time')
						->where('tl.user_id','=', $user->id)
						->where('t.status','=', '1')
						->where('tl.created_at', 'like', '%' . $datetimeYMD . '%')
						->orderBy('tl.created_at')
						->get();

					}
					else if($data['reportDuration'] == "Week"){
						$taskList = DB::table('services_hskp_public_area_task as t')
						->leftJoin('services_location as sl', 't.location_id', '=', 'sl.id')
						->leftJoin('services_hskp_public_area_task_log as tl','t.id','=','tl.task_id')
						->selectRaw('t.id, sl.name, tl.start_time, tl.end_time,tl.created_at as created_time')
						->where('tl.user_id','=', $user->id)
						->where('t.status','=', '1')
						->whereBetween('tl.created_at', [$oneWeekAgoFormat, $datetimeStr])
						->orderBy('tl.created_at')
						->get();

					}
					else if($data['reportDuration'] == "Month"){
						$taskList = DB::table('services_hskp_public_area_task as t')
						->leftJoin('services_location as sl', 't.location_id', '=', 'sl.id')
						->leftJoin('services_hskp_public_area_task_log as tl','t.id','=','tl.task_id')
						->selectRaw('t.id, sl.name, tl.start_time, tl.end_time,tl.created_at as created_time')
						->where('tl.user_id','=', $user->id)
						->where('t.status','=', '1')
						->whereBetween('tl.created_at', [$oneMonthAgoFormat, $datetimeStr])
						->orderBy('tl.created_at')
						->get();

					}
					else{
						$taskList = DB::table('services_hskp_public_area_task as t')
						->leftJoin('services_location as sl', 't.location_id', '=', 'sl.id')
						->leftJoin('services_hskp_public_area_task_log as tl','t.id','=','tl.task_id')
						->selectRaw('t.id, sl.name, tl.start_time, tl.end_time,tl.created_at as created_time')
						->where('tl.user_id','=', $user->id)
						->where('t.status','=', '1')
						->orderBy('tl.created_at')
						->get();
					}

					$returnData .= "<tr><td style='text-align:left;'>User: <b>".$user->first_name . " " . $user->last_name ."</b></td></tr>";
					
					if($taskList){
						$returnData .="<tr>";
						$returnData .= "<td style='background-color:#2e3344; color:#fff;text-align:left;'>Location</td>
						<td style='background-color:#2e3344; color:#fff;text-align:left;'>Start Time</td>
						<td style='background-color:#2e3344; color:#fff;text-align:left;'>End Time</td>";
						$returnData .="</tr>";
						
						foreach($taskList as $task){
							$returnData .="<tr>";
							$returnData .= "<td style='text-align:left;'>". $task->name ."</td>";
							$returnData .= "<td style='text-align:left;'>". $task->start_time ."</td>";
							$returnData .= "<td style='text-align:left;'>". $task->end_time ."</td>";
							$returnData .="</tr>";
						}
						$returnData .="<tr>";
						$returnData .= "<td >&nbsp;</td>";
						$returnData .="</tr>";
					}
				}
			}
		}

		if($data['reportBy'] == "Location"){

			$returnData .= "<tr><td colspan='4' style='text-align:center;'>Report By ".$data['reportBy']."</td></tr>";

			//GET LOCATIONS

			$locationData = DB::table('services_hskp_public_area_task as t')
			->join('services_hskp_public_area_task_log as tl','t.id','=','tl.task_id')
			// ->where('t.created_at', 'like', '%' . $datetimeYMD . '%')
			->whereNotNull('t.location_id')
			->select('t.location_id')
			->get();
			
			$locationIds = [];
			foreach( $locationData as $key => $value )
			{
				$locationIds[] = $value->location_id;
			}

			$locations = DB::table('services_location')
			->whereIn('id',$locationIds)
			->get();

			foreach( $locations as $location )
			{
				$returnData .= "<tr><td style='text-align:left;'>Location: <b>".$location->name ."</b></td></tr>";

				if($data['reportDuration'] == "Day"){
					$task_logs = DB::table('services_hskp_public_area_task_log as tl')
					->join('services_hskp_public_area_task as t','t.id','=','tl.task_id')
					->join('common_users as cu','cu.id','=','tl.user_id')
					->where('tl.created_at', 'like', '%' . $datetimeYMD . '%')
					->whereRaw("find_in_set('".$location->id."',t.location_id)")
					->whereNotNull('t.location_id')
					->select('cu.first_name','tl.start_time','tl.end_time','tl.created_at')
					->get();

					if($task_logs){
						$returnData .="<tr>";
							$returnData .= "<td style='background-color:#2e3344; color:#fff;text-align:left;'>User Name</td>
							<td style='background-color:#2e3344; color:#fff;text-align:left;'>Start Time</td>
							<td style='background-color:#2e3344; color:#fff;text-align:left;'>End Time</td>";
							$returnData .="</tr>";
							
						foreach($task_logs as $tl){
							$returnData .="<tr>";
							$returnData .= "<td style='text-align:left;'>". $tl->first_name ."</td>";
							$returnData .= "<td style='text-align:left;'>". $tl->start_time ."</td>";
							$returnData .= "<td style='text-align:left;'>". $tl->end_time ."</td>";
							$returnData .="</tr>";
						}
						$returnData .="<tr>";
						$returnData .= "<td >&nbsp;</td>";
						$returnData .="</tr>";
					}

				}else if($data['reportDuration'] == "Week"){
					$task_logs = DB::table('services_hskp_public_area_task_log as tl')
					->join('services_hskp_public_area_task as t','t.id','=','tl.task_id')
					->join('common_users as cu','cu.id','=','tl.user_id')
					->whereBetween('tl.created_at', [$oneWeekAgoFormat, $datetimeStr])
					->whereRaw("find_in_set('".$location->id."',t.location_id)")
					->whereNotNull('t.location_id')
					->select('cu.first_name','tl.start_time','tl.end_time','tl.created_at')
					->get();

					if($task_logs){
						$returnData .="<tr>";
							$returnData .= "<td style='background-color:#2e3344; color:#fff;text-align:left;'>User Name</td>
							<td style='background-color:#2e3344; color:#fff;text-align:left;'>Start Time</td>
							<td style='background-color:#2e3344; color:#fff;text-align:left;'>End Time</td>";
							$returnData .="</tr>";
							
						foreach($task_logs as $tl){
							$returnData .="<tr>";
							$returnData .= "<td style='text-align:left;'>". $tl->first_name ."</td>";
							$returnData .= "<td style='text-align:left;'>". $tl->start_time ."</td>";
							$returnData .= "<td style='text-align:left;'>". $tl->end_time ."</td>";
							$returnData .="</tr>";
						}
						$returnData .="<tr>";
						$returnData .= "<td >&nbsp;</td>";
						$returnData .="</tr>";
					}
					
				}else if($data['reportDuration'] == "Month"){
					$task_logs = DB::table('services_hskp_public_area_task_log as tl')
					->join('services_hskp_public_area_task as t','t.id','=','tl.task_id')
					->join('common_users as cu','cu.id','=','tl.user_id')
					->whereBetween('tl.created_at', [$oneMonthAgoFormat, $datetimeStr])
					->whereRaw("find_in_set('".$location->id."',t.location_id)")
					->whereNotNull('t.location_id')
					->select('cu.first_name','tl.start_time','tl.end_time','tl.created_at')
					->get();

					if($task_logs){
						$returnData .="<tr>";
							$returnData .= "<td style='background-color:#2e3344; color:#fff;text-align:left;'>User Name</td>
							<td style='background-color:#2e3344; color:#fff;text-align:left;'>Start Time</td>
							<td style='background-color:#2e3344; color:#fff;text-align:left;'>End Time</td>";
							$returnData .="</tr>";
							
						foreach($task_logs as $tl){
							$returnData .="<tr>";
							$returnData .= "<td style='text-align:left;'>". $tl->first_name ."</td>";
							$returnData .= "<td style='text-align:left;'>". $tl->start_time ."</td>";
							$returnData .= "<td style='text-align:left;'>". $tl->end_time ."</td>";
							$returnData .="</tr>";
						}
						$returnData .="<tr>";
						$returnData .= "<td >&nbsp;</td>";
						$returnData .="</tr>";
					}
					
				}else {
					$task_logs = DB::table('services_hskp_public_area_task_log as tl')
					->join('services_hskp_public_area_task as t','t.id','=','tl.task_id')
					->join('common_users as cu','cu.id','=','tl.user_id')
					->where('t.created_at', 'like', '%' . $datetimeYMD . '%')
					->whereRaw("find_in_set('".$location->id."',t.location_id)")
					->whereNotNull('t.location_id')
					->select('cu.first_name','tl.start_time','tl.end_time','tl.created_at')
					->get();

					if($task_logs){
						$returnData .="<tr>";
							$returnData .= "<td style='background-color:#2e3344; color:#fff;text-align:left;'>User Name</td>
							<td style='background-color:#2e3344; color:#fff;text-align:left;'>Start Time</td>
							<td style='background-color:#2e3344; color:#fff;text-align:left;'>End Time</td>";
							$returnData .="</tr>";
							
						foreach($task_logs as $tl){
							$returnData .="<tr>";
							$returnData .= "<td style='text-align:left;'>". $tl->first_name ."</td>";
							$returnData .= "<td style='text-align:left;'>". $tl->start_time ."</td>";
							$returnData .= "<td style='text-align:left;'>". $tl->end_time ."</td>";
							$returnData .="</tr>";
						}
						$returnData .="<tr>";
						$returnData .= "<td >&nbsp;</td>";
						$returnData .="</tr>";
					}
				}
			}
		}
		if($data['reportBy'] == "Date"){
			$returnData .= "<tr><td colspan='4' style='text-align:center;'>Report By ".$data['reportBy']."</td></tr>";

			if($data['reportDuration'] == "Day"){
				$task_logs = DB::table('services_hskp_public_area_task_log as tl')
				->join('services_hskp_public_area_task as t','t.id','=','tl.task_id')
				->join('common_users as cu','cu.id','=','tl.user_id')
				->where('tl.created_at', 'like', '%' . $datetimeYMD . '%')
				->whereNotNull('t.location_id')
				->orderBy('tl.created_at', 'DESC')
				->select('cu.first_name','tl.start_time','tl.end_time','tl.created_at')
				->get();

				if($task_logs){
					$returnData .="<tr>";
						$returnData .= "<td style='background-color:#2e3344; color:#fff;text-align:left;'>User Name</td>
						<td style='background-color:#2e3344; color:#fff;text-align:left;'>Start Time</td>
						<td style='background-color:#2e3344; color:#fff;text-align:left;'>End Time</td>";
						$returnData .="</tr>";
						
					foreach($task_logs as $tl){
						$dt = new DateTime($tl->created_at);
						$dateEndCheck = $dt->format('Y-m-d');
						
						if(!isset($dtCheck)){
							$dtCheck = $dateEndCheck;
						}
						if($dtCheck != $dateEndCheck){
							$dtCheck = $dateEndCheck;

							$returnData .="<tr>";
							$returnData .= "<td >&nbsp;</td>";
							$returnData .="</tr>";
						}

						$returnData .="<tr>";
						$returnData .= "<td style='text-align:left;'>". $tl->first_name ."</td>";
						$returnData .= "<td style='text-align:left;'>". $tl->start_time ."</td>";
						$returnData .= "<td style='text-align:left;'>". $tl->end_time ."</td>";
						$returnData .="</tr>";
					}
					$returnData .="<tr>";
					$returnData .= "<td >&nbsp;</td>";
					$returnData .="</tr>";
				}
				
			}

			if($data['reportDuration'] == "Week"){
				$task_logs = DB::table('services_hskp_public_area_task_log as tl')
				->join('services_hskp_public_area_task as t','t.id','=','tl.task_id')
				->join('common_users as cu','cu.id','=','tl.user_id')
				->whereBetween('tl.created_at', [$oneWeekAgoFormat, $datetimeStr])
				->whereNotNull('t.location_id')
				->orderBy('tl.created_at', 'DESC')
				->select('cu.first_name','tl.start_time','tl.end_time','tl.created_at')
				->get();

				if($task_logs){
					$returnData .="<tr>";
						$returnData .= "<td style='background-color:#2e3344; color:#fff;text-align:left;'>User Name</td>
						<td style='background-color:#2e3344; color:#fff;text-align:left;'>Start Time</td>
						<td style='background-color:#2e3344; color:#fff;text-align:left;'>End Time</td>";
						$returnData .="</tr>";
						
					foreach($task_logs as $tl){
						$dt = new DateTime($tl->created_at);
						$dateEndCheck = $dt->format('Y-m-d');
						
						if(!isset($dtCheck)){
							$dtCheck = $dateEndCheck;
						}
						if($dtCheck != $dateEndCheck){
							$dtCheck = $dateEndCheck;

							$returnData .="<tr>";
							$returnData .= "<td >&nbsp;</td>";
							$returnData .="</tr>";
						}

						$returnData .="<tr>";
						$returnData .= "<td style='text-align:left;'>". $tl->first_name ."</td>";
						$returnData .= "<td style='text-align:left;'>". $tl->start_time ."</td>";
						$returnData .= "<td style='text-align:left;'>". $tl->end_time ."</td>";
						$returnData .="</tr>";
					}
					$returnData .="<tr>";
					$returnData .= "<td >&nbsp;</td>";
					$returnData .="</tr>";
				}
			}

			if($data['reportDuration'] == "Month"){
				$task_logs = DB::table('services_hskp_public_area_task_log as tl')
				->join('services_hskp_public_area_task as t','t.id','=','tl.task_id')
				->join('common_users as cu','cu.id','=','tl.user_id')
				->whereBetween('tl.created_at', [$oneMonthAgoFormat, $datetimeStr])
				->whereNotNull('t.location_id')
				->orderBy('tl.created_at', 'DESC')
				->select('cu.first_name','tl.start_time','tl.end_time','tl.created_at')
				->get();

				if($task_logs){
					$returnData .="<tr>";
						$returnData .= "<td style='background-color:#2e3344; color:#fff;text-align:left;'>User Name</td>
						<td style='background-color:#2e3344; color:#fff;text-align:left;'>Start Time</td>
						<td style='background-color:#2e3344; color:#fff;text-align:left;'>End Time</td>";
						$returnData .="</tr>";
						
					foreach($task_logs as $tl){
						$dt = new DateTime($tl->created_at);
						$dateEndCheck = $dt->format('Y-m-d');
						
						if(!isset($dtCheck)){
							$dtCheck = $dateEndCheck;
						}
						if($dtCheck != $dateEndCheck){
							$dtCheck = $dateEndCheck;

							$returnData .="<tr>";
							$returnData .= "<td >&nbsp;</td>";
							$returnData .="</tr>";
						}

						$returnData .="<tr>";
						$returnData .= "<td style='text-align:left;'>". $tl->first_name ."</td>";
						$returnData .= "<td style='text-align:left;'>". $tl->start_time ."</td>";
						$returnData .= "<td style='text-align:left;'>". $tl->end_time ."</td>";
						$returnData .="</tr>";
					}
					$returnData .="<tr>";
					$returnData .= "<td >&nbsp;</td>";
					$returnData .="</tr>";
				}
				
			}

		}


		$returnData .= "</table>";
		$returnData .= "</div>";

		$mpdf = new \Mpdf\Mpdf();
		$mpdf->WriteHTML($returnData);
		$mpdf->Output( 'public_area_report.pdf');

		//$returnData = "<h1>".$data['test']."</h1>";
		return $returnData;		
	} 

	public function generatePublicAreaReport(Request $request){

		$reportBy = $request->get('reportBy', "");
		$reportDuration = $request->get('reportDuration', "");

		$report_data['reportBy'] = $reportBy;
		$report_data['reportDuration'] = $reportDuration;

		

		//return view('frontend.report.public_area_report', compact('report_data'));
		$returnData = $this->publicAreaReport($report_data);
		return $returnData;
	}



	public function downloadPublicAreaReport(){

		$report_data['test'] = 'testing';

		// $mpdf = new \Mpdf\Mpdf();
		// $mpdf->WriteHTML('<h1>Hello world!</h1>');
		// $mpdf->Output( 'public_area_report.pdf');




		$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
		return $actual_link . '/public_area_report.pdf';

	}

	public function createLinenSetting(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$qty = $request->get('qty', '1');
		$room_type_id = $request->get('room_type_id', 0);
		$linen_type = $request->get('linen_type', 0);
		$vip_id = $request->get('vip_id', '');
		$user_id = $request->get('user_id', '1');
		

		// check duplicated name
		$query = DB::table('services_linen_setting')
			->where('room_type_id', $room_type_id);
		//	->where('vip_id', $vip_id);
			
		
		$check_query = clone $query;	
		$exists = $check_query
			->where('vip_id', $vip_id)
			->where('linen_type', $linen_type)
			->where('qty', $qty)
			->exists();

		$ret = array();	
		if( $exists == true )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Duplicated Entry';

			return Response::json($ret);
		}	

		

		DB::table('services_linen_setting')
			->insert([
				'qty' => $qty,
				'room_type_id' => $room_type_id,
				'vip_id' => $vip_id,
				'linen_type' => $linen_type,
				'created_at' => $cur_time,
			]);

		$ret['code'] = 200;

		$list = DB::table('services_linen_setting')
				->where('vip_id', $vip_id)
				->where('room_type_id', $room_type_id)
				->where('linen_type', $linen_type)
				->get();
		$ret['list'] = $list;

		return Response::json($ret);
	}

	public function getLinenSettingList(Request $request)
	{
		$vip_id = $request->get('vip_id', 0);
		$room_type_id = $request->get('room_type_id', 0);
		$linen_type = $request->get('linen_type', 0);

		$list = DB::table('services_linen_setting as shr')
					->leftjoin('common_room_type as crt', 'shr.room_type_id', '=', 'crt.id')
					->leftjoin('common_vip_codes as cvc', 'shr.vip_id', '=', 'cvc.id')
					->leftjoin('services_linen_type as slt', 'shr.linen_type', '=', 'slt.id')
					->where('shr.vip_id', $vip_id)
					->where('shr.room_type_id', $room_type_id)
				//	->where('shr.linen_type', $linen_type)
					->select(DB::raw('shr.*, cvc.name as vip_name, crt.type as room_type, slt.type as linentype'))
					->get();

		return Response::json($list);
	}

	public function deleteLinenSetting(Request $request)
	{
		$id = $request->get('id', 0);
		
		DB::table('services_linen_setting')
			->where('id', $id)			
			->delete();

			return $this->index($request);
	}

	public function updateLinenSetting(Request $request)
	{
		$id = $request->get('id', 0);
		$room_type_id = $request->get('room_type_id', '');
		$vip_id = $request->get('vip_id', '');
		$linen_type = $request->get('linen_type', '');
		$qty = $request->get('qty', 0);

		// find checklist
		$item = DB::table('services_linen_setting')
					->where('id', $id)
					->first();

		if( empty($item) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Setting';

			return Response::json($ret);
		}			

		// check duplicated name
		$query = DB::table('services_linen_setting')
			->where('room_type_id', $room_type_id);
		//	->where('vip_id', $vip_id);
		
		$check_query = clone $query;	
		$exists = $check_query
			->where('id', '!=', $id)
			->where('vip_id', $vip_id)
			->where('linen_type', $linen_type)
			->where('qty', $qty)
			->exists();

		$ret = array();	
		if( $exists == true )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Duplicated Entry';

			return Response::json($ret);
		}	

		$update_query = clone $query;	

		DB::table('services_linen_setting')
			->where('id', $id)
			->update([
				'room_type_id' => $room_type_id,
				'vip_id' => $vip_id,
				'linen_type' => $linen_type,
				'qty' => $qty,
			]);

		

		$ret['code'] = 200;
		$list = DB::table('services_linen_setting')->get();
		$ret['list'] = $list;

		return Response::json($ret);
	}

	public function getlinentotal(Request $request)
	{
		$user_id = $request->get('user_id', 562);
		

		$count = DB::table('services_linen_total as lt')
		->leftJoin('services_linen_type as slt', 'lt.linen_type', '=', 'slt.id')
						->where('lt.user_id', $user_id)
						->select(DB::raw('lt.user_id, slt.type, lt.count'))
						->get();

		return Response::json($count);
	}

	public function getlinentotalloc(Request $request)
	{
		
		$loc_id = $request->get('room_id', '0');
		

		$query = DB::table('services_linen_status as lt')
				->leftJoin('services_linen_type as slt', 'lt.type_id', '=', 'slt.id')
						->where('lt.room_id', $loc_id);

		$temp_query = clone $query;

		$soiled = $temp_query->select(DB::raw('slt.id as type_id, slt.type, lt.room_id, lt.soiled, lt.damaged, lt.missing, lt.rewash'))->get();

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $soiled;
		return Response::json($ret);
	}

	public function updatelinentotalloc(Request $request)
	{
		$user_id = $request->get('user_id', 562);
		$loc_id = $request->get('room_id', '0');
		$type_id = $request->get('type_id', '0');
		$soiled = $request->get('soiled', '0');
		$damaged = $request->get('damaged', '0');
		$missing = $request->get('missing', '0');
		$rewash = $request->get('rewash', '0');

		$cur_time = date("Y-m-d H:i:s");
		

		DB::table('services_linen_status')
					->where('room_id', $loc_id)
					->where('type_id', $type_id)
					->update(['soiled' => $soiled,
								'damaged' => $damaged,
								'missing' => $missing,
								'rewash' => $rewash,
								'updated_at' => $cur_time
							]);
		DB::table('services_linen_status_log')
					
					->insert(['room_id' => $loc_id,
								'type_id' => $type_id,
								'soiled' => $soiled,
								'damaged' => $damaged,
								'missing' => $missing,
								'rewash' => $rewash,
								'created_at' => $cur_time
							]);
		
		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = "The Linen list has been updated successfully";

		return Response::json($ret);
	}
	public function publicAreaCheckTimeOut(Request $request){

		$dt = new DateTime();	
		$update_at = $dt->format("Y-m-d H:i:s");

		$addedCheckQuery = DB::table('services_hskp_public_area_task_log as tl')
			->leftJoin('services_hskp_public_area_task as t','t.id','=','tl.task_id')
			->whereNULL('tl.end_time')
			->select('tl.id','t.time_out','tl.start_time','tl.end_time')
			->get();

		foreach ($addedCheckQuery as $key => $value) 
		{
			if($value->time_out != null){
				
				$compareDt = DateTime::createFromFormat('H:i:s', $value->start_time);
				$compareDt->add(new DateInterval('PT' . $value->time_out . 'M'));
				$compareDate = $compareDt->format("Y-m-d H:i:s");

				if($update_at > $compareDate){
					DB::table('services_hskp_public_area_task_log')
						->where('id',$value->id)
						->whereNULL('end_time')
						->update(['end_time' => $compareDate]);
				}
				
			}

		}
		//return "end time updated";


	}

	public function getPublicAreaUserLog(Request $request){

		$user_id = $request->get('user_id', 0);

		$datetime = new DateTime();
		$datetimeYMD = $datetime->format('Y-m-d');

		if($user_id != 0){
			$taskList = DB::table('services_hskp_public_area_task as t')
			->leftJoin('services_location as sl', 't.location_id', '=', 'sl.id')
			->leftJoin('services_hskp_public_area_task_log as tl','t.id','=','tl.task_id')
			->selectRaw('t.id as location_id, sl.name as location_name, tl.start_time, tl.end_time')
			->where('tl.user_id','=', $user_id)
			->where('t.status','=', '1')
			->where('tl.created_at', 'like', '%' . $datetimeYMD . '%')
			->orderBy('tl.created_at')
			->get();
		}else{
			$taskList = [];
		}
		

		return $taskList;
	}

	public function updateRoomLuggage(Request $request) {
		$room_id = $request->get('room_id', 0);
		$user = $request->get('user_id', '0');

		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$ret = array();
		$ret['code'] = 201;

		
		$room_status = HskpRoomStatus::find($room_id);
		if(empty($room_status) ){
			$ret['message'] = 'Cannot find Room';
			return Response::json($ret);
		}

		$guest = DB::table('common_guest as cg')
				->where('cg.room_id',$room_id)
				->where('cg.departure','>=', $cur_date)
				->where('cg.checkout_flag', '=','checkin')
				->first();


		if(empty($guest) )
		{
			$ret['message'] = 'Room not Checked In';
			return Response::json($ret);
		}
		

		DB::table('services_hskp_room_luggage')
			->insert([
				'room_id' => $room_id,
				'profile_id' => $guest->profile_id,
				'user_id' => $user,
				'created_at' => $cur_time
			]);
			
		
		
		$ret['code'] = 200;
		$ret['message'] = 'No Luggage is posted successfully';
		$ret['content'] = $room_status;

		return Response::json($ret);		
	}

	public function getRmStateList(Request $request) {
		
		$property_id = $request->get('property_id', '0');

        $resultList = DB::table('services_room_status')
            ->select(DB::raw('room_status as label'))
			->where('property_id', $property_id)
            ->groupBy('room_status')
			->orderBy('room_status','asc')
            ->get();

        return Response::json($resultList);
    }

	public function getFOStateList(Request $request) {
		$property_id = $request->get('property_id', 0);
        $resultList = DB::table('services_room_status')
            ->select(DB::raw('fo_state as label'))
			->where('property_id', $property_id)
            ->groupBy('fo_state')
			->orderBy('fo_state','asc')
            ->get();

        return Response::json($resultList);
    }
}