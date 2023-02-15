<?php

namespace App\Http\Controllers\Backoffice\User;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Common\Property;
use App\Models\Common\PermissionModule;

use DB;
use Datatables;
use Response;

class PmModuleController extends Controller
{
    public function showIndexPage($request, $model)
    {
        // delete action
        $ids = $request->input('ids');
        if( !empty($ids) )
        {
            DB::table('common_building')->whereIn('id', $ids)->delete();
            return back()->withInput();
        }

        $query = PermissionsModule::where('id', '>', '0');

        $pagesize = $request->input('pagesize');
        if( empty($pagesize) )
            $pagesize = 10;

        $request->flashOnly('search');

        $property = Property::lists('name', 'id');
        $datalist = $query->orderby('name')->paginate($pagesize);

        //$mode = "read";
        $step = '2';
        return view('backoffice.wizard.property.commonmodule', compact('datalist', 'model', 'pagesize', 'property', 'step'));
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $datalist = DB::table('common_page_route as cpr')
                ->leftJoin('common_module as cm', 'cpr.module_id', '=', 'cm.id')
                ->select(['cpr.*', 'cm.name as mname']);
            return Datatables::of($datalist)
                ->editColumn('mname', function($data) {
                    if($data->module_id == 10000) {
                        return 'All Module';
                    }else {
                        return $data->mname;
                    }
                })
                ->addColumn('checkbox', function ($data) {
                    return '<input type="checkbox" class="checkthis" />';
                })
                ->addColumn('edit', function ($data) {
                    return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="viewclass" ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
                })
                ->addColumn('delete', function ($data) {
                    return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs disabled" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-disabled="viewclass" ng-click="onDeleteRow('.$data->id.')" >
							<span class="glyphicon glyphicon-trash"></span>
						</button></p>';
                })
                ->rawColumns(['mname', 'checkbox', 'edit', 'delete'])
                ->make(true);
        }
        else
        {
            $model = new PermissionModule();
            return $this->showIndexPage($request, $model);
        }
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $input = $request->except('id');
        foreach ($input as $key => $value) {
			if($value === null) $input[$key] = "";
		}
        
        $model = PermissionModule::create($input);

        $message = 'SUCCESS';

        if( empty($model) )
            $message = 'Internal Server error';

        if ($request->ajax())
            return Response::json($model);
        else
            return back()->with('error', $message)->withInput();
    }

    function createData(Request $request)
    {
        $input = $request->except(['id']);

        try {
            $model = PermissionModule::create($input);
        } catch(PDOException $e){
            return Response::json([
                'success' => false,
                'message' => 'Hello'
            ], 422);
        }

        return Response::json($model);
    }


    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $model = PermissionModule::find($id);
        if( empty($model) )
            $model = new PermissionModule();

        return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
        $model = PermissionModule::find($id);

        $input = $request->except('id');
        $model->update($input);

        return $this->index($request);
    }

    public function destroy(Request $request, $id)
    {
        $model = PermissionModule::find($id);
        $model->delete();

        return $this->index($request);
    }
}
