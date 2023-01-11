<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Models\Service\Location;
use App\Models\Service\LocationType;
use DB;

class AdminArea extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_admin_area';
	public 		$timestamps = false;
	
	public function floor()
    {
		return $this->belongsTo(CommonFloor::class, 'floor_id');
	}	
	
	public static function createLocation()
	{
		$count = 0;
		$list = DB::table('common_admin_area as aa')
					->join('common_floor as cf','aa.floor_id', '=', 'cf.id')
					->join('common_building as cb','cf.bldg_id', '=', 'cb.id')
					->select(DB::raw('aa.*, cf.bldg_id, cb.property_id'))
					->get();
		
		$loc_type = LocationType::createOrFind('Admin Area');
		
		foreach($list as $row)
		{			
			$location = Location::where('property_id', $row->property_id)
					->where('building_id', $row->bldg_id)
					->where('floor_id', $row->floor_id)
					->where('room_id', $row->id)
					->where('type_id', $loc_type->id)
					->first();

			if( empty($location) )
			{
				$location = new Location();
				$location->type_id = $loc_type->id;
				$location->property_id = $row->property_id;
				$location->building_id = $row->bldg_id;
				$location->floor_id = $row->floor_id;
				$location->room_id = $row->id;
			}		

			$location->name = $row->name;
			$location->desc = $row->description;
			$location->save();

			$count++;	
		}

		return $count;
	}
}