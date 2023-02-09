<?php

namespace App\Http\Controllers\Backoffice\Call;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Common\Property;
use App\Models\Call\Carrier;

use Yajra\Datatables\Datatables;

use DB;
use Response;

class CarrierWizardController extends Controller
{
    public function index(Request $request)
    {
		$step = '3';
		
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			Carrier::whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$property = Property::lists('name', 'id');
			
		return view('backoffice.wizard.call.carrier', compact('property', 'step'));			
    }
	
	public function getGridData()
    {
		// $datalist = GuestExtension::all();
		$datalist = DB::table('call_carrier as cr')
			->leftJoin('common_property as cp', 'cr.prpty_id', '=', 'cp.id')			
			->select(['cr.*', 'cp.name']);
		return Datatables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('edit', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  onClick="onShowEditRow('.$data->id.')">
						<span class="glyphicon glyphicon-pencil"></span>
					</button></p>';
				})
				->addColumn('delete', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" onClick="onDeleteRow('.$data->id.')">
						<span class="glyphicon glyphicon-trash"></span>
					</button></p>';
				})				
				->make(true);
    }

	public function getGridNgData()
    {
		// $datalist = GuestExtension::all();
		$datalist = DB::table('call_carrier as cr')
			->leftJoin('common_property as cp', 'cr.prpty_id', '=', 'cp.id')			
			->select(['cr.*', 'cp.name']);
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
	
	public function store(Request $request)
    {
		$input = $request->except('id');
		if($input['carrier'] === null) $input['carrier'] = '';
		if($input['description'] === null) $input['description'] = '';

		try {			
			$model = Carrier::create($input);
		} catch(PDOException $e){
		   return Response::json([
				'success' => false,
				'message' => 'Hello'
				], 422);
		}	
	
		return Response::json($model);
    }
	
	public function createData(Request $request)
	{
		return $this->store($request);				
	}
	
	public function show($id)
    {
        $model = Carrier::find($id);	
		
		return Response::json($model);
    }

  
    public function edit(Request $request, $id)
    {
   
    }

    public function update(Request $request, $id)
    {
		$input = $request->all();
		
		$model = Carrier::find($id);
		
		if( !empty($model) )
			$model->update($input);
		
		return Response::json($input);		
    }

	public function updateData(Request $request)
	{
		$id = $request->get('id', '0');
		
		return $this->update($request, $id);	
	}

    public function destroy(Request $request, $id)
    {
        $model = Carrier::find($id);
		$model->delete();

		return $this->index($request);
    }
}
