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
use App\Models\Service\AgentAgentChat;
use App\Models\Service\AgentChatGroup;
use App\Models\Service\AgentChatGroupMembers;
use App\Models\Service\AgentGroupChatHistory;

use App\Modules\Functions;
use DB;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Redis;
use Response;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Event;
use App\Events\WhatsappProcessed;
use Illuminate\Support\Facades\Log;

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



class GuestChatController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function guestChatRegister(Request $request)
    {
        $guest_id = $request->get('guest_id', 0);
        $guest_name = $request->get('guest_name', 0);

        $guest = DB::table('services_chat_guest_connected')
            ->where('guest_id', $guest_id)
            ->first();

        if(empty($guest)){
            DB::table('services_chat_guest_connected')->insert(
                ['guest_id' => $guest_id,'guest_name' => $guest_name]
            );
        }else{
            DB::table('services_chat_guest_connected')
                ->where('guest_id', $guest_id)
                ->update(['guest_name' => $guest_name]);
        }

        $this->createSession($guest_id, $guest_name);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function guestChatUnregister(Request $request)
    {
        $guest_id = $request->get('guest_id', 0);
        $guest_name = $request->get('guest_name', 0);

        $guest = DB::table('services_chat_guest_connected')
            ->where('guest_id', $guest_id)
            ->first();

        if(!empty($guest)){
            DB::table('services_chat_guest_connected')
                ->where('guest_id', $guest_id)
                ->delete();
        }

        $session_data = DB::table('services_chat_guest_session')
             ->where('mobile_number', $guest_id)
             ->orderBy('id','desc')
             ->first();

        if(empty($session_data)){
        return;
        }

        $chat_guest_history = DB::table('services_chat_history')
            ->where('mobile_number', $guest_id)
            ->orderBy('id','desc')
             ->first();

        $this->endAgentChat($session_data->id, $session_data->mobile_number, $session_data->property_id, $chat_guest_history);

    }

    public function guestChatRecieveMsg(Request $request)
    {
        $guest_id = $request->get('guest_id', 0);
        $room_no = $request->get('room_number', 0);
        $guest_name = $request->get('guest_name', 0);
        $data = $request->get('data', 0);

        $guest = DB::table('services_chat_guest_connected')
            ->where('guest_id', $guest_id)
            ->first();

        $guest_details = DB::table('common_guest')
            ->where('guest_id', $guest_id)
            ->first();

        // if(empty($guest)){
        //     DB::table('services_chat_guest_connected')->insert(
        //         ['room_no' => $room_no,'guest_name' => $guest_name]
        //     );
        // }

        $room = DB::table('common_room')
            ->where('room', $room_no)
            ->first();

        $session_data = DB::table('services_chat_guest_session')
            ->where('room_id', $room->id)
            ->orderBy('id','desc')
            ->first();

        $message_content = $data['msg'];

        $chat_guest_history = DB::table('services_chat_history')
            ->where('session_id', $session_data->id)
            ->orderBy('id','desc')
            ->first();

        if( empty($chat_guest_history)){
            $session_data->id = '0';
            $session_data->agent = '0';
            $this->scSaveAgentMessage($guest->guest_id, $message_content, $guest_details->property_id, $session_data, 'text', '');
            return;
        }
        
        $this->scSendChatToAgent($session_data->id, $message_content, $chat_guest_history, 'text', '');

    }

    public function createSession($guest_id, $guest_name){

        //data from guest app
        //$guest_id = $room_no;

        $guest = DB::table('common_guest as cg')
            ->leftJoin('common_room as cr', 'cr.id', '=', 'cg.room_id')
            ->where('cg.guest_id', $guest_id)
            ->select(DB::raw('cg.guest_id as guest_id, cr.room as room, cr.id as room_id, cg.property_id as property_id, cg.guest_name as guest_name'))
            ->first();
        if(empty($guest)){
            return;
        }
        $property_id = $guest->property_id;

        $session = new GuestChatSession();

        if( empty($guest)){
            return "INVALID GUEST";
        }

        $session->guest_id = 0;//$guest->guest_id;
        $session->agent_id = 0;
        $session->skill_id = 0;

        $session->socket_app = 1;

        $session->guest_type = 'Guestapp';
        $session->mobile_number = $guest->guest_id;
        $session->language = 'en';
        $session->guest_name = $guest->guest_name;
        $session->property_id = $guest->property_id;

        $session->guest_path = 'Guest APP';

        //if (!empty($guest_info)) {
            $session->room_id = $guest->room_id;
            $session->status = WAITING;
            $session->transfer_id = 0;
            $session->start_time = '';
        //}

        $session->save();

        DB::table('services_chat_history')
                ->where('guest_id', $guest_id)
                ->update(['session_id' => $session->id]);

        $this->getCallToAgent($session->id);
    }

    public function scSendChatToAgent($session_id, $message_content, $chat_guest_history, $chat_type = "text", $attach_location){

        $session_info = DB::table('services_chat_guest_session as cgs')
            ->leftJoin('common_users as cu', 'cu.id', '=', 'cgs.agent_id')
            ->where('cgs.id', $session_id)
            ->select(DB::raw('cgs.start_time, cgs.mobile_number, cgs.guest_name, 
                SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(cgs.end_time, cgs.start_time))) as chat_duration,
                SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(cgs.start_time, cgs.created_at))) as wait_time,
            CONCAT(cu.first_name, " ", cu.last_name) as agent_name'))
            ->first();

        $session = DB::table('services_chat_guest_session')
            ->where('id',$session_id)->first();

       $this->scSaveAgentMessage($chat_guest_history->mobile_number, $message_content, $chat_guest_history->property_id, $session, $chat_type, $attach_location);
        
       $agents = DB::table('common_chat_skill_mapping')
            ->where('skill_id', $session->skill_id)
            ->get();

        $message = [];
        $message['session_id'] = $session->id;
        //$message['guest_name'] = isset($guest_info['guest_name']) ? $guest_info['guest_name'] : '';
        $guest_info['guest_name'] = '';
        $message['guest_id'] = '0';
        $message['agent_id'] = $session->agent_id;
        $message['property_id'] = $session->property_id;
        //$message['room'] = isset($other_info->room) ? $other_info->room : 0;
        $message['text'] = $message_content;
        $message['created_at'] = date('Y-m-d H:i:s');

        $message['chat_type'] = $chat_type;
        $message['attachment'] = $attach_location;

        $message['direction'] = "1";
        $message['language'] = 'en';

        $msgInfo = [];
        $msgInfo['type'] = 'chat_event';
        $msgInfo['sub_type'] = 'guest_message';
        $msgInfo['data'] = $message;
        $msgInfo['agents'] = $agents;

        Redis::publish('notify', json_encode($msgInfo));
        
    }

    public function scSaveAgentMessage($mobile_number, $responseText, 
                    $property_id, $session, $chat_type, $attach_location)
    {   //sendToMetaWhatsapp 
        // print_r($session);
        // die();
        $saveInfo = [
            'property_id' => $property_id,
            'mobile_number' => $mobile_number,
            'session_id' => $session->id,
            'guest_id' => '0',
            'agent_id' => $session->agent_id,
            'text' => $responseText,
            'text_trans' => '',
            'direction' => '0',
            'type' => 'agent',
            'language' => 'en',
            'chat_type' => $chat_type,
            'attachment' => $attach_location,
            'other_info' => '',
            'cur_chat_name' => '',
            'cur_chat_id' => '0',
            'uuid' => '',
            'guest_path' => '',
            'status' => '0'
        ];
        $this->scSaveToChatHistory($saveInfo);
    }

    public function scSaveToChatHistory($saveInfo){
        //default save validation
        $saveInfo['property_id'] = $saveInfo['property_id'] == '' ? '4' : $saveInfo['property_id'];
        $saveInfo['mobile_number'] = $saveInfo['mobile_number'] == '' ? '' : $saveInfo['mobile_number'];
        $saveInfo['session_id'] = $saveInfo['session_id'] == '' ? '' : $saveInfo['session_id'];
        $saveInfo['guest_id'] = $saveInfo['guest_id'] == '' ? '0' : $saveInfo['guest_id'];
        $saveInfo['agent_id'] = $saveInfo['agent_id'] == '' ? '0' : $saveInfo['agent_id'];
        $saveInfo['text'] = $saveInfo['text'] == '' ? '' : $saveInfo['text'];
        $saveInfo['text_trans'] = $saveInfo['text_trans'] == '' ? '' : $saveInfo['text_trans'];
        $saveInfo['type'] = $saveInfo['type'] == 'guest' ? '0' : '1';
        $saveInfo['language'] = $saveInfo['language'] == '' ? 'en' : $saveInfo['language'];
        $saveInfo['chat_type'] = $saveInfo['chat_type'] == '' ? 'text' : $saveInfo['chat_type'];
        $saveInfo['attachment'] = $saveInfo['attachment'] == '' ? '' : $saveInfo['attachment'];
        $saveInfo['other_info'] = $saveInfo['other_info'] == '' ? '' : $saveInfo['other_info'];
        $saveInfo['cur_chat_name'] = $saveInfo['cur_chat_name'] == '' ? '' : $saveInfo['cur_chat_name'];
        $saveInfo['cur_chat_id'] = $saveInfo['cur_chat_id'] == '' ? '' : $saveInfo['cur_chat_id'];
        $saveInfo['uuid'] = $saveInfo['uuid'] == '' ? '' : $saveInfo['uuid'];
        $saveInfo['guest_path'] = $saveInfo['guest_path'] == '' ? '' : $saveInfo['guest_path'];

        DB::table('services_chat_history')->insert($saveInfo);
        
    }

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

    public function endAgentChat($session_id, $mobile_number, $property_id, $chat_guest_history){
        $session = GuestChatSession::find($session_id);
        
        //$this->scSendMessage($mobile_number, "Session Ended", 'text');
        //$this->scSaveAgentEndMessage($mobile_number, "Session Ended", $property_id, $chat_guest_history);

        $session = $this->endChatSession($session);

        // send notify to all agents
        $message = array();
        $message['type'] = 'chat_event';
        $message['sub_type'] = 'end_chat';
        $message['data'] = $session;

        Redis::publish('notify', json_encode($message));
    }

    public function endChatSession($session)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        if (empty($session)) {
            return array();
        }

        $session->status = ENDED;
        $session->end_time = $cur_time;
        $session->start_time = null;
        $session->end_time = null;

        $session->save();

        $this->saveChatEvent($session, 2);    // end chat


        return $session;
    }

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

}
