<?php

namespace App\Http\Controllers\Backoffice\Guest;

use App\Models\Service\ComplaintDeptdefaultAssign;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;


use Excel;
use DB;
use Datatables;
use Response;

class DeptdefaultassController extends UploadController
{
    public function showIndexPage($request, $model)
    {
        // delete action
        $ids = $request->input('ids');
        if( !empty($ids) )
        {
            DB::table('services_complaint_dept_default_assignee')->whereIn('id', $ids)->delete();
            return back()->withInput();
        }

        $query = ComplaintDeptdefaultAssign::where('id', '>', '0');

        $pagesize = $request->input('pagesize');
        if( empty($pagesize) )
            $pagesize = 10;

        $request->flashOnly('search');

        $datalist = $query->paginate($pagesize);

        //$mode = "read";
        $step = '2';
        return view('backoffice.wizard.guestservice.createcomplaindepartdefaultassign', compact('datalist', 'model', 'pagesize', 'step'));
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $datalist = DB::table('services_complaint_dept_default_assignee as sa')
                ->leftJoin('common_department as cd', 'sa.id', '=', 'cd.id')
                ->leftJoin('common_users as cu', 'sa.user_id', '=', 'cu.id')
				->leftJoin('common_user_group as cg', 'sa.user_group', '=', 'cg.id')
                ->select(['sa.*','cd.department','cu.first_name','cu.last_name','cg.name as group_name']);
            return Datatables::of($datalist)
                ->addColumn('location_list', function ($data) {
                    $id = $data->id;
                    $list = DB::table('services_complaint_dept_location_pivot as a')
                        ->join('services_location as sl', 'a.location_id', '=', 'sl.id')
                        ->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
                        ->where('a.dept_id', $id)
                        ->select(DB::raw('sl.*, CONCAT(sl.name, " - ", slt.type) as location_type_name'))
                        ->get();
                    
                    return $list;
                })
                ->addColumn('location_type_list', function ($data) {
                    $id = $data->id;
                    $list = DB::table('services_complaint_dept_location_type_pivot as a')
                        ->join('services_location_type as slt', 'a.loc_type_id', '=', 'slt.id')
                        ->where('a.dept_id', $id)
                        ->select(DB::raw('slt.*'))
                        ->get();
                    
                    return $list;
                })
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
                ->rawColumns(['location_list', 'location_type_list', 'checkbox', 'edit', 'delete'])
                ->make(true);
        }
        else
        {
            $model = new ComplaintDeptdefaultAssign();

            return $this->showIndexPage($request, $model);
        }
    }


    public function create()
    {
        $model = new ComplaintDeptdefaultAssign();
        $step = '2';

        return view('backoffice.wizard.guestservice.createcomplaintdeptdefaultassign', compact('model', 'step'));
    }

    public function store(Request $request)
    {
        $step = '2';
        $id = $request->get('id',0);
        $input = $request->except(['location_list', 'location_type_list']);
        $data = DB::table('services_complaint_dept_default_assignee')->where('id', $id)->first();
        if(!empty($data)) {
            $ret['code'] = '400';
            return Response::json($ret);
        }
        $model = ComplaintDeptdefaultAssign::create($input);

        $message = 'SUCCESS';

        if( empty($model) )
            $message = 'Internal Server error';

        // update location list
        $location_list = $request->get('location_list', []);
        $this->updateDeptLocPivot($id, $location_list);    

        // update location type list
        $location_type_list = $request->get('location_type_list', []);
        $this->updateDeptLocTypePivot($id, $location_type_list);    


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
        $model = ComplaintDeptdefaultAssign::find($id);
        if( empty($model) )
        {
            return back();
        }
        $step = '2';

        return view('backoffice.wizard.guestservice.createdepartmentdefaultassign', compact('model', 'step'));
    }

    private function updateDeptLocPivot($dept_id, $location_list)
    {
        DB::table('services_complaint_dept_location_pivot')
            ->where('dept_id', $dept_id)
            ->delete();

        foreach($location_list as $row)
        {
            $input = array();
            $input['dept_id'] = $dept_id;
            $input['location_id'] = $row['id'];

            DB::table('services_complaint_dept_location_pivot')->insert($input);
        }    
    }

    private function updateDeptLocTypePivot($dept_id, $location_type_list)
    {
        DB::table('services_complaint_dept_location_type_pivot')
            ->where('dept_id', $dept_id)
            ->delete();

        foreach($location_type_list as $row)
        {
            $input = array();
            $input['dept_id'] = $dept_id;
            $input['loc_type_id'] = $row['id'];

            DB::table('services_complaint_dept_location_type_pivot')->insert($input);
        }    
    }

    public function update(Request $request, $id)
    {
        $dept_id = $request->get('dept_id', 0);
        if( $id != $dept_id )
        {
            $data = DB::table('services_complaint_dept_default_assignee')->where('id', $dept_id)->first();
            if(!empty($data)) {
                $ret['code'] = '400';
                return Response::json($ret);
            }
        }

        $model = ComplaintDeptdefaultAssign::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "Complaint department default assignee does not exist.";
            return back()->with('error', $message)->withInput();
        }

        $input = $request->except(['dept_id', 'location_list', 'location_type_list']);
        $input['id'] = $dept_id;

        $model->update($input);

        if( empty($model) )
            $message = 'Internal Server error';

        // update location list
        $location_list = $request->get('location_list', []);
        $this->updateDeptLocPivot($dept_id, $location_list);

        // update location type list
        $location_type_list = $request->get('location_type_list', []);
        $this->updateDeptLocTypePivot($id, $location_type_list);    
  
        
        if ($request->ajax())
            return Response::json($model);
        else
            return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = ComplaintDeptdefaultAssign::find($id);
        $model->delete();

        if ($request->ajax())
            return Response::json($model);
        else
            return Redirect::to('/backoffice/guestservice/wizard/createcomplaintdefaultassignee');
    }

}
