<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Modules\Functions;
class DeftFunction extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_dept_function';
	public 		$timestamps = false;

	static function getGSDeviceSetting($dept_id,$dept_func) {
		$data = DB::table('services_dept_function as sdf')
					->where('sdf.dept_id', $dept_id)
					->where('sdf.id', $dept_func)
					->select(DB::raw('sdf.gs_device'))
					->first();

		if( empty($data) )
			return 0;

		return $data->gs_device;
	}

	static function getHskpRole($dept_func_id)
	{
		$dept_func = DB::table('services_dept_function')
			->where('id', $dept_func_id)
			->first();

		$hskp_role = 'None';	
		if( !empty($dept_func) )
			$hskp_role = $dept_func->hskp_role;	

		return $hskp_role;	
	}
}