<?php

use App\Http\Controllers\Backoffice\Guest\HSKPController;
use App\Http\Controllers\Backoffice\Guest\MinibarController;
use App\Http\Controllers\Backoffice\Guest\MinibarItemController;
use App\Http\Controllers\Backoffice\Property\BuildingWizardController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\Frontend\CallController;
use App\Http\Controllers\Frontend\ComplaintController;
use App\Http\Controllers\Frontend\GuestserviceController;
use App\Http\Controllers\Frontend\LNFController;
use App\Http\Controllers\Frontend\ReportController;
use App\Http\Controllers\Frontend\WakeupController;
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
Route::any('/report/filterlist', [ReportController::class, 'getFilterList']);

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
        Route::any('guestfacilitylist', 'getGuestFacilityList');
        Route::any('createguestfacility', 'createGuestFacility');
        Route::any('exitguest', 'exitGuest');
        Route::any('getguestchatsettinginfo', 'getGuestChatSettingInfo');
        Route::any('getjobrolelist', 'getJobRoleList');
        Route::any('saveguestchatsettinginfo', 'saveGuestChatSettingInfo');
        Route::any('getsettingtaskgrouplist', 'getSettingTaskgroupList');
        Route::any('getsettingdeftfunclist', 'getSettingDeptFuncList');
        Route::any('getsettingusergrouplist', 'getSettingUsergroupList');
        Route::any('getsettingjobrolelist', 'getSettingJobroleList');
        Route::any('addsettingtaskgroup', 'addSettingTaskGroup');
        Route::any('editsettingtaskgroup', 'editSettingTaskGroup');
        Route::any('deletesettingtaskgrouprow', 'deleteSettingTaskgroupRow');
        Route::any('taskinfowithassign', 'getTaskInfoWithAssign');
        Route::any('getsettinglocationgrouplist', 'getSettingLocationgroupList');        
        Route::any('getsettingtasklist', 'getSettingTaskList');
        Route::any('getsettingtaskgroups', 'getSettingTaskGroups');
        Route::any('getsettingtaskcategories', 'getTaskCategoryList');
        Route::any('getsettinguserlanglist', 'getSettingLangList');
        Route::any('addsettingtask', 'addSettingTask');
        Route::any('editsettingtask', 'editSettingTask');
        Route::any('deletesettingtaskrow', 'deleteSettingTaskRow');
        Route::any('getsettinglocationgroupdetaillist', 'getSettingLocationGroupDetailList');
        Route::any('getsettingclientlist', 'getSettingClientList');
        Route::any('getsettinglocationtypelist', 'getSettingLocationTypeList');
        Route::any('addsettinglocationgroup', 'addSettingLocationGroup');
        Route::any('updatesettinglocationgroup', 'updateSettingLocationGroup');
        Route::any('deletesettinglocationgrouprow', 'deleteSettingLocationgroupRow');
        Route::any('devicelist', 'getDeviceList'); //add
        Route::any('getroomservicelist', 'getRoomServiceList'); //add
        Route::any('getroomservicecategorylist', 'getRoomServiceCategoryList'); //add
        Route::any('getroomlistassign', 'getRoomListforDeviceAssign'); // add
        Route::any('getfloorlist', 'getFloorList'); // add
        Route::any('getrosters_minibar', 'getRostersMinibarDeptFunc'); //add
        Route::any('getroomlistunassign', 'getUnassignedRoomList'); //add
        Route::any('createrosterdevice_minibar', 'createRosterMinibarForDevice'); //add
    });
    
    Route::any('manualpost', [ProcessController::class, 'postManual']);
    
    Route::controller(ChatController::class)->group(function () {
        Route::any('chatroomlist', 'getChatSessionList');
        Route::any('getinitinfofortemplate', 'getInitInfoForTemplate');
        Route::post('getchattemplatelist', 'getChatTemplateList');
        Route::post('savetemplatedata', 'saveTemplateData');
        Route::post('updatetemplaterow', 'updateTemplateRow');
        Route::post('deletetemplaterow', 'deleteTemplateRow');
        Route::any('chatsessionlistnew', 'getChatSessionListNew');
        Route::any('getpresetmessages', 'getPresetMessages');
        Route::any('savepresetmessages', 'savePresetMessages');
    });
});

Route::prefix('chat')->group(function () {
    Route::controller(ChatController::class)->group(function () {
        Route::any('agentlist', 'getChatAgentList');
        Route::any('agentconversationhistory', 'getAgentConversationHistory');
        Route::any('group/list', 'getGroupChatList');
        Route::any('setreadflag', 'setReadFlag');
        Route::any('agentchathistory', 'getAgentChatHistory');
        Route::any('groupchathistory', 'getGroupChatHistory');
        Route::any('group/detail', 'detailGroupChat');
        Route::any('group/create', 'createNewGroup');
        Route::any('group/uploadprofileimage', 'uploadProfilePicture');
        Route::any('group/update', 'updateGroupChat');
    });
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
        Route::any('uploadguestimage', 'uploadFileGuest');
    });
});

Route::prefix('wakeup')->group(function () {
    Route::controller(WakeupController::class)->group(function () {
        Route::any('list', 'getList');
        Route::any('create', 'create');
        Route::any('update', 'update');
        Route::any('cancel', 'cancel');
        Route::any('guestgroups', 'getGuestGroups');
        Route::any('roomlist', 'getRoomList');
        Route::any('createmultiple', 'createMultiple');
        Route::any('logs', 'getLogs');
    });
});

Route::prefix('hskp')->group(function () {
    Route::controller(HSKPController::class)->group(function () {
        Route::any('statistics', 'getStatisticInfo');
        Route::any('logs', 'getHskpLogs');
        Route::any('checklistitem', 'getChecklistItems');
        Route::any('getchecklistlist', 'getCheckListList');
        Route::any('getschedulelist', 'getScheduleList');
        Route::any('getrulelist', 'getRuleList');
        Route::any('triggertasklist', 'getTriggerTaskList');
        Route::any('getlinensettinglist', 'getLinenSettingList');
        Route::any('activetriggertask', 'activeTriggerTask');
        Route::any('createtriggertask', 'createTriggerTask');
        Route::any('deletetriggertask', 'deleteTriggerTask');
        Route::any('updatechecklist', 'updateCheckList');
        Route::any('getchecklist', 'getCheckList');
        Route::any('addchecklistitemtogroup', 'addChecklistItemToGroup');
        Route::any('removechecklistitemfromgroup', 'removeChecklistItemFromGroup');
        Route::any('deletechecklistwithgroup', 'deleteChecklistWithGroup');
        Route::any('createchecklistwithgroup', 'createChecklistWithGroup');
        Route::any('createchecklistgroup', 'createChecklistGroup');
        Route::any('checklistgrouplist', 'getChecklistGroupList');
        Route::any('createchecklist', 'createCheckList');
        Route::any('createrulelist', 'createRuleList');
        Route::any('updaterule', 'updateRule');
        Route::any('removerule', 'deleteRule');
        Route::any('createschedulelist', 'createScheduleList');
        Route::any('updateschedule', 'updateSchedule');
        Route::any('removeschedule', 'deleteSchedule');
        Route::any('createlinensetting', 'createLinenSetting'); // added column "qty" in "services_linen_setting" table to resolve error
        Route::any('updatelinensetting', 'updateLinenSetting');
        Route::any('removelinensetting', 'deleteLinenSetting');
    });
});

Route::prefix('minibar')->group(function () {

    Route::controller(MinibarItemController::class)->group(function () {
        Route::any('logs', 'getMinibarLogs');
        Route::any('guest', 'getMinibarGuest');
        Route::any('detail', 'getMininbarDetail');
        Route::any('stocks', 'getMinibarStock');
    });

    Route::controller(MinibarController::class)->group(function () {
        Route::any('repost', 'postMinibarItemList');
        Route::any('postminibaritemstatuschange', 'postMinibarItemStatusChange'); //add
        Route::any('postminibaritem', 'postMinibarItemList'); // add
    });
});

Route::prefix('lnf')->group(function () {
    Route::controller(LNFController::class)->group(function () {
        Route::any('getSearchTagsAll', 'getSearchTagsAll'); //add
        Route::any('inquirylist', 'getInquiryItems'); //add
        Route::any('availablelist', 'getAvailableItems'); //add
        Route::any('getLnfAllItems', 'getLnfAllItems'); //add
    });
});