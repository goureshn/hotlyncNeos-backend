<?php

namespace App\Models\Common;

use App\Models\Service\Location;
use App\Models\Service\LocationType;
use Illuminate\Database\Eloquent\Model;
use DB;

class Room extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_room';
	public 		$timestamps = false;

	public function building()
    {
		return $this->belongsTo(Building::class, 'bldg_id');
    }	
	
	public function roomtype()
    {
		return $this->belongsTo(RoomType::class, 'type_id');
    }	
	
	public function floor()
    {
		return $this->belongsTo(CommonFloor::class, 'flr_id');
    }
	
	public function hskpstatus()
    {
		return $this->belongsTo(HskpStatus::class, 'hskp_status_id');
    }


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

	public static function createLocation()
	{
		$count = 0;
		$list = DB::table('common_room as cr')
					->join('common_floor as cf','cr.flr_id', '=', 'cf.id')
					->join('common_building as cb','cf.bldg_id', '=', 'cb.id')
					->select(DB::raw('cr.*, cf.bldg_id, cb.property_id'))
					->get();
		
		$loc_type = LocationType::createOrFind('Room');

		foreach($list as $row)
		{
			$location = Location::where('property_id', $row->property_id)
					->where('building_id', $row->bldg_id)
					->where('floor_id', $row->flr_id)
					->where('room_id', $row->id)
					->where('type_id', $loc_type->id)
					->first();

			if( empty($location) )
			{
				$location = new Location();
				$location->type_id = $loc_type->id;
				$location->property_id = $row->property_id;
				$location->building_id = $row->bldg_id;
				$location->floor_id = $row->flr_id;
				$location->room_id = $row->id;
			}		

			$location->name = $row->room;
			$location->desc = $row->description;
			$location->save();

			$count++;	
		}

		return $count;
	}

	public static function deleteLocation()
	{
		$count = 0;
		$list = DB::table('common_room as cr')
					->join('common_floor as cf','cr.flr_id', '=', 'cf.id')
					->join('common_building as cb','cf.bldg_id', '=', 'cb.id')
					->select(DB::raw('cr.*, cf.bldg_id, cb.property_id'))
					->get();
		
		foreach($list as $row)
		{
			$loc_type = LocationType::createOrFind('Room');
			Location::where('property_id', $row->property_id)
					->where('building_id', $row->bldg_id)
					->where('floor_id', $row->flr_id)
					->where('room_id', $row->id)
					->where('type_id', $loc_type->id)
					->delete();

			$count++;	
		}

		return $count;
	}
}