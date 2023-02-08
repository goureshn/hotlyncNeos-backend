<?php

namespace App\Http\Controllers\Backoffice\Call;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Common\Department;
use App\Models\Call\Section;

use Redirect;
use DB;
use Response;
use Datatables;

class SectionWizardController extends Controller
{
    private $request;
	
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
		
    public function index(Request $request)
    {
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			Section::whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$department = Department::lists('department', 'id');
		
		$users = DB::table('common_users as u')
            ->join('common_user_group_members as ugm', 'u.id', '=', 'ugm.user_id')
            ->join('common_user_group as ug', 'ugm.group_id', '=', 'ug.id')
			->where('ug.access_level', 'like', '%Manager%')
            ->select('u.id', 'u.username as name')
            ->pluck('name', 'id');
		
		$step = '0';
		
		return view('backoffice.wizard.call.section', compact('department', 'users', 'step'));	
    }

	public function getGridData()
    {
		$datalist = DB::table('call_section as cs')			
						->leftJoin('common_users as cu', 'cs.manager_id', '=', 'cu.id')
						->leftJoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')						
						->select(['cs.*', 'cu.username', 'cd.department']);
						
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
		$datalist = DB::table('call_section as cs')			
						->leftJoin('common_users as cu', 'cs.manager_id', '=', 'cu.id')
						->leftJoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')
						->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')						
						->select(['cs.*', 'cu.username', 'cd.department', 'cp.name as cpname']);
						
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
				->rawColumns(['checkbox', 'edit', 'delete'])
				->make(true);
    }
	
    public function create()
    {
		
    }
	
    public function store(Request $request)
    {
		$input = $request->except(['id']);
		if($input['section'] === null) $input['section'] = '';
		if($input['description'] === null) $input['description'] = '';

		try {
			$model = Section::create($input);
		} catch(PDOException $e){
		   return Response::json($model);
		}	
		
		return Response::json($model);
    }
	
	public function createData(Request $request)
	{
		return $this->store($request);
	}

    public function show($id)
    {
		$model = Section::find($id);	
		
		return Response::json($model);
    }

    public function edit($id)
    {
		
    }

	public function updateData(Request $request)
	{
		$id = $request->get('id', '0');
		
		return $this->update($request, $id);
	}
	
    public function update(Request $request, $id)
    {
		$input = $request->all();
		
		$model = Section::find($id);
			
		if( !empty($model) )
			$model->update($input);
		
		return Response::json($model);			
    }

    public function destroy($id)
    {
		$model = Section::find($id);
		$model->delete();
		
		return Response::json($model);				
    }	
	
	public function getSectionList(Request $request, $id)
	{
		if( $id > 0 )
		{
			$model = DB::table('call_section')->where('dept_id', $id)->get();		
		}
		else
		{
			$model = DB::table('call_section')->get();			
		}
		
		return Response::json($model);
	}

	public function SectionListOfKey(Request $request)
	{
		$dept_id = $request->get('dept_id', 0) ;
		$section = $request->get('section','');
		if( $dept_id > 0 )
		{
			$model = DB::table('call_section')
				->where('dept_id', $dept_id)
				->whereRaw("section like '%".$section."%'")->get();
		}
		else
		{
			$model = DB::table('call_section')
			->whereRaw("section like '%".$section."%'")->get();
		}

		return Response::json($model);
	}
}
