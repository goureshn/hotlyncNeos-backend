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
use App\Exports\CommonExport;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Border;

use App\Modules\Functions;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Response;
use Excel;
use Redis;

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
	protected $common_style = [
		6    => [
			'font' => ['bold' => true],
			'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
		],
		7    => [
			'font' => ['bold' => true],
			'fill' => [
				'fillType' => Fill::FILL_SOLID, 
				'startColor' => ['argb' => 'ECEFF1']
			],
			'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
		],
		'F2' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
		'F3' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
		'F4' => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
		'H4' => ['font' => ['bold' => true]]
	];

	protected $row_style = [
		'alignment' => [
			'horizontal' => Alignment::HORIZONTAL_CENTER, 
			'vertical' => Alignment::VERTICAL_CENTER
		],
		// 'borders' => [
		// 	'right' => [ 'borderStyle' => Border::BORDER_THIN ]
		// ],
	];

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

	public function downloadFacilitiesReportExcel(Request $request){

		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date('Y-M-d H:i:s');
		$cur_date = date('Y-M-d');

		$property_id = $request->get('property_id',4);
		$start_time = $request->get('start_time', $cur_date . ' 00:00:00');
		$end_time = $request->get('end_time', $cur_time);
		$data=$this->generateReportProc($property_id, $start_time, $end_time);
		//$property_id = $request->get('property_id',4);

		$property = DB::table('common_property')->where('id', $property_id)->first();
		if (empty($property)) {
			echo "Property does not exist";
			return;
		}
		$logo_path = $property->logo_path;

		$filename = $data['report_type'] .'_Report_By_' . $data['report_by'] . '_' . date('d_M_Y_H_i');
		//$folder_path = public_path() . '/uploads/reports/';
		// $path = $folder_path . $filename . '.html';
		// $pdf_path = $folder_path . $filename . '.xls';
		// ob_start();

		$param = $request->all();

		$export_data = ['datalist' => []];
		$datalist = [];
		$row_num = 8;
		$style = $this->common_style;
	
		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['period'] = $data['period'];
		$export_data['sub_title'] = "Facilities Report";
		
		$row_num = 4;

		if($data['report_by'] == 'Facilities' && $data['report_type'] == 'Detailed' && !empty($data['fac_list']) ) {
			$row_num += 4;
			$top_row=0;
			$rows=0;

			foreach ($data['fac_list'] as   $obj) {
				$arr = [];
				$rows++;
				$col_num='A';
				$col_num++;
	
					$arr["No."] = $rows;
					$arr["Guest Name"] = $obj->guest_name;
					$arr["Room Number"] = $obj->room;
					$arr["No. of Adult"] = $obj->adults;
					$arr["No. of Kids"] = $obj->kids;
					$arr["Extra Members"] = $obj->extra;
					$arr["Date"] = $obj->created_at;
					$arr["Facility"] = $obj->location;
					$arr["Comment"] = $obj->comment;
					$arr["Check-in Time"] = $obj->entry_time;
					$arr["Check-out Time"] = $obj->exit_time;
					$col_num++;

					$style[$row_num] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
				$row_num++;
				array_push($datalist, $arr);
			}
				$row_num--;
		}
		if($data['report_by'] == 'Facilities' && $data['report_type'] == 'Detailed') $export_data['datalist'] = $datalist;
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Facilities Report', $style), $filename . '.xlsx');
	}

	public function generateReportProc($property_id, $start_time, $end_time)
	{
		$data = [];
		$data['report_type'] = 'Detailed';
		$data['report_by'] = 'Facilities';
		$data['property_id'] = $property_id;
		$data['property'] = Property::find($property_id);
		$data['start_date'] = $start_time;
		$data['end_date'] = $end_time;

		$data['fac_list'] = DB::table('common_guest_facility_log as cgf')
		->leftJoin('common_guest as cg', 'cg.id', '=', 'cgf.guest_id')
		->leftJoin('common_room as cr', 'cg.room_id', '=', 'cr.id')
		->where('cg.property_id', $property_id)
		->whereBetween('cgf.entry_time', array($start_time, $end_time))
		->select(DB::raw('cg.guest_name, cr.room, cgf.adults, cgf.kids, cgf.extra, cgf.created_at, cgf.location, cgf.comment, cgf.entry_time, cgf.exit_time'))
		->get();

		$data['start_date'] = date('h:i A', strtotime($start_time));
		$data['end_date'] = date('h:i A', strtotime($end_time));
		$data['period'] = date_format(new DateTime($start_time), "d-M-Y H:i") . ' to ' . date_format(new DateTime($end_time), "d-M-Y H:i");
		$data['title'] = $data['report_type'] . ' Report by ' . $data['report_by'] . ' ';
	  	return $data;
	}

	private function outputExcelLogo($logo_path) {
		// Hotlync Logo

		$drawing = new Drawing();
        $drawing->setPath(public_path($logo_path));
        $drawing->setHeight(70);
        $drawing->setCoordinates('A1');

		return $drawing;
	}

	private function sendNotifyDownloadCompleted($param) {
		if( empty($param) )
			return;

		$message = [];
		$message['type'] = 'report_excel';
		$message['content'] = $param;

		Redis::publish('notify', json_encode($message));
	}

	public function downloadAuditExcelReportTask(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);


		$data = $this->makeAuditReportDataTask($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1) {
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}
		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Task_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = [];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Task Audit Report";
		$export_data['heading_list'] = ["ID","Department","Department Function","Task Group Name","Task","Category","Status"];
		$export_data['datalist'] = $data['data_list'];
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Task', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataTask(Request $request) {
		$property_id = $request->get('property_id','4');

		$user_id = $request->get('user_id', 0);

		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$query = DB::table('services_task_list as tl')
					->leftJoin('services_task_category as stc', 'tl.category_id', '=', 'stc.id')
					->leftJoin('services_task_group_members as tgm', 'tl.id', '=', 'tgm.task_list_id')
					->leftJoin('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
					->leftJoin('services_dept_function as df', 'tg.dept_function', '=', 'df.id')
					->leftJoin('common_department as cd', 'df.dept_id', '=', 'cd.id');

		if (!empty($user_id)) {
		    $dept_ids = $this->getDeptIdsFromUserId($user_id);

		    $query->whereIn('cd.id', $dept_ids);
        }

		$task_list = $query->select(DB::raw('tl.id, cd.department, df.function, tg.name, tl.task , stc.name as category,CASE WHEN tl.status = 1 THEN "Active" ELSE "In-Active" END ' ))
					->get();


		$total = count($task_list);

		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}


		$ret['Total'] = $total;
		$ret['data_list'] = $task_list;

		return $ret;
	}

	private function getDeptIdsFromUserId($user_id) {
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

	public function downloadAuditExcelReportDeptFunc(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);


		$data = $this->makeAuditReportDataDeptFunc($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1)
		{
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}
		}
		else
		{
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Department Function_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = [];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Department Function Audit Report";
		$export_data['heading_list'] = ["ID","Department","Function","Short Code","Description","Device Setting"];
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Dept Func', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataDeptFunc(Request $request) {
		$property_id = $request->get('property_id','4');
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$dept_list = DB::table('services_dept_function as df')
				->leftjoin('common_department as cd', 'df.dept_id', '=', 'cd.id')
				->select(DB::raw('df.id, cd.department, df.function, df.short_code, df.description, df.gs_device ' ))
				->get();


		$total = count($dept_list);

		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}

		$ret['Total'] = $total;
		$ret['data_list'] = $dept_list;

		return $ret;
	}

	public function downloadAuditExcelReportDevice(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);


		$data = $this->makeAuditReportDataDevice($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1) {
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}

		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Device_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = [];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Device Audit Report";
		$export_data['heading_list'] = ["ID","Name","Department Function","Secondary Department Function","Type","Number","Location Group","Secondary Location Group","Building","Device ID"];
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Device', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataDevice(Request $request) {
		$property_id = $request->get('property_id','4');
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$device_list = DB::table('services_devices as sd')
						->leftJoin('services_dept_function as df', 'sd.dept_func', '=', 'df.id')
						->leftJoin('services_dept_function as df2', 'sd.sec_dept_func', '=', 'df2.id')
						->leftJoin('services_location_group as lg', 'sd.loc_grp_id', '=', 'lg.id')
						->leftJoin('services_location_group as lg2', 'sd.sec_loc_grp_id', '=', 'lg2.id')
						->leftJoin('common_building as cb', 'sd.bldg_id', '=', 'cb.id')
						->select(DB::raw('sd.id, sd.name, df.function,df2.function as sec_function, sd.type, sd.number,lg.name as loc_name,lg2.name as sec_loc_name,cb.name as cb_name, sd.device_id'))
						->get();


		$total = count($device_list);

		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}

		$ret['Total'] = $total;
		$ret['data_list'] = $device_list;

		return $ret;
	}

	public function downloadAuditExcelReportUser(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);

		$data = $this->makeAuditReportDataUser($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1) {
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}

		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_User_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = [];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "User Audit Report";
		$export_data['heading_list'] = ["ID","First Name","Last Name","Username","Job Role","Department","Email","Mobile","Last Login","Status"];
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for User', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataUser(Request $request) {
		$property_id = $request->get('property_id','4');
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$user_list = DB::table('common_users as cu')
					->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->leftJoin('common_job_role as cj', 'cu.job_role_id', '=', 'cj.id')
					//->leftJoin('common_user_transaction as cut', 'cu.id', '=', 'cut.user_id')
					->leftJoin('common_user_transaction as cut', function($join) {
						$join->on('cu.id', '=', 'cut.user_id');
						$join->where('cut.action', 'like', 'login');
						//$join->where(max('cut.id'));
					})
					->select(DB::raw('cu.id, cu.first_name, cu.last_name, cu.username, cj.job_role, cd.department,cu.email, cu.mobile, CASE WHEN max(cut.created_at) THEN max(cut.created_at) ELSE " " END, CASE WHEN cu.deleted = 0 THEN "Active" ELSE "In-Active" END' ))
					->groupby('cu.id')
					->get();


		$total = count($user_list);

		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}


		$ret['Total'] = $total;
		$ret['data_list'] = $user_list;

		return $ret;
	}

	public function downloadFeedbackReportExcel(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date('Y-M-d H:i:s');
		$cur_date = date('Y-M-d');


		$property_id = $request->get('property_id',4);
		//$start_time = $request->get('start_date', $cur_date . ' 00:00:00');
		//$end_time = $request->get('end_date', $cur_time);
		$data = $this->getFBReportData($request);
		$property = DB::table('common_property')->where('id', $property_id)->first();
		if (empty($property)) {
			echo "Property does not exist";
			return;
		}
		$logo_path = $property->logo_path;

		$filename = $data['report_type'] .'_Guest Relations Log Report' . '_' . date('d_M_Y_H_i');

		$param = $request->all();

		$export_data = ['datalist' => []];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['datalist']) ) {
			foreach ($data['datalist'] as   $obj) {
				$style[$row_num] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['period'] = $data['period'];
		$export_data['sub_title'] = "Summary Guest Relations Log Report";
		$export_data['heading_list'] = ["Date","ID","Property","Category","Sub-Category","Room","Guest Name","Check-In","Check-Out","Feedback/Comments","Location","Occasion","Created At","Created By"];
		if($data['report_by'] == 'Summary' && $data['report_type'] == 'Summary' ) $export_data['datalist'] = $data['datalist']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Guest Relations Log Report', $style), $filename . '.xlsx');
	}

	public function getFBReportData(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date('Y-m-d H:i:s');
		$cur_date = date('Y-m-d');
		$report = [];

		$report['report_type'] = $request->get('report_type', 'Summary');
		$report['report_by'] = $request->get('report_by', 'Summary');
		$report['filter'] = $request->get('filter', 'All');
		$report['filter_value'] = $request->get('filter_value','');
		$report['start_date'] =$request->get('start_date', $cur_date . ' 00:00:00');
		$report['end_date'] =  $request->get('end_date', $cur_time);
		$report['property_id'] = $request->get('property_id', '');

		return $this->makeFBReportData($report);
	}

	public function makeFBReportData($report) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date('Y-M-d H:i:s');
		$cur_date = date('Y-M-d');

		$report_type = $report['report_type'];
		$report_by = $report['report_by'];
		$filter = $report['filter'];
		$filter_value = $report['filter_value'];
		$start_time = $report['start_date'];
		$end_time = $report['end_date'];
		$property_id=$report['property_id'];


		$ret = [];

		if($report_by == 'Summary')
			$this->getFBReportBy($report, $ret);

		$ret['report_by'] = $report_by;
		$ret['report_type'] = $report_type;
		$ret['filter'] = $filter;
		//$ret['start_date'] = date('h:i A', strtotime($start_time));
	    //$ret['end_date'] = date('h:i A', strtotime($end_time));

		$ret['period'] = date_format(new DateTime($start_time), "d-M-Y H:i") . ' to ' . date_format(new DateTime($end_time), "d-M-Y H:i");
		$ret['title'] = $report_type . ' Guest Relations Log Report ' . ' ';
		$ret['property'] = Property::find($property_id);
		return $ret;
	}

	public function getFBReportBy($report, &$ret) {

		$filter = $report['filter'];
		$filter_value = $report['filter_value'];
		$start_time = $report['start_date'];
		$end_time = $report['end_date'];



		$date_range = sprintf("DATE(gr.created_at) >= '%s' AND DATE(gr.created_at) <= '%s'", $start_time, $end_time);

		$query =DB::table('services_complaint_gr as gr')
				->leftJoin('common_guest as cg', 'gr.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_room as cr', 'cg.room_id', '=', 'cr.id')
				->leftJoin('common_room as crn', 'gr.room_id', '=', 'crn.id')
				->leftJoin('common_guest_profile as gp', 'gr.guest_id', '=', 'gp.id')
				->leftJoin('common_property as cp', 'gr.property_id', '=', 'cp.id')
				->leftJoin('services_complaint_gr_occasion as gro', 'gr.occasion_id', '=', 'gro.id')
				->leftJoin('common_employee as ce', 'gr.requestor_id', '=', 'ce.id')
				->whereRaw($date_range);

		if( $filter != 'Total' && $filter != '')
		{
			if( $filter == 1 || $filter == F_INTERACTION)	// On Route
				$query->where('gr.category', F_INTERACTION);
			if( $filter == 2 || $filter == F_COURTESY)
					$query->where('gr.category',F_COURTESY);
			if( $filter == 3 || $filter == F_INSPECTION )
					$query->where('gr.category', F_INSPECTION);
			if( $filter == 4 || $filter == F_ATTENTION )
					$query->where('gr.category', F_ATTENTION);
			if( $filter == 5 || $filter == F_ESCORTED)
					$query->where('gr.category', F_ESCORTED);

			}

		if($filter_value != '')
			{
				$query->where(function ($query) use ($filter_value) {
						$value = '%' . $filter_value . '%';
						$query->where('gr.id', 'like', $value)
							->orWhere('cr.room', 'like', $value)
							->orWhere('cp.name', 'like', $value)
							->orWhere('ce.fname', 'like', $value)
							->orWhere('ce.lname', 'like', $value)
							->orWhere('gp.guest_name', 'like', $value)
							->orWhere('cg.guest_name', 'like', $value);
					});
			}

		$data_query = clone $query;
		/*
		if ('gr.category' == 'Room Inspection')
		{
		$data_list = $data_query
			 ->orderBy('created_at', 'desc')
			->select(DB::raw('DATE(gr.created_at), gr.id, cp.name as property_name, gr.category, crn.room, gp.guest_name, cg.arrival,cg.departure, gr.comment, gro.occasion,gr.created_at, CONCAT_WS(" ", ce.fname, ce.lname) as wholename'))
			->get();
		}
		else
		{*/

		//}

		$data_list = $data_query
			->orderBy('created_at', 'desc')
		   ->select(DB::raw('DATE(gr.created_at), gr.id, cp.name as property_name, gr.category, gr.sub_category, cr.room, crn.room,CONCAT_WS(" ", gp.guest_name, cg.guest_name) as guest_name, cg.arrival,cg.departure, gr.comment,gr.loc_id, gro.occasion,gr.created_at, CONCAT_WS(" ", ce.fname, ce.lname) as wholename'))
		   ->get();

		foreach($data_list as $key => $row) {
		$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			$location = '';
				if(!empty($info)) {
					 $location =  $info->name.'-'.$info->type;
				}
				$occasion = $row->occasion;
				$created_at = $row->created_at;
				$wholename = $row->wholename;
				unset($row->loc_id);
				unset($row->occasion);
				unset($row->wholename);
				unset($row->created_at);
				$row->location = $location;
				$row->occasion = $occasion;
				$row->created_at = $created_at;
				$row->wholename = $wholename;


		}
		/*
				$data_list = $data_query
					->orderBy('created_at', 'desc')
				->select(DB::raw('DATE(gr.created_at), gr.id, cp.name as property_name, gr.category, cr.room, crn.room,CONCAT_WS(" ", gp.guest_name, cg.guest_name) as guest_name, cg.arrival,cg.departure, gr.comment,gr.loc_id, gro.occasion,gr.created_at, CONCAT_WS(" ", ce.fname, ce.lname) as wholename'))
				->get();
		*/


		$ret['datalist'] = $data_list;
	}

	public function downloadAuditExcelReportDepartment(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);

		$data = $this->makeAuditReportDataDepartment($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1)
		{
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}

		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Department_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = ['datalist' => []];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Department Audit Report";
		$export_data['heading_list'] = [ 'ID', 'Property', 'Department', 'Short Code', 'Services', 'Description' ];
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Department', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataDepartment(Request $request) {
		$property_id = $request->property_id ?? '4';
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$list = DB::table('common_department as cd')
				->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')
				->select(DB::raw('cd.id, cp.name, cd.department, cd.short_code,cd.services, cd.description' ))
				->get();

		$total = count($list);

		if(count($property) > 0 )
		{
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}

		$ret['Total'] = $total;
		$ret['data_list'] = $list;

		return $ret;
	}

	public function downloadAuditExcelReportAdminArea(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);


		$data = $this->makeAuditReportDataAdminArea($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1) {
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}
		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Admin Area_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = ['datalist' => []];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Admin Area Audit Report";
		$export_data['heading_list'] = [ 'ID', 'Property', 'Building', 'Floor', 'Name' ];
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Admin Area', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataAdminArea(Request $request) {
		$property_id = $request->property_id ?? '4';
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$area_list = DB::table('common_admin_area as ca')
				->leftJoin('common_floor as cf','ca.floor_id','=','cf.id')
				->leftJoin('common_building as cb','cf.bldg_id','=','cb.id')
				->leftJoin('common_property as cp', 'cb.property_id', '=', 'cp.id')
				//->where('cb.property_id', $property_id)
				->select(DB::raw('ca.id,cp.name as property_name,cb.name as building ,cf.floor,ca.name' ))
				->get();


		$total = count($area_list);

		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}


		$ret['Total'] = $total;
		$ret['data_list'] = $area_list;

		return $ret;
	}

	public function downloadAuditExcelReportRoom(Request $request) {
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);

		$data = $this->makeAuditReportDataRoom($request);

		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1) {
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}

		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Room_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = ['datalist' => []];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]];
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Room Audit Report";
		$export_data['heading_list'] = [ 'ID', 'Building', 'Floor', 'Room Type', 'Room', 'Description', 'Credits' ];
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Room', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataRoom(Request $request) {

		$property_id = $request->get('property_id','0');
		$ret = [];
		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$query = DB::table('common_room as cr')
				->leftjoin('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->leftJoin('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->leftjoin('common_room_type as ct', 'ct.id', '=', 'cr.type_id')
				->leftJoin('common_property as cp', 'cb.property_id', '=', 'cp.id');
				//->where('cb.property_id', $property_id);

		$data_query = clone $query;

		$data_list = $data_query
			->select(DB::raw('cr.id,cb.name as building, cf.floor, ct.type as room_type, cr.room, cr.description,cr.credits'))
			->get();

		$total = count($data_list);

		if(count($property) > 0 ) {
				$label = '';
				for($i = 0; $i < count($property); $i++)
				{
					if( $i > 0 )
						$label = $label . ', ';
					$label = $label . $property[$i]->name;
				}
				$ret['property'] = $label;
		}

		$ret['Total'] = $total;
		$ret['data_list'] = $data_list;

		return $ret;
	}

	public function downloadAuditExcelReportSection(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);

		$data = $this->makeAuditReportDataSection($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1)
		{
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}
		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Section_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = ['datalist' => []];
		$export_data['heading_list'] = [ 'ID', 'Property', 'Section', 'Department' ];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = $this->row_style;
				// $style['A' . $row_num] = $this->row_style;
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Section Audit Report";
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Section', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataSection(Request $request) {
		$property_id = $request->get('property_id','4');
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$section_list = DB::table('call_section as cs')
				->leftjoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')
				->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')
				//->where('cd.property_id', $property_id)
				->select(DB::raw('cs.id, cp.name, cs.section,cd.department' ))
				->get();

		$total = count($section_list);

		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}

		$ret['Total'] = $total;
		$ret['data_list'] = $section_list;

		return $ret;
	}

	public function downloadAuditExcelReportAdminExt(Request $request) {
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);

		$data = $this->makeAuditReportDataAdminExt($request);

		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1) {
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}

		}else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Admin_Extension_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = ['datalist' => []];
		$export_data['heading_list'] = [ 'ID', 'Property', 'Building', 'Department', 'Section', 'Extension', 'User', 'User Group', 'Description', 'Status' ];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = $this->row_style;
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Admin Extension Audit Report";
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Admin Ext', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataAdminExt(Request $request) {
		$property_id = $request->get('property_id','4');
		$ret = [];

		$property = DB::table('common_property as cb')
					->select(DB::raw('cb.name'))
					->get();


		$adminlist = DB::table('call_staff_extn as ce')
					->leftjoin('call_section as cs', 'ce.section_id', '=', 'cs.id')
					->leftjoin('common_users as cu','ce.user_id','=','cu.id')
					->leftjoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')
					->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')
					->leftJoin('common_building as cb', 'ce.building_id', '=', 'cb.id')
					//->where('cd.property_id', $property_id)
					->orderBy('ce.id','asc')
					->select(DB::raw('ce.id, cp.name, cb.name as building, cd.department, cs.section,ce.extension, cu.username,ce.user_group_name,ce.description, CASE WHEN ce.enable = 1 THEN "Active" ELSE "In-Active" END' ))
					->get();

		$total = count($adminlist);
		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++) {
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}

		$ret['Total'] = $total;
		$ret['data_list'] = $adminlist;

		return $ret;
	}

	public function downloadAuditExcelReportGuestExt(Request $request) {
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);

		$data = $this->makeAuditReportDataGuestExt($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1)
		{
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}

		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Guest_Extension_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
        $excel_file_type = config('app.report_fle_type');

		$export_data = ['datalist' => []];
		$export_data['heading_list'] = [ 'ID', 'Property', 'Building', 'Room', 'Extension', 'Status' ];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = $this->row_style;
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Guest Extension Audit Report";
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Guest Ext', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataGuestExt(Request $request) {
		$property_id = $request->get('property_id','4');
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$guestlist = DB::table('call_guest_extn as ce')
				->leftjoin('common_building as cb','ce.bldg_id','=','cb.id')
				->leftjoin('common_room as cr', 'ce.room_id', '=', 'cr.id')
				->leftjoin('common_property as cp', 'cb.property_id', '=', 'cp.id')
				//->where('cb.property_id', $property_id)
				->orderBy('ce.extension','asc')
				->select(DB::raw('ce.id, cp.name,cb.name as building, cr.room, ce.extension, CASE WHEN ce.enable = 1 THEN "Active" ELSE "In-Active" END' ))
				->get();

		$total = count($guestlist);

		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++) {
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}

		$ret['Total'] = $total;
		$ret['data_list'] = $guestlist;
		//$ret['property'] = Property::find($property_id);
		return $ret;
	}

	public function downloadAuditExcelReportGuestRate(Request $request) {
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);


		$data = $this->makeAuditReportDataGuestRate($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1) {
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}

		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Guest Rate Mapping_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = ['datalist' => []];
		$export_data['heading_list_colspan'] = [
			['value' => '', 'col' => 0],
			['value' => '', 'col' => 0],
			['value' => '', 'col' => 0],
			['value' => '', 'col' => 0],
			['value' => 'Morning Off Peak 00:00:00 - 06:59:59', 'col' => 3],
			['value' => 'Daily Peak 07:00:00 - 20:59:59', 'col' => 3],
			['value' => 'Night Off Peak 21:00:00 - 23:59:59', 'col' => 3],
			['value' => 'All Day Off Peak 00:00:00 - 23:59:59', 'col' => 3]
		];
		$export_data['heading_list'] = ['Group', 'Country Code', 'Country','Allowance', 'Carrier', 'Property','Total','Carrier','Property', 'Total', 'Carrier','Property', 'Total','Carrier','Property','Total'];
		$row_num = 8;
		$style = $this->common_style;
		$data_collection = collect($data['data_list'])->pipe(function ($coll) {
			return collect([ '', '', '', 
			'Total Count : ' . $coll->count(), 
			$coll->sum('morning_carrier'), '',
			$coll->sum('morning_total'),
			$coll->sum('daily_carrier'), '',
			$coll->sum('daily_total'),
			$coll->sum('night_carrier'), '',
			$coll->sum('night_total'),
			$coll->sum('all_carrier'), '', 
			$coll->sum('all_total')]);
		})->toArray();

		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as $key => $obj) {
				if($row_num === 8){
					$style[8] = [
						'font' => ['bold' => true],
						'fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'ECEFF1'] ],
						'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
					];
				}else $style[$row_num] = $this->row_style;
				$row_num++;
			}
		}
		$row_num++;
		$style["E$row_num:P$row_num"] = ['fill' => [ 'fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => '91afb2'] ]];
		array_push($data['data_list'], $data_collection); 
		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Guest Extension Audit Report";
		$export_data['datalist'] = $data['data_list'];
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Guest Ext', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataGuestRate(Request $request) {
		$property_id = $request->get('property_id','4');
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$query = DB::table('call_guest_charge_map as cm')
				->leftjoin('call_carrier_groups as cg', 'cm.carrier_group_id', '=', 'cg.id')
				->leftjoin('call_group_destination as cd', 'cd.carrier_group_id','=','cg.id')
				->leftJoin('call_destination as cds', 'cd.destination_id', '=', 'cds.id')
				->leftJoin('call_allowance as ca', 'cm.call_allowance', '=', 'ca.id')
				->leftjoin('call_time_slab as cs','cm.time_slab','=', 'cs.id')
				->leftjoin('call_carrier_charges as cc' , 'cm.carrier_charges','=','cc.id')
				->leftjoin('call_hotel_charges as chc' , 'cm.hotel_charges','=','chc.id')
				->leftjoin('call_tax as ct' , 'cm.tax','=','ct.id')
				->groupBy('cg.id')
				//->groupBy('cds.id')
				->groupBy('cds.country')
				//->groupBy('ca.id')
				->groupBy('cs.id')
				->select(DB::raw('cg.name as group_name, cds.country as country , cds.code as country_code,
				 ca.name as allowance, cs.name as slab_name, cs.start_time as slab_start_time,
		   		 cs.end_time as slab_end_time, cs.days_of_week as slab_day, round(cc.charge,2) as charge, round(chc.charge,2) as hotel_charge, round(ct.value,2) as tax, chc.method  '))
				->get();
		$list = [];
		foreach ($query as $row) {
			$start_time = new DateTime($row->slab_start_time);
			$end_time = new DateTime($row->slab_end_time);
			$group_key = $row->group_name.$row->country_code;

			if (isset($list[$group_key])) {
			} else {
				$list[$group_key] = [];
			}

			$list[$group_key]['group_name'] = $row->group_name;
			$list[$group_key]['country'] = $row->country;
			$list[$group_key]['country_code'] = $row->country_code;
			$list[$group_key]['allowance'] = $row->allowance;
			$hotel_charge = 0;
				if($row->method == 'Duration' ) $hotel_charge = $row->charge*$row->hotel_charge;
				if($row->method == 'Per Call' ) $hotel_charge = $row->hotel_charge;
				if($row->method == 'Percentage' ) $hotel_charge = ($row->hotel_charge/100)*$row->charge;
				if($row->method == 'Pulse' ) $hotel_charge = $row->charge*$row->hotel_charge;
				$hotel_charge = round($hotel_charge,2);

			if($row->slab_day != 'Friday' && $start_time >= new DateTime('00:00:00') && $end_time <= new DateTime('06:59:59')) {
				$row->morning = $row->slab_name;
				$row->daily = '';
				$row->night = '';
				$row->all = '';
				$list[$group_key]['morning_carrier'] = $row->charge;

				//$hotel_chrg = round(($row->hotel_charge * $row->charge)/100,2);
				$list[$group_key]['hotel_1'] = $hotel_charge;
				$list[$group_key]['morning_total'] = round($row->charge + $hotel_charge + $row->tax ,2);
				$row->daily_carrier = '';
				$row->daily_total = '';
				$row->night_carrier = '';
				$row->night_total = '';
				$row->all_carrier = '';
				$row->all_total = '';
			}
			if($row->slab_day != 'Friday' && $start_time >= new DateTime('07:00:00') && $end_time <= new DateTime('20:59:59')) {
				$row->morning = '';
				$row->daily = $row->slab_name;
				$row->night = '';
				$row->all = '';
				$row->morning_carrier = '';
				$row->morning_total = '';
				$list[$group_key]['daily_carrier'] = $row->charge;
				$list[$group_key]['hotel_2'] = $hotel_charge;
				//$list[$group_key]['hotel_2'] =round(($row->hotel_charge * $row->charge)/100,2);
				$list[$group_key]['daily_total'] = round($row->charge + $hotel_charge + $row->tax ,2);
				$row->night_carrier = '';
				$row->night_total = '';
				$row->all_carrier = '';
				$row->all_total = '';
			}
			if($row->slab_day != 'Friday' && $start_time >= new DateTime('21:00:00') && $end_time <= new DateTime('23:59:59')) {
				$row->morning = '';
				$row->daily = '';
				$row->night = $row->slab_name;
				$row->all = '';
				$row->morning_carrier = '';
				$row->morning_total = '';
				$row->daily_carrier = '';
				$row->daily_total = '';
				$list[$group_key]['night_carrier'] = $row->charge;
				$list[$group_key]['hotel_3'] = $hotel_charge;
				//$list[$group_key]['hotel_3'] = round(($row->hotel_charge * $row->charge)/100,2);
				$list[$group_key]['night_total'] = round($row->charge + $hotel_charge + $row->tax ,2);
				$row->all_carrier = '';
				$row->all_total = '';
			}
			if($row->slab_day == 'Friday' && $start_time >= new DateTime('00:00:00') && $end_time <= new DateTime('23:59:59')) {
				$row->morning = '';
				$row->daily = '';
				$row->night = '';
				$row->all = $row->slab_name;
				$row->morning_carrier = '';
				$row->morning_total = '';
				$row->daily_carrier = '';
				$row->daily_total = '';
				$row->night_carrier = '';
				$row->night_total = '';
				$list[$group_key]['all_carrier'] = $row->charge;
				$list[$group_key]['hotel_4'] = $hotel_charge;
				//$list[$group_key]['hotel_4'] = round(($row->hotel_charge * $row->charge)/100,2);
				$list[$group_key]['all_total'] = round($row->charge + $hotel_charge + $row->tax ,2);
			}

			if($start_time >= new DateTime('00:00:00') && $end_time <= new DateTime('23:59:59')) {
				$row->morning = '';
				$row->daily = '';
				$row->night = '';
				$row->all = $row->slab_name;
				$row->morning_carrier = '';
				$row->morning_total = '';
				$row->daily_carrier = '';
				$row->daily_total = '';
				$row->night_carrier = '';
				$row->night_total = '';
				$list[$group_key]['morning_carrier'] = 0;
				$list[$group_key]['morning_total'] = 0;
				$list[$group_key]['daily_carrier'] = 0;
				$list[$group_key]['daily_total'] = 0;
				$list[$group_key]['night_carrier'] = 0;
				$list[$group_key]['night_total'] = 0;
				$list[$group_key]['hotel_1'] = 0;
				$list[$group_key]['hotel_2'] = 0;
				$list[$group_key]['hotel_3'] = 0;
				$list[$group_key]['all_carrier'] = $row->charge;
				$list[$group_key]['hotel_4'] = $hotel_charge;
				//$list[$group_key]['hotel_4'] = round(($row->hotel_charge * $row->charge)/100,2);
				$list[$group_key]['all_total'] = round($row->charge + $hotel_charge + $row->tax ,2);
			}

		}

		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}

		//$ret['Total'] = $total;
		$ret['data_list'] = $list;
		//$ret['property'] = Property::find($property_id);
		return $ret;
	}

	public function downloadAuditExcelReportMinibar(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);

		$data = $this->makeAuditReportDataMinibar($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1) {
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}
		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Minibar Item_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = ['datalist' => []];
		$export_data['heading_list'] = [ 'ID', 'Item Name', 'Charge', 'PMS Code', 'IVR Code', 'Max Item', 'Item Status' ];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = $this->row_style;
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Minibar Audit Report";
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Minibar', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataMinibar(Request $request) {
		$property_id = $request->get('property_id','4');
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$minibar_list = DB::table('services_rm_srv_itm as rsi')
					->select(DB::raw('rsi.id, rsi.item_name, rsi.charge, rsi.pms_code, rsi.ivr_code, rsi.max_qty, CASE WHEN rsi.active_status = 1 THEN "Active" ELSE "In-Active" END'))
					->get();


		$total = count($minibar_list);

		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}

		$ret['Total'] = $total;
		$ret['data_list'] = $minibar_list;

		return $ret;
	}

	public function downloadAuditExcelReportCompensation(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);


		$data = $this->makeAuditReportDataCompensation($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1) {
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}

		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Compensation_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = ['datalist' => []];
		$export_data['heading_list'] = [ 'ID', 'Client', 'Property', 'Compensation', 'Cost', 'Approval Route' ];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = $this->row_style;
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Compensation Audit Report";
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Compensation', $style), $filename . '.xlsx');
	}

	public function makeAuditReportDataCompensation(Request $request) {
		$property_id = $request->get('property_id','4');
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$comp_list = DB::table('services_compensation as sc')
				->leftJoin('services_approval_route as sar', 'sc.approval_route_id', '=', 'sar.id')
				->leftJoin('common_property as cp','sc.property_id','=','cp.id')
				->leftJoin('common_chain as cc','sc.client_id', '=','cc.id')
				->select(DB::raw('sc.id, cc.name, cp.name as property, sc.compensation, sc.cost, sar.approval ' ))
				->get();


		$total = count($comp_list);

		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}


		$ret['Total'] = $total;
		$ret['data_list'] = $comp_list;
		//$ret['property'] = Property::find($property_id);
		return $ret;
	}

	public function downloadAuditExcelReportJobrole(Request $request)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);

		$data = $this->makeAuditReportDataJobrole($request);
		$property = DB::table('common_property as cp')->select(DB::raw('cp.id,cp.logo_path'))->get();

		if (count($property) <= 1) {
			foreach ($property as $key => $value) {
				$logo_path = $value->logo_path;
			}
		} else {
			$logo_path = 'frontpage/img/logo_1490913790.png';
		}

		$param = $request->all();
		$filename = 'Audit_Report_Job Role_' . date('d_M_Y_H_i');

		//	$excel_file_type = 'csv';
		//	if($param['excel_type'] == 'excel')
		$excel_file_type = config('app.report_file_type');

		$export_data = ['datalist' => []];
		$export_data['heading_list'] = [ 'ID', 'Property', 'Job Role', 'Department', 'Permission Group' ];
		$row_num = 8;
		$style = $this->common_style;
		
		if( !empty($data['data_list']) ) {
			foreach ($data['data_list'] as   $obj) {
				$style[$row_num] = $this->row_style;
				$row_num++;
			}
		}

		$export_data['logo'] = $this->outputExcelLogo($logo_path);;
		$export_data['property'] = $data['property'];
		$export_data['sub_title'] = "Job Role Audit Report";
		$export_data['datalist'] = $data['data_list']->toArray();
		$this->sendNotifyDownloadCompleted($param);

		return Excel::download(new CommonExport('excel.common_export', $export_data, 'Audit Report for Job Role', $style), $filename . '.xlsx');

		// $ret = Excel::create($filename, function ($excel) use ($data, $logo_path, $param) {
		// 	$excel->sheet('Audit Report for Jobrole', function ($sheet) use ($data, $logo_path) {
		// 		$sheet->setOrientation('landscape');

		// 		$this->outputAuditLogo($sheet, $logo_path);

		// 		$row_num = 1;
		// 		$row_num = $this->outputAuditJobReport($sheet, $row_num, $data);
		// 				$row_num += 2;
		// 	});

		// 	$this->sendNotifyDownloadCompleted($param);

		// })->export($excel_file_type);
	}

	public function makeAuditReportDataJobrole(Request $request) {
		$property_id = $request->get('property_id','4');
		$ret = [];

		$property = DB::table('common_property as cb')
				->select(DB::raw('cb.name'))
				->get();

		$job_list = DB::table('common_job_role as cj')
					->leftJoin('common_property as cp', 'cj.property_id', '=', 'cp.id')
					->leftJoin('common_department as cd', 'cj.dept_id', '=', 'cd.id')
					// ->leftJoin('common_property as cp', 'ug.property_id', '=', 'cp.id')
					->leftJoin('common_perm_group as pg', 'cj.permission_group_id', '=', 'pg.id')
					->select(DB::raw('cj.id, cp.name as property_name, cj.job_role, cd.department as department, pg.name as pgname'))
					->get();

		$total = count($job_list);
		if(count($property) > 0 ) {
			$label = '';
			for($i = 0; $i < count($property); $i++)
			{
				if( $i > 0 )
					$label = $label . ', ';
				$label = $label . $property[$i]->name;
			}
			$ret['property'] = $label;
		}

		$ret['Total'] = $total;
		$ret['data_list'] = $job_list;

		return $ret;
	}
}

