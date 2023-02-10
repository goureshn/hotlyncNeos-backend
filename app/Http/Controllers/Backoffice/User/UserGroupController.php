<?php

namespace App\Http\Controllers\Backoffice\User;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;

use App\Models\Common\Chain;
use App\Models\Common\UserGroup;
use App\Models\Common\Property;
use App\Models\Common\PermissionGroup;

use Excel;
use DB;
use Datatables;
use Response;

class UserGroupController extends UploadController
{
    public function showIndexPage($request, $model)
	{
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			DB::table('common_user_group')->whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$query = UserGroup::where('id', '>', '0');

		$pagesize = $request->input('pagesize');
		if( empty($pagesize) )
			$pagesize = 10;		
		
		$request->flashOnly('search'); 
		
		$datalist = $query->paginate($pagesize);
		
		//$mode = "read";
		$step = '2';
		return view('backoffice.wizard.user.usergroup', compact('datalist', 'model', 'pagesize', 'step'));				
	}	
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('common_user_group as ug')									
						->leftJoin('common_property as cp', 'ug.property_id', '=', 'cp.id')			
						->select(['ug.*', 'cp.name as cpname']);		
			return Datatables::of($datalist)
						->addColumn('location_group', function ($data) {
						$location_group_id= $data->location_group;
						$array = explode(',', $location_group_id);
						$location_group = '';
						foreach ($array as $value) {
							$loc_group_data = DB::table('services_location_group as lg')
							// ->leftJoin('common_module as cm','cmp.module_id','=','cm.id')
							->where('lg.id', $value)
							->select(DB::raw('lg.*'))
							->get();
						
						for($j=0; $j < count($loc_group_data) ;$j++) {
							$location_group .= ''. $loc_group_data[$j]->name . ',' ;
						}
						}
						
						return $location_group;
					})
					->addColumn('vip', function ($data) {
						$vip_id = $data->vip;
						$array = explode(',', $vip_id);
						$vip = '';
						foreach ($array as $value) {

						$vip_data = DB::table('common_vip_codes as cvc')
							//->leftJoin('common_module as cm','cmp.module_id','=','cm.id')
							->where('cvc.id', $value)
							->select(DB::raw('cvc.*'))
							->get();
						
						for($j=0; $j < count($vip_data) ;$j++) {
							$vip .= ''. $vip_data[$j]->name . ',' ;
						}
					}
						return $vip;
					})
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
			$model = new UserGroup();
		
			return $this->showIndexPage($request, $model);
		}
    }

  
    public function create()
    {
		$model = new UserGroup();
		$property = Property::lists('name', 'id');
		$pmgroup = PermissionGroup::lists('name', 'id');
		$step = '2';		
		
		return view('backoffice.wizard.user.usergroupcreate', compact('model', 'property', 'pmgroup', 'step'));	
    }

    public function store(Request $request)
    {
        $step = '2';
	
		$input = $request->except(['id']);

		$model = UserGroup::create($input);
		
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
        $model = UserGroup::find($id);
		if( empty($model) )
		{
			return back();
		}
		$property = Property::lists('name', 'id');
		$step = '2';		
		
		return view('backoffice.wizard.user.usercreate', compact('model', 'property', 'step'));	
    }

    public function update(Request $request, $id)
    {
        $model = UserGroup::find($id);
		
		$message = 'SUCCESS';
		
		if( empty($model) )
		{
			$message = "User does not exist.";
			return back()->with('error', $message)->withInput();					
		}
		
        $input = $request->all();		
		$model->update($input);
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = UserGroup::find($id);
		$model->delete();

		if ($request->ajax()) 
			return Response::json($model);
		else	
			return Redirect::to('/backoffice/user/wizard/pmgroup');		
    }
	
	
}
