<?php

namespace App\Http\Controllers\Intface;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Intface\Formatter;

use DB;
use Datatables;
use Response;

class FormatterController extends Controller
{	
    public function index(Request $request)
    {
		$datalist = DB::connection('interface')->table('formatter as fm')
					->leftJoin('protocol as pr', 'fm.protocol_id', '=', 'pr.id')
					->select(['fm.*', 'pr.name as prname']);
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
        //
    }

    public function store(Request $request)
    {
    	$input = $request->except('id');
		$model = Formatter::create($input);
		
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

  
    public function edit(Request $request, $id)
    {
        $model = Formatter::find($id);	
		if( empty($model) )
			$model = new Formatter();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = Formatter::find($id);	
		
        $input = $request->except('id');
		$model->update($input);
		
		return Response::json($model);
    }

    public function destroy(Request $request, $id)
    {
        $model = Formatter::find($id);
		$model->delete();

		return $this->index($request);
    }
}
