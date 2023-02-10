<?php

namespace App\Http\Controllers\Backoffice\Guest;

use App\Models\Service\Complaints;
use App\Models\Service\ComplaintType;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;

use Excel;
use DB;
use Datatables;
use Response;

class ComplaintController extends UploadController
{
    public function showIndexPage($request, $model)
    {
        // delete action
        $ids = $request->input('ids');
        if( !empty($ids) )
        {
            DB::table('services_complaints')->whereIn('id', $ids)->delete();
            return back()->withInput();
        }

        $query = Complaints::where('id', '>', '0');

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
            $datalist = DB::table('services_complaints as sc')
                ->leftJoin('services_complaint_type as sct', 'sc.type_id', '=', 'sct.id')
                ->select(['sc.*', 'sct.type as type']);
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
            $model = new Complaints();

            return $this->showIndexPage($request, $model);
        }
    }


    public function create()
    {
        $model = new Complaints();
        $type = ComplaintType::lists('type', 'id');
        $step = '2';

        return view('backoffice.wizard.guestservice.createcomplaint', compact('model', 'type', 'step'));
    }

    public function store(Request $request)
    {
        $step = '2';

        $input = $request->except(['id']);
        if($input['complaint'] === null) $input['complaint'] = '';

        $model = Complaints::create($input);

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
        $model = Complaints::find($id);
        if( empty($model) )
        {
            return back();
        }
        $type = ComplaintType::lists('type', 'id');
        $step = '2';

        return view('backoffice.wizard.guestservice.createjob', compact('model', 'type', 'step'));
    }

    public function update(Request $request, $id)
    {
        $model = Complaints::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "Complaints does not exist.";
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
        $model = Complaints::find($id);
        $model->delete();

        if ($request->ajax())
            return Response::json($model);
        else
            return Redirect::to('/backoffice/guestservice/wizard/createcomplaints');
    }


}
