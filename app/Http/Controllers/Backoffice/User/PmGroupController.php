<?php

namespace App\Http\Controllers\Backoffice\User;

use App\Models\Common\Permissions;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;

use App\Models\Common\Chain;
use App\Models\Common\PermissionGroup;
use App\Models\Common\Property;

use DB;
use Datatables;
use Response;

class PmGroupController extends UploadController
{
	public function showIndexPage($request, $model)
	{
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			DB::table('common_perm_group')->whereIn('id', $ids)->delete();
			return back()->withInput();
		}

		$query = PermissionGroup::where('id', '>', '0');

		$pagesize = $request->input('pagesize');
		if( empty($pagesize) )
			$pagesize = 10;

		$request->flashOnly('search');

		$datalist = $query->paginate($pagesize);

		//$mode = "read";
		$step = '1';
		return view('backoffice.wizard.user.pmgroup', compact('datalist', 'model', 'pagesize', 'step'));
	}
	public function index(Request $request)
	{
		if ($request->ajax()) {
			$datalist = DB::table('common_perm_group as pg')
					->leftJoin('common_property as cp', 'pg.property_id', '=', 'cp.id')
					->leftJoin('common_page_route as pr', 'pg.home_route_id', '=', 'pr.id')
					->select(['pg.*', 'cp.name as cpname', 'pr.name as prname', 'pr.description as prdescription']);
			return Datatables::of($datalist)
					->addColumn('checkbox', function ($data) {
						return '<input type="checkbox" class="checkthis" />';
					})
					->addColumn('edit', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="viewclass" ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
					})
					->addColumn('copy', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Copy"><button class="btn btn-success btn-xs" data-title="Copy" data-toggle="modal" data-target="#copyModal"  ng-disabled="viewclass" ng-click="onShowCopyRow('.$data->id.')">
							<span class="glyphicon glyphicon-copy"></span>
						</button></p>';
					})
					->addColumn('delete', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-disabled="viewclass" ng-click="onDeleteRow('.$data->id.')">
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
					})
					->rawColumns(['checkbox', 'edit', 'copy', 'delete'])
					->make(true);
		}
		else
		{
			$model = new PermissionGroup();

			return $this->showIndexPage($request, $model);
		}
	}


	public function create()
	{
		$model = new PermissionGroup();
		$property = Property::lists('name', 'id');
		$step = '1';

		return view('backoffice.wizard.user.pmgroupcreate', compact('model', 'property', 'step'));
	}

	public function store(Request $request)
	{
		$step = '1';

		$input = $request->except(['id', 'prname','prdescription']);
		if($input['name'] === null) $input['name'] = '';
		if($input['description'] === null) $input['description'] = '';

		$model = PermissionGroup::create($input);

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

	public function edit($id)
	{
		$model = PermissionGroup::find($id);
		if( empty($model) )
		{
			return back();
		}
		$property = Property::lists('name', 'id');
		$step = '1';

		return view('backoffice.wizard.user.usercreate', compact('model', 'property', 'step'));
	}

	public function update(Request $request, $id)
	{
		$model = PermissionGroup::find($id);

		$message = 'SUCCESS';

		if( empty($model) )
		{
			$message = "User does not exist.";
			return back()->with('error', $message)->withInput();
		}

		$input = $request->except(['id', 'prname', 'prdescription']);
		$model->update($input);

		if( empty($model) )
			$message = 'Internal Server error';

		if ($request->ajax())
			return Response::json($model);
		else
			return $this->index($request);
	}

	public function copyPmgroupMembers(Request $request)
	{
		$pmgroup_id = $request->id ?? 0;
		$property_id = $request->property_id ?? 0;
	
		$input = $request->except(['id', 'prname','tasks','prdescription']);
		if($input['description'] === null) $input['description'] = '';

		$model = PermissionGroup::create($input);

		$message = 'SUCCESS';
/*
		if( empty($model) )
			$message = 'Internal Server error';

		if ($request->ajax())
			return Response::json($model);
		else
			return back()->with('error', $message)->withInput();
*/
		$list_id = Permissions::where('perm_group_id', $pmgroup_id)->select('page_route_id')->get()->pluck('page_route_id');

		$selected_cond = DB::table('common_page_route as cpr')
				->leftJoin('common_module_property as cmp', 'cpr.module_id', '=', 'cmp.module_id')
				->where('cmp.property_id', $property_id)
				->whereIn('cpr.id', $list_id)				
				->get();
		$selected_all = DB::table('common_page_route')
			->where('module_id', 10000)
			->whereIn('id', $list_id)
			->orderBy('name')
			->get();

		$selected = array_merge($selected_cond->toArray(),$selected_all->toArray());

		$query= DB::table('common_perm_group');
		$max_query=clone $query;
		$id=$max_query->max('id');

		Permissions::where('perm_group_id', $id)->delete();

		foreach ($selected as $value) {
			$page_route_id=$value->id;
			DB::table('common_permission_members')->insert(['perm_group_id' => $id ,'page_route_id' => $page_route_id]);
		}

	return Response::json($model);

	}

	public function destroy(Request $request, $id)
	{
		$model = PermissionGroup::find($id);
		$model->delete();

		DB::table('common_permission_members')->where('perm_group_id', $id)->delete();

		if ($request->ajax())
			return Response::json($model);
		else
			return Redirect::to('/backoffice/user/wizard/pmgroup');
	}

	public function getPageList(Request $request)
	{
		$property_id = $request->get('property_id', 0);
		$pmgroup_id = $request->get('id', 0);

		$list_id = Permissions::where('perm_group_id', $pmgroup_id)->select('page_route_id')->get()->pluck('page_route_id');

		$unselected_cond = DB::table('common_page_route as cpr')
				->leftJoin('common_module_property as cmp', 'cpr.module_id', '=', 'cmp.module_id')
				->where('cmp.property_id', $property_id)
				->whereNotIn('cpr.id', $list_id)				
				->get();
		$unselected_all = DB::table('common_page_route')
			->where('module_id', 10000)
			->whereNotIn('id', $list_id)
			->orderBy('name')
			->get();
		$unselected = array_merge($unselected_cond->toArray(), $unselected_all->toArray());

		$selected_cond = DB::table('common_page_route as cpr')
				->leftJoin('common_module_property as cmp', 'cpr.module_id', '=', 'cmp.module_id')
				->where('cmp.property_id', $property_id)
				->whereIn('cpr.id', $list_id)				
				->get();
		$selected_all = DB::table('common_page_route')
			->where('module_id', 10000)
			->whereIn('id', $list_id)
			->orderBy('name')
			->get();

		$selected = array_merge($selected_cond->toArray(), $selected_all->toArray());

		$model = array();
		$model[] = $unselected;
		$model[] = $selected;

		return Response::json($model);
	}

	public function postPageList(Request $request)
	{
		$pmgroup_id = $request->get('pmgroup_id', '1');

		Permissions::where('perm_group_id', $pmgroup_id)->delete();

		$select_id = $request->get('select_id');

		for( $i = 0; $i < count($select_id); $i++ )
		{
			$page_route_id = $select_id[$i];

			$permission = new Permissions();

			$permission->perm_group_id = $pmgroup_id;
			$permission->page_route_id = $page_route_id;
			$permission->save();
		}

		echo "Permission Group has beed updated successfully";
	}


}
