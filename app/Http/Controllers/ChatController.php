<?php

namespace App\Http\Controllers;

use App\Models\Common\CommonUser;
use App\Models\Common\Department;
use App\Models\Common\Property;
use App\Models\Common\SystemNotification;
use App\Models\Service\AgentChatHistory;
use App\Models\Service\DeftFunction;
use App\Models\Service\Escalation;
use App\Models\Service\GuestChatSession;
use App\Models\Service\Location;
use App\Models\Service\LocationType;
use App\Models\Service\Priority;
use App\Models\Service\RosterList;
use App\Models\Service\ShiftUser;
use App\Models\Service\Task;
use App\Models\Service\TaskList;
use App\Modules\Functions;
use DB;
use Illuminate\Http\Request;
use Redis;
use Response;
use File;

define("WAITING", 1);
define("ACTIVE", 2);
define("ENDED", 3);
define("TRANSFER", 4);

define("COMPLETEDCHAT", 0);
define("OPEN", 1);
define("ESCALATED", 2);
define("TIMEOUT", 3);
define("CANCELED", 4);
define("SCHEDULED", 5);
define("UNASSIGNED", 6);

// for in-house
// names for chat template (content and chat name)
define("CHAT_IN_HOUSE_WELCOME", "in_house_welcome");
define("CHAT_IN_HOUSE_SELECT_LANGUAGE", 'in_house_select_language');
define("CHAT_IN_HOUSE_LANGUAGE_SELECTED", 'in_house_language_selected');
define("CHAT_IN_HOUSE_MAIN_MENU", 'in_house_main_menu');

define("CHAT_IN_HOUSE_REQUEST_MENU", "in_house_request_menu");
define("CHAT_IN_HOUSE_REQUEST_QUANTITY", "in_house_request_quantity");
define("CHAT_IN_HOUSE_REQUEST_SUCCESS", "in_house_request_success");
define("CHAT_IN_HOUSE_REQUEST_FAILED", "in_house_request_failed");
define("CHAT_IN_HOUSE_REQUEST_INVALID", "in_house_request_invalid");

define("CHAT_IN_HOUSE_FEEDBACK_MAIN_MENU", 'in_house_feedback_main_menu');

define("CHAT_IN_HOUSE_FAQ_MAIN_MENU", 'in_house_faq_main_menu');

// for outside
define("CHAT_OUTSIDE_WELCOME", 'outside_welcome');

// for outside + in-hosue faq
define("CHAT_OUTSIDE_FAQ_MAIN_MENU", 'outside_main_menu');

define("CHAT_FAQ_RECEPTION_MENU", 'outside_reception_menu');
define("CHAT_FAQ_RECEPTION_ANSWERS", 'outside_reception_answers');

define("CHAT_FAQ_SPA_MENU", 'outside_spa_menu');
define("CHAT_FAQ_SPA_ANSWERS", 'outside_spa_answers');

define("CHAT_FAQ_ROOM_RESERVATION_MENU", 'outside_room_reservation_menu');
define("CHAT_FAQ_ROOM_RESERVATION_ANSWERS", 'outside_room_reservation_answers');

define("CHAT_FAQ_RESTAURANT_MENU", 'outside_restaurant_menu');
define("CHAT_FAQ_RESTAURANT_ANSWERS", 'outside_restaurant_answers');

define("CHAT_FAQ_CONCIERGE_MENU", 'outside_concierge_menu');
define("CHAT_FAQ_CONCIERGE_ANSWERS", 'outside_concierge_answers');

define("CHAT_FAQ_HOTEL_MENU", 'outside_hotel_menu');
define("CHAT_FAQ_HOTEL_ANSWERS", 'outside_hotel_answers');

// for question
define("CHAT_QUESTION_FIRST", "question_first");
define("CHAT_QUESTION_ROOM", "question_room");
define("CHAT_QUESTION_GUEST_NAME", "question_guestname");

// for general

define("CHAT_WRONG", "wrong");
define("CHAT_INVALID", "invalid");
define("CHAT_WELCOME", "welcome");

define("CHAT_AGENT_CALL_WAITING", "agent_call_waiting");

define("CHAT_AGENT_LOGOUT", 'agent_logout');


class ChatController extends Controller
{
    public function getChatSessionList(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date('Y-m-d H:i:s');
        $last24 = date('Y-m-d H:i:s', strtotime(' -1 day'));

        $property_id = $request->get('property_id', 0);
        $agent_id = $request->get('agent_id', 0);
        $status = $request->get('status', 'Today');

        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');

        $room_ids = $request->get('room_ids', []);
        $agent_ids = $request->get('agent_ids', []);

        $search_text = $request->get('search_text', "");

        $query = DB::table('services_chat_guest_session as cgs')
            // ->leftJoin('common_guest as cg', 'cgs.guest_id', '=', 'cg.guest_id')
            ->leftJoin('common_guest as cg', function ($join) use ($property_id) {
                $join->on('cgs.guest_id', '=', 'cg.guest_id');
                $join->on('cgs.property_id', '=', 'cg.property_id');
            })
            ->leftJoin('common_users as cu', 'cgs.agent_id', '=', 'cu.id')
            ->leftJoin('common_users as cu1', 'cgs.transfer_id', '=', 'cu1.id')
            ->join('common_room as cr', 'cgs.room_id', '=', 'cr.id')
            ->leftJoin('common_room_type as crt', 'cr.type_id', '=', 'crt.id')
            ->leftJoin('common_language_code as lc', 'cgs.language', '=', 'lc.code')
            ->where('cgs.property_id', $property_id);

        if ($agent_id > 0)
            $query->where('cgs.agent_id', $agent_id);

        if ($status == 'Today') {
            $query->whereIn('cgs.status', array(WAITING, ACTIVE));
            $query->where('cgs.updated_at', '>=', $last24);
        } else {
            if ($status != 'All')
                $query->where('cgs.status', $status);

            if (!empty($start_date))
                $query->where('cgs.updated_at', '>=', $start_date . ' 00:00:00');

            if (!empty($end_date))
                $query->where('cgs.updated_at', '<=', $end_date . ' 23:59:59');

            if (!empty($agent_ids) && count($agent_ids) > 0)
                $query->whereIn('cgs.agent_id', $agent_ids);

            if (!empty($room_ids) && count($room_ids) > 0)
                $query->whereIn('cgs.room_id', $room_ids);
        }

        if (!empty($search_text)) {
            $where = sprintf("(cgs.id like '%%%s%%' or 
            cgs.guest_name like '%%%s%%' or
            cg.mobile like '%%%s%%')",
                $search_text, $search_text,
                $search_text
            );

            $query->whereRaw($where);
        }

        $session_list = $query
            ->orderBy('cgs.updated_at', 'desc')
            ->select(DB::raw('cgs.*, cr.room, crt.type as room_type, lc.language as language_name,
				CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name, CONCAT_WS(" ", cu1.first_name, cu1.last_name) as transfer_name,
				SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(cgs.start_time, cgs.created_at))) as wait_time,
				SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(\'' . $cur_time . '\', cgs.start_time))) as duration, 0 as unread
				'))
            ->get();

        return Response::json($session_list);
    }

    public function getInitInfoForTemplate(Request $request)
    {

        $queryChatNameList = DB::table('common_chat_templates')
            ->where('name', '<>', '')
            ->select(DB::raw('name'))
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        $resultTypeList = DB::table('common_room_type')
            ->select(DB::raw('id, type'))
            ->orderBy('type')
            ->get();

        $vipList = DB::table('common_vip_codes')
            ->select(DB::raw('id, name'))
            ->orderBy('name')
            ->get();

        $resultChatNameList = [];
        foreach ($queryChatNameList as $item) {
            $resultChatNameList[] = $item->name;
        }

        $result = [
            'chatNameList' => $resultChatNameList,
            'typeList' => $resultTypeList,
            'vipLevelList' => $vipList
        ];

        return Response::json($result);
    }

    public function getChatTemplateList(Request $request)
    {
        $property_id = $request->input('property_id', 0);
        $page = $request->input('page', 0);
        $pageSize = $request->input('pageSize', 25);
        $skip = $page;

        $orderBy = $request->input('field', 'cct.id');
        $sort = $request->input('sort', 'ASC');

        // search part
        $typeId = $request->input('typeId', 0);
        $vipId = $request->input('vipId', 0);
        $chatName = $request->input('chatName', '');

        $searchValue = $request->input('searchValue', '');

        $searchTypeList = [];
        $searchVipList = [];
        if (!empty($searchValue)) {
            $typeList = DB::table('common_room_type')
                ->where('type', 'LIKE', '%' . $searchValue . '%')
                ->select(DB::raw('id'))
                ->orderBy('type')
                ->get();

            if (!empty($typeList)) {
                foreach ($typeList as $typeListItem) {
                    $searchTypeList[] = $typeListItem->id;
                }
            }

            $vipList = DB::table('common_vip_codes')
                ->where('name', 'LIKE', '%' . $searchValue . '%')
                ->select(DB::raw('id'))
                ->orderBy('name')
                ->get();

            if (!empty($vipList)) {
                foreach ($vipList as $vipItem) {
                    $searchVipList[] = $vipItem->id;
                }
            }
        }

        $query = DB::table('common_chat_templates')
            ->where('property_id', $property_id);

        if ($typeId != 0) {
            $query->where(function ($query) use ($typeId) {
                $query->where('room_type_ids', '' . $typeId)
                    ->orWhere('room_type_ids', 'LIKE', $typeId . ',%')
                    ->orWhere('room_type_ids', 'LIKE', '%,' . $typeId . ',%')
                    ->orWhere('room_type_ids', 'LIKE', '%,' . $typeId);
            });
        }

        if ($vipId != 0) {
            $query->where(function ($query) use ($vipId) {
                $query->where('vip_ids', '' . $vipId)
                    ->orWhere('vip_ids', 'LIKE', $vipId . ',%')
                    ->orWhere('vip_ids', 'LIKE', '%,' . $vipId . ',%')
                    ->orWhere('vip_ids', 'LIKE', '%,' . $vipId);
            });
        }

        if ($chatName != '') {
            $query->where('name', 'LIKE', '%' . $chatName . '%');
        }

        if ($searchValue != '') {
            $query->where(/**
             * @param $query
             */ function ($query) use ($searchValue, $searchTypeList, $searchVipList) {
                $value = '%' . $searchValue . '%';
                $query->where('name', 'like', $value);

                foreach ($searchTypeList as $searchTypeItem) {
                    $query->orWhere('room_type_ids', 'Like', '%' . $searchTypeItem . '%');
                }

                foreach ($searchVipList as $searchVipItem) {
                    $query->orWhere('vip_ids', 'Like', '%' . $searchVipItem . '%');
                }
            });
        }

        $totalQuery = clone $query;

        if ($pageSize > 0) {
            $query->skip($skip)
                ->take($pageSize);
        }

        if (!empty($orderBy)) {
            $query->orderBy($orderBy, $sort);
        }

        $dataResult = $query->select(DB::raw('id, name, template, room_type_ids, vip_ids'))
            ->get();
        $totalCount = $totalQuery->count();

        $resultList = [];
        foreach ($dataResult as $dataItem) {
            $strRoomTypeIds = $dataItem->room_type_ids;

            if (!empty($strRoomTypeIds)) {
                $roomTypeIds = explode(",", $strRoomTypeIds);

                $typeList = DB::table('common_room_type')
                    ->whereIn('id', $roomTypeIds)
                    ->select(DB::raw('id, type'))
                    ->get();

                $dataItem->room_types = $typeList;
            } else {
                $dataItem->room_types = [];
            }

            $strVipIds = $dataItem->vip_ids;

            if (!empty($strVipIds)) {
                $vipIds = explode(",", $strVipIds);

                $vipList = DB::table('common_vip_codes')
                    ->whereIn('id', $vipIds)
                    ->select(DB::raw('id, name'))
                    ->get();

                $dataItem->vips = $vipList;
            } else {
                $dataItem->vips = [];
            }

            $resultList[] = $dataItem;
        }

        $result = [
            'templatelist' => $resultList,
            'totalCount' => $totalCount
        ];

        return Response::json($result);
    }

    public function saveTemplateData(Request $request)
    {
        $property_id = $request->input('property_id', 0);
        $roomTypeIds = $request->input('roomTypeIds', []);
        $vipLevelIds = $request->input('vipLevelIds', []);

        $chatName = $request->input('chatName', '');
        $template = $request->input('template', '');


        $inputRoomTypeIds = "";
        if (!empty($roomTypeIds)) {
            $inputRoomTypeIds = implode(",", $roomTypeIds);
        }

        $inputVipIds = "";
        if (!empty($vipLevelIds)) {
            $inputVipIds = implode(",", $vipLevelIds);
        }

        DB::table('common_chat_templates')
            ->insert([
                'property_id' => $property_id,
                'room_type_ids' => $inputRoomTypeIds,
                'vip_ids' => $inputVipIds,
                'name' => $chatName,
                'template' => $template
            ]);

        $res = [
            'success' => true
        ];

        return Response::json($res);
    }

    public function updateTemplateRow(Request $request)
    {
        $editId = $request->input('editId', 0);
        $name = $request->input('name', '');
        $roomTypeIds = $request->input('roomTypeIds', []);
        $vipIds = $request->input('vipIds', []);
        $template = $request->input('template', '');
        $res = [
            'success' => false,
            'message' => ''
        ];

        if ($editId == 0) {
            $res['message'] = 'There is no id to update.';

            return Response::json($res);
        }

        DB::table('common_chat_templates')
            ->where('id', $editId)
            ->update(['template' => $template, 'vip_ids' => !empty($vipIds) ?
                implode(",", $vipIds) : "", 'room_type_ids' => !empty($roomTypeIds) ?
                implode(",", $roomTypeIds) : "", 'name' => $name]);

        $res['success'] = true;

        return Response::json($res);
    }

    public function deleteTemplateRow(Request $request)
    {
        $deleteId = $request->input('deleteId', 0);

        $res = [
            'success' => false,
            'message' => ''
        ];

        if ($deleteId == 0) {
            $res['message'] = 'There is no id to delete.';

            return Response::json($res);
        }

        DB::table('common_chat_templates')
            ->where('id', $deleteId)
            ->delete();

        $res['success'] = true;

        return Response::json($res);
    }

    public function getChatAgentList(Request $request)
    {
        $agent_id = $request->get('agent_id', 0);
        $property_id = $request->get('property_id', 0);
        $online_flag = $request->get('online_flag', true);
        $filter = $request->get('filter', '');

        $filter = "'%" . $filter . "%'";

        $cur_time = date("Y-m-d H:i:s");

        $query = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->join('common_permission_members as pm', 'pm.perm_group_id', '=', 'jr.permission_group_id')
            ->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
            ->where('pr.name', 'app.guestservice.chat')
            ->where('cd.property_id', $property_id)
            ->where('cu.id', '!=', $agent_id)
            ->where('cu.deleted', 0)
            ->whereRaw('CONCAT_WS(" ", cu.first_name, cu.last_name) like ' . $filter)
            ->orderBy('cu.online_status', 'desc')
            ->select(DB::raw('cu.id, cu.online_status, cu.picture, jr.job_role, 
            CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name'));

        if ($online_flag)
            $query->where('cu.online_status', 1);

        $agent_list = $query->get();

        $site_url = Functions::getSiteURL();
        // get last message, unread count
        foreach ($agent_list as $row) {
            $row->picture = $site_url . $row->picture;
            $query = DB::table('services_chat_agent_history as cah')
                ->where('cah.from_id', $agent_id)
                ->where('cah.to_id', $row->id);

            $temp_query = clone $query;
            $row->last_message = $temp_query
                ->orderBy('cah.updated_at', 'desc')
                ->select(DB::raw('cah.*'))
                ->first();

            // get last message
            $temp_query = clone $query;
            $unread_info = $temp_query
                ->select(DB::raw('COALESCE(SUM(cah.unread), 0) as unread_cnt'))
                ->first();
            $row->unread_count = $unread_info->unread_cnt;
        }
        $agent_list_arr = $agent_list->toArray();
        usort( $agent_list_arr, function ($a, $b) {
            if (empty($a->last_message) && empty($b->last_message))
                return 1;
            if (empty($a->last_message) && !empty($b->last_message))
                return 1;
            if (!empty($a->last_message) && empty($b->last_message))
                return -1;
            return $a->last_message->updated_at >= $b->last_message->updated_at ? -1 : 1;
        });

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $agent_list_arr;

        return Response::json($ret);
    }

    public function getAgentConversationHistory(Request $request)
    {
        $from_id = $request->get('from_id', 0);

        $query = DB::table('services_chat_agent_history as hist')
            ->join(
                DB::raw('(SELECT MAX(hist1.id) AS last_id, SUM(hist1.unread * (hist1.direction = 0)) AS unread_cnt 
                FROM services_chat_agent_history AS hist1 where hist1.from_id = ' . $from_id . ' GROUP BY hist1.to_id) AS hist2'),
                'hist2.last_id', '=', 'hist.id'
            )
            ->leftJoin('common_users as cu1', 'hist.from_id', '=', 'cu1.id')
            ->leftJoin('common_users as cu2', 'hist.to_id', '=', 'cu2.id')
            ->where('from_id', $from_id);

        $list = $query->orderBy('hist.id', 'desc')
            ->select(DB::raw('hist.*, hist2.unread_cnt,
		 		CONCAT_WS(" ", cu1.first_name, cu1.last_name) as from_name,
		 		cu1.picture as from_picture,
		 		CONCAT_WS(" ", cu2.first_name, cu2.last_name) as to_name,
		 		cu2.picture as to_picture,
		 		cu2.active_status
		 		'))
            ->get();

        $ret = array();

        $ret['code'] = 200;
        $ret['list'] = $list;

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function requestChat(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $last24 = date('Y-m-d H:i:s', strtotime(' -1 day'));

        DB::table('services_chat_guest_session as cgs')
            ->where('cgs.updated_at', '<=', $last24)
            ->where('cgs.status', '!=', ENDED)
            ->update(array('cgs.status' => ENDED));

//		added new code for whatsapp api
        $mobile_number = $request->get('mobile_number', '');

        // get guest info from mobile_number
        $guest_info = DB::table('common_guest')
            ->where('mobile', $mobile_number)
            ->first();

        $guest_type = '';
        if (!empty($guest_info)) {
            $guest_id = $guest_info->id;

            if ($guest_info->checkout_flag == 'checkin') {
                $guest_type = 'In-House';
            } else {
                $guest_type = "Checked out";
            }
            $guest_name = $guest_info->guest_name;
        } else {
            $guest_id = 0;
            $guest_type = 'Outside';
            $guest_name = 'Unknown';
        }

        //

        $property_id = $request->get('property_id', 0);
        $room_id = $request->get('room_id', 0);
        $guest_id = $request->get('guest_id', 0);
        $guest_name = $request->get('guest_name', 0);
        $language = $request->get('language', 'en');

        $ret = $this->createChatSession($property_id, $guest_id, $language, $guest_type, $guest_name, $mobile_number);

        $new_flag = $ret['new_flag'];
        $session = $ret['session'];

        $room = DB::table('common_room as cr')
            ->where('id', $room_id)
            ->first();

        if ($new_flag == true && $session->status == WAITING)    // create new chat session and waiting
            $this->saveSystemNotification($property_id, $session->id, $room->room, $guest_name);

        return Response::json($session);
    }

    /**
     * @param $property_id
     * @param $guest_id
     * @param $language
     * @param $mobile_number
     * @param $guest_type
     * @param array $guest_path_list
     * @return array
     */
    public function createChatSession($property_id, $guest_id, $language, $mobile_number, $guest_type, &$guest_path_list = [])
    {
        $ret = array();

        if (!empty($guest_id)) {
            $ret['new_flag'] = false;

            // find first waiting session
            $session = GuestChatSession::where('guest_id', $guest_id)
                ->where('status', WAITING)
                ->first();

            if (!empty($session))    // exist waiting session
            {
                $ret['session'] = $session;
                return $ret;
            }

            // find active session
            $session = GuestChatSession::where('guest_id', $guest_id)
                ->where('status', ACTIVE)
                ->first();

            if (!empty($session))    // exist active session
            {
                $ret['session'] = $session;
                return $ret;
            }
        }

        $guest_name = 'Unknown';
        // get guest info from mobile_number
        $guest_info = DB::table('common_guest')
            ->where('property_id', $property_id)
            ->where('guest_id', $guest_id)
            ->first();

        if (!empty($guest_info)) {
            $guest_name = $guest_info->guest_name;
        }

        $ret['new_flag'] = true;
        $session = new GuestChatSession();

        $session->guest_id = $guest_id;
        $session->agent_id = 0;

        $session->guest_type = $guest_type;
        $session->mobile_number = $mobile_number;
        $session->language = $language;
        $session->guest_name = $guest_name;
        $session->property_id = $property_id;

        $session->guest_path = !empty($guest_path_list) ? implode(" >> ", $guest_path_list) : '';

        if (!empty($guest_info)) {
            $session->room_id = $guest_info->room_id;
            $session->status = WAITING;
            $session->transfer_id = 0;
            $session->start_time = '';
        }

        $session->save();

        $guest_path_list = [];
        $ret['session'] = $session;

        return $ret;
    }

    /**
     * @param $property_id
     * @param $session_id
     * @param $room
     * @param $guest_name
     */
    public function saveSystemNotification($property_id, $session_id, $room, $guest_name)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $notification = new SystemNotification();

        $notification->type = 'app.guestservice.chat';
        $notification->header = 'Chat';
        $notification->property_id = $property_id;

        $notification->content = sprintf('%s from Room %s wants to have a chat.', $guest_name, $room);
        $notification->notification_id = $session_id;
        $notification->created_at = $cur_time;
        $notification->save();

        CommonUser::addNotifyCount($property_id, 'app.guestservice.chat');

        $message = array();
        $message['type'] = 'webpush';
        $message['to'] = $property_id;
        $message['content'] = $notification;

        Redis::publish('notify', json_encode($message));
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function callToAgent(Request $request)
    {
        $session_id = $request->get('session_id', 0);

        $ret = $this->getCallToAgent($session_id);
        return Response::json($ret);
    }

    /**
     * @param $session_id
     * @return array
     */
    private function getCallToAgent($session_id)
    {
        // find first waiting session
        $session = GuestChatSession::where('id', $session_id)
            ->first();

        $ret = [
            'success' => true
        ];

        if (empty($session)) {
            $ret['success'] = false;
            return $ret;
        }

        $message = array();
        $message['type'] = 'chat_event';
        $message['sub_type'] = 'request_chat';
        $message['data'] = $session;

        Redis::publish('notify', json_encode($message));

        return $ret;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptChat(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $session_id = $request->get('session_id', 0);
        $agent_id = $request->get('agent_id', 0);

        $session = DB::table('services_chat_guest_session as cgs')
            ->leftJoin('common_users as cu', 'cgs.agent_id', '=', 'cu.id')
            ->where('cgs.id', $session_id)
            ->select(DB::raw('cgs.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name'))
            ->first();

        $ret = array();

        $ret['code'] = 200;

        if (empty($session))        // there is no guest chat session
        {
            $ret['code'] = 201;
            $ret['message'] = 'Invalid Chat Session';
            return Response::json($ret);
        }

        $valid_accept = false;
        if ($session->status == WAITING ||            // waiting
            $session->status == ACTIVE && $session->transfer_id > 0)    // transfer request
            $valid_accept = true;

        if ($valid_accept == false)    // already assigned to a agent and diffrent agent
        {
            $ret['code'] = 202;

            $ret['message'] = 'Invalid Chat Session';
            if ($session->status == ENDED)
                $ret['message'] = 'This chat session is ended';

            if ($session->status == ACTIVE && $session->transfer_id == 0)
                $ret['message'] = 'This chat session is already accepted by ' . $session->agent_name;

            $ret['session'] = $session;
            return Response::json($ret);
        }

        $session = GuestChatSession::find($session_id);

        $session->agent_id = $agent_id;
        $session->status = ACTIVE;
        $session->start_time = $cur_time;
        $session->transfer_id = 0;

        $session->save();

        $this->saveChatEvent($session, 1);    // accept chat

        // send accept message and save to database
        $accept_chat = $request->get('accept_chat', '');

        if (!empty($accept_chat)) {
            $mobile_number = $session->mobile_number;
            $property_id = $session->property_id;
            $language = $session->language;
            $room = $request->get('room', 0);

            $guest_type = $session->guest_type;
            $language_name = $request->get('language_name', 'English');

            $accept_chat_trans = $accept_chat;
            if ($language != 'en') {
                $accept_chat_trans = $this->getTranslatedText('en', $language, $accept_chat);
            }

            $message_info['text'] = $accept_chat_trans;
            $message_info['mobile_number'] = $mobile_number;

            $other_info = [
                'guest_type' => $guest_type,
                'room' => $room,
                'language_name' => $language_name
            ];

            // save chat info
            $saveInfo = [
                'property_id' => $property_id,
                'session_id' => $session_id,
                'agent_id' => $agent_id,
                'guest_id' => $session->guest_id,
                'cur_chat_name' => '',
                'mobile_number' => $mobile_number,
                'text' => $accept_chat,
                'text_trans' => '',
                'language' => $language,
                'sender' => 'server',
                'chat_type' => 'text',
                'attachment' => '',
                'other_info' => json_encode($other_info)
            ];

            $responseWhatsapp = $this->sendMessageToWhatsapp($message_info);
            if ($responseWhatsapp != false) {
                $saveInfo['uuid'] = $responseWhatsapp->MessageUUID;
                $this->saveChatbotHistoryWhatsapp($saveInfo);
            }
        }

        $ret['session'] = $session;

        // send notify to all agents
        $message = array();
        $message['type'] = 'chat_event';
        $message['sub_type'] = 'accept_chat';
        $message['data'] = $session;


        Redis::publish('notify', json_encode($message));

        return Response::json($ret);
    }

    /**
     * @param $session
     * @param $event
     */
    private function saveChatEvent($session, $event)
    {
        // save accept chat event
        $input = array();

        $input['property_id'] = $session->property_id;
        $input['session_id'] = $session->id;
        $input['guest_id'] = $session->guest_id;
        $input['agent_id'] = $session->agent_id;
        $input['text'] = $event;
        $input['text_trans'] = '';
        $input['language'] = 'en';
        $input['direction'] = -1;    // outgoing
        $input['type'] = 0;            // guest message

        $id = DB::table('services_chat_history')->insertGetId($input);
    }

    /**
     * @param $from
     * @param $to
     * @param $text
     * @return mixed
     */
    private function getTranslatedText($from, $to, $text)
    {
        $key = "AIzaSyBXzVNjgOdra7iyK6rHeN2nJv6maIptE1Y";

        $request = [
            'key' => $key,
            'source' => $from,
            'target' => $to,
            'q' => $text
        ];

        $url = 'https://www.googleapis.com/language/translate/v2?' . http_build_query($request);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        $res = json_decode($response);

        if (isset($res->data->translations)) {
            return $res->data->translations[0]->translatedText;
        } else {
            return $text;
        }
    }

    /**
     * @param $message_info
     * @param string $messageType
     * @param int $mediaId
     * @return bool|mixed
     */
    private function sendMessageToWhatsapp($message_info, $messageType = 'Text', $mediaId = 1)
    {
        $authKey = 'xFNfwpRkveF013zOrtbk';
        $authorization = 'eEZOZndwUmt2ZUYwMTN6T3J0Yms6U211UzNRakJ3blhCbk9TNG5rb3lPT1c2ZVVhSkJ1MXFmSXltR1lOMA==';
        $channelId = '94792798-e368-4e53-80c3-6a13d586bb01';

        $mobile_number = $message_info['mobile_number'];
        $text = $message_info['text'];

        $url = "https://restapi.smscountry.com/v0.1/Accounts/$authKey/Whatsapp/$channelId/Messages/";
        $headers = [
            'Content-Type: application/json',
            'Authorization: Basic ' . $authorization
        ];

        $body = [
            'Text' => $text,
            'Number' => $mobile_number,
            'MediaId' => $mediaId,
            'MessageType' => $messageType,
            'Tool' => 'API',
            'TemplateID' => ""
        ];

        $postData = json_encode($body);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $server_response = curl_exec($ch);

        curl_close($ch);

        if ($server_response == false) {
            return false;
        }

        return json_decode($server_response);
    }

    /**
     * @param $saveInfo
     */
    public function saveChatbotHistoryWhatsapp($saveInfo)
    {
        $property_id = $saveInfo['property_id'];
        $session_id = $saveInfo['session_id'];
        $guest_id = $saveInfo['guest_id'];
        $agent_id = $saveInfo['agent_id'];
        $text = $saveInfo['text'];
        $text_trans = $saveInfo['text_trans'];
        $language = $saveInfo['language'];

        $phone_number = $saveInfo['mobile_number'];

        $cur_chat_name = $saveInfo['cur_chat_name'];
        $chat_type = $saveInfo['chat_type'];
        $attachment = $saveInfo['attachment'];
        $other_info = $saveInfo['other_info'];

        $guest_path = isset($saveInfo['guest_path']) ? $saveInfo['guest_path'] : '';

        $uuid = isset($saveInfo['uuid']) ? $saveInfo['uuid'] : '';

        $sender = $saveInfo['sender'];

        $input = [];

        $input['property_id'] = $property_id;
        $input['session_id'] = $session_id;
        $input['guest_id'] = $guest_id;
        $input['agent_id'] = $agent_id;

        $input['text'] = "$text";
        $input['text_trans'] = $text_trans;
        $input['language'] = $language;

        $input['cur_chat_name'] = $cur_chat_name;
        $input['phone_number'] = $phone_number;
        $input['chat_type'] = $chat_type;
        $input['attachment'] = $attachment;
        $input['other_info'] = $other_info;
        $input['uuid'] = $uuid;

        $input['guest_path'] = $guest_path;


        if ($sender == 'guest') {
            $input['direction'] = 1;    // outgoing
            $input['type'] = 0;            // guest message
            DB::table('services_chat_history')->insert($input);

            $input['direction'] = 0;    // incoming
            $input['type'] = 1;            // agent message

            DB::table('services_chat_history')->insert($input);
        } else {
            $input['direction'] = 1;    // outgoing
            $input['type'] = 1;            // agent message
            DB::table('services_chat_history')->insert($input);

            $input['direction'] = 0;    // incoming
            $input['type'] = 0;            // guest message

            DB::table('services_chat_history')->insert($input);
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endChatFromAgent(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $ret = array();
        $ret['code'] = 200;

        $session_id = $request->get('session_id', 0);

        $session = GuestChatSession::find($session_id);

        if (empty($session)) {
            $ret['code'] = 201;
            $ret['message'] = 'Invalid Chat Session';
            return Response::json($ret);
        }

        // send end chat message and save to database
        $end_chat = $request->get('end_chat', '');
        if (!empty($end_chat)) {
            $mobile_number = $session->mobile_number;
            $property_id = $session->property_id;
            $language = $session->language;
            $room = $request->get('room', 0);

            $guest_type = $session->guest_type;
            $language_name = $request->get('language_name', 'English');

            $end_chat_trans = $end_chat;
            if ($language != 'en') {
                $end_chat_trans = $this->getTranslatedText('en', $language, $end_chat);
            }

            $message_info['text'] = $end_chat_trans;
            $message_info['mobile_number'] = $mobile_number;

            $other_info = [
                'guest_type' => $guest_type,
                'room' => $room,
                'language_name' => $language_name
            ];

            // save chat info
            $saveInfo = [
                'property_id' => $property_id,
                'session_id' => $session_id,
                'agent_id' => $session->agent_id,
                'guest_id' => $session->guest_id,
                'cur_chat_name' => '',
                'mobile_number' => $mobile_number,
                'text' => $end_chat,
                'text_trans' => '',
                'language' => $language,
                'sender' => 'server',
                'chat_type' => 'text',
                'attachment' => '',
                'other_info' => json_encode($other_info)
            ];

            $responseWhatsapp = $this->sendMessageToWhatsapp($message_info);
            if ($responseWhatsapp != false) {
                $saveInfo['uuid'] = $responseWhatsapp->MessageUUID;
                $this->saveChatbotHistoryWhatsapp($saveInfo);
            }
        }

        $session = $this->endChatSession($session);

        // send notify to all agents
        $message = array();
        $message['type'] = 'chat_event';
        $message['sub_type'] = 'end_chat';
        $message['data'] = $session;

        Redis::publish('notify', json_encode($message));

        $ret['session'] = $session;

        return Response::json($ret);
    }

    /**
     * @param $session
     * @return array
     */
    public function endChatSession($session)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        if (empty($session)) {
            return array();
        }

        $session->status = ENDED;
        $session->end_time = $cur_time;

        $session->save();

        $this->saveChatEvent($session, 2);    // end chat


        return $session;
    }

    /**
     * @param $agent_id
     * @param $cur_time
     */
    public function logoutChatAgent($agent_id, $cur_time)
    {
        // find all active chat session for this agent
        $session_list = DB::table('services_chat_guest_session as cgs')
            ->leftJoin('common_room as cr', 'cgs.room_id', '=', 'cr.id')
            ->join('common_users as cu', 'cgs.agent_id', '=', 'cu.id')
            ->where('cgs.agent_id', $agent_id)
            ->where('cgs.status', ACTIVE)
            ->select(DB::raw('cgs.*, cr.room, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name'))
            ->get();

        foreach ($session_list as $session) {

            $this->sendLogoutMessageToGuest($agent_id, $session);

            DB::table('services_chat_guest_session')->where('id', $session->id)->update(
                [
                    'status' => ENDED,
                    'end_time' => date("Y-m-d H:i:s")
                ]
            );

            // send notify to all agents
            $notification = new SystemNotification();

            $notification->type = 'app.guestservice.chat';
            $notification->property_id = $session->property_id;

            $room = !empty($session->room) ? $session->room : 0;
            $notification->content = sprintf('%s is logged out, so %s from Room %s wants to have a chat.',
                $session->agent_name, $session->guest_name, $room);
            $notification->notification_id = $session->id;

            $notification->created_at = $cur_time;
            $notification->save();

            CommonUser::addNotifyCount($session->property_id, 'app.guestservice.chat');

            $message = array();
            $message['type'] = 'webpush';
            $message['to'] = $session->property_id;
            $message['content'] = $notification;

            Redis::publish('notify', json_encode($message));

            // send notify to all agents
            $message = array();
            $message['type'] = 'chat_event';
            $message['sub_type'] = 'logout_chat';
            $message['data'] = $session;

            Redis::publish('notify', json_encode($message));
        }
    }

    /**
     * @param $agent_id
     * @param $session
     */
    private function sendLogoutMessageToGuest($agent_id, $session)
    {
        // send message to guest before making ended
        $mobile_number = $session->mobile_number;
        $property_id = $session->property_id;
        $language = $session->language;

        $room = !empty($session->room) ? $session->room : 0;

        $guest_type = $session->guest_type;
        $language_name = 'English';
        if ($language == 'ar') {
            $language_name = 'Arabic';
        } else if ($language == 'cn') {
            $language_name = 'Chinese';
        }

        // get logout chat from template
        $chatResult = $this->getChatResult($property_id, CHAT_AGENT_LOGOUT, 0, 0,
            CHAT_AGENT_LOGOUT);
        $logout_chat = !empty($chatResult) ? $chatResult->template : '';

        if ($language != 'en') {
            $logout_chat = $this->getTranslatedText('en', $language, $logout_chat);
        }

        $message_info['text'] = $logout_chat;
        $message_info['mobile_number'] = $mobile_number;

        $this->sendMessageToWhatsapp($message_info);

        $other_info = [
            'guest_type' => $guest_type,
            'room' => $room,
            'language_name' => $language_name
        ];

        // save chat info
        $saveInfo = [
            'property_id' => $property_id,
            'session_id' => $session->id,
            'agent_id' => $agent_id,
            'guest_id' => $session->guest_id,
            'cur_chat_name' => '',
            'mobile_number' => $mobile_number,
            'text' => $logout_chat,
            'text_trans' => '',
            'language' => $language,
            'sender' => 'server',
            'chat_type' => 'text',
            'attachment' => '',
            'other_info' => json_encode($other_info)
        ];

        // save logout message
        $this->saveChatbotHistoryWhatsapp($saveInfo);

        // get next chat
        $chat_name = CHAT_IN_HOUSE_MAIN_MENU;
        $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;
        if ($guest_type == 'Outside') {
            $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
            $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
        }

        $chatResult = $this->getChatResult($property_id, $chat_name, 0, 0, $prev_chat_name);
        $next_chat = !empty($chatResult) ? $chatResult->template : '';

        if ($language != 'en') {
            $next_chat = $this->getTranslatedText('en', $language, $next_chat);
        }

        $message_info['text'] = $next_chat;
        $this->sendMessageToWhatsapp($message_info);

        $other_info = [
            'guest_type' => $guest_type,
            'room' => $room,
            'language_name' => $language_name
        ];

        $saveInfo['agent_id'] = 0;
        $saveInfo['session_id'] = 0;
        $saveInfo['cur_chat_name'] = $chat_name;
        $saveInfo['text'] = $next_chat;

        $this->saveChatbotHistoryWhatsapp($saveInfo);
    }

    private function getChatResult($property_id, $chat_name, $room_type_id, $vip_id, $prev_chat_name, $bWrong = false)
    {
        $query = DB::table('common_chat_templates')
            ->where('property_id', $property_id)
            ->where('name', $chat_name);

        $typeId = !empty($room_type_id) ? $room_type_id : 0;
        if (!empty($typeId)) {
            $query->where(function ($query) use ($typeId) {
                $query->where('room_type_ids', '' . $typeId)
                    ->orWhere('room_type_ids', 'LIKE', $typeId . ',%')
                    ->orWhere('room_type_ids', 'LIKE', '%,' . $typeId . ',%')
                    ->orWhere('room_type_ids', 'LIKE', '%,' . $typeId);
            });
        }

        $vipId = !empty($vip_id) ? $vip_id : 0;
        if (!empty($vip_id)) {
            $query->where(function ($query) use ($vipId) {
                $query->where('vip_ids', '' . $vipId)
                    ->orWhere('vip_ids', 'LIKE', $vipId . ',%')
                    ->orWhere('vip_ids', 'LIKE', '%,' . $vipId . ',%')
                    ->orWhere('vip_ids', 'LIKE', '%,' . $vipId);
            });


        }
        $chatResult = $query->select(DB::raw('name, template'))
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($chatResult)) {
            $next_chat_name = CHAT_INVALID;
            if ($prev_chat_name === CHAT_IN_HOUSE_MAIN_MENU || $prev_chat_name === CHAT_WRONG) {
                $next_chat_name = CHAT_WRONG;
            }

            $chatResult = $this->getChatResult($property_id, $next_chat_name, $room_type_id, $vip_id, $prev_chat_name, true);

            if ($chatResult->name !== CHAT_WRONG && $next_chat_name !== CHAT_WRONG) {
                $chatResult->is_wrong = true;
            }
        }

        return $chatResult;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function onReceiveMessageFromGuest(Request $request)
    {
        $property_id = $request->get('property_id', 4);
        $session_id = $request->get('session_id', 0);
        $guest_id = $request->get('guest_id', 0);
        $text = $request->get('text', '');
        $text_trans = $request->get('text_trans', '');
        $language = $request->get('language', 'en');

        $session = GuestChatSession::find($session_id);

        // save chat history
        $this->saveGuestMessage($session, $text, $text_trans, $language);

        return Response::json($session);
    }

    /**
     * @param $session
     * @param $text
     * @param $text_trans
     * @param $language
     */
    private function saveGuestMessage($session, $text, $text_trans, $language)
    {
        if (empty($session))
            return;

        $input = array();

        $input['property_id'] = $session->property_id;
        $input['session_id'] = $session->id;
        $input['guest_id'] = $session->guest_id;
        $input['agent_id'] = $session->agent_id;
        $input['text'] = $text;
        $input['text_trans'] = $text_trans;
        $input['language'] = $language;
        $input['direction'] = 1;    // outgoing
        $input['type'] = 0;            // guest message
        $input['phone_number'] = $session->mobile_number;
        $input['cur_chat_name'] = CHAT_AGENT_CALL_WAITING;

        $id = DB::table('services_chat_history')->insertGetId($input);

        $input['direction'] = 0;    // incoming
        $input['type'] = 1;            // agent message

        $id = DB::table('services_chat_history')->insertGetId($input);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function onReceiveMessageFromAgent(Request $request)
    {
        $session_id = $request->get('session_id', 0);
        $text = $request->get('text', '');
        $text_trans = $request->get('text_trans', '');
        $language = $request->get('lang_code', 'en');

        $session = GuestChatSession::find($session_id);

        // save chat history
        $this->saveAgentMessage($session, $text, $text_trans, $language);

        return Response::json($session);
    }

    /**
     * @param $session
     * @param $text
     * @param $text_trans
     * @param $language
     */
    private function saveAgentMessage($session, $text, $text_trans, $language)
    {
        $input = array();

        $input['property_id'] = $session->property_id;
        $input['session_id'] = $session->id;
        $input['guest_id'] = $session->guest_id;
        $input['agent_id'] = $session->agent_id;
        $input['text'] = $text;
        $input['text_trans'] = $text_trans;
        $input['language'] = $language;
        $input['direction'] = 1;    // outgoing
        $input['type'] = 1;            // agent message
        $input['phone_number'] = $session->mobile_number;
        $input['cur_chat_name'] = CHAT_AGENT_CALL_WAITING;

        $id = DB::table('services_chat_history')->insertGetId($input);

        $input['direction'] = 0;    // incoming
        $input['type'] = 0;            // guest message

        $id = DB::table('services_chat_history')->insertGetId($input);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getChatHistoryForGuest(Request $request)
    {
        $session_id = $request->get('session_id', 0);

        $chat_history = DB::table('services_chat_history as sch')
            ->leftJoin('common_users as cu', 'sch.agent_id', '=', 'cu.id')
            ->join('services_chat_guest_session as cgs', 'sch.session_id', '=', 'cgs.id')
            ->where('type', 0)
            ->where('sch.agent_id', '!=', 0)
            ->where('sch.session_id', $session_id)
            ->select(DB::raw('sch.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name'))
            ->get();

        $accept_count = DB::table('services_chat_history as sch')
            ->where('sch.type', 0)
            ->where('sch.session_id', $session_id)
            ->where('sch.direction', -1)
            ->where('sch.text', '1')
            ->count();

        $count = 1;
        foreach ($chat_history as $row) {
            if ($row->text == '1')        // aceept chat
            {
                if ($count == $accept_count)    // last accept
                    $row->text = 'You are now chatting with ' . $row->agent_name;
                else
                    $row->text = $row->agent_name . ' - ' . date('H:i A', strtotime($row->created_at));
                $count++;
            }

            if ($row->text == '2')    // end chat
            {
                $row->text = 'Your chat is ended with ' . $row->agent_name;
            }

        }

        return Response::json($chat_history);
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \Throwable
     */
    public function sendEmailToUsers(Request $request)
    {
        $users = $request->get('users', []);
        $property_id = $request->get('property_id', 4);
        $session_id = $request->get('session_id', 0);

        $chat_histories = $this->getChatHistoryBySessionIdForSending($session_id);

        foreach ($users as $user) {
            $email = $user['email'];

            $message = [];
            $message['type'] = 'email';
            $message['to'] = $email;
            $message['subject'] = (!empty($subject)) ? ('Hotlync Notification - ' . $subject) : 'Hotlync Notification - Chat History';
            $message['title'] = '';

            $message['content'] = view('emails.chat_history', ['info' => $chat_histories])->render();
            $message['smtp'] = Functions::getMailSetting($property_id, 'notification_');

            Redis::publish('notify', json_encode($message));
        }

        $ret = [
            'success' => true,
            'message' => ''
        ];

        return Response::json($ret);
    }

    /**
     * @param $session_id
     * @return array
     */
    private function getChatHistoryBySessionIdForSending($session_id)
    {
        $session_info = DB::table('services_chat_guest_session as cgs')
            ->leftJoin('common_users as cu', 'cu.id', '=', 'cgs.agent_id')
            ->where('cgs.id', $session_id)
            ->select(DB::raw('cgs.start_time, cgs.mobile_number, cgs.guest_name, 
                SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(cgs.end_time, cgs.start_time))) as chat_duration,
                SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(cgs.start_time, cgs.created_at))) as wait_time,
             CONCAT(cu.first_name, " ", cu.last_name) as agent_name'))
            ->first();
        $ret = [];
        if (!empty($session_info)) {
            $ret['start_time'] = date('D d M h:i:s A', strtotime($session_info->start_time));
            $ret['mobile_number'] = $session_info->mobile_number;
            $ret['guest_name'] = $session_info->guest_name;
            $ret['chat_duration'] = $session_info->chat_duration;
            $ret['wait_time'] = $session_info->wait_time;

            $ret['agent_name'] = $session_info->agent_name;

            $chat_histories = DB::table('services_chat_history as sch')
                ->leftJoin('common_users as cu', 'sch.agent_id', '=', 'cu.id')
                ->join('services_chat_guest_session as cgs', 'sch.session_id', '=', 'cgs.id')
                ->where('type', 1)
                ->where('sch.agent_id', '!=', 0)
                ->where('sch.session_id', $session_id)
                ->select(DB::raw('sch.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name'))
                ->get();

            $ret['chat_histories'] = $chat_histories;
        }

        return $ret;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getActiveUsers(Request $request)
    {
        $cur_user_id = $request->get('cur_user_id', 0);

        $users = DB::table('common_users')
            ->where('id', '!=', $cur_user_id)
            ->where('deleted', '=', 0)
            ->where('email', '!=', '')
            ->whereNotNull('email')
            ->select(DB::raw('id, CONCAT(first_name, " ", last_name) as label, email'))
            ->get();

        return Response::json($users);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getChatHistoryForAgent(Request $request)
    {
        $session_id = $request->get('session_id', 0);

        $chat_history = $this->getChatHistoryBySessionId($session_id);

        return Response::json($chat_history);
    }

    /**
     * @param $session_id
     * @return mixed
     */
    private function getChatHistoryBySessionId($session_id)
    {
        $chat_histories = DB::table('services_chat_history as sch')
            ->leftJoin('common_users as cu', 'sch.agent_id', '=', 'cu.id')
            ->join('services_chat_guest_session as cgs', 'sch.session_id', '=', 'cgs.id')
            ->where('type', 1)
            ->where('sch.agent_id', '!=', 0)
            ->where('sch.session_id', $session_id)
            ->select(DB::raw('sch.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name'))
            ->get();

        return $chat_histories;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function downloadChatHistoryToPdf(Request $request)
    {
        $session_id = $request->get('session_id', 0);
        $property_id = $request->get('property_id', 0);

        $chat_history = $this->getChatHistoryBySessionIdForSending($session_id);

        // get logo path
        $model = Property::find($property_id);

        $logo_path = '';
        if (!empty($model)) {
            $logo_path = $model->logo_path;
        }

        $timestamp = time();

        $fileName = 'chathistory_pdf_' . $timestamp;

        $folder_path = public_path() . '/uploads/reports/';
        $path = $folder_path . $fileName . '.html';

        ob_start();
        $content = view('frontend.report.guestservice.chat_history_pdf', ['info' => $chat_history, 'logo_path' => $logo_path]);

        echo $content;

        file_put_contents($path, ob_get_contents());

        ob_clean();

        $ret = [];
        $ret['filename'] = $fileName;
        $ret['folder_path'] = $folder_path;

        $options = [];
        $options['html'] = $path;
        $options['paperSize'] = ['format' => 'A4', 'orientation' => 'portrait'];

        $ret['options'] = $options;

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function setGuestSpamInfo(Request $request)
    {
        $agent_id = $request->get('agent_id', 0);
        $mobile_number = $request->get('mobile_number', '');
        $spam_status = $request->get('spam_status', 0);
        $spam_id = $request->get('spam_id', 0);

        $input = [
            'agent_id' => $agent_id,
            'mobile_number' => $mobile_number,
        ];

        if (empty($spam_id)) {
            // create
            $input['spam_status'] = 1;

            DB::table('common_guest_spam')
                ->insert($input);
        } else {
            $statusArr = [];
            if (empty($spam_status)) {
                $statusArr['spam_status'] = 1;
            } else {
                $statusArr['spam_status'] = 0;
            }

            // update
            DB::table('common_guest_spam')
                ->where('id', $spam_id)
                ->update($statusArr);
        }

        $ret = [
            'success' => true
        ];

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function deletePhonebookInfo(Request $request)
    {
        $phonebook_id = $request->get('phonebook_id', 0);

        DB::table('common_guest_phonebook')
            ->where('id', $phonebook_id)
            ->delete();

        $ret = [
            'success' => true
        ];

        return Response::json($ret);
    }

    public function getPresetMessages(Request $request)
    {
        $agent_id = $request->get('agent_id', 0);

        $search_text = $request->get('search_text', '');
        $query = DB::table('common_preset_messages')
            ->where('agent_id', $agent_id);

        if (!empty($search_text)) {
            $query->where('message', 'LIKE', '%' . $search_text . '%');
        }

        $ret = $query->select(['id', 'message'])
            ->orderBy('message')
            ->get();

        return Response:: json($ret);
    }

    public function savePresetMessages(Request $request)
    {
        $agent_id = $request->get('agent_id', 0);
        $preset_messages = $request->get('preset_messages', []);

        foreach ($preset_messages as $preset_message) {
            $id = $preset_message['id'];
            $message = $preset_message['message'];

            if (empty($id)) {
                if (!empty($message)) {
                    $input = [
                        'agent_id' => $agent_id,
                        'message' => $message
                    ];
                    DB::table('common_preset_messages')
                        ->insert($input);
                }
            } else {
                if (empty($message)) {
                    // delete
                    DB::table('common_preset_messages')
                        ->where('id', $id)
                        ->delete();
                } else {
                    // update
                    $input = [
                        'message' => $message
                    ];

                    DB::table('common_preset_messages')
                        ->where('id', $id)
                        ->update($input);
                }
            }
        }

        return $this->getPresetMessages($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getChatSessionListNew(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date('Y-m-d H:i:s');
        $last24 = date('Y-m-d H:i:s', strtotime(' -1 day'));

        $property_id = $request->get('property_id', 0);
        $agent_id = $request->get('agent_id', 0);
        $status = $request->get('status', '');

        $status_arr = $request->get('status_arr', []);

        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');

        $room_ids = $request->get('room_ids', []);
        $agent_ids = $request->get('agent_ids', []);

        $search_text = $request->get('search_text', "");

        $user_id = $request->get('user_id', 0);

        $query = DB::table('services_chat_guest_session as cgs')
            // ->leftJoin('common_guest as cg', 'cgs.guest_id', '=', 'cg.guest_id')
            ->leftJoin('common_guest as cg', function ($join) use ($property_id) {
                $join->on('cgs.guest_id', '=', 'cg.guest_id');
                $join->on('cgs.property_id', '=', 'cg.property_id');
            })
            ->leftJoin('common_guest_phonebook as cgpb', function ($join) use ($property_id, $user_id) {
                $join->on('cgpb.mobile_number', '=', 'cgs.mobile_number');
                $join->on('cgpb.property_id', '=', 'cgs.property_id');
            })
            ->leftJoin('common_guest_spam as cgsp', function ($join) use ($property_id, $user_id) {
                $join->on('cgsp.mobile_number', '=', 'cgs.mobile_number');
                $join->where('cgsp.agent_id', '=', $user_id);
            })
            ->leftJoin('common_guest_profile as cgp', 'cgp.id', '=', 'cg.profile_id')
            ->leftJoin('common_users as cu', 'cgs.agent_id', '=', 'cu.id')
            ->leftJoin('common_users as cu1', 'cgs.transfer_id', '=', 'cu1.id')
            ->leftJoin('common_room as cr', 'cgs.room_id', '=', 'cr.id')
            ->leftJoin('common_room_type as crt', 'cr.type_id', '=', 'crt.id')
            ->leftJoin('common_language_code as lc', 'cgs.language', '=', 'lc.code')
            ->where('cgs.property_id', $property_id);

        if ($agent_id > 0)
            $query->where('cgs.agent_id', $agent_id);

//        if( $status != 'All' && !empty($status) )
//            $query->where('cgs.status', $status);

        if (!empty($start_date))
            $query->where('cgs.updated_at', '>=', $start_date . ' 00:00:00');

        if (!empty($end_date))
            $query->where('cgs.updated_at', '<=', $end_date . ' 23:59:59');

        if (!empty($agent_ids) && count($agent_ids) > 0)
            $query->whereIn('cgs.agent_id', $agent_ids);

        if (!empty($room_ids) && count($room_ids) > 0)
            $query->whereIn('cgs.room_id', $room_ids);

        if (!empty($status_arr)) {
            $query->whereIn('cgs.status', $status_arr);
        }

        if (!empty($search_text)) {
            $where = sprintf("(cgs.id like '%%%s%%' or 
            cgs.guest_name like '%%%s%%' or
            cg.mobile like '%%%s%%')",
                $search_text, $search_text,
                $search_text
            );

            $query->whereRaw($where);
        }

        $session_list = $query
            ->orderBy('cgs.status', 'asc')
            ->orderBy('cgs.updated_at', 'desc')
            ->select(DB::raw('cgs.*, cr.room, crt.type as room_type, lc.language as language_name,
				CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name, CONCAT_WS(" ", cu1.first_name, cu1.last_name) as transfer_name,
				SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(cgs.start_time, cgs.created_at))) as wait_time,
				SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(cgs.end_time, cgs.start_time))) as duration, 0 as unread,
				cg.vip as vip, cg.booking_src as booking_src, cg.booking_rate as booking_rate, cgp.nationality as natitionality,
				cg.arrival as arrival, cg.departure as departure, cgpb.id as phonebook_id, cgpb.name as phonebook_name,
				cgsp.id as spam_id, cgsp.spam_status as spam_status, cgsp.agent_id as spam_agent_id
				'))
            ->get();

        return Response::json($session_list);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function savePhonebookInfo(Request $request)
    {
        $property_id = $request->get('property_id', 4);
        $agent_id = $request->get('agent_id', 0);
        $mobile_number = $request->get('mobile_number', '');
        $phonebook_id = $request->get('phonebook_id', 0);
        $phonebook_name = $request->get('phonebook_name', '');

        $input = [
            'property_id' => $property_id,
            'agent_id' => $agent_id,
            'mobile_number' => $mobile_number,
            'name' => $phonebook_name
        ];

        $id = $phonebook_id;

        if (empty($phonebook_id)) {
            // add
            $id = DB::table('common_guest_phonebook')
                ->insertGetId($input);
        } else {
            // update
            DB::table('common_guest_phonebook')
                ->where('id', $phonebook_id)
                ->update($input);
        }

        $message = array();
        $message['type'] = 'chat_event';
        $message['sub_type'] = 'updated_phonebook_info';
        $message['data'] = [
            'property_id' => $property_id
        ];

        Redis::publish('notify', json_encode($message));

        $ret = [
            'success' => true,
            'id' => $id
        ];

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getChatSessionHistoryList(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date('Y-m-d H:i:s');
        $last24 = date('Y-m-d H:i:s', strtotime(' -10 day'));

        $property_id = $request->get('property_id', 0);
//        $guest_id = $request->get('guest_id', 0);
        $mobile_number = $request->get('mobile_number', '');

        $query = DB::table('services_chat_guest_session as cgs')
            // ->leftJoin('common_guest as cg', 'cgs.guest_id', '=', 'cg.guest_id')
            ->leftJoin('common_guest as cg', function ($join) use ($property_id) {
                $join->on('cgs.guest_id', '=', 'cg.guest_id');
                $join->on('cgs.property_id', '=', 'cg.property_id');
            })
            ->leftJoin('common_users as cu', 'cgs.agent_id', '=', 'cu.id')
            ->leftJoin('common_users as cu1', 'cgs.transfer_id', '=', 'cu1.id')
            ->leftJoin('common_room as cr', 'cgs.room_id', '=', 'cr.id')
            ->leftJoin('common_room_type as crt', 'cr.type_id', '=', 'crt.id')
            ->leftJoin('common_language_code as lc', 'cgs.language', '=', 'lc.code')
            ->where('cgs.property_id', $property_id);

        if (!empty($mobile_number))
            $query->where('cgs.mobile_number', $mobile_number);

        $session_list = $query->orderBy('cgs.updated_at', 'desc')
            ->select(DB::raw('cgs.*, cr.room, crt.type as room_type, lc.language as language_name,
				CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name, CONCAT_WS(" ", cu1.first_name, cu1.last_name) as transfer_name,
				SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(cgs.start_time, cgs.created_at))) as wait_time,
				SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(\'' . $cur_time . '\', cgs.start_time))) as duration
				'))
            ->get();

        return Response::json($session_list);
    }

    /**
     * @param $guest_id
     */
    public function sendGuestOffline($guest_id)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        // find all active chat session for this agent
        $session_list = DB::table('services_chat_guest_session as cgs')
            ->join('common_room as cr', 'cgs.room_id', '=', 'cr.id')
            ->where('cgs.guest_id', $guest_id)
            ->whereIn('cgs.status', array(WAITING, ACTIVE))
            ->select(DB::raw('cgs.*, cr.room'))
            ->get();

        foreach ($session_list as $row) {
            $session = GuestChatSession::find($row->id);

            $session->status = ENDED;
            $session->end_time = $cur_time;
            $session->save();

            // send notify to all agents
            $notification = new SystemNotification();

            $notification->type = 'app.guestservice.chat';
            $notification->property_id = $session->property_id;

            $notification->content = sprintf('%s from Room %s logged out, chat ended!!', $row->guest_name, $row->room);
            $notification->notification_id = $row->id;

            $notification->created_at = $cur_time;
            $notification->save();

            CommonUser::addNotifyCount($session->property_id, 'app.guestservice.chat');

            $message = array();
            $message['type'] = 'webpush';
            $message['to'] = $session->property_id;
            $message['content'] = $notification;

            Redis::publish('notify', json_encode($message));

            // send notify to all agents
            $message = array();
            $message['type'] = 'chat_event';
            $message['sub_type'] = 'logout_chat';
            $message['data'] = $session;

            Redis::publish('notify', json_encode($message));
        }
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getChatActiveAgentList(Request $request)
    {
        $agent_id = $request->get('agent_id', 0);
        $property_id = $request->get('property_id', 0);
        $filter = $request->get('filter', '');

        $filter = "'%" . $filter . "%'";

        $agent_list = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->join('common_permission_members as pm', 'pm.perm_group_id', '=', 'jr.permission_group_id')
            ->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
            ->where('pr.name', 'app.guestservice.chat')
            ->where('cd.property_id', $property_id)
            ->where('cu.id', '!=', $agent_id)
            ->where('cu.web_login', 1)
            ->where('cu.active_status', 1)
            ->where('cu.deleted', 0)
            ->whereRaw('CONCAT_WS(" ", cu.first_name, cu.last_name) like ' . $filter)
            ->select(DB::raw('cu.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name'))
            ->get();

        return Response::json($agent_list);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function transferChat(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $session_id = $request->get('session_id', 0);
        $origin_agent_id = $request->get('origin_agent_id', 0);
        $new_agent_id = $request->get('new_agent_id', 0);

        $session = GuestChatSession::find($session_id);

        $ret = array();

        $ret['code'] = 200;

        if (empty($session))        // there is no guest chat session
        {
            $ret['code'] = 201;
            $ret['message'] = 'Invalid Chat Session';
            return Response::json($ret);
        }

        if ($session->status != ACTIVE) {
            $ret['code'] = 201;
            $ret['message'] = 'Chat Session is not active';
            return Response::json($ret);
        }

        $session->transfer_id = $new_agent_id;

        $session->save();

        $row = DB::table('services_chat_guest_session as cgs')
            ->join('common_room as cr', 'cgs.room_id', '=', 'cr.id')
            ->join('common_users as cu', 'cgs.agent_id', '=', 'cu.id')
            ->join('common_users as cu1', 'cgs.transfer_id', '=', 'cu1.id')
            ->where('cgs.id', $session_id)
            ->select(DB::raw('cgs.*, cr.room, CONCAT_WS(" ", cu.first_name, cu.last_name) as agent_name, 
            CONCAT_WS(" ", cu1.first_name, cu1.last_name) as transfer_name'))
            ->first();


        // send notify to all agents
        $notification = new SystemNotification();

        $notification->type = 'app.guestservice.chat';
        $notification->property_id = $session->property_id;

        $notification->content = sprintf('%s request to tranfer chat to %s for %s from Room %s.',
            $row->agent_name, $row->transfer_name, $row->guest_name, $row->room);

        $notification->notification_id = $session->id;

        $notification->created_at = $cur_time;
        $notification->save();

        CommonUser::addNotifyCount($session->property_id, 'app.guestservice.chat');

        $message = array();
        $message['type'] = 'webpush';
        $message['to'] = $session->property_id;
        $message['content'] = $notification;

        Redis::publish('notify', json_encode($message));

        // send notify to all agents
        $message = array();
        $message['type'] = 'chat_event';
        $message['sub_type'] = 'transfer_chat';
        $message['data'] = $session;

        Redis::publish('notify', json_encode($message));

        $ret['session'] = $session;

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelTransfer(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $session_id = $request->get('session_id', 0);

        $session = GuestChatSession::find($session_id);

        $ret = array();

        $ret['code'] = 200;

        if (empty($session))        // there is no guest chat session
        {
            $ret['code'] = 201;
            $ret['message'] = 'Invalid Chat Session';
            return Response::json($ret);
        }

        if ($session->status != ACTIVE) {
            $ret['code'] = 201;
            $ret['message'] = 'Chat Session is not active';
            return Response::json($ret);
        }

        $session->transfer_id = 0;

        $session->save();

        // send notify to all agents
        $message = array();
        $message['type'] = 'chat_event';
        $message['sub_type'] = 'cancel_transfer';
        $message['data'] = $session;

        Redis::publish('notify', json_encode($message));

        $ret['session'] = $session;

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function onReceiveMessageFromAgentToAgent(Request $request)
    {
        $input = array();

        $input['property_id'] = $request->get('property_id', 0);
        $input['from_id'] = $request->get('from_id', 0);
        $input['to_id'] = $request->get('to_id', 0);
        $input['text'] = $request->get('text', '');
        $input['type'] = $request->get('type', 1);
        $input['direction'] = 1;    // outgoing
        $input['path'] = $request->get('path', '');
        $input['ack'] = 1;
        $input['unread'] = 0;

        $outgoing_id = DB::table('services_chat_agent_history')->insertGetId($input);

        $input['direction'] = 0;    // incoming
        $input['from_id'] = $request->get('to_id', 0);
        $input['to_id'] = $request->get('from_id', 0);
        $input['unread'] = 1;

        $incoming_id = DB::table('services_chat_agent_history')->insertGetId($input);

        $input['id'] = $incoming_id;

        $ret = array();
        $ret['outgoing_msg_id'] = $outgoing_id;
        $ret['incoming_msg_id'] = $incoming_id;
        $ret['unread_cnt'] = AgentChatHistory::getUnreadCount($request->get('to_id', 0), $request->get('from_id', 0));
        $ret['unread_total_cnt'] = AgentChatHistory::getTotalUnreadCount($request->get('to_id', 0));
        $ret['message'] = $input;

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function onReceiveMessageGroup(Request $request)
    {
        $input = array();

        $input['property_id'] = $request->get('property_id', 0);
        $input['from_id'] = $request->get('from_id', 0);
        $input['group_id'] = $request->get('group_id', 0);
        $input['text'] = $request->get('text', '');
        $input['type'] = $request->get('type', 1);
        $input['direction'] = 1;    // outgoing
        $input['path'] = $request->get('path', '');
        $input['ack'] = $request->get('ack', 1);
        $input['unread'] = 0;

        $outgoing_id = DB::table('services_chat_agent_history')->insertGetId($input);

        $ret = array();
        $ret['outgoing_msg_id'] = $outgoing_id;
        //$ret['unread_cnt'] = AgentChatHistory::getUnreadCount($request->get('to_id', 0), $request->get('from_id', 0));
        //$ret['unread_total_cnt'] = AgentChatHistory::getTotalUnreadCount($request->get('to_id', 0));

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getAgentChatHistory(Request $request)
    {
        $from_id = $request->get('from_id', 0);
        $to_id = $request->get('to_id', 0);
        $last_id = $request->get('last_id', 0);
        $pageSize = $request->get('pageSize', 20);
        $base_url = $request->get('base_url', Functions::getSiteURL());

        $query = DB::table('services_chat_agent_history as hist')
            ->leftJoin('common_users as cu1', 'hist.from_id', '=', 'cu1.id')
            ->leftJoin('common_users as cu2', 'hist.to_id', '=', 'cu2.id')
            ->where('from_id', $from_id)
            ->where('to_id', $to_id);

        if ($last_id > 0)
            $query->where('hist.id', '<', $last_id);

        $list = $query->orderBy('hist.id', 'desc')
            ->select(DB::raw('hist.*, 
				CONCAT_WS(" ", cu1.first_name, cu1.last_name) as from_name,
				cu1.picture as from_picture,
				CONCAT_WS(" ", cu2.first_name, cu2.last_name) as to_name,
				cu2.picture as to_picture,
				cu2.active_status
				'))
            ->take($pageSize)
            ->get();

        if (empty($list) || count($list) < 1) {
            $last_id = -1;
            $first_id = -1;
        } else {
            $last_id = $list[count($list) - 1]->id;
            $first_id = $list[0]->id;;
        }

        $ids = [];
        foreach ($list as $key => $row) {
            $ids[] = $row->id;
            $row->from_picture = $base_url . $row->from_picture;
            $row->to_picture = $base_url . $row->to_picture;
            if ($row->type > 1)    // file
            {
                $row->path = $base_url . $row->path;
            }
        }

        DB::table('services_chat_agent_history as hist')
            ->where('hist.from_id', $from_id)
            ->where('hist.unread', '>', 0)
            ->update(array('unread' => 0));

        $ret = array();

        $ret['code'] = 200;
        $data = array();

        $data['list'] = $list;
        $data['last_id'] = $last_id;
        $data['first_id'] = $first_id;

        $ret['content'] = $data;

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function setReadFlag(Request $request)
    {
        $from_id = $request->get('from_id', 0);
        $to_id = $request->get('to_id', 0);
        $last_id = $request->get('last_id', 0);

        $query = DB::table('services_chat_agent_history as hist')
            ->where('from_id', $from_id)
            ->where('to_id', $to_id);

        if ($last_id > 0)
            $query->where('hist.id', '>', $last_id);

        $query->update(array('unread' => 0));

        $ret = array();

        $ret['code'] = 200;

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function uploadFiles(Request $request)
    {
        $output_dir = "uploads/chat/";

        if(!File::isDirectory(public_path($output_dir)))
            File::makeDirectory(public_path($output_dir), 0777, true, true);

        $ret = array();

        $filekey = 'myfile';

        if ($request->hasFile($filekey) === false) {
            $ret['code'] = 201;
            $ret['message'] = "No input file";
            $ret['content'] = array();
            return Response::json($ret);
        }

        $outgoing_msg_id = $request->get('outgoing_msg_id', 0);
        $incoming_msg_id = $request->get('incoming_msg_id', 0);


        //You need to handle  both cases
        //If Any browser does not support serializing of multiple files using FormData()
        if (!is_array($_FILES[$filekey]["name"])) //single file
        {
            if ($request->file($filekey)->isValid() === false) {
                $ret['code'] = 202;
                $ret['message'] = "No valid file";
                return Response::json($ret);
            }

            $fileName = $_FILES[$filekey]["name"];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $filename1 = "chat_" . time() . "." . strtolower($ext);

            $dest_path = $output_dir . $filename1;

            move_uploaded_file($_FILES[$filekey]["tmp_name"], $dest_path);
        } else  //Multiple files, file[]
        {
            $filename = array();
            $fileCount = count($_FILES[$filekey]["name"]);
            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = $_FILES[$filekey]["name"][$i];
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $filename1 = "chat_" . time() . '_' . ($i + 1) . "." . strtolower($ext);

                $dest_path = $output_dir . $filename1;
                move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);
                $filename[$i] = $dest_path;
            }

        }

        $outgoing = AgentChatHistory::find($outgoing_msg_id);
        if (!empty($outgoing)) {
            $outgoing->path = $dest_path;
            $outgoing->save();
        }

        $incoming = AgentChatHistory::find($incoming_msg_id);
        if (!empty($incoming)) {
            $incoming->path = $dest_path;
            $incoming->save();
        }

        $incoming = $this->getMessageDetail($incoming_msg_id);
        // $outgoing = $this->getMessageDetail($outgoing_msg_id);

        $message = array();
        $message['type'] = 'agent_chat_event';
        $message['sub_type'] = 'uploaded_file_chat';
        $message['data'] = $incoming;
        // $message['data'] = $outgoing; // for test

        Redis::publish('notify', json_encode($message));


        $ret['code'] = 200;
        $ret['message'] = "File is uploaded successfully";
        $ret['content'] = $dest_path;
        $ret['outgoing_msg_id'] = $outgoing_msg_id;
        $ret['incoming_msg_id'] = $incoming_msg_id;

        return Response::json($ret);

    }

    /**
     * @param $id
     * @return mixed
     */
    private function getMessageDetail($id)
    {
        $message = DB::table('services_chat_agent_history as hist')
            ->leftJoin('common_users as cu1', 'hist.from_id', '=', 'cu1.id')
            ->leftJoin('common_users as cu2', 'hist.to_id', '=', 'cu2.id')
            ->where('hist.id', $id)
            ->select(DB::raw('hist.*, 
				CONCAT_WS(" ", cu1.first_name, cu1.last_name) as from_name,
				cu1.picture as from_picture,
				CONCAT_WS(" ", cu2.first_name, cu2.last_name) as to_name,
				cu2.picture as to_picture,
				cu2.active_status
				'))
            ->first();

        if (!empty($message)) {
            $message->path = Functions::getSiteURL() . $message->path;
        }

        return $message;
    }

    public function uploadFilesGroup(Request $request)
    {
        $output_dir = "uploads/chat/";

        if(!File::isDirectory(public_path($output_dir)))
            File::makeDirectory(public_path($output_dir), 0777, true, true);

        $ret = array();

        $filekey = 'myfile';

        if ($request->hasFile($filekey) === false) {
            $ret['code'] = 201;
            $ret['message'] = "No input file";
            $ret['content'] = array();
            return Response::json($ret);
        }

        $outgoing_msg_id = $request->get('outgoing_msg_id', 0);

        //You need to handle  both cases
        //If Any browser does not support serializing of multiple files using FormData()
        if (!is_array($_FILES[$filekey]["name"])) //single file
        {
            if ($request->file($filekey)->isValid() === false) {
                $ret['code'] = 202;
                $ret['message'] = "No valid file";
                return Response::json($ret);
            }

            $fileName = $_FILES[$filekey]["name"];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $filename1 = "chat_" . time() . "." . strtolower($ext);

            $dest_path = $output_dir . $filename1;

            move_uploaded_file($_FILES[$filekey]["tmp_name"], $dest_path);
        } else  //Multiple files, file[]
        {
            $filename = array();
            $fileCount = count($_FILES[$filekey]["name"]);
            for ($i = 0; $i < $fileCount; $i++) {
                $fileName = $_FILES[$filekey]["name"][$i];
                $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                $filename1 = "chat_" . time() . '_' . ($i + 1) . "." . strtolower($ext);

                $dest_path = $output_dir . $filename1;
                move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);
                $filename[$i] = $dest_path;
            }

        }

        $outgoing = AgentChatHistory::find($outgoing_msg_id);
        if (!empty($outgoing)) {
            $outgoing->path = $dest_path;
            $outgoing->save();
        }


        $incoming = $this->getGroupMessageDetail($outgoing_msg_id);

        $message = array();
        $message['type'] = 'group_chat_event';
        $message['sub_type'] = 'uploaded_file_chat';
        $message['data'] = $incoming;
        // $message['data'] = $outgoing; // for test

        Redis::publish('notify', json_encode($message));

        $ret['code'] = 200;
        $ret['message'] = "File is uploaded successfully";
        $ret['content'] = $dest_path;
        $ret['outgoing_msg_id'] = $outgoing_msg_id;

        return Response::json($ret);

    }

    /**
     * @param $id
     * @return mixed
     */
    private function getGroupMessageDetail($id)
    {
        $message = DB::table('services_chat_agent_history as hist')
            ->leftJoin('common_users as cu1', 'hist.from_id', '=', 'cu1.id')
            ->where('hist.id', $id)
            ->select(DB::raw('hist.*, 
				CONCAT_WS(" ", cu1.first_name, cu1.last_name) as from_name,
				cu1.picture as from_picture,
				cu1.active_status
				'))
            ->first();

        if (!empty($message)) {
            $message->path = Functions::getSiteURL() . $message->path;
        }

        $participant_list = DB::table('services_groupchat_uesrs as gcu')
            ->leftJoin('common_users as cu', 'gcu.user_id', '=', 'cu.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->where('gcu.group_id', $message->group_id)
            ->select(DB::raw('gcu.*, 
				CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
				cu.picture,jr.job_role as job_role_name,
				cu.active_status'))
            ->get();
        $to_ids = array();
        for ($i = 0; $i < count($participant_list); $i++) {
            $to_ids[] = $participant_list[$i]->user_id;
        }
        $message->to_ids = $to_ids;

        return $message;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getGroupChatList(Request $request)
    {
        $group_name = $request->get("group_name", '');


        $query = DB::table('services_groupchat as gc')
            ->leftJoin('common_users as cu', 'gc.created_by', '=', 'cu.id')
            ->whereNull('deleted_at');
        if ($group_name != '')
            $query->where('groupchat_name', 'LIKE', '%' . $group_name . '%');

        $group_list = $query->select(DB::raw('gc.*, gc.profile_picture as profile_image,
				CONCAT_WS(" ", cu.first_name, cu.last_name) as created_name,
				cu.picture as created_picture,
				cu.active_status'))
            ->orderBy('created_at', 'desc')
            ->get();

        $ret = array();
        $ret['group_list'] = $group_list;
        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function detailGroupChat(Request $request)
    {
        $group_id = $request->get("group_id", 0);

        $participant_list = DB::table('services_groupchat_uesrs as gcu')
            ->leftJoin('common_users as cu', 'gcu.user_id', '=', 'cu.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->where('gcu.group_id', $group_id)
            ->select(DB::raw('gcu.*, 
				CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
				cu.picture,jr.job_role as job_role_name,
				cu.active_status'))
            ->get();

        $ret = array();
        $ret['list'] = $participant_list;
        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function createNewGroup(Request $request)
    {
        $group_name = $request->get("group_name", "");
        $description = $request->get("description", "");
        $participants = $request->get("participants", []);
        $created_by = $request->get("user_id", 0);

        $ret = array();

        $group_list = DB::table('services_groupchat as gc')
            ->where('groupchat_name', $group_name)
            ->get();

        if (count($group_list) > 0) {
            $ret['code'] = 201;
            return Response::json($ret);
        }

        $group_id = DB::table('services_groupchat')->insertGetId([
            "groupchat_name" => $group_name,
            "description" => $description,
            "created_by" => $created_by
        ]);

        for ($i = 0; $i < count($participants); $i++) {
            DB::table('services_groupchat_uesrs')->insert([
                "group_id" => $group_id,
                "user_id" => $participants[$i],
            ]);
        }
        DB::table('services_groupchat_uesrs')->insert([
            "group_id" => $group_id,
            "user_id" => $created_by,
        ]);
        $group_list = DB::table('services_groupchat as gc')
            ->leftJoin('common_users as cu', 'gc.created_by', '=', 'cu.id')
            ->whereNull('deleted_at')
            ->select(DB::raw('gc.*, gc.profile_picture as profile_image,
				CONCAT_WS(" ", cu.first_name, cu.last_name) as created_name,
				cu.picture as created_picture,
				cu.active_status'))
            ->orderBy('created_at', 'desc')
            ->get();


        $ret['code'] = 200;
        $ret['group_id'] = $group_id;
        $ret['group_list'] = $group_list;
        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function updateGroupChat(Request $request)
    {
        $group_id = $request->get("id", "");
        $group_name = $request->get("groupchat_name", "");
        $description = $request->get("description", "");
        $participants = $request->get("participants", []);
        $created_by = $request->get("user_id", 0);

        $ret = array();

        $group_list = DB::table('services_groupchat as gc')
            ->where('id', '<>', $group_id)
            ->where('groupchat_name', $group_name)
            ->get();

        if (count($group_list) > 0) {
            $ret['code'] = 201;
            return Response::json($ret);
        }

        DB::table('services_groupchat')
            ->where('id', $group_id)
            ->update([
                "groupchat_name" => $group_name,
                "description" => $description,
                "created_by" => $created_by
            ]);

        DB::table('services_groupchat_uesrs')
            ->where('group_id', $group_id)
            ->delete();

        for ($i = 0; $i < count($participants); $i++) {
            DB::table('services_groupchat_uesrs')->insert([
                "group_id" => $group_id,
                "user_id" => $participants[$i],
            ]);
        }

        /*DB::table('services_groupchat_uesrs')->insert([
            "group_id" => $group_id,
            "user_id"    => $created_by,
        ]);*/ // Adding creator . group chat owner
        $group_list = DB::table('services_groupchat as gc')
            ->leftJoin('common_users as cu', 'gc.created_by', '=', 'cu.id')
            ->whereNull('deleted_at')
            ->select(DB::raw('gc.*, gc.profile_picture as profile_image,
				CONCAT_WS(" ", cu.first_name, cu.last_name) as created_name,
				cu.picture as created_picture,
				cu.active_status'))
            ->orderBy('created_at', 'desc')
            ->get();


        $ret['code'] = 200;
        $ret['group_id'] = $group_id;
        $ret['group_list'] = $group_list;
        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function deleteGroupChat(Request $request)
    {

        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $group_id = $request->get("group_id", 0);
        DB::table('services_groupchat')
            ->where('id', $group_id)
            ->update([
                "status" => 1,
                "deleted_at" => $cur_time,
            ]);


        $group_list = DB::table('services_groupchat as gc')
            ->leftJoin('common_users as cu', 'gc.created_by', '=', 'cu.id')
            ->whereNull('deleted_at')
            ->select(DB::raw('gc.*, gc.profile_picture as profile_image,
				CONCAT_WS(" ", cu.first_name, cu.last_name) as created_name,
				cu.picture as created_picture,
				cu.active_status'))
            ->orderBy('created_at', 'desc')
            ->get();

        $ret = array();

        $ret['code'] = 200;
        $ret['group_id'] = $group_id;
        $ret['group_list'] = $group_list;
        return Response::json($ret);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function uploadProfilePicture(Request $request)
    {
        $output_dir = $_SERVER["DOCUMENT_ROOT"] . '/uploads/chat/group/';
        if (!file_exists($output_dir)) {
            mkdir($output_dir, 0777, true);
        }
        $output_dir = "uploads/chat/group/";
        $ret = array();
        $filekey = 'files';
        $group_id = $request->get('group_id', 0);

        $fileName = $_FILES[$filekey]["name"];
        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $filename1 = "group_" . $group_id . '_' . $fileName;

        $dest_path = $output_dir . $filename1;
        move_uploaded_file($_FILES[$filekey]["tmp_name"], $dest_path);

        DB::table('services_groupchat')->where('id', $group_id)
            ->update([
                "profile_picture" => $dest_path,
            ]);

        $group_list = DB::table('services_groupchat as gc')
            ->leftJoin('common_users as cu', 'gc.created_by', '=', 'cu.id')
            ->whereNull('deleted_at')
            ->select(DB::raw('gc.*, gc.profile_picture as profile_image,
				CONCAT_WS(" ", cu.first_name, cu.last_name) as created_name,
				cu.picture as created_picture,
				cu.active_status'))
            ->orderBy('created_at', 'desc')
            ->get();

        $ret = array();

        $ret['code'] = 200;
        $ret['group_id'] = $group_id;
        $ret['group_list'] = $group_list;
        $ret['file'] = $_FILES[$filekey];
        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function addNewQuickTask(Request $request)
    {
//        $phone_number = $request->get('phone_number', '');
        $guest_id = $request->get('guest_id', 0);
        $task_id = $request->get('task_id', 0);

        $ret = [
            'success' => true,
            'result' => []
        ];
//
//        if (empty($phone_number) || empty($task_id)) {
//            return Response::json($ret);
//        }

        if (empty($guest_id) || empty($task_id)) {
            return Response::json($ret);
        }

        //        get guest info (
//        $guestResult = $this->getGuestInfoFromPhoneNumber($phone_number);

        $guestResult = $this->getGuestInfoFromGuestId($guest_id);

        if (empty($guestResult)) {
            $ret['success'] = false;
            return Response::json($ret);
        }


        $ret['success'] = true;
//        $ret['result']['guest_name'] = $guestResult->guest_name;
//        $ret['result']['phone_number'] = $phone_number;
        $ret['result']['info_list'] = [];

        $property_id = $guestResult->property_id;
        $room_id = $guestResult->room_id;

        $locationGroupInfo = $this->getLocationGroupIDFromRoom($room_id);

        if (empty($locationGroupInfo)) {
            $ret['success'] = false;
            return Response::json($ret);
        }

        // get task info from task_id, $location group id

        $taskInfo = $this->getTaskShiftInfo($task_id, $locationGroupInfo->id);

        if (empty($taskInfo)) {
            $ret['success'] = false;
            return Response::json($ret);
        }

        $ret['result']['task_info'] = $taskInfo;
        $ret['result']['property_id'] = $property_id;
        $ret['result']['room_id'] = $room_id;
        $ret['result']['task_id'] = $task_id;
//        $ret['result']['guest_id'] = $guestResult->guest_id;
        $ret['result']['location_id'] = $locationGroupInfo->id;

        return Response::json($ret);
    }

    /**
     * @param $guest_id
     * @return mixed
     */
    private function getGuestInfoFromGuestId($guest_id)
    {
        $guestResult = DB::table('common_guest as cg')
            ->leftJoin('common_vip_codes as cvc', 'cvc.vip_code', '=', 'cg.vip')
            ->leftJoin('common_room as cr', 'cr.id', '=', 'cg.room_id')
            ->where('cg.guest_id', $guest_id)
            ->select(DB::raw('cg.id as id, cg.guest_name as guest_name, cg.language as language,
             cg.property_id as property_id, cr.type_id as room_type_id, cvc.id as vip_id, cg.room_id as room_id'))
            ->first();

        return $guestResult;
    }

    /**
     * @param $room_id
     * @return |null
     */
    private function getLocationGroupIDFromRoom($room_id)
    {
        $location_info = Location::getLocationFromRoom($room_id);

        return $location_info;
    }

    /**
     * @param $task_id
     * @param $location_id
     * @return array
     */
    private function getTaskShiftInfo($task_id, $location_id)
    {
        // find department function
        $ret = array();
        $model = TaskList::find($task_id);


        $taskgroup = $model->taskgroup;
        if (empty($taskgroup) || count($taskgroup) < 1) {
            $ret['code'] = 201;
            $ret['message'] = 'No task group.';
            return $ret;
        }

        $task = $taskgroup[0];

        return $this->getTaskShiftInfoData($task_id, $task, $location_id);
    }

    /**
     * @param $task_id
     * @param $taskgroup
     * @param $location
     * @return array
     */
    private function getTaskShiftInfoData($task_id, $taskgroup, $location)
    {
        $ret = array();

        // // find department function
        // $model = TaskList::find($task_id);

        $task = $taskgroup;
        $ret['taskgroup'] = $task;

        $dept_func_id = $task->dept_function;

        $dept_func = DeftFunction::find($dept_func_id);
        if (empty($dept_func))
            return $ret;

        // find building id
        $building_id = Location::find($location)->building_id;

        // find job role for level = 0
        $escalation = Escalation::where('escalation_group', $dept_func_id)
            ->where('level', 0)
            ->first();

        $job_role_id = 0;

        if (!empty($escalation))
            $job_role_id = $escalation->job_role_id;

        $ret['deptfunc'] = $dept_func;

        // find department and property
        $department = Department::find($dept_func->dept_id);
        $ret['department'] = $department;

        date_default_timezone_set(config('app.timezone'));
        $datetime = date('Y-m-d H:i:s');

        $shift_group_members = array();

        // find staff list
        if ($taskgroup->reassign_flag == 1 && $taskgroup->reassign_job_role != '') {
            $shift_arr = explode(",", $taskgroup->reassign_job_role);
            foreach ($shift_arr as $value) {
                // $shift_group_member = ShiftGroupMember::getUserlistOnCurrentShift($department->property_id, $value,
                // $dept_func->dept_id, 0, 0,
                // $location_group_id, $task->id, true, false);
                $shift_group_member = ShiftUser::getUserlistOnCurrentShift($value, $dept_func_id, $taskgroup->id,
                    $location, $building_id, true);
                foreach ($shift_group_member as $row)
                    $shift_group_members[] = $row;
            }
            $ret['sels'] = $shift_group_members;
        } else {
            $ret['sels'] = '1';

            $setting = DeftFunction::getGSDeviceSetting($department->id, $dept_func_id);

            if ($setting == 0)    // User Based
            {
                // find staff list
                // $shift_group_members = ShiftGroupMember::getUserlistOnCurrentShift($department->property_id,
                // $job_role_id, $dept_func->dept_id, 0, 0, $location_group_id, $task->id, true, false);
                $shift_group_members = ShiftUser::getUserlistOnCurrentShift($job_role_id, $dept_func_id,
                    $taskgroup->id, $location, $building_id, true);
            } else if ($setting == 1) {
                // $shift_group_members = $this->getUserListDeviceBasedDeptFunc($location_group_id,$location,
                // $department->property_id, $job_role_id, $dept_func);
                $shift_group_members = ShiftUser::getDevicelistOnCurrentShift(0, $dept_func_id, $location,
                    $building_id, true);

                // Log::info(json_encode($shift_group_members));
            } else if ($setting == 2) {
                $loc_type = LocationType::createOrFind('Room');
                $location_room = Location::where('id', $location)
                    ->where('type_id', $loc_type->id)
                    ->select('room_id')
                    ->first();
                if (!empty($location_room))
                    $shift_group_members = RosterList::getRosterListFromRoomDeptFunc($dept_func->id, $location_room->room_id);

                Log::info(json_encode($shift_group_members));
            }

            $ret['setting'] = $setting;
        }

        if ($task->unassigne_flag == 1)
            $shift_group_members = [];


        // sort active staff by complete time
        $time = array();
        foreach ($shift_group_members as $key => $row) {
            // calculate max complete time for each staff
            $assigned_flag = Task::whereRaw('DATE(start_date_time) = CURDATE()')
                ->where('dispatcher', $row->user_id)
                ->where(function ($query) use ($datetime, $task) {
                    $query->whereIn('status_id', array(OPEN, ESCALATED))
                        ->orWhere(function ($subquery) use ($datetime, $task) {    // vacation period
                            $subquery->where('status_id', SCHEDULED)
                                ->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) < $task->max_time");
                        });
                })
                ->exists();

            if ($assigned_flag == false)    // free staff
            {
                // calcuate spent time for free staff
                $spent = DB::table('services_task')
                    ->whereRaw('DATE(start_date_time) = CURDATE()')
                    ->where('dispatcher', $row->user_id)
                    ->where(function ($query) use ($datetime, $task) {
                        $query->whereIn('status_id', array(COMPLETED, TIMEOUT, CANCELED))
                            ->orWhere(function ($subquery) use ($datetime, $task) {    // vacation period
                                $subquery->where('status_id', SCHEDULED)
                                    ->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) >= $task->max_time");
                            });
                    })
                    ->select(DB::raw('sum(duration) AS spent'))
                    ->first();

                if (empty($spent))
                    $difftime = 0;
                else
                    $difftime = $spent->spent;

                $time[$key] = $difftime;
                $shift_group_members[$key]->spent = $difftime;
                $shift_group_members[$key]->assigned = false;
            } else    // active staff
            {
                // calcuate max complete time for active staff
                $completetime = DB::table('services_task')
                    ->whereRaw('DATE(start_date_time) = CURDATE()')
                    ->where('dispatcher', $row->user_id)
                    ->where(function ($query) use ($datetime, $task) {
                        $query->whereIn('status_id', array(OPEN, ESCALATED))
                            ->orWhere(function ($subquery) use ($datetime, $task) {    // vacation period
                                $subquery->where('status_id', SCHEDULED)
                                    ->whereRaw("TIME_TO_SEC(TIMEDIFF(start_date_time, '$datetime')) < $task->max_time");
                            });
                    })
                    ->orderBy('complete', 'desc')
                    ->select(DB::raw('max(TIME_TO_SEC(start_date_time) + max_time) AS complete'))
                    ->first();

                $time[$key] = $completetime->complete + 60 * 24 * 265;    // 1 year +
                $shift_group_members[$key]->spent = $time[$key];
                $shift_group_members[$key]->assigned = true;
            }
        }

        array_multisort($time, SORT_ASC, $shift_group_members);

        $ret['staff_list'] = $shift_group_members;

        $ret['check'] = $taskgroup->reassign_flag . ' ' . $taskgroup->reassign_job_role;

        $prioritylist = Priority::all();
        $ret['prioritylist'] = $prioritylist;
        $ret['shift_group_members'] = $shift_group_members;
        $ret['code'] = 200;
        return $ret;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getGroupChatHistory(Request $request)
    {
        $group_id = $request->get('group_id', 0);
        $pageSize = $request->get('pageSize', 20);
        $base_url = $request->get('base_url', Functions::getSiteURL());
        $last_id = $request->get('last_id', 0);

        $query = DB::table('services_chat_agent_history as hist')
            ->leftJoin('common_users as cu1', 'hist.from_id', '=', 'cu1.id')
            ->where('group_id', $group_id);

        if ($last_id > 0)
            $query->where('hist.id', '<', $last_id);


        $list = $query->orderBy('hist.id', 'desc')
            ->select(DB::raw('hist.*,
				CONCAT_WS(" ", cu1.first_name, cu1.last_name) as from_name,
				cu1.picture as from_picture,
				cu1.active_status
				'))
            ->take($pageSize)
            ->get();
        if (empty($list) || count($list) < 1) {
            $last_id = -1;
            $first_id = -1;
        } else {
            $last_id = $list[count($list) - 1]->id;
            $first_id = $list[0]->id;;
        }

        $ids = [];
        foreach ($list as $key => $row) {
            $ids[] = $row->id;
            $row->from_picture = $base_url . $row->from_picture;
            //$row->to_picture = $base_url . $row->to_picture;
            if ($row->type > 1)    // file
            {
                $row->path = $base_url . $row->path;
            }
        }

        DB::table('services_chat_agent_history as hist')
            ->whereIn('hist.id', $ids)
            ->update(array('unread' => 0));

        $ret = array();

        $ret['code'] = 200;
        $ret['list'] = $list;
        $ret['last_id'] = $last_id;
        $ret['first_id'] = $first_id;

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function sendAttachmentFromAgent(Request $request)
    {
        $text = $request->get('text', '');
        $mobile_number = $request->get('mobile_number', '');
        $property_id = $request->get('property_id', 4);
        $language = $request->get('lang_code', 'en');
        $session_id = $request->get('session_id', 0);
        $room = $request->get('room', 0);
        $guest_type = $request->get('guest_type', '');
        $language_name = $request->get('language_name', 'English');

        $chat_type = $request->get('chat_type', '');
        $attachment = $request->get('attachment', '');
        $media_id = $request->get('media_id', 1);

        $filename = $request->get('filename', '');
        $message_info = [];
        $message_info['text'] = $chat_type == 'image' || $chat_type == 'video' ? $text : $filename;
        $message_info['mobile_number'] = $request->get('mobile_number');

        $other_info = [
            'guest_type' => $guest_type,
            'room' => $room,
            'language_name' => $language_name
        ];

        // save chat info
        $saveInfo = [
            'property_id' => $property_id,
            'session_id' => $session_id,
            'agent_id' => $request->get('agent_id', 0),
            'guest_id' => $request->get('guest_id', 0),
            'cur_chat_name' => '',
            'mobile_number' => $mobile_number,
            'text' => $text,
            'text_trans' => '',
            'language' => $language,
            'sender' => 'server',
            'chat_type' => $chat_type,
            'attachment' => $attachment,
            'other_info' => json_encode($other_info)
        ];

        $ret = [
            'success' => false
        ];

        $responseWhatsapp = $this->sendMessageToWhatsapp($message_info, 'Attachment', $media_id);
        if ($responseWhatsapp != false) {
            $saveInfo['uuid'] = $responseWhatsapp->MessageUUID;
            $this->saveChatbotHistoryWhatsapp($saveInfo);

            $ret['success'] = true;
        }

        return Response::json($ret);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function sendMessageFromAgent(Request $request)
    {

        $text = $request->get('text', '');
        $mobile_number = $request->get('mobile_number', '');
        $property_id = $request->get('property_id', 4);
        $language = $request->get('lang_code', 'en');
        $session_id = $request->get('session_id', 0);
        $room = $request->get('room', 0);
        $guest_type = $request->get('guest_type', '');
        $language_name = $request->get('language_name', 'English');

        if ($language != 'en') {
            $text = $this->getTranslatedText('en', $language, $text);
        }

        $message_info = [];
        $message_info['text'] = $text;
        $message_info['mobile_number'] = $request->get('mobile_number');

        $other_info = [
            'guest_type' => $guest_type,
            'room' => $room,
            'language_name' => $language_name
        ];

        // save chat info
        $saveInfo = [
            'property_id' => $property_id,
            'session_id' => $session_id,
            'agent_id' => $request->get('agent_id', 0),
            'guest_id' => $request->get('guest_id', 0),
            'cur_chat_name' => '',
            'mobile_number' => $mobile_number,
            'text' => $text,
            'text_trans' => '',
            'language' => $language,
            'sender' => 'server',
            'chat_type' => 'text',
            'attachment' => '',
            'other_info' => json_encode($other_info)
        ];

        $ret = [
            'success' => false
        ];

        $responseWhatsapp = $this->sendMessageToWhatsapp($message_info);
        if ($responseWhatsapp != false) {
            $saveInfo['uuid'] = $responseWhatsapp->MessageUUID;
            $this->saveChatbotHistoryWhatsapp($saveInfo);

            $ret['success'] = true;
        }

        return Response::json($ret);
    }

    /**
     * @param Request $request
     */
    public function onReceiveMessageFromWhatsapp(Request $request)
    {
        $outgoing = $request->input('outgoing', true);

        if ($outgoing == false) {
//            $msgInfo = [];
//            $msgInfo['type'] = 'whatsapp';
//            $msgInfo['sub_type'] = 'guest_message';
//            $msgInfo['data'] = $request->input();
//
//            Redis::publish('notify', json_encode($msgInfo));

            $id = $request->input('id', '');
            $uuid = $request->input('uuid', '');

            if (!empty($id)) {

                // delete status
                return;
            }

            $payload = $request->input('payload', []);

            $chat_type = isset($payload['type']) ? $payload['type'] : '';
            $attachment = isset($payload['attachment']) ? $payload['attachment'] : '';
            $message_content = isset($payload['text']) ? $payload['text'] : '';
            $mobile_number = isset($payload['user']) ? $payload['user']['id'] : '';

            // get Info
            $info = $this->getInfoFromMobileNumber($mobile_number);

            $guest_info = $info['guest_info'];
            $session_info = $info['session_info'];
            $cur_chat_name = $info['cur_chat_name'];

            $other_info = $info['other_info'];

            $guest_path_list = $info['guest_path_list'];

            if ($session_info['status'] == ENDED) {

                $guest_info['agent_id'] = 0;

                $session_info['id'] = 0;
                $session_info['agent_id'] = 0;

                // save current chat
                $saveInfo = [
                    'property_id' => $guest_info['property_id'],
                    'mobile_number' => $mobile_number,
                    'session_id' => $session_info['id'],
                    'guest_id' => $guest_info['guest_id'],
                    'agent_id' => $session_info['agent_id'],
                    'text' => $message_content,
                    'text_trans' => '',
                    'sender' => 'guest',
                    'language' => $session_info['language'],
                    'chat_type' => $chat_type,
                    'attachment' => $attachment,
                    'other_info' => json_encode($other_info),
                    'cur_chat_name' => $cur_chat_name,
                    'uuid' => $uuid
                ];

                $this->saveChatbotHistoryWhatsapp($saveInfo);

                $guest_id = $guest_info['guest_id'];
                $property_id = $guest_info['property_id'];

                $chat_name = CHAT_IN_HOUSE_MAIN_MENU;
                $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;

                if ($other_info->guest_type === "Outside") {
                    $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                    $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                }

                $guestResult = $this->getGuestInfoFromGuestId($guest_id);
                $room_type_id = 0;
                $vip_id = 0;

                if (!empty($guestResult)) {
                    $room_type_id = $guestResult->room_type_id;
                    $vip_id = $guestResult->vip_id;
                }

                $chatResult = $this->getChatResult($property_id, $chat_name, $room_type_id, $vip_id, $prev_chat_name);
                $saveInfo['cur_chat_name'] = $chat_name;
                $saveInfo['text'] = $chatResult->template;
                $saveInfo['session_id'] = 0;
                $saveInfo['agent_id'] = 0;
                if ($session_info['language'] != 'en') {
                    $saveInfo['text'] = $this->getTranslatedText('en', $session_info['language'], $saveInfo['text']);
                }

                $message_info = [
                    'text' => $saveInfo['text'],
                    'language' => $session_info['language'],
                    'mobile_number' => $mobile_number
                ];

                $responseWhatsapp = $this->sendMessageToWhatsapp($message_info);
                if ($responseWhatsapp != false) {
                    $saveInfo['uuid'] = $responseWhatsapp->MessageUUID;
                    $this->saveChatbotHistoryWhatsapp($saveInfo);
                }

                return;
            }

            $text_trans = '';
            if ($session_info['language'] != 'en') {
                $text_trans = $this->getTranslatedText($session_info['language'], 'en', $message_content);
            }

            // save current chat
            $saveInfo = [
                'property_id' => $guest_info['property_id'],
                'mobile_number' => $mobile_number,
                'session_id' => $session_info['id'],
                'guest_id' => $guest_info['guest_id'],
                'agent_id' => $session_info['agent_id'],
                'text' => $message_content,
                'text_trans' => $text_trans,
                'sender' => 'guest',
                'language' => $session_info['language'],
                'chat_type' => $chat_type,
                'attachment' => $attachment,
                'other_info' => json_encode($other_info),
                'cur_chat_name' => $cur_chat_name,
                'guest_path' => empty($guest_path_list) ? '' : implode(">>", $guest_path_list)
            ];

            $this->saveChatbotHistoryWhatsapp($saveInfo);

            if (!empty($session_info['id'])) { // chatting with agent
                if ($session_info['status'] == WAITING) {

                    if ($message_content === '0') { // end
                        $session_id = $session_info['id'];

                        DB::table('services_chat_guest_session')
                            ->where('id', $session_id)
                            ->update(['status' => ENDED]);

                        DB::table('services_chat_guest_session')
                            ->where('agent_id', '=', 0)
                            ->where('id', $session_id)
                            ->delete();
                        // send notification to agent

                        $message = array();
                        $message['type'] = 'chat_event';
                        $message['sub_type'] = 'exit_chat';
                        $message['data'] = [
                            'property_id' => $saveInfo['property_id']
                        ];

                        Redis::publish('notify', json_encode($message));

                        $guest_id = $guest_info['guest_id'];
                        $property_id = $guest_info['property_id'];

                        $chat_name = CHAT_IN_HOUSE_MAIN_MENU;
                        $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;

                        if ($other_info->guest_type === "Outside") {
                            $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                            $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                        }

                        $guestResult = $this->getGuestInfoFromGuestId($guest_id);
                        $room_type_id = 0;
                        $vip_id = 0;

                        if (!empty($guestResult)) {
                            $room_type_id = $guestResult->room_type_id;
                            $vip_id = $guestResult->vip_id;
                        }

                        $chatResult = $this->getChatResult($property_id, $chat_name, $room_type_id, $vip_id, $prev_chat_name);
                        $saveInfo['cur_chat_name'] = $chat_name;
                        $saveInfo['text'] = $chatResult->template;
                        $saveInfo['session_id'] = 0;
                        $saveInfo['agent_id'] = 0;
                        if ($session_info['language'] != 'en') {
                            $saveInfo['text'] = $this->getTranslatedText('en', $session_info['language'], $saveInfo['text']);
                        }

                        $message_info = [
                            'text' => $saveInfo['text'],
                            'language' => $session_info['language'],
                            'mobile_number' => $mobile_number
                        ];

                        $responseWhatsapp = $this->sendMessageToWhatsapp($message_info);
                        if ($responseWhatsapp != false) {
                            $saveInfo['uuid'] = $responseWhatsapp->MessageUUID;
                            $this->saveChatbotHistoryWhatsapp($saveInfo);
                        }
                    } else {
                        $guest_id = $guest_info['guest_id'];
                        $property_id = $guest_info['property_id'];
                        $chat_name = CHAT_AGENT_CALL_WAITING;
                        $prev_chat_name = CHAT_AGENT_CALL_WAITING;

                        $guestResult = $this->getGuestInfoFromGuestId($guest_id);
                        $room_type_id = 0;
                        $vip_id = 0;

                        if (!empty($guestResult)) {
                            $room_type_id = $guestResult->room_type_id;
                            $vip_id = $guestResult->vip_id;
                        }

                        $chatResult = $this->getChatResult($property_id, $chat_name, $room_type_id, $vip_id, $prev_chat_name);
                        $saveInfo['cur_chat_name'] = CHAT_AGENT_CALL_WAITING;
                        $saveInfo['text'] = $chatResult->template;

                        if ($session_info['language'] != 'en') {
                            $saveInfo['text'] = $this->getTranslatedText('en', $session_info['language'], $saveInfo['text']);
                        }

                        $message_info = [
                            'text' => $saveInfo['text'],
                            'language' => $session_info['language'],
                            'mobile_number' => $mobile_number
                        ];

                        $responseWhatsapp = $this->sendMessageToWhatsapp($message_info);
                        if ($responseWhatsapp != false) {
                            $saveInfo['uuid'] = $responseWhatsapp->MessageUUID;
                            $this->saveChatbotHistoryWhatsapp($saveInfo);
                        }
                    }
                    return;
                } else {
                    $message = [];
                    $message['session_id'] = $session_info['id'];
                    $message['guest_name'] = isset($guest_info['guest_name']) ? $guest_info['guest_name'] : '';
                    $message['guest_id'] = $guest_info['guest_id'];
                    $message['agent_id'] = $session_info['agent_id'];
                    $message['property_id'] = $guest_info['property_id'];
                    $message['room'] = isset($other_info->room) ? $other_info->room : 0;
                    $message['text'] = $message_content;
                    $message['created_at'] = date('Y-m-d H:i:s');

                    $message['chat_type'] = $chat_type;
                    $message['attachment'] = $attachment;

                    $message['direction'] = 1;
                    $message['language'] = $session_info['language'];

                    if ($session_info['language'] != 'en') {
                        $message['text_trans'] = $this->getTranslatedText($session_info['language'], 'en', $message_content);
                    }

                    $msgInfo = [];
                    $msgInfo['type'] = 'chat_event';
                    $msgInfo['sub_type'] = 'guest_message';
                    $msgInfo['data'] = $message;

                    Redis::publish('notify', json_encode($msgInfo));
                }
            } else {
                $isRecall = false;

                while (true) {
                    // get next chat info
                    $reqInfo = [
                        'mobile_number' => $mobile_number,
                        'property_id' => $guest_info['property_id'],
                        'message_content' => $isRecall ? '' : $message_content,
                        'prev_chat_name' => $cur_chat_name,
                        'guest_id' => $guest_info['guest_id'],
                        'guest_name' => isset($guest_info['guest_name']) ? $guest_info['guest_name'] : '',
                        'language' => $session_info['language'],
                        'room_id' => $guest_info['room_id'],
                        'session_id' => $session_info['id']
                    ];

                    $nextInfo = $this->getNextChatContentWhatsapp($reqInfo, $other_info, $guest_path_list);

                    $guest_info['guest_id'] = $nextInfo['guest_id'];
                    $guest_info['guest_name'] = isset($nextInfo['guest_name']) ? $nextInfo['guest_name'] : '';
                    $session_info['id'] = isset($nextInfo['session_id']) ? $nextInfo['session_id'] : $session_info['id'];
                    $cur_chat_name = $nextInfo['name'];

                    $session_info['language'] = $nextInfo['language'];

                    $text = $nextInfo['message_content'];

                    if ($session_info['language'] != 'en') {
                        $text = $this->getTranslatedText('en', $session_info['language'], $nextInfo['message_content']);
                    }

                    // save chat info
                    $saveInfo = [
                        'property_id' => $guest_info['property_id'],
                        'session_id' => $session_info['id'],
                        'agent_id' => $session_info['agent_id'],
                        'guest_id' => $guest_info['guest_id'],
                        'cur_chat_name' => $cur_chat_name,
                        'mobile_number' => $mobile_number,
                        'text' => $text,
                        'text_trans' => '',
                        'language' => $session_info['language'],
                        'sender' => 'server',
                        'chat_type' => 'text',
                        'attachment' => '',
                        'other_info' => json_encode($other_info),
                        'guest_path' => empty($guest_path_list) ? '' : implode(">>", $guest_path_list)
                    ];

                    $message_info = [
                        'text' => $text,
                        'language' => $session_info['language'],
                        'mobile_number' => $mobile_number
                    ];

                    $responseWhatsapp = $this->sendMessageToWhatsapp($message_info);
                    if ($responseWhatsapp != false) {
                        $saveInfo['uuid'] = $responseWhatsapp->MessageUUID;
                        $this->saveChatbotHistoryWhatsapp($saveInfo);
                    }

                    if ($nextInfo['is_wrong'] == false) {
                        break;
                    } else {
                        $isRecall = true;

                        usleep(200);
                    }
                }
            }
        }


    }

    /**
     * @param $mobile_number
     * @return array
     */
    private function getInfoFromMobileNumber($mobile_number)
    {
        $other_info = new \stdClass();

        $guest_path_list = [];

        $last24 = date('Y-m-d H:i:s', strtotime(' -1 day'));
        DB::table('services_chat_guest_session as cgs')
            ->where('cgs.updated_at', '<=', $last24)
            ->where('cgs.status', '!=', ENDED)
            ->update(array('cgs.status' => ENDED));

        $session_info = [
            'id' => 0,
            'guest_id' => 0,
            'agent_id' => 0,
            'status' => '0',
            'language' => 'en'
        ];

        date_default_timezone_set(config('app.timezone'));

        $cur_chat_name = '';

        $limit_time = 10;
        $limit_time_info = DB::table('property_setting')
            ->where('settings_key', 'chatbot_limit_time')
            ->first();

        if (!empty($limit_time_info)) {
            $limit_time = $limit_time_info->value;
        }

        $limit_time = date('Y-m-d H:i:s', strtotime("-" . $limit_time . " Hours"));


        $historyInfo = DB::table('services_chat_history as sch')
            ->leftJoin('services_chat_guest_session as scgs', 'scgs.id', '=', 'sch.session_id')
            ->where('sch.type', 0)
            ->where('sch.direction', '!=', '-1')
            ->where('sch.phone_number', $mobile_number)
            ->where('sch.created_at', '>=', $limit_time)
            ->select(DB::raw('sch.*, scgs.status'))
            ->orderBy('sch.id', 'desc')
            ->first();

        if (!empty($historyInfo)) {
            $session_info['language'] = $historyInfo->language;

            if (!empty($historyInfo->guest_path)) {
                $guest_path_list = explode(">>", $historyInfo->guest_path);
            }
        }

        if (!empty($historyInfo->session_id)) {
            $temp_info = DB::table('services_chat_guest_session')
                ->where('id', $historyInfo->session_id)
                ->first();

            if (!empty($temp_info)) {
                $session_info['id'] = $temp_info->id;
                $session_info['agent_id'] = $temp_info->agent_id;
                $session_info['status'] = $temp_info->status;
            }
        }

        if (!empty($historyInfo->cur_chat_name)) {
            $cur_chat_name = $historyInfo->cur_chat_name;
        }

        if (!empty($historyInfo->other_info)) {
            $other_info = json_decode($historyInfo->other_info);
        }

        $guest_info = [
            'guest_id' => 0,
            'property_id' => 4,
            'room_id' => 0,
            'guest_type' => ''
        ];


        // get guest info from phone number

        $guestResult = null;
        if (!empty($historyInfo->guest_id)) {
            $guestResult = DB::table('common_guest as cg')
                ->leftJoin('common_room as cr', 'cr.id', '=', 'cg.room_id')
                ->where('cg.guest_id', $historyInfo->guest_id)
                ->select(['cg.*', 'cr.room'])
                ->first();
        } else {
            $guestResult = DB::table('common_guest as cg')
                ->leftJoin('common_room as cr', 'cr.id', '=', 'cg.room_id')
                ->where('cg.mobile', $mobile_number)
                ->where('cg.checkout_flag', 'checkin')
                ->select(['cg.*', 'cr.room'])
                ->first();
        }

        if (!empty($guestResult)) {
            $guest_info['guest_id'] = $guestResult->guest_id;
            $guest_info['guest_name'] = $guestResult->guest_name;
            $guest_info['property_id'] = $guestResult->property_id;
            $guest_info['room_id'] = $guestResult->room_id;

            if ($guestResult->checkout_flag == 'checkin') {
                $other_info->guest_type = 'In-House';
            } else {
                $other_info->guest_type = 'Check-out';
            }

            $other_info->room = $guestResult->room;
        }

        $ret = [
            'session_info' => $session_info,
            'cur_chat_name' => $cur_chat_name,
            'guest_info' => $guest_info,
            'other_info' => $other_info,
            'guest_path_list' => $guest_path_list
        ];

        return $ret;
    }

    /**
     * @param $reqInfo
     * @param $other_info
     * @param array $guest_path_list
     * @return mixed
     */
    public function getNextChatContentWhatsapp($reqInfo, &$other_info, &$guest_path_list = [])
    {
        $guest_id = $reqInfo['guest_id'];

        $guest_type = isset($other_info->guest_type) ? $other_info->guest_type : '';

        if (empty($guest_id) || str_contains($guest_id, 'external')) {
            if ($guest_type != 'Outside') { // question part
                $ret = $this->getQuestionChatResultWhatsapp($reqInfo, $other_info);
            } else { // Outside part
                $ret = $this->getOutsideChatResultWhatsapp($reqInfo, $other_info, $guest_path_list);
            }
        } else { // in-house part
            $ret = $this->getInHouseChatResultWhatsapp($reqInfo, $other_info, $guest_path_list);
        }

        return $ret;
    }

    /**
     * @param $reqInfo
     * @param $other_info
     * @return mixed
     */
    private function getQuestionChatResultWhatsapp($reqInfo, &$other_info)
    {
        $guest_id = $reqInfo['guest_id'];
        $message_content = $reqInfo['message_content'];
        $prev_chat_name = $reqInfo['prev_chat_name'];
        $property_id = $reqInfo['property_id'];
        $language = $reqInfo['language'];
        $language_name = isset($other_info->language_name) ? $other_info->language_name : 'English';

        $guest_type = isset($other_info->guest_type) ? $other_info->guest_type : '';

        $room = isset($other_info->room) ? $other_info->room : 0;

        $isWrong = false;

        $chat_name = '';


        if (empty($prev_chat_name)) {
            $chat_name = CHAT_QUESTION_FIRST;
            $prev_chat_name = CHAT_QUESTION_FIRST;
        } else if ($prev_chat_name == CHAT_QUESTION_FIRST) {
            if (empty($message_content)) {
                $chat_name = CHAT_QUESTION_FIRST;
                $prev_chat_name = CHAT_QUESTION_FIRST;
            } else if (strtolower($message_content) == 'yes') {
                $chat_name = CHAT_QUESTION_ROOM;
                $prev_chat_name = CHAT_QUESTION_ROOM;
                $guest_type = 'In-House';

            } else if (strtolower($message_content) == 'no') {
                $isWrong = true;

                $chat_name = CHAT_OUTSIDE_WELCOME;
                $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;

                $guest_type = 'Outside';
            } else {
                $chat_name = CHAT_INVALID;
                $prev_chat_name = CHAT_QUESTION_FIRST;
                $isWrong = true;
            }
        } else if ($prev_chat_name == CHAT_QUESTION_ROOM) {
            $room = $message_content;

            if ($this->checkGuestByRoom($room)) {
                $chat_name = CHAT_QUESTION_GUEST_NAME;
                $prev_chat_name = CHAT_QUESTION_GUEST_NAME;
            } else {
                $chat_name = CHAT_QUESTION_FIRST;
                $prev_chat_name = CHAT_QUESTION_FIRST;
                $room = 0;
            }
        } else if ($prev_chat_name == CHAT_QUESTION_GUEST_NAME) {
            $guest_name = $message_content;
            $tempGuestId = $this->getGuestIdFromRoomAndName($room, $guest_name);

            if (!empty($tempGuestId)) {
                $chat_name = CHAT_WELCOME;
                $prev_chat_name = CHAT_IN_HOUSE_SELECT_LANGUAGE;
                $isWrong = true;
                $ret['join_in_house'] = true;
                $guest_id = $tempGuestId;
            } else {
                $chat_name = CHAT_QUESTION_FIRST;
                $prev_chat_name = CHAT_QUESTION_FIRST;
            }
        }

        $chatResult = $this->getChatResult($property_id, $chat_name, 0, 0, $prev_chat_name);

        //        filter
        if (!empty($guest_id)) {

            $guestResult = $this->getGuestInfoFromGuestId($guest_id);

            $chatResult->template = str_replace('{{user_name}}', $guestResult->guest_name, $chatResult->template);
            $chatResult->template = str_replace('{{hotel_name}}', "XXX", $chatResult->template);
        }

        $ret['name'] = $isWrong ? $prev_chat_name : $chatResult->name;
        $ret['message_content'] = $chatResult->template;
        $ret['is_wrong'] = $isWrong;
        $ret['prev_chat_name'] = $prev_chat_name;
        $ret['guest_id'] = $guest_id;
        $ret['language'] = $language;
        $ret['session_id'] = isset($reqInfo['session_id']) ? $reqInfo['session_id'] : 0;

        $other_info->guest_type = $guest_type;
        $other_info->room = $room;
        $other_info->language_name = $language_name;
        return $ret;
    }

    /**
     * @param $room
     * @return bool
     */
    private function checkGuestByRoom($room)
    {
        $results = DB::table('common_guest as cg')
            ->leftJoin('common_room as cr', 'cr.id', '=', 'cg.room_id')
            ->where('cr.room', $room)
            ->get();

        if (!empty($results)) {
            return true;
        }

        return false;
    }

    /**
     * @param $room
     * @param $guest_name
     * @return int
     */
    private function getGuestIdFromRoomAndName($room, $guest_name)
    {
        $guest_info = DB::table('common_guest as cg')
            ->leftJoin('common_room as cr', 'cr.id', '=', 'cg.room_id')
            ->where('cg.guest_name', $guest_name)
            ->where('cr.room', $room)
            ->select(['cg.guest_id'])
            ->first();

        if (!empty($guest_info)) {
            return $guest_info->guest_id;
        }

        return 0;
    }

    /**
     * @param $reqInfo
     * @param $other_info
     * @param array $guest_path_list
     * @return mixed
     */
    private function getOutsideChatResultWhatsapp($reqInfo, &$other_info, &$guest_path_list = [])
    {

        $guest_id = $reqInfo['guest_id'];
        $message_content = $reqInfo['message_content'];
        $prev_chat_name = $reqInfo['prev_chat_name'];
        $property_id = $reqInfo['property_id'];
        $language = $reqInfo['language'];

        $language_name = isset($other_info->language_name) ? $other_info->language_name : 'English';
        $guest_type = isset($other_info->guest_type) ? $other_info->guest_type : '';
        $room = isset($other_info->room) ? $other_info->room : 0;

        $isWrong = false;

        $chat_name = '';

        if (empty($prev_chat_name)) {
            $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
            $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
        } else if ($prev_chat_name == CHAT_AGENT_CALL_WAITING) {
            $other_info->selectedAnswerNumber = 0;

            $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
            $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
        } else if ($prev_chat_name == CHAT_OUTSIDE_FAQ_MAIN_MENU) {
            $other_info->selectedAnswerNumber = 0;

            $this->setFaqMainMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, false, $reqInfo, $other_info, $guest_path_list);

        } else if ($prev_chat_name == CHAT_FAQ_RECEPTION_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqReceptionMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, false, $reqInfo, $guest_path_list);

        } else if ($prev_chat_name == CHAT_FAQ_RECEPTION_ANSWERS) {
            $this->setFaqReceptionAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_SPA_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqSpaMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, false, $reqInfo, $guest_path_list);

        } else if ($prev_chat_name == CHAT_FAQ_SPA_ANSWERS) {
            $this->setFaqSpaAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);

        } else if ($prev_chat_name == CHAT_FAQ_ROOM_RESERVATION_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqReservationMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, false, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_ROOM_RESERVATION_ANSWERS) {
            $this->setFaqRoomReservationAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_RESTAURANT_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqRestaurantMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, false, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_RESTAURANT_ANSWERS) {
            $this->setFaqRestaurantAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_CONCIERGE_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqConciergeMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, false, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_CONCIERGE_ANSWERS) {
            $this->setFaqConciergeAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_HOTEL_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqHotelMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, false, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_HOTEL_ANSWERS) {
            $this->setFaqHotelAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = '';
            $isWrong = true;
        }

        $chatResult = $this->getChatResult($property_id, $chat_name, 0, 0, $prev_chat_name);

        if (!empty($other_info->selectedAnswerNumber)) {


            $this->changeAnswerByNumber($chatResult->template, $other_info->selectedAnswerNumber, $guest_path_list);
        }

        $ret['name'] = $chatResult->name == CHAT_INVALID ? $prev_chat_name : $chatResult->name;
        $ret['message_content'] = $chatResult->template;
        $ret['is_wrong'] = $isWrong;
        $ret['prev_chat_name'] = $prev_chat_name;
        $ret['guest_id'] = $guest_id;
        $ret['language'] = $language;

        $ret['session_id'] = isset($reqInfo['session_id']) ? $reqInfo['session_id'] : 0;

        $other_info->guest_type = $guest_type;
        $other_info->room = $room;
        $other_info->language_name = $language_name;

        return $ret;
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $bInHouse
     * @param $reqInfo
     * @param $other_info
     * @param array $guest_path_list
     */
    private function setFaqMainMenu($message_content, &$chat_name, &$prev_chat_name, &$isWrong, $bInHouse,
                                    &$reqInfo, $other_info, &$guest_path_list = [])
    {

        if ($bInHouse == true) {
            if ($message_content === "00") {
                $chat_name = CHAT_IN_HOUSE_MAIN_MENU;
                $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;

                $guest_path_list = [];

                return;
            }
        }

        if ($message_content === "") {
            if ($bInHouse == true) {
                $chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;

            } else {
                $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
            }

        } else if ($message_content === "0") {
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if ($message_content === '1') { // reception
            $chat_name = CHAT_FAQ_RECEPTION_MENU;
            $prev_chat_name = CHAT_FAQ_RECEPTION_MENU;

            $guest_path_list[] = '1.Reception';
        } else if ($message_content === '2') { // SPA
            $chat_name = CHAT_FAQ_SPA_MENU;
            $prev_chat_name = CHAT_FAQ_SPA_MENU;

            $guest_path_list[] = '2.SPA';

        } else if ($message_content === '3') { // room reservation
            $chat_name = CHAT_FAQ_ROOM_RESERVATION_MENU;
            $prev_chat_name = CHAT_FAQ_ROOM_RESERVATION_ANSWERS;

            $guest_path_list[] = '3.Reservation';
        } else if ($message_content === '4') { // room reservation
            $chat_name = CHAT_FAQ_RESTAURANT_MENU;
            $prev_chat_name = CHAT_FAQ_RESTAURANT_ANSWERS;
            $guest_path_list[] = '4.Restaurants';

        } else if ($message_content === '5') { // Concierge
            $chat_name = CHAT_FAQ_CONCIERGE_MENU;
            $prev_chat_name = CHAT_FAQ_CONCIERGE_ANSWERS;
            $guest_path_list[] = '5.Concierge';

        } else if ($message_content === '6') { // room reservation
            $chat_name = CHAT_FAQ_HOTEL_MENU;
            $prev_chat_name = CHAT_FAQ_HOTEL_ANSWERS;

            $guest_path_list[] = '5.Hotel';
        } else {
            $chat_name = CHAT_INVALID;
            if ($bInHouse == true) {
                $prev_chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;
            } else {
                $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
            }

            $isWrong = true;
        }
    }

    /**
     * @param $chat_name
     * @param $prev_chat_name
     * @param $reqInfo
     * @param $other_info
     * @param array $guest_path_list
     */
    private function setAgentChat(&$chat_name, &$prev_chat_name, &$reqInfo, $other_info, &$guest_path_list = [])
    {

        $session = $this->getCreatedSession($reqInfo['mobile_number'], $reqInfo['property_id'], $other_info->room, $reqInfo['guest_id'],
            $reqInfo['language'], $other_info->guest_type, $guest_path_list);
        if (!empty($session)) {
            // save to database
            $reqInfo['session_id'] = $session->id;

            $callResponse = $this->getCallToAgent($reqInfo['session_id']);

            if ($callResponse['success'] == true) {
                $chat_name = CHAT_AGENT_CALL_WAITING;
                $prev_chat_name = CHAT_AGENT_CALL_WAITING;
            }
        }
    }

    /**
     * @param $mobile_number
     * @param $property_id
     * @param $room
     * @param $guest_id
     * @param $language
     * @param $guest_type
     * @param array $guest_path_list
     * @return mixed
     */
    private function getCreatedSession($mobile_number, $property_id, $room, $guest_id, $language, $guest_type, &$guest_path_list = [])
    {
        date_default_timezone_set(config('app.timezone'));
        $last24 = date('Y-m-d H:i:s', strtotime(' -1 day'));

        DB::table('services_chat_guest_session as cgs')
            ->where('cgs.updated_at', '<=', $last24)
            ->where('cgs.status', '!=', ENDED)
            ->update(array('cgs.status' => ENDED));

        $ret = $this->createChatSession($property_id, $guest_id, $language, $mobile_number, $guest_type, $guest_path_list);
        $new_flag = $ret['new_flag'];
        $session = $ret['session'];

        if ($new_flag == true && $session->status == WAITING)    // create new chat session and waiting
            $this->saveSystemNotification($property_id, $session->id, $room, $session->guest_name);

        return $session;
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $bInHouse
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqReceptionMenu($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                         &$other_info, $bInHouse, &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_RECEPTION_MENU;
            $prev_chat_name = CHAT_FAQ_RECEPTION_MENU;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if (in_array($message_content, ["1", "2", "3", "4", "5", "6", "7", "8", "9"])) {
            $guest_path_list[] = $this->getGuestFindPath($prev_chat_name, $message_content, $reqInfo);
            $other_info->selectedAnswerNumber = intval($message_content);
            $chat_name = CHAT_FAQ_RECEPTION_ANSWERS;
            $prev_chat_name = CHAT_FAQ_RECEPTION_ANSWERS;

        } else if ($message_content === "00") {
            if ($bInHouse == true) {
                $chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;

                array_pop($guest_path_list);
            } else {
                $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                $guest_path_list = [];

            }
        } else if ($message_content === "000") {
            $chat_name = CHAT_FAQ_RECEPTION_ANSWERS;
            $prev_chat_name = CHAT_FAQ_RECEPTION_ANSWERS;

            $guest_path_list[] = 'All';
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_RECEPTION_MENU;

            $isWrong = true;
        }
    }

    /**
     * @param $chat_name
     * @param $number
     * @param $reqInfo
     * @return string
     */
    private function getGuestFindPath($chat_name, $number, $reqInfo)
    {
        $property_id = $reqInfo['property_id'];
        $guest_id = $reqInfo['guest_id'];

        $guestResult = null;

        if (!empty($guest_id)) {
            $guestResult = $this->getGuestInfoFromGuestId($guest_id);
        }

        $room_type_id = 0;
        $vip_id = 0;

        if (!empty($guestResult)) {
            $room_type_id = $guestResult->room_type_id;
            $vip_id = $guestResult->vip_id;
        }

        $chat_result = $this->getChatResult($property_id, $chat_name, $room_type_id, $vip_id, $chat_name);

        $res = 'not found';
        if (!empty($chat_result)) {

            $template = $chat_result->template;
            $tempArr = explode("\n", $template);

            foreach ($tempArr as $tempRow) {
                if (str_contains($tempRow, $number . '. ')) {
                    $res = $tempRow;
                    break;
                }
            }
        }

        return $res;
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqReceptionAnswers($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                            &$other_info, &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_RECEPTION_ANSWERS;
            $prev_chat_name = CHAT_FAQ_RECEPTION_ANSWERS;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if ($message_content === "00") {
            $other_info->selectedAnswerNumber = 0;
            $chat_name = CHAT_FAQ_RECEPTION_MENU;
            $prev_chat_name = CHAT_FAQ_RECEPTION_MENU;

            array_pop($guest_path_list);

        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_RECEPTION_ANSWERS;

            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $bInHouse
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqSpaMenu($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                   &$other_info, $bInHouse, &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_SPA_MENU;
            $prev_chat_name = CHAT_FAQ_SPA_MENU;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if (in_array($message_content, ["1", "2", "3", "4", "5"])) {

            $guest_path_list[] = $this->getGuestFindPath($prev_chat_name, $message_content, $reqInfo);
            $other_info->selectedAnswerNumber = intval($message_content);
            $chat_name = CHAT_FAQ_SPA_ANSWERS;
            $prev_chat_name = CHAT_FAQ_SPA_ANSWERS;

        } else if ($message_content === "00") {
            if ($bInHouse == true) {
                $chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;

                array_pop($guest_path_list);
            } else {
                $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;

                $guest_path_list = [];
            }
        } else if ($message_content === "000") {
            $chat_name = CHAT_FAQ_SPA_ANSWERS;
            $prev_chat_name = CHAT_FAQ_SPA_ANSWERS;

            $guest_path_list[] = 'All';
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_SPA_MENU;

            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqSpaAnswers($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                      &$other_info, &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_SPA_ANSWERS;
            $prev_chat_name = CHAT_FAQ_SPA_ANSWERS;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if ($message_content === "00") {
            $other_info->selectedAnswerNumber = 0;
            $chat_name = CHAT_FAQ_SPA_MENU;
            $prev_chat_name = CHAT_FAQ_SPA_MENU;

            array_pop($guest_path_list);
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_SPA_ANSWERS;

            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $bInHouse
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqReservationMenu($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                           &$other_info, $bInHouse, &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_ROOM_RESERVATION_MENU;
            $prev_chat_name = CHAT_FAQ_ROOM_RESERVATION_MENU;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if (in_array($message_content, ["1", "2", "3", "4", "5", "6", "7", "8"])) {
            $guest_path_list[] = $this->getGuestFindPath($prev_chat_name, $message_content, $reqInfo);
            $other_info->selectedAnswerNumber = intval($message_content);
            $chat_name = CHAT_FAQ_ROOM_RESERVATION_ANSWERS;
            $prev_chat_name = CHAT_FAQ_ROOM_RESERVATION_ANSWERS;

        } else if ($message_content === "00") {
            if ($bInHouse == true) {
                $chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;

                array_pop($guest_path_list);

            } else {
                $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
            }
        } else if ($message_content === "000") {
            $chat_name = CHAT_FAQ_ROOM_RESERVATION_ANSWERS;
            $prev_chat_name = CHAT_FAQ_ROOM_RESERVATION_ANSWERS;

            $guest_path_list[] = 'All';
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_ROOM_RESERVATION_MENU;

            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqRoomReservationAnswers($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                                  &$other_info, &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_ROOM_RESERVATION_ANSWERS;
            $prev_chat_name = CHAT_FAQ_ROOM_RESERVATION_ANSWERS;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if ($message_content === "00") {
            $other_info->selectedAnswerNumber = 0;
            $chat_name = CHAT_FAQ_ROOM_RESERVATION_MENU;
            $prev_chat_name = CHAT_FAQ_ROOM_RESERVATION_MENU;

            array_pop($guest_path_list);
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_ROOM_RESERVATION_ANSWERS;

            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $bInHouse
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqRestaurantMenu($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                          &$other_info, $bInHouse, &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_RESTAURANT_MENU;
            $prev_chat_name = CHAT_FAQ_RESTAURANT_MENU;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if (in_array($message_content, ["1", "2", "3", "4", "5"])) {
            $guest_path_list[] = $this->getGuestFindPath($prev_chat_name, $message_content, $reqInfo);
            $other_info->selectedAnswerNumber = intval($message_content);
            $chat_name = CHAT_FAQ_RESTAURANT_ANSWERS;
            $prev_chat_name = CHAT_FAQ_RESTAURANT_ANSWERS;

        } else if ($message_content === "00") {
            if ($bInHouse == true) {
                $chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;

                array_pop($guest_path_list);
            } else {
                $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
            }
        } else if ($message_content === "000") {
            $chat_name = CHAT_FAQ_RESTAURANT_ANSWERS;
            $prev_chat_name = CHAT_FAQ_RESTAURANT_ANSWERS;
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_RESTAURANT_MENU;

            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqRestaurantAnswers($message_content, &$chat_name, &$prev_chat_name, &$isWrong, &$other_info,
                                             &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_RESTAURANT_ANSWERS;
            $prev_chat_name = CHAT_FAQ_RESTAURANT_ANSWERS;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if ($message_content === "00") {
            $other_info->selectedAnswerNumber = 0;
            $chat_name = CHAT_FAQ_RESTAURANT_MENU;
            $prev_chat_name = CHAT_FAQ_RESTAURANT_MENU;
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_RESTAURANT_ANSWERS;

            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $bInHouse
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqConciergeMenu($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                         &$other_info, $bInHouse, &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_CONCIERGE_MENU;
            $prev_chat_name = CHAT_FAQ_CONCIERGE_MENU;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if (in_array($message_content, ["1", "2", "3", "4", "5"])) {
            $guest_path_list[] = $this->getGuestFindPath($prev_chat_name, $message_content, $reqInfo);

            $other_info->selectedAnswerNumber = intval($message_content);
            $chat_name = CHAT_FAQ_CONCIERGE_ANSWERS;
            $prev_chat_name = CHAT_FAQ_CONCIERGE_ANSWERS;

        } else if ($message_content === "00") {
            if ($bInHouse == true) {
                $chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;

                array_pop($guest_path_list);
            } else {
                $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
            }
        } else if ($message_content === "000") {
            $chat_name = CHAT_FAQ_CONCIERGE_ANSWERS;
            $prev_chat_name = CHAT_FAQ_CONCIERGE_ANSWERS;

            $guest_path_list[] = 'All';
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_CONCIERGE_MENU;

            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqConciergeAnswers($message_content, &$chat_name, &$prev_chat_name, &$isWrong, &$other_info,
                                            &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_CONCIERGE_ANSWERS;
            $prev_chat_name = CHAT_FAQ_CONCIERGE_ANSWERS;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if ($message_content === "00") {
            $other_info->selectedAnswerNumber = 0;
            $chat_name = CHAT_FAQ_CONCIERGE_MENU;
            $prev_chat_name = CHAT_FAQ_CONCIERGE_MENU;

            array_pop($guest_path_list);
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_CONCIERGE_ANSWERS;

            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $bInHouse
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqHotelMenu($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                     &$other_info, $bInHouse, &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_HOTEL_MENU;
            $prev_chat_name = CHAT_FAQ_HOTEL_MENU;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if (in_array($message_content, ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10"])) {
            $guest_path_list[] = $this->getGuestFindPath($prev_chat_name, $message_content, $reqInfo);
            $other_info->selectedAnswerNumber = intval($message_content);
            $chat_name = CHAT_FAQ_HOTEL_ANSWERS;
            $prev_chat_name = CHAT_FAQ_HOTEL_ANSWERS;

        } else if ($message_content === "00") {
            if ($bInHouse == true) {
                $chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;

                array_pop($guest_path_list);
            } else {
                $chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
                $prev_chat_name = CHAT_OUTSIDE_FAQ_MAIN_MENU;
            }

        } else if ($message_content === "000") {
            $chat_name = CHAT_FAQ_HOTEL_ANSWERS;
            $prev_chat_name = CHAT_FAQ_HOTEL_ANSWERS;

            $guest_path_list[] = 'All';
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_HOTEL_MENU;

            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $reqInfo
     * @param array $guest_path_list
     */
    private function setFaqHotelAnswers($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                        &$other_info, &$reqInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_FAQ_HOTEL_ANSWERS;
            $prev_chat_name = CHAT_FAQ_HOTEL_ANSWERS;
        } else if ($message_content === "0") { // chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if ($message_content === "00") {
            $other_info->selectedAnswerNumber = 0;
            $chat_name = CHAT_FAQ_HOTEL_MENU;
            $prev_chat_name = CHAT_FAQ_HOTEL_MENU;

            array_pop($guest_path_list);

        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_FAQ_HOTEL_ANSWERS;

            $isWrong = true;
        }
    }

    /**
     * @param $template
     * @param $number
     * @param array $guest_path_list
     */
    private function changeAnswerByNumber(&$template, $number, &$guest_path_list = [])
    {

        $tempArr = explode("\n\n", $template);

        if (count($tempArr) > 1) {
            $res = '';

            $tempFirst = $tempArr[0];
            $tempSecond = $tempArr[1];

            $tempArr = explode("\n", $tempFirst);

            foreach ($tempArr as $tempRow) {
                if (str_contains($tempRow, $number . '. ')) {
                    $res .= $tempRow . "\n";
                }
            }

            $res .= "\n" . $tempSecond;

            $template = $res;
        }

    }

    /**
     * @param $reqInfo
     * @param $other_info
     * @param array $guest_path_list
     * @return mixed
     */
    private function getInHouseChatResultWhatsapp($reqInfo, &$other_info, &$guest_path_list = [])
    {

        $guest_id = $reqInfo['guest_id'];

        $message_content = $reqInfo['message_content'];

        $prev_chat_name = $reqInfo['prev_chat_name'];
        $language = $reqInfo['language'];
        $guest_name = $reqInfo['guest_name'];

        $guest_type = isset($other_info->guest_type) ? $other_info->guest_type : '';
        $room = isset($other_info->room) ? $other_info->room : 0;
        $language_name = isset($other_info->language_name) ? $other_info->language_name : 'English';

        $chat_name = '';

        $isWrong = false;

        $guestResult = $this->getGuestInfoFromGuestId($guest_id);

        $createTaskResultInfo = [
            'message' => '',
            'success' => true
        ];

        $lang_selected = false;

        if (empty($prev_chat_name)) {
            $chat_name = CHAT_WELCOME;
            $prev_chat_name = CHAT_IN_HOUSE_SELECT_LANGUAGE;
            $isWrong = true;
        } else if ($prev_chat_name == CHAT_IN_HOUSE_SELECT_LANGUAGE) {

            $this->setInHouseSelectLanguage($message_content, $chat_name, $prev_chat_name,
                $language_name, $lang_selected, $isWrong);

        } else if ($prev_chat_name == CHAT_IN_HOUSE_LANGUAGE_SELECTED) {

            $this->setInHouseLanguageSelected($message_content, $chat_name, $prev_chat_name,
                $language_name, $language, $isWrong, $lang_selected);

        } else if ($prev_chat_name == CHAT_IN_HOUSE_MAIN_MENU) {

            $this->setInHouseMainMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info->bInfoList, $guest_path_list);

        } else if ($prev_chat_name == CHAT_IN_HOUSE_FEEDBACK_MAIN_MENU) {

            $this->setInHouseFeedbackMainMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $reqInfo, $other_info, $guest_path_list);

        } else if ($prev_chat_name == CHAT_IN_HOUSE_FAQ_MAIN_MENU) {

            $this->setFaqMainMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, true, $reqInfo, $other_info, $guest_path_list);

        } else if ($prev_chat_name == CHAT_FAQ_RECEPTION_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqReceptionMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, true, $reqInfo, $guest_path_list);

        } else if ($prev_chat_name == CHAT_FAQ_RECEPTION_ANSWERS) {
            $this->setFaqReceptionAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_SPA_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqSpaMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, true, $reqInfo, $guest_path_list);

        } else if ($prev_chat_name == CHAT_FAQ_SPA_ANSWERS) {
            $this->setFaqSpaAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);

        } else if ($prev_chat_name == CHAT_FAQ_ROOM_RESERVATION_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqReservationMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, true, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_ROOM_RESERVATION_ANSWERS) {
            $this->setFaqRoomReservationAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_RESTAURANT_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqRestaurantMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, true, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_RESTAURANT_ANSWERS) {
            $this->setFaqRestaurantAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $other_info, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_CONCIERGE_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqConciergeMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, true, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_CONCIERGE_ANSWERS) {
            $this->setFaqConciergeAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_HOTEL_MENU) {
            $other_info->selectedAnswerNumber = 0;
            $this->setFaqHotelMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, true, $reqInfo, $guest_path_list);
        } else if ($prev_chat_name == CHAT_FAQ_HOTEL_ANSWERS) {

            $this->setFaqHotelAnswers($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $guest_path_list);

        } else if ($prev_chat_name == CHAT_IN_HOUSE_REQUEST_MENU) {
            $other_info->bInfoList = false;

            $this->setInHouseRequestMenu($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $createTaskResultInfo, $guest_path_list);

        } else if ($prev_chat_name == CHAT_IN_HOUSE_REQUEST_QUANTITY) {
            $this->setInHouseRequestQuantity($message_content, $chat_name, $prev_chat_name,
                $isWrong, $other_info, $reqInfo, $createTaskResultInfo, $guest_path_list);

        } else if ($prev_chat_name == CHAT_AGENT_CALL_WAITING) {
            $chat_name = CHAT_IN_HOUSE_MAIN_MENU;
            $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;
        }

        $property_id = $guestResult->property_id;

        $chatResult = $this->getChatResult($property_id, $chat_name, $guestResult->room_type_id, $guestResult->vip_id, $prev_chat_name);

        if (!empty($other_info->selectedAnswerNumber)) {
            $this->changeAnswerByNumber($chatResult->template, $other_info->selectedAnswerNumber, $guest_path_list);
        }

        if (empty($chatResult)) {
            $ret['success'] = false;
            return $ret;
        }

        //        filter

        if (isset($other_info->bInfoList) && $other_info->bInfoList == true) {
            $templateInfo = $this->getQuickTaskTemplateInfo($chatResult->template);
            $chatResult->template = $templateInfo['template'];
            $other_info->info_list = $templateInfo['info_list'];
        }

        $ret['name'] = $isWrong ? $prev_chat_name : $chatResult->name;

        $ret['message_content'] = $chatResult->template;

        if ($lang_selected == true) {
            $ret['message_content'] = str_replace('{{lang_label}}', $language_name, $ret['message_content']);
        }

        if ($createTaskResultInfo['success'] == false) {
            $ret['message_content'] = str_replace('{{error}}', $createTaskResultInfo['message'], $ret['message_content']);
        }

        if (!empty($other_info->taskName)) {
            $ret['message_content'] = str_replace('{{task_name}}',
                isset($other_info->taskName) ? $other_info->taskName : '', $chatResult->template);
        }

        if ($chat_name == CHAT_WELCOME) {
            $ret['message_content'] = str_replace('{{user_name}}', $guest_name, $ret['message_content']);
        }

        $ret['is_wrong'] = $isWrong;
        $ret['prev_chat_name'] = $ret['is_wrong'] ? $prev_chat_name : $chatResult->name;

        $ret['guest_id'] = $guest_id;
        $ret['guest_name'] = $guest_name;
        $ret['language'] = $language;

        $ret['session_id'] = isset($reqInfo['session_id']) ? $reqInfo['session_id'] : 0;

        $other_info->guest_type = $guest_type;
        $other_info->room = $room;
        $other_info->language_name = $language_name;

        return $ret;
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $language_name
     * @param $lang_selected
     * @param $isWrong
     */
    private function setInHouseSelectLanguage($message_content, &$chat_name, &$prev_chat_name, &$language_name, &$lang_selected, &$isWrong)
    {
        if ($message_content === "") {
            $chat_name = CHAT_IN_HOUSE_SELECT_LANGUAGE;
            $prev_chat_name = CHAT_IN_HOUSE_SELECT_LANGUAGE;
        } else if ($message_content === '1') {
            $language_name = 'Arabic';

            $lang_selected = true;
            $chat_name = CHAT_IN_HOUSE_LANGUAGE_SELECTED;
            $prev_chat_name = CHAT_IN_HOUSE_LANGUAGE_SELECTED;
        } else if ($message_content === '2') {
            $language_name = 'English';

            $lang_selected = true;
            $chat_name = CHAT_IN_HOUSE_LANGUAGE_SELECTED;
            $prev_chat_name = CHAT_IN_HOUSE_LANGUAGE_SELECTED;
        } else if ($message_content === '3') {
            $language_name = 'Chinese';
            $lang_selected = true;
            $chat_name = CHAT_IN_HOUSE_LANGUAGE_SELECTED;
            $prev_chat_name = CHAT_IN_HOUSE_LANGUAGE_SELECTED;
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_IN_HOUSE_SELECT_LANGUAGE;
            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $language_name
     * @param $language
     * @param $isWrong
     * @param $lang_selected
     */
    private function setInHouseLanguageSelected($message_content, &$chat_name, &$prev_chat_name,
                                                &$language_name, &$language, &$isWrong, &$lang_selected)
    {
        if ($message_content == "") {
            $lang_selected = true;
            $chat_name = CHAT_IN_HOUSE_LANGUAGE_SELECTED;
            $prev_chat_name = CHAT_IN_HOUSE_LANGUAGE_SELECTED;
        } else if ($message_content == '1') {
            if ($language_name == 'Arabic') {
                $language = 'ar';
            } else if ($language_name == 'English') {
                $language = 'en';
            } else if ($language_name == 'Chinese') {
                $language = 'cn';
            }
            $isWrong = true;
            $chat_name = CHAT_IN_HOUSE_WELCOME;
            $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;
        } else if ($message_content == '0') {
            $language = 'en';
            $language_name = 'English';

            $chat_name = CHAT_IN_HOUSE_SELECT_LANGUAGE;
            $prev_chat_name = CHAT_IN_HOUSE_SELECT_LANGUAGE;
        } else {
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_IN_HOUSE_LANGUAGE_SELECTED;
            $isWrong = true;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $bInfoList
     * @param array $guest_path_list
     */
    private function setInHouseMainMenu($message_content, &$chat_name, &$prev_chat_name,
                                        &$isWrong, &$bInfoList, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $bInfoList = false;

            $chat_name = CHAT_IN_HOUSE_MAIN_MENU;
            $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;

            $guest_path_list = [];
        } else if ($message_content === '1') { // create request
            $bInfoList = true;

            $chat_name = CHAT_IN_HOUSE_REQUEST_MENU;
            $prev_chat_name = CHAT_IN_HOUSE_REQUEST_MENU;

            $guest_path_list = ['1.Request'];
        } else if ($message_content === '2') { // feedback
            $bInfoList = false;

            $chat_name = CHAT_IN_HOUSE_FEEDBACK_MAIN_MENU;
            $prev_chat_name = CHAT_IN_HOUSE_FEEDBACK_MAIN_MENU;

            $guest_path_list = ['2.Feedback'];
        } else if ($message_content === '3') { // faqs
            $bInfoList = false;

            $chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;
            $prev_chat_name = CHAT_IN_HOUSE_FAQ_MAIN_MENU;

            $guest_path_list = ['3.Faq'];
        } else {
            $isWrong = true;

            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $reqInfo
     * @param $other_info
     * @param array $guest_path_list
     */
    private function setInHouseFeedbackMainMenu($message_content, &$chat_name, &$prev_chat_name,
                                                &$isWrong, &$reqInfo, $other_info, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_IN_HOUSE_FEEDBACK_MAIN_MENU;
            $prev_chat_name = CHAT_IN_HOUSE_FEEDBACK_MAIN_MENU;
        } else if ($message_content === "0") { // create chat with agent
            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);
        } else if ($message_content === "00") { // go to the main menu
            $chat_name = CHAT_IN_HOUSE_MAIN_MENU;
            $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;

            $guest_path_list = [];
        } else {
            $isWrong = true;
            $chat_name = CHAT_INVALID;
            $prev_chat_name = CHAT_IN_HOUSE_FEEDBACK_MAIN_MENU;
        }
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $reqInfo
     * @param $createTaskResultInfo
     * @param array $guest_path_list
     */
    private function setInHouseRequestMenu($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                           &$other_info, &$reqInfo, &$createTaskResultInfo, &$guest_path_list = [])
    {
        if ($message_content === "") {
            $chat_name = CHAT_IN_HOUSE_REQUEST_MENU;
            $prev_chat_name = CHAT_IN_HOUSE_REQUEST_MENU;
        } else if ($message_content === '0') { // chat with agent

            $this->setAgentChat($chat_name, $prev_chat_name, $reqInfo, $other_info, $guest_path_list);

        } else if ($message_content === '00') {
            $chat_name = CHAT_IN_HOUSE_MAIN_MENU;
            $chat_name = CHAT_IN_HOUSE_MAIN_MENU;
            $other_info->taskName = '';
            $other_info->task_id = 0;

            $guest_path_list = [];
        } else {
            $propertyName = "$message_content";
            if (!empty($other_info->info_list->$propertyName)) {
                $itemInfo = $other_info->info_list->$propertyName;

                if (isset($itemInfo->quantity)) {
                    $chat_name = CHAT_IN_HOUSE_REQUEST_QUANTITY;
                    $prev_chat_name = CHAT_IN_HOUSE_REQUEST_QUANTITY;

                    $other_info->taskName = isset($itemInfo->name) ? $itemInfo->name : '';
                    $other_info->task_id = isset($itemInfo->id) ? $itemInfo->id : 0;
                    $other_info->bInfoList = false;
                } else {
                    // create task item

                    $res = $this->createQuickTask($reqInfo, $other_info, 1, $createTaskResultInfo);

                    if ($res == true) {
                        // if success
                        $chat_name = CHAT_IN_HOUSE_REQUEST_SUCCESS;
                        $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;
                    } else {
                        // failed
                        $chat_name = CHAT_IN_HOUSE_REQUEST_FAILED;
                        $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;
                    }

                    $other_info->bInfoList = true;
                    $other_info->taskName = '';
                    $other_info->task_id = 0;

                    $isWrong = true;
                }
            } else {
                $chat_name = CHAT_INVALID;
                $prev_chat_name = CHAT_IN_HOUSE_REQUEST_MENU;
                $isWrong = true;
            }
        }
    }

    /**
     * @param $reqInfo
     * @param $other_info
     * @param $quantity
     * @param $createTaskResultInfo
     * @return bool
     */
    private function createQuickTask($reqInfo, $other_info, $quantity, &$createTaskResultInfo)
    {
        $property_id = isset($reqInfo['property_id']) ? $reqInfo['property_id'] : 0;
        $task_id = isset($other_info->task_id) ? $other_info->task_id : 0;
        $guest_id = isset($reqInfo['guest_id']) ? $reqInfo['guest_id'] : 0;
        $room_id = $reqInfo['room_id'] ? $reqInfo['room_id'] : 0;


        $result = $this->getCreateTaskResult($guest_id, $task_id, $room_id);

        if (empty($result)) {
            return false;
        }

        $task_info = $result['task_info'];
        $location_id = $result['location_id'];

        if (empty($task_info['department'])) {
            return false;
        }

        $priorityList = DB::table('services_priority as pr')
            ->select(DB::raw('pr.*'))
            ->get();


        $quickTaskData['property_id'] = $property_id;
        $quickTaskData['dept_func'] = $task_info['deptfunc']['id'];
        $quickTaskData['department_id'] = $task_info['department']['id'];
        $quickTaskData['type'] = 1;
        $quickTaskData['priority'] = empty($priorityList) ? 0 : $priorityList[0]->id;

        $time = date('Y-m-d H:i:s');
        $quickTaskData['start_date_time'] = $time;
        $quickTaskData['created_time'] = $time;
        $quickTaskData['end_date_time'] = '0000-00-00 00:00:00';
        $quickTaskData['dispatcher'] = $task_info['prioritylist'][0]['id'];
        $quickTaskData['feedback_flag'] = false;
        $quickTaskData['attendant'] = 0;
        $quickTaskData['room'] = $room_id;
        $quickTaskData['task_list'] = $task_id;
        $quickTaskData['max_time'] = $task_info['taskgroup']['max_time'];
        $quickTaskData['quantity'] = $quantity;
        $quickTaskData['custom_message'] = '';
        $quickTaskData['status_id'] = 1;
        $quickTaskData['guest_id'] = $guest_id;
        $quickTaskData['location_id'] = $location_id;

        $headers = [
            'Content-Type: application/json'
        ];

        $taskList[] = $quickTaskData;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://mytestsite.com/createtasklist');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($taskList));

        $server_response = curl_exec($ch);

        curl_close($ch);

        $response = json_decode($server_response);

        if ($response == false) {
            return false;
        } else {

            if (count($response->invalid_task_list) == 0) {
                return true;
            } else {
                foreach ($response->invalid_task_list as $list) {
                    $createTaskResultInfo['success'] = false;
                    $createTaskResultInfo['message'] .= $list->message . "\n";
                }

                return false;
            }
        }
    }

    /**
     * @param $guest_id
     * @param $task_id
     * @param $room_id
     * @return array
     */
    private function getCreateTaskResult($guest_id, $task_id, $room_id)
    {

        $ret = [
        ];

        if (empty($guest_id) || empty($task_id) || empty($room_id)) {
            return $ret;
        }


        $locationGroupInfo = $this->getLocationGroupIDFromRoom($room_id);

        if (empty($locationGroupInfo)) {
            return $ret;
        }

        $taskInfo = $this->getTaskShiftInfo($task_id, $locationGroupInfo->id);

        if (empty($taskInfo)) {
            return $ret;
        }

        $ret['task_info'] = $taskInfo;
        $ret['location_id'] = $locationGroupInfo->id;

        return $ret;
    }

    /**
     * @param $message_content
     * @param $chat_name
     * @param $prev_chat_name
     * @param $isWrong
     * @param $other_info
     * @param $reqInfo
     * @param $createTaskResultInfo
     * @param array $guest_path_list
     */
    private function setInHouseRequestQuantity($message_content, &$chat_name, &$prev_chat_name, &$isWrong,
                                               &$other_info, &$reqInfo, &$createTaskResultInfo, &$guest_path_list = [])
    {
        if ($message_content === '0') {
            $chat_name = CHAT_IN_HOUSE_REQUEST_MENU;
            $prev_chat_name = CHAT_IN_HOUSE_REQUEST_MENU;
            $other_info->bInfoList = true;

            $guest_path_list = ['1.Request'];
        } else if ($message_content === "") {
            $chat_name = CHAT_IN_HOUSE_REQUEST_QUANTITY;
            $prev_chat_name = CHAT_IN_HOUSE_REQUEST_QUANTITY;
            $other_info->bInfoList = false;

        } else if (is_numeric($message_content)) {
            $quantity = intval($message_content);
            // create task
            $res = $this->createQuickTask($reqInfo, $other_info, $quantity, $createTaskResultInfo);

            if ($res == true) {
                $chat_name = CHAT_IN_HOUSE_REQUEST_SUCCESS;
                $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;
            } else {
                $chat_name = CHAT_IN_HOUSE_REQUEST_FAILED;
                $prev_chat_name = CHAT_IN_HOUSE_MAIN_MENU;
            }
            // if success

            $other_info->bInfoList = true;
            $other_info->taskName = '';
            $other_info->task_id = 0;

            $isWrong = true;
        } else {
            $chat_name = CHAT_IN_HOUSE_REQUEST_INVALID;
            $prev_chat_name = CHAT_IN_HOUSE_REQUEST_QUANTITY;
            $other_info->bInfoList = false;
            $isWrong = true;
        }
    }

    private function getQuickTaskTemplateInfo($curTemplate)
    {

        $result = [
            'template' => '',
            'info_list' => []
        ];

        if (empty($curTemplate)) {
            $result['template'] = '';
            return $result;
        }

        $realTemplate = "";
        $tempArr = explode("\n", $curTemplate);

        $realIndex = 0;

        //        structure : key, name, id, quantity
        foreach ($tempArr as $index => $tempItem) {

            if ($index > 0) {
                $realTemplate .= "\n";
            }

            $secondTempArr = explode('_', $tempItem);

            if (count($secondTempArr) < 2) {
                $realTemplate .= trim($secondTempArr[0]);
            } else { // only key, name and id

                $key = trim($secondTempArr[0]);

                $realIndex++;
                $temp = trim($secondTempArr[1]);
                $tempInfoItem = json_decode($temp);

                $name = isset($tempInfoItem->name) ? $tempInfoItem->name : '';

                $realTemplate .= $key . '. ' . $name;
                $result['info_list'][strval($realIndex)] = $tempInfoItem;
            }
        }

        $result['template'] = $realTemplate;
        return $result;
    }
}
