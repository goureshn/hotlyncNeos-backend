<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;
use App\Models\Common\CommonUser;
use App\Models\Service\TaskList;
use App\Models\Service\TaskGroupPivot;

use Excel;
use Response;
use DB;
use Datatables;

class TaskListController extends UploadController
{
   	public function index(Request $request)
    {
		$user_id = $request->user_id ?? 0;
		$filter = json_decode($request->filter ?? "", true);
		$client_id = $request->client_id ?? 0;

			if ($user_id > 0)
				$property_list = CommonUser::getPropertyIdsByJobroleids($user_id);
			else
				$property_list = CommonUser::getProertyIdsByClient($client_id);

		$datalist = DB::table('services_task_list as tl')
						->leftJoin('services_task_group_members as tgm', 'tgm.task_list_id', '=', 'tl.id')
						->leftJoin('services_task_group as tg', 'tgm.task_grp_id', '=', 'tg.id')
						->leftJoin('services_dept_function as df','tg.dept_function','=','df.id')
						->leftJoin('common_department as cd','df.dept_id','=','cd.id')
						// ->whereIn('cd.property_id', $property_list)
						->select(['tl.*', 'tg.name as tgname']);

		if (!empty($filter)) {
			if (!empty($filter["status"])) {
				$datalist->whereIn('tl.status', $filter["status"]);
			}
		}

		return Datatables::of($datalist)
					->addColumn('checkbox', function ($data) {
						return '<input type="checkbox" class="checkthis" />';
					})
					->addColumn('edit', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
					})
					->editColumn('chkstatus', function ($data) {
						if($data->status == 1){
							return "Active";
						}else{
							return 'Deactive';
						}
					})
					->addColumn('lang', function ($data) {

							$langs = json_decode($data->lang);
							// echo count($langs).' :Count';
							// foreach($langs as $key=>$val)
							// {
							// 	echo ($val->id);
							// 	if(!empty($val))
							// 	echo $key.' '.$val.'\n';
							// 	else
							// 	echo 'NULL';
							// }

							// $lang_ids=array_keys($langs);
							// $lang_val=array_values($langs);
							// $array = explode(',', $vip_id);
							$final_lang = '';
							$all_langs = DB::table('common_user_language as cul')->get();
							if(!empty($data->lang))
							{
								foreach ($all_langs as $key=>$val) {



								foreach($langs as $key1=>$val1) {
									if($val1->id==$val->id)
									$final_lang .= ''.$val->language .':' . $val1->text . ',' ;
								}
							}
						}
						else
						{
							foreach ($all_langs as $key=>$val) {




									$final_lang .= ''.$val->language .':' . ' '. ',' ;
							}

						}


						return $final_lang;
					})
					->addColumn('delete', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-disabled="job_role!=\'SuperAdmin\'" ng-click="onDeleteRow('.$data->id.')">
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
					})
					->rawColumns(['checkbox', 'edit', 'chkstatus', 'lang', 'delete'])
					->make(true);


    }

    public function create(Request $request)
    {
		$input = $request->all();

		$model = TaskList::create($input);

		return Response::json($model);
    }


    public function store(Request $request)
    {
		echo 'here';
    	$input = $request->except('id');
		$model = TaskList::create($input);
		return Response::json($model);
    }

    public function show($id)
    {
        $model = TaskList::find($id);

		$model->taskgroup;

		return Response::json($model);
    }

    public function edit(Request $request, $id)
    {

    }


    public function update(Request $request, $id)
    {
		$input = $request->all();

		$model = TaskList::find($id);
		if( empty($model) )
			return Response::json($model);

		$model->task = $request->get('tasklist_name', '0');
		$model->category_id = $request->get('category_id', '0');
		$model->cost = $request->get('cost', '0');
		$model->status = $request->get('status', '0');
		$model->lang = json_encode($request->get('lang', '0'));
		$model->save();

		TaskGroupPivot::where('task_list_id', $id)->delete();

		$pivot = new TaskGroupPivot();
		$pivot->task_grp_id = $request->get('taskgroup_id', '0');
		$pivot->task_list_id = $id;

		$pivot->save();

		return Response::json($model);
    }

    public function destroy(Request $request, $id)
    {
        $model = TaskList::find($id);
		$model->delete();
		return Response::json($model);
    }

	public function parseExcelFile($path)
	{
		Excel::selectSheets('tasklist')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					// $bldg_id = $data['bldg_id'];
					// $floor = $data['floor'];
					// if( Escalation::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
						// continue;
					TaskList::create($data);
				}
			}
		});
	}

	public function getTaskGorupList(Request $request) {
		$taskgroup = $request->get('taskgroup','');

		$model = DB::table('services_task_group')
			->whereRaw("name like '%".$taskgroup."%'")
			->get();

		return Response::json($model);
	}

	public function getEscalationGroup(Request $request) {
		$name = $request->get('name','');

		$model = DB::table('services_escalation_group')
			->whereRaw("name like '%".$name."%'")
			->get();

		return Response::json($model);
	}
	public function getCategoryName(Request $request) {
		$category_id = $request->get('category_id',0);
		$model = DB::table('services_task_category')
			->select(DB::raw('name'))
			->where('id',$category_id)
			->first();

		return Response::json($model);

	}
}
