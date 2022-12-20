<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

use DB;

class AgentChatHistory extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_chat_agent_history';
	public 		$timestamps = true;

	public static function getTotalUnreadCount($from_id) {
		return DB::table('services_chat_agent_history as hist')
			->where('hist.from_id', $from_id)
			->where('hist.direction', 0)	
			->where('unread', 1)
			->count();
	}

	public static function getUnreadCount($from_id, $to_id) {
		return DB::table('services_chat_agent_history as hist')
			->where('hist.from_id', $from_id)
			->where('hist.to_id', $to_id)
			->where('hist.direction', 0)	
			->where('unread', 1)
			->count();
	}

}