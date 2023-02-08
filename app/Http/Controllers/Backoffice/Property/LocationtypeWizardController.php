<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Service\LocationType;

use DB;
use Datatables;
use Response;


class LocationtypeWizardController extends Controller
{
   
    public function index(Request $request)
    {
		$datalist = DB::table('services_location_type')									
				->select(DB::raw('*'));		
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
	

    public function create()
    {
        //
	}
	
	private function checkDuplicatedShortCode($short_code, $id)
	{
		return LocationType::where('id', '!=', $id)
					->where('short_code', $short_code)
					->exists();
	}

    public function store(Request $request)
    {
		$input = $request->except('id');
		
		if( $this->checkDuplicatedShortCode($input['short_code'] ?? "", 0) == true)
		{
			return Response::json([]);	
		}
		
		$model = LocationType::create($input);
		
		return Response::json($model);	
    }

    public function show($id)
    {
        //
    }


    public function edit(Request $request, $id)
    {
   
    }

    public function update(Request $request, $id)
    {
		$model = LocationType::find($id);	
		
		$input = $request->all();
		
		if( $this->checkDuplicatedShortCode($input['short_code'] ?? "", $id) == true)
		{
			return Response::json([]);	
		}
		
		$model->update($input);
		
		return Response::json($model);		
    }

    public function destroy(Request $request, $id)
    {
        $model = LocationType::find($id);
		$model->delete();

		return Response::json($model);	
    }	
}
