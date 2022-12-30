<?php

use App\Http\Controllers\Backoffice\Configuration\GeneralController;
use App\Http\Controllers\Backoffice\Guest\AlarmController;
use App\Http\Controllers\Backoffice\Property\BuildingWizardController;
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
});

Route::prefix('guestservice/wizard')->group(function () {
    Route::prefix('alarmgroup')->controller(AlarmController::class)->group(function () {
        Route::get('userlist', 'getUserList');
    });
});

Route::prefix('configuration/wizard')->group(function () {
    Route::post('general', [GeneralController::class, 'getGeneral']);
});