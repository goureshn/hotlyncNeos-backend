<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Call\GuestExtension;
use App\Models\Common\CommonUser;
use App\Models\Common\CommonUserNotification;
use App\Models\Common\Guest;
use App\Models\Common\GuestProfile;
use App\Models\Common\Property;
use App\Models\Common\PropertySetting;
use App\Models\Common\Room;
use App\Models\Common\SystemNotification;
use App\Models\Service\Wakeup;
use App\Models\Service\WakeupLog;
use App\Models\Service\ComplaintRequest;
use App\Models\Service\ENGTaskLog;
use App\Models\Service\ComplaintReminder;
use App\Models\Service\ComplaintSublist;
use App\Models\Service\ComplaintBriefing;
use App\Models\Service\ComplaintFlag;
use App\Models\Service\ComplaintNote;
use App\Models\Service\ComplaintLog;
use App\Models\Service\ComplaintUpdated;
use App\Modules\Functions;
use App\Models\Service\ComplaintState;
use App\Models\Service\CompensationRequest;
use App\Models\Service\CompensationTemplate;
use App\Models\Service\CompensationItem;
use App\Models\Service\CompensationState;
use App\Models\Service\CompensationApproveRoute;
use App\Models\Service\Tasklog;
use App\Models\Service\ComplaintCategory;
use App\Models\Service\ComplaintSubcategory;
use App\Models\Service\ShiftGroupMember;
use App\Models\Eng\Supplier;

use DateInterval;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Redis;
use Response;
use Curl;
use Redirect;
//use Session;

define("C_PENDING", 'Pending');
define("C_RESOLVED", 'Resolved');
define("C_REJECTED", 'Rejected');
define("C_INPROG", 'In-Progress');
define("C_REOPEN", 'Re-Opened');
define("C_CLOSED", 'Closed');
define("C_AWAIT", 'Awaiting Approval');
define("C_ACK", 'Acknowledge');
define("C_UNRESOLVED", 'Unresolved');



define("SC_OPEN", 1);
define("SC_ESCALATED", 3);
define("SC_COMPLETE", 2);
define("SC_REASSIGN", 4);
define("SC_CANCELED", 5);

define("CP_COMPLETE_APPROVE", 0);
define("CP_ON_ROUTE", 1);
define("CP_REJECTED", 2);
define("CP_RETURNED", 3);
define("CP_PENDING", 4);

class ENGController extends Controller
{
	public function sendMailApprove(Request $request) {
		 $response = new Response();
       $property_id = $request->get('property_id', 4);
       $status_id = $request->get('status_id', 0);
       $id = $request->get('id', 0);
       $eng =  ENGTaskLog::find($id);
       if($status_id==6 && ($eng->status=='Awaiting Approval'))
       {
	       $eng->status="Rejected";
	    // Session::flash('message1', 'My message');
	    echo "<div class='notification-e'>
							 <img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
							 <div class='notification-header-e'>Engineering Request EN000$id has been rejected</div>
							 <div>";
	     	       
       }
       else if($status_id==1 && ($eng->status=='Awaiting Approval'))
       {
	       $eng->status="Pending";
	       echo "<div class='notification-e'>
							 <img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
							 <div class='notification-header-e'>Engineering Request EN000$id has been approved.</div>
							 <div>";
	       
       }
       else if(($status_id==6 && ($eng->status=='Rejected')) || ($status_id==1 && ($eng->status=='Rejected')))
       {
	     echo "<div class='notification-e'>
							 <img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
							 <div class='notification-header-e'>Engineering Request EN000$id  has already been rejected. </div>
							 <div>"; 
       }
       else
       {
	       echo "<div class='notification-e'>
							 <img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
							 <div class='notification-header-e'>Engineering Request EN000$id   has already been approved. </div>
							 <div>";
       }
       $eng->save();
    }

	 public function sendMail(Request $request) {
		 $response = new Response();
       $property_id = $request->get('property_id', 4);
       $status_id = $request->get('status_id', 0);
       $id = $request->get('id', 0);
       $eng =  ENGTaskLog::find($id);
       if($status_id==5 && ($eng->status=='Resolved'))
       {
	       $eng->status="Closed";
	    // Session::flash('message1', 'My message');
	    echo "<div class='notification-e'>
							 <img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
							 <div class='notification-header-e'>Engineering Request EN000$id has been resolved</div>
							 <div>";
	     	       
       }
       else if($status_id==4 && ($eng->status=='Resolved'))
       {
	       $eng->status="Re-Opened";
	       echo "<div class='notification-e'>
							 <img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
							 <div class='notification-header-e'>Engineering Request EN000$id has been reopened</div>
							 <div>";
	       
       }
       else if(($status_id==5 && ($eng->status=='Closed')) || ($status_id==4 && ($eng->status=='Closed')))
       {
	     echo "<div class='notification-e'>
							 <img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
							 <div class='notification-header-e'>Engineering Request EN000$id  has already been closed </div>
							 <div>"; 
       }
       else
       {
	       echo "<div class='notification-e'>
							 <img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
							 <div class='notification-header-e'>Engineering Request EN000$id   has already been re-opened </div>
							 <div>";
       }
       $eng->save();
    }

	 public function getImage(Request $request) {
        $image_url = $request->get("image_url",'');
        if($image_url !='') {
            $path = $_SERVER["DOCUMENT_ROOT"] . $image_url;
            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            return Response::json($base64);
        }else {
            return Response::json('');
        }
    }
	 public function getEngInformList(Request $request){
        $eng_id = $request->get('eng_id', 0);

        $ret = array();
        $filelist = DB::table('services_eng_tasklog')
            ->where('id', $eng_id)
            ->select(DB::raw("*"))
            ->get();
        $ret['filelist'] = $filelist;
        return Response::json($ret);
    }
	public function getEngInformListfromMobile(Request $request){
		$eng_id = $request->get('eng_id', 0);
		$prop_id = $request->get('property_id', '');

		$ret = array();
		$filelist = DB::table('services_eng_tasklog')
				->where('id', $eng_id)
				->select(DB::raw("*"))
				->get();
		$ret['filelist'] = $filelist;
		$sevlist = DB::table('services_eng_severity')
				->select(DB::raw('*'))
				->get();
		$ret['sevlist'] = $sevlist;
		$catlist = DB::table('services_eng_category')
				->select(DB::raw('*'))
				->get();
		$ret['catlist'] = $catlist;
		$subcatlist = DB::table('services_eng_subcategory as sis')
				->select(DB::raw('*'))
				->get();
		$ret['subcatlist'] = $subcatlist;
		$job_roles = PropertySetting::getJobRoles($prop_id);

		// $userlist = CommonUser::getUserList($eng->prop_id, $job_roles['eng_dept_id']);
		// $grouplist = DB::table('services_eng_category')
		//     ->where('category', 'like', '%' . $group_name . '%')
		//     ->select(DB::raw('*'))
		//     ->get();
		$assignlist = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->join('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
				->where('cd.property_id',$prop_id)
				->where('cu.dept_id', $job_roles['eng_dept_id'])
				->select(DB::raw('cu.*,cd.department,jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
				->get();
		$ret['assignlist'] = $assignlist;
		$ret['code'] = 200;
		return Response::json($ret);
	}
	
	 public function updateEng(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date('Y-m-d H:i:s');
        $id = $request->get('id',0);
        //delete image
        $eng =  ENGTaskLog::find($id);
        $image_url = $eng->upload;
        $output_file = $_SERVER["DOCUMENT_ROOT"] .$image_url;
        if(file_exists($output_file) && $image_url != '') unlink($output_file);
        //insert image and update image url
        $base64_string = $request->get('image_src','') ;
        $image_url = $request->get('image_url','');
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/eng/';
        if(!file_exists($output_file)) {
            mkdir($output_file, 0777);
        }
        //if($image_url == '') $image_url = 'default.png';
        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/uploads/eng/' . $image_url;
        if($base64_string !='') {
            $ifp = fopen($output_file, "wb");
            $data = explode(',', $base64_string);
            fwrite($ifp, base64_decode($data[1]));
            fclose($ifp);
        }

        $eng =  ENGTaskLog::find($id);
       
        $eng->updated_by = $request->get('updated_by', '0');

/*
        $eng->name = $request->get('name', '');
        $eng->description = $request->get('description', '');
        $eng->critical_flag = $request->get('critical_flag', '0');
        $eng->equip_id = $request->get('equip_id','');
        $eng->external_maintenance = $request->get('external_maintenance', '');
        $eng->external_maintenance_id = $request->get('external_maintenance_id','0');
        $eng->dept_id = $request->get('dept_id', '0');
        $eng->life = $request->get('life', '0');
        $eng->life_unit = $request->get('life_unit', 'days');
*/
        $eng->updated_at= $request->get('updated_at', '0000-00-00');
        $eng->request = $request->get('request', '');
        $eng->subject = $request->get('subject', '');
        $eng->subcategory = $request->get('subcategory', '');
        $eng->category = $request->get('category', '');
        $eng->severity = $request->get('severity', '');
        $eng->status = $request->get('status', '0');
        //$equipment->model = $request->get('model', '');
        //$equipment->barcode = $request->get('barcode', '0');
        //$equipment->warranty_start = $request->get('warranty_start', '0000-00-00');
        //$equipment->warranty_end = $request->get('warranty_end', '0000-00-00');
        //$equipment->supplier_id = $request->get('supplier_id', '');
        if($image_url!='')
        $eng->upload = '/uploads/eng/' . $image_url;
        
		$eng->resolution = $request->get('resolution', '');
		if(($eng->status=="Resolved"))
		{
				$eng->resolved_at=$cur_time;
			
					$val1=new DateTime($eng->resolved_at);
					$val2=new DateTime($eng->created_at);
				$val3=$val1->diff($val2);
				$eng->resolved_duration=$val3->format('%d days, %H hours, %I minutes, %S seconds');
				
		}
         $eng->reject = $request->get('reject', '');
         $eng->sendflag = $request->get('sendflag', '0');
       // $eng->prop_id = $request->get('property_id','0');
        //$equipment->maintenance_date = $cur_time;

        $eng->save();
        $eng_id = $id;
        //$equipment_group = $request->get('equipment_group',[]);
        //DB::table('eng_equip_group_member')->where('equip_id', $equip_id)->delete();
        //DB::table('eng_equip_part_group_member')->where('equip_id', $equip_id)->delete();

/*
        for($i = 0 ; $i < count($equipment_group) ;$i++) {
            $group_id = $equipment_group[$i]['equip_group_id'];
            DB::table('eng_equip_group_member')->insert(['equip_id' => $equip_id, 'group_id' => $group_id]);
        }
*/
/*
        $part_group = $request->get('part_group',[]);
        for($i = 0 ; $i < count($part_group) ;$i++) {
            $part_group_id = $part_group[$i]['part_group_id'];
            $type = $part_group[$i]['type'];
            if (DB::table('eng_equip_part_group_member')
                ->where('equip_id', $equip_id)
                ->where('part_group_id', $part_group_id)
                ->where('type', $type)
                ->exists())
                continue;
            else
                DB::table('eng_equip_part_group_member')->insert(['equip_id' => $equip_id,
                    'part_group_id' => $part_group_id,
                    'type' => $type]);
        }
*/
        $ret =array();
        $ret['id'] = $eng->id;
        return Response::json($ret);
    }

	
	  public function getStatusList(Request $request)
    {
        $list = DB::table('services_eng_status')
            ->select(DB::raw('*'))
            ->get();
        return Response::json($list);
    }
    
      public function getSeverityList(Request $request)
    {
        $list = DB::table('services_eng_severity')
            ->select(DB::raw('*'))
            ->get();
        return Response::json($list);
    }
	
	  public function getCategoryList(Request $request)
    {
        $group_name = $request->get('category', '');

        $grouplist = DB::table('services_eng_category')
            ->where('category', 'like', '%' . $group_name . '%')
            ->select(DB::raw('*'))
            ->get();
        return Response::json($grouplist);
    }
    
      public function getSubCategoryList(Request $request)
    {
        $group_name = $request->get('sub_cat', '');
        $category=$request->get('category', '');

        $grouplist = DB::table('services_eng_subcategory as sis')
            ->leftJoin('services_eng_category as sic', 'sic.id', '=', 'sis.cat_id')
            ->where('sic.category', 'like', '%' . $category . '%')
            ->where('sub_cat', 'like', '%' . $group_name . '%')
            ->select(DB::raw('*'))
            ->get();
        return Response::json($grouplist);
    }
    public function getAssigneeList(Request $request)
    {
        $group_name ='%' . $request->get('asgn', ''). '%';
      
        $prop_id = $request->get('property_id', '');
         $job_roles = PropertySetting::getJobRoles($prop_id);
      
		// $userlist = CommonUser::getUserList($eng->prop_id, $job_roles['eng_dept_id']);
        // $grouplist = DB::table('services_eng_category')
        //     ->where('category', 'like', '%' . $group_name . '%')
        //     ->select(DB::raw('*'))
        //     ->get();
        	$users = DB::table('common_users as cu')
				->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->join('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
		        ->where('cd.property_id',$prop_id)
                ->where('cu.dept_id', $job_roles['eng_dept_id'])
                ->whereRaw("(CONCAT(cu.first_name, ' ', cu.last_name) like '" . $group_name . "' or jr.job_role like '". $group_name ."')")
			->select(DB::raw('cu.*,cd.department,jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			->get();
        return Response::json($users);
    }
	
	public function getEngList(Request $request) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");


        $property_id = $request->get('property_id', 4);
      
      

        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $searchoption = $request->get('searchoption','');
        $searchtext = $request->get('searchtext', '');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');
        $filter = $request->get('filter','');


        if ($pageSize < 0)
            $pageSize = 20;

        $ret = array();
        $query = DB::table('services_eng_tasklog as sit')
					->join('common_users as cu', 'sit.requestor_id', '=', 'cu.id')
					->join('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
					->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
					->join('services_eng_severity as sis', 'sis.id', '=', 'sit.severity')
					->leftJoin('common_users as cu2', 'sit.updated_by', '=', 'cu2.id')
					->leftJoin('eng_supplier as es', 'sit.supplier_id', '=', 'es.id');
        
        if( $filter != 'Total' && $filter != '')
		{
			if( $filter == 1 || $filter == C_PENDING)	// On Route
				$query->where('sit.status', C_PENDING);
			if( $filter == 2 || $filter == C_INPROG )
					$query->where('sit.status', C_INPROG);
			if( $filter == 3 || $filter == C_RESOLVED )
					$query->where('sit.status', C_RESOLVED);
			if( $filter == 4 || $filter == C_REOPEN )
					$query->where('sit.status', C_REOPEN);
			if( $filter == 5 || $filter == C_CLOSED )
					$query->where('sit.status', C_CLOSED);
			if( $filter == 6 || $filter == C_REJECTED )
					$query->where('sit.status', C_REJECTED);
			if( $filter == 7 || $filter == C_AWAIT )
					$query->where('sit.status', C_AWAIT);
			
			if( $filter == 8  || $filter == C_ACK)
					$query->where('sit.status', C_ACK);
			}
			if($start_date != '')
				$query->whereRaw(sprintf("DATE(sit.created_at) >= '%s' and DATE(sit.created_at) <= '%s'", $start_date, $end_date));
		

        $data_query = clone $query;
        $data_list = $data_query
            ->orderBy($orderby, $sort)
            ->select(DB::raw('sit.*, sis.severity as sev, cu2.first_name, cu2.last_name, CONCAT_WS(" ", cu2.first_name, cu2.last_name) as up_wholename,cu.first_name, cu.last_name, cu.employee_id, cu.email, jr.job_role, cd.department, es.supplier'))
            ->skip($skip)->take($pageSize)
            ->get();


        $count_query = clone $query;
        $totalcount = $count_query->count();
		$ret['code'] = 200;
        $ret['datalist'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    		

	
	public function getStaffList(Request $request)
	{
		$value = '%' . $request->get('value', '') . '%';
		$client_id = $request->get('client_id', 4);

		$ret = DB::table('common_users as cu')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
			->leftJoin('common_department as de','cu.dept_id','=','de.id')
			->leftJoin('common_property as cp','de.property_id','=','cp.id')
			->whereRaw("(CONCAT(cu.first_name, ' ', cu.last_name) like '" . $value . "' or cu.email like '$value')")
			->where('cp.client_id', $client_id)
			->where('cu.deleted', 0)
			->select(DB::raw('cu.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename , de.department, cp.name as property_name,cp.id as property_id'))
			->get();


		return Response::json($ret);
	}

	public function getRepairStaffList(Request $request)
	{
		$value = '%' . $request->get('value', '') . '%';
		$client_id = $request->get('client_id', 4);

		$query = DB::table('common_users as cu')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
			->leftJoin('common_department as de','cu.dept_id','=','de.id')
			->leftJoin('common_property as cp','de.property_id','=','cp.id')
			->whereRaw("(CONCAT(cu.first_name, ' ', cu.last_name) like '" . $value . "' or cu.email like '$value')")
			->where('cp.client_id', $client_id)
			->where('cu.deleted', 0);
		//	->select(DB::raw('cu.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename , de.department, cp.name as property_name,cp.id as property_id'))
		//	->get();

		$leasor_query = DB::table('eng_contracts as ec')
			->whereRaw("(ec.leasor like '" . $value . "' or ec.leasor_email like '$value')")
			->select(DB::raw('ec.id, ec.leasor as wholename, ec.leasor_email as job_role, ec.leasor_contact as department'))
			->get();
		foreach( $leasor_query as $leasor){
			$leasor->type  =  'Leasor';
		}

		$tenant_query = DB::table('eng_tenant as et')
			->whereRaw("(et.name like '" . $value . "' or et.email like '$value')")
			->select(DB::raw('et.id, et.name as wholename,et.email as job_role, et.contact as department'))
			->get();
		foreach( $tenant_query as $tenant){
			$tenant->type  =  'Tenant';
		}

		$user_query=$query->select(DB::raw('cu.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename , de.department, cp.name as property_name,cp.id as property_id'))
				->get();

		foreach( $user_query as $user){
					$user->type  =  'User';
			}

		$model = array_merge($user_query, $leasor_query, $tenant_query); 
		$model = array_unique($model, SORT_REGULAR);
		$model = array_merge($model, array());
		return Response::json($model);
	}

	public function getTenantList(Request $request) {
		$property_id = $request->get('property_id', 0);
		$value = '%' . $request->get('value', '') . '%';
        $query = DB::table('eng_tenant as et')
	//	->leftJoin('common_users as cu', 'et.added_by', '=', 'cu.id')
		->whereRaw("et.name like '" . $value . "' or et.email like '$value'");

        if( $property_id > 0 )
            $query->where('et.property_id', $property_id);

        $list = $query->select(DB::raw('et.id, et.property_id, et.name as tenant_name, et.email, et.contact, "Tenant" as type'))
            ->get();
 

        return Response::json($list);
    }

	
	public function getID(Request $request) {
		$max_id = DB::table('services_eng_tasklog')
			->select(DB::raw('max(id) as max_id'))
			->first();

		return Response::json($max_id);
	}

	public function create(Request $request) {
		$client_id = $request->get('client_id', 4);
		$property_id = $request->get('property_id', 4);
		$loc= $request->get('location', '');
		//$guest_type = $request->get('guest_type', 'Walk-in');
		//$room_id = $request->get('room_id', 0);
		//$guest_id = $request->get('guest_id', 0);
        $requestor_id = $request->get('requestor_id', 0);
        $id = $request->get('id', 0);
		$comment = $request->get('comment', '');
		//$new_guest = $request->get('new_guest', 0);
		//$mobile = $request->get('mobile', '');
		//$email = $request->get('email', '');
		//$guest_name = $request->get('guest_name', '');
		$status = $request->get('status', 'Pending');
		$severity = $request->get('severity', 1);
		$initial_response = $request->get('initial_response', '');
		$housecomplaint_id = $request->get('housecomplaint_id', 0);
		$category = $request->get('category', '');
    $subcategory = $request->get('subcategory', '');
		//$incident_time = $request->get('incident_time', 0);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$created_at = $cur_time;

		$eng = new ENGTaskLog();

        $eng->prop_id = $property_id;
        $eng->id = $id;
		//$complaint->loc_id = $loc_id;
		//$complaint->guest_type = $guest_type;
		//$complaint->room_id = $room_id;
		$eng->requestor_id = $requestor_id;
		
		$eng->severity = $severity;
		$eng->subject = $initial_response;
		$eng->request = $comment;
		$eng->category = $category;
		$eng->location = $loc;
		$mngr_flag = DB::table('services_eng_subcategory as sis')
		   ->leftJoin('services_eng_category as sic', 'sic.id', '=', 'sis.cat_id')
		   ->where('sic.category', 'like', '%' . $category . '%')
           ->where('sis.sub_cat', 'like', '%' . $subcategory . '%')
            ->select(DB::raw('sis.mngr_flag'))
            ->first();
		$eng->subcategory = $subcategory;
		if(!empty($subcategory))
		{
			$eng->subcat_mngr_flag = ($mngr_flag->mngr_flag);
		}
		else
		{
			$eng->subcat_mngr_flag = 0;
		}


		if($eng->subcat_mngr_flag == '1')
		{
			$eng->status = 'Awaiting Approval';
		}
		else
			$eng->status = $status;
		//$eng->incident_time = $cur_date . ' ' . $incident_time;
		$eng->created_at = $created_at;
		
		$eng->save();

		//wait-----ComplaintUpdated::modifyByUser($complaint->id, $requestor_id);

		$id = $eng->id;		

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $eng->id;

		$ret['message'] = $this->sendNotifyForComplaint($id, 1);
		$ret['content'] = $eng;
		
		return Response::json($ret);
	}
	public function updateStatus(Request $request)
	{
		$id = $request->get('id', 0);
		$status = $request->get('status', '');
		$sub = ENGTaskLog::find($id);
		$sub->status= $status;
		$sub->updated_by = $request->get('updated_by', '0');
		$sub->save();

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$created_at = $cur_time;

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $sub->id;
 		sleep(1);
		$ret['message'] = $this->sendNotifyForComplaint($id, 2);
		$ret['content'] = $sub;
		
		return Response::json($ret);
		
	}
	public function updateStatusfromMobile(Request $request)
	{

		$id = $request->get('id', 0);
		$status = $request->get('status', '');
		$comment = $request->get('comment', '');
		$sub = ENGTaskLog::find($id);
		$sub->status= $status;
		if($status == 'Rejected'){
			$sub->reject = $comment;
		}
		if($status == 'Resolved'){
			$sub->resolution = $comment;
		}
		$sub->updated_by = $request->get('updated_by', '0');
		$sub->save();

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$created_at = $cur_time;

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $sub->id;
		sleep(1);
		$ret['message'] = $this->sendNotifyForComplaint($id,2);
		$ret['content'] = $sub;

		return Response::json($ret);

	}
	public function updateCategory(Request $request)
	{
		
		$id = $request->get('id', 0);
    $category = $request->get('category', '');
    $sub = ENGTaskLog::find($id);
	$sub->category= $category;
	 $sub->updated_by = $request->get('updated_by', '0');
    $sub->save();

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$created_at = $cur_time;

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $sub->id;
 		//sleep(1);
		//$ret['message'] = $this->sendNotifyForComplaint($id,2);
		$ret['content'] = $sub;
		
		return Response::json($ret);
		
	}
	public function updateSubcategory(Request $request)
	{
		
		$id = $request->get('id', 0);
     $subcategory = $request->get('subcategory', '');
    $sub = ENGTaskLog::find($id);
	$sub->subcategory= $subcategory;
	 $sub->updated_by = $request->get('updated_by', '0');
    $sub->save();

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$created_at = $cur_time;

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $sub->id;
 		//sleep(1);
		//$ret['message'] = $this->sendNotifyForComplaint($id,2);
		$ret['content'] = $sub;
		
		return Response::json($ret);
		
    }
    public function updateAssignee(Request $request)
	{		
		$id = $request->get('id', 0);
		$assignee = $request->get('assignee', '');

		$asgn = ENGTaskLog::find($id);
		$asgn->assignee=  $assignee;
		$asgn->updated_by = $request->get('updated_by', '0');
		$asgn->save();

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$created_at = $cur_time;

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $asgn->id;
 		//sleep(1);
		//$ret['message'] = $this->sendNotifyForComplaint($id,2);
		$ret['content'] = $asgn;
		
		return Response::json($ret);
		
	}

	public function updateSupplier(Request $request)
	{		
		$id = $request->get('id', 0);
		$outside_flag = $request->get('outside_flag', '');
		$supplier_id = $request->get('supplier_id', '');

		if( $outside_flag == false )
			$supplier_id = 0;

		$asgn = ENGTaskLog::find($id);
		$asgn->outside_flag=  $outside_flag;
		$asgn->supplier_id=  $supplier_id;
		$asgn->save();

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $asgn->id;
 		$ret['content'] = $asgn;
		
		return Response::json($ret);
		
	}

	public function saveComment(Request $request)
	{
		
	$id = $request->get('id', 0);
    $ipcomment = $request->get('ipcomment', '');
    $task = ENGTaskLog::find($id);
	$task->ipcomment=  $ipcomment;
	$task->updated_by = $request->get('updated_by', '0');
	$user_id = $request->get('updated_by',0);
	$action='';
    $task->save();
	
	$user_query = DB::table('common_users as cu')
					->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
					->where('cu.id', $user_id)
					->first();
					
	$user_name = $user_query->wholename;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$created_at = $cur_time;
		
		 
		 if ($ipcomment != '')
		{
			DB::table('services_eng_req_history')->insert(['issue_id' => $id,
			    'action' => $action,
				'user' => $user_name,
				'comment' => $ipcomment,
				'created_at' => $cur_time]);		
		}
		 

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $task->id;
 		//sleep(1);
		$ret['message'] = $this->sendNotifyForComplaint($id,2);
		$ret['content'] = $task;
		
		return Response::json($ret);
		
	}
	
	public function getEngRequestHistory(Request $request) {
		
		
		
		$sort = $request->get('sort', 'desc');
		$property_id = $request->get('property_id', '0');		
		$id = $request->get('id',0);
		
		
		$ret = array();	
		
		$query = DB::table('services_eng_req_history as ml');
				 		 
		$data_query = clone $query;
		
		$data_list = $data_query
					->orderBy('ml.created_at', $sort)
					->select(DB::raw('ml.user,ml.created_at,ml.action,ml.comment'))
					->where('ml.issue_id', $id)
					->get();	
		
		$ret['datalist'] = $data_list;
		return Response::json($ret);
	}
	
	public function updateSeverity(Request $request)
	{
		
		$id = $request->get('id', 0);
    $severity = $request->get('severity', 0);
    $sub = ENGTaskLog::find($id);
	$sub->severity= $severity;
	 $sub->updated_by = $request->get('updated_by', '0');
    $sub->save();

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");
		$created_at = $cur_time;

		$ret = array();

		$ret['code'] = 200;
		$ret['id'] = $sub->id;
 		//sleep(1);
		//$ret['message'] = $this->sendNotifyForComplaint($id,2);
		$ret['content'] = $sub;
		
		return Response::json($ret);
		
	}
	
	

	
	public function testNotifyComplaint() {
		$this->sendNotifyForComplaint(1, 1);
	}

	private function sendNotifyForComplaint($id, $num) {
		$eng = DB::table('services_eng_tasklog as scr')
			->join('common_users as cu', 'scr.requestor_id', '=', 'cu.id')
			->join('common_property as cp', 'scr.prop_id', '=', 'cp.id')
			->join('services_eng_severity as sis', 'sis.id', '=', 'scr.severity')
			->select(DB::raw('scr.*, sis.severity as sev, scr.subject as engsubject,scr.request as comment, scr.resolution as resolution,scr.reject as reject,scr.status as status,cu.dept_id, cu.email, cu.mobile,cu.fcm_key, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cp.name as property_name'))
			->where('scr.id', $id)
			->first();
			$eng->cc="";

		if( empty($eng) )
			return '';

		$eng->sub_type = 'post';

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
		if($num == 1)
		{
			$message_content = sprintf('There is a new engineering request EN%05d which has been raised by %s for %s in %s',
											$eng->id, $eng->wholename, $location_name, $eng->property_name);
		}
		else	
			$message_content = sprintf('The status of engineering request EN%05d is now %s.',
										$eng->id, $eng->status);
	
		// find duty manager on shift
        $job_roles = PropertySetting::getJobRoles($eng->prop_id);
      
		$userlist = CommonUser::getUserList($eng->prop_id, $job_roles['eng_dept_id']);
		$managers = DB::table('services_complaint_dept_default_assignee as scd')
			->join('common_users as cu2', 'scd.user_id', '=', 'cu2.id')
			->select(DB::raw('scd.*, cu2.email, cu2.mobile, cu2.fcm_key, CONCAT_WS(" ", cu2.first_name, cu2.last_name) as wholename'))
			->where('scd.id', $eng->dept_id)
			->first();
			
		$ip = DB::table('property_setting as ps')
			->select(DB::raw('ps.value'))
			->where('ps.settings_key', 'hotlync_host')
			->first();

		if( empty($userlist) || count($userlist) < 1 )
			return $message_content;

		$eng->content = $message_content;
		
		$info = array();
		if($num == 1)
		{
			foreach($userlist as $row)
			{
				$info['wholename'] = $row->first_name;
				$info['category'] = $eng->category;
				$info['sub_cat'] = $eng->subcategory;
				$info['severity'] = $eng->sev;
				$info['raised_by'] = $eng->wholename;
				$info['comment'] = $eng->comment;
				$info['subject'] = $eng->engsubject;
				$info['subject'] = $eng->engsubject;
				$info['location'] = $eng->location;
				$info['dept_name'] = $eng->property_name;
				$info['assignee_name'] = $eng->assignee;

				$eng->subject = sprintf('EN%05d: New Engineering Request Raised', $eng->id);
				$eng->email_content = view('emails.eng_eng_create', ['info' => $info])->render();

				$this->sendComplaintNotification($eng->prop_id, $message_content, $eng->comment, $eng, $row->email, $row->mobile, $row->fcm_key, $eng->cc);
			
			}
			if($eng->subcat_mngr_flag =='1' && !empty($managers))
			{
				$info3['wholename'] = $managers->wholename;
				$info3['category'] = $eng->category;
				$info3['sub_cat'] = $eng->subcategory;
				$info3['severity'] = $eng->sev;
				$info3['raised_by'] = $eng->wholename;
				$info3['comment'] = $eng->comment;
				$info3['subject'] = $eng->engsubject;
				$info3['dept_name'] = $eng->property_name;
				$info3['location'] = $eng->location;
				$info3['id'] = $eng->id;
				$info3['ip'] = $ip->value;
				$info3['assignee_name'] = $eng->assignee;
				$eng->subject = sprintf('EN%05d: Approval Required', $eng->id);
				$eng->email_content = view('emails.eng_eng_approve', ['info' => $info3])->render();

				$this->sendComplaintNotification($eng->prop_id, $message_content, $eng->comment, $eng, $managers->email, $managers->mobile, $managers->fcm_key, $eng->cc);			
			}
			else if($eng->subcat_mngr_flag =='1' && empty($managers))
			{
				$sub = ENGTaskLog::find($eng->id);
				$sub->status="Pending";
				$sub->subcat_mngr_flag=0;
				$sub->save();
			}
		}
		
		$info_2 = array();
		$info_2['wholename'] = $eng->wholename;
		$info_2['category'] = $eng->category;
		$info_2['sub_cat'] = $eng->subcategory;
		$info_2['severity'] = $eng->sev;
		$info_2['raised_by'] = $eng->wholename;
		$info_2['comment'] = $eng->comment;
		$info_2['subject'] = $eng->engsubject;
		$info_2['resolution'] = $eng->resolution;
		$info_2['reject'] = $eng->reject;
		$info_2['dept_name'] = $eng->property_name;
		$info_2['location'] = $eng->location;
		$info_2['id'] = $eng->id;
		$info_2['assignee_name'] = $eng->assignee;
		
		if($num==1)
		{
			$eng->subject = sprintf('EN%05d: [Confirmation] New Engineering Request Raised', $eng->id);
			$eng->email_content = view('emails.eng_eng_create_req', ['info' => $info_2])->render();
		}
		else if($num==2 && (($eng->status)!="Pending"))
		{
			$updated_by = DB::table('common_users as cu')
			            ->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
			            ->where('cu.id', $eng->updated_by)
			            ->first();
			$info_2['updated_by'] = $updated_by->wholename;
			if(!empty($eng->ipcomment))
			{
				$info_2['ipcomment'] = $eng->ipcomment;	
			}
			            
			$info_2['status'] = $eng->status;
			
			if($eng->status==("Resolved"))
			{
				$info_2['ip'] = $ip->value;
				$eng->subject = sprintf('EN%05d: Resolved', $eng->id);
				$eng->email_content = view('emails.eng_eng_resolve', ['info' => $info_2])->render();
				$userlist_email=array();
				$i=0;
				foreach($userlist as $row)
				{
					$userlist_email[$i++]=$row->email;
				}
				$eng->cc = implode(',', $userlist_email);
			}
			else if($eng->status==("Rejected"))
			{
				$eng->subject = sprintf('EN%05d: Rejected', $eng->id);
				$eng->email_content = view('emails.eng_eng_reject', ['info' => $info_2])->render();
			}
			else
			{
				$eng->subject = sprintf('EN%05d: Change in Status', $eng->id);
				$eng->email_content = view('emails.eng_eng_change', ['info' => $info_2])->render();				
			}			
		}

		$this->sendComplaintNotification($eng->prop_id, $message_content, $eng->comment, $eng, $eng->email, $eng->mobile, $eng->fcm_key, $eng->cc);

		return $message_content;
	}

	public function sendComplaintNotification($property_id, $subject, $content, $data, $email, $mobile, $pushkey, $cc) {
		$complaint_setting = PropertySetting::getComplaintSetting($property_id);

		// check notify mode(email, sms, mobile push)
		$alarm_mode = $complaint_setting['complaint_notify_mode'];

		$email_mode = false;
/*
		$sms_mode = false;
		$webapp_mode = false;
*/		
		if (strpos($alarm_mode, 'email') !== false) {
		    $email_mode = true;
		}

/*
		if (strpos($alarm_mode, 'sms') !== false) {
		    $sms_mode = true;
		}

		if (strpos($alarm_mode, 'webapp') !== false) {
		    $webapp_mode = true;
		}
*/

		if( $email_mode == true )
		{
			$smtp = Functions::getMailSetting($property_id, 'notification_');

			$message = array();
			$message['type'] = 'email';

			$message['to'] = $email;
			if(!empty($cc))
			$message['cc'] = $cc;
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

/*
		if( $sms_mode == true )
		{
			// send sms
			$message = array();
			$message['type'] = 'sms';

			$message['to'] = $mobile;
			$message['content'] = $subject;

			Redis::publish('notify', json_encode($message));	
		}

		if( $webapp_mode == true )
		{
			// send sms
			$message = array();
			$message['type'] = 'eng';			
			$message['data'] = $data;

			Redis::publish('notify', json_encode($message));	
		}
*/
	}

	public function uploadFiles(Request $request) {
 		$output_dir = "uploads/eng/";
		
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
			$filename1 = "eng_" . $id . '_' . $i . '_' . $fileName;
			
			$dest_path = $output_dir . $filename1;
			move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);						
			if( $i > 0 )
				$path .= '|';

			$path .=  $dest_path;			
		}

		$eng = ENGTaskLog::find($id);
		if( !empty($eng) )
		{
			$eng->upload = $path;
			$eng->save();			
		}
		
		// $ret['id'] = $eng->id;

		// $ret['message'] = $this->sendNotifyForComplaint($id,1);
		// $ret['content'] = $eng;
		return Response::json($_FILES[$filekey]);
 	}

 	public function uploadFilesToEng(Request $request) {
 		$output_dir = "uploads/eng/";
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
			$filename1 = "eng_" . $id . '_' . $i . '_' . $fileName;
			
			$dest_path = $output_dir . $filename1;
			move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);						
			if( $i > 0 )
				$path .= '|';

			$path .=  $dest_path;			
		}

		$sub = ENGTaskLog::find($id);
		if( !empty($sub) )
		{
			if( empty($sub->upload) ) 
				$sub->upload = $path;
			else
				$sub->upload .= '|' . $path;

			$sub->save();		

/*
			$complaint = ComplaintRequest::find($sub->parent_id);			
			ComplaintUpdated::modifyByUser($complaint->id, $user_id);
*/
		}

		return Response::json($sub);
 	}

 	public function removeFilesFromEng(Request $request) {
 		$id = $request->get('id', 0);
 		$user_id = $request->get('user_id', 0);
 		$index = $request->get('index', 0);
		
		$sub = ENGTaskLog::find($id);
		if( !empty($sub) )
		{
			$path_array = explode('|', $sub->upload);

			unset($path_array[$index]);
			$sub->upload = implode('|', $path_array);

			$sub->save();

/*
			$complaint = ComplaintRequest::find($sub->parent_id);
			ComplaintUpdated::modifyByUser($id, $user_id);
*/
		}

		return Response::json($sub);
	 }
	 
	public function createSupplier(Request $request)
	{
		$input = $request->except(['id']);

		$id = $request->get('id', 0);

		if( !($id > 0) )
			$model = Supplier::create($input);
		else
		{	
			$model = Supplier::find($id);
			if( !empty($model) )
            	$model->update($input);
		}

		return Response::json(Supplier::all());
	}

	public function deleteSupplier(Request $request)
	{
		$id = $request->get('id', 0);
		
		$model = Supplier::find($id);
		if( !empty($model) )
			$model->delete();		
		
		return Response::json(Supplier::all());			
	}
}
