<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class GuestChatSession extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_chat_guest_session';
	public 		$timestamps = true;
}