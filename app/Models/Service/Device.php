<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Device extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_devices';
	public 		$timestamps = false;
	
	protected $typelist = [
        '1' => 'Mobile',
        '2' => 'Landline'		
    ];
	
	public function getTypeList()
    {
        return $this->typelist;
    }

    static function getDeviceNumber($device_id)
    {
        $data = DB::table('services_devices as sd')
					->where('sd.device_id', $device_id)
					->select(DB::raw('sd.number'))
					->first();

		if( empty($data) )
			return 0;

		return $data->number;
    }

    static function getLocGroup($device_id)
    {
        $data = DB::table('services_devices as sd')
					->where('sd.device_id', $device_id)
					->select(DB::raw('sd.loc_grp_id'))
					->first();

		if( empty($data) )
			return 0;

		return $data->loc_grp_id;
	}
	
	static function getSecDeptFunc($user_id)
    {
        $data = DB::table('services_devices as sd')
				->leftJoin('common_users as cu', 'cu.device_id', '=', 'sd.device_id')
					->where('cu.id', $user_id)
					->select(DB::raw('sd.sec_dept_func'))
					->first();

		if( empty($data) )
			return 0;

		return $data->sec_dept_func;
    }
}