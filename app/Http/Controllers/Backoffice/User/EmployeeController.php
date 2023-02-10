<?php

namespace App\Http\Controllers\Backoffice\User;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;

use App\Models\Common\Chain;
use App\Models\Common\CommonUser;
use App\Models\Common\Department;
use App\Models\Common\Employee;
use App\Models\Common\PropertySetting;
use App\Models\Common\Property;

use Excel;
use DB;
use Datatables;
use Response;
use Redis;
use App\Modules\Functions;


class EmployeeController extends UploadController
{
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$user_id = $request->get('user_id', 0);

			if( $user_id > 0 )
				$property_ids_by_jobrole = $request->get('property_ids_by_jobrole', []);
			else
			{
				$client_id = $request->get('client_id', 0);
				$property_ids_by_jobrole = CommonUser::getProertyIdsByClient($client_id);	
			}

			
			$datalist = DB::table('common_employee as ce')
				->leftJoin('common_department as cd', 'ce.dept_id', '=', 'cd.id')
				->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id');

			$datalist->whereIn('ce.property_id', $property_ids_by_jobrole);
			
			$datalist->select(DB::raw('ce.*, cu.picture, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'));

			return Datatables::of($datalist)			
					->addColumn('checkbox', function ($data) {
						return '<input type="checkbox" class="checkthis" />';
					})
					->addColumn('edit', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"   ng-disabled="viewclass" ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
					})
					->addColumn('delete', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal"  ng-disabled="viewclass" ng-click="onDeleteRow('.$data->id.')">
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
					})
					->addColumn('image', function ($data) {
						if($data->picture != '') {
							return '<p data-placement="top" data-toggle="tooltip" >
										<span class="thumb-sm avatar pull-left thumb-xs m-r-xs">
						            		<img src="'.$data->picture.'">
										</span></p>';
						}else {
							return '';
						}
					})
					->rawColumns(['checkbox', 'edit', 'delete', 'image'])					
					->make(true);
        }
    }
	
    public function store(Request $request)
    {
        $input = $request->except(['picture', 'wholename']);

        if( $input['user_id'] > 0 )
        {
        	$model = Employee::find($input['user_id']);

        	$input['id'] = $input['user_id'];

        	if( !empty($model) )        		
        		$model->update($input);
        }
        
        if( empty($model) )
        	$model = Employee::create($input);

		if( $input['user_id'] > 0 && !empty($model) )
		{
			$user = CommonUser::find($input['user_id']);

			if( !empty($user) ) 
			{
				$user->employee_id = $model->id;
				$user->save();		
			}
		}

		return Response::json($model);		
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
       	
    }

    public function update(Request $request, $id)
    {
	    $input = $request->except(['picture', 'wholename', 'image']);

        if( $input['user_id'] > 0 )
        {
        	Employee::where('user_id', $input['user_id'])
        		->where('id', '!=', $id)
       			->delete();

       		$input['id'] = $input['user_id'];	
        }

        $model = Employee::find($id);

        if( !empty($model) && $model->user_id > 0 )
		{
			$user = CommonUser::find($model->user_id);
			if( !empty($user) ) 
			{
				$user->employee_id = 0;
				$user->save();		
			}
		}
		
		$model->update($input);

		if( !empty($model) && $input['user_id'] > 0 )
		{
			$user = CommonUser::find($input['user_id']);
			if( !empty($user) ) 
			{
				$user->employee_id = $input['user_id'];
				$user->save();		
			}
		}
		
		return Response::json($input);		
    }

    public function destroy(Request $request, $id)
    {
        $model = Employee::find($id);
        if( !empty($model) )
        {
        	$user = CommonUser::find($model->user_id);
			if( !empty($user) ) 
			{
				$user->employee_id = 0;
				$user->save();		
			}

			$model->delete();
        }

		return Response::json($model);		
    }

    public function parseExcelFile($path)
	{
		Excel::selectSheets('Employee')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				$user_ids = [];

				foreach( $rows[$i] as $data )
				{
					$user_ids[] = $data['user_id'];					
				}

				Employee::whereIn('user_id', $user_ids)->delete();		

				foreach( $rows[$i] as $data )
				{					
					$employee = Employee::create($data);
					if( $data['user_id'] > 0 && !empty($employee) )
					{
						$user = CommonUser::find($data['user_id']);
						$user->employee_id = $employee->id;
						$user->save();	
					}
					
				}
			}							
		});
	}

	public function migrate(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $client_id = $request->get('client_id', 0);

        date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");


        $user_list = DB::table('common_users as cu')
        	->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')        	
        	->join('common_property as cp', 'cd.property_id', '=', 'cp.id')
        	->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')        	
        	->where('cp.client_id', $client_id)
        	->select(DB::raw('cu.*, cd.property_id, cp.client_id, jr.job_role, cd.department, cp.name as property_name'))
        	->get();

        DB::update("ALTER TABLE common_employee AUTO_INCREMENT = 0;");

        foreach ($user_list as $key => $row) {
    		$employee = Employee::find($row->id);
    		if( empty($employee) )
    			$employee = new Employee();

    		$employee->id = $row->id;
			$employee->user_id = $row->id;
    		$employee->client_id = $client_id;
    		$employee->property_id = $row->property_id;
    		$employee->dept_id = $row->dept_id;
    		$employee->wef = $cur_time;
    		$employee->fname = $row->first_name;
    		$employee->lname = $row->last_name;
    		$employee->mobile = $row->mobile;
    		$employee->design = $row->job_role;
    		$employee->empid = '';
    		$employee->property = $row->property_name;
    		$employee->dept = $row->department;

    		$employee->save();

    		$user = CommonUser::find($row->id);
    		$user->employee_id = $employee->id;
    		$user->save();
    	}	

    	$ret = array();
    	$ret['code'] = 200;

		return Response::json($ret);		
    }

    public function getSyncSetting(Request $request) {
    	$client_id = $request->get('client_id', 0);

    	$setting = PropertySetting::getEmployeeSetting($client_id);

    	return Response::json($setting);    	
    } 

    public function updateSyncSetting(Request $request) {    	
    	$client_id = $request->get('client_id', 0);
    	$auto_sync_employee = $request->get('auto_sync_employee', 0);
    	$auto_sync_employee_time = $request->get('auto_sync_employee_time', 0);

    	$values = array();
    	$values['auto_sync_employee'] = $auto_sync_employee;
    	$values['auto_sync_employee_time'] = $auto_sync_employee_time;
    	$values['employee_db_type'] = $request->get('employee_db_type', 'MS SQL');
    	$values['employee_db_address'] = $request->get('employee_db_address', '192.168.1.251');
    	$values['employee_db_username'] = $request->get('employee_db_username', 'sa');
    	$values['employee_db_password'] = $request->get('employee_db_password', '123456');
    	$values['employee_db_name'] = $request->get('employee_db_name', 'HEADS_Int');

    	PropertySetting::savePropertySetting($client_id, $values);

    	return Response::json($values);    	
    } 
 
 // http://192.168.1.253/schedule/employee/sync?property_id=4
    public function syncWithExternalSystem(Request $request) {
    	ini_set('memory_limit','-1');
		ini_set('max_execution_time', 300);

    	$client_id = $request->get('property_id', 0);
    	
		$sync_setting = PropertySetting::getEmployeeSetting($client_id);

		DB::update("ALTER TABLE common_employee AUTO_INCREMENT = 1000000;");

		$property_ids = array();
		$dept_ids = array();

		if( $sync_setting['auto_sync_employee'] == 1 ) {

			$driver = 'sqlsrv';
			switch( $sync_setting['employee_db_type'] )
			{
				case 'MS SQL':
					$driver = 'sqlsrv';
					break;
				case 'Mysql':
					$driver = 'mysql';
					break;	
			}

			config(['database.connections.employee' => [
			            'driver' => $driver,
			            'host' => $sync_setting['employee_db_address'],
			            'username' => $sync_setting['employee_db_username'],
			            'password' => $sync_setting['employee_db_password'],
			            'database' => $sync_setting['employee_db_name'],
			            'charset'  => 'utf8',
			            'prefix'   => '',
			        ]]);

			try {
				$datalist = DB::connection('employee')->table('heads_emp_details')
					// ->limit(10)
					->get();
			} catch (\Exception $e) {
			    $message = array();

				$message['type'] = 'alarm';			
				$data['client_id'] = $client_id;
				$message['data'] = $data;

				Redis::publish('notify', json_encode($message));	
				echo $e->getMessage();

			    exit;
			    // something went wrong
			}	

			$employee_ids = [];	
			foreach($datalist as $row) {
				$property_id = 0;

				if( !empty($row->property) )
				{
					$property_name = $row->property;
					// $property_name = 'ETS-Test';
					if( empty($property_ids[$property_name]) )	
					{
						$property = Property::where('name', $property_name)->first();	
						if( !empty($property) )
						{
							$property_ids[$property_name] = $property->id;
						}
						else
							$property_ids[$property_name] = '';

					}

					$property_id = $property_ids[$property_name];
				} 

				$dept_id = 0;

				if( !empty($row->dept) )
				{
					$dept_name = $row->dept;
					// $dept_name = 'Reception GS10';
					if( empty($dept_ids[$dept_name]) )	
					{
						$dept = Department::where('department', $dept_name)->first();	
						if( !empty($dept) )
						{
							$dept_ids[$dept_name] = $dept->id;
						}
						else
							$dept_ids[$dept_name] = '';

					}

					$dept_id = $dept_ids[$dept_name];
				} 

				$first_name = $row->fname;
				$last_name = $row->lname;

				$user_id = 0;
				$user = CommonUser::where('first_name', $first_name)
					->where('last_name', $last_name)
					->first();

				if( !empty($user) )
					$user_id = $user->id;	

				$empid = $row->empid;

				if( $user_id > 0 )
					$employee = Employee::find($user_id);
				else					
					$employee = Employee::where('empid', $empid)->first();

				if( empty($employee) )
				{
					$employee = new Employee();
					if( $user_id > 0 )
						$employee->id = $user_id;
				}

				$employee->client_id = $client_id;
				$employee->property_id = $property_id;
				$employee->dept_id = $dept_id;
				$employee->user_id = $user_id;
				$employee->wef = $row->wef;
				$employee->rtype = $row->rtype;
				$employee->empid = $row->empid;
				$employee->fname = $first_name;
				$employee->lname = $last_name;
				$employee->sex = $row->sex;
				$employee->mstatus = $row->mstatus;
				$employee->dob = $row->dob;
				$employee->nationality = $row->nationality;
				$employee->tel = $row->tel;
				$employee->mobile = $row->mobile;
				$employee->address = $row->address;
				$employee->country = $row->country;

				if( !empty($row->property) )
					$employee->property = $row->property;
				if( !empty($row->dept) )
					$employee->dept = $row->dept;
				if( !empty($row->sdept) )
					$employee->sdept = $row->sdept;

				$employee->divsn = $row->divsn;
				$employee->sdept = $row->sdept;
				$employee->design = $row->design;
				$employee->doj = $row->doj;
				$employee->psnum = $row->psnum;
				$employee->psexp = $row->psexp;
				$employee->vsexp = $row->vsexp;
				$employee->hasdone = $row->hasdone;
				$employee->dot = $row->dot;
				$employee->cardid = $row->cardid;
				$employee->resfileno = $row->resfileno;
				$employee->dateofissue = $row->dateofissue;
				$employee->passportno = $row->passportno;
				$employee->vacation_start = $row->vacation_start;
				$employee->vacation_end = $row->vacation_end;
				$employee->grade = $row->grade;
				$employee->short_name = $row->short_name;
				$employee->employee_type = $row->employee_type;

				$employee->save();

				if( !empty($user) )
				{
					$user->employee_id = $employee->id;
		    		$user->save();	
				}
				
				$employee_ids[] = $employee->id;
			}

			Employee::whereNotIn('id', $employee_ids)
				->where('empid', '!=', '')
				->delete();

			echo json_encode($employee_ids);	
		}		
    }
	
}
