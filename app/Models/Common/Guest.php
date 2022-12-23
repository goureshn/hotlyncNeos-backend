<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Models\Common\RoomOccupancy;

use DB;

class Guest extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_guest';
	public 		$timestamps = false;

	public static function getGuestList(&$datalist, $filter = '') {
		// get guest ids
		$guest_ids = array();

		foreach($datalist as $row){
			$guest_ids[] = $row->guest_id;
		}

		// find guests
		$query = DB::table('common_guest')
				->whereIn('guest_id', $guest_ids);

		$guests = $query->select(DB::raw('guest_id, guest_name'))
				->get();

		// save guest with guest_id
		$guest_info = array();
		foreach($guests as $row){
			$guest_info[$row->guest_id] = $row;
		}

		// set call info by guest_id
		foreach($datalist as $key => $row){
			if( array_key_exists($row->guest_id, $guest_info) )
				$datalist[$key]->guest_name = $guest_info[$row->guest_id]->guest_name;
			else
				$datalist[$key]->guest_name = '';
		}
	}

	public static function getCheckinCount($property_id, $date) {
		$checkin_list = DB::table('common_guest as cg')				
				->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->where('cg.checkout_flag', 'checkin')
				->where('cg.departure', '>=', $date)
				->where('cb.property_id', $property_id)	
				->groupBy('cg.room_id')
				->select(DB::raw('cg.room_id'))
				->distinct()
				->get();				

		return count($checkin_list);		
	}

	public static function addOccupancyData($property_id, $date) {
		$occupancy = RoomOccupancy::where('property_id', $property_id)
			->where('check_date', $date)
			->first();

		if( empty($occupancy) )
			$occupancy = new RoomOccupancy();

		$occupancy->property_id = $property_id;
		$occupancy->occupancy = Guest::getCheckinCount($property_id, $date);
		$occupancy->check_date = $date;

		$occupancy->save();		
	}

	public static function getGuestDetail(&$data_list) {
		foreach($data_list as $row) {
			$guest = DB::table('common_guest as cg')
				->where('cg.room_id', $row->id)
				->orderBy('cg.departure', 'desc')
				->orderBy('cg.arrival', 'desc')
				->first();

			if( empty($guest) )
				continue;

			$detail = DB::table('common_guest_advanced_detail as gad')
				->where('gad.id', $guest->id)
				->first();		

			if( empty($detail) )	
				continue;

			$row->audit_no = $detail->audit_no;
			$row->children = $detail->children;
			$row->guest_room_type = $detail->room_type;
		}		

	}
}