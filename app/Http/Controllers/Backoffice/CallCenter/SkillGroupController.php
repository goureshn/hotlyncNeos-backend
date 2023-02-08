<?php

namespace App\Http\Controllers\Backoffice\CallCenter;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\CallCenter\IVRCallType;

use Yajra\Datatables\Datatables;

use DB;
use Response;

class SkillGroupController extends Controller
{
    public function index(Request $request)
    {
		$datalist = DB::table('ivr_call_center_skill_group')						
						->select(DB::raw('id, group_name,duration,email'));		
						
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
	
	/*
    public function store(Request $request)
    {
		$input = $request->except('id');
		
		$model = IVRCallType::create($input);
		
		return Response::json($model);			
    }

	public function show($id)
    {
        $model = IVRCallType::find($id);	
		
		return Response::json($model);
    }

  
    public function edit(Request $request, $id)
    {
		
    }
	*/

    public function update(Request $request, $id)
    {
		$input = $request->except(['id', 'skillgroup_id', 'group_name']);
		
	

			DB::table('ivr_call_center_skill_group')
				->where('id', $id)
				->update($input);
		
		return Response::json($input);			
    }

    public function destroy(Request $request, $id)
    {
		
        DB::table('ivr_call_center_skill_group')
				->where('id', $id)
				->update(['email' => '','duration' => '']);

		return $this->index($request);
    }
}
