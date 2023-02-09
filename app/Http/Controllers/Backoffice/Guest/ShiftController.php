<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;
use App\Models\Service\ShiftUser;

use DB;
use Datatables;
use Response;
use Excel;

class ShiftController extends UploadController
{
   	public function index(Request $request)
    {
		/*
		$datalist = DB::select('SELECT su.*,
											GROUP_CONCAT(cb.name SEPARATOR ",") AS building_list 
											FROM ( 
											SELECT su.*, 
											GROUP_CONCAT(lg.name SEPARATOR ",") AS location_group_list 
											FROM (
											SELECT su.*, 
											GROUP_CONCAT(tg.name SEPARATOR ",") AS task_group_list 
											FROM(
											SELECT su.*, 
											CONCAT_WS(" ", cu.first_name, cu.last_name) AS wholename, 
											GROUP_CONCAT(df.function SEPARATOR ",") AS dept_func_list
											FROM services_shift_users AS su
											JOIN common_users AS cu ON su.user_id = cu.id
											LEFT JOIN services_dept_function AS df ON FIND_IN_SET(df.id, su.dept_func_ids)
											GROUP BY su.id
											) AS su
											LEFT JOIN services_task_group AS tg ON FIND_IN_SET(tg.id, su.task_group_ids)
											GROUP BY su.id
											) AS su
											LEFT JOIN services_location_group AS lg ON FIND_IN_SET(lg.id, su.location_group_ids)
											GROUP BY su.id
											) AS su											
											LEFT JOIN common_building AS cb ON FIND_IN_SET(cb.id, su.building_ids)
											GROUP BY su.id');
		$datalist = collect($datalist);
		
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

			

	*/	

		$datalist = DB::table('services_shift_users as su')
						->leftJoin('common_users as cu', 'su.user_id', '=', 'cu.id')
						->leftjoin("services_dept_function as df", \DB::raw('FIND_IN_SET(df.id, su.dept_func_ids)'), '>', \DB::raw("'0'"))
					
					//	->leftjoin("services_location_group as lg", \DB::raw('FIND_IN_SET(lg.id, su.location_group_ids)'), '>', \DB::raw("'0'"))
						
					//	->leftjoin("services_task_group as tg", \DB::raw('FIND_IN_SET(tg.id, su.task_group_ids)'), '>', \DB::raw("'0'"))
					->select(DB::Raw('su.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename , GROUP_CONCAT(df.function) as dept_func_list'))
						->groupBy('su.id');
						
					

						
					//	->GroupBy('su.id')
					//	->OrderBy('su.id');


		return Datatables::of($datalist)
		/*
				->addColumn('dept_func_list', function ($data) {
					$ids = $data->dept_func_ids;
					$list = DB::table('services_dept_function')
						->whereRaw("FIND_IN_SET(id, '$ids')")
						->select(DB::raw('GROUP_CONCAT(`function`) as field'))
						->first();

					return $list->field;
				})
			*/
				->addColumn('location_group_list', function ($data) {
					$ids = $data->location_group_ids;
					$list = DB::table('services_location_group')
						->whereRaw("FIND_IN_SET(id, '$ids')")
						->select(DB::raw('GROUP_CONCAT(name) as field'))
						->first();

					return $list->field;
				})
				
				->addColumn('task_group_list', function ($data) {
					$ids = $data->task_group_ids;
					$list = DB::table('services_task_group')
						->whereRaw("FIND_IN_SET(id, '$ids')")
						->select(DB::raw('GROUP_CONCAT(name) as field'))
						->first();

					return $list->field;
				})
				
				->addColumn('building_list', function ($data) {
						$ids = $data->building_ids;
						$list = DB::table('common_building')
							->whereRaw("FIND_IN_SET(id, '$ids')")
							->select(DB::raw('GROUP_CONCAT(name) as field'))
							->first();

						return $list->field;
				})
			
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
				->rawColumns(['location_group_list', 'task_group_list', 'building_list', 'checkbox', 'edit', 'delete'])
				->make(true);

				
    }

    public function create()
    {
		return view('backoffice.wizard.guestservice.ShiftUsercreate');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
		$input = $request->all();
		$model = ShiftUser::create($input);
		
		return Response::json($model);
    }
	
    public function show($id)
    {
        $model = ShiftUser::find($id);	
		
		return Response::json($model);
    }
  
    public function edit(Request $request, $id)
    {
        $model = ShiftUser::find($id);	
		if( empty($model) )
			$model = new ShiftUser();
		
		return $this->showIndexPage($request, $model);
    }
   
    public function update(Request $request, $id)
    {
		$model = ShiftUser::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		return Response::json($model);	
    }
   
    public function destroy(Request $request, $id)
    {
        $model = ShiftUser::find($id);
		$model->delete();

		return Response::json($model);	
    }
	
	public function parseExcelFile($path)
	{
		Excel::selectSheets('ShiftUser')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					// $bldg_id = $data['bldg_id'];
					// $floor = $data['floor'];
					// if( Escalation::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
						// continue;					
					ShiftUser::create($data);
				}
			}							
		});
	}
}
