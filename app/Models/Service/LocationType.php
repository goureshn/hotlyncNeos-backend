<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class LocationType extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_location_type';
	public 		$timestamps = false;

	public static function createOrFind($type)
	{
		$model = LocationType::where('type', $type)->first();
		if( empty($model) )
		{
			$model = new LocationType();
			$model->type = $type;

			$model->save();
		}

		return $model;	
	}
}