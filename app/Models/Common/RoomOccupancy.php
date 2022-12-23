<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use DB;

class RoomOccupancy extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_room_occupancy';
	public 		$timestamps = false;
}