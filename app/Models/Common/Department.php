<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Modules\Functions;

class Department extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_department';
	public 		$timestamps = false;
	
	public function property()
    {
		return $this->belongsTo(Property::class, 'property_id');
    }

	static function getGSDeviceSetting($property_id,$dept_id) {
		$data = DB::table('common_department as cd')
					->where('cd.property_id', $property_id)
					->where('cd.id', $dept_id)
					->select(DB::raw('cd.device_based'))
					->first();

		if( empty($data) )
			return 0;

		return $data->device_based;
	}
		
}