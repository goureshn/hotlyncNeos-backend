<?php

namespace App\Http\Controllers\Intface;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Common\Property;
use App\Models\Intface\Channel;
use App\Models\Common\CommonUser;

use DB;
use Datatables;
use Response;

class ChannelController extends Controller
{	
    public function index(Request $request)
    {
		// $datalist = Channel::all();

		$user_id = $request->get('user_id', 0);
		$client_id = $request->get('client_id', 0);
		if ($user_id > 0)
			$property_list = CommonUser::getPropertyIdsByJobroleids($user_id);
		else
			$property_list = CommonUser::getProertyIdsByClient($client_id);
		
		$datalist = DB::connection('interface')->table('channel as cn')
					->leftJoin('protocol as cp', 'cn.protocol_id', '=', 'cp.id')
					->select(['cn.*', 'cp.name as cpname', 'cp.type as cptype']);
				
		$datalist->whereIn('cn.property_id', $property_list);
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
				->addColumn('property', function ($data) {
					$property_id = $data->property_id;
					$property = Property::find($property_id);
					return $property->name;
				})
				->addColumn('last_data', function ($data) {					
					return '<span id="lastdata'.$data->id.'">1 min</span>';
				})
				->addColumn('view_log', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="View Log"><button class="btn btn-info btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="job_role!=\'SuperAdmin\'&&edit_flag==0" ng-click="onShowEditRow('.$data->id.')">
						<span class="glyphicon glyphicon-stats"></span>
					</button></p>';
				})
				->addColumn('live_data', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Start"><button id="livedata'.$data->id.'"class="btn btn-success btn-xs" ng-click="onActiveConnection('.$data->id.')">
						<span class="fa fa-play"></span>
					</button></p>';
				})	
				->addColumn('conn_status', function ($data) {
					return '<div id="holder" class="status'.$data->id.'">
								<div class="dot" style="background-color:red;border:red"></div>
								<div class="pulse" style="background-color:red;border:red"></div>
							</div>';
				})
				->rawColumns(['checkbox', 'edit', 'delete', 'property', 'last_data', 'view_log', 'live_data', 'conn_status'])				
				->make(true);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
    	$input = $request->except('id');
		$model = Channel::create($input);
		
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
        $model = Channel::find($id);	
		if( empty($model) )
			$model = new Channel();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = Channel::find($id);	
		
        $input = $request->except('id');
		$model->update($input);
		
		return Response::json($model);
    }

    public function destroy(Request $request, $id)
    {
        $model = Channel::find($id);
		$model->delete();

		return $this->index($request);
    }

	public function getAcceptBuildingList(Request $request) {
		$id = $request->get('id', 0) ?? 0;
		$property_id = $request->get('property_id', 0) ?? 0;

		$channel = DB::connection('interface')->table('channel as cn')
				->where('id', $id)
				->first();

		$accept_build_ids = explode(",", $channel->accept_build_id);

		$selected = DB::table('common_building')
				->whereIn('id', $accept_build_ids)
				->where('property_id', $property_id)
				->get()
				->toArray();

		$unselected = DB::table('common_building')
				->whereNotIn('id', $accept_build_ids)
				->where('property_id', $property_id)
				->get()
				->toArray();

		$not_accept = ['id' => -1, 'name' => 'Not Accepted'];
		
		if ( in_array(-1, $accept_build_ids) )
			array_push($selected, $not_accept);
		else
			array_push($unselected, $not_accept);


		$model = array();
		$model[] = $unselected;
		$model[] = $selected;

		return Response::json($model);
	}

	public function postBuildingList(Request $request)
	{
		$id = $request->get('id', '1');
		$select_id = $request->get('select_id', "");

		DB::connection('interface')->table('channel as cn')
				->where('id', $id)
				->update(array('accept_build_id' => $select_id));

		echo "Accept Building list has beed updated successfully";
	}
}
