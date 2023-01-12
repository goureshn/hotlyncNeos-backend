<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Frontend\GuestserviceController;
use App\Models\Call\CarrierCharges;
use App\Models\Call\CarrierGroup;
use App\Models\Call\HotelCharges;
use App\Models\Call\TimeSlab;
use App\Models\Common\CommonUser;
use App\Models\Common\Property;
use App\Models\Common\PropertySetting;
use App\Models\Common\Room;
use App\Models\Common\UserGroup;
use App\Models\Intface\Channel;
use App\Models\Intface\Formatter;
use App\Models\Intface\Protocol;
use App\Models\Service\AgentChatHistory;
use App\Models\Service\Device;
use App\Models\Service\HskpStatus;
use App\Models\Service\LocationType;
use App\Models\Service\TaskList;
use App\Modules\Functions;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Redis;
use Response;
use Schema;

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


define("CLEANING_PENDING_NAME", 'Pending');
define("CLEANING_RUNNING_NAME", 'Cleaning');
define("CLEANING_DONE_NAME", 'Done');
define("CLEANING_DND_NAME", 'DND (Do not Disturb)');
define("CLEANING_DECLINE_NAME", 'Reject');
define("CLEANING_POSTPONE_NAME", 'Delay');
define("CLEANING_PAUSE_NAME", 'Pause');
define("CLEANING_COMPLETE_NAME", 'Inspected');

class DataController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getList(Request $request, $name)
    {
        $model = array();
        switch ($name) {
            case 'client':
                $model = DB::table('common_chain')->get();
                // $model = Chain::all();
                break;
            case 'manager':
                $model = DB::table('common_users as u')
                    ->join('common_user_group_members as ugm', 'u.id', '=', 'ugm.user_id')
                    ->join('common_user_group as ug', 'ugm.group_id', '=', 'ug.id')
                    ->where('ug.access_level', 'like', '%Manager%')
                    ->select('u.id', 'u.username as name')
                    ->get();
                break;
            case 'user':
                $model = Db::table('common_users')
                    ->select(DB::raw('*, CONCAT_WS(" ", first_name, last_name,"(",username,")") as wholename'))
                    ->where('deleted', 0)
                    ->orderBy('wholename', 'asc')
                    ->get();
                // $model = CommonUser::all();
                break;
            case 'emails':
                $model = Db::table('common_users')
                    ->where('deleted', 0)
                    ->whereRaw('email <> ""')
                    ->groupBy('email')
                    ->pluck('email');
                break;
            case 'userlist':
                $property_id = $request->get('property_id', 0);
                $dept = $request->get('dept', '');
                $dept_id = $request->get('dept_id', 0);
                $query = DB::table('common_users as cu')
                    ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
                    ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
                    ->where('cu.deleted', 0);
                if ($property_id > 0)
                    $query->where('cd.property_id', $property_id);
                if (!empty($dept))
                    $query->where('cd.department', $dept);

                if ($dept_id > 0)
                    $query->where('cu.dept_id', $dept_id);

                $model = $query->select(DB::raw('cu.*,jr.job_role as job_role_name, cd.department, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                    ->get();
                break;

            case 'userlist1':
                $property_id = $request->get('property_id', 0);
                $dept = $request->get('dept', '');
                $dept_id = $request->get('dept_id', 0);
                $query = DB::table('common_users as cu')
                    ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
                    ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
                    ->where('cu.deleted', 0);
                //			->select(DB::raw('cu.*,jr.job_role as job_role_name, cd.department, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                //			->get();
                $leasor_query = DB::table('eng_contracts as ec')
                    ->select(DB::raw('ec.id, ec.leasor as wholename, ec.leasor_email as job_role_name, ec.leasor_contact as department'))
                    ->get();
                foreach ($leasor_query as $leasor) {
                    $leasor->type = 'Leasor';
                }

                $tenant_query = DB::table('eng_tenant as et')
                    ->select(DB::raw('et.id, et.name as wholename,et.email as job_role_name, et.contact as department'))
                    ->get();
                foreach ($tenant_query as $tenant) {
                    $tenant->type = 'Tenant';
                }

                if ($property_id > 0)
                    $user_query->where('cd.property_id', $property_id);
                if (!empty($dept))
                    $user_query->where('cd.department', $dept);

                if ($dept_id > 0)
                    $user_query->where('cu.dept_id', $dept_id);


                $user_query = $query->select(DB::raw('cu.*,jr.job_role as job_role_name, cd.department, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                    ->get();

                foreach ($user_query as $user) {
                    $user->type = 'User';
                }
                //	$model1=$leasor_query->select(DB::raw('ec.leasor as wholename, ec.leasor_email, ec.leasor_contact'))
                //			->get();

                $model = array_merge($user_query, $leasor_query, $tenant_query);
                $model = array_unique($model, SORT_REGULAR);
                $model = array_merge($model, array());
                break;
            case 'jobrole':
                $property_id = $request->get('property_id', 0);
                $query = DB::table('common_job_role');
                if ($property_id > 0)
                    $query->where('property_id', $property_id);

                $model = $query->get();
                // $model = CommonUser::all();
                break;

            case 'usergroup':
                $property_id = $request->get('property_id', 0);
                $query = DB::table('common_user_group');
                if ($property_id > 0)
                    $query->where('property_id', $property_id);

                $model = $query->get();
                break;
            case 'userlang':
                $model = DB::table('common_user_language')->get();
                // $model = UserGroup::all();
                break;
            case 'shiftgroup':
                $dept_id = $request->get('dept_id', 0);
                $model = DB::table('services_shift_group')->where('dept_id', $dept_id)->get();
                // $model = UserGroup::all();
                break;
            case 'module':
                $model = DB::table('common_module')->get();
                break;
            case 'property':
                $client_id = $request->get('client_id', 0);
                $query = DB::table('common_property');
                if ($client_id > 0)
                    $query->where('client_id', $client_id);
                $model = $query->get();
                break;
            case 'propertylist':
                $property_id = $request->get('property_id', 0);
                if($property_id == 0){
                    $query = DB::table('common_property');
                }else{
                    $query = DB::table('common_property')->where('id',$property_id);
                }
                $model = $query->get();
                break;
            case 'propertybyuser':
                $user_id = $request->get('user_id', 0);
                $property_list = CommonUser::getPropertyIdsByJobroleids($user_id);
                if($user_id == 0){
                    $query = DB::table('common_property');
                }else{
                    $query = DB::table('common_property')->whereIn('id', $property_list);
                }
                $model = $query->get();
                break;
            case 'category':
                $property_id = $request->get('property_id', 0);
                $query = DB::table('eng_request_category');
                $query->where('property_id', $property_id);
                $model = $query->get();
                break;
            case 'building':
                $model = DB::table('common_building')->get();
                // $model = Building::all();
                break;
            case 'department':
                $model = DB::table('common_department')->get();
                // $model = Department::all();
                break;
            case 'departmentbyproperty':
                $property_id = $request->get('property_id', 0);
                $model = DB::table('common_department')
                        ->where('property_id', $property_id)
                        ->get();
                // $model = Department::all();
                break;
            case 'division':
                $model = DB::table('common_division')->get();
                break;
            case 'servicedepartment':
                $model = DB::table('common_department')
                    ->where('services', 'Y')
                    ->get();
                // $model = Department::all();
                break;
            case 'section':
                $model = DB::table('call_section')->get();
                // $model = Section::all();
                break;
            case 'calltype':
                $model = new CarrierGroup();
                $model = $model->getTypeList();
                break;
            case 'carrier':
                $model = DB::table('call_carrier')->get();
                // $model = Carrier::all();
                break;
            case 'carriergroup':
                $model = DB::table('call_carrier_groups')->get();
                // $model = CarrierGroup::all();
                break;
            case 'carriercharge':
                // $model = CarrierCharges::all();
                $model = DB::table('call_carrier_charges')->get();
                break;
            case 'chargetype':
                $model = new CarrierCharges();
                $model = $model->getTypeList();
                break;
            case 'casualstaff':
                $dept_func_id = $request->get('dept_func_id', '');
                $property_id = $request->get('property_id', '');
                $model = DB::table('common_users as cu')
                    ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
                    ->join('services_dept_function as sdf', 'sdf.dept_id', '=', 'cd.id')
                    ->where('cd.property_id', $property_id)
                    ->where("sdf.id", $dept_func_id)
                    ->where("cu.casual_staff", 'Y')
                    ->select(DB::raw('sdf.function,cu.id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                    ->get();
                break;
            case 'propertycharge':
                $model = new HotelCharges();
                $model = $model->getTypeList();
                break;
            case 'days':
                $model = new TimeSlab();
                $model = $model->getDayList();
                break;
            case 'allowance':
                // $model = Allowance::all();
                $model = DB::table('call_allowance')->get();
                break;
            case 'timeslab':
                // $model = TimeSlab::all();
                $model = DB::table('call_time_slab')->get();
                break;
            case 'hotelcharge':
                // $model = HotelCharges::all();
                $model = DB::table('call_hotel_charges')->get();
                break;
            case 'tax':
                // $model = Tax::all();
                $model = DB::table('call_tax')->get();
                break;
            case 'locationgroups':
                $model = DB::table('services_location_group')->get();
                break;

            case 'vips':
                $model = DB::table('common_vip_codes')->get();
                break;
            case 'roomtype':
                $model = DB::table('common_room_type')->get();
                break;
            case 'linentype':
                $model = DB::table('services_linen_type')->get();
                break;

            case 'devices':
                $devices = DB::table('services_devices')->whereNotNull('device_id')->select('device_id')->get();
                $device_ids = [];
                foreach ($devices as $row)
                    $device_ids[] = $row->device_id;
                $model = DB::table('common_users')
                    ->whereNotIn('device_id', $device_ids)
                    ->whereNotNull('device_id')->where('device_id', '!=', '')->select('device_id')->distinct()->get();

                break;
            case 'channels':
                $model = DB::table('ivr_channels')->get();
                break;
            case 'call_types':
                $model = DB::table('ivr_call_types')->get();
                break;
            case 'auto_types':
                $model = DB::table('ivr_auto_attendant')->select('call_type as label')->distinct()->get();
                break;
            case 'locationgroup':
                $model = LocationType::all();
                break;
            case 'locationtype':
                $model = LocationType::all();
                break;
            case 'deptfunc':
                // $model = DeftFunction::all();
                $model = DB::table('services_dept_function')->get();
                break;
            case 'escalationgroup':
                // $model = EscalationGroup::all();
                $model = DB::table('services_escalation_group')->get();
                break;
            case 'roomservicegroup':
                // $model = RoomServiceGroup::all();
                $model = DB::table('services_rm_srv_grp')->get();
                break;
            case 'taskgroup':
                // $model = TaskGroup::all();
                $model = DB::table('services_task_group')->get();
                break;
            case 'tasklist':
                // $model = TaskGroup::all();
                $model = DB::table('services_task_list')
                    ->orderBy('task')
                    ->get();
                break;
            case 'hskptype':
                $model = new HskpStatus();
                $model = $model->getTypeList();
                break;
            case 'hskpstatus':
                //$model = new HskpStatus();
                $model = HskpStatus::distinct('status')->select('status')->get();
                break;
            case 'complainttype':
                $model = DB::table('services_complaint_type')->get();
                break;
            case 'complaint':
                $model = DB::table('services_complaints')->get();
                break;
            case 'approvalroute':
                $model = DB::table('services_approval_route')->get();
                break;
            case 'client':
                $model = DB::table('common_chain')->get();
                break;
            case 'phonetype':
                $model = new Device();
                $model = $model->getTypeList();
                break;
            case 'modules':
                $model = new Property();
                $model = $model->getModuleList();
                break;
            case 'module_list':
                $model = new Property();
                $property_id = $request->get('property_id', 0);
                $model = $model->getPropertyModuleList($property_id);
                break;
            case 'usergrouptype':
                $model = new UserGroup();
                $model = $model->getTypeList();
                break;
            case 'externaltype':
                $model = new Protocol();
                $model = $model->getTypeList();
                break;
            case 'checksumtype':
                $model = new Formatter();
                $model = $model->getTypeList();
                break;
            case 'commodetype':
                $model = new Channel();
                $model = $model->getComModeList();
                break;
            case 'prgroup':
                $model = DB::table('common_perm_group')->get();
                break;
            case 'country':
                $list = DB::table('common_country')->get();
                $model = [];
                foreach ($list as $row)
                    $model[] = $row->name;
                break;
            case 'protocol':
                $model = DB::connection('interface')->table('protocol')->get();
                break;
            case 'complaint_datalist':
                $client_id = $request->get('client_id', 4);
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
                    ->select(DB::raw('scmc.*, ct.type, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, ci.division'))
                    ->orderBy('scmc.name', 'asc')
                    ->get();
                break;
            case 'severitylist':
                $model = DB::table('services_complaint_type')->get();
                break;
            case 'feedbacktypelist':
                $model = DB::table('services_complaint_feedback_type')->get();
                break;
            case 'feedbacksourcelist':
                $type_id = $request->get('type_id', '4');
                if ($type_id != 0) {
                    $model['feedback_source_list'] = DB::table('services_complaint_feedback_source')
                    ->where('type_id', $type_id)
                    ->get();
                }
                
                if (empty($model['feedback_source_list']?->toArray())) {
                    $model['feedback_source_list'] = DB::table('services_complaint_feedback_source')->get();
                }
                break;
            case 'severitylistit':
                $model = DB::table('services_it_severity')->get();
                break;
            case 'typelistit':
                $model = DB::table('services_it_type')->get();
                break;
            case 'severitylisteng':
                $model = DB::table('services_eng_severity')->get();
                break;
            case 'pageroute':
                $model = DB::table('common_page_route')
                    ->orderBy('name', 'asc')
                    ->get();
                break;
            case 'alarmgroup':
                $property_id = $request->get('property_id', '4');
                $alarm_name = $request->get('alarm_name', '');
                if ($alarm_name != '') {
                    $model = Db::table('services_alarm_groups')
                        ->where('property', $property_id)
                        ->where('enable', 1)
                        ->where('name', 'like', '%' . $alarm_name . '%')
                        ->groupBy('name')
                        ->get();
                } else {
                    $model = Db::table('services_alarm_groups')
                        ->where('property', $property_id)
                        ->where('enable', 1)
                        ->groupBy('name')
                        ->get();
                }
                break;
            case 'locationlist':
                $value = '%' . $request->get('location', '') . '%';
                $property_id = $request->get('property_id', '');
                $model = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationListData($value, $property_id);
                break;
            case 'locationtotallist':
                $value = '%' . $request->get('location', '') . '%';
                $client_id = $request->get('client_id', '');
                $model = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationTotalListData($value, $client_id);
                break;
            case 'locationtotallisteng':
                $value = '%' . $request->get('location', '') . '%';
                $client_id = $request->get('client_id', '');
                $user_id = $request->get('user_id', '');
                $model = app('App\Http\Controllers\Frontend\RepairRequestController')->getLocationTotalListData($value, $client_id, $user_id);
                break;
            case 'locationtotallistprop':
                $value = '%' . $request->get('location', '') . '%';
                $client_id = $request->get('client_id', '');
                $property_ids = $request->get('property_ids', '');
                $model = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationTotalListPropData($value, $client_id, $property_ids);
                break;
            case 'facilitytotallist':
                $value = '%' . $request->get('location', '') . '%';
                $client_id = $request->get('client_id', '');
                $model = app('App\Http\Controllers\Frontend\GuestserviceController')->getFacilityTotalListData($value, $client_id);
                break;
            case 'equipmentlist':
                $value = $request->get('equipment', '');
                $client_id = $request->get('client_id', '');
                $properyt_id = $request->get('property_id', '');
                $location_id = $request->get('location_id', 0);
                $model = app('App\Http\Controllers\Frontend\EquipmentController')->getEquipmentData($value, $client_id, $properyt_id, $location_id);
                break;
            case 'roomlist':
                $room = '%' . $request->get('room', '') . '%';
                $property_id = $request->get('property_id', '');
                $model = DB::table('common_room as cr')
                    ->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
                    ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
                    ->where('cr.room', 'like', '%' . $room . '%')
                    ->where('cb.property_id', $property_id)
                    ->select(DB::raw('cr.*,cb.id as bldg_id, cb.property_id'))
                    ->get();
                break;
            case 'engcategorylist':
                $property_id = $request->get('property_id', 0);
                $model = DB::table('eng_request_category as erc')
                    ->where('erc.name', 'like', '%' . $request->get('category', '') . '%')
                    ->where('erc.property_id', $property_id)
                    ->select(DB::raw('erc.*'))
                    ->get();
                break;
            case 'engsubcategorylist':
                $category_id = $request->get('category_id', 0);
                $subcategory = '%' . $request->get('subcategory', '') . '%';
                $property_id = $request->get('property_id', 0);
                $model = DB::table('eng_request_subcategory as ers')
                    ->where('ers.name', 'like', '%' . $subcategory . '%')
                    ->where('ers.property_id', $property_id)
                    ->where('ers.category_id', $category_id)
                    ->select(DB::raw('ers.*'))
                    ->get();
                break;
            case 'engrepairstatuslist':
                $model = DB::table('eng_repair_status')->get();
                break;

            case 'engrpreventivestatuslist':
                $model = DB::table('eng_preventive_status')->get();
                break;

            case 'checklistcategorylist':
                $model = DB::table('eng_checklist_category')->get();
                break;
            case 'modchecklistcategorylist':
                $model = DB::table('mod_checklist_category')->get();
                break;
            case 'countrylist':
                $value = '%' . $request->get('value', '') . '%';
                $model = DB::table('common_country')
                    ->where('name', 'like', '%' . $value . '%')
                    ->get();
                break;
            case 'languagelist':
                $model = DB::table('common_language_code')
                    ->get();
                break;
            case 'housecomplaint':
                $model = DB::table('common_house_complaints_category')
                    ->get();
                break;
            case 'deptpermissiongroup':
                $model = array();
                $property_id = $request->get('property_id', 0);
                $model['departments'] = DB::table('common_department')
                    ->where('property_id', $property_id)
                    ->get();
                $model['perm_groups'] = DB::table('common_perm_group')
                    ->where('property_id', $property_id)
                    ->get();

                break;
            case 'dutymanagerlist':
                $property_id = $request->get('property_id', 0);
                $job_roles = PropertySetting::getJobRoles($property_id);

                $model = DB::table('common_users as cu')
                    ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
                    ->where('cu.deleted', 0)
                    ->where('cd.property_id', $property_id)
                    ->where('cu.job_role_id', $job_roles['dutymanager_job_role'])
                    ->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                    ->get();
                break;
            case 'employeelist':
                $client_id = $request->get('client_id', 0);
                $model = DB::table('common_employee as ce')
                    ->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
                    ->leftJoin('common_department as cd', 'ce.dept_id', '=', 'cd.id')
                    ->leftJoin('common_property as cp', 'ce.property_id', '=', 'cp.id')
                    ->where('ce.client_id', $client_id)
                //    ->where('ce.dot', NULL)

                    ->where(function ($model)  {
                        $model->where('ce.dot', NULL)
                            ->orWhere('ce.dot','<', '2010-01-01 00:00:00');
                    })
                   
                    ->select(DB::raw('ce.*, cd.department, CONCAT_WS(" ", ce.fname, ce.lname) as wholename, cp.name as property_name'))
                    ->get();
                // $model = CommonUser::all();
                break;
            case 'addressbook':
                $client_id = $request->get('client_id', 0);
                $model = DB::table('marketing_addressbook as ma')
                    ->where('ma.client_id', $client_id)
                    ->select(DB::raw('ma.*'))
                    ->get();
                break;

            case 'lnf_storedlocation':
                $client_id = $request->get('client_id', 0);
                $model = DB::table('services_lnf_storedloc as ls')
                    ->select(DB::raw('ls.*'))
                    ->get();
                break;
            case 'lnf_itemtype':
                $client_id = $request->get('client_id', 0);
                $model = DB::table('services_lnf_item_type as ina')
                    ->select(DB::raw('ina.*'))
                    ->get();
                break;
            case 'lnf_itemcustomuser':
                $client_id = $request->get('client_id', 0);

                $hotel_list = DB::table('common_users as cu')
                    ->select(DB::raw('cu.*, CONCAT(cu.first_name, " ", COALESCE(cu.last_name,""), " - Hotel User") as fullname, 1 as user_type'))
                    ->get();

                $custom_list = DB::table('services_lnf_item_customuser as ta')
                    ->select(DB::raw('ta.*, CONCAT(ta.first_name, " ", COALESCE(ta.last_name,""), " - Custom User") as fullname, 2 as user_type'))
                    ->get();

                $model = array_merge($hotel_list, $custom_list);

                break;
            case 'lnf_itemcolor':
                $client_id = $request->get('client_id', 0);
                $model = DB::table('services_lnf_item_color as ta')
                    ->select(DB::raw('ta.*'))
                    ->get();
                break;
            case 'lnf_itembrand':
                $client_id = $request->get('client_id', 0);
                $model = DB::table('services_lnf_item_brand as ta')
                    ->select(DB::raw('ta.*'))
                    ->get();
                break;
            case 'lnf_status':
                $client_id = $request->get('client_id', 0);
                $model = DB::table('services_lnf_status as ta')
                    ->select(DB::raw('ta.*'))
                    ->get();
                break;
            case 'lnf_tags':
                $client_id = $request->get('client_id', 0);
                $list = DB::table('services_lnf_item')
                    ->select(DB::raw('tags'))
                    ->get();

                $tag_list = [];
                foreach ($list as $row) {
                    $sub_list = explode(",", $row->tags);
                    foreach ($sub_list as $row1) {
                        if (empty($row1))
                            continue;
                        $tag_list[] = $row1;
                    }
                }

                $model = array_unique($tag_list);

                break;
            case 'lnf_datalist':
                $client_id = $request->get('client_id', 0);

                // LNF Item Store Location
                $list = DB::table('services_lnf_storedloc as ls')
                    ->select(DB::raw('ls.*'))
                    ->get();
                $model['store_loc'] = $list;

                // LNF Item Type
                $list = DB::table('services_lnf_item_type as ina')
                    ->select(DB::raw('ina.*'))
                    ->get();
                $model['item_type'] = $list;

                // LNF Item User
                $hotel_list = DB::table('common_users as cu')
                    ->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
                    ->select(DB::raw('cu.*, cd.department, CONCAT(cu.first_name, " ", COALESCE(cu.last_name,""), " - Hotel User") as fullname, 1 as user_type'))
                    ->where('cu.deleted', 0)
                    ->get();

                $custom_list = DB::table('services_lnf_item_customuser as ta')
                    ->select(DB::raw('ta.*, CONCAT(ta.first_name, " ", COALESCE(ta.last_name,""), " - Custom User") as fullname, 2 as user_type'))
                    ->get();

                $list = array_merge($hotel_list->toArray(), $custom_list->toArray());
                $model['item_user'] = $list;

                // LNF Item Color
                $list = DB::table('services_lnf_item_color as ta')
                    ->select(DB::raw('ta.*'))
                    ->get();
                $model['item_color'] = $list;

                // LNF Item Brand
                $list = DB::table('services_lnf_item_brand as ta')
                    ->select(DB::raw('ta.*'))
                    ->get();
                $model['item_brand'] = $list;

                // LNF Item Status
                $list = DB::table('services_lnf_status as ta')
                    ->select(DB::raw('ta.*'))
                    ->get();
                $model['item_status'] = $list;

                // LNF Item Tag
                $model['item_tag'] = Functions::getTagList('services_lnf_item', 'tags');

                // LNF Item Category
                //$list = LNFItemCategory::get();
                $list = DB::table('services_lnf_item_category as ca')
                    ->leftJoin('common_job_role as jr', 'ca.notify_job_role_id', '=', 'jr.id')
                    ->select(DB::raw('ca.*, jr.job_role'))
                    ->get();
                $model['item_category'] = $list;

                // Job Role
                $list = DB::table('common_job_role')->orderBy('job_role', 'asc')->get();
                $model['item_jobrole'] = $list;

                break;
            case 'lnf_surrendered_data':
                // LNF Surrendered Tag
                $list = Functions::getTagList('services_lnf_item', 'surrendered_department');
                $model['department_tag'] = $list;

                $list = Functions::getTagList('services_lnf_item', 'surrendered_to');
                $model['to_tag'] = $list;

                $list = Functions::getTagList('services_lnf_item', 'surrendered_location');
                $model['location_tag'] = $list;

                break;
            case 'lnf_discarded_data':
                // LNF Surrendered Tag
                $staff_list = Functions::getTagList('common_users', 'CONCAT(first_name, " ", COALESCE(last_name,"")) as fullname', 'fullname');
                $user_list = Functions::getTagList('services_lnf_item', 'discarded_by');
                $list = array_merge($staff_list, $user_list);
                $list = array_unique($list, SORT_REGULAR);
                $list = array_merge($list, array());

                $model['user_tag'] = $list;


                break;
            case 'carrier_group':
                $model = DB::table('call_carrier_groups')
                    ->get();
                break;
            case 'suppliers':
                $model = DB::table('eng_supplier')
                    ->get();
                break;

            case 'complaintsetting':
                $property_id = $request->get('property_id', 0);
                $model = PropertySetting::getComplaintSetting($property_id);
                break;

            case 'engsetting':
                $property_id = $request->get('property_id', 0);
                $model = PropertySetting::getEngSetting($property_id);
                break;

            case 'hskpsetting':
                $property_id = $request->get('property_id', 0);
                $model = PropertySetting::getHskpSettingValue($property_id);
                break;

            case 'itcategory':
                $model = DB::table('services_it_category')
                    ->get();
                break;

            case 'roomcleaningstatelist':
                $model = DB::table('services_room_working_status')
                    ->orderBy('status_id')
                    ->get();
                break;
            case 'schedulelist':
                $model = DB::table('services_hskp_schedule')
                    ->select(DB::raw('id, name'))
                    ->get();
                break;
            case 'equipstatuslist':
                $model = DB::table('eng_equip_status')
                    ->get();
                break;
            case 'calldestlist':
                $model = DB::table('call_destination')
                    ->get();
                break;
            case 'checklist':
                $property_id = $request->get('property_id', 0);

                $query = DB::table('mod_checklist as mc');
                if ($property_id > 0)
                    $query->where('mc.property_id', $property_id);

                $model = $query->select(DB::raw('mc.name'))
                    ->distinct()
                    ->get();
                break;

        }

        return Response::json($model);
    }

    public function getBuildList(Request $request)
    {
        $property_id = $request->get('property_id');
        $build_list = DB::table('common_building as cb')
            ->where('property_id', $property_id)
            ->get();
        return Response::json($build_list);
    }

    public function getProjectSetting(Request $request)
    {
        $property_id = $request->get('property_id', 0);
        $setting = DB::table('property_setting')
            ->where('property_id', 0)
            ->get();
        return Response::json($setting);
    }

    public function loadData(Request $request)
    {
        $sync = $request->get('sync', 0);
        $user_id = $request->get('user_id', 0);
        $client_id = $request->get('client_id', 0);
        $property_id = $request->get('property_id', 0);
        $cur_date = date("Y-m-d");
        $start = microtime(true);

        $ret = array();

        $ret['user_id'] = $user_id;
        //$ret['country'] = $country;

        if (CommonUser::isValidModule($user_id, 'mobile.guestservice.view'))
            $severitylist = DB::table('services_complaint_type')->get();
        else
            $severitylist = [];

        $ret['severitylist'] = $severitylist;

        if (CommonUser::isValidModule($user_id, 'mobile.mytask')) {
            $compensation_item_list = DB::table('services_compensation as sc')
                ->leftJoin('common_property as cp', 'sc.property_id', '=', 'cp.id')
                ->where('sc.client_id', $client_id)
                ->select(DB::raw('sc.*, cp.name'))
                ->get();
        } else
            $compensation_item_list = [];

        $ret['compensation_item_list'] = $compensation_item_list;
        //$ret['complaint_item_info'] = app('App\Http\Controllers\Frontend\ComplaintController')->getComplaintItemListData($property_id);

        $user = CommonUser::find($user_id);
        $dept_id = 0;
        if (!empty($user))
            $dept_id = $user->dept_id;

        $query = DB::table('common_users as cu')
            ->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
            ->leftJoin('common_department as de', 'cu.dept_id', '=', 'de.id')
            ->leftJoin('common_property as cp', 'de.property_id', '=', 'cp.id')
            ->where('cp.client_id', $client_id)
            ->where('cu.deleted', 0);

        if ($dept_id > 0)
            $query->where('cu.dept_id', $dept_id);

        if (CommonUser::isValidModule($user_id, 'mobile.mytask') ||
            CommonUser::isValidModule($user_id, 'mobile.maintenance.view')) {
            $ret['staff_list'] = $query
                ->select(DB::raw('cu.id, jr.job_role, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename , de.department, cp.name as property_name'))
                ->get();
        } else
            $ret['staff_list'] = [];

        $query = DB::table('common_property');
        if ($client_id > 0)
            $query->where('client_id', $client_id);

        if (CommonUser::isValidModule($user_id, 'mobile.mytask'))
            $ret['property_list'] = $query->get();
        else
            $ret['property_list'] = [];

        if (CommonUser::isValidModule($user_id, 'mobile.guestservice.view') ||
            CommonUser::isValidModule($user_id, 'mobile.mytask') ||
            CommonUser::isValidModule($user_id, 'mobile.dutymanager.view') ||
            CommonUser::isValidModule($user_id, 'mobile.maintenance.view')
        ) {
            $ret['location_list'] = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationListData('%%', $property_id);
        } else
            $ret['location_list'] = [];

        // $ret['gs_location_list'] = DB::table('common_room as cr')
        // 		->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
        // 		->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
        // 		->where('cb.property_id', $property_id)
        // 		->select(DB::raw("cr.*,cf.floor, cb.property_id"))
        // 		->orderby('cr.id', 'asc')
        // 		->get();
        $ret['gs_location_list'] = [];

        if (CommonUser::isValidModule($user_id, 'mobile.guestservice.view')) {
            $ret['guestrequest_list'] = app('App\Http\Controllers\Frontend\GuestserviceController')->makeTaskListForMobile($property_id, 1, $user_id);
            $ret['deptrequest_list'] = app('App\Http\Controllers\Frontend\GuestserviceController')->makeTaskListForMobile($property_id, 2, $user_id);
        } else {
            $ret['guestrequest_list'] = [];
            $ret['deptrequest_list'] = [];
        }

        if (CommonUser::isValidModule($user_id, 'mobile.guestservice.view') ||
            CommonUser::isValidModule($user_id, 'mobile.mytask') ||
            CommonUser::isValidModule($user_id, 'mobile.hskpattendant.view') ||
            CommonUser::isValidModule($user_id, 'mobile.minibar.view') ||
            CommonUser::isValidModule($user_id, 'mobile.myroom')
        ) {
            $ret['room_list'] = DB::table('services_room_status as rs')
                ->join('common_room as cr', 'rs.id', '=', 'cr.id')
                ->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
                ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
                ->where('cb.property_id', $property_id)
                ->select(DB::raw('cr.*,cb.property_id'))
                ->get();
        } else {
            $ret['room_list'] = [];
        }

        //$ret['housecomplaint_category_list'] = DB::table('common_house_complaints_category')->get();
        if (CommonUser::isValidModule($user_id, 'mobile.guestservice.view'))
            $ret['task_action_reason_list'] = DB::table('services_task_action_reason')
                ->where('client_id', $client_id)
                ->get();
        else
            $ret['task_action_reason_list'] = [];

        $ret['minutes_list'] = [5, 10, 15, 20, 30, 40, 50, 60];

        $ret['system_task_type'] = [
            PropertySetting::getCleaningRoomSystemTaskType($property_id),
            PropertySetting::getCheckMinibarSystemTaskType($property_id),
        ];

        if (CommonUser::isValidModule($user_id, 'mobile.guestservice.view') ||
            CommonUser::isValidModule($user_id, 'mobile.mytask') ||
            CommonUser::isValidModule($user_id, 'mobile.minibar.view')
        ) {
            $ret['minibar_item_list'] = DB::table('services_rm_srv_itm')->get();
        } else
            $ret['minibar_item_list'] = [];

        return Response::json($ret);
    }

    public function getCurrency()
    {
        $currencyitem = \DB::table('property_setting')->where('settings_key', 'currency')->first();
        $currency = "";
        if (!empty($currencyitem)) {
            $currency = $currencyitem->value;
        }
        $ret = array();
        $ret['code'] = 200;
        $ret['currency'] = $currency;
        return Response::json($ret);
    }

    public function getPropertyList(Request $request)
    {
        $client_id = $request->get('client_id', 0);
        $query = DB::table('common_property');
        if ($client_id > 0)
            $query->where('client_id', $client_id);
        $model = $query->get();

        return Response::json($model);
    }

    public function getLocationList(Request $request)
    {
        $client_id = $request->get('client_id', 0);

        $model = app('App\Http\Controllers\Frontend\GuestserviceController')->getLocationTotalListData('%%', $client_id);

        return Response::json($model);
    }

    public function getRoomList(Request $request)
    {
        $property_id = $request->get('property_id', '');
        $model = DB::table('common_room as cr')
            ->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
            ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
            ->where('cb.property_id', $property_id)
            ->select(DB::raw('cr.*, cb.property_id'))
            ->get();
        return Response::json($model);
    }

    public function getFloorList(Request $request)
    {
        $property_id = $request->get('property_id', '');
        $building_id = $request->get('building_id', '');
        $building_tags = $request->get('building_tags');
        $query = DB::table('common_floor as cf')
            ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
            ->where('cb.property_id', $property_id);

        if ($building_id > 0)
            $query->where('cf.bldg_id', $building_id);
        else if (!empty($building_tags)) {
            $building_ids = [];
            foreach ($building_tags as $key => $value) {
                $building_ids[] = $value['id'];
            }
            $query->whereIn('cf.bldg_id', $building_ids);
        }

        $floors = $query->select(DB::raw('cf.*, cb.name, CONCAT_WS(" - ", cb.name, cf.floor) as floor_name'))
            ->get();
        return Response::json($floors);
    }

    public function getBadgeCount(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $last24 = date('Y-m-d H:i:s', strtotime(' -1 day'));
        $user_id = $request->get('user_id', 0);
        $guestservice_count = DB::table('services_task as st')
            ->where('st.dispatcher', $user_id)
            ->whereIn('st.status_id', array(1, 2, 6, 7, 8))
            ->where('st.start_date_time', '>=', $last24)
            ->count();
        $minibar_count = 0;
        $feedback_count = 0;
        $hskp_count = 0;
        $myrooms_count = 0;
        $mymanager_count = 0;
        $duty_count = 0;
        $maintenance_count = 0;
        $push_notify_cnt = DB::table('common_notification as cn')
            ->where('cn.type', config('constants.MOBILE_PUSH_NOTIFY'))
            ->where('cn.user_id', $user_id)
            ->where('cn.unread_flag', 1)
            ->count();
        $chat_notify_cnt = DB::table('services_chat_agent_history as hist')
            ->where('hist.from_id', $user_id)
            ->where('hist.direction', 0)
            ->where('unread', 1)
            ->count();
        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = array(
            'guestservice' => $guestservice_count,
            'push_notify_cnt' => $push_notify_cnt,
            'chat_notify_cnt' => $chat_notify_cnt,
            'minibar' => $minibar_count,
            'feedback' => $feedback_count,
            'hskp' => $hskp_count,
            'myrooms' => $myrooms_count,
            'mymgr' => $mymanager_count,
            'dutymgr' => $duty_count,
            'maintenance' => $maintenance_count,
        );
        return Response::json($ret);
    }

    public function clearMobileNotificationList(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $last_id = $request->get('last_id', 0);
        $page_size = $request->get('page_size', 0);
        DB::table('common_notification')
            ->where('type', config('constants.MOBILE_PUSH_NOTIFY'))
            ->where('user_id', $user_id)
            ->delete();
        $ret = array();
        $ret['code'] = 200;
        $ret['message'] = "Successfully cleared.";
        return Response::json($ret);
    }

    public function getChatUnreadCount(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $chat_notify_cnt = AgentChatHistory::getTotalUnreadCount($user_id);
        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = array(
            'chat_notify_cnt' => $chat_notify_cnt,
        );
        return Response::json($ret);
    }

    public function getMobileNotificationList(Request $request)
    {
        $user_id = $request->get('user_id', 0);

        $last_id = $request->get('last_id', 0);
        $searchtext = $request->get('searchtext', "");
        $last24 = date('Y-m-d H:i:s', strtotime(' -1 day'));
        $page_size = $request->get('page_size', 0);
        $user = CommonUser::find($user_id);

        $lang = $user->lang_id;
        if ($searchtext == "") {
            $query = DB::table('common_notification as cn')
                ->where('cn.type', config('constants.MOBILE_PUSH_NOTIFY'))
                ->where('cn.user_id', $user_id)
                ->where('cn.created_at', '>=', $last24)
                ->orderBy('cn.id', 'desc')
                ->take($page_size);
        } else {
            $query = DB::table('common_notification as cn')
                ->where('cn.type', config('constants.MOBILE_PUSH_NOTIFY'))
                ->where('cn.user_id', $user_id)
                ->where('cn.created_at', '>=', $last24)
                ->where(function ($query) use ($searchtext) {
                    $query->where('content', 'like', '%' . $searchtext . '%')
                        ->orWhere('payload', 'like', '%' . $searchtext . '%');
                })
                ->orderBy('cn.id', 'desc')
                ->take($page_size);
        }
        if ($last_id > 0)
            $query->where('cn.id', '<', $last_id);
        $data_query = clone $query;
        $datalist = $data_query->get();
        foreach ($datalist as $key => $row) {

            if ($lang != 0 && (strpos($row->payload, 'tasklist') !== false)) {

                $payload = json_decode($row->payload);
                $tasklist = TaskList::find($payload->tasklist);
                if (!empty($tasklist->lang)) {
                    $languages = json_decode($tasklist->lang);
                    foreach ($languages as $key_l => $val_l) {

                        if (($val_l->id == $lang) && $val_l->text) {
                            $row->content = str_replace($tasklist->task, $val_l->text, ($row->content));
                            // echo ($x);
                        }
                    }
                }
            }
        }

        if (empty($datalist) || count($datalist) < 1)
            $last_id = -1;
        else
            $last_id = $datalist[count($datalist) - 1]->id;
        $update_query = clone $query;
        $update_query->update(array('cn.unread_flag' => 0));
        if ($searchtext == "") {
            $push_notify_cnt = DB::table('common_notification as cn')
                ->where('cn.type', config('constants.MOBILE_PUSH_NOTIFY'))
                ->where('cn.user_id', $user_id)
                ->where('cn.created_at', '>=', $last24)
                ->where('cn.unread_flag', 1)
                ->count();
        } else {
            $push_notify_cnt = DB::table('common_notification as cn')
                ->where('cn.type', config('constants.MOBILE_PUSH_NOTIFY'))
                ->where('cn.user_id', $user_id)
                ->where('cn.created_at', '>=', $last24)
                ->where(function ($query) use ($searchtext) {
                    $query->where('content', 'like', '%' . $searchtext . '%')
                        ->orWhere('payload', 'like', '%' . $searchtext . '%');
                })
                ->where('cn.unread_flag', 1)
                ->count();
        }
        $ret = array();
        $ret['code'] = 200;
        $ret['datalist'] = $datalist;
        $ret['last_id'] = $last_id;
        $ret['push_notify_cnt'] = $push_notify_cnt;
        return Response::json($ret);
    }

    public function userlistwithids(Request $request)
    {
        $userids = $request->get('userids', []);
        $model = DB::table('common_users as cu')
            ->whereIn('cu.id', $userids)
            ->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
            ->get();
        return Response::json($model);
    }

    public function getDataForMyManager(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');
        $user_id = $request->get('user_id', '0');
        $last24 = date('Y-m-d H:i:s', strtotime(' -1 day'));
        $cur_date = date('Y-m-d', strtotime(' -1 day'));
        $start_time = $start_date . ' 00:00:00';
        $end_time = $end_date . ' 00:00:00';
        $property_id = $request->get('property_id', 0);
        $start = microtime(true);
        $ret = array();
        // get VIP guest data
        $vip_list = DB::table('common_guest as cg')
            ->join('services_room_status as rs', 'cg.room_id', '=', 'rs.id')
            ->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
            ->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
            ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
            ->join('common_vip_codes as vc', 'vc.vip_code', '=', 'cg.vip')
            ->leftJoin('common_guest_profile as gp', 'cg.guest_id', '=', 'gp.guest_id')
            //->where('cg.created_at', '>=', $last24)
            ->whereBetween('cg.created_at', array($start_time, $end_time))
            ->where('cg.vip', '!=', '0')
            ->where('cb.property_id', $property_id)
            ->groupBy('cg.guest_id')
            ->select(DB::raw('cg.*,rs.working_status, gp.id as new_guest_id, cr.room, vc.name as vip_name'))
            ->get();

        $total_count_select = '
						CAST(COALESCE(sum(st.status_id = 0 and st.duration <= st.max_time), 0) AS UNSIGNED) as ontime,				
						count(*) as total						
					';
        foreach ($vip_list as $key => $row) {
            $complaint = DB::table('services_complaint_request as scr')
                ->where('scr.guest_id', $row->new_guest_id)
                ->where('scr.created_at', '>=', $last24)
                ->select(DB::raw("
						count(*) as cnt,
						CAST(COALESCE(sum(scr.status = 'Resolved'), 0) AS UNSIGNED) as resolved,
						CAST(COALESCE(sum(scr.status = 'Pending'), 0) AS UNSIGNED) as pending
					"))
                ->first();
            $vip_list[$key]->complaint = $complaint;
            $guest_request = DB::table('services_task as st')
                ->where('st.start_date_time', '>=', $last24)
                ->where('st.guest_id', $row->guest_id)
                ->select(DB::raw($total_count_select))
                ->first();
            $vip_list[$key]->request = $guest_request;

            if (($row->working_status >= 0)) {
                switch ($vip_list[$key]->working_status) {
                    case CLEANING_PENDING:
                        $vip_list[$key]->cleaning_state = 'Pending';
                        break;
                    case CLEANING_RUNNING:
                        $vip_list[$key]->cleaning_state = 'Cleaning';
                        break;
                    case CLEANING_DONE:
                        $vip_list[$key]->cleaning_state = 'Done';
                        break;
                    case CLEANING_DND:
                        $vip_list[$key]->cleaning_state = 'DND';
                        break;
                    case CLEANING_DECLINE:
                        $vip_list[$key]->cleaning_state = 'Reject';
                        break;
                    case CLEANING_POSTPONE:
                        $vip_list[$key]->cleaning_state = 'Delay';
                        break;
                    case CLEANING_COMPLETE:
                        $vip_list[$key]->cleaning_state = 'Inspected';
                        break;
                    case CLEANING_PAUSE:
                        $vip_list[$key]->cleaning_state = 'Pause';
                        break;
                    default:
                        $vip_list[$key]->cleaning_state = 'Unassigned';
                        break;
                }

            }
        }
        // get total rooms
        $total_room = Room::getRoomCount($property_id);
        $room_status_counts = app('App\Http\Controllers\Frontend\GuestserviceController')->getmyroomcountAll($property_id, '');
        // get occupancy room count for today
        $occupancy = DB::table('common_room_occupancy')
            ->where('property_id', $property_id)
            ->where('check_date', '<', $end_date)
            ->orderBy('check_date', 'desc')
            ->first();

        $occupancy_percent = 0;
        if (!empty($occupancy) > 0)
            $occupancy_percent = round($occupancy->occupancy * 100 / $total_room, 1);
        $occupancy_list = $this->getOccupancyStatisticsByDate($property_id, $start_date, $end_date);
        // get total request and ontime
        $guest_request = DB::table('services_task as st')
            ->where('st.property_id', $property_id)
            ->whereBetween('st.start_date_time', array($start_time, $end_time))
            ->select(DB::raw('
						CAST(COALESCE(sum(st.status_id = 0 and st.duration <= st.max_time), 0) AS UNSIGNED) as ontime,
						count(*) as cnt						
					'))
            ->first();
        $ontime_percent = 0;
        if (!empty($guest_request) && $guest_request->cnt > 0)
            $ontime_percent = round($guest_request->ontime * 100 / $guest_request->cnt, 1);
        $guest_request->ontime_percent = $ontime_percent;
        // get minibar sales
        $total_mb_sales = DB::table('services_minibar_log as ml')
            ->join('common_room as cr', 'ml.room_id', '=', 'cr.id')
            ->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
            ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
            ->where('cb.property_id', $property_id)
            ->whereBetween('ml.created_at', array($start_time, $end_time))
            ->select(DB::raw('CAST(COALESCE(sum(ml.total_amount), 0) AS UNSIGNED) as total'))
            ->first();
        // get complaint severity
        $complaint = DB::table('services_complaint_request as scr')
            ->where('scr.property_id', $property_id)
            ->whereBetween('scr.created_at', array($start_time, $end_time))
            ->select(DB::raw("
						CAST(COALESCE(sum(scr.status = 'Resolved'), 0) AS UNSIGNED) as resolved,
						count(*) as cnt						
					"))
            ->first();
        $resolved_percent = 0;
        if (!empty($complaint) && $complaint->cnt > 0) {
            $resolved_percent = round($complaint->resolved * 100 / $complaint->cnt);
        }
        $complaint->resolved_percent = $resolved_percent;
        $severity_types = DB::table('services_complaint_type as sct')
            ->get();
        $complaint_sql = '';
        foreach ($severity_types as $row) {
            $complaint_sql .= sprintf("CAST(COALESCE(sum(scr.severity = %d), 0) AS UNSIGNED) as %s,", $row->id, $row->type);
        }
        $complaint_sql .= "count(*) as cnt";
        $complaint_by_severity = DB::table('services_complaint_request as scr')
            ->where('scr.property_id', $property_id)
            ->whereBetween('scr.created_at', array($start_time, $end_time))
            ->select(DB::raw($complaint_sql))
            ->first();
        $severity_list = [];
        foreach ($complaint_by_severity as $key => $row) {
            if ($key == 'cnt')
                continue;
            $severity_list[] = array('count' => $row, 'name' => $key);
        }
        $complaint->severity_by = $severity_list;
        //department chart
        $query = DB::table('services_task as st')
            ->leftJoin('services_task_list as tl', 'st.task_list', '=', 'tl.id')
            ->leftJoin('common_department as cd', 'st.department_id', '=', 'cd.id');
        $dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT_DASHBOARD'));
        if ($dept_id == 0) {
            $dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT'));
        }
        if ($dept_id > 0)
            $query->where('st.department_id', $dept_id);
        else
            $query->where('st.property_id', $property_id);
        $count_query = clone $query;
        $time_range = sprintf("'%s' < DATE(st.start_date_time) AND DATE(st.start_date_time) <= '%s'", $start_date, $end_date);
        $count_query->whereRaw($time_range);
        $dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT_DASHBOARD'));
        if ($dept_id == 0) {
            $dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT'));
        }
        $query = DB::table('common_department as cd')
            ->where('cd.services', 'Y')
            ->where('cd.property_id', $property_id);
        if ($dept_id > 0)
            $query->where('cd.id', $dept_id);
        $department_list = $query->select(DB::raw('cd.*'))
            ->get();
        $by_dept = array();
        for ($d = 0; $d < count($department_list); $d++) {
            $dept_id = $department_list[$d]->id;
            $dept_query = clone $count_query;
            $dept_query->where('st.department_id', $dept_id);
            $by_dept[$d] = $this->getTotalGuestServiceCount($dept_query, null);
            $by_dept[$d]['department'] = $department_list[$d]->short_code;
        }
        $end = microtime(true);
        $currencyitem = \DB::table('property_setting')->where('settings_key', 'currency')->first();
        if (!empty($currencyitem)) {
            $currency = $currencyitem->value;
        } else {
            $currency = 'AED';
        }
        $time_range1 = sprintf("(scr.created_at >= '%s' && scr.created_at <= '%s')", $start_date, $end_date);
        $query = DB::table('services_complaint_request as scr')
            ->where('scr.property_id', $property_id)
            ->whereRaw($time_range1);
        $select_sql = sprintf("COALESCE(sum(scr.compensation_total), 0) as compensation");
        // get status statistics
        $data_query = clone $query;
        $count = $data_query
            ->select(DB::raw($select_sql))
            ->first();
        if (!empty($count)) {
            $compensation = $count->compensation;
        }
        $staffitem = \DB::table('common_users')->where('device_id', '!=', '')->count();
        $ret['code'] = 200;
        $ret['time'] = $end - $start;
        $ret['total_room'] = $total_room;
        $ret['vip_list'] = $vip_list;
        $ret['occupancy_percent'] = $occupancy_percent;
        $ret['room_status_counts'] = $room_status_counts;
        $ret['occupancy_list'] = $occupancy_list;
        $ret['guest_request'] = $guest_request;
        $ret['total_mb_sales'] = $currency . ' ' . number_format($total_mb_sales->total, 2, '.', ',');
        $ret['total_mb_recovery'] = $currency . ' ' . number_format($compensation, 2, '.', ',');
        $ret['total_mb_staff'] = $staffitem;
        $ret['complaint'] = $complaint;
        $ret['department'] = $by_dept;
        return Response::json($ret);
    }

    public function getOccupancyStatisticsByDate($property_id, $start_date, $end_date)
    {
        $average_list = [];
        // get average checkin room count for each month
        for ($i = 0; $i < 400; $i++) {
            $cur_date = date('Y-m-d', strtotime($i . " days", strtotime($start_date)));
            if ($cur_date >= $end_date)
                break;
            $total = 0;
            $date_count = 0;
            $occupancy = DB::table('common_room_occupancy')
                ->where('property_id', $property_id)
                ->where('check_date', $cur_date)
                ->select(DB::raw('
							occupancy as checkin_count
							'))
                ->first();
            if (empty($occupancy))
                $average_list[] = 0;
            else
                $average_list[] = $occupancy->checkin_count;
        }
        // get total room count
        $total_room = Room::getRoomCount($property_id);
        // get percent
        $percent_list = [];
        foreach ($average_list as $row) {
            $occupancy_percent = 0;
            if ($total_room > 0)
                $occupancy_percent = round($row * 100 / $total_room, 1);
            $percent_list[] = $occupancy_percent;
        }
        return $percent_list;
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

    public function getOccupancyStatistics($property_id)
    {
        date_default_timezone_set(config('app.timezone'));

        $cur_date = date('Y-m-d', strtotime("1 days"));
        $cur_month = date('Y-m-01');

        // generate 12 month
        $month_first = [];
        for ($i = 0; $i <= 12; $i++)
            $month_first[] = date('Y-m-d', strtotime(' -' . (12 - $i) . ' months', strtotime($cur_month)));
        $month_first[] = $cur_date;
        $average_list = [];
        // get average checkin room count for each month
        for ($i = 0; $i < count($month_first) - 1; $i++) {
            $total = 0;
            $date_count = 0;
            $occupancy = DB::table('common_room_occupancy')
                ->where('property_id', $property_id)
                ->where('check_date', '>=', $month_first[$i])
                ->where('check_date', '<', $month_first[$i + 1])
                ->select(DB::raw('
							CAST(COALESCE(sum(occupancy), 0) AS UNSIGNED) as checkin_count
							'))
                ->first();
            $date_count = (strtotime($month_first[$i + 1]) - strtotime($month_first[$i])) / 3600 / 24;

            $average = round($occupancy->checkin_count / $date_count, 0);
            $average_list[] = $average;
        }
        // get total room count
        $total_room = Room::getRoomCount($property_id);
        // get percent
        $percent_list = [];
        foreach ($average_list as $row) {
            $occupancy_percent = 0;
            if ($total_room > 0)
                $occupancy_percent = round($row * 100 / $total_room, 1);
            $percent_list[] = $occupancy_percent;
        }
        return $percent_list;
    }

    public function getMultiPropertyList(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        if ($user_id > 0)
            $property_ids_by_jobrole = $request->get('property_ids_by_jobrole', []);
        else {
            $client_id = $request->get('client_id', 0);
            $property_ids_by_jobrole = CommonUser::getProertyIdsByClient($client_id);
        }
        $client_id = $request->get('client_id', 0);
        $model = DB::table('common_property')
            ->whereIn('id', $property_ids_by_jobrole)
            ->get();
        return Response::json($model);
    }

    public function onAckPushMessage(Request $request)
    {
        $message_key = $request->get('message_key', '');
        $table_name = $request->get('table_name', '');
        $table_id = $request->get('table_id', '');
        $property_id = $request->get('property_id', '');
        $user_id = $request->get('user_id', '');
        $ack = $request->get('ack', 2);
        $duration = Redis::get($message_key);

        if (Schema::hasColumn($table_name, 'id') && Schema::hasColumn($table_name, 'ack')) {
            Redis::set($message_key, 0);    // on ack
            if (!empty($duration) && is_numeric($duration))
                Redis::expire($message_key, $duration);

            DB::table($table_name)->where('id', $table_id)->update(array('ack' => $ack));
        }

        return Response::json($message_key);
    }
}
