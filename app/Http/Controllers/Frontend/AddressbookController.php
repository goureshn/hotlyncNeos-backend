<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

use DB;
use Illuminate\Http\Request;
use Response;

class AddressbookController extends Controller
{
	function getUserlist(Request $request) {
		$user_id = $request->get('user_id', 0);
		$client_id = $request->get('client_id', 0);

		$userlist = DB::table('marketing_guest as mg')
			->select(DB::raw('mg.*, CONCAT_WS(" ", mg.first_name, mg.last_name) as wholename'))
			->get();

		return Response::json($userlist);	
	}
}