<?php

namespace App\Http\Controllers;

use App\Models\Common\GuestLogin;
use App\Modules\UUID;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Response;

class GuestController extends Controller
{
	function login(Request $request)
	{
		$room_id = $request->get('room_id', 0);
		$guest_name = $request->get('guest_name', '');
		$language = $request->get('language', '');

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		// check common guest table
		$time_range = sprintf("(cg.arrival <= '%s' and cg.departure >= '%s')", $cur_date, $cur_date);
		$name_compare = sprintf("(cg.guest_name like '%% %s' or cg.guest_name like '%s %%' or cg.guest_name like '%%.%s' or cg.guest_name = '%s')", $guest_name, $guest_name, $guest_name, $guest_name);
		$guest = DB::table('common_guest as cg')
			->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
			->where('cg.room_id', $room_id)
			->whereRaw($name_compare)
			->where('cg.checkout_flag', 'checkin')
			->whereRaw($time_range)
			->select(DB::raw('cg.*, cr.room'))
			->first();

		$ret = array();

		$ret['code'] = 200;

		if( empty($guest) )
		{
			$ret['code'] = 201;
			$ret['message'] = 'Input fields are not correct';
			return Response::json($ret);
		}

		$uuid = new UUID();
		$access_token = $uuid->uuid;

		$guest_login = GuestLogin::find($guest->guest_id);
		if( empty($guest_login) )
			$guest_login = new GuestLogin();			
		
		$guest_login->id = $guest->guest_id;		
		$guest_login->access_token = $access_token;
		$guest_login->created_at = $cur_time;

		$guest_login->save();

		$guest->access_token = $access_token;
		$guest->language = $language;

		$ret['guest'] = $guest;

		return Response::json($ret);
	}

	
	function getRoomList(Request $request) {
		$property_id = $request->get('property_id', 4);

		$property = DB::table('common_property as cp')
			->where('cp.id', $property_id)
			->first();	

		$room_list = DB::table('common_room as cr')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->where('cb.property_id', $property_id)
			->select('cr.*')
			->get();

		$ret = array();
		
		$ret['property'] = $property;
		$ret['room_list'] = $room_list;	

		return Response::json($ret);
	}

	function logout(Request $request)
	{
		$guest_id = $request->get('guest_id', 0);
		
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d");

		$guest = GuestLogin::find($guest_id);
		if( !empty($guest) )
		{
			$guest->access_token = '';
			$guest->save();
		}

		app('App\Http\Controllers\ChatController')->sendGuestOffline($guest_id);

		return Response::json($guest);
	}

	
}
