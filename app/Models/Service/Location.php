<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;

class Location extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_location';
	public 		$timestamps = true;


	public static function getLocationFromRoom($room_id)
	{
		$location_info = DB::table('common_room as cr')
				->leftJoin('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->leftJoin('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->where('cr.id', $room_id)
				->select(DB::raw('cf.id as floor_id, cb.id as building_id, cb.property_id'))
				->first();

		if( empty($location_info) )
			return null;

		$floor_id = $location_info->floor_id;
		$building_id = $location_info->building_id;
		$property_id = $location_info->property_id;

		$ret = DB::table('services_location as sl')
			->where('sl.property_id', $property_id)
			->where('sl.building_id', $building_id)
			->where('sl.floor_id', $floor_id)
			->where('sl.room_id', $room_id)
			->first();
		
		return $ret;
	}

	public static function getLocationInfo($id)
	{
		$location = DB::table('services_location as sl')
							->join('services_location_type as lt', 'sl.type_id', '=', 'lt.id')
							->where('sl.id', $id)
							->select(DB::raw('sl.*, lt.type'))
							->first();

		return $location;					
	}
}