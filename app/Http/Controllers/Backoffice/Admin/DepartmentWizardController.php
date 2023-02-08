<?php

namespace App\Http\Controllers\Backoffice\Admin;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\Models\Common\Department;
use App\Models\Common\Property;
use App\Models\Common\CommonDepartmentPropertyPivot;
use App\Models\Service\ShiftGroup;
use DB;
use Datatables;
use Response;

class DepartmentWizardController extends Controller
{
    public function showIndexPage($request, $model)
	{
		  // delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			DB::table('common_department')->whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$query = Department::where('id', '>', '0');
	
		$pagesize = $request->input('pagesize');
		if( empty($pagesize) )
			$pagesize = 10;		
		
		$request->flashOnly('search'); 
		
		$property = Property::lists('name', 'id');		
		$datalist = $query->paginate($pagesize);
		
		//$mode = "read";
		$step = '0';
		return view('backoffice.wizard.admin.department', compact('datalist', 'model', 'pagesize', 'property', 'step'));				
	}	
    public function index(Request $request)
    {
		if ($request->ajax()) {
			$datalist = DB::table('common_department as cd')									
						->leftJoin('common_property as cp', 'cd.property_id', '=', 'cp.id')	
						->leftJoin('common_building as cb', 'cd.building_id', '=', 'cb.id')	
						->leftJoin('common_division as ci', 'cd.division_id', '=', 'ci.id')	
						->select(['cd.*', 'cp.name as cpname', 'cb.name as cbname', 'ci.division']);		
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
		else
		{
			$model = new Department();
			return $this->showIndexPage($request, $model);
		}
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
		//echo 'here';
    	$input = $request->except('id');
		if($input['short_code'] === null) $input['short_code'] = '';
		if($input['description'] === null) $input['description'] = '';

		$model = Department::create($input);
		
		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		

		$shift=new ShiftGroup();
		$shift->dept_id=$model->id;
		$shift->shift=1;
		$shift->name='Default';
		$shift->notify_off_shift='N';
		$shift->save();

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
        $model = Department::find($id);	
		if( empty($model) )
			$model = new Department();
		
		return $this->showIndexPage($request, $model);
    }

     public function update(Request $request, $id)
    {
		$model = Department::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		if ($request->ajax()) 
			return Response::json($model);
		else	
			return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = Department::find($id);
		$model->delete();

		if ($request->ajax()) 
			return Response::json($model);
		else	
			return $this->index($request);
    }

	public function getDepartmentList(Request $request)
	{
		$property_id = $request->get('property_id', '0');
		$department = $request->get('department','');

		if( $property_id > 0 )
		{
			$model = DB::table('common_department')
				->where('property_id', $property_id)
				->whereRaw("department like '%".$department."%'")
				->get();
		}
		else
		{
			$model = DB::table('common_department')
				->whereRaw("department like '%".$department."%'")
				->get();
		}

		return Response::json($model);
	}

    public function getUserList(Request $request)
    {
        $department_id = $request->get('department_id', 0);
        $username = $request->get('user_name','');

        $arr = explode(' ', $username);
        $first_name = $arr[0];
        $last_name = '';
        if (count($arr) > 1) {
            $last_name = $arr[1];
        }

        if( $department_id > 0 )
        {
            $model = DB::table('common_users')
                ->where('dept_id', $department_id)
                ->whereRaw("first_name like '%".$first_name."%' and last_name like '%".$last_name."%'")
                ->select(DB::raw('id, first_name, last_name'))
                ->get();
        }
        else
        {
            $model = DB::table('common_users')
                ->whereRaw("first_name like '%".$first_name."%' and last_name like '%".$last_name."%'")
                ->select(DB::raw('id, first_name, last_name'))
                ->get();
        }

        return Response::json($model);
    }

	public function getPropertyList(Request $request, $id) {
        $list_id = CommonDepartmentPropertyPivot::where('dept_id', $id)
            ->select('property_id')->get()->pluck('property_id');

        $unselected = DB::table('common_property')
                ->whereNotIn('id', $list_id)
                ->get();
        $selected = DB::table('common_property')
                ->whereIn('id', $list_id)
                ->get();

        $model = array();
        $model[] = $unselected;
        $model[] = $selected;

        return Response::json($model);
    }

    public function postPropertyList(Request $request)
    {
        $dept_id = $request->get('dept_id', '1');

        CommonDepartmentPropertyPivot::where('dept_id', $dept_id)->delete();

        $select_id = $request->get('select_id');

        for( $i = 0; $i < count($select_id); $i++ )
        {
            $property_id = $select_id[$i];

            $pivot = new CommonDepartmentPropertyPivot();

            $pivot->dept_id = $dept_id;
            $pivot->property_id = $property_id;
            $pivot->save();
        }

        echo "Property list has beed updated successfully";
    }

	public function getDeptList(Request $request)	// based on authentification
	{
		$property_id = $request->get('property_id','');
		$department = $request->get('department','');

		$model = DB::table('common_department as cd')
		    ->join('common_property_department_pivot as pdp', 'cd.id', '=', 'pdp.dept_id')
		    ->where('pdp.property_id', $property_id)			
			->whereRaw("department like '%".$department."%'")
			->select(DB::raw('cd.*'))
			->get();
		
		
		return Response::json($model);
	}
	public function getDeptLists(Request $request)	// based on authentification
	{
		$building_ids = $request->get('building_ids','');
		$department = $request->get('department','');

		$model = DB::table('common_department as cd')
		   // ->join('common_property_department_pivot as pdp', 'cd.id', '=', 'pdp.dept_id')
			->whereRaw("FIND_IN_SET(cd.building_id, '$building_ids')")			
			->whereRaw("department like '%".$department."%'")
			->select(DB::raw('cd.*'))
			->get();
		
		return Response::json($model);
	}

	public function getDepartList(Request $request)
	{
		$building_id = $request->get('building_id','');
		if( $building_id > 0 )
		{
			$model = DB::table('common_department')->where('building_id', $building_id)->get();		
		}
		else
		{
			$model = DB::table('common_department')->get();			
		}
		
		return Response::json($model);
	}
	
}
