<?php

namespace App\Http\Controllers\Backoffice\Admin;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Common\Property;
use App\Models\Common\Division;

use Excel;
use DB;
use Datatables;
use Response;

class DivisionWizardController extends UploadController
{


    public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('common_division as cd')									
						->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')						
						->select(['cd.*', 'cp.name as cpname']);		
						
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
					->rawColumns(['checkbox', 'edit', 'delete'])			
					->make(true);
        }	
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
    	$input = $request->except('id');
		if($input['division'] === null) $input['division'] = '';
		if($input['description'] === null) $input['description'] = '';

		$model = Division::create($input);
		
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
        $model = Division::find($id);	
		if( empty($model) )
			$model = new Division();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = Division::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = Division::find($id);
		$model->delete();

		return $this->index($request);
    }
}
