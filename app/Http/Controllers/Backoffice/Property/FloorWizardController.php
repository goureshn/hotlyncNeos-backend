<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;
use App\Models\Service\Location;
use App\Models\Service\LocationType;


use DB;
use Datatables;
use Response;

class FloorWizardController extends UploadController
{
   	public function showIndexPage($request, $model)
	{
		  // delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			DB::table('common_floor')->whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$query = CommonFloor::where('id', '>', '0');

		$pagesize = $request->input('pagesize');
		if( empty($pagesize) )
			$pagesize = 10;		
		
		$request->flashOnly('search'); 
		
		$building = Building::lists('name', 'id');				
		$datalist = $query->paginate($pagesize);
		
		//$mode = "read";
		$step = '3';
		return view('backoffice.wizard.property.floor', compact('datalist', 'model', 'pagesize', 'building', 'step'));				
	}	
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('common_floor as cf')									
						->leftJoin('common_building as cb', 'cf.bldg_id', '=', 'cb.id')		
						->leftJoin('common_property as cp', 'cb.property_id', '=', 'cp.id')
						->select(['cf.*', 'cb.name as cbname', 'cp.name as cpname']);		
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
		else
		{
			$model = new CommonFloor();
			return $this->showIndexPage($request, $model);
		}
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
    	$input = $request->except('id');
		$model = CommonFloor::create($input);
		CommonFloor::createLocation();

		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return back()->with('error', $message)->withInput();	
    }
    
    public function show($id)
    {
        //
    }

    public function edit(Request $request, $id)
    {
        $model = CommonFloor::find($id);	
		if( empty($model) )
			$model = new CommonFloor();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = CommonFloor::find($id);	
		
        $input = $request->all();
		$model->update($input);
		CommonFloor::createLocation();
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return $this->index($request);
    }
  
    public function destroy(Request $request, $id)
    {		
		// find building, property id
		$data = CommonFloor::getPropertyBuilding($id);

        $model = CommonFloor::find($id);
		$model->delete();

		// delete location
		$loc_type = LocationType::createOrFind('Floor');
		
		Location::where('property_id', $data->property_id)
				->where('building_id', $data->bldg_id)
				->where('floor_id', $id)
				->where('type_id', $loc_type->id)
				->delete();

		// delete location group member
		DB::select('DELETE lgm 
					FROM services_location_group_members AS lgm 
					INNER JOIN services_location AS sl ON lgm.loc_id = sl.id 
					WHERE sl.property_id = ? 
					AND sl.building_id = ?
					AND sl.floor_id = ?
					AND sl.type_id = ?', [$data->property_id, $data->bldg_id, $id, $loc_type->id]);		

		return $this->index($request);
    }
	
	public function getList(Request $request)
	{
		$build_id = $request->get('build_id', '0');
		
		if( $build_id > 0 )
			$floor = CommonFloor::where('bldg_id', $build_id)->get();
		else
			$floor = CommonFloor::all();
		
		return Response::json($floor);
	}

}
