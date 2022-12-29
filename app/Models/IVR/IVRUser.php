<?php

namespace App\Models\IVR;

use App\Models\Common\Room;
use Illuminate\Database\Eloquent\Model;

class IVRUser extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'ivr_vm_users';
	public 		$timestamps = false;
	protected 	$primaryKey = "uniqueid";

	public function room()
	{
		return $this->belongsTo(Room::class, 'room_id');
	}
}