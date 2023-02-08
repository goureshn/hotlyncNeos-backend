<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Common\Chain;

use Redirect;

class ClientController extends Controller
{
    private $request;
	
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
	
    public function index(Request $request)
    {
		return view('backoffice.app');
    }

    public function create()
    {
		
    }

    public function store(Request $request)
    {
		$input = $request->all();
		$model = Chain::create($input);
		
		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		return back()->with('error', $message)->withInput();		
    }

    public function show($id)
    {
		
    }

    public function edit($id)
    {
		$model = Chain::find($id);	
		
		$step = '0';
			
		return view('backoffice.wizard.property.client', compact('client', 'step'));
    }

    public function update(Request $request, $id)
    {
		$step = '0';
		
		$model = Chain::find($id);
		
		$input = $request->all();
		
		$message = 'SUCCESS';
		
		if (!$model->update(input)) {
			$message = 'Internal Server error';		
		}	
		
		return view('backoffice.wizard.property.client', compact('client', 'step'));				
    }

    public function destroy($id)
    {
		$model = Chain::find($id);
		$model->delete();
		
		return Redirect::to('/backoffice/property/wizard/client');	
    }	
}
