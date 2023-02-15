<?php

namespace App\Http\Controllers\Backoffice\Guest;

use App\Models\Service\ApprovalRouteMember;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;


use Excel;
use DB;
use Datatables;
use Response;

class CompapproutememController extends UploadController
{
    public function showIndexPage($request, $model)
    {
        // delete action
        $ids = $request->input('ids');
        if( !empty($ids) )
        {
            DB::table('services_approval_route_members')->whereIn('id', $ids)->delete();
            return back()->withInput();
        }

        $query = ApprovalRouteMember::where('id', '>', '0');

        $pagesize = $request->input('pagesize');
        if( empty($pagesize) )
            $pagesize = 10;

        $request->flashOnly('search');

        $datalist = $query->paginate($pagesize);

        //$mode = "read";
        $step = '2';
        return view('backoffice.wizard.guestservice.createcompapproutemem', compact('datalist', 'model', 'pagesize', 'step'));
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $datalist = DB::table('services_approval_route_members as sm')
                ->leftJoin('common_property as cp', 'sm.property_id', '=', 'cp.id')
                ->leftJoin('services_approval_route as sr', 'sm.approval_route_id', '=', 'sr.id')
                ->leftJoin('common_job_role as cr', 'sm.job_role_id', '=', 'cr.id')
                ->select(['sm.*','cp.name as property','sr.approval' ,'cr.job_role']);
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
            $model = new ApprovalRouteMember();

            return $this->showIndexPage($request, $model);
        }
    }


    public function create()
    {
        $model = new ApprovalRouteMember();
        $step = '2';

        return view('backoffice.wizard.guestservice.createcomapproutemember', compact('model', 'step'));
    }

    public function store(Request $request)
    {
        $step = '2';
        $property_id = $request->property_id ?? 0;
        $approval_route_id = $request->approval_route_id ?? 0;
        $job_role_id = $request->job_role_id ?? 0;

        $query = DB::table('services_approval_route_members')
            ->where('property_id', $property_id)
            ->where('approval_route_id', $approval_route_id);
        $job_role = clone $query;
        $job = $job_role
                ->where('job_role_id' , $job_role_id)
                ->first();

        if(!empty($job)) {
            $ret['code'] = '400';
            return Response::json($ret);
        }

        $level_data = clone $query;
        $level_model = $level_data
            ->orderBy('level','desc')
            ->first();
        if(empty($level_model)) $level = 1 ;
        else $level = $level_model->level + 1;

        $input = $request->except(['id']);
        $input['level'] = $level;

        $model = ApprovalRouteMember::create($input);

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
        $model = ApprovalRouteMember::find($id);
        if( empty($model) )
        {
            return back();
        }
        $step = '2';

        return view('backoffice.wizard.guestservice.createcomapproute', compact('model', 'step'));
    }

    public function update(Request $request, $id)
    {
        $model = ApprovalRouteMember::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "Compensation Approval Route does not exist.";
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
        $model = ApprovalRouteMember::find($id);
        $model->delete();

        if ($request->ajax())
            return Response::json($model);
        else
            return Redirect::to('/backoffice/guestservice/wizard/createcompapproutemember');
    }

}
