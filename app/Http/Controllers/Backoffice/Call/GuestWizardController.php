<?php

namespace App\Http\Controllers\Backoffice\Call;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Common\Building;
use App\Models\Call\GuestExtension;

use Redirect;
use DB;
use Response;
use \Illuminate\Database\QueryException;

use Yajra\Datatables\Datatables;

class GuestWizardController extends Controller
{
   
    public function index(Request $request)
    {
		$step = '2';
		
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			GuestExtension::whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$building = Building::lists('name', 'id');
			
		return view('backoffice.wizard.call.guest', compact('building', 'step'));
    }

	public function getGridData()
    {
		// $datalist = GuestExtension::all();
		$datalist = DB::table('call_guest_extn as ge')
			->leftJoin('common_building as b', 'ge.bldg_id', '=', 'b.id')
			->leftJoin('common_room as r', 'ge.room_id', '=', 'r.id')
			->leftJoin('common_property as cp', 'b.property_id', '=', 'cp.id')
			->select(['ge.*', 'b.name', 'r.room', 'cp.name as cpname']);
		return Datatables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('genable', function ($data) {
					if($data->enable == 1) return 'Yes';
					else return 'No';					
				})
				->addColumn('edit', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#editModal"  onClick="onShowEditRow('.$data->id.')">
						<span class="glyphicon glyphicon-pencil"></span>
					</button></p>';
				})
				->addColumn('delete', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" onClick="onDeleteRow('.$data->id.')">
						<span class="glyphicon glyphicon-trash"></span>
					</button></p>';
				})				
				->make(true);
    }
	
	public function getGridNgData()
    {
		// $datalist = GuestExtension::all();
		$datalist = DB::table('call_guest_extn as ge')
			->leftJoin('common_building as b', 'ge.bldg_id', '=', 'b.id')
			->leftJoin('common_room as r', 'ge.room_id', '=', 'r.id')
			->leftJoin('common_property as cp', 'b.property_id', '=', 'cp.id')
			->select(['ge.*', 'b.name', 'r.room', 'cp.name as cpname']);
		return Datatables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('genable', function ($data) {
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
				->rawColumns(['checkbox', 'genable', 'edit', 'delete'])			
				->make(true);
    }
	
	
    public function create()
    {
	
		return view('backoffice.wizard.property.propertycreate', compact('model', 'client', 'step'));	
    }
	
	public function createData(Request $request)
	{
		return $this->store($request);	
	}

    public function store(Request $request)
    {
		  $input = $request->except(['id','sub_exten']);
		  $extension = $request->extension ?? '0';
		  $enable = $request->enable ?? '0';
		  if($input['extension'] === null) $input['extension'] = '';
		  if($input['description'] === null) $input['description'] = '';

		  $query =  DB::table('call_staff_extn as cse')
				->where('cse.extension', '=', $extension)
				->first();

		  $query1 =  DB::table('call_staff_extn as cse')
				->where('cse.extension', '=', $extension)
				->select(DB::raw('cse.enable'))
				->first();


		if ((is_null($query) || ($query1->enable == 0)  || ($enable == 0)))
		{
		
		try { 			
			$model = GuestExtension::create($input);
		} catch(PDOException $e){
		   return Response::json([
				'success' => false,
				'message' => 'Hello'
				], 422);
		}	
	    
		if(!empty($model)) {
			$bldg_id = $request->get('bldg_id', 0);
			$room_id = $request->get('room_id', 0);
			$primary_extn = 'N';
			$enable = $request->get('enable', 0);
			$description = $request->get('description','');
			$sub_exten = $request->get('sub_exten',[]);
			
			
			for($i= 0 ;$i < count($sub_exten) ;$i++) {
				$input_sub = array();
				$input_sub['bldg_id'] = $bldg_id;
				$input_sub['room_id'] = $room_id;
				$input_sub['primary_extn'] = $primary_extn;
				$input_sub['enable'] = $enable;
				$input_sub['extension'] = $sub_exten[$i];
				$input_sub['description'] = $description;
				$query_ext =  DB::table('call_staff_extn as cse')
							->where('cse.extension', '=', $sub_exten[$i])
							->first();
				$query_admin =  DB::table('call_staff_extn as cse')
							->where('cse.extension', '=', $sub_exten[$i])
							->select(DB::raw('cse.enable'))
							->first();
				if ((is_null($query_ext) || ($query_admin->enable == 0) || ($enable == 0)))
				{
					$model_sub = GuestExtension::create($input_sub);
				}
				else
				{
					$error = 1;
					return Response::json($error);
	
				}
			}
		
		}
	}

	else
	{
		$error = 1;
		return Response::json($error);
	}
		return Response::json($model);	
	
	
	
    }

    public function show($id)
    {
        $model = GuestExtension::find($id);	
		
		return Response::json($model);
    }

    public function edit($id)
    {
       	$model = GuestExtension::find($id);	
		
		return Response::json($model);
    }

    public function update(Request $request, $id)
    {
		$id = $request->get('id', '0');
		$extension = $request->get('extension','0');
		$enable = $request->get('enable','0');
		//$input = $request->all();
		$input = $request->except(['sub_exten']);

		
		$model = GuestExtension::find($id);
		$query =  DB::table('call_staff_extn as cse')
				->where('cse.extension', '=', $extension)
				->first();
		$query1 =  DB::table('call_staff_extn as cse')
				->where('cse.extension', '=', $extension)
				->select(DB::raw('cse.enable'))
				->first();


		if ((is_null($query) || ($query1->enable == 0) || ($enable == 0)))
		{
		try {
			if (!empty($model))
				$model->update($input);

			if(!empty($model)) {
				$bldg_id = $request->get('bldg_id', 0);
				$room_id = $request->get('room_id', 0);
				$primary_extn = 'N';
				$enable = $request->get('enable', 0);
				$description = $request->get('description','');
				$sub_exten = $request->get('sub_exten',[]);
				for($i= 0 ;$i < count($sub_exten) ;$i++) {
					$input_sub = array();
					$input_sub['bldg_id'] = $bldg_id;
					$input_sub['room_id'] = $room_id;
					$input_sub['primary_extn'] = $primary_extn;
					$input_sub['enable'] = $enable;
					$input_sub['extension'] = $sub_exten[$i];
					$input_sub['description'] = $description;
					$model_sub = GuestExtension::create($input_sub);
					
				}
			}

			return Response::json($input);

		}catch (QueryException $e){

			 $errorCode = $e->errorInfo[1];
			 if($errorCode == 1062){
				 // houston, we have a duplicate entry problem
			 }
			 return Response::json($errorCode);
		 }
		}
		else
		{
			$error = 1;
			return Response::json($error);

		}
    }
	
	public function updateData(Request $request)
	{
		$id = $request->get('id', '0');
		try {
			return $this->update($request, $id);
		}catch(QueryException $e){
			$errorCode = $e->errorInfo[1];
			if($errorCode == 1062){
				// houston, we have a duplicate entry problem
			}
			return Response::json($errorCode);
		}
	}

    public function destroy($id)
    {
        $model = GuestExtension::find($id);
		$model->delete();

		return Response::json($model);	
    }
}
