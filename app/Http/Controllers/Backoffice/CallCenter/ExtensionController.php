<?php

namespace App\Http\Controllers\Backoffice\CallCenter;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\CallCenter\CallCenterExtension;

use Yajra\Datatables\Datatables;

use DB;
use Response;

class ExtensionController extends Controller
{
    public function index(Request $request)
    {
		$datalist = DB::table('call_center_extension as ce')
						->leftJoin('common_property as cp', 'ce.property_id', '=', 'cp.id')
						->leftJoin('common_department as cd', 'ce.dept_id', '=', 'cd.id')
                        ->leftJoin('common_users as cu', 'cu.id', '=', 'ce.user_id')
						->select(DB::raw('ce.*, cp.name as property_name, cd.department, concat(cu.first_name, " ", cu.last_name) as user_name'));
						
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
	
    public function create()
    {
        //
    }
	
	
    public function store(Request $request)
    {
		$input = $request->except('id');
		
		$model = CallCenterExtension::create($input);
		
		return Response::json($model);			
    }

	public function show($id)
    {
        $model = CallCenterExtension::find($id);	
		
		return Response::json($model);
    }

  
    public function edit(Request $request, $id)
    {
		
    }

    public function update(Request $request, $id)
    {
		$input = $request->all();
		
		$model = CallCenterExtension::find($id);
		
		if( !empty($model) )
			$model->update($input);
		
		return Response::json($input);			
    }

    public function destroy(Request $request, $id)
    {
        $model = CallCenterExtension::find($id);
		$model->delete();

		return $this->index($request);
    }
}
