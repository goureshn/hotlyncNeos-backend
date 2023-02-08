<?php

namespace App\Http\Controllers\Backoffice\User;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Redirect;

use App\Models\Common\Chain;
use App\Models\Common\CommonUser;
use App\Models\Common\Department;
use App\Models\Common\Employee;
use App\Models\Common\PropertySetting;

use App\Models\Service\TaskGroup;
use App\Models\Service\LocationGroup;
use App\Models\Service\ShiftGroupMember;

use Excel;
use DB;
use Datatables;
use Response;
use Redis;
use App\Modules\Functions;

class AccesscodeController extends Controller
{    
    public function index(Request $request)
    {
        //$property_id = $request->get('property_id', 0);

        $user_id = $request->get('user_id', 0);

        if( $user_id > 0 )
            $property_ids_by_jobrole = $request->get('property_ids_by_jobrole', []);
        else
        {
            $client_id = $request->get('client_id', 0);
            $property_ids_by_jobrole = CommonUser::getProertyIdsByClient($client_id);
        }


        $datalist = DB::table('common_users as cu')
            ->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->leftJoin('common_building as cb', 'cd.building_id', '=', 'cb.id')
            ->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id');

        //$datalist->whereIn('cd.property_id', $property_ids_by_jobrole);

        $datalist->select(['cu.*', 'cd.department',DB::raw('CONCAT(COALESCE(cu.first_name," "), " ",COALESCE(cu.last_name," ") ) as full_name'), 'cd.property_id', 'jr.job_role', 'cb.name as cbname']);

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

    public function create()
    {    
    }

    public function store(Request $request)
    {
       //
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
       
    }

    public function update(Request $request, $id)
    {
        $model = CommonUser::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "User does not exist.";
            return back()->with('error', $message)->withInput();
        }

        $input = $request->all();
        $model->update(['access_code'=>$input["access_code"]]);

        if( empty($model) )
            $message = 'Internal Server error';

        if ($request->ajax())
            return Response::json($model);
        else
            return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = DB::table('common_users')
            ->where('id', $id)
            ->update(['access_code'=>""]);

        if ($request->ajax())
            return Response::json($model);
        else
            return Redirect::to('/backoffice/user/wizard/accesscode');
    }

}
