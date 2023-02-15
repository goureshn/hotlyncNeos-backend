<?php

namespace App\Http\Controllers\Backoffice\Guest;

use App\Models\Service\FeedbackType;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;


use Excel;
use DB;
use Datatables;
use Response;

class FeedbackTypeController extends UploadController
{
   
    public function index(Request $request)
    {
        $datalist = DB::table('services_complaint_feedback_type as scft')
            ->select(['scft.*']);
        return Datatables::of($datalist)
            // ->addColumn('category', function ($data) {
            //     $ids = $data->category_ids;
            //     $list = DB::table('services_complaint_maincategory')
            //         ->whereRaw("FIND_IN_SET(id, '$ids')")
            //         ->select(DB::raw('GROUP_CONCAT(name) as field'))
            //         ->first();
                
            //     return $list->field;
            // })
            // ->addColumn('severity', function ($data) {
            //     $ids = $data->severity_ids;
            //     $list = DB::table('services_complaint_type')
            //         ->whereRaw("FIND_IN_SET(id, '$ids')")
            //         ->select(DB::raw('GROUP_CONCAT(type) as field'))
            //         ->first();
                
            //     return $list->field;
            // })		
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
        $model = new FeedbackType();
        $step = '2';

        return view('backoffice.wizard.guestservice.createcomplaint', compact('model', 'step'));
    }

    public function store(Request $request)
    {
        $input = $request->except(['id', 'category', 'severity']);
        $model = FeedbackType::create($input);

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
        $model = FeedbackType::find($id);	

		return Response::json($model);
    }

    public function edit($id)
    {
        
    }

    public function update(Request $request, $id)
    {
        $model = FeedbackType::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "Feedback Source does not exist.";
            return back()->with('error', $message)->withInput();
        }

        $input = $request->except(['id', 'category', 'severity']);
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
        $model = FeedbackType::find($id);
        $model->delete();

        if ($request->ajax())
            return Response::json($model);
        else
            return Redirect::to('/backoffice/guestservice/wizard/createFeedbackType');
    }

}
