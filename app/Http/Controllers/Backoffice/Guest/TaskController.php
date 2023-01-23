<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;
use App\Models\Service\TaskGroup;
use App\Models\Service\TaskList;
use App\Models\Service\DeftFunction;
use App\Models\Service\EscalationGroup;
use App\Models\Service\TaskGroupPivot;

use Excel;
use Response;
use DB;
use Datatables;

class TaskController extends UploadController
{
   	public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('services_task_group as tg')
						->leftJoin('services_dept_function as df', 'tg.dept_function', '=', 'df.id')
						->leftJoin('common_user_group as cug', 'tg.user_group', '=', 'cug.id')
						->select(DB::Raw('tg.*, df.function, cug.name as ugname'));

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
		else
		{
			$taskgrouplist = TaskGroup::lists('name', 'id');
			$deftlist = DeftFunction::lists('function', 'id');
			$escplist = EscalationGroup::lists('name', 'id');

			$taskgroup_id = 1;
			$taskgroup = TaskGroup::first();
			if( !empty($taskgroup) )
				$taskgroup_id = $taskgroup->id;

			$step = '3';

			return view('backoffice.wizard.guestservice.task', compact('taskgrouplist', 'deftlist', 'escplist', 'taskgroup_id', 'step'));
		}
    }

    public function create(Request $request)
    {
		$input = $request->all();

		$model = TaskGroup::create($input);

		return Response::json($model);
    }

	public function getTaskList(Request $request)
    {
		$taskgroup_id = $request->get('taskgroup_id', '1');

		$taskgroup = TaskGroup::where('id', $taskgroup_id)->first();

		if( empty($taskgroup) )
		{
			return Response::json(array());
		}

		$tasklist = $taskgroup->tasklist;
		return Response::json($tasklist);
    }

	public function createTaskList(Request $request)
    {
		$input = $request->all();

		$model = new TaskList();

		$model->task = $request->get('tasklist_name', '0');
		$model->category_id = $request->get('category_id', '0');
		$model->cost = $request->get('cost', '0');
		$model->status = $request->get('status', '0');
		$model->lang = json_encode($request->get('lang', '0'));
		$model->save();

		$tasklist_id = $model->id;

		$pivot = new TaskGroupPivot();
		$pivot->task_grp_id = $request->get('taskgroup_id', '0');
		$pivot->task_list_id = $tasklist_id;

		$pivot->save();

		return Response::json($model);
	}

	public function getEscalationGroup($dept_function, $escalation_group)
	{
		if( $escalation_group > 0 )
			return $escalation_group;

		$task_group = DB::table('services_task_group')
			->where('dept_function', $dept_function)
			->first();
		if( !empty($task_group) )
			return $task_group->escalation_group;

		return 0;
	}

	public function updateEscalationGroup($dept_function, $escalation_group)
	{
		// check only 1 escalation group can be added for a department Function
		$data = DB::table('services_task_group')
					->where('dept_function', $dept_function)
					->update(['escalation_group' => $escalation_group]);
	}

	public function store(Request $request)
    {
		$input = $request->except('id');

		$model = TaskGroup::create($input);

		return Response::json($model);
    }

    public function show($id)
    {
        $model = TaskGroup::find($id);

		return Response::json($model);
    }

    public function edit(Request $request, $id)
    {
		$model = TaskGroup::find($id);
		return Response::json($model);
    }

    public function update(Request $request, $id)
    {
		$model = TaskGroup::find($id);
        $input = $request->all();
		$model->update($input);

		return Response::json($model);
    }

    public function destroy(Request $request, $id)
    {
        $model = TaskGroup::find($id);
		$model->delete();
		return Response::json($model);
	}



	public function parseExcelFile($path)
	{
		Excel::selectSheets('taskgroup')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					// $bldg_id = $data['bldg_id'];
					// $floor = $data['floor'];
					// if( Escalation::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
						// continue;
					TaskGroup::create($data);
				}
			}
		});
	}

	/* React functions */

	public function taskIndex(Request $request)
	{

		$platform = $request->get('platform', '');
		$user_id = $request->get('user_id', 0);

		$limit = $request->get('limit', 0);
		$offset = $request->get('offset', 0);
		$search = $request->get('searchtext', "");
		$sortColumn = $request->get('sortcolumn', 'tg.id');
		$sortOrder = $request->get('sortorder', 'desc');
		$filter = json_decode($request->get("filter", ""), true);

		$datalist = DB::table('services_task_group as tg')
			->leftJoin('services_dept_function as df', 'tg.dept_function', '=', 'df.id')
			->leftJoin('common_user_group as cug', 'tg.user_group', '=', 'cug.id')
			->select(DB::Raw('tg.*, df.function, cug.name as ugname'));

		if (!empty($filter)) {
			if (!empty($filter["escalation"])) {
				$datalist->whereIn('tg.escalation', $filter["escalation"]);
			}

			if (!empty($filter["by_guest_flag"])) {
				$datalist->whereIn('tg.by_guest_flag', $filter["by_guest_flag"]);
			}

			if ($filter["duration"] == "0")
				$datalist->whereBetween('tg.max_time', [0, 1800]);
			if ($filter["duration"] == "1")
				$datalist->whereBetween('tg.max_time', [1800, 3600]);
		}

		if (!empty($search)) {
			$datalist->where('tg.id', 'like', '%' . $search . '%')->orWhere('df.function', 'like', '%' . $search . '%')->orWhere('tg.name', 'like', '%' . $search . '%')->orWhere('cug.name', 'like', '%' . $search . '%');
		}

		if (!empty($sortColumn) && !empty($sortOrder)) {
			$datalist->orderBy($sortColumn, $sortOrder);
		}

		$total = count($datalist->get());

		if ($limit != 0) {
			$datalist->take($limit);
		}
		if ($offset != 0) {
			$datalist->skip($offset);
		}

		$service_task_groups = $datalist->get();

		// $taskgrouplist = TaskGroup::lists('name', 'id');
		// $deftlist = DeftFunction::lists('function', 'id');
		// $escplist = EscalationGroup::lists('name', 'id');

		// $taskgroup_id = 1;
		// $taskgroup = TaskGroup::first();
		// if (!empty($taskgroup))
		// 	$taskgroup_id = $taskgroup->id;

		// $response = [$service_task_groups, $taskgrouplist, $deftlist, $escplist, $taskgroup_id];
		// $response = [$service_task_groups, $taskgrouplist, $deftlist, $escplist, $total];
		return Response::json(["data" => $service_task_groups, "recordsFiltered" => $total]);
	}

	/* React functions Ends */
}
