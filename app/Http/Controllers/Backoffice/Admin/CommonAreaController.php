<?php

namespace App\Http\Controllers\Backoffice\Admin;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use App\Models\Common\CommonArea;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;

use Excel;
use DB;
use Datatables;
use Response;

class CommonAreaController extends UploadController
{
    public function showIndexPage($request, $model)
	{
		  // delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			DB::table('common_cmn_area')->whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$query = CommonArea::where('id', '>', '0');

		$pagesize = $request->input('pagesize');
		if( empty($pagesize) )
			$pagesize = 10;		
		
		$request->flashOnly('search'); 
		
		$build_id = $request->get('build_id', '1');
		$building = Building::lists('name', 'id');
		$floor = CommonFloor::where('bldg_id', $build_id)->get()->pluck('floor', 'id');
	
		$datalist = $query->paginate($pagesize);
		
		$step = '1';
		return view('backoffice.wizard.admin.common', compact('datalist', 'model', 'pagesize', 'building', 'build_id', 'floor', 'step'));				
	}	
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('common_cmn_area as ca')									
						->leftJoin('common_floor as cf', 'ca.floor_id', '=', 'cf.id')						
						->leftJoin('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
						->leftJoin('common_property as cp', 'cb.property_id', '=', 'cp.id')
						->select(['ca.*', 'cf.floor', 'cb.name as cbname', 'cp.name as cpname']);
						
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
			$model = new CommonArea();
			return $this->showIndexPage($request, $model);
		}
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
		$message = 'SUCCESS';	
		
    	if( CommonArea::where('floor_id',  $request->floor_id ?? '')->where('name', $request->name ?? '')->exists() )
			$message = 'name is duplicated';		
		else
		{
			$input = $request->except('id');
			if($input['name'] === null) $input['name'] = '';
			if($input['description'] === null) $input['description'] = '';
			
			$model = CommonArea::create($input);
		}
		
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
        $model = CommonArea::find($id);	
		if( empty($model) )
			$model = new CommonArea();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$input = $request->all();
		
		$message = 'SUCCESS';
		if( CommonArea::where('floor_id',  $request->get('floor_id', ''))->where('name', $request->get('name', ''))->exists() )
			$message = 'name is duplicated';	
		else
		{
			$model = CommonArea::find($id);	
			if( empty($model) )
				$message = 'Internal Server error';		
			else
				$model->update($input);	
		}
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return $this->index($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $model = CommonArea::find($id);
		$model->delete();

		return $this->index($request);
    }	
	
	public function parseExcelFile($path)
	{
		Excel::selectSheets('Common Area')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					//echo json_encode($data);
					
					$floor_id = $data['floor_id'];
					$name = $data['name'];
					if( CommonArea::where('floor_id', $floor_id)->where('name', $name)->exists() )
						continue;					
					CommonArea::create($data);
				}
			}							
		});
	}
	
}
