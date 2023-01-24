<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;
use App\Models\Service\DeftFunction;
use App\Models\Service\Device;

use DB;
use Datatables;
use Response;
use Excel;

class DeviceController extends UploadController
{
   	public function index(Request $request)
    {
		$device_status = $request->get('status', 'All');
		$datalist = DB::table('services_devices as sd')
						->leftJoin('common_users as cu', 'sd.device_id', '=', 'cu.device_id')
						->select(DB::Raw('sd.*, CASE WHEN cu.active_status = 1 THEN "Active" ELSE "Offline" END as device_status'));

		if($device_status == 'Online')
			$datalist->where('cu.active_status', 1);

		if($device_status == 'Offline')
			$datalist->whereRaw('(cu.active_status = 0 OR cu.active_status is NULL)');

		if($device_status == 'Disabled')
			$datalist->where('sd.enabled', 0);

		$datalist->groupBy('sd.id')	;

		return Datatables::of($datalist)
				->addColumn('function', function ($data) {
					$ids = $data->dept_func_array_id;
					$list = DB::table('services_dept_function')
						->whereRaw("FIND_IN_SET(id, '$ids')")
						->select(DB::raw('GROUP_CONCAT(`function`) as field'))
						->first();

					return $list->field;
				})
				->addColumn('sec_function', function ($data) {
					$ids = $data->sec_dept_func;
					$list = DB::table('services_dept_function')
						->whereRaw("FIND_IN_SET(id, '$ids')")
						->select(DB::raw('GROUP_CONCAT(`function`) as field'))
						->first();

					return $list->field;
				})
				->addColumn('loc_name', function ($data) {
					$ids = $data->loc_grp_array_id;
					$list = DB::table('services_location_group')
						->whereRaw("FIND_IN_SET(id, '$ids')")
						->select(DB::raw('GROUP_CONCAT(name) as field'))
						->first();

					return $list->field;
				})
				->addColumn('sec_loc_name', function ($data) {
					$ids = $data->sec_loc_grp_id;
					$list = DB::table('services_location_group')
						->whereRaw("FIND_IN_SET(id, '$ids')")
						->select(DB::raw('GROUP_CONCAT(name) as field'))
						->first();

					return $list->field;
				})
				->addColumn('cb_name', function ($data) {
						$ids = $data->building_ids;
						$list = DB::table('common_building')
							->whereRaw("FIND_IN_SET(id, '$ids')")
							->select(DB::raw('GROUP_CONCAT(name) as field'))
							->first();

						return $list->field;
				})
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
		$step = '6';

		$deftlist = DeftFunction::lists('`function`', 'id');
		$model = new Device();

		return view('backoffice.wizard.guestservice.devicecreate', compact('model', 'deftlist', 'step'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    	$model = new Device();

		$model->dept_func = $request->get('dept_id', '0');

		$typelist = $model->getTypeList();
		$type = $typelist[$request->get('type_id', '1')];
		$model->type = $type;
		$model->number = $request->get('number', '');
		$model->save();

		return $this->create();
    }

	public function storeng(Request $request)
    {
    	$input = $request->except('id');

		$model = Device::create($input);

		return Response::json($model);
    }

    public function show($id)
    {
        $model = Device::find($id);

		return Response::json($model);
    }

    public function edit(Request $request, $id)
    {
        $model = Device::find($id);
		if( empty($model) )
			$model = new Device();

		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
     //   die('ddddddddddddd');
		$model = Device::find($id);

        $input = $request->all();
		$model->update($input);

		return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = Device::find($id);
		$model->delete();

		return Response::json($model);
	}

	public function checkPrimaryDeptFunc(Request $request)
	{
		$dept_func_array_id = $request->get('dept_func_array_id', []);

		$roster_setting_count = DB::table('services_dept_function')
			->whereIn('id', $dept_func_array_id)
			->where('gs_device', 2)
			->count();

		$ret= array();
		$ret['code'] = 200;
		$ret['roster_setting_count'] = $roster_setting_count;

		return Response::json($ret);
	}

	public function getDeviceList(Request $request)
	{
		$val = $request->get('val', '');

		$filter = '%%' . $val . '%%';

		$ret = DB::table('services_devices as sd')
			->where('sd.name', 'like', $filter)
			->select(DB::raw('sd.*'))
			->get();

		return Response::json($ret);
	}

	public function parseExcelFile($path)
	{
		Excel::selectSheets('device')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					// $bldg_id = $data['bldg_id'];
					// $floor = $data['floor'];
					// if( Escalation::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
						// continue;
					Device::create($data);
				}
			}
		});
	}
}
