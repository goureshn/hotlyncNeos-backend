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
