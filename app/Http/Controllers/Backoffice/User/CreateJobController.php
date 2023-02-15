<?php

namespace App\Http\Controllers\Backoffice\User;

use App\Models\Common\Department;
use App\Models\Service\ShiftGroup;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;

use App\Models\Common\Chain;
use App\Models\Common\JobRole;
use App\Models\Common\Property;
use App\Models\Common\PermissionGroup;
use App\Models\Common\CommonJobrolePropertyPivot;
use App\Models\Service\SubcomplaintJobroleDeptPivot;

use Excel;
use DB;
use Datatables;
use Response;

class CreateJobController extends UploadController
{
    public function showIndexPage($request, $model)
    {
        // delete action
        $ids = $request->input('ids');
        if( !empty($ids) )
        {
            DB::table('common_job_role')->whereIn('id', $ids)->delete();
            return back()->withInput();
        }

        $query = JobRole::where('id', '>', '0');

        $pagesize = $request->input('pagesize');
        if( empty($pagesize) )
            $pagesize = 10;

        $request->flashOnly('search');

        $datalist = $query->paginate($pagesize);

        //$mode = "read";
        $step = '2';
        return view('backoffice.wizard.user.createjob', compact('datalist', 'model', 'pagesize', 'step'));
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $datalist = DB::table('common_job_role as cj')
                ->leftJoin('common_property as cp', 'cj.property_id', '=', 'cp.id')
                ->leftJoin('common_department as cd', 'cj.dept_id', '=', 'cd.id')
//                ->leftJoin('common_property as cp', 'ug.property_id', '=', 'cp.id')
                ->leftJoin('common_perm_group as pg', 'cj.permission_group_id', '=', 'pg.id')
                ->select(['cj.*', 'cp.name as property_name', 'cd.department as department', 'pg.name as pgname']);
            return Datatables::of($datalist)
                ->addColumn('checkbox', function ($data) {
                    return '<input type="checkbox" class="checkthis" />';
                })
                ->addColumn('edit', function ($data) {
                    return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="viewclass" ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
                })
                ->addColumn('delete', function ($data) {
                    return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-disabled="viewclass" ng-click="onDeleteRow('.$data->id.')">
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
                })
                ->rawColumns(['checkbox', 'edit', 'delete'])
                ->make(true);
        }
        else
        {
            $model = new JobRole();

            return $this->showIndexPage($request, $model);
        }
    }

    public function getList(Request $request)
    {
        $datalist = DB::table('common_job_role as cj')
            ->leftJoin('common_property as cp', 'cj.property_id', '=', 'cp.id')
            ->leftJoin('common_department as cd', 'cj.dept_id', '=', 'cd.id')
//                ->leftJoin('common_property as cp', 'ug.property_id', '=', 'cp.id')
            ->leftJoin('common_perm_group as pg', 'cj.permission_group_id', '=', 'pg.id')
            ->select(['cj.*', 'cp.name as property_name', 'cd.department as department', 'pg.name as pgname']);
        return Datatables::of($datalist)
            ->make(true);

    }


    public function create()
    {
        $model = new JobRole();
        $property = Department::lists('name', 'id');
        $pmgroup = PermissionGroup::lists('name', 'id');
        $step = '2';

        return view('backoffice.wizard.user.createjobcreate', compact('model', 'property', 'pmgroup', 'step'));
    }

    public function store(Request $request)
    {
        $step = '2';

        $input = $request->except(['id']);
        if($input['job_role'] === null) $input['job_role'] = '';

        // attendant/supervisor is only one
		if( $input['hskp_role'] == 'Attendant')
            DB::table('common_job_role')->where('hskp_role', 'Attendant')
                ->update(['hskp_role' => 'None']);

        if( $input['hskp_role'] == 'Supervisor')
                DB::table('common_job_role')->where('hskp_role', 'Supervisor')
                    ->update(['hskp_role' => 'None']);		

        $model = JobRole::create($input);
       // echo json_encode($model);
        $message = 'SUCCESS';
        
        if( empty($model) )
            $message = 'Internal Server error';

        $shift = ShiftGroup::where('dept_id',$model->dept_id)->where('name','Default')->first();
        if(!empty($shift))
        {
            $job_roles = json_decode($shift->job_role_ids);
            $job_roles[] = $model->id;
            $shift->job_role_ids = json_encode($job_roles);
            $shift->save();
        }
        if ($request->ajax())
            return Response::json($model);
        else
            return back()->with('error', $message)->withInput();
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $model = JobRole::find($id);
        if( empty($model) )
        {
            return back();
        }
        $property = Property::lists('name', 'id');
        $step = '2';

        return view('backoffice.wizard.user.createjob', compact('model', 'property', 'step'));
    }

    public function update(Request $request, $id)
    {
        $model = JobRole::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "Job Role does not exist.";
            return back()->with('error', $message)->withInput();
        }

        $input = $request->all();

        // attendant/supervisor is only one
		if( $input['hskp_role'] == 'Attendant')
            DB::table('common_job_role')->where('hskp_role', 'Attendant')
                ->update(['hskp_role' => 'None']);

        if( $input['hskp_role'] == 'Supervisor')
                DB::table('common_job_role')->where('hskp_role', 'Supervisor')
                    ->update(['hskp_role' => 'None']);		
                    
        $model->update($input);

        if( empty($model) )
            $message = 'Internal Server error';

        if ($request->ajax())
            return Response::json($model);
        else
            return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = JobRole::find($id);
        $model->delete();

        if ($request->ajax())
            return Response::json($model);
        else
            return Redirect::to('/backoffice/user/wizard/createjob');
    }

    public function getPropertyList(Request $request, $id) {
        $job_role_id = $id;

        $list_id = CommonJobrolePropertyPivot::where('job_role_id', $job_role_id)
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
        $job_role_id = $request->get('job_role_id', '1');

        CommonJobrolePropertyPivot::where('job_role_id', $job_role_id)->delete();

        $select_id = $request->get('select_id');

        for( $i = 0; $i < count($select_id); $i++ )
        {
            $property_id = $select_id[$i];

            $pivot = new CommonJobrolePropertyPivot();

            $pivot->job_role_id = $job_role_id;
            $pivot->property_id = $property_id;
            $pivot->save();
        }

        echo "Property list has beed updated successfully";
    }

    public function getDeptList(Request $request) {
        $job_role_id = $request->get('job_role_id', 0);
        $client_id = $request->get('client_id', 0);

        $list_id = SubcomplaintJobroleDeptPivot::where('job_role_id', $job_role_id)
            ->select('dept_id')->get()->pluck('dept_id');

        $unselected = DB::table('common_department as cd')
                ->join('common_property as cp', 'cd.property_id', '=', 'cp.id')
                ->whereNotIn('cd.id', $list_id)
                ->where('cp.client_id', $client_id)
                ->select(DB::raw('cd.*, cp.name as property_name'))
                ->get();

        $selected = DB::table('common_department as cd')
                ->join('common_property as cp', 'cd.property_id', '=', 'cp.id')
                ->whereIn('cd.id', $list_id)
                ->where('cp.client_id', $client_id)
                ->select(DB::raw('cd.*, cp.name as property_name'))
                ->get();

        $model = array();
        $model[] = $unselected;
        $model[] = $selected;

        return Response::json($model);
    }

    public function postDeptList(Request $request)
    {
        $job_role_id = $request->get('job_role_id', '1');

        SubcomplaintJobroleDeptPivot::where('job_role_id', $job_role_id)->delete();

        $select_id = $request->get('select_id');

        for( $i = 0; $i < count($select_id); $i++ )
        {
            $dept_id = $select_id[$i];

            $pivot = new SubcomplaintJobroleDeptPivot();

            $pivot->job_role_id = $job_role_id;
            $pivot->dept_id = $dept_id;
            $pivot->save();
        }

        echo "Department list has beed updated successfully";
    }


}
