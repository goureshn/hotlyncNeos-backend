<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\Frontend\CallController;
use App\Http\Controllers\Frontend\ComplaintController;
use App\Http\Controllers\Frontend\GuestserviceController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\Intface\ProcessController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend Routes
|--------------------------------------------------------------------------
|
| Here is where you can register Frontend routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// prefix => frontend, middleware used => ['api', 'api_auth_group']

Route::any('/call/agentstatus', [CallController::class, 'getAgentStatus']);
Route::any('/chat/unreadcount', [DataController::class, 'getChatUnreadCount']);
Route::any('getfavouritemenu', [FrontendController::class, 'getFavouriteMenus']);

Route::prefix('guestservice')->group(function () {
    
    Route::controller(GuestserviceController::class)->group(function () {
        Route::any('statistics', 'getTicketStatisticInfo');
        Route::any('getgsdispatcherlist', 'getGSDispatcherList');
        Route::any('filterlist', 'getFilterList');
        Route::any('prioritylist', 'getPriorityList');
        Route::any('roomlist', 'getRoomList');
        Route::any('locationlist', 'getLocationList');
        Route::any('stafflist', 'getStaffList');
        Route::any('departlist', 'getDepartmentList');
        Route::any('usergrouplist', 'getUserGroupList');
        Route::any('systemtasklist', 'getSystemTaskList');
        Route::any('tasklist', 'getTaskList');
        Route::any('ticketlist', 'getTicketList');
        Route::any('storetasklistprofile', 'storeTaskListProfile');
        Route::any('maxticketno', 'getMaxTicketNumber');
        Route::any('guestname', 'getGuestName');
        Route::any('quicktasklist', 'getQuickTaskList');
        Route::any('maintasklist', 'getMainTaskList');
        Route::any('locationgroup', 'getLocationGroup');
        Route::any('historylist', 'getGuestHistoryList');
        Route::any('taskinfo', 'getTaskInfo');
        Route::any('getguestdata', 'getGuestData');
        Route::any('guestprevhistorylist', 'getGuestPrevHistoryList');
        Route::any('taskinfofromtask', 'getTaskInfoFromTask');
        Route::any('createtasklist', 'createTaskList');
        Route::any('createtasklistnew', 'createTaskListNew');
        Route::any('uploadfiles', 'uploadFiles');
        Route::any('changetask', 'changeTask');
        Route::any('changefeedback', 'changeFeedback');
        Route::any('messagelist', 'getTaskMessage');
        Route::any('notifylist', 'getNotificationHistoryList');
        Route::any('guestfeedback', 'updateGuestFeedback');
        Route::any('taskinfowithreassign', 'getTaskInfoWithReassign');
        Route::any('resendmessage', 'resendMessage');
        Route::any('repeatticketlist', 'getRepeatedList');
        Route::any('cancelrepeat', 'cancelRepeat');
        Route::any('addtask', 'addTask');
        Route::any('createmanagedtasklist', 'createManagedTaskList');
        Route::any('guestlist', 'getGuestInfoList');
        Route::any('getguestsmshistory', 'getGuestSMSHisotry');
        Route::any('sendguestsms', 'sendGuestSMS');
        Route::any('getguestloglist', 'getGuestLogList');
        Route::any('facilitylog', 'facilityLog');
        Route::any('getguestloghistory', 'getfacilityLog');
        Route::any('guestexit', 'guestExit');
        Route::any('tablecheck', 'getTablecheck');
        Route::any('tablecheckupdate', 'getTablecheckupdate');
        Route::any('tablecheckwalkin', 'getTablecheckWalkin');
        Route::any('guestreservationlist', 'getReservationList');
        Route::any('createguestreservation', 'createReservation');
        Route::any('getguestsmstemplate', 'getGuestSmsTemplate');
        Route::any('saveguestsmstemplate', 'saveGuestSmsTemplate');
        Route::any('awcroomlist', 'getAWCRoomList');
        Route::any('shiftinfolist', 'getInformationForShift');
        Route::any('taskgrouplist', 'getTaskgrouplist');
        Route::any('createshiftgrouplist', 'createShiftGroupList');
        Route::any('alarmlistten', 'getAlarmListTen');
        Route::any('alarmlist', 'getAlarmList');
        Route::any('alarmgroup', 'getAlarmGroupList'); 
        Route::any('sendalarm', 'sendAlarm');
    });
    
    Route::any('manualpost', [ProcessController::class, 'postManual']);
});

Route::prefix('complaint')->group(function () {
    Route::controller(ComplaintController::class)->group(function () {
        Route::any('stafflist', 'getStaffList');
        Route::any('id', 'getID');
        Route::any('findcheckinguest', 'findCheckinGuest');
        Route::any('mainsubcategory', 'getMainSubCategoryList');
        Route::any('searchguestlist', 'searchGuestList');
        Route::any('searchcheckoutguest', 'getCheckoutGuestList');
        Route::any('createmaincategory', 'createMainCategory');
        Route::any('createmainsubcategory', 'createMainSubCategory');
        Route::any('compensationtype', 'getCompensationType');
        Route::any('deletemainsubcategory', 'deleteMainSubCategory');
        Route::any('deletemaincategory', 'deleteMainCategory');
        Route::any('changeseverity', 'changeSeverity');
        Route::any('post', 'create');
        Route::any('uploadfiles', 'uploadFiles');
    });
});