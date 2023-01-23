<?php

use App\Http\Controllers\Backoffice\Configuration\GeneralController;
use App\Http\Controllers\Backoffice\Configuration\EngController;
use App\Http\Controllers\Backoffice\Guest\AlarmController;
use App\Http\Controllers\Backoffice\Guest\TaskListController;
use App\Http\Controllers\Backoffice\Guest\TaskController;
use App\Http\Controllers\Backoffice\Guest\EscalationController;
use App\Http\Controllers\Frontend\GuestserviceController;
use App\Http\Controllers\Backoffice\Property\BuildingWizardController;
use App\Http\Controllers\Backoffice\Property\LicenseWizardController;
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

    Route::controller(EscalationController::class)->group(function () {
        Route::get('deptfunclist', 'deptFuncList');
        Route::get('usergrouplist', 'userGroupList');
    });
});

Route::prefix('configuration/wizard')->group(function () {
    Route::post('general', [GeneralController::class, 'getGeneral']);
    Route::post('getrepairrequest', [EngController::class, 'getRepairRequest']);
});