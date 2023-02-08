<?php

namespace App\Http\Controllers\Backoffice\User;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Common\Property;
use App\Models\Common\Permissions;

use DB;
use Datatables;
use Response;

class PermissionController extends Controller
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

		$query = Permissions::where('id', '>', '0');

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
			$datalist = DB::table('common_permission_members as pm')
					->leftJoin('common_perm_group as pg', 'pm.perm_group_id', '=', 'pg.id')
					->leftJoin('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
					->select(['pm.*', 'pg.name as pgname', 'pr.name as prname']);
			return Datatables::of($datalist)
					->addColumn('checkbox', function ($data) {
						return '<input type="checkbox" class="checkthis" />';
					})
					->addColumn('edit', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="viewclass" ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
					})
					->addColumn('delete', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-disabled="viewclass" ng-click="onDeleteRow('.$data->id.')">
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
					})
					->make(true);
		}
		else
		{
			$model = new Permissions();
			return $this->showIndexPage($request, $model);
		}
	}


	public function create()
	{
		//
	}

	public function store(Request $request)
	{
		$input = $request->except(['id','prname']);
		$prname = $request->get('prname', '');
		$model = Permissions::create($input);
		 
		$query = DB::table('common_permission_members');

		$page_id = DB::table('common_page_route as cpr')
					->select(DB::raw('cpr.id'))
					->where('name','like', $prname)
					->first();
		

		$max_query=clone $query;
		$id=$max_query->max('id');

		$upd_list = $query
					->where('id', $id)
					->update(['page_route_id'=>$page_id->id]);
		

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
			$model = Permissions::create($input);
		} catch(PDOException $e){
			return Response::json([
					'success' => false,
					'message' => 'Hello'
			], 422);
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
		$model = Permissions::find($id);
		if( empty($model) )
			$model = new Permissions();

		return $this->showIndexPage($request, $model);
	}

	public function update(Request $request, $id)
	{
		$model = Permissions::find($id);

		$input = $request->except(['id','prname']);
		$prname = $request->get('prname', '');
		$model->update($input);

		$query = DB::table('common_permission_members');

		$page_id = DB::table('common_page_route as cpr')
					->select(DB::raw('cpr.id'))
					->where('name','like', $prname)
					->first();

		$upd_list = $query
					->where('id', $id)
					->update(['page_route_id'=>$page_id->id]);


		return $this->index($request);
	}

	public function destroy(Request $request, $id)
	{
		$model = Permissions::find($id);
		$model->delete();

		return $this->index($request);
	}
}
