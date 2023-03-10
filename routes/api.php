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
use App\Http\Controllers\Frontend\RepairRequestController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\AngularController;
use App\Http\Controllers\EncryptGateway;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\GuestChatController;
use App\Http\Controllers\OfflineInterfaceController;
use App\Http\Controllers\Frontend\AlarmController as FrontAlarmController;
use App\Http\Controllers\Frontend\CallController;
use App\Http\Controllers\Frontend\ComplaintController;
use App\Http\Controllers\Frontend\ENGController;
use App\Http\Controllers\Frontend\EquipmentController;
use App\Http\Controllers\Frontend\FormController;

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

Route::post('/42yUojv1YQPOssPEpn5i3q6vjdhh7hl7djVWDIAVhFDRMAwZ1tj0Og2v4PWyj4PZ/webhook', function () {
    $update = Telegram::commandsHandler(true);
});

Route::get('/setwebhook', function () {
    $response = Telegram::setWebhook(['url' => 'https://develop1.myhotlync.com/42yUojv1YQPOssPEpn5i3q6vjdhh7hl7djVWDIAVhFDRMAwZ1tj0Og2v4PWyj4PZ/webhook']);
    // dd($response);
    return response($response);
});
Route::any('api/updatedndivr', [ProcessController::class, 'updateDNDIvr']);
Route::any('process/manualnightaudit', [ProcessController::class, 'manualNightAudit']);

Route::get('hotlyncGU-S', [FrontendController::class, 'guestSimulator']);
Route::any('dbinstall', [FrontendController::class, 'db_install']);

Route::get('complaint/sendmaila', [ComplaintController::class, 'sendMailApprove']);

//Angular Routing
Route::any('/assets/angular/{any}', [AngularController::class, 'index'])->where('any', '^(?!api).*$');

Route::group(['middleware' => ['cors']], function () {
    Route::get('alarm/changeacknowledge', [FrontAlarmController::class, 'changeAcknowledge']);
    Route::get('al/{id}', [FrontAlarmController::class, 'changeAcknowledge1']);
});

Route::any('/mobileserver/sendalarm', [FrontAlarmController::class, 'sendMobileserverAlarm']);

// Route::any("/test-route", [RepairRequestController::class, 'getMyRepairListForMobile']);

Route::get('dropdown/floor', [AjaxController::class, 'dropdownfloor']);
Route::get('dropdown/deft', [AjaxController::class, 'dropdowndept']);

Route::post('storelicense', [PropertyWizardController::class, 'storeLicense']);
Route::post('licensekey', [PropertyWizardController::class, 'licenseKey']);
Route::get('room/list', [RoomWizardController::class, 'getRoomList']);
Route::any('uploadpicture', [UploadController::class, 'uploadpicture']);
Route::any('upload', [UploadController::class, 'upload']);

Route::post('checklicense', [LicenseWizardController::class, 'checkLicense']);

Route::post('hotlync/checklicense', [LicenseWizardController::class, "checkLicense"]);
Route::any('guest/roomlist', [GuestController::class, 'getRoomList']);
Route::any('guest/login', [GuestController::class, 'login']);
Route::any('guest/logout', [GuestController::class, 'logout']);

Route::get('eng_mytask/sendmail', [ENGController::class, 'sendMail']);
Route::get('eng_mytask/sendmaila', [ENGController::class, 'sendMailApprove']);

Route::any('/equipment/exceltest', [EquipmentController::class, 'exceltest']);
Route::any('/equipment/mailtest', [EquipmentController::class, 'sendEmail']);

Route::get('form_request/sendmaila', [FormController::class, 'sendMailApprove']);

Route::group(['middleware' => ['auth_key']], function () {
    Route::get('api/getfeedbacktypelist', [ComplaintController::class, 'getFeedbackTypeListforGuestApp']); 
    Route::post('api/createguestfeedback', [ComplaintController::class, 'createFeedbackGuestApp']); 
});

// Offline Interface API
Route::any('api/setservicesromstatus', [OfflineInterfaceController::class, 'setServicesRoomStatus']);
Route::any('api/getcommonroomtable', [OfflineInterfaceController::class, 'getCommonRoomTable']);

//GUEST CHAT
Route::any('chat/guestchatregister', [GuestChatController::class, 'guestChatRegister']);
Route::any('chat/guestchatunregister', [GuestChatController::class, 'guestChatUnregister']);
Route::any('chat/guestchatrecievemsg', [GuestChatController::class, 'guestChatRecieveMsg']);

Route::any('hskp/publicAreaGetTasksMain', [HSKPController::class, 'publicAreaGetTasksMain']);
Route::any('hskp/publicAreaAddTaskMain', [HSKPController::class, 'publicAreaAddTaskMain']);
Route::any('hskp/publicAreaEditTaskMain', [HSKPController::class, 'publicAreaEditTaskMain']);
Route::any('hskp/publicAreaGetTasksByMainId', [HSKPController::class, 'publicAreaGetTasksByMainId']);
Route::any('hskp/publicAreaGetLocationsWithIds', [HSKPController::class, 'publicAreaGetLocationsWithIds']);
Route::any('hskp/publicAreaEditTask', [HSKPController::class, 'publicAreaEditTask']);
Route::any('hskp/publicAreaEditTaskActive', [HSKPController::class, 'publicAreaEditTaskActive']);
Route::any('hskp/publicAreaAddTask', [HSKPController::class, 'publicAreaAddTask']);

Route::controller(UserController::class)->group(function () {
    Route::post('auth/login', 'login');
    Route::post('auth/changepassword', 'changePassword');
    Route::any('auth/sendpassword', 'SendPassword');
    Route::post('auth/forgotsendpassword', 'forgotSendPassword');
    Route::post('auth/sendexpirymail', 'SendExpired');
    Route::post('auth/getpassgroup', 'GetPasswordGroup');
    Route::get('auth/active', 'Active');
    Route::post('auth/getcompareflag', 'GetCompareFlag');
    Route::any('call/getconfig', 'GetConfig');
    Route::post('desktop/login', 'loginDesktop');
});

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
    Route::get('getcurrency', 'getCurrency');
    Route::any('build/list', 'getBuildList');
    Route::any('floor/list', 'getFloorList');
    Route::any('userlistwithids', 'userlistwithids');
    Route::any('deletenotify', 'clearMobileNotificationList');
    Route::any('mymanagerlist', 'getDataForMyManager');

});

Route::group(['prefix' => 'backend_api/', 'middleware' => ['api_auth_group']], function () {
    Route::get('multipropertylist', [DataController::class, 'getMultiPropertyList']);
});

Route::get('backoffice/user/jobrole', [UserWizardController::class, 'getJobRoles']);

Route::controller(ChatController::class)->group(function () {

    Route::any('chatbothistory-simulator', 'getChatbotHistorySimulator');
    Route::any('savechatbothistory-simulator', 'saveChatbotHistorySimulator');
    Route::any('getnextchatcontent-simulator', 'getNextChatContentSimulator');
    Route::any('addnewquicktask', 'addNewQuickTask');
    Route::any('calltoagent', 'callToAgent');
    Route::any('requestchat-simulator', 'requestChatSimulator');
    Route::any('receive-media', 'receiveMedia');
    Route::any('downloadchathistorytopdf', 'downloadChatHistoryToPdf');
    Route::any('chat/onwhatsappmsg', 'onReceiveMessageFromWhatsapp');
    Route::any('chat/gettelegrammessage', 'getTelegramBotMessage');
    Route::any('chat/onmetawhatsappmsg', 'onMetaWhatsapp');
    Route::any('chat/onmetawhatsappmsgverificationtest', 'onMetaWhatsappVerificationTest');
    Route::any('chat/sendbmetawhatsappmsgtest', 'sendBusinessMessageToWhatsappTest');//sendmetawhatsappmsg //new api test
    Route::any('chat/sendmetawhatsappmsgtest', 'sendMessageToWhatsappTest');//sendmetawhatsappmsg //new api test
    Route::any('chat/sendmetawhatsappfromliveserver', 'sendMetaWhatsappFromLiveserver'); //new api outgoing
    Route::any('chat/sendmetawhatsappmsgdoctest', 'sendMessageToWhatsappDocTest');
    Route::any('chat/senddocmessagetowhatsapp', 'sendDocMessageToWhatsapp');
    Route::any('chat/scsetnewcallagentchat', 'scSetNewCallAgentChat');
    Route::any('chat/sendscmetawhatsappfromliveserver', 'sendSCMetaWhatsappFromLiveserver');
    Route::any('chat/sendsclocationmetawhatsappfromliveserver', 'sendSCLocationMetaWhatsappFromLiveserver');

    //internal chat
    Route::any('chat/devicecreatechatsession', 'deviceCreateChatSession');
    Route::any('chat/devicesendmessage', 'deviceSendMessage');
    Route::any('chat/devicesendchattoagent', 'deviceSendChatToAgent');

    //whatsapp Staycae button urls
    Route::any('chat/wascchatfirstquestiona1', 'waSCChatFirstQuestionA1');
    Route::any('chat/wascchatfirstquestiona2', 'waSCChatFirstQuestionA2');

    // QQQ apis for chat from socket server
    Route::group(['prefix' => '/api/chat'], function()  {
	    Route::any('sendagentagentmessage', 'sendAgentAgentMessage');
	    Route::any('changeagentagentreadstatus', 'changeAgentAgentReadStatus');
        Route::any('changeguestagentreadstatus', 'changeGuestAgentReadStatus');

	    Route::any('uploadpictures', 'uploadPictures');
	    Route::any('uploadfiles', 'uploadFilesNew');

        Route::any('sendgroupchatmessage', 'sendGroupChatMessage');
        Route::any('sendleavefromgroup', 'sendLeaveFromGroup');
        Route::any('senddeletegroup', 'sendDeleteGroup');
        Route::any('sendgroupchatchanged', 'sendGroupChatChanged'); // creatd or updated
        Route::any('sendgroupchatfocused', 'sendGroupChatFocused'); // group chat focused
    });
});
// QQQ apis for chat from socket server
Route::any('api/chat/changeuserstatus', [UserController::class, 'changeUserActiveStatus']);

// Mobile Api

Route::post('/api/logout', [UserController::class, 'logoutFromDevice']);
Route::any('api/getcurrenttiminginfo', [CallController::class, 'getCurrentTimingInfo']);

Route::group(['prefix' => 'api/', 'middleware' => ['api_mobile_group', 'hash_check']], function () {

    Route::any('uploadpicture', [UploadController::class, 'uploadpicture']);
    Route::any('mobileprofilephoto', [UploadController::class, 'mobileprofilephoto']);

    Route::controller(EquipmentController::class)->group(function () {
        Route::any('getpreventivemaintenancelist', 'getPreventiveMaintenanceListForMobile');
        Route::any('createworkordermanual', 'createWorkorderManualForMobile');
        Route::any('getequiplistbygroupid', 'getEquipListByGroupId');

        //=================== Engineering API =======================================================
        Route::any('workorder/filterlist', 'getWorkorderFilterList');
        Route::any('workorder/statuscount', 'getMyWorkorderStatusCount');
        Route::any('workorder/mylist', 'getMyWorkorderList');
        Route::any('workorder/mylistformobile', 'getMyWorkorderListForMobile');

        // QQQ
        Route::any('eng/getequipmentorgrouplist', 'getEquipmentOrGroupListForMobile');


        Route::any('workorder/detail', 'getWorkOrderDetail');
        Route::any('workorder/update', 'updateWorkorderFromMobile');
        
        Route::any('workorder/changestatus', 'changeWorkorderStatus');
        Route::any('workorder/changestatustocomplete', 'changeWorkorderStatusToComplete');

        Route::any('workorder/uploadfiles', 'uploadFilesToWorkOrder');
        Route::any('workorder/deletefile', 'deleteFileFromWorkOrder');
        Route::any('workorder/confirminspected', 'confirmInspected');

        Route::any('workorder/listbystaff', 'getMyWorkorderListByStaff');

        Route::any('workorder/gethistorylist', 'getWorkorderHistoryList');
        Route::any('workorder/updatehistory', 'updateWorkOrderHistory');
        Route::any('workorder/deletehistory', 'deleteWorkorderHistory');

        Route::any('workorder/checklist', 'getChecklistForWorkorder');
        Route::any('workorder/updatechecklist', 'updateChecklistForWorkorder');

        Route::any('workorder/updatechecklistcomment', 'updateChecklistCommentForWorkorder');
        Route::any('workorder/updatechecklistattach', 'updateChecklistAttachForWorkorder');

        Route::any('workorder/updatechecklistoneitem', 'updateChecklistOneItemForWorkorder');
        Route::any('workorder/updatechecklistbatch', 'updateChecklistBatchForWorkorder');
        Route::any('workorder/flagworkorder', 'flagWorkorder');

        Route::any('workorder/start', 'startWorkorder');
        Route::any('workorder/hold', 'holdWorkorder');
        Route::any('workorder/resume', 'resumeWorkorder');
        Route::any('workorder/finish', 'finishWorkorder');
        Route::any('workorder/precomments', 'getWOCommentList');

        Route::any('workorder/uploadsignature', 'uploadSignature');
        Route::any('repair/equiplist', 'getEquipmentNameList');
        Route::any('repair/getstaffgrouplist', 'getStaffGroupList');

        //Equipment API's

        /// QQQ //// 
		Route::any('equipment/changeassetstatus','changeAssetStatus');    	    

        Route::any('equipment/getequipmentlist','getEquipList');
        Route::any('equipment/getequipmentlistitem','getEquipListItem');
        Route::any('equipment/createworkordermanualbyasset', 'createWorkorderManualByAssetForMobile');
        
        Route::any('equipment/getequipmentlistforengineering','getEquipListForEngineering');

        Route::any('equipment/getpmlistbyequipid','getPreventiveMaintenanceByEquipId');
        Route::any('equipment/equipmentinformlist', 'getEquipmentInformListForMobile');
        Route::any('equipment/equipmentworkorderlist', 'getEquipmentWorkorderListForMobile');
        Route::any('equipment/equipmentconfiglist', 'getConfigListForMobile');

        Route::any('equipment/createequipment', 'CreateEquipmentForMobile');
        Route::any('equipment/updateequipment', 'updateEquipmentForMobile');

        Route::any('equipment/createequipmentfile', 'createEquipmentFileForMobile');


        //=================== Engineering API =======================================================

    });

    Route::controller(UserController::class)->group(function () {
        Route::any('changepasswordfrommobile', 'changePassword');
        Route::post('updateprofile', 'updateProfilefromMobile');
        Route::post('updatenotifysetting', 'updateNotifySetting');
        Route::post('updateprofilelanguage', 'updateProfileLanguage');
        Route::post('updateprofilesound', 'updateProfileSound');
        Route::post('updateprofilemute', 'updateProfileMute');
        
        Route::any('setonline', 'setOnline');

        Route::any('selectsecondaryjobroles', 'selectSecondaryJobRoles');

        Route::any('secondaryjobroles', 'loadSecondaryJobRoles');
        Route::any('savesecondaryjobroles', 'saveSecondaryJobRoles');

        // added global lists
        Route::any('loaduserpermissions', 'loadUserPermissions');
    });

    Route::controller(DataController::class)->group(function () {
        Route::any('loaddata', 'loadData');
        Route::any('loaddatanew', 'loadDatanew');
        Route::any('getcurrency', 'getCurrencyforMobile');
        Route::any('propertylist', 'getPropertyList');
        Route::any('floorlist', 'getFloorListMobile');
        Route::any('locationlist', 'getLocationList');
        Route::any('roomlist', 'getRoomList');
        Route::any('notifylist', 'getMobileNotificationList');
        Route::any('deletenotify', 'clearMobileNotificationList');
        Route::any('mymanagerlist', 'getDataForMyManager');
        Route::any('mymanagerlistgs', 'getDataForMyManagerGS');
        Route::any('occupancy_percent', 'getOccupancyStatistics');

        // added global lists
        Route::any('loadcompensationlist', 'loadCompensationList');
        Route::any('loadstafflist', 'loadStaffList');
        Route::any('loadminibaritemlist', 'loadMinibarItemList');
        Route::any('loadguestrequestlist', 'loadGuestRequestListRequest');
        Route::any('loadroomlist', 'loadRoomList');
        Route::any('loadtaskactionreasonlist', 'loadTaskActionReasonList');
        Route::any('loadlocationlist', 'loadLocationList');

        // added by GNH
        Route::any('hskpsetting', 'getHskpSettingInfo');

        Route::any('badgecount', 'getBadgeCount');
        Route::any('chat/unreadcount', 'getChatUnreadCount');
    });

    Route::any('englist', [ENGController::class, 'getEngList']);
    Route::any('enginformlist', [ENGController::class, 'getEngInformListfromMobile']);
    Route::any('engupdateassignee', [ENGController::class, 'updateAssignee']);
    Route::any('engupdatecategory', [ENGController::class, 'updateCategory']);
    Route::any('engupdatesubcategory', [ENGController::class, 'updateSubcategory']);

    // IT api, QQQ
    Route::any('complaintsubmylist', [ComplaintController::class, 'getSubMyListfromMobile']);
    Route::any('engupdateseverity', [ENGController::class, 'updateSeverity']);
    Route::any('engupdatestatus', [ENGController::class, 'updateStatusFromMobile']);

    // chat for mobile  
    Route::controller(ChatController::class)->group(function () {
        // group chat 
        Route::any('chat/group/list', 'getGroupChatList');

        // QQQ guest chat 
        Route::any('chat/guest/list', 'getGuestChatList');
        Route::any('chat/guest/getfilterinfo', 'getGuestChatFilterInfo');
        Route::any('chat/guest/setfilterinfo', 'setGuestChatFilterInfo');
        Route::any('chat/guest/acceptchat', 'acceptChat');
        Route::any('chat/guest/endchat', 'endChatFromAgent');
        Route::any('chat/guest/deletephonebook', 'deletePhonebookInfo');
        Route::any('chat/guest/setguestspaminfo', 'setGuestSpamInfo');
        Route::any('chat/guest/senddocmessagetowhatsapp', 'sendDocMessageToWhatsappForMobile');
        
        Route::any('chat/guest/sendmessagefromagent', 'sendMessageFromAgent');
    
        Route::any('chat/guest/transferchat', 'transferChat');
        Route::any('chat/guest/canceltransfer', 'cancelTransfer');
        
        Route::any('chat/guest/uploadpicture', 'uploadGuestChatPicture');
        Route::any('chat/guest/uploadfile', 'uploadGuestChatFile');
    
        Route::any('chat/guest/chatsessionlistformobile', 'getChatSessionListForMobile');
        Route::any('chat/guest/chatsessiondetailinfo', 'getChatSessionDetailInfo');
    
        Route::any('chat/guest/chathistory', 'getChatHistoryForAgentForMobile');
     
        Route::any('chat/loadinitlist', 'loadInitList');
    
        // QQQ group chat 
        Route::any('chat/group/listnew', 'getGroupChatListNew'); // get group chat list
        Route::any('chat/createchatgroup', 'createChatGroup'); // create a group chat 
        Route::any('chat/group/detaillist', 'getGroupDetailList'); // load group chat history
    
        // QQQ agent chat
        Route::any('chat/agentlistnew', 'getChatAgentListNew'); // load agent list for agent chat
        Route::any('chat/agentlistonly', 'getChatAgentListOnly'); // load agent list for agent chat
    
        Route::any('chat/agentagentchatlist', 'getAgentAgentChatList'); // agent agent chat list

        Route::any('chat/getagentprofile', 'getAgentProfile');
    });

    Route::controller(ComplaintController::class)->group(function () {
        Route::any('common/getdepartmentlist', 'getDepartmentList');

        Route::any('complaint/getcomplaintconfiglist', 'getComplaintConfigList');

        Route::any('complaint/info', 'getComplaintInfoData');
        Route::any('complaint/getcomplaintinfo', 'getComplaintDetailInfo');

        Route::any('complaint/mainsubcategory', 'getMainSubCategoryList');
        Route::any('complaint/detailinfo', 'create');
        Route::any('complainttypecount', 'getComplaintTypeCount');
        Route::any('complaintlist', 'getListfromMobile');
        Route::any('complaintlistformobile', 'getComplaintListForMobile');

        Route::any('complaintsublist', 'getSubcomplaintListForMobile');
        Route::any('severitylist', 'getSeverityList');
        Route::any('housecomplaintlist', 'getHouseComplaintList');
        Route::any('changeseverity', 'changeSeverity');

        /// QQQ
        Route::any('complaint/ack', 'acknowledge');
        Route::any('complaint/reject', 'reject');
        Route::any('complaint/resolve', 'resolve');
        Route::any('complaint/close', 'close');
        Route::any('complaint/reopen', 'reopen');
        Route::any('complaint/repending', 'repending');
        Route::any('complaint/unresolve', 'unresolve');

        Route::any('complaint/forward', 'forward');
        Route::any('complaintitems', 'getComplaintItemList');
        Route::any('complaint/selectassignee', 'selectAssignee');
        Route::any('complaint/assignsubcomplaint', 'assignOneSubcomplaint');
        Route::any('complaint/compensationtype', 'getCompensationType');
        Route::any('complaint/postcompensation', 'postCompensation');
        Route::any('complaint/addcommentoreturn', 'addCommentToReturn');
        Route::any('complaint/stafflist', 'getStaffList');
        Route::any('complaint/findcheckinguest', 'findCheckinGuest');
        Route::any('complaint/searchguestlist', 'searchGuestList');
        Route::any('complaint/searchcheckoutguestformobile', 'getCheckoutGuestListForMobile');
        Route::any('complaint/searchcoguestlist', 'searchCOGuestList');
        Route::any('complaint/create', 'create');
        Route::any('complaint/update', 'update');
        Route::any('complaint/guesthistory', 'getGuestHistory');
        Route::any('complaint/saveguestprofile', 'saveGuestProfile');
        Route::any('complaint/logs', 'getComplaintLogsForMobile');

        Route::any('complaint/addcomment', 'addComment');
        Route::any('complaint/updatecomment', 'updateComment');
        Route::any('complaint/deletecomment', 'deleteComment');
        Route::any('complaint/updatefilepath', 'updateFilePath');
        Route::any('complaint/senddept', 'sendDeptForMobile');
        Route::any('complaint/uploadfiles', 'uploadFiles');
    });
        
});

// guest office page
Route::group(['prefix' => 'guest/', 'middleware' => ['guest_group']], function () {
    Route::controller(ChatController::class)->group(function () {
        Route::any('requestchat', 'requestChat');
        Route::any('endchatfromguest', 'endChatFromAgent');
        Route::any('destroychat', 'destroyChat');
        Route::any('chathistory', 'getChatHistoryForGuest');

        Route::any('getnextchatcontent', 'getNextChatContent');
        Route::any('addnewquicktask', 'addNewQuickTask');
        Route::any('savechatbothistory', 'saveChatbotHistory');
        Route::any('chatbothistory', 'getChatbotHistory');
        Route::any('calltoagent', 'callToAgent');
    });
});

Route::prefix('call')->group(function () {
    Route::controller(CallController::class)->group(function () {
        Route::any('lockfile', 'generateLockFile');
        Route::any('incoming', 'incomingCall');
        Route::any('checkagent', 'checkAgent');
        Route::any('redirectincoming', 'redirectIncomingCall');
        Route::any('abandoned', 'abandonedCall');
        Route::any('answer', 'answerCall');
        Route::any('endcall', 'endCall');
        Route::any('agentbusy', 'agentBusy');
        Route::any('checkfreeagent', 'agentFree');
        Route::any('joinqueue', 'joinQueue');
        Route::any('callqueue', 'callQueue');
        Route::any('leavequeue', 'leaveQueue');
        Route::any('holdresume', 'holdResume');
        Route::any('mute', 'mute');
        Route::any('transfer', 'transfer');
        Route::any('hangup', 'hangup');
    });
});

Route::any('chat/guestmessage', [ChatController::class, 'onReceiveMessageFromGuest']);
Route::any('chat/agentmessage', [ChatController::class, 'onReceiveMessageFromAgent']);

Route::group(['prefix' => 'react', 'middleware' => ['cors']], function () {
    
    //LOGIN
    Route::post('/api/login', [UserController::class ,'login']);
    Route::post('/api/getcompareflag', [UserController::class ,'GetCompareFlag']);

    //GUEST SERVICE DASHBOARD
    Route::any('call/agentstatus', [CallController::class, 'getAgentStatus']);
    Route::any('chat/unreadcount', [DataController::class, 'getChatUnreadCount']);

    Route::get('/list/react/{name}', [DataController::class, 'getList']);
});

Route::group(['prefix' => 'reacttest', 'middleware' => ['api_auth_group']], function () {
    Route::post('/api/reacttest', [UserController::class ,'ReactTest']);
    Route::get('/api/reacttest', [UserController::class ,'ReactTest']);
});


// fix db url
Route::any('fixcallchargedestid', [ProcessController::class, 'fixCallChargeDestId']);
Route::any('testpreapproved', [ProcessController::class, 'testPreapprovedCallTypes']);
Route::any('fixcallchargerateid', [ProcessController::class, 'fixRateId']);
Route::any('migratecompensationlist', [ComplaintController::class, 'migrateCompensationItem']);

// test url
Route::any('testupdate', [ProcessController::class, 'testUpdate']);

Route::any('call/statistics', [CallController::class, 'getStatisticInfo']);
Route::any('fixagentduration', [CallController::class, 'fixAgentStatusDuration']);
Route::any('testavailable', [CallController::class, 'checkAgentAvailable']);
Route::any('testphpgraph', [CallController::class, 'testPHPGraph']);
Route::any('test/callthreshold', [CallController::class, 'testCheckThreshold']);
Route::any('test/queueevent', [CallController::class, 'testQueueChangeEvent']);

Route::any('test/encrypt', [EncryptGateway::class, 'encrypt']);
Route::any('test/file_encrypt', [EncryptGateway::class, 'file_encrypt']);
Route::any('test/dbconnect', [EncryptGateway::class, 'testDBConnect']);

Route::get('test/openssl', [ProcessController::class, 'testOpenSSl']);

Route::any('testcomplaint', [ComplaintController::class, 'testComplaintReport']);
Route::any('fixsubcomptotal', [ComplaintController::class, 'fixSubcomplaintTotal']);
Route::any('testcomplaint', [ComplaintController::class, 'checkSubComplaintLocStateProc']);
Route::any('test/notifycomplaint', [ComplaintController::class, 'testNotifyComplaint']);
Route::any('test/flagguest', [ComplaintController::class, 'testCheckFlagGuest']);
Route::any('test/briefingsummary', [ComplaintController::class, 'testSendBriefingSummary']);
Route::any('test/complaintsetting', [ComplaintController::class, 'testComplaintNotifySetting']);
Route::any('test/complaint/flagguest', [ComplaintController::class, 'testGuestFlagEmailTemplate']);
Route::any('test/complaint/briefinginvite', [ComplaintController::class, 'testBriefingRoomInviteLink']);

