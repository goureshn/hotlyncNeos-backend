<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Common\Property;
use App\Models\Common\Building;
use App\Models\Service\Location;
use App\Models\Service\LocationType;


use DB;
use Datatables;
use Response;

class BuildingWizardController extends Controller
{
	public function showIndexPage($request, $model)
	{
		  // delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			DB::table('common_building')->whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$query = Building::where('id', '>', '0');
		
		$pagesize = $request->input('pagesize');
		if( empty($pagesize) )
			$pagesize = 10;		
		
		$request->flashOnly('search'); 
		
		$property = Property::lists('name', 'id');				
		$datalist = $query->orderby('name')->paginate($pagesize);
		
		//$mode = "read";
		$step = '2';
		return view('backoffice.wizard.property.building', compact('datalist', 'model', 'pagesize', 'property', 'step'));				
	}	
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('common_building as cb')									
						->leftJoin('common_property as cp', 'cb.property_id', '=', 'cp.id')		
						->select(['cb.*', 'cp.name as cpname']);		
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
		else
		{
			$model = new Building();
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
		$model = Building::create($input);
		Building::createLocation();	
		
		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return back()->with('error', $message)->withInput();	
    }

	function createData(Request $request)
	{
		$input = $request->except(['id']);
			
		try {			
			$model = Building::create($input);	
			Building::createLocation();	
		} catch(PDOException $e){
		   return Response::json([
				'success' => false,
				'message' => 'Hello'
				], 422);
		}	
		
		return Response::json($model);			
	}
	
	public function getBuildingList(Request $request)
	{
		$property_id = $request->get('property_id', '0');
        $name = $request->get('name', '');
		
		if( $property_id > 0 )
		{
			$model = DB::table('common_building')
                ->where('property_id', $property_id)
                ->where('name','LIKE',  "%".$name."%")
                ->get();
		}
		else
		{
			$model = DB::table('common_building')->where('name','LIKE', "%".$name."%")->get();
		}
		
		return Response::json($model);
	}

	public function getBuildingSomeList(Request $request)
	{
		$building_ids = $request->get('building_ids', '');
        $name = $request->get('name', '');
		
		if( !empty($building_ids) )
		{
			$model = DB::table('common_building')
				->whereRaw("FIND_IN_SET(id, '$building_ids')")				
                ->where('name','LIKE',  "%".$name."%")
                ->get();
		}
		else
		{
			$model = DB::table('common_building')->where('name','LIKE', "%".$name."%")->get();
		}
		
		return Response::json($model);
	}
  
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $model = Building::find($id);	
		if( empty($model) )
			$model = new Building();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = Building::find($id);	
		
        $input = $request->except('id');
		$model->update($input);
		Building::createLocation();
		
		return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = Building::find($id);
		$model->delete();

		// delete location
		$loc_type = LocationType::createOrFind('Building');
		Location::where('property_id', $model->property_id)
				->where('building_id', $id)
				->where('type_id', $loc_type->id)
				->delete();

		// delete location group member
		DB::select('DELETE lgm 
					FROM services_location_group_members AS lgm 
					INNER JOIN services_location AS sl ON lgm.loc_id = sl.id 
					WHERE sl.property_id = ? 
					AND sl.building_id = ?
					AND sl.type_id = ?', [$model->property_id, $id, $loc_type->id]);				

		return $this->index($request);
    }
}
