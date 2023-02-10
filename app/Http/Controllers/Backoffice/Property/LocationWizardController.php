<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;
use App\Models\Common\Property;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;
use App\Models\Common\Room;
use App\Models\Service\Location;
use App\Models\Service\LocationType;


use DB;
use Datatables;
use Response;

class LocationWizardController extends UploadController
{
   	public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('services_location as sl')
						->leftJoin('services_location_type as lt', 'sl.type_id', '=', 'lt.id')
						->leftJoin('common_room as cr', 'sl.room_id', '=', 'cr.id')
						->leftJoin('common_floor as cf', 'sl.floor_id', '=', 'cf.id')
						->leftJoin('common_building as cb', 'sl.building_id', '=', 'cb.id')
						->leftJoin('common_property as cp', 'sl.property_id', '=', 'cp.id')
						->orderBy('sl.id')
						->select(['sl.*', 'lt.type', 'cr.room', 'cf.floor', 'cb.name as cbname', 'cp.name as cpname']);
			return Datatables::of($datalist)
					->addColumn('checkbox', function ($data) {
						return '<input type="checkbox" class="checkthis" />';
					})
					->addColumn('lenable', function ($data) {
						if($data->disable == 1) return 'Yes';
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
					->rawColumns(['checkbox', 'lenable', 'edit', 'delete'])
					->make(true);
        }
		else
		{
			$model = new CommonFloor();
			return $this->showIndexPage($request, $model);
		}
    }

    public function create()
    {
        //
	}

	private function updateLocation($type, $model)
	{
		switch($type)
		{
			case 'Property':
				break;
			case 'Building':
				break;
			case 'Floor':
				$building_id = $model->building_id;
				if( $building_id > 0 )
				{
					$floor = CommonFloor::where('bldg_id', $building_id)
						->where('floor', $model->name)
						->first();

					if( empty($floor) )
					{
						$floor = new CommonFloor();
						$floor->bldg_id = $building_id;
						$floor->floor = $model->name;
					}

					$floor->description = $model->desc;

					$floor->save();

					$model->floor_id = $floor->id;
					$model->save();
				}

				break;
			case 'Room':
				$floor_id = $model->floor_id;
				if( $floor_id > 0 )
				{
					$room = Room::where('flr_id', $floor_id)
						->where('room', $model->name)
						->first();

					if( empty($room) )
					{
						$room = new Room();
						$room->flr_id = $floor_id;
						$room->room = $model->name;
						$room->type_id = 2;
					}

					$room->description = $model->desc;

					$room->save();

					$model->room_id = $room->id;
					$model->save();
				}

				break;
		}
	}

    public function store(Request $request)
    {
		$input = $request->except(['id', 'type', 'floor', 'room']);

		$type = $request->get('type', '');
		if( $type == '' )
			return Response::json($input);

		$type_model = LocationType::createOrFind($type);
		$input['type_id'] = $type_model->id;

		$model = Location::create($input);

		$this->updateLocation($type, $model);

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
        $model = Location::find($id);
		if( empty($model) )
			$model = new Location();

		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = Location::find($id);

		$input = $request->except(['id', 'type', 'floor', 'room']);

		$type = $request->get('type', '');
		if( $type == '' )
			return Response::json($input);

		$type_model = LocationType::createOrFind($type);
		$input['type_id'] = $type_model->id;

		$model->update($input);

		$this->updateLocation($type, $model);

		if ($request->ajax())
			return Response::json($model);
		else
			return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
		$model = Location::find($id);
		$model->delete();

		// delete location group member
		DB::select('DELETE lgm 
					FROM services_location_group_members AS lgm 
					INNER JOIN services_location AS sl ON lgm.loc_id = sl.id 
					WHERE sl.id = ?', [$id]);

		return $this->index($request);
    }

	public function getTypeList(Request $request)
	{
		$val = $request->get('val', '');

		$list = LocationType::where('type', 'like', '%' . $val . '%')
			->get();

		return Response::json($list);
	}

	public function createLocationType(Request $request)
	{
		$type = $request->get('type', '');
		$model = LocationType::createOrFind($type);

		return Response::json($model);
	}

	/* React Functions */

	public function locationIndex(Request $request)
	{
		$platform = $request->get('platform');
		$limit = $request->get('limit', 0);
		$offset = $request->get('offset', 0);
		$search = $request->get('searchtext', "");
		$sortColumn = $request->get('sortcolumn', 'sl.id');
		$sortOrder = $request->get('sortorder', 'desc');

		$datalist = DB::table('services_location as sl')
			->leftJoin('services_location_type as lt', 'sl.type_id', '=', 'lt.id')
			->leftJoin('common_room as cr', 'sl.room_id', '=', 'cr.id')
			->leftJoin('common_floor as cf', 'sl.floor_id', '=', 'cf.id')
			->leftJoin('common_building as cb', 'sl.building_id', '=', 'cb.id')
			->leftJoin('common_property as cp', 'sl.property_id', '=', 'cp.id')
			// ->orderBy('sl.id')
			->select(['sl.*', 'lt.type', 'cr.room', 'cf.floor', 'cb.name as cbname', 'cp.name as cpname']);

		if (!empty($search)) {
			$datalist->where('sl.id', 'like', '%' . $search . '%')
				->orWhere('sl.name', 'like', '%' . $search . '%')
				->orWhere('lt.type', 'like', '%' . $search . '%')
				->orWhere('cp.name', 'like', '%' . $search . '%')
				->orWhere('cb.name', 'like', '%' . $search . '%')
				->orWhere('cf.floor', 'like', '%' . $search . '%')
				->orWhere('cr.room', 'like', '%' . $search . '%')
				->orWhere('sl.desc', 'like', '%' . $search . '%');
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

	/* React Function Ends */
}
