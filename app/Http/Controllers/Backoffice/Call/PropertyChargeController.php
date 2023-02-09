<?php

namespace App\Http\Controllers\Backoffice\Call;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Call\Carrier;
use App\Models\Call\HotelCharges;

use Yajra\Datatables\Datatables;

use DB;
use Response;

class PropertyChargeController extends Controller
{
    public function index(Request $request)
    {
		$step = '7';
		
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			HotelCharges::whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 
		
		$carrier = Carrier::lists('carrier', 'id');
		$hotel_charge = new HotelCharges();

		return view('backoffice.wizard.call.property_charge', compact('hotel_charge', 'carrier', 'step'));			
    }
	
	public function getGridData()
    {
		$datalist = DB::table('call_hotel_charges as chc')			
						->leftJoin('call_carrier as cr', 'chc.carrier_id', '=', 'cr.id')		
						->select(['chc.*', 'cr.carrier']);
		
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
		$datalist = DB::table('call_hotel_charges as chc')			
						->leftJoin('call_carrier as cr', 'chc.carrier_id', '=', 'cr.id')		
						->select(['chc.*', 'cr.carrier']);
		
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
		if($input['name'] === null) $input['name'] = '';

		try {			
			$model = HotelCharges::create($input);
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
        $model = HotelCharges::find($id);	
		
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
		
		$model = HotelCharges::find($id);
		
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
        $model = HotelCharges::find($id);
		$model->delete();

		return $this->index($request);
    }
}
