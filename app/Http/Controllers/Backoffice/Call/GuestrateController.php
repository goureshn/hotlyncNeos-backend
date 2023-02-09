<?php

namespace App\Http\Controllers\Backoffice\Call;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Call\GuestChargeMap;
use App\Models\Call\CarrierGroup;
use App\Models\Call\TimeSlab;
use App\Models\Call\CarrierCharges;
use App\Models\Call\Allowance;
use App\Models\Call\HotelCharges;
use App\Models\Call\Tax;


use Yajra\Datatables\Datatables;

use DB;
use Response;

class GuestrateController extends Controller
{
    public function index(Request $request)
    {
		$step = '12';
		
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			GuestChargeMap::whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 
		
		$carriergroup = CarrierGroup::lists('name', 'id');
		$timeslab = TimeSlab::lists('name', 'id');
		$carriercharge = CarrierCharges::lists('charge', 'id');
		$hotelcharge = HotelCharges::lists('name', 'id');
		$tax = Tax::lists('name', 'id');
		$allowance = Allowance::lists('Name', 'id');
		
		$charge = new GuestChargeMap();

		return view('backoffice.wizard.call.guestratemap', compact('charge', 'carriergroup', 'timeslab', 'carriercharge', 'allowance', 'hotelcharge', 'tax', 'step'));			
    }
	
	public function getGridData()
    {
		$datalist = DB::table('call_guest_charge_map as ccm')			
						->leftJoin('call_carrier_groups as cg', 'ccm.group_id', '=', 'cg.id')
						->leftJoin('call_time_slab as ts', 'ccm.time_slab', '=', 'ts.id')
						->leftJoin('call_carrier_charges as cc', 'ccm.carrier_charges', '=', 'cc.id')
						->leftJoin('call_allowance as ca', 'ccm.call_allowance', '=', 'ca.id')
						->leftJoin('call_hotel_charges as hc', 'ccm.hotel_charges', '=', 'hc.id')
						->leftJoin('call_tax as tax', 'ccm.tax', '=', 'tax.id')
						->select(['ccm.*', 'cg.name as cgname', 'ts.name as tsname', 'cc.charge as ccname', 'ca.Name as caname', 'hc.name as hcname', 'tax.name as taxname']);
						
		
		return Datatables::of($datalist)
		->addColumn('roomtype', function ($data) {
			$vip_id = $data->room_type_ids;
			$array = explode(',', $vip_id);
			$vip = '';
			foreach ($array as $value) {

			$vip_data = DB::table('common_vip_codes as cvc')
				//->leftJoin('common_module as cm','cmp.module_id','=','cm.id')
				->where('cvc.id', $value)
				->select(DB::raw('cvc.*'))
				->get();
			
			for($j=0; $j < count($vip_data) ;$j++) {
				$vip .= ''. $vip_data[$j]->name . ',' ;
			}
		}
			return $vip;
		})
		->addColumn('vip', function ($data) {
			$vip_id = $data->vip_ids;
			$array = explode(',', $vip_id);
			$vip = '';
			foreach ($array as $value) {

			$vip_data = DB::table('common_vip_codes as cvc')
				//->leftJoin('common_module as cm','cmp.module_id','=','cm.id')
				->where('cvc.id', $value)
				->select(DB::raw('cvc.*'))
				->get();
			
			for($j=0; $j < count($vip_data) ;$j++) {
				$vip .= ''. $vip_data[$j]->name . ',' ;
			}
		}
			return $vip;
		})
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
		$datalist = DB::table('call_guest_charge_map as ccm')			
						->leftJoin('call_carrier_groups as cg', 'ccm.carrier_group_id', '=', 'cg.id')
						->leftJoin('call_time_slab as ts', 'ccm.time_slab', '=', 'ts.id')
						->leftJoin('call_carrier_charges as cc', 'ccm.carrier_charges', '=', 'cc.id')
						->leftJoin('call_allowance as ca', 'ccm.call_allowance', '=', 'ca.id')
						->leftJoin('call_hotel_charges as hc', 'ccm.hotel_charges', '=', 'hc.id')
						->leftJoin('call_tax as tax', 'ccm.tax', '=', 'tax.id')
						->select(['ccm.*', 'cg.name as cgname', 'ts.name as tsname', 'cc.charge as ccname', 'ca.Name as caname', 'hc.name as hcname', 'tax.name as taxname']);
						
		
		return Datatables::of($datalist)
		->addColumn('roomtype', function ($data) {
			$type_id = $data->room_type_ids;
			$array = explode(',', $type_id);
			$type = '';
			foreach ($array as $value) {

			$type_data = DB::table('common_room_type as rt')
				//->leftJoin('common_module as cm','cmp.module_id','=','cm.id')
				->where('rt.id', $value)
				->select(DB::raw('rt.*'))
				->get();
			
			for($j=0; $j < count($type_data) ;$j++) {
				$type .= ''. $type_data[$j]->type . ',' ;
			}
		}
			return $type;
		})
		->addColumn('vip', function ($data) {
			$vip_id = $data->vip_ids;
			$array = explode(',', $vip_id);
			$vip = '';
			foreach ($array as $value) {

			$vip_data = DB::table('common_vip_codes as cvc')
				//->leftJoin('common_module as cm','cmp.module_id','=','cm.id')
				->where('cvc.id', $value)
				->select(DB::raw('cvc.*'))
				->get();
			
			for($j=0; $j < count($vip_data) ;$j++) {
				$vip .= ''. $vip_data[$j]->name . ',' ;
			}
		}
			return $vip;
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
				->rawColumns(['roomtype', 'vip', 'checkbox', 'edit', 'delete'])				
				->make(true);
    }


    public function create()
    {
        //
    }
	
	public function store(Request $request)
    {
		$input = $request->except(['id', 'vip']);
		if($input['name'] === null) $input['name'] = '';
		if($input['room_type_ids'] === null) $input['room_type_ids'] = '';
		if($input['vip_ids'] === null) $input['vip_ids'] = '';

		try {			
			$model = GuestChargeMap::create($input);
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
        $model = GuestChargeMap::find($id);	
		
		return Response::json($model);
    }

  
    public function edit(Request $request, $id)
    {
		
    }
	
	public function update(Request $request, $id)
    {
		$input = $request->all();
		
		$model = GuestChargeMap::find($id);
		
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
        $model = GuestChargeMap::find($id);
		$model->delete();

		return $this->index($request);
    }
}
