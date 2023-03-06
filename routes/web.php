<?php

use App\Http\Controllers\Backoffice\Property\PropertyWizardController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\BackofficeController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\OfflineInterfaceEmailController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RMSInterfaceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Frontend\CallaccountController;
use App\Http\Controllers\Frontend\CallController;
use App\Http\Controllers\Frontend\CampaignController;
use App\Http\Controllers\Frontend\ComplaintController;
use App\Http\Controllers\Frontend\ContractController;
use App\Http\Controllers\Frontend\ENGController;
use App\Http\Controllers\Frontend\EquipmentController;

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
Route::get('hotlyncGU', [FrontendController::class, 'guest']);
Route::get('hotlyncbriefing', [FrontendController::class, 'briefing']);
Route::get('briefingmgr', [FrontendController::class, 'briefingmng']);
Route::get('hotlyncfacilities', [FrontendController::class, 'facilities']);
Route::any('inboundofflinereport',[OfflineInterfaceEmailController::class, 'inboundTest']);
Route::any('call/logout', [UserController::class, 'logout']);
Route::any('api/settopic', [UserController::class, 'subscribeTopic']);
Route::any('api/unsettopic', [UserController::class, 'unsubscribeTopic']);

Route::get('/hotlyncBO', [BackofficeController::class, 'index']);
Route::get('/hotlyncBO/signin', [BackofficeController::class, 'signin']);
Route::get('/backoffice1', [BackofficeController::class, 'test']);
Route::any('/checkfreespace', [BackofficeController::class, 'checkFreeSpace']);
Route::post('project/setting', [DataController::class, 'getProjectSetting']);
Route::any('api/directoutgoing', [CallController::class, 'directOutgoingCall']);

Route::get('hotlync/rejectpromotion', [CampaignController::class, 'rejectPromotion']);

Route::any('complaint/statistics', [ComplaintController::class, 'getStatisticInfo']);

Route::controller(CallaccountController::class)->group(function () {
    Route::any('callaccount/synctracklist', 'syncMobileTrackList');
    Route::any('callaccount/callfinalmail', 'callFinalMail');
    Route::any('callaccount/checkfinalmail', 'checkClassifyFinalMail');
    Route::any('callaccount/callremindermail', 'callReminderMail');
    Route::any('callaccount/checkreminders', 'checkClassifyReminders');
    Route::any('callaccount/checkinitmail', 'checkClassifyInit');

    // fix db url
    Route::any('fixreceived', 'fixReceivedCall');
});

Route::group(['prefix' => 'schedule/'], function () {
    Route::any('callaccount/synctracklist', [CallaccountController::class, 'syncMobileTrackList']);
    Route::any('callaccount/callremindermail', [CallaccountController::class, 'callReminderMail']);
    Route::any('callaccount/callfinalmail', [CallaccountController::class, 'callFinalMail']);
    Route::any('callaccount/checkinitmail', [CallaccountController::class, 'checkClassifyInit']);
    Route::any('callaccount/checkreminders', [CallaccountController::class, 'checkClassifyReminders']);
    Route::any('callaccount/checkfinalmail', [CallaccountController::class, 'checkClassifyFinalMail']);

    Route::any('call/checkcallcenter', [CallController::class, 'checkCallCenterState']);
    Route::any('call/checkautoattendant', [CallController::class, 'checkAutoAttendantState']);
    Route::any('call/checkcallbackcall', [CallController::class, 'checkCallBackCall']);

    Route::any('chat/timeoutsessionchat', [ChatController::class, 'timoutSessionChat']);

    Route::any('rms/authtoken', [RMSInterfaceController::class, 'getauthToken']);
    Route::any('rms/pooldata', [RMSInterfaceController::class, 'updateData']);
    Route::any('rms/syncdata', [RMSInterfaceController::class, 'syncData']);
    Route::any('rms/syncguestdata', [RMSInterfaceController::class, 'syncguestData']);

    Route::any('marketing/campaign', [CampaignController::class, 'scheduleCheckCampaignList']);
    Route::any('eng/checkworkordernotification', [ENGController::class, 'checkWorkOrderNotification']);

    Route::any('complaint/checkstate', [ComplaintController::class, 'checkComplaintState']);
    Route::any('complaint/generatereport', [ComplaintController::class, 'generateReport']);

    Route::any('eng/generateremindercontract', [ContractController::class, 'generateReminder']);
    Route::any('eng/checkcontractexpired', [ContractController::class, 'checkExpireRemind']);

    Route::any('eng/checkworkorder', [EquipmentController::class, 'checkWorkorder']);
    Route::any('eng/checkpreventive', [EquipmentController::class, 'checkPreventive']);
    Route::any('equipment/checkassetdepreciation', [EquipmentController::class, 'applyDepreciation']);
    Route::any('equipment/updateassetdepreciation', [EquipmentController::class, 'updateAssetDepreciation']);

});

Route::group(['prefix' => 'api/', 'middleware' => ['hash_check']], function () {
    Route::any('login', [UserController::class, 'login']);
    Route::any('loginnew', [UserController::class, 'loginNew']);
});


/*  Test code -- v */
use Illuminate\Support\Facades\Redis;
 
Route::get('/dds', function () {
    // ...
 
    Redis::publish('test-channel', json_encode([
        'name' => 'Adam Wathan'
    ]));
});