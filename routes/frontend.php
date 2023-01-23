<?php

use App\Http\Controllers\Backoffice\Guest\HSKPController;
use App\Http\Controllers\Backoffice\Guest\MinibarController;
use App\Http\Controllers\Backoffice\Guest\MinibarItemController;
use App\Http\Controllers\Backoffice\Property\BuildingWizardController;
use App\Http\Controllers\Backoffice\Property\RoomtypeWizardController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DataController;
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
Route::get('/buildsomelist', [BuildingWizardController::class, 'getBuildingSomeList']);
Route::any('/user/setactivestatus', [UserController::class, 'setActiveStatus']);
Route::any('/workorder/uploadchecklistfiles', [EquipmentController::class, 'uploadAttachForChecklist']);

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
    });
    
    Route::any('manualpost', [ProcessController::class, 'postManual']);
    Route::get('roomtypelist',  [RoomtypeWizardController::class, 'getRoomTypeList']);

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
        Route::any('categorylist', 'getCategoryList');
        Route::any('statistics', 'getStatisticInfo');
        Route::any('fblist', 'getfbList');
        Route::any('feedbackid', 'getfeedbackID');
        Route::any('searchcoguestlist', 'searchCOGuestList');
        Route::any('occasionlist', 'getOccasionList');
        Route::any('postfeedback', 'createFeedback');
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
    });
});

Route::prefix('part')->group(function () {
    Route::controller(EquipmentController::class)->group(function () {
        Route::any('partlist', 'getPartList');
        Route::any('createpart', 'CreatePart');
        Route::any('updatepart', 'updatePart');
        Route::any('partdelete', 'deletePart');
    });
});