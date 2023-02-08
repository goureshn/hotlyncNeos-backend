<?php

namespace App\Http\Controllers\Backoffice\Call;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Call\StaffExternal;
use App\Models\Call\Section;
use App\Models\Common\CommonUser;
use App\Models\Common\Department;
use App\Models\Common\UserGroup;

use Yajra\Datatables\Datatables;

use DB;
use Response;


class AdminWizardController extends Controller
{
    public function index(Request $request)
    {
		$step = '1';
		
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			StaffExternal::whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$section = Section::lists('section', 'id');
		$users = CommonUser::lists('username', 'id');
		$usergroup = UserGroup::lists('name', 'id');
			
		return view('backoffice.wizard.call.admin', compact('section', 'users', 'usergroup', 'step'));			
    }
	
	public function getGridData()
    {
		$datalist = DB::table('call_staff_extn as cse')			
						->leftJoin('call_section as cs', 'cse.section_id', '=', 'cs.id')
						->leftJoin('common_users as cu', 'cse.user_id', '=', 'cu.id')
						->leftJoin('common_user_group as cug', 'cse.user_group_id', '=', 'cug.id')
						->leftJoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')
						->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')
						->leftJoin('common_building as cb', 'cse.building_id', '=', 'cb.id')
						->where('cse.bc_flag', 0)
						->select(['cse.*', 'cs.section', 'cu.username', 'cug.name', 'cd.department', 'cp.name as cpname','cb.name as cbname']);
						
		return Datatables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('edit', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  onClick="onShowEditRow('.$data->id.')">
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
		$datalist = DB::table('call_staff_extn as cse')			
						->leftJoin('call_section as cs', 'cse.section_id', '=', 'cs.id')
						->leftJoin('common_users as cu', 'cse.user_id', '=', 'cu.id')
						->leftJoin('common_user_group as cug', 'cse.user_group_id', '=', 'cug.id')						
						->leftJoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')
						->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')
						->leftJoin('common_building as cb', 'cse.building_id', '=', 'cb.id')
						->where('cse.bc_flag', '0')
						->select('cse.*', 'cs.section', 'cu.username', 'cug.name', 'cd.department', 'cp.name as cpname', 'cb.name as cbname');
		
		return Datatables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('adminenable', function ($data) {
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
				->rawColumns(['checkbox', 'adminenable', 'edit', 'delete'])
				->make(true);
    }

    public function create()
    {
		
    }
	
    public function store(Request $request)
    {
		$input = $request->except(['id','department']);
		$group_ids = $request->user_group_name ?? [];
		$extension = $request->extension ?? '0';
		$enable = $request->enable ?? '0';
		$duplicate = StaffExternal::where('extension', $extension)->select('enable')->first();
		//echo json_encode($duplicate);
		$group1 = DB::table('common_user_group')->whereIn("id", $group_ids)->get();
		$value = '';
		foreach($group1 as $item) {
			$value .= $item->name . ", ";
		}
		$input['user_group_name'] = $value;
		
		$query =  DB::table('call_guest_extn as cge')
				->where('cge.extension', '=', $extension)
				->first();
		$query1 =  DB::table('call_guest_extn as cge')
				->where('cge.extension', '=', $extension)
				->select(DB::raw('cge.enable'))
				->first();

		if ((is_null($query) || ($query1->enable == 0 ) || ($enable == 0)))
		{
			if(!empty($duplicate)){
				if(($duplicate->enable == 1) && ($enable == 1))
				{	
					$error = 3;
					return Response::json($error);
				}else{
					try 
					{
						$model = StaffExternal::create($input);
						} catch(PDOException $e){
		  		 		return Response::json($input);
					}	
	
				return Response::json($model);
				}
			}else{
				try 
				{
					$model = StaffExternal::create($input);
					} catch(PDOException $e){
		  		 	return Response::json($input);
				}	
	
				return Response::json($model);	
			}	
		}
		else
		{
			$error = 1;
			return Response::json($error);

		} 
		
    }
	
	public function createData(Request $request)
	{
		return $this->store($request);
	}

    public function show($id)
    {
		$model = StaffExternal::find($id);	
		
		return Response::json($model);
    }

    public function edit($id)
    {
		
    }

    public function update(Request $request, $id)
    {
		$id = $request->get('id', '0');
		$group_ids = $request->get('user_group_name', []);
		$extension = $request->get('extension','0');
		$enable = $request->get('enable','0');
		$input = $request->except(['department']);
		//$duplicate = StaffExternal::where('id', $id)->select('extension')->first();
		$exist = StaffExternal::where('extension', $extension)->where('id','!=',$id)->first();
		$group1 = DB::table('common_user_group')->whereIn("id", $group_ids)->get();

		
		$value = '';
		foreach($group1 as $item) {
			$value .= $item->name . ", ";
		}
		$input['user_group_name'] = $value;
		$model = StaffExternal::find($id);
		$query =  DB::table('call_guest_extn as cge')
				->where('cge.extension', '=', $extension)
				->first();
		$query1 =  DB::table('call_guest_extn as cge')
				->where('cge.extension', '=', $extension)
				->select(DB::raw('cge.enable'))
				->first();


		if  ((is_null($query)) || ($query1->enable == 0) || ($enable == 0))
		{
			if(!empty($exist)){
				if((($exist->enable == 1) && ($enable == 1)) && ($exist->extension == $extension))
				{	
					$error = 3;
					return Response::json($error);
				}else{
					if( !empty($model) ) {
			
						$model->update($input);
					}
				}
			}else{
			
				if( !empty($model) ) {
			
					$model->update($input);
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
	public function updateext(Request $request, $id)
	{
		$id = $request->get('id', '0');

		$group_ids = $request->get('user_group_name', []);
		$input = $request->all();
		$group1 = DB::table('common_user_group')->whereIn("id", $group_ids)->get();
		$value = '';
		foreach($group1 as $item) {
			$value .= $item->name . ", ";
		}
		$input['user_group_name'] = $value;
		$model = StaffExternal::find($id);

		if( !empty($model) ) {
			$model->update($input);
		}

		return Response::json($model);
	}

	public function updateData(Request $request)
	{
		$id = $request->get('id', '0');
		
		return $this->update($request, $id);
	}
	
    public function destroy($id)
    {
		$model = StaffExternal::find($id);
		$model->delete();
		
		return Response::json($model);				
    }

	public  function  getUserList(Request $request) {

		$user_name = $request->get('user_name','');

		$model = DB::table('common_users')
			->whereRaw("username like '%".$user_name."%'")->get();

		return Response::json($model);
	}

	public function getSectionList(Request $request)
	{
	
		$id = $request->get('building_id', 0);
	
		$department =  Department::where('building_id',$id)->select('id')->get()->pluck('id');	

		$model = DB::table('call_section')->whereIn('dept_id', $department)->select('section')->get();	
	
		return Response::json($model);
		
	}

	public function SectionListOfKey(Request $request)
	{
		$building_id = $request->get('building_id', 0) ;
		$section = $request->get('section','');
		if( $building_id > 0 )
		{
			$departments =  Department::where('building_id', $building_id)->select('id')->get();	
			$model = DB::table('call_section')->whereIn('dept_id', $departments)->whereRaw("section like '%".$section."%'")->get();	
			
		}
		else
		{
			$model = DB::table('call_section')
			->whereRaw("section like '%".$section."%'")->get();
		}

		return Response::json($model);
	}

}
