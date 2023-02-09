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
Route::get('user/wizard/user/getimage', [UserWizardController::class, 'getImage']);

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

    //department default assignee
    Route::resource('deptdefaultass', DeptdefaultassController::class);
    Route::post('deptdefaultass/delete/{id?}', [DeptdefaultassController::class, 'destroy']);

    // GS-Complaint function
    Route::resource('subcomplaint', ComplaintController::class);
    Route::post('subcomplaint/delete/{id?}', [ComplaintController::class, 'destroy']);

});

Route::prefix('configuration/wizard')->group(function () {
    Route::post('general', [GeneralController::class, 'getGeneral']);
    Route::post('getrepairrequest', [EngController::class, 'getRepairRequest']);
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
    Route::controller(UserWizardController::class)->group(function () {
        Route::get('user/getimage', 'getImage');
        Route::get('user/resetpassword/{id}', 'resetPassword');
        Route::get('user/gethistory/{id}', 'getHistory');
        Route::get('userindex', 'userIndex');
    });

    Route::resource('user', UserWizardController::class);

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
