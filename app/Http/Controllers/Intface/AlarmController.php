<?php

namespace App\Http\Controllers\Intface;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Common\Property;
use App\Models\Intface\Alarm;

use DB;
use Datatables;
use Response;

class AlarmController extends Controller
{	
    public function index(Request $request)
    {
		$datalist = DB::connection('interface')->table('alarm')
					->select(['*']);
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
				->addColumn('property', function ($data) {
					$property_id = $data->property_id;
					$property = Property::find($property_id);
					return $property->name;
				})
				->rawColumns(['checkbox', 'edit', 'delete', 'property'])
				->make(true);
    }
	

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
    	$input = $request->except('id');

		$property_id = $request->get('property_id', '0');
		$model = Alarm::where('property_id', $property_id)->first();
		if( empty($model) )
			$model = Alarm::create($input);
		else
			$model->update($input);
		
		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return back()->with('error', $message)->withInput();	
    }
	
    public function show($id)
    {
        //
    }

  
    public function edit(Request $request, $id)
    {
        $model = Alarm::find($id);	
		if( empty($model) )
			$model = new Alarm();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = Alarm::find($id);	
		
        $input = $request->except('id');
		$model->update($input);
		
		return Response::json($model);
    }

    public function destroy(Request $request, $id)
    {
        $model = Alarm::find($id);
		$model->delete();

		return $this->index($request);
    }
}
