<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;
use App\Models\Common\Department;
use App\Models\Service\DeftFunction;
use App\Models\Service\Escalation;

use Excel;
use DB;
use Datatables;
use Response;

class DepartFuncController extends UploadController
{
    public function index(Request $request)
    {
		$datalist = DB::table('services_dept_function as df')			
						->leftJoin('common_department as cd', 'df.dept_id', '=', 'cd.id')
						->select(DB::raw('df.*, cd.department, 
							CASE 
								WHEN gs_device = "0" THEN "User"
								WHEN gs_device = "1" THEN "Device"
								WHEN gs_device = "2" THEN "Roster"
							END as device	
							'));
						
		return Datatables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('edit', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="job_role!=\'SuperAdmin\'&&edit_flag==0" ng-click="onShowEditRow('.$data->id.')">
						<span class="glyphicon glyphicon-pencil"></span>
					</button></p>';
				})
				->addColumn('delete', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-disabled="job_role!=\'SuperAdmin\'" ng-click="onDeleteRow('.$data->id.')">
						<span class="glyphicon glyphicon-trash"></span>
					</button></p>';
				})				
				->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
		$step = '0';
        $model = new DeftFunction();
		$department = Department::lists('department', 'id');
		
		return view('backoffice.wizard.guestservice.deftfunccreate', compact('model', 'pagesize', 'department', 'step'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
		$input = $request->except(['id', 'job_role', 'job_role_id', 'dept_name']);
		
		// attendant/supervisor is only one
		if( $input['hskp_role'] == 'Attendant')
			DB::table('services_dept_function')->where('hskp_role', 'Attendant')
				->update(['hskp_role' => 'None']);

		if( $input['hskp_role'] == 'Supervisor')
				DB::table('services_dept_function')->where('hskp_role', 'Supervisor')
					->update(['hskp_role' => 'None']);		

		$model = DeftFunction::create($input);
		
		$message = 'SUCCESS';	
		
		if ($request->ajax()) {
			return Response::json($model);
        }
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		return back()->with('error', $message)->withInput();	
    }

    public function show($id)
    {
		$model = DB::table('services_dept_function as df')
						->join('common_department as cd', 'df.dept_id', '=', 'cd.id')
						->where('df.id', $id)
						->select(DB::raw('df.*, cd.department as dept_name'))
						->first();	

		
		$escalation = DB::table('services_escalation as se')
				->join('common_job_role as jr', 'se.job_role_id', '=', 'jr.id')
				->join('common_job_role as sjr', 'se.sec_job_role_id', '=', 'sjr.id')
				->where('escalation_group', $id)
				->where('level', 0)
				->select(DB::raw('se.*, jr.job_role, sjr.job_role as sec_job_role'))
				->first();

		if( !empty($escalation) )		
		{
			$model->job_role = $escalation->job_role;		
			$model->job_role_id = $escalation->job_role_id;	
			$model->sec_job_role = $escalation->sec_job_role;		
			$model->sec_job_role_id = $escalation->sec_job_role_id;		
		}
		
		return Response::json($model);
    }

    public function edit(Request $request, $id)
    {
        $model = DeftFunction::find($id);	
		if( empty($model) )
			$model = new DeftFunction();
		
		return $this->showIndexPage($request, $model);
    }
  
    public function update(Request $request, $id)
    {
		$model = DeftFunction::find($id);	
		
		$input = $request->except(['id', 'job_role', 'job_role_id','sec_job_role','sec_job_role_id', 'dept_name']);
		
		// attendant/supervisor is only one
		if( $input['hskp_role'] == 'Attendant')
			DB::table('services_dept_function')->where('hskp_role', 'Attendant')
				->update(['hskp_role' => 'None']);

		if( $input['hskp_role'] == 'Supervisor')
				DB::table('services_dept_function')->where('hskp_role', 'Supervisor')
					->update(['hskp_role' => 'None']);		

		$model->update($input);

		// set job role on 
		$gs_device = $request->get('gs_device', 0);
		$job_role_id = $request->get('job_role_id', 0);
		$sec_job_role_id = $request->get('sec_job_role_id', 0);

		if( $gs_device == 0 )	// user based
		{
			$escalation = Escalation::where('escalation_group', $id)
					->where('level', 0)
					->first();

			if( empty($escalation) )			
			{
				$escalation = new Escalation();
				$escalation->escalation_group = $id;
				$escalation->level = 0;
				$escalation->max_time = 600;
			}

			$escalation->job_role_id = $job_role_id;
			$escalation->sec_job_role_id = $sec_job_role_id;

			$escalation->save();
		}
		
		if ($request->ajax()) {
			return Response::json($model);
        }
		
		return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = DeftFunction::find($id);
		$model->delete();

		DB::table('services_escalation')
			->where('escalation_group', $id)
			->delete();

		return $this->index($request);
    }
	
	public function parseExcelFile($path)
	{
		Excel::selectSheets('deptfunc')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					// echo json_encode($data);
					
					// $bldg_id = $data['bldg_id'];
					// $floor = $data['floor'];
					// if( DeftFunction::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
						// continue;					
					DeftFunction::create($data);
				}
			}							
		});
	}
}
