<?php

namespace App\Http\Controllers\Backoffice\Guest;

use App\Models\Service\FeedbackSource;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;


use Excel;
use DB;
use Datatables;
use Response;

class FeedbackSourceController extends UploadController
{
   
    public function index(Request $request)
    {
        $datalist = DB::table('services_complaint_feedback_source as scfs')
            ->select(['scfs.*']);
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
        $model = new FeedbackSource();
        $step = '2';

        return view('backoffice.wizard.guestservice.createcomplaint', compact('model', 'step'));
    }

    public function store(Request $request)
    {
        $input = $request->except(['id']);
        if($input['name'] === null) $input['name'] = '';

        $model = FeedbackSource::create($input);

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
        $model = FeedbackSource::find($id);	

		return Response::json($model);
    }

    public function edit($id)
    {
        
    }

    public function update(Request $request, $id)
    {
        $model = FeedbackSource::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "Feedback Source does not exist.";
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
        $model = FeedbackSource::find($id);
        $model->delete();

        if ($request->ajax())
            return Response::json($model);
        else
            return Redirect::to('/backoffice/guestservice/wizard/createFeedbackSource');
    }

}
