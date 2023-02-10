<?php

namespace App\Http\Controllers\Backoffice\Call;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Call\Carrier;
use App\Models\Call\CarrierCharges;

use Yajra\Datatables\Datatables;

use DB;
use Response;

class CarrierChargeController extends Controller
{
    public function index(Request $request)
    {
		$step = '6';
		
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			CarrierCharges::whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 
		
		$carrier = Carrier::lists('carrier', 'id');
		$carrier_charge = new CarrierCharges();

		return view('backoffice.wizard.call.carrier_charge', compact('carrier_charge', 'carrier', 'step'));			
    }
	
	public function getGridData()
    {
		$datalist = DB::table('call_carrier_charges as ccc')			
						->leftJoin('call_carrier as cr', 'ccc.carrier_id', '=', 'cr.id')		
						->select(['ccc.*', 'cr.carrier']);
		
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
		$datalist = DB::table('call_carrier_charges as ccc')			
						->leftJoin('call_carrier as cr', 'ccc.carrier_id', '=', 'cr.id')		
						->select(['ccc.*', 'cr.carrier']);
		
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
		$input = $request->except(['id', 'chargetype_id']);
		if($input['charge'] === null) $input['charge'] = '';
		if($input['description'] === null) $input['description'] = '';

		try {			
			$model = CarrierCharges::create($input);
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
        $model = CarrierCharges::find($id);	
		
		$chargetype_id = $model->getChargeType();
		
		$data = $model->toArray();
		$data['chargetype_id'] = $chargetype_id;
		
		return Response::json($data);
    }

  
    public function edit(Request $request, $id)
    {
   
    }

    public function update(Request $request, $id)
    {
		$input = $request->except('chargetype_id');
		
		$model = CarrierCharges::find($id);
		
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
        $model = CarrierCharges::find($id);
		$model->delete();

		return $this->index($request);
    }
}
