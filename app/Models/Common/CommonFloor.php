<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Models\Service\Location;
use App\Models\Service\LocationType;
use DB;

class CommonFloor extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_floor';
	public 		$timestamps = false;
	
	public function building()
    {
		return $this->belongsTo(Building::class, 'bldg_id');
	}		

	public static function getPropertyBuilding($floor_id) {		
		// find building, property id
		$data = DB::table('common_floor as cf')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->where('cf.id', $floor_id)
			->select(DB::raw('cf.*, cb.property_id'))
			->first();

		return $data;	
    }
	
	public static function createLocation()
	{
		$count = 0;
		$list = DB::table('common_floor as cf')
					->join('common_building as cb','cf.bldg_id', '=', 'cb.id')
					->select(DB::raw('cf.*, cb.property_id'))
					->get();
		
		$loc_type = LocationType::createOrFind('Floor');

		foreach($list as $row)
		{
			$location = Location::where('property_id', $row->property_id)
					->where('building_id', $row->bldg_id)
					->where('floor_id', $row->id)
					->where('type_id', $loc_type->id)
					->first();

			if( empty($location) )
			{
				$location = new Location();
				$location->type_id = $loc_type->id;
				$location->property_id = $row->property_id;
				$location->building_id = $row->bldg_id;
				$location->floor_id = $row->id;
			}		

			$location->name = $row->floor;
			$location->desc = $row->description;
			$location->save();

			$count++;	
		}
		
		return $count;
	}

	public static function deleteLocation()
	{
		$count = 0;
		$list = DB::table('common_floor as cf')
					->join('common_building as cb','cf.bldg_id', '=', 'cb.id')
					->select(DB::raw('cf.*, cb.property_id'))
					->get();
		
		foreach($list as $row)
		{
			$loc_type = LocationType::createOrFind('Floor');
			Location::where('property_id', $row->property_id)
					->where('building_id', $row->bldg_id)
					->where('floor_id', $row->id)
					->where('type_id', $loc_type->id)
					->delete();

			$count++;	
		}
		
		return $count;
	}
}
