<?php

namespace App\Http\Controllers\Backoffice\Engineering;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Eng\Subcategory;

use Redirect;
use DB;
use Response;
use Datatables;

class SubcategoryController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index(Request $request)
    {
        // delete action
        $ids = $request->input('ids');
        if( !empty($ids) )
        {
            Section::whereIn('id', $ids)->delete();
            return back()->withInput();
        }


        $partgroup = DB::table('eng_request_subcategory as ers')
            ->where('ers.name', 'like', '%name%')
            ->select('ers.id', 'ers.name')
            ->pluck('name', 'id');

        $step = '0';

        return view('backoffice.wizard.engineering.subcategory', compact('name', 'description', 'code'));
    }

    public function getGridData()
    {
        $datalist = DB::table('eng_request_subcategory as ers')
            ->leftJoin('eng_request_category as erc', 'ers.category_id', '=', 'erc.id')
            ->leftJoin('common_property as cp', 'ers.property_id', '=', 'cp.id')
            ->select(['ers.*','cp.name as cpname','erc.name as category_name']);

        return Datatables::of($datalist)
            ->addColumn('checkbox', function ($data) {
                return '<input type="checkbox" class="checkthis" />';
            })
            ->addColumn('edit', function ($data) {
                return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  onClick="onShowEditRow('.$data->id.')">
						<span class="glyphicon glyphicon-pencil"></span>
					</button></p>';
            })
            ->addColumn('delete', function ($data) {
                return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" onClick="onDeleteRow('.$data->id.')">
						<span class="glyphicon glyphicon-trash"></span>
					</button></p>';
            })
            ->make(true);
    }

    public function getGridNgData()
    {
        $datalist = DB::table('eng_request_subcategory as ers')
            ->leftJoin('eng_request_category as erc', 'ers.category_id', '=', 'erc.id')
            ->leftJoin('common_property as cp', 'ers.property_id', '=', 'cp.id')
            ->select(['ers.*','cp.name as cpname','erc.name as category_name']);

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
        $input = $request->except(['id']);

        try {
            $model = Subcategory::create($input);
        } catch(PDOException $e){
            return Response::json($input);
        }

        return Response::json($model);
    }

    public function createData(Request $request)
    {
        return $this->store($request);
    }

    public function show($id)
    {
        $model = Subcategory::find($id);

        return Response::json($model);
    }

    public function edit($id)
    {

    }

    public function updateData(Request $request)
    {
        $id = $request->get('id', '0');

        return $this->update($request, $id);
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();

        $model = Subcategory::find($id);

        if( !empty($model) )
            $model->update($input);

        return Response::json($model);
    }

    public function destroy($id)
    {
        $model = Subcategory::find($id);
        $model->delete();

        return Response::json($model);
    }

}
