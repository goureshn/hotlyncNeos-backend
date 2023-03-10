<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Call\Section;

use Response;

class SectionController extends Controller
{
    private $request;
	
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
	
    public function getList()
    {
		$model = Section::all();
	
		return Response::json($model);
    }
    
}
