<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;

use App\Http\Controllers\UploadController;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;
use App\Models\Common\RoomType;
use App\Models\Common\Room;
use App\Models\Service\HskpStatus;
use App\Models\Service\Location;
use App\Models\Service\LocationType;

use Redirect;
use Excel;
use DB;
use Datatables;
use Response;

class RoomWizardController extends UploadController
{
    public function showIndexPage($request, $model)
	{
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			DB::table('common_floor')->whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$query = Room::where('id', '>', '0');
		
		$pagesize = $request->input('pagesize');
		if( empty($pagesize) )
			$pagesize = 10;		
		
		$request->flashOnly('search'); 
		
		$datalist = $query->paginate($pagesize);
		
		//$mode = "read";
		$step = '5';
		return view('backoffice.wizard.property.room', compact('datalist', 'model', 'pagesize', 'step'));				
	}	
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('common_room as cr')									
						->leftJoin('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
						->leftJoin('common_floor as cf', 'cr.flr_id', '=', 'cf.id')						
						->leftJoin('services_hskp_status as hskp', 'cr.hskp_status_id', '=', 'hskp.id')
						->leftJoin('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
						->leftJoin('common_property as cp', 'cb.property_id', '=', 'cp.id')
						->select(['cr.*', 'rt.type', 'cf.floor', 'hskp.status', 'cb.name as cbname', 'cb.id as cbid', 'cp.name as cpname', 'cp.id as cpid', 'cb.property_id', 'cf.bldg_id']);
						
			return Datatables::of($datalist)
					->addColumn('checkbox', function ($data) {
						return '<input type="checkbox" class="checkthis" />';
					})
					->addColumn('renable', function ($data) {
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
					->rawColumns(['checkbox', 'renable', 'edit', 'delete'])
					->make(true);
        }
		else
		{
			$model = new Room();
			return $this->showIndexPage($request, $model);
		}
    }

    public function create(Request $request)
    {
        $model = new Room();
		
		$building = Building::lists('name', 'id');
		
		$build_id = $request->get('bldg_id', '1');
		$model->bldg_id = $build_id;
		
		$floor = CommonFloor::where('bldg_id', $build_id)->get()->pluck('floor', 'id');
		$roomtype = RoomType::where('bldg_id', $build_id)->get()->pluck('type', 'id');
		$hsks = HskpStatus::where('bldg_id', $build_id)->get()->pluck('status', 'id');
		
		$step = '5';
		
		return view('backoffice.wizard.property.roomcreate', compact('model', 'building', 'floor', 'roomtype', 'hsks', 'step'));						
    }
	
	public function getRoomAssistList(Request $request)
	{
		$build_id = $request->get('build_id', '1');
		
		if( $build_id > 0 )
		{
			$floor = DB::table('common_floor')->where('bldg_id', $build_id)->get();
			$roomtype = DB::table('common_room_type')->where('bldg_id', $build_id)->get();
			$hskp = DB::table('services_hskp_status')->where('bldg_id', $build_id)->get();
		}
		else
		{
			$floor = DB::table('common_floor')->get();
			$roomtype = DB::table('common_room_type')->get();
			$hskp = DB::table('services_hskp_status')->get();
		}
		$model = array();
		
		$model['floor'] = $floor;
		$model['roomtype'] = $roomtype;
		$model['hskp'] = $hskp;
		
		return Response::json($model);
	}

    public function store(Request $request)
    {
		$input = $request->except('id');
		foreach ($input as $key => $value) {
			if($value === null) $input[$key] = "";
		}

		$enable = $request->enable ?? 0;
		$total = DB::table('property_setting as ps')
			    ->select(DB::raw('ps.value'))
			    ->where('ps.settings_key', 'total_rooms')
				->first();
		$total_current = DB::table('common_room as cr')
							->where('enable', 1);
		$data_query = clone $total_current;
		$totalcount = $data_query->count();
		if(!empty($total->value)){
		if(($totalcount + $enable) <= $total->value){
		$model = Room::create($input);

		Room::createLocation();
		
		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return  Redirect::to('/backoffice/property/wizard/room');
		
		}
		else{
			$error = 1;
			return Response::json($error);
		}	
		}
		else{
			$model = Room::create($input);
		
		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return  Redirect::to('/backoffice/property/wizard/room');
		}
    }

    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $model = Room::find($id);
		
		$build_id = $model->bldg_id;
		
		if( $build_id < 1 )
		{
			$build_id = 1;
			$model->bldg_id = $build_id;
		}
		
		$building = Building::lists('name', 'id');
		
		$floor = CommonFloor::where('bldg_id', $build_id)->get()->pluck('floor', 'id');
		$roomtype = RoomType::where('bldg_id', $build_id)->get()->pluck('type', 'id');
		$hsks = HskpStatus::where('bldg_id', $build_id)->get()->pluck('status', 'id');
		
		$step = '5';
		
		return view('backoffice.wizard.property.roomcreate', compact('model', 'building', 'floor', 'roomtype', 'hsks', 'step'));
    }

    public function update(Request $request, $id)
    {
		$model = Room::find($id);
		$enable = $request->get('enable', 0);
		$total = DB::table('property_setting as ps')
			    ->select(DB::raw('ps.value'))
			    ->where('ps.settings_key', 'total_rooms')
				->first();
		$total_current = DB::table('common_room as cr')
							->where('enable', 1);
							$data_query = clone $total_current;
							$totalcount = $data_query->count();
		if (!empty($total->value))
		{
			if(($totalcount + $enable) <= $total->value)
			{	
				if( empty($model) )
				{
					return Redirect::back();			
				}
				
				$input = $request->all();
				$model->update($input);

				Room::createLocation();
				
				$message = 'SUCCESS';	
				
				if( empty($model) )
					$message = 'Internal Server error';		
				
				if ($request->ajax()) 
					return Response::json($model);
				else	
					return  Redirect::to('/backoffice/property/wizard/room');
			}
			else
			{
				$error = 1;
				return Response::json($error);
			}
		}
		else{
			if( empty($model) )
			{
				return Redirect::back();			
			}
			
			$input = $request->all();
			$model->update($input);
			Room::createLocation();
			
			$message = 'SUCCESS';	
			
			if( empty($model) )
				$message = 'Internal Server error';		
			
			if ($request->ajax()) 
				return Response::json($model);
			else	
				return  Redirect::to('/backoffice/property/wizard/room');
		}
			
    }

    public function destroy(Request $request, $id)
    {
		// find building, property id
		$data = Room::getPropertyBuildingFloor($id);

        $model = Room::find($id);
		$model->delete();


		// delete location
		$loc_type = LocationType::createOrFind('Room');
		
		Location::where('property_id', $data->property_id)
				->where('building_id', $data->building_id)
				->where('floor_id', $data->flr_id)
				->where('room_id', $id)
				->where('type_id', $loc_type->id)
				->delete();

		// delete location group member
		DB::select('DELETE lgm 
					FROM services_location_group_members AS lgm 
					INNER JOIN services_location AS sl ON lgm.loc_id = sl.id 
					WHERE sl.property_id = ? 
					AND sl.building_id = ?
					AND sl.floor_id = ?
					AND sl.room_id = ?
					AND sl.type_id = ?', [$data->property_id, $data->building_id, $data->flr_id, $id, $loc_type->id]);				

		return $this->index($request);
    }	
	
	public function getRoomList(Request $request)
	{
		$build_id = $request->get('build_id', '0');

		if( $build_id > 0 )
		{
			$roomlist = DB::table('common_room as r')
				->join('common_floor as f', 'r.flr_id', '=', 'f.id')			
				->where('f.bldg_id', $build_id)	
				->select('r.*')
				->get();	
		}
		else
		{
			$roomlist = DB::table('common_room')->get();
		}
		
			
		return Response::json($roomlist);
	}
	
	public function parseExcelFile($path)
	{
		Excel::selectSheets('Floor')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					$bldg_id = $data['bldg_id'];
					$type = $data['type'];
					if( RoomType::where('bldg_id', $bldg_id)->where('type', $type)->exists() )
						continue;					
					
					RoomType::create($data);
				}
			}							
		});

		CommonFloor::createLocation();
		
		Excel::selectSheets('Room')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					$flr_id = $data['flr_id'];
					$room = $data['room'];
					if( Room::where('flr_id', $flr_id)->where('room', $room)->exists() )
						continue;			
					
					Room::create($data);
				}
			}							
		});

		Room::createLocation();
	}
}
