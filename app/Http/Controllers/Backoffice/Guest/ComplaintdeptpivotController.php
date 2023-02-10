<?php

namespace App\Http\Controllers\Backoffice\Guest;

use App\Models\Service\ComplaintDeptPivot;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;


use Excel;
use DB;
use Datatables;
use Response;

class ComplaintdeptpivotController extends UploadController
{
    public function showIndexPage($request, $model)
    {
        // delete action
        $ids = $request->input('ids');
        if( !empty($ids) )
        {
            DB::table('services_complaint_dept_pivot')->whereIn('id', $ids)->delete();
            return back()->withInput();
        }

        $query = ComplaintDeptPivot::where('id', '>', '0');

        $pagesize = $request->input('pagesize');
        if( empty($pagesize) )
            $pagesize = 10;

        $request->flashOnly('search');

        $datalist = $query->paginate($pagesize);

        //$mode = "read";
        $step = '2';
        return view('backoffice.wizard.guestservice.createcomplaintdeptpivot', compact('datalist', 'model', 'pagesize', 'step'));
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $datalist = DB::table('services_complaint_dept_pivot as sp')
                ->leftJoin('services_complaints as sc', 'sp.complaint_id', '=', 'sc.id')
                ->leftJoin('common_department as cd', 'sp.dept_id', '=', 'cd.id')
                ->select(['sp.*','sc.complaint','cd.department']);
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
            $model = new ComplaintDeptPivot();

            return $this->showIndexPage($request, $model);
        }
    }


    public function create()
    {
        $model = new ComplaintDeptPivot();
        $step = '2';

        return view('backoffice.wizard.guestservice.createcomplaintdeptpivot', compact('model', 'step'));
    }

    public function store(Request $request)
    {
        $step = '2';
        $complaint_id = $request->get('complaint_id',0);
        $query = DB::table('services_complaint_dept_pivot')
            ->where('complaint_id', $complaint_id)
            ->first();

        if(!empty($query)) {
            $ret['code'] = '400';
            return Response::json($ret);
        }

        $input = $request->except(['id']);

        $model = ComplaintDeptPivot::create($input);

        $message = 'SUCCESS';

        if( empty($model) )
            $message = 'Internal Server error';

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
        $model = ComplaintDeptPivot::find($id);
        if( empty($model) )
        {
            return back();
        }
        $step = '2';

        return view('backoffice.wizard.guestservice.createcomdeptpivot', compact('model', 'step'));
    }

    public function update(Request $request, $id)
    {
        $model = ComplaintDeptPivot::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "Complaint Department Pivot does not exist.";
            return back()->with('error', $message)->withInput();
        }

        $input = $request->all();
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
        $model = ComplaintDeptPivot::find($id);
        $model->delete();

        if ($request->ajax())
            return Response::json($model);
        else
            return Redirect::to('/backoffice/guestservice/wizard/createcompdeptpivot');
    }

}
