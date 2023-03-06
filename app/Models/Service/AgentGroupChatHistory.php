<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class AgentGroupChatHistory extends Model
{
    //
    protected $table = 'services_chat_agent_group_history';
    protected $hidden = [
    	'updated_at'
    ];
}
