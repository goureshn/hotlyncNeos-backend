<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Common\Property;
use App\Models\Service\AlarmMember;
use App\Models\Service\AlarmGroup;

use Response;
use Datatables;
use DB;

class AlarmController extends Controller
{
   	public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('services_alarm_groups as ag')			
						->leftJoin('common_property as cp', 'ag.property', '=', 'cp.id')
						->select(['ag.*', 'cp.name as cpname']);
						
			return Datatables::of($datalist)
					->addColumn('checkbox', function ($data) {
						return '<input type="checkbox" class="checkthis" />';
					})
					->addColumn('enable', function ($data) {
						if($data->enable == 1) return 'Yes';
						else return 'No';					
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
					->rawColumns(['checkbox', 'enable', 'edit', 'delete'])				
					->make(true);
        }
		else
		{
			$propertylist = Property::lists('name', 'id');
		
			$property_id = 1;
			$property = Property::first();
			if( !empty($client) )
				$property_id = $client->id;			
			
			$step = '8';
			
			return view('backoffice.wizard.guestservice.alarm', compact('propertylist', 'property_id', 'step'));				
		}
    }

    public function create(Request $request)
    {
		$input = $request->all();
		
		$model = AlarmGroup::create($input);
		
		return Response::json($model);				
    }
	
	public function getGroupList(Request $request)
    {
		$property_id = $request->get('property_id', '0');
		
		$grouplist = AlarmGroup::where('property', $property_id)->get();
		
		return Response::json($grouplist);		
    }
	
	public function getUserList(Request $request)
	{
		$alarm_id = $request->get('alarm_id', '1');
		
		$list_id = AlarmMember::where('alarm_group', $alarm_id)->select('user_id')->pluck('user_id');

		$unselected_member = DB::table('common_user_group')
				->whereNotIn('id', $list_id)
				->select(DB::raw('*, name as wholename'))
				->get();
		$selected_member = DB::table('common_user_group')
				->whereIn('id', $list_id)
				->select(DB::raw('*, name as wholename'))
				->get();
		
		$model = array();
		$model[] = $unselected_member;
		$model[] = $selected_member;
		
		return Response::json($model);
	}
	

	public function createGroup(Request $request)
    {
		$input = $request->all();
		
		$model = AlarmGroup::create($input);
		
		return Response::json($model);		
    }
	
	
	public function postAlarm(Request $request)
	{
		$alarm_id = $request->get('alarm_id', '1');
		
		AlarmMember::where('alarm_group', $alarm_id)->delete();
		
		$select_id = $request->get('select_id');
		
		for( $i = 0; $i < count($select_id); $i++ )
		{
			$user_id = $select_id[$i];			
			
			$alarmgroupmember = new AlarmMember();
			
			$alarmgroupmember->alarm_group = $alarm_id;
			$alarmgroupmember->user_id = $user_id;
			$alarmgroupmember->save();
		}
		
		echo "Alarm has been updated successfully";		
	}
   
    public function store(Request $request)
    {
    	$input = $request->except('id');
		if($input['name'] === null) $input['name'] = '';
		if($input['description'] === null) $input['description'] = '';
		if($input['pref'] === null) $input['pref'] = '';

		$model = AlarmGroup::create($input);
		
		return Response::json($model);
    }

    public function show($id)
    {
        $model = AlarmGroup::find($id);	
		
		return Response::json($model);
    }

    public function edit(Request $request, $id)
    {
        
    }


    public function update(Request $request, $id)
    {
		$model = AlarmGroup::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		return Response::json($model);	
    }

    public function destroy(Request $request, $id)
    {
        $model = AlarmGroup::find($id);
		$model->delete();
		return Response::json($model);	
    }	
}
