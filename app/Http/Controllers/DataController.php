<?php

namespace App\Http\Controllers;

use App\Models\Service\AgentChatHistory;
use Illuminate\Http\Request;
use Response;

define("CLEANING_NOT_ASSIGNED", 100);
define("CLEANING_PENDING", 0);
define("CLEANING_RUNNING", 1);
define("CLEANING_DONE", 2);
define("CLEANING_DND", 3);
define("CLEANING_REFUSE", 4);
define("CLEANING_POSTPONE", 5);
define("CLEANING_PAUSE", 6);
define("CLEANING_COMPLETE", 7);
define("CLEANING_DECLINE", 8);
define("CLEANING_OUT_OF_ORDER", 9);
define("CLEANING_OUT_OF_SERVICE", 10);


define("CLEANING_PENDING_NAME", 'Pending');
define("CLEANING_RUNNING_NAME", 'Cleaning');
define("CLEANING_DONE_NAME", 'Done');
define("CLEANING_DND_NAME", 'DND (Do not Disturb)');
define("CLEANING_DECLINE_NAME", 'Reject');
define("CLEANING_POSTPONE_NAME", 'Delay');
define("CLEANING_PAUSE_NAME", 'Pause');
define("CLEANING_COMPLETE_NAME", 'Inspected');

class DataController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getChatUnreadCount(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $chat_notify_cnt = AgentChatHistory::getTotalUnreadCount($user_id);
        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = array(
            'chat_notify_cnt' => $chat_notify_cnt,
        );
        return Response::json($ret);
    }
}
