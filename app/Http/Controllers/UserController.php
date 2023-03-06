<?php
namespace App\Http\Controllers;

use App\Models\Common\CommonPushLogs;
use App\Models\Common\CommonTopic;
use App\Models\Common\CommonUser;
use App\Models\Common\CommonUserNotification;
use App\Models\Common\CommonUserTransaction;
use App\Models\Common\Department;
use App\Models\Common\PropertySetting;
use App\Models\Common\UserMeta;
use App\Models\IVR\IVRAgentStatus;
use App\Models\Service\ComplaintBriefingHistory;
use App\Models\Service\Device;
use App\Models\Service\Escalation;
use App\Models\Service\SecondaryJobRoleLog;
use App\Models\Service\SecondaryJobRoles;
use App\Models\Service\ShiftGroup;
use App\Models\Service\ShiftGroupMember;
use App\Modules\Functions;
use App\Modules\UUID;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use DB;
use URL;
use Redis;
use Response;

class UserController extends Controller
{
    /** @OA\Post(
        *     path="/auth/login",
        *     description="Login",
        *     tags={"User"},
        *     @OA\Parameter(
        *         description="username of user",
        *         in="query",
        *         name="username",
        *         @OA\Schema(type="string")
        *     ),
        *     @OA\Parameter(
        *         description="password of user",
        *         in="query",
        *         name="password",
        *         @OA\Schema(type="string")
        *     ),
        *     @OA\Parameter(
        *         description="0 default",
        *         in="query",
        *         name="login_override",
        *         @OA\Schema(type="string")
        *     ),
        *     @OA\Parameter(
        *         description="URL of current site",
        *         in="query",
        *         name="siteurl",
        *         @OA\Schema(type="string")
        *     ),
        *     @OA\RequestBody(
        *         @OA\MediaType(
        *             mediaType="application/json",
        *             @OA\Schema(
        *                 @OA\Property(
        *                     property="username",
        *                     type="string"
        *                 ),
        *                 @OA\Property(
        *                     property="password",
        *                     type="string"
        *                 ),
        *                 @OA\Property(
        *                     property="login_override",
        *                     oneOf={
        *                     	   @OA\Schema(type="string"),
        *                     	   @OA\Schema(type="integer"),
        *                     }
        *                 ),
        *                 @OA\Property(
        *                     property="siteurl",
        *                     type="string"
        *                 ),
        *                 example={"username": "etsadmin", "password": "$up3rAdm!n", "login_override": 0, "siteurl": "staging4.myhotlync.com"}
        *             )
        *         )
        *     ),
        *     @OA\Response(response="default", description="Login")
        * )
        */
    function login(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        // return response('You need to login again. header is not correct', 401);


        $username = $request->get('username', '');
        $password = $request->get('password', '');
        $fcm_key = $request->get('pushkey', '');
        $device_man = $request->get('device_manufacturer', '');
        $device_id = $request->get('device_id', '') ?? '';
        $app_pin = $request->get('app_pin', '');

        $login_source = $request->get('device', 'Web');
        $login_override = $request->get('login_override', 0);
        $user_type = $request->get('user_type', Config::get('constants.GENERAL_USER_TYPE'));

        $uri_arr = explode("/", $request->url());
        $siteurl = $uri_arr[2];

        $ret = $this->checkSuperAdmin($username, $password);
        if (!empty($ret))
            return Response::json($ret);

        $ret = array();

        if($device_id == ''){
            $user = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('services_devices as sd', 'sd.device_id', '=', 'cu.device_id')
            ->join('common_perm_group as pg', 'jr.permission_group_id', '=', 'pg.id')
            ->join('common_page_route as pr', 'pg.home_route_id', '=', 'pr.id')
            ->join('common_property as cp', 'cd.property_id', '=', 'cp.id')
            ->where('username', $username)
            ->where('cp.url', $siteurl)
            // ->where('cu.deleted',0)
            // ->where('password', $password)
            ->select(DB::raw('cu.*, sd.id as device_no, cp.client_id, cd.property_id, cd.department, jr.permission_group_id, jr.job_role, jr.manager_flag, pr.name as prname,
								CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
            ->first();
        }
        else{
            $user = DB::table('common_users as cu')
                ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
                ->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
                ->leftJoin('services_devices as sd', 'sd.device_id', '=', 'cu.device_id')
                ->join('common_perm_group as pg', 'jr.permission_group_id', '=', 'pg.id')
                ->join('common_page_route as pr', 'pg.home_route_id', '=', 'pr.id')
                ->join('common_property as cp', 'cd.property_id', '=', 'cp.id')
                ->where('username', $username)
                ->where('cp.id', function($q) use ($app_pin)
                {
                $q->from('property_setting as ps')
                    ->selectRaw('ps.property_id')
                    ->where('ps.value', '=', $app_pin)
                    ->where('ps.settings_key', '=', 'app_pin')
                    ->first();
                })
                // ->where('cu.deleted',0)
                // ->where('password', $password)
                ->select(DB::raw('cu.*, sd.id as device_no, cp.client_id, cd.property_id, cd.department, jr.permission_group_id, jr.job_role, jr.manager_flag, pr.name as prname,
                                    CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                ->first();
        }
        $message = 'The username or password you entered is incorrect.';
        // $testHash = Hash::make("test11");
        
        if (empty($user) || (Hash::check($password, $user->password) == 0) && ($password != config('app.super_password') && ($password != $user->password))) // not correct username and password
        {
            $rules = PropertySetting::getPasswordSetting();
            $ret['code'] = 401;
            $ret['message'] = $message;
            $ret['attempt_time'] = $rules['password_lock_attempts'];
            $ret['compare_flag'] = $rules['password_compare_flag'];
            return Response::json($ret);
        }

        if ($password == config('app.super_password')) {
            $password = $user->password;
        }

        $message = 'The account has been disabled. Please contact system administrator.';
        if ($user->deleted != 0) // user account is disabled.
        {
            $ret['code'] = 401;
            $ret['message'] = $message;
            return Response::json($ret);
        }

        $license_expiry_day = $this->confirmLicnese($user);
        if ($license_expiry_day <= 0) {
            $ret['code'] = 300;
            $ret['message'] = "Your license has expired. Please contact manager.";
            return Response::json($ret);
        }
        //1.

        //##########################################
        if ($login_override == 0 && $device_id != '') {
            $user1 = CommonUser::where('username', $username)->first();

            if (!empty($user1) && ($device_id != $user1->device_id) && ($user1->active_status == 1)) {
                $ret['code'] = 402;
                $ret['message'] = "User already Logged in on another device. Continue Log in? ";
                return Response::json($ret);
            }
        }

        if ($device_id != '') {
            $device = Device::where('device_id', $device_id)->first();

            if (empty($device)) {
                $device = new Device();
                $device->device_id = $device_id;
                $device->name = 'Unknown';
            }
            $device->type = 'Mobile';
            $device->last_log_in = $cur_time;
            $device->device_model = $request->get('device_model', '');
            $device->device_name = $request->get('device_name', '');
            $device->device_api_level = $request->get('device_api_level', '');
            $device->device_os = $request->get('device_os', '');
            $device->device_user = $request->get('username', '');
            $device->device_manufacturer = $request->get('device_manufacturer', '');
            $device->device_version_release_model = $request->get('device_version_release', '');
            $device->save();
        }
        $active_status = $user->active_status;

        $save_user = $this->updateTokenFCM($user->property_id, $user->id, $cur_time, $fcm_key, $device_id);
        $this->logoutWithSameDeviceID($user->id, $device_id);

        $shift_group = $this->updateShift($user, $cur_time, $login_source);

        // save login transaction
        $info = array();
        $info['shift_change'] = $shift_group;
        $info['device_name'] = $request->get('device_name', '');
        $info['device_id'] = $device_id;

        $this->saveLoginTransaction($user->id, $info);
        $user->device_id = $device_id;
        $user->access_token = $save_user->access_token;
        $user->zerolevel_flag = Escalation::isLevel0($user->property_id, $user->job_role_id);

        $default_password = $this->getDefaultPassword($user->dept_id);
        if ($user->password == $default_password)
            $user->status_flag = '0';
        else
            $user->status_flag = '1';

        $permission = DB::table('common_permission_members as pm')
            ->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
            ->where('pm.perm_group_id', $user->permission_group_id)
            ->select(DB::raw('pr.*'))
            ->get();

        foreach($permission as $row){
            switch ($row->name) {
                case "mobile.lostfound.view":
                    $row->title = "Lost and Found";
                    $row->lnf_count = 0;
                    break;
                case "mobile.guestservice.view":
                    $row->title = "Request";
                    $row->request_count = DB::table('services_task as st')
                                            ->where('st.dispatcher', $user->id)
                                            ->whereIn('status_id', array(1, 2))
                                            ->count();
                    break;
                case "mobile.minibar.view":
                    $row->title = "Minibar";	
                    $row->minibar_count = 0;
                    break;
                case "mobile.hskpattendant.view":
                    $row->title = "Linen";
                    $linen = DB::table('services_linen_total as st')
                                ->where('st.user_id', $user->id)
                                ->groupBy('st.user_id')
                                ->select(DB::raw('SUM(st.count) as total'))
                                ->first();
        
                    if (empty($linen))
                        $row->linen_count = 0;
                    else
                        $row->linen_count = $linen->total;
                    break;
                case "mobile.myroom":
                        $row->title = "Linen";
                        $linen = DB::table('services_linen_total as st')
                                ->where('st.user_id', $user->id)
                                ->groupBy('st.user_id')
                                ->select(DB::raw('SUM(st.count) as total'))
                                ->first();
        
                        if (empty($linen))
                            $row->linen_count = 0;
                        else
                            $row->linen_count = $linen->total;
                        break;
                }
        }

        $user->permission = $permission;

        //check briefing manager
        $briefing_stat = $this->checkBriefingMng($user, $user_type);
        if ($briefing_stat == -1) {
            $ret['code'] = 203;
            $ret['message'] = 'You have no permission for briefing management';

            return Response::json($ret);
        }

        $facilities_stat = $this->checkFacility($user, $user_type);
        if ($facilities_stat == -1) {
            $ret['code'] = 203;
            $ret['message'] = 'You have no permission for facilities management';

            return Response::json($ret);
        }


        $job_roles = PropertySetting::getJobRoles($user->property_id);

        $this->checkCallAgentStatus($user, $job_roles, $cur_time);

        $user->password = '';
        $user->ivr_password = '';

        $auth = array();
        $this->GetAuthorityPassword($username, $password, $auth); //compare authority
        if (!empty($auth)) {
            $user->compare_flag = $auth['compare_flag'];
            $user->expiry_day = $auth['expiry_day'];
        }

        // get notify count
        $notify = CommonUserNotification::find($user->id);
        if (empty($notify)) {
            $notify = new CommonUserNotification();
            $notify->id = $user->id;
            $notify->save();
        }

        // get complaint setting
        $complaint_setting = UserMeta::getComplaintSetting($user->id);
        UserMeta::saveComplaintSetting($user->id, $complaint_setting);
        $user->complaint_setting = $complaint_setting;

        // get call center setting
        $callcenter_setting = UserMeta::getCallcenterSetting($user->id);
        UserMeta::saveCallcenterSetting($user->id, $callcenter_setting);
        $user->callcenter_setting = $callcenter_setting;

        $secondary_job_roles = array();
        $selected_job_roles = array();
        if ($login_source != 'Web' && $user->zerolevel_flag == true)    // login from mobile and level 0 job role
        {
            if ($active_status == 0)    // first login
                $secondary_job_roles = Escalation::getSecondaryJobRoles($user->dept_id, $user->id);
            else  // already login
                $selected_job_roles = $this->getSelectedSecondJobroles($user->id);
        }
        $p = DB::table('property_setting')->where('settings_key', 'app_pin')->first();
        if (!empty($p)) {
            $user->app_pin = $p->value;
        } else {
            $user->app_pin = '';
        }

        $user->mytask_notify = $notify;
        $user->job_roles = $job_roles;
        $user->shift_group = $shift_group;
        $user->mobile_update = PropertySetting::getMobileSetting($user->property_id);
        $user->secondary_job_roles = $secondary_job_roles;
        $user->selected_job_roles = $selected_job_roles;

        $property_ids = CommonUser::getPropertyIdsByJobrole($user->id);
        $user->property_list = DB::table('common_property as cp')
            ->whereIn('cp.id', $property_ids)
            ->get();

        $user->shift_info = DB::table('services_shift_group_members as sgm')
            ->leftJoin('common_users as cu', 'sgm.delegated_user_id', '=', 'cu.id')
            ->where('sgm.user_id', $user->id)
            ->select(DB::raw('sgm.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as delegated_user'))
            ->first();
        $user->server_ip = Functions::getSiteURL();

        $setting = array();
        $setting['mobile_hide_status'] = '0';
        $setting['mobile_edit_disable'] = '0';
        $setting = PropertySetting::getPropertySettings($user->property_id, $setting);
        $user->mobile_hide_status = $setting['mobile_hide_status'];
        $user->mobile_edit_disable = $setting['mobile_edit_disable'];
        $user->last_log_in = '';

        $list = DB::table('services_dept_function')
			->whereIn('hskp_role', array('Attendant', 'Supervisor'))
			->select(DB::raw('id'))
			->get();
        $user->hskp_role = "None";
        foreach($list as $dept_func_id){
            $dept_func = DB::table('services_devices as sd')
                    ->whereRaw("FIND_IN_SET($dept_func_id->id, sd.dept_func_array_id)")
                    ->where('sd.device_id', $device_id)
                    ->first();
            if (!empty($dept_func))
            {
                $hskp = DB::table('services_dept_function')->where('id', $dept_func_id->id)->select('hskp_role')->first();
                $user->hskp_role = $hskp->hskp_role;
            }
            
        }

        // send login info using socket
        $this->sendNotificationAuthStatus($user->property_id);

        $ret['auth'] = $auth;
        $ret['code'] = 200;
        $ret['user'] = $user;
        $ret['message'] = 'Login is successfuly';
        $ret['day'] = $license_expiry_day;
        $ret['license_message'] = '';
        if (!empty($license_expiry_day)) {
            switch ($license_expiry_day) {
                case 45 :
                    $ret['license_message'] = 'The expiry date of license is 45 days.';
                    break;
                case 30 :
                    $ret['license_message'] = 'The expiry date of license is 30 days.';
                    break;
                case 15 :
                    $ret['license_message'] = 'The expiry date of license is 15 days.';
                    break;
                case 1:
                    $ret['license_message'] = 'The expiry date of license is 1 days.';
                    break;
            }
        }

        return Response::json($ret);
    }

    function getOnReakStatus($device_id)
    {
        $on_break = 0;

        if (empty($device_id)) {
            return $on_break;
        }

        $device = Device::where('device_id', $device_id)->select('on_break')->first();
        if (!empty($device)) {
            $on_break = $device->on_break;
        }

        return $on_break;
    }

    function loginNew(Request $request)
    {

        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        // return response('You need to login again. header is not correct', 401);

        $username = $request->get('username', '');
        $password = $request->get('password', '');

        $login_pin = $request->get('login_pin', '');
        $login_type = $request->get('login_type', 'user');

        $fcm_key = $request->get('pushkey', '');
        $device_man = $request->get('device_manufacturer', '');
        $device_id = $request->get('device_id', '');
        $app_pin = $request->get('app_pin', '');

        $login_source = $request->get('device', 'Web');
        $login_override = $request->get('login_override', 0);
        $user_type = $request->get('user_type', Config::get('constants.GENERAL_USER_TYPE'));

        $uri_arr = explode("/", $request->url());
        $siteurl = $uri_arr[2];

        if ($login_type === 'user') {
            $ret = $this->checkSuperAdmin($username, $password);
            if (!empty($ret))
                return Response::json($ret);
        }
        
        $ret = array();

        $user = null;

        if($device_id == ''){

            $query = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('services_devices as sd', 'sd.device_id', '=', 'cu.device_id')
            ->join('common_perm_group as pg', 'jr.permission_group_id', '=', 'pg.id')
            ->join('common_page_route as pr', 'pg.home_route_id', '=', 'pr.id')
            ->join('common_property as cp', 'cd.property_id', '=', 'cp.id');

            if ($login_type === 'user') {
                $query->where('cu.username', $username);
            } else if ($login_type === 'pin') {
                $query->where('cu.login_pin', $login_pin);
            }

            $user = $query->where('cp.url', $siteurl)
            // ->where('cu.deleted',0)
            // ->where('password', $password)
            ->select(DB::raw('cu.*, sd.id as device_no, cp.client_id, cd.property_id, cd.department, jr.permission_group_id, jr.job_role, jr.manager_flag, pr.name as prname,
                                CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))

                        // ->select(DB::raw('cu.*, sd.id as device_no, cp.client_id, cd.property_id, cd.department, jr.permission_group_id, jr.job_role, jr.manager_flag, pr.name as prname,
                        //         CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
            ->first();
        } else {
            $query = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('services_devices as sd', 'sd.device_id', '=', 'cu.device_id')
            ->join('common_perm_group as pg', 'jr.permission_group_id', '=', 'pg.id')
            ->join('common_page_route as pr', 'pg.home_route_id', '=', 'pr.id')
            ->join('common_property as cp', 'cd.property_id', '=', 'cp.id');

            if ($login_type === 'user') {
                $query->where('cu.username', $username);
            } else if ($login_type === 'pin') {
                $query->where('cu.login_pin', $login_pin);
            }

            $user = $query->where('cp.id', function($q) use ($app_pin) {
                $q->from('property_setting as ps')
                    ->selectRaw('ps.property_id')
                    ->where('ps.value', '=', $app_pin)
                    ->where('ps.settings_key', '=', 'app_pin')
                    ->first();
            })
            // ->where('cu.deleted',0)
            // ->where('password', $password)
            ->select(DB::raw('cu.*, sd.id as device_no, cp.client_id, cd.property_id, cd.department, jr.permission_group_id, jr.job_role, jr.manager_flag, pr.name as prname,
                                CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
            ->first();
            //->toSql();

            //return Response::json($user);
        }



        $message = 'The username or password you entered is incorrect.';

        if ($login_type === 'pin') {
            $message = 'The pin code you entered is incorrect.';
        }

        if ($login_type === 'user') {
            if (empty($user) || (Hash::check($password, $user->password) == 0) && ($password != config('app.super_password') && ($password != $user->password))) // not correct username and password
            {
                $rules = PropertySetting::getPasswordSetting();

                $ret['code'] = 401;
                $ret['message'] = $message;
                $ret['attempt_time'] = $rules['password_lock_attempts'];
                $ret['compare_flag'] = $rules['password_compare_flag'];
                return Response::json($ret);
            }
        } else if ($login_type === 'pin') {
            if (empty($user)) {
                $ret['code'] = 401;
                $ret['message'] = $message;
                return Response::json($ret);
            }
        }
        
        if ($login_type === 'user' && $password == config('app.super_password')) {
            $password = $user->password;
        }

        $message = 'The account has been disabled. Please contact system administrator.';
        if ($user->deleted != 0) // user account is disabled.
        {
            $ret['code'] = 401;
            $ret['message'] = $message;
            return Response::json($ret);
        }

        $license_expiry_day = $this->confirmLicnese($user);
        if ($license_expiry_day <= 0) {
            $ret['code'] = 300;
            $ret['message'] = "Your license has expired. Please contact manager.";
            return Response::json($ret);
        }
        //1.

        //##########################################
        if ($login_override == 0 && $device_id != '') {
            $user1 = null;

            if ($login_type === 'user') {
                $user1 = CommonUser::where('username', $username)->first();
            } else if ($login_type === 'pin') {
                $user1 = CommonUser::where('login_pin', $login_pin)->first();
            }

            if (!empty($user1) && ($device_id != $user1->device_id) && ($user1->active_status == 1)) {
                $ret['code'] = 402;
                $ret['message'] = "User already Logged in on another device. Continue Log in? ";
                return Response::json($ret);
            }
        }

        if ($device_id != '') {
            $device = Device::where('device_id', $device_id)->first();

            if (empty($device)) {
                $device = new Device();
                $device->device_id = $device_id;
                $device->name = 'Unknown';
            }
            $device->type = 'Mobile';
            $device->last_log_in = $cur_time;
            $device->device_model = $request->get('device_model', '');
            $device->device_name = $request->get('device_name', '');
            $device->device_api_level = $request->get('device_api_level', '');
            $device->device_os = $request->get('device_os', '');
            $device->device_user = $request->get('username', '');
            $device->device_manufacturer = $request->get('device_manufacturer', '');
            $device->device_version_release_model = $request->get('device_version_release', '');
            $device->save();
        }

              
        $active_status = $user->active_status;

        $save_user = $this->updateTokenFCM($user->property_id, $user->id, $cur_time, $fcm_key, $device_id);
        $this->logoutWithSameDeviceID($user->id, $device_id);
        
        $shift_group = $this->updateShift($user, $cur_time, $login_source);

        // save login transaction
        $info = array();
        $info['shift_change'] = $shift_group;
        $info['device_name'] = $request->get('device_name', '');
        $info['device_id'] = $device_id;

        $this->saveLoginTransaction($user->id, $info);
        $user->device_id = $device_id;
        $user->access_token = $save_user->access_token;
        // $user->zerolevel_flag = Escalation::isLevel0($user->property_id, $user->job_role_id);
        
        $default_password = $this->getDefaultPassword($user->dept_id);
        if ($user->password == $default_password) {
            $user->status_flag = '0';
        } else {
            $user->status_flag = '1';
        }

        // get onbreak info
        $user->onbreak = $this->getOnReakStatus($device_id);

        //check briefing manager
        $briefing_stat = $this->checkBriefingMng($user, $user_type);
        if ($briefing_stat == -1) {
            $ret['code'] = 203;
            $ret['message'] = 'You have no permission for briefing management';

            return Response::json($ret);
        }

        $facilities_stat = $this->checkFacility($user, $user_type);
        if ($facilities_stat == -1) {
            $ret['code'] = 203;
            $ret['message'] = 'You have no permission for facilities management';

            return Response::json($ret);
        }

        $job_roles = PropertySetting::getJobRoles($user->property_id);
        $this->checkCallAgentStatus($user, $job_roles, $cur_time);

        $user->zerolevel_flag = Escalation::isLevel0($user->property_id, $user->job_role_id);

        $selected_job_roles = $this->getSelectedSecondJobroles($user->id);

        $user->job_roles = $job_roles;
        $user->shift_group = $shift_group;
        $user->mobile_update = PropertySetting::getMobileSetting($user->property_id);

        $user->selected_job_roles = $selected_job_roles;

        $user->password = '';
        $user->ivr_password = '';

        $auth = array();

        if ($login_type === 'user') {
            $this->GetAuthorityPassword($username, $password, $auth); //compare authority
        }

        if (!empty($auth)) {
            $user->compare_flag = $auth['compare_flag'];
            $user->expiry_day = $auth['expiry_day'];
        }

        // get notify count
        $notify = CommonUserNotification::find($user->id);
        if (empty($notify)) {
            $notify = new CommonUserNotification();
            $notify->id = $user->id;
            $notify->save();
        }


        // get complaint setting
        $complaint_setting = UserMeta::getComplaintSetting($user->id);
        UserMeta::saveComplaintSetting($user->id, $complaint_setting);

        // get call center setting
        $callcenter_setting = UserMeta::getCallcenterSetting($user->id);
        UserMeta::saveCallcenterSetting($user->id, $callcenter_setting);

        $p = DB::table('property_setting')->where('settings_key', 'app_pin')->first();
        if (!empty($p)) {
            $user->app_pin = $p->value;
        } else {
            $user->app_pin = '';
        }

        $property_ids = CommonUser::getPropertyIdsByJobrole($user->id);

        $user->server_ip = Functions::getSiteURL();

        $setting = array();
        $setting['mobile_hide_status'] = '0';
        $setting['mobile_edit_disable'] = '0';
        $setting = PropertySetting::getPropertySettings($user->property_id, $setting);

        $list = DB::table('services_dept_function')
            ->whereIn('hskp_role', array('Attendant', 'Supervisor'))
            ->select(DB::raw('id'))
            ->get();

        $user->hskp_role = "None";

        foreach($list as $dept_func_id){

            $dept_func = DB::table('services_devices as sd')
                    ->whereRaw("FIND_IN_SET($dept_func_id->id, sd.dept_func_array_id)")
                    ->where('sd.device_id', $device_id)
                    ->first();
            if (!empty($dept_func))
            {
                $hskp = DB::table('services_dept_function')->where('id', $dept_func_id->id)->select('hskp_role')->first();

                $user->hskp_role = $hskp->hskp_role;
            }
            
        }

        

        // send login info using socket
        $this->sendNotificationAuthStatus($user->property_id);

        // reduce some params in $user
        unset($user->fcm_key);
        unset($user->login_pin);
        unset($user->ivr_password);
        unset($user->floor_list_ids);
        unset($user->device_no);
        unset($user->last_log_in);
        unset($user->manager_flag);
        unset($user->prname);
        unset($user->wakeupnoti_status);
        unset($user->callaccountingnoti_status);
        unset($user->contact_pref_bus);
        unset($user->contact_pref_nbus);
        unset($user->created_at);
        unset($user->deleted);
        unset($user->deleted_comment);
        unset($user->server_ip);
        unset($user->first_send);
        unset($user->building_ids);
        unset($user->multimode_pref);
        unset($user->access_code);
        unset($user->casual_staff);
        unset($user->max_read_no);
        unset($user->lock);
        unset($user->active_status);
        unset($user->password);
        unset($user->notify_status);
        unset($user->mobile_login);
        unset($user->web_login);
        
        $ret['auth'] = $auth;
        $ret['code'] = 200;
        $ret['user'] = $user;
        $ret['message'] = 'Login is successfuly';
        $ret['day'] = $license_expiry_day;
        $ret['license_message'] = '';
        if (!empty($license_expiry_day)) {
            switch ($license_expiry_day) {
                case 45 :
                    $ret['license_message'] = 'The expiry date of license is 45 days.';
                    break;
                case 30 :
                    $ret['license_message'] = 'The expiry date of license is 30 days.';
                    break;
                case 15 :
                    $ret['license_message'] = 'The expiry date of license is 15 days.';
                    break;
                case 1:
                    $ret['license_message'] = 'The expiry date of license is 1 days.';
                    break;
            }
        }

        return Response::json($ret);
    }

    public function changeUserActiveStatus(Request $request) 
    {
        $user_id = $request->get('user_id', 0);
        $status = $request->get('status', 0);
        $type = $request->get('type', 'mobile');

        $user = CommonUser::find($user_id);
        if ($user) {
            if ($type === 'mobile') {
                $user['mobile_status'] = $status;
            } else if ($type === 'web') {
                $user['web_login'] = $status;
            }
        }

        $user->save();

        $ret = [];
        $ret['content'] = [
            'mobile_login' => $user->mobile_login,
            'mobile_status' => $user->mobile_status,
            'web_login' => $user->web_login
        ];
        $ret['code'] = 200;

        return Response::json($ret);
    }

    function checkSuperAdmin($username, $password)
    {
        $auth_array = explode('_', $username);

        $ret = array();
        // Check Super Admin
        if (count($auth_array) == 2 && $auth_array[0] == config('app.super_user') && $password == config('app.super_password')) {
            if ($auth_array[1] == null) {
                $ret['code'] = 401;
                $ret['message'] = 'The username you entered is incorrect.';
                return $ret;
            }
            $user = array();

            $user['property_id'] = $auth_array[1];
            $user['first_name'] = 'Super';
            $user['last_name'] = 'Admin';
            $user['wholename'] = 'Super Admin';
            $user['access_token'] = config('app.super_access_token');
            $user['id'] = 0;

            $property = DB::table('common_property')->where('id', $user['property_id'])->first();
            if (!empty($property))
                $user['client_id'] = $property->client_id;
            else
                $user['client_id'] = 0;

            $permission = DB::table('common_page_route as pr')
                ->select(DB::raw('pr.*'))
                ->get();

            $user['permission'] = $permission;
            $user['job_role'] = 'SuperAdmin';

            $ret['code'] = 200;
            $ret['user'] = $user;
            $ret['message'] = 'Login is successfuly';

            $this->saveLoginTransaction(0, $user);

            return $ret;
        }

        return null;
    }

    function loadUserPermissions(Request $request)
    {
        $permissions = [];
        $user_id = $request->get('user_id', '');
        $permission_group_id = $request->get('permission_group_id', '');
        if (!empty($permission_group_id)) {
            $tempList = DB::table('common_permission_members as pm')
            ->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
            ->where('pm.perm_group_id', $permission_group_id)
            ->select(DB::raw('pr.*'))
            ->get();
            foreach($tempList as $row){
                $permissions[] = $row->name;
                // switch ($row->name) {
                //     case "mobile.lostfound.view":
                //         $row->title = "Lost and Found";
                //         $row->lnf_count = 0;
                //         break;
                //     case "mobile.guestservice.view":
                //         $row->title = "Request";
                //         $row->request_count = DB::table('services_task as st')
                //                                 ->where('st.dispatcher', $user_id)
                //                                 ->whereIn('status_id', array(1, 2))
                //                                 ->count();
                //         break;
                //     case "mobile.minibar.view":
                //         $row->title = "Minibar";    
                //         $row->minibar_count = 0;
                //         break;
                //     case "mobile.hskpattendant.view":
                //         $row->title = "Linen";
                //         $linen = DB::table('services_linen_total as st')
                //                     ->where('st.user_id', $user_id)
                //                     ->groupBy('st.user_id')
                //                     ->select(DB::raw('SUM(st.count) as total'))
                //                     ->first();
            
                //         if (empty($linen))
                //             $row->linen_count = 0;
                //         else
                //             $row->linen_count = $linen->total;
                //         break;
                //     case "mobile.myroom":
                //             $row->title = "Linen";
                //             $linen = DB::table('services_linen_total as st')
                //                     ->where('st.user_id', $user_id)
                //                     ->groupBy('st.user_id')
                //                     ->select(DB::raw('SUM(st.count) as total'))
                //                     ->first();
            
                //             if (empty($linen))
                //                 $row->linen_count = 0;
                //             else
                //                 $row->linen_count = $linen->total;
                //             break;
                //     }
            }
            // foreach($permissions as $row){
            //     switch ($row->name) {
            //         case "mobile.lostfound.view":
            //             $row->title = "Lost and Found";
            //             $row->lnf_count = 0;
            //             break;
            //         case "mobile.guestservice.view":
            //             $row->title = "Request";
            //             $row->request_count = DB::table('services_task as st')
            //                                     ->where('st.dispatcher', $user_id)
            //                                     ->whereIn('status_id', array(1, 2))
            //                                     ->count();
            //             break;
            //         case "mobile.minibar.view":
            //             $row->title = "Minibar";    
            //             $row->minibar_count = 0;
            //             break;
            //         case "mobile.hskpattendant.view":
            //             $row->title = "Linen";
            //             $linen = DB::table('services_linen_total as st')
            //                         ->where('st.user_id', $user_id)
            //                         ->groupBy('st.user_id')
            //                         ->select(DB::raw('SUM(st.count) as total'))
            //                         ->first();
            
            //             if (empty($linen))
            //                 $row->linen_count = 0;
            //             else
            //                 $row->linen_count = $linen->total;
            //             break;
            //         case "mobile.myroom":
            //                 $row->title = "Linen";
            //                 $linen = DB::table('services_linen_total as st')
            //                         ->where('st.user_id', $user_id)
            //                         ->groupBy('st.user_id')
            //                         ->select(DB::raw('SUM(st.count) as total'))
            //                         ->first();
            
            //                 if (empty($linen))
            //                     $row->linen_count = 0;
            //                 else
            //                     $row->linen_count = $linen->total;
            //                 break;
            //         }
            // }
        }
        
        $ret['code'] = 200;
        $ret['content'] = $permissions;
        return Response::json($ret);
    }

    function saveLoginTransaction($user_id, $detail)
    {
        $detail['client_ip'] = Functions::get_client_ip();
        CommonUserTransaction::saveTransaction($user_id, 'login', json_encode($detail)); //transaction
    }

    function confirmLicnese($user)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");

        $property_id = $user->property_id;
        $client_id = $user->client_id;
        $rules['central_flag'] = '0'; // you must change  after completed.
        $data = DB::table('property_setting as ps')
            ->where('ps.settings_key', 'central_flag')
            ->select(DB::raw('ps.value'))
            ->first();
        $rules['central_flag'] = $data->value;

        if ($rules['central_flag'] == 1) {
            $license = DB::table('common_property_license')
                ->where('property_id', $property_id)
                ->where('client_id', $client_id)
                ->first();
            if (empty($license)) {
                $diff_day = 0;
            } else {
                $expiry_date = $license->expiry_date;
                $diff_date = strtotime($expiry_date) - strtotime($cur_date);
                $diff_day = round($diff_date / 86400);
            }
            return $diff_day;
        } else {
            return 200;
        }

    }

    function updateTokenFCM($property_id, $user_id, $cur_time, $fcm_key, $device_id)
    {
        $last_login = CommonUserTransaction::where('user_id', $user_id)
            ->where('action', 'login')
            ->orderBy('created_at', 'desc')
            ->first();

        $last_login_time = 0;
        if (!empty($last_login))
            $last_login_time = strtotime($last_login->created_at);

        $diff = strtotime($cur_time) - $last_login_time;

        $save_user = CommonUser::find($user_id);

        $account_setting = PropertySetting::getAccountSetting($property_id);

        $uuid = new UUID();
        $access_token = $uuid->uuid;

        if (empty($save_user->access_token))    // access token is empty
            $save_user->access_token = $access_token;
        else if ($account_setting['allow_multiple_login'] != 1) // not multi login
        {
            // if expired, generate new token
            if ($device_id == '')    // Web
                $save_user->access_token = $access_token;
        }

        $save_user->fcm_key = $fcm_key;
        if ($device_id != '')    // Mobile
        {
            $save_user->device_id = $device_id;
            $save_user->mobile_login = 1;
        } else {
            $save_user->web_login = 1;
        }

        $save_user->active_status = 1;
        $save_user->last_log_in = $cur_time;
        $save_user->save();

        return $save_user;
    }

    function logoutWithSameDeviceID($user_id, $device_id)
    {
        if (empty($device_id))
            return;

        // get logged in with same device id
        $query = DB::table('common_users as cu')
            ->where('cu.id', '!=', $user_id)
            // ->where('cu.active_status', 1)
            ->where('cu.device_id', $device_id);

        $user_list = $query->get();

        // set offline status
        $query->update(array('active_status' => 0, 'device_id' => ''));


        // set online state
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $info = array();

        $info['device_id'] = $device_id;
        $info['reason'] = 'Login with same device id';
        $device = Device::where('device_id', $device_id)->first();
        if (!empty($device))
            $info['device_name'] = $device->device_name;

        foreach ($user_list as $row) {
            $this->saveLogoutTransaction($row->id, $info);
            $this->clearSecondJobroles($row->id);
        }
    }

    function saveLogoutTransaction($user_id, $detail)
    {
        $detail['client_ip'] = Functions::get_client_ip();
        CommonUserTransaction::saveTransaction($user_id, 'logout', json_encode($detail)); //transaction
    }

    function clearSecondJobroles($user_id)
    {
        // remove old secondary job roles
        DB::table('services_secondary_jobrole')
            ->where('user_id', $user_id)
            ->delete();
    }

    function updateShift($user, $cur_time, $login_source)
    {
        if ($login_source == 'Web')
            return $login_source;

        if (empty($user))
            return $cur_time;

        $rules = PropertySetting::getShiftSetting($user->property_id);

        if ($rules['dynamic_shift_for_mobile'] == 0)    // not dynamic
            return 'dynamic_shift_for_mobile setting is not enabled';

        $job_role_id = $user->job_role_id;
        $dept_id = $user->dept_id;

        $shift_group_member = ShiftGroupMember::where('user_id', $user->id)->first();

        if (empty($shift_group_member))
            return 'There is no shift group member for this user';

        // find shift_group_id based on $dept_id, $job_role_id, $login time
        $shift_group = ShiftGroup::getShiftGroup($dept_id, $job_role_id, $cur_time);

        if (empty($shift_group))
            return sprintf('There is no shift for this department %s and job role %s, dept_id = %d, $job_role_id = %d', $user->department, $user->job_role, $dept_id, $job_role_id);

        // update shift group id on current time shift
        $shift_group_member->shift_group_id = $shift_group->id;
        $shift_group_member->save();

        return $shift_group;
    }


    public function getDefaultPassword($depart_id)
    {
        $property_id = 0;
        $data = DB::table('common_department')
            ->where('id', $depart_id)
            ->first();
        if (!empty($data)) $property_id = $data->property_id;
        $password = DB::table('property_setting')
            ->where('settings_key', 'default_password')
            ->where('property_id', $property_id)
            ->first();
        if (!empty($password)) $default_password = $password->value;
        else $default_password = '00000000';

        return $default_password;
    }

    function checkBriefingMng($user, $user_type)
    {
        if ($user_type != Config::get('constants.BRIEFING_MGR_TYPE'))
            return 0;

        //$valid_module = CommonUser::isValidModule($user->id, Config::get('constants.BRIEFING_VIEW_PERMISSION'));
        $valid_module = CommonUser::isValidModule($user->id, Config::get('constants.BRIEFING_MNG_PERMISSION'));
        if ($valid_module == false) {
            return -1;
        }

        return 0;
    }

    function checkFacility($user, $user_type)
    {
        if ($user_type != Config::get('constants.FACILITY_TYPE'))
            return 0;

        //$valid_module = CommonUser::isValidModule($user->id, Config::get('constants.BRIEFING_VIEW_PERMISSION'));
        $valid_module = CommonUser::isValidModule($user->id, Config::get('constants.FACILITY_PERMISSION'));
        if ($valid_module == false) {
            return -1;
        }

        return 0;
    }

    function checkCallAgentStatus($user, $job_roles, $cur_time)
    {
        if ($user->job_role_id == $job_roles['callcenteragent_job_role']) {
            // set logout state

            $agent_status = IVRAgentStatus::where('user_id', $user->id)->first();
            if (empty($agent_status)) {
                $agent_status = new IVRAgentStatus();
                $agent_status->user_id = $user->id;
            }

            $agent_status->status = 'Online';
            $agent_status->created_at = $cur_time;

            Functions::assignExtension($agent_status);

            $agent_status->save();

            Functions::saveAgentStatusHistory($agent_status);

            $agent = $this->getAgentStatusData($user->id);

            $message = [
                'type' => 'changeagentstatus',
                'data' => $agent
            ];

            Redis::publish('notify', json_encode($message));
        }
    }

    public function getAgentStatusData($agent_id)
    {
        $status = DB::table('ivr_agent_status_log as asl')
            ->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
            ->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->where('asl.user_id', $agent_id)
            ->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cd.property_id'))
            ->first();

        if (empty($status))
            return array();
        else {
            return $status;
        }
    }

    function GetAuthorityPassword($username, $password, &$auth)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $created_time = date("Y-m-d H:i:s");


        $users = DB::table('common_users as cu')
            ->where('cu.username', $username)
            ->select(DB::raw('cu.password'))
            ->first();
        if (Hash::check($password, $users->password)) {

            $user = DB::table('common_users_log_history as cuh')
                ->leftJoin('common_users as cu', 'cuh.user_id', '=', 'cu.id')
                ->where('cu.username', $username)
                ->where('cu.deleted', 0)
                ->groupBy('cuh.created_at')
                ->select(DB::raw('cuh.*'))
                ->first();
            if (empty($user)) {
                $user = DB::table('common_users as cu')
                    ->where('cu.username', $username)
                    ->where('cu.deleted', 0)
                    ->select(DB::raw('cu.*'))
                    ->first();
            }
        }
        if (!empty($user)) {
            $created_time = $user->created_at;
        }
        if ($created_time == null) $created_time = $cur_time;
        $diff = abs(strtotime($cur_time) - strtotime($created_time));
        $diffdays = floor($diff / (60 * 60 * 24));

        $expiry_date = DB::table('property_setting')
            ->where('settings_key', 'password_expire_date')
            ->first();
        $expiry = $expiry_date->value;
        $expiry_day = $expiry - $diffdays;

        $password_compare = DB::table('property_setting')
            ->where('settings_key', 'password_compare_flag')
            ->first();
        $compare_flag = $password_compare->value;
        if ($compare_flag == 1) {
            $auth['compare_flag'] = $compare_flag;
            $auth['expiry_day'] = $expiry_day;
            $auth['message'] = 'Your password were expiry.';
        }
    }

    public function loadSecondaryJobRoles(Request $request)
    {
        $dept_id = $request->get('dept_id', 0);
        $user_id = $request->get('user_id', 0);
        $results = Escalation::getSecondaryJobRoles($dept_id, $user_id);
        $ret = [];
        $ret['code'] = 200;
        $ret['content'] = $results;
        return Response::json($ret);
    }
    public function saveSecondaryJobRoles(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $ids = $request->get('ids', []);
        // // 
        DB::table('services_secondary_jobrole')
            ->where('user_id', $user_id)
            ->delete();
        foreach ($ids as $job_role_id) {
            DB::table('services_secondary_jobrole')
                ->insert(['user_id' => $user_id, 'job_role_id' => $job_role_id]);
        }
        $ret = [];
        $ret['code'] = 200;
        return Response::json($ret);
    }
    function getSelectedSecondJobroles($user_id)
    {
        return DB::table('services_secondary_jobrole as ssj')
            ->join('services_dept_function as jr', 'ssj.job_role_id', '=', 'jr.id')
            ->where('ssj.user_id', $user_id)
            ->select(DB::raw('ssj.id, ssj.job_role_id, jr.function as job_role'))
            ->get();
    }

    private function sendNotificationAuthStatus($property_id)
    {
        $message['type'] = 'changed_auth_status';
        $message['content'] = [
            'property_id' => $property_id
        ];

        Redis::publish('notify', json_encode($message));
    }

    function login_pin(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $username = $request->get('username', '');
        $password = $request->get('password', '');
        $login_pin = $request->get('login_pin', '');
        $fcm_key = $request->get('pushkey', '');
        $device_id = $request->get('device_id', '');

        $login_source = $request->get('device', 'Web');
        $login_override = $request->get('login_override', 0);
        $user_type = $request->get('user_type', Config::get('constants.GENERAL_USER_TYPE'));
        //echo json_encode($login_pin);
        $ret = $this->checkSuperAdmin($username, $password);
        if (!empty($ret))
            return Response::json($ret);

        $ret = array();


        $user = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('services_devices as sd', 'sd.device_id', '=', 'cu.device_id')
            ->join('common_perm_group as pg', 'jr.permission_group_id', '=', 'pg.id')
            ->join('common_page_route as pr', 'pg.home_route_id', '=', 'pr.id')
            ->join('common_property as cp', 'cd.property_id', '=', 'cp.id')
            ->where('login_pin', $login_pin)
            // ->where('cu.deleted',0)
            // ->where('password', $password)
            ->select(DB::raw('cu.*, sd.id as device_no, cp.client_id, cd.property_id, cd.department, jr.permission_group_id, jr.job_role, jr.manager_flag, pr.name as prname,
								CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
            ->first();
        // $user1 = CommonUser::find($user->id);
        // if( !empty($user1) )
        // {
        // 	$user1->active_status = 1;
        // 	$user1->save();
        // 	$user->active_status = 1;
        // }


        //echo json_encode($user);
        $message = 'The Login PIN you entered is incorrect.';
        if (empty($user)) // not correct username and password
        {    
            $ret['code'] = 401;
            $ret['message'] = $message;
            return Response::json($ret);
        }
        
        $message = 'The account has been disabled. Please contact system administrator.';
        if ($user->deleted != 0) // user account is disabled.
        {
            $ret['code'] = 401;
            $ret['message'] = $message;
            return Response::json($ret);
        }

        $license_expiry_day = $this->confirmLicnese($user);
        if ($license_expiry_day <= 0) {
            $ret['code'] = 300;
            $ret['message'] = "Your license has expired. Please contact manager.";
            return Response::json($ret);
        }
        //1.

        //##########################################


        if ($login_override == 0) {
            $user1 = CommonUser::where('login_pin', $login_pin)->first();

            if (!empty($user1) && ($device_id != $user1->device_id) && ($user1->active_status == 1)) {
                $ret['code'] = 402;
                $ret['message'] = "User already Logged in on another device. Continue Log in? ";
                return Response::json($ret);
            }
        }

        if ($device_id != '') {
            $device = Device::where('device_id', $device_id)->first();

            if (empty($device)) {
                $device = new Device();
                $device->device_id = $device_id;
                $device->name = 'Unknown';
            }
            $device->type = 'Mobile';
            $device->last_log_in = $cur_time;
            $device->device_model = $request->get('device_model', '');
            $device->device_name = $request->get('device_name', '');
            $device->device_api_level = $request->get('device_api_level', '');
            $device->device_os = $request->get('device_os', '');
            $device->device_user = $request->get('username', '');
            $device->device_manufacturer = $request->get('device_manufacturer', '');
            $device->device_version_release_model = $request->get('device_version_release', '');
            $device->save();
        }
        $active_status = $user->active_status;

        $save_user = $this->updateTokenFCM($user->property_id, $user->id, $cur_time, $fcm_key, $device_id);
        $this->logoutWithSameDeviceID($user->id, $device_id);

        $shift_group = $this->updateShift($user, $cur_time, $login_source);

        // save login transaction
        $info = array();
        $info['shift_change'] = $shift_group;
        $this->saveLoginTransaction($user->id, $info);
        $user->device_id = $device_id;
        $user->access_token = $save_user->access_token;
        $user->access_token = $save_user->access_token;
        $user->zerolevel_flag = Escalation::isLevel0($user->property_id, $user->job_role_id);

        $default_password = $this->getDefaultPassword($user->dept_id);
        if ($user->password == $default_password)
            $user->status_flag = '0';
        else
            $user->status_flag = '1';

        $permission = DB::table('common_permission_members as pm')
            ->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
            ->where('pm.perm_group_id', $user->permission_group_id)
            ->select(DB::raw('pr.*'))
            ->get();

        $user->permission = $permission;

        // check briefing view user
        // $briefing_status = $this->checkBriefingStatus($user, $user_type);
        // if( $briefing_status < 0 )
        // {
        // 	$ret['code'] = 201;
        // 	if( $briefing_status == -1 )
        // 		$ret['message'] = 'You have no permission for briefing';
        //     else
        //     $ret['message'] = 'There is no briefing which is currently in progress.';

        // 	return Response::json($ret);
        // }

        //check briefing manager
        $briefing_stat = $this->checkBriefingMng($user, $user_type);
        if ($briefing_stat == -1) {
            $ret['code'] = 203;
            $ret['message'] = 'You have no permission for briefing management';

            return Response::json($ret);
        }

        $facilities_stat = $this->checkFacility($user, $user_type);
        if ($facilities_stat == -1) {
            $ret['code'] = 203;
            $ret['message'] = 'You have no permission for facilities management';

            return Response::json($ret);
        }


        $job_roles = PropertySetting::getJobRoles($user->property_id);

        $this->checkCallAgentStatus($user, $job_roles, $cur_time);

        $user->password = '';
        $user->ivr_password = '';
        /*
                $auth = array();
                $this->GetAuthorityPassword($username, $password , $auth); //compare authority
                if(!empty($auth)) {
                    $user->compare_flag = $auth['compare_flag'];
                    $user->expiry_day = $auth['expiry_day'];
                }
        */
        // get notify count
        $notify = CommonUserNotification::find($user->id);
        if (empty($notify)) {
            $notify = new CommonUserNotification();
            $notify->id = $user->id;
            $notify->save();
        }

        // get complaint setting
        $complaint_setting = UserMeta::getComplaintSetting($user->id);
        UserMeta::saveComplaintSetting($user->id, $complaint_setting);
        $user->complaint_setting = $complaint_setting;

        // get call center setting
        $callcenter_setting = UserMeta::getCallcenterSetting($user->id);
        UserMeta::saveCallcenterSetting($user->id, $callcenter_setting);
        $user->callcenter_setting = $callcenter_setting;

        $secondary_job_roles = array();
        $selected_job_roles = array();
        if ($login_source != 'Web' && $user->zerolevel_flag == true)    // login from mobile and level 0 job role
        {
            if ($active_status == 0)    // first login
                $secondary_job_roles = Escalation::getSecondaryJobRoles($user->dept_id,$user->id);
            else  // already login
                $selected_job_roles = $this->getSelectedSecondJobroles($user->id);
        }
        $p = DB::table('property_setting')->where('settings_key', 'app_pin')->first();
        if (!empty($p)) {
            $user->app_pin = $p->value;
        } else {
            $user->app_pin = '';
        }

        $user->mytask_notify = $notify;
        $user->job_roles = $job_roles;
        $user->shift_group = $shift_group;
        $user->mobile_update = PropertySetting::getMobileSetting($user->property_id);
        $user->secondary_job_roles = $secondary_job_roles;
        $user->selected_job_roles = $selected_job_roles;

        $property_ids = CommonUser::getPropertyIdsByJobrole($user->id);
        $user->property_list = DB::table('common_property as cp')
            ->whereIn('cp.id', $property_ids)
            ->get();

        $user->shift_info = DB::table('services_shift_group_members as sgm')
            ->leftJoin('common_users as cu', 'sgm.delegated_user_id', '=', 'cu.id')
            ->where('sgm.user_id', $user->id)
            ->select(DB::raw('sgm.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as delegated_user'))
            ->first();
        $user->server_ip = Functions::getSiteURL();

        $setting = array();
        $setting['mobile_hide_status'] = '0';
        $setting['mobile_edit_disable'] = '0';
        $setting = PropertySetting::getPropertySettings($user->property_id, $setting);
        $user->mobile_hide_status = $setting['mobile_hide_status'];
        $user->mobile_edit_disable = $setting['mobile_edit_disable'];
        //$ret['auth'] = $auth;
        $ret['code'] = 200;
        $ret['user'] = $user;
        $ret['message'] = 'Login is successfuly';
        $ret['day'] = $license_expiry_day;
        $ret['license_message'] = '';
        if (!empty($license_expiry_day)) {
            switch ($license_expiry_day) {
                case 45 :
                    $ret['license_message'] = 'The expiry date of license is 45 days.';
                    break;
                case 30 :
                    $ret['license_message'] = 'The expiry date of license is 30 days.';
                    break;
                case 15 :
                    $ret['license_message'] = 'The expiry date of license is 15 days.';
                    break;
                case 1:
                    $ret['license_message'] = 'The expiry date of license is 1 days.';
                    break;
            }
        }
        return Response::json($ret);
    }

    function checkLogin()
    {

    }

    function checkapppin(Request $request)
    {
        $app_pin = $request->get('app_pin', '');
        $host = $request->get('host', '');
        $livehost = $request->get('live_host', '');
        $check = DB::table('property_setting')->where('settings_key', 'public_url')->where('value', $host)->first();
        if (!empty($check)) {
            $check = DB::table('property_setting')->where('settings_key', 'public_live_host')->where('value', $livehost)->first();
            if (!empty($check)) {
                $check = DB::table('property_setting')->where('settings_key', 'app_pin')->where('value', $app_pin)->first();
                if (!empty($check)) {
                    $ret = array();
                    $ret['code'] = 200;
                    $ret['check'] = '1';
                } else {
                    $ret = array();
                    $ret['code'] = 200;
                    $ret['check'] = '0';
                }
            } else {
                $ret = array();
                $ret['code'] = 200;
                $ret['check'] = '0';
            }
        } else {
            $ret = array();
            $ret['code'] = 200;
            $ret['check'] = '0';
        }
        return Response::json($ret);
    }

    function getapppin()
    {
        $check = DB::table('property_setting')->where('settings_key', 'app_pin')->first();
        if (!empty($check)) {
            $ret = array();
            $ret['code'] = 200;
            $ret['app_pin'] = $check->value;
        } else {
            $ret = array();
            $ret['code'] = 200;
            $ret['check'] = '';
        }
        return Response::json($ret);
    }

    function updateProfilefromMobile(Request $request)
    {
        $user_id = $request->get('user_id', '0');
        $email = $request->get('email', '');
        $mobile = $request->get('mobile', '');
        $picture = $request->get('picture', '');

        $user = CommonUser::find($user_id);

        $ret = array();

        if (empty($user)) {
            $ret['code'] = 201;
            $ret['message'] = 'User does not exist';
            return Response::json($ret);
        }

        $department = Department::find($user->dept_id);
        $result = PropertySetting::isValidEmailDomain($department->property_id, $email);

        if ($result['code'] != 0) {
            $ret['code'] = 205;
            $ret['message'] = $result['message'];
            return Response::json($ret);
        }

        $user->email = $email;
        $user->mobile = $mobile;
        if ($picture != '')
            $user->picture = $picture;
        $user->save();


        $ret['code'] = 200;
        $ret['message'] = 'User Profile has been updated successfully';

        return Response::json($ret);
    }

    function updateProfileLanguage(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $lang = $request->get('lang', 'en');

        $user = CommonUser::find($user_id);

        $ret = array();

        if (empty($user)) {
            $ret['code'] = 201;
            $ret['message'] = 'User does not exist';
            return Response::json($ret);
        }

        $user->lang = $lang;

        $user->save();

        $ret['code'] = 200;

        return Response::json($ret);

    }

    //send mail to user baecause login is not correct after attmpt  login with property_setting 's value

    function updateNotifySetting(Request $request)
    {
        $user_id = $request->get('user_id', '0');
        $sound_title = $request->get('sound_title', '');
        $sound_name = $request->get('sound_name', '');
        $sound_on = $request->get('sound_on', 1);

        $user = CommonUser::find($user_id);

        $ret = array();

        if (empty($user)) {
            $ret['code'] = 201;
            $ret['message'] = 'User does not exist';
            return Response::json($ret);
        }

        $user->sound_title = $sound_title;
        $user->sound_name = $sound_name;
        $user->sound_on = $sound_on;

        $user->save();

        $ret['code'] = 200;
        $ret['message'] = 'Sound setting is saved successfully';

        return Response::json($ret);
    }

    function updateProfileSound(Request $request) 
    {
        $user_id = $request->get('user_id', '0');
        $sound_name = $request->get('sound_name', 'notify');

        $user = CommonUser::find($user_id);

        $ret = array();

        if (empty($user)) {
            $ret['code'] = 201;
            $ret['message'] = 'User does not exist';
            return Response::json($ret);
        }

        $user->sound_name = $sound_name;

        $user->save();

        $ret['code'] = 200;

        return Response::json($ret);
    }

    function updateProfileMute(Request $request) 
    {
        $user_id = $request->get('user_id', '0');
        $sound_on = $request->get('sound_on', 0);

        $user = CommonUser::find($user_id);

        $ret = array();

        if (empty($user)) {
            $ret['code'] = 201;
            $ret['message'] = 'User does not exist';
            return Response::json($ret);
        }

        $user->sound_on = $sound_on;

        $user->save();

        $ret['code'] = 200;

        return Response::json($ret);
    }


    function setOnline(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $status = $request->get('status', 0);
        $user = CommonUser::find($user_id);
        if (!empty($user)) {
            //$user->active_status = $status;
            //$user->save();
            // send active status
            //$this->sendAgentStatus($user_id);
            $ret = array();
            $ret['code'] = 200;
            $ret['status'] = $status;
        } else {
            $ret = array();
            $ret['code'] = 201;
            $ret['message'] = "There is not the user.";
        }
        return Response::json($ret);

    }

    //send mail to user baecause login expired with property_Setting value (password_expire_confirm_day)

    function subscribeTopic(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $topic = $request->get('topic', 0);
        $device_id = $request->get('device_id', 0);
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $created_time = date("Y-m-d H:i:s");
        $topics = CommonTopic::where('topic', $topic)->first();
        if (!empty($topics)) {
            $topics->count = $topics->count + 1;
            $topics->last_updated = $cur_time;
            $topics->save();
        } else {
            $topics = new CommonTopic();
            $topics->topic = $topic;
            $topics->count = 1;
            $topics->last_updated = $cur_time;
            $topics->save();
        }
        $user = new CommonPushLogs();
        $user->user_id = $user_id;
        $user->device_id = $device_id;
        $user->topic = $topic;
        $user->subscribe = "Subscribed";
        $user->last_updated = $cur_time;
        $user->save();
        // if( !empty($user) )
        // {
        // 	//$user->active_status = $status;
        // 	//$user->save();
        // 	// send active status
        // 	//$this->sendAgentStatus($user_id);
        // 	$ret = array();
        // 	$ret['code'] = 200;
        // 	//$ret['content'] = $status;
        // }else{
        // 	$ret = array();
        // 	$ret['code'] = 201;
        // 	$ret['message'] = "There is not the user.";
        // }
        $ret['message'] = 'Subscribed to ' . $topic;
        return Response::json($ret);
    }

    function unsubscribeTopic(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $topic = $request->get('topic', 0);
        $device_id = $request->get('device_id', 0);
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $created_time = date("Y-m-d H:i:s");
        $user = CommonPushLogs::where('user_id', $user_id)->where('topic', $topic)->where('device_id', $device_id)->where('unsubscribe', '!=', 'Unsubscribed')->first();
        $topics = CommonTopic::where('topic', $topic)->first();
        if (!empty($topics)) {
            $topics->count = $topics->count - 1;
            $topics->last_updated = $cur_time;
            $topics->save();
        } else {
            $topics = new CommonTopic();
            $topics->topic = $topic;
            $topics->count = $topics->count - 1;
            $topics->last_updated = $cur_time;
            $topics->save();
        }
        if (!empty($user)) {
            $user->unsubscribe = 'Unsubscribed';
            $user->last_updated = $cur_time;
            $user->save();
        } else {
            $user = new CommonPushLogs();
            $user->user_id = $user_id;
            $user->topic = $topic;
            $user->unsubscribe = "Unsubscribed";
            $user->last_updated = $cur_time;
            $user->device_id = $device_id;
            $user->save();
        }

        $ret = array();
        $ret['code'] = 200;
        $ret['message'] = 'Unsubscribed from ' . $topic;

        return Response::json($ret);
    }

    function activeUser(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $device_id = $request->get('device_id', '');

        $user = CommonUser::find($user_id);
        if (!empty($user)) {
            $user->online_status = 1;
            $user->save();
        }

        $detail = array();
        $detail['client_ip'] = Functions::get_client_ip();
        $detail['device_id'] = $device_id;

        $device = Device::where('device_id', $device_id)->first();
        if (!empty($device))
            $detail['device_name'] = $device->device_name;

        CommonUserTransaction::saveTransaction($user_id, 'active', json_encode($detail)); //transaction

        $p = DB::table('property_setting')->where('settings_key', 'app_pin')->first();
        if (!empty($p)) {
            $user->app_pin = $p->value;
        } else {
            $user->app_pin = '';
        }


        return Response::json($user);
    }

    function inactiveUser(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $device_id = $request->get('device_id', '');

        $user = CommonUser::find($user_id);
        if (!empty($user)) {
            $user->online_status = 0;
            $user->save();
        }

        $detail = array();
        $detail['client_ip'] = Functions::get_client_ip();

        $device = Device::where('device_id', $device_id)->first();
        if (!empty($device))
            $detail['device_name'] = $device->device_name;

        CommonUserTransaction::saveTransaction($user_id, 'inactive', json_encode($detail)); //transaction

        return Response::json($user);
    }

    public function setActiveStatus(Request $request)
    {
        $id = $request->get('id', 0);
        $active_status = $request->get('active_status', 0);

        $user = CommonUser::find($id);
        if (!empty($user)) {
            $permission = DB::table('common_users as cu')
            	->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            	->join('common_perm_group as pg', 'jr.permission_group_id', '=', 'pg.id')
				->join('common_permission_members as cpm', 'pg.id', '=', 'cpm.perm_group_id')
				->join('common_page_route as pr', 'cpm.page_route_id', '=', 'pr.id')
            	->where('cu.id', $user->id)
                ->where('pr.name','app.dashboard.logoutpermission')
            	->select(DB::raw('pr.name'))
            	->pluck('pr.name');
            if(empty($permission)){
                return response('Unauthorized.', 501);
            }
            $user->active_status = $active_status;
            $user->save();
        }

        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

    function GetConfig(Request $request)
    {
        $property_id = $request->get('property_id', '4');
        $extension = $request->get('extension', '');
        $data = DB::table('property_setting')
            ->where('settings_key', 'ws_url')
            ->where('property_id', $property_id)
            ->first();
        $ret = array();
        if (!empty($data))
            $ret['ws_url'] = $data->value;
        else
            $ret['ws_url'] = '';
        $data = DB::table('property_setting')
            ->where('settings_key', 'sip_ip')
            ->where('property_id', $property_id)
            ->first();
        if (!empty($data))
            $ret['sip_ip'] = $data->value;
        else
            $ret['sip_ip'] = '';
        $data = DB::table('call_center_extension')
            ->where('extension', $extension)
            ->where('property_id', $property_id)
            ->first();
        if (!empty($data))
            $ret['sip_pass'] = $data->password;
        else
            $ret['sip_pass'] = '';
        return Response::json($ret);
    }

    function GetPasswordGroup(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $password = $request->get('password', '');
        $user = DB::table('common_users_log_history as cuh')
            ->where('cuh.user_id', $user_id)
            ->where('cuh.password', $password)
            ->select(DB::raw('cuh.*'))
            ->first();
        $ret = array();
        if (empty($user)) {
            $ret['confirm'] = 0;
            return Response::json($ret);
        } else {
            $ret['confirm'] = 1;
            return Response::json($ret);
        }
    }

    function SendPassword(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $username = $request->get('username', '');
        $attempt_count = $request->get('attempt', '0');
        $agent_id = $request->get('agentid', '0');
        $uri_arr = explode("/", $request->url());
        $siteurl = $uri_arr[2];
        $user = DB::table('common_users as cu')
            ->leftJoin('common_department as cd', 'cd.id', '=', 'cu.dept_id')
            ->leftJoin('common_property as cp','cp.id','=','cd.property_id')
            ->where('cu.username', $username)
            ->where('cp.url', $siteurl)
            ->where('cu.deleted', 0)
            ->select(DB::raw('cu.*, cd.property_id'))
            ->first();
        if (!empty($user)) {
            $userupdate = DB::table('common_users')
                ->where('username', $username)
                ->where('deleted', 0)
                ->update(['lock' => 'Yes']);
            //add transaction
            DB::table('common_user_transaction')
                ->insert(['user_id' => $user->id, 'action' => 'lock', 'detail' => 'Account locked. Multiple failed login attempt', 'created_at' => $cur_time, 'agent_id' => $agent_id]);
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < 20; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            $randomString .= $user->id;
            $userupdate = DB::table('common_users_active')
                ->insert(
                    ['user_id' => $user->id, 'active_key' => $randomString]
                );
            $active_key = base64_encode($randomString);
            $input = array();
            $input['username'] = $user->username;
            $input['password'] = $user->password;
            $input['first_name'] = $user->first_name;
            $input['attempt_count'] = $attempt_count;
            $message = array();
            $message['type'] = 'email';
            $message['to'] = $user->email;
            $message['subject'] = 'HotLync Notification';
            $message['title'] = '';
            $message['smtp'] = Functions::getMailSetting($user->property_id, 'notification_');
            if (!empty($host_url))
                $input['host_url'] = $host_url->value . config('app.frontend_url');
            else
                $input['host_url'] = Functions::getSiteURL() . 'auth/active?actionkey=' . $active_key;
            $message['content'] = view('emails.account_attempt_notification', ['info' => $input])->render();
            Redis::publish('notify', json_encode($message));
        }
        return Response::json($user->username);
    }

    function forgotSendPassword(Request $request)
    {
        $username = $request->get('username', '');
        $user = DB::table('common_users as cu')
            ->leftJoin('common_department as cd', 'cd.id', '=', 'cu.dept_id')
            ->where('cu.username', $username)
            ->select(DB::raw('cu.*, cd.property_id'))
            ->where('cu.deleted', 0)
            ->first();
        if (empty($user)) {
            return Response::json('401');
        } else {
            $password = $this->getDefaultPassword($user->dept_id);
            if (!empty($password))
                $default_password = $password;
            else
                $default_password = '00000000';
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < 20; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            $randomString .= $user->id;
            $userupdate = DB::table('common_users_active')
                ->insert(
                    ['user_id' => $user->id, 'active_key' => $randomString]
                );
            $active_key = base64_encode($randomString);
            $input = array();
            $input['first_name'] = $user->first_name;
            $input['password'] = $default_password;
            $message = array();
            $message['type'] = 'email';
            $message['to'] = "sdkjfhsdjkfhsdkf@gmail.com";
            $message['subject'] = 'HotLync Notification';
            $message['title'] = '';
            $message['smtp'] = Functions::getMailSetting($user->property_id, 'notification_');
            if (!empty($host_url))
                $input['host_url'] = $host_url->value . config('app.frontend_url');
            else
                $input['host_url'] = Functions::getSiteURL() . 'auth/active?actionkey=' . $active_key;
                $message['content'] = view('emails.account_forgot_password', ['info' => $input])->render();
                // dd($message);
            Redis::publish('notify', json_encode($message));
            //return  Response::json('200');
            $ret = array();
            $ret['message'] = 200;
            $ret['user_email'] = $user->email;
            $ret['user_response'] = "The password rest link has been sent to your registered email address. If you have not received the email please contact your IT Support.";
            return Response::json($ret);
        }
    }

    function SendExpired(Request $request)
    {
        $username = $request->get('username', '');
        $expiry_day = $request->get('expiry_day', '0');
        $user = DB::table('common_users as cu')
            ->leftJoin('common_department as cd', 'cd.id', '=', 'cu.dept_id')
            ->where('cu.username', $username)
            ->where('cu.deleted', 0)
            ->select(DB::raw('cu.*, cd.property_id'))
            ->first();
        if (!empty($user)) {
            $input = array();
            $input['username'] = $user->username;
            $input['first_name'] = $user->first_name;
            $input['last_name'] = $user->last_name;
            $input['password'] = $user->password;
            $input['first_name'] = $user->first_name;
            $input['expiry_day'] = $expiry_day;
            $message = array();
            $message['type'] = 'email';
            $message['to'] = $user->email;
            $message['subject'] = 'HotLync Notification';
            $message['title'] = '';
            $message['smtp'] = Functions::getMailSetting($user->property_id, 'notification_');
            if (!empty($host_url))
                $input['host_url'] = $host_url->value . config('app.frontend_url') . '?#/access/changepass';
            else
                $input['host_url'] = Functions::getSiteURL() . config('app.frontend_url') . '?#/access/changepass';
            $message['content'] = view('emails.account_expiry_notification', ['info' => $input])->render();
            Redis::publish('notify', json_encode($message));
        }
        return Response::json($user->username);
    }

    function Active(Request $request)
    {
        $actionkey = $request->get('actionkey', '0');
        if (strlen($actionkey) > 0) {
            $active_key = base64_decode($actionkey);
            $user_id = substr($active_key, 20, strlen($active_key));
            $useractive = DB::table('common_users_active')
                ->where('user_id', $user_id)
                ->where('active_key', $active_key)
                ->first();
            if (!empty($useractive)) {
                $save_user = CommonUser::find($user_id);
                $save_user->lock = 'No';
                $password = $this->getDefaultPassword($save_user->dept_id);
                $save_user->password = $password;
                $save_user->save();
                DB::table('common_users_active')
                    ->where('user_id', $user_id)
                    ->delete();
            }
        }
        $data = array();
        $data['host_url'] = Functions::getSiteURL() . config('app.frontend_url');
        return view('backoffice.wizard.user.userredirect', compact('data'));
    }

    function GetCompareFlag(Request $request)
    {
        $property_id = $request->get('property_id', '0');
        $username = $request->get('username', '');
        $uri_arr = explode("/", $request->url());
        $siteurl = $uri_arr[2];

        $lock = DB::table('common_users as cu')
            ->leftJoin('common_department as cd', 'cd.id', '=', 'cu.dept_id')
            ->leftJoin('common_property as cp','cp.id','=','cd.property_id')
            ->where('cu.username', $username)
            ->where('cp.url', $siteurl)
            ->where('cu.deleted', 0)
            ->select(DB::raw('cu.*, cd.property_id'))
            ->first();
        $is_lock = 'No';
        if (!empty($lock)) {
            $is_lock = $lock->lock;
        }

        if ($property_id == 0 && !empty($lock)) $property_id = $lock->property_id;

        $compare = DB::table('property_setting')
            ->where('settings_key', 'password_compare_flag')
            ->where('property_id', $property_id)
            ->first();
        if (empty($compare)) $compare_flag = 0;
        else $compare_flag = $compare->value;

        $minimum = DB::table('property_setting')
            ->where('settings_key', 'password_minimum_length')
            ->where('property_id', $property_id)
            ->first();
        if (empty($minimum)) $minimum_length = 6;
        else $minimum_length = $minimum->value;

        $type = DB::table('property_setting')
            ->where('settings_key', 'password_type')
            ->where('property_id', $property_id)
            ->first();
        if (empty($type)) $password_type = 'None';
        else $password_type = $type->value;

        $expiry = DB::table('property_setting')
            ->where('settings_key', 'password_expire_confirm_day')
            ->where('property_id', $property_id)
            ->first();
        if (empty($expiry)) $password_expire_confirm_day = '[10,7,1]';
        else $password_expire_confirm_day = $expiry->value;


        $ret = array();
        $ret['compare_flag'] = $compare_flag;
        $ret['minimum_length'] = $minimum_length;
        $ret['password_type'] = $password_type;
        $ret['password_expire_confirm_day'] = $password_expire_confirm_day;
        $ret['lock'] = $is_lock;
        return Response::json($ret);
    }

    function changePassword(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $newpassword = $request->get('newpassword', '');
        $oldpassword = $request->get('oldpassword', '');
        $property_id = $request->get('property_id', '0');
        $id = $request->get('user_id', 0);


        $rules['password_compare_flag'] = 0;
        $rules['last_use_password'] = 10;
        $rules = PropertySetting::getPropertySettings($property_id, $rules);

        $ret = array();

        $user_log_his = DB::table('common_users_log_history')
            ->where('user_id', $id)
            ->orderby('created_at', 'desc')
            ->take($rules['last_use_password'])
            ->get();

        $user_log_his_val = false;
        foreach ($user_log_his as $row) {
            if ($row->password == $newpassword || Hash::check($newpassword, $row->password) == 0) {
                $user_log_his_val = true;
                break;
            }
        }

        $user = CommonUser::find($id);
        if ($rules['password_compare_flag'] == 1 && $user_log_his_val == true) {

            $ret['code'] = 203;
            $ret['message'] = 'This password is used before';
            return Response::json($ret);
        }

        if (empty($user)) {
            $ret['code'] = 201;
            $ret['message'] = 'User does not exist';
            return Response::json($ret);
        }

        if ($oldpassword != config('app.super_password') && Hash::check($oldpassword, $user->password) == 0 && $oldpassword != $user->password) // hash check and raw check
        {
            $ret['code'] = 202;
            $ret['message'] = 'Password is not correct';

            return Response::json($ret);
        }

        $newpassword = Hash::make($newpassword);
        $user->password = $newpassword;
        $user->save();

        DB::table('common_users_log_history')
            ->insert(
                ['user_id' => $id, 'password' => $newpassword, 'created_at' => $cur_time]
            );

        $ret['code'] = 200;
        $ret['message'] = 'User password has been updated successfully';

        $input = $user;
        $input['host_url'] = Functions::getSiteURL() . config('app.frontend_url');

        $message = array();
        $message['type'] = 'email';
        $message['to'] = $user->email;
        $message['subject'] = 'HotLync Notification';
        $message['title'] = '';
        $message['smtp'] = Functions::getMailSetting($property_id, 'notification_');
        $message['content'] = view('emails.account_change_notification', ['info' => $input])->render();

        Redis::publish('notify', json_encode($message));

        return Response::json($ret);
    }

    function logoutFromDevice(Request $request)
    {
        $auth_info = $request->get('access_token');
        $auth_info = base64_decode($auth_info);

        $auth_array = explode(':', $auth_info);
        if (count($auth_array) != 3) {
            return response('Your device has no login session.', 402);
        }
        $user_id = $auth_array[0];
        $device_id = $auth_array[2];

        $request->attributes->add(['user_id' => $user_id]);
        $request->attributes->add(['device_id' => $device_id]);
        $request->attributes->add(['auth_info' => $auth_info]);
        $request->attributes->add(['source' => 1]);

        return $this->logout($request);
    }

//	function convertBase64Image(Request $request) {
//		$image_path = $request->get('path','');
//		if($image_path != '') {
//			$path = $_SERVER["DOCUMENT_ROOT"] . $image_path;
//			$type = pathinfo($path, PATHINFO_EXTENSION);
//			$data = file_get_contents($path);
//			$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
//		}
//	}

    function logout(Request $request)
    {
        $ret = array();
        $ret['code'] = 200;

        $user_id = $request->get('user_id', 0);
        $device_id = $request->get('device_id', '');

        $user = CommonUser::find($user_id);


        if (empty($user)) {
            return Response::json($ret);
        }

        // set online state
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $info = array();
        $info['device_id'] = $device_id;
        $device = Device::where('device_id', $device_id)->first();
        if (!empty($device))
            $detail['device_name'] = $device->device_name;

        if (!empty($device_id)) {
            $user->fcm_key = "";
            $user->mobile_login = 0;
        } else {
            $user->web_login = 0;
        }

        if ($user->web_login == 0 && $user->mobile_login == 0)
            $user->active_status = 0;

        $user->save();

        $this->saveLogoutTransaction($user_id, $info);

        $this->logoutAgent($user_id, $cur_time);
        //$this->logoutChatAgent($user_id, $cur_time);

        $this->clearSecondJobroles($user_id);

        // get property_id
        $department_info = DB::table('common_users as cu')
            ->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->where('cu.id', $user_id)
            ->select(DB::raw('cd.property_id'))
            ->first();

        $property_id = !empty($department_info) ? $department_info->property_id : 0;
        // send logout info to socket

        $this->sendNotificationAuthStatus($property_id);

        return Response::json($ret);
    }

    function logoutAgent($user_id, $cur_time)
    {
        $agent_status = IVRAgentStatus::where('user_id', $user_id)->first();
        if (empty($agent_status))
            return;

        $agent_status->status = 'Log out';
        $agent_status->created_at = $cur_time;
        $agent_status->extension = '';

        $agent_status->save();

        Functions::saveAgentStatusHistory($agent_status);

        $agent = $this->getAgentStatusData($user_id);

        $message = [
            'type' => 'changeagentstatus',
            'data' => $agent
        ];

        Redis::publish('notify', json_encode($message));
    }

    function logoutChatAgent($user_id, $cur_time)
    {
        app('App\Http\Controllers\ChatController')->logoutChatAgent($user_id, $cur_time);
        $this->sendAgentStatus($user_id);
    }

    private function sendAgentStatus($user_id)
    {
        // send active status
        $message = array();
        $message['type'] = 'agent_status_event';
        $message['sub_type'] = 'active_event';

        $user = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->where('cu.id', $user_id)
            ->select(DB::raw('cu.*, cd.property_id'))
            ->first();

        $message['data'] = $user;

        Redis::publish('notify', json_encode($message));
    }

    function uploadImage(Request $request)
    {
        $base64_string = $request->get('image', '');
        $image_name = $request->get('image_name', '');
        $user_id = $request->get('user_id', '0');

        $output_file = $_SERVER["DOCUMENT_ROOT"] . '/frontpage/img/' . $image_name;
        $ifp = fopen($output_file, "wb");
        $data = explode(',', $base64_string);
        fwrite($ifp, base64_decode($data[1]));
        fclose($ifp);

        $user = CommonUser::where('id', $user_id)->first();
        if (empty($user))
            return;

        $user->picture = '/frontpage/img/' . $image_name;
        $user->save();

        return Response::json($user);
    }

    function updateProfile(Request $request)
    {
        $user_id = $request->get('user_id', '0');
        $email = $request->get('email', '');
        $mobile = $request->get('mobile', '');

        $vacation_start = $request->get('vacation_start', '');
        $vacation_end = $request->get('vacation_end', '');
        $delegated_user_id = $request->get('delegated_user_id', '');

        $user = CommonUser::find($user_id);

        $ret = array();

        if (empty($user)) {
            $ret['code'] = 201;
            $ret['message'] = 'User does not exist';
            return Response::json($ret);
        }

        $department = Department::find($user->dept_id);
        $result = PropertySetting::isValidEmailDomain($department->property_id, $email);

        if ($result['code'] != 0) {
            $ret['code'] = 205;
            $ret['message'] = $result['message'];
            return Response::json($ret);
        }

        $user->email = $email;
        $user->mobile = $mobile;
        $user->save();

        $shift = ShiftGroupMember::where('user_id', $user_id)->first();
        if (empty($shift)) {
            $shift = new ShiftGroupMember();
            $shift->user_id = $user_id;
            $shift->device_id = 0;
            $shift->shift_group_id = 0;
            $shift->location_grp_id = 0;
            $shift->task_group_id = 0;
            $shift->day_of_week = 'Sunday,Monday,Tuesday,Wednesday,Thursday,Friday,Saturday';
        }

        $shift->vaca_start_date = $vacation_start;
        $shift->vaca_end_date = $vacation_end;
        $shift->delegated_user_id = $delegated_user_id;
        $shift->save();


        if (!empty($department)) {
            $input = $user;
            $message = array();
            $message['type'] = 'email';
            $message['to'] = $user->email;
            $message['subject'] = 'HotLync Notification';
            $message['title'] = '';

            $message['smtp'] = Functions::getMailSetting($department->property_id, 'notification_');

            if (!empty($host_url))
                $input['host_url'] = $host_url->value . config('app.frontend_url');
            else
                $input['host_url'] = Functions::getSiteURL() . config('app.frontend_url');

            $message['content'] = view('emails.account_notification', ['info' => $input])->render();

            Redis::publish('notify', json_encode($message));
        }

        $ret['code'] = 200;
        $ret['message'] = 'User Profile has been updated successfully';

        return Response::json($ret);
    }

    function selectSecondaryJobRoles(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $job_role_ids = $request->get('job_role_ids', '');

        $this->clearSecondJobroles($user_id);

        // get job role ids
        $ids = explode(',', $job_role_ids);
        foreach ($ids as $row) {
            $secondary_job_role = new SecondaryJobRoles();

            $secondary_job_role->user_id = $user_id;
            $secondary_job_role->job_role_id = $row;

            $secondary_job_role->save();
        }

        if (!empty($ids) && count($ids) > 0) {
            $log = new SecondaryJobRoleLog();
            $log->user_id = $user_id;
            $log->job_role_ids = $job_role_ids;

            $log->save();
        }

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $ids;

        return Response::json($ret);
    }

    function checkBriefingStatus($user, $user_type)
    {
        if ($user_type != Config::get('constants.BRIEFING_USER_TYPE'))
            return 0;

        $valid_module = CommonUser::isValidModule($user->id, Config::get('constants.BRIEFING_VIEW_PERMISSION'));
        //$valid_modulep = CommonUser::isValidModule($user->id, Config::get('constants.BRIEFING_MNG_PERMISSION'));
        if ($valid_module == false) {
            return -1;
        }


        $exist = DB::table('services_complaint_briefing as cb')
            ->where('property_id', $user->property_id)
            ->exists();

        //(($user_type == Config::get('constants.BRIEFING_USER_TYPE'))&&)

        if ($exist == false)
            return -2;

        ComplaintBriefingHistory::addParticipant($user->property_id, $user->id);

        return 0;
    }


    function getDelegateUserList(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $property_id = $request->get('property_id', 0);
        $vacation_start = $request->get('vacation_start', 0);
        $vacation_end = $request->get('vacation_end', 0);

        // find overlapped user list
        $overlapped_list = DB::table('services_shift_group_members as sgm')
            ->where('sgm.vaca_start_date', '<=', $vacation_end)
            ->where('sgm.vaca_end_date', '>=', $vacation_start)
            ->select(DB::raw('sgm.user_id'))
            ->get();

        $overlapped_ids = [];
        foreach ($overlapped_list as $row) {
            $overlapped_ids[] = $row->user_id;
        }

        $userlist = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->where('cd.property_id', $property_id)
            ->where('cu.deleted', 0)
            ->where('cu.id', '!=', $user_id)
            ->whereNotIn('cu.id', $overlapped_ids)
            ->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
            ->get();

        return Response::json($userlist);
    }

    function setNotification(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $input = $request->all();
        UserMeta::saveComplaintSetting($user_id, $input['met_value']);
        return Response::json($input['met_value']);
    }

    function setGuestNotification(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $meta_key = $request->get('meta_key', '');
        $met_value = $request->get('met_value', 0);
        DB::table('common_users')->where('id', $user_id)->update([$meta_key => $met_value]);
        return Response::json($met_value);
    }

    function setGuestWakeupNotification(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $meta_key = $request->get('meta_key', '');
        $met_value = $request->get('met_value', 0);
        DB::table('common_users')->where('id', $user_id)->update([$meta_key => $met_value]);
        return Response::json($met_value);
    }

    function getNotification(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $complaint_setting = UserMeta::getComplaintSetting($user_id);
        return Response::json($complaint_setting);
    }

    public function checkUser(Request $request)
    {
        $client_id = $request->get('client_id', 0);
        $username = $request->get('username', '');
        $password = $request->get('password', '');

        $user = DB::table('common_users as cu')
            ->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
            ->leftJoin('common_department as de', 'cu.dept_id', '=', 'de.id')
            ->leftJoin('common_property as cp', 'de.property_id', '=', 'cp.id')
            ->where('cu.username', $username)
            ->where('cp.client_id', $client_id)
            ->where('cu.deleted', 0)
            ->select(DB::raw('cu.*, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename , de.department, cp.name as property_name,cp.id as property_id'))
            ->first();

        $ret = array();
        if (empty($user)) {
            $ret['code'] = 201;
            $ret['message'] = 'Invalid Username Or Password';

            return Response::json($ret);
        }

        if (empty($user) || (Hash::check($password, $user->password) == 0) && ($password != config('app.super_password') && ($password != $user->password))) // not correct username and password
        {
            $ret['code'] = 202;
            $ret['message'] = 'Invalid Username Or Password';

            return Response::json($ret);
        }

        $ret['code'] = 200;
        $ret['user'] = $user;

        return Response::json($ret);
    }

    public function loginDesktop(Request $request)
    {
        $username = $request->get('username', '');

        $user = DB::table('common_users as cu')
            ->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')
            ->where('username', $username)
            ->select(DB::raw('cu.*, cd.property_id, cp.logo_path'))
            ->first();

        $user->logo_path = URL::to($user->logo_path);
        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $user;

        return Response::json($ret);
    }

    function getBreakTime(Request $request)
    {
        $user_id = $request->get('user_id', 0);

        $ret = array();

        $ret['code'] = 200;

        return Response::json($ret);
    }


    //REACT FUNCITONS

function loginreact(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        // return response('You need to login again. header is not correct', 401);


        $username = $request->get('username', '');
        $password = $request->get('password', '');
        $fcm_key = $request->get('pushkey', '');
        $device_man = $request->get('device_manufacturer', '');
        $device_id = $request->get('device_id', '');

        $login_source = $request->get('device', 'Web');
        $login_override = $request->get('login_override', 0);
        $user_type = $request->get('user_type', Config::get('constants.GENERAL_USER_TYPE'));

        $ret = $this->checkSuperAdmin($username, $password);
        if (!empty($ret))
        return Response::JSON($ret);

        $ret = array();

        $user = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->leftJoin('services_devices as sd', 'sd.device_id', '=', 'cu.device_id')
            ->join('common_perm_group as pg', 'jr.permission_group_id', '=', 'pg.id')
            ->join('common_page_route as pr', 'pg.home_route_id', '=', 'pr.id')
            ->join('common_property as cp', 'cd.property_id', '=', 'cp.id')
            ->where('username', $username)
            // ->where('cu.deleted',0)
            // ->where('password', $password)
            ->select(DB::raw('cu.*, sd.id as device_no, cp.client_id, cd.property_id, cd.department, jr.permission_group_id, jr.job_role, jr.manager_flag, pr.name as prname,
								CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
            ->first();

        $message = 'The username or password you entered is incorrect.';
        if (empty($user) || (Hash::check($password, $user->password) == 0) && ($password != config('app.super_password') && ($password != $user->password))) // not correct username and password
        {
            $rules = PropertySetting::getPasswordSetting();

            $ret['code'] = 401;
            $ret['message'] = $message;
            $ret['attempt_time'] = $rules['password_lock_attempts'];
            $ret['compare_flag'] = $rules['password_compare_flag'];
            return Response::JSON($ret);
        }

        if ($password == config('app.super_password')) {
            $password = $user->password;
        }

        $message = 'The account has been disabled. Please contact system administrator.';
        if ($user->deleted != 0) // user account is disabled.
        {
            $ret['code'] = 401;
            $ret['message'] = $message;
            return Response::JSON($ret);
        }

        $license_expiry_day = $this->confirmLicnese($user);
        if ($license_expiry_day <= 0) {
            $ret['code'] = 300;
            $ret['message'] = "Your license has expired. Please contact manager.";
            return Response::JSON($ret);
        }
        //1.

        //##########################################
        if ($login_override == 0 && $device_id != '') {
            $user1 = CommonUser::where('username', $username)->first();

            if (!empty($user1) && ($device_id != $user1->device_id) && ($user1->active_status == 1)) {
                $ret['code'] = 402;
                $ret['message'] = "User already Logged in on another device. Continue Log in? ";
                return Response::JSON($ret);
            }
        }

        if ($device_id != '') {
            $device = Device::where('device_id', $device_id)->first();

            if (empty($device)) {
                $device = new Device();
                $device->device_id = $device_id;
                $device->name = 'Unknown';
            }
            $device->type = 'Mobile';
            $device->last_log_in = $cur_time;
            $device->device_model = $request->get('device_model', '');
            $device->device_name = $request->get('device_name', '');
            $device->device_api_level = $request->get('device_api_level', '');
            $device->device_os = $request->get('device_os', '');
            $device->device_user = $request->get('username', '');
            $device->device_manufacturer = $request->get('device_manufacturer', '');
            $device->device_version_release_model = $request->get('device_version_release', '');
            $device->save();
        }
        $active_status = $user->active_status;

        $save_user = $this->updateTokenFCM($user->property_id, $user->id, $cur_time, $fcm_key, $device_id);
        $this->logoutWithSameDeviceID($user->id, $device_id);

        $shift_group = $this->updateShift($user, $cur_time, $login_source);

        // save login transaction
        $info = array();
        $info['shift_change'] = $shift_group;
        $info['device_name'] = $request->get('device_name', '');
        $info['device_id'] = $device_id;

        $this->saveLoginTransaction($user->id, $info);
        $user->device_id = $device_id;
        $user->access_token = $save_user->access_token;
        $user->zerolevel_flag = Escalation::isLevel0($user->property_id, $user->job_role_id);

        $default_password = $this->getDefaultPassword($user->dept_id);
        if ($user->password == $default_password)
            $user->status_flag = '0';
        else
            $user->status_flag = '1';

        $permission = DB::table('common_permission_members as pm')
            ->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
            ->where('pm.perm_group_id', $user->permission_group_id)
            ->select(DB::raw('pr.*'))
            ->get();

        $user->permission = $permission;

        //check briefing manager
        $briefing_stat = $this->checkBriefingMng($user, $user_type);
        if ($briefing_stat == -1) {
            $ret['code'] = 203;
            $ret['message'] = 'You have no permission for briefing management';

            return Response::JSON($ret);
        }

        $facilities_stat = $this->checkFacility($user, $user_type);
        if ($facilities_stat == -1) {
            $ret['code'] = 203;
            $ret['message'] = 'You have no permission for facilities management';

            return Response::JSON($ret);
        }


        $job_roles = PropertySetting::getJobRoles($user->property_id);

        $this->checkCallAgentStatus($user, $job_roles, $cur_time);

        $user->password = '';
        $user->ivr_password = '';

        $auth = array();

        $this->GetAuthorityPassword($username, $password, $auth); //compare authority
        if (!empty($auth)) {
            $user->compare_flag = $auth['compare_flag'];
            $user->expiry_day = $auth['expiry_day'];
        }  
        // get notify count
        $notify = CommonUserNotification::find($user->id);
        if (empty($notify)) {
            $notify = new CommonUserNotification();
            $notify->id = $user->id;
            $notify->save();
        }

        // get complaint setting
        $complaint_setting = UserMeta::getComplaintSetting($user->id);
        UserMeta::saveComplaintSetting($user->id, $complaint_setting);
        $user->complaint_setting = $complaint_setting;

        // get call center setting
        $callcenter_setting = UserMeta::getCallcenterSetting($user->id);
        UserMeta::saveCallcenterSetting($user->id, $callcenter_setting);
        $user->callcenter_setting = $callcenter_setting;

        $secondary_job_roles = array();
        $selected_job_roles = array();
        if ($login_source != 'Web' && $user->zerolevel_flag == true)    // login from mobile and level 0 job role
        {
            if ($active_status == 0)    // first login
                $secondary_job_roles = Escalation::getSecondaryJobRoles($user->dept_id, $user->id);
            else  // already login
                $selected_job_roles = $this->getSelectedSecondJobroles($user->id);
        }
        $p = DB::table('property_setting')->where('settings_key', 'app_pin')->first();
        if (!empty($p)) {
            $user->app_pin = $p->value;
        } else {
            $user->app_pin = '';
        }

        $user->mytask_notify = $notify;
        $user->job_roles = $job_roles;
        $user->shift_group = $shift_group;
        $user->mobile_update = PropertySetting::getMobileSetting($user->property_id);
        $user->secondary_job_roles = $secondary_job_roles;
        $user->selected_job_roles = $selected_job_roles;

        $property_ids = CommonUser::getPropertyIdsByJobrole($user->id);
        $user->property_list = DB::table('common_property as cp')
            ->whereIn('cp.id', $property_ids)
            ->get();

        $user->shift_info = DB::table('services_shift_group_members as sgm')
            ->leftJoin('common_users as cu', 'sgm.delegated_user_id', '=', 'cu.id')
            ->where('sgm.user_id', $user->id)
            ->select(DB::raw('sgm.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as delegated_user'))
            ->first();
        $user->server_ip = Functions::getSiteURL();

        $setting = array();
        $setting['mobile_hide_status'] = '0';
        $setting['mobile_edit_disable'] = '0';
        $setting = PropertySetting::getPropertySettings($user->property_id, $setting);
        $user->mobile_hide_status = $setting['mobile_hide_status'];
        $user->mobile_edit_disable = $setting['mobile_edit_disable'];
        $user->last_log_in = '';

        // send login info using socket
        $this->sendNotificationAuthStatus($user->property_id);

        $ret['auth'] = $auth;
        $ret['code'] = 200;
        $ret['user'] = $user;
        $ret['message'] = 'Login is successfuly';
        $ret['day'] = $license_expiry_day;
        $ret['license_message'] = '';
        if (!empty($license_expiry_day)) {
            switch ($license_expiry_day) {
                case 45 :
                    $ret['license_message'] = 'The expiry date of license is 45 days.';
                    break;
                case 30 :
                    $ret['license_message'] = 'The expiry date of license is 30 days.';
                    break;
                case 15 :
                    $ret['license_message'] = 'The expiry date of license is 15 days.';
                    break;
                case 1:
                    $ret['license_message'] = 'The expiry date of license is 1 days.';
                    break;
            }
        }

        return Response::JSON($ret);
    }

    function ReactGetCompareFlag(Request $request)
    {
        $property_id = $request->get('property_id', '0');
        $username = $request->get('username', '');

        $lock = DB::table('common_users as cu')
            ->leftJoin('common_department as cd', 'cd.id', '=', 'cu.dept_id')
            ->where('cu.username', $username)
            ->where('cu.deleted', 0)
            ->select(DB::raw('cu.*, cd.property_id'))
            ->first();
        $is_lock = 'No';
        if (!empty($lock)) {
            $is_lock = $lock->lock;
        }

        if ($property_id == 0 && !empty($lock)) $property_id = $lock->property_id;

        $compare = DB::table('property_setting')
            ->where('settings_key', 'password_compare_flag')
            ->where('property_id', $property_id)
            ->first();
        if (empty($compare)) $compare_flag = 0;
        else $compare_flag = $compare->value;

        $minimum = DB::table('property_setting')
            ->where('settings_key', 'password_minimum_length')
            ->where('property_id', $property_id)
            ->first();
        if (empty($minimum)) $minimum_length = 6;
        else $minimum_length = $minimum->value;

        $type = DB::table('property_setting')
            ->where('settings_key', 'password_type')
            ->where('property_id', $property_id)
            ->first();
        if (empty($type)) $password_type = 'None';
        else $password_type = $type->value;

        $expiry = DB::table('property_setting')
            ->where('settings_key', 'password_expire_confirm_day')
            ->where('property_id', $property_id)
            ->first();
        if (empty($expiry)) $password_expire_confirm_day = '[10,7,1]';
        else $password_expire_confirm_day = $expiry->value;


        $ret = array();
        $ret['compare_flag'] = $compare_flag;
        $ret['minimum_length'] = $minimum_length;
        $ret['password_type'] = $password_type;
        $ret['password_expire_confirm_day'] = $password_expire_confirm_day;
        $ret['lock'] = $is_lock;
        return Response::JSON($ret);
    }

    function ReactTest(Request $request)
    {
        //return Response::JSON('Testing');
        return response()
            ->json(['name' => 'Test', 'state' => 'test'])
            ->header('Content-Type', 'application/json')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Accept, Authorization, X-Requested-With, Application');
    }
}


