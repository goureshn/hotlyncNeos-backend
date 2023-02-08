<?php

namespace App\Http\Controllers\Backoffice\Admin;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Common\Property;
use App\Models\Common\OutDoor;

use Excel;
use DB;
use Datatables;
use Response;

class OutdoorWizardController extends UploadController
{
	public function showIndexPage($request, $model)
	{
		  // delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			DB::table('common_Outdoor')->whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$query = OutDoor::where('id', '>', '0');
		/*
		$search = $request->input('search');
		if( !empty($search) )
		{
			$query->where(function($searchquery)
				{
					$search = '%' . $request->input('search') . '%';
					$searchquery->where('name', 'like', $search)
					 ->orWhere('description', 'like', $search);
				});	
		}
		*/
		$pagesize = $request->input('pagesize');
		if( empty($pagesize) )
			$pagesize = 10;		
		
		$request->flashOnly('search'); 
		
		$property = Property::lists('name', 'id');				
		$datalist = $query->orderby('name')->paginate($pagesize);
		
		//$mode = "read";
		$step = '3';
		return view('backoffice.wizard.admin.outdoor', compact('datalist', 'model', 'pagesize', 'property', 'step'));				
	}	
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('common_outdoor as co')									
						->leftJoin('common_property as cp', 'co.property_id', '=', 'cp.id')						
						->select(['co.*', 'cp.name as cpname']);		
						
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
			$model = new OutDoor();
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
		if($input['name'] === null) $input['name'] = '';
		if($input['description'] === null) $input['description'] = '';

		$model = OutDoor::create($input);
		
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
        $model = OutDoor::find($id);	
		if( empty($model) )
			$model = new OutDoor();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = OutDoor::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = OutDoor::find($id);
		$model->delete();

		return $this->index($request);
    }
	
	public function parseExcelFile($path)
	{
		Excel::selectSheets('Outdoor Area')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					//echo json_encode($data);
					
					$property_id = $data['property_id'];
					$name = $data['name'];
					if( OutDoor::where('property_id', $property_id)->where('name', $name)->exists() )
						continue;					
					OutDoor::create($data);
				}
			}							
		});
	}
}
