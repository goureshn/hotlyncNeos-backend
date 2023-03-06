<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class AgentChatGroup extends Model
{
    //
	protected $table = 'services_chat_agent_group';
	protected $hidden = [
        'updated_at'
    ];
}
