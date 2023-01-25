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
use App\Http\Controllers\Backoffice\Property\LocationWizardController;
use App\Http\Controllers\Backoffice\User\UserWizardController;
use App\Http\Controllers\Frontend\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// prefix => backoffice, middleware used => ['api', 'api_auth_group']

Route::prefix('property/wizard')->group(function () {
    Route::controller(BuildingWizardController::class)->group(function () {
        Route::get('buildlist', 'getBuildingList');
    });
    
    Route::resource('license', LicenseWizardController::class);
    Route::post('uploadlicense', [LicenseWizardController::class, 'uploadLicense']);
    Route::get('locationindex', [LocationWizardController::class, 'locationIndex']);
});

Route::group(['prefix'=>'guestservice/wizard','as'=>'guestservice.wizard.'], function () {

    Route::prefix('alarmgroup')->controller(AlarmController::class)->group(function () {
        Route::get('userlist', 'getUserList');
    });

    Route::resource('tasklist', TaskListController::class);
    Route::get('gettaskgrouplist', [TaskListController::class, 'getTaskGorupList']);
    Route::any('gettaskcategorylist', [GuestserviceController::class, 'getTaskCategoryList']);
    Route::post('task/createlist', [TaskController::class, 'createTaskList']);
    Route::get('categoryname', [TaskListController::class, 'getCategoryName']);

    Route::resource('task', TaskController::class);
    Route::get('taskindex', [TaskController::class, 'taskIndex']);

    Route::controller(EscalationController::class)->group(function () {
        Route::get('deptfunclist', 'deptFuncList');
        Route::get('usergrouplist', 'userGroupList');
        Route::post('escalation/selectitem', 'selectGroup');
        Route::post('escalation/updateinfo', 'updateEscalationInfo');
        Route::post('escalation/deleteinfo', 'deleteEscalationInfo');
        Route::get('escalationgroupindex', 'escalationgroupindex');
        Route::get('list/grouplist', 'groupList');
    });

    Route::resource('shift', ShiftController::class);
    Route::resource('departfunc', DepartFuncController::class);
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
});

Route::prefix('configuration/wizard')->group(function () {
    Route::post('general', [GeneralController::class, 'getGeneral']);
    Route::post('getrepairrequest', [EngController::class, 'getRepairRequest']);
});

Route::prefix('admin/wizard')->group(function () {
    Route::get('departmentlist', [DepartmentWizardController::class, 'getDepartmentList']);
});

Route::group(['prefix'=>'user/wizard','as'=>'user.wizard.'], function () {
    Route::controller(UserWizardController::class)->group(function () {
        Route::get('userindex', 'userIndex');
    });
});