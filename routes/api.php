<?php

use App\Http\Controllers\Backoffice\Property\PropertyWizardController;
use App\Http\Controllers\Backoffice\Guest\HSKPController;
use App\Http\Controllers\Backoffice\Property\LicenseWizardController;
use App\Http\Controllers\Backoffice\User\UserWizardController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\Intface\ProcessController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\Backoffice\Property\RoomWizardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Intface\ChannelController;
use App\Http\Controllers\Intface\LogController;
use App\Http\Controllers\Intface\ProtocolController;
use App\Http\Controllers\Intface\ParserController;
use App\Http\Controllers\Intface\FormatterController;
use App\Http\Controllers\Intface\AlarmController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('storelicense', [PropertyWizardController::class, 'storeLicense']);
Route::post('licensekey', [PropertyWizardController::class, 'licenseKey']);
Route::get('room/list', [RoomWizardController::class, 'getRoomList']);
Route::any('uploadpicture', [UploadController::class, 'uploadpicture']);

Route::post('checklicense', [LicenseWizardController::class, 'checkLicense']);

Route::get('getcurrency', [DataController::class, 'getCurrency']);
Route::any('build/list', [DataController::class, 'getBuildList']);
Route::any('floor/list', [DataController::class, 'getFloorList']);

Route::post('hotlync/checklicense', [LicenseWizardController::class, "checkLicense"]);
Route::post('auth/getcompareflag', [UserController::class, 'GetCompareFlag']);
Route::post('auth/login', [UserController::class, 'login']);
Route::any('guest/roomlist', [GuestController::class, 'getRoomList']);

Route::any('hskp/publicAreaGetTasksMain', [HSKPController::class, 'publicAreaGetTasksMain']);
Route::any('hskp/publicAreaAddTaskMain', [HSKPController::class, 'publicAreaAddTaskMain']);
Route::any('hskp/publicAreaEditTaskMain', [HSKPController::class, 'publicAreaEditTaskMain']);
Route::any('hskp/publicAreaGetTasksByMainId', [HSKPController::class, 'publicAreaGetTasksByMainId']);
Route::any('hskp/publicAreaGetLocationsWithIds', [HSKPController::class, 'publicAreaGetLocationsWithIds']);
Route::any('hskp/publicAreaEditTask', [HSKPController::class, 'publicAreaEditTask']);
Route::any('hskp/publicAreaEditTaskActive', [HSKPController::class, 'publicAreaEditTaskActive']);
Route::any('hskp/publicAreaAddTask', [HSKPController::class, 'publicAreaAddTask']);

// Interface
Route::group(['prefix' => 'interface/', 'middleware' => ['interface_auth_group']], function () {
    Route::resource('channel', ChannelController::class);
    Route::resource('log', LogController::class);
    Route::resource('protocol', ProtocolController::class);
    Route::resource('parser', ParserController::class);
    Route::resource('formatter', FormatterController::class);
    Route::resource('alarm', AlarmController::class);
    Route::get('buildlist', [ChannelController::class, 'getAcceptBuildingList']);
    Route::post('postbuildlist', [ChannelController::class, 'postBuildingList']);
    Route::post('interfaceurl', [ChannelController::class, 'getInterfaceURL']);

    Route::any('process/{action}', [ProcessController::class, 'process']);
});

Route::controller(DataController::class)->group(function () {
    
    Route::get('list/{name}', 'getList');
    
});

Route::get('backend_api/multipropertylist', [DataController::class, 'getMultiPropertyList']);
Route::get('backoffice/user/jobrole', [UserWizardController::class, 'getJobRoles']);