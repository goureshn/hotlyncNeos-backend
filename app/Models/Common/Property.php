<?php

namespace App\Models\Common;
use App\Models\Service\Location;
use App\Models\Service\LocationType;

use Illuminate\Database\Eloquent\Model;

use DB;

class Property extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_property';
	public 		$timestamps = false;
	
	protected $moduleslist = [
        '1' => 'Call Accounting',
        '2' => 'Voicemail',
		'3' => 'Room Service',
		'4' => 'Houskeeping',
		'5' => 'Guest Services',
		'6' => 'Engineering',		
    ];
	
	public function client()
    {
		return $this->belongsTo(Chain::class, 'client_id');
    }	

    public function getModuleList()
    {
        return $this->moduleslist;
    }

	public function getPropertyModuleList($property_id) {
		return DB::table('common_module as cm')
			->leftJoin('common_module_property as cp', 'cm.id', '=', 'cp.module_id')
			->where('cp.property_id', $property_id)
			->select(DB::raw('cm.*'))
			->get();
	}
	
	public function getModules()
	{
		$moduledata = array();
		$data = explode(",", $this->modules);
		foreach($data as $value)
		{
			foreach($this->moduleslist as $key => $module)
			{
				if( $value == $module )
				{
					array_push($moduledata,$key);
					break;
				}				
			}
		}		
		
		return $moduledata;
	}
	
	public function setModules($modules)
	{
		$value = "";
		$i = 0;
		
		if( !empty($modules) )
		{
			foreach( $modules as $key => $id )
			{
				$value = $value . $this->moduleslist[$id];			
				$value = $value . ',';
				$i++;
			}
		}
		
		$this->modules = $value;
		$this->save();
	}

	public static function getLogPath($id) {
		$property = Property::find($id);
		if( empty($property) )
			return '';

		return $property->logo_path;
	}

	public static function createLocation()
	{
		$list = DB::table('common_property')
			->get();

		$count = 0;	
		$loc_type = LocationType::createOrFind('Property');

		foreach($list as $row)		{

			$location = Location::where('property_id', $row->id)
					->where('type_id', $loc_type->id)
					->first();

			if( empty($location) )
			{
				$location = new Location();				
				$location->type_id = $loc_type->id;				
				$location->property_id = $row->id;				
			}		

			$location->name = $row->name;
			$location->desc = $row->description;
			$location->save();

			$count++;	
		}

		return $count;
	}

}