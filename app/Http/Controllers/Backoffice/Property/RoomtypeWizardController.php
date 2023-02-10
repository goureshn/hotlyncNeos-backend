<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;
use App\Models\Common\Building;
use App\Models\Common\RoomType;

use Excel;
use DB;
use Datatables;
use Response;


class RoomtypeWizardController extends UploadController
{
    public function getRoomTypeList(Request $request)
	{
		$property_id = $request->get('property_id', '0');

		$model = DB::table('common_room_type as rt')									
			->leftJoin('common_building as cb', 'rt.bldg_id', '=', 'cb.id')		
			->leftJoin('common_property as cp', 'cb.property_id', '=', 'cp.id')
			->where('cp.id', $property_id)
			->select('rt.*')
			->get();
	
		return Response::json($model);
	}

	public function showIndexPage($request, $model)
	{
		  // delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			DB::table('common_floor')->whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$query = RoomType::where('id', '>', '0');

		$pagesize = $request->input('pagesize');
		if( empty($pagesize) )
			$pagesize = 10;		
		
		$request->flashOnly('search'); 
		
		$building = Building::lists('name', 'id');		
		$datalist = $query->paginate($pagesize);
		
		//$mode = "read";
		$step = '4';
		return view('backoffice.wizard.property.roomtype', compact('datalist', 'model', 'pagesize', 'building', 'step'));				
	}	
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('common_room_type as rt')									
						->leftJoin('common_building as cb', 'rt.bldg_id', '=', 'cb.id')		
						->leftJoin('common_property as cp', 'cb.property_id', '=', 'cp.id')
						->select(['rt.*', 'cb.property_id', 'cb.name as cbname', 'cp.name as cpname']);		
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
			$model = new RoomType();
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
		$model = RoomType::create($input);
		
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
        $model = RoomType::find($id);	
		if( empty($model) )
			$model = new RoomType();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = RoomType::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = RoomType::find($id);
		$model->delete();

		if ($request->ajax()) 
			return Response::json($model);
		else	
			return $this->index($request);
    }	
	
	public function parseExcelFile($path)
	{
		Excel::selectSheets('Room Type')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					RoomType::create($data);
				}
			}							
		});
	}
}
