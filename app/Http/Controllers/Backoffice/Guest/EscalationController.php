<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Service\DeftFunction;
use App\Models\Service\Escalation;
use App\Models\Service\EscalationGroup;
use App\Models\Common\CommonJobrole;

use Excel;
use Response;
use DB;
use Datatables;

class EscalationController extends UploadController
{
   	public function showIndexPage($request, $model)
	{
		$deftlist = DeftFunction::lists('function', 'id');
		
		$deft_id = 1;
		$deft = DeftFunction::first();
		if( !empty($deft) )
			$deft_id = $deft->id;			
		
		$step = '2';
		return view('backoffice.wizard.guestservice.escalation', compact('deftlist', 'deft_id', 'step'));
	}	
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('services_escalation_group as eg')			
						->leftJoin('services_dept_function as sdf', 'eg.dept_func', '=', 'sdf.id')
						->select(['eg.*', 'sdf.function']);
						
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
			$model = new Escalation();
			return $this->showIndexPage($request, $model);	
		}
		
    }

    public function create()
    {
		$step = '0';
        $model = new Escalation();
		$department = Department::lists('department', 'id');
		
		return view('backoffice.wizard.guestservice.deftfunccreate', compact('model', 'pagesize', 'department', 'step'));
    }

	public function createGroup(Request $request)
    {
		$input = $request->all();
		
		$model = EscalationGroup::create($input);
		
		return Response::json($model);		
    }
	
	public function selectGroup(Request $request)
    {
		$esgroup_id = $request->get('esgroup_id', '1');
		
		$job_role_id = Escalation::where('escalation_group', $esgroup_id)->select('job_role_id')->get()->pluck('job_role_id');
		
		$unselected_jobrole = CommonJobrole::whereNotIn('id', $job_role_id)
									->orderBy('job_role')
									->get();
		
		$selected_jobrole = DB::table('services_escalation as se')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'se.job_role_id')
			->where('se.escalation_group', $esgroup_id)
			->where('se.level', '>', 0)
			->select('se.*', 'jr.job_role')
			->orderBy('se.level')
			->get();
		
		// $selected_member = CommonUser::whereIn('id', $userlist_id)->get();

		$model = array();
		$model[] = $unselected_jobrole;
		$model[] = $selected_jobrole;
		
		return Response::json($model);		
	}
	
	public function updateEscalationInfo(Request $request)
	{
		$id = $request->get('id', 0);
		$job_role_id = $request->get('job_role_id', 0);
		$level = $request->get('level', 0);
		$max_time = $request->get('max_time', 0);
		$device_type = $request->get('device_type', 0);
		$notify_type = $request->get('notify_type', 0);
		$escalation_group = $request->get('escalation_group', 0);

		$model = Escalation::find($id);

		if( empty($model) )
		{
			$model = new Escalation();
			$model->escalation_group = $escalation_group;
		}

		$model->job_role_id = $job_role_id;
		$model->level = $level;
		$model->max_time = $max_time;
		$model->device_type = $device_type;
		$model->notify_type = $notify_type;

		$model->save();
		
		$selected_level = DB::table('services_escalation as se')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'se.job_role_id')
			->where('se.escalation_group', $escalation_group)
			->where('se.level', '>', 0)
			->select('se.*', 'jr.job_role')
			->orderBy('se.level')
			->get();
		
		return Response::json($selected_level);	
	}

	public function deleteEscalationInfo(Request $request)
	{
		$id = $request->get('id', 0);
		$escalation_group = $request->get('escalation_group', 0);
		
		$model = Escalation::find($id);

		if( !empty($model) )
		{
			$escalation_group = $model->escalation_group;			
			$level = $model->level;
		
			$model->delete();	

			$sql = sprintf('UPDATE `services_escalation` SET `level` = `level` - 1 WHERE `escalation_group` = %d AND `level` > %d',
							$escalation_group, $level);

			DB::select($sql);	
		}

		$selected_level = DB::table('services_escalation as se')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'se.job_role_id')
			->where('se.escalation_group', $escalation_group)
			->where('se.level', '>', 0)
			->select('se.*', 'jr.job_role')
			->orderBy('se.level')
			->get();
		
		return Response::json($selected_level);		
	}
	
	public function postEscalation(Request $request)
	{
		$esgroup_id = $request->get('esgroup_id', '1');
		
		Escalation::where('escalation_group', $esgroup_id)->delete();
		
		$select_id = $request->get('select_id');
		$max_time = $request->get('max_time');
		for( $i = 0; $i < count($select_id); $i++ )
		{
			$job_role_id = $select_id[$i];
			$time = $max_time[$job_role_id];
			$escalation = new Escalation();
			
			$escalation->job_role_id = $job_role_id;
			$escalation->escalation_group = $esgroup_id;
			$escalation->level = $i;
			$escalation->max_time = $time;
			$escalation->save();
		}
		
		echo "Escalation group has beed added successfully";		
	}
	
    public function store(Request $request)
    {
    	$input = $request->except('id');
		$model = EscalationGroup::create($input);
		
		return Response::json($model);			
    }

    public function show($id)
    {
        $model = EscalationGroup::find($id);	
		
		return Response::json($model);
    }
    
    public function edit(Request $request, $id)
    {
        $model = Escalation::find($id);	
		if( empty($model) )
			$model = new Escalation();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = EscalationGroup::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		return Response::json($model);	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $model = EscalationGroup::find($id);
		$model->delete();

		return $this->index($request);
    }
	
	public function parseExcelFile($path)
	{
		Excel::selectSheets('escalationgroup')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					//echo json_encode($data);
					
					// $bldg_id = $data['bldg_id'];
					// $floor = $data['floor'];
					// if( Escalation::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
						// continue;					
					EscalationGroup::create($data);
				}
			}							
		});
		
		Excel::selectSheets('escalation')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					//echo json_encode($data);
					
					// $bldg_id = $data['bldg_id'];
					// $floor = $data['floor'];
					// if( Escalation::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
						// continue;					
					Escalation::create($data);
				}
			}							
		});
	}

	public function deptFuncList(Request $request) {
		$deptfunc = $request->get('deptfunc','');

		$model = DB::table('services_dept_function')
			->whereRaw("`function` like '%".$deptfunc."%'")
			->get();

		return Response::json($model);
	}
	public function userGroupList(Request $request) {
		$usergroup = $request->get('usergroup','');

		$model = DB::table('common_user_group')
			->whereRaw("name like '%".$usergroup."%'")
			->get();

		return Response::json($model);
	}

	/* React Functions */

	public function escalationIndex(Request $request)
	{
		$platform = $request->get('platform');
		$limit = $request->get('limit', 0);
		$offset = $request->get('offset', 0);
		$search = $request->get('searchtext', "");
		$sortColumn = $request->get('sortcolumn', 'se.id');
		$sortOrder = $request->get('sortorder', 'desc');
		$filter = json_decode($request->get("filter", ""), true);

		$datalist = DB::table('services_escalation as se')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'se.job_role_id')
			->leftJoin('services_escalation_group as seg', 'seg.id', '=', 'se.escalation_group')
			//->where('se.escalation_group', $esgroup_id)
			->where('se.level', '>', 0)
			->select('se.*', 'seg.name as escalation_group_name', 'jr.job_role');
		// ->orderBy('se.id');

		if (!empty($filter)) {
			if (!empty($filter["device_type"])) {
				$datalist->whereIn('se.device_type', $filter["device_type"]);
			}
			if (!empty($filter["notify_type"])) {
				$datalist->whereIn('se.notify_type', $filter["notify_type"]);
			}

			if (isset($filter["max_time"])) {
				if ($filter["max_time"] == "0")
					$datalist->whereBetween('se.max_time', [0, 1800]);
				if ($filter["max_time"] == "1")
					$datalist->whereBetween('se.max_time', [1800, 3600]);
			}
		}

		if (!empty($search)) {
			$datalist->where('se.id', 'like', '%' . $search . '%')
				->orWhere('seg.name', 'like', '%' . $search . '%')
				->orWhere('jr.job_role', 'like', '%' . $search . '%')
				->orWhere('se.notify_type', 'like', '%' . $search . '%');
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

		$response = $datalist->get();

		return Response::json(["data" => $response, "recordsFiltered" => $total]);
	}
	
	public function escalationgroupindex(Request $request)
	{
		$platform = $request->get('platform');
		$limit = $request->get('limit', 0);
		$offset = $request->get('offset', 0);
		$search = $request->get('searchtext', "");
		$sortColumn = $request->get('sortcolumn', 'eg.id');
		$sortOrder = $request->get('sortorder', 'desc');
		// $filter = json_decode($request->get("filter", ""), true);

		$datalist = DB::table('services_escalation_group as eg')
			->leftJoin('services_dept_function as sdf', 'eg.dept_func', '=', 'sdf.id')
			->select("eg.*", "sdf.function");

		if (!empty($search)) {
			$datalist->where('eg.id', 'like', '%' . $search . '%')
				->orWhere('eg.name', 'like', '%' . $search . '%')
				->orWhere('sdf.function', 'like', '%' . $search . '%');
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

		$response = $datalist->get();
		return Response::json(["data" => $response, "recordsFiltered" => $total]);
	}

	public function groupList()
	{
		return EscalationGroup::get();
	}

	/* React Function Ends */
}
