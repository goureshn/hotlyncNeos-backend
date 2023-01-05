<?php

namespace App\Http\Controllers\Frontend;

use App;
use App\Http\Controllers\Controller;
use App\Models\Call\StaffExternal;
use App\Models\Common\CommonStatusPerUser;
use App\Models\Common\CommonUser;
use App\Models\Common\CommonStatusPerProperty;
use App\Models\Common\Property;
use App\Models\Common\PropertySetting;
use App\Models\Common\ReportHistory;
use App\Models\Common\CronLogs;
use App\Models\Common\ScheduleReportSetting;
use App\Models\Service\CompensationTemplate;
use App\Models\Service\ComplaintSublist;
use App\Models\Eng\EngRepairStaff;
use App\Models\Eng\WorkOrder;
use App\Models\Eng\EngRepairRequest;

use App\Modules\Functions;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Response;

// use Charts;

define("RINGING", 'Ringing');
define("ABANDONED", 'Abandoned');
define("ANSWERED", 'Answered');
define("CALLBACK", 'Callback');
define("FOLLOWUP", 'Modify');
define("HOLD", 'Hold');
define("MISSED", 'Missed');

define("F_INTERACTION", 'Guest Interaction');
define("F_COURTESY", 'Courtesy Calls');
define("F_INSPECTION", 'Room Inspection');
define("F_ATTENTION", 'In-House Special Attention');
define("F_ESCORTED", 'Escorted to Room');

define("C_PENDINGG", 'Pending');
define("C_RESOLVEDD", 'Resolved');
define("C_REJECTEDD", 'Rejected');
define("C_INPROGG", 'In-Progress');
define("C_REOPENN", 'Re-Opened');
define("C_CLOSEDD", 'Closed');
define("C_AWAITT", 'Awaiting Approval');
define("C_UNRESOLVEDD", 'Unresolved');
define("C_AWAITT2", 'Awaiting Approval 2');
define("C_AWAITT3", 'Awaiting Approval 3');

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

class ReportController extends Controller
{
	public function getFilterList(Request $request) {
		$filter_name = $request->get('filter_name', 'room');
		$filter_department = json_decode($request->get('filter_department', '[]'));
		$filter_department_function = json_decode($request->get('filter_department_function', '[]'));
		$filter_building = json_decode($request->get('filter_building', '[]'));
		$filter = $request->get('filter', '');
		$property_id = $request->get('property_id', '0');
		$property_names = json_decode($request->get('property_names', '[]'));
		$property_tags = json_decode($request->get('property_tags', '[]'));
		$department_tags = json_decode($request->get('department_tags', '[]'));
		$category_tags = json_decode($request->get('category_tags', '[]'));

		$filter = '%' . $filter . '%';

		$ret = [];

		switch($filter_name) {
			case 'Property';
				$datalist = DB::table('common_property as cp')
					->where('cp.name', 'like', $filter)
					->get();
					if((!empty($request->report_by))&&(($request->report_by=='Complaint')||($request->report_by=='Sub-complaint')||($request->report_by=='Compensation')))
					{
						for($i = 0; $i < count($datalist); $i++)
							$ret[] = $datalist[$i]->name . ":" . $datalist[$i]->id;
					}
					else
					{
						for($i = 0; $i < count($datalist); $i++)
							$ret[] = $datalist[$i]->name;
					}
				break;
			case 'Building';
				$datalist = DB::table('common_building as cb')
					->where('cb.name', 'like', $filter)
					->get();
					if((!empty($request->report_by))&&(($request->report_by=='Complaint')||($request->report_by=='Sub-complaint')||($request->report_by=='Compensation')))
					{
						for($i = 0; $i < count($datalist); $i++)
							$ret[] = $datalist[$i]->name;
					}
					else
					{
						for($i = 0; $i < count($datalist); $i++)
							$ret[] = $datalist[$i]->name;
					}
				break;

			case 'Floor';
				$datalist = DB::table('common_floor as cf')
					->leftJoin('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
					->where('cf.floor', 'like', $filter)
					->where('cb.property_id', $property_id);

					if(count($filter_building)>0 ) {
						$datalist->whereIn('cb.name', $filter_building);
					}


				$datalist = $datalist->select(DB::raw('cf.*, cb.name'))->get();


						for($i = 0; $i < count($datalist); $i++)
							$ret[] = $datalist[$i]->description;

				break;
			case 'PropertyID';
				$ret = DB::table('common_property as cp')
					->where('cp.name', 'like', $filter)
					->get();
				break;
			case 'Type';
				$datalist = DB::table('services_complaint_feedback_type')
					->groupBy('name')
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->name;
				break;

			case 'Source';
				$datalist = DB::table('services_complaint_feedback_source')
					->groupBy('name')
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->name;
				break;

			case 'MainCategory';
				$query = DB::table('services_complaint_maincategory as sm ')
					->leftJoin('common_property as cp', 'sm.property_id', '=', 'cp.id')
					->where('sm.name', 'like', $filter);
				if( count($property_tags) > 0 )
					$query->whereIn('cp.name', $property_tags);

				$datalist = $query->select(DB::raw('sm.name as category_name'))
					->groupBy('sm.name')
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->category_name;
				break;
			case 'MainCategoryID';
				$query = DB::table('services_complaint_maincategory as sm ')
					->where('sm.name', 'like', $filter);
				if( count($property_tags) > 0 )
					$query->whereIn('sm.property_id', $property_tags);

				$ret = $query
					->groupBy('sm.name')
					->get();

				break;
			case 'Category';
				$query = DB::table('services_complaint_category as sc')
					->where('sc.name', 'like', $filter);

				if( count($department_tags) > 0 )
					$query->join('common_department as cd', 'sc.dept_id', '=', 'cd.id')
						->where('cd.department', $department_tags);

				$datalist = $query->get();
				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->name;

				break;
			case 'CategoryID';
				$query = DB::table('services_complaint_category as sc')
					->where('sc.name', 'like', $filter);

				if( count($department_tags) > 0 )
					$query->whereIn('sc.dept_id', $department_tags);

				$ret = $query->get();

				break;
			case 'SubCategory';
				$query = DB::table('services_complaint_subcategory as csc')
					->where('csc.name', 'like', $filter);

				if( count($category_tags) > 0 )
				{
					$query->join('services_complaint_category as sc', 'csc.category_id', '=', 'sc.id')
						->whereIn('sc.name', $category_tags);
				}

				$datalist = $query->select(DB::raw('csc.name'))->get();
				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->name;
				break;

			case 'SubCategoryID';
				$query = DB::table('services_complaint_subcategory as csc')
					->where('csc.name', 'like', $filter);

				if( count($category_tags) > 0 )
				{
					$query->whereIn('csc.category_id', $category_tags);
				}

				$ret = $query->select(DB::raw('csc.name'))->get();

				break;

			case 'Serverity';
				$datalist = DB::table('services_complaint_type as st')
					->where('st.type', 'like', $filter)
					->get();
				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->type;
				break;
			case 'ServerityID';
				$ret = DB::table('services_complaint_type as st')
					->where('st.type', 'like', $filter)
					->get();
				break;
			case 'Room';
				$datalist = DB::table('common_room as cr')
					->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
					->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
					->where('cr.room', 'like', $filter)
					->where('cb.property_id', $property_id)
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->room;
				break;
			case 'Rooms';
				$datalist =  DB::table('common_guest as cg')
								->leftJoin('common_room as cr', 'cg.room_id', '=', 'cr.id')
								->where('cg.checkout_flag', 'checkin')
								->where('cr.room', 'like', $filter)
								->orderBy('cr.id', 'asc')
								->select('cr.room')
								->distinct()
									->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->room;
				break;
			case 'RoomID';
				$ret = DB::table('common_room as cr')
					->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
					->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
					->where('cr.room', 'like', $filter)
					->where('cb.property_id', $property_id)
					->get();
				break;
			case 'Department';
				$datalist = DB::table('common_department as cd')
				        ->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')
						->where('cd.department', 'like', $filter)
						->where('cd.property_id', $property_id)
						->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->department;
				break;
			case 'Departments';
				$query = DB::table('common_department as cd')
					->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')
					->where('cd.department', 'like', $filter);

				if( count($property_tags) > 0 )
					$query->whereIn('cp.name', $property_tags);

				$datalist =	$query->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->department;
				break;
			case 'DepartmentsID';
				$query = DB::table('common_department as cd')
					->where('cd.department', 'like', $filter);

				if( count($property_tags) > 0 )
					$query->whereIn('cd.property_id', $property_tags);

				$ret =	$query->get();

				break;
			case 'Section';
				$datalist = DB::table('call_section as cs')
						->join('common_department as cd', 'cs.dept_id', '=', 'cd.id');
							if(count($filter_department)>0 ) {
								$datalist->whereIn('cd.department', $filter_department);
							}
							$datalist = $datalist->where('cs.section', 'like', $filter)
								->where('cd.property_id', $property_id)
								->get();
				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->section;
				break;
			case 'Extension';
				$datalist = DB::table('call_guest_extn as ge')
						->join('common_building as cb', 'ge.bldg_id', '=', 'cb.id')
						->where('ge.extension', 'like', $filter)
						->where('cb.property_id', $property_id)
						->orderBy('ge.extension',  'asc')
						->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->extension;

				$datalist = DB::table('call_staff_extn as se')
						->join('call_section as cs', 'se.section_id', '=', 'cs.id')
						->join('common_building as cb', 'cs.building_id', '=', 'cb.id')
						->join('common_department as cd', 'cs.dept_id', '=', 'cd.id');
						if(count($filter_department)>0 ) {
							$datalist->whereIn('cd.department', $filter_department);
						}
						$datalist = $datalist	->where('se.bc_flag', 0)
							->where('se.extension', 'like', $filter)
							->where('cb.property_id', $property_id)
							->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->extension;

				array_multisort($ret);
				break;
			case 'Destination';
				$datalist = DB::table('call_destination')
						->where('country', 'like', $filter)
						->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->country;
				break;
			case 'Status';
				$ret = array('Completed', 'Open', 'Escalated', 'Timeout', 'Canceled', 'Scheduled', 'Unassigned');
				break;
			case 'Staff';
				$name_filter = sprintf("CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%s'", $filter);

				$datalist = DB::table('common_users as cu')
						->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
						->whereRaw($name_filter)
						->where('cd.property_id', $property_id)
						->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
						->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->wholename;
				break;
			case 'Item';
				$datalist = DB::table('services_task_list as tl')
						->join('services_task_group_members as tgm', 'tl.id', '=', 'tgm.task_list_id')
						->join('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
						->join('services_dept_function as df', 'tg.dept_function', '=', 'df.id')
						->join('common_department as cd', 'df.dept_id', '=', 'cd.id');
						if(count($filter_department_function)>0 ) {
							$datalist->whereIn('df.function', $filter_department_function);
						}
						$datalist = $datalist->where('tl.task', 'like', $filter)
						->where('cd.property_id', $property_id)
						->where('tl.status',1)
						->select(DB::raw('distinct(tl.task)'))
						->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->task;
				break;
			case 'Department Function';
				$datalist = DB::table('services_dept_function as df')
						->join('common_department as cd', 'df.dept_id', '=', 'cd.id');
						if(count($filter_department)>0 ) {
							$datalist->whereIn('cd.department', $filter_department);
						}
						$datalist = $datalist->where('df.function', 'like', $filter)
						->where('cd.property_id', $property_id)
						->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->function;
				break;
			case 'Location';
				$datalist = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationListData($filter, $property_id);

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->name . ":" . $datalist[$i]->type;
				break;
			case 'Shift';
				$datalist = DB::table('services_shifts')
						->where('name', 'like', $filter)
						->where('property_id', $property_id)
						->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->name;
				break;
			case 'Guest ID';
				$datalist = DB::table('services_minibar_log as ml')
						->join('common_room as cr', 'ml.room_id', '=', 'cr.id')
						->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
						->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
						->where('guest_id', 'like', $filter)
						->where('property_id', $property_id)
						->select(DB::raw('ml.*'))
						->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->guest_id;
				break;
			case 'Posted by';
				$name_filter = sprintf("CONCAT_WS(\" \", cu.first_name, cu.last_name) like '%s'", $filter);

				$datalist = DB::table('common_users as cu')
						->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
						->whereRaw($name_filter)
						->where('cd.property_id', $property_id)
						->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
						->get();

				for($i = 0; $i < count($datalist); $i++)
					if(!in_array($datalist[$i]->wholename, $ret)) $ret[] = $datalist[$i]->wholename;

				break;
			case 'Service Item';
				$datalist = DB::table('services_rm_srv_itm as rsi')
						->join('services_srv_grp_mbr as sgm', 'sgm.item_id', '=', 'rsi.id')
						->join('services_rm_srv_grp as rsg', 'sgm.grp_id', '=', 'rsg.id')
						->join('common_building as cb', 'rsg.building_id', '=', 'cb.id')
						->where('rsi.item_name', 'like', $filter)
						->where('cb.property_id', $property_id)
						->select(DB::raw('rsi.*'))
						->get();
				for($i = 0; $i < count($datalist); $i++)
					if(!in_array($datalist[$i]->item_name, $ret)) $ret[] = $datalist[$i]->item_name;
				break;
			case 'Housekeeping Status';
				$datalist = DB::table('services_hskp_status as hs')
						->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
						->where('hs.status', 'like', $filter)
						->where('cb.property_id', $property_id)
						->select(DB::raw('hs.*'))
						->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->status;
				break;
			case 'origin';
				$datalist = DB::table('common_country as cy')
					->where('cy.name', 'like', $filter.'%')
					->select(DB::raw('cy.*'))
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->name;
				break;
			case 'locationgroup';
				$datalist = DB::table('services_location_type as slt')
					->where('slt.type', 'like', $filter.'%')
					->select(DB::raw('slt.type'))
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->type;
				break;
			case 'GuestName';
				$datalist = DB::table('common_guest as cg')
					->where('cg.guest_name', 'like', $filter.'%')
					->select(DB::raw('cg.guest_name'))
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->guest_name;
				break;
			case 'GuestID';
				$datalist = DB::table('common_guest as cg')
					->where('cg.guest_id', 'like', $filter.'%')
					->select(DB::raw('cg.guest_id'))
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->guest_id;
				break;
			case 'GuestEmail';
				$datalist = DB::table('common_guest as cg')
					->where('cg.email', 'like', $filter.'%')
					->select(DB::raw('cg.email'))
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->email;
				break;
			case 'GuestMobile';
				$datalist = DB::table('common_guest as cg')
					->where('cg.mobile', 'like', $filter.'%')
					->select(DB::raw('cg.mobile'))
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->mobile;
				break;
			case 'Email';
				$datalist = DB::table('common_users as cu')
					->where('cu.first_name', 'like', $filter.'%')
					->select(DB::raw('cu.email'))
					->where('cu.deleted','!=',1)
					->distinct()
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->email;
				break;
			case 'Eng Staff';
				 $eng_dept = DB::table('property_setting')
            		->where('settings_key', 'eng_dept_id')
            		->where('property_id', $property_id)
            		->first();

        		$datalist = DB::table('common_users as cu')
            		->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
            		->leftJoin('common_department as de','cu.dept_id','=','de.id')
            		->whereRaw("CONCAT(cu.first_name, ' ', cu.last_name) like '%" . $filter . "%'")
         		    ->where('de.property_id', $property_id)
            	//	->whereIn('de.property_id', $property_list)
            		->groupBy('cu.id')
            		->where('cu.deleted', 0)
            		->where('cu.dept_id', $eng_dept->value)
            		->select(DB::raw('cu.id, jr.cost as cost, CONCAT_WS(" ", cu.first_name, cu.last_name) as name, "single" as type, "Individual" as label, cu.active_status'))
            		->orderBy('name')
            		->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->name;
				break;

			case 'Room Type';
				$datalist = DB::table('common_room_type as cy')
					->where('cy.type', 'like', $filter.'%')
					->select(DB::raw('cy.*'))
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->type;
				break;
			case 'Room Status';
				$datalist = DB::table('services_room_status as rs')
					->where('rs.rm_state', 'like', $filter.'%')
					->select(DB::raw('rs.rm_state'))
					->distinct()
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->rm_state;
				break;
			case 'Occupancy';
				$datalist = DB::table('services_room_status as rs')
					->where('rs.occupancy', 'like', $filter.'%')
					->select(DB::raw('rs.occupancy'))
					->distinct()
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->occupancy;
				break;
			case 'Reservation Status';
				$datalist = DB::table('services_room_status as rs')
					->where('rs.fo_state', 'like', $filter.'%')
					->select(DB::raw('rs.fo_state'))
					->distinct()
					->get();

				for($i = 0; $i < count($datalist); $i++)
					$ret[] = $datalist[$i]->fo_state;
				break;

		}

		$ret = array_unique($ret, SORT_REGULAR);
		$ret = array_merge($ret, []);

		//echo json_encode($ret);
		return Response::json($ret);
	}
}

