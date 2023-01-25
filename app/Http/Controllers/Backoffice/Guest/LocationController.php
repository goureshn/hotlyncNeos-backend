<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;
use App\Http\Controllers\UploadController;
use App\Models\Common\Chain;
use App\Models\Common\Property;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;
use App\Models\Common\Room;
use App\Models\Common\CommonArea;
use App\Models\Common\AdminArea;
use App\Models\Common\OutDoor;
use App\Models\Service\LocationGroupMember;
use App\Models\Service\LocationGroup;

use Excel;
use Response;
use DB;
use Datatables;
use Symfony\Component\Console\Tests\Output\OutputTest;

class LocationController extends UploadController
{
	private function showIndexPage(Request $request)
	{
		$clientlist = Chain::lists('name', 'id');

		$client_id = 1;
		$client = Chain::first();
		if( !empty($client) )
			$client_id = $client->id;

		$ltgroupmember = new LocationGroupMember();
		$step = '1';

		return view('backoffice.wizard.guestservice.location', compact('clientlist', 'client_id', 'ltgroupmember', 'step'));
	}
   	public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('services_location_group as lg')
						->leftJoin('common_chain as cc', 'lg.client_id', '=', 'cc.id')
						->select(['lg.*', 'cc.name as ccname']);

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
			return $this->showIndexPage($request);
		}

    }

    public function create()
    {
		$input = $request->all();

		$model = LocationGroup::create($input);

		return Response::json($model);
    }

	public function getGroupList(Request $request)
    {
		$client_id = $request->get('client_id', '0');

		$grouplist = LocationGroup::where('client_id', $client_id)->get();

		return Response::json($grouplist);
    }

	public function getLocationList(Request $request) {
		$type_id = $request->get('type_id', '1');
		$ltgroup_id = $request->get('ltgroup_id', '1');

		$unselected_member = array();
		$selected_member = array();

		$selected_member = DB::table('services_location as sl')
			->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			->join('services_location_group_members as lgm', 'lgm.loc_id', '=', 'sl.id')
			->where('lgm.location_grp', $ltgroup_id)
			->where('sl.type_id', $type_id)
			->select(DB::raw('sl.*, slt.type'))
			->get();

		$list_id = [0];
		foreach($selected_member as $row)
			$list_id[] = $row->id;

		$unselected_member = DB::table('services_location as sl')
			->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			->where('sl.type_id', $type_id)
			->whereNotIn('sl.id', $list_id)
			->select(DB::raw('sl.*, slt.type'))
			->get();

		$model = array();
		$model[] = $unselected_member;
		$model[] = $selected_member;

		return Response::json($model);
	}

	public function createGroup(Request $request)
    {
		return $this->store($request);
    }

	public function postLocation(Request $request)
	{
		$ltgroup_id = $request->get('ltgroup_id', '1');
		$type_id = $request->get('type_id', '1');
		$select_id = $request->get('select_id');

		DB::select('DELETE lgm 
					FROM services_location_group_members AS lgm 
					INNER JOIN services_location AS sl ON lgm.loc_id = sl.id 
					WHERE sl.type_id = ? 
					AND lgm.location_grp = ?', [$type_id, $ltgroup_id]);

		foreach($select_id as $row)
		{
			$model = new LocationGroupMember();
			$model->location_grp = $ltgroup_id;
			$model->loc_id = $row;

			$model->save();
		}

		echo "Location has beed updated successfully";
	}

    public function store(Request $request)
    {
    	$input = $request->except('id');

    	var_dump($input);
    	exit;

        $model = LocationGroup::create($input);

		return Response::json($model);
    }

    public function show($id)
    {
        $model = LocationGroup::find($id);

		return Response::json($model);
    }

    public function edit(Request $request, $id)
    {

    }

    public function update(Request $request, $id)
    {
		$model = LocationGroup::find($id);

        $input = $request->all();
		$model->update($input);

		return Response::json($model);
    }

    public function destroy(Request $request, $id)
    {
        $model = LocationGroup::find($id);
		$model->delete();

		// delete services_location_group_members table record for location group
		DB::table('services_location_group_members')
			->where('location_grp', $id)
			->delete();

		return Response::json($model);
    }

	public function parseExcelFile($path)
	{
		Excel::selectSheets('locationgroup')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					// $bldg_id = $data['bldg_id'];
					// $floor = $data['floor'];
					// if( Escalation::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
						// continue;
					LocationGroup::create($data);
				}
			}
		});
	}

	/* React Functions */

	public function locationGroupIndex(Request $request)
	{
		$platform = $request->get('platform');
		$limit = $request->get('limit', 0);
		$offset = $request->get('offset', 0);
		$search = $request->get('searchtext', "");
		$sortColumn = $request->get('sortcolumn', 'lg.id');
		$sortOrder = $request->get('sortorder', 'desc');

		$datalist = DB::table('services_location_group as lg')
			->leftJoin('common_chain as cc', 'lg.client_id', '=', 'cc.id')
			->select(['lg.*', 'cc.name as ccname']);

		if (!empty($search)) {
			$datalist->where('lg.id', 'like', '%' . $search . '%')->orWhere('cc.name', 'like', '%' . $search . '%')->orWhere('lg.name', 'like', '%' . $search . '%')->orWhere('lg.description', 'like', '%' . $search . '%');
		}

		if (!empty($sortColumn) && !empty($sortOrder)) {
			$datalist->orderBy($sortColumn, $sortOrder);
		}

		$total = count($response = $datalist->get());

		if ($limit != 0) {
			$datalist->take($limit);
		}
		if ($offset != 0) {
			$datalist->skip($offset);
		}

		$response = $datalist->get();

		return Response::json(["data" => $response, "recordsFiltered" => $total]);
	}

	/* React Functions Ends */
}
