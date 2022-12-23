<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use DB;

class CommonJobrole extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_job_role';
	public 		$timestamps = false;

	static function getHskpRole($job_role_id)
	{
		$model = DB::table('common_job_role')
			->where('id', $job_role_id)
			->first();

		$hskp_role = 'None';	
		if( !empty($model) )
			$hskp_role = $model->hskp_role;	

		return $hskp_role;	
	}
}