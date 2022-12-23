<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

use DB;
class Employee extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_employee';
	public 		$timestamps = true;

	public static function getUserID($employee_id) {
		$employee = Employee::find($employee_id);	
		if( empty($employee) )
			return 0;

		return $employee->user_id;
	}

	public static function createFromUser($user) {
		$employee = Employee::find($user->id);
		if( empty($employee) )
			$employee = new Employee();

		$employee->id = $user->id;
		$employee->user_id = $user->id;
		$employee->dept_id = $user->dept_id;
		$employee->fname = $user->first_name;
		$employee->lname = $user->last_name;
		$employee->mobile = $user->mobile;

		$data = DB::table('common_department as cd')
        	->join('common_property as cp', 'cd.property_id', '=', 'cp.id')
        	->where('cp.id', $user->dept_id)
        	->select(DB::raw('cd.property_id, cp.client_id'))
        	->first();

        if( !empty($data) )
        {
        	$employee->client_id = $data->client_id;
			$employee->property_id = $data->property_id;	
        }
		
		$employee->save();

		//$user->employee_id = $employee->id;
		//$user->save();
	}
}