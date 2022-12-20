<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use DB;

class Room extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_room';
	public 		$timestamps = false;

    public static function getPropertyBuildingFloor($room_id) {
    	$room = DB::table('common_room as cr')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->where('cr.id', $room_id)
			->select(DB::raw('cr.*, cb.id as building_id, cb.property_id'))
			->first();

		return $room;	
    }

    public static function getPropertyBuildingFloorFromRoom($room, $property_id) {
    	$room = DB::table('common_room as cr')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->where('cr.room', $room)
			->where('cb.property_id', $property_id)
			->select(DB::raw('cr.*, cb.id as building_id, cb.property_id'))
			->first();

		return $room;	
    }

    public static function getRoomCount($property_id) {
    	$total_room = DB::table('common_room as cr')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->where('cb.property_id', $property_id)
			->count();

		return $total_room;	
	}
}