<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
class MinibarRosterList extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_minibar_roster_list';
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
					->whereRaw("FIND_IN_SET($dept_func_id, sd.dept_func_array_id)")					 
	 				->select(DB::raw('sr.id,sr.location_list,sr.device'))
					->get();
		$arr=[];
		$i;

		foreach ($data as $key => $value) 
		{
			$arr= json_decode($value->location_list);
			if(in_array($room_id, $arr))
				$i=$value;
		} 

	 	if( empty($i) )
	 		return 0;

	 	return $i;
	 }
	 static function getAssignedDevices(&$rooms,$dept_func,$filters,$roomlist)
     {
		 $data = DB::table('services_roster_list as sr')
					->join('services_devices as sd', 'sd.id', '=', 'sr.device');
		 if( $dept_func > 0 )			
			$data->whereRaw("FIND_IN_SET($dept_func, sd.dept_func_array_id)");

		foreach ($rooms as $key => $value) 
		{
						
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
			$room_lists[] = $value;
		}
		$rooms = $room_lists;

	 	$data = $data->select(DB::raw('sr.id, sd.name as device_name, sr.location_list'))
					 ->get();

		
		$arr=[];
					
					 
		foreach ($data as $key => $value) {
			$arr= json_decode($value->location_list);

			foreach ($rooms as $key1 => $row) 
			{
				if(in_array($row->id, $arr) && (!in_array($value->device_name,$row->assigned_device_list)))
				{					
					$rooms[$key1]->assigned_device_count=(($rooms[$key1]->assigned_device_count)+1);
					$rooms[$key1]->assigned_device_list[]=$value->device_name;
				}
			}
		}
				
				
		if(!empty($filters['unassigned']) && ($filters['unassigned'] ==true))
		{
			$room_temp=[];
			foreach ($rooms as $key1 => $value1) {
				if($value1->assigned_device_count == 0) 
				{
					//echo 'Room: '.$value1->room.'Count: '.$value1->assigned_device_count;
					$room_temp[]=$value1;
				}
			}
			$rooms=$room_temp;
		}
					 
	 	if( empty($i) )
	 		return 0;

	 	return $i;
	 }
	 
	 
	  static function getUnassignedFloorRooms(&$floors,$dept_func,$filters)
     {
		 $data = DB::table('services_roster_list as sr')
		 			->join('services_devices as sd', 'sd.id', '=', 'sr.device');
		if( $dept_func > 0 )			
			$data->whereRaw("FIND_IN_SET($dept_func, sd.dept_func_array_id)");

		$data = $data->select(DB::raw('sr.id,sr.location_list'))
					 ->get();

		$location_arr=[];
		$arr=[];
		$i;
		$query= DB::table('services_room_status as rs')
			->join('common_room as cr', 'rs.id', '=', 'cr.id');


		if(!empty($filters['occupied']) && $filters['occupied'] ==true)
		{
			$query->where('rs.occupancy', 'Occupied' );
		}		
		$rooms=$query->select(DB::raw('cr.*'))
				->get();
					

		foreach ($data as $key => $value) {
			$arr= json_decode($value->location_list);
			foreach ($arr as $value1) 
			{
				if(!in_array($value1,$location_arr))
					$location_arr[]=$value1;
			}	
		}
				
								
		foreach ($rooms as $key1 => $row1) 
		{
			foreach ($floors as $key2 => $value2) {					
				if(($value2->id)==($row1->flr_id))
				{					
					$floors[$key2]->room_count=(($floors[$key2]->room_count)+1);
					$floors[$key2]->floor_credits=(($floors[$key2]->floor_credits)+$row1->credits);
					

					if(!in_array($row1->id, $location_arr))
					{	
						$floors[$key2]->unassigned_count=(($floors[$key2]->unassigned_count)+1);
						$floors[$key2]->unassigned_list[]=$row1->room;
					}
				}
			}
		}

		if(!empty($filters['unassigned']) && ($filters['unassigned'] ==true))
		{
			$floor_temp=[];	
			foreach ($floors as $key => $value) {
				if($value->unassigned_count > 0) 
					$floor_temp[]=$value;
			}

			$floors=$floor_temp;
		}
					

	 	if( empty($data) )
	 		return 0;

	 	return 1;
     }

   
}