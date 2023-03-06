<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Common\CommonUser;

class AgentAgentChat extends Model
{
    //
    use SoftDeletes;

    protected $table = 'services_chat_agent_agent';

    protected $fillable = [
    	'from_id', 'to_id', 'text', 'path', 'read_status'
    ];

    protected $hidden = [
        'updated_at'
    ];

    public function sender() {
    	return $this->belongsTo(CommonUser::class, 'id', 'from_id');
    }

    public function receiver() {
    	return $this->belongsTo(CommonUser::class, 'id', 'to_id');
    }
}
