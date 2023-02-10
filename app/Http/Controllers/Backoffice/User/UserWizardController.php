<?php

namespace App\Http\Controllers\Backoffice\User;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;

use App\Models\Common\Chain;
use App\Models\Common\CommonUser;
use App\Models\Common\Department;
use App\Models\Common\Employee;
use App\Models\Common\PropertySetting;

use App\Models\Service\TaskGroup;
use App\Models\Service\LocationGroup;
use App\Models\Service\ShiftGroupMember;

use Excel;
use DB;
use Datatables;
use Response;
use Redis;
use App\Modules\Functions;


class UserWizardController extends UploadController
{
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$user_id = $request->get('user_id', 0);

			if( $user_id > 0 )
				$property_ids_by_jobrole = $request->get('property_ids_by_jobrole', []);
			else
			{
				$client_id = $request->get('client_id', 0);
				$property_ids_by_jobrole = CommonUser::getProertyIdsByClient($client_id);	
			}

			$datalist = DB::table('common_users as cu')		
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
				->leftJoin('common_user_language as cul', 'cu.lang_id', '=', 'cul.id')
				->leftJoin('common_user_group_members as cugm', 'cu.id', '=', 'cugm.user_id')
				->leftJoin('common_user_group as cg', 'cugm.group_id', '=', 'cg.id')
				->groupBy('cu.id');

			$datalist->whereIn('cd.property_id', $property_ids_by_jobrole);

			$status = $request->get('status', 0);

			if( $status == 'Active' )
				$datalist->where('cu.deleted', 0);

			if( $status == 'Disabled' )
				$datalist->where('cu.deleted', 1);	

			
			$datalist->select(DB::raw('cu.*,IFNULL( cul.language, "English") as language,
								cd.department,cd.property_id,jr.job_role,
								GROUP_CONCAT(cg.name) as usergroup
								'))
								->get();

			return Datatables::of($datalist)
					->addColumn('cbname', function ($data) {
						$ids = $data->building_ids;
						$list = DB::table('common_building')
							->whereRaw("FIND_IN_SET(id, '$ids')")
							->select(DB::raw('GROUP_CONCAT(name) as field'))
							->first();
						
						return $list->field;
					})			
					->addColumn('shiftgroup', function ($data) {
						$user_id = $data->id;
						$group_data = DB::table('services_shift_group_members as sgm')
							->leftJoin('services_shift_group as cg','sgm.shift_group_id','=','cg.id')
							->where('sgm.user_id', $user_id)
							->select(DB::raw('cg.*'))
							->first();
						$shiftgroup = '';
						if(!empty($group_data))
						$shiftgroup = $group_data->name ;
						//}
						return $shiftgroup;
					})	
					->addColumn('disabled_label', function ($data) {
						return $data->deleted ? 'Yes' : 'No';
					})	
					->addColumn('online_label', function ($data) {
						return $data->active_status ? 'Yes' : 'No';
					})									
					->addColumn('edit', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"   ng-disabled="viewclass" ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
					})
					->addColumn('delete', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal"  ng-disabled="viewclass" ng-click="onDeleteRow('.$data->id.')">
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
					})
					->addColumn('image', function ($data) {
						if($data->picture != '') {
							return '<p data-placement="top" data-toggle="tooltip" >
										<span class="thumb-sm avatar pull-left thumb-xs m-r-xs">
						            		<img src="'.$data->picture.'">
										</span></p>';
						}else {
							return '';
						}
					})
					->addColumn('reset', function ($data) {
//						if($data->email == '') {
							return '<p data-placement="top" data-toggle="tooltip" title="Reset"><button class="btn btn-success btn-xs" data-title="Reset" data-toggle="modal" data-target="#resetModal" ' . ($data->deleted == 1 || $data->lock == 'Yes' ? 'disabled' : ' ng-click="onResetRow('.$data->id.')"') . '>
								<span class="fa fa-undo"></span>
							</button></p>';
//						}else {
//							return '';
//						}
					})
					->rawColumns(['cbname', 'shiftgroup', 'disabled_label', 'online_label', 'edit', 'delete', 'image', 'reset'])
					->make(true);
        }
		else
		{
			// delete action
			$ids = $request->input('ids');
			if( !empty($ids) )
			{
				DB::table('common_users')->whereIn('id', $ids)->delete();			
				return back()->withInput();				
			} 

			$step = '0';
			return view('backoffice.wizard.user.user', compact('step'));			
		}
    }
	
	public function getGridData()
    {
		$datalist = DB::table('common_users as cu')		
						->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
						->leftJoin('common_building as cb', 'cd.building_id', '=', 'cb.id')
						->leftJoin('common_user_group_members as cm' ,'cu.id','=','cm.user_id')
						->leftJoin('common_user_group as cg','cm.group_id','=','cg.id')
						->select(['cu.*', 'cd.department','cg.name as usergroup','cb.name as cbname']);
						
		return Datatables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('edit', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit"  ng-disabled="viewclass" onclick="location.href = ' . "'/backoffice/user/wizard/user/".$data->id."/edit/'" .
							'"><span class="glyphicon glyphicon-pencil"></span></button></p>';
				})
				->addColumn('delete', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal"  ng-disabled="viewclass" onClick="onDeleteRow('.$data->id.')">
						<span class="glyphicon glyphicon-trash"></span>
					</button></p>';
				})
				->addColumn('image', function ($data) {
					if($data->picture != '') {
						return '<p data-placement="top" data-toggle="tooltip" >
											<span class="thumb-sm avatar pull-left thumb-xs m-r-xs">
												<img src="'.$data->picture.'">
											</span></p>';
					}else {
						return '';
					}
				})
				->addColumn('reset', function ($data) {
					if($data->email == '') {
						return '<p data-placement="top" data-toggle="tooltip" title="Reset"><button class="btn btn-danger btn-xs" data-title="Reset" data-toggle="modal" data-target="#resetModal"  ng-disabled="viewclass" onClick="onResetRow('.$data->id.')">
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
					}else {
						return '';
					}
				})
				->make(true);
    }

  
    public function create()
    {
		$model = new CommonUser();
		$department = Department::lists('department', 'id');
		$step = '0';


		return view('backoffice.wizard.user.usercreate', compact('model', 'department', 'step'));	
    }

	public function getDefaultPassword($depart_id) {
		$property_id = 0;
		$data = DB::table('common_department')
			->where('id', $depart_id)
			->first();
		if(!empty($data)) $property_id = $data->property_id;
		$password = DB::table('property_setting')
				->where('settings_key', 'default_password')
				->where('property_id', $property_id)
				->first();
		if(!empty($password))  $default_password = $password -> value;
		else $default_password = '00000000';

		return $default_password;
	}

    public function store(Request $request)
    {
        $step = '0';
	
		$input = $request->except(['id','usergroup_ids','usergroup','agent_id','picture_src','picture_name', 'job_role', 'shiftgroup', 'already_deleted','shift_id','disabled_label','online_label']);
		$shift=$request->get('shift_id',0);
		//echo $shift;
		$agent_id = $request->get('agent_id',0);
		
		$ret = array();

		$department = Department::find($input['dept_id']);
		if( empty($department) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Department';
			return Response::json($ret);
		}

		$email = $input['email'];
		/*
		$result = PropertySetting::isValidEmailDomain($department->property_id, $email);

		if( $result['code'] != 0 )
		{
			$ret['code'] = 205;
			$ret['message'] = $result['message'];
			return Response::json($ret);
		}
		*/
		$picture_src = $request->get('picture_src','') ;
		$picture = $request->get('picture_name','');
		$output_file = $_SERVER["DOCUMENT_ROOT"] . '/frontpage/img/';
		if(!file_exists($output_file)) {
			mkdir($output_file, 0777, true);
		}
		if($picture == '' ||  $input['picture'] == '/frontpage/img/default_photo.png') $picture = 'default_photo.png';
		$output_file = $_SERVER["DOCUMENT_ROOT"] . '/frontpage/img/' . $picture;
		if($picture_src !='') {
			$ifp = fopen($output_file, "wb");
			$data = explode(',', $picture_src);
			fwrite($ifp, base64_decode($data[1]));
			fclose($ifp);
		}
		$input['picture'] = '/frontpage/img/'.$picture;

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date('Y-m-d H:i:s');
		$input['created_at'] = $cur_time;
		$department_id = $input['dept_id'];
		$default_password = $this ->getDefaultPassword($department_id);
		$input['password'] = $default_password;

		$confirmuser = CommonUser::getUserName($input['username']);
		if($confirmuser == true) {
			$ret['code'] = '400';
			return Response::json($ret);
		}
		if ($input['login_pin'] == null){
		$alphabet = "0123456789";
		$pass = array();
		$alphaLength = strlen($alphabet) - 1;
    	for ($i = 0; $i < 6; $i++) {
        		$n = rand(0, $alphaLength);
        		$pass[$i] = $alphabet[$n];
    	}
		$pin = implode($pass);
		$input['login_pin'] = $pin;
		}
		
		$ivr_code = $request->get('ivr_password');

		if( is_null($ivr_code)){
			$ivr_code = 0;
			$request->ivr_password = 0;
			$input['ivr_password'] = 0;
		}

		if($ivr_code != 0){
			$confirmIVRPassword = CommonUser::getIVRPassword($ivr_code);
			
			if($confirmIVRPassword == true) {
				$ret['code'] = '402';
				$ret['message'] = "";
				return Response::json($ret);
			}
		}

		$confirmpin = CommonUser::getPin($input['login_pin']);
		if($confirmpin == true) {
			$ret['code'] = '401';
			return Response::json($ret);
		}

		$email = $input['email'];

		$model = CommonUser::create($input);

		// save it to employee table
		Employee::createFromUser($model);

		$message = 'SUCCESS';
		//add transaction
		if(!empty($model)) {
			DB::table('common_user_transaction')
				->insert(['user_id'=> $model->id , 'action' => 'create', 'detail' => 'Created','created_at' => $cur_time,'agent_id'=>$agent_id]);
		}
		if(!empty($model)) {
			$usergroup_ids = $request->get('usergroup_ids');
			if(!empty($usergroup_ids)) {
				$user_array =array();
				for($i = 0 ; $i < count($usergroup_ids) ; $i++ ) {
					$user_array[$i]['user_id'] = $model->id;
					$user_array[$i]['group_id'] = $usergroup_ids[$i];
				}
				if(!empty($user_array))
				 DB::table('common_user_group_members')->insert($user_array);
			}
		}

		$ret['code'] = 201;
		if( empty($model) )
		{
			$ret['message'] = 'Fail to create User';
			return Response::json($ret);
		}
		
		$task_groups = DB::table('services_task_group as stg')
					->join('services_dept_function as sdf', 'stg.dept_function', '=', 'sdf.id')
					->join('common_department as cd', 'sdf.dept_id', '=', 'cd.id')
					->where('cd.id',$model->dept_id)
					->select(DB::raw('stg.id'))
					->get();
		$task_group_ids=[];
		if(!empty($task_groups))
		{
		foreach ($task_groups as  $value) {
			$task_group_ids[]=$value->id;
		}
		}	
		$loc_groups = LocationGroup::all();
		$loc_group_ids=[];
		if(!empty($loc_groups))
		{
		foreach ($loc_groups as  $value) {
			$loc_group_ids[]=$value->id;
		}
		}	
		
		if(!empty($shift))
         {
		
		$shift_group_member= new ShiftGroupMember();
		$shift_group_member->user_id=$model->id;
		$shift_group_member->shift_group_id=$shift;
		$shift_group_member->task_group_id=json_encode($task_group_ids);
		$shift_group_member->location_grp_id=json_encode($loc_group_ids);
		$shift_group_member->day_of_week = 'Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday';
		$shift_group_member->save();
		 }
		$host_url = DB::table('property_setting as ps')
				->where('ps.settings_key', 'hotlync_host')
				->select(DB::raw('ps.*'))
				->first();

		$message = array();
		$message['type'] = 'email';
		$message['to'] = $input['email'];
		$message['subject'] = 'Hotlync Notification';
		$message['title'] = '';


		$message['smtp'] = Functions::getMailSetting($department->property_id, 'notification_');

		if( !empty($host_url) )
			$input['host_url'] = $host_url->value . config('app.frontend_url');
		else
			$input['host_url'] = Functions::getSiteURL() . config('app.frontend_url');

		$message['content'] = view('emails.account_notification', ['info' => $input])->render();

		Redis::publish('notify', json_encode($message));

		$ret['code'] = 200;
		$ret['data'] = $model;

		if ($request->ajax())
			return Response::json($ret);		
		else	
			return Redirect::to('/backoffice/user/wizard/user');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $model = CommonUser::find($id);
		if( empty($model) )
		{
			return back();
		}
		$department = Department::lists('department', 'id');
		$step = '0';		
		
		return view('backoffice.wizard.user.usercreate', compact('model', 'department', 'step'));	
    }

    public function update(Request $request, $id)
    {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date('Y-m-d H:i:s');
        $model = CommonUser::find($id);
		$agent_id = $request->get('agent_id',0);
		$message = 'SUCCESS';
		
		if( empty($model) )
		{
			$message = "User does not exist.";
			return back()->with('error', $message)->withInput();					
		}
		
		$input = $request->except(['usergroup_ids','usergroup','image','reset','agent_id','picture_src','picture_name', 'job_role', 'already_deleted','shiftgroup','shift_id','disabled_label', 'online_label']);
		$shift=$request->get('shift_id',0);
		
		$ret = array();

		$ivr_code = $request->get('ivr_password');

		if( is_null($ivr_code)){
			$ivr_code = 0;
			$request->ivr_password = 0;
			$input['ivr_password'] = 0;
		}
		
		$ivr_lock_check = $request->get('lock');
		$ivr_disable_check = $request->get('deleted');
		if($ivr_lock_check != 'Yes'){
			if(isset($ivr_disable_check)){
				if($ivr_disable_check != 1){
					if($ivr_code != 0){
						$confirmIVRPassword = CommonUser::getIVRPassword($ivr_code, $id);
						if($confirmIVRPassword == true) {
							$ret['code'] = '402';
							$ret['message'] = "Duplicate IVR " .$ivr_code. ". Please Change.";
							return Response::json($ret);
						}
					}
				}
			}
		}
		
		
		$department = Department::find($input['dept_id']);
		if( empty($department) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Invalid Department';
			return Response::json($ret);
		}

		$email = $input['email'];
/*
		$result = PropertySetting::isValidEmailDomain($department->property_id, $email);

		if( $result['code'] != 0 )
		{
			$ret['code'] = 205;
			$ret['message'] = $result['message'];
			return Response::json($ret);
		}
*/
		if ($input['login_pin'] == null){
			$alphabet = "0123456789";
			$pass = array();
			$alphaLength = strlen($alphabet) - 1;
			for ($i = 0; $i < 6; $i++) {
				$n = rand(0, $alphaLength);
				$pass[$i] = $alphabet[$n];
			}
			$pin = implode($pass);
			$input['login_pin'] = $pin;
		}
		if($model->login_pin != $input['login_pin']){
		$confirmpin = CommonUser::getPin($input['login_pin']);
		if($confirmpin == true) {
			$ret['code'] = '401';
			return Response::json($ret);
		}
		$confirmIVRPassword = CommonUser::getIVRPassword($input['ivr_password']);
		if($confirmIVRPassword == true) {
			$ret['code'] = '402';
			$ret['message'] = $input['ivr_password'];
			return Response::json($ret);
		}
		}
		if($input['lock'] == 'Yes') {
			$department_id = $input['dept_id'];
			$default_password = $this->getDefaultPassword($department_id);
			$input['password'] = $default_password;
		}

		$picture_src = $request->get('picture_src','') ;
		$picture = $request->get('picture_name','');
		$output_file = $_SERVER["DOCUMENT_ROOT"] . '/frontpage/img/';
		if(!file_exists($output_file)) {
			mkdir($output_file, 0777);
		}
		if($picture == '' || $input['picture'] == '/frontpage/img/default_photo.png') $picture = 'default_photo.png';
		$output_file = $_SERVER["DOCUMENT_ROOT"] . '/frontpage/img/' . $picture;
		if($picture_src !='') {
			$ifp = fopen($output_file, "wb");
			$data = explode(',', $picture_src);
			fwrite($ifp, base64_decode($data[1]));
			fclose($ifp);
		}
		$input['picture'] = '/frontpage/img/'.$picture;

		Redis::set('agent_id_' . $model->id, $agent_id);

		$already_deleted = $model->deleted;
		$already_lock = $model->lock;

		$model->update($input);
		if(!empty($model)) {
			if($input['lock'] == 'Yes' && $already_lock != 'Yes' ) {
				//add transaction
				DB::table('common_user_transaction')
						->insert(['user_id' => $model->id, 'action' => 'lock', 'detail' => 'Account locked', 'created_at' => $cur_time,'agent_id' => $agent_id]);
			}
			if($input['deleted'] == '1' && $already_deleted != 1 ) {
				//add transaction
				DB::table('common_user_transaction')
					->insert(['user_id' => $model->id, 'action' => 'disable', 'detail' => $input['deleted_comment'], 'created_at' => $cur_time,'agent_id' => $agent_id]);
			        DB::table('ivr_agent_status_log')->where('user_id', $id)->delete();
			}
		}

		if(!empty($model)) {
			$usergroup_ids = $request->get('usergroup_ids');
			DB::table('common_user_group_members')
				->where('user_id', $id)
//					->whereIn('group_id', $usergroup_ids)
				->delete();
			$user_array =array();
			for($i = 0 ; $i < count($usergroup_ids) ; $i++ ) {
				$user_array[$i]['user_id'] = $id;
				$user_array[$i]['group_id'] = $usergroup_ids[$i];
			}
			if(!empty($user_array))
				DB::table('common_user_group_members')->insert($user_array);

		}else {
			//if( empty($model) )
				$message = 'Internal Server error';
		}
		
		if(!empty($shift))
		{
			$task_groups = DB::table('services_task_group as stg')
						->join('services_dept_function as sdf', 'stg.dept_function', '=', 'sdf.id')
						->join('common_department as cd', 'sdf.dept_id', '=', 'cd.id')
						->where('cd.id',$model->dept_id)
						->select(DB::raw('stg.id'))
						->get();
			$task_group_ids=[];
			if(!empty($task_groups))
			{
			foreach ($task_groups as  $value) {
				$task_group_ids[]=$value->id;
			}
			}	
			$loc_groups = LocationGroup::all();
			$loc_group_ids=[];
			if(!empty($loc_groups))
			{
			foreach ($loc_groups as  $value) {
				$loc_group_ids[]=$value->id;
			}
			}	
			
			
			$shift_group_member= new ShiftGroupMember();
			$shift_group_member->user_id=$model->id;
			$shift_group_member->shift_group_id=$shift;
			$shift_group_member->task_group_id=json_encode($task_group_ids);
			$shift_group_member->location_grp_id=json_encode($loc_group_ids);
			$shift_group_member->day_of_week = 'Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday';
			$shift_group_member->save();
		}

        if(!empty($model)) {
			if($model->lock == 'Yes' ) {
				$department_id = $input['dept_id'];
				$default_password = $this->getDefaultPassword($department_id);
				$password = $default_password;
				$user = DB::table('common_users_log_history')
					->insert(
						['user_id' => $model->id, 'password' => $password, 'created_at'=>$cur_time ]
					);
			}
		}

		// When user is disabled from BO > Users > User, Calls in Mobile and Landline should be marked as Closed as well.
		if( $input['deleted'] == 1 )
		{
			DB::table('call_admin_calls')
				->where('user_id', $id)
				->where('approval', '!=', 'Closed')
				->update(
					[
						'approval' => 'Closed'
					]
				);

			DB::table('call_mobile_calls')
				->where('user_id', $id)
				->where('approval', '!=', 'Closed')
				->update(
					[
						'approval' => 'Closed'
					]
				);
			$ret['admin_mobile'] = 'Yes';	 
		}

		$ret['code'] = 200;
		$ret['data'] = $model;
		
		if ($request->ajax())
			return Response::json($ret);
		else
			return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = CommonUser::find($id);
		$model->delete();

		DB::table('common_user_group_members')
			->where('user_id',$id)
			->delete();

		if ($request->ajax()) 
			return Response::json($model);
		else	
			return Redirect::to('/backoffice/user/wizard/user');		
    }

	public function getJobRoles(Request $request) {
		$property_id = $request->get('property_id', 0);
		$job_role = $request->get('job_role','');
		$model = Db::table('common_job_role as jr')
				->where('jr.property_id', $property_id)
				->whereRaw("job_role like '%".$job_role."%'")
				->select(DB::raw('jr.*'))
				->take(10)
				->get();

		return Response::json($model);
	}

	public function resetPassword($id) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date('Y-m-d H:i:s');
		$ids = explode("-",$id);

		$user_id = $ids[0];
		$agent_id = $ids[1];
		if($user_id > 0 ) {
			$user = DB::table('common_users')
				->where('id', $user_id)
				->first();
			$depart_id = $user->dept_id;
			$password = $this->getDefaultPassword($depart_id);
			DB::table('common_users')
				->where('id', $user_id)
				->update(['password'=>$password]);
			//add transaction
			DB::table('common_user_transaction')->
				insert(['user_id' => $id, 'action' => 'reset', 'detail' => 'Reset password', 'created_at' => $cur_time,'agent_id' => $agent_id]);
		}

		$ret = array();
		$ret['user_id'] = $user_id;
		$ret['password'] = $password;
		return Response::json($ret);
		//return Response::json($user_id);
		//return Response::json($password);
	}

	public function getHistory($id) {
		$actions = array('create','delete','lock','reset', 'update','disable');
		$ignore_fields = ['fcm_key','device_id'];
		$query = DB::table('common_user_transaction')
			->whereIn('action', $actions);
		
		foreach($ignore_fields as $row)	
		{
			$query->whereRaw(sprintf("detail NOT LIKE '%%%s:%%'", $row));
		}

		$user = $query
			->where('user_id',$id)
			->orderBy('created_at')
			->select(DB::raw('*'))
			->get();

		$userdate = array();
		for($i =0 ; $i < count($user) ; $i++) {
			$userdate[$i]['detail']  = $user[$i]->detail;
			$userdate[$i]['action']  = $user[$i]->action;

			$user_id = $user[$i]->user_id;
			$username = DB::table('common_users as cu')
				->where('cu.id', $user_id)
				->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as name'))
				->first();
			if(!empty($username))
				$userdate[$i]['username'] = $username->name;
			else
				$userdate[$i]['username'] = '';

			$agent_id = $user[$i]->agent_id;
			$agentname = DB::table('common_users as cu')
				->where('cu.id', $agent_id)
				->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as name'))
				->first();

			if(!empty($agentname))
				$userdate[$i]['agentname'] = $agentname->name;
			else
				$userdate[$i]['agentname'] = '';

			if($agent_id == 0) $userdate[$i]['agentname'] = 'SuperAdmin';

			$userdate[$i]['created_at'] = date('d-M-Y h:m A' ,strtotime($user[$i]->created_at));
		}

		return Response::json($userdate);
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

	public function sendCredential(Request $request) {
		$username = $request->get('username','');
		$data = Db::table('common_users as cu')
			->leftJoin('common_job_role as cr', 'cu.job_role_id', '=', 'cr.id')
			->whereRaw("cu.email != ''")
			->select(DB::raw('cu.*, cr.property_id'))
			->get();

		foreach ($data as $row)
		{
			$property_id = $row->property_id;
			$info = array();
			$info['first_name'] = $row->first_name;
			$info['last_name'] = $row->last_name;
			$info['username'] = $row->username;
			$info['password'] = $row->password;
			$info['send_name'] = $username;
			$info['host_url'] = Functions::getSiteURL();
			$email = $row->email;

			$smtp = Functions::getMailSetting($property_id, 'notification_');


			$message = array();
			$message['type'] = 'email';
			$message['to'] = $email;
			//$message['to'] = 'goldstarkyg91@gmail.com';
			$message['subject'] = 'This is your credential';
			$message['title'] = ' Hello '.$row->first_name.' ' .$row->first_name;
			$message['content'] =  view('emails.send_credential', ['info' => $info])->render();;
			$message['smtp'] = $smtp;
			Redis::publish('notify', json_encode($message));
		}
		$ret = array();
		$ret['username'] = $username;
		return Response::json($ret);
	}

	public function sendPingToWin(Request $request)
    {
        $message = array();
        $message['type'] = 'windows_app';
        $message['to'] = $request->get('property_id',"");
        $message['content'] = $request->get('content',"");
        $message['subject'] = $request->get('subject',"");

        Redis::publish('notify', json_encode($message));
        $ret = array();
        $ret['result'] = "Testing";
        return Response::json($ret);
    }

	/* React functions */

	public function userIndex(Request $request)
	{

		$platform = $request->get('platform');
		$user_id = $request->get('user_id', 0);
		$client_id = $request->get('client_id', 0);
		if ($client_id != 0) {
			$property_ids_by_jobrole = CommonUser::getProertyIdsByClient($client_id);
		}

		$limit = $request->get('limit', 0);
		$offset = $request->get('offset', 0);
		$search = $request->get('searchtext', "");
		$sortColumn = $request->get('sortcolumn', 'cu.id');
		$sortOrder = $request->get('sortorder', 'desc');
		$filter = json_decode($request->get("filter", ""), true);

		$datalist = DB::table('common_users as cu')
			->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->leftJoin('common_user_language as cul', 'cu.lang_id', '=', 'cul.id')
			->leftJoin('common_user_group_members as cugm', 'cu.id', '=', 'cugm.user_id')
			->leftJoin('common_user_group as cg', 'cugm.group_id', '=', 'cg.id')
			->groupBy('cu.id');

		// if ($status == 'Active')
		// 	$datalist->where('cu.deleted', 0);

		// if ($status == 'Disabled')
		// 	$datalist->where('cu.deleted', 1);

		if (!empty($filter)) {
			if (!empty($filter["status"])) {
				$datalist->whereIn('cu.deleted', $filter["status"]);
			}
		}

		if (!empty($sortColumn) && !empty($sortOrder)) {
			$datalist->orderBy($sortColumn, $sortOrder);
		}

		if (!empty($search)) {
			$datalist->where('cu.id', 'like', '%' . $search . '%')
				->orWhere('cu.first_name', 'like', '%' . $search . '%')
				->orWhere('cu.last_name', 'like', '%' . $search . '%')
				->orWhere('cu.username', 'like', '%' . $search . '%')
				->orWhere('cul.language', 'like', '%' . $search . '%')
				->orWhere('jr.job_role', 'like', '%' . $search . '%')
				->orWhere('cu.ivr_password', 'like', '%' . $search . '%')
				->orWhere('cd.department', 'like', '%' . $search . '%')
				->orWhere('cu.mobile', 'like', '%' . $search . '%')
				->orWhere('cu.email', 'like', '%' . $search . '%');
			// ->orWhere('cu.deleted', 'like', '%' . $search . '%')
			// ->orWhere('cu.active_status', 'like', '%' . $search . '%');
		}

		if ($client_id != 0) {
			$datalist->whereIn('cd.property_id', $property_ids_by_jobrole);
		}

		$total = count($datalist->get());

		if ($limit != 0) {
			$datalist->take($limit);
		}
		if ($offset != 0) {
			$datalist->skip($offset);
		}

		$users = $datalist->select(DB::raw('cu.*,IFNULL( cul.language, "English") as language,
			cd.department,cd.property_id,jr.job_role,
			GROUP_CONCAT(cg.name) as usergroup
			'))
			->get();

		foreach ($users as $key => $val) {

			// Building
			$ids = $val->building_ids;
			$list = DB::table('common_building')
				->whereRaw("FIND_IN_SET(id, '$ids')")
				->select(DB::raw('GROUP_CONCAT(name) as field'))
				->first();

			$val->cbname = $list->field;

			// Shift Group
			$user_id = $val->id;
			$group_data = DB::table('services_shift_group_members as sgm')
				->leftJoin('services_shift_group as cg', 'sgm.shift_group_id', '=', 'cg.id')
				->where('sgm.user_id', $user_id)
				->select(DB::raw('cg.*'))
				->first();
			$shiftgroup = '';
			if (!empty($group_data))
				$shiftgroup = $group_data->name;

			$val->shiftgroup = $shiftgroup;

			// Disable Flag
			$val->disabled_label = $val->deleted ? 'Yes' : 'No';

			// Online Flag
			$val->online_label = $val->active_status ? 'Yes' : 'No';
		}

		return Response::json(['data' => $users, 'recordsFiltered' => $total]);
	}

	/* React functions Ends */
}
