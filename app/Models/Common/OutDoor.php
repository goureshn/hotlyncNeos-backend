<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Models\Service\Location;
use App\Models\Service\LocationType;
use DB;

class OutDoor extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_outdoor';
	public 		$timestamps = false;
	
	public function property()
    {
		return $this->belongsTo(Property::class, 'property_id');
	}	
	
	public static function createLocation()
	{
		$count = 0;
		$list = DB::table('common_outdoor')
			->get();
		
		$loc_type = LocationType::createOrFind('Outdoor');

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