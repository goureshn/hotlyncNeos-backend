<?php

use App\Http\Controllers\Backoffice\Guest\HSKPController;
use App\Http\Controllers\Backoffice\Guest\MinibarController;
use App\Http\Controllers\Backoffice\Guest\MinibarItemController;
use App\Http\Controllers\Backoffice\Property\BuildingWizardController;
use App\Http\Controllers\Backoffice\Property\RoomtypeWizardController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\Frontend\ModController;
use App\Http\Controllers\Frontend\CallController;
use App\Http\Controllers\Frontend\ComplaintController;
use App\Http\Controllers\Frontend\GuestserviceController;
use App\Http\Controllers\Frontend\LNFController;
use App\Http\Controllers\Frontend\ReportController;
use App\Http\Controllers\Frontend\WakeupController;
use App\Http\Controllers\Frontend\EquipmentController;
use App\Http\Controllers\Frontend\RepairRequestController;
use App\Http\Controllers\Frontend\ContractController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\Intface\ProcessController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Frontend\AddressbookController;
use App\Http\Controllers\Frontend\AlarmController;
use App\Http\Controllers\Frontend\CallaccountController;
use App\Http\Controllers\Frontend\CampaignController;
use App\Http\Controllers\Frontend\ENGController;
use App\Http\Controllers\Frontend\FaqController;
use App\Http\Controllers\Frontend\FormController;
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

Route::any('/chat/unreadcount', [DataController::class, 'getChatUnreadCount']);
Route::any('/report/guestfacilities_excelreport', [ReportController::class, 'downloadFacilitiesReportExcel'])->withoutMiddleware('api_auth_group');
Route::any('/report/filterlist', [ReportController::class, 'getFilterList']);
Route::any('/report/feedback_excelreport', [ReportController::class, 'downloadFeedbackReportExcel'])->withoutMiddleware('api_auth_group');
Route::get('/buildsomelist', [BuildingWizardController::class, 'getBuildingSomeList']);
Route::any('/workorder/uploadchecklistfiles', [EquipmentController::class, 'uploadAttachForChecklist']);

Route::any('/notify/list', [FrontendController::class, 'getNotificationList']);
Route::any('/notify/count', [FrontendController::class, 'getNotificationCount']);
Route::any('/notify/clearall', [FrontendController::class, 'clearNotificationList']);
Route::any('getfavouritemenu', [FrontendController::class, 'getFavouriteMenus']);
Route::any('removefavouritemenu', [FrontendController::class, 'removeFavouriteMenus']);
Route::any('addfavouritemenu', [FrontendController::class, 'addFavouriteMenus']);

Route::any('/callaccount/calcrate', [ProcessController::class, 'getChargeValue1']);
Route::any('addressbook/userlist', [AddressbookController::class, 'getUserlist']);

Route::get('faq/getmodulelist', [FaqController::class, 'getModuleList']);
Route::post('faq/getfaqlist', [FaqController::class, 'getFaqList']);

Route::controller(CampaignController::class)->group(function () {
    Route::any('campaign/list', 'getList');
    Route::any('campaign/create', 'create');
    Route::any('campaign/update', 'update');
    Route::any('campaign/changeguest', 'changeGuest');
    Route::any('campaign/receipientlist', 'getReceipientList');
    Route::any('campaign/uploadaddressexcel', 'uploadAddressbookExcel')->withoutMiddleware('api_auth_group');
});

Route::controller(EquipmentController::class)->group(function () {
    Route::any('workorder/start', 'startWorkorder');
    Route::any('workorder/hold', 'holdWorkorder');
    Route::any('workorder/resume', 'resumeWorkorder');
    Route::any('workorder/finish', 'finishWorkorder');
    Route::any('workorder/precomments', 'getWOCommentList');
    Route::any('workorder/uploadchecklistfiles', 'uploadAttachForChecklist');
});

Route::controller(UserController::class)->group(function () {
    Route::post('logout', 'logout');
    Route::post('user/delegatelist', 'getDelegateUserList');
    Route::post('user/updateprofile', 'updateProfile');
    Route::post('user/uploadimage', 'uploadImage');
    Route::post('user/setnotification', 'setNotification');
    Route::post('user/setguestnotification', 'setGuestNotification');
    Route::post('user/setguestwakeupnotification', 'setGuestWakeupNotification');
    Route::post('user/setguestcallaccountingnotification', 'setGuestCallaccountingNotification');
    Route::post('user/getnotification', 'getNotification');

    Route::any('user/setactivestatus', 'setActiveStatus');
    Route::any('auth/checkuser', 'checkUser')->withoutMiddleware('api_auth_group');
});

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
        Route::any('devicelist', 'getDeviceList');
        Route::any('getroomservicelist', 'getRoomServiceList');
        Route::any('getroomservicecategorylist', 'getRoomServiceCategoryList');
        Route::any('getroomlistassign', 'getRoomListforDeviceAssign');
        Route::any('getfloorlist', 'getFloorList');
        Route::any('getrosters_minibar', 'getRostersMinibarDeptFunc');
        Route::any('getroomlistunassign', 'getUnassignedRoomList');
        Route::any('createrosterdevice_minibar', 'createRosterMinibarForDevice');
        Route::any('gettaskcategory', 'getTaskCategory');
        Route::any('addpreference', 'addPreference');
        Route::any('deletepreference', 'deletePreference');
        Route::any('departsearchlist', 'getDepartmentSearchList');
        Route::any('forward', 'forwardTicket');
        Route::any('supervisorlist', 'getSupervisorList');
        Route::any('getrosters', 'getRostersDeptFunc');
        Route::any('updaterosterdevice', 'updateRosterForDevice');
        Route::any('transferdevice', 'transferDevice');
        Route::any('clearallrosters', 'clearAllRosters');
        Route::any('uploadguestcustomimg', 'uploadguestcustomimg');
        Route::any('removeguestcustomimg', 'removeguestcustomimg');
        Route::any('emailvippdf', 'emailvippdf');
        Route::any('uploadguestimg', 'uploadGuestImg');
        Route::any('exportguestfacility', 'exportGuestFacilityList')->withoutMiddleware('api_auth_group');
    });
    
    Route::any('manualpost', [ProcessController::class, 'postManual']);
    Route::get('roomtypelist',  [RoomtypeWizardController::class, 'getRoomTypeList']);
    Route::any('complaintitems', [ComplaintController::class, 'getComplaintItemList']);

    Route::controller(ChatController::class)->group(function () {
        Route::any('acceptchat', 'acceptChat');
        Route::any('sendmessagefromagent', 'sendMessageFromAgent');
        Route::any('sendattachmentfromagent', 'sendAttachmentFromAgent');

        Route::any('chatroomlist', 'getChatSessionList');
        Route::any('getinitinfofortemplate', 'getInitInfoForTemplate');
        Route::post('getchattemplatelist', 'getChatTemplateList');
        Route::post('loadviplevels', 'loadVipLevels');
        Route::post('savetemplatedata', 'saveTemplateData');
        Route::post('updatetemplaterow', 'updateTemplateRow');
        Route::post('deletetemplaterow', 'deleteTemplateRow');
        Route::any('chatsessionlistnew', 'getChatSessionListNew');
        Route::any('getpresetmessages', 'getPresetMessages');
        Route::any('savepresetmessages', 'savePresetMessages');
        Route::any('deletepresetmessages', 'deletePresetMessage');

        Route::any('chathistory', 'getChatHistoryForAgent');
        Route::any('getactiveusers', 'getActiveUsers');
        Route::any('sendemailtousers', 'sendEmailToUsers');
        Route::any('savephonebookinfo', 'savePhonebookInfo');
        Route::any('deletephonebookinfo', 'deletePhonebookInfo');
        Route::any('setguestspaminfo', 'setGuestSpamInfo');

        Route::any('chatsessionhistory', 'getChatSessionHistoryList');
        Route::any('endchatfromagent', 'endChatFromAgent');
        Route::any('chatactiveagentlist', 'getChatActiveAgentList');
        Route::any('transferchat', 'transferChat');
        Route::any('canceltransfer', 'cancelTransfer');
    });
});

Route::prefix('chat')->group(function () {
    Route::controller(ChatController::class)->group(function () {
        
        // QQQ chat api
        Route::any('agentlist', 'getChatAgentList');
        Route::any('agentnamelistonly', 'getChatAgentNameListOnly');
        Route::any('agentagentchatlist', 'getAgentAgentChatList'); // agent agent chat list
        Route::any('uploadpictures', 'uploadPictures');
        Route::any('uploadfiles', 'uploadFilesNew');
        
        Route::any('agentconversationhistory', 'getAgentConversationHistory');
        Route::any('group/list', 'getGroupChatList');
        Route::any('setreadflag', 'setReadFlag');
        Route::any('agentchathistory', 'getAgentChatHistory');
        Route::any('groupchathistory', 'getGroupChatHistory');
        Route::any('group/detail', 'detailGroupChat');
        Route::any('group/create', 'createNewGroup');
        Route::any('group/uploadprofileimage', 'uploadProfilePicture');
        Route::any('group/update', 'updateGroupChat');
        Route::any('group/delete', 'deleteGroupChat');

        /// QQQ group chat api 
        Route::any('group/listnew', 'getGroupChatListNew');
        Route::any('group/detaillist', 'getGroupDetailList'); // load group chat history
        Route::any('createchatgroup', 'createChatGroup'); // create a group chat
        
        Route::any('agentchatlist', 'agentlist');
        Route::any('agentchattypelist', 'agentchattypelist');
        Route::any('agentchatskillgrouplist', 'agentchatskillgrouplist');
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
        Route::any('categorylist', 'getCategoryList');
        Route::any('statistics', 'getStatisticInfo');
        Route::any('fblist', 'getfbList');
        Route::any('feedbackid', 'getfeedbackID');
        Route::any('searchcoguestlist', 'searchCOGuestList');
        Route::any('occasionlist', 'getOccasionList');
        Route::any('postfeedback', 'createFeedback');

        Route::any('forward', 'forward');
        Route::any('list', 'getList');
        Route::any('jobrole', 'getJobRoles');
        Route::any('modcategorylist', 'getModCategory');
        Route::any('saveticketfilter', 'saveTicketFilter');
        Route::any('guestlist', 'getGuestList');
        Route::any('guesthistory', 'getGuestHistory');
        Route::any('senddept', 'sendDept');
        Route::any('deptlist', 'deptsList');
        Route::any('ack', 'acknowledge');
        Route::any('reject', 'reject');
        Route::any('resolve', 'resolve');
        Route::any('close', 'close');
        Route::any('reopen', 'reopen');
        Route::any('repending', 'repending');
        Route::any('unresolve', 'unresolve');
        Route::any('delete', 'delete');
        Route::any('deletesubcomplaint', 'deleteSubcomplaint');
        Route::any('changelocation', 'changeLocation');
        Route::any('changemaincategory', 'changeMainCategory');
        Route::any('changemainsubcategory', 'changeMainSubCategory');
        Route::any('saveguestprofile', 'saveGuestProfile');
        Route::any('flagguest', 'flagGuest');
        Route::any('selectassignee', 'selectAssignee');
        Route::any('getdeptloclist', 'getDeptLocList');
        Route::any('getdeptloctypelist', 'getDeptLocTypeList');
        Route::any('createcomplaintitem', 'createComplaintItem');
        Route::any('savecomplaintdept', 'saveComplaintDept');
        Route::any('assignsubcomplaint', 'assignSubcomplaint');
        Route::any('getsubcomplaintlist', 'getSubcomplaintList');
        Route::any('getcomplaintinfo', 'getComplaintInfo');
        Route::any('submylist', 'getSubMyList');
        Route::any('completesubcomplaint', 'completeSubComplaint');
        Route::any('cancelsubcomplaint', 'cancelSubComplaint');
        Route::any('reassignsubcomplaint', 'reassignSubComplaint');
        Route::any('assigneelist', 'queryAssigneeList');
        Route::any('changeassignee', 'changeAssignee');
        Route::any('acksubcomplaint', 'ackSubComplaint');
        Route::any('inprogresssubcomplaint', 'inprogressSubComplaint');
        Route::any('reopensubcomplaint', 'reopenSubComplaint');
        Route::any('getcomments', 'getComments');
        Route::any('addcomment', 'addComment');
        Route::any('updatecomment', 'updateComment');
        Route::any('deletecomment', 'deleteComment');
        Route::any('getsubcomments', 'getSubcomments');
        Route::any('addsubcomment', 'addSubcomment');
        Route::any('postcompensation', 'postCompensation');
        Route::any('deletemaincompensation', 'deleteMainCompensation');
        Route::any('addcompensationtype', 'addCompensationType');
        Route::any('onroutemylist', 'getOnrouteMylist');
        Route::any('getcompensationcomments', 'getCompensationComments');
        Route::any('approve', 'approve');
        Route::any('rejectcompensation', 'rejectCompensation');
        Route::any('returncompensation', 'returnCompensation');
        Route::any('addcommentoreturn', 'addCommentToReturn');
        Route::any('getcompensationlistforsubcomplaint', 'getCompensationListForSubcomplaint');
        Route::any('addcompensationforsubcomplaint', 'addCompensationForSubcomplaint');
        Route::any('addcompensationlistforsubcomplaint', 'addCompensationListForSubcomplaint');
        Route::any('deletecompensationforsubcomplaint', 'deleteCompensationForSubcomplaint');
        Route::any('removesubfiles', 'removeFilesFromSubcomplaint');
        Route::any('updateguest', 'updateGuest');


        Route::any('briefingsrclist', 'getBriefingSrcList');
        Route::any('briefingprogresslist', 'getBriefingProgressList');
        Route::any('startbriefing', 'startBriefing');
        Route::any('endbriefing', 'endBriefing');
        Route::any('currentbriefing', 'getCurrentBriefing');
        Route::any('discussnextbriefing', 'discussNextBriefing');
        Route::any('movenextbriefing', 'moveNextBriefing');
        Route::any('moveprevbriefing', 'movePrevBriefing');

        Route::any('briefingprogresslist1', 'getBriefingProgressList1');
        Route::any('saveticketlistforbriefing', 'saveTicketListForBriefing');
        Route::any('endbriefing1', 'endBriefing1');
        Route::any('currentbriefing1', 'getCurrentBriefing1');
        Route::any('discussnextbriefing1', 'discussNextBriefing1');
        Route::any('movenextbriefing1', 'moveNextBriefing1');
        Route::any('moveprevbriefing1', 'movePrevBriefing1');


        Route::any('flag', 'flagComplaint');
        Route::any('flagmark', 'flagmarkComplaint');
        Route::any('makenote', 'makeNote');
        Route::any('mystatistics', 'getMyStatisticInfo');
        Route::any('createcategory', 'createCategory');
        Route::any('subcategorylist', 'getSubcategoryList');
        Route::any('locationlist', 'getComplaintLocationList');
        Route::any('createsubcategory', 'createSubcategory');
        Route::any('savecategory', 'saveCategory');
        Route::any('savesubcategory', 'saveSubcategory');
        Route::any('savelocation', 'saveLocation');
        Route::any('changesubcomment', 'changeSubComment');
        Route::any('changesubsolution', 'changeSubSolution');
        Route::any('changefeedback', 'changeFeedback');
        Route::any('changeinitresponse', 'changeInitialResponse');
        Route::any('changefeedbacktype', 'changeFeedbackType');
        Route::any('changefeedbacksource', 'changeFeedbackSource');
        Route::any('changeincidentime', 'changeIncidentTime');
        Route::any('changeresolution', 'changeResolution');
        Route::any('myfilterlist', 'getMyFilterList');
        Route::any('savereminder', 'saveReminder');
        Route::any('getcomptemplate', 'getCompensationTemplate');
        Route::any('savecomptemplate', 'saveCompensationTemplate');
        Route::any('highlight', 'setHighlight');
        Route::any('logs', 'getComplaintLogs');
        Route::any('logsforsubcomplaint', 'getSubcomplaintLogs');
        Route::any('briefingroomlist', 'getBriefingRoomList');
        Route::any('mybriefingroomlist', 'getMyBriefingRoomList');
        Route::any('attendantlist', 'getAttendantList');
        Route::any('createbriefingroom', 'createBriefingRoom');
        Route::any('updatebriefingroom', 'updateBriefingRoom');
        Route::any('cancelbriefingroom', 'cancelBriefingRoom');
        Route::any('joinqrcode', 'getQRCodeForJoin');
        Route::any('sharejoinurl', 'shareJoinURL');
        Route::any('presenterlist', 'getPresenterList');

        Route::any('createmodchecklist', 'createModCheckList');
        Route::any('getmodchecklist', 'getModCheckList');
        Route::any('deletemodchecklist', 'deleteModCheckList');
        Route::any('tasklist', 'getTaskList');
        Route::any('updatetasklist', 'updateTaskList');
        Route::any('deletemodtask', 'deleteModTask');
        Route::any('updatecomplete', 'updateCompleteChecklist');

        Route::any('reportfilter', 'getReportFilterValues');

        Route::withoutMiddleware('api_auth_group')->group(function () {
            Route::any('searchguestlist', 'searchGuestList');
            Route::any('searchcoguestlist', 'searchCOGuestList');
            Route::any('findcheckinguest', 'findCheckinGuest');
            Route::any('searchcheckoutguest', 'getCheckoutGuestList');
            Route::any('stafflist', 'getStaffList');
            Route::any('occasionlist', 'getOccasionList');
            Route::any('maincategorylist', 'getMainCategoryList');
            Route::any('id', 'getID');
            Route::any('feedbackid', 'getfeedbackID');
            Route::any('roomlist', 'getRoomList');
            Route::any('post', 'create');
            Route::any('postfeedback', 'createFeedback');
            Route::any('uploadfiles', 'uploadFiles');
            Route::any('updatefilepath', 'updateFilePath');
            Route::any('uploadguestimage', 'uploadFileGuest');
            Route::any('uploadsubfiles', 'uploadFilesToSubcomplaint');
            Route::any('compensationtype', 'getCompensationType');
            Route::any('addcompensationtype', 'addCompensationType');
            Route::any('refreshchecklist', 'refreshChecklist');
            Route::any('colorcode', 'getPropertyColorCode');
        });
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
        Route::any('gethskpstatusbyfloor', 'getHskpStatusByFloor');
        Route::any('gethskpattendantlist', 'getHskpAttendantUserList');
        Route::any('getroomhistory', 'getRoomHistory');
        Route::any('updateroomluggage', 'updateRoomLuggage');
        Route::any('updatecleaningstate', 'updateCleaningState');
        Route::any('reassignroster', 'reassignRosterToToom');
        Route::any('updateroomdiscrepancy', 'updateRoomDiscrepancy');
        Route::any('updateroomschedule', 'updateRoomSchedule');
        Route::any('updateroomstatusmanually', 'changehskpstatus');
        Route::any('updaterushclean', 'updateRushClean');
        Route::any('updateservicestate', 'updateServiceState');
        Route::any('hskpdeptfunclist', 'getHskpDeptFuncList');
        Route::any('hskpjobrolelist', 'getHskpJobRoleList');
        Route::any('getservicestatelist', 'getServiceStateList');
        Route::any('hskpuserlist', 'getHskpUserList');
        Route::any('rosterroomlist', 'getRoomListForRoster');
        Route::any('shiftlist', 'getShiftList');
        Route::any('attendantlist', 'getAttendantList');
        Route::any('assignedroomlist', 'getAssignedRoomListToStaff');
        Route::any('getroomlist', 'getRoomList');
        Route::any('createroomassignment', 'createRoomAssignment');
        Route::any('assignroomwithauto', 'createRoomAssignmentWithAuto');
        Route::any('getfostatelist', 'getFOStateList');
        Route::any('getrmstatelist', 'getRmStateList');
    });
    
    Route::any('hskpdevicelist', [GuestserviceController::class, 'getDeviceList']);
    Route::any('newhskpdevicelist', [GuestserviceController::class, 'getNewDeviceList']);
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
        Route::any('postminibaritemstatuschange', 'postMinibarItemStatusChange');
        Route::any('postminibaritem', 'postMinibarItemList');
    });
});

Route::prefix('lnf')->group(function () {
    Route::controller(LNFController::class)->group(function () {
        Route::any('getSearchTagsAll', 'getSearchTagsAll');
        Route::any('inquirylist', 'getInquiryItems');
        Route::any('availablelist', 'getAvailableItems');
        Route::any('getLnfAllItems', 'getLnfAllItems');
        Route::any('searchguestlist', 'searchGuestList');
        Route::any('create_lnf', 'createLNF');
        Route::any('create_lnf_item', 'createLNFItem');
        Route::any('uploadfiles', 'uploadfiles');
        Route::any('completepostitem', 'completePostItem');
        Route::any('createitemcustomuser', 'createItemCustomUser');
        Route::any('createitemtype', 'createItemType');
        Route::any('createitemcategory', 'createItemCategory');        
        Route::any('deleteitemcategory', 'deleteItemCategory');
        Route::any('createstoredlocation', 'createStoredLocation');
        Route::any('savereturn', 'saveReturnStatus');
        Route::any('savediscarded', 'saveDiscardedStatus');
        Route::any('savesurrendered', 'saveSurrenderedStatus');
        Route::any('closeitem', 'closeItem');
        Route::any('getLnfItemHistory', 'getLnfItemHistory');
        Route::any('getLnfItemComment', 'getLnfItemComment');
        Route::any('getLnfItems', 'getLnfItems');
        Route::any('createitembrand', 'createItemBrand');
        Route::any('submit_comment', 'submit_comment');
        Route::any('update_lnf_item', 'updateLnfItem');
        Route::any('matchitems', 'matchItems');
    });
});

Route::prefix('eng')->group(function () {

    Route::any('uploadfiles', [ComplaintController::class, 'uploadFiles'])->withoutMiddleware('api_auth_group');

    Route::controller(RepairRequestController::class)->group(function () {
        Route::any('requestorlist', 'getRequestorList');
        Route::any('repairrequest_getcategory_list', 'getCategoryList');
        Route::any('repairrequestlist', 'repairrequestList');
        Route::any('getrepaircomment', 'getCommentList');
        Route::any('stafflist', 'getStaffList');
        Route::any('repairrequest_getsubcategory_list', 'getSubcategoryList');
        Route::any('repairrequest_savecategory', 'saveCategory');
        Route::any('repairrequest_savesubcategory', 'saveSubcategory');
        Route::any('postrepaircomment', 'postComment');
        Route::any('updaterepairrequest', 'updateRequest');
        Route::any('repairrequest_tenant_list', 'getTenantList');
        Route::any('repairrequest_savetenant', 'saveTenant');
        Route::any('createrepairrequest', 'createRequest');
        Route::any('upload_repair_attach', 'uploadFiles');
        Route::any('deleterepairrequest', 'deleteRequest');
        Route::any('exportrepairrequest', 'exportRepairRequestList')->withoutMiddleware('api_auth_group');
    });

    Route::controller(EquipmentController::class)->group(function () {
        Route::any('getequipmentorgrouplist', 'getEquipmentOrGroupList');
        Route::any('getstaffgrouplist', 'getStaffGroupList');
        Route::any('getPreventivestatusList', 'PreventivestatusList');
        Route::any('getpreventivemaintenancelist', 'getPreventiveMaintenanceList');
        Route::any('getchecklistfrompreventive', 'getCheckListFromPreventive');
        Route::any('createpreventivemaintenance', 'createPreventiveMaintenance');
        Route::any('deletepreventivemaintenance', 'deletePreventiveMaintenance');
        Route::any('createworkordermanual', 'createWorkorderManual');
        Route::any('getwridlist', 'getWRIDList');
        Route::any('workorderlist', 'getWorkorderList');
        Route::any('flagworkorder', 'flagWorkorder');
        Route::any('partlist', 'getPartNameList');
        Route::any('createworkorder', 'createWorkorder');
        Route::any('workorderchecklist', 'getChecklistForWorkorder');
        Route::any('updateworkorderchecklist', 'updateChecklistForWorkorder');
        Route::any('updateworkorderchecklistattach', 'updateChecklistAttachForWorkorder');
        Route::any('updateworkorderchecklistcomment', 'updateChecklistCommentForWorkorder');
        Route::any('getworkorderdetail', 'getWorkOrderDetail');
        Route::any('getworkorderhistorylist', 'getWorkorderHistoryList');
        Route::any('getworkorderstafflist', 'getWorkorderStaffList');
        Route::any('changestatus', 'changeWorkorderStatus');
        Route::any('updateworkorderhistory', 'updateWorkOrderHistory');
        Route::any('deleteworkorderhistory', 'deleteWorkorderHistory');
        Route::any('createworkorderstaff', 'createWorkOrderStaff');
        Route::any('updateworkorderstaff', 'updateWorkOrderstaff');
        Route::any('deleteworkorderstaff', 'deleteWorkorderStaff');
        Route::any('uploadfilestoworkorder', 'uploadFilesToWorkOrder');
        Route::any('deletefilefromworkorder', 'deleteFileFromWorkOrder');
        Route::any('updateworkorder', 'updateWorkorder');
        Route::any('deleteworkorder', 'deleteWorkorder');
        Route::any('changeworkdate', 'changeWorkOrderDate');
        Route::any('getPreventiveperiodList', 'PreventiveperiodList');
        Route::any('requestlist', 'RequestList');
        Route::any('requestorhistory', 'requestorHistory');
        Route::any('updaterequest', 'updateRequest');
        Route::any('exportworkorder', 'exportWorkorderList')->withoutMiddleware('api_auth_group');
    });

    Route::controller(ContractController::class)->group(function () {
        Route::any('contractlist', 'contractList');
        Route::any('createcontract', 'createContract');
        Route::any('updatecontract', 'updateContract');
        Route::any('deletecontract', 'deleteContract');
    });
});

Route::prefix('equipment')->group(function () {
    Route::controller(EquipmentController::class)->group(function () {
        Route::any('statistics', 'getStatisticInfo');
        Route::any('idlist', 'getEquipIdList');
        Route::any('partgrouplist', 'getOnlyPartList');
        Route::any('getengchecklistnames', 'getEngCheckListNames');
        Route::any('categorylist', 'getCategoryList');
        Route::any('getchecklistitemlist', 'getChecklistItemList');
        Route::any('createchecklistitem', 'createChecklistItem');
        Route::any('deletechecklistitem', 'deleteCheckListItem');
        Route::any('grouplist', 'getEquipGroupList');
        Route::any('createequipcheckList', 'createEquipCheckList');
        Route::any('deletequipchecklist', 'deletEquipChecklist');
        Route::any('equipmentlist', 'getEquipmentList');
        Route::any('statuslist', 'getStatusList');
        Route::any('maintenancelist', 'getMaintenanceList');
        Route::any('equipmentpartgrouplist', 'getPartGroupList');
        Route::any('supplierlist', 'getSupplierList');
        Route::any('createequipment', 'CreateEquipment');
        Route::any('equipmentinformlist', 'getEquipmentInformList');
        Route::any('equipmentworkorderlist', 'getEquipmentWorkorderList');
        Route::any('createequipfile', 'createEquipmentFile');
        Route::any('equipmentinfiledel', 'delEquipmentFile');
        Route::any('getimage', 'getImage');
        Route::any('updateequipment', 'updateEquipment');
        Route::any('equipmentdelete', 'deleteEquipment');
        Route::any('onlypartgrouplist', 'getOnlyPartGroupList');
        Route::any('createpartgroup', 'createPartGroup');
        Route::any('createsupplier', 'createSupplier');
        Route::any('getchecklist', 'getCheckList');
        Route::any('createchecklistcategory', 'createChecklistCategory');
        Route::any('equipmentimagedel', 'delEquipmentImage');
        Route::any('importexcel', 'importExcel');
        Route::any('equipmentlistonppm', 'getEquipmentListonPPM');
        Route::any('postengchecklistitems', 'postEngCheckListItems');
        Route::any('creategroup', 'createGroup');
        Route::any('generateassetqrs', 'generateAssetQRS');
        Route::any('createmaintenance', 'createMaintenance');
        Route::any('sendemail', 'sendEmail');
    });
});

Route::prefix('part')->group(function () {
    Route::controller(EquipmentController::class)->group(function () {
        Route::any('partlist', 'getPartList');
        Route::any('createpart', 'CreatePart');
        Route::any('updatepart', 'updatePart');
        Route::any('partdelete', 'deletePart');
        Route::any('importexcelpart', 'importExcelPart');
    });
});

//alarm module
Route::prefix('alarm')->group(function () {
    Route::controller(AlarmController::class)->group(function () {
        Route::any('setting/creategroup', 'createSettingGroup');
        Route::any('setting/getgroup', 'getSettingGroup');
        Route::any('setting/deletegroup', 'deleteSettingGroup');
        Route::any('setting/createalarm', 'createSettingAlarm');
        Route::any('setting/getalarm', 'getSettingAlarm');
        Route::any('setting/deletedash', 'deleteSettingDash');
        Route::any('setting/getusers_alarms', 'getUsersAlarms');
        Route::any('setting/getsamegroupusers', 'getSameGroupUsers');
        Route::any('setting/getgroups_alarms', 'getGroupsAlarms');
        Route::any('setting/getusergroup', 'getUserGroup');
        Route::any('setting/createdash', 'createSettingDash');
        Route::any('setting/getdash', 'getSettingDash');
        Route::any('setting/getuserdash', 'getUserDash');
        Route::any('setting/getdashalarms', 'getDashAlarms');
        Route::any('setting/getsettinglist', 'getSettingList');
        Route::any('setting/createalarmsetting', 'createAlarmSetting');
        Route::any('setting/deletealarmsetting', 'deleteAlarmSetting');
        Route::any('setting/getacknow', 'getAcknow');
        Route::any('setting/getnotifistatus', 'getNotifiStatus');
        Route::any('dash/getuserlistofalarm', 'getUserListOfAlarm');
        Route::any('dash/getnotificationtype', 'getNotificationType');
        Route::any('dash/sendalarm', 'sendAlarm');
        Route::any('dash/getalarmnotifilist', 'getAlarmNotifiList');
        Route::any('dash/getalarmnotifiuserlist', 'getAlarmNotifiUserList');
        Route::any('dash/getalarmupdatelist', 'getAlarmUpdateList');
        Route::any('dash/changealarmstatus', 'changeAlarmStatus');
        Route::any('dash/changealarmstatusofuser', 'changeAlarmStatusOfUser');
        Route::any('dash/sendescalation', 'sendEscalation');
        Route::any('setting/changeimageofalarm', 'changeImageOfAlarm');
        Route::any('setting/getalarmgrouplist', 'getAlarmGroupList');

        Route::any('dash/exportlog', 'exportAlarmLogList')->withoutMiddleware('api_auth_group');
    });
});

Route::prefix('callaccount')->group(function () {
    Route::controller(CallaccountController::class)->group(function () {
        Route::any('guestcall', 'getGuestCalls');
        Route::any('bccall', 'getBCCalls');
        Route::any('guestcallforreport', 'getGuestCallsForReport');
        Route::any('admincall', 'getAdminCalls');
        Route::any('guestext', 'getGuestExtensionList');

        Route::any('statistics', 'getCallStatistics');
        Route::any('myadmincall', 'getMyAdminCalls');
        Route::any('mymobilecall', 'getMyMobileCalls');
        Route::any('mobiledetailcall', 'getDetailMobileCall');
        Route::any('getmyadmincallsfromfinance', 'getMyAdminCallsFromFinance');
        Route::any('getmymobilecallsfromfinance', 'getMyMobileCallsFromFinance');
        Route::any('myextlist', 'getMyAdminExtensionList');
        Route::any('submitapproval', 'submitApproval');
        Route::any('submitmobileapproval', 'submitMobileApproval');
        Route::any('approvallist', 'getApprovalUserList');
        Route::any('approvalmobilelist', 'getApprovalMobileUserList');
        Route::any('approvalapprove', 'updateApprovalApproveReject');
        Route::any('updatemobileapproval', 'updateApprovalMobileApproveReject');
        Route::any('departlist', 'getFinancelDepartList');
        Route::any('departlistmobile', 'getFinanceDepartListMobile');
        Route::any('approvalnotifylist', 'getApprovalNotifyList');
        Route::any('commentlist', 'getCallCommentList');
        Route::any('commentlistmobile', 'getCallCommentListMobile');
        Route::any('getdestinationname', 'getDestination');
        Route::any('submitcomment', 'submitComment');
        Route::any('submitcommentmobile', 'submitCommentMobile');
        Route::any('detailcalllist', 'getApprovalListForUser');
        Route::any('detailmobilelist', 'getApprovalMobileListForUser');
        Route::any('financedetailcalllist', 'getFinanceListForUser');
        Route::any('financedetailcalllistmob', 'getFinanceListMobForUser');
        Route::any('financedepartclose', 'updateFinanceDepartClose');
        Route::any('financemobdepartclose', 'updateFinanceMobDepartClose');
        Route::any('approvecall', 'approveCall');
        Route::any('approvemobilecall', 'approveMobileCall');
        Route::any('destlist', 'getDestinationList');
        Route::any('callranks', 'getCallRanks');
        Route::any('mycallstats', 'getMyCallStats');
        Route::any('gettracklist', 'getMobileTrackList');
        Route::any('syncstart', 'syncstatusChange');
        Route::any('sendreminder', 'callReminderMail');
        Route::any('deletetracklist', 'deleteMobileTrackList');
        Route::any('uploadimage', 'uploadMobileTrackList');
        Route::any('phonelist', 'getPhonebookList');
        Route::any('addphonebook', 'addPhonebook');
        Route::any('updatephonebook', 'updatePhonebook');
        Route::any('deletephonebook', 'deletePhonebook');
    });
});

Route::prefix('mod')->group(function () {	
    Route::controller(ModController::class)->group(function () {	
        Route::any('getchecklist', 'getCheckList');	
        Route::any('getchecklisttask', 'getCheckListTask');	
        Route::any('createchecklist', 'createCheckList');	
        Route::any('activechecklist', 'activeCheckList');	
        Route::any('categorylist', 'getCategoryList');	
        Route::any('getchecklistitemlist', 'getChecklistItemList');	
        Route::any('createchecklistcategory', 'createChecklistCategory');	
        Route::any('createchecklistitem', 'createChecklistItem');	
        Route::any('deletechecklistitem', 'deleteCheckListItem');	
        Route::any('deletechecklist', 'deleteCheckList');	
        Route::any('createchecklisttask', 'createChecklistTaskFromWeb');	
        Route::any('checklistresult', 'getChecklistResult');	
        Route::any('uploadchecklistfiles', 'uploadAttachForChecklist');	
        Route::any('updatechecklistattach', 'updateChecklistAttach');	
        Route::any('addchecklistitem', 'addChecklistItem');	
        Route::any('updatechecklistresult', 'updateChecklistResultFromWeb');	
        Route::any('deletechecklisttask', 'deleteChecklistTask');	
    });	
});

Route::prefix('forms')->group(function () {	
    Route::controller(FormController::class)->group(function () {
        Route::any('createliftusagerequest', 'createLiftUsageRequest');
        Route::any('liftformlist', 'getLiftList');
        Route::any('updatestatus', 'updateStatus');
        Route::any('prptylist', 'getPropertyList');
        Route::any('createpermittoworkrequest', 'createPermitWorkRequest');
        Route::any('permitformlist', 'getPermitList');
        Route::any('updatelease', 'updatePermitLease');
        Route::any('updateauthr', 'updatePermitAuth');
        Route::any('updatethird', 'updatePermitThird');
        Route::any('createhotworkrequest', 'createHotWorkRequest');
        Route::any('hotworkformlist', 'getHotworkList');
        Route::any('updateauth', 'updateHotworkAuth');
        Route::any('updatelist', 'updateHotworkInspection');
        Route::any('inspectlist', 'getInspectionList');
        Route::any('updatefinal', 'updateHotworkFinal');
        Route::any('updateclose', 'updateHotworkClose');
    });
});

Route::prefix('call')->group(function () {
    Route::controller(CallController::class)->group(function () {
        Route::any('outgoing', 'outgoingCall');
        Route::any('incoming', 'incomingCallFromSoftphone');

        Route::any('config', 'getCallcenterConfig');
        Route::any('agentextlist', 'getAgentExtensionList');
        Route::any('logs', 'getLogs');
        Route::any('aalogs', 'getAALogs');
        Route::any('history', 'getCallHistory');
        Route::any('statistics', 'getStatisticInfo');
        Route::any('agentstatus', 'getAgentStatus');
        Route::any('changestatus', 'changeAgentStatus');
        Route::any('changeextension', 'changeAgentExtension');
        Route::any('myapprovaluserlist', 'getApprovalUserList');
        Route::any('savecalllog', 'saveCallLog');
        Route::any('callphonebook', 'getCallPhonebook');
        Route::any('removephonebook', 'removeCallPhonebook');
        Route::any('addexceldata', 'addExcelData');

        Route::any('agentcalllist', 'getAgentCallList');
        Route::any('agentlist', 'getAgentList');
        Route::any('agentlist1', 'getAgentList1');
        Route::any('ivrcalltypelist', 'getIvrCallTypeList');
        Route::any('stafflist', 'getStaffList');
        Route::any('typelist', 'getTypeList');
        Route::any('calltypelist', 'getCallTypeList');
        Route::any('autotypelist', 'getAutoTypeList');
        Route::any('autocalltypelist', 'getAutoCallTypeList');
        Route::any('skillgroup', 'getSkillGroup');
        Route::any('channellist', 'getChannelList');
        Route::any('callbacklist', 'getCallbackList');
        Route::any('missedlist', 'getMissedList');
        Route::any('addcomment/{type}', 'addComment');
        Route::any('abandonlist', 'getAbandonList');
        Route::any('takecallback', 'takeCallback');
        Route::any('updatecalllog', 'updateCallLog');
        Route::any('queuecalllist', 'callQueueList');
        Route::any('changequeuepriority', 'callQueuePriority');
        Route::any('skilllist', 'getSkillList');
        Route::any('createskill', 'createSkill');
        Route::any('deleteskill', 'deleteSkill');
        Route::any('skillgrouplist', 'getSkillGroupList');
        Route::any('skillgrouplistuser', 'getSkillGroupListUser');
        Route::any('createskillgroup', 'createSkillGroup');
        Route::any('deleteskillgroup', 'deleteSkillGroup');
        Route::any('agentskillist', 'getAgentSkillList');
        Route::any('editagentskill', 'editAgentSkill');
        Route::any('agentskillevellist', 'getAgentSkillLevelList');
        Route::any('addagentskilllevel', 'addAgentSkillLevel');
        Route::any('deleteagentskilllevel', 'deleteAgentSkillLevel');
        Route::any('sipcontactlist', 'getSIPContactList');
        Route::any('storecallcenterprofile', 'storeCallcenterProfile');
        Route::any('filterlist', 'getFilterList');

        Route::any('timinginfo', 'getTimingInfo');
        Route::any('setdaystiminginfo', 'setDaysTimingInfo');
        Route::any('setdatestiminginfo', 'setDatesTimingInfo');
        Route::any('getcurrenttiminginfo', 'getCurrentTimingInfo');

        Route::any('threshold', 'getThresholdSetting');
        Route::any('savethreshold', 'saveThresholdSetting');
        Route::any('departlist', 'getDepartmentList');

        Route::any('getcalldetail', 'getCallDetail');
        Route::any('addusertophonebook', 'addUserToPhonebook');

        Route::any('moveup', 'moveUp');
        Route::any('movedown', 'moveDown');
        Route::any('takequeuecall', 'takeQueueCall');

        Route::any('exportphonebook', 'exportPhonebook')->withoutMiddleware('api_auth_group');
    });
});

Route::withoutMiddleware('api_auth_group')->group(function () {

    Route::any('/eng/id', [EquipmentController::class, 'getMaxID']);
    Route::any('/eng/post', [EquipmentController::class, 'createRequest']);

    Route::controller(ENGController::class)->group(function () {
        Route::any('eng_mytask/id', 'getID');
        Route::any('eng_mytask/stafflist', 'getStaffList');
        Route::any('eng_mytask/repairstafflist', 'getRepairStaffList');
        Route::any('eng_mytask/repairtenantlist', 'getTenantList');
        Route::any('eng_mytask/post', 'create');
        Route::any('eng_mytask/updateeng', 'updateEng');
        Route::any('eng_mytask/uploadfiles', 'uploadFiles');
        Route::any('eng_mytask/englist', 'getEngList');
        Route::any('eng_mytask/catlist', 'getCategoryList');
        Route::any('eng_mytask/subcatlist', 'getSubCategoryList');
        Route::any('eng_mytask/assigneelist', 'getAssigneeList');
        Route::any('eng_mytask/statuslist', 'getStatusList');
        Route::any('eng_mytask/severitylist', 'getSeverityList');
        Route::any('eng_mytask/enginformlist', 'getEngInformList');
        Route::any('eng_mytask/getimage', 'getImage');
        Route::any('eng_mytask/uploadsubfiles', 'uploadFilesToEng');
        Route::any('eng_mytask/removefiles', 'removeFilesFromEng');
        Route::any('eng_mytask/updatestatus', 'updateStatus');
        Route::any('eng_mytask/updatecategory', 'updateCategory');
        Route::any('eng_mytask/updatesubcategory', 'updateSubcategory');
        Route::any('eng_mytask/updateassignee', 'updateAssignee');
        Route::any('eng_mytask/updatesupplier', 'updateSupplier');
        Route::any('eng_mytask/savecomment', 'saveComment');
        Route::any('eng_mytask/requesthist', 'getEngRequestHistory');
        Route::any('eng_mytask/updateseverity', 'updateSeverity');
        Route::any('eng_mytask/createsupplier', 'createSupplier');
        Route::any('eng_mytask/deletesupplier', 'deleteSupplier');
    });
});