<?php

use App\Http\Controllers\Backoffice\Configuration\GeneralController;
use App\Http\Controllers\Backoffice\Configuration\EngController;
use App\Http\Controllers\Backoffice\Guest\AlarmController;
use App\Http\Controllers\Backoffice\Guest\TaskListController;
use App\Http\Controllers\Backoffice\Guest\TaskController;
use App\Http\Controllers\Backoffice\Guest\EscalationController;
use App\Http\Controllers\Backoffice\Guest\DepartFuncController;
use App\Http\Controllers\Backoffice\Guest\ShiftController;
use App\Http\Controllers\Backoffice\Guest\DeviceController;
use App\Http\Controllers\Backoffice\Guest\LocationController;
use App\Http\Controllers\Frontend\GuestserviceController;
use App\Http\Controllers\Backoffice\Admin\DepartmentWizardController;
use App\Http\Controllers\Backoffice\Property\BuildingWizardController;
use App\Http\Controllers\Backoffice\Property\LicenseWizardController;
use App\Http\Controllers\Backoffice\Property\PropertyWizardController;
use App\Http\Controllers\Backoffice\Property\LocationWizardController;
use App\Http\Controllers\Backoffice\User\UserWizardController;
use App\Http\Controllers\Frontend\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Backoffice\Property\ModuleWizardController;
use App\Http\Controllers\Backoffice\Property\ClientWizardController;
use App\Http\Controllers\Backoffice\Property\SupportWizardController;
use App\Http\Controllers\Backoffice\Property\FloorWizardController;
use App\Http\Controllers\Backoffice\Property\RoomtypeWizardController;
use App\Http\Controllers\Backoffice\Property\RoomWizardController;
use App\Http\Controllers\Backoffice\Property\LocationtypeWizardController;
use App\Http\Controllers\Backoffice\User\CreateJobController;
use App\Http\Controllers\Backoffice\Admin\DivisionWizardController;
use App\Http\Controllers\Backoffice\Admin\CommonAreaController;
use App\Http\Controllers\Backoffice\Admin\AdminAreaController;
use App\Http\Controllers\Backoffice\Admin\OutdoorWizardController;
use App\Http\Controllers\Backoffice\Admin\DataManageController;
use App\Http\Controllers\Backoffice\Admin\FaqWizardController;
use App\Http\Controllers\Backoffice\Call\SectionWizardController;
use App\Http\Controllers\Backoffice\Call\AdminWizardController;
use App\Http\Controllers\Backoffice\Call\AdminTrackingController;
use App\Http\Controllers\Backoffice\Call\GuestWizardController;
use App\Http\Controllers\Backoffice\Call\CarrierWizardController;
use App\Http\Controllers\Backoffice\Call\DestWizardController;
use App\Http\Controllers\Backoffice\Call\CarrierGroupController;
use App\Http\Controllers\Backoffice\Call\CarrierChargeController;
use App\Http\Controllers\Backoffice\Call\PropertyChargeController;
use App\Http\Controllers\Backoffice\Call\TaxController;
use App\Http\Controllers\Backoffice\Call\AllowanceController;
use App\Http\Controllers\Backoffice\Call\WhitelistController;
use App\Http\Controllers\Backoffice\Call\TimeslabController;
use App\Http\Controllers\Backoffice\Call\AdminrateController;
use App\Http\Controllers\Backoffice\Call\GuestrateController;
use App\Http\Controllers\Backoffice\Guest\TaskMainController;
use App\Http\Controllers\Backoffice\Guest\HSKPController;
use App\Http\Controllers\Backoffice\Guest\MinibarController;
use App\Http\Controllers\Backoffice\Guest\MinibarItemController;
use App\Http\Controllers\Backoffice\Guest\CompensationController;
use App\Http\Controllers\Backoffice\Guest\CompapprouteController;
use App\Http\Controllers\Backoffice\Guest\DeptdefaultassController;
use App\Http\Controllers\Backoffice\Guest\ComplaintController;
use App\Http\Controllers\Backoffice\Guest\ComplaintTypeController;
use App\Http\Controllers\Backoffice\Guest\FeedbackSourceController;
use App\Http\Controllers\Backoffice\Guest\FeedbackTypeController;
use App\Http\Controllers\Backoffice\Guest\ComplaintdeptpivotController;
use App\Http\Controllers\Backoffice\Guest\ComplaintescalationController;
use App\Http\Controllers\Backoffice\Guest\ComplaintDivisionEscalationController;
use App\Http\Controllers\Backoffice\Guest\SubcomplaintLocEscalationController;
use App\Http\Controllers\Backoffice\User\PmModuleController;
use App\Http\Controllers\Backoffice\User\PmGroupController;
use App\Http\Controllers\Backoffice\User\PermissionController;
use App\Http\Controllers\Backoffice\User\UserGroupController;
use App\Http\Controllers\Backoffice\User\ShiftController as UserShiftController;
use App\Http\Controllers\Backoffice\User\EmployeeController;
use App\Http\Controllers\Backoffice\Engineering\PartGroupController;
use App\Http\Controllers\Backoffice\Engineering\EquipGroupController;
use App\Http\Controllers\Backoffice\Engineering\CategoryController;
use App\Http\Controllers\Backoffice\Engineering\SubcategoryController;
use App\Http\Controllers\Backoffice\Engineering\SupplierController;
use App\Http\Controllers\Backoffice\Configuration\RequestController;
use App\Http\Controllers\Backoffice\Configuration\WakeupController;
use App\Http\Controllers\Backoffice\Configuration\CallAccountController;
use App\Http\Controllers\Backoffice\Configuration\MinibarController as ConfigMinibarController;
use App\Http\Controllers\Backoffice\Configuration\ConfigurationController;
use App\Http\Controllers\Backoffice\Configuration\GuestServiceController as BoGuestServiceController;
use App\Http\Controllers\Backoffice\Configuration\ComplaintController as ConfigComplaintController;
use App\Http\Controllers\Backoffice\Configuration\HouseKeepingController;
use App\Http\Controllers\Backoffice\Configuration\ReportController as ConfigReportController;
use App\Http\Controllers\Backoffice\Configuration\CallCenterController;
use App\Http\Controllers\Backoffice\Configuration\HelpdeskController;
use App\Http\Controllers\Backoffice\CallCenter\ExtensionController;
use App\Http\Controllers\Backoffice\CallCenter\ChannelController;
use App\Http\Controllers\Backoffice\CallCenter\IVRCallTypeController;
use App\Http\Controllers\Backoffice\CallCenter\SkillGroupController;
use App\Http\Controllers\Backoffice\IT\CategoryController as BoItCategoryController;
use App\Http\Controllers\Backoffice\IT\SubcategoryController as BoItSubcategoryController;
use App\Http\Controllers\Backoffice\IT\TypeController;
use App\Http\Controllers\Backoffice\Backup\BackupController;
use App\Http\Controllers\Backoffice\Guest\CompapproutememController;
use App\Http\Controllers\Backoffice\Guest\SubcomplaintReopenEscalationController;

/*
|--------------------------------------------------------------------------
| BackOffice Routes
|--------------------------------------------------------------------------
|
| Here is where you can register BackOffice routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// prefix => backoffice, middleware used => ['web']

Route::get('user/jobrole', [UserWizardController::class, 'getJobRoles']);
// Route::get('user/wizard/user/getimage', [UserWizardController::class, 'getImage']);

Route::get('serialtest', [LicenseWizardController::class, 'testDeviceSerial']);
Route::get('sockettest', [LicenseWizardController::class, 'testSocket']);
Route::get('sockettestsupport', [SupportWizardController::class, 'testSocketSupport']);
Route::get('faq/category', [FaqWizardController::class, 'getCategories']);
Route::get('module/getmodulelist', [ModuleWizardController::class, 'getModuleList']);
Route::get('faq/gettaglist', [FaqWizardController::class, 'getTagList']);

Route::prefix('property/wizard')->group(function () {

    // Client
    Route::resource('client', ClientWizardController::class);
    Route::get('client/delete/{id?}', [ClientWizardController::class, 'destroy']);
    
    Route::controller(PropertyWizardController::class)->group(function () {
        // Property
        Route::post('storeproperty', 'storeProperty');
        Route::get('property/delete/{id?}', 'destroy');
        Route::post('propertycreate', 'createData');
        Route::post('propertyupdate/{id}', 'updateData');
        Route::post('property/uploadlogo', 'uploadlogo');
        Route::any('gethardsize', 'getHardDisk');
    });
    Route::resource('property', PropertyWizardController::class);

    // Module
    Route::get('module/delete/{id?}', [ModuleWizardController::class, 'destroy']);
    Route::resource('module', ModuleWizardController::class);

    // Building
    Route::get('building/delete/{id?}', [BuildingWizardController::class, 'destroy']);
    Route::get('buildlist', [BuildingWizardController::class, 'getBuildingList']);
    Route::resource('building', BuildingWizardController::class);

    // License
    Route::get('license/delete/{id?}', [LicenseWizardController::class, 'destroy']);
    Route::get('licenselist', [LicenseWizardController::class, 'getLicenseList']);
    Route::get('license_deviceid', [LicenseWizardController::class, 'getDeviceId']);
    Route::post('uploadlicense', [LicenseWizardController::class, 'uploadLicense']);
    Route::resource('license', LicenseWizardController::class);

    // Floor
    Route::get('floor/delete/{id?}', [FloorWizardController::class, 'destroy']);
    Route::post('floor/upload', [FloorWizardController::class, 'upload']);
    Route::get('floorlist', [FloorWizardController::class, 'getList']);
    Route::post('floor/erasetables', [FloorWizardController::class, 'eraseTables']);
    Route::resource('floor', FloorWizardController::class);

    // Room Type
    Route::get('roomtype/delete/{id?}', [RoomtypeWizardController::class, 'destroy']);
    Route::post('roomtype/upload', [RoomtypeWizardController::class, 'upload']);
    Route::resource('roomtype', RoomtypeWizardController::class);

    // Room
    Route::get('roomlist/assist', [RoomWizardController::class, 'getRoomAssistList']);
    Route::get('room/delete/{id?}', [RoomWizardController::class, 'destroy']);
    Route::post('room/upload', [RoomWizardController::class, 'upload']);
    Route::resource('room', RoomWizardController::class);

    // Location
    Route::get('location/delete/{id?}', [LocationWizardController::class, 'destroy']);
    Route::get('location_type', [LocationWizardController::class, 'getTypeList']);
    Route::post('location/createtype', [LocationWizardController::class, 'createLocationType']);
    Route::resource('location', LocationWizardController::class);

    // Location Type
    Route::resource('locationtype', LocationtypeWizardController::class);
    Route::get('locationtype/delete/{id?}', [LocationtypeWizardController::class, 'destroy']);

    Route::controller(BuildingWizardController::class)->group(function () {
        Route::get('buildlist', 'getBuildingList');
    });
    
    Route::get('locationindex', [LocationWizardController::class, 'locationIndex']);

    // Export
    Route::controller(ReportController::class)->withoutMiddleware('api_auth_group')->group(function () {
        Route::get('audittask_excelreport', 'downloadAuditExcelReportTask');
        Route::get('auditdeptfunc_excelreport', 'downloadAuditExcelReportDeptFunc');
        Route::get('auditdevice_excelreport', 'downloadAuditExcelReportDevice');
        Route::get('audituser_excelreport', 'downloadAuditExcelReportUser');
    });
});

Route::group(['prefix'=>'guestservice/wizard','as'=>'guestservice.wizard.'], function () {

    // Route::prefix('alarmgroup')->controller(AlarmController::class)->group(function () {
    //     Route::get('userlist', 'getUserList');
    // });

    Route::resource('tasklist', TaskListController::class);
    Route::get('gettaskgrouplist', [TaskListController::class, 'getTaskGorupList']);
    Route::any('gettaskcategorylist', [GuestserviceController::class, 'getTaskCategoryList']);
    Route::post('task/createlist', [TaskController::class, 'createTaskList']);
    Route::get('categoryname', [TaskListController::class, 'getCategoryName']);

    Route::resource('task', TaskController::class);
    Route::get('taskindex', [TaskController::class, 'taskIndex']);

    Route::resource('taskmain', TaskMainController::class);

    Route::controller(EscalationController::class)->group(function () {
        Route::get('deptfunclist', 'deptFuncList');
        Route::get('usergrouplist', 'userGroupList');
        Route::post('escalation/selectitem', 'selectGroup');
        Route::post('escalation/updateinfo', 'updateEscalationInfo');
        Route::post('escalation/deleteinfo', 'deleteEscalationInfo');
        Route::get('escalationgroupindex', 'escalationgroupindex');
        Route::get('list/grouplist', 'groupList');
        Route::post('escalation/group_create', 'createGroup');
    });
    Route::resource('escalation', EscalationController::class);

    Route::resource('shift', ShiftController::class);
    Route::resource('departfunc', DepartFuncController::class);
    Route::get('departfunc/delete/{id?}', [DepartFuncController::class, 'destroy']);
    Route::get('deptfuncindex', [DepartFuncController::class, 'deptFuncIndex']);

    Route::resource('device', DeviceController::class);
    Route::controller(DeviceController::class)->group(function () {
        Route::get('devicelist', 'getDeviceList');
        Route::post('device/upload', 'upload');
        Route::post('device/storeng', 'storeng');
        Route::get('deviceindex', 'deviceIndex');
    });

    Route::resource('location', LocationController::class);
    Route::post('location/list', [LocationController::class, 'getLocationList']);
    Route::post('location/postlocation', [LocationController::class, 'postLocation']);
    Route::get('locationgroupindex', [LocationController::class, 'locationGroupIndex']);

    // GS-Houskeeping function
    Route::resource('hskp', HSKPController::class);
    Route::post('hskp/storeng', [HSKPController::class, 'storeng']);

    // Minibar function
    Route::controller(MinibarController::class)->group(function () {
        Route::post('minibar/creategroup', 'create');
        Route::get('minibargroup/list', 'getGroupList');
        Route::post('minibar/createlist', 'createRSIList');
        Route::post('minibar/upload', 'upload');
        Route::post('minibar/roomtypelist', 'getRoomTypeList');
        Route::get('minibargroup/grouplist', 'getServiceGroupList');
        Route::post('minibargroup/postgroup', 'postGroup');
    });
    Route::resource('minibar', MinibarController::class);

    // Minibar function
    Route::resource('minibaritem', MinibarItemController::class);
    Route::post('minibaritem/upload', [MinibarItemController::class, 'upload']);
    Route::get('minibaritem/gethistory/{id?}', [MinibarItemController::class, 'getHistory']);

    // GS-alarm function
    Route::controller(AlarmController::class)->group(function () {
        Route::post('alarm/creategroup', 'create');
        Route::get('alarmgroup/list', 'getGroupList');
        Route::get('alarmgroup/userlist', 'getUserList');
        Route::post('alarmgroup/postalarm', 'postAlarm');
    });
    Route::resource('alarm', AlarmController::class);

    // compensation
    Route::resource('compensation', CompensationController::class);
    Route::post('compensation/delete/{id?}', [CompensationController::class, 'destroy']);

    // compension approval route
    Route::resource('compapproute', CompapprouteController::class);
    Route::post('compapproute/delete/{id?}', [CompapprouteController::class, 'destroy']);

    // compension approval route
    Route::resource('compapproutemem', CompapproutememController::class);
    Route::post('compapproutemem/delete/{id?}', [CompapproutememController::class, 'destroy']);

    //department default assignee
    Route::resource('deptdefaultass', DeptdefaultassController::class);
    Route::post('deptdefaultass/delete/{id?}', [DeptdefaultassController::class, 'destroy']);

    // GS-Complaint function
    Route::resource('subcomplaint', ComplaintController::class);
    Route::post('subcomplaint/delete/{id?}', [ComplaintController::class, 'destroy']);

    // complaint type
    Route::post('complainttype/delete/{id?}', [ComplaintTypeController::class, 'destroy']);
    Route::resource('complainttype', ComplaintTypeController::class);

    // feedback source
    Route::post('feedbacksource/delete/{id?}', [FeedbackSourceController::class, 'destroy']);
    Route::resource('feedbacksource', FeedbackSourceController::class);

    // feedback type
    Route::resource('feedbacktype', FeedbackTypeController::class);
    Route::post('feedbacktype/delete/{id?}', [FeedbackTypeController::class, 'destroy']);

    // complaint department pivot
    Route::resource('complaintdeptpivot', ComplaintdeptpivotController::class);
    Route::post('complaintdeptpivot/delete/{id?}', [ComplaintdeptpivotController::class, 'destroy']);

    // complaint escalation
    Route::post('complaintescalation/selectitem', [ComplaintescalationController::class, 'selectItem']);
    Route::post('complaintescalation/updateinfo', [ComplaintescalationController::class, 'updateEscalationInfo']);
    Route::post('complaintescalation/deleteinfo', [ComplaintescalationController::class, 'deleteEscalationInfo']);
    Route::resource('complaintescalation', ComplaintescalationController::class);

    // complaint division escalation
    Route::post('complaintdivisionescalation/selectitem', [ComplaintDivisionEscalationController::class, 'selectItem']);
    Route::post('complaintdivisionescalation/updateinfo', [ComplaintDivisionEscalationController::class, 'updateEscalationInfo']);
    Route::post('complaintdivisionescalation/deleteinfo', [ComplaintDivisionEscalationController::class, 'deleteEscalationInfo']);
    Route::resource('complaintdivisionescalation', ComplaintDivisionEscalationController::class);

    // sub complaint location escalation
    Route::post('subcomplaintlocescalation/selectitem', [SubcomplaintLocEscalationController::class, 'selectItem']);
    Route::post('subcomplaintlocescalation/updateinfo', [SubcomplaintLocEscalationController::class, 'updateEscalationInfo']);
    Route::post('subcomplaintlocescalation/deleteinfo', [SubcomplaintLocEscalationController::class, 'deleteEscalationInfo']);
    Route::resource('subcomplaintlocescalation', SubcomplaintLocEscalationController::class);

    // sub complaint reopen escalation
    Route::resource('subcomplaintreopenescalation', SubcomplaintReopenEscalationController::class);
    Route::post('subcomplaintreopenescalation/selectitem', [SubcomplaintReopenEscalationController::class, 'selectItem']);
    Route::post('subcomplaintreopenescalation/updateinfo', [SubcomplaintReopenEscalationController::class, 'updateEscalationInfo']);
    Route::post('subcomplaintreopenescalation/deleteinfo', [SubcomplaintReopenEscalationController::class, 'deleteEscalationInfo']);
});

Route::prefix('configuration/wizard')->group(function () {
    // General
    Route::post('general', [GeneralController::class, 'getGeneral']);
    Route::post('savegeneral', [GeneralController::class, 'saveGeneral']);

    // Request
    Route::controller(RequestController::class)->group(function () {
        Route::post('request', 'getRequestSettingInfo');
        Route::post('saverequest', 'saveRequestSettingInfo');
        Route::get('departmentlist', 'getDepartmentList');
        Route::get('jobrolelist', 'getJobRoleList');
        Route::post('sendrequestemail', 'sendRequestEmail');
        Route::get('joblistforall', 'getJobListForAll');
    });


    // wakeup
    Route::post('wakeup', [WakeupController::class, 'getWakeup']);
    Route::post('savewakeup', [WakeupController::class, 'saveWakeup']);
    // callaccount
    Route::post('callaccount', [CallAccountController::class, 'getCallAccount']);
    Route::post('savecallaccount', [CallAccountController::class, 'saveCallAccount']);
    // minibar
    Route::post('minibar', [ConfigMinibarController::class, 'getMinibar']);
    Route::post('saveminibar', [ConfigMinibarController::class, 'saveMinibar']);

    Route::post('getchatbotsettinginfo', [ConfigurationController::class, 'getChatbotSettingInfo']);
    Route::post('savechatbotsettinginfo', [ConfigurationController::class, 'saveChatbotSettingInfo']);

    // guestservice
    Route::post('guestservice', [BoGuestServiceController::class, 'getGuestService']);
    Route::post('saveguestservice', [BoGuestServiceController::class, 'saveGuestService']);

    // complaint
    Route::post('complaint', [ConfigComplaintController::class, 'getComplaint']);
    Route::post('savesetting', [ConfigComplaintController::class, 'saveSetting']);

    Route::post('getcomlaintdataforemail', [ConfigComplaintController::class, 'getComplaintDataForEmail']);
    Route::post('savecomplaintdataforemail', [ConfigComplaintController::class, 'saveComplaintDataForEmail']);

    Route::post('sendcomplaintemail', [ConfigComplaintController::class, 'sendComplaintEmail']);

    // housekeeping
    Route::post('housekeeping', [HouseKeepingController::class, 'getHouseKeeping']);
    Route::post('savehousekeeping', [HouseKeepingController::class, 'saveHouseKeeping']);

    // automated report
    Route::post('report', [ConfigReportController::class, 'getReport']);
    Route::post('saveusersetting', [ConfigReportController::class, 'saveUserSetting']);
    Route::post('savereport', [ConfigReportController::class, 'saveReport']);
    Route::get('userlist', [ConfigReportController::class, 'getUserList']);

    // callcenter
    Route::post('callcenter', [CallCenterController::class, 'getCallCenter']);
    Route::post('savecallcenter', [CallCenterController::class, 'saveCallCenter']);

    // config
    Route::post('config', [ConfigurationController::class, 'getConfigValue']);
    Route::post('saveconfig', [ConfigurationController::class, 'saveConfigValue']);

    Route::post('updatemobileapp', [PropertyWizardController::class, 'updateMobileSetting']);
    Route::post('getmobilesetting', [PropertyWizardController::class, 'getMobileSetting']);

    Route::post('updateapppin', [PropertyWizardController::class, 'updatePinSetting']);
    Route::post('getpinsetting', [PropertyWizardController::class, 'getPinSetting']);
    Route::post('tunnelClientStart', [PropertyWizardController::class, 'tunnelClientStart']);

    //stock notification for engineering
    Route::post('getstocknotifigroup', [EngController::class, 'getStockNotifiGroup']);
    Route::post('savestocknotifigroup', [EngController::class, 'saveStockNotifiGroup']);

    // reminder of contract for engineering
    Route::post('saveremindercontract', [EngController::class, 'saveReminderContract']);
    Route::post('getremindercontract', [EngController::class, 'getReminderContract']);

    // imap email for repair request
    Route::post('saveimapconfig', [EngController::class, 'saveImapConfig']);
    Route::post('getimapconfig', [EngController::class, 'getImapConfig']);

    // it imap email for it
    Route::post('saveitimapconfig', [HelpdeskController::class, 'saveItImapConfig']);
    Route::post('getitimapconfig', [HelpdeskController::class, 'getItImapConfig']);

    // repair request for engineering
    Route::post('saverepairrequest', [EngController::class, 'saveRepairRequest']);
    Route::post('getrepairrequest', [EngController::class, 'getRepairRequest']);

    // config for preventive
    Route::post('savepreventive', [EngController::class, 'savePreventiveConfig']);
    Route::post('getpreventive', [EngController::class, 'getPreventiveConfig']);

    // config for work request
    Route::post('saveworkrequestconfig', [EngController::class, 'saveWorkRequestConfig']);
    Route::post('getworkrequestconfig', [EngController::class, 'getWorkRequestConfig']);


    // Services on live server , interface server , mobile server , export server
    Route::get('getliveserver', [PropertyWizardController::class, 'getLiveServerSetting']);
    Route::get('getinterfaceserver', [PropertyWizardController::class, 'getInterfaceServerSetting']);
    Route::get('getmobileserver', [PropertyWizardController::class, 'getMobileServerSetting']);
    Route::get('getexportserver', [PropertyWizardController::class, 'getExportServerSetting']);

    Route::post('updateliveserver', [PropertyWizardController::class, 'updateLiveServer']);
    Route::post('updateinterfaceserver', [PropertyWizardController::class, 'updateInterfaceServer']);
    Route::post('updatemobileserver', [PropertyWizardController::class, 'updateMobileServer']);
    Route::post('updateexportserver', [PropertyWizardController::class, 'updateExportServer']);
});

Route::prefix('admin/wizard')->group(function () {
    // Department
    Route::controller(DepartmentWizardController::class)->group(function () {
        Route::get('department/delete/{id?}', 'destroy');
        Route::get('departmentlist', 'getDepartmentList');
        Route::get('userlist', 'getUserList');
        Route::get('department/propertylist/{id?}', 'getPropertyList');
        Route::post('department/postpropertylist', 'postPropertyList');
    });
    Route::resource('department', DepartmentWizardController::class);

    // Division Area
    Route::get('division/delete/{id?}', [DivisionWizardController::class, 'destroy']);
    Route::resource('division', DivisionWizardController::class);

    // Common Area
    Route::get('common/delete/{id?}', [CommonAreaController::class, 'destroy']);
    Route::post('common/upload', [CommonAreaController::class, 'upload']);
    Route::resource('common', CommonAreaController::class);

    // Admin Area
    Route::get('admin/delete/{id?}', [AdminAreaController::class, 'destroy']);
    Route::post('admin/upload', [AdminAreaController::class, 'upload']);
    Route::resource('admin', AdminAreaController::class);

    // Outdoor Area
    Route::get('outdoor/delete/{id?}', [OutdoorWizardController::class, 'destroy']);
    Route::post('outdoor/upload', [OutdoorWizardController::class, 'upload']);
    Route::resource('outdoor', OutdoorWizardController::class);

    // Data manager Area
    Route::post('datamng/upload', [DataManageController::class, 'upload']);
    Route::post('datamng/erasetables', [DataManageController::class, 'eraseTables']);

    //faq
    Route::get('faq/delete/{id?}', [FaqWizardController::class, 'destroy']);
    Route::post('faq/upload', [FaqWizardController::class, 'upload']);
    Route::post('faq/addcategory', [FaqWizardController::class, 'addCategory']);
    Route::resource('faq', FaqWizardController::class);
});

Route::group(['prefix'=>'user/wizard','as'=>'user.wizard.', 'middleware' => ['api_auth_group']], function () {
    // User
    Route::controller(UserWizardController::class)->group(function () {
        Route::get('user/getimage', 'getImage');
        Route::get('user/resetpassword/{id}', 'resetPassword');
        Route::get('user/gethistory/{id}', 'getHistory');
        Route::get('userindex', 'userIndex');
        Route::get('user/delete/{id?}', 'destroy');
        Route::get('usergrid/get', 'getGridData');
        Route::post('user/sendCredential', 'sendCredential');
    });
    Route::resource('user', UserWizardController::class);

    // User Group
    Route::get('usergroup/delete/{id?}', [UserGroupController::class, 'destroy']);
    Route::resource('usergroup', UserGroupController::class);

    Route::get('department', [DepartmentWizardController::class, 'getDepartList']);
    Route::get('departmentlist', [DepartmentWizardController::class, 'getDeptLists']);

    //Create Job Role
    // User Group
    Route::controller(CreateJobController::class)->group(function () {
        Route::get('createjob/delete/{id?}', 'destroy');
        Route::get('createjob/propertylist/{id?}', 'getPropertyList');
        Route::post('createjob/postpropertylist', 'postPropertyList');
        Route::get('jobrole/list', 'getList');
        Route::post('jobrole/deptlist', 'getDeptList');
        Route::post('jobrole/postdeptlist', 'postDeptList');
    });
    Route::resource('createjob', CreateJobController::class);

    //pm Module
    Route::resource('pmmodule', PmModuleController::class);

    // Permission Group
    Route::controller(PmGroupController::class)->group(function () {
        Route::post('pmgroup/pagelist', 'getPageList');
        Route::post('pmgroup/copy', 'copyPmgroupMembers');
        Route::get('pmgroup/delete/{id?}', 'destroy');
        Route::post('pmgroup/postpagelist', 'postPageList');
    });
    Route::resource('pmgroup', PmGroupController::class);

    Route::resource('permission', PermissionController::class);
    Route::resource('shift', UserShiftController::class);

    // Employee
    Route::post('employee/upload', [EmployeeController::class, 'upload']);
    Route::post('employee/migrate', [EmployeeController::class, 'migrate']);
    Route::post('employee/getsyncsetting', [EmployeeController::class, 'getSyncSetting']);
    Route::post('employee/updatesyncsetting', [EmployeeController::class, 'updateSyncSetting']);
    Route::resource('employee', EmployeeController::class);

});

Route::group(['prefix'=>'call/wizard','as'=>'call.wizard.'], function () {
    // section
    Route::controller(SectionWizardController::class)->group(function () {
        Route::get('sectiongrid/get', 'getGridData');
        Route::get('sectionnggrid/get', 'getGridNgData');
        Route::post('section/createdata', 'createData');
        Route::post('section/updatedata', 'updateData');
        Route::get('section/delete/{id?}', 'destroy');
        Route::get('sectionlist/{id}', 'getSectionList');
        Route::get('sectionlistofkey', 'SectionListOfKey');
    });
    Route::resource('section', SectionWizardController::class);

    // admin
    Route::controller(AdminWizardController::class)->group(function () {
        Route::get('admin/delete/{id?}', 'destroy');
        Route::post('admin/createdata', 'createData');
        Route::post('admin/updatedata', 'updateData');
        Route::get('admingrid/get', 'getGridData');
        Route::get('adminnggrid/get', 'getGridNgData');
        Route::get('adminnggrid/userlist', 'getUserList');
        Route::get('sectionlist', 'getSectionList');
        Route::get('sectionlistofkey', 'SectionListOfKey');
    });
    Route::resource('admin', AdminWizardController::class);

    // admin tracking
    Route::controller(AdminTrackingController::class)->group(function () {
        Route::get('admintracking/delete/{id?}', 'destroy');
        Route::post('admintracking/createdata', 'createData');
        Route::post('admintracking/updatedata', 'updateData');
        Route::get('admintrackinggrid/get', 'getGridData');
        Route::get('admintrackingnggrid/get', 'getGridNgData');
        Route::get('admintrackingnggrid/userlist', 'getUserList');
    });
    Route::resource('admintracking', AdminTrackingController::class);


    // guest
    Route::controller(GuestWizardController::class)->group(function () {
        Route::get('guest/delete/{id?}', 'destroy');
        Route::get('guestgrid/get', 'getGridData');
        Route::get('guestnggrid/get', 'getGridNgData');
        Route::post('guest/createdata', 'createData');
        Route::post('guest/updatedata', 'updateData');
    });
    Route::resource('guest', GuestWizardController::class);

    // carrier
    Route::controller(CarrierWizardController::class)->group(function () {
        Route::get('carrier/delete/{id?}', 'destroy');
        Route::get('carriergrid/get', 'getGridData');
        Route::get('carriernggrid/get', 'getGridNgData');
        Route::post('carrier/createdata', 'createData');
        Route::post('carrier/updatedata', 'updateData');
    });
    Route::resource('carrier', CarrierWizardController::class);

    // destination
    Route::controller(DestWizardController::class)->group(function () {
        Route::get('dest/delete/{id?}', 'destroy');
        Route::get('destgrid/get', 'getGridData');
        Route::get('destnggrid/get', 'getGridNgData');
        Route::post('dest/createdata', 'createData');
        Route::post('dest/updatedata', 'updateData');
    });
    Route::resource('dest', DestWizardController::class);

    // carrier group
    Route::controller(CarrierGroupController::class)->group(function () {
        Route::get('carriergroup/delete/{id?}', 'destroy');
        Route::get('carriergroupgrid/get', 'getGridData');
        Route::get('carriergroupnggrid/get', 'getGridNgData');
        Route::post('carriergroup/createdata', 'createData');
        Route::post('carriergroup/updatedata', 'updateData');
        Route::get('carriergroupdest/list', 'getDestDist');
        Route::post('carriergroup/postdestlist', 'postDestList');
    });
    Route::resource('carriergroup', CarrierGroupController::class);

    // carrier charge
    Route::controller(CarrierChargeController::class)->group(function () {
        Route::get('carriercharge/delete/{id?}', 'destroy');
        Route::get('carrierchargegrid/get', 'getGridData');
        Route::get('carrierchargenggrid/get', 'getGridNgData');
        Route::post('carriercharge/createdata', 'createData');
        Route::post('carriercharge/updatedata', 'updateData');
    });
    Route::resource('carriercharge', CarrierChargeController::class);

    // property charge
    Route::controller(PropertyChargeController::class)->group(function () {
        Route::get('propertycharge/delete/{id?}', 'destroy');
        Route::get('propertychargegrid/get', 'getGridData');
        Route::get('propertychargenggrid/get', 'getGridNgData');
        Route::post('propertycharge/createdata', 'createData');
        Route::post('propertycharge/updatedata', 'updateData');
    });
    Route::resource('propertycharge', PropertyChargeController::class);

    // tax
    Route::controller(TaxController::class)->group(function () {
        Route::get('tax/delete/{id?}', 'destroy');
        Route::get('taxgrid/get', 'getGridData');
        Route::get('taxnggrid/get', 'getGridNgData');
        Route::post('tax/createdata', 'createData');
        Route::post('tax/updatedata', 'updateData');
    });
    Route::resource('tax', TaxController::class);

    // allowance
    Route::controller(AllowanceController::class)->group(function () {
        Route::get('allowance/delete/{id?}', 'destroy');
        Route::get('allowancegrid/get', 'getGridData');
        Route::get('allowancenggrid/get', 'getGridNgData');
        Route::post('allowance/createdata', 'createData');
        Route::post('allowance/updatedata', 'updateData');
    });
    Route::resource('allowance', AllowanceController::class);

    // whitelist
    Route::controller(WhitelistController::class)->group(function () {
        Route::get('whitelist/delete/{id?}', 'destroy');
        Route::get('whitelistgrid/get', 'getGridData');
        Route::get('whitelistnggrid/get', 'getGridNgData');
        Route::post('whitelist/createdata', 'createData');
        Route::post('whitelist/updatedata', 'updateData');
    });
    Route::resource('whitelist', WhitelistController::class);

    // timeslab
    Route::controller(TimeslabController::class)->group(function () {
        Route::get('timeslab/delete/{id?}', 'destroy');
        Route::get('timeslabgrid/get', 'getGridData');
        Route::get('timeslabnggrid/get', 'getGridNgData');
        Route::post('timeslab/createdata', 'createData');
        Route::post('timeslab/updatedata', 'updateData');
    });
    Route::resource('timeslab', TimeslabController::class);

    // admin rate map
    Route::controller(AdminrateController::class)->group(function () {
        Route::get('adminrate/delete/{id?}', 'destroy');
        Route::get('adminrategrid/get', 'getGridData');
        Route::get('adminratenggrid/get', 'getGridNgData');
        Route::post('adminrate/createdata', 'createData');
        Route::post('adminrate/updatedata', 'updateData');
    });
    Route::resource('adminrate', AdminrateController::class);

    // guest rate map
    Route::controller(GuestrateController::class)->group(function () {
        Route::get('guestrate/delete/{id?}', 'destroy');
        Route::get('guestrategrid/get', 'getGridData');
        Route::get('guestratenggrid/get', 'getGridngData');
        Route::post('guestrate/createdata', 'createData');
        Route::post('guestrate/updatedata', 'updateData');
    });
    Route::resource('guestrate', GuestrateController::class);
});

Route::group(['prefix'=>'engineering/wizard','as'=>'engineering.wizard.'], function () {
    // partgroup
    Route::get('partgroupnggrid/get', [PartGroupController::class, 'getGridNgData']);
    Route::resource('partgroup', PartGroupController::class);

    // quipgroup
    Route::get('equipgroupnggrid/get', [EquipGroupController::class, 'getGridNgData']);
    Route::resource('equipgroup', EquipGroupController::class);

    // category
    Route::get('categorynggrid/get', [CategoryController::class, 'getGridNgData']);
    Route::resource('category', CategoryController::class);

    // subcategory
    Route::get('subcategorynggrid/get', [SubcategoryController::class, 'getGridNgData']);
    Route::resource('subcategory', SubcategoryController::class);

    // supplier
    Route::get('suppliernggrid/get', [SupplierController::class, 'getGridNgData']);
    Route::resource('supplier', SupplierController::class);
});

Route::group(['prefix'=>'callcenter/wizard','as'=>'callcenter.wizard.'], function () {
    // Department
    Route::resource('extension', ExtensionController::class);
    Route::resource('channel', ChannelController::class);
    Route::resource('ivrcalltype', IVRCallTypeController::class);
    Route::resource('skillgroup', SkillGroupController::class);
});

Route::group(['prefix'=>'it/wizard','as'=>'it.wizard.'], function () {
    // category
    Route::post('category/selectitem', [BoItCategoryController::class, 'selectGroup']);
    Route::post('category/updateinfo', [BoItCategoryController::class, 'updateEscalationInfo']);
    Route::post('category/deleteinfo', [BoItCategoryController::class, 'deleteEscalationInfo']);
    Route::resource('category', BoItCategoryController::class);

    Route::controller(BoItSubcategoryController::class)->group(function () {
        Route::get('approvallist', 'getApprovalList');
        Route::post('selectitem', 'selectItem');
        Route::post('updateinfo', 'updateEscalationInfo');
        Route::post('deleteinfo', 'deleteEscalationInfo');
    });
    Route::resource('subcategory', BoItSubcategoryController::class);

    Route::resource('type', TypeController::class);
});

Route::group(['prefix'=>'backup/wizard','as'=>'backup.wizard.'], function () {
    // backup
    Route::get('daily/get', [BackupController::class, 'getDaily']);
    Route::get('weekly/get', [BackupController::class, 'getWeekly']);
    Route::get('monthly/get', [BackupController::class, 'getMonthly']);
});