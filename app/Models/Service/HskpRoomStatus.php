<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Models\Service\RosterList;
use App\Models\Common\PropertySetting;

class HskpRoomStatus extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_room_status';
	
	static function getRoomStatusQuery1($hskp_role = '')
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$query = DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
			->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
			->leftJoin('services_minibar_posting_status as mps', 'mps.id', '=', 'rs.minibar_post_status')
			->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
			->leftJoin('common_guest as cg', function($join) use ($cur_date) {
					$join->on('cr.id', '=', 'cg.room_id');
					$join->on('cg.departure','>=',DB::raw($cur_date));
					$join->on('cg.checkout_flag','=', DB::raw("'checkin'"));
				})
			->leftjoin('common_vip_codes as cvc', 'cg.vip', '=', 'cvc.vip_code')	
			->leftJoin('common_guest_remark_log as grl', function($join) use ($cur_date) {
					$join->on('grl.room_id', '=', 'cg.room_id');
					$join->on('grl.guest_id', '=', 'cg.guest_id');
					$join->where('grl.expire_date','>=', DB::raw($cur_date));					
				})
			->leftJoin('services_room_working_status as srws', 'rs.working_status', '=', 'srws.status_id');

		if( !empty($hskp_role) )
		{	
			$query->leftJoin('services_hskp_checklist_list as hcl', function($join) use ($hskp_role) {
				$join->on('hcl.room_type_id', '=', 'cr.type_id');
				$join->on('hcl.hskp_role', '=', DB::raw("'$hskp_role'"));
				$join->on('hcl.active', '=', DB::raw("'true'"));
			});
		}

		return $query;	
	}

	static function getRoomStatusQuery2($query, $hskp_role = '')
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$checklist_active_select = '';
		if( !empty($hskp_role) )
		{
			$checklist_active_select = ", COALESCE(hcl.id, 0) as checklist_id, COALESCE(hcl.name, '') as checklist_name";
		}

		$query->select(DB::raw("cr.*,cf.floor, cb.property_id, hs.status, rt.id as room_type_id, cvc.id as vip_id,
					cg.pref,cg.adult,cg.chld, grl.remark, rs.rush_flag,rs.schedule,rs.fo_state,
					rt.type as room_type, rs.working_status, rs.td_flag, rs.td_working_status, rs.service_state, rs.start_time, rs.end_time as updated_at,
					rs.occupancy,cg.arrival,cg.departure,cg.guest_name, cg.guest_id, cg.vip,mps.posting_status,rs.minibar_post_id as post_id,
					rs.room_status, rs.attendant_id, rs.attendant_check_num, rs.supervisor_check_num, 
					 cg.checkout_flag, rs.due_out, rs.full_clean_date,
					srws.status_name as cleaning_state,
					CASE WHEN linen_date = '$cur_date' THEN 1 ELSE 0 END as linen_change
					$checklist_active_select
					"));

		return $query;			
	}

	static function applyFilterSort($query, $hskp_role, $filter, $sortby)
	{
		$filterlist = explode(",", $filter);
		if( !in_array("All", $filterlist) )
		{
			$query->whereIn('srws.status_name', $filterlist);
		}

		if($sortby != '')
		{
			switch($sortby){
				case 'Room':
					$query->orderBy('cr.room');					
					break;
				case 'Floor':
					$query->orderBy('cf.floor');					
					break;
				case 'Room Type':
					$query->orderBy('rt.type');					
					break;
				case 'Occupancy':
					$query->orderBy('rs.occupancy');							
					break;
				case 'Cleaning Status':
					if( $hskp_role == 'Attendant')
						$query->orderBy('srws.attendant_order');	
					if( $hskp_role == 'Supervisor')
						$query->orderBy('srws.supervisor_order');						
					break;		
			}		
		}
		$query->groupBy('cr.room');
		$query->orderBy('cr.room');						

		return $query;
	}

    static function getRoomListForRoster($roster_id, $hskp_role, $filter = "", $sortby="Room")
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$query = HskpRoomStatus::getRoomStatusQuery1($hskp_role);
		
		if( $hskp_role == 'Attendant' )
		{
			$query->where('rs.attendant_id', $roster_id);
		}
		if( $hskp_role == 'Supervisor' )
		{
			$query->where('rs.supervisor_id', $roster_id);
		}

		$query = HskpRoomStatus::applyFilterSort($query, $hskp_role, $filter, $sortby);
		
		$query = HskpRoomStatus::getRoomStatusQuery2($query, $hskp_role);

		$room_list = $query->get();

		foreach($room_list as $row){

			$schedule = DB::table('services_hskp_schedule')
						->where('id', $row->schedule)
						->select(DB::raw('days'))
						->first();

			if (!empty($schedule)){
				if ($row->working_status == 11){
					$row->cleaning = "No Service";
				}
				else{
					$row->cleaning = "Full Service";
				}
			}
			else{
				if ($row->occupancy == 'Occupied'){

					if ($row->full_clean_date == $cur_date){

						$row->cleaning = "Full";
					}
					else{

						$row->cleaning = "Partial";
					}	
				}
				else{
					$row->cleaning = '';
				}
			}
		}
		
		return $room_list;
	}

	static function getRoomListForRosterWithBinded($roster_id, $hskp_role, $binded_attendant_ids, $binded_supervisor_ids, $filter = "", $sortby="Room")
	{
		if( $hskp_role == 'Supervisor' )	
				$roster = RosterList::where('id', $roster_id)->select('location_list')->first();
		if( $hskp_role == 'Attendant' )	
				$roster = RosterList::where('id', $binded_supervisor_ids)->select('location_list')->first();
		
		$location = json_decode($roster->location_list);

		if (empty($location))
		 	$location = [];
		
		
		$query = HskpRoomStatus::getRoomStatusQuery1($hskp_role);
		
		if( $hskp_role == 'Attendant' )
			$query->where('rs.attendant_id', $roster_id);

		if(( $hskp_role == 'Supervisor' ) && count($location) > 0)
			$query->where('rs.supervisor_id', $roster_id);
	
		if( count($binded_attendant_ids) > 0 )
			$query->whereIn('rs.attendant_id', $binded_attendant_ids);

		if( (count($binded_supervisor_ids) > 0 ) && count($location) > 0)
			$query->whereIn('rs.supervisor_id', $binded_supervisor_ids);	

		$query = HskpRoomStatus::applyFilterSort($query, $hskp_role, $filter, $sortby);
		
		$query = HskpRoomStatus::getRoomStatusQuery2($query, $hskp_role);
		$room_list = $query->get();
		
		return $room_list;
	}
	
	static function getRoomListForRosterList($roster_ids, $hskp_role, $filter = "", $sortby="Room")
	{
		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");
		$query = HskpRoomStatus::getRoomStatusQuery1($hskp_role);

		$roster = RosterList::where('id', $roster_ids)->select('location_list')->first();
		$location = json_decode($roster->location_list);
		
		if (empty($location))
		 	$location = [];
		
		$query->orderBy('rs.rush_flag', 'desc');

		if( $hskp_role == 'Attendant' )
		{
			$query->whereIn('rs.attendant_id', $roster_ids);
		}
		if(( $hskp_role == 'Supervisor' ) && count($location) > 0)
		{
			$query->whereIn('rs.supervisor_id', $roster_ids);
		}

		$query = HskpRoomStatus::applyFilterSort($query, $hskp_role, $filter, $sortby);
		
		$query = HskpRoomStatus::getRoomStatusQuery2($query, $hskp_role);
		$room_list = $query->get();

		if( $hskp_role == 'Supervisor' )
		{
			foreach($room_list as $row)
			{
				$roster = RosterList::find($row->attendant_id);
				$user = RosterList::getUserFromRoster($roster);
				if( empty($user) )
					$row->attendant_name = '';
				else
					$row->attendant_name = "$user->first_name $user->last_name";				
			}
		}

		foreach($room_list as $row){

				$row->arrival_status = $row->fo_state;

			if ($row->working_status == 8){

				$room_info = DB::table('services_hskp_log as hl')
						->where('hl.room_id', $row->id)
						->where('hl.state', 8)
						->select(DB::raw('hl.reason'))
						->first();

				if (!empty($room_info))	{	
						$row->pref = $room_info->reason;
				}
			}
			else{

				$row->pref = $row->remark;
			}

			$schedule = DB::table('services_hskp_schedule')
						->where('id', $row->schedule)
						->select(DB::raw('days'))
						->first();

			if (!empty($schedule)){
				if ($row->working_status == 11){
					$row->remark = "No Service";
				}
				else{
					$row->remark = "Full Service";
				}
			}
			else{
				if ($row->occupancy == 'Occupied'){

					if ($row->full_clean_date == $cur_date){

						$row->remark = "Full";
					}
					else{

						$row->remark = "Partial";
					}	
				}
				else{
					$row->remark = '';
				}
			}
		}
		
		return $room_list;
	}

	static function updateRoomCredits($room_list, $property_id)
	{
		if( empty($room_list))
			return;

		$setting['hskp_inspection_flag'] = 0;
		$setting = PropertySetting::getPropertySettings($property_id, $setting);		
		$hskp_inspection_flag = $setting['hskp_inspection_flag'];		

		foreach($room_list as $row)
		{
			if( $hskp_inspection_flag == 0 )
			{
				// room is checkout and clean or inspected then based on setting the credit should be 0
				if( $row->checkout_flag != 'checkin' &&
					($row->room_status == 'Inspected' || $row->room_status == 'Clean') )
					$row->credits = 0;
			}

			// No Service Rooms
			if ($row->working_status == 11){
				$row->credits = 0;
			}
		}			
	}
	
	static function getRoomStatus($room_id)
	{
		$query = HskpRoomStatus::getRoomStatusQuery1();
		
        $query->where('rs.id', $room_id);
		
		$query = HskpRoomStatus::getRoomStatusQuery2($query);

		$room = $query->first();
		
		return $room;
	}

	static function isActiveChecklist($room_id, $hskp_role)
	{		
		$exists =  DB::table('common_room as cr')
			->leftJoin('services_hskp_checklist_list as hcl', function($join) use ($hskp_role) {
				$join->on('hcl.room_type_id', '=', 'cr.type_id');
				$join->on('hcl.hskp_role', '=', DB::raw("'$hskp_role'"));
				$join->on('hcl.active', '=', DB::raw("'true'"));
			})
			->where('cr.id', $room_id)
			->exists();
		
		return $exists;	
	}

	public function getHskpStatusId()
	{
		$status_name = "$this->occupancy $this->room_status"; 
		$hskp_info = DB::table('services_hskp_status')
			->where('status', $status_name)
			->first();

		$hskp_status_id = 0;
		if( !empty($hskp_info) )
			$hskp_status_id = $hskp_info->id;

		return $hskp_status_id;	
	}
}