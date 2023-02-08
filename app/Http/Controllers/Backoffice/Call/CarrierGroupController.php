<?php

namespace App\Http\Controllers\Backoffice\Call;

use App\Models\Call\GroupDestination;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Call\Carrier;
use App\Models\Call\CarrierGroup;

use Yajra\Datatables\Datatables;

use DB;
use Response;

class CarrierGroupController extends Controller
{
    public function index(Request $request)
    {
		$step = '5';
		
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			CarrierGroup::whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 
		
		$carrier = Carrier::lists('carrier', 'id');
		$carrier_group = new CarrierGroup();

		return view('backoffice.wizard.call.carrier_group', compact('carrier_group', 'carrier', 'step'));			
    }
	
	public function getGridData()
    {
		// $datalist = GuestExtension::all();
		$datalist = DB::table('call_carrier_groups as ccg')			
						->leftJoin('call_carrier as cr', 'ccg.carrier_id', '=', 'cr.id')		
						->select(['ccg.*', 'cr.carrier']);
		
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
		$datalist = DB::table('call_carrier_groups as ccg')			
						->leftJoin('call_carrier as cr', 'ccg.carrier_id', '=', 'cr.id')		
						->select(['ccg.*', 'cr.carrier']);
		
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
		$input = $request->except(['id', 'calltype_id']);
		
		try {			
			$model = CarrierGroup::create($input);
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
        $model = CarrierGroup::find($id);	
		
		$calltype_id = $model->getCallType();
		
		$data = $model->toArray();
		$data['calltype_id'] = $calltype_id;
		
		return Response::json($data);
    }

  
    public function edit(Request $request, $id)
    {
   
    }

    public function update(Request $request, $id)
    {
		$input = $request->except('calltype_id');
		
		$model = CarrierGroup::find($id);
		
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
        $model = CarrierGroup::find($id);
		$model->delete();

		return $this->index($request);
    }

	public function getDestDist(Request $request) {
		$id = $request->get('id', 0);
		$dest_ids = GroupDestination::where('carrier_group_id', $id)->select('destination_id')->get()->pluck('destination_id');

		$exist_ids = GroupDestination::select('destination_id')->get()->pluck('destination_id');

		$selected_dest = DB::table('call_destination')
				->whereIn('id', $dest_ids)
				->get();

		$unselected_dest = DB::table('call_destination')
				->whereNotIn('id', $exist_ids)
				->get();

		$model = array();
		$model[] = $unselected_dest;
		$model[] = $selected_dest;

		return Response::json($model);
	}

	public function postDestList(Request $request)
	{
		$id = $request->get('id', '1');

		GroupDestination::where('carrier_group_id', $id)->delete();

		$select_id = $request->get('select_id');

		for( $i = 0; $i < count($select_id); $i++ )
		{
			$dest_id = $select_id[$i];

			$group_dest = new GroupDestination();

			$group_dest->carrier_group_id = $id;
			$group_dest->destination_id = $dest_id;
			$group_dest->save();
		}

		echo "Group Destination has beed updated successfully";
	}
}
