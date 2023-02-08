<?php

namespace App\Http\Controllers\Backoffice\IT;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Common\CommonJobrole;
use App\Models\IT\ITType;
use App\Models\IT\ITApprovalRoute;


use Excel;
use Response;
use DB;
use Datatables;

class TypeController extends UploadController
{   
    public function index(Request $request)
    {
		$datalist = DB::table('services_it_type as it')			
					->select(DB::raw('it.*'));
					
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

	public function store(Request $request)
    {
    	$input = $request->except('id');
		$model = ITType::create($input);
		
		return Response::json($model);			
    }


    public function update(Request $request, $id)
    {
		$model = ITType::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		return Response::json($model);	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $model = ITType::find($id);
		$model->delete();

		return Response::json($model);
    }
	
	

	
	
	
	

	
	

}
