<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Models\Service\Device;
use App\Models\Common\PropertySetting;
use App\Modules\Functions;
use App\Models\Common\Room;

class RosterList extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_roster_list';
	public 		$timestamps = false;
	
	
     static function findlocationIDs($begin_date_time,$device)
     {
		 $data = DB::table('services_roster_list as sr')
				 ->whereRaw("sr.begin_date_time <='" . $begin_date_time . "' AND sr.end_date_time >= '" . $begin_date_time . "'")
				// whereRaw("DATE(created_at) = '" . $cur_date . "'")
	 				->where('sr.device', $device)
	 				->select(DB::raw('sr.location_list'))
					 ->get();
					 $location_arr=[];
					 $arr=[];
					 $i;
				foreach ($data as $key => $value) {
					//for($i=0;$i<count($value->location_list);$i++)) {
						// $location_arr=$row;
						$arr= json_decode($value->location_list);
						foreach ($arr as $row) {
							if(!(in_array($row, $location_arr)))
							$location_arr[]=$row;
						}
					//}
				}	 

	 	if( empty($data) )
	 		return 0;

	 	return $location_arr;
	 }
	 
	static function findRosterIDfromRoom($dept_func_id, $room_id)
    {
		$data = DB::table('services_roster_list as sr')
					->join('services_devices as sd','sd.id','=','sr.device')
					->whereRaw("FIND_IN_SET(".$room_id.", sr.location_list)")
					->whereRaw("FIND_IN_SET(".$dept_func_id.", sd.dept_func_array_id)")
					->select(DB::raw('sr.*'))
					->first();

		return $data;			
	}

	static function getRosterListFromRoomDeptFunc($dept_func_id, $room_id)
    {
		$data = DB::table('common_users as cu')
					->join('services_devices as sd','sd.device_id','=','cu.device_id')
					->join('services_roster_list as srl','sd.id','=','srl.device')
					->join('services_room_status as sr','srl.id','=','sr.attendant_id')
					//->whereRaw("FIND_IN_SET(".$room_id.", sr.location_list)")
					->where('sr.id', $room_id)
					->whereRaw("FIND_IN_SET(".$dept_func_id.", sd.dept_func_array_id)")
					->where('cu.deleted', 0)
					->where('cu.active_status', 1)
					->select(DB::raw('cu.*, cu.id as user_id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, sd.number'))
					->groupBy('cu.id')
					->get();

		
					if( count($data) > 0 ){

						return $data;	
					}
					else{
			
						$data = DB::table('common_users as cu')
								->join('services_devices as sd','sd.device_id','=','cu.device_id')
							//	->join('services_roster_list as srl','sd.id','=','srl.device')
							//	->join('services_room_status as sr','srl.id','=','sr.attendant_id')
								//->whereRaw("FIND_IN_SET(".$room_id.", sr.location_list)")
							//	->where('sr.id', $room_id)
							//	->whereRaw("FIND_IN_SET(".$dept_func_id.", sd.dept_func_array_id)")
								->whereRaw('FIND_IN_SET('.$dept_func_id.', sd.sec_dept_func)')	
								->where('cu.deleted', 0)
								->where('cu.active_status', 1)
								->select(DB::raw('cu.*, cu.id as user_id, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, sd.number'))
								->groupBy('cu.id')
								->get();
			
						return $data;	
					}				
	}

	static function findSupRosterfromRoom($job_role,$room_id)
    {
		 
		$data = DB::table('services_roster_list as rs')
		->leftJoin('services_devices as sd', 'sd.id', '=', 'rs.device')
		->leftJoin('common_users as cu', 'cu.username', '=', 'sd.device_user')
				->where('job_role_id', $job_role)
				->select(DB::raw('rs.*, cu.id as supervisor_id,CONCAT_WS(" ", cu.first_name, cu.last_name) as supervisor'))
				->get();

		$arr = [];
		$i;
		foreach ($data as $key => $value) {
			$arr= json_decode($value->location_list);

			if(in_array($room_id, $arr))
			$i=$value;
		}
					 

	 	if( empty($i) )
	 		return 0;

	 	return $i;
	 }

	static function getAssignedDevices(&$rooms, $dept_func, $filters, $roomlist)
	{
		$data = DB::table('services_roster_list as sr')
			->join('services_devices as sd', 'sd.id', '=', 'sr.device');

		if( $dept_func > 0 )			
			$data->whereRaw("FIND_IN_SET($dept_func, sd.dept_func_array_id)");
			//$roomlist=[];

		foreach ($rooms as $key => $value) {						
			if($value->share==1)
			{
				$arr=(array_keys($roomlist,$value->room));
				if(count($arr)>1)
				{
					foreach ($arr as $value1) {							
						if($key==$value1)
						{	
							unset($rooms[$value1]);
							unset($roomlist[$value1]);
						}
					}
				}
			}
		}

		$room_lists=[];
		foreach($rooms as $key => $value)
		{
			$room_lists[]=$value;
		}
		$rooms = $room_lists;

	 	$data = $data->select(DB::raw('sr.id, sd.name as device_name, sr.location_list'))
					 ->get();

		$arr = [];
					 
		foreach ($data as $key => $value) {
			$arr = json_decode($value->location_list, true);

			foreach ($rooms as $key1 => $row) {
				if(in_array($row->id, $arr) && (!in_array($value->device_name, $row->assigned_device_list)))
				{
					$rooms[$key1]->assigned_device_count = (($rooms[$key1]->assigned_device_count)+1);
					$rooms[$key1]->assigned_device_list[] = $value->device_name;
				}
			}
		}
		
		if(!empty($filters['unassigned']) && ($filters['unassigned'] ==true))
		{
			$room_temp = [];
			foreach ($rooms as $key1 => $value1) {
				if($value1->assigned_device_count == 0) 
				{
					//echo 'Room: '.$value1->room.'Count: '.$value1->assigned_device_count;
					$room_temp[]=$value1;
				}
			}
			$rooms = $room_temp;
		}
					 

	 	if( empty($i) )
	 		return 0;

	 	return $i;
	 }
	 
	 
	static function getUnassignedFloorRooms($hskp_role, $filters, $filters1, $building_id, $floor_name)
    {
		$cur_date = date("Y-m-d");
	
		$query = DB::table('common_floor as cf')				
				->join('common_room as cr', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('services_room_status as rs', 'rs.id', '=', 'cr.id')
				->leftJoin('common_guest as cg', function($join) use ($cur_date) {
						$join->on('cr.id', '=', 'cg.room_id');
						$join->on('cg.departure','>=',DB::raw($cur_date));
						$join->on('cg.checkout_flag','=', DB::raw("'checkin'"));
					});

		if( $building_id > 0 )
			$query->where('cf.bldg_id', $building_id);
		if( !empty($floor_name) )
			$query->where('cf.floor', 'like', '%' . $floor_name . '%');			

		if( $hskp_role == 'Attendant' )			
			$query->where('rs.attendant_id', 0);

		if( $hskp_role == 'Supervisor' )			
			$query->where('rs.supervisor_id', 0);	

			
		if(!empty($filters['dirty']) && $filters['dirty'] ==true)
			$query->where('rs.room_status', 'Dirty');

		if(!empty($filters['clean']) && $filters['clean'] ==true)
			$query->where('rs.room_status', 'Clean');
			
		if(!empty($filters['inspected']) && $filters['inspected'] ==true)
			$query->where('rs.room_status', 'Inspected');

		if(!empty($filters['dueout']) && $filters['dueout'] ==true)
			$query->where('cg.departure', $cur_date);

		if(!empty($filters1['vacant']) && $filters1['vacant'] ==true)
			$query->where('rs.occupancy', 'Vacant' );

		if(!empty($filters1['occupied']) && $filters1['occupied'] ==true)
			$query->where('rs.occupancy', 'Occupied' );

		if(!empty($filters['unassigned']) && ($filters['unassigned'] ==true))
		{
			$query->havingRaw("COUNT(cr.id) > 0");
		}
						

		$floors = $query->select(DB::raw('cf.*, cb.name, CONCAT_WS(" - ", cb.name, cf.floor) as floor_name, 
											count(cr.id) as unassigned_count, sum(cr.credits) as floor_credits,
											GROUP_CONCAT(cr.id) as room_list'))
					->groupBy('cf.id')
					->orderBy('cf.id', 'asc')
					->get();
		
	 	return $floors;
	}

	static function updateFromDevice($device)
	{
		$room_list = DB::table('services_room_status as srs')
			->join('common_room as cr', 'srs.id', '=', 'cr.id')
			->where('device_ids', $device->device_id)	
			->select(DB::raw('srs.*, cr.credits'))
			->get();

		$roster = RosterList::where('device', $device->id)->first();
		if( empty($roster) )
		{
			$roster = new RosterList();
			$roster->device = $device->id;			
			$roster->name = $device->name;			
		}
		
		$roster->location_list = json_encode(array_map(function($item) {
			return $item->id;
		}, $room_list->toArray()));

		$total_credits = 0;
		$turn_down_list = [];
		foreach($room_list as $item)
		{
			$total_credits += $item->credits;
			if( $item->td_flag == 1 )
				$turn_down_list[] = $item->id;
		}

		$roster->td_list = json_encode($turn_down_list);
		$roster->total_credits = $total_credits;

		$roster->save();

		return $roster;
	}



	static function getUserFromRoster($roster)
	{
		if( empty($roster) )
			return array();

		$query = DB::table('common_users as cu')
			->join('common_department as cd', 'cu.dept_id', '=', 'cd.id');			

		if( $roster->user_id > 0 )
		{
			$query->where('cu.id', $roster->user_id);
		}
		else
		{
			$query->join('services_devices as sd', 'sd.device_id', '=', 'cu.device_id')
				->where('sd.id', $roster->device);
		}

		$user = $query
				->select(DB::raw('cu.*, cd.property_id'))
				->first();

		return $user;		
	}

	static function sendRosterNotification($roster, $message)
	{	
		$user = RosterList::getUserFromRoster($roster);			
		
		if (empty($user))
			return;

		$payload = array();
		$payload['broadcast_flag']=0;
		$payload['task_id'] = 0;
		$payload['table_id'] = 0;
		$payload['table_name'] = '';
		$payload['type'] = 'Room Assignment';
		$payload['header'] = 'Housekeeping';

		$payload['ack'] = 0;

		//$payload['table_name'] = 'services_task';

		$payload['property_id'] = $user->property_id;
		$payload['notify_type'] = 'roster';
		$payload['notify_id'] = 0;

		$user->mobile = Device::getDeviceNumber($user->device_id);
		
		Functions::sendPushMessgeToDeviceWithRedisNodejs(
			$user,0, $payload['type'], $message, $payload
		);
	}

	static function sendRushCleanNotification($room_status)
	{	
		if( empty($room_status) )
			return 'Empty Room Status';
		$roster = RosterList::find($room_status->attendant_id);
		if( empty($roster) )
			return 'Empty Roster';

		$room = Room::find($room_status->id);
		if( empty($room) )
			return 'Empty Room';

		$user = RosterList::getUserFromRoster($roster);			
		
		if (empty($user))
		{			
			return 'Empty User';
		}

		$payload = array();
		$payload['broadcast_flag'] = 0;
		$payload['task_id'] = $roster->id;
		$payload['table_id'] = 'id';
		$payload['table_name'] = 'services_room_status';

	
		$payload['type'] = "Rush Room - $room->room";
		$payload['header'] = "Housekeeping";

		$payload['ack'] = 0;

		//$payload['table_name'] = 'services_task';

		$payload['property_id'] = $user->property_id;
		$payload['notify_type'] = 'roster';
		$payload['notify_id'] = 0;

		$message = "Room $room->room is at the top and needs to be cleaned as soon as possible";

		$result = Functions::sendPushMessgeToDeviceWithRedisNodejs(
			$user, 0, $payload['type'], $message, $payload
		);

		return $message;
	}

	static function getCurrentCleaningRoomID($roster_id)
	{
		$room_status = DB::table('services_room_status')
			->where('attendant_id', $roster_id)
			->where('working_status', 1)
			->first();

		if( empty($room_status) )
			return -1;
			
		return $room_status->id;	
	} 

	static function resetRoomCredits()
	{
		// update credits
		DB::select("
			UPDATE services_roster_list AS rl
				SET rl.total_credits = (
				SELECT COALESCE(SUM(cr.`credits`), 0) 
				FROM services_room_status AS rs 
				INNER JOIN common_room AS cr ON rs.id = cr.id
				WHERE rs.`attendant_id` = rl.id
				)
		");
	}
	
	function updateRosterInfo($hskp_role, $property_id)
	{
		$query = DB::table('services_room_status as srs')
				->join('common_room as cr', 'srs.id', '=', 'cr.id');

		if( $hskp_role == 'Attendant' )	
			$query->where('attendant_id', $this->id);

		if( $hskp_role == 'Supervisor' )	
			$query->where('supervisor_id', $this->id);

		$room_list = $query->select(DB::raw('srs.*, cr.credits'))			
			->get();

		$this->location_list = json_encode(array_map(function($item) {
			return $item->id;
		}, $room_list->toArray()));

		$turn_down_list = [];
		foreach($room_list as $item)
		{
			if( $item->td_flag == 1 )
				$turn_down_list[] = $item->id;
		}

		$this->td_list = json_encode($turn_down_list);
		$total_credits = RosterList::getTotalCredits($hskp_role, $this->id, 0, $property_id);
		$this->total_credits = $total_credits;

		$this->save();		
	}

	static function getTotalCredits($hskp_role, $roster_id, $room_id, $property_id)
	{
		$setting['hskp_inspection_flag'] = 0;
		$setting = PropertySetting::getPropertySettings($property_id, $setting);				
		$hskp_inspection_flag = $setting['hskp_inspection_flag'];

		date_default_timezone_set(config('app.timezone'));
		$cur_date = date("Y-m-d");

		$query = DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id')
			->leftJoin('services_hskp_status as hs', 'cr.hskp_status_id', '=', 'hs.id')
			->leftJoin('common_guest as cg', function($join) use ($cur_date) {
					$join->on('cr.id', '=', 'cg.room_id');
					$join->on('cg.departure','>=',DB::raw($cur_date));
					$join->on('cg.checkout_flag','=', DB::raw("'checkin'"));
				});

		if( $roster_id > 0 )
		{
			if( $hskp_role == 'Attendant' )
				$query->where('rs.attendant_id', $roster_id);

			if( $hskp_role == 'Supervisor' )
				$query->where('rs.supervisor_id', $roster_id);			
		}

		if( $room_id > 0 )
			$query->where('rs.id', $room_id);

		$list = $query->select(DB::raw('rs.*, cr.credits, cg.checkout_flag'))
					->get();

		$total_credits = 0;
		foreach($list as $row)
		{
			$credits = $row->credits;

			if( $hskp_inspection_flag == 0 )
			{
				// room is checkout and clean or inspected then based on setting the credit should be 0
				if( $row->checkout_flag == 'checkout' &&
					($row->room_status == 'Inspected' || $row->room_status == 'Clean') )
					$credits = 0;
			}

			// No Service Rooms
			if ($row->working_status == 11){
				$credits = 0;
			}

			$total_credits += $credits;
		}	

		return $total_credits;
	}

	static function getRosterIds($device_id, $user_id)
	{
		// both device based and user based
		$roster_list = DB::table('services_roster_list as rs')
				->join('services_devices as sd', 'sd.id', '=', 'rs.device')				
				->where('sd.device_id',$device_id)
				->orWhere('rs.user_id', $user_id)
				->select(DB::raw('rs.*'))
				->get();

		$roster_ids = [];
		foreach($roster_list as $row)
			$roster_ids[] = $row->id;	
			
		return $roster_ids;	
	}

	static function setRosterIdsForHskpLog(&$hskp_log, $room_status)
	{
		if( empty($room_status) )
			return;

		$roster = RosterList::find($room_status->attendant_id);
		if( !empty($roster) )
		{
			$roster_user = RosterList::getUserFromRoster($roster);
			if( !empty($roster_user) )
				$hskp_log->attendant_id = $roster_user->id;
		}	
			
		$roster = RosterList::find($room_status->supervisor_id);
		if( !empty($roster) )
		{
			$roster_user = RosterList::getUserFromRoster($roster);
			if( !empty($roster_user) )
				$hskp_log->supervisor_id = $roster_user->id;	
		}
	}
}
