<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Models\Common\CommonFloor;
use App\Models\Service\EscalationGroup;

use Response;


class AjaxController extends Controller
{
    public function dropdownfloor(Request $request)
    {
		$build_id = $request->get('build_id', '1');		
		$floor = CommonFloor::where('bldg_id', $build_id)->get();
		
		return Response::json($floor);
    }
	
	public function dropdowndept(Request $request)
    {
		$dept_id = $request->get('deft_id', '1');		
		$deft = EscalationGroup::where('dept_func', $dept_id)->get();
		
		return Response::json($deft);
    }
}
