<?php

namespace App\Models\Common;

use App\Models\Service\Location;
use App\Models\Service\LocationType;
use Illuminate\Database\Eloquent\Model;
use DB;

class Building extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_building';
	public 		$timestamps = false;

	public static function createLocation()
	{
		$count = 0;
		$list = DB::table('common_building')
			->get();
		
		$loc_type = LocationType::createOrFind('Building');

		foreach($list as $row)
		{
			$location = Location::where('property_id', $row->property_id)
					->where('building_id', $row->id)
					->where('type_id', $loc_type->id)
					->first();

			if( empty($location) )
			{
				$location = new Location();
				$location->type_id = $loc_type->id;
				$location->property_id = $row->property_id;
				$location->building_id = $row->id;
			}		

			$location->name = $row->name;
			$location->desc = $row->description;
			$location->save();

			$count++;	
		}

		return $count;
	}
}