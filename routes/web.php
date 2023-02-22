<?php

use App\Http\Controllers\Backoffice\Property\PropertyWizardController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\DataController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('sendlicreq', [PropertyWizardController::class, 'sendTestLicReq']);


Route::get('/', function () {
    return Redirect::to('/' . config('app.frontend_url'));
});
Route::any('/' . config('app.frontend_url'), [FrontendController::class, 'index']);

Route::get('/hotlyncBO', [BackofficeController::class, 'index']);
Route::get('/hotlyncBO/signin', [BackofficeController::class, 'signin']);
Route::post('project/setting', [DataController::class, 'getProjectSetting']);//add