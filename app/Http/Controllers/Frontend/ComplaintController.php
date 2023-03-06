<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Call\GuestExtension;
use App\Models\Common\CommonUser;
use App\Models\Common\CommonUserNotification;
use App\Models\Common\Guest;
use App\Models\Common\GuestProfile;
use App\Models\Common\GuestAdvancedDetail;
use App\Models\Common\Property;
use App\Models\Common\PropertySetting;
use App\Models\Common\CronLogs;
use App\Models\Common\Room;
use App\Models\Common\Employee;
use App\Models\Common\UserMeta;
use App\Models\Service\Wakeup;
use App\Models\Service\WakeupLog;
use App\Models\Service\ComplaintRequest;
use App\Models\Service\ModCheckList;
use App\Models\Service\ComplaintGR;
use App\Models\Service\ComplaintReminder;
use App\Models\Service\ComplaintSublist;
use App\Models\Service\ComplaintSublistState;
use App\Models\Service\ComplaintSublistLocState;
use App\Models\Service\ComplaintSublistReopenState;
use App\Models\Service\ComplaintBriefing;
use App\Models\Service\ComplaintFlag;
use App\Models\Service\ComplaintNote;
use App\Models\Service\ComplaintLog;
use App\Models\Service\ComplaintUpdated;
use App\Modules\Functions;
use App\Models\Service\ComplaintState;
use App\Models\Service\ComplaintMainState;
use App\Models\Service\ComplaintDivisionMainState;
use App\Models\Service\CompensationRequest;
use App\Models\Service\CompensationTemplate;
use App\Models\Service\CompensationItem;
use App\Models\Service\CompensationState;
use App\Models\Service\CompensationApproveRoute;
use App\Models\Service\Tasklog;
use App\Models\Service\ComplaintMainCategory;
use App\Models\Service\ComplaintMainSubCategory;
use App\Models\Service\ComplaintCategory;
use App\Models\Service\ComplaintSubcategory;
use App\Models\Service\Device;
use App\Models\Service\ShiftGroupMember;
use App\Models\Service\ComplaintBriefingHistory;
use App\Models\Service\ComplaintBriefingRoom;
use App\Models\Service\LocationGroupMember;
use App\Models\Common\CommonUserGroup;
use App\Models\Service\ShiftUser;
use App\Models\Service\ComplaintSublistCompensation;
use App\Models\Service\Location;
use Illuminate\Support\Facades\Config;

use DateInterval;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Redis;
use Response;
use Curl;
use Log;
use File;

define("C_PENDING", 'Pending');
define("C_RESOLVED", 'Resolved');
define("C_REJECTED", 'Rejected');
define("C_ACK", 'Acknowledge');
define("C_TIMEOUT", 'Timeout');
define("C_ESCALATED", 'Escalated');
define("C_CANCELED", 'Canceled');
define("C_FORWARDED", 'Forwarded');
define("C_UNRESOLVED", 'Unresolved');

define("F_INTERACT", 'Guest Interaction');
define("F_COURTESYS", 'Courtesy Calls');
define("F_INSPECT", 'Room Inspection');
define("F_ATTENT", 'In-House Special Attention');
define("F_ESCORT", 'Escorted to Room');



define("SC_OPEN", 1);
define("SC_ESCALATED", 3);
define("SC_COMPLETE", 2);
define("SC_REASSIGN", 4);
define("SC_CANCELED", 5);
define("SC_TIMEOUT", 6);
define("SC_REOPEN", 7);

define("CP_COMPLETE_APPROVE", 0);
define("CP_ON_ROUTE", 1);
define("CP_REJECTED", 2);
define("CP_RETURNED", 3);
define("CP_PENDING", 4);

define("B_WAITING", 'Waiting');
define("B_ACTIVE", 'Active');
define("B_ENDED", 'Ended');
define("B_CANCELLED", 'Cancelled');
define("B_SCHEDULED", 'Scheduled');


class ComplaintController extends Controller
{
	public function sendMailApprove(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$comment="From E-Mail";
		 
		$status_id = $request->get('status_id', 0);
		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);
		$complaint = ComplaintRequest::find($id);
		if($status_id==6 && ($complaint->status=='Pending'))
		{
	       //$complaint->status="Rejected";
	       if( !empty($complaint) )
			{
				$complaint->status = C_REJECTED;
				$complaint->solution = $comment;
				$complaint->updated_at = $cur_time;
				$complaint->save();

				ComplaintMainState::initState($complaint->id);
				ComplaintDivisionMainState::initState($complaint->id);

				ComplaintUpdated::modifyByUser($complaint->id, $user_id);
			}

			// Session::flash('message1', 'My message');
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $id;
			$complaint_log->sub_id = 0;
			$complaint_log->comment = 'Rejected - Via E-Mail';
			$complaint_log->type = C_REJECTED;
			$complaint_log->user_id = $user_id;
			$complaint_log->save();
			
			echo "<div class='alert alert-success'>
						<strong>Success! </strong>Complaint C000$id has been Rejected.
					<div>";
	     	       
       }
       else if($status_id==1 && ($complaint->status=='Pending'))
       {	      
	       	if( !empty($complaint) )
			{
				$complaint->status = C_ACK;
				$complaint->updated_at = $cur_time;
				$complaint->save();

				ComplaintMainState::initState($complaint->id);
				ComplaintDivisionMainState::initState($complaint->id);
				
				//ComplaintUpdated::modifyByUser($complaint->id, $user_id);
			}
	       $complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = 'Acknowledged - Via E-Mail';
		$complaint_log->type = C_ACK;
		$complaint_log->user_id = $user_id;
		$complaint_log->save();
		
		
	       echo "<div class='alert alert-success'>
							  <strong>Success! </strong>Complaint C000$id has been Acknowledged.
							 <div>";
	       
       }
       else if(($status_id==6 && ($complaint->status=='Rejected')) || ($status_id==1 && ($complaint->status=='Rejected')))
       {
	     echo "<div class='alert alert-danger'>
							  <strong>Failed to update status! </strong>Complaint C000$id has already been Rejected .
							 <div>";
       }
       else
       {
	      echo "<div class='alert alert-danger'>
							  <strong>Failed to update status! </strong>Complaint C000$id has already been Acknowledged.
							 <div>";
       }
       
    }

    private function applyComplaintFilter($user_id, $property_ids, $filter, $filter_value, $start_date, $end_date, $id) {
    	$query = DB::table('services_complaint_request as sc')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_department as cd', 'sc.dept_id', '=', 'cd.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
				// ->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc.property_id', '=', 'cg.property_id');
				})
				//	->leftJoin('common_vip_codes as vc', 'vc.vip_code', '=', 'cg.vip')
				->leftJoin('common_vip_codes as vc', function($join) {
					$join->on('sc.property_id', '=', 'vc.property_id');
					$join->on('cg.vip', '=', 'vc.vip_code');
				})					
				->leftJoin('common_vip_codes as vc', 'vc.vip_code', '=', 'cg.vip')
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')			
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
				->leftJoin('common_house_complaints_category as hcc', 'sc.housecomplaint_id', '=', 'hcc.id')			
				->leftJoin('services_complaint_reminder as scr', 'sc.id', '=', 'scr.id')
				->leftJoin('services_complaint_maincategory as cmc', 'sc.category_id', '=', 'cmc.id')
				->leftJoin('services_complaint_main_subcategory as cmsc', 'sc.subcategory_id', '=', 'cmsc.id')
				->leftJoin('services_complaint_feedback_type as scft', 'sc.feedback_type_id', '=', 'scft.id')
				->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
				->leftJoin('services_location as sl', 'sc.loc_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
				->where('sc.delete_flag',0);
		if( $id > 0 )
		{
			$query->where('sc.id', $id);
			return $query;	
		}		
		// check main complaint view permission		
		if( CommonUser::isValidModule($user_id, Config::get('constants.MAINCOMPLAINT_DEPT_VIEW_ALL')) == false )
		{
			$user = CommonUser::find($user_id);				
			$query->where('cu.dept_id', $user->dept_id);
		}
				
		if(!empty($property_ids))
			$query->whereIn('sc.property_id', $property_ids);

		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));
		$cur_time = date("Y-m-d H:i:s");


		if($filter_value != '')
		{
			$query->where(function ($query) use ($filter_value) {	
					$value = '%' . $filter_value . '%';
					$query->where('sc.id', 'like', $value)
						->orWhere('sc.guest_type', 'like', $value)
						->orWhere('sc.status', 'like', $value)
						->orWhere('cr.room', 'like', $value)
						->orWhere('cp.name', 'like', $value)
						->orWhere('ce.fname', 'like', $value)
						->orWhere('ce.lname', 'like', $value)
						->orWhere('gp.guest_name', 'like', $value)				
						->orWhere('gp.guest_id', 'like', $value);				
				});
		}

		switch($filter['ticket']) 
		{
			case 'All Tickets':
				$query->whereRaw(sprintf("DATE(sc.created_at) >= '%s' and DATE(sc.created_at) <= '%s'", $start_date, $end_date));
				break;
			case 'Custom Days':
				$query->whereRaw(sprintf("DATE(sc.created_at) >= '%s' and DATE(sc.created_at) <= '%s'", $start_date, $end_date));
				break; 
			case 'Last 24 Hours':				
				$query->whereBetween('sc.created_at', array($last24, $cur_time));
				break;
			case 'Tickets created by me':
				$query->whereRaw(sprintf("DATE(sc.created_at) >= '%s' and DATE(sc.created_at) <= '%s'", $start_date, $end_date));
				$query->where('sc.requestor_id', $user_id);	
				break;		
		}
		
		if($filter['all_flag']==0)
		{			
			if($filter['service_recovery'])
			{
				$amt=500;
				switch($filter['service_recovery']) 
				{					
					case 'Less than 500':
						$query->whereRaw("((sc.compensation_total + sc.subcomp_total) BETWEEN 1 AND $amt)");
						break;
					case 'Greater than 500':				
						$query->whereRaw("((sc.compensation_total + sc.subcomp_total) >= $amt)");
						break;
					case 'All':
						$query->whereRaw("((sc.compensation_total + sc.subcomp_total) >= 1)");
						break;		
				}
			}
		
			// check status filter
			if(array_search(true,$filter['status_filter']))
			{		
				$query->where(function ($subquery) use ($filter, $user_id) {	
					$subquery_flag = false;
					if( $filter['status_filter']['Pending'] )
					{
						$subquery->where('sc.status', 'Pending');
						$subquery_flag = true;
					}
					
					if( $filter['status_filter']['Resolved'] )
					{
						$subquery->orWhere('sc.status', 'Resolved');
						$subquery_flag = true;
					}

					if( $filter['status_filter']['Acknowledge'] )
					{
						$subquery->orWhere('sc.status', 'Acknowledge');
						$subquery_flag = true;
					}

					if( $filter['status_filter']['Rejected'] )
					{
						$subquery->orWhere('sc.status', 'Rejected');
						$subquery_flag = true;
					}

					if( $filter['status_filter']['Unresolved'] )
					{
						$subquery->orWhere('sc.status', 'Unresolved');
						$subquery_flag = true;
					}

					if( $filter['status_filter']['Flagged'] )
					{
						$subquery->orWhere('sc.mark_flag', 1);						
						$subquery_flag = true;
					}	

					if( $subquery_flag == false )
						$subquery->whereRaw('1=1');
				});
			}
			
			$query->where(function ($subquery) use ($filter, $user_id) {
				if( isset($filter['status_filter']['Closed']) && $filter['status_filter']['Closed'])
					$subquery->where('sc.closed_flag', 1);	
				else
					$subquery->whereRaw('1=1');
			});	

			// check severity filter
			if(array_search(true,$filter['severity_filter']))
			{
				$query->where(function ($subquery) use ($filter, $user_id) {	
					$severity_ids = [];
					foreach($filter['severity_filter'] as $key => $row) {
						if( $row == false )
							continue;

						$severity = DB::table('services_complaint_type')
							->where('type', $key)
							->first();

						if( empty($severity) )
							continue;	

						$severity_ids[] = $severity->id;
					}

					if( count($severity_ids) > 0 )
						$subquery->orWhereIn('sc.severity', $severity_ids);
					else
						$subquery->whereRaw('1=1');
				});
			}

			// check property filter
			if(array_search(true,$filter['property_filter']))
			{
				if( count($property_ids) > 1 )	// only multi job role
				{
					$query->where(function ($subquery) use ($filter, $user_id) {	
						$property_ids = [];
						foreach($filter['property_filter'] as $key => $row) {
							if( $row == false )
								continue;

							$property = DB::table('common_property')
								->where('name', $key)
								->first();

							if( empty($property) )
								continue;	

							$property_ids[] = $property->id;
						}

						if( count($property_ids) > 0 )
							$subquery->orWhereIn('sc.property_id', $property_ids);
						else
							$subquery->whereRaw('1=1');
					});	
				}
			}
			
			// get department
			// $query->leftJoin('services_complaint_sublist as scs', 'scs.parent_id', '=', 'sc.id');
			// $query->leftJoin('common_department as cd', 'scs.dept_id', '=', 'cd.id');
			// $query->groupBy('sc.id');
			// echo json_encode($filter['department_tags']);
			if(!empty($filter['department_tags']))
			{
				// echo 'here';
				$dept_ids = [];
				foreach($filter['department_tags'] as $key => $row) {
					if( $row == false )
						continue;

					$dept_ids[] = $row['id'];
				}
		
				// echo json_encode($dept_ids);
				if( count($dept_ids) > 0 )
				{
					$query->whereExists(function ($subquery) use ($dept_ids) {
							$subquery->select(DB::raw(1))
								->from('services_complaint_sublist as scs')
								->whereIn('scs.dept_id', $dept_ids)
								->where('scs.delete_flag', 0)
								->whereRaw('sc.id = scs.parent_id');
						});
					foreach($dept_ids as $row)
						$query->orWhereRaw('FIND_IN_SET('.$row.',sc.sent_ids)');							
				}
			}

			// discussed filter
			if( $filter['discussed'] )
				$query->where('sc.discussed_flag', 1);

			// guest type filter
			if(array_search(true,$filter['guest_type_filter']))
			{
				$query->where(function ($subquery) use ($filter, $user_id) {	
					$guest_types = [];
					foreach($filter['guest_type_filter'] as $key => $row) {
						if( $row == true )
							$guest_types[] = $key;
					}	

					if( count($guest_types) > 0 )
						$subquery->whereIn('sc.guest_type', $guest_types);
				});
			}

			// departure date 
			if( $filter['departure_flag'] )
			{
				$query->where('cg.departure', $filter['departure_date']);
			}		
		}

		return $query;

    }
	public function getfbList(Request $request)
	{
		$start = microtime(true);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');		
		$filter = $request->get('filter');
		$filter_value = $request->get('filter_value', '');
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$user_id = $request->get('user_id', 0);
		//$category = $request->get('category');
		$property_ids_by_jobrole = $request->get('property_ids_by_jobrole', []);
		
		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));

		if ($pageSize < 0)
			$pageSize = 20;
		$ret = array();
		
		$date_range = sprintf("DATE(gr.created_at) >= '%s' AND DATE(gr.created_at) <= '%s'", $start_date, $end_date);
		
		$query =DB::table('services_complaint_gr as gr')
				->leftJoin('common_guest as cg', 'gr.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_room as cr', 'cg.room_id', '=', 'cr.id')
				->leftJoin('common_room as crn', 'gr.room_id', '=', 'crn.id')
				->leftJoin('common_guest_profile as gp', 'gr.guest_id', '=', 'gp.id')
				->leftJoin('common_property as cp', 'gr.property_id', '=', 'cp.id')
				
				//->leftJoin('common_room as crn',function($join){
					//$join->('gr.room_id', '=', 'crn.id');
					//$join->('gr.guest_id', '=', 'gp.id');
				//})
				->leftJoin('services_complaint_gr_occasion as gro', 'gr.occasion_id', '=', 'gro.id')
				->leftJoin('common_employee as ce', 'gr.requestor_id', '=', 'ce.id')
				->whereRaw($date_range)
				->where('gr.property_id', $property_id);
				
		/*
		if ('gr.category' == 'Room Inspection')
		{
			$query->leftJoin('common_room as crn', 'gr.room_id', '=', 'crn.id')
				  ->whereRaw('gr.guest_id = gp.id');
		}
		*/
		if( $filter != 'Total' && $filter != '')
		{
			if( $filter == 1 || $filter == F_INTERACT)	// On Route
				$query->where('gr.category', F_INTERACT);
			if( $filter == 2 || $filter == F_COURTESYS)
					$query->where('gr.category',F_COURTESYS);
			if( $filter == 3 || $filter == F_INSPECT)
					$query->where('gr.category', F_INSPECT);
			if( $filter == 4 || $filter == F_ATTENT)
					$query->where('gr.category', F_ATTENT);
			if( $filter == 5 || $filter == F_ESCORT)
					$query->where('gr.category', F_ESCORT);
			
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
		
		$data_list = $data_query
		    ->orderBy('created_at', 'desc')
			->select(DB::raw('gr.*,gro.occasion,cg.guest_name,gp.guest_name as new_guest,cg.arrival,cg.departure,cr.room,crn.room as new_room,cp.name as property_name,CONCAT_WS(" ", ce.fname, ce.lname) as wholename'))
			->skip($skip)->take($pageSize)
			->get();

			foreach($data_list as $key => $row) {
				$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
				if( !empty($info) )
				{
					$data_list[$key]->lgm_name = $info->name;
					$data_list[$key]->lgm_type = $info->type;
					$data_list[$key]->lg_property_id = $info->property_id;
				}
			}
		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['code'] = 200;
		$ret['message'] = '';
 		
		$ret['totalcount'] = $totalcount;

		$ret['datalist'] = $data_list;
		$end = microtime(true);	
		$ret['time'] = $end - $start;
		$ret['filter'] = $filter;
		$ret['property_ids'] = $property_ids_by_jobrole;

		return Response::json($ret);
	}

	public function getList(Request $request)
	{
		$start = microtime(true);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');		
		$filter = $request->get('filter');
		$filter_value = $request->get('filter_value', '');
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$user_id = $request->get('user_id', 0);
		$property_ids_by_jobrole = $request->get('property_ids_by_jobrole', []);
		
		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));

		if ($pageSize < 0)
			$pageSize = 20;
		$ret = array();
		
		if( empty($filter) )
		{
			$filter = UserMeta::getComplaintTicketFilter($user_id,$property_ids_by_jobrole);			
		}

		$query = $this->applyComplaintFilter($user_id, $property_ids_by_jobrole, $filter, $filter_value, $start_date, $end_date, $id);
		
		$data_query = clone $query;
		
		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('sc.*, cd.department, gp.guest_name, cr.room, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name, hcc.name as house_complaint_name, ce.design,
					(select count(*) from services_complaint_updated where complaint_id = sc.id and user_id = ' . $user_id . ') as latest, 
					DATEDIFF(CURTIME(), sc.incident_time) as age_days, sl.name as lgm_name, slt.type as lgm_type, sc.property_id as lg_property_id,
					gp.mobile, gp.phone, cg.first_name as fname,gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, cp.name as property_name, cp.shortcode,gp.guest_id as gs_guest_id, gp.passport, gp.comment as guest_comment, gp.pref, gp.check_flag,
					scr.reminder_ids, scr.reminder_flag, scr.reminder_time, scr.comment as reminder_comment, scft.name as feedback_type, scfs.name as feedback_source,
					sc.mark_flag as flag, cg.arrival, cg.departure,cg.booking_src,vc.name as vip,cg.booking_rate,cg.company, jr.job_role, cmc.name as category_name, cmsc.name as subcategory_name'))
				->skip($skip)->take($pageSize)
				->where('sc.delete_flag',0)
				->get();


				$ret['datalist'] = $data_list;
		// Guest::getGuestList($data_list);
		ComplaintSublist::getCompleteInfo($data_list);

		$setting = PropertySetting::getComplaintSetting($property_id);

		foreach($data_list as $key => $row) {			
			$data_list[$key]->forward_flag = $setting['complaint_forward_flag'];
			if( empty($row->design) )
				$row->design = $row->job_role;

			// get department list
			$data_list[$key]->dept_list = ComplaintRequest::getSubDeptList($row->id);
			$data_list[$key]->selected_ids = ComplaintRequest::deptList($row->id);
			$data_list[$key]->response = nl2br($row->initial_response);
			$data_list[$key]->comment_response = nl2br($row->comment);			
		}
		
		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['code'] = 200;
		$ret['message'] = '';
 		
		$ret['totalcount'] = $totalcount;
		

		$data_query = clone $query;

		$subcount = $data_query
				->select(DB::raw("
						count(*) as total,
						COALESCE(sum(sc.status = '". C_PENDING . "'), 0) as pending,
						COALESCE(sum(sc.status = '" . C_RESOLVED . "'), 0) as resolved,
						COALESCE(sum(sc.status = '" . C_REJECTED . "'), 0) as rejected,
						COALESCE(sum(sc.status = '". C_ACK . "'), 0) as ack,
						COALESCE(sum(sc.status = '". C_TIMEOUT . "'), 0) as timeout,
						COALESCE(sum(sc.status = '". C_ESCALATED . "'), 0) as escalated,
						COALESCE(sum(sc.discussed_flag = 1), 0) as discussed,
						COALESCE(sum(sc.mark_flag = 1), 0) as flag,
						COALESCE(sum(sc.status = '". C_CANCELED . "'), 0) as canceled
						"))
				->first();
			

		$ret['subcount'] = $subcount;
		
		$end = microtime(true);	
		$ret['time'] = $end - $start;
		if( $id == 0 )
			$ret['filter'] = $filter;
		$ret['property_ids'] = $property_ids_by_jobrole;

		// get dept list
		$ret['dept_list'] = DB::table('common_department as cd')
			// ->leftJoin('common_property_department_pivot as pdp', 'cd.id', '=', 'pdp.dept_id')
			// ->where('pdp.property_id', $property_id)
			->whereIn('cd.property_id', $property_ids_by_jobrole)
			// ->orWhere('cd.property_id', $property_id)
			->select(DB::raw('cd.id, cd.department'))
			->orderBy('cd.department')
			->get();			


		// save filter
		if( $id == 0 )
			UserMeta::saveComplaintTicketFilter($user_id, $filter);

		return Response::json($ret);
	}

	public function fixSubcomplaintTotal(Request $reuqest)
	{
		ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);
		set_time_limit(0);

		$ret = ComplaintRequest::updateSubcompTotalForAll();

		echo "Total Complaint Count = $ret";
	}

	public function getListfromMobile(Request $request)
	{
		$start = microtime(true);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$filter = $request->get('filter');
		$filter_value = $request->get('filter_value', '');
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$user_id = $request->get('user_id', 0);
		$property_ids_by_jobrole = $request->get('property_ids_by_jobrole', []);
		$property_ids = array();
		$property_ids[] = $property_id;
		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));

		if ($pageSize < 0)
			$pageSize = 20;

		if( empty($filter) )
		{
			//$filter = UserMeta::getComplaintTicketFilter($user_id,$property_ids_by_jobrole);
		}

		$ret = array();
		$query = $this->applyComplaintFilterfromMobile($user_id, $property_ids, $filter, $filter_value, $start_date, $end_date);

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('sc.*, cmc.name as category, cmsc.name as subcategory, gp.guest_name, cr.room, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name, hcc.name as house_complaint_name, ce.design,
				(select 1 from services_complaint_updated where complaint_id = sc.id and user_id = ' . $user_id . ') as latest, group_concat(scs.dept_id) as dept_list,
				gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, cp.name as property_name, cp.shortcode, gp.passport, gp.comment as guest_comment, gp.pref, gp.check_flag,
				scr.reminder_ids, scr.reminder_flag, scr.reminder_time, scr.comment as reminder_comment,vc.name as vip,
				cg.booking_rate, cg.booking_src,
				sc.mark_flag as flag, cg.arrival, cg.departure, jr.job_role, cg.company, cmc.name as category_name'))
				->skip($skip)->take($pageSize)
				->get();

		// Guest::getGuestList($data_list);
		ComplaintSublist::getCompleteInfo($data_list);

		$setting = PropertySetting::getComplaintSetting($property_id);

		foreach($data_list as $key => $row) {
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			if( !empty($info) )
			{
				$data_list[$key]->lgm_name = $info->name;
				$data_list[$key]->lgm_type = $info->type;
				$data_list[$key]->lg_property_id = $info->property_id;
			}
			$data_list[$key]->forward_flag = $setting['complaint_forward_flag'];

			// get department list
			if( empty($row->dept_list) )
				$row->dept_list = [];
			else
			{
				$dept_list = DB::table('common_department as cd')
						->whereRaw('cd.id in (' . $row->dept_list . ')')
						->get();

				$data_list[$key]->dept_list = $dept_list;
			}
		}

		$count_query = clone $query;
		$totalcount = $count_query
				->select(DB::raw('count(sc.id) as cnt'))
				->get();

		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['datalist'] = $data_list;
		$ret['totalcount'] = count($totalcount);

		$data_query = clone $query;

		$subcount = $data_query
				->select(DB::raw("
						count(*) as total,
						COALESCE(sum(sc.status = '". C_PENDING . "'), 0) as pending,
						COALESCE(sum(sc.status = '" . C_RESOLVED . "'), 0) as resolved,
						COALESCE(sum(sc.status = '" . C_REJECTED . "'), 0) as rejected,
						COALESCE(sum(sc.status = '". C_ACK . "'), 0) as ack,
						COALESCE(sum(sc.status = '". C_TIMEOUT . "'), 0) as timeout,
						COALESCE(sum(sc.status = '". C_ESCALATED . "'), 0) as escalated,
						COALESCE(sum(sc.discussed_flag = 1), 0) as discussed,
						COALESCE(sum(sc.mark_flag = 1), 0) as flag,
						COALESCE(sum(sc.status = '". C_CANCELED . "'), 0) as canceled
						"))
				->first();

		$ret['subcount'] = $subcount;

		$end = microtime(true);
		$ret['time'] = $end - $start;
		$ret['filter'] = $filter;
		$ret['property_ids'] = $property_ids;

		// get dept list
		$ret['dept_list'] = DB::table('common_department as cd')
				// ->leftJoin('common_property_department_pivot as pdp', 'cd.id', '=', 'pdp.dept_id')
				// ->where('pdp.property_id', $property_id)
				->whereIn('cd.property_id', $property_ids)
				// ->orWhere('cd.property_id', $property_id)
				->select(DB::raw('cd.id, cd.department'))
				->orderBy('cd.department')
				->get();


		// save filter
		UserMeta::saveComplaintTicketFilter($user_id, $filter);


		return Response::json($ret);
	}

	public function getComplaintListForMobile(Request $request)
	{
		$start = microtime(true);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$pageNumber = $request->get('pageNumber', 0);
		$pageCount = $request->get('pageCount', 25);
		$status = $request->get('status', '');
		$searchKey = $request->get('searchKey', '');
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$user_id = $request->get('user_id', 0);
		$property_ids_by_jobrole = $request->get('property_ids_by_jobrole', []);
		$property_ids = array();
		$property_ids[] = $property_id;
		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));
		$statusNames = ['Total', 'Pending', 'Resolved', 'Acknowledge', 'Rejected', 'Closed', 'Unresolved', 'Flagged'];
		$countInfo['All'] = 0;
		foreach($statusNames as $statusName) {
			$tempQuery = $this->applyComplaintFilterfromMobile($user_id, $property_ids, $statusName, $searchKey, $start_date, $end_date);
			$tempCount = count($tempQuery->get());
			if ($tempCount < 1) {
				continue;
			}
			$countInfo[$statusName] = $tempCount;
			$countInfo['All'] += $tempCount;
		}
		$query = $this->applyComplaintFilterfromMobile($user_id, $property_ids, $status, $searchKey, $start_date, $end_date);
		$data_query = clone $query;
////
		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('sc.*, cmc.name as category, cmsc.name as subcategory, gp.guest_name, cr.room, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name, hcc.name as house_complaint_name, ce.design,
					(select 1 from services_complaint_updated where complaint_id = sc.id and user_id = ' . $user_id . ') as latest, group_concat(scs.dept_id) as dept_list,
					gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, cp.name as property_name, cp.shortcode, gp.passport, gp.comment as guest_comment, gp.pref, gp.check_flag,
					scr.reminder_ids, scr.reminder_flag, scr.reminder_time, scr.comment as reminder_comment,vc.name as vip,
					cg.booking_rate, cg.booking_src,
					sc.mark_flag as flag, cg.arrival, cg.departure, jr.job_role, cg.company, cmc.name as category_name'))
				->skip($pageCount * $pageNumber)->take($pageCount)
				->get();
		// Guest::getGuestList($data_list);
		ComplaintSublist::getCompleteInfo($data_list);
		
		$setting = PropertySetting::getComplaintSetting($property_id);
		foreach($data_list as $key => $row) {
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			if( !empty($info) )
			{
				$data_list[$key]->lgm_name = $info->name;
				$data_list[$key]->lgm_type = $info->type;
				$data_list[$key]->lg_property_id = $info->property_id;
			}
			$data_list[$key]->forward_flag = $setting['complaint_forward_flag'];
			// get department list
			if( empty($row->dept_list) )
				$row->dept_list = [];
			else
			{
				$dept_list = DB::table('common_department as cd')
						->whereRaw('cd.id in (' . $row->dept_list . ')')
						->get();
				$data_list[$key]->dept_list = $dept_list;
			}
		}
		$count_query = clone $query;
		$currencyitem = DB::table('property_setting')->where('settings_key', 'currency')->where('property_id', $property_id)->first();
		    if(!empty($currencyitem)){
			    $currency = $currencyitem->value;
		    }else{
			    $currency = 'AED';
		    }
		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['datalist'] = $data_list;
		$ret['currency'] = $currency;
		// $data_query = clone $query;
		// $subcount = $data_query
		// 		->select(DB::raw("
		// 				count(*) as total,
		// 				COALESCE(sum(sc.status = '". C_PENDING . "'), 0) as pending,
		// 				COALESCE(sum(sc.status = '" . C_RESOLVED . "'), 0) as resolved,
		// 				COALESCE(sum(sc.status = '" . C_REJECTED . "'), 0) as rejected,
		// 				COALESCE(sum(sc.status = '". C_ACK . "'), 0) as ack,
		// 				COALESCE(sum(sc.status = '". C_TIMEOUT . "'), 0) as timeout,
		// 				COALESCE(sum(sc.status = '". C_ESCALATED . "'), 0) as escalated,
		// 				COALESCE(sum(sc.discussed_flag = 1), 0) as discussed,
		// 				COALESCE(sum(sc.mark_flag = 1), 0) as flag,
		// 				COALESCE(sum(sc.status = '". C_CANCELED . "'), 0) as canceled
		// 				"))
		// 		->first();
		$ret['countInfo'] = $countInfo;
		$end = microtime(true);
		// $ret['time'] = $end - $start;
		$ret['property_ids'] = $property_ids;
		// get dept list
		$ret['dept_list'] = DB::table('common_department as cd')
				// ->leftJoin('common_property_department_pivot as pdp', 'cd.id', '=', 'pdp.dept_id')
				// ->where('pdp.property_id', $property_id)
				->whereIn('cd.property_id', $property_ids)
				// ->orWhere('cd.property_id', $property_id)
				->select(DB::raw('cd.id, cd.department'))
				->orderBy('cd.department')
				->get();
		// save filter
		UserMeta::saveComplaintTicketFilter($user_id, $status);
		return Response::json($ret);
	}


	private function applyComplaintFilterfromMobile($user_id, $property_ids, $filter, $filter_value, $start_date, $end_date) {
		$query = DB::table('services_complaint_request as sc')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
				// ->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc.property_id', '=', 'cg.property_id');
				})
				//	->leftJoin('common_vip_codes as vc', 'vc.vip_code', '=', 'cg.vip')
				->leftJoin('common_vip_codes as vc', function($join) {
					$join->on('sc.property_id', '=', 'vc.property_id');
					$join->on('cg.vip', '=', 'vc.vip_code');
				})
				->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
				->leftJoin('common_house_complaints_category as hcc', 'sc.housecomplaint_id', '=', 'hcc.id')
				->leftJoin('services_complaint_reminder as scr', 'sc.id', '=', 'scr.id')
				->leftJoin('services_complaint_maincategory as cmc', 'sc.category_id', '=', 'cmc.id')
				->leftJoin('services_complaint_main_subcategory as cmsc', 'sc.subcategory_id', '=', 'cmsc.id')
				->where('sc.delete_flag', 0);

		// check main complaint view permission		
		if( CommonUser::isValidModule($user_id, Config::get('constants.MAINCOMPLAINT_DEPT_VIEW_ALL')) == false )
		{
			$user = CommonUser::find($user_id);				
			$query->where('cu.dept_id', $user->dept_id);
		}		

		$query->whereIn('sc.property_id', $property_ids);
		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));
		$cur_time = date("Y-m-d H:i:s");
		//switch($filter['ticket'])
		//{
			//case 'All Tickets':
				$query->whereRaw(sprintf("DATE(sc.created_at) >= '%s' and DATE(sc.created_at) <= '%s'", $start_date, $end_date));
				//break;
			/*case 'Last 24 Hours':
				$query->whereBetween('sc.created_at', array($last24, $cur_time));
				break;
			case 'Tickets created by me':
				$query->whereRaw(sprintf("DATE(sc.created_at) >= '%s' and DATE(sc.created_at) <= '%s'", $start_date, $end_date));
				$query->where('sc.requestor_id', $user_id);
				break;
		}*/

		if($filter_value != '')
		{
			$query->where(function ($query) use ($filter_value) {
				$value = '%' . $filter_value . '%';
				$query->where('sc.id', 'like', $value)
						->orWhere('sc.guest_type', 'like', $value)
						->orWhere('sc.status', 'like', $value)
						->orWhere('cr.room', 'like', $value)
						->orWhere('cp.name', 'like', $value)
						->orWhere('ce.fname', 'like', $value)
						->orWhere('ce.lname', 'like', $value)
						->orWhere('gp.guest_name', 'like', $value);
			});
		}

		if (!empty($filter)) {
			$query->where('sc.status', $filter);
		}

		// // check status filter
		// $query->where(function ($subquery) use ($filter, $user_id) {
		// 	if( $filter == 'Pending' )
		// 		$subquery->where('sc.status', 'Pending');

		// 	if( $filter == 'Resolved' )
		// 		$subquery->orWhere('sc.status', 'Resolved');

		// 	if( $filter == 'Acknowledge' )
		// 		$subquery->orWhere('sc.status', 'Acknowledge');

		// 	if( $filter == 'Rejected' )
		// 		$subquery->orWhere('sc.status', 'Rejected');

		// 	if( $filter == 'Closed' )
		// 		$subquery->orWhere('sc.closed_flag', 1);

		// 	if( $filter == 'Unresolved' )
		// 		$subquery->orWhere('sc.status', 'Unresolved');

		// 	if( $filter == 'Flagged' )
		// 	{
		// 		$subquery->orWhere('sc.mark_flag', 1);
		// 	}
		// 	if( $filter == 'Total' )
		// 	{
		// 		$subquery->whereNotNull('sc.status');
		// 	}
		// });

		// check severity filter
		/*$query->where(function ($subquery) use ($filter, $user_id) {
			$severity_ids = [];
			foreach($filter['severity_filter'] as $key => $row) {
				if( $row == false )
					continue;

				$severity = DB::table('services_complaint_type')
						->where('type', $key)
						->first();

				if( empty($severity) )
					continue;

				$severity_ids[] = $severity->id;
			}

			if( count($severity_ids) > 0 )
			{
				$subquery->orWhereIn('sc.severity', $severity_ids);
			}
		});*/

		// check property filter
		if( count($property_ids) > 1 )	// only multi job role
		{
			$query->where(function ($subquery) use ($filter, $user_id) {
				$property_ids = [];
				foreach($filter['property_filter'] as $key => $row) {
					if( $row == false )
						continue;

					$property = DB::table('common_property')
							->where('name', $key)
							->first();

					if( empty($property) )
						continue;

					$property_ids[] = $property->id;
				}

				if( count($property_ids) > 0 )
				{
					$subquery->orWhereIn('sc.property_id', $property_ids);
				}
			});
		}

		// get department
		$query->leftJoin('services_complaint_sublist as scs', 'scs.parent_id', '=', 'sc.id');
		$query->leftJoin('common_department as cd', 'scs.dept_id', '=', 'cd.id');
		// $query->where('scs.delete_flag', 0);
		$query->groupBy('sc.id');

		// check department filter
		/*$query->where(function ($subquery) use ($filter, $user_id) {
			$dept_ids = [];
			foreach($filter['department_tags'] as $key => $row) {
				if( $row == false )
					continue;

				$dept_ids[] = $row['id'];
			}

			if( count($dept_ids) > 0 )
			{
				$subquery->orWhereIn('scs.dept_id', $dept_ids);
			}
		});*/

		// discussed filter
		/*if( $filter['discussed'] )
			$query->where('sc.discussed_flag', 1);*/

		// guest type filter
		/*$query->where(function ($subquery) use ($filter, $user_id) {
			$guest_types = [];
			foreach($filter['guest_type_filter'] as $key => $row) {
				if( $row == true )
					$guest_types[] = $key;
			}

			if( count($guest_types) > 0 )
				$subquery->whereIn('sc.guest_type', $guest_types);
		});

		// departure date
		if( $filter['departure_flag'] )
		{
			$query->where('cg.departure', $filter['departure_date']);
		}*/

		return $query;

	}
	public function saveTicketFilter(Request $request) {
		$user_id = $request->get('user_id', 0);

	}

	public function getComplaintTypeCount(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', '0');		

		$date = new DateTime($cur_time);
		$date->sub(new DateInterval('P1D'));
		$last_time = $date->format('Y-m-d H:i:s');

		$ret = array();
		$query = DB::table('services_complaint_request as sc')
				->where('sc.delete_flag', 0)				
				->where('sc.property_id', $property_id);
				// ->where('time', '>', $last_time);

		$subcount = $query
				->select(DB::raw("
						count(*) as total,
						COALESCE(sum(sc.status = '". C_PENDING . "'), 0) as pending,
						COALESCE(sum(sc.status = '" . C_RESOLVED . "'), 0) as resolved,
						COALESCE(sum(sc.status = '" . C_REJECTED . "'), 0) as rejected,
						COALESCE(sum(sc.status = '". C_ACK . "'), 0) as ack,
						COALESCE(sum(sc.status = '". C_TIMEOUT . "'), 0) as timeout,
						COALESCE(sum(sc.status = '". C_ESCALATED . "'), 0) as escalated,
						COALESCE(sum(sc.status = '". C_CANCELED . "'), 0) as canceled,
						COALESCE(sum(sc.closed_flag = 1), 0) as closed
						"))
				->first();

		$content = array(
			array('name' => 'PENDING', 'count' => $subcount->pending, 'color' => 'ff1352e2'),
			array('name' => 'ACKNOWLEDGED', 'count' => $subcount->ack, 'color' => 'fff7941d'),
			array('name' => 'RESOLVED', 'count' => $subcount->resolved, 'color' => 'ff22c064'),
			array('name' => 'CLOSED', 'count' => $subcount->closed, 'color' => 'ffe0483ec')						
		);		

		$ret['code'] = 200;
		$ret['content'] = $content;
		$ret['total'] = $subcount->total;
		$ret['message'] = '';

		return Response::json($ret);
	}


	public function getBriefingSrcList(Request $request)
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
		$filter_value = $request->get('filter_value', '');
		$selected_ids = $request->get('selected_ids', []);
		$category_ids = $request->get('category_ids', []);
		$start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');

		$date = new DateTime($cur_time);
		$date->sub(new DateInterval('P1D'));
		$last_time = $date->format('Y-m-d H:i:s');

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();
		$query = DB::table('services_complaint_request as sc')				
		        ->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')					
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
//				->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc.property_id', '=', 'cg.property_id');
				})
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')		
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')	
				->leftJoin('services_complaint_maincategory as cmc', 'sc.category_id', '=', 'cmc.id')										
				->whereRaw('sc.property_id = ' . $property_id)
				->whereRaw(sprintf("DATE(sc.created_at) >= '%s' and DATE(sc.created_at) <= '%s'", $start_date, $end_date))
				->where('sc.delete_flag', 0);
				// ->where('time', '>', $last_time);
			

		if( !empty($selected_ids) && count($selected_ids) )
		{
			$query->whereRaw('sc.id NOT IN (' . implode(',', $selected_ids) . ')');
		}		

		if( !empty($category_ids) && count($category_ids) )
		{
			$query->whereRaw('sc.category_id NOT IN (' . implode(',', $category_ids) . ')');
		}	

		if($filter != 'Total' && $filter != 'Discussed' && $filter != 'Flagged' ) {
			$query->whereRaw("sc.status = '" . $filter . "'");
		}
		
		if( $filter == 'Flagged' ) {  //  flagged filter
			$query->whereRaw('sc.mark_flag = 1');
		}

		if( $filter == 'Discussed' ) {  //  discussed filter
			$query->whereRaw('sc.discussed_flag = 1');
		}		
		

		if($filter_value != '')
		{
			$query->where(function ($query) use ($filter_value) {	
					$value = '%' . $filter_value . '%';
					$query->whereRaw("sc.id like '" . $value . "'")
						->orWhereRaw("sc.guest_type like '" . $value . "'")
						->orWhereRaw("sc.status like '" . $value . "'")
						->orWhereRaw("cr.room like '" . $value . "'")
						->orWhereRaw("cp.name like '" . $value . "'")
						->orWhereRaw("ce.fname like '" . $value . "'")
						->orWhereRaw("ce.lname like '" . $value . "'")
						->orWhereRaw("gp.guest_name like '" . $value . "'");				
				});
		}
		//$query->whereRaw(sprintf("DATE(sc.created_at) >= '%s' and DATE(sc.created_at) <= '%s'", $start_date, $end_date));

		$query->groupBy(DB::raw('CASE WHEN category_name IS NULL THEN sc.id ELSE category_name END'))
			->select(DB::raw('MAX(sc.id) AS id, cmc.name as category_name'));

		$group_sql = '(' . $query->toSql()	. ') as sc2';

		// get grouped category list
		$query = DB::table('services_complaint_request as sc1')		
		        ->leftJoin('common_room as cr', 'sc1.room_id', '=', 'cr.id')				
				->leftJoin('common_employee as ce', 'sc1.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc1.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
//				->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc1.property_id', '=', 'cg.property_id');
				})
				->leftJoin('common_property as cp', 'sc1.property_id', '=', 'cp.id')		
				->leftJoin('services_complaint_type as ct', 'sc1.severity', '=', 'ct.id')				
				->join(DB::raw($group_sql), 'sc2.id', '=', 'sc1.id')
				->where('sc1.delete_flag', 0);
//$query->whereRaw(sprintf("DATE(sc1.created_at) >= '%s' and DATE(sc1.created_at) <= '%s'", $start_date, $end_date));
		$data_query = clone $query;
		
		$data_list = $data_query
		        ->orderBy('sc1.id', 'desc')								
				->select(DB::raw('sc1.*, sc2.category_name, gp.guest_name, cr.room, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name, 
					gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, gp.passport, cg.arrival, cg.departure, jr.job_role'))
				->skip($skip)->take($pageSize)
				->get();

		// Guest::getGuestList($data_list);
		ComplaintSublist::getCompleteInfo($data_list);

		foreach($data_list as $key => $row) {
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			if( !empty($info) )
			{
				$data_list[$key]->lgm_name = $info->name;
				$data_list[$key]->lgm_type = $info->type;
			}
			if( $row->category_id > 0 )
			{
				$cnt = DB::table('services_complaint_request as sc')
					->where('sc.discussed_flag', 0)
					->where('sc.delete_flag', 0)
				    ->where('sc.property_id', $property_id)
				    ->where('sc.category_id', $row->category_id)
				    ->count();

				$data_list[$key]->cnt = $cnt - 1;    
			}
			else
			{
				$data_list[$key]->cnt = 0;    	
			}

			$data_list[$key]->dept_list = ComplaintRequest::getSubDeptList($row->id);
			$data_list[$key]->selected_ids = ComplaintRequest::deptList($row->id);
		}

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;
		$ret['sql'] = $group_sql;

		return Response::json($ret);
	}

	public function getBriefingProgressList(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', '0');
		
		$ret = array();
		$query = DB::table('services_complaint_briefing as scb')
				->leftJoin('services_complaint_request as sc', 'scb.complaint_id', '=', 'sc.id')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')				
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
//				->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('scb.property_id', '=', 'cg.property_id');
				})
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
				->leftJoin('services_complaint_maincategory as cmc', 'sc.category_id', '=', 'cmc.id')				
				->where('sc.delete_flag', 0)
				->where('sc.property_id', $property_id);
				// ->where('time', '>', $last_time);

		$data_query = clone $query;

		$data_list = $data_query	
				->orderBy('scb.id')					
				->select(DB::raw('sc.*, cmc.name as category_name, gp.guest_name, cr.room, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name, scb.id as brief_id,  scb.discussed_flag as dis_flag,					
					gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, gp.passport, cg.arrival, cg.departure, jr.job_role'))				
				->get();

		// Guest::getGuestList($data_list);
		ComplaintSublist::getCompleteInfo($data_list);

		foreach($data_list as $key => $row) {
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			if( !empty($info) )
			{
				$data_list[$key]->lgm_name = $info->name;
				$data_list[$key]->lgm_type = $info->type;
			}

			if( $row->category_id > 0 )
			{
				$cnt = DB::table('services_complaint_request as sc')
					->where('sc.discussed_flag', 0)
					->where('sc.delete_flag', 0)
				    ->where('sc.property_id', $property_id)
				    ->where('sc.category_id', $row->category_id)
				    ->count();

				$data_list[$key]->cnt = $cnt - 1;    
			}
			else
			{
				$data_list[$key]->cnt = 0;    	
			}

			$data_list[$key]->dept_list = ComplaintRequest::getSubDeptList($row->id);
			$data_list[$key]->selected_ids = ComplaintRequest::deptList($row->id);

		}

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		// find current brief id
		$complaint = DB::table('services_complaint_briefing as scb')
					->leftJoin('services_complaint_request as sc', 'scb.complaint_id', '=', 'sc.id')					
					->where('scb.discussed_flag', 2)
					->where('sc.delete_flag', 0)
					->where('sc.property_id', $property_id)
					->select(DB::raw('sc.*, scb.id as brief_id'))
					->first();

		if( empty($complaint) )
		{
			$complaint = DB::table('services_complaint_briefing as scb')
					->leftJoin('services_complaint_request as sc', 'scb.complaint_id', '=', 'sc.id')					
					->where('scb.discussed_flag', 1)	// discussed
					->where('sc.property_id', $property_id)
					->where('sc.delete_flag', 0)
					->select(DB::raw('sc.*, scb.id as brief_id'))
					->first();
		}			

		if( empty($complaint) )			
			$ret['current_brief_id'] = -1;
		else
			$ret['current_brief_id'] = $complaint->brief_id;

		// get participants list
		$ret['participant_list'] = ComplaintBriefingHistory::getParticipantsList($property_id);

		return Response::json($ret);
	}

	public function getGuestHistory(Request $request)
	{
		$complaint_id = $request->get('complaint_id', '0');
		$guest_id = $request->get('guest_id', '0');
		
		$ret = $this->getGuestHistoryData($complaint_id, $guest_id);

		return Response::json($ret);
	}

	public function getComplaintLogs(Request $request)
	{
		$complaint_id = $request->get('complaint_id', '0');

		$logs = DB::table('services_complaint_log as scl')
			->leftJoin('common_users as cu', 'scl.user_id', '=', 'cu.id')
			->where('scl.complaint_id', $complaint_id)
			->where('scl.sub_id', 0)
			->select(DB::raw('scl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();
		
		$ret['datalist'] = $logs;

		return Response::json($ret);
	}

	public function getComplaintLogsForMobile(Request $request)
	{
		$complaint_id = $request->get('complaint_id', '0');
		$logs = DB::table('services_complaint_log as scl')
			->leftJoin('common_users as cu', 'scl.user_id', '=', 'cu.id')
			->where('scl.complaint_id', $complaint_id)
			->where('scl.sub_id', 0)
			->select(DB::raw('scl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();
		
		$ret['content'] = $logs;
		$ret['code'] = 200;
		return Response::json($ret);
	}


	public function getSubcomplaintLogs(Request $request)
	{
		$id = $request->get('id', '0');

		$logs = DB::table('services_complaint_log as scl')
			->leftJoin('common_users as cu', 'scl.user_id', '=', 'cu.id')
			->leftJoin('services_compensation as comp', 'scl.compensation_id', '=', 'comp.id')
			->leftJoin('common_users as cu3', 'scl.sub_provider_id', '=', 'cu3.id')			
			->where('scl.sub_id', $id)			
			->select(DB::raw('scl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
							comp.compensation as compensation_name, comp.cost, CONCAT_WS(" ", cu3.first_name, cu3.last_name) as sub_provider'))
			->get();
		
		$ret['datalist'] = $logs;

		return Response::json($ret);
	}

	public function getGuestHistoryData($complaint_id, $guest_id) {
		// find privous guest ids
		$guest_profile = DB::table('common_guest_profile as gp')
		    ->join('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
				//	$join->on('gp.profile_id', '=', 'cg.profile_id');
					$join->on('gp.property_id', '=', 'cg.property_id');
				})
			->where('gp.id', $guest_id)
			->select(DB::raw('cg.guest_id, cg.profile_id, gp.property_id, cg.first_name, cg.guest_name'))
			->first();


		$ret = array();
		$query = DB::table('services_complaint_request as sc')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')				
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
				->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				//->leftJoin('common_guest as cg', function($join) {
				// 	$join->on('gp.profile_id', '=', 'cg.profile_id');
				// 	$join->on('sc.property_id', '=', 'cg.property_id'); 
				// })
				->leftJoin('services_complaint_feedback_type as scft', 'sc.feedback_type_id', '=', 'scft.id')
				->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')			
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')	
				->where('sc.delete_flag', 0)		
				->where('sc.id', '!=', $complaint_id);

		if( empty($guest_profile) )		
			$query->where('sc.guest_id', $guest_id);
		else
		{
			// check exist guest uuid
			$advance_info = DB::table('common_guest_advanced_detail')
				->where('profile_id', $guest_profile->profile_id)
				->where('property_id', $guest_profile->property_id)
				->first();

			$guest_ids = [];

			if( empty($advance_info) )
			{
				// find guest with first name and last name
				$guest_list = DB::table('common_guest as cg')
					->where('cg.first_name', $guest_profile->first_name)
					->where('cg.guest_name', $guest_profile->guest_name)
					->where('cg.property_id', $guest_profile->property_id)
					->get();

				foreach($guest_list as $row)	
				{
					$guest_ids[] = $row->profile_id;
				}	
			}
			else
			{
				$advance_list = DB::table('common_guest_advanced_detail')
					->where('uuid', $advance_info->uuid)
					->get();	

				foreach($advance_list as $row)	
				{
					$guest_ids[] = $row->profile_id;
				}		
			}

			if( count($guest_ids) > 1 )
				$query->where('sc.guest_id', $guest_id);	// find with guest profile id
			else
				$query->whereIn('cg.profile_id', $guest_ids);		// find with same first name and last name			
		}
		
		$data_query = clone $query;
		$data_list = $data_query
				->orderBy('created_at', 'desc')
				->select(DB::raw('sc.*, gp.guest_name, cr.room, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name, 
					gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, gp.passport, jr.job_role, (select sum(cost) from services_compensation_request where complaint_id = sc.id) as total_cost, (select count(*) from services_compensation_request where complaint_id = sc.id) as total_count, scft.name as feedback_type, scfs.name as feedback_source'))
				->get();

		foreach($data_list as $key => $row) {
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			if( !empty($info) )
			{
				$data_list[$key]->lgm_name = $info->name;
				$data_list[$key]->lgm_type = $info->type;
				$data_list[$key]->lg_property_id = $info->property_id;
			}
			$data_list[$key]->compen_list = DB::table('services_compensation_request as scmp')
						->leftJoin('services_compensation as scmpt', 'scmp.item_id', '=', 'scmpt.id')
						->leftJoin('common_users as cu1', 'scmp.provider_id', '=', 'cu1.id')
						->where('scmp.complaint_id', $row->id)
						->select(DB::raw('scmpt.compensation, scmp.cost, scmp. comment, CONCAT_WS(" ", cu1.first_name, cu1.last_name) as wholename, scmp.created_at'))
						->get();
			$data_list[$key]->sub_compen = DB::table('services_complaint_sublist_compensation as scsc')
						->leftJoin('services_compensation as scmpt', 'scsc.compensation_id', '=', 'scmpt.id')
						->leftJoin('services_complaint_sublist as scs', function($join) {
									$join->on('scsc.sub_id', '=', 'scs.id');
								})
						->leftJoin('common_users as cu', 'scsc.sub_provider_id', '=', 'cu.id')
						->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
						->where('scs.parent_id', $row->id)
						->where('scs.delete_flag', 0)
						->select(DB::raw('scmpt.compensation, scsc.cost,  CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, scsc.created_at,cd.department'))
						->get();

			
			$sub_compen_total = DB::table('services_complaint_sublist_compensation as scsc')
						->leftJoin('services_complaint_sublist as scs', 'scsc.sub_id', '=', 'scs.id')
						->where('scs.parent_id', $row->id)
						->where('scs.delete_flag', 0)
						->select(DB::raw('sum(scsc.cost)  as total'))
						->first();
			
			$data_list[$key]->sub_compen_list_all = $sub_compen_total->total;
		}

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		return $ret;
	}

	public function getGuestHistoryReportData($complaint_id, $guest_id) {
		// find privous guest ids
		$guest_profile = DB::table('common_guest_profile as gp')
		    ->join('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
				//	$join->on('gp.profile_id', '=', 'cg.profile_id');
					$join->on('gp.property_id', '=', 'cg.property_id');
				})
			->where('gp.id', $guest_id)
			->select(DB::raw('cg.guest_id, cg.profile_id, gp.property_id, cg.first_name, cg.guest_name'))
			->first();


		$ret = array();
		$query = DB::table('services_complaint_request as sc')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')				
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
				->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('services_complaint_feedback_type as scft', 'sc.feedback_type_id', '=', 'scft.id')
				->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')			
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
				->where('sc.delete_flag', 0)			
				->where('sc.id', '!=', $complaint_id);

		if( empty($guest_profile) )		
			$query->where('sc.guest_id', $guest_id);
		else
		{
			// check exist guest uuid
			$advance_info = DB::table('common_guest_advanced_detail')
				->where('profile_id', $guest_profile->profile_id)
				->where('property_id', $guest_profile->property_id)
				->first();

			$guest_ids = [];

			if( empty($advance_info) )
			{
				// find guest with first name and last name
				$guest_list = DB::table('common_guest as cg')
					->where('cg.first_name', $guest_profile->first_name)
					->where('cg.guest_name', $guest_profile->guest_name)
					->where('cg.property_id', $guest_profile->property_id)
					->get();

				foreach($guest_list as $row)	
				{
					$guest_ids[] = $row->profile_id;
				}	
			}
			else
			{
				$advance_list = DB::table('common_guest_advanced_detail')
					->where('uuid', $advance_info->uuid)
					->get();	

				foreach($advance_list as $row)	
				{
					$guest_ids[] = $row->profile_id;
				}		
			}

			if( count($guest_ids) > 1 )
				$query->where('sc.guest_id', $guest_id);	// find with guest profile id
			else
				$query->whereIn('cg.profile_id', $guest_ids);		// find with same first name and last name			
		}
		
		$data_query = clone $query;
		$data_list = $data_query
				->orderBy('created_at', 'desc')
				->select(DB::raw('sc.*, gp.guest_name, cr.room, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name, 
					gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, gp.passport, jr.job_role, (select sum(cost) from services_compensation_request where complaint_id = sc.id) as total_cost, (select count(*) from services_compensation_request where complaint_id = sc.id) as total_count, scft.name as feedback_type, scfs.name as feedback_source'))				
				->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		return $ret;
	}

	public function getStaffList(Request $request)
	{
		$value = '%' . $request->get('value', '') . '%';
		$client_id = $request->get('client_id', 4);

		$ret = DB::table('common_users as cu')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
			->leftJoin('common_department as de','cu.dept_id','=','de.id')
			->leftJoin('common_property as cp','de.property_id','=','cp.id')
			->whereRaw("(CONCAT(cu.first_name, ' ', cu.last_name) like '" . $value . "' or cu.employee_id like '$value')")
			->where('cp.client_id', $client_id)
			->where('cu.deleted', 0)
			->orderBy('wholename','asc')
			->select(DB::raw('cu.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename , de.department, cp.name as property_name'))
			->get();


		return Response::json($ret);
	}

	public function getOccasionList(Request $request)
	{
		$value = '%' . $request->get('value', '') . '%';
		$property_id = $request->get('property_id', 4);

		$ret = DB::table('services_complaint_gr_occasion as gro')
			->leftJoin('common_property as cp','gro.property_id','=','cp.id')
			->where('cp.id', $property_id)
			->whereRaw("gro.occasion like '$value'")
			->select(DB::raw('gro.*'))
			->get();


		return Response::json($ret);
	}
	public function getMainCategoryList(Request $request)
	{		
		$client_id = $request->get('client_id', 4);
		
		$ret = DB::table('services_complaint_maincategory as scmc')
			->leftJoin('common_users as cu', 'scmc.user_id', '=', 'cu.id')
			->leftJoin('services_complaint_type as ct', 'scmc.severity', '=', 'ct.id')
			->leftJoin('common_property as cp','scmc.property_id','=','cp.id')
			->where('cp.client_id', $client_id)
			->select(DB::raw('scmc.*, ct.type, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->orderBy('scmc.name', 'asc')
			->get();	

		return Response::json($ret);
	}
    public function searchCOGuestList(Request $request) {
		$client_id = $request->get('client_id', 4);
		$property_id = $request->get('property_id', 4);		
		$room_id = $request->get('room_id', 0);
		$value = $request->get('value', '');
		//$guest_type = $request->get('guest_type', 'Walk-in');
		

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$start_date = new DateTime($cur_time);
		$start_date->sub(new DateInterval('P2D'));
		$start_date = $start_date->format('Y-m-d');

		// $start_date = '2016-10-08';
		// $cur_date = '2016-10-10';
	

		$dateRange = sprintf("(cg.arrival <= '%s' and cg.departure >= '%s')", $cur_date, $start_date);

			$query = DB::table('common_guest as cg')
				->leftJoin('common_guest_profile as gp', 'cg.guest_id', '=', 'gp.guest_id')
				->where('cg.guest_name', 'like', '%' . $value . '%')
				->where('cg.room_id', $room_id)
				->where('cg.property_id', $property_id)
				->orderBy('cg.departure', 'desc')
				->take(4);
				

			
				// $query->where('cg.checkout_flag', 'checkout')
				// 	->where('cg.departure', '<=', $cur_date)
				// 	->take(4)
				// 	->orderBy('cg.departure', 'desc')
				// 	->orderBy('cg.arrival', 'desc');
			


			$guest_list = $query
				->select(DB::raw('cg.*, gp.mobile, gp.email'))
				->get();	
		

		return Response::json($guest_list);	
	}
	public function searchGuestList(Request $request) {
		$client_id = $request->get('client_id', 4);
		$property_id = $request->get('property_id', 4);		
		$room_id = $request->get('room_id', 0);
		$value = $request->get('value', '');
		$guest_type = $request->get('guest_type', 'Walk-in');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$start_date = new DateTime($cur_time);
		$start_date->sub(new DateInterval('P2D'));
		$start_date = $start_date->format('Y-m-d');

		// $start_date = '2016-10-08';
		// $cur_date = '2016-10-10';

		if( ($guest_type == 'Walk-in' || $guest_type == 'Arrival') || !($room_id > 0) )
		{
			$filter = '%' . $value . '%';
			$guest_list = DB::table('common_guest_profile as gp')				
				->where('gp.client_id', $client_id)
				->where(function ($query) use ($filter) {	// vacation period
					$query->where('gp.guest_name', 'like', $filter)
						->orWhere('gp.email', 'like', $filter)
						->orWhere('gp.mobile', 'like', $filter);				
				})
				->take(20)
				->select(DB::raw('gp.*, gp.fname as first_name, gp.id as guest_id'))
				->get();
		}
		else
		{
			$dateRange = sprintf("(cg.arrival <= '%s' and cg.departure >= '%s')", $cur_date, $start_date);

			$query = DB::table('common_guest as cg')
				->leftJoin('common_guest_profile as gp', 'cg.guest_id', '=', 'gp.guest_id')
				->where('cg.guest_name', 'like', '%' . $value . '%')
				->where('cg.room_id', $room_id)
				->where('cg.property_id', $property_id);
				

			if( $guest_type == 'Checkout')
			{
				$query->where('cg.checkout_flag', 'checkout')
					->where('cg.departure', '<=', $cur_date)
					->take(4)
					->orderBy('cg.departure', 'desc')
					->orderBy('cg.arrival', 'desc');
			}

			if( $guest_type == 'In-House')
			{
				$query->where('cg.checkout_flag', 'checkin' )
						->take(1)
						->orderBy('cg.departure', 'desc')
						->orderBy('cg.arrival', 'desc');
			}

			$guest_list = $query
				->select(DB::raw('cg.*, gp.mobile, gp.email'))
				->get();	
		}

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $guest_list;

		return Response::json($ret);	
	}

	public function findCheckinGuest(Request $request) {
		$room_id = $request->get('room_id', 0);

		$ret = array();

		$ret['code'] = 200;

		$guest = DB::table('common_guest as cg')
			->where('cg.room_id', $room_id)
			->where('cg.checkout_flag', 'checkin')
			->orderBy('cg.departure', 'desc')
			->orderBy('cg.arrival', 'desc')
			->first();

		if( empty($guest) )
		{
			$ret['code'] = 201;
			$ret['messsage'] = 'No guest checkin';
			return Response::json($ret);
		}

		$ret['data'] = $guest;

 		return Response::json($ret);
	}

	public function getCheckoutGuestList(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$room_id = $request->get('room_id', 0);
		$other = $request->get('other', 0);

		date_default_timezone_set(config('app.timezone'));		
		$cur_date = date("Y-m-d");

		$query = DB::table('common_guest as cg')
				// ->leftJoin('common_guest_profile as gp', 'cg.guest_id', '=', 'gp.guest_id')
				->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
				->where('cg.property_id', $property_id)
				->where('cg.departure', '<=', $cur_date)
				->where('cg.checkout_flag', 'checkout');

		$query->where('cg.room_id', $room_id);
		
		if( !empty($other) )
			$query->whereRaw("(cg.guest_name like '%$other%' OR cg.mobile like '%$other%' OR cg.email like '%$other%')");			
		
		$guest_list = $query
			->select(DB::raw('cg.*, cr.room'))
			->get();	

		// $query = DB::table('common_guest_profile as cg');		
		// $guest_list = $query
		// 	->select(DB::raw('cg.*'))
		// 	->get();	

		return Response::json($guest_list);
	}

	public function getCheckoutGuestListForMobile(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$room_id = $request->get('room_id', 0);
		$other = $request->get('other', '');
		date_default_timezone_set(config('app.timezone'));		
		$cur_date = date("Y-m-d");
		$query = DB::table('common_guest as cg')
				// ->leftJoin('common_guest_profile as gp', 'cg.guest_id', '=', 'gp.guest_id')
				->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
				->where('cg.property_id', $property_id)
				->where('cg.departure', '<=', $cur_date)
				->where('cg.checkout_flag', 'checkout');
		$query->where('cg.room_id', $room_id);
		
		if( !empty($other) )
			$query->whereRaw("(cg.guest_name like '%$other%' OR cg.mobile like '%$other%' OR cg.email like '%$other%')");			
		
		$guest_list = $query
			->select(DB::raw('cg.*, cr.room'))
			->get();	
		// $query = DB::table('common_guest_profile as cg');		
		// $guest_list = $query
		// 	->select(DB::raw('cg.*'))
		// 	->get();	
		$ret = [];
		$ret['code'] = 200;
		$ret['content'] = $guest_list;
		return Response::json($ret);
	}

	public function getID(Request $request) {
		$max_id = DB::table('services_complaint_request')
			->select(DB::raw('max(id) as max_id'))
			->first();

		return Response::json($max_id);
	}
	
	public function getfeedbackID(Request $request) {
		$maxf_id = DB::table('services_complaint_gr')
			->select(DB::raw('max(id) as maxf_id'))
			->first();

		return Response::json($maxf_id);
	}

	public function createFeedback(Request $request) {
		$client_id = $request->get('client_id', 4);
		$property_id = $request->get('property_id', 4);
		$loc_id = $request->get('loc_id', 0);
		$room_id = $request->get('room_id', 0);
		$guest_id = $request->get('guest_id', 0);
		$requestor_id = $request->get('requestor_id', 0);
		$comment = $request->get('comment', '');
		$category = $request->get('category');
		$sub_category = $request->get('sub_category');
		$occasion_id = $request->get('occasion_id', 0);
		$guest_name = $request->get('guest_name', '');
		
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		//$created_at = $cur_time;
/*
		$complaint = new ComplaintGR();

		$complaint->property_id = $property_id;
		$complaint->room_id = $room_id;
		$complaint->requestor_id = $requestor_id;
		$complaint->category = $category;
		$complaint->comment = $comment;
		$complaint->occasion_id = $occasion_id;
		$complaint->created_at = $created_at;
	
		*/
		
		
		//$complaint->save();	

		if ($category == 'Room Inspection')
		{
				$profile = new GuestProfile();
	
					$profile->client_id = $client_id;
					$profile->property_id = $property_id;
					$profile->guest_id = 0;
					$profile->guest_name = $guest_name;
					//$profile->fname = $first_name;
					//$profile->mobile = $mobile;
					//$profile->email = $email;
					
					$profile->save();
				DB::table('services_complaint_gr')->insert(['property_id' => $property_id,
					'room_id' => $room_id,
					'loc_id' => $loc_id,
					'guest_id' => $profile->id,
					'requestor_id' => $requestor_id,
					'comment' => $comment,
					'category' => $category,
					'sub_category' => $sub_category,
					'occasion_id' => $occasion_id,
					'created_at' => $cur_time]);
			
					
		}
		else
		{
				DB::table('services_complaint_gr')->insert(['property_id' => $property_id,
					'room_id' => $room_id,
					'loc_id' => $loc_id,
					'guest_id' => $guest_id,
					'requestor_id' => $requestor_id,
					'comment' => $comment,
					'category' => $category,
					'sub_category' => $sub_category,
					'occasion_id' => $occasion_id,
					'created_at' => $cur_time]);
		}

		$maxfb_id = DB::table('services_complaint_gr')
			->select(DB::raw('max(id) as maxfb_id'))
			->first();
		
		//$ret['content'] = $complaint;
		
		return Response::json($maxfb_id);
	}

	private function sendRefreshEvent($property_id, $type, $info, $user_id)
	{
		$data = array();

		$data['property_id'] = $property_id;
		$data['user_id'] = $user_id;
		$data['sub_type'] = $type;
		$data['info'] = $info;

		// send web push
		$message = array();
		$message['type'] = 'complaint';			
		$message['data'] = $data;

		Redis::publish('notify', json_encode($message));		
	}
	
	public function create(Request $request) {
		$client_id = $request->get('client_id', 4);
		$property_id = $request->get('property_id', 4);
		$loc_id = $request->get('loc_id', 0);
		$guest_type = $request->get('guest_type', 'Walk-in');
		$room_id = $request->get('room_id', 0);
		$guest_id = $request->get('guest_id', 0);
		$profile_id = $request->get('profile_id', 0);
		$nationality = $request->get('nationality', 'GB');
		$requestor_id = $request->get('requestor_id', 0);
		$comment = $request->get('comment', '');
		$new_guest = $request->get('new_guest', 0);
		$mobile = $request->get('mobile', 0);
		$email = $request->get('email', '');
		$guest_name = $request->get('guest_name', '');
		$first_name = $request->get('first_name', '');
		$status = $request->get('status', 'Pending');
		$severity = $request->get('severity', 1);
		$initial_response = $request->get('initial_response', '');
		$solution = $request->get('solution', '');
		$housecomplaint_id = $request->get('housecomplaint_id', 0);
		$category_id = $request->get('category_id', 0);
		$subcategory_id = $request->get('subcategory_id', 0);
		$compen_list = $request->get('compen_list', []);
		$incident_time = $request->get('incident_time', 0);
		$feedback_type_id = $request->get('feedback_type_id', 0);
		$feedback_source_id = $request->get('feedback_source_id', 0);
		$compensation_type = $request->get('compensation_type', '');
		$dept_list=$request->get('depts_list', []);
		$path = $request->get('path', '');
		$send_flag= $request->get('send_flag', 0);
		$total = $request->get('total', 0);
		$dept_id = $request->get('dept_id', 0);
		$employee_name = $request->get('employee_name', '');
		$employee_id = $request->get('employee_id', 0);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$created_at = $cur_time;

		$complaint = new ComplaintRequest();

		$complaint->property_id = $property_id;
		$complaint->feedback_type_id = $feedback_type_id;
		$complaint->feedback_source_id = $feedback_source_id;
		$complaint->loc_id = $loc_id;
		$complaint->guest_type = $guest_type;
		$complaint->room_id = $room_id;
		$complaint->requestor_id = $requestor_id;
		$complaint->category_id = $category_id;
		$complaint->subcategory_id = $subcategory_id;
		$complaint->status = $status;
		$complaint->severity = $severity;
		$complaint->initial_response = $initial_response;
		$complaint->compensation_type = $compensation_type;

		$complaint->dept_id = $dept_id;
		$complaint->employee_name = $employee_name;
		$complaint->employee_id = $employee_id;

		$complaint->comment = $comment;
		$complaint->path = $path;
	//	$complaint->incident_time = $cur_date . ' ' . $incident_time;
		$complaint->incident_time = $incident_time;
		$complaint->created_at = $created_at;
		$complaint->compensation_total = $total;
		if( $status == 'Resolved' )
			$complaint->solution = $solution;
		
		// update main category severity
		$main_category = ComplaintMainCategory::find($complaint->category_id);
		if( !empty($main_category) )
		{
			$main_category->severity = $severity;
			$main_category->save();
		}
		
		if( $guest_type == 'Walk-in' || $guest_type == 'Arrival' )
		{
			if( $new_guest == 1 || !($guest_id > 0) )
			{
				$profile = new GuestProfile();

				$profile->client_id = $client_id;
				$profile->property_id = $property_id;
				$profile->guest_id = 0;
				$profile->profile_id = 0;
				$profile->guest_name = $guest_name;
				$profile->fname = $first_name;
				$profile->mobile = $mobile;
				$profile->email = $email;
				
				$profile->save();

				$complaint->guest_id = $profile->id;				
			}
			else
			{
				$profile = GuestProfile::find($guest_id);
				if( !empty($profile) )
				{
					$profile->guest_id = 0;
					$profile->guest_name = $guest_name;
					$profile->fname = $first_name;
					$profile->mobile = $mobile;
					$profile->email = $email;
					$profile->created_at = $cur_time;

					$profile->save();
				}

				$complaint->guest_id = $guest_id;
			}
		}
		else if( $guest_type == 'House Complaint' )
		{
			$complaint->housecomplaint_id = $housecomplaint_id;
		}
		else
		{
			if( $profile_id > 0 )
				$profile = GuestProfile::where('profile_id', $profile_id)->first();
			else	
				$profile = GuestProfile::where('guest_id', $guest_id)->first();

			if( empty($profile) )
				$profile = new GuestProfile();

			$profile->client_id = $client_id;
			$profile->property_id = $property_id;
			$profile->guest_id = $guest_id;
			$profile->guest_name = $guest_name;
			$profile->fname = $first_name;
			$profile->mobile = $mobile;
			$profile->email = $email;
			$profile->profile_id = $profile_id;
			if($nationality != NULL)
			{
				$national = DB::table('common_country')
								->where('code', 'like', $nationality)
								->first();
				if(!empty($national))
					$profile->nationality = $national->id;
				else
					$profile->nationality = NULL;
			}
			else
			{
				$profile->nationality = NULL;
			}
			$profile->created_at = $cur_time;
			$profile->save();

			$complaint->guest_id = $profile->id;
		}

		$complaint->save();

		if( $status == 'Resolved' ){
		
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $complaint->id;
			$complaint_log->sub_id = 0;
			$complaint_log->comment = 'Resolved - Added Primary Resolution';
			$complaint_log->type = C_RESOLVED;
			$complaint_log->user_id = $requestor_id;
		
			$complaint_log->save();
		}

		// add complaint state
		ComplaintMainState::initState($complaint->id);
		ComplaintDivisionMainState::initState($complaint->id);

		$request->merge(['id' => $complaint->id]);
		if(!empty($compen_list))
		{
			$data = array();
			$data['id']=$complaint->id;
			
					
	//		$data['user_id'] = $requestor_id;
			
			foreach($compen_list as $row)
        	{
        		
        		$data['compensation_id']=$row['id'];
				$data['cost']=$row['cost'];
				$data['user_id']=$row['provider_id'];
				$data['dept_id']=$row['dept_id'];
				$data['comment']=$row['comment'];
			//	$data['total']=$row['total'];
        		$this->postCompensationUI($data);
        		
        	}
     	}  	
		
		ComplaintUpdated::modifyByUser($complaint->id, Employee::getUserID($requestor_id));

		if(!empty($dept_list))
		  	$this->sendDept($request);
		  
		$id = $complaint->id;		

		$this->sendRefreshEvent($property_id, 'main_complaint_create', $complaint, Employee::getUserID($requestor_id));

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $complaint->id;

		$ret['message'] = $this->sendNotifyForComplaint($id);
		$ret['content'] = $complaint;
		
		return Response::json($ret);
	}

	public function update(Request $request)
	{
		$user_id = $request->get('user_id', 0);

		$id = $request->get('id', 0);
		$category_id = $request->get('category_id', 0);
		$subcategory_id = $request->get('subcategory_id', 0);
		$severity = $request->get('severity', 1);
		$incident_time = $request->get('incident_time', '');
		$feedback_type = $request->get('feedback_type_id', '');
		$feedback_source = $request->get('feedback_source_id', '');
		$comment = $request->get('comment', '');
		$init_response = $request->get('initial_response', '');

		// update main category severity
		$main_category = ComplaintMainCategory::find($category_id);		
		if( !empty($main_category) )
		{
			$main_category->severity = $severity;
			$main_category->save();
		}

		$complaint = ComplaintRequest::find($id);

		$complaint->category_id = $category_id;
		$complaint->subcategory_id = $subcategory_id;
		$complaint->severity = $severity;
		$complaint->incident_time = $incident_time;
		$complaint->feedback_type_id = $feedback_type;
		$complaint->feedback_source_id = $feedback_source;
		$complaint->comment = $comment;
		$complaint->initial_response = $init_response;

		$complaint->save();

		$ret = array();
		$ret['code'] = 200;
		return Response::json($ret);
	}

	public function updateGuest(Request $request) {
		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 4);
		$client_id = $request->get('client_id', 4);
		$property_id = $request->get('property_id', 4);
		$guest_type = $request->get('guest_type', 'Walk-in');
		$room_id = $request->get('room_id', 0);
		$guest_id = $request->get('guest_id', 0);
		$profile_id = $request->get('profile_id', 0);
		$new_guest = $request->get('new_guest', 0);
		$mobile = $request->get('mobile', 0);
		$email = $request->get('email', '');
		$guest_name = $request->get('guest_name', '');
		$first_name = $request->get('first_name', '');
		$housecomplaint_id = $request->get('housecomplaint_id', 0);
		$nationality = $request->get('nationality', 'GB');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		
		$complaint = ComplaintRequest::find($id);
		
		$complaint->property_id = $property_id;
		$complaint->guest_type = $guest_type;
		$complaint->room_id = $room_id;
		
		if( $guest_type == 'Walk-in' || $guest_type == 'Arrival' )
		{
			if( $new_guest == 1 || !($guest_id > 0) )
			{
				$profile = new GuestProfile();

				$profile->client_id = $client_id;
				$profile->property_id = $property_id;
				$profile->guest_id = 0;
				$profile->profile_id = 0;
				$profile->guest_name = $guest_name;
				$profile->fname = $first_name;
				$profile->mobile = $mobile;
				$profile->email = $email;
				
				$profile->save();

				$complaint->guest_id = $profile->id;				
			}
			else
			{
				$profile = GuestProfile::find($guest_id);
				if( !empty($profile) )
				{
					$profile->guest_id = 0;
					$profile->guest_name = $guest_name;
					$profile->fname = $first_name;
					$profile->mobile = $mobile;
					$profile->email = $email;
					$profile->created_at = $cur_time;

					$profile->save();
				}

				$complaint->guest_id = $guest_id;
			}
		}
		else if( $guest_type == 'House Complaint' )
		{
			$complaint->housecomplaint_id = $housecomplaint_id;
		}
		else
		{
			if( $profile_id > 0 )
				$profile = GuestProfile::where('profile_id', $profile_id)->first();
			else	
				$profile = GuestProfile::where('guest_id', $guest_id)->first();

			if( empty($profile) )
				$profile = new GuestProfile();

			$profile->client_id = $client_id;
			$profile->property_id = $property_id;
			$profile->guest_id = $guest_id;
			$profile->guest_name = $guest_name;
			$profile->fname = $first_name;
			$profile->mobile = $mobile;
			$profile->email = $email;
			$profile->profile_id = $profile_id;
			if($nationality != NULL)
			{
				$national = DB::table('common_country')
								->where('code', 'like', $nationality)
								->first();
				if(!empty($national))
					$profile->nationality = $national->id;
				else
					$profile->nationality = NULL;
			}
			else
			{
				$profile->nationality = NULL;
			}
			$profile->created_at = $cur_time;
			$profile->save();

			$complaint->guest_id = $profile->id;
		}

		$complaint->save();

		ComplaintUpdated::modifyByUser($complaint->id, $user_id);

		$house_complaint = DB::table('common_house_complaints_category')
				->where('id', $housecomplaint_id)
				->first();
		if( !empty($house_complaint) )
			$complaint->house_complaint_name = $house_complaint->name;
		
		$complaint->profile = $profile;	

		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_guest_changed', $complaint, $user_id);

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $complaint->id;

		$ret['content'] = $complaint;
		
		return Response::json($ret);
	}

	// http://192.168.1.253/test/flagguest?property_id=4&guest_id=3036793
	public function testCheckFlagGuest(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$guest_id = $request->get('guest_id', 0);

		$output = $this->checkFlagGuest($property_id, $guest_id);
		echo $output;
	}

	public function checkFlagGuest($property_id, $guest_id) {
		$setting = PropertySetting::getComplaintSetting($property_id);		

		if( $setting['complaint_same_guest_notify'] == 0 )
			return 'Setting is not activate'; 

		$advance_info = GuestAdvancedDetail::where('guest_id', $guest_id)
                    ->where('property_id', $property_id)
                    ->first();

        $guest_ids = [];            
        if( !empty($advance_info) )
        {
        	$advance_list = DB::table('common_guest_advanced_detail')	// find with guest uuid
        		->where('uuid', $advance_info->uuid)	
        		->get();

        	foreach($advance_list as $row)
        	{
        		$guest_ids[] = $row->guest_id;
        	}	
        }            
        else
        {
        	$guest = DB::table('common_guest')
        					->where('guest_id', $guest_id)
		                    ->where('property_id', $property_id)
		                    ->first();

			if( !empty($guest) )
			{
				$guest_list = DB::table('common_guest')		// find with first name and last name
					->where('first_name', $guest->first_name)
					->where('guest_name', $guest->guest_name)
					->where('property_id', $property_id)
					->get();

				foreach($guest_list as $row)
	        	{
	        		$guest_ids[] = $row->guest_id;
	        	}		
			}	                    
        }

        if( count($guest_ids) < 1 )	// empty guest list
        	return 'Empty Guest List';

        $flag_guest_profile = DB::table('common_guest_profile as gp')
        	->leftJoin('common_users as cu', 'gp.flag_by', '=', 'cu.id')
        	->where('gp.check_flag', 1)	
        	->whereIn('gp.guest_id', $guest_ids)
        	->where('gp.property_id', $property_id)
        	->select(DB::raw('gp.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as flag_agent_name'))
        	->first();

        $email_content = '';	

        if( !empty($flag_guest_profile) )
        {
        	$smtp = Functions::getMailSetting($property_id, 'notification_');

        	// find guest
        	$guest = DB::table('common_guest as cg')
        		->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
        		->whereIn('guest_id', $guest_ids)
	        	->where('property_id', $property_id)
	        	->select(DB::raw('cg.*, cr.room'))
	        	->first();

	        // find previous guest with checkin
	        $prev_guest = DB::table('common_guest_log as cgl')
        		->join('common_room as cr', 'cgl.room_id', '=', 'cr.id')
        		->whereIn('guest_id', $guest_ids)
	        	->where('property_id', $property_id)
	        	->where('action', 'checkin')
	        	->select(DB::raw('cgl.*, cr.room'))	  
	        	->orderBy('cgl.id', 'desc')
	        	->skip(1)      		        	
	        	->first();	

	        // find guest's previous complaint history
	        $history = $this->getGuestHistoryData(0, $flag_guest_profile->id);	

        	// find duty manager on shift
			$job_roles = PropertySetting::getJobRoles($property_id);
			$userlist = ShiftGroupMember::getUserlistOnCurrentShift($property_id, $job_roles['dutymanager_job_role'], 0, 0, 0, 0, 0, false, false);
			
			foreach($userlist as $key => $row) {
				$duty_manager = $row;
				
				$info = array();
				$info['first_name'] = $row->first_name;
				$info['guest_name'] = $guest->guest_name;
				$info['flag_by'] = $flag_guest_profile->flag_agent_name;
				$info['flag_date'] = date('Y-m-d', strtotime($flag_guest_profile->flag_at));
				$info['room'] = $guest->room;
				$info['arrival'] = $guest->arrival;
				$info['departure'] = $guest->departure;

				if( !empty($prev_guest) )
				{
					$info['prev_room'] = $prev_guest->room;
					$info['prev_arrival'] = $prev_guest->arrival;
					$info['prev_departure'] = $prev_guest->departure;	
				}
				else
				{
					$info['prev_room'] = '';
					$info['prev_arrival'] = '';
					$info['prev_departure'] = '';		
				}
				
				$info['guest_comment'] = $flag_guest_profile->comment;
				$info['guest_pref'] = $flag_guest_profile->pref;
				$info['history_list'] = $history['datalist'];

				$email_content = view('emails.complaint_guest_flag', ['info' => $info])->render();

				$message = array();
				$message['type'] = 'email';
				$message['to'] = $row->email;
				$message['subject'] = 'Flagged Guest is checkin';
				$message['content'] = $email_content;
				$message['smtp'] = $smtp;

				Redis::publish('notify', json_encode($message));
			}		    
        }	

        return $email_content;
	}

	public function testGuestFlagEmailTemplate(Request $request) {
		$info = array();
		$info['first_name'] = 'Gouresh';
		$info['guest_name'] = 'Mr. Alen';
		$info['flag_by'] = 'Shen Baylon';
		$info['flag_date'] = '28th March 2017';
		$info['room'] = '5000';
		$info['arrival'] = '28th March 2017';
		$info['departure'] = '28th March 2017';
		$info['prev_room'] = '5000';
		$info['prev_arrival'] = '28th March 2017';
		$info['prev_departure'] = '28th March 2017';

		$info['guest_comment'] = '1111';
		$info['guest_pref'] = 'pref';

		$email_content = view('emails.complaint_guest_flag', ['info' => $info])->render();

		echo $email_content;
	}

	public function forward(Request $request) {
		$id = $request->get('id', 0);
		$lg_property_id = $request->get('lg_property_id', 0);
		
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$created_at = $cur_time;

		$old_complaint = ComplaintRequest::find($id);
		$old_complaint->status = C_FORWARDED;
		$old_complaint->updated_at = $cur_time;
		$old_complaint->escalation_flag = 0;
		$old_complaint->timeout_flag = 0;
		$old_complaint->save();

		ComplaintMainState::deleteState($old_complaint->id);

		$complaint = new ComplaintRequest();

		$complaint->property_id = $lg_property_id;
		$complaint->loc_id = $old_complaint->loc_id;
		$complaint->guest_type = $old_complaint->guest_type;
		$complaint->room_id = $old_complaint->room_id;
		$complaint->requestor_id = $old_complaint->requestor_id;
		$complaint->status = C_PENDING;
		$complaint->severity = $old_complaint->severity;
		$complaint->initial_response = $old_complaint->initial_response;
		$complaint->comment = $old_complaint->comment;
		$complaint->forwarded_id = $old_complaint->id;
		$complaint->created_at = $created_at;

		$complaint->save();

		ComplaintMainState::initState($complaint->id);
		ComplaintDivisionMainState::initState($complaint->id);


		$id = $complaint->id;		
		$ret['id'] = $complaint->id;

		$ret['message'] = $this->sendNotifyForComplaint($id);
		
		return Response::json($ret);
	}
	
	public function deptsList(Request $request) {
		$deptlist = array();
		$id = $request->get('id', 0);

		$complaint = DB::table('services_complaint_request as scr')
			->where('scr.delete_flag', 0)
			->select(DB::raw('scr.send_flag, scr.sent_ids'))
			->where('scr.id', $id)
			->first();

		if($complaint->send_flag == 1)
			$dept_ids = explode(",", $complaint->sent_ids);
		else
			$dept_ids = [];

		if( empty($dept_ids) )
			$deptlist = [];
		else
		{
			$deptlist = DB::table('common_department as cd')
									->select(DB::raw('cd.*'))
									->whereIn('cd.id', $dept_ids)
									->get();
		}

		
		$ret['deptlist'] = $deptlist;

		return Response::json($ret);
	}
	
	public function sendDept(Request $request) {
		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);
		$dept_list = $request->get('depts_list',[]);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$created_at = $cur_time;

		if( is_array($dept_list) == false )
		{
			if( empty($dept_list) )
				$dept_list = [];
			else
				$dept_list = explode(',', $dept_list);
		}

		$send_flag = count($dept_list) > 0 ? 1 : 0;

		$complaint = ComplaintRequest::find($id);
		//echo json_encode($id);
		$send_ids = implode(',', $dept_list);
		if($complaint->send_flag == 1)
		{
			$depts = explode(",", $complaint->sent_ids);
			$dept_list = array_diff($dept_list, $depts);
		}

		$complaint->send_flag = $send_flag;		
		$complaint->sent_ids = $send_ids;
		$complaint->save();

		ComplaintUpdated::modifyByUser($complaint->id, $user_id);

		$id = $complaint->id;		
		$ret['id'] = $complaint->id;
		$ret['dept_list'] = $dept_list;

		foreach($dept_list as $dept)
		{
			$ret['message'] = $this->sendNotifyForComplaintDept($id,$dept);
		}

		$complaint->selected_ids = ComplaintRequest::deptList($complaint->id);
		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_dept_changed', $complaint, $user_id);			
		
		return Response::json($ret);
	}

	public function sendDeptForMobile(Request $request) {
		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);
		$dept_list = $request->get('depts_list',[]);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$created_at = $cur_time;
		if( is_array($dept_list) == false )
		{
			if( empty($dept_list) )
				$dept_list = [];
			else
				$dept_list = explode(',', $dept_list);
		}
		$send_flag = count($dept_list) > 0 ? 1 : 0;
		$complaint = ComplaintRequest::find($id);
		//echo json_encode($id);
		$send_ids = implode(',', $dept_list);
		if($complaint->send_flag == 1)
		{
			$depts = explode(",", $complaint->sent_ids);
			$dept_list = array_diff($dept_list, $depts);
		}
		$complaint->send_flag = $send_flag;		
		$complaint->sent_ids = $send_ids;
		$complaint->save();
		ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		$id = $complaint->id;		
		$ret['id'] = $complaint->id;
		$ret['dept_list'] = $dept_list;
		foreach($dept_list as $dept)
		{
			$ret['message'] = $this->sendNotifyForComplaintDept($id,$dept);
		}
		$complaint->selected_ids = ComplaintRequest::deptList($complaint->id);
		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_dept_changed', $complaint, $user_id);			
		
		$ret = [];
		$ret['code'] = 200;
		$ret['content'] = $dept_list;
		return Response::json($ret);
	}

	// http://192.168.1.253/test/notifycomplaint?id=190
	public function testNotifyComplaint(Request $request) {
		$id = $request->get('id', 22);
		$this->sendNotifyForComplaint($id);
	}

	// http://192.168.1.253/test/notifycomplaint?id=190
	public function testComplaintNotifySetting(Request $request) {
		$flag = $this->canReceiveNotification(array(1), 1, 'subcomplaint_complete', 'Informational');
		if( $flag )
			echo 'OK';
		else
			echo 'Cancel';
	}

	private function canReceiveNotification($complaint, $user_id, $action_type, $severity) {
		if( empty($complaint) )
			return false;

		if( $user_id < 1 )
			return false;

		$setting = UserMeta::getComplaintSetting($user_id);

		if( $setting['complaint_notify'] == false )
			return false;

		if( $setting[$action_type] == false )
			return false;

		if( !isset($setting['severity_filter']) || !isset($setting['severity_filter'][$severity]) || $setting['severity_filter'][$severity] == false )
			return false;

		return true;
	}

	private function sendNotifyForComplaint($id) {
		$complaint = DB::table('services_complaint_request as scr')
			->leftJoin('common_employee as ce', 'scr.requestor_id', '=', 'ce.id')
			->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
			->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')
			->leftJoin('common_room as cr', 'scr.room_id', '=', 'cr.id')
			->leftJoin('common_guest_profile as gp', 'scr.guest_id', '=', 'gp.id')
			->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('scr.property_id', '=', 'cg.property_id');
				})
			->join('common_property as cp', 'scr.property_id', '=', 'cp.id')
			->select(DB::raw('scr.*, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severitytype,cg.arrival, cg.departure, cp.name as property_name, cr.room, gp.guest_name'))
			->where('scr.id', $id)
			->where('scr.delete_flag', 0)
			->first();

		if( empty($complaint) )
			return '';

		// find duty manager
		date_default_timezone_set(config('app.timezone'));
		$dayofweek = date('w');

		$date = date('Y-m-d');
		$time = date('H:i:s');
		$datetime = date('Y-m-d H:i:s');

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

		$location_name = '';
		$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($complaint->loc_id);
		if( !empty($info) )
			$location_name = $info->name . ' - ' . $info->type;			

		$message_content = sprintf('There is a new complaint C%05d which has been raised by %s for %s in %s',
										$complaint->id, $complaint->wholename, $location_name, $complaint->property_name);	

		$setting = PropertySetting::getServerConfig(0);

		$complaint->sub_type = 'post';		// for web push
		$complaint->content = $message_content;
		
		try {
			$user_group = CommonUserGroup::getComplaintNotify($complaint->property_id);
			if(!empty($user_group))
			{
				foreach ($user_group as $key => $value) {					
					$this->broadcast($complaint, $setting, $location_name, $value->id);
				}
			}
		} catch (QueryException $e) {
			$errorCode = $e->errorInfo[1];
	 	}
	   
		return $message_content;
	}

	public function broadcast($complaint, $setting, $location_name, $user_group )
	{
		$complaint_setting = PropertySetting::getComplaintSetting($complaint->property_id);
		$job_role_list = explode(",", $complaint_setting['complaint_approval_job_roles']);
			 
		if( ! ($user_group > 0) )
			 return;
			 
		$user_group_members= DB::table('common_users as cu')
				->join('common_user_group_members as cugm', 'cugm.user_id', '=', 'cu.id')
				->join('common_user_group as cug', 'cugm.group_id', '=', 'cug.id')
				->where('cug.id', $user_group)
				->select(DB::raw('cu.*,cu.email as recipient, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,cug.*, cugm.user_id'))
				->get();

		$com_list = DB::table('services_compensation_request as scr')
				->join('services_compensation as comp', 'scr.item_id', '=', 'comp.id')
				->leftJoin('common_users as cu', 'scr.provider_id', '=', 'cu.id')
				->where('scr.complaint_id', $complaint->id)
				->select(DB::raw('scr.*, comp.compensation as item_name ,CONCAT_WS(" ", cu.first_name, cu.last_name) as provider'))
				->get();

		$currency = DB::table('property_setting as ps')
						->select(DB::raw('ps.value'))
						->where('ps.settings_key', 'currency')
						->first();

		if(!empty($user_group_members))
		{
			$send_mode = $user_group_members[0]->group_notification_type;
			
			foreach($user_group_members as $key => $value)
			{
				$duty_manager = $value;
				if($send_mode == 'email')	
				{
					$info = array();
					$info['wholename'] = $duty_manager->first_name;
					$info['user_id']= $duty_manager->user_id;
					$info['dept_name'] = $complaint->property_name;
					$info['location'] = $location_name;
					$info['raised_by'] = $complaint->wholename;
					$info['comment'] = $complaint->comment;
					$info['severity'] = $complaint->severitytype;
					$info['room'] = $complaint->room;
					$info['guest_name'] = $complaint->guest_name;
					$info['arrival'] = $complaint->arrival;
					$info['departure'] = $complaint->departure;
					$info['guest_type'] = $complaint->guest_type;
					$info['ip'] = $setting['public_url'];
					$info['id'] = $complaint->id;
					$info['intial_response'] = $complaint->initial_response;
					$info['approve_reject_flag'] = in_array($duty_manager->job_role_id, $job_role_list);
					$info['comp_list'] = $com_list;
					$info['currency'] = $currency->value;
					$info['status'] = $complaint->status;
					$info['resolution'] = $complaint->solution;
					
					$complaint->subject = sprintf('F%05d: New %s Complaint Raised', $complaint->id, $complaint->severitytype);
					$complaint->email_content = view('emails.complaint_create', ['info' => $info])->render();
					
					if ($complaint->status == C_RESOLVED && $complaint->closed_flag == 0){
						$complaint->subject = sprintf('F%05d: Complaint %s', $complaint->id, $complaint->status);
						$complaint->email_content = view('emails.complaint_resolved', ['info' => $info])->render();
					}

					if ($complaint->status == C_RESOLVED && $complaint->closed_flag == 1){
						$complaint->subject = sprintf('F%05d: Complaint Closed', $complaint->id);
						$complaint->email_content = view('emails.complaint_closed', ['info' => $info])->render();
					}
				}

				$complaint->to = $duty_manager->user_id;
				$webpush_flag = $key == 0;
				$notify_flag = $this->canReceiveNotification($complaint, $duty_manager->user_id, 'complaint_create', $complaint->severitytype);	
				$this->sendComplaintNotification($complaint->property_id, $complaint->content, $complaint->comment, $complaint, $duty_manager->recipient, $duty_manager->mobile, $duty_manager->fcm_key, $webpush_flag, $notify_flag, $send_mode );			
			}
		}

	}

	public function sendComplaintNotification($property_id, $subject, $content, $data, $email, $mobile, $pushkey, $webpush_flag = true, $notify_flag = true,$alarm_mode ) {
		$complaint_setting = PropertySetting::getComplaintSetting($property_id);

		// check notify mode(email, sms, mobile push)
		if(empty($alarm_mode))
			$alarm_mode = $complaint_setting['complaint_notify_mode'];

		$email_mode = false;
		$sms_mode = false;
		$webapp_mode = false;	
		$push_mode = false;	
		if (strpos($alarm_mode, 'email') !== false) {
		    $email_mode = true;
		}

		if ((strpos($alarm_mode, 'sms') !== false)||(strpos($alarm_mode, 'SMS') !== false)) {
		    $sms_mode = true;
		}

		if (strpos($alarm_mode, 'webapp') !== false) {
		    $webapp_mode = true;
		}
		if (strpos($alarm_mode, 'Mobile') !== false) {
		    $push_mode = true;
		}

		if( $email_mode == true && $notify_flag == true )
		{
			$smtp = Functions::getMailSetting($property_id, 'notification_');

			$message = array();
			$message['type'] = 'email';

			$message['to'] = $email;
			if( !empty($data->subject) )
				$message['subject'] = $data->subject;
			else
				$message['subject'] = $subject;

			if( !empty($data->email_content) )
				$message['content'] = $data->email_content;
			else
				$message['content'] = $content;

			$message['smtp'] = $smtp;

			Redis::publish('notify', json_encode($message));
		}

		if( $sms_mode == true && $notify_flag == true)
		{
			// send sms
			$message = array();
			$message['type'] = 'sms';

			$message['to'] = $mobile;
			$message['content'] = $subject;

			Redis::publish('notify', json_encode($message));	
		}

		if( $webapp_mode == true && $webpush_flag == true )
		{
			// send web push
			$message = array();
			$message['type'] = 'complaint';			
			$message['data'] = $data;

			Redis::publish('notify', json_encode($message));	
		}
		if( $push_mode == true && $notify_flag == true)
		{
			// send Mobile push
			$user = DB::table('common_users as cu')
				->where('cu.id', $data->to)
				->select(DB::raw('cu.*'))
				->first();
		if (empty($user))
			return;

			$payload = array();
			$payload['broadcast_flag']=0;
			$payload['task_id'] = 0;
			$payload['table_id'] = 0;
			$payload['table_name'] = '';
			$payload['type'] = 'New Complaint Raised';
			$payload['header'] = 'Feedback';

			$payload['ack'] = 0;
			$payload['property_id'] = $property_id;
			$payload['notify_type'] = 'Complaint';
			$payload['notify_id'] = 0;			
		
			$result = Functions::sendPushMessgeToDeviceWithRedisNodejs(
					$user,0, $payload['type'], $subject, $payload
			);	
		}
	}

	public function getGuestList(Request $request) {
		$property_id = $request->get('property_id', 4);	
		$value = $request->get('value', '');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$start_date = new DateTime($cur_time);
		$start_date->sub(new DateInterval('P2D'));
		$start_date = $start_date->format('Y-m-d');

		// $start_date = '2016-10-08';
		// $cur_date = '2016-10-10';

		$dateRange = sprintf("(cg.arrival between '%s' and '%s' or cg.departure between '%s' and '%s')", $start_date, $cur_date, $start_date, $cur_date);

		$room_id = $request->get('room_id');

		$guest_list = DB::table('common_guest as cg')
			->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->where('cb.property_id', $property_id)
			->where('cg.guest_name', 'like', '%' . $value . '%')
			// ->whereRaw($dateRange)
			->get();

		return Response::json($guest_list);	
	}

	public function saveGuestProfile(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 1);
		$guest_id = $request->get('guest_id', 1);
		$user_id = $request->get('user_id', 0);

		$profile = GuestProfile::find($guest_id);

		if( empty($profile) ) 
			$profile = new GuestProfile();		

		$profile->id = $guest_id;
		$profile->mobile = $request->get('mobile', '');
		$profile->phone = $request->get('phone', '');
		$profile->email = $request->get('email', '');
		$profile->address = $request->get('address', '');
		$profile->gender = $request->get('gender', '');
		$profile->nationality = $request->get('nationality', '');
		$profile->passport = $request->get('passport', '');
		$profile->created_at = $cur_time;

		$profile->save();

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->guest_id = $guest_id;
			$complaint->save();
			
			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		return Response::json($profile);
	}

	public function flagGuest(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 1);
		$guest_id = $request->get('guest_id', 1);
		$user_id = $request->get('user_id', 0);

		$profile = GuestProfile::find($guest_id);

		if( empty($profile) ) 
			$profile = new GuestProfile();		

		$profile->id = $guest_id;
		$profile->check_flag = 1;
		$profile->comment = $request->get('guest_comment', '');
		$profile->pref = $request->get('pref', '');
		$profile->flag_by = $user_id;
		$profile->flat_at = $cur_time;
		
		$profile->save();

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->guest_id = $guest_id;
			$complaint->save();
			
			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		return Response::json($profile);
	}

	public function acknowledge(Request $request) {
		$id = $request->get('id', 0);
		$status = $request->get('status', C_ACK);
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->status = C_ACK;
			$complaint->save();

			ComplaintMainState::initState($complaint->id);
			ComplaintDivisionMainState::initState($complaint->id);
		}
		
		
		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = 'Acknowledged - Via Web App';
		$complaint_log->type = C_ACK;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $complaint;
 
		return Response::json($ret);
	}

	public function reject(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->status = C_REJECTED;
			$complaint->solution = $comment;
			$complaint->updated_at = $cur_time;			
			$complaint->save();

			ComplaintMainState::initState($complaint->id);
			ComplaintDivisionMainState::initState($complaint->id);

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}
		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = 'Rejected - Via Web App';
		$complaint_log->type = C_REJECTED;
		$complaint_log->user_id = $user_id;

		$complaint_log->save();


		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $complaint;
 
		return Response::json($ret);
	}

	public function resolve(Request $request) {		
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$status = $request->get('status', C_REJECTED);
		$solution = $request->get('solution', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->solution = $solution;
			$complaint->status = C_RESOLVED;
			$complaint->updated_at = $cur_time;
			$complaint->save();

			DB::table('services_complaint_main_state')
				->where('complaint_id', $complaint->id)
				->delete();	

			DB::table('services_complaint_division_main_state')
				->where('complaint_id', $complaint->id)
				->delete();	

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = 'Resolved - Added Primary Resolution';
		$complaint_log->type = C_RESOLVED;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = $this->sendNotifyForComplaint($id);
		$ret['content'] = $complaint;

		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_status_changed', $complaint, $user_id);
 
		return Response::json($ret);
	}

	public function close(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->closed_flag = 1;
			$complaint->closed_comment = $comment;
			$complaint->closed_time = $cur_time; 
			$complaint->save();

			DB::table('services_complaint_main_state')
				->where('complaint_id', $complaint->id)
				->delete();	

			DB::table('services_complaint_division_main_state')
				->where('complaint_id', $complaint->id)
				->delete();	

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = 'Closed - ' . $comment;
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = $this->sendNotifyForComplaint($id);
		$ret['content'] = $complaint;

		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_status_changed', $complaint, $user_id);
 
		return Response::json($ret);
	}

	public function reopen(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->closed_flag = 0;			
			$complaint->save();

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = 'Reopen Complaint'.': '.$comment;
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $complaint;
 
		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_status_changed', $complaint, $user_id);

		return Response::json($ret);
	}

	public function repending(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->status = C_ACK;	
			$complaint->save();

			ComplaintMainState::initState($complaint->id);
			ComplaintDivisionMainState::initState($complaint->id);

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $id;
		$complaint_log->sub_id = 0;

		$complaint_log->comment = 'Revert Resolved'.': '.$comment;
		

		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_status_changed', $complaint, $user_id);


		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $complaint;
 
		return Response::json($ret);
	}

	public function unresolve(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->closed_flag = 1;
			$complaint->closed_comment = $comment;
			$complaint->closed_time = $cur_time; 
			$complaint->status = C_UNRESOLVED;				
			$complaint->save();

			ComplaintMainState::initState($complaint->id);
			ComplaintDivisionMainState::initState($complaint->id);

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_status_changed', $complaint, $user_id);

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $complaint;

		return Response::json($ret);
	}

	public function delete(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->delete_flag = 1;
			
			$complaint->save();

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);

			$this->sendRefreshEvent($complaint->property_id, 'main_complaint_delete', $complaint, $user_id);
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $id;
		$complaint_log->sub_id = 0;

		$complaint_log->comment = 'Deleted'.': '.$comment;
		

		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $complaint;

 
		return Response::json($ret);
	}

	public function deleteSubcomplaint(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$parent_id = $request->get('parent_id',0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($parent_id);
		$sub_complaint = DB::table('services_complaint_sublist')->where('id',$id)->first();
		if( !empty($sub_complaint) )
		{
			$subcomplaint_total = DB::table('services_complaint_sublist_compensation as scsc')
					->join('services_complaint_sublist as scs', 'scsc.sub_id', '=', 'scs.id')
					->where('scs.parent_id', $parent_id)
					->where('scs.id',$id)
					->select(DB::raw('sum(scsc.cost)  as total'))
					->first(); 
			$complaint->subcomp_total = $complaint->subcomp_total - $subcomplaint_total->total;
			$complaint->save();	
			DB::table('services_complaint_sublist')->where('id',$id)->update(['delete_flag' => 1]);


		
		//	ComplaintUpdated::modifyByUser($complaint->id, $user_id);

		//	$this->sendRefreshEvent($complaint->property_id, 'main_complaint_delete', $complaint, $user_id);
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $parent_id;
		$complaint_log->sub_id = $id;

		$complaint_log->comment = 'Deleted'.': '.$comment;
		

		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $sub_complaint;

 
		return Response::json($ret);
	}


	public function changeSeverity(Request $request) {
		$id = $request->get('id', 0);
		$category_id = $request->get('category_id', 0);
		$severity = $request->get('severity', 1);
		$user_id = $request->get('user_id', 0);

		// update main category severity
		$main_category = ComplaintMainCategory::find($category_id);		
		if( !empty($main_category) )
		{
			$main_category->severity = $severity;
			$main_category->save();
		}

		$complaint = ComplaintRequest::find($id);
		if( empty($complaint) )
		{
			$ret = array();

			$ret['code'] = 201;
			$ret['message'] = '';	

			return Response::json($ret);
		}

		if( !empty($complaint) )
		{
			$complaint->severity = $severity;
			$complaint->save();

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		

		$severity_item = DB::table('services_complaint_type')
			->where('id', $severity)
			->first();

		if( !empty($severity_item) && $id > 0)
		{
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $id;
			$complaint_log->sub_id = 0;
			$complaint_log->comment = 'Severity - (' . $severity_item->type . ')';
			$complaint_log->type = 0;
			$complaint_log->user_id = $user_id;
			
			$complaint_log->save();	
		}	

		$complaint->severity_name = $severity_item->type;
		$this->sendRefreshEvent($complaint->property_id, 'main_severity_changed', $complaint, $user_id);

		$ret = array();

		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $complaint;

		return Response::json($ret);
	}

	public function changeLocation(Request $request) {
		$id = $request->get('id', 0);
		$location = $request->get('location', '');
		$loc_id = $request->get('loc_id', 0);
		$user_id = $request->get('user_id', 0);

		

		$complaint = ComplaintRequest::find($id);
		if( empty($complaint) )
		{
			$ret = array();

			$ret['code'] = 201;
			$ret['message'] = '';	

			return Response::json($ret);
		}

		if( !empty($complaint) )
		{
			$complaint->loc_id = $loc_id;
			$complaint->save();

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		

		

		if( $id > 0)
		{
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $id;
			$complaint_log->sub_id = 0;
			$complaint_log->comment = 'Location - (' . $location . ')';
			$complaint_log->type = 0;
			$complaint_log->user_id = $user_id;
			
			$complaint_log->save();	
		}	

		$complaint->location = $location;
		$this->sendRefreshEvent($complaint->property_id, 'main_location_changed', $complaint, $user_id);

		$ret = array();

		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $complaint;

		return Response::json($ret);
		
	}

	public function changeMainCategory(Request $request) {
		$id = $request->get('id', 0);
		$category_id = $request->get('category_id', 0);
		$user_id = $request->get('user_id', 0);
		
		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->category_id = $category_id;
			$complaint->save();

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$category = DB::table('services_complaint_maincategory')
			->where('id', $category_id)
			->first();

		if( !empty($category) )
		{
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $id;
			$complaint_log->sub_id = 0;
			$complaint_log->comment = 'Category - (' . $category->name . ')';
			$complaint_log->type = 0;
			$complaint_log->user_id = $user_id;
			
			$complaint_log->save();	
		}	
		
		// get category name 
		$main_category = ComplaintMainCategory::find($complaint->category_id);
		if( empty($main_category) )
			$complaint->category_name = 'Unclassifed';
		else	
			$complaint->category_name = $main_category->name;

		$this->sendRefreshEvent($complaint->property_id, 'main_category_changed', $complaint, $user_id);

		$ret = array();

		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $complaint;

		return Response::json($ret);
	}

	public function changeMainSubCategory(Request $request) {
		$id = $request->get('id', 0);
		$subcategory_id = $request->get('subcategory_id', 0);
		$user_id = $request->get('user_id', 0);
		
		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->subcategory_id = $subcategory_id;
			$complaint->save();

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$subcategory = DB::table('services_complaint_main_subcategory')
			->where('id', $subcategory_id)
			->first();

		if( !empty($subcategory) )
		{
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $id;
			$complaint_log->sub_id = 0;
			$complaint_log->comment = 'Sub Category - (' . $subcategory->name . ')';
			$complaint_log->type = 0;
			$complaint_log->user_id = $user_id;
			
			$complaint_log->save();	
		}	
		
		// get category name 
		$sub_category = ComplaintMainSubCategory::find($complaint->category_id);
		if( empty($sub_category) )
			$complaint->subcategory_name = 'Unclassifed';
		else	
			$complaint->subcategory_name = $sub_category->name;

		$this->sendRefreshEvent($complaint->property_id, 'main_sub_category_changed', $complaint, $user_id);

		$ret = array();

		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $complaint;

		return Response::json($ret);
	}

	public function getComplaintItemList(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$user_id = $request->get('user_id', 0);

		$ret = $this->getComplaintItemListData($user_id, $property_id);

		return Response::json($ret);
	}

	public function getComplaintItemListData($user_id, $property_id)
	{
		$ret = array();
		$ret['list'] = DB::table('services_complaints')			
			->get();
		$ret['types'] = DB::table('services_complaint_type')->get();		

		$ret['com_dept'] = DB::table('services_complaint_dept_pivot as cdp')			
			->join('common_property_department_pivot as pdp', 'cdp.dept_id', '=', 'pdp.dept_id')
			->where('pdp.property_id', $property_id)			
			->select(DB::raw('cdp.*'))
			->get();

		$query = DB::table('common_department as cd')
			->leftJoin('services_complaint_dept_default_assignee as cdda', 'cd.id', '=', 'cdda.id')
			->leftJoin('common_users as cu', 'cdda.user_id', '=', 'cu.id')
			->join('common_property_department_pivot as pdp', 'cd.id', '=', 'pdp.dept_id')
			->where('pdp.property_id', $property_id);

		// check main complaint view permission		
		if( CommonUser::isValidModule($user_id, Config::get('constants.SUBCOMPLAINT_DEPT_CREATE_ALL')) == false )
		{
			$user = CommonUser::find($user_id);				
			$query->where('cd.id', $user->dept_id);
		}			

		$ret['dept'] = $query
			->orderBy('cd.department')
			->select(DB::raw('cd.*, cdda.user_id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();			

		$ret['com_usergroup'] = DB::table('services_complaint_usergroup_pivot as cup')
			->leftJoin('common_user_group as ug', 'cup.usergroup_id', '=', 'ug.id')
			->where('ug.property_id', $property_id)
			->select(DB::raw('cup.*'))
			->get();

		return $ret;
	}

	public function getDeptLocList(Request $request)
	{
		$dept_id = $request->get('dept_id', 0);
		
		$list = DB::table('services_complaint_dept_location_pivot as a')
                        ->join('services_location as sl', 'a.location_id', '=', 'sl.id')
                        ->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
                        ->join('common_property as cp', 'sl.property_id', '=', 'cp.id')
                        ->where('a.dept_id', $dept_id)
                        ->select(DB::raw('sl.*, slt.type, cp.name as property'))
						->get();
		if( count($list) < 1 )
		{
			$list = DB::table('services_location as sl')
						->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->join('services_complaint_dept_location_type_pivot as a', 'a.loc_type_id', '=', 'sl.type_id')
                        ->join('common_property as cp', 'sl.property_id', '=', 'cp.id')
						->where('a.dept_id', $dept_id)
						->groupBy('sl.id')
                        ->select(DB::raw('sl.*, slt.type, cp.name as property'))
						->get();
		}	
		
		if( count($list) < 1 )
		{
			$dept = DB::table('common_department')->where('id', $dept_id)->first();

			if( !empty($dept) )
			{
				$list = DB::table('services_location as sl')
						->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->join('common_property as cp', 'sl.property_id', '=', 'cp.id')						
						->where('sl.property_id', $dept->property_id)
						->select(DB::raw('sl.*, slt.type, cp.name as property'))
						->get();
			}
		}
		
		return Response::json($list);
	}

	public function getDeptLocTypeList(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$dept_ids = $request->get('dept_ids', []);

		$ret = array();

		if( count($dept_ids) < 1 )
		{
			$ret['dept_loc_list'] = DB::table('services_location as sl')                        
                        ->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
                        ->join('common_property as cp', 'sl.property_id', '=', 'cp.id')
                        ->where('sl.property_id', $property_id)
                        ->select(DB::raw('sl.*, slt.type, cp.name as property'))
						->get();

			$ret['dept_loc_type_list'] = DB::table('services_location_type as slt')                        
                        ->select(DB::raw('slt.*'))
						->get();			

			return Response::json($ret);			
		}
		
		// loc list
		$list = DB::table('services_complaint_dept_location_pivot as a')
                        ->join('services_location as sl', 'a.location_id', '=', 'sl.id')
                        ->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
                        ->join('common_property as cp', 'sl.property_id', '=', 'cp.id')
						->whereIn('a.dept_id', $dept_ids)
						->groupBy('sl.id')
                        ->select(DB::raw('sl.*, slt.type, cp.name as property'))
						->get();
		if( count($list) < 1 )
		{
			$list = DB::table('services_location as sl')
						->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->join('services_complaint_dept_location_type_pivot as a', 'a.loc_type_id', '=', 'sl.type_id')
                        ->join('common_property as cp', 'sl.property_id', '=', 'cp.id')
                        ->whereIn('a.dept_id', $dept_ids)						
						->groupBy('sl.id')
                        ->select(DB::raw('sl.*, slt.type, cp.name as property'))
						->get();
		}	
		
		if( count($list) < 1 )
		{
			$property_list = DB::table('common_department as cd')
				->whereIn('cd.id', $dept_ids)
				->groupBy('cd.property_id')
				->select(DB::raw('cd.property_id'))
				->get();

			$property_ids = array_map(function($item) {
				return $item->property_id;
			}, $property_list);	

			$list = DB::table('services_location as sl')
						->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->join('common_property as cp', 'sl.property_id', '=', 'cp.id')						
						->whereIn('sl.property_id', $property_ids)
						->select(DB::raw('sl.*, slt.type, cp.name as property'))
						->get();			
		}

		$ret['dept_loc_list'] = $list;

		// loc_type_list
		$list = DB::table('services_location_type as slt')						
						->join('services_complaint_dept_location_type_pivot as a', 'a.loc_type_id', '=', 'slt.id')                        
                        ->whereIn('a.dept_id', $dept_ids)						
						->groupBy('slt.id')
                        ->select(DB::raw('slt.*'))
						->get();
		if( count($list) < 1 )
		{
			$list = DB::table('services_location_type as slt')                        
				->select(DB::raw('slt.*'))
				->get();			
		}
		$ret['dept_loc_type_list'] = $list;
		
		return Response::json($ret);			
	}


	public function selectAssignee(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$complaint_id = $request->get('complaint_id', 0);
		$usergroup_id = $request->get('usergroup_id', 0);
		$dept_id = $request->get('dept_id', 0);
		$loc_id = $request->get('loc_id', 0);

		$user_list = $this->getAssigneeList($property_id, $complaint_id, $usergroup_id, $dept_id, $loc_id);

		return Response::json($user_list);
	}

	private function getAssigneeList($property_id, $complaint_id, $usergroup_id, $dept_id, $loc_id) 	
	{
		$location = DB::table('services_location_group_members')
			->where('loc_id', $loc_id)
			->first();

		$location_group_id = 0;

		if( !empty($location) )
		{
			$location_group_id = $location->location_grp;
		}	

		$user_list = ShiftGroupMember::getUserlistOnCurrentShift($property_id, 0, 0, 0, $usergroup_id, $location_group_id, 0, false, false);

		return $user_list;	
	}

	public function queryAssigneeList(Request $request) {
		$id = $request->get('id', 0);

		$sub = DB::table('services_complaint_sublist as cs')
			->join('services_complaint_request as cr', 'cs.parent_id', '=', 'cr.id')
			->where('cs.id', $id)
			->where('cs.delete_flag', 0)
			->where('cr.delete_flag', 0)
			->select(DB::raw('cs.*, cr.property_id, cr.loc_id'))
			->first();	

		if( empty($sub) )
			return Response::json(array());

		$property_id = $sub->property_id;
		$complaint_id = $sub->item_id;
		$dept_id = $sub->dept_id;
		$loc_id = $sub->loc_id;

		$user_group = DB::table('services_complaint_usergroup_pivot')
			->where('complaint_id', $sub->item_id)
			->first();

		if( empty($user_group) )
			return Response::json(array());	

		$usergroup_id = $user_group->usergroup_id;
		
		$user_list = $this->getAssigneeList($property_id, $complaint_id, $usergroup_id, $dept_id, $loc_id);

		return Response::json($user_list);
	}



	public function createComplaintItem(Request $request)
	{
		$input = $request->all();

		$id = DB::table('services_complaints')->insertGetId($input);
		$list = DB::table('services_complaints')->get();

		$ret = array();
		$ret['id'] = $id;
		$ret['list'] = $list;

		return Response::json($ret);
	}

	public function saveComplaintDept(Request $request)
	{
		$input = $request->except('property_id');
		$property_id = $request->get('property_id', 4);

		$ret = $this->addComplaintDept($input, $property_id);
		
		return Response::json($ret);
	}

	private function addComplaintDept($input, $property_id)
	{
		$exists = DB::table('services_complaint_dept_pivot')
			->where('complaint_id', $input['complaint_id'] )
			->where('dept_id', $input['dept_id'] )
			->exists();

		$ret = array();

		if( $exists == false )
		{
			$id = DB::table('services_complaint_dept_pivot')->insertGetId($input);	
			$ret['code'] = 200;
		}	
		else
		{
			$ret['code'] = 201;
		}

		$ret['com_dept'] = DB::table('services_complaint_dept_pivot as cdp')
			->join('common_department as cd', 'cdp.dept_id', '=', 'cd.id')
			->where('cd.property_id', $property_id)
			->select(DB::raw('cdp.*'))
			->get();

		return $ret;
	}

	public function assignSubcomplaint(Request $request) {
		$property_id = $request->get('property_id', 4);
		$complaint_id = $request->get('id', 0);
		$sublist = $request->get('subcomplaints', []);
		$user_id = $request->get('user_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ret['code'] = 200;
		$ret['list'] = array();

		foreach($sublist as $row)
		{
			// add new sub complaint
			$sub = new ComplaintSublist();

			$sub->parent_id = $complaint_id;
			$sub->sub_id = ComplaintSublist::getMaxSubID($complaint_id);
			$sub->item_id = $row['complaint_id'];			
			$sub->assignee_id = $row['assignee_id'];
			$sub->dept_id  = $row['dept_id'];
			$sub->submitter_id  = $user_id;
			$sub->status = SC_OPEN;
			$sub->running = 1;

			if(!empty($row['comment'])){
				$sub->comment= $row['comment'];
			}
			if(!empty($row['init_response'])){
				$sub->init_response= $row['init_response'];
			}

			$sub->category_id = $row['category_id'];
			$sub->subcategory_id  = $row['subcategory_id'];

			// $sub->compensation_id = $row['compensation_id'];
			// $sub->cost = $row['cost'];
			// $sub->sub_provider_id = $row['sub_provider_id'];

			$sub->location_id = $row['location_id'];
			$sub->compensation_status = 0;
			$sub->compensation_comment = '';
			$sub->severity = $row['severity'];
			$sub->created_at = $cur_time;

			$sub->save();

			$ret['list'][] = $sub;

			// add comment to sub complaint
			$comment = array();

			$comment['sub_id'] = $sub->id;
			$comment['parent_id'] = 0;
			$comment['user_id'] = $user_id;
			if(!empty($row['comment'])){
				$comment['comment'] = $row['comment'];
			}
			$comment['created_at'] = $cur_time;

			DB::table('services_complaint_sublist_comments')->insertGetId($comment);	

			// $this->createComplaintState($sub);

			// add log to sub complaint

			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $sub->parent_id;
			$complaint_log->sub_id = $sub->id;
			$complaint_log->comment = 'Assign Subcomplaint';
			$complaint_log->type = 0;
			$complaint_log->user_id = $user_id;
			
			$complaint_log->save();

			// add sub complaint state
			ComplaintSublistState::initState($sub->id, 0);	
			ComplaintSublistLocState::initState($sub->id, 0);	

			// increase notification
			CommonUserNotification::addComplaintNotifyCount($sub->assignee_id, $sub->dept_id);
			$this->sendNotificationForSubcomplaint($sub, true);		
		}

		$complaint = ComplaintRequest::find($complaint_id);		

		ComplaintUpdated::modifyByUser($complaint->id, $user_id);

		$complaint->sub_count = DB::table('services_complaint_sublist')
				->where('parent_id', $complaint_id)
				->where('delete_flag', 0)
				->select(DB::raw('sum(status = 2) as completed, sum(status != 4) as total'))				
				->first();
		$this->sendRefreshEvent($complaint->property_id, 'subcomplaint_create', $complaint, $user_id);


		return Response::json($ret);
	}

	public function assignOneSubcomplaint(Request $request) {
		$property_id = $request->get('property_id', 4);
		$complaint_id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);
		$complaint_comment = $request->get('comment', '');
		
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ret['code'] = 200;

		$sub = new ComplaintSublist();

		$sub->parent_id = $complaint_id;
		$sub->sub_id = ComplaintSublist::getMaxSubID($complaint_id);
		$sub->item_id = $request->get('complaint_id', 0);
		$sub->assignee_id = $request->get('assignee_id', 0);
		$sub->dept_id  = $request->get('dept_id', 0);
		$sub->submitter_id  = $user_id;
		$sub->status = SC_OPEN;
		$sub->running = 1;
		$sub->compensation_id = 0;
		$sub->compensation_status = 0;
		$sub->compensation_comment = '';
		$sub->severity = $request->get('severity', 1);
		$sub->created_at = $cur_time;
		$sub->comment = $complaint_comment;

		$sub->save();

		$comment = array();

		$comment['sub_id'] = $sub->id;
		$comment['parent_id'] = 0;
		$comment['user_id'] = $user_id;
		if(!empty($comment)){
			$comment['comment'] = $complaint_comment;
		}
		$comment['created_at'] = $cur_time;

		DB::table('services_complaint_sublist_comments')->insertGetId($comment);	

		// $this->createComplaintState($sub);

		ComplaintSublistState::initState($sub->id, 0);
		ComplaintSublistLocState::initState($sub->id, 0);
		// increase notification
		CommonUserNotification::addComplaintNotifyCount($sub->assignee_id, $sub->dept_id);
		$this->sendNotificationForSubcomplaint($sub, true);			
		
		$complaint = ComplaintRequest::find($sub->parent_id);	
		$complaint->sub_count = DB::table('services_complaint_sublist')
				->where('parent_id', $complaint_id)
				->where('delete_flag', 0)
				->select(DB::raw('sum(status = 2) as completed, sum(status != 4) as total'))				
				->first();
		$this->sendRefreshEvent($complaint->property_id, 'subcomplaint_create', $complaint, $user_id);
		
		return $this->getSubcomplaintList($request);
	}

	public function getComplaintInfo(Request $request) {
		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 0);
		$client_id = $request->get('property_id', 0);

		// sub complaint list
		$sublist = $this->getSubcomplaintListData($id);

		$complaint = ComplaintRequest::find($id);
		$complaint->save();	

		ComplaintUpdated::viewByUser($complaint->id, $user_id);

		$data['sublist'] = $sublist;

		// compensation list
		$comp_list = $this->getCompensationListData($id);

		$data['comp_list'] = $comp_list;

		$category_list = DB::table('services_complaint_maincategory as scmc')
			->leftJoin('common_users as cu', 'scmc.user_id', '=', 'cu.id')
			->leftJoin('services_complaint_type as ct', 'scmc.severity', '=', 'ct.id')
			->where('scmc.property_id', $property_id)
			->select(DB::raw('scmc.*, ct.type, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->orderBy('scmc.name', 'asc')
			->get();	

		$data['category_list'] = $category_list;	

		$ret['code'] = 200;
		$ret['content'] = $data;

		return Response::json($ret);	
	}

	public function getComplaintConfigList(Request $request) {
		$client_id = $request->get('client_id', 4);
		$property_id = $request->get('property_id', 4);
        $model['severity_list'] = DB::table('services_complaint_type')->get();
        $model['division_list'] = DB::table('common_division')->get();
        $model['feedback_type_list'] = DB::table('services_complaint_feedback_type')->get();
        $model['feedback_source_list'] = DB::table('services_complaint_feedback_source')->get();
        $model['category_list'] = DB::table('services_complaint_maincategory as scmc')
            ->leftJoin('common_users as cu', 'scmc.user_id', '=', 'cu.id')
            ->leftJoin('services_complaint_type as ct', 'scmc.severity', '=', 'ct.id')
            ->leftJoin('common_property as cp', 'scmc.property_id', '=', 'cp.id')
            ->leftJoin('common_division as ci', 'scmc.division_id', '=', 'ci.id')
            ->where('cp.client_id', $client_id)
            ->where('scmc.disabled', 0)
            ->select(DB::raw('scmc.*, ct.type, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, ci.division'))
            ->orderBy('scmc.name', 'asc')
            ->get();
        $model['housecomplaint_list'] = DB::table('common_house_complaints_category')
                    ->get();
        $model['complaint_setting'] = PropertySetting::getComplaintSetting($property_id);
        $ret = [];
        $ret['code'] = 200;
        $ret['content'] = $model;
        return Response::json($ret);	
	}

	public function getComplaintDetailInfo(Request $request) {
		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);
		
		// sub complaint list
		$sublist = $this->getSubcomplaintListData($id);

		$complaint = ComplaintRequest::find($id);
		$complaint->save();	

		ComplaintUpdated::viewByUser($complaint->id, $user_id);

		$data['sublist'] = $sublist;

		// compensation list
		$comp_list = $this->getCompensationListData($id);
		$data['comp_list'] = $comp_list;

		$data['comment_list'] = $this->getCommentData($id);

		$ret['code'] = 200;
		$ret['content'] = $data;

		return Response::json($ret);	
	}

	public function getMainSubCategoryList(Request $request)
	{
		$category_id = $request->get('category_id', 0);

		$subcategory_list = DB::table('services_complaint_main_subcategory as scms')
			->leftJoin('common_users as cu', 'scms.user_id', '=', 'cu.id')			
			->where('scms.category_id', $category_id)
			->select(DB::raw('scms.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->orderBy('scms.name', 'asc')
			->where('scms.disabled', 0)
			->get();	

		$ret = array();

		$ret['code'] = 200;	
		$ret['content'] = $subcategory_list;	

		return Response::json($ret);
	}
	
	public function getSubcomplaintList(Request $request) {
		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);

		$sublist = $this->getSubcomplaintListData($id);

		return Response::json($sublist);	
	}


	public function getSubcomplaintListForMobile(Request $request) {
		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);

		$sublist = $this->getSubcomplaintListData($id);

		$complaint = ComplaintRequest::find($id);		
		$complaint->save();	

		ComplaintUpdated::viewByUser($id, $user_id);

		$ret = array();

		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $sublist;
 
		return Response::json($ret);	
	}

	public function getSubcomplaintListData($id) {
		$sublist = DB::table('services_complaint_sublist as cs')
			->leftJoin('services_complaints as sc', 'cs.item_id', '=', 'sc.id')
			->join('common_department as cd', 'cs.dept_id', '=', 'cd.id')
			->leftJoin('common_users as cu', 'cs.assignee_id', '=', 'cu.id')			
			->leftJoin('services_complaint_type as ct', 'cs.severity', '=', 'ct.id')
			->leftJoin('services_compensation as comp', 'cs.compensation_id', '=', 'comp.id')
			->leftJoin('common_users as cu1', 'cs.completed_by', '=', 'cu1.id')
			->leftJoin('common_users as cu2', 'cs.submitter_id', '=', 'cu2.id')
			->leftJoin('common_users as cu3', 'cs.sub_provider_id', '=', 'cu3.id')			
			->leftJoin('services_complaint_category as scc', 'cs.category_id', '=', 'scc.id')	
			->leftJoin('services_complaint_subcategory as scs', 'cs.subcategory_id', '=', 'scs.id')	
			->leftJoin('services_location as sl', 'cs.location_id', '=', 'sl.id')
			->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			->where('cs.parent_id', $id)
			->where('cs.delete_flag',0)
			->select(DB::raw("cs.*, sc.complaint as complaint_name, cd.department, comp.compensation as compensation_name, ct.type, CONCAT_WS(\" \", cu.first_name, cu.last_name) as assignee_name, cd.short_code,
					CONCAT_WS(\" \", cu1.first_name, cu1.last_name) as completed_by_name, 
					CONCAT_WS(\" \", cu2.first_name, cu2.last_name) as created_by, 
					scc.name as category_name, scs.name as subcategory_name,
					sl.name as location_name, slt.type as location_type,
					CONCAT_WS(\" \", cu3.first_name, cu3.last_name) as sub_provider, 
					DATEDIFF(CURTIME(), cs.created_at) as age_days,
					REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sub_id, '0', 'A')
					, '1', 'B')
					, '2', 'C')
					, '3', 'D')
					, '4', 'E')
					, '5', 'F')
					, '6', 'G')
					, '7', 'H')
					, '8', 'I')
					, '9', 'J') as sub_label"))
			->get();


		foreach($sublist as $row) {
			$row->comment_list = $this->getSubcommentsData($row->id);

			$logs = DB::table('services_complaint_log as scl')
							->leftJoin('common_users as cu', 'scl.user_id', '=', 'cu.id')
							->leftJoin('services_compensation as comp', 'scl.compensation_id', '=', 'comp.id')
							->leftJoin('common_users as cu3', 'scl.sub_provider_id', '=', 'cu3.id')			
							->where('scl.sub_id', $row->id)
							->select(DB::raw('scl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
										comp.compensation as compensation_name, comp.cost, CONCAT_WS(" ", cu3.first_name, cu3.last_name) as sub_provider'))
							->get();
			$row->log_list = $logs;				
			$row->compensation_list = ComplaintSublist::getCompensationList($row->id);
		}
	
		return $sublist;	
	}

	public function getCompensationListData($id) {
		$comp_list = DB::table('services_compensation_request as scr')
			->join('services_compensation as sc', 'scr.item_id', '=', 'sc.id')
			->leftJoin('common_users as cu', 'scr.provider_id', '=', 'cu.id')
			->leftJoin('common_department as cd', 'scr.dept_id', '=', 'cd.id')
			->where('scr.complaint_id', $id)
			->select(DB::raw('scr.*, cd.department, sc.compensation as item_name, CONCAT_WS(" ", cu.first_name, cu.last_name) as provider'))
			->get();

		return $comp_list;	
	}

	public function getCompensationType(Request $request)
	{
		$client_id = $request->get('client_id', 4);

		$list = DB::table('services_compensation as sc')
			->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')			
			->where('sc.client_id', $client_id)
			->select(DB::raw('sc.*, cp.name'))
			->get();

		return Response::json($list);
	}


	public function getSubMyList(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', 4);
		$dispatcher = $request->get('dispatcher', 0);		
		$user_id = $request->get('user_id', 0);		 
		$dept_id = $request->get('dept_id', 0);
		

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');				
		$filter = $request->get('filter');
		$flag = $request->get('flag', 0);
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');

		$date = new DateTime($cur_time);
		$date->sub(new DateInterval('P1D'));
		$last_time = $date->format('Y-m-d H:i:s');

		$user = DB::table('common_users as cu')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->where('cu.id', $dispatcher)
			->select(DB::raw('cu.*, jr.manager_flag'))
			->first();

		if( empty($filter) )
		{
			$filter = UserMeta::getSubcomplaintTicketFilter($user_id, [$property_id]);
		}	

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();
		$query = DB::table('services_complaint_sublist as cs')
				->leftJoin('services_complaints as item', 'cs.item_id', '=', 'item.id')
				->leftJoin('common_users as cu1', 'cs.assignee_id', '=', 'cu1.id')				
				->leftJoin('services_complaint_request as sc', 'cs.parent_id', '=', 'sc.id')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')				
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_users as cu2', 'cs.submitter_id', '=', 'cu2.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
				->leftJoin('services_complaint_type as ct', 'cs.severity', '=', 'ct.id')
				->leftJoin('services_complaint_type as ct1', 'sc.severity', '=', 'ct1.id')
//				->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc.property_id', '=', 'cg.property_id');
				})
				->leftJoin('common_vip_codes as vc', 'vc.vip_code', '=', 'cg.vip')
				->leftJoin('services_complaint_feedback_type as scft', 'sc.feedback_type_id', '=', 'scft.id')
				->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
				->leftJoin('services_complaint_category as scc', 'cs.category_id', '=', 'scc.id')	
				->leftJoin('services_complaint_subcategory as scs', 'cs.subcategory_id', '=', 'scs.id')	
				->leftJoin('services_location as sl', 'cs.location_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
				->leftJoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')
				->leftJoin('common_house_complaints_category as hcc', 'sc.housecomplaint_id', '=', 'hcc.id')
				->leftJoin('common_department as cd1', 'cs.reassigne_dept_id', '=', 'cd1.id')
				->where('sc.delete_flag', 0)
				->where('cs.delete_flag',0)
				->where('cs.dept_id', $dept_id); 

		$dept_ids_by_jobrole = CommonUser::getDeptIdsByJobrole($dispatcher);

		// if( $user->manager_flag != 0 )	// manager
		// 	$query->whereIn('cs.dept_id', $dept_ids_by_jobrole);	
		// else 							// general staff
		// 	$query->where('cs.dept_id', $dept_id);


		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));
		switch($filter['ticket']) 
		{
			case 'All Tickets':
				$query->whereRaw(sprintf("DATE(cs.created_at) >= '%s' and DATE(cs.created_at) <= '%s'", $start_date, $end_date));
				break;
			case 'Last 24 Hours':				
				$query->whereBetween('cs.created_at', array($last24, $cur_time));
				break;
			case 'Acknowledged by me':
				$query->whereRaw(sprintf("DATE(cs.created_at) >= '%s' and DATE(cs.created_at) <= '%s'", $start_date, $end_date));
				$query->where('cs.ack_by', $user_id);	
				break;		
		}

		//check service recovery
		$amt=500;
		switch($filter['service_recovery']) 
		{
			
			case 'Less than 500':
				$query->whereBetween('sc.compensation_total', array(1, $amt));
				break;
			case 'Greater than 500':				
				$query->where('sc.compensation_total','>=',$amt);
				break;
			case 'All':
				$query->where('sc.compensation_total','>',0);	
				break;		
		}

		// check status filter
		$query->where(function ($subquery) use ($filter, $dispatcher) {	
			$subquery_flag = false;
			if( $filter['status_filter']['Pending'] )
			{
				$subquery->where('cs.status', 1);
				$subquery_flag = true;
			}
		
			if( $filter['status_filter']['Completed'] )
			{
				$subquery->orWhere('cs.status', 2);
				$subquery_flag = true;
			}

			if( $filter['status_filter']['Re-routing'] )
			{
				$subquery->orWhere('sc.status', 4);
				$subquery_flag = true;
			}

			if( $filter['status_filter']['Flagged'] )
			{
				$subquery->orWhere('sc.status', 'Rejected');
				$subquery_flag = true;
			}

			if( $filter['status_filter']['Flagged'] )
			{
				$subquery->orWhereExists(function ($query) use ($dispatcher) {
	                $query->select(DB::raw(1))
	                      ->from('services_complaint_flag as scf')
	                      ->whereRaw('scf.user_id = ' . $dispatcher . ' and scf.complaint_id = cs.parent_id');
				});								
				$subquery_flag = true;			
			}		

			if( $subquery_flag == false )
				$subquery->whereRaw('1=1');
		});

		// check severity filter
		$query->where(function ($subquery) use ($filter, $user_id) {	
			$severity_ids = [];
			
			foreach($filter['severity_filter'] as $key => $row) {
				if( $row == false )
					continue;

				$severity = DB::table('services_complaint_type')
					->where('type', $key)
					->first();

				if( empty($severity) )
					continue;	

				$severity_ids[] = $severity->id;
			}

			if( count($severity_ids) > 0 )
				$subquery->orWhereIn('cs.severity', $severity_ids);
			else
				$subquery->whereRaw('1=1');
		});

		// check category filter
		$query->where(function ($subquery) use ($filter, $user_id) {	
			$category_ids = [];
			foreach($filter['category_tags'] as $key => $row) {
				if( $row == false )
					continue;

				$category_ids[] = $row['id'];
			}

			if( count($category_ids) > 0 )
				$subquery->orWhereIn('cs.category_id', $category_ids);
			else
				$subquery->whereRaw('1=1');
		});

		// check sub category filter
		$query->where(function ($subquery) use ($filter, $user_id) {	
			$subcategory_ids = [];
			foreach($filter['subcategory_tags'] as $key => $row) {
				if( $row == false )
					continue;

				$subcategory_ids[] = $row['id'];
			}

			if( count($subcategory_ids) > 0 )
				$subquery->orWhereIn('cs.subcategory_id', $subcategory_ids);
			else
				$subquery->whereRaw('1=1');
		});
		$rooms=[];
		// check location filter
		$query->where(function ($subquery) use ($filter, $user_id, &$rooms) {	
			$location_ids = [];
			foreach($filter['location_tags'] as $key => $row) {
				$location_ids[] = $row['id'];				
			}

			if( count($location_ids) > 0 )
				$subquery->orWhereIn('cs.location_id', $location_ids);			
			else
				$subquery->whereRaw('1=1');
		});

		// check location type
		$query->where(function ($subquery) use ($filter, $user_id, &$rooms) {	
			$location_type_ids = [];
			foreach($filter['location_type_tags'] as $key => $row) {
				$location_type_ids[] = $row['id'];				
			}

			if( count($location_type_ids) > 0 )
				$subquery->orWhereIn('sl.type_id', $location_type_ids);			
			else
				$subquery->whereRaw('1=1');
		});

		$data_query = clone $query;
		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw("cs.*,sc.closed_flag, sc.guest_id, sc.guest_type, sc.loc_id, sc.path as main_path, sc.compensation_total, sc.comment as feedback, sc.comment_highlight, sc.initial_response as init_response, sc.response_highlight, item.complaint, gp.guest_name, cr.room, CONCAT_WS(\" \", ce.fname, ce.lname) as wholename, cd.department, CONCAT_WS(\" \", cu1.first_name, cu1.last_name) as assignee_name, jr.job_role, CONCAT_WS(\" \", cu2.first_name, cu2.last_name) as created_by, jr.job_role, cd1.department as reassign_dept, hcc.name as house_complaint_name, 
					(select 1 from services_complaint_flag as scf where scf.user_id = " . $dispatcher . " and scf.complaint_id = cs.parent_id limit 1) as flag,
					(select comment from services_complaint_note as scn where scn.user_id = " . $dispatcher . " and scn.complaint_id = sc.id limit 1) as note_comment, scc.name as category_name, scs.name as subcategory_name,
					sl.name as location_name, slt.type as location_type,  scft.name as feedback_type, scfs.name as feedback_source,
					gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, gp.passport, ct.type as severity_name, ct1.type as main_severity_name, cg.arrival, cg.departure, vc.name as vip, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sub_id, '0', 'A')
						, '1', 'B')
						, '2', 'C')
						, '3', 'D')
						, '4', 'E')
						, '5', 'F')
						, '6', 'G')
						, '7', 'H')
						, '8', 'I')
						, '9', 'J') as sub_label"))
				->skip($skip)->take($pageSize)
				->get();

		foreach($data_list as $key => $row) {			
			$data_list[$key]->compen_list = DB::table('services_compensation_request as scmp')
						->leftJoin('services_compensation as scmpt', 'scmp.item_id', '=', 'scmpt.id')
						->leftJoin('services_complaint_request as sc', 'scmp.complaint_id', '=', 'sc.id')
						->leftJoin('common_users as cu1', 'scmp.provider_id', '=', 'cu1.id')
						->where('scmp.complaint_id', $row->parent_id)
						->where('sc.delete_flag', 0)
						->select(DB::raw('scmpt.compensation, scmp.cost, scmp. comment, CONCAT_WS(" ", cu1.first_name, cu1.last_name) as wholename, scmp.created_at'))
			->get();
			$data_list[$key]->sub_compen = DB::table('services_complaint_sublist_compensation as scsc')
						->leftJoin('services_compensation as scmpt', 'scsc.compensation_id', '=', 'scmpt.id')
						->leftJoin('services_complaint_sublist as scs', function($join) {
									$join->on('scsc.sub_id', '=', 'scs.id');
								})
						->leftJoin('common_users as cu', 'scsc.sub_provider_id', '=', 'cu.id')
						->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
						->where('scs.parent_id', $row->parent_id)
						->where('scs.delete_flag', 0)
						->select(DB::raw('scmpt.compensation, scsc.cost,  CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, scsc.created_at,cd.department'))
						->get();

			$sub_compen_total = DB::table('services_complaint_sublist_compensation as scsc')
						->where('scsc.sub_id', $row->id)
						->select(DB::raw('sum(scsc.cost)  as total'))
						->first();
			$data_list[$key]->sub_compen_list_total = $sub_compen_total->total;
			
			$sub_compen_total = DB::table('services_complaint_sublist_compensation as scsc')
						->leftJoin('services_complaint_sublist as scs', 'scsc.sub_id', '=', 'scs.id')
						->where('scs.parent_id', $row->parent_id)
						->where('scs.delete_flag', 0)
						->select(DB::raw('sum(scsc.cost)  as total'))
						->first();
			
			$data_list[$key]->sub_compen_list_all = $sub_compen_total->total;
		}

	
		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;
		$ret['filter'] = $filter;

		CommonUserNotification::setNotifyCount($dispatcher, 'complaint_cnt', 0);

		// save filter
		UserMeta::saveSubcomplaintTicketFilter($user_id, $filter);

		return Response::json($ret);	
	}
	public function getSubMyListfromMobile(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', 4);
		$dispatcher = $request->get('dispatcher', 0);
		$dept_id = $request->get('dept_id', 0);


		$filter = $request->get('filter', "");
		$status_filter = [];
		if(strlen($filter) > 0){
			$status_filter = explode(",", $filter);
		}

		$date = new DateTime($cur_time);
		$date->sub(new DateInterval('P1D'));
		$last_time = $date->format('Y-m-d H:i:s');

		$user = DB::table('common_users as cu')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->where('cu.id', $dispatcher)
				->select(DB::raw('cu.*, jr.manager_flag'))
				->first();

		$ret = array();
		$query = DB::table('services_complaint_sublist as cs')
				->leftJoin('services_complaints as item', 'cs.item_id', '=', 'item.id')
				->leftJoin('common_users as cu1', 'cs.assignee_id', '=', 'cu1.id')
				->leftJoin('services_complaint_request as sc', 'cs.parent_id', '=', 'sc.id')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_users as cu2', 'cs.submitter_id', '=', 'cu2.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
				->leftJoin('services_complaint_type as ct', 'cs.severity', '=', 'ct.id')
				->leftJoin('services_complaint_type as ct1', 'sc.severity', '=', 'ct1.id')
//				->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc.property_id', '=', 'cg.property_id');
				})
				->leftJoin('services_complaint_category as scc', 'cs.category_id', '=', 'scc.id')
				->leftJoin('services_complaint_subcategory as scs', 'cs.subcategory_id', '=', 'scs.id')
				->leftJoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')
				->leftJoin('common_house_complaints_category as hcc', 'sc.housecomplaint_id', '=', 'hcc.id')
				->leftJoin('common_department as cd1', 'cs.reassigne_dept_id', '=', 'cd1.id')
				->where('sc.delete_flag', 0)
				->where('cs.delete_flag', 0);

		$dept_ids_by_jobrole = CommonUser::getDeptIdsByJobrole($dispatcher);

		if( $user->manager_flag != 0 )	// manager
			$query->whereIn('cs.dept_id', $dept_ids_by_jobrole);
		else 							// general staff
			$query->where('cs.assignee_id', $dispatcher);


		/*switch($filter['ticket'])
		{
			case 'Acknowledged by me':
				$query->where('cs.ack_by', $dispatcher);
				break;
		}*/


		// check status filter
		$query->where(function ($subquery) use ($status_filter, $dispatcher) {
			for($i = 0; $i < count($status_filter); $i++) {
				if ($status_filter[$i] == 'Pending')
					$subquery->where('cs.status', 1);

				if ($status_filter[$i] == 'Completed')
					$subquery->orWhere('cs.status', 2);

				if ($status_filter[$i] == 'Re-routing')
					$subquery->orWhere('sc.status', 4);

				if ($status_filter[$i] == 'Flagged')
					$subquery->orWhere('sc.status', 'Rejected');

				if ($status_filter[$i] == 'Flagged') {
					$subquery->orWhereExists(function ($query) use ($dispatcher) {
						$query->select(DB::raw(1))
								->from('services_complaint_flag as scf')
								->whereRaw('scf.user_id = ' . $dispatcher . ' and scf.complaint_id = cs.parent_id');
					});
				}
			}
		});
/*
		// check severity filter
		$query->where(function ($subquery) use ($filter, $user_id) {
			$severity_ids = [];

			foreach($filter['severity_filter'] as $key => $row) {
				if( $row == false )
					continue;

				$severity = DB::table('services_complaint_type')
						->where('type', $key)
						->first();

				if( empty($severity) )
					continue;

				$severity_ids[] = $severity->id;
			}

			if( count($severity_ids) > 0 )
			{
				$subquery->orWhereIn('cs.severity', $severity_ids);
			}
		});*/
		$rooms=[];

		$data_query = clone $query;
		$data_list = $data_query
				->orderBy("id", "desc")
				->select(DB::raw("cs.*, sc.guest_id, sc.guest_type, sc.loc_id, sc.path as main_path, sc.compensation_total, sc.comment, sc.comment_highlight, sc.initial_response, sc.response_highlight, item.complaint, gp.guest_name, cr.room, CONCAT_WS(\" \", ce.fname, ce.lname) as wholename, cd.department, CONCAT_WS(\" \", cu1.first_name, cu1.last_name) as assignee_name, jr.job_role, CONCAT_WS(\" \", cu2.first_name, cu2.last_name) as created_by, jr.job_role, cd1.department as reassign_dept, hcc.name as house_complaint_name,
					(select 1 from services_complaint_flag as scf where scf.user_id = " . $dispatcher . " and scf.complaint_id = cs.parent_id limit 1) as flag,
					(select comment from services_complaint_note as scn where scn.user_id = " . $dispatcher . " and scn.complaint_id = sc.id limit 1) as note_comment, scc.name as category_name, scs.name as subcategory_name,
					gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, gp.passport, ct.type as severity_name, ct1.type as main_severity_name, cg.arrival, cg.departure, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sub_id, '0', 'A')
						, '1', 'B')
						, '2', 'C')
						, '3', 'D')
						, '4', 'E')
						, '5', 'F')
						, '6', 'G')
						, '7', 'H')
						, '8', 'I')
						, '9', 'J') as sub_label"))
				->get();

		foreach($data_list as $key => $row) {
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			if( !empty($info) )
			{
				$data_list[$key]->lgm_name = $info->name;
				$data_list[$key]->lgm_type = $info->type;
			}
			$data_list[$key]->compen_list = DB::table('services_compensation_request as scmp')
					->leftJoin('services_compensation as scmpt', 'scmp.item_id', '=', 'scmpt.id')
					->where('scmp.complaint_id', $row->parent_id)
					->select(DB::raw('scmpt.compensation, scmp.cost, scmp. comment'))
					->get();
			// $exists = ComplaintFlag::where('user_id', $dispatcher)
			// 	->where('complaint_id', $row->parent_id)
			// 	->exists();
			// $data_list[$key]->flag = $exists ? 1 : 0;
		}

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['code'] = 200;
		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		CommonUserNotification::setNotifyCount($dispatcher, 'complaint_cnt', 0);

		return Response::json($ret);
	}

	private function removeEscalationState($sub_id)
	{
		DB::table('services_complaint_sublist_state')
			->where('sub_id', $sub_id)
			->delete();	

		DB::table('services_complaint_sublist_loc_state')
			->where('sub_id', $sub_id)
			->delete();		

		DB::table('services_complaint_sublist_reopen_state')
			->where('sub_id', $sub_id)
			->delete();		
	}

	public function completeSubComplaint(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', 4);
		$id = $request->get('id', 0);
		$comment = $request->get('comment', '');	
		$user_id = $request->get('user_id', 0);	

		$sub = ComplaintSublist::find($id);

		$sub->resolution = $comment;
		$sub->status = SC_COMPLETE;
		$sub->completed_at = $cur_time;
		$sub->completed_by = $user_id;
		$sub->in_progress = 0;
		$sub->save();

		$complaint = ComplaintRequest::find($sub->parent_id);
		if( !empty($complaint) )
		{	
			$complaint->save();		
			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $sub->parent_id;
		$complaint_log->sub_id = $id;
		$complaint_log->comment = 'Complete Subcomplaint';
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();
		
		// DB::table('services_complaint_state')
		// 	->where('task_id', $id)
		// 	->delete();

		$this->removeEscalationState($id);

		$this->sendNotifyForSubcomplaint($sub);	
		
		$this->sendNotifyAllCompleteSubcomplaint($sub);	
		$this->sendSubcomplaintStatusChangeEvent($sub, $user_id);

		return Response::json($sub);
	}

	public function cancelSubComplaint(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', 4);
		$id = $request->get('id', 0);
		$comment = $request->get('comment', '');	
		$user_id = $request->get('user_id', 0);	

		$sub = ComplaintSublist::find($id);

		$sub->comment = $comment;
		$sub->status = SC_CANCELED;
		$sub->in_progress = 0;
		$sub->save();

		$complaint = ComplaintRequest::find($sub->parent_id);
		if( !empty($complaint) )
		{			
			$complaint->save();		
			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $sub->parent_id;
		$complaint_log->sub_id = $id;
		$complaint_log->comment = 'Cancel Subcomplaint';
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();
		
		// DB::table('services_complaint_state')
		// 	->where('task_id', $id)
		// 	->delete();

		$this->removeEscalationState($id);

		$sub->canceled_by = $user_id;	

		$this->sendNotifyForSubcomplaint($sub);	

		$this->sendSubcomplaintStatusChangeEvent($sub, $user_id);

		return Response::json($sub);
	}

	public function sendSubcomplaintStatusChangeEvent($sub, $user_id)
	{
		$complaint = ComplaintRequest::find($sub->parent_id);

		// send refresh event
		$complaint->sub = $sub;
		$complaint->sub_count = DB::table('services_complaint_sublist')
				->where('parent_id', $complaint->id)
				->where('delete_flag', 0)
				->select(DB::raw('sum(status = 2) as completed, sum(status != 4) as total'))				
				->first();
		$this->sendRefreshEvent($complaint->property_id, 'subcomplaint_status_changed', $complaint, $user_id);
	}

	public function ackSubComplaint(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);	

		$sub = ComplaintSublist::find($id);

		$sub->ack = 1;
		$sub->ack_by = $user_id;
		$sub->save();

		$complaint = ComplaintRequest::find($sub->parent_id);
		if( !empty($complaint) )
		{	
			$complaint->save();		
			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $sub->parent_id;
		$complaint_log->sub_id = $id;
		$complaint_log->comment = 'Acknowledge Subcomplaint';
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		DB::table('services_complaint_sublist_reopen_state')
			->where('sub_id', $id)
			->delete();	

		$this->sendSubcomplaintStatusChangeEvent($sub, $user_id);
		
		return Response::json($sub);
	}

	public function inprogressSubComplaint(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);	

		$sub = ComplaintSublist::find($id);

		$sub->in_progress = 1;
		$sub->save();

		$complaint = ComplaintRequest::find($sub->parent_id);
		if( !empty($complaint) )
		{	
			$complaint->save();		
			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $sub->parent_id;
		$complaint_log->sub_id = $id;
		$complaint_log->comment = 'Put In Progress Subcomplaint';
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		DB::table('services_complaint_sublist_reopen_state')
			->where('sub_id', $id)
			->delete();	

		$this->sendSubcomplaintStatusChangeEvent($sub, $user_id);
		
		return Response::json($sub);
	}

	private function sendNotifyForSubcomplaint($sub) {
		$complaint = DB::table('services_complaint_request as scr')
			->leftJoin('common_employee as ce', 'scr.requestor_id', '=', 'ce.id')
			->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')		
			->leftJoin('common_property as cp', 'scr.property_id', '=', 'cp.id')
			->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')
			->select(DB::raw('scr.*, ct.type, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, cp.name as property_name'))
			->where('scr.id', $sub->parent_id)
			->where('scr.delete_flag', 0)
			->first();

		if( empty($complaint) )
			return $complaint->id;

		$assignee = DB::table('common_users as cu')
			->where('cu.id', $sub->assignee_id)
			->select(DB::raw("cu.*, CONCAT_WS(\" \", cu.first_name, cu.last_name) as assignee_name"))
			->first();

		$dept = DB::table('common_department as cd')
			->where('cd.id', $sub->dept_id)
			->first();

		$label = DB::table('services_complaint_sublist as scs')	
			->where('scs.id', $sub->id)
			->select(DB::raw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(scs.sub_id, '0', 'A')
						, '1', 'B')
						, '2', 'C')
						, '3', 'D')
						, '4', 'E')
						, '5', 'F')
						, '6', 'G')
						, '7', 'H')
						, '8', 'I')
						, '9', 'J') as sub_label"))
			->first();

		if( empty($assignee) || empty($dept) )
			return;

		$complaint->sub_type = 'post';

		$location_name = '';	
		$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($complaint->loc_id);
		if( !empty($info) )
			$location_name = $info->name . ' - ' . $info->type;			

		if( $sub->status == SC_COMPLETE ){
			$agent = DB::table('common_users as cu')
				->where('cu.id', $sub->completed_by)
				->select(DB::raw("cu.*, CONCAT_WS(\" \", cu.first_name, cu.last_name) as wholename"))
				->first();

			$agent_name = 'Someone';
			if( !empty($agent) )
				$agent_name = $agent->wholename;	

			$message_content = sprintf('Sub complaint C%05d%s has been completed by %s from %s department',
										$complaint->id, $label->sub_label, $agent_name, $dept->department);	
			$scstatus = 'Completed';
		}
		if( $sub->status == SC_CANCELED ){
			$agent = DB::table('common_users as cu')
				->where('cu.id', $sub->canceled_by)
				->select(DB::raw("cu.*, CONCAT_WS(\" \", cu.first_name, cu.last_name) as wholename"))
				->first();

			$agent_name = 'Someone';
			if( !empty($agent) )
				$agent_name = $agent->wholename;	

			$message_content = sprintf('Sub complaint C%05d%s has been canceled by %s from %s department',
										$complaint->id, $label->sub_label, $agent_name, $dept->department);	
			$scstatus = 'Canceled';
		}

		// find duty manager	
		$job_roles = PropertySetting::getJobRoles($complaint->property_id);

		$userlist = ShiftGroupMember::getUserlistOnCurrentShift($complaint->property_id, $job_roles['dutymanager_job_role'], 0, 0, 0, 0, 0, false, false);

		if( empty($userlist) || count($userlist) < 1 )
			return $complaint->id;

		foreach($userlist as $key => $row) {
			$duty_manager = $row;

			$complaint->content = $message_content;

			$complaint->subject = sprintf('C%05d%s: Sub-Complaint %s', $complaint->id, $label->sub_label,$scstatus);
			$complaint->email_content = $message_content;

			$webpush_flag = $key == 0;

			$notify_flag = true;

			if( $sub->status == SC_COMPLETE )
				$notify_flag = $this->canReceiveNotification($complaint, $row->id, 'subcomplaint_complete', $complaint->type);

			$this->sendComplaintNotification($complaint->property_id, $message_content, $complaint->comment, $complaint, $duty_manager->email, $duty_manager->mobile, $duty_manager->fcm_key, $webpush_flag, $notify_flag, NULL);	
		}

		return $message_content;
	}

	private function sendNotifyAllCompleteSubcomplaint($sub) {
		$complaint = DB::table('services_complaint_request as scr')
			->leftJoin('common_employee as ce', 'scr.requestor_id', '=', 'ce.id')
			->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')		
			->leftJoin('common_property as cp', 'scr.property_id', '=', 'cp.id')
			->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')
			->select(DB::raw('scr.*, ct.type, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, 
				cu.email, cu.mobile, cp.name as property_name'))
			->where('scr.id', $sub->parent_id)
			->where('scr.delete_flag', 0)
			->first();

		if( empty($complaint) )
			return $complaint->id;

		$sub_list = DB::table('services_complaint_sublist as scs')
			->leftJoin('common_users as cu', 'scs.completed_by', '=', 'cu.id')
			->leftJoin('common_department as cd', 'scs.dept_id', '=', 'cd.id')
			->where('scs.parent_id', $sub->parent_id)
			->where('scs.delete_flag', 0)
			->select(DB::raw("scs.*, cd.department, CONCAT_WS(\" \", cu.first_name, cu.last_name) as wholename,
							REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(scs.sub_id, '0', 'A')
										, '1', 'B')
										, '2', 'C')
										, '3', 'D')
										, '4', 'E')
										, '5', 'F')
										, '6', 'G')
										, '7', 'H')
										, '8', 'I')
										, '9', 'J') as sub_label"))
			->get();	

		if( count($sub_list) < 1 )
			return;
			
		$completed_count = 0;
		foreach($sub_list as $row)
		{
			if( $row->status == SC_COMPLETE )
				$completed_count++;
		}	

		if( $completed_count < count($sub_list) )	// not all completed
			return;
	
		$info = array();
		$info['wholename'] = $complaint->wholename;
		$info['main_id'] = sprintf("F%05d", $complaint->id);
		$info['subcomplaint_list'] = $sub_list;

		$email_content = view('emails.complaint_subcomplaint_all_completed', ['info' => $info])->render();

		$smtp = Functions::getMailSetting($complaint->property_id, 'notification_');

		$message = array();
		$message['type'] = 'email';
		$message['to'] = $complaint->email;
		$message['subject'] = sprintf('F%05d: All Sub-Complaints Completed', $complaint->id); 
		$message['content'] = $email_content;
		$message['smtp'] = $smtp;

		// echo $email_content;
		// echo $complaint->email;

		Redis::publish('notify', json_encode($message));

		return $message;
	}

	public function createComplaintState($sub)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		// find max time for selected complaint and department
		$dept = DB::table('services_complaint_dept_pivot')
			->where('complaint_id', $sub->item_id)
			->where('dept_id', $sub->dept_id)
			->first();			

		if( !empty($dept) && $dept->max_time > 0 )	// max time is exsit
		{
			// task state
			$task_state = new ComplaintState();

			$task_state->task_id = $sub->id;
			$task_state->type_id = 1;
			$task_state->level = 0;
			$task_state->start_time = $sub->created_at;
			$task_state->status_id = SC_OPEN;
			$task_state->dispatcher = $sub->assignee_id;
			$task_state->attendant = $sub->submitter_id;
			$task_state->running = $sub->running;

			$end_time = new DateTime($sub->created_at);
			$end_time->add(new DateInterval('PT' . $dept->max_time . 'S'));
			$task_state->end_time = $end_time->format('Y-m-d H:i:s');

			$task_state->save();
		}

		// increase notification
		CommonUserNotification::addComplaintNotifyCount($sub->assignee_id, $sub->dept_id);

		$this->sendNotificationForSubcomplaint($sub, true);
	}

	public function reassignSubComplaint(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', 4);
		$user_id = $request->get('user_id', 0);
		$id = $request->get('id', 0);		
		$dept_id = $request->get('dept_id', 0);
		$assignee_id = $request->get('assignee_id', 0);

		$sub = ComplaintSublist::find($id);

		$sub->reassigne_dept_id = $dept_id;
		$sub->status = SC_REASSIGN;
		$sub->save();

		$complaint = ComplaintRequest::find($sub->parent_id);		
		$complaint->save();	
		ComplaintUpdated::modifyByUser($complaint->id, $user_id);

		$this->removeEscalationState($id);

		// create new sub complaint	
		$new_sub = new ComplaintSublist();

		$new_sub->parent_id = $sub->parent_id;
		$new_sub->sub_id = ComplaintSublist::getMaxSubID($sub->parent_id);
		$new_sub->item_id = $sub->item_id;
		$new_sub->assignee_id = $assignee_id;
		$new_sub->dept_id  = $dept_id;
		$new_sub->submitter_id  = $sub->submitter_id;
		$new_sub->status = SC_OPEN;
		$new_sub->severity = $sub->severity;
		$new_sub->running = 1;
		$new_sub->comment = $sub->comment;
		$new_sub->compensation_id = $sub->compensation_id;
		$new_sub->compensation_status = $sub->compensation_status;
		$new_sub->compensation_comment = $sub->compensation_comment;
		$new_sub->created_at = $cur_time;

		$new_sub->save();

		$comment = array();

		$comment['sub_id'] = $new_sub->id;
		$comment['parent_id'] = 0;
		$comment['user_id'] = $user_id;
		$comment['comment'] = $sub->comment;
		$comment['created_at'] = $cur_time;

		DB::table('services_complaint_sublist_comments')->insertGetId($comment);	

		// $this->createComplaintState($new_sub);
		ComplaintSublistState::initState($new_sub->id, 0);
		ComplaintSublistLocState::initState($new_sub->id, 0);

		// increase notification
		CommonUserNotification::addComplaintNotifyCount($new_sub->assignee_id, $new_sub->dept_id);
		$this->sendNotificationForSubcomplaint($new_sub, true);		

		// save log
		$department = DB::table('common_department as cd')
			->where('cd.id', $dept_id)
			->first();
		
		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $sub->parent_id;
		$complaint_log->sub_id = $sub->id;
		$complaint_log->comment = 'Reassigned to department ' . $department->department;
		$complaint_log->type = SC_REASSIGN;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$this->sendSubcomplaintStatusChangeEvent($sub, $user_id);
		$this->sendSubcomplaintStatusChangeEvent($new_sub, $user_id);

		return Response::json($new_sub);
	}

	public function reopenSubComplaint(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);	

		$sub = ComplaintSublist::find($id);

		$sub->status = SC_REOPEN;
		$sub->save();

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $sub->parent_id;
		$complaint_log->sub_id = $id;
		$complaint_log->comment = 'Re-open Subcomplaint';
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$this->sendSubcomplaintStatusChangeEvent($sub, $user_id);

		$complaint = ComplaintRequest::find($sub->parent_id);
		
		if( !empty($complaint) )
		{	
			$complaint->closed_flag = 0;			
			$complaint->save();

			ComplaintUpdated::modifyByUser($complaint->id, $user_id);

			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $sub->parent_id;
			$complaint_log->sub_id = 0;
			$complaint_log->comment = 'Re-open SubComplaint';
			$complaint_log->type = 0;
			$complaint_log->user_id = $user_id;
			
			$complaint_log->save();
		}

		ComplaintSublistReopenState::initState($sub->id, 0);

		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_status_changed', $complaint, $user_id);
		
		return Response::json($sub);
	}

	public function checkComplaintState()	
	{
		$this->checkComplaintStateProc();
		$this->checkComplaintDivisionStateProc();
		$this->checkSubComplaintStateProc();
		$this->checkSubComplaintLocStateProc();
		$this->checkSubComplaintReopenStateProc();
		$this->checkCompensationStateProc();
		$this->checkReminderStateProc();
	}

	public function checkComplaintStateProc()
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_state_list = DB::table('services_complaint_main_state as cms')
			->join('services_complaint_request as cr', 'cms.complaint_id', '=', 'cr.id')
			->whereRaw("cms.end_time < '" . $cur_time . "' and cms.running = 1")		// running
			->where('cr.delete_flag', 0)
			->select(DB::raw('cms.*, cr.status, cr.property_id'))
			->get();

		echo 'Complaint = ' . json_encode($task_state_list) . '<br>';

		$non_exist_ids = array();
		for($i = 0; $i < count($task_state_list); $i++)
		{
			$task_state = $task_state_list[$i];

			// update service_task
			$complaint = ComplaintRequest::find($task_state->complaint_id);

			if( empty($complaint) )
			{
				array_push($non_exist_ids, $task_state->id);
				continue;
			}

			$escalation_levels = DB::table('services_complaint_main_escalation as cme')
				->where('cme.status', $task_state->status)
				->where('cme.level', '>', $task_state->level)
				->orderBy('cme.level', 'asc')
				->get();

			echo 'Escalation Levels = ' . json_encode($escalation_levels) . '<br>';

			$user_list = [];
			$escalation = null;
			foreach($escalation_levels as $row)
			{
				$escalation = $row;

				$job_role_ids = explode(',', $row->job_role_ids);
				if( count($job_role_ids) < 1 )
					continue;

				$user_list = DB::table('common_users as cu')	
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')				
					->whereIn('cu.job_role_id', $job_role_ids)				
					->where('cd.property_id', $task_state->property_id)
					->where('cu.deleted', 0)
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->get();	
				
				if( count($user_list) < 1 )
					continue;

				break;
			}

			echo 'Escalation = ' . json_encode($escalation) . '<br>';
			echo 'User List = ' . json_encode($user_list) . '<br>';

			$comment = '';
			if( count($user_list) > 0 && !empty($escalation) )		// escalated
			{
				$complaint->escalation_flag = 1;
				$complaint->save();

				$this->upgradeComplaintEscalateLevel($task_state->id, $escalation, $cur_time);
				$this->sendNotificationForComplaintEscalated($complaint, $task_state, $escalation);

				$comment = 'Escalated to ' . $user_list[0]->wholename;
			}
			else
			{
				$comment = $complaint->status . ' -> ' . 'Timeout';
				$complaint->timeout_flag = 1;
				$complaint->save();				

				array_push($non_exist_ids, $task_state->id);				
			}

			// save log
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $task_state->complaint_id;
			$complaint_log->sub_id = 0;			
			$complaint_log->comment = $comment;
			$complaint_log->type = $complaint->status;
			
			$complaint_log->save();
		}

		DB::table('services_complaint_main_state')->whereIn('id', $non_exist_ids)->delete();
	}


	public function checkComplaintDivisionStateProc()
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_state_list = DB::table('services_complaint_division_main_state as cms')
			->join('services_complaint_request as cr', 'cms.complaint_id', '=', 'cr.id')
			->leftJoin('services_complaint_maincategory as cm', 'cr.category_id', '=', 'cm.id')
			->whereRaw("cms.end_time < '" . $cur_time . "' and cms.running = 1")		// running
			->where('cr.delete_flag', 0)
			->select(DB::raw('cms.*, cr.severity, cm.division_id, cr.property_id'))
			->get();

		echo 'Complaint Division = ' . json_encode($task_state_list) . '<br>';

		$non_exist_ids = array();
		for($i = 0; $i < count($task_state_list); $i++)
		{
			$task_state = $task_state_list[$i];

			// update service_task
			$complaint = ComplaintRequest::find($task_state->complaint_id);

			if( empty($complaint) )
			{
				array_push($non_exist_ids, $task_state->id);
				continue;
			}

			$escalation_levels = DB::table('services_complaint_division_escalation as cme')
				->where('cme.division_id', $task_state->division_id)
				->where('cme.severity_id', $task_state->severity)
				->where('cme.level', '>', $task_state->level)
				->orderBy('cme.level', 'asc')
				->get();

			echo 'Escalation Levels = ' . json_encode($escalation_levels) . '<br>';

			$user_list = [];
			$escalation = null;
			foreach($escalation_levels as $row)
			{
				$escalation = $row;

				$job_role_ids = explode(',', $row->job_role_ids);
				if( count($job_role_ids) < 1 )
					continue;

				$user_list = DB::table('common_users as cu')	
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')				
					->whereIn('cu.job_role_id', $job_role_ids)				
					->where('cd.property_id', $task_state->property_id)
					->where('cu.deleted', 0)
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->get();	
				
				if( count($user_list) < 1 )
					continue;

				break;
			}

			echo 'Escalation = ' . json_encode($escalation) . '<br>';
			echo 'User List = ' . json_encode($user_list) . '<br>';

			$comment = '';
			if( count($user_list) > 0 && !empty($escalation) )		// escalated
			{
				$complaint->escalation_flag = 1;
				$complaint->save();

				$this->upgradeComplaintDivisionEscalateLevel($task_state->id, $escalation, $cur_time);
				$this->sendNotificationForComplaintDivisionEscalated($complaint, $task_state, $escalation);

				$comment = 'Escalated to ' . $user_list[0]->wholename;
			}
			else
			{
				$comment = 'Division: ' . $complaint->division_id . ' -> ' . 'Timeout';
				$complaint->timeout_flag = 1;
				$complaint->save();				

				array_push($non_exist_ids, $task_state->id);				
			}

			// save log
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $task_state->complaint_id;
			$complaint_log->sub_id = 0;			
			$complaint_log->comment = $comment;
			$complaint_log->type = $complaint->division_id;
			
			$complaint_log->save();
		}

		DB::table('services_complaint_division_main_state')->whereIn('id', $non_exist_ids)->delete();
	}

	public function checkSubComplaintStateProc()
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_state_list = DB::table('services_complaint_sublist_state as ct')
			->join('services_complaint_sublist as cs', 'ct.sub_id', '=', 'cs.id')
			->join('services_complaint_request as cr', 'cs.parent_id', '=', 'cr.id')
			->join('common_department as cd', 'cs.dept_id', '=', 'cd.id')	// sub complaint department property
			->whereRaw("ct.end_time < '" . $cur_time . "' and ct.running = 1")		// running
			->where('cs.delete_flag', 0)
			->where('cr.delete_flag', 0)
			->select(DB::raw('ct.*, cs.severity, cd.property_id, cs.item_id, cs.parent_id, cs.dept_id'))
			->get();

		echo 'Sub Complaint = ' . json_encode($task_state_list) . '<br>';

		$non_exist_ids = array();
		for($i = 0; $i < count($task_state_list); $i++)
		{
			$task_state = $task_state_list[$i];

			// update service_task
			$sub = ComplaintSublist::find($task_state->sub_id);

			if( empty($sub) )
			{
				array_push($non_exist_ids, $task_state->id);
				continue;
			}

			$escalation_levels = DB::table('services_complaint_sublist_escalation as cse')
				->where('cse.dept_id', $task_state->dept_id)
				->where('cse.severity_id', $task_state->severity)
				->where('cse.level', '>', $task_state->level)
				->orderBy('cse.level', 'asc')
				->get();
			
			echo 'Escalation Levels = ' . json_encode($escalation_levels) . '<br>';

			$user_list = [];
			$escalation = null;
			foreach($escalation_levels as $row)
			{
				$escalation = $row;

				$job_role_ids = explode(',', $row->job_role_ids);
				if( count($job_role_ids) < 1 )
					continue;

				$user_list = DB::table('common_users as cu')	
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')				
					->whereIn('cu.job_role_id', $job_role_ids)				
					->where('cd.property_id', $task_state->property_id)
					->where('cu.deleted', 0)
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->get();	
				
				if( count($user_list) < 1 )
					continue;

				break;
			}

			// echo 'Escalation = ' . json_encode($escalation) . '<br>';
			// echo 'User List = ' . json_encode($user_list) . '<br>';

			$comment = '';
			if( count($user_list) > 0 && !empty($escalation) )		// escalated
			{
				// find escalation staff_id
				$assigned_id = $user_list[0]->id;
				
				$sub->status = SC_ESCALATED;
				$sub->save();

				$this->upgradeSubComplaintEscalateLevel($task_state->id, $escalation, $cur_time);

				$this->sendNotificationForSubcomplaintEscalated($sub, $task_state, $escalation);


				$comment = 'Escalated to ' . $user_list[0]->wholename;
			}
			else
			{
				$sub->status = SC_TIMEOUT;
				$sub->save();				

				array_push($non_exist_ids, $task_state->id);
				$comment = 'Timeout';
			}

			// save log
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $task_state->parent_id;
			$complaint_log->sub_id = $task_state->sub_id;			
			$complaint_log->comment = $comment;
			$complaint_log->type = $sub->status;
			
			$complaint_log->save();
		}

		DB::table('services_complaint_sublist_state')->whereIn('id', $non_exist_ids)->delete();
	}

	public function checkSubComplaintLocStateProc()
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_state_list = DB::table('services_complaint_sublist_loc_state as ct')
			->join('services_complaint_sublist as cs', 'ct.sub_id', '=', 'cs.id')
			->join('services_complaint_request as cr', 'cs.parent_id', '=', 'cr.id')
			->join('services_location as sl', 'cs.location_id', '=', 'sl.id')	// sub complaint location
			->whereRaw("ct.end_time < '" . $cur_time . "' and ct.running = 1")		// running
			->where('cs.delete_flag', 0)
			->where('cr.delete_flag', 0)
			->select(DB::raw('ct.*, cs.severity, cr.property_id, cs.item_id, cs.parent_id, sl.type_id'))
			->get();

		echo 'Sub Complaint Loc = ' . json_encode($task_state_list) . '<br>';

		$non_exist_ids = array();
		for($i = 0; $i < count($task_state_list); $i++)
		{
			$task_state = $task_state_list[$i];

			// update service_task
			$sub = ComplaintSublist::find($task_state->sub_id);

			if( empty($sub) )
			{
				array_push($non_exist_ids, $task_state->id);
				continue;
			}

			$escalation_levels = DB::table('services_complaint_sublist_loc_escalation as cse')
				->where('cse.type_id', $task_state->type_id)
				->where('cse.severity_id', $task_state->severity)
				->where('cse.level', '>', $task_state->level)
				->orderBy('cse.level', 'asc')
				->get();
			
			echo 'Escalation Levels = ' . json_encode($escalation_levels) . '<br>';

			$user_list = [];
			$escalation = null;
			foreach($escalation_levels as $row)
			{
				$escalation = $row;

				$job_role_ids = explode(',', $row->job_role_ids);
				if( count($job_role_ids) < 1 )
					continue;

				$user_list = DB::table('common_users as cu')	
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')				
					->whereIn('cu.job_role_id', $job_role_ids)				
					->where('cd.property_id', $task_state->property_id)
					->where('cu.deleted', 0)
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->get();	
				
				if( count($user_list) < 1 )
					continue;

				break;
			}

			// echo 'Escalation = ' . json_encode($escalation) . '<br>';
			// echo 'User List = ' . json_encode($user_list) . '<br>';

			$comment = '';
			if( count($user_list) > 0 && !empty($escalation) )		// escalated
			{
				// find escalation staff_id
				$assigned_id = $user_list[0]->id;
				
				$sub->status = SC_ESCALATED;
				$sub->save();

				$this->upgradeSubComplaintLocEscalateLevel($task_state->id, $escalation, $cur_time);
				$this->sendNotificationForSubcomplaintEscalated($sub, $task_state, $escalation);


				$comment = 'Escalated to ' . $user_list[0]->wholename;
			}
			else
			{
				$sub->status = SC_TIMEOUT;
				$sub->save();				

				array_push($non_exist_ids, $task_state->id);
				$comment = 'Timeout';
			}

			// save log
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $task_state->parent_id;
			$complaint_log->sub_id = $task_state->sub_id;			
			$complaint_log->comment = $comment;
			$complaint_log->type = $sub->status;
			
			$complaint_log->save();
		}

		DB::table('services_complaint_sublist_loc_state')->whereIn('id', $non_exist_ids)->delete();
	}

	public function checkSubComplaintReopenStateProc()
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$task_state_list = DB::table('services_complaint_sublist_reopen_state as ct')
			->join('services_complaint_sublist as cs', 'ct.sub_id', '=', 'cs.id')
			->join('services_complaint_request as cr', 'cs.parent_id', '=', 'cr.id')
			->whereRaw("ct.end_time < '" . $cur_time . "' and ct.running = 1")		// running
			->where('cs.delete_flag', 0)
			->where('cr.delete_flag', 0)
			->select(DB::raw('ct.*, cs.severity, cr.property_id, cs.item_id, cs.parent_id'))
			->get();

		echo 'Sub Complaint Reopen = ' . json_encode($task_state_list) . '<br>';

		$non_exist_ids = array();
		for($i = 0; $i < count($task_state_list); $i++)
		{
			$task_state = $task_state_list[$i];

			// update service_task
			$sub = ComplaintSublist::find($task_state->sub_id);

			if( empty($sub) )
			{
				array_push($non_exist_ids, $task_state->id);
				continue;
			}

			$escalation_levels = DB::table('services_complaint_sublist_reopen_escalation as cse')				
				->where('cse.severity_id', $task_state->severity)
				->where('cse.level', '>', $task_state->level)
				->orderBy('cse.level', 'asc')
				->get();
			
			echo 'Escalation Levels = ' . json_encode($escalation_levels) . '<br>';

			$user_list = [];
			$escalation = null;
			foreach($escalation_levels as $row)
			{
				$escalation = $row;

				$job_role_ids = explode(',', $row->job_role_ids);
				if( count($job_role_ids) < 1 )
					continue;

				$user_list = DB::table('common_users as cu')	
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')				
					->whereIn('cu.job_role_id', $job_role_ids)				
					->where('cd.property_id', $task_state->property_id)
					->where('cu.deleted', 0)
					->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->get();	
				
				if( count($user_list) < 1 )
					continue;

				break;
			}

			// echo 'Escalation = ' . json_encode($escalation) . '<br>';
			// echo 'User List = ' . json_encode($user_list) . '<br>';

			$comment = '';
			if( count($user_list) > 0 && !empty($escalation) )		// escalated
			{
				// find escalation staff_id
				$assigned_id = $user_list[0]->id;
				
				$sub->status = SC_ESCALATED;
				$sub->save();

				$this->upgradeSubComplaintReopenEscalateLevel($task_state->id, $escalation, $cur_time);
				$this->sendNotificationForSubcomplaintEscalated($sub, $task_state, $escalation);


				$comment = 'Escalated to ' . $user_list[0]->wholename;
			}
			else
			{
				$sub->status = SC_TIMEOUT;
				$sub->save();				

				array_push($non_exist_ids, $task_state->id);
				$comment = 'Timeout';
			}

			// save log
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $task_state->parent_id;
			$complaint_log->sub_id = $task_state->sub_id;			
			$complaint_log->comment = $comment;
			$complaint_log->type = $sub->status;
			
			$complaint_log->save();
		}

		DB::table('services_complaint_sublist_reopen_state')->whereIn('id', $non_exist_ids)->delete();
	}


	public function checkCompensationStateProc() {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$state_list = DB::table('services_compensation_state as scs')
			->join('services_complaint_request as scr', 'scs.task_id', '=', 'scr.id')
			->whereRaw("(scs.status_id = 1) and scs.end_time < '" . $cur_time . "' and scs.running = 1")
			->where('scr.delete_flag', 0)
			->select(DB::raw('scs.*'))
			->get();


		$non_exist_ids = array();	

		foreach ($state_list as $key => $row) {
			$task_state = CompensationState::find($row->id);			
			$complaint = ComplaintRequest::find($task_state->task_id);
			$comp = CompensationRequest::find($row->comp_id);

			if( empty($complaint) )
				continue;

			$job_role_id = 0;
			$log_type = '';
			$reason = 'Auto Approved';
			$user_id = 0;
			$comment = 'Auto Approved';

			// create compensation tracker
			if( $task_state->approval_route_id > 0 )	// need to be approve
			{
				// task state
				$task_state->start_time = $cur_time;

				$escalation_levels = DB::table('services_approval_route_members as arm')
					->where('arm.property_id', $complaint->property_id)	// compensation's property
					->where('arm.approval_route_id', $task_state->approval_route_id)
					->where('arm.level', '>', $task_state->level)
					->orderBy('arm.level', 'asc')
					->first();

				if( empty($escalation_levels) )
				{
					$ret = $this->changeCompensationStatus($comp, $task_state, CP_COMPLETE_APPROVE);
					$log_type = $ret['log_type'];
					$reason = $ret['reason'];		

					array_push($non_exist_ids, $task_state->id);
				}
				else
				{
					// find escalation staff_id
					$job_role_id = $escalation_levels->job_role_id;

					if( $job_role_id >= 0 )		// there is approver
					{
						$task_state->level = $escalation_levels->level;
						$task_state->dispatcher = $job_role_id;

						$end_time = new DateTime($cur_time);
						$end_time->add(new DateInterval('PT' . $escalation_levels->max_time . 'S'));
						$task_state->end_time = $end_time->format('Y-m-d H:i:s');

						// $this->saveNotification($assigned_id, $task_state->task_id, 'Escalation');
						echo 'Compensation is approved: level = ' . $task_state->level . ' job role = ' . $job_role_id . '</br>'; 

						$task_state->save();

						$this->sendNotifyForCompensation($task_state, $comment);
						$this->sendNotifyForApproveStatus($task_state, '', 0);

						CommonUserNotification::addComplaintNotifyCountWithJobRole($complaint->property_id, $job_role_id);

						$log_type = 'On-Route';
						$reason = 'Approve';
					}
					else	// There is no staff
					{
						// change task to pending
						$ret = $this->changeCompensationStatus($comp, $task_state, CP_PENDING);
						$log_type = $ret['log_type'];	
						$reason = $ret['reason'];

						// find default approver
						$approval_route = CompensationApproveRoute::find($task_state->approval_route_id);
						if( !empty($approval_route) )
						{
							// $user_id = $approval_route->default_approver;
							// $this->saveNotification($approval_route->default_approver, $task_state->task_id, 'Modification');
						}
						else {
							// $this->saveNotification($task->attendant, $task_state->task_id, 'Modification');
						}
					}
				}			
			}		

			if( !empty($task_state) )
			{
				$input['task_id'] = $complaint->id;
				$input['comp_id'] = $comp->id;
				$input['parent_id'] = 0;
				$input['user_id'] = $user_id;
				$input['reason'] = $reason;
				$input['comment'] = $comment;
				$this->addCommentToApproval($input);
			}
			

			// send notification
			// $this->saveNotification($task->dispatcher, $task->id, 'Assignment');

			// save log
			$task_log = new Tasklog();
			$task_log->task_id = $complaint->id;
			$task_log->user_id = $job_role_id;
			$task_log->comment = $comment;
			$task_log->log_type = $log_type;
			$task_log->log_time = $cur_time;

			$task_log->save();
		}		

		DB::table('services_compensation_state')->whereIn('id', $non_exist_ids)->delete();
	}

	public function upgradeComplaintEscalateLevel($id, $escalation, $cur_time)
	{
		$task_state_model = ComplaintMainState::find($id);
		if( empty($task_state_model) )
			return;

		$task_state_model->level = $escalation->level;
		$task_state_model->start_time = $cur_time;

		date_default_timezone_set(config('app.timezone'));
		$end_time = new DateTime($cur_time);

		$end_time->add(new DateInterval('PT' . $escalation->max_time . 'S'));
		$task_state_model->end_time = $end_time->format('Y-m-d H:i:s');
		$task_state_model->elaspse_time = 0;

		$task_state_model->save();
	}

	public function upgradeComplaintDivisionEscalateLevel($id, $escalation, $cur_time)
	{
		$task_state_model = ComplaintDivisionMainState::find($id);
		if( empty($task_state_model) )
			return;

		$task_state_model->level = $escalation->level;
		$task_state_model->start_time = $cur_time;

		date_default_timezone_set(config('app.timezone'));
		$end_time = new DateTime($cur_time);

		$end_time->add(new DateInterval('PT' . $escalation->max_time . 'S'));
		$task_state_model->end_time = $end_time->format('Y-m-d H:i:s');
		$task_state_model->elaspse_time = 0;

		$task_state_model->save();
	}

	public function upgradeSubComplaintEscalateLevel($id, $escalation, $cur_time)
	{
		$task_state_model = ComplaintSublistState::find($id);
		if( empty($task_state_model) )
			return;

		$task_state_model->level = $escalation->level;
		$task_state_model->start_time = $cur_time;

		date_default_timezone_set(config('app.timezone'));
		$end_time = new DateTime($cur_time);

		$end_time->add(new DateInterval('PT' . $escalation->max_time . 'S'));
		$task_state_model->end_time = $end_time->format('Y-m-d H:i:s');
		$task_state_model->elaspse_time = 0;

		$task_state_model->save();
	}

	public function upgradeSubComplaintLocEscalateLevel($id, $escalation, $cur_time)
	{
		$task_state_model = ComplaintSublistLocState::find($id);
		if( empty($task_state_model) )
			return;

		$task_state_model->level = $escalation->level;
		$task_state_model->start_time = $cur_time;

		date_default_timezone_set(config('app.timezone'));
		$end_time = new DateTime($cur_time);

		$end_time->add(new DateInterval('PT' . $escalation->max_time . 'S'));
		$task_state_model->end_time = $end_time->format('Y-m-d H:i:s');
		$task_state_model->elaspse_time = 0;

		$task_state_model->save();
	}

	public function upgradeSubComplaintReopenEscalateLevel($id, $escalation, $cur_time)
	{
		$task_state_model = ComplaintSublistReopenState::find($id);
		if( empty($task_state_model) )
			return;

		$task_state_model->level = $escalation->level;
		$task_state_model->start_time = $cur_time;

		date_default_timezone_set(config('app.timezone'));
		$end_time = new DateTime($cur_time);

		$end_time->add(new DateInterval('PT' . $escalation->max_time . 'S'));
		$task_state_model->end_time = $end_time->format('Y-m-d H:i:s');
		$task_state_model->elaspse_time = 0;

		$task_state_model->save();
	}

	public function sendNotifyForComplaintDept($id, $dept) {
		$complaint = DB::table('services_complaint_request as scr')
			->leftJoin('common_employee as ce', 'scr.requestor_id', '=', 'ce.id')
			->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
			->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')
			->leftJoin('common_room as cr', 'scr.room_id', '=', 'cr.id')
			->leftJoin('common_guest_profile as gp', 'scr.guest_id', '=', 'gp.id')
			->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('scr.property_id', '=', 'cg.property_id');
				})
			->join('common_property as cp', 'scr.property_id', '=', 'cp.id')
			->select(DB::raw('scr.*, CONCAT_WS(" ", ce.fname, ce.lname) as wholename,cg.arrival, cg.departure, ct.type as severitytype, cp.name as property_name, cr.room, gp.guest_name'))
			->where('scr.id', $id)
			->where('scr.delete_flag', 0)
			->first();

		if( empty($complaint) )
			return '';

		// find duty manager
		date_default_timezone_set(config('app.timezone'));
		$dayofweek = date('w');

		$date = date('Y-m-d');
		$time = date('H:i:s');
		$datetime = date('Y-m-d H:i:s');

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

		$location_name = '';
		$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($complaint->loc_id);
		if( !empty($info) )
			$location_name = $info->name . ' - ' . $info->type;			

		$message_content = sprintf('There is a new complaint C%05d which has been raised by %s for %s in %s',
										$complaint->id, $complaint->wholename, $location_name, $complaint->property_name);
										
		$assignee = DB::table('services_complaint_dept_default_assignee as da')
			->leftJoin('common_users as cu', 'da.user_id', '=', 'cu.id')
			->select(DB::raw('da.user_id as dept_user_id, cu.email as dept_email, cu.mobile as dept_mobile, cu.fcm_key as dept_fcm_key, cu.first_name as assinee_name'))
			->where('da.id', $dept)
			->first();

		// find user group
		$userlist = DB::table('services_complaint_dept_default_assignee as da')
		->leftJoin('common_user_group_members as cug', 'da.user_group', '=', 'cug.group_id')
		->leftJoin('common_users as cu', 'cug.user_id', '=', 'cu.id')
	    ->select(DB::raw('da.user_id as depts_user_id, cu.email as depts_email, cu.mobile as depts_mobile, cu.fcm_key as depts_fcm_key, cu.first_name as first_name'))
		->where('da.id', $dept)
		->get();	

		$dep = DB::table('common_department as cd')
			->select(DB::raw('cd.department as deptname'))
			->where('cd.id', $dept)
			->first();
		
		$setting = PropertySetting::getServerConfig(0);

		$complaint->sub_type = 'post';		// for web push
		$complaint->content = $message_content;
		if(($complaint->status))
		{
			if( !empty($assignee) )	
		   	{
				$info = array();
				$info['wholename'] = $assignee->assinee_name;
				$info['user_id']= $assignee->dept_user_id;
				$info['dept_name'] = $complaint->property_name;
				$info['location'] = $location_name;
				$info['raised_by'] = $complaint->wholename;
				$info['comment'] = $complaint->comment;
				$info['dept'] = $dep->deptname;
				$info['arrival'] = $complaint->arrival;
				$info['departure'] = $complaint->departure;
				$info['severity'] = $complaint->severitytype;
				$info['room'] = $complaint->room;
			    $info['guest_name'] = $complaint->guest_name;
				$info['guest_type'] = $complaint->guest_type;
				$info['id']=$complaint->id;
				$info['intial_response'] = $complaint->initial_response;
				$info['status'] = $complaint->status;
				$info['resolution'] = $complaint->solution;
				$complaint->subject = sprintf('F%05d: New %s Complaint Raised', $complaint->id, $complaint->severitytype);
				$complaint->email_content = view('emails.complaint_create_dept', ['info' => $info])->render();

				$this->sendComplaintNotification($complaint->property_id, $message_content, $complaint->comment, $complaint, $assignee->dept_email, $assignee->dept_mobile, $assignee->dept_fcm_key, true, true, NULL);
			}

			foreach($userlist as $key => $row) {
				//$duty_manager = $row;

				$info = array();
				$info['wholename'] = $row->first_name;
				$info['user_id']=$row->depts_user_id;
				$info['dept_name'] = $complaint->property_name;
				$info['location'] = $location_name;
				$info['dept'] = $dep->deptname;
				$info['raised_by'] = $complaint->wholename;
				$info['comment'] = $complaint->comment;
				$info['severity'] = $complaint->severitytype;
				$info['arrival'] = $complaint->arrival;
				$info['departure'] = $complaint->departure;
				$info['room'] = $complaint->room;
			    $info['guest_name'] = $complaint->guest_name;
				$info['guest_type'] = $complaint->guest_type;
				$info['id']=$complaint->id;
				$info['intial_response'] = $complaint->initial_response;
				$info['status'] = $complaint->status;
				$info['resolution'] = $complaint->solution;
				$complaint->subject = sprintf('F%05d: New %s Complaint Raised', $complaint->id, $complaint->severitytype);
				$complaint->email_content = view('emails.complaint_create_dept', ['info' => $info])->render();

				$this->sendComplaintNotification($complaint->property_id, $message_content, $complaint->comment, $complaint, $row->depts_email, $row->depts_mobile, $row->depts_fcm_key, true, true, NULL);
			}
	    }
		return $message_content;
	}


	public function sendNotificationForSubcomplaint($sub, $check_dept) {
		// get primary complaint info
		$complaint = DB::table('services_complaint_sublist as scs')
			->join('services_complaint_request as scr', 'scs.parent_id', '=', 'scr.id')
			->leftJoin('services_complaints as sc', 'scs.item_id', '=', 'sc.id')
			->leftJoin('common_property as cp', 'scr.property_id', '=', 'cp.id')	// main complaint property
			->leftJoin('common_department as cd', 'scs.dept_id', '=', 'cd.id')	// sub complaint department property
			->leftJoin('common_users as cu', 'scs.submitter_id', '=', 'cu.id')	// sub complaint submitter
			->leftJoin('common_room as cr', 'scr.room_id', '=', 'cr.id')
			->leftJoin('common_guest_profile as gp', 'scr.guest_id', '=', 'gp.id')
			->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('scr.property_id', '=', 'cg.property_id');
				})
			->leftJoin('services_complaint_category as scc', 'scs.category_id', '=', 'scc.id')	
			->leftJoin('services_complaint_subcategory as scsub', 'scs.subcategory_id', '=', 'scsub.id')		
			->leftJoin('services_complaint_type as ct', 'scs.severity', '=', 'ct.id')
			->leftJoin('services_location as sl', 'scs.location_id', '=', 'sl.id')
			->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			->where('scs.id', $sub->id)
			->where('scs.delete_flag', 0)
			->where('scr.delete_flag', 0)
			->select(DB::raw("scs.*, scr.id as p_id, scr.loc_id,scr.comment as primarycom, cr.room, gp.guest_name,cg.arrival, cg.departure, scr.guest_type, scr.initial_response, ct.type, cd.department, cd.property_id, sc.complaint, cp.name,
				CONCAT_WS(\" \", cu.first_name, cu.last_name) as raised_by,
				scc.name as category_name, scsub.name as subcategory_name, sl.name as location_name, slt.type as location_type,
				REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(scs.sub_id, '0', 'A')
					, '1', 'B')
					, '2', 'C')
					, '3', 'D')
					, '4', 'E')
					, '5', 'F')
					, '6', 'G')
					, '7', 'H')
					, '8', 'I')
					, '9', 'J') as sub_label"))
			->first();

		$location_name = '';	
		$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($complaint->location_id);
		if( !empty($info) )
			$location_name = $info->name . ' - ' . $info->type;			

		$message_content = sprintf('A new sub complaint C%05d%s - %s for %s in %s has been raised', $complaint->p_id, $complaint->sub_label, $complaint->comment, $location_name,  $complaint->name);	
		// $message_content = json_encode($complaint);

		// find user
		$assignee = DB::table('common_users as cu')
			->leftJoin('services_complaint_dept_default_assignee as da', 'cu.dept_id', '=', 'da.id')
			->leftJoin('common_users as cu1', 'da.user_id', '=', 'cu1.id')
			->where('cu.id', $sub->assignee_id)
			->select(DB::raw('cu.*, da.user_id as dept_user_id,cu1.email as dept_email, cu1.mobile as dept_mobile, cu1.fcm_key as dept_fcm_key, cu1.first_name as assinee_name'))
			->first();

		// find user group
		$assignee_group = DB::table('services_complaint_dept_default_assignee as da')
			->where('da.id', $sub->dept_id)
			->select(DB::raw('da.*,da.user_group'))
			->first();
			
		$complaint->sub_type = 'assign_subcomplaint';
		$complaint->content = $message_content;

		$info = array();
		$info['dept_name'] = $complaint->department;
		$info['location_name'] = $complaint->location_name;
		$info['location_type'] = $complaint->location_type;
		$info['primary'] = $complaint->primarycom;
		$info['initial_response'] = $complaint->init_response;
		$info['severity'] = $complaint->type;
		$info['raised_by'] = $complaint->raised_by;
		$info['arrival'] = $complaint->arrival;
		$info['departure'] = $complaint->departure;
		$info['room'] = $complaint->room;
		$info['guest_name'] = $complaint->guest_name;
		$info['comment'] = $sub->comment;
		$info['type'] = $complaint->guest_type;
		$info['issue'] = $complaint->complaint;
		$info['category_name'] = $complaint->category_name;
		$info['subcategory_name'] = $complaint->subcategory_name;
		$complaint->subject = sprintf('F%05d%s: New %s Sub-Complaint Raised.', $sub->parent_id, $complaint->sub_label,$complaint->type);
		
		// echo $complaint->email_content;
		if( !empty($assignee) )	
		{
			$info['wholename'] = $assignee->first_name;
			$complaint->email_content = view('emails.subcomplaint_create', ['info' => $info])->render();

			$complaint->assignee_id = $sub->assignee_id;
			$notify_flag = true;

			$notify_flag = $this->canReceiveNotification($complaint, $sub->assignee_id, 'subcomplaint_create', $complaint->type);

			if( $notify_flag == true )
			{
				$this->sendComplaintNotification($complaint->property_id, $message_content, $sub->comment, $complaint, $assignee->email, $assignee->mobile, $assignee->fcm_key, true, $notify_flag,NULL);
				if ($assignee_group->user_group > 0) 
				{
					//get users in user group
					$assignee_user_group = DB::table('common_users as cu')
						->leftJoin('common_user_group_members as cgm', 'cgm.user_id', '=', 'cu.id')
						->where('cgm.group_id', $assignee_group->user_group)
						->where('cu.deleted', 0)
						->select(DB::raw('cu.*, cu.email as email, cu.mobile as mobile, cu.fcm_key as fcm_key, cu.first_name as assignee_name'))
						->get();
	
					for($i = 0; $i < count($assignee_user_group); $i++)
					{	
						$info['wholename'] = $assignee_user_group[$i]->assignee_name;		
						$complaint->email_content = view('emails.subcomplaint_create', ['info' => $info])->render();
						$this->sendComplaintNotification($complaint->property_id, $message_content, $sub->comment, $complaint, $assignee_user_group[$i]->email, $assignee_user_group[$i]->mobile, $assignee_user_group[$i]->fcm_key, true, $notify_flag,NULL);
					}
	
				}				
				// find delegated user
				$delegated_assigned_user = ShiftGroupMember::getDelegatedUser($sub->assignee_id);		
				if( $delegated_assigned_user != null )
				{
					$info['wholename'] = $delegated_assigned_user->first_name;
					$complaint->email_content = view('emails.subcomplaint_create', ['info' => $info])->render();

					$this->sendComplaintNotification($complaint->property_id, $message_content, $sub->comment, $complaint, $delegated_assigned_user->email, $delegated_assigned_user->mobile, $delegated_assigned_user->fcm_key, false, $notify_flag,NULL);	
				}
	
			}

			$notify_flag = $this->canReceiveNotification($complaint, $assignee->dept_user_id, 'subcomplaint_create', $complaint->type);

			if( $check_dept == true && $sub->assignee_id != $assignee->dept_user_id && $assignee->dept_user_id > 0 && $notify_flag == true )	// this is not default assinee
			{
				$info['wholename'] = $assignee->assinee_name;
				$complaint->email_content = view('emails.subcomplaint_create', ['info' => $info])->render();

				$complaint->assignee_id = $assignee->dept_user_id;
				// send message to default assignee
				$this->sendComplaintNotification($complaint->property_id, $message_content, $sub->comment, $complaint, $assignee->dept_email, $assignee->dept_mobile, $assignee->dept_fcm_key, true, true,NULL);						

				// find delegated department manager
				$delegated_dept_manager = ShiftGroupMember::getDelegatedUser($assignee->dept_user_id);		
				if( $delegated_dept_manager != null )
				{
					$info['wholename'] = $delegated_dept_manager->first_name;
					$complaint->email_content = view('emails.subcomplaint_create', ['info' => $info])->render();
					
					$this->sendComplaintNotification($complaint->property_id, $message_content, $sub->comment, $complaint, $delegated_dept_manager->email, $delegated_dept_manager->mobile, $delegated_dept_manager->fcm_key, false, true,NULL);	
				}
			}
		}

		//------------ send notification based on location group ----------------
		// get location group for sub complaint
		if( $complaint->location_id < 1 )
			return;

		$loc_group_array = LocationGroupMember::getLocationGroupIds($complaint->location_id);
		$multi_loc_group = 'CONCAT(",", %s, ",") REGEXP ",(' . implode('|', $loc_group_array) . '),"';

		$user_list = DB::table('common_users as cu')			
			->join('common_user_group_members as cugm', 'cu.id', '=', 'cugm.user_id')
			->join('common_user_group as cug', 'cugm.group_id', '=', 'cug.id')
			->whereRaw(sprintf($multi_loc_group, 'cug.location_group'))			
			->where('subcomplaint_notify', 'Y')
			->groupBy('cu.email')
			->select(DB::raw('cu.*, cu.id as user_id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();

		Log::info(json_encode($user_list));	

		foreach($user_list as $row)
		{
			$info['wholename'] = $row->wholename;
			$complaint->email_content = view('emails.subcomplaint_create', ['info' => $info])->render();	

			$this->sendComplaintNotification($complaint->property_id, $message_content, $sub->comment, $complaint, $row->email, $row->mobile, $row->fcm_key, false, true, 'email');
		}
	}

	public function sendNotificationForComplaintEscalated($complaint, $task_state, $escalation) {
		// get primary complaint info
		$complaint = DB::table('services_complaint_request as scr')
			->leftJoin('common_property as cp', 'scr.property_id', '=', 'cp.id')
			->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')
			->where('scr.id', $complaint->id)
			->where('scr.delete_flag', 0)
			->select(DB::raw("scr.*, cp.name, ct.type"))
			->first();

		$location_name = '';	
		$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($complaint->loc_id);
		if( !empty($info) )
			$location_name = $info->name . ' - ' . $info->type;			

		$message_content = sprintf('A Feedback F%05d in %s has been escalated.', $complaint->id, $location_name);	

		$job_role_ids = explode(',', $escalation->job_role_ids);
		if( count($job_role_ids) < 1 )
			return;

		$user_query = DB::table('common_users as cu')	
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')				
			->whereIn('cu.job_role_id', $job_role_ids)				
			->where('cd.property_id', $task_state->property_id)
			->where('cu.deleted', 0);	

		$user_email_query = clone $user_query;
		$user_email_query->groupBy('cu.email');
		$user_email_list = $user_email_query->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();

		$user_sms_query = clone $user_query;
		$user_sms_query->groupBy('cu.mobile');
		$user_sms_list = $user_sms_query->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();	


		$smtp = Functions::getMailSetting($task_state->property_id, 'notification_');

		if( strpos($escalation->notify_type, 'Email') !== false )
		{
			foreach($user_email_list as $row)
			{
				$message = array();

				$message['type'] = 'email';

				if( empty($row->email) )
					continue;

				$message['to'] = $row->email;
				$message['subject'] = 'Complaint is escalated';
				$message['content'] = $message_content;

				$message['smtp'] = $smtp;

				echo 'Send Email = ' . json_encode($message) . '<br>';

				Redis::publish('notify', json_encode($message));			
			}
		}

		if( strpos($escalation->notify_type, 'SMS') !== false )
		{
			foreach($user_sms_list as $row)
			{
				$message = array();

				if( empty($row->mobile) )
					continue;

				$message['to'] = $row->mobile;
				$message['content'] = $message_content;

				echo 'Send SMS = ' . json_encode($message) . '<br>';

				Redis::publish('notify', json_encode($message));		
			}
		}
	}


	public function sendNotificationForComplaintDivisionEscalated($complaint, $task_state, $escalation) {
		// get primary complaint info
	/*	$complaint = DB::table('services_complaint_request as scr')
			->leftJoin('common_property as cp', 'scr.property_id', '=', 'cp.id')
			->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')
			->leftJoin('services_complaint_maincategory as cm', 'scr.category_id', '=', 'cm.id')
			->where('scr.id', $complaint->id)
			->where('scr.delete_flag', 0)
			->select(DB::raw("scr.*, cp.name, ct.type, cm.name as category_name, cm.division_id"))
			->first();
	*/

		$complaints = DB::table('services_complaint_request as scr')
			->leftJoin('common_employee as ce', 'scr.requestor_id', '=', 'ce.id')
			->leftJoin('common_users as cu', 'scr.requestor_id', '=', 'cu.id')
			->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')
			->leftJoin('services_complaint_maincategory as cm', 'scr.category_id', '=', 'cm.id')
			->leftJoin('services_complaint_main_subcategory as cmsc', 'scr.subcategory_id', '=', 'cmsc.id')
			->leftJoin('services_complaint_feedback_type as scft', 'scr.feedback_type_id', '=', 'scft.id')
			->leftJoin('services_complaint_feedback_source as scfs', 'scr.feedback_source_id', '=', 'scfs.id')
			->leftJoin('services_location as sl', 'scr.loc_id', '=', 'sl.id')
			->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			->leftJoin('common_room as cr', 'scr.room_id', '=', 'cr.id')
			->leftJoin('common_guest_profile as gp', 'scr.guest_id', '=', 'gp.id')
			->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('scr.property_id', '=', 'cg.property_id');
				})
			->join('common_property as cp', 'scr.property_id', '=', 'cp.id')
			->select(DB::raw('scr.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,cg.arrival, cg.departure, 
									ct.type as severitytype, cp.name as property_name, cr.room, gp.guest_name, 
									cm.name as category_name, cmsc.name as subcat_name, cm.division_id,
									scft.name as feedback, scfs.name as source, sl.name as location_name, slt.type as location_type'))
			->where('scr.id', $complaint->id)
			->where('scr.delete_flag', 0)
			->first();

		$comment_list = DB::table('services_complaint_comments as scc')
			//->join('services_compensation as comp', 'scr.item_id', '=', 'comp.id')
			->leftJoin('common_users as cu', 'scc.user_id', '=', 'cu.id')
			->where('scc.sub_id', $complaint->id)
			->select(DB::raw('scc.*,CONCAT_WS(" ", cu.first_name, cu.last_name) as commented_by'))
			->get();


		//set compensation list
		$com_list = DB::table('services_compensation_request as scr')
					->join('services_compensation as comp', 'scr.item_id', '=', 'comp.id')
					->leftJoin('common_users as cu', 'scr.provider_id', '=', 'cu.id')
					->where('scr.complaint_id', $complaint->id)
					->select(DB::raw('scr.*, comp.compensation as item_name ,CONCAT_WS(" ", cu.first_name, cu.last_name) as provider'))
					->get();



		// deprtment tags
		$department_tags = [];
		if( !empty($complaints->sent_ids) )
		{
			$department_tags = DB::table('common_department')	
				->whereRaw("id IN ($complaints->sent_ids)")
				->pluck('department');
		}
		
		$department_tags = implode(",", $department_tags);

		$currency = DB::table('property_setting as ps')
						->select(DB::raw('ps.value'))
						->where('ps.settings_key', 'currency')
						->first();

			

		$category_severity_name = $complaints->category_name . " - " . $complaints->severitytype;		
		$message_content = sprintf('A Feedback F%05d in %s has been escalated.', $complaint->id, $category_severity_name);	

		$job_role_ids = explode(',', $escalation->job_role_ids);
		if( count($job_role_ids) < 1 )
			return;

		$user_query = DB::table('common_users as cu')	
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')				
			->whereIn('cu.job_role_id', $job_role_ids)				
			->where('cd.property_id', $task_state->property_id)
			->where('cu.deleted', 0);	

		$user_email_query = clone $user_query;
		$user_email_query->groupBy('cu.email');
		$user_email_list = $user_email_query->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();

		$user_sms_query = clone $user_query;
		$user_sms_query->groupBy('cu.mobile');
		$user_sms_list = $user_sms_query->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();	


			$location_name = '';
		$info1 = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($complaint->loc_id);
		if( !empty($info) )
			$location_name = $info1->name . ' - ' . $info1->type;		


			$info = array();
			
		//	$info['wholename'] = $assignee->assinee_name;
		//	$info['user_id']= $assignee->dept_user_id;
			$info['cat_name'] = $category_severity_name;
			$info['category'] = $complaints->category_name;
			$info['subcategory'] = $complaints->subcat_name;
			$info['type'] = $complaints->feedback;
			$info['source'] = $complaints->source;
			$info['location'] = $complaints->location_name . '-' . $complaints->location_type;
			$info['raised_by'] = $complaints->wholename;
			$info['comment'] = $complaints->comment;
			$info['arrival'] = $complaints->arrival;
			$info['departure'] = $complaints->departure;
			$info['severity'] = $complaints->severitytype;
			
			$info['room'] = $complaints->room;
			$info['guest_name'] = $complaints->guest_name;
			$info['guest_type'] = $complaints->guest_type;
			$info['id']=sprintf('F%05d', $complaint->id);
			$info['intial_response'] = $complaints->initial_response;
			$info['incident_time'] = $complaints->incident_time;
			$info['status'] = $complaints->status;
			$info['solution'] = $complaints->solution;
			$info['closed_comment'] = $complaints->closed_comment;
			$info['department_tags'] = $department_tags;
			$info['comment_list'] = $comment_list;
			$info['comp_list'] = $com_list;
			$info['currency'] = $currency->value;
			$info['created_at'] = $complaints->created_at;
			
			


		$smtp = Functions::getMailSetting($task_state->property_id, 'notification_');

		if( strpos($escalation->notify_type, 'Email') !== false )
		{
			foreach($user_email_list as $row)
			{

				$info['wholename'] = $row->wholename;
				$email_content = view('emails.complaint_escalated', ['info' => $info])->render();
				$message = array();

				$message['type'] = 'email';

				if( empty($row->email) )
					continue;

				$message['to'] = $row->email;
			//	$message['subject'] = 'Complaint is escalated';
				$message['subject'] = sprintf('Feedback F%05d  has been escalated.', $complaint->id);
				$message['content'] = $email_content;

				$message['smtp'] = $smtp;

				echo 'Send Email = ' . json_encode($message) . '<br>';

				Redis::publish('notify', json_encode($message));			
			}
		}

		if( strpos($escalation->notify_type, 'SMS') !== false )
		{
			foreach($user_sms_list as $row)
			{
				$message = array();

				if( empty($row->mobile) )
					continue;

				$message['to'] = $row->mobile;
				$message['content'] = $message_content;

				echo 'Send SMS = ' . json_encode($message) . '<br>';

				Redis::publish('notify', json_encode($message));		
			}
		}
	}

	public function sendNotificationForSubcomplaintEscalated($sub, $task_state, $escalation) {
		// get primary complaint info
		$sub_complaint = ComplaintSublist::getSubcomplaintDetail($sub->id);			
			
		echo json_encode($sub_complaint);	

		// Prepare email content
		$info = array();

		$info['ticket_id'] = sprintf('F%05d%s', $sub_complaint->p_id, $sub_complaint->sub_label);	
		$info['sub'] = $sub_complaint;

		$comp_list = ComplaintSublist::getCompensationList($sub->id);

		foreach($comp_list as $key => $row)
		{
			$row->no = $key + 1;
		}		

		$info['compensation_list'] = $comp_list;


		$subject = sprintf('Escalation %d for Sub-complaint %s - %s', $escalation->level, $info['ticket_id'], $sub_complaint->department);	
		$message_content = sprintf('A sub complaint %s for %s in %s - %s has been escalated.', $info['ticket_id'], $sub_complaint->complaint, $sub_complaint->location_name, $sub_complaint->location_type);	

		$job_role_ids = explode(',', $escalation->job_role_ids);
		if( count($job_role_ids) < 1 )
			return;

		$user_query = DB::table('common_users as cu')	
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')				
			->whereIn('cu.job_role_id', $job_role_ids)				
			->where('cd.property_id', $task_state->property_id)
			->where('cu.deleted', 0);	

		$user_email_query = clone $user_query;
		$user_email_query->groupBy('cu.email');
		$user_email_list = $user_email_query->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();

		$user_sms_query = clone $user_query;
		$user_sms_query->groupBy('cu.mobile');
		$user_sms_list = $user_sms_query->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();	


		$sub_complaint->sub_type = 'escalate_subcomplaint';
		$sub_complaint->content = $message_content;

		$smtp = Functions::getMailSetting($task_state->property_id, 'notification_');

		if( strpos($escalation->notify_type, 'Email') !== false )
		{
			foreach($user_email_list as $row)
			{
				$message = array();

				$message['type'] = 'email';

				if( empty($row->email) )
					continue;

				$info['first_name'] = $row->first_name;	
				$info['last_name'] = $row->last_name;	

				$email_content = view('emails.subcomplaint_escalated_reminder', ['info' => $info])->render();	
	
				$message['to'] = $row->email;
				$message['subject'] = $subject;
				$message['content'] = $email_content;

				$message['smtp'] = $smtp;

				echo $email_content;
				echo '<br>';

				Redis::publish('notify', json_encode($message));			
			}
		}

		if( strpos($escalation->notify_type, 'SMS') !== false )
		{
			foreach($user_sms_list as $row)
			{
				$message = array();

				if( empty($row->mobile) )
					continue;

				$message['to'] = $row->mobile;
				$message['content'] = $message_content;

				echo 'Send SMS = ' . json_encode($message) . '<br>';

				Redis::publish('notify', json_encode($message));		
			}
		}
	}

	public function changeAssignee(Request $request) {
		$id = $request->get('id', 0);		
		$assignee_id = $request->get('assignee_id', 0);

		$sub = ComplaintSublist::find($id);

		$sub->assignee_id = $assignee_id;
		$sub->save();
		
		$this->sendNotificationForSubcomplaint($sub, false);

		return Response::json($sub);
	}

	public function getComments(Request $request) {
		$id = $request->get('id', 0);		

		$list = $this->getCommentData($id);

		return Response::json($list);
	}

	public function getCommentData($id) {
		$list = DB::table('services_complaint_comments as cc')
			->leftJoin('common_users as cu', 'cc.user_id', '=', 'cu.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->where('cc.sub_id', $id)
			->select(DB::raw('cc.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cu.picture, jr.job_role'))
			->get();

		return $list;	
	}

	public function addComment(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
	
		$input = array();
		
		$input['sub_id'] = $request->get('sub_id', 0);
		$input['parent_id'] = $request->get('parent_id', 0);
		$input['user_id'] = $request->get('user_id', 0);
		$input['comment'] = $request->get('comment', '');	
		$input['created_at'] = $cur_time;

		$id = DB::table('services_complaint_comments')->insertGetId($input);

		$this->sendCommentUpdateInfo($input);

		$data['id'] = $id;

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $data;

		return Response::json($ret);
	}

	public function updateComment(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$input = $request->all();

		unset($input['access_token']);
		
		DB::table('services_complaint_comments')
				->where('id', $input['id'])
				->update(
					[
						'comment' => $input['comment'],
						'created_at' => $cur_time
					]
				);

		$this->sendCommentUpdateInfo($input);

		$data['id'] = $input['id'];

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $data;

		return Response::json($ret);
	}

	public function deleteComment(Request $request) {
		$input = $request->all();

		unset($input['access_token']);

		DB::table('services_complaint_comments')
				->where('id', $input['id'])
				->delete();

		$this->sendCommentUpdateInfo($input);

		$data['id'] = $input['id'];

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $data;

		return Response::json($ret);
	}

	public function sendCommentUpdateInfo($input)
	{
		$user_id = $input['user_id'];
		$complaint = ComplaintRequest::find($input['sub_id']);
		if( !empty($complaint) )	
			$complaint->touch();	

		ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		
		$list = DB::table('services_complaint_comments as cc')
			->leftJoin('common_users as cu', 'cc.user_id', '=', 'cu.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->where('cc.sub_id', $input['sub_id'])
			->select(DB::raw('cc.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cu.picture, jr.job_role'))
			->get();

		$this->sendNotifyCounttoSubcomplaintsForMainComplaint($input['sub_id']);	

		$complaint->comment_list = $list;
		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_comment_added', $complaint, $user_id);
	}

	public function sendNotifyCounttoSubcomplaintsForMainComplaint($complaint_id) {
		// send notify sub complaint's assignee relate to this main complaint
		$sublist = DB::table('services_complaint_sublist')
			->where('parent_id', $complaint_id)
			->where('delete_flag', 0)
			->groupBy('assignee_id')
			->select('assignee_id')
			->get();

		foreach($sublist as $row) {
			CommonUserNotification::addComplaintNotifyCount($row->assignee_id, 0);	
		}	
	}

	public function getSubcomments(Request $request) {
		$id = $request->get('id', 0);		

		$list = $this->getSubcommentsData($id);

		return Response::json($list);
	}

	public function getSubcommentsData($id) {
		$list = DB::table('services_complaint_sublist_comments as csc')
			->leftJoin('common_users as cu', 'csc.user_id', '=', 'cu.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->where('csc.sub_id', $id)
			->select(DB::raw('csc.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cu.picture, jr.job_role'))
			->get();

		return $list;
	}

	public function addSubcomment(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$input = $request->all();
		$user_id = $request->get('user_id', 0);

		$input['created_at'] = $cur_time;
		$id = DB::table('services_complaint_sublist_comments')->insertGetId($input);

		$list = DB::table('services_complaint_sublist_comments as csc')
			->leftJoin('common_users as cu', 'csc.user_id', '=', 'cu.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->where('csc.sub_id', $input['sub_id'])
			->select(DB::raw('csc.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, jr.job_role, cu.picture'))
			->get();

		$sub = ComplaintSublist::find($input['sub_id']);
		if( !empty($sub) )
		{	
			$sub->touch();
			if( !empty($sub) )
			{
				$complaint = ComplaintRequest::find($sub->parent_id);				
				$complaint->save();	

				ComplaintUpdated::modifyByUser($complaint->id, $user_id);

				$sub->comment_list = $list;
				$complaint->sub = $sub;
				$this->sendRefreshEvent($complaint->property_id, 'subcomplaint_comment_added', $complaint, $user_id);					
			}
		}

		return Response::json($list);
	}

	private function changeCompensationStatus($comp, $task_state, $status)
	{
		$ret = array();

		$comp->status = $status;
		$comp->save();

		$log_type = 'Pending';
		$reason = '';

		if( $status == CP_PENDING || $status == CP_COMPLETE_APPROVE || $status == CP_REJECTED )
			$task_state->running = 0;
		else
			$task_state->running = 1;

		$status_label = ['Complete Approve', 'On-Route', 'Rejected', 'Returend', 'Pending'];
		$reason_label = ['Complete Approve', 'Approve', 'Rejected', 'Returend', 'Pending'];

		$log_type = $status_label[$status];

		$task_state->status_id = $status;
		$task_state->save();

		if( $status == CP_PENDING )
		{
			$ret['code'] = 202;
			$ret['message'] = 'There is no approval route';	
		}
		else
		{
			$ret['code'] = 200;
			$ret['message'] = '';		
		}

		$this->sendNotifyForApproveStatus($task_state, '', 0);
		
		$ret['log_type'] = $log_type;
		$ret['reason'] = $reason_label[$status];

		return $ret;
	}
	
	public function postCompensationUI($data) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $data['id'];
		$compensation_id = $data['compensation_id'];
		$user_id = $data['user_id'];
		$dept_id = $data['dept_id'];		
		$cost = $data['cost'];
		$comment = $data['comment'];
	//	$total = $data['total'];

		$job_role_id = 0;
		$log_type = '';
		$reason = '';

		$complaint = ComplaintRequest::find($id);

		$ret = array();
		$ret['code'] = 200;

		if( empty($complaint) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Complaint';
			return Response::json($ret);
		}

		$comp = new CompensationRequest();

		$comp->complaint_id = $id;
		$comp->item_id = $compensation_id;
		$comp->cost = $cost;
		$comp->provider_id = $user_id;
		$comp->dept_id = $dept_id;
		$comp->comment = $comment;
		$comp->status = CP_ON_ROUTE;
		$comp->save();


		$input = array();

		// create compensation tracker
		$compensation_item = CompensationItem::find($comp->item_id);
		if( !empty($compensation_item) )			 
		{
			$compensation_item->cost = $cost;
			$compensation_item->save();
		}
		
		if( !empty($compensation_item) && $compensation_item->approval_route_id > 0 )	// need to be approve
		{
			// task state
			$task_state = new CompensationState();

			$task_state->task_id = $id;
			$task_state->comp_id = $comp->id;
			$task_state->approval_route_id = $compensation_item->approval_route_id;
			$task_state->start_time = $cur_time;
			$task_state->status_id = $comp->status;
			$task_state->dispatcher = 0;
			$task_state->attendant = $user_id;
			$task_state->running = 1;

			$escalation_levels = DB::table('services_approval_route_members as arm')
				->where('arm.property_id', $compensation_item->property_id)	// compensation's property
				->where('arm.approval_route_id', $task_state->approval_route_id)
				->orderBy('arm.level', 'asc')
				->first();

			if( empty($escalation_levels) )	// there is no escalation
			{
				$ret = $this->changeCompensationStatus($comp, $task_state, CP_PENDING);
				$log_type = $ret['log_type'];	
				$reason = $ret['reason'];	 	
			}
			else
			{
				// find escalation staff_id
				$job_role_id = $escalation_levels->job_role_id;

				if( $job_role_id >= 0 )		// there is approver
				{
					$task_state->level = $escalation_levels->level;
					$task_state->dispatcher = $job_role_id;
					$end_time = new DateTime($cur_time);
					$end_time->add(new DateInterval('PT' . $escalation_levels->max_time . 'S'));
					$task_state->end_time = $end_time->format('Y-m-d H:i:s');

					$task_state->save();

					$this->sendNotifyForCompensation($task_state, $comment);

					CommonUserNotification::addComplaintNotifyCountWithJobRole($complaint->property_id, $job_role_id);

					$log_type = 'On-Route';
					$reason = 'Approve Request';
				}
				else	// There is no staff
				{
					// change task to pending
					$ret = $this->changeCompensationStatus($comp, $task_state, CP_PENDING);
					$log_type = $ret['log_type'];
					$reason = $ret['reason'];	
				}
			}
			
		}
		else
		{
			if( !($compensation_item->approval_route_id > 0) )	// no need approve
			{
				$comp->status = CP_COMPLETE_APPROVE; 	
				$comp->save();
				$log_type = 'Complete Approve';
				
				$reason = 'Complete Approve';
			}
			else
			{
				$log_type = 'Pending';
				// $this->saveNotification($task->attendant, $task->id, 'Modification');
				$ret['code'] = 204;
				$ret['message'] = 'Invalid compensation item';			
			}			
		}

		if( !empty($task_state) )
		{
			$input['task_id'] = $complaint->id;
			$input['parent_id'] = 0;
			$input['user_id'] = $user_id;
			$input['reason'] = $reason;
			$input['comment'] = $comment;
			$this->addCommentToApproval($input);
		}
		
		// save log
		$task_log = new Tasklog();
		$task_log->task_id = $complaint->id;
		$task_log->user_id = $job_role_id;
		$task_log->comment = "";
		$task_log->log_type = $log_type;
		$task_log->log_time = $cur_time;

		$task_log->save();

		$comp_total = CompensationRequest::where('complaint_id', $id)
			->select(DB::raw('SUM(cost) as total'))
			->first();

		if( !empty($comp_total->total) )
			$complaint->compensation_total = $comp_total->total;

		$complaint->save();

		ComplaintUpdated::modifyByUser($complaint->id, $user_id);

		$ret['complaint'] = $complaint;
		$ret['comp_list'] = $this->getCompensationListData($id);

		$complaint->comp_list = $ret['comp_list'];
 
		return Response::json($ret);
	}

	public function postCompensation(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id', 0);
		$compensation_id = $request->get('compensation_id', 0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);
		$provider_id = $request->get('provider_id', 0);
		$dept_id = $request->get('dept_id', 0);
		$cost = $request->get('cost', 0);
		$total = $request->get('total', 0);

		$job_role_id = 0;
		$log_type = '';
		$reason = '';

		$complaint = ComplaintRequest::find($id);

		$ret = array();
		$ret['code'] = 200;

		if( empty($complaint) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Complaint';
			return Response::json($ret);
		}

		$comp = new CompensationRequest();

		$comp->complaint_id = $id;
		$comp->item_id = $compensation_id;
		$comp->cost = $cost;
		$comp->dept_id = $dept_id;
		$comp->provider_id = $provider_id;
		$comp->comment = $comment;
		$comp->status = CP_ON_ROUTE;
		$comp->save();
		
		$input = array();

		// create compensation tracker
		$compensation_item = CompensationItem::find($comp->item_id);
		if( !empty($compensation_item) )			 
		{
			$compensation_item->cost = $cost;
			$compensation_item->save();
		}
		
		if( !empty($compensation_item) && $compensation_item->approval_route_id > 0 )	// need to be approve
		{
			// task state
			$task_state = new CompensationState();

			$task_state->task_id = $id;
			$task_state->comp_id = $comp->id;
			$task_state->approval_route_id = $compensation_item->approval_route_id;
			$task_state->start_time = $cur_time;
			$task_state->status_id = $comp->status;
			$task_state->dispatcher = 0;
			$task_state->attendant = $user_id;
			$task_state->running = 1;

			$escalation_levels = DB::table('services_approval_route_members as arm')
				->where('arm.property_id', $compensation_item->property_id)	// compensation's property
				->where('arm.approval_route_id', $task_state->approval_route_id)
				->orderBy('arm.level', 'asc')
				->first();

			if( empty($escalation_levels) )	// there is no escalation
			{
				$data = $this->changeCompensationStatus($comp, $task_state, CP_PENDING);
				$log_type = $data['log_type'];	
				$reason = $data['reason'];	 	
			}
			else
			{
				// find escalation staff_id
				$job_role_id = $escalation_levels->job_role_id;

				if( $job_role_id >= 0 )		// there is approver
				{
					$task_state->level = $escalation_levels->level;
					$task_state->dispatcher = $job_role_id;
					$end_time = new DateTime($cur_time);
					$end_time->add(new DateInterval('PT' . $escalation_levels->max_time . 'S'));
					$task_state->end_time = $end_time->format('Y-m-d H:i:s');

					$task_state->save();

					$this->sendNotifyForCompensation($task_state, $comment);

					CommonUserNotification::addComplaintNotifyCountWithJobRole($complaint->property_id, $job_role_id);

					$log_type = 'On-Route';
					$reason = 'Approve Request';
				}
				else	// There is no staff
				{
					// change task to pending
					$data = $this->changeCompensationStatus($comp, $task_state, CP_PENDING);
					$log_type = $data['log_type'];	
					$reason = $data['reason'];	 	
				}
			}
			
		}
		else
		{
			if( !($compensation_item->approval_route_id > 0) )	// no need approve
			{
				$comp->status = CP_COMPLETE_APPROVE; 	
				$comp->save();
				$log_type = 'Complete Approve';
				
				$reason = 'Complete Approve';
			}
			else
			{
				$log_type = 'Pending';
				// $this->saveNotification($task->attendant, $task->id, 'Modification');
				$ret['code'] = 204;
				$ret['message'] = 'Invalid compensation item';			
			}			
		}

		if( !empty($task_state) )
		{
			$input['task_id'] = $complaint->id;
			$input['parent_id'] = 0;
			$input['user_id'] = $user_id;
			$input['reason'] = $reason;
			$input['comment'] = $comment;
			$this->addCommentToApproval($input);
		}
		
		// save log
		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $complaint->id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = $comment;
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();	


		$comp_total = CompensationRequest::where('complaint_id', $id)
			->select(DB::raw('SUM(cost) as total'))
			->first();

		if( !empty($comp_total->total) )
			$complaint->compensation_total = $comp_total->total;

		$complaint->save();

		ComplaintUpdated::modifyByUser($complaint->id, $user_id);

		$data = array();
		$data['complaint'] = $complaint;
		$data['user_id'] = $user_id;
		$data['comp_list'] = $this->getCompensationListData($id);

		$complaint->comp_list = $data['comp_list'];

		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_compensation_create', $complaint, $user_id);
		
		$ret['content'] = $data;

		return Response::json($ret);
	}	

	public function deleteMainCompensation(Request $request) {		
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$user_id = $request->get('user_id', 0);
		$id = $request->get('id', 0);

		$comp = CompensationRequest::find($id);
		$complaint = ComplaintRequest::find($comp->complaint_id);
		$compensation_item = CompensationItem::find($comp->item_id);

		DB::table('services_compensation_state')->where('comp_id', $id)->delete();
		DB::table('services_compensation_request')->where('id', $id)->delete();

		// save log
		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $complaint->id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = "Main Compensation $compensation_item->compensation, Cost = $comp->cost is deleted";
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();	

		$comp_total = CompensationRequest::where('complaint_id', $complaint->id)
			->select(DB::raw('SUM(cost) as total'))
			->first();

		if( !empty($comp_total->total) )
			$complaint->compensation_total = $comp_total->total;
		else{
			$comp_total->total = 0;
			$complaint->compensation_total = $comp_total->total;
		}

		$complaint->save();

		ComplaintUpdated::modifyByUser($complaint->id, $user_id);

		$ret['complaint'] = $complaint;
		$ret['user_id'] = $user_id;
		$ret['comp_list'] = $this->getCompensationListData($complaint->id);

		$complaint->comp_list = $ret['comp_list'];

		$this->sendRefreshEvent($complaint->property_id, 'maincomplaint_compensation_create', $complaint, $user_id);
 
		return Response::json($ret);
	}	
	
	
	public function addCompensationType(Request $request) {
		$input = $request->all();		

		$setting = PropertySetting::getComplaintSetting($input['property_id']);
		// $input['approval_route_id'] = $setting['default_approval_route'];
		$input['approval_route_id'] = 0;

		$id = DB::table('services_compensation')->insertGetId($input);

		$list = DB::table('services_compensation as sc')
			->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')			
			->where('sc.client_id', $input['client_id'])
			->select(DB::raw('sc.*, cp.name'))
			->get();	


		$ret['code'] = 200;
		$ret['compensation_id'] = $id;
		$ret['list'] = $list;

		return Response::json($ret);
	}

	public function getOnrouteMylist(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', 4);
		$dispatcher = $request->get('dispatcher', 0);		
		$dept_id = $request->get('dept_id', 0);
		$job_role_id = $request->get('job_role_id', 0);

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');		
		$filter = $request->get('filter','Total');

		$date = new DateTime($cur_time);
		$date->sub(new DateInterval('P1D'));
		$last_time = $date->format('Y-m-d H:i:s');

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();
		$query = DB::table('services_compensation_state as scs')
			->join('services_complaint_request as scr', 'scs.task_id', '=', 'scr.id')
			->join('services_compensation_request as sc', 'scs.comp_id', '=', 'sc.id')
			->leftJoin('common_room as cr', 'scr.room_id', '=', 'cr.id')			
			->leftJoin('common_employee as ce', 'scr.requestor_id', '=', 'ce.id')
			->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
			->leftJoin('common_guest_profile as gp', 'scr.guest_id', '=', 'gp.id')
			->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
//			->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
			->leftJoin('common_guest as cg', function($join) {
				$join->on('gp.guest_id', '=', 'cg.guest_id');
				$join->on('scr.property_id', '=', 'cg.property_id');
			})
			->leftJoin('services_compensation as comp', 'sc.item_id', '=', 'comp.id')
			->where('scs.dispatcher', $job_role_id)
			->where('scr.property_id', $property_id)
			->where('scs.running', 1)
			->where('scr.delete_flag', 0)
			->whereIn('scs.status_id', array(CP_ON_ROUTE));

		// ->where('time', '>', $last_time);	

		$data_query = clone $query;
		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('scs.*, scr.id as task_id, scr.guest_type, gp.guest_name, scr.loc_id, cr.room, scr.created_at,  scr.comment, sc.cost, comp.compensation, sc.comment,
					gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, gp.passport, cg.arrival, cg.departure'))
				->skip($skip)->take($pageSize)
				->get();

		foreach($data_list as $key => $row) {
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			if( !empty($info) )
			{
				$data_list[$key]->lgm_name = $info->name;
				$data_list[$key]->lgm_type = $info->type;
			}
		}		

		$count_query = clone $query;
		$totalcount = $count_query->count();

		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);		
	}

	private function addCommentToApproval($input) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$input['created_at'] = $cur_time;
		$id = DB::table('services_compensation_comments')->insertGetId($input);

		$complaint = ComplaintSublist::find($input['task_id']);
		if( !empty($complaint) )	
			$complaint->touch();

		return Response::json($id);
	}

	public function getCompensationCommentListData($id) {
		$list = DB::table('services_compensation_comments as scc')
			->leftJoin('common_users as cu', 'scc.user_id', '=', 'cu.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->where('scc.comp_id', $id)
			->select(DB::raw('scc.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cu.picture, jr.job_role'))
			->get();

		return $list;
	}

	public function getCompensationComments(Request $request) {
		$id = $request->get('id', 0);		

		$list = $this->getCompensationCommentListData($id);

		return Response::json($list);
	}

	public function approve(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', 4);
		$user_id = $request->get('user_id', 0);		
		$ticket_id = $request->get('id', 0);
		$comment = $request->get('comment', '');

		$ret = array();
		$ret['code'] = 200;

		$task_state = CompensationState::find($ticket_id);
		if( empty($task_state) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Compensation';

			return Response::json($ret);
		}

		$complaint = ComplaintRequest::find($task_state->task_id);
		$comp = CompensationRequest::find($task_state->comp_id);

		if( empty($complaint) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Complaint';
			return Response::json($ret);
		}

		$job_role_id = 0;
		$log_type = '';
		$reason = 'Approve';


		// create compensation tracker
		if( $task_state->approval_route_id > 0 )	// need to be approve
		{
			// task state
			$task_state->start_time = $cur_time;
			$task_state->attendant = $complaint->requestor_id;

			$escalation_levels = DB::table('services_approval_route_members as arm')
				->where('arm.property_id', $complaint->property_id)
				->where('arm.approval_route_id', $task_state->approval_route_id)
				->where('arm.level', '>', $task_state->level)
				->orderBy('arm.level', 'asc')
				->first();

			if( empty($escalation_levels) )
			{
				$ret = $this->changeCompensationStatus($comp, $task_state, CP_COMPLETE_APPROVE);
				$log_type = $ret['log_type'];
				$reason = $ret['reason'];		
				$task_state->delete();
			}
			else
			{
				// find escalation staff_id
				$job_role_id = $escalation_levels->job_role_id;

				if( $job_role_id >= 0 )		// there is approver
				{
					$task_state->level = $escalation_levels->level;
					$task_state->dispatcher = $job_role_id;
					$end_time = new DateTime($cur_time);
					$end_time->add(new DateInterval('PT' . $escalation_levels->max_time . 'S'));
					$task_state->end_time = $end_time->format('Y-m-d H:i:s');

					// $this->saveNotification($assigned_id, $task_state->task_id, 'Escalation');

					$task_state->save();

					$this->sendNotifyForCompensation($task_state, $comment);
					$this->sendNotifyForApproveStatus($task_state, '', $user_id);

					CommonUserNotification::addComplaintNotifyCountWithJobRole($complaint->property_id, $job_role_id);

					$log_type = 'On-Route';
					$reason = 'Approve';
				}
				else	// There is no staff
				{
					// change task to pending
					$ret = $this->changeCompensationStatus($complaint, $task_state, CP_PENDING);
					$log_type = $ret['log_type'];	
					$reason = $ret['reason'];

					// find default approver
					$approval_route = CompensationApproveRoute::find($task_state->approval_route_id);
					if( !empty($approval_route) )
					{
						// $user_id = $approval_route->default_approver;
						// $this->saveNotification($approval_route->default_approver, $task_state->task_id, 'Modification');
					}
					else {
						// $this->saveNotification($task->attendant, $task_state->task_id, 'Modification');
					}
				}
			}			
		}		

		if( !empty($task_state) )
		{
			$input['task_id'] = $complaint->id;
			$input['comp_id'] = $task_state->comp_id;
			$input['parent_id'] = 0;
			$input['user_id'] = $user_id;
			$input['reason'] = $reason;
			$input['comment'] = $comment;
			$this->addCommentToApproval($input);
		}
		

		// send notification
		// $this->saveNotification($task->dispatcher, $task->id, 'Assignment');

		// save log
		$task_log = new Tasklog();
		$task_log->task_id = $complaint->id;
		$task_log->user_id = $job_role_id;
		$task_log->comment = $comment;
		$task_log->log_type = $log_type;
		$task_log->log_time = $cur_time;

		$task_log->save();

		return Response::json($ret);
	}

	public function rejectCompensation(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', 4);
		$user_id = $request->get('user_id', 0);		
		$ticket_id = $request->get('id', 0);
		$comment = $request->get('comment', '');

		$ret = array();
		$ret['code'] = 200;

		$task_state = CompensationState::find($ticket_id);
		if( empty($task_state) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Compensation';

			return Response::json($ret);
		}

		$complaint = ComplaintRequest::find($task_state->task_id);
		$comp = CompensationRequest::find($task_state->comp_id);

		if( empty($complaint) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Complaint';
			return Response::json($ret);
		}

		$job_role_id = 0;
		$log_type = '';
		$reason = 'Approve';

		$ret = $this->changeCompensationStatus($comp, $task_state, CP_REJECTED);
		$log_type = $ret['log_type'];
		$reason = $ret['reason'];

		// send notification
		// $this->saveNotification($task->dispatcher, $task->id, 'Assignment');

		if( !empty($task_state) )
		{
			$input['task_id'] = $complaint->id;
			$input['comp_id'] = $comp->id;
			$input['parent_id'] = 0;
			$input['user_id'] = $user_id;
			$input['reason'] = $reason;
			$input['comment'] = $comment;
			$this->addCommentToApproval($input);
		}

		// save log
		$task_log = new Tasklog();
		$task_log->task_id = $complaint->id;
		$task_log->user_id = $job_role_id;
		$task_log->comment = $comment;
		$task_log->log_type = $log_type;
		$task_log->log_time = $cur_time;

		$task_log->save();

		return Response::json($ret);
	}

	public function returnCompensation(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', 4);
		$user_id = $request->get('user_id', 0);		
		$ticket_id = $request->get('id', 0);
		$comment = $request->get('comment', '');

		$ret = array();
		$ret['code'] = 200;

		$task_state = CompensationState::find($ticket_id);
		if( empty($task_state) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Compensation';

			return Response::json($ret);
		}

		$complaint = ComplaintRequest::find($task_state->task_id);
		$comp = CompensationRequest::find($task_state->comp_id);

		if( empty($complaint) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Complaint';
			return Response::json($ret);
		}

		$job_role_id = 0;
		$log_type = '';
		$reason = 'Returend';

		$ret = $this->changeCompensationStatus($comp, $task_state, CP_RETURNED);
		$log_type = $ret['log_type'];
		$reason = $ret['reason'];

		// send notification
		// $this->saveNotification($task->dispatcher, $task->id, 'Assignment');

		if( !empty($task_state) )
		{
			$input['task_id'] = $complaint->id;
			$input['comp_id'] = $comp->id;
			$input['parent_id'] = 0;
			$input['user_id'] = $user_id;
			$input['reason'] = $reason;
			$input['comment'] = $comment;

			$this->addCommentToApproval($input);
		}


		// save log
		$task_log = new Tasklog();
		$task_log->task_id = $complaint->id;
		$task_log->user_id = $job_role_id;
		$task_log->comment = $comment;
		$task_log->log_type = $log_type;
		$task_log->log_time = $cur_time;

		$task_log->save();

		return Response::json($ret);
	}

	public function addCommentToReturn(Request $request) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$comp_id = $request->get('id', 0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);

		$log_type = 'Comment to Return';
		$reason = 'Comment to Return';

		$ret = array();
		$ret['code'] = 200;

		$task_state = CompensationState::where('comp_id', $comp_id)
			->where('status_id', CP_RETURNED)
			->first();

		if( empty($task_state) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Compensation';

			return Response::json($ret);
		}

		$complaint = ComplaintRequest::find($task_state->task_id);
		$comp = CompensationRequest::find($task_state->comp_id);

		if( empty($complaint) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Complaint';
			return Response::json($ret);
		}

		$task_state->start_time = $cur_time;
		$task_state->status_id = CP_ON_ROUTE;
		$task_state->running = 1;

		$escalation_levels = DB::table('services_approval_route_members as arm')
				->where('arm.property_id', $complaint->property_id)		// compensation's property
				->where('arm.approval_route_id', $task_state->approval_route_id)
				->where('arm.level', '>=', $task_state->level)
				->orderBy('arm.level', 'asc')
				->first();

		if( empty($escalation_levels) )	// there is no escalation
		{
			$ret = $this->changeCompensationStatus($comp, $task_state, CP_PENDING);
			$log_type = $ret['log_type'];	
			$reason = $ret['reason'];	 	
		}
		else
		{
			// find escalation staff_id
			$job_role_id = $escalation_levels->job_role_id;

			if( $job_role_id >= 0 )		// there is approver
			{
				$task_state->level = $escalation_levels->level;
				$task_state->dispatcher = $job_role_id;
				$end_time = new DateTime($cur_time);
				$end_time->add(new DateInterval('PT' . $escalation_levels->max_time . 'S'));
				$task_state->end_time = $end_time->format('Y-m-d H:i:s');

				$task_state->save();

				$this->sendNotifyForCompensation($task_state, $comment);

				$ret = $this->changeCompensationStatus($comp, $task_state, CP_ON_ROUTE);
				CommonUserNotification::addComplaintNotifyCountWithJobRole($complaint->property_id, $job_role_id);
				
				$log_type = 'On-Route';
				$reason = 'Comment to Return';
			}			
		}

		// send notification
		// $this->saveNotification($task->dispatcher, $task->id, 'Assignment');

		if( !empty($task_state) )
		{
			$input['task_id'] = $complaint->id;
			$input['comp_id'] = $comp->id;
			$input['parent_id'] = 0;
			$input['user_id'] = $user_id;
			$input['reason'] = $reason;
			$input['comment'] = $comment;
			
			$this->addCommentToApproval($input);
		}

		// save log
		$task_log = new Tasklog();
		$task_log->task_id = $complaint->id;
		$task_log->user_id = $job_role_id;
		$task_log->comment = $comment;
		$task_log->log_type = $log_type;
		$task_log->log_time = $cur_time;

		$task_log->save();

		$ret['complaint'] = $complaint;
		$ret['comment_list'] = $this->getCompensationCommentListData($comp->id);
 
		return Response::json($ret);
	}	

	private function sendNotifyForCompensation($task_state, $comment) {
		$complaint = DB::table('services_compensation_state as scs')
			->join('services_complaint_request as sc', 'scs.task_id', '=', 'sc.id')
			->join('services_compensation_request as scr', 'scs.comp_id', '=', 'scr.id')
			->leftJoin('common_users as cu', 'scs.attendant', '=', 'cu.id')
			->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')
			->leftJoin('services_compensation as comp', 'scr.item_id', '=', 'comp.id')
			->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
			->select(DB::raw('sc.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cp.name as property_name, scs.dispatcher as job_role_id, comp.compensation, sc.property_id, ct.type'))	// compensation's property
			->where('scs.id', $task_state->id)
			->where('sc.delete_flag', 0)
			->first();

		if( empty($complaint) )
			return $complaint->id;

		$complaint->sub_type = 'post_compensation';
		$complaint->comment = $comment;

		// find duty manager		
		$location_name = '';	
		$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($complaint->loc_id);
		if( !empty($info) )
			$location_name = $info->name . ' - ' . $info->type;			

		$message_content = sprintf('Please Approve Compensation %s raised by %s for Complaint C%05d', $complaint->compensation, $complaint->wholename, $complaint->id);	

		$complaint->content = $message_content;

		$user_list = DB::table('common_users as cu')
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->where('cu.job_role_id', $complaint->job_role_id)
			->where('cd.property_id', $complaint->property_id)
			->where('cu.deleted', 0)
			->select(DB::raw('cu.*'))
			->get();

		foreach($user_list as $user) {
			$complaint->assignee_id = $user->id;	
			$notify_flag = $this->canReceiveNotification($complaint, $user->id, 'compensation_change', $complaint->type);
			if( $notify_flag == false )
				continue;

			$this->sendComplaintNotification($complaint->property_id, $message_content, $complaint->comment, $complaint, $user->email, $user->mobile, $user->fcm_key, true, true,NULL);	

			$delegated_user = ShiftGroupMember::getDelegatedUser($user->id);
			if( !empty($delegated_user) )
			{
				$this->sendComplaintNotification($complaint->property_id, $message_content, $complaint->comment, $complaint, $delegated_user->email, $delegated_user->mobile, $delegated_user->fcm_key, false, true,NULL);					
			} 
		}	

		return $message_content;
	}

	private function sendNotifyForApproveStatus($task_state, $comment, $user_id) {
		$complaint = DB::table('services_compensation_state as scs')
			->join('services_complaint_request as sc', 'scs.task_id', '=', 'sc.id')
			->join('services_compensation_request as scr', 'scs.comp_id', '=', 'scr.id')
			->leftJoin('common_users as cu', 'scs.attendant', '=', 'cu.id')
			->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')			
			->leftJoin('services_compensation as comp', 'scr.item_id', '=', 'comp.id')
			->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
			->select(DB::raw('sc.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cp.name as property_name, scs.dispatcher as job_role_id, comp.compensation, scs.attendant, ct.type'))
			->where('scs.id', $task_state->id)
			->where('sc.delete_flag', 0)
			->first();

		if( empty($complaint) )
			return $complaint->id;

		$complaint->sub_type = 'compensation_approve';
		$complaint->comment = $comment;

		// find duty manager		
		$location_name = '';	
		$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($complaint->loc_id);
		if( !empty($info) )
			$location_name = $info->name . ' - ' . $info->type;			

		$message_content = '';
		if( $task_state->status_id == CP_REJECTED )
		{
			$message_content = sprintf('Compensation for complaint C%05d has been rejected.', $complaint->id);		
		}
		if( $task_state->status_id == CP_RETURNED )
		{
			$message_content = sprintf('Compensation for complaint C%05d has been returned for more details.', $complaint->id);
		}
		if( $task_state->status_id == CP_COMPLETE_APPROVE )
		{
			$message_content = sprintf('Compensation for complaint C%05d has been Completely Approved.', $complaint->id);
		}

		if( $task_state->status_id == CP_ON_ROUTE )
		{
			$by_user_name = '';
			$next_user_name = '';

			$by_user = DB::table('common_users as cu')
				->where('cu.id', $user_id)
				->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->first();

			$next_user = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('cu.job_role_id', $task_state->dispatcher)
				->where('cd.property_id', $complaint->property_id)
				->where('cu.deleted', 0)
				->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->first();

			if( !empty($by_user) )
				$by_user_name = $by_user->wholename;	

			if( !($user_id > 0) )
				$by_user_name = 'System';				

			if( !empty($next_user) )
				$next_user_name = $next_user->wholename;	

			$message_content = sprintf('Compensation for complaint %05d has been approved by %s. Next Approver : %s', $complaint->id, $by_user_name, $next_user_name);
		}

		$complaint->content = $message_content;

		$user = DB::table('common_users as cu')
			->where('cu.id', $complaint->attendant)
			->select(DB::raw('cu.*'))
			->first();

		$notify_flag = $this->canReceiveNotification($complaint, $complaint->attendant, 'compensation_change', $complaint->type);	

		if( !empty($user) && $notify_flag == true)
		{
			$complaint->assignee_id = $user->id;			
			$this->sendComplaintNotification($complaint->property_id, $message_content, $complaint->comment, $complaint, $user->email, $user->mobile, $user->fcm_key, true, true,NULL);		

			$delegated_user = ShiftGroupMember::getDelegatedUser($user->id);
			if( !empty($delegated_user) )
			{
				$this->sendComplaintNotification($complaint->property_id, $message_content, $complaint->comment, $complaint, $delegated_user->email, $delegated_user->mobile, $delegated_user->fcm_key, false, true, NULL);						
			}
		}

		return $message_content;
	}

	public function getCompensationListForSubcomplaint(Request $request) {
		$sub_id = $request->get('sub_id', 0);
		
		$comp_list = ComplaintSubList::getCompensationList($sub_id);
		
		return Response::json($comp_list);
	}

	private function sendSubcomplaintCompensationInformation($sub_id, $user_id)
	{
		$complaint = DB::table('services_complaint_sublist as scs')
			->join('services_complaint_request as scr', 'scs.parent_id', '=', 'scr.id')
			->where('scs.id', $sub_id)
			->where('scs.delete_flag', 0)
			->where('scr.delete_flag', 0)
			->select(DB::raw('scr.*, scs.id as sub_id'))
			->first();

		ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		ComplaintRequest::updateSubcompTotal($complaint->id);

		$complaint->sub_comp_list = ComplaintSubList::getCompensationList($sub_id);

		$subcomplaint_total = DB::table('services_complaint_sublist_compensation as scsc')
					->join('services_complaint_sublist as scs', 'scsc.sub_id', '=', 'scs.id')
					->where('scs.parent_id', $complaint->id)
					->where('scs.delete_flag',0)
					->select(DB::raw('sum(scsc.cost)  as total'))
					->first(); 

		if( empty($subcomplaint_total->total) )			
			$complaint->subcomp_total = 0;	
		else
			$complaint->subcomp_total = $subcomplaint_total->total;	

		$sub_compen_total = DB::table('services_complaint_sublist_compensation as scsc')			
			->where('scsc.sub_id', $sub_id)
			->select(DB::raw('sum(scsc.cost)  as total'))
			->first();
		$complaint->sub_item_total = $sub_compen_total->total;			

		$this->sendRefreshEvent($complaint->property_id, 'subcomplaint_compensation_create', $complaint, $user_id);
	}
	
	public function addCompensationForSubcomplaint(Request $request) {
		$sub_id = $request->get('sub_id', 0);
		$user_id = $request->get('user_id', 0);
		$compensation_id = $request->get('id', 0);
		$cost = $request->get('cost', 0);
		$sub_provider_id = $request->get('sub_provider_id', 0);
		
		$comp = new ComplaintSublistCompensation();

		$comp->sub_id = $sub_id;
		$comp->compensation_id = $compensation_id;
		$comp->cost = $cost;
		$comp->sub_provider_id = $sub_provider_id;

		$comp->save();

		$this->sendSubcomplaintCompensationInformation($sub_id, $user_id);

		return Response::json($comp);
	}

	public function addCompensationListForSubcomplaint(Request $request) {
		$sub_id = $request->get('sub_id', 0);
		$user_id = $request->get('user_id', 0);
		$comp_list = $request->get('comp_list', []);
		
		foreach($comp_list as $row)
		{
			$comp = new ComplaintSublistCompensation();

			$comp->sub_id = $sub_id;
			$comp->compensation_id = $row['compensation_id'];
			$comp->cost = $row['cost'];
			$comp->sub_provider_id = $row['sub_provider_id'];

			$comp->save();	
		}
		
		$this->sendSubcomplaintCompensationInformation($sub_id, $user_id);
		
		return Response::json($comp_list);
	}

	public function deleteCompensationForSubcomplaint(Request $request) {
		$id = $request->get('id', 0);
		$user_id = $request->get('user_id', 0);
		
		$comp = ComplaintSublistCompensation::find($id);
		$comp_item = CompensationItem::find($comp->compensation_id);
		$comp->delete();

		$sub = ComplaintSublist::find($comp->sub_id);

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $sub->parent_id;
		$complaint_log->sub_id = $sub->id;
		$complaint_log->comment = "Delete Compensation For Sub Complaint. Item = $comp_item->compensation, Cost = AED $comp->cost";
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$this->sendSubcomplaintCompensationInformation($comp->sub_id, $user_id);

		return Response::json($comp);
	}

	private function removeOldBriefing($property_id) {
		DB::table('services_complaint_briefing')			
			->where('property_id', $property_id)
			->delete();
	}

	private function markAsDiscussed($property_id) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$list = DB::table('services_complaint_briefing')			
			->where('property_id', $property_id)
			->where('discussed_flag', 1)			
			->select(DB::raw('complaint_id'))
			->get();

		$selected_ids = array();
		$category_ids = array();

		foreach($list as $row)
		{			
			array_push($selected_ids, $row->complaint_id);

			$complaint = ComplaintRequest::find($row->complaint_id);
			if($complaint->category_id > 0 )
				$category_ids[] = $complaint->category_id;
		}	

		$input = array('discussed_flag' => 1, 'discuss_end_time' => $cur_time);

		// update individual complaint
		DB::table('services_complaint_request')			
			->whereIn('id', $selected_ids)	
			->where('delete_flag', 0)		
			->update($input);		

		// update all complaint for selected category	
		DB::table('services_complaint_request')			
			->whereIn('category_id', $category_ids)		
			->where('discussed_flag', 0)	
			->where('delete_flag', 0)
			->update($input);				
	}

	private function markAsStartedBriefing($selected_ids) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		
		$input = array('discuss_start_time' => $cur_time);

		DB::table('services_complaint_request')			
			->whereIn('id', $selected_ids)
			->where('delete_flag', 0)
			->update($input);			
	}


	public function startBriefing(Request $request) {
		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 4);
		$selected_ids = $request->get('selected_ids', []);

		$this->removeOldBriefing($property_id);
		
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		$ret['code'] = 200;

		foreach($selected_ids as $row)
		{
			$input = array();

			$input['complaint_id'] = $row;		
			$input['property_id'] = $property_id;	
			DB::table('services_complaint_briefing')->insertGetId($input);
		}

		$this->markAsStartedBriefing($selected_ids);

		$this->notifyNextBriefing($property_id, false, 0, 1, $user_id);

		$history = new ComplaintBriefingHistory();
		$history->property_id = $property_id;
		$history->discussed_complaints = implode(',', $selected_ids);
		$history->participants = '';
		$history->start_time = $cur_time;

		$history->save();

		return $this->getBriefingProgressList($request);
	}	

	public function endBriefing(Request $request) {
		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 4);

		ComplaintBriefingHistory::endBriefing($property_id);
		
		$this->markAsDiscussed($property_id);		

		$this->sendBriefingSummary($property_id);

		$this->removeOldBriefing($property_id);

		$this->notifyNextBriefing($property_id, true, 0, 1, $user_id);
		
		$ret['code'] = 200;

		return Response::json($ret);
	}

	// http://192.168.1.253/test/briefingsummary?property_id=4
	public function testSendBriefingSummary(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$message = $this->sendBriefingSummary($property_id);

		echo json_encode($message);
	}

	public function sendBriefingSummary($property_id) {
		date_default_timezone_set(config('app.timezone'));

		$setting = PropertySetting::getComplaintSetting($property_id);

		if( $setting['complaint_briefing_summary'] != 1 )
			return;

		$query = DB::table('services_complaint_briefing as scb')
				->join('services_complaint_request as sc', 'scb.complaint_id', '=', 'sc.id')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
				// ->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc.property_id', '=', 'cg.property_id');
				})	
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')			
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
				->leftJoin('common_house_complaints_category as hcc', 'sc.housecomplaint_id', '=', 'hcc.id')			
				->leftJoin('services_complaint_reminder as scr', 'sc.id', '=', 'scr.id')
				->leftJoin('services_complaint_maincategory as cmc', 'sc.category_id', '=', 'cmc.id')
				->leftJoin('services_complaint_feedback_type as scft', 'sc.feedback_type_id', '=', 'scft.id')
				->leftJoin('services_location as sl', 'sc.loc_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
				->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
				->where('sc.delete_flag',0);

		$query->where('scb.property_id', $property_id);

		$data = array();
		$data['report_type'] = 'Summary';
		$data['report_by'] = 'Summary';
		$data['property_id'] = $property_id;
		$data['start_date'] = date('Y-m-d');
		$data['end_date'] = date('Y-m-d');
		$data['property'] = Property::find($property_id);		
		
		$this->getComplaintSummaryDataList($query, $data);

		$filename = 'Summary_Complaint_Report_Briefing' . '_' . date('d_M_Y_H_i');
		$filename = str_replace(' ', '_', $filename);

		$folder_path = public_path() . '/uploads/reports/';
		$path = $folder_path . $filename . '.html';
		$pdf_path = $folder_path . $filename . '.pdf';

		ob_start();

		$content = view('frontend.report.complaint_summary_pdf', compact('data'))->render();
		echo $content;

		file_put_contents($path, ob_get_contents());

		ob_clean();

		$participants = ComplaintBriefingHistory::getParticipantsList($property_id);
		if( count($participants) < 1 )
			return 'No Participants';

		$email_list = [];
		foreach($participants as $row)
			$email_list[] = $row->email;

		$request = array();
		$request['filename'] = $filename . '.pdf';
		$request['folder_path'] = $folder_path;
		$request['to'] = implode(',', $email_list);

		$subject = 'Complaint Briefing Summary Report';

		$request['subject'] = $subject;
		$request['html'] = $subject;
		$request['content'] = view('emails.complaint_briefing_summary')->render();

		$smtp = Functions::getMailSetting($property_id, '');
		$request['smtp'] = $smtp;

		$options = array();
		$options['html'] = $path;
		$options['pdf'] = $pdf_path;		
		$options['paperSize'] = array('format' => 'A4', 'orientation' => 'portrait');		
		$options['subject'] = "night_audit";
		$request['options'] = $options;

		$message = array();
		$message['type'] = 'report_pdf';
		$message['content'] = $request;

		Redis::publish('notify', json_encode($message));

		return $message;
	}

	public function getCurrentBriefing(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$property_id = $request->get('property_id', '0');
		$user_id = $request->get('user_id', '0');

		$ret = array();
		$query = DB::table('services_complaint_briefing as scb')
				->leftJoin('services_complaint_request as sc', 'scb.complaint_id', '=', 'sc.id')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')				
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
//				->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc.property_id', '=', 'cg.property_id');
				})
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')				
				->where('scb.discussed_flag', 2)
				->where('sc.delete_flag', 0)
				->where('sc.property_id', $property_id);
				// ->where('time', '>', $last_time);

		$data_list = $query				
				->select(DB::raw('sc.*, gp.guest_name, cr.room, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name,
					(select 1 from services_complaint_flag as scf where scf.user_id = ' . $user_id . ' and scf.complaint_id = sc.id limit 1) as flag,
					(select comment from services_complaint_note as scn where scn.user_id = ' . $user_id . ' and scn.complaint_id = sc.id limit 1) as note_comment,
					gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, gp.passport, cg.arrival, cg.departure, jr.job_role, scb.id as brief_id'))				
				->take(1)
				->get();

		// Guest::getGuestList($data_list);
		ComplaintSublist::getCompleteInfo($data_list);

		foreach($data_list as $key => $row) {
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			if( !empty($info) )
			{
				$data_list[$key]->lgm_name = $info->name;
				$data_list[$key]->lgm_type = $info->type;
			}

			$user = DB::table('common_users as cu')
				->where('cu.id', $user_id)
				->first();

			$data_list[$key]->self_sub_count = 0;
			if( !empty($user) )
			{
				$self_count = DB::table('services_complaint_sublist as scs')
					->where('scs.dept_id', $user->dept_id)
					->where('scs.parent_id', $row->id)
					->where('scs.delete_flag', 0)
					->count();

				$data_list[$key]->self_sub_count = $self_count; 	
			}
		}

		$ret['datalist'] = $data_list;

		if( count($data_list) > 0 )
		{
			ComplaintBriefingHistory::addParticipant($property_id, $user_id);

			$ret['code'] = 200;

			// find num/count
			$briefing_list = DB::table('services_complaint_briefing as scb')								
					->where('scb.property_id', $property_id)
					->get();

			$num = 0;		
			foreach($briefing_list as $key => $row) {
				if($row->id == $data_list[0]->brief_id )
					$num = $key;
			}		

			$ret['briefing_count'] = count($briefing_list);
			$ret['briefing_num'] = $num + 1;
		}
		else
			$ret['code'] = 201;

		return Response::json($ret);
	}

	public function checkExistNotDiscussed($property_id) {
		$exists = DB::table('services_complaint_briefing as scb')
					->leftJoin('services_complaint_request as sc', 'scb.complaint_id', '=', 'sc.id')					
					->where('scb.discussed_flag', '!=', 1)	// discussed
					->where('sc.property_id', $property_id)
					->where('sc.delete_flag', 0)
					->exists();

		if( $exists == true )
			return;

		// send briefing
		$message = array();
		$message['type'] = 'complaint';			

		$data = array();
		$data['property_id'] = $property_id;
		$data['sub_type'] = 'briefing_ended';
		$data['message'] = 'Briefing is ened';

		$message['data'] = $data;

		Redis::publish('notify', json_encode($message));
	}

	public function sendBreifingStatus($briefing, $property_id) {
		// send briefing
		$message = array();
		$message['type'] = 'complaint';			

		$data = array();
		$data['property_id'] = $property_id;
		$data['sub_type'] = 'briefing_status';
		$data['briefing'] = $briefing;
		
		$message['data'] = $data;

		Redis::publish('notify', json_encode($message));
	}

	public function discussNextBriefing(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', '0');
		$brief_id = $request->get('brief_id', 0);
		
		$ret = array();

		// update briefing to discussed
		$briefing = ComplaintBriefing::find($brief_id);

		if( empty($briefing) )
		{
			$ret['code'] = 201;
			return Response::json($ret);
		}

		$briefing->discussed_flag = 1;
		$briefing->save();

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $briefing->complaint_id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = 'Discussed in Briefing - Completed';
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();	

		$this->notifyNextBriefing($property_id, false, $brief_id, 1, $user_id);
		$this->checkExistNotDiscussed($property_id);
		$this->sendBreifingStatus($briefing, $property_id);

		return Response::json($ret);
	}

	public function moveNextBriefing(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', '0');
		$brief_id = $request->get('brief_id', 0);

		$ret = array();

		// update briefing to original
		$briefing = ComplaintBriefing::find($brief_id);

		if( empty($briefing) )
		{
			$ret['code'] = 201;
			return Response::json($ret);
		}

		$ret = $this->notifyNextBriefing($property_id, false, $brief_id, 1, $user_id);

		if($ret == 0)	// move successfully
		{
			if( $briefing->discussed_flag != 1 )	// not discussed
			{
				$briefing->discussed_flag = 0;
				$briefing->save();	

				$this->sendBreifingStatus($briefing, $property_id);
			}
		}

		return Response::json($ret);
	}

	public function movePrevBriefing(Request $request)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', '0');
		$brief_id = $request->get('brief_id', 0);

		$ret = array();

		// update briefing to original
		$briefing = ComplaintBriefing::find($brief_id);

		if( empty($briefing) )
		{
			$ret['code'] = 201;
			return Response::json($ret);
		}

		$ret = $this->notifyNextBriefing($property_id, false, $brief_id, -1, $user_id);

		if($ret == 0)	// move successfully
		{
			if( $briefing->discussed_flag != 1 )	// not discussed
			{
				$briefing->discussed_flag = 0;
				$briefing->save();	

				$this->sendBreifingStatus($briefing, $property_id);
			}			
		}

		return Response::json($ret);
	}

	public function notifyNextBriefing($property_id, $end_flag, $brief_id, $direction, $user_id) {
		$ret = 0;
		$message = array();
		$message['type'] = 'complaint';			

		$data = array();
		$data['property_id'] = $property_id;
		$data['sub_type'] = 'briefing';

		if( $end_flag == true )			
			$data['message'] = 'Briefing is ended';
		else
		{
			$query = DB::table('services_complaint_briefing as scb')
					->leftJoin('services_complaint_request as sc', 'scb.complaint_id', '=', 'sc.id')
					->where('sc.delete_flag', 0);					
					// ->where('scb.discussed_flag', 0)
					
			$origin_query1 = clone $query;		
					$query->whereIn('scb.discussed_flag', array(0, 1)) // discussed or waiting
					      ->where('sc.property_id', $property_id);
			$origin_query1->whereIn('scb.discussed_flag', array(0, 2)) // discussed or waiting
					      ->where('sc.property_id', $property_id);	

			$origin_query = clone $query;	
				
        $complaint_briefs = $origin_query1->select(DB::raw('sc.*, scb.discussed_flag, scb.id as brief_id'))
					->get();
			if( $direction == 1 )	// next
			{
				$query->where('scb.id', '>', $brief_id);
			}

			if( $direction == -1 )	// prev
			{
				$query->where('scb.id', '<', $brief_id)
						->orderBy('scb.id', 'desc');
			}

			$complaint = $query->select(DB::raw('sc.*, scb.id as brief_id'))
					->first();

			if( empty($complaint) )		
			{
				$query = clone $origin_query;

				// search ring
				if( $direction == 1 )	// next
				{
					// search from first
					$query->where('scb.id', '<', $brief_id);
				}

				if( $direction == -1 )	// prev
				{
					// search from last
					$query->where('scb.id', '>', $brief_id)
							->orderBy('scb.id', 'desc');
				}

				$complaint = $query->select(DB::raw('sc.*, scb.id as brief_id'))
						->first();
			}

			if( empty($complaint) )
			{
				$data['message'] = 'There is no briefing complaint';	
				$ret = -1;
			}
			else
			{
				$data['message'] = sprintf('New complaint C#%05d is displayed', $complaint->id);	
				$briefing = ComplaintBriefing::find($complaint->brief_id);
				$data['brief_id'] = $complaint->brief_id;

				if( !empty($briefing) && $briefing->discussed_flag != 1 )	// not disucss completed
				{
						// discussing
					
					foreach($complaint_briefs as $brief) {
						
						if(($brief->discussed_flag ==2))
						{
							$main_brief = ComplaintBriefing::find($brief->brief_id);
							$main_brief->discussed_flag=0;
							$main_brief->save();
						}
						}
						$briefing->discussed_flag = 2;
					$briefing->save();
				}

				$exists = ComplaintLog::where('complaint_id', $complaint->id)->where('type', 100)->exists();
				if( $exists == false )
				{
					$complaint_log = new ComplaintLog();

					$complaint_log->complaint_id = $complaint->id;
					$complaint_log->sub_id = 0;
					$complaint_log->comment = 'Discussed in Briefing';
					$complaint_log->type = 100;
					$complaint_log->user_id = $user_id;
					
					$complaint_log->save();		
				}
				
			}
		}

		$message['data'] = $data;

		Redis::publish('notify', json_encode($message));

		return $ret;
	}

	public function getBriefingRoomList(Request $request) {
		$client_id = $request->get('client_id', 0);
		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');		
		$filter = $request->get('filter');
		$filter_value = $request->get('filter_value', '');
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$user_id = $request->get('user_id', 0);
		$property_ids_by_jobrole = $request->get('property_ids_by_jobrole', []);
		$join_flag = $request->get('join_flag', 0);
		
		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));

		if ($pageSize < 0)
			$pageSize = 20;


		$query = DB::table('services_complaint_briefing_room_list as brl')
			->join('common_users as cu', 'brl.created_by', '=', 'cu.id');

		if( $join_flag != 0 )	// manager mode
			$query->where('brl.property_id', $property_id);

		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('brl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as presenter'))
				->skip($skip)->take($pageSize)
				->get();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		foreach($data_list as $key => $row) {
			$attendant_list = DB::table('services_complaint_briefing_room_member as cbrm')
				->join('common_users as cu', 'cbrm.user_id', '=', 'cu.id')
				->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')			
				->where('cbrm.briefing_room_id', $row->id)			
			    ->select(DB::raw('cbrm.status, cu.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			    ->get();

			 $row->attendant_list = $attendant_list;   

			 $row->participant_count = DB::table('services_complaint_briefing_room_member as cbrm')
				->where('cbrm.briefing_room_id', $row->id)
				->where('cbrm.status', '>', 0)			
			    ->count();
		}

		$ret['code'] = 200;
		$ret['message'] = '';
 		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		return Response::json($ret);					
	}

	public function createBriefingRoom(Request $request) {
		$user_id = $request->get('user_id', 0);
		$client_id = $request->get('client_id', 0);
		$property_id = $request->get('property_id', 0);
		$free_join_flag = $request->get('free_join_flag', 0);
		$start_at = $request->get('start_at', '');
		$email_link_flag = $request->get('email_link_flag', 0);
		$schedule_flag = $request->get('schedule_flag', 0);
		$attendant_list = $request->get('attendant_list', []);
		
		$briefing_room = new ComplaintBriefingRoom();
		$briefing_room->client_id = $client_id;
		$briefing_room->property_id = $property_id;
		$briefing_room->free_join_flag = $free_join_flag;
		$briefing_room->schedule_flag = $schedule_flag;
		$briefing_room->start_at = $start_at;
		$briefing_room->email_link_flag = $email_link_flag;

		$status = B_WAITING;
		if( $schedule_flag == 1 )
		{
			$status = B_SCHEDULED;
		}

		$briefing_room->status = $status;
		$briefing_room->created_by = $user_id;

		$briefing_room->save();

		foreach($attendant_list as $key => $row)
		{
			$data = array();
			$data['briefing_room_id'] = $briefing_room->id;
			$data['user_id'] = $row;

			DB::table('services_complaint_briefing_room_member')->insert($data);
		}

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = 'Briefing room is created successfully';
		$ret['data'] = $briefing_room;

		return Response::json($ret);
	}

	public function getAttendantList(Request $request) {
		$client_id = $request->get('client_id', 0);

		$userlist = DB::table('common_users as cu')
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->join('common_property as cp', 'cd.property_id', '=', 'cp.id')
			->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')			
			->where('cp.client_id', $client_id)
			->select(DB::raw('cu.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();

		return Response::json($userlist);	
	}

	public function flagComplaint(Request $request) {
		$user_id = $request->get('user_id', 0);
		$complaint_id = $request->get('complaint_id', 0);

		$complaint_flag = ComplaintFlag::where('user_id', $user_id)
			->where('complaint_id', $complaint_id)
			->first();

		if( empty($complaint_flag) )	// not flaged
		{
			// flag new
			$complaint_flag = new ComplaintFlag();
			$complaint_flag->user_id = $user_id;
			$complaint_flag->complaint_id = $complaint_id;

			$complaint_flag->save();

			$complaint_flag->flag = 1;
		}	
		else
		{
			$complaint_flag->delete();
			$complaint_flag->flag = 0;
		}

		if( $complaint_flag->flag == 1 )
		{
			$complaint_log = new ComplaintLog();

			$complaint_log->complaint_id = $complaint_id;
			$complaint_log->sub_id = 0;
			$complaint_log->comment = 'Flagged';
			$complaint_log->type = 0;
			$complaint_log->user_id = $user_id;
			
			$complaint_log->save();	
		}

		return Response::json($complaint_flag);
	}

	public function flagmarkComplaint(Request $request) {
		$complaint_id = $request->get('complaint_id', 0);
		$mark_flag = $request->get('mark_flag', 0);

		$complaint = ComplaintRequest::find($complaint_id);
		$complaint->mark_flag = $mark_flag;
		$complaint->save();

		return Response::json($complaint);
	}

	public function makeNote(Request $request) {		
		$user_id = $request->get('user_id', 0);
		$complaint_id = $request->get('complaint_id', 0);
		$comment = $request->get('comment', '');

		$complaint_note = ComplaintNote::where('user_id', $user_id)
			->where('complaint_id', $complaint_id)
			->first();

		if( empty($complaint_note) )	// not exist note
			$complaint_note = new ComplaintNote();

		$complaint_note->complaint_id = $complaint_id;
		$complaint_note->user_id = $user_id;
		$complaint_note->comment = $comment;

		$complaint_note->save();
		
		return Response::json($complaint_note);
	}


	public function getStatisticInfo(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$period = $request->get('period', 'Today');
		$end_date = $request->get('end_date', '');
		$during = $request->get('during', '');
		$category_id = $request->get('category_id', 0);
		$property_ids = $request->get('property_ids_by_jobrole', []);
		$propertys_ids = $request->get('property_ids', []);

		$filter = array();
		$filter['category_id'] = $category_id;
		$filter['dept_id'] = 0;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		switch ($period) {
			case 'Today';
				$ret = $this->getStaticsticsByToday($propertys_ids, $property_ids, $filter, $property_id);
				break;
			case 'Weekly';
				$ret = $this->getStaticsticsByDate($cur_time, 7, $propertys_ids, $property_ids, $filter, $property_id);
				break;
			case 'Monthly';
				$ret = $this->getStaticsticsByDate($cur_time, 30, $propertys_ids, $property_ids, $filter, $property_id);
				break;
			case 'Custom Days';
				$ret = $this->getStaticsticsByDate($end_date, $during, $propertys_ids, $property_ids, $filter, $property_id);
				break;
			case 'Yearly';
				$ret = $this->getStaticsticsByYearly($cur_time, $propertys_ids, $property_ids, $filter, $property_id);
				break;
		}

		$ret['property_list'] = DB::table('common_property as cp')
			->whereIn('cp.id', $property_ids)
			->select(DB::raw('cp.id, cp.name'))
			->get();

		$ret['property_ids'] = $property_ids;
		$ret['propertys_ids'] = explode(',',$propertys_ids);
	
		return Response::json($ret);
	}

	public function getMyStatisticInfo(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$period = $request->get('period', 'Today');
		$end_date = $request->get('end_date', '');
		$during = $request->get('during', '');
		$dept_id = $request->get('dept_id', 0);
		$property_ids = $request->get('property_ids_by_jobrole', []);

		$filter = array();
		$filter['category_id'] = 0;
		$filter['dept_id'] = $dept_id;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$ret = array();

		switch ($period) {
			case 'Today';
				$ret = $this->getMySubStaticsticsByToday($property_id, $filter);
				break;
			case 'Weekly';
				$ret = $this->getMySubStaticsticsByDate($cur_time, 7, $property_id, $filter);
				break;
			case 'Monthly';
				$ret = $this->getMySubStaticsticsByDate($cur_time, 30, $property_id, $filter);
				break;
			case 'Custom Days';
				$ret = $this->getMySubStaticsticsByDate($end_date, $during, $property_id, $filter);
				break;
			case 'Yearly';
				$ret = $this->getMySubStaticsticsByYearly($cur_time, $property_id, $filter);
				break;
		}

		return Response::json($ret);
	}

	public function getStaticsticsByToday($propertys_ids,$property_ids, $filter, $property_id)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$date = new DateTime($cur_time);
		$date->sub(new DateInterval('P1D'));		
		$start_time = $date->format('Y-m-d H:i:s');

		$time_range = sprintf("(scr.created_at >= '%s' && scr.created_at <= '%s')", $start_time, $cur_time);
		$sub_time_range = sprintf("(scs.created_at >= '%s' && scs.created_at <= '%s')", $start_time, $cur_time);
        $total_count = $this->getTotalCountStatistics($propertys_ids, $time_range, $filter, $property_id);
		$ret = $this->getStatisticValues($propertys_ids, $property_ids, $time_range, $sub_time_range, $filter, $start_time,  $cur_time, $property_id);
		
		$ret['total'] = $total_count;
		return $ret;
	}


	public function getStaticsticsByDate($end_date, $during, $propertys_ids, $property_ids, $filter, $property_id)
	{
		date_default_timezone_set(config('app.timezone'));
		
		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P' . $during . 'D'));

		$start_time = $date->format('Y-m-d H:i:s');

		$time_range = sprintf("(scr.created_at >= '%s' && scr.created_at <= '%s')", $start_time, $end_date);
		$sub_time_range = sprintf("(scs.created_at >= '%s' && scs.created_at <= '%s')", $start_time, $end_date);
        $total_count =  $this->getTotalCountStatistics($propertys_ids, $time_range, $filter, $property_id);
		$ret = $this->getStatisticValues($propertys_ids, $property_ids, $time_range, $sub_time_range, $filter, $start_time,  $end_date, $property_id);
		$ret['total'] = $total_count;
		return $ret;
	}

	public function getStaticsticsByYearly($end_date,$propertys_ids, $property_ids, $filter, $property_id)
	{
		date_default_timezone_set(config('app.timezone'));
		
		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P1Y'));

		$start_time = $date->format('Y-m-d H:i:s');

		$time_range = sprintf("(scr.created_at >= '%s' && scr.created_at <= '%s')", $start_time, $end_date);
		$sub_time_range = sprintf("(scs.created_at >= '%s' && scs.created_at <= '%s')", $start_time, $end_date);
        $total_count = $this->getTotalCountStatistics($propertys_ids, $time_range, $filter, $property_id);
		$ret = $this->getStatisticValues($propertys_ids, $property_ids, $time_range, $sub_time_range, $filter, $start_time,  $end_date, $property_id);
		$ret['total'] = $total_count;
		return $ret;
	}

	private function getStatisticValues($propertys_ids, $property_ids, $time_range, $sub_time_range, $filter , $start_time, $end_date, $property_id)
	{
		$ret = array();

		if (!empty($propertys_ids))
			$propertys = explode(",",$propertys_ids);
		else
			$propertys = [];

		// get possible severity list
		$severity_list = DB::table('services_complaint_type')->get();
		$select_sql = '';

		$dept_id = $filter['dept_id'];

		$ret['severity_name_list'] = $severity_list;

		foreach($severity_list as $key => $row) {
			if($key > 0 )
				$select_sql .= ', ';

			$select_sql .= sprintf('COALESCE(sum(scr.severity = %d), 0) as %s', $row->id, $row->type);
		}

		$query =  DB::table('services_complaint_request as scr')
					// ->whereIn('scr.property_id', $property_ids)
					->whereRaw($time_range)
					->where('scr.delete_flag','=', 0);
					
		if (!empty($propertys))	
			$query->whereIn('scr.property_id', $propertys);
		else
			$query->where('scr.property_id', $property_id);

		if( $dept_id > 0 )
		{
			$query->whereExists(function ($subquery) use ($dept_id) {
	                $subquery->select(DB::raw(1))
						  ->from('services_complaint_sublist as scs')
						  ->where('scs.delete_flag', 0)
	                      ->whereRaw('scs.dept_id = ' . $dept_id . ' and scr.id = scs.parent_id');
	            });						
		}	

		// get severity statistics
		$data_query = clone $query;					
		$ret['severity'] = $data_query
			// ->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')			
			->select(DB::raw($select_sql))
			->first();

		// get status staticstics
		// get possible status	
		$result = DB::select("SHOW COLUMNS FROM services_complaint_request WHERE FIELD = 'status'");	
		$result = str_replace(array("enum('", "')", "''"), array('', '', "'"), $result[0]->Type);
		$status_list = explode("','", $result);
/*
		$select_sql = '';
		foreach($status_list as $key => $row) {
			if($key > 0 )
				$select_sql .= ', ';

			$select_sql .= sprintf("COALESCE(sum(scr.status = '%s'), 0) as `%s`", $row, $row);
		}

		if( !empty($select_sql) )
			$select_sql .= ', ';
*/
		$select_sql = sprintf("COALESCE(sum(scr.closed_flag != 1), 0) as Open");
		$select_sql .= sprintf(", COALESCE(sum(scr.closed_flag = 1), 0) as Closed");
//		$select_sql .= sprintf(", COALESCE(sum(scr.status != 'Resolved'), 0) as Unresolved");

		// get status statistics
		$data_query = clone $query;
		$ret['status'] = $data_query
			// ->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')
			->select(DB::raw($select_sql))
			->first();

		// get guest_type staticstics
		// get possible guest_type	
		$result = DB::select("SHOW COLUMNS FROM services_complaint_request WHERE FIELD = 'guest_type'");	
		if( count($result) > 0 )
			$result = str_replace(array("enum('", "')", "''"), array('', '', "'"), $result[0]->Type);
		else
			$result = '';

		$guest_type_list = explode("','", $result);
		
		$ret['guest_type_list'] = $guest_type_list;

		$select_sql = '';
		foreach($guest_type_list as $key => $row) {
			if($key > 0 )
				$select_sql .= ', ';

			$select_sql .= sprintf("COALESCE(sum(scr.guest_type = '%s'), 0) as gt%d", $row, $key);
		}

		// get guest_type statistics
		$data_query = clone $query;
		$ret['guest_types'] = $data_query
			// ->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')
			->select(DB::raw($select_sql))
			->first();
		
		// get guest type/country statistics	
		$country_guest_type_select_sql = "count(*) as total, COALESCE(cc.name, 'X') as country_name, COALESCE(cc.code, 'X') as country_code, $select_sql";
		$data_query = clone $query;
		$ret['guest_type_country'] = $data_query
										->leftJoin('common_guest_profile as gp', 'scr.guest_id', '=', 'gp.id')
										->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
										->groupBy('gp.nationality')
										->orderBy('total' , 'desc')
										->limit(10)
										->select(DB::raw($country_guest_type_select_sql))
										->get();


		$ret['dept_data'] = array();	
		$ret['cost_data'] = array();	
		$ret['subcomplaint_data'] = array();
		
		$category_id = $filter['category_id'];

		$count_sql = '
				CAST(COALESCE(sum(scs.status = 1), 0) AS UNSIGNED) as pending_cnt,
				CAST(COALESCE(sum(scs.status = 2), 0) AS UNSIGNED) as completed_cnt,
				CAST(COALESCE(sum(scs.status = 3), 0) AS UNSIGNED) as escalated_cnt,
				CAST(COALESCE(sum(scs.status = 4), 0) AS UNSIGNED) as reassign_cnt,
				CAST(COALESCE(sum(scs.status = 5), 0) AS UNSIGNED) as canceled_cnt,
				CAST(COALESCE(sum(scs.status = 6), 0) AS UNSIGNED) as timeout_cnt,
				CAST(COALESCE(sum(scs.status = 7), 0) AS UNSIGNED) as reopen_cnt
				';

		if( $dept_id > 0 )	// individual department
		{
			$subcomplaint_data = DB::table('services_complaint_sublist as scs')
				->leftJoin('services_complaint_category as cc', 'scs.category_id', '=', 'cc.id')
				->join('services_complaint_request as scr', 'scs.parent_id', '=', 'scr.id')
				->where('scs.dept_id', $dept_id)
				->whereRaw($time_range)
				->where('scs.delete_flag', 0)
				->where('scr.delete_flag', 0)
				->groupBy('scs.category_id')
				->orderBy('cc.name')
				->select(DB::raw("scs.category_id, count(*) as cnt, cc.name as category_name, $count_sql"))
				->get();

			$ret['subcomplaint_data'] = $subcomplaint_data;	

		}
		else  // get total data
		{
			

			$time_range1 = sprintf("(scs.created_at >= '%s' && scs.created_at <= '%s')", $start_time, $end_date);
			foreach($property_ids as $property) {
				/*
				$select_sql = "scs.dept_id, count(*) as cnt, cd.department, cd.short_code, $count_sql";		

				$query = DB::table('services_complaint_sublist as scs')
					->join('services_complaint_request as scr', 'scs.parent_id', '=', 'scr.id')
					->leftJoin('common_department as cd', 'scs.dept_id', '=', 'cd.id')
					->where('scr.property_id', $property)
					->where('scs.delete_flag', 0)
					->where('scr.delete_flag', 0)
					->whereRaw($time_range);

				if( $category_id > 0 )	
					$query->where('scs.category_id', $category_id);	

				$data_query = clone $query;	

				$data_query->groupBy('scs.dept_id')
					->orderBy('cd.department', 'asc')
					->select(DB::raw($select_sql));
		
				$data = $data_query->get();

				foreach($data as $row)
				{
					$cost_query = DB::table('services_complaint_sublist_compensation as scsc')
						->join('services_complaint_sublist as scs', 'scsc.sub_id', '=', 'scs.id')
						->join('services_complaint_request as scr', 'scs.parent_id', '=', 'scr.id')
						->where('scs.dept_id', $row->dept_id)
						->where('scr.property_id', $property)
						->where('scs.delete_flag', 0)
						->where('scr.delete_flag', 0)
						->whereRaw($time_range);
					
					$data_query = clone $cost_query;	

					$cost_data = $data_query->select(DB::raw('sum(scsc.cost) as cost'))
						->first();

					if( !empty($cost_data->cost) )
						$row->sub_total_cost = $cost_data->cost;
					else
						$row->sub_total_cost = 0;

					// get subcomplaint count by location type
					$data_query = clone $query;	

					$select_sql = "sl.type_id, slt.type, slt.short_code, count(*) as cnt, $count_sql";		

					$data_query
						->leftJoin('services_location as sl', 'scs.location_id', '=', 'sl.id')
						->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->where('scs.dept_id', $row->dept_id)
						->groupBy('sl.type_id')						
						->select(DB::raw($select_sql));
			
					$row->loc_type_count_data  = $data_query->get();


					// get subcomplaint compensation by location type
					$data_query = clone $cost_query;	
					$row->loc_type_comp_data = $data_query
						->leftJoin('services_location as sl', 'scs.location_id', '=', 'sl.id')
						->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->groupBy('sl.type_id')						
						->select(DB::raw('sl.type_id, slt.type, slt.short_code, sum(scsc.cost) as cost'))
						->get();
				}
				*/

				$select_sql = "sc.dept_id, count(*) as cnt, cd.department, cd.short_code, $count_sql";	
				$query = DB::table('services_complaint_request as scs')
					->Join('services_compensation_request as sc', 'scs.id', '=', 'sc.complaint_id')
					->leftJoin('common_department as cd', 'sc.dept_id', '=', 'cd.id')
					->where('scs.property_id', $property)
					->where('scs.delete_flag', 0)
					->whereRaw($time_range1);

				if( $category_id > 0 )	
					$query->where('scs.category_id', $category_id);	

				$data_query = clone $query;	

				$data_query->groupBy('sc.dept_id')
					->orderBy('cd.department', 'asc')
					->select(DB::raw($select_sql));
		
				$data = $data_query->get();

				foreach($data as $row)
				{
					$cost_query = DB::table('services_compensation_request as sc')
						->join('services_complaint_request as scs', 'sc.complaint_id', '=', 'scs.id')
						->where('sc.dept_id', $row->dept_id)
						->where('scs.property_id', $property)
						->where('scs.delete_flag', 0)
						->whereRaw($time_range1);
					
					$data_query = clone $cost_query;	

					$cost_data = $data_query->select(DB::raw('sum(sc.cost) as cost'))
						->first();

					if( !empty($cost_data->cost) )
						$row->sub_total_cost = $cost_data->cost;
					else
						$row->sub_total_cost = 0;

					// get complaint count by location type
					$data_query = clone $query;	

					$select_sql = "sl.type_id, slt.type, slt.short_code, count(*) as cnt, $count_sql";		

					$data_query
						->leftJoin('services_location as sl', 'scs.loc_id', '=', 'sl.id')
						->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->where('sc.dept_id', $row->dept_id)
						->groupBy('sl.type_id')						
						->select(DB::raw($select_sql));
			
					$row->loc_type_count_data  = $data_query->get();


					// get complaint compensation by location type
					$data_query = clone $cost_query;	
					$row->loc_type_comp_data = $data_query
						->leftJoin('services_location as sl', 'scs.loc_id', '=', 'sl.id')
						->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->groupBy('sl.type_id')						
						->select(DB::raw('sl.type_id, slt.type, slt.short_code, sum(sc.cost) as cost'))
						->get();
				}

				array_push($ret['dept_data'], $data);	
			}
		}

		// guest type/country

		return $ret;
	}

	private function getTotalCountStatistics($property_ids, $time_range, $filter, $property_id) {	
		$ret = array();

		if (!empty($property_ids))
			$propertys_ids = explode(",",$property_ids);
		else
			$propertys_ids = [];

		$query =  DB::table('services_complaint_request as scr')
					// ->whereIn('scr.property_id', $property_ids)
					->whereRaw($time_range)
					->where('scr.delete_flag','=', 0);

		if (!empty($propertys_ids))	
			$query->whereIn('scr.property_id', $propertys_ids);
		else
			$query->where('scr.property_id', $property_id);

		$select_sql = sprintf("COALESCE(sum(scr.closed_flag = 1), 0) as closed");
		$select_sql .= sprintf(", COALESCE(sum(scr.closed_flag = 0 && scr.status != 'Rejected'), 0) as open");
		$select_sql .= sprintf(", COALESCE(sum(scr.severity = 4), 0) as major");
		$select_sql .= sprintf(", COALESCE(sum(scr.severity = 5), 0) as serious");
		$select_sql .= sprintf(", COALESCE(sum(scr.status != 'Resolved'), 0) as unresolved");
		$select_sql .= sprintf(", COALESCE(sum(scr.status = 'Resolved'), 0) as resolved");
		$select_sql .= sprintf(", COALESCE(sum(scr.compensation_total), 0) as compensation");
		$select_sql .= sprintf(", COALESCE(sum(scr.subcomp_total), 0) as subcompensation");

		// get status statistics
		$data_query = clone $query;
		$count = $data_query			
			->select(DB::raw($select_sql))
			->first();
		
		$total_room = DB::table('common_room as cr')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id');

		if (!empty($propertys_ids))	
			$total_room->whereIn('cb.property_id', $propertys_ids);
		else
			$total_room->where('cb.property_id', $property_id);
		$data_query1 = clone $total_room;
		$total_room = $data_query1->count();

		$end_date = date("Y-m-d");	
		$occupancy = DB::table('common_room_occupancy');	

		if (!empty($propertys_ids))	
			$occupancy->whereIn('property_id', $propertys_ids);
		else
			$occupancy->where('property_id', $property_id);
		$data_query1 = clone $occupancy;
		$occupancy = $data_query1				
				->where('check_date', '<', $end_date)
				->orderBy('check_date', 'desc')							
				->first();
		
		$occupancy_percent = 0;
		if( !empty($occupancy) > 0 )					
			$occupancy_percent = round($occupancy->occupancy * 100 / $total_room, 1);
		
		$ret['closed'] = $count->closed;
		$ret['resolved'] = $count->resolved;
		$ret['major'] = $count->major;
		$ret['serious'] = $count->serious;
		$ret['unresolved'] = $count->unresolved;
		$ret['compensation'] = number_format(($count->compensation + $count->subcompensation),2);
		$ret['open'] = $count->open;
		
		$ret['occupancy'] = $occupancy_percent;
			
		return $ret;
	}

	public function getMySubStaticsticsByToday($property_id, $filter)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$date = new DateTime($cur_time);
		$date->sub(new DateInterval('P1D'));		
		$start_time = $date->format('Y-m-d H:i:s');

		$time_range = sprintf("(scs.created_at >= '%s' && scs.created_at <= '%s')", $start_time, $cur_time);      
		$sub_time_range = sprintf("(scs.created_at >= '%s' && scs.created_at <= '%s')", $start_time, $cur_time);  
		$resolved_time_range = sprintf("(scs.completed_at >= '%s' && scs.completed_at <= '%s')", $start_time, $cur_time);        
		$ret = $this->getMySubStatisticValues($property_id, $time_range, $sub_time_range, $resolved_time_range, $filter);
		
		return $ret;
	}

	public function getMySubStaticsticsByDate($end_date, $during, $property_id, $filter)
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		
		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P' . $during . 'D'));

		$start_time = $date->format('Y-m-d H:i:s');

		$time_range = sprintf("(scr.created_at >= '%s' && scr.created_at <= '%s')", $start_time, $end_date);
		$sub_time_range = sprintf("(scs.created_at >= '%s' && scs.created_at <= '%s')", $start_time, $end_date); 
		$resolved_time_range = sprintf("(scs.completed_at >= '%s' && scs.completed_at <= '%s')", $start_time, $end_date);                
		$ret = $this->getMySubStatisticValues($property_id, $time_range,$sub_time_range, $resolved_time_range, $filter);
		return $ret;
	}

	public function getMySubStaticsticsByYearly($end_date, $property_id, $filter)
	{
		date_default_timezone_set(config('app.timezone'));
		
		$date = new DateTime($end_date);
		$date->sub(new DateInterval('P1Y'));

		$start_time = $date->format('Y-m-d H:i:s');

		$time_range = sprintf("(scr.created_at >= '%s' && scr.created_at <= '%s')", $start_time, $end_date);
		$sub_time_range = sprintf("(scs.created_at >= '%s' && scs.created_at <= '%s')", $start_time, $end_date);
		$resolved_time_range = sprintf("(scs.completed_at >= '%s' && scs.completed_at <= '%s')", $start_time, $end_date);                        
		$ret = $this->getMySubStatisticValues($property_id, $time_range, $sub_time_range, $resolved_time_range, $filter); 
		
		return $ret;
	}

	private function getMySubStatisticValues($property_id, $time_range, $sub_time_range, $resolved_time_range, $filter)
	{
		$ret = array();

		// get subcomplaint count for department category		
		$query = DB::table('services_complaint_sublist as scs')
					->join('services_complaint_request as scr', 'scs.parent_id', '=', 'scr.id')					
					->where('scr.property_id', $property_id)
					->where('scs.delete_flag',0)
					->where('scr.delete_flag', 0)					
					->whereRaw($time_range);

		// get category list
		$category_query = clone $query;
		$category_list = $category_query
							->leftJoin('services_complaint_category as scc', 'scs.category_id', '=', 'scc.id')
							->select(DB::raw("scs.category_id, COALESCE(scc.name, 'Unknown') as category_name"))
							->distinct()
							->get();

		$ret['category_list'] = $category_list;				
				
		
		$select_sql = 'scs.dept_id, cd.short_code, cd.department';
		foreach($category_list as $key => $row) {
			$select_sql .= ', ';

			$select_sql .= sprintf('COALESCE(sum(scs.category_id = %d), 0) as `%s`', $row->category_id, $row->category_name);
		}

		$dept_category_query = clone $query;
		$ret['dept_category_list'] = $dept_category_query	
								->leftJoin('common_department as cd', 'scs.dept_id', '=', 'cd.id')
								->groupBy('scs.dept_id')
								->select(DB::raw($select_sql))
								->get();

		// get location/category list						
		$select_sql = "COALESCE(slt.short_code, 'Unknown') as short_code, COALESCE(slt.type, 'X') as type, sl.type_id";
		foreach($category_list as $key => $row) {
			$select_sql .= ', ';

			$select_sql .= sprintf('COALESCE(sum(scs.category_id = %d), 0) as `%s`', $row->category_id, $row->category_name);
		}

		foreach( $ret['dept_category_list']	as $row )
		{
			$loc_category_query = clone $query;
			$row->loc_category_list = $loc_category_query	
						->leftJoin('services_location as sl', 'scs.location_id', '=', 'sl.id')
						->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->where('scs.dept_id', $row->dept_id)
						->groupBy('sl.type_id')
						->select(DB::raw($select_sql))
						->get();
			
			// dept => category => subcategory by loc type
			// $row->loc_category_subcategory_list = [];
			// foreach($category_list as $row1)
			// {
			// 	$sub_category_query = clone $query;
			// 	$subcategory_list = $sub_category_query
			// 						->leftJoin('services_complaint_subcategory as scc', 'scs.subcategory_id', '=', 'scc.id')
			// 						->where('scs.category_id', $row1->category_id)
			// 						->select(DB::raw("scs.subcategory_id, COALESCE(scc.name, 'Unknown') as subcategory_name"))
			// 						->distinct()
			// 						->get();


			// 	$subcategry_select_sql = "COALESCE(slt.short_code, 'Unknown') as short_code";
			// 	foreach($subcategory_list as $key => $row2) 
			// 	{
			// 		$subcategry_select_sql .= ', ';
			// 		$subcategry_select_sql .= sprintf('COALESCE(sum(scs.subcategory_id = %d), 0) as `%s`', $row2->subcategory_id, $row2->subcategory_name);
			// 	}

			// 	$loc_subcategory_query = clone $query;
			// 	$row->loc_category_subcategory_list[$row1->category_name] = $loc_subcategory_query	
			// 				->leftJoin('services_location as sl', 'scs.location_id', '=', 'sl.id')
			// 				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			// 				->where('scs.category_id', $row1->category_id)
			// 				->where('scs.dept_id', $row->dept_id)							
			// 				->groupBy('sl.type_id')
			// 				->select(DB::raw($subcategry_select_sql))
			// 				->get();
			// }	

			// number for that sub category under department/category/location
			$row->dept_loc_category_subcategory_list = [];
			$dept_id = $row->dept_id;
			foreach($row->loc_category_list as $row1)
			{
				$loc_type_id = $row1->type_id;
				foreach($category_list as $row2)
				{
					$category_id = $row2->category_id;
					$key = $loc_type_id . "_" . $category_id;
					
					$row->dept_loc_category_subcategory_list[$key] = 

					$sub_query = clone $query;
					$row->dept_loc_category_subcategory_list[$key] = $sub_query	
								->leftJoin('services_location as sl', 'scs.location_id', '=', 'sl.id')	
								->leftJoin('services_complaint_subcategory as scc', 'scs.subcategory_id', '=', 'scc.id')
								->where('scs.dept_id', $dept_id)
								->where('sl.type_id', $loc_type_id)
								->where('scs.category_id', $category_id)								
								->groupBy('scs.subcategory_id')
								->select(DB::raw("count(*) as cnt, COALESCE(scc.name, 'Unknown') as subcategory_name"))
								->get();

				}
			}
		}					

		// get severity list	
		/*
		$severity_list = DB::table('services_complaint_type')->get();
		$select_sql = '';

		foreach($severity_list as $key => $row) {
			if($key > 0 )
				$select_sql .= ', ';

			$select_sql .= sprintf('COALESCE(sum(scs.severity = %d), 0) as %s', $row->id, $row->type);
		}

		$severity_query = clone $query;

		$ret['severity_type'] =  $severity_query
					->select(DB::raw($select_sql))
					->first();
*/
		$severity_list = DB::table('services_complaint_type')->get();
		$select_sql = '';

		$dept_id = $filter['dept_id'];

		$ret['severity_name_list'] = $severity_list;
		foreach($severity_list as $key => $row) {
			if($key > 0 )
				$select_sql .= ', ';

		$select_sql .= sprintf('COALESCE(sum(scr.severity = %d), 0) as %s', $row->id, $row->type);
		}

		$data_query = clone $query;					
		$ret['severity'] = $data_query
			// ->leftJoin('services_complaint_type as ct', 'scr.severity', '=', 'ct.id')			
			->select(DB::raw($select_sql))
			->first();

		// sub complaint severity count based on department
		$query = DB::table('services_complaint_sublist as scs')
			->leftJoin('common_department as cd', 'scs.dept_id', '=', 'cd.id')	
			->where('scs.delete_flag',0)		
			->whereRaw($sub_time_range);

		$select_sql = 'scs.dept_id, cd.department, cd.short_code';
		$count_sql = '';
		foreach($severity_list as $key => $row) {
			$count_sql .= sprintf(', COALESCE(sum(scs.severity = %d), 0) as %s', $row->id, $row->type);
		}

		$select_sql .= $count_sql;
	
		$sub_query = clone $query;	
		$ret['subcomplaint_severity'] = $sub_query
											->groupBy('scs.dept_id')	
											->select(DB::raw($select_sql))
											->get();

		$select_sql = "COALESCE(sl.type_id, 0) as type_id, COALESCE(slt.type, 'Unknown') as type, COALESCE(slt.short_code, 'Unknown') as short_code, count(*) as cnt $count_sql";		

		foreach($ret['subcomplaint_severity'] as $row)
		{

			$sub_query = clone $query;	
			$row->severity_loc_type = $sub_query
								->leftJoin('services_location as sl', 'scs.location_id', '=', 'sl.id')
								->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
								->where('scs.dept_id', $row->dept_id)
								->groupBy('sl.type_id')						
								->select(DB::raw($select_sql))
								->get();
		}	
 // Severity end
		$query = DB::table('services_complaint_sublist as scs')
						->join('services_complaint_request as scr', 'scs.parent_id', '=', 'scr.id')					
						->leftJoin('common_department as cd', 'scs.dept_id', '=', 'cd.id')					
						->where('scr.property_id', $property_id)					
						->whereRaw($resolved_time_range)
						->where('scs.delete_flag',0)	
						->where('scr.delete_flag', 0)
						->where('scs.status', SC_COMPLETE);
		
		$dept_resolve_time_query = clone $query;				

		$ret['dept_resolve_time'] = $dept_resolve_time_query
					->groupBy('scs.dept_id')
					->select(DB::raw('scs.dept_id, cd.short_code, cd.department,  COALESCE(count(*), 0) as cnt, COALESCE(sum(TIME_TO_SEC(TIMEDIFF(scs.completed_at, scs.created_at))), 0) as total_time'))
					->get();

		foreach( $ret['dept_resolve_time']	as $row )
		{
			$loc_resolve_query = clone $query;
			$row->loc_resolve_list = $loc_resolve_query	
						->leftJoin('services_location as sl', 'scs.location_id', '=', 'sl.id')
						->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->where('scs.dept_id', $row->dept_id)
						->groupBy('sl.type_id')
						->select(DB::raw("COALESCE(slt.short_code, 'Unknown') as short_code, slt.type as location_type, COALESCE(count(*), 0) as cnt, COALESCE(sum(TIME_TO_SEC(TIMEDIFF(scs.completed_at, scs.created_at))), 0) as total_time"))
						->get();
		}					
						
	
		$dept_id = $filter['dept_id'];			

		// if( $dept_id > 0 )	// individual department
		// {
		// 	$subcomplaint_data = DB::table('services_complaint_sublist as scs')
		// 		->leftJoin('services_complaint_category as cc', 'scs.category_id', '=', 'cc.id')
		// 		->join('services_complaint_request as scr', 'scs.parent_id', '=', 'scr.id')
		// 		->where('scs.dept_id', $dept_id)
		// 		->whereRaw($time_range)
		// 		->groupBy('scs.category_id')
		// 		->orderBy('cc.name')
		// 		->select(DB::raw("scs.category_id, count(*) as cnt, cc.name as category_name, $count_sql"))
		// 		->get();

		// 	$ret['subcomplaint_data'] = $subcomplaint_data;	
		// }
		
		return $ret;
	}

	
	public function testComplaintReport(Request $request)
	{
		$report = array();

		$report['id'] = $request->get('id', 0);
		$report['property_id'] = $request->get('property_id', 0);
		$report['start_date'] = $request->get('start_date', '');
		$report['end_date'] = $request->get('end_date', '');

		$data = $this->makeComplaintSummaryReportData($report);

		return Response::json($data);
	}

	private function getComplaintDataList($query, &$data) {
		$data_list = $query				
				->select(DB::raw('sc.*, 
						gp.guest_name, cr.room, 
						CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name,	
						gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality,
						cmc.name as maincategory,cc.name as nationality_name, gp.passport, sl.name as location_name,slt.type as location_type,
						cg.arrival, cg.departure, cg.vip,cg.booking_src,cg.booking_rate,cg.company, 
						jr.job_role, scft.name as feedback_type, scfs.name as feedback_source,
						(select sum(cost) from services_compensation_request where complaint_id = sc.id) as total_cost'))		
				->get();

		// Guest::getGuestList($data_list);
		ComplaintSublist::getCompleteInfo($data_list);

		$data['compensation'] = array();
		$data['compensation']['list'] = array();

		$complaint_ids = [];
		$replacement = '<span class="highlight">${1}</span>';
		foreach($data_list as $key => $row) {
			$complaint_ids[] = $row->id;

			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			if( !empty($info) )
			{
				$data_list[$key]->lgm_name = $info->name;
				$data_list[$key]->lgm_type = $info->type;
			}
			else
			{
				$data_list[$key]->lgm_name = '';
				$data_list[$key]->lgm_type = '';	
			}

			// Highlight comment and initial response.
			$comment_highlight = '/(' . str_replace("&&", "|", $data_list[$key]->comment_highlight) . ')/';
			if( Functions::isRegularExpression($comment_highlight) )
				$data_list[$key]->comment_highlighted = preg_replace($comment_highlight, $replacement, $data_list[$key]->comment);
			else
				$data_list[$key]->comment_highlighted = $data_list[$key]->comment;

			$response_highlight = '/(' . str_replace("&&", "|", $data_list[$key]->response_highlight) . ')/';			
			if( Functions::isRegularExpression($response_highlight) )
				$data_list[$key]->initial_response_highlighted = preg_replace($response_highlight, $replacement, $data_list[$key]->initial_response);
			else
				$data_list[$key]->initial_response_highlighted = $data_list[$key]->initial_response;

			$sublist = $this->getSubcomplaintListData($row->id);
			foreach ($sublist as $key1 => $sub) {
				$log = DB::table('services_complaint_log as scl')
					->where('scl.sub_id', $sub->id)
					->get();	

				$sublist[$key1]->log = $log; 	

				if($sub->status == SC_COMPLETE)
				{
					$diff = strtotime($sub->completed_at) - strtotime($sub->created_at);
					$sublist[$key1]->elaspse_time = Functions::getHHMMSSFormatFromSecond($diff);
				}
				else
					$sublist[$key1]->elaspse_time = 'Unknown';

				if( $sub->created_at != $sub->updated_at )
					$sublist[$key1]->update_status = ' (Updated)';
				else
					$sublist[$key1]->update_status = '';				
			}

			$data_list[$key]->sublist = $sublist;

			$comp_list = DB::table('services_compensation_request as scr')
				->join('services_compensation as comp', 'scr.item_id', '=', 'comp.id')
				->where('scr.complaint_id', $row->id)
				->select(DB::raw('scr.*, comp.compensation as name'))
				->get();

			$data_list[$key]->comp_list = $comp_list;	

			if($row->status == C_RESOLVED || $row->status == C_REJECTED || $row->status == C_FORWARDED)
			{
				$diff = strtotime($row->updated_at) - strtotime($row->created_at);

				$data_list[$key]->resolution_time = Functions::getHHMMSSFormatFromSecond($diff);
			}	

			if($row->closed_flag == 1)
			{
				$diff = strtotime($row->closed_time) - strtotime($row->created_at);

				$data_list[$key]->total_resolution_time = Functions::getHHMMSSFormatFromSecond($diff);
			}		

			// $data_list[$key]->total_cost = $each_cost;

			// get guest history
			$guest_history = $this->getGuestHistoryData($row->id, $row->guest_id);
			$data_list[$key]->guest_history = $guest_history['datalist'];

			// get comment list
			$main_comment_list = $this->getCommentData($row->id);
			$data_list[$key]->main_comment_list = $main_comment_list;			
		}

		$comp_list = DB::table('services_compensation_request as scr')
			->join('services_compensation as comp', 'scr.item_id', '=', 'comp.id')
			->whereIn('scr.complaint_id', $complaint_ids)
			->groupBy('scr.item_id')
			->select(DB::raw('sum(scr.cost) as cost, count(scr.id) as count, comp.compensation as name'))
			->get();

		$data['compensation']['list'] = $comp_list;	

		$total_comp = DB::table('services_compensation_request as scr')
			->whereIn('scr.complaint_id', $complaint_ids)
			->select(DB::raw('sum(scr.cost) as cost, count(scr.id) as count'))
			->first();	

		$currency = DB::table('property_setting as ps')
			->select(DB::raw('ps.value'))
			->where('ps.settings_key', 'currency')
			->first();

		$data['currency'] = $currency->value;

		$data['list'] = $data_list;
		$data['compensation']['total_cost'] = $total_comp->cost;
		$data['compensation']['total_count'] = $total_comp->count;
	}

	private function getComplaintSummaryDataList($query, &$data) {
		$data_list = $query				
				->select(DB::raw('sc.*, 
						gp.guest_name, cr.room, 
						CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name,	
						gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cd.department,
						cmc.name as maincategory,cc.name as nationality_name, gp.passport, sl.name as lgm_name,slt.type as lgm_type,
						cg.arrival, cg.departure, cg.vip,cg.booking_src,cg.booking_rate,cg.company, 
						jr.job_role, scft.name as feedback_type, scfs.name as feedback_source,
						(select sum(cost) from services_compensation_request where complaint_id = sc.id) as total_cost'))		
				->get();

		// Guest::getGuestList($data_list);
		ComplaintSublist::getCompleteInfo($data_list);

		$data['compensation'] = array();
		$data['compensation']['list'] = array();

		$complaint_ids = [];
		$replacement = '<span class="highlight">${1}</span>';
		foreach($data_list as $key => $row) {
			$complaint_ids[] = $row->id;

			// Highlight comment and initial response.
			$comment_highlight = '/(' . str_replace("&&", "|", $data_list[$key]->comment_highlight) . ')/';
			if( Functions::isRegularExpression($comment_highlight) )
				$data_list[$key]->comment_highlighted = preg_replace($comment_highlight, $replacement, $data_list[$key]->comment);
			else
				$data_list[$key]->comment_highlighted = $data_list[$key]->comment;

			$response_highlight = '/(' . str_replace("&&", "|", $data_list[$key]->response_highlight) . ')/';			
			if( Functions::isRegularExpression($response_highlight) )
				$data_list[$key]->initial_response_highlighted = preg_replace($response_highlight, $replacement, $data_list[$key]->initial_response);
			else
				$data_list[$key]->initial_response_highlighted = $data_list[$key]->initial_response;

			$sublist = DB::table('services_complaint_sublist as cs')
						->leftJoin('services_complaints as sc', 'cs.item_id', '=', 'sc.id')
						->join('common_department as cd', 'cs.dept_id', '=', 'cd.id')						
						->where('cs.parent_id', $row->id)	
						->where('cs.delete_flag', 0)					
						->select(DB::raw("cs.*, sc.complaint as complaint_name, cd.department, 
								REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sub_id, '0', 'A')
								, '1', 'B')
								, '2', 'C')
								, '3', 'D')
								, '4', 'E')
								, '5', 'F')
								, '6', 'G')
								, '7', 'H')
								, '8', 'I')
								, '9', 'J') as sub_label"))
						->get();

			foreach ($sublist as $key1 => $sub) {				
				if($sub->status == SC_COMPLETE)
				{
					$diff = strtotime($sub->completed_at) - strtotime($sub->created_at);
					$sublist[$key1]->elaspse_time = Functions::getHHMMSSFormatFromSecond($diff);
				}
				else
					$sublist[$key1]->elaspse_time = 'Unknown';

				if( $sub->created_at != $sub->updated_at )
					$sublist[$key1]->update_status = ' (Updated)';
				else
					$sublist[$key1]->update_status = '';				
			}

			$data_list[$key]->sublist = $sublist;

			if($row->status == C_RESOLVED || $row->status == C_REJECTED || $row->status == C_FORWARDED)
			{
				$diff = strtotime($row->updated_at) - strtotime($row->created_at);

				$data_list[$key]->resolution_time = Functions::getHHMMSSFormatFromSecond($diff);
			}	

			if($row->closed_flag == 1)
			{
				$diff = strtotime($row->closed_time) - strtotime($row->created_at);

				$data_list[$key]->total_resolution_time = Functions::getHHMMSSFormatFromSecond($diff);
			}		
		}

		$comp_list = DB::table('services_compensation_request as scr')
			->join('services_compensation as comp', 'scr.item_id', '=', 'comp.id')
			->whereIn('scr.complaint_id', $complaint_ids)
			->groupBy('scr.item_id')
			->select(DB::raw('sum(scr.cost) as cost, count(scr.id) as count, comp.compensation as name'))
			->get();

		$data['compensation']['list'] = $comp_list;	

		$total_comp = DB::table('services_compensation_request as scr')
			->whereIn('scr.complaint_id', $complaint_ids)
			->select(DB::raw('sum(scr.cost) as cost, count(scr.id) as count'))
			->first();	

		$currency = DB::table('property_setting as ps')
			->select(DB::raw('ps.value'))
			->where('ps.settings_key', 'currency')
			->first();

		$data['currency'] = $currency->value;

		$data['list'] = $data_list;
		$data['compensation']['total_cost'] = $total_comp->cost;
		$data['compensation']['total_count'] = $total_comp->count;
	}

	private function getComplaintDetailReportData($query, &$data) {
		$data_list = $query				
				->select(DB::raw('sc.*, 
						gp.guest_name, cr.room, 
						CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name,	
						gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cd.department,
						cmc.name as maincategory,cc.name as nationality_name, gp.passport, sl.name as lgm_name,slt.type as lgm_type,
						cg.arrival, cg.departure, cg.vip,cg.booking_src,cg.booking_rate,cg.company, 
						jr.job_role, scft.name as feedback_type, scfs.name as feedback_source,
						(select sum(cost) from services_compensation_request where complaint_id = sc.id) as total_cost'))		
				->get();

		// Guest::getGuestList($data_list);
		ComplaintSublist::getCompleteInfo($data_list);

		$data['compensation'] = array();
		$data['compensation']['list'] = array();

		$complaint_ids = [];
		$replacement = '<span class="highlight">${1}</span>';
		foreach($data_list as $key => $row) {
			$complaint_ids[] = $row->id;

			// Highlight comment and initial response.
			$comment_highlight = '/(' . str_replace("&&", "|", $data_list[$key]->comment_highlight) . ')/';
			if( Functions::isRegularExpression($comment_highlight) )
				$data_list[$key]->comment_highlighted = preg_replace($comment_highlight, $replacement, $data_list[$key]->comment);
			else
				$data_list[$key]->comment_highlighted = $data_list[$key]->comment;

			$response_highlight = '/(' . str_replace("&&", "|", $data_list[$key]->response_highlight) . ')/';			
			if( Functions::isRegularExpression($response_highlight) )
				$data_list[$key]->initial_response_highlighted = preg_replace($response_highlight, $replacement, $data_list[$key]->initial_response);
			else
				$data_list[$key]->initial_response_highlighted = $data_list[$key]->initial_response;

			$sublist = DB::table('services_complaint_sublist as cs')
				->leftJoin('services_complaints as sc', 'cs.item_id', '=', 'sc.id')
				->leftJoin('common_users as cu', 'cs.assignee_id', '=', 'cu.id')
				->leftJoin('services_complaint_category as scc', 'cs.category_id', '=', 'scc.id')	
				->leftJoin('services_complaint_subcategory as scs', 'cs.subcategory_id', '=', 'scs.id')	
				->leftJoin('services_complaint_type as ct', 'cs.severity', '=', 'ct.id')
				->leftJoin('services_location as sl', 'cs.location_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')	
				->leftJoin('common_users as cu2', 'cs.submitter_id', '=', 'cu2.id')
				->leftJoin('common_users as cu1', 'cs.completed_by', '=', 'cu1.id')		
				->join('common_department as cd', 'cs.dept_id', '=', 'cd.id')						
				->where('cs.parent_id', $row->id)	
				->where('cs.delete_flag', 0)					
				->select(DB::raw("cs.*, sc.complaint as complaint_name,scc.name as category_name, scs.name as subcategory_name, cd.department, CONCAT_WS(\" \", cu.first_name, cu.last_name) as assignee_name,
						sl.name as location_name, slt.type as location_type,  ct.type as severity_name,
				CONCAT_WS(\" \", cu1.first_name, cu1.last_name) as completed_by_name,  										
										CONCAT_WS(\" \", cu2.first_name, cu2.last_name) as created_by, 
						REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sub_id, '0', 'A')
						, '1', 'B')
						, '2', 'C')
						, '3', 'D')
						, '4', 'E')
						, '5', 'F')
						, '6', 'G')
						, '7', 'H')
						, '8', 'I')
						, '9', 'J') as sub_label"))
				->get();


			foreach ($sublist as $key1 => $sub) {
				$log = DB::table('services_complaint_log as scl')
					->where('scl.sub_id', $sub->id)
					->get();
				
				$sub_comp = DB::table('services_complaint_sublist_compensation as scsc')
						->leftJoin('common_users as cu', 'scsc.sub_provider_id', '=', 'cu.id')
						->join('services_compensation as comp', 'scsc.compensation_id', '=', 'comp.id')
						->select(DB::raw('scsc.*, comp.compensation as name, CONCAT_WS(" ", cu.first_name, cu.last_name) as provided_by '))
						->where('scsc.sub_id', $sub->id)
						->get();
				$sub_comp_total = DB::table('services_complaint_sublist_compensation as scsc')
						->join('services_compensation as comp', 'scsc.compensation_id', '=', 'comp.id')
						->select(DB::raw('sum(scsc.cost) as sub_comp_total'))
						->where('scsc.sub_id', $sub->id)
						->first();	

				$sublist[$key1]->log = $log; 	
				$sublist[$key1]->subcomp_list = $sub_comp; 
				$sublist[$key1]->sub_comp_total = $sub_comp_total->sub_comp_total; 

				if($sub->status == SC_COMPLETE)
				{
					$diff = strtotime($sub->completed_at) - strtotime($sub->created_at);
					$sublist[$key1]->elaspse_time = Functions::getHHMMSSFormatFromSecond($diff);
				}
				else
					$sublist[$key1]->elaspse_time = 'Unknown';

				if( $sub->created_at != $sub->updated_at )
					$sublist[$key1]->update_status = ' (Updated)';
				else
					$sublist[$key1]->update_status = '';				
			}

			$data_list[$key]->sublist = $sublist;

			$comp_list = DB::table('services_compensation_request as scr')
				->leftJoin('common_users as cu', 'scr.provider_id', '=', 'cu.id')
				->join('services_compensation as comp', 'scr.item_id', '=', 'comp.id')
				->where('scr.complaint_id', $row->id)
				->select(DB::raw('scr.*, comp.compensation as name, CONCAT_WS(" ", cu.first_name, cu.last_name) as provided_by'))
				->get();

			$data_list[$key]->comp_list = $comp_list;	

			if($row->status == C_RESOLVED || $row->status == C_REJECTED || $row->status == C_FORWARDED)
			{
				$diff = strtotime($row->updated_at) - strtotime($row->created_at);

				$data_list[$key]->resolution_time = Functions::getHHMMSSFormatFromSecond($diff);
			}	

			if($row->closed_flag == 1)
			{
				$diff = strtotime($row->closed_time) - strtotime($row->created_at);

				$data_list[$key]->total_resolution_time = Functions::getHHMMSSFormatFromSecond($diff);
			}		

			// $data_list[$key]->total_cost = $each_cost;

			// get guest history
			$guest_history = $this->getGuestHistoryReportData($row->id, $row->guest_id);
			$data_list[$key]->guest_history = $guest_history['datalist'];

			// get comment list
			$main_comment_list = $this->getCommentData($row->id);
			$data_list[$key]->main_comment_list = $main_comment_list;			
		}

		$currency = DB::table('property_setting as ps')
			->select(DB::raw('ps.value'))
			->where('ps.settings_key', 'currency')
			->first();

		$data['currency'] = $currency->value;

		$data['list'] = $data_list;		
	}

	// http://192.168.1.253:8894/frontend/report/pdfreport?report_target=complaint&property_id=4&report_by=Complaint&id=29
	public function makeComplaintDetailReportData($report) {
		$data = $report;

		$id = $report['id'];

		$query = DB::table('services_complaint_request as sc')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')				
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_department as cd', 'sc.dept_id', '=', 'cd.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
//				->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc.property_id', '=', 'cg.property_id');
				})
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')
				->leftJoin('services_complaint_maincategory as cmc', 'sc.category_id', '=', 'cmc.id')
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
				->leftJoin('services_complaint_feedback_type as scft', 'sc.feedback_type_id', '=', 'scft.id')
				->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
				->leftJoin('services_location as sl', 'sc.loc_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
				->where('sc.delete_flag',0)
				->where('sc.id', $id);
				
		$this->getComplaintDetailReportData($query, $data);
	
		return $data;
	}

	// http://192.168.1.253:8894/frontend/report/pdfreport?report_target=complaint_summary&property_id=4&report_by=Summary&report_type=Summary&start_date=2017-01-01&end_date=2017-02-10
	public function makeComplaintSummaryReportData($report) {
		$data = $report;

		$property_id = $report['property_id']; 
		$start_date = $report['start_date'];
		$end_date = $report['end_date'];
		$user_id = $report['user_id'];
		$filter_value = $report['filter_value'];

		$property_ids_by_jobrole = CommonUser::getPropertyIdsByJobrole($user_id);
		$filter = UserMeta::getComplaintTicketFilter($user_id,$property_ids_by_jobrole);

		$query = $this->applyComplaintFilter($user_id, $property_ids_by_jobrole, $filter, $filter_value, $start_date, $end_date,0);

		$this->getComplaintSummaryDataList($query, $data);

		return $data;
	}
	
	private function getFeedbackDataList($query, &$data) {
		$data_query = clone $query;
		
		$data_list = $data_query
		    ->orderBy('created_at', 'desc')
			->select(DB::raw('gr.*,gro.occasion,cg.guest_name,gp.guest_name as new_guest,cg.arrival,cg.departure,cr.room,crn.room as new_room,cp.name as property_name,CONCAT_WS(" ", ce.fname, ce.lname) as wholename'))
			->get();
	
		foreach($data_list as $key => $row) {
		
		$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
		
			if( !empty($info) )
			{
				$data_list[$key]->lgm_name = $info->name;
				$data_list[$key]->lgm_type = $info->type;
			}
			else
			{
				$data_list[$key]->lgm_name = '';
				$data_list[$key]->lgm_type = '';	
			}
		}
		$data['datalist'] = $data_list;
	}
		
	public function makeFeedbackSummaryReportData($report) {
		$data = $report;

		//$id = $report['id'];

		$property_id = $report['property_id']; 
		$start_date = $report['start_date'];
		$end_date = $report['end_date'];
		$user_id = $report['user_id'];
		$filter_value = $report['filter_value'];	
		$filter = $report['filter'];

		$date_range = sprintf("DATE(gr.created_at) >= '%s' AND DATE(gr.created_at) <= '%s'", $start_date, $end_date);
		
		$query =DB::table('services_complaint_gr as gr')
				->leftJoin('common_guest as cg', 'gr.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_room as cr', 'cg.room_id', '=', 'cr.id')
				->leftJoin('common_room as crn', 'gr.room_id', '=', 'crn.id')
				->leftJoin('common_guest_profile as gp', 'gr.guest_id', '=', 'gp.id')
				->leftJoin('common_property as cp', 'gr.property_id', '=', 'cp.id')
				->leftJoin('services_complaint_gr_occasion as gro', 'gr.occasion_id', '=', 'gro.id')
				->leftJoin('common_employee as ce', 'gr.requestor_id', '=', 'ce.id')
				->whereRaw($date_range)
				->where('gr.property_id', $property_id);	
		
		
		if( $filter != 'Total' && $filter != '')
		{
			if( $filter == 1 || $filter == F_INTERACT)	
				$query->where('gr.category', F_INTERACT);
			if( $filter == 2 || $filter == F_COURTESYS)
					$query->where('gr.category',F_COURTESYS);
			if( $filter == 3 || $filter == F_INSPECT)
					$query->where('gr.category', F_INSPECT);
			if( $filter == 4 || $filter == F_ATTENT)
					$query->where('gr.category', F_ATTENT);
			if( $filter == 5 || $filter == F_ESCORT)
					$query->where('gr.category', F_ESCORT);
			
			
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
		$this->getFeedbackDataList($query, $data);
		return $data;
		
		
	}

	public function makeCompensationReportData($report) {
		$data = $report;

		$property_id = $report['property_id']; 
		$complaint_id = $report['id'];
		$user_id = $report['user_id'];
		
		$comp_list = DB::table('services_compensation_request as scr')
			->join('services_compensation as comp', 'scr.item_id', '=', 'comp.id')
			->where('scr.complaint_id', $complaint_id)
			->select(DB::raw("scr.*, comp.compensation as item_name"))
			->get();

		$complaint = DB::table('services_complaint_request as sc')
			->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')
			->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
			->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
			->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
//			->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
			->leftJoin('common_guest as cg', function($join) {
				$join->on('gp.guest_id', '=', 'cg.guest_id');
				$join->on('sc.property_id', '=', 'cg.property_id');
			})
			->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')			
			->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
			->leftJoin('common_house_complaints_category as hcc', 'sc.housecomplaint_id', '=', 'hcc.id')			
			->leftJoin('services_complaint_reminder as scr', 'sc.id', '=', 'scr.id')	
			->where('sc.id', $complaint_id)
			->where('sc.delete_flag', 0)
			->select(DB::raw('sc.*, cr.room, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name, hcc.name as house_complaint_name, 	cp.name as property_name,				
					gp.salutation, gp.guest_name, gp.address, gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, gp.passport, 
					cg.arrival, cg.departure, jr.job_role'))
			->first();

		$user = DB::table('common_users as cu')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')	
			->where('cu.id', $user_id)
			->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,  jr.job_role'))
			->first();


		$data['comp_list'] = $comp_list;	
		$data['complaint'] = $complaint;
		$data['user'] = $user;

		return $data;
	}

	private function getKPISummary($report) {
		$start_time = $report['start_time'];
		$end_time = $report['end_time'];
		$property_id = $report['property_id'];

		$summary = array();

		$summary['total_checkin_guest'] = DB::table('common_guest_log')
					->whereBetween('arrival', array($start_time, $end_time))
					->where('property_id', $property_id)
					->count();
		$summary['inhouse_complaint'] = DB::table('services_complaint_request')
						->whereIn('guest_type', array('In-House', 'Arrival', 'Checkout'))
						->whereBetween('created_at', array($start_time, $end_time))
						->where('delete_flag', 0)
						->where('property_id', $property_id)
						->count();

		$avg_inhouse_complaint = 0;
		if( $summary['total_checkin_guest'] > 0 )
			$avg_inhouse_complaint = round($summary['inhouse_complaint'] / $summary['total_checkin_guest'], 1);

		$summary['avg_inhouse_complaint'] = $avg_inhouse_complaint;	

		$summary['walkin_complaint'] =  DB::table('services_complaint_request')
										->whereIn('guest_type', array('Walk-in'))
										->whereBetween('created_at', array($start_time, $end_time))
										->where('delete_flag', 0)
										->where('property_id', $property_id)
										->count();
		$summary['total_complaint'] = DB::table('services_complaint_request')										
										->whereBetween('created_at', array($start_time, $end_time))
										->where('delete_flag', 0)
										->where('property_id', $property_id)
										->count();

		$data = DB::table('services_complaint_request')										
										->whereBetween('created_at', array($start_time, $end_time))
										->where('delete_flag', 0)
										->where('property_id', $property_id)
										->select(DB::raw("COALESCE(AVG(TIME_TO_SEC(TIMEDIFF(closed_time, created_at))), 0) as avg_closure_time"))
										->first();

		$summary['avg_closure_days'] = round($data->avg_closure_time / 3600, 2);	
		
		$data = DB::table('services_complaint_request')										
										->whereBetween('created_at', array($start_time, $end_time))
										->where('delete_flag', 0)
										->where('property_id', $property_id)
										->select(DB::raw("COALESCE(SUM(compensation_total + subcomp_total), 0) as total_comp"))
										->first();
		$summary['total_comp'] = $data->total_comp;	

		$avg_comp = 0;
		if( $summary['total_complaint'] > 0 )
			$avg_comp = round($summary['total_comp'] / $summary['total_complaint'], 2);

		$summary['avg_comp'] = $avg_comp;	
		
		return $summary;
	}

	private function getComplaintSummaryBySource($report) {
		$start_time = $report['start_time'];
		$end_time = $report['end_time'];
		$property_id = $report['property_id'];

		$summary = array();

		// get feedback source list
		$feedback_source_list = DB::table('services_complaint_feedback_source')->where('property_id', $property_id)->get();
		$summary['source_list'] = $feedback_source_list;
		
		$select_query = 'COALESCE(count(*), 0) as total_cnt ';
		foreach($feedback_source_list as $key => $row)
		{
			$select_query .= sprintf(", COALESCE(SUM(scfs.name = '%s'), 0) as cnt%d", $row->name, $key);
		}

		$query = DB::table('services_complaint_request as sc')				
			->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
			->where('sc.delete_flag', 0)
			->where('sc.property_id', $property_id)
			->whereBetween('sc.created_at', array($start_time, $end_time))			
			->select(DB::raw($select_query));

		$datalist = $query->first();

		$summary['summary_by_source'] = $datalist;

		return $summary;
	}

	private function getConsolidateSummary($select_query, $report) {
		$start_time = $report['start_time'];
		$end_time = $report['end_time'];
		$property_id = $report['property_id'];

		$summary = array();

		$query = DB::table('services_complaint_request as sc')				
			->where('sc.delete_flag', 0)
			->where('sc.property_id', $property_id)
			->whereBetween('sc.created_at', array($start_time, $end_time))
			->select(DB::raw($select_query));
		
		// Checkoutunt	
		$data_query = clone $query;
		$summary['in_house'] = $data_query->where('sc.guest_type', 'In-House')
			->first();

		// post departure count	
		$data_query = clone $query;
		$summary['post_departure'] = $data_query->where('sc.guest_type', 'Checkout')
			->first();
			
		// pre arrival count	
		$data_query = clone $query;
		$summary['pre_arrival'] = $data_query->where('sc.guest_type', 'Arrival')
			->first();	

		// total1 count	
		$data_query = clone $query;
		$summary['total1'] = $data_query->whereIn('sc.guest_type', array('In-House', 'Checkout', 'Arrival'))
			->first();		
		
		// outside visitor count	
		$data_query = clone $query;
		$summary['outside_visitor'] = $data_query->where('sc.guest_type', 'Walk-in')
			->first();
			
		// others count	
		$data_query = clone $query;
		$summary['others'] = $data_query->where('sc.guest_type', 'House Complaint')
			->first();	

		// total1 count	
		$data_query = clone $query;
		$summary['total2'] = $data_query->whereIn('sc.guest_type', array('Walk-in', 'House Complaint'))
			->first();
			
		// grand total count	
		$data_query = clone $query;
		$summary['grand_total'] = $data_query
									->first();	

		return $summary;									
	}

	private function getCategoryCompensationTypeData($report) {
		$start_time = $report['start_time'];
		$end_time = $report['end_time'];
		$property_id = $report['property_id'];
		$location_tags = $report['location_tags'];
		$location_type_tags = $report['location_type_tags'];
		$department_tags = $report['department_tags'];

		$summary = array();

		// get componsation type list

		$query = DB::table('services_complaint_sublist_compensation as scsc')
						->join('services_complaint_sublist as scs', 'scsc.sub_id', '=', 'scs.id')
						->join('services_complaint_request as sc', 'scs.parent_id', '=', 'sc.id')
						->leftJoin('services_compensation as scmpt', 'scsc.compensation_id', '=', 'scmpt.id')
						->leftJoin('services_location as sl', 'scs.location_id', '=', 'sl.id')
						->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
						->where('sc.delete_flag', 0)
						->where('scs.delete_flag', 0)
						->where('sc.property_id', $property_id)
						->whereBetween('sc.created_at', array($start_time, $end_time));

		if(count($location_tags) > 0 ){
			$query->whereIn('scs.location_id', $location_tags);			
		}
		if(count($location_type_tags) > 0 ){
			$query->whereIn('sl.type_id', $location_type_tags);					
		}
		if(count($department_tags) >0){
			$query->whereIn('scs.dept_id', $department_tags);			
		}

		$data_query = clone $query;	

		$datalist = $data_query
						->select(DB::raw('scsc.compensation_id, scmpt.compensation'))
						->distinct()->get();

		$select_sql = "count(*) as total_cnt"; 						
		$compensation_type_list = [];
		foreach($datalist as $key => $row)
		{
			$select_sql .= ',';
			$select_sql .= sprintf("SUM(scsc.compensation_id = '%d') as cnt%d, SUM((scsc.compensation_id = '%d') * scsc.cost) as amount%d", $row->compensation_id, $key, $row->compensation_id, $key);
			$compensation_type_list[] = $row->compensation;			
		}

		$data_query = clone $query;	
		$datalist = $data_query->leftJoin('services_complaint_subcategory as cmc', 'scs.subcategory_id', '=', 'cmc.id')
						->groupBy('scs.subcategory_id')
						->orderBy('total_cnt', 'desc')
						->select(DB::raw($select_sql . ", cmc.name as category" ))
						->get();

		$summary['compensation_type_list'] = $compensation_type_list; 		 		
		$summary['category_type_list'] = $datalist; 		 		

		$data_query = clone $query;	
		$datalist = $data_query
						->select(DB::raw($select_sql))
						->first();

		$summary['total_type_data'] = $datalist; 		 								
					
		return $summary;
	}

	private function getComplaintSummaryBySatisfaction($report) {
		$start_time = $report['start_time'];
		$end_time = $report['end_time'];
		$property_id = $report['property_id'];

		$summary = array();

		$select_query = 'COALESCE(count(*), 0) as total_cnt ';
		for($i = 0; $i <= 10; $i++)
		{
			$select_query .= sprintf(", COALESCE(SUM(sc.satisfaction = %d), 0) as cnt%d", $i, $i);
		}

		$query = DB::table('services_complaint_request as sc')				
			->where('sc.delete_flag', 0)
			->where('sc.property_id', $property_id)
			->whereBetween('sc.created_at', array($start_time, $end_time))			
			->select(DB::raw($select_query));

		$datalist = $query->first();

		$summary['summary_by_satisfaction'] = $datalist;

		// generate graph
		$param = array();	

		$param['type'] = 'bar-chart';
		
		$data_array = [];
		$data = array();
		$data['name'] = "table";
		$values = [];

		for($i = 0; $i <= 10; $i++ )
		{
			$item = array();
			$group_key = $i;

			$count_key = 'cnt' . $i;
			$item['category'] = $group_key;
			$item['amount'] = (int)$datalist->$count_key;

			$values[] = $item;
		}
		$data['values'] = $values;

		$data_array[] = $data;
		$param['data'] = $data_array;

		$param['title'] = array('text' => 'INTERACTIONS & GUEST SATISFACTION' );

		$setting = PropertySetting::getServerConfig(0);

		$summary['export_server'] = $setting['export_server'] . 'exportchart';

		$graph = Curl::to($setting['export_server'] . 'vegachart')
            ->withData($param)
            ->asJson()
            ->post();    

        if( !empty($graph) && !empty($graph->data) )    
        	$summary['graph'] = $graph->data;    
        else
			$summary['graph'] = ''; 
			
		$summary['graph_style'] = Functions::isSuperAgent() && Functions::isLinux() ? 'style=width:500px' : '';	

		return $summary;
	}

	private function getComplaintSummaryByHotIssue($report) {
		$start_time = $report['start_time'];
		$end_time = $report['end_time'];
		$property_id = $report['property_id'];

		$summary = array();

		$query = DB::table('services_complaint_request as sc')				
			->where('sc.delete_flag', 0)
			->where('sc.property_id', $property_id)
			->whereBetween('sc.created_at', array($start_time, $end_time))			
			->select(DB::raw('count(*) as cnt, sc.hot_issue'))
			->groupBy('sc.hot_issue')
			->orderBy('cnt', 'desc');

		$datalist = $query->get();

		$summary['summary_by_hotissue'] = $datalist;

		// generate graph
		
		// generate graph
		$param = array();	

		$param['type'] = 'bar-chart';
		
		$data_array = [];
		$data = array();
		$data['name'] = "table";
		$values = [];

		foreach($datalist as $key => $row)
		{
			$item = array();

			if( empty($row->hot_issue) )
				$row->hot_issue = 'Unknown';

			$item['category'] = $row->hot_issue;
			$item['amount'] = (int)$row->cnt;		

			$values[] = $item;
		}

		$data['values'] = $values;

		$data_array[] = $data;
		$param['data'] = $data_array;

		$param['title'] = array('text' => 'FREQUENCY REPORT' );


		$setting = PropertySetting::getServerConfig(0);

		$summary['export_server'] = $setting['export_server'] . 'exportchart';

		$graph = Curl::to($setting['export_server'] . 'vegachart')
            ->withData($param)
            ->asJson()
            ->post();    

        if( !empty($graph) && !empty($graph->data) )    
        	$summary['graph'] = $graph->data;    
        else
			$summary['graph'] = ''; 
			
		$summary['graph_style'] = Functions::isSuperAgent() && Functions::isLinux() ? 'style=width:500px' : '';	
		$summary['graph_option'] = $param;

		return $summary;
	}

	public function getComplaintConsolidatedForCSV($report, &$ret)
	{
		$start_time = $report['start_time'];
		$end_time = $report['end_time'];

		$main_query = DB::table('services_complaint_request as sc')	
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc.property_id', '=', 'cg.property_id');
				})					
				->leftJoin('common_vip_codes as vc', 'vc.vip_code', '=', 'cg.vip')
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')			
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
				->leftJoin('common_house_complaints_category as hcc', 'sc.housecomplaint_id', '=', 'hcc.id')			
				->leftJoin('services_complaint_reminder as scr', 'sc.id', '=', 'scr.id')
				->leftJoin('services_complaint_maincategory as cmc', 'sc.category_id', '=', 'cmc.id')
				->leftJoin('services_complaint_feedback_type as scft', 'sc.feedback_type_id', '=', 'scft.id')
				->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
				->where('sc.delete_flag',0)
				->whereBetween('sc.created_at', array($start_time, $end_time));
		
		$ret['main_complaint_list'] = $main_query->select(DB::raw('sc.*, gp.guest_name, cr.room, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, ct.type as severity_name, hcc.name as house_complaint_name, ce.design,				
				DATEDIFF(CURTIME(), sc.incident_time) as age_days,
				gp.mobile, gp.phone, cg.first_name as fname,gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, cp.name as property_name, cp.shortcode,gp.guest_id as gs_guest_id, gp.passport, gp.comment as guest_comment, gp.pref, gp.check_flag,
				scr.reminder_ids, scr.reminder_flag, scr.reminder_time, scr.comment as reminder_comment, scft.name as feedback_type, scfs.name as feedback_source,
				sc.mark_flag as flag, cg.arrival, cg.departure,cg.booking_src,vc.name as vip,cg.booking_rate,cg.company, jr.job_role, cmc.name as category_name'))		
				->get();

		$sub_query = DB::table('services_complaint_sublist as cs')
				->leftJoin('services_complaints as item', 'cs.item_id', '=', 'item.id')
				->leftJoin('services_complaint_request as sc', 'cs.parent_id', '=', 'sc.id')
				->leftJoin('common_users as cu2', 'cs.submitter_id', '=', 'cu2.id')
				->leftJoin('common_users as cu', 'cs.completed_by', '=', 'cu.id')
				->leftJoin('services_complaint_type as ct', 'cs.severity', '=', 'ct.id')
				->leftJoin('services_complaint_category as scc', 'cs.category_id', '=', 'scc.id')	
				->leftJoin('services_complaint_subcategory as scs', 'cs.subcategory_id', '=', 'scs.id')	
				->leftJoin('services_location as sl', 'cs.location_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
				->leftJoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')
				->where('sc.delete_flag', 0)
				->where('cs.delete_flag', 0)
				->whereBetween('sc.created_at', array($start_time, $end_time));				

		$ret['sub_complaint_list'] = $sub_query->select(DB::raw("cs.*, cd.department, 
										CONCAT_WS(\" \", cu.first_name, cu.last_name) as completed_by_name,  										
										CONCAT_WS(\" \", cu2.first_name, cu2.last_name) as created_by,  										
										scc.name as category_name, scs.name as subcategory_name,
										sl.name as location_name, slt.type as location_type,  										
										(select sum(cost) from services_complaint_sublist_compensation where sub_id = cs.id) as sub_comp, 
										ct.type as severity_name, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sub_id, '0', 'A')
											, '1', 'B')
											, '2', 'C')
											, '3', 'D')
											, '4', 'E')
											, '5', 'F')
											, '6', 'G')
											, '7', 'H')
											, '8', 'I')
											, '9', 'J') as sub_label"))									
									->get();
	}

	public function getComplaintConsolidated($report, &$ret) {
		// $start_time = $report['start_time'];
		// $end_time = $report['end_time'];

		// if( isset($report['excel_type']) && $report['excel_type'] == 'csv' )
		// {
		// 	return $this->getComplaintConsolidatedForCSV($report, $ret);
		// }

		// ---------- KPI Summary ---------------------------------
		$ret['kpi_summary'] = $this->getKPISummary($report);

		// ---------- Source By Summary ---------------------------------
		$ret['source_summary'] = $this->getComplaintSummaryBySource($report);
		
		//----------- STATUS OF COMPLAINTS ---------------------------
		$status_summary_sql = "COALESCE(SUM(final_status = 'Open'), 0) as open_cnt, 
						COALESCE(SUM(final_status = 'Re-open'), 0) as reopen_cnt, 
						COALESCE(SUM(final_status = 'Closed'), 0) as closed_cnt, 
						COALESCE(count(*), 0) as total_cnt";
		
		$ret['status_summary'] = $this->getConsolidateSummary($status_summary_sql, $report);

		//----------- CLOSURE RATE OF COMPLAINTS ---------------------------							

		$closure_rate_summary_sql = "COALESCE(SUM(closed_flag = 1 AND TIME_TO_SEC(TIMEDIFF(sc.closed_time, sc.incident_time)) <= 3600), 0) as within_cnt, 						
						COALESCE(count(*), 0) as total_cnt";
			
		$ret['closure_rate_summary'] = $this->getConsolidateSummary($closure_rate_summary_sql, $report);	
		foreach($ret['closure_rate_summary'] as $key => $row)
		{
			$row->about_cnt = $row->total_cnt - $row->within_cnt;
			if( $row->total_cnt > 0 )
			{
				$row->within_percent = round($row->within_cnt * 100 / $row->total_cnt, 1);
				$row->about_percent = 100 - $row->within_percent;
			}
			else
			{
				$row->within_percent = 0;
				$row->about_percent = 0;
			}
		}	

		//----------- Category / Compensation Type -----------------------------------
		$ret['category_type_summary'] = $this->getCategoryCompensationTypeData($report);	

		//----------- Satisfation Summary ----------------------------------
		$ret['satisfaction_summary'] = $this->getComplaintSummaryBySatisfaction($report);	

		//----------- Hot Issue Summary ----------------------------------
		$ret['hot_issue_summary'] = $this->getComplaintSummaryByHotIssue($report);	
	}


	public function getSeverityList(Request $request) {
		$model = Db::table('services_complaint_type')->get();

		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $model;

		return Response::json($ret);
 	}

 	public function getHouseComplaintList(Request $request) {
		$model = Db::table('common_house_complaints_category')->get();

		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['content'] = $model;

		return Response::json($ret);
	 }
	 
	public function getComplaintInfoData(Request $request)
	{
		$client_id = $request->get('client_id', 4);
		$property_id = $request->get('property_id', 4);

		$model = array();

		$model['severity_list'] = DB::table('services_complaint_type')->get();
		$model['feedback_type_list'] = DB::table('services_complaint_feedback_type')->get();
		$model['feedback_source_list'] = DB::table('services_complaint_feedback_source')->get();
		$model['category_list'] = DB::table('services_complaint_maincategory as scmc')
									->leftJoin('common_users as cu', 'scmc.user_id', '=', 'cu.id')
									->leftJoin('services_complaint_type as ct', 'scmc.severity', '=', 'ct.id')
									->leftJoin('common_property as cp','scmc.property_id','=','cp.id')
									->where('cp.client_id', $client_id)
									->select(DB::raw('scmc.*, ct.type, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
									->orderBy('scmc.name', 'asc')
									->get();	
		$model['dept_list'] = DB::table('common_department')
									->where('property_id', $property_id)
									->get();
		$model['housecomplaint_category'] = Db::table('common_house_complaints_category')->get();

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $model;

		return Response::json($ret);
	}

	public function getDepartmentList(Request $request) {
		$property_id = $request->get('property_id', 4);
		$list = DB::table('common_department')
					->where('property_id', $property_id)
					->get();
		$ret = [];
		$ret['code'] = 200;
		$ret['content'] = $list;
		return Response::json($ret);
	}

 	public function createMainCategory(Request $request) {
		$id = $request->get('id', '');
		$name = $request->get('name', '');
		$severity = $request->get('severity', 0);
		$division_id = $request->get('division_id', 0);
		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 0);

		if( $id > 0 )
			$category = ComplaintMainCategory::find($id);
		else
			$category = new ComplaintMainCategory();

		if( !empty($category) )
		{
			$category->name = $name;
			$category->severity = $severity;
			$category->division_id = $division_id;
			$category->property_id = $property_id;
			$category->user_id = $user_id;

			$category->save();
		}

		$category_list = DB::table('services_complaint_maincategory as scmc')
			->leftJoin('common_users as cu', 'scmc.user_id', '=', 'cu.id')
			->leftJoin('services_complaint_type as ct', 'scmc.severity', '=', 'ct.id')
			->leftJoin('common_division as ci', 'scmc.division_id', '=', 'ci.id')	
			->where('scmc.property_id', $property_id)
			->where('scmc.disabled', 0)
			->select(DB::raw('scmc.*, ct.type, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, ci.division'))
			->orderBy('scmc.name', 'asc')
			->get();	

 		return Response::json($category_list);	
	}
	 
	public function deleteMainCategory(Request $request) {
		$id = $request->get('id', '');
		$property_id = $request->get('property_id', 0);
		
		ComplaintMainCategory::where('id', $id)->update(['disabled' => 1]);
		ComplaintMainSubCategory::where('category_id', $id)->update(['disabled' => 1]);
		
		$category_list = DB::table('services_complaint_maincategory as scmc')
		   ->leftJoin('common_users as cu', 'scmc.user_id', '=', 'cu.id')
		   ->leftJoin('services_complaint_type as ct', 'scmc.severity', '=', 'ct.id')
		   ->where('scmc.property_id', $property_id)
		   ->where('scmc.disabled', 0)
		   ->select(DB::raw('scmc.*, ct.type, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
		   ->orderBy('scmc.name', 'asc')
		   ->get();	

		return Response::json($category_list);	
	}

 	public function getCategoryList(Request $request) {
 		$dept_id = $request->get('dept_id', 0);

 		$query = DB::table('services_complaint_category as scc')
 			->leftJoin('common_users as cu', 'scc.user_id', '=', 'cu.id');

 		if( $dept_id > 0 )
 			$query->where('scc.dept_id', $dept_id);

 		$list = $query->select(DB::raw('scc.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
 			->get();

 		return Response::json($list);	
	}
	 
	public function createMainSubCategory(Request $request) {
		$id = $request->get('id', '');
		$name = $request->get('name', '');
		$category_id = $request->get('category_id', 0);
		$user_id = $request->get('user_id', 0);
		
		if( $id > 0 )
			$subcategory = ComplaintMainSubCategory::find($id);
		else
			$subcategory = new ComplaintMainSubCategory();

		if( !empty($subcategory) )
		{
			$subcategory->name = $name;
			$subcategory->category_id = $category_id;			
			$subcategory->user_id = $user_id;

			$subcategory->save();
		}

		return $this->getMainSubCategoryList($request);
	}
	 
	public function deleteMainSubCategory(Request $request) {
		$id = $request->get('id', '');
		$category_id = $request->get('category_id', 0);
		
		ComplaintMainSubCategory::where('id', $id)->update(['disabled' => 1]);
		
		return $this->getMainSubCategoryList($request);
	}
	
	public function getComplaintLocationList(Request $request) { 	
		$dept_id = $request->get('dept_id', 0);

		$ret = array();

		$ret['loc_list'] = DB::table('services_complaint_dept_location_pivot as scdlp')
				->leftJoin('services_location as sl', 'scdlp.location_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
				->where('scdlp.dept_id', $dept_id)
				->select(DB::raw('sl.*, CONCAT(sl.name, " - ", slt.type) as loc_name'))		
				->get();

		$ret['loc_type_list'] = DB::table('services_complaint_dept_location_type_pivot as scdlp')				
				->leftJoin('services_location_type as slt', 'scdlp.loc_type_id', '=', 'slt.id')
				->where('scdlp.dept_id', $dept_id)
				->select(DB::raw('slt.*'))		
				->get();		
		
 		return Response::json($ret);	
 	}


 	public function createCategory(Request $request) {
 		$name = $request->get('name', '');
 		$user_id = $request->get('user_id', 0);
 		$dept_id = $request->get('dept_id', 0);

 		$category = new ComplaintCategory();

 		$category->name = $name;
 		$category->dept_id = $dept_id;
		$category->user_id = $user_id;

 		$category->save();

 		return $this->getCategoryList($request);	
 	}

 	public function getSubcategoryList(Request $request) {
 		$category_id = $request->get('category_id', 0);
 		$dept_id = $request->get('dept_id', 0);

 		$query = DB::table('services_complaint_subcategory as scs')
 			->join('services_complaint_category as scc', 'scs.category_id', '=', 'scc.id') 			
 			->leftJoin('common_users as cu', 'scs.user_id', '=', 'cu.id');

 		if( $category_id > 0 )
 			$query->where('scs.category_id', $category_id);

 		if( $dept_id > 0 )
 			$query->where('scc.dept_id', $dept_id);

 		$list = $query->select(DB::raw('scs.*, scc.name as category_name, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
 			->get();

 		return Response::json($list);	
 	}

 	public function createSubcategory(Request $request) {
 		$name = $request->get('name', '');
 		$user_id = $request->get('user_id', 0);
 		$category_id = $request->get('category_id', 0);

 		$subcategory = new ComplaintSubcategory();

 		$subcategory->name = $name;
 		$subcategory->category_id = $category_id;
		$subcategory->user_id = $user_id;

 		$subcategory->save();

 		return $this->getSubcategoryList($request);	
 	}

 	public function saveCategory(Request $request) { 		
 		$id = $request->get('id', 0);
 		$category_id = $request->get('category_id', 0);
 		$user_id = $request->get('user_id', 0);

		 $sub = ComplaintSublist::find($id);
	
		
 		if( !empty($sub) )
 		{
			$old_cat_id = $sub->category_id;
 			$sub->category_id = $category_id;
 			$sub->save();
		}
		$old_category = DB::table('services_complaint_category')
 			->where('id', $old_cat_id)
 			->first(); 


 		$category = DB::table('services_complaint_category')
 			->where('id', $category_id)
 			->first();

 		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $sub->parent_id;
		$complaint_log->sub_id = $id;
		$complaint_log->comment = 'Category changed from (' . $old_category->name . ') to (' . $category->name . ')' ;
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$sub->category_name = $category->name;
 		
 		return Response::json($sub);	
 	}

 	public function saveSubcategory(Request $request) { 		
 		$id = $request->get('id', 0);
 		$subcategory_id = $request->get('subcategory_id', 0);
 		$user_id = $request->get('user_id', 0);

		$sub = ComplaintSublist::find($id);
	
		 
 		if( !empty($sub) )
 		{
			$old_subcat_id = $sub->subcategory_id;
 			$sub->subcategory_id = $subcategory_id;
 			$sub->save();
		}
		 
		$old_subcategory = DB::table('services_complaint_subcategory')
 			->where('id', $old_subcat_id)
 			->first();

 		$subcategory = DB::table('services_complaint_subcategory')
 			->where('id', $subcategory_id)
 			->first();

 		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $sub->parent_id;
		$complaint_log->sub_id = $id;
		$complaint_log->comment = 'Sub-Category changed from (' . $old_subcategory->name . ') to (' . $subcategory->name . ')' ;
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();
 		
 		return Response::json($sub);	
	 }
	 
	 public function saveLocation(Request $request) { 		
		$id = $request->get('id', 0);
		$location_id = $request->get('location_id', 0);
		$user_id = $request->get('user_id', 0);

		$sub = ComplaintSublist::find($id);
	
		if( !empty($sub) )
		{
			$old_loc_id = $sub->location_id;
			$sub->location_id = $location_id;
			$sub->save();
		}

		$old_location = DB::table('services_location as sl')
			->leftjoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			->where('sl.id', $old_loc_id)
			->select(DB::raw('sl.name, slt.type'))
			->first();

		$location = DB::table('services_location as sl')
			->leftjoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			->where('sl.id', $location_id)
			->select(DB::raw('sl.name, slt.type'))
			->first();

		$complaint_log = new ComplaintLog();

	   $complaint_log->complaint_id = $sub->parent_id;
	   $complaint_log->sub_id = $id;
	   $complaint_log->comment = 'Location changed from (' . $old_location->name . ' '. $old_location->type . ') to (' . $location->name . ' '. $location->type . ')' ;
	   $complaint_log->type = 0;
	   $complaint_log->user_id = $user_id;
	   
	   $complaint_log->save();
		
		return Response::json($sub);	
	}

 	public function uploadFiles(Request $request) {
 		$output_dir = "uploads/complaint/";
		
		if(!File::isDirectory(public_path($output_dir)))
			File::makeDirectory(public_path($output_dir), 0777, true, true);

		$ret = array();

		$filekey = 'files';

		$id = $request->get('id', 0);
		
		// if($request->hasFile($filekey) === false )
		// 	return "No input file";
		
		//You need to handle  both cases
		//If Any browser does not support serializing of multiple files using FormData() 
		
		$fileCount = count($_FILES[$filekey]["name"]);
		$complaint = ComplaintRequest::find($id);
		if(!empty($complaint->path)){

			$multiple = explode("|",$complaint->path);
			$count = count($multiple);
		}
		else{
			$count = 0;
		}
		
		$path = '';

		for ($i = 0; $i < $fileCount; $i++)
		{
			$fileName = $_FILES[$filekey]["name"][$i];
			$ext = pathinfo($fileName, PATHINFO_EXTENSION);	
			$filename1 = "complaint_" . $id . '_' . $count . '_' . $fileName;
			
			$dest_path = $output_dir . $filename1;
			move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);						
			if( $i > 0  || !empty($complaint->path))
				$path .= '|';

			$path .=  $dest_path;	
			$count++;		
		} 

		$complaint = ComplaintRequest::find($id);
		
		if( !empty($complaint) )		
		{
		//	$path .= '|';		
			$complaint->path .= $path; 

			$complaint->save();			
		}

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $complaint->path;
		
		return Response::json($ret);
	}

	public function updateFilePath(Request $request) {
		$id = $request->get('id', 0);
		$path = $request->get('path', '');

		$complaint = ComplaintRequest::find($id);
		
		if( !empty($complaint) )		
		{		
			$complaint->path = $path; 
			$complaint->save();			
		}

		$ret = array();
		$ret['code'] = 200;
		$ret['content'] = $path;
		
		return Response::json($ret);
   	}
	 
	 public function uploadFileGuest(Request $request) {
 		$output_dir = "uploads/complaint/";

		if(!File::isDirectory(public_path($output_dir)))
			File::makeDirectory(public_path($output_dir), 0777, true, true);
		
		$ret = array();

		$filekey = 'guest_image';

		$id = $request->get('id', 0);
		
		// if($request->hasFile($filekey) === false )
		// 	return "No input file";
		
		//You need to handle  both cases
		//If Any browser does not support serializing of multiple files using FormData() 
		
		$fileCount = count($_FILES[$filekey]["name"]);
		$path = '';
		for ($i = 0; $i < $fileCount; $i++)
		{
			$fileName = $_FILES[$filekey]["name"][$i];
			$ext = pathinfo($fileName, PATHINFO_EXTENSION);	
			$filename1 = "complaint_guest_" . $id . '_' . $i . '_' . $fileName;
			
			$dest_path = $output_dir . $filename1;
			move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);						
			if( $i > 0 )
				$path .= '|';

			$path .=  $dest_path;			
		}

		$complaint = ComplaintRequest::find($id);
		if( !empty($complaint) )
		{
			$complaint->guest_path = $path;
			$complaint->save();			
		}
		$ret = array();
		$ret['files']=$_FILES[$filekey];
		$ret['path']=$path;
		return Response::json($ret);
 	}

 	public function uploadFilesToSubcomplaint(Request $request) {
 		$output_dir = "uploads/complaint/";

		if(!File::isDirectory(public_path($output_dir)))
			File::makeDirectory(public_path($output_dir), 0777, true, true);

 		$user_id = $request->get('user_id', 0);
		
		$ret = array();

		$filekey = 'files';

		$id = $request->get('id', 0);
		
		// if($request->hasFile($filekey) === false )
		// 	return "No input file";
		
		//You need to handle  both cases
		//If Any browser does not support serializing of multiple files using FormData() 
		
		$fileCount = count($_FILES[$filekey]["name"]);
		$path = '';
		for ($i = 0; $i < $fileCount; $i++)
		{
			$fileName = $_FILES[$filekey]["name"][$i];
			$ext = pathinfo($fileName, PATHINFO_EXTENSION);	
			$filename1 = "subcomplaint_" . $id . '_' . $i . '_' . $fileName;
			
			$dest_path = $output_dir . $filename1;
			move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);						
			if( $i > 0 )
				$path .= '|';

			$path .=  $dest_path;			
		}

		$sub = ComplaintSublist::find($id);
		if( !empty($sub) )
		{
			if( empty($sub->path) ) 
				$sub->path = $path;
			else
				$sub->path .= '|' . $path;

			$sub->save();		

			$complaint = ComplaintRequest::find($sub->parent_id);			
			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
		}

		return Response::json($sub);
 	}

 	public function removeFilesFromSubcomplaint(Request $request) {
 		$id = $request->get('id', 0);
 		$user_id = $request->get('user_id', 0);
 		$index = $request->get('index', 0);
		
		$sub = ComplaintSublist::find($id);
		if( !empty($sub) )
		{
			$path_array = explode('|', $sub->path);

			unset($path_array[$index]);
			$sub->path = implode('|', $path_array);

			$sub->save();

			$complaint = ComplaintRequest::find($sub->parent_id);
			ComplaintUpdated::modifyByUser($id, $user_id);

			$complaint->sub = $sub;

			$this->sendRefreshEvent($complaint->property_id, 'subcomplaint_files_changed', $complaint, $user_id);
		}



		return Response::json($sub);
 	}

	// 192.168.1.253/schedule/complaint/generatereport?property_id=4
 	public function generateReport(Request $request) {
 		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		
 		$property_id = $request->get('property_id', 0);

 		$settings = array();

		$settings['complaint_report_time_interval'] = 6;
		$settings['complaint_report_recipients'] = '';

		$settings = PropertySetting::getPropertySettings($property_id, $settings);
		
		// calc start time based on hours
		$start_time = date('Y-m-d H:i:s', strtotime($cur_time) - $settings['complaint_report_time_interval'] * 3600);

		// $start_time = "2017-03-01 00:00:00"; 
		
		$this->generateReportProc($property_id, $start_time, $cur_time, $settings);
		//}
 	}

 	public function generateReportProc($property_id, $start_time, $end_time, $settings) 	
 	{
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
 		$data = array();

 		$data['report_type'] = 'complaint_summary';
		$data['report_by'] = 'complaint_summary';
		$data['property_id'] = $property_id;
		$data['property'] = Property::find($property_id);
		$data['start_date'] = $start_time;
		$data['end_date'] = $end_time;
		$check_flag = CronLogs::checkDuplicates($cur_time,json_encode($data));

		if($check_flag==true)
		{
			$query = DB::table('services_complaint_request as sc')
				->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')				
				->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
				->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
//				->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
				->leftJoin('common_guest as cg', function($join) {
					$join->on('gp.guest_id', '=', 'cg.guest_id');
					$join->on('sc.property_id', '=', 'cg.property_id');
				})
				->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')
				->leftJoin('services_complaint_maincategory as cmc', 'sc.category_id', '=', 'cmc.id')	
				->leftJoin('services_complaint_type as ct', 'sc.severity', '=', 'ct.id')
				->leftJoin('services_complaint_feedback_type as scft', 'sc.feedback_type_id', '=', 'scft.id')
				->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
				->leftJoin('services_location as sl', 'sc.loc_id', '=', 'sl.id')
				->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
				->where('sc.delete_flag',0)
				->whereBetween('sc.updated_at', array($start_time, $end_time))
				->where('sc.property_id', $property_id);

			$this->getComplaintSummaryDataList($query, $data);

			$data['start_date'] = date('h:i A', strtotime($start_time));
			$data['end_date'] = date('h:i A', strtotime($end_time));

			$filename = 'Hotlync_Summary_Feedback_Report_' . date('d_m_hA', strtotime($end_time));
			$filename = str_replace(' ', '_', $filename);

			$folder_path = public_path() . '/uploads/reports/';
			$path = $folder_path . $filename . '.html';
			$pdf_path = $folder_path . $filename . '.pdf';

			ob_start();

			$content = view('frontend.report.complaint_summary_pdf', compact('data'))->render();

			echo $content;

			file_put_contents($path, ob_get_contents());

			ob_clean();
			$recipients = $this->getPropertySetting($property_id, 'complaint_report_recipients');
			
			$request = array();
		//	$request['to'] = Functions::getUserEmailArray($settings['complaint_report_recipients'], ";");
			$request['to'] = $recipients->value;
			$request['subject'] = 'HotLync Feedback Report';
			$request['filename'] = $filename . '.pdf';

			$info = array();
			$info['start_time'] = date('H:i', strtotime($start_time));
			$info['end_time'] = date('H:i', strtotime($end_time));
			$info['date'] = date('d F Y', strtotime($end_time));
			$info['company_name'] = 'EnnovaTech Solutions';

			$request['content'] = view('emails.complaint_report', ['info' => $info])->render();
			
			$smtp = Functions::getMailSetting($property_id, '');
			$request['smtp'] = $smtp;

			$options = array();
			$options['html'] = $path;
			$options['paperSize'] = array('format' => 'A4', 'orientation' => 'portrait');

			$request['options'] = $options;

			$message = array();
			$message['type'] = 'report_pdf';
			$message['content'] = $request;

			Redis::publish('notify', json_encode($message));

			echo json_encode($message);
		}
	 }
	 
	 function getPropertySetting($property_id, $setting_key) {

		$data = DB::table('property_setting')
			->where('settings_key', $setting_key)
			->where('property_id', $property_id)
			->first();
		return  $data;
	}

 	public function getMyFilterList(Request $request)
	{
		$property_id = $request->get('property_id', 4);
		$dispatcher = $request->get('dispatcher', 0);		
		$dept_id = $request->get('dept_id', 0);
		$job_role_id = $request->get('job_role_id', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$user = DB::table('common_users as cu')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->where('cu.id', $dispatcher)
			->select(DB::raw('cu.*, jr.manager_flag'))
			->first();

		$filterlist = array();

		$mytask = array();

		$approval = array();
		$approval['name'] = 'Approvals';
		$approval['badge'] = DB::table('services_compensation_state as cs')
			->join('services_complaint_request as cr', 'cs.task_id', '=', 'cr.id')
			->where('cs.dispatcher', $job_role_id)
			->where('cr.property_id', $property_id)
			->where('cs.running', 1)
			->where('cr.delete_flag', 0)
			->whereIn('status_id', array(CP_ON_ROUTE))
			->count();

		array_push($filterlist, $approval);

		$complaint = array();
		$complaint['name'] = 'Complaints';
		$query = DB::table('services_complaint_sublist as cs')->where('cs.delete_flag', 0);

		if( $user->manager_flag != 0 )	// manager
			$query->where('cs.dept_id', $dept_id);	
		else 							// general staff
			$query->where('cs.assignee_id', $dispatcher);	

		$complaint['badge'] = $query->count();

		array_push($filterlist, $complaint);

		$returned = array();
		$returned['name'] = 'Returned';
		$where = sprintf("st.compensation_status = 3 and cs.running = 1 and cs.attendant = %d", $dispatcher);
		$returned['badge'] = DB::table('services_compensation_state as cs')
				->leftJoin('services_task as st', 'cs.task_id', '=', 'st.id')
				->whereRaw($where)
				->count();
		array_push($filterlist, $returned);

		return Response::json($filterlist);
	}

	public function saveReminder(Request $request) {
		$user_id = $request->get('user_id', 0);
		$complaint_id = $request->get('id', 0);
		$reminder_time = $request->get('reminder_time', '');
		$reminder_ids = $request->get('reminder_ids', '[]');
		$reminder_flag = $request->get('reminder_flag', 0);
		$reminder_comment = $request->get('reminder_comment', '');

		$reminder = ComplaintReminder::find($complaint_id);
		if(empty($reminder))
			$reminder = new ComplaintReminder();

		$reminder->id = $complaint_id;
		$reminder->reminder_ids = $reminder_ids;
		$reminder->reminder_flag = $reminder_flag;
		$reminder->reminder_time = $reminder_time;
		$reminder->comment = $reminder_comment;
		$reminder->by_user = $user_id;
		$reminder->ack = 0;

		$reminder->save();

		$reminder_list = DB::table('common_users as cu')
			->whereIn('cu.id', json_decode($reminder_ids))
			->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();

		$comment = '';	
		foreach($reminder_list as $key => $row)	
		{
			if( $key > 0 )
				$comment .= ',';
			$comment .= $row->wholename;
		}

		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $complaint_id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = 'Remider Set: ' . $comment;
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();	

		return Response::json($reminder);	
	}

	public function checkReminderStateProc() {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$reminder_list = DB::table('services_complaint_reminder as rmd')
			->join('services_complaint_request as sc', 'rmd.id', '=', 'sc.id')
			->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
//			->leftJoin('common_guest as cg', 'gp.guest_id', '=', 'cg.guest_id')
			->leftJoin('common_guest as cg', function($join) {
				$join->on('gp.guest_id', '=', 'cg.guest_id');
				$join->on('sc.property_id', '=', 'cg.property_id');
			})
			->where('rmd.ack', 0)
			->where('rmd.reminder_flag', 1)
			->where('rmd.reminder_time', '<=', $cur_time)
			->where('sc.delete_flag', 0)
			->select(DB::raw('sc.*, gp.guest_name, rmd.id as rmd_id, rmd.reminder_ids, rmd.by_user, rmd.comment as rmd_comment'))
			->get();

		foreach($reminder_list as $key => $row) {
			$info = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationInfo($row->loc_id);
			if( !empty($info) )
			{
				$reminder_list[$key]->lgm_name = $info->name;
				$reminder_list[$key]->lgm_type = $info->type;
				$reminder_list[$key]->lg_property_id = $info->property_id;
			}			
		}	

		$property_list = DB::table('common_property')
			->get();	

		$smtp_list = array();
			
		foreach($property_list as $row)
		{
			$smtp_list[$row->id] = Functions::getMailSetting($row->id, 'notification_');
		}

		foreach ($reminder_list as $key => $row) {
			$smtp = $smtp_list[$row->property_id];
			if( empty($smtp) )
				continue;

			$reminder_ids = json_decode($row->reminder_ids);
			$reminder_user_list = DB::table('common_users as cu')
				->whereIn('cu.id', $reminder_ids)
				->where('cu.deleted', 0)
				->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();

			$by_user = DB::table('common_users as cu')
			    ->where('cu.id', $row->by_user)
			    ->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			    ->first();	

			$info = array();
			$info['ticket_info'] = sprintf('C%05d %s - %s %s', $row->id, $row->lgm_type, $row->lgm_name, $row->guest_name);			
			if( empty($by_user) )
				$info['by_name'] = 'EnnovaTech';
			else
				$info['by_name'] = $by_user->wholename;

			$info['reminder_comment'] = $row->rmd_comment;
			$info['complaint_comment'] = $row->comment;
			
			$payload = array();
			$payload['ack'] = 1;
			$payload['table_id'] = $row->rmd_id;
			$payload['table_name'] = 'services_complaint_reminder';

			echo json_encode($reminder_user_list);
					
			foreach ($reminder_user_list as $key1 => $reminder) {
				$info['wholename'] = $reminder->wholename;
				$email_content = view('emails.complaint_reminder', ['info' => $info])->render();
				
				$message = array();

				$message['type'] = 'email';
				$message['to'] = $reminder->email;
				$message['subject'] = 'Complaint Reminder';
				$message['content'] = $email_content;			
				$message['smtp'] = $smtp;
				$message['payload'] = $payload;

				Redis::publish('notify', json_encode($message));		

				$delegated_user = ShiftGroupMember::getDelegatedUser($reminder->id);
				if( !empty($delegated_user) )
				{
					$info['wholename'] = $delegated_user->wholename;
					$email_content = view('emails.complaint_reminder', ['info' => $info])->render();
					
					$message['to'] = $delegated_user->email;
					$message['content'] = $email_content;			
					
					Redis::publish('notify', json_encode($message));		
				}
			}	
		}
	}

	public function getCompensationTemplate(Request $request) {
		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 0);
		
		$ret = array();

		$model = DB::table('services_complaint_compensation_template')
			->where('property_id', $property_id)
			->first();

		if( empty($model) )
			$ret['template'] = '';
		else
			$ret['template'] = $model->template;

		$ret['temp_item_list'] = CompensationTemplate::getTemplateElementList();

		$ret['code'] = 200;

		return Response::json($ret);	
	}

	public function saveCompensationTemplate(Request $request) {
		$user_id = $request->get('user_id', 0);
		$property_id = $request->get('property_id', 0);
		$template = $request->get('template', '');
		
		$ret = array();

		$model = CompensationTemplate::where('property_id', $property_id)
			->first();

		if( empty($model) )
		{
			$model = new CompensationTemplate();
			$model->property_id = $property_id;
		}	

		$model->template = $template;
		$model->modified_by = $user_id;
		$model->save();

		$ret['code'] = 200;

		return Response::json($ret);	
	}

	public function migrateCompensationItem(Request $request) {
		$complaint_list = DB::table('services_complaint_request as sc')
			->join('services_compensation as comp', 'sc.compensation_id', '=', 'comp.id')
			->where('sc.compensation_id', '>', 0)
			->where('sc.delete_flag', 0)
			->select(DB::raw('sc.*, comp.cost'))
			->get();

		foreach($complaint_list as $row) {
			$comp = new CompensationRequest();

			$comp->complaint_id = $row->id;
			$comp->item_id = $row->compensation_id;
			$comp->cost = $row->cost;
			$comp->status = $row->compensation_status;
			$comp->comment = '';

			$comp->save();

			DB::table('services_compensation_comments')
				->where('task_id', $row->id)
				->update(array('comp_id' => $comp->id));

			DB::table('services_compensation_state')
				->where('task_id', $row->id)
				->update(array('comp_id' => $comp->id));	
		}	

	}

	public function setHighlight(Request $request) {
		$user_id = $request->get('user_id', 0);
		$id = $request->get('id', 0);
		$comment_highlight = $request->get('comment_highlight', '');
		$response_highlight = $request->get('response_highlight', '');
		$mode = $request->get('mode', 0);

		$complaint = ComplaintRequest::find($id);

		$type = 101;

		if( !empty($complaint) )
		{
			if( $mode == 0 )
			{
				$log_message = '';
				if( $complaint->comment_highlight != $comment_highlight )
				{
					if( empty($complaint->comment_highlight) && !empty($comment_highlight) )
					{
						$log_message = 'Add Highlight - Complaint';
						$type = 101;
					}
					if( !empty($complaint->comment_highlight) && !empty($comment_highlight) )
					{
						$log_message = 'Edit Highlight - Complaint';
						$type = 102;
					}
					if( !empty($complaint->comment_highlight) && empty($comment_highlight) )
					{
						$log_message = 'Delete Highlight - Complaint';
						$type = 103;
					}
					$complaint->comment_highlight = $comment_highlight;	
				}
				
			}

			if( $mode == 1 )
			{
				if( $complaint->response_highlight != $response_highlight )
				{
					if( empty($complaint->response_highlight) && !empty($response_highlight) )
					{
						$log_message = 'Add Highlight - Initial Response';
						$type = 104;
					}
					if( !empty($complaint->response_highlight) && !empty($response_highlight) )
					{
						$log_message = 'Edit Highlight - Initial Response';
						$type = 105;
					}
					if( !empty($complaint->response_highlight) && empty($response_highlight) )
					{
						$log_message = 'Delete Highlight - Initial Response';
						$type = 106;
					}
					$complaint->response_highlight = $response_highlight;
				}				
			}

			if( !empty($log_message) )
			{
				$exists = ComplaintLog::where('complaint_id', $complaint->id)->where('type', $type)->exists();
				if( $exists == false )
				{
					$complaint_log = new ComplaintLog();

					$complaint_log->complaint_id = $id;
					$complaint_log->sub_id = 0;
					$complaint_log->comment = $log_message;
					$complaint_log->type = $type;
					$complaint_log->user_id = $user_id;
					
					$complaint_log->save();
				}
				
			}

			$complaint->save();
		}

		return Response::json($complaint);
	}

	public function createModCheckList(Request $request)
    {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$input = $request->except(['tasks']);
		$tasks =$request->get('tasks','');
       
		if( $input['id'] <= 0 ) {
            $checklist = new ModCheckList();
            $checklist->property_id = $request->get('property_id','0');
            $checklist->name = $request->get('name','');
			$checklist->created_by = $request->get('created_by','0');
            $checklist->created_at = $cur_time;
			$checklist->save();
			$query= DB::table('services_mod_checklist');
			$max_query=clone $query;
			$id=$max_query->max('id');
			foreach ($tasks as $value) {
				$category=$value['category'];
				$task=$value['task'];
				
	
				DB::table('services_mod_tasklist')->insert(['checklist_id' => $id ,'category' => $category,'task' => $task]);
			}
		}else{
			$id = $input['id'];
            $checklist =  ModCheckList::find($id);
            $checklist->property_id = $request->get('property_id','0');
            $checklist->name = $request->get('name','');
			$checklist->created_by = $request->get('created_by','0');
			$checklist->save();
			foreach ($tasks as $value) {
				$category=$value['category'];
				$task=$value['task'];
				
	
				DB::table('services_mod_tasklist')->insert(['checklist_id' => $id ,'category' => $category,'task' => $task]);
			}

		}   

           

        $ret = array();

        return Response::json($ret);
	}
	
	public function getModCheckList(Request $request) {
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

        $query = DB::table('services_mod_checklist as mc')
			->leftjoin('common_users as cu', 'mc.created_by', '=', 'cu.id')
			->leftjoin('common_job_role as jb', 'cu.job_role_id', '=', 'jb.id')
			->where('mc.property_id', $property_id);
			
		if($filter_value != '')
			{
				$query->where(function ($query) use ($filter_value) {	
						$value = '%' . $filter_value . '%';
						$query->where('mc.name', 'like', $value)
							->orWhere('jb.job_role', 'like', $value)
							->orWhere('cu.first_name', 'like', $value)
							->orWhere('cu.last_name', 'like', $value);				
					});
			}

        $data_query = clone $query;

        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->select(DB::raw('mc.*, jb.job_role,CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
            ->skip($skip)->take($pageSize)
            ->get();

        

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;
       

        return Response::json($ret);
	}
	
	public function deleteModChecklist(Request $request) {
        $id = $request->get('id', '0');
        $checklist =  ModCheckList::find($id);
		$checklist->delete();
		
		DB::table('services_mod_tasklist')->where('checklist_id',$id)->delete();
       
        return Response::json('200');
	}
	
	public function getModCategory(Request $request)
	{
		$value = '%' . $request->get('value', '') . '%';
		$property_id = $request->get('property_id', 4);

		$ret = DB::table('services_mod_category as smc')
			->leftJoin('common_property as cp','smc.property_id','=','cp.id')
			->where('cp.id', $property_id)
			->whereRaw("smc.category like '$value'")
			->select(DB::raw('smc.*'))
			->get();


		return Response::json($ret);
	}

	public function getTaskList(Request $request)
	{
		$start = microtime(true);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$id = $request->get('id',0);

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');		
		
		

		if ($pageSize < 0)
			$pageSize = 20;
		$ret = array();
		
		$query = DB::table('services_mod_tasklist as smt');
				
		
		$data_query = clone $query;
		
		$data_list = $data_query
		    //->orderBy('quantity', 'desc')
			->select(DB::raw('smt.*'))
			->where('smt.checklist_id',$id)
			->skip($skip)->take($pageSize)
			->get();
		
		$count_query = clone $query;
		$totalcount = count($data_list);

		
		$ret['code'] = 200;
		$ret['message'] = '';
 		
		$ret['totalcount'] = $totalcount;

		$ret['datalist'] = $data_list;
		$end = microtime(true);	
	
		return Response::json($ret);
	}

	public function updateTaskList(Request $request) {
		$checklist_id = $request->get('id', '');
		$category = $request->get('category', '');
		$task = $request->get('task', '');
		$input = $request->except(['id','category','task']);
		//$input = $request->all();
		DB::table('services_mod_tasklist')
			->where('checklist_id', $checklist_id)
			->where('category', $category)
			->where('task', $task)
			->update($input);	// read state

		return Response::json($input);
	}

	public function deleteModTask(Request $request) {
		$id = $request->get('id', '0');
		$category = $request->get('category','');
		$task = $request->get('task','');
		
		DB::table('services_mod_tasklist')->where('checklist_id',$id)->where('category',$category)->where('task',$task)->delete();
       
        return Response::json('200');
	}

	public function updateCompleteChecklist(Request $request) {
		$id = $request->get('id', '');
		$complete = $request->get('complete','');
		$completed_by = $request->get('completed_by','');
		
		
		
			$checklist =  ModCheckList::find($id);
			$checklist->complete = $complete;
			$checklist->completed_by = $completed_by;
			$checklist->save();

		$query =DB::table('services_mod_checklist as smc')
			->leftJoin('services_mod_tasklist as smt', 'smc.id', '=', 'smt.checklist_id')
			->leftJoin('common_users as cu', 'smc.created_by', '=', 'cu.id')
			->leftJoin('common_users as cu1', 'smc.completed_by', '=', 'cu1.id')
			->select(DB::raw('smc.*,CONCAT_WS(" ", cu.first_name, cu.last_name) as creator,cu1.email, CONCAT_WS(" ", cu1.first_name, cu1.last_name) as completer'))
			->where('smc.id',$id)
			->first();	

		$property_id = $query->property_id;
		$mod_email = $query->email;
		$mod = $query->completer;
		$creator = $query->creator;
		$name = $query->name;

		$this->sendChecklistEmail($property_id,$mod_email,$mod,$creator,$name,$request);
      // echo $request;
        return Response::json('200');
	}

	public function getReportFilterValues()
	{
		$ret = array();

		$ret['property_list'] = DB::table('common_property')->get();
		$ret['dept_list'] = DB::table('common_department')->get();
		
		return Response::json($ret);
	}

	private function sendChecklistEmail($property_id,$mod_email,$mod,$creator,$name, $request)
	{
		$settings = array();
		$settings['mod_recipients'] = 'snehanyk05@gmail.com';
		$settings = PropertySetting::getPropertySettings($property_id, $settings);
	
		$report = array();
		$report['property'] = Property::find($request->get("property_id" ,'0'));
		$report['id'] = $request->get('id', '');
		$report['property_id'] = $request->get('property_id', '');
		$report['generated_by'] = $request->get('generated_by', '');
		$report['name'] = $request->get('name', '');
	
		if (!empty($mod_email))
		{
			ob_start();
			
			$filename = 'MOD Checklist_' . date('d_M_Y_H_i') . '_' . $name;
			$filename = str_replace(' ', '_', $filename);
			$folder_path = public_path() . '/uploads/reports/';
			$path = $folder_path . $filename . '.html';
			
			$pdf_path = $folder_path . $filename . '.pdf';
			$data = array();
			$data['font-size']='7px';
			$data = app('App\Http\Controllers\Frontend\ReportController')->makeModChecklistReportData($report);
			$content = view('frontend.report.mod_checklist_pdf', compact('data'))->render();
			echo $content;
			file_put_contents($path, ob_get_contents());

			ob_clean();
			$info = array();					

			
			$info['mod'] = $mod; 
			$info['creator'] = $creator;
			$info['name'] = $name;
			$request = array();
			
			$subject = 'MOD Checklist ' . $name;
			$email_content = view('emails.mod_checklist', ['info' => $info])->render();

			$request['to'] = $settings['mod_recipients'];
			
			$request['subject'] = $subject;
			$request['html'] = $subject;
			$request['filename'] = $filename . '.pdf';
			$request['content'] = $email_content;

			$smtp = Functions::getMailSetting($property_id, '');

			$request['smtp'] = $smtp;

			$options = array();
			$options['html'] = $path;
			$options['pdf'] = $pdf_path;
			$options['paperSize'] = array('format' => 'A4', 'orientation' => 'portrait');
			$request['options'] = $options;

			$message = array();
			$message['type'] = 'report_pdf';
			$message['content'] = $request;
			Redis::publish('notify', json_encode($message));
			return Response::json($request);
		}
		else
		{
			return;
		}
	}

	public function changeSubComment(Request $request) {
		$id = $request->get('id', 0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);

		$sub = ComplaintSublist::find($id);
		
 		if( !empty($sub) )
 		{
			$old_comment = $sub->comment;
 			$sub->comment = $comment;
 			$sub->save();
		 }
		 
		 $complaint_log = new ComplaintLog();

	   $complaint_log->complaint_id = $sub->parent_id;
	   $complaint_log->sub_id = $id;
	   $complaint_log->comment = 'Comment changed from (' . $old_comment . ') to (' . $comment . ')' ;
	   $complaint_log->type = 0;
	   $complaint_log->user_id = $user_id;
	   
	   $complaint_log->save();

		return Response::json($sub);
	}

	public function changeSubSolution(Request $request) {
		$id = $request->get('id', 0);
		$solution = $request->get('resolution', '');
		$user_id = $request->get('user_id', 0);

		$sub = ComplaintSublist::find($id);
	
 		if( !empty($sub) )
 		{
			$old_resolution = $sub->resolution;
 			$sub->resolution = $solution;
 			$sub->save();
		 }
		 
		 $complaint_log = new ComplaintLog();

		 $complaint_log->complaint_id = $sub->parent_id;
		 $complaint_log->sub_id = $id;
		 $complaint_log->comment = 'Resolution changed from (' . $old_resolution . ') to (' . $solution . ') ';
		 $complaint_log->type = 0;
		 $complaint_log->user_id = $user_id;
		 
		 $complaint_log->save();

		return Response::json($sub);
	}

	public function changeFeedback(Request $request) {
		$id = $request->get('id', 0);
		$comment = $request->get('comment', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
	
 		if( !empty($complaint) )
 		{
			$old_comment = $complaint->comment;
 			$complaint->comment = $comment;
 			$complaint->save();
		 }
		 
		 $complaint_log = new ComplaintLog();

		 $complaint_log->complaint_id = $id;
		 $complaint_log->sub_id = 0;
		 $complaint_log->comment = 'Feedback changed from (' . $old_comment . ') to (' . $comment . ') ';
		 $complaint_log->type = 0;
		 $complaint_log->user_id = $user_id;
		 
		 $complaint_log->save();

		return Response::json($complaint);
	}

	public function changeInitialResponse(Request $request) {
		$id = $request->get('id', 0);
		$init_response = $request->get('initial_response', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
	
 		if( !empty($complaint) )
 		{
			$old_response = $complaint->initial_response;
 			$complaint->initial_response = $init_response;
 			$complaint->save();
		 }
		 
		 $complaint_log = new ComplaintLog();

		 $complaint_log->complaint_id = $id;
		 $complaint_log->sub_id = 0;
		 $complaint_log->comment = 'Initial Response changed from (' . $old_response . ') to (' . $init_response . ') ';
		 $complaint_log->type = 0;
		 $complaint_log->user_id = $user_id;
		 
		 $complaint_log->save();

		return Response::json($complaint);
	}

	public function changeFeedbackType(Request $request) {
		$id = $request->get('id', 0);
		$feedback_type = $request->get('feedback_type_id', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
	
 		if( !empty($complaint) )
 		{
			$old_type = $complaint->feedback_type_id;
 			$complaint->feedback_type_id = $feedback_type;
 			$complaint->save();
		 }

		$old = DB::table('services_complaint_feedback_type')
 			->where('id', $old_type)
 			->first(); 


 		$new = DB::table('services_complaint_feedback_type')
 			->where('id', $feedback_type)
 			->first();
		 
		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = 'Feedback Type changed from (' . $old->name . ') to (' . $new->name . ') ';
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$complaint->feedback_type = $new->name;

		return Response::json($complaint);
	}

	public function changeFeedbackSource(Request $request) {
		$id = $request->get('id', 0);
		$feedback_source = $request->get('feedback_source_id', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
	
 		if( !empty($complaint) )
 		{
			$old_source = $complaint->feedback_source_id;
 			$complaint->feedback_source_id = $feedback_source;
 			$complaint->save();
		 }

		$old = DB::table('services_complaint_feedback_source')
 			->where('id', $old_source)
 			->first(); 


 		$new = DB::table('services_complaint_feedback_source')
 			->where('id', $feedback_source)
 			->first();
		 
		$complaint_log = new ComplaintLog();

		$complaint_log->complaint_id = $id;
		$complaint_log->sub_id = 0;
		$complaint_log->comment = 'Feedback Source changed from (' . $old->name . ') to (' . $new->name . ') ';
		$complaint_log->type = 0;
		$complaint_log->user_id = $user_id;
		
		$complaint_log->save();

		$complaint->feedback_source = $new->name;


		return Response::json($complaint);
	}

	public function changeIncidentTime(Request $request) {
		$id = $request->get('id', 0);
		$incident_time = $request->get('incident_time', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
	
 		if( !empty($complaint) )
 		{
			$old_time = $complaint->incident_time;
 			$complaint->incident_time = $incident_time;
 			$complaint->save();
		}
		 
		 $complaint_log = new ComplaintLog();

		 $complaint_log->complaint_id = $id;
		 $complaint_log->sub_id = 0;
		 $complaint_log->comment = 'Incident Time changed from (' . $old_time . ') to (' . $incident_time . ') ';
		 $complaint_log->type = 0;
		 $complaint_log->user_id = $user_id;
		 
		 $complaint_log->save();

		return Response::json($complaint);
	}

	public function changeResolution(Request $request) {
		$id = $request->get('id', 0);
		$resolution = $request->get('solution', '');
		$user_id = $request->get('user_id', 0);

		$complaint = ComplaintRequest::find($id);
	
 		if( !empty($complaint) )
 		{
			$old_resolution = $complaint->solution;
 			$complaint->solution = $resolution;
 			$complaint->save();
		 }

	
		 
		 $complaint_log = new ComplaintLog();

		 $complaint_log->complaint_id = $id;
		 $complaint_log->sub_id = 0;
		 $complaint_log->comment = 'Resolution changed from (' . $old_resolution . ') to (' . $resolution . ') ';
		 $complaint_log->type = 0;
		 $complaint_log->user_id = $user_id;
		 
		 $complaint_log->save();

		return Response::json($complaint);
	}

	public function getPropertyColorCode(Request $request)
	{
		$property_id = $request->get('property_id', 0);

		$color_code = Property::where('id', $property_id)
			->select(DB::raw('color_code'))
			->first();	

		$ret = array();

		$ret['code'] = 200;	
		$ret['content'] = $color_code;	

		return Response::json($ret);
	}

	public function getFeedbackTypeListforGuestApp(Request $request)
	{

		date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$uri_arr = explode("/", $request->url());
        $siteurl = $uri_arr[2];

		$ret = array();

		$property = Property::where('url', $siteurl)->first();
		if (empty($property))
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid';
			return Response::json($ret);
		}

		$list = DB::table('services_complaint_feedback_type')
				->where('property_id',$property->id)
				->select(DB::raw('id,name'))
				->get();


		if (!empty($list)){

				$ret['code'] = 200;
				$ret['typelist'] = $list;
		}
		else{
				$ret['code'] = 201;
				$ret['message'] = 'Invalid';
		}

		return Response::json($ret);
	}

	public function createFeedbackGuestApp(Request $request) {

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$uri_arr = explode("/", $request->url());
        $siteurl = $uri_arr[2];

		$property = Property::where('url', $siteurl)->first();
		if (empty($property))
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid';
			return Response::json($ret);
		}
		$room = $request->get('room_id', 0);

		$roomid = Room::where('room', $room)->first();

		$room_id = $roomid->id;

		$guest = DB::table('common_guest as cg')
				->leftJoin('common_room as cr', 'cg.room_id', '=', 'cr.id')
					->where('cr.room', $room)
					->where('cg.departure', '>=', $cur_date)
					->where('cg.checkout_flag', 'checkin')
					->where('cg.property_id',$property->id)
					->first();

		// $guest = Guest::where('room_id', $room_id)->where('departure', '>=', $cur_date)
		// 		    	->where('checkout_flag', 'checkin')
		// 		    	->first();

		if (empty($guest))
		{
			$ret['code'] = 201;
			$ret['message'] = 'No Guest Checkin';
			return Response::json($ret);
		}

		$loc_info = Location::getLocationFromRoom($room_id);

		
		$property_id = $property->id;
		$guest_type = 'In-House';
		$comment = $request->get('feedback', '');
		$feedback_type_id = $request->get('feedback_type_id', 0);
		$created_at = $cur_time;
		$incident_time = $cur_time;
		$requestor_id = 0;

		$complaint = new ComplaintRequest();

		$complaint->property_id = $property_id;
		$complaint->feedback_type_id = $feedback_type_id;
		$complaint->loc_id = $loc_info->id;
		$complaint->guest_type = $guest_type;
		$complaint->room_id = $room_id;
		$complaint->comment = $comment;
		$complaint->incident_time = $incident_time;
		$complaint->created_at = $created_at;
		$complaint->requestor_id = 0;

	
		if (!empty($guest)){
				
			$profile = GuestProfile::where('guest_id', $guest->guest_id)->first();

			if( empty($profile) )
				$profile = new GuestProfile();

			$profile->client_id = $property->client_id;
			$profile->property_id = $property->id;
			$profile->guest_id =  $guest->guest_id;
			$profile->guest_name = $guest->guest_name;
			$profile->fname = $guest->first_name;
			$profile->mobile = $guest->mobile;
			$profile->email = $guest->email;
			$profile->profile_id = $guest->profile_id;
			$profile->nationality = NULL;
			$profile->created_at = $cur_time;
			$profile->save();

			$complaint->guest_id = $profile->id;
		}

		$complaint->save();

		// add complaint state
		ComplaintMainState::initState($complaint->id);
		ComplaintDivisionMainState::initState($complaint->id);

		$request->merge(['id' => $complaint->id]);
	
		
		ComplaintUpdated::modifyByUser($complaint->id, Employee::getUserID($requestor_id));

	
		$id = $complaint->id;		

		$this->sendRefreshEvent($property_id, 'main_complaint_create', $complaint, Employee::getUserID($requestor_id));

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $complaint->id;

		$ret['message'] = $this->sendNotifyForComplaint($id);
		$ret['content'] = $complaint;
		
		return Response::json($ret);
	}
}
