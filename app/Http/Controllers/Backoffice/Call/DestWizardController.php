<?php

namespace App\Http\Controllers\Backoffice\Call;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Call\Destination;
use App\Models\Call\GroupDestination;

use Yajra\Datatables\Datatables;

use DB;
use Response;

class DestWizardController extends Controller
{
    public function index(Request $request)
    {
		$step = '4';
		
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			Carrier::whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		return view('backoffice.wizard.call.destination', compact('step'));			
    }
	
	public function getGridData()
    {
		// $datalist = GuestExtension::all();
		$datalist = DB::table('call_destination as cd')
							->leftJoin('call_group_destination as cgd', 'cd.id', '=', 'cgd.destination_id')
							->leftJoin('call_carrier_groups as ccg', 'cgd.carrier_group_id', '=', 'ccg.id')										
							->select(['cd.*', 'ccg.name']);
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
		$datalist = DB::table('call_destination as cd')
							->leftJoin('call_group_destination as cgd', 'cd.id', '=', 'cgd.destination_id')
							->leftJoin('call_carrier_groups as ccg', 'cgd.carrier_group_id', '=', 'ccg.id')						
							->groupBy('cd.id')				
							->select(['cd.*', 'ccg.name']);
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
		$input = $request->except(['id', 'carrier_group_id']);
		if($input['country'] === null) $input['country'] = '';
		if($input['code'] === null) $input['code'] = '';

		try {			
			$model = Destination::create($input);

			// save carrier group id
			$carrier_group_id = $request->get('carrier_group_id', 0);

			DB::table('call_group_destination')
				->where('destination_id', $model->id)
				->delete();

			$carrier_group = new GroupDestination();
			$carrier_group->carrier_group_id = $carrier_group_id;
			$carrier_group->destination_id = $model->id;
			$carrier_group->save();
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
		$model = Destination::find($id);	
		$carrier_group = DB::table('call_group_destination')->where('destination_id', $id)->first();

		if( !empty($carrier_group) )
			$model->carrier_group_id = $carrier_group->carrier_group_id;
		else	
			$model->carrier_group_id = 0;
		
		return Response::json($model);
    }

  
    public function edit(Request $request, $id)
    {
		
    }
	
	public function update(Request $request, $id)
    {
		$input = $request->except(['id', 'carrier_group_id']);
		
		$model = Destination::find($id);
		
		if( !empty($model) )
		{
			$model->update($input);

			// save carrier group id
			$carrier_group_id = $request->get('carrier_group_id', 0);

			$carrier_group = GroupDestination::where('destination_id', $id)->first();
			if( empty($carrier_group) )
				$carrier_group = new GroupDestination();

			$carrier_group->carrier_group_id = $carrier_group_id;
			$carrier_group->destination_id = $model->id;
			$carrier_group->save();
		}
		
		return Response::json($input);			
    }
	
	public function updateData(Request $request)
	{
		$id = $request->get('id', '0');
		
		return $this->update($request, $id);
	}

    public function destroy(Request $request, $id)
    {
        $model = Destination::find($id);
		$model->delete();

		DB::table('call_group_destination')
				->where('destination_id', $id)
				->delete();

		return $this->index($request);
    }
}
