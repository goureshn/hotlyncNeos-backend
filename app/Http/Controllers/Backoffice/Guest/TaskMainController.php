<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Common\Building;

use App\Models\Service\TaskMain;
use App\Models\Service\TaskGroupPivot;
use App\Models\Common\PropertySetting;
use App\Models\Service\TaskList;

use Excel;
use Response;
use DB;
use Datatables;

class TaskMainController extends UploadController
{
   	public function index(Request $request)
    {		
		// $datalist = DB::table('services_task_main as tm')													
		// 				->select(['tm.*']);
		$datalist = DB::table('services_task_list_main as tm')
						->join('services_task_list as tlm', 'tlm.id','=','tm.task_list_id')													
						->select(['*']);

		// echo '<pre>';
		// echo $datalist;
		// die();
						
		return Datatables::of($datalist)	
					
					
					->addColumn('checkbox', function ($data) {
						return '<input type="checkbox" class="checkthis" />';
					})
					->addColumn('edit', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
					})				
					->addColumn('delete', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-click="onDeleteRow('.$data->id.')">
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
					})
					->rawColumns(['checkbox', 'edit', 'delete'])				
					->make(true);
	
					
		//ng-disabled="job_role!=\'SuperAdmin\'"
    }

    public function create(Request $request)
    {
		$service_task_list['task'] = $request['task'];
		$service_task_list['type'] = 9000;
		$service_task_list['lang'] = '0';
		$service_task_list['category_id'] = 0;
		$service_task_list['user_created'] = 0;
		$service_task_list['cost'] = 0;
		$service_task_list['status'] = 1;
		$service_task_list['created_by'] = 0;

		$service_task_list_model = TaskList::create($service_task_list);

		$service_task_group_member['task_list_id'] = $service_task_list_model['id'];
		$service_task_group_member['task_grp_id'] = $request['taskgroup_id'];
		
		$service_task_group_pivot = TaskGroupPivot::create([
			'task_list_id' => $service_task_group_member['task_list_id'],
			'task_grp_id' => $service_task_group_member['task_grp_id']
		]);

		$property_settings['property_id'] = 0;
		$data = DB::table('common_department')
            ->first();
        if (!empty($data)) $property_settings['property_id'] = $data->property_id;
		$property_settings['settings_key'] = $request['settings_key'];
		$property_settings['value'] = 9000;//master value
		$property_settings['comment'] = $request['comment'];

		$property_setting_model = PropertySetting::create($property_settings);

		$service_task_list_main['task_list_id'] = $service_task_list_model['id'];
		$service_task_list_main['property_setting_id'] = $property_setting_model['id'];

		$service_task_list_main_model = TaskMain::create($service_task_list_main);
		
		// $service_task_group_members['task_list_id'] = $service_task_list_model['id'];
		// $service_task_group_members['taskgroup_id'] = $request['taskgroup_id'];

		return Response::json($service_task_list_model);
		//$input = $request->all();
			
		//$model = TaskMain::create($input);

		//return Response::json($model);
    }
		
		
    public function store(Request $request)
    {

		$service_task_list['task'] = $request['task'];
		$service_task_list['type'] = 9000;
		$service_task_list['lang'] = '0';
		$service_task_list['category_id'] = 0;
		$service_task_list['user_created'] = 0;
		$service_task_list['cost'] = 0;
		$service_task_list['status'] = 1;
		$service_task_list['created_by'] = 0;

		$service_task_list_model = TaskList::create($service_task_list);

		$service_task_group_member['task_list_id'] = $service_task_list_model['id'];
		$service_task_group_member['task_grp_id'] = $request['taskgroup_id'];
		
		$service_task_group_pivot = TaskGroupPivot::create([
			'task_list_id' => $service_task_group_member['task_list_id'],
			'task_grp_id' => $service_task_group_member['task_grp_id']
		]);

		$property_settings['property_id'] = 0;
		$data = DB::table('common_department')
            ->first();
        if (!empty($data)) $property_settings['property_id'] = $data->property_id;
		$property_settings['settings_key'] = $request['settings_key'];
		$property_settings['value'] = 9000;//master value
		$property_settings['comment'] = $request['comment'];

		$property_setting_model = PropertySetting::create($property_settings);

		$service_task_list_main['task_list_id'] = $service_task_list_model['id'];
		$service_task_list_main['property_setting_id'] = $property_setting_model['id'];

		$service_task_list_main_model = TaskMain::create($service_task_list_main);
		
		// $service_task_group_members['task_list_id'] = $service_task_list_model['id'];
		// $service_task_group_members['taskgroup_id'] = $request['taskgroup_id'];

		return Response::json($service_task_list_model);
    	// $input = $request->except('id');
		// $model = TaskMain::create($input);
		// return Response::json($model);
    }

    public function show($id)
    {
		
        $model = TaskList::find($id);	
		$tm = DB::table('services_task_list_main')->where('task_list_id', $id)->first();
		$property_settings = DB::table('property_setting')->where('id', $tm->property_setting_id)->first();
		$model['settings_key'] = $property_settings->settings_key;
		$model['comment'] = $property_settings->comment;
		return Response::json($model);
    }

    public function edit(Request $request, $id)
    {
        
    }


    public function update(Request $request, $id)
    {
		$input = $request->all();
		$model = TaskList::find($id);	
		$tm = DB::table('services_task_list_main')->where('task_list_id', $id)->first();
		$taskmain = DB::table('services_task_list')->where('id', $id)->update([
			'task' => $input['task']
		]);
		$property_settings = DB::table('property_setting')->where('id', $tm->property_setting_id)->update([
			'settings_key' => $input['settings_key'] ?? "",
			'comment' => $input['comment'] ?? ""
		]);
		
		if(@$input['taskgroup_id']){
			$service_group_members = DB::table('services_task_group_members')->where('task_list_id', $id)->update([
				'task_grp_id' => $input['taskgroup_id']
			]);
		}
		
		return Response::json($model);		
        // $input = $request->all(); //comment, settings_key
		// $model->update($input);
		// return Response::json($input);
    }

    public function destroy(Request $request, $id)
    {
		$model = TaskList::find($id);
		$tm = DB::table('services_task_list_main')->where('task_list_id', $id)->first();
		$taskmain = DB::table('services_task_list_main')->where('task_list_id', $id)->delete();
		$property_settings = DB::table('property_setting')->where('id', $tm->property_setting_id)->delete();
		$service_group_members = DB::table('services_task_group_members')->where('task_list_id', $id)->delete();
		
		return Response::json($model);
    }
}
