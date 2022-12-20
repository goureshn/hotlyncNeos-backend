<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Response;

define("WAITING", 1);
define("ACTIVE", 2);
define("ENDED", 3);
define("TRANSFER", 4);

define("COMPLETED", 0);
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
}
