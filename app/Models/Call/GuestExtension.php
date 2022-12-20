<?php

namespace App\Models\Call;

use App\Models\Common\Building;
use App\Models\Common\Room;
use Illuminate\Database\Eloquent\Model;

class GuestExtension extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_guest_extn';
	public 		$timestamps = false;
	
	public function building()
    {
		return $this->belongsTo(Building::class, 'bldg_id');
    }	
	public function room()
    {
		return $this->belongsTo(Room::class, 'room_id');
    }
}