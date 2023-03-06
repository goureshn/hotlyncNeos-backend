<?php

namespace App\Http\Controllers;

use Auth;
use Hash;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
	private $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
	
	function setting($step)
	{
		$next = "";
		
		if( $step == 'client' )
			$next = 'property';
		if( $step == 'property' )
			$next = 'building';
		if( $step == 'building' )
			$next = 'floor';
		if( $step == 'floor' )
			$next = 'room';
		if( $step == 'room' )
			$next = 'confirm';
		if( $step == 'confirm' )
			$next = 'client';
		
		return view('admin.setting.' . $step, compact('step','next'));
	}
	
}
