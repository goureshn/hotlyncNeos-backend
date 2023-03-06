<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Models\Call\GuestExtension;
use App\Models\Common\CommonUser;
use App\Models\Common\CommonUserNotification;
use App\Models\Common\Guest;
use App\Models\Common\GuestProfile;
use App\Models\Common\Property;
use App\Models\Common\PropertySetting;
use App\Models\Common\Room;
use App\Models\Common\SystemNotification;
use App\Models\Service\LiftFormLog;

use App\Modules\Functions;


use DateInterval;
use DateTime;
use DB;
use Illuminate\Http\Request;
use Redis;
use Response;
use Curl;
use Redirect;
//use Session;

define("L_PENDING", 'Pending');
define("L_LEVEL1", 'Approved Level 1');
define("L_LEVEL2", 'Approved Level 2');
define("L_LEVEL3", 'Approved Level 3');


class FormController extends Controller
{
	public function sendMailApprove(Request $request) {
		$response = new Response();
	  $property_id = $request->get('property_id', 4);
	  $form_id = $request->get('form_id', 0);
	  $user_id = $request->get('user_id', 0);
	  $id = $request->get('id', 0);
	  $status = 'Approved Level ';
	  if ($form_id == 1){
		  $title = 'Lift Usage Form';
	  }else{
		$title = 'Permit to Work Form';
	  }

	  $query = DB::table('services_form_appl_status as stat');
			
	  $data_query = clone $query;
		
	  $data_list = $data_query
			->select(DB::raw('stat.*'))
			->where('stat.request_id',$id)
			->where('stat.form_id',$form_id)
			->first();

	$check_level = DB::table('services_form_approval_assignee as appr')
			->select(DB::raw('appr.level'))
			->where('appr.level','>',$data_list->level)
			->where('appr.user_id', $user_id)
			->where('appr.form_id' , $data_list->form_id)	
			->first();
	 //$level = $data_list->level;
	 if (!empty($check_level))
	 {
		if (($check_level->level- $data_list->level) == 1) 
		{
	
	
		$query = DB::table('services_form_appl_status as ml');
	
		$data_query = clone $query;
	
		$new_status = $status . $check_level->level;
	
		$upd_list = $data_query
						->where('ml.request_id', $id)
						->where('ml.form_id', $form_id)
						->update(['ml.status' => $new_status,'ml.level' => $check_level->level]);
			echo "<div class='notification-e'>
						<img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
						<div class='notification-header-e'>Form Request F000$id has been approved</div>
						<div>";
	
		$this->sendnotification($property_id,$data_list->form_id,$check_level->level+1,$id,$title);
	
		}
		else
		{
			echo "<div class='notification-e'>
						<img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
						<div class='notification-header-e'>Form Request F000$id has already been approved.</div>
						<div>";
		}
	  
	}
	else
		{
			echo "<div class='notification-e'>
						<img id='close_notification_f7c717ded4d80bebcb6f777f80e6840f7b48' class='cm-notification-close hand' src='/css/images/icons/icon_close.gif' width='13' height='13' border='0' alt='Close' title='Close'>
						<div class='notification-header-e'>Form Request F000$id has already been approved.</div>
						<div>";
		}
   }

   
	public function createLiftUsageRequest(Request $request)
	{
		$property_id = $request->get('property_id', 4);
		$input = $request->except(['tasks','property_id','permit_flag']);
		$tasks =$request->get('tasks','');
		$permit_flag = $request->get('permit_flag',0);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		
		$query= DB::table('services_liftusage_form');
		$insert=clone $query;
		$insert->insert($input);

		$max_query=clone $query;
		$id=$max_query->max('id');

		$upd_list = $max_query
					->where('id', $id)
					->update(['signed_on'=>$cur_time]);
		
		//echo $id;
		//}
		foreach ($tasks as $value) {
			$item=$value['items'];
			$qty=$value['quantity'];
			

			DB::table('services_liftusage_propertylist')->insert(['request_id' => $id ,'quantity' => $qty,'items' => $item]);
		}
		
		$level = 0;
		DB::table('services_form_appl_status')->insert(['form_id' => 1, 'request_id' => $id ,'status' => "Pending",'level' => $level]);
	   
		$form_query = DB::table('services_liftusage_form as form')
					->select(DB::raw('form.form_id'))
					->where('id', $id)
					->first();
		$title = "Lift Usage Form";
		$this->sendnotification($property_id,$form_query->form_id,$level+1,$id,$title);
	
		

		$ret = array();
		$ret['lift_id'] = $id;

		return Response::json($ret);
	}

	private function sendnotification($property_id,$form_query,$level,$request_id,$title)
	{
		
		
		$user_query = DB::table('services_form_approval_assignee as fa')
					->join('common_users as cu','fa.user_id','=','cu.id')
					->select(DB::raw('cu.email,cu.first_name as name,fa.level,fa.user_id'))
					->where('fa.level', $level)
					->where('fa.form_id',$form_query)
					->first();

		$requestor_query = DB::table('services_liftusage_form as form')
		            ->leftjoin('common_users as cu','form.signed_by','=','cu.id')
					->select(DB::raw('cu.first_name,form.*'))
					->where('form.id', $request_id)
					->first();

		$requestor_query1 = DB::table('services_permitwork_form as form')
		           // ->leftjoin('common_users as cu','form.signed_by','=','cu.id')
					->select(DB::raw('form.*'))
					->where('form.id', $request_id)
					->first();

		$ip = DB::table('property_setting as ps')
					->select(DB::raw('ps.value'))
					->where('ps.settings_key', 'hotlync_host')
					->first();


		if (!empty($user_query))
		{
		
		$info = array();					

		$info['host_ip'] = $ip->value;
		$info['title'] = $title;
		$info['user_name'] = $user_query->name; 
		$info['requestor_name'] = $requestor_query->first_name;
		$info['id'] = $request_id;
		$info['form_id'] = $form_query;	
		$info['user_id'] = $user_query->user_id;	
		if ($form_query == 1)
		{
			$info['req_name'] = $requestor_query->request_name;
			$info['req_date'] = $requestor_query->request_date;
			$info['lease_name'] = $requestor_query->lease_name;
			$info['lease_date'] = $requestor_query->lease_date;
			$info['food_items'] = $requestor_query->food_items;
			$info['printed_materials'] = $requestor_query->printed_materials;
			$info['stationery'] = $requestor_query->stationery;
			$info['furniture'] = $requestor_query->furniture;
			$info['it_products'] = $requestor_query->it_products;
			$info['cleaning'] = $requestor_query->cleaning;
			$info['pest_control'] = $requestor_query->pest_control;
			$info['prptyrmv'] = $requestor_query->prptyrmv;
			$info['fit_out'] = $requestor_query->fit_out;
			$info['permit_work'] = $requestor_query->permit_work;
			$info['other_items'] = $requestor_query->other_items;


		}
		else
		{
			$info['req_name'] = $requestor_query1->request_name;
			$info['req_date'] = $requestor_query1->request_date;
			$info['lease_name'] = $requestor_query1->lease_name;
			$info['lease_date'] = $requestor_query1->lease_date;

		}
		
		$subject = sprintf('F%05d: Approval Required', $request_id);
		$email_content = view('emails.form_request_approve', ['info' => $info])->render();

		$smtp = Functions::getMailSetting($property_id, 'notification_');

			$message = array();
			$message['type'] = 'email';

			$message['to'] = $user_query->email;
			$message['subject'] = $subject;
			$message['content'] = $email_content;

			$message['smtp'] = $smtp;

			Redis::publish('notify', json_encode($message));
		}
		else
		{
			return;
		}

	}

	public function getLiftList(Request $request)
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
		

		if ($pageSize < 0)
			$pageSize = 20;
		$ret = array();
		
		$date_range = sprintf("DATE(form.signed_on) >= '%s' AND DATE(form.signed_on) <= '%s'", $start_date, $end_date);
		
		$query =DB::table('services_liftusage_form as form')
				->join('common_users as cu', 'form.signed_by', '=', 'cu.id')
				->leftJoin('services_form_appl_status as sta', function($join) {
					$join->on('form.id', '=', 'sta.request_id');
					$join->on('form.form_id', '=', 'sta.form_id');
				})
				->whereRaw($date_range);


		if( $filter != 'Total' && $filter != '')
				{
					if( $filter == 1 || $filter == L_PENDING)	// On Route
						$query->where('sta.status', L_PENDING);
					if( $filter == 2 || $filter == L_LEVEL1)
							$query->where('sta.status',L_LEVEL1);
					if( $filter == 3 || $filter == L_LEVEL2)
							$query->where('sta.status', L_LEVEL2);
					if( $filter == 4 || $filter == L_LEVEL3)
							$query->where('sta.status', L_LEVEL3);
					
					}
				
		if($filter_value != '')
			{
				$query->where(function ($query) use ($filter_value) {	
						$value = '%' . $filter_value . '%';
						$query->where('form.id', 'like', $value)
							->orWhere('cu.first_name', 'like', $value)
							->orWhere('form.request_name', 'like', $value);			
					});
			}
		$data_query = clone $query;
		
		$data_list = $data_query
		    ->orderBy('signed_on', 'desc')
			->select(DB::raw('form.*,sta.status, cu.first_name'))
			->skip($skip)->take($pageSize)
			->get();
			
		$count_query = clone $query;
		$totalcount = $count_query->count();

		
		$ret['code'] = 200;
		$ret['message'] = '';
 		
		$ret['totalcount'] = $totalcount;

		$ret['datalist'] = $data_list;
		$end = microtime(true);	
		$ret['time'] = $end - $start;

		return Response::json($ret);
	}

	public function getPropertyList(Request $request)
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
		
		$query = DB::table('services_liftusage_propertylist as prop');
				
		
		$data_query = clone $query;
		
		$data_list = $data_query
		    //->orderBy('quantity', 'desc')
			->select(DB::raw('prop.*'))
			->where('prop.request_id',$id)
			->skip($skip)->take($pageSize)
			->get();
		
		$count_query = clone $query;
		$totalcount = $count_query->count();

		
		$ret['code'] = 200;
		$ret['message'] = '';
 		
		$ret['totalcount'] = $totalcount;

		$ret['datalist'] = $data_list;
		$end = microtime(true);	
		$ret['time'] = $end - $start;
	
		return Response::json($ret);
	}


	public function updateStatus(Request $request)
	{
	$property_id = $request->get('property_id', 4);	
	$id = $request->get('id', 0);
	$status = $request->get('status', '');
	$form_id = $request->get('form_id', 0);
	$updated_by = $request->get('updated_by', '0' );
	$ret = array();
	
	$current_status = DB::table('services_form_appl_status as stat')
					->select(DB::raw('stat.status, stat.level, stat.form_id'))
					->where('stat.request_id', $id)
					->where('stat.form_id', $form_id)
					->first();

	
	$check_level = DB::table('services_form_approval_assignee as appr')
					->select(DB::raw('appr.level'))
					->where('appr.level','>',$current_status->level)
					->where('appr.user_id', $updated_by)
					->where('appr.form_id' , $current_status->form_id)	
					->first();
	if ($current_status->form_id == 1)
	{
		$title = "Lift Usage Form";
	}
	else
	{
		$title = "Permit to Work Form";
	}

	if (!empty($check_level))
	{

	if (($check_level->level- $current_status->level) == 1) 
	{


	$query = DB::table('services_form_appl_status as ml');

	$data_query = clone $query;

	$new_status = $status . $check_level->level;

	$upd_list = $data_query
					->where('ml.request_id', $id)
					->where('ml.form_id', $form_id)
					->update(['ml.status' => $new_status,'ml.level' => $check_level->level]);

	$this->sendnotification($property_id,$current_status->form_id,$check_level->level+1,$id,$title);

	}
	else
	{
		$code = 1;
		$ret['error'] = $code;
	}
}
	else
	{
		$code = 0;
		$ret['error'] = $code;
	}

		$ret['code'] = 200;
		
		$ret['id'] = $form_id;
 		sleep(1);
		return Response::json($ret);
		
	}
	

	public function createPermitWorkRequest(Request $request)
	{
		
		$property_id = $request->get('property_id', 4);
		$input = $request->except(['property_id']);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$lift_query= DB::table('services_liftusage_form');
		
		$max=clone $lift_query;
		$max_id=$max->max('id');
		
		
		$query = DB::table('services_permitwork_form');
		$insert=clone $query;
		$insert->insert($input);

		$max_query=clone $query;
		$id=$max_query->max('id');
		$upd_list = $insert
					->where('id', $id)
					->update(['lift_id' => $max_id,'requested_on'=>$cur_time]);


		$level = 0;
		DB::table('services_form_appl_status')->insert(['form_id' => 2, 'request_id' => $id ,'status' => "Incomplete",'level' => $level]);

		

		$ret = array();
		$ret['max_id'] = $max_id;
		return Response::json($ret);
	}

	public function getPermitList(Request $request)
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
		$filter_value = $request->get('filter_value', '');
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$user_id = $request->get('user_id', 0);
		

		if ($pageSize < 0)
			$pageSize = 20;
		$ret = array();
		
		$date_range = sprintf("DATE(form.requested_on) >= '%s' AND DATE(form.requested_on) <= '%s'", $start_date, $end_date);
		
		$query =DB::table('services_permitwork_form as form')
				->join('common_users as cu', 'form.request_by', '=', 'cu.id')
				->leftJoin('services_form_appl_status as sta', function($join) {
					$join->on('form.id', '=', 'sta.request_id');
					$join->on('form.form_id', '=', 'sta.form_id');
				})
				->whereRaw($date_range);
				
		
				
		if($filter_value != '')
			{
				$query->where(function ($query) use ($filter_value) {	
						$value = '%' . $filter_value . '%';
						$query->where('form.id', 'like', $value)
							->orWhere('cu.first_name', 'like', $value)
							->orWhere('form.request_name', 'like', $value);			
					});
			}
		$data_query = clone $query;
		
		$data_list = $data_query
		    ->orderBy('requested_on', 'desc')
			->select(DB::raw('form.*,sta.status, cu.first_name'))
			->skip($skip)->take($pageSize)
			->get();
			
		$count_query = clone $query;
		$totalcount = $count_query->count();

		
		$ret['code'] = 200;
		$ret['message'] = '';
 		
		$ret['totalcount'] = $totalcount;

		$ret['datalist'] = $data_list;
		$end = microtime(true);	
		$ret['time'] = $end - $start;
		
	


		return Response::json($ret);
	}

	public function updatePermitLease(Request $request)
	{
	$property_id = $request->get('property_id', 4);	
	$id = $request->get('id', 0);
	$lease_name = $request->get('lease_name', '');
	$lease_no = $request->get('lease_no', '');
	$lease_date = $request->get('lease_date', '');
	$updated_by = $request->get('updated_by', '0' );
	$ret = array();

	date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

	$query = DB::table('services_permitwork_form as form');
	$data_query = clone $query;

	$upd_list = $data_query
					->where('form.id', $id)
					->update(['form.lease_name' => $lease_name,
					'form.lease_no' => $lease_no,
					'form.lease_date' => $lease_date, 
					'form.leaser'=> $updated_by,
					'form.lease_sign'=>$cur_time]);

		$ret['code'] = 200;
		
 		sleep(1);
		return Response::json($ret);
		
	}

	public function updatePermitAuth(Request $request)
	{
	$property_id = $request->get('property_id', 4);	
	$id = $request->get('id', 0);
	$authorize_name = $request->get('authorize_name', '');
	$worker = $request->get('worker', '');
	$contact = $request->get('contact', '');
	$updated_by = $request->get('updated_by', '0' );
	$ret = array();
		$status = 'Pending';
	date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

	$query = DB::table('services_permitwork_form as form');
	$data_query = clone $query;

	$upd_list = $data_query
					->where('form.id', $id)
					->update(['form.authorize_name' => $authorize_name,
					'form.worker' => $worker,
					'form.contact' => $contact, 
					'form.auth_by'=> $updated_by,
					'form.authorizing_date'=>$cur_time]);

	$update = DB::table('services_form_appl_status as ml');

	$upd_query = clone $update;
						
	$upd_list = $upd_query
				->where('ml.request_id', $id)
				->where('ml.form_id', 2)
				->update(['ml.status' => $status]);
					
	$level = 0;
	$form_query = DB::table('services_permitwork_form as form')
					->select(DB::raw('form.form_id'))
					->where('id', $id)
					->first();
		$title = "Permit to Work Form";
		$this->sendnotification($property_id,$form_query->form_id,$level+1,$id,$title);

		$ret['code'] = 200;
		
 		sleep(1);
		return Response::json($ret);
		
	}

	public function updatePermitThird(Request $request)
	{
	$property_id = $request->get('property_id', 4);	
	$id = $request->get('id', 0);
	$third_name = $request->get('third_name', '');
	$third_area = $request->get('third_area', '');
	$third_date = $request->get('third_date', '');
	$updated_by = $request->get('updated_by', '0' );
	$start_time = $request->get('start_time', '' );
	$end_time = $request->get('end_time', '' );
	$manager_no = $request->get('manager_no', '0' );
	$persons = $request->get('persons', '' );
	$scope = $request->get('scope', '' );
	$equipment = $request->get('equipment', '' );
	$keyissued = $request->get('keyissued', '' );
	$hotwork = $request->get('permit_require', '' );
	$isolation = $request->get('isolation_require', '' );
	$description = $request->get('description', '0' );
	$goggles = $request->get('goggles','0');
	$safetyglasses = $request->get('safetyglasses', '0');
	$faceshield = $request->get('faceshield', '0');
	$gloves = $request->get('gloves', '0');
	$respirator = $request->get('respirator', '0');
	$hearingprotection = $request->get('hearingprotection', '0');
	$welding = $request->get('welding', '0');
	$apron = $request->get('apron', '0');
	$showereyewash = $request->get('showereyewash', '0');
	$fallarrest = $request->get('fallarrest', '0');
	$other = $request->get('other', '0');
	$other_equip = $request->get('other_equip', '');
	$toxic = $request->get('toxic', '0');
	$corrosive = $request->get('corrosive', '0');
	$radioactive = $request->get('radioactive', '0');
	$electrical = $request->get('electrical', '0');
	$thermal = $request->get('thermal', '0');
	$storedenergy = $request->get('storedenergy', '0');
	$mechanical = $request->get('mechanical', '0');
	$spills = $request->get('spills', '0');
	$fire = $request->get('fire', '0');
	$other1 = $request->get('other1', '0');
	$other_hzrd = $request->get('other_hzrd', '');
	$cleaning = $request->get('cleaning', '0');
	$ventilation = $request->get('ventilation', '0');
	$signbarrier = $request->get('signbarrier', '0');
	$weldingcurtain = $request->get('weldingcurtain', '0');
	$clothes = $request->get('clothes', '0');
	$lockout = $request->get('lockout', '0');
	$mechanicallinkage = $request->get('mechanicallinkage', '0');
	$secure = $request->get('secure', '0');
	$other2 = $request->get('other2', '0');
	$other_iso = $request->get('other_iso', '');




	$status = "Pending";

	$ret = array();
	date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
	
	$query = DB::table('services_permitwork_form as form');

	$data_query = clone $query;

	

	$upd_list = $data_query
					->where('form.id', $id)
					->update(['form.third_name' => $third_name,
					'form.third_area' => $third_area,'form.third_date' => $third_date, 'form.third_user' => $updated_by,'form.start_time' => $start_time,
					'form.end_time' => $end_time,'form.manager_no' => $manager_no,'form.persons' => $persons,'form.scope' => $scope,
					'form.equipment' => $equipment,'form.keyissued' =>$keyissued,'form.permit_require' =>$hotwork,'form.isolation_require' =>$isolation,'form.description' =>$description,
					'form.third_sign'=>$cur_time,

					'form.goggles'=>$goggles,'form.safetyglasses'=>$safetyglasses,'form.faceshield'=>$faceshield,'form.welding'=>$welding,
					'form.respirator'=>$respirator,'form.hearingprotection'=>$hearingprotection,'form.gloves'=>$gloves,'form.apron'=>$apron,
					'form.showereyewash'=>$showereyewash,'form.fallarrest'=>$fallarrest,'form.other'=>$other,'form.other_equip'=>$other_equip,

					'form.toxic'=>$toxic,'form.corrosive'=>$corrosive,'form.radioactive'=>$radioactive,'form.electrical'=>$electrical,
					'form.thermal'=>$thermal,'form.storedenergy'=>$storedenergy,'form.mechanical'=>$mechanical,'form.spills'=>$spills,
					'form.fire'=>$fire,'form.other1'=>$other1,'form.other_hzrd'=>$other_hzrd,

					'form.cleaning'=>$cleaning,'form.ventilation'=>$ventilation,'form.signbarrier'=>$signbarrier,'form.weldingcurtain'=>$weldingcurtain,
					'form.clothes'=>$clothes,'form.lockout'=>$lockout,'form.mechanicallinkage'=>$mechanicallinkage,'form.secure'=>$secure,
					'form.other2'=>$other2,'form.other_iso'=>$other_iso]);

	
	
	
		$ret['code'] = 200;
		
		
 		sleep(1);
		
		return Response::json($ret);
		
	}

	public function createHotWorkRequest(Request $request)
	{
		
		$property_id = $request->get('property_id', 4);
		$input = $request->except(['property_id']);
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$permit_query= DB::table('services_permitwork_form');
					//	->where('keyissued','=','Yes');
		
		$max = clone $permit_query;
		
		$max_id=$max->max('id');
	
		$query = DB::table('services_hotwork_form');
		$insert=clone $query;
		$insert->insert($input);

		$max_query=clone $query;
		$id=$max_query->max('id');

		$upd_list = $insert
					->where('id', $id)
					->update(['permit_id' => $max_id,'requested_on'=>$cur_time]);



		$level = 0;
		DB::table('services_form_appl_status')->insert(['form_id' => 3, 'request_id' => $id ,'status' => "Incomplete",'level' => $level]);

		$ret = array();
		
		return Response::json($ret);
	}

	public function getHotworkList(Request $request)
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
		$filter_value = $request->get('filter_value', '');
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$user_id = $request->get('user_id', 0);
		

		if ($pageSize < 0)
			$pageSize = 20;
		$ret = array();
		
		$date_range = sprintf("DATE(form.requested_on) >= '%s' AND DATE(form.requested_on) <= '%s'", $start_date, $end_date);
		
		$query =DB::table('services_hotwork_form as form')
				->join('common_users as cu', 'form.request_by', '=', 'cu.id')
				->leftJoin('services_form_appl_status as sta', function($join) {
					$join->on('form.id', '=', 'sta.request_id');
					$join->on('form.form_id', '=', 'sta.form_id');
				})
				->whereRaw($date_range);
				
		
				
		if($filter_value != '')
			{
				$query->where(function ($query) use ($filter_value) {	
						$value = '%' . $filter_value . '%';
						$query->where('form.id', 'like', $value)
							->orWhere('cu.first_name', 'like', $value)
							->orWhere('form.request_company', 'like', $value);			
					});
			}
		$data_query = clone $query;
		
		$data_list = $data_query
		    ->orderBy('requested_on', 'desc')
			->select(DB::raw('form.*,sta.status, cu.first_name'))
			->skip($skip)->take($pageSize)
			->get();
			
		$count_query = clone $query;
		$totalcount = $count_query->count();

		
		$ret['code'] = 200;
		$ret['message'] = '';
 		
		$ret['totalcount'] = $totalcount;

		$ret['datalist'] = $data_list;

		return Response::json($ret);
	}

	public function updateHotworkAuth(Request $request)
	{
	$property_id = $request->get('property_id', 4);	
	$id = $request->get('id', 0);
//	$input = $request->except(['id','property_id']);
	$method = $request->get('method', '0');
	$risk = $request->get('risk', '0');
	$work_loc = $request->get('work_loc', '');
	$work_desc = $request->get('work_desc', '');
	$work_date = $request->get('work_date', '');
	$start_time = $request->get('start_time', '');
	$end_time = $request->get('end_time', '');
	$duration = $request->get('duration', '');
	$other_desc = $request->get('other_desc', '');
	$auth_name = $request->get('auth_name', '');
	$auth_pos = $request->get('auth_pos', '');
	$auth_date = $request->get('auth_date', '');
	$auth_by = $request->get('auth_by', '0' ); 
	$ret = array();

	date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

	$query = DB::table('services_hotwork_form as form');
	$data_query = clone $query;

	$upd_list = $data_query
					->where('form.id', $id)
					->update(['form.method' => $method,
					'form.risk' => $risk,
					'form.work_loc' => $work_loc, 
					'form.work_desc'=> $work_desc,
					'form.start_time' => $start_time,
					'form.end_time' => $end_time, 
					'form.duration'=> $duration,
					'form.other_desc'=> $other_desc,
					'form.auth_name' => $auth_name,
					'form.auth_pos' => $auth_pos, 
					'form.auth_date'=> $auth_date,
					'form.auth_by'=>$auth_by,
					'form.auth_on'=>$cur_time]);


		$ret['code'] = 200;
		
 		sleep(1);
		return Response::json($ret);
		
	}

	public function updateHotworkInspection(Request $request)
	{
	$property_id = $request->get('property_id', 4);	
	$id = $request->get('id', 0);

	$tasks =$request->get('tasks','');
	$inspect_by = $request->get('inspect_by', '');
	
	$ret = array();

	date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

	foreach ($tasks as $value) {
			$inspect_time=$value['inspect_time'];
			$work_in=$value['work_in'];
			

			DB::table('services_form_inspect_list')->insert(['request_id' => $id ,'work_in' => $work_in,'inspect_time' => $inspect_time,'inspect_by' => $inspect_by,'inspect_on' => $cur_time]);
		}
		
		$ret['code'] = 200;
		$ret['inspect_by'] = $inspect_by;
		
 		sleep(1);
		return Response::json($ret);
		
	}

	public function getInspectionList(Request $request)
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
		
		$query = DB::table('services_form_inspect_list as list');
				
		
		$data_query = clone $query;
		
		$data_list = $data_query
		    //->orderBy('quantity', 'desc')
			->select(DB::raw('list.*'))
			->where('list.request_id',$id)
			->skip($skip)->take($pageSize)
			->get();

		$inspect = $data_query
				->select(DB::raw('list.inspect_by'))
				->first();

		$count_query = clone $query;
		$totalcount = $count_query->count();

		
		$ret['code'] = 200;
		$ret['message'] = '';
 		
		$ret['totalcount'] = $totalcount;

		$ret['datalist'] = $data_list;
		$ret['inspect_by'] = $inspect;
		$end = microtime(true);	
		$ret['time'] = $end - $start;
	
		return Response::json($ret);
	}


	public function updateHotworkFinal(Request $request)
	{
	$property_id = $request->get('property_id', 4);	
	$id = $request->get('id', 0);
//	$input = $request->except(['id','property_id']);
	$ended_time = $request->get('ended_time', '');
	$fire_check = $request->get('fire_check', '0');
	$alarm_check = $request->get('alarm_check', '0');
	$permit_check = $request->get('permit_check', '0');
	$sup_name = $request->get('sup_name', '');
	$sup_pos = $request->get('sup_pos', '');
	$final_date = $request->get('final_date', '');
	$final_time = $request->get('final_time', '');
	$final_by = $request->get('final_by', '0' ); 
	$ret = array();

	date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

	$query = DB::table('services_hotwork_form as form');
	$data_query = clone $query;

	$upd_list = $data_query
					->where('form.id', $id)
					->update(['form.ended_time' => $ended_time,
					'form.fire_check' => $fire_check,
					'form.alarm_check' => $alarm_check, 
					'form.permit_check'=> $permit_check,
					'form.sup_name' => $sup_name,
					'form.sup_pos' => $sup_pos, 
					'form.final_date'=> $final_date,
					'form.final_time'=> $final_time,
					'form.final_by'=>$final_by,
					'form.final_on'=>$cur_time]);


		$ret['code'] = 200;
		
 		sleep(1);
		return Response::json($ret);
		
	}


	public function updateHotworkClose(Request $request)
	{
	$property_id = $request->get('property_id', 4);	
	$id = $request->get('id', 0);
//	$input = $request->except(['id','property_id']);
	
	$close = $request->get('close', '');
	$withdraw_reason = $request->get('withdraw_reason', '');
	$close_name = $request->get('close_name', '');
	$close_pos = $request->get('close_pos', '');
	$close_date = $request->get('close_date', '');
	$close_time = $request->get('close_time', '');
	$close_by = $request->get('close_by', '0' ); 
	$ret = array();

	date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

	$query = DB::table('services_hotwork_form as form');
	$data_query = clone $query;

	$upd_list = $data_query
					->where('form.id', $id)
					->update(['form.close' => $close,
					'form.withdraw_reason'=> $withdraw_reason,
					'form.close_name' => $close_name,
					'form.close_pos' => $close_pos, 
					'form.close_date'=> $close_date,
					'form.close_time'=> $close_time,
					'form.close_by'=>$close_by,
					'form.close_on'=>$cur_time]);

	$update = DB::table('services_form_appl_status as ml');

	$upd_query = clone $update;
						
	$upd_list = $upd_query
				->where('ml.request_id', $id)
				->update(['ml.status' => 'Complete']);


		$ret['code'] = 200;
		
 		sleep(1);
		return Response::json($ret);
		
	}

	
}
