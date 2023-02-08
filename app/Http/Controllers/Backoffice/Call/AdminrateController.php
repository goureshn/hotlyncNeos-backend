<?php

namespace App\Http\Controllers\Backoffice\Call;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Call\AdminChargeMap;
use App\Models\Call\CarrierGroup;
use App\Models\Call\TimeSlab;
use App\Models\Call\CarrierCharges;
use App\Models\Call\Allowance;


use Yajra\Datatables\Datatables;

use DB;
use Response;

class AdminrateController extends Controller
{
    public function index(Request $request)
    {
		$step = '11';
		
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			AdminChargeMap::whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 
		
		$carriergroup = CarrierGroup::lists('name', 'id');
		$timeslab = TimeSlab::lists('name', 'id');
		$carriercharge = CarrierCharges::lists('charge', 'id');
		$allowance = Allowance::lists('Name', 'id');
		
		$charge = new AdminChargeMap();

		return view('backoffice.wizard.call.adminratemap', compact('charge', 'carriergroup', 'timeslab', 'carriercharge', 'allowance', 'step'));			
    }
	
	public function getGridData()
    {
		$datalist = DB::table('call_admin_charge_map as ccm')			
						->leftJoin('call_carrier_groups as cg', 'ccm.carrier_groups', '=', 'cg.id')
						->leftJoin('call_time_slab as ts', 'ccm.time_slab_group', '=', 'ts.id')
						->leftJoin('call_carrier_charges as cc', 'ccm.carrier_charges', '=', 'cc.id')
						->leftJoin('call_allowance as ca', 'ccm.call_allowance', '=', 'ca.id')						
						->select(['ccm.*', 'cg.name as cgname', 'ts.name as tsname', 'cc.charge as ccname', 'ca.Name as caname']);
						
		
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
		$datalist = DB::table('call_admin_charge_map as ccm')			
						->leftJoin('call_carrier_groups as cg', 'ccm.carrier_group_id', '=', 'cg.id')
						->leftJoin('call_time_slab as ts', 'ccm.time_slab_group', '=', 'ts.id')
						->leftJoin('call_carrier_charges as cc', 'ccm.carrier_charges', '=', 'cc.id')
						->leftJoin('call_allowance as ca', 'ccm.call_allowance', '=', 'ca.id')						
						->select(['ccm.*', 'cg.name as cgname', 'ts.name as tsname', 'cc.charge as ccname', 'ca.Name as caname']);
						
		
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
		
		try {			
			$model = AdminChargeMap::create($input);
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
        $model = AdminChargeMap::find($id);	
		
		return Response::json($model);
    }

  
    public function edit(Request $request, $id)
    {
		
    }

    public function update(Request $request, $id)
    {
		$input = $request->all();
		
		$model = AdminChargeMap::find($id);
		
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
        $model = AdminChargeMap::find($id);
		$model->delete();

		return $this->index($request);
    }
}
