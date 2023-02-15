<?php

namespace App\Http\Controllers\Backoffice\User;

use App\Models\Common\Department;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use Redirect;

use App\Models\Common\Chain;
use App\Models\Service\Shift;
use App\Models\Common\Property;

use DB;
use Datatables;
use Response;

class ShiftController extends Controller
{    
    public function index(Request $request)
    {
        $property_id = $request->get('property_id', 0);

        $datalist = DB::table('services_shifts as sh')
            ->join('common_property as cp', 'sh.property_id', '=', 'cp.id')            
            ->select(DB::raw('sh.*, cp.name as property'));
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
        if($input['name'] === null) $input['name'] = '';

        $model = Shift::create($input);

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
       
    }

    public function update(Request $request, $id)
    {
        $model = Shift::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "Shift does not exist.";
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
        $model = Shift::find($id);
        $model->delete();

        if ($request->ajax())
            return Response::json($model);
        else
            return Redirect::to('/backoffice/user/wizard/shift');
    }

}
