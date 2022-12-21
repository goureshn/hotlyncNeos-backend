<?php
namespace App\Http\Controllers;

use App\Models\Common\CommonUser;
use App\Models\Common\CommonUserNotification;
use App\Models\Common\CommonUserTransaction;
use App\Models\Common\PropertySetting;
use App\Models\Common\UserMeta;
use App\Models\IVR\IVRAgentStatus;
use App\Models\Service\Device;
use App\Models\Service\Escalation;
use App\Models\Service\ShiftGroup;
use App\Models\Service\ShiftGroupMember;
use App\Modules\Functions;
use App\Modules\UUID;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use DB;
use Illuminate\Support\Facades\Hash;
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
        $device_id = $request->get('device_id', '');

        $login_source = $request->get('device', 'Web');
        $login_override = $request->get('login_override', 0);
        $user_type = $request->get('user_type', Config::get('constants.GENERAL_USER_TYPE'));

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
            ->where('username', $username)
            // ->where('cu.deleted',0)
            // ->where('password', $password)
            ->select(DB::raw('cu.*, sd.id as device_no, cp.client_id, cd.property_id, cd.department, jr.permission_group_id, jr.job_role, jr.manager_flag, pr.name as prname,
								CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
            ->first();

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
                $secondary_job_roles = Escalation::getSecondaryJobRoles($user->dept_id);
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

    private function sendNotificationAuthStatus($property_id)
    {
        $message['type'] = 'changed_auth_status';
        $message['content'] = [
            'property_id' => $property_id
        ];

        Redis::publish('notify', json_encode($message));
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

    function GetCompareFlag(Request $request)
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
        return Response::json($ret);
    }
}


