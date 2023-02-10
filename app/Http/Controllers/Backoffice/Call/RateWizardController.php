<?php

namespace App\Http\Controllers\Backoffice\Call;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;

use Excel;

class RateWizardController extends UploadController
{
   	public function index(Request $request)
    {
		$step = '3';
		return view('backoffice.wizard.call.rate', compact('model', 'step'));;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    	$input = $request->all();
		$model = CommonFloor::create($input);
		
		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		return back()->with('error', $message)->withInput();	
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
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
        $model = CommonFloor::find($id);	
		if( empty($model) )
			$model = new CommonFloor();
		
		return $this->showIndexPage($request, $model);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
		$model = CommonFloor::find($id);	
		
        $input = $request->all();
		$model->update($input);
		
		return $this->index($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $model = CommonFloor::find($id);
		$model->delete();

		return $this->index($request);
    }
	
	public function parseExcelFile($path)
	{
		Excel::selectSheets('Floor')->load($path, function($reader) {
			$rows = $reader->all()->toArray();
			for($i = 0; $i < count($rows); $i++ )
			{
				foreach( $rows[$i] as $data )
				{
					//echo json_encode($data);
					
					$bldg_id = $data['bldg_id'];
					$floor = $data['floor'];
					if( CommonFloor::where('bldg_id', $bldg_id)->where('floor', $floor)->exists() )
						continue;					
					CommonFloor::create($data);
				}
			}							
		});
	}
}
