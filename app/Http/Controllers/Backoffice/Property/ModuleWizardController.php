<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Common\Module;

use Redirect;
use DB;
use Datatables;
use Response;

class ModuleWizardController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $datalist = DB::table('common_module');

            return Datatables::of($datalist)
                ->addColumn('checkbox', function ($data) {
                    return '<input type="checkbox" class="checkthis" />';
                })
                ->addColumn('edit', function ($data) {
                    return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal" ng-disabled="job_role!=\'SuperAdmin\'&&edit_flag==0" ng-click="onShowEditRow('.$data->id.')">
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
            $model = new Chain();

            $model->name = '';
            $model->description = '';

            $step = '0';

            return view('backoffice.wizard.property.module', compact('model', 'step'));
        }
    }


    public function create()
    {

    }

    public function store(Request $request)
    {
        $input = $request->except('id');
        $model = Module::create($input);

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

    }

    public function edit($id)
    {
        $model = Module::find($id);

        $step = '0';

        return view('backoffice.wizard.property.module', compact('client', 'step'));
    }

    public function update(Request $request, $id)
    {
        $step = '0';

        $model = Module::find($id);

        $input = $request->all();

        $message = 'SUCCESS';

        if (!$model->update($input)) {
            $message = 'Internal Server error';
        }

        return Response::json($model);
    }

    public function destroy(Request $request, $id)
    {
        $model = Module::find($id);
        $model->delete();

        return Response::json($model);
    }

    public function getModuleList(Request $request) {

        $model = Db::table('common_module')            
            ->get();

        return Response::json($model);
    }
}
