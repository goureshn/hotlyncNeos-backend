<?php

namespace App\Http\Controllers\Intface;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\Intface\Logs;

use DB;
use Datatables;
use Response;
use DateTime;

class LogController extends Controller
{	
    public function index(Request $request)
    {
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$request_date = $request->get('start_date', $cur_date);

		$start_date = new DateTime($request_date);
//		$end_date = new DateTime($request_date);
//		$end_date->add(new DateInterval('P1D'));

		$datalist = Logs::where('timestamp', '>', $start_date)->get();

		return Datatables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('delete', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-click="onDeleteRow()">
						<span class="glyphicon glyphicon-trash"></span>
					</button></p>';
				})
				->make(true);
    }
	

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
    	$input = $request->except('id');
		$model = Channel::create($input);
		
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
        $model = Logs::find($id);	
		
		return Response::json($model);
    }

  
    public function edit(Request $request, $id)
    {
        $model = Channel::find($id);	
		if( empty($model) )
			$model = new Channel();
		
		return $this->showIndexPage($request, $model);
    }

    public function update(Request $request, $id)
    {
		$model = Channel::find($id);	
		
        $input = $request->except('id');
		$model->update($input);
		
		return Response::json($model);
    }

    public function destroy(Request $request, $id)
    {
        $model = Channel::find($id);
		$model->delete();

		return $this->index($request);
    }
}
