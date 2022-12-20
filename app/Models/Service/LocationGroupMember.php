<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;

class LocationGroupMember extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_location_group_members';
	public 		$timestamps = false;
	
	protected $typelist = [
        '1' => 'Property',
        '2' => 'Building',
		'3' => 'Floor',
		'4' => 'Room',
		'5' => 'Common Area',
		'6' => 'Admin Area',
		'7' => 'Outdoor'		
    ];
	
	public function getTypeList()
    {
        return $this->typelist;
    }

    public static function getLocationInfo($type, $loc_id) {
    	$loc_info = LocationGroupMember::where('type', $type)
    		->where('type_id', $loc_id)
    		->first();

    	return $loc_info;	
    }
	
	 public static function getLocationGroup($type) {
    	$loc_info = LocationGroupMember::where('type', $type)
			->select(DB::raw("type_id"))
    		->get();

    	return $loc_info;	
	}
	
	public static function getLocationGroupIds($loc_id)
	{
		$data = LocationGroupMember::where('loc_id', $loc_id)							
							->select(DB::raw('GROUP_CONCAT(location_grp) as grp_array'))
							->first();

		$loc_group_array = array_unique(explode(',', $data->grp_array));					

		return $loc_group_array;
	}
}