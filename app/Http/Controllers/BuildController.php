<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Common\Building;

use DB;

use Response;

class BuildController extends Controller
{
    private $request;
	
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
	    
	public function getList()
    {
		$model = Building::all();	
		
		return Response::json($model);
    }
}
