<?php

namespace App\Http\Controllers;

use App;
use App\Models\Common\Room;
use App\Models\Common\Guest;
use App\Models\Common\Building;
use App\Models\Service\HskpRoomStatus;
use App\Models\Service\Location;
use App\Models\Service\LocationType;
use App\Models\Common\CommonFloor;
use App\Models\Eng\EquipmentList;
use Artisan;
use DB;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Redirect;
use Response;
use View;

class RMSInterfaceController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index(Request $request)
    {

    }

    
    public function updateData(Request $request)
    {
        $rms_prop = DB::table('property_setting')
			            ->where('settings_key', 'rms_property_id')
                        ->select(['value'])
			            ->first();

        $property_id = 0;
        if (!empty($rms_prop)){

            $property_id = $rms_prop->value;   

        }
        if($property_id != 0){

            $this->getPropertyData($property_id);
            sleep(2);
            $this->getRoomTypeData($property_id);
            sleep(2);
            $this->getRoomData($property_id);
        } 
    
    }

    public function syncData(Request $request)
    {

        $rms_prop = DB::table('property_setting')
			            ->where('settings_key', 'rms_property_id')
                        ->select(['value'])
			            ->first();

        $property_id = 0;
        if (!empty($rms_prop)){

            $property_id = $rms_prop->value;   

        }
        if($property_id != 0){

            $this->getCommonRoomTable($property_id);
            sleep(2);
            $this->getCommonGuestDepartingTable($property_id);
            sleep(2);
            $this->getCommonGuestTable($property_id);
            sleep(2);
            $this->getCommonGuestArrivingTable($property_id);
            
        } 
       
    }

    public function syncguestData(Request $request)
    {

         $rms_prop = DB::table('property_setting')
			            ->where('settings_key', 'rms_property_id')
                        ->select(['value'])
			            ->first();

        $property_id = 0;
        if (!empty($rms_prop)){

            $property_id = $rms_prop->value;   

        }
        if($property_id != 0){
           
                $this->syncCommonGuestTable($property_id); 
            //$this->getCommonRoomTable($property_id); 

        //    $this->getReservationData($property_id);
        //    $this->getCommonGuestArrivingTable($property_id);

            //    $this->updateRoomStatus($property_id);

       
                
        }   
        
    }


    public function getauthToken(Request $request)
    {

        $rms_prop = DB::table('property_setting')
			            ->where('settings_key', 'rms_property_id')
                        ->select(['value'])
			            ->first();

        if (!empty($rms_prop)){

            if ($rms_prop->value != 0)
            {

                $url = 'https://restapi11.rmscloud.com/authToken';

                $method = "POST";
                $data = array("agentId" => 648,
					  "agentPassword" => "2Zjsj9nXTdQGDrq6!",
					  "clientId" => 12440,
					  "clientPassword" => '7z$JW4c*',
					  "useTrainingDatabase" => false,
					  "moduleType" => ["guestServices"]
                    );


		        $data_string = json_encode($data);
                $result = $this->fetchDatafromRMS($url, $data_string, $method);
       
                $result1 = json_decode($result);

                DB::table('property_setting as ps')
			        ->where('ps.settings_key', 'rms_authtoken')
			        ->update(['value' => $result1->token]);

            }

        }  
        
    }

    private function getPropertyData($property_id)
    {
        
        $url = 'https://restapi11.rmscloud.com/properties?modelType=Full';

        $method = "GET";
        $result = $this->fetchDatafromRMS($url, '', $method);
       
        $data = json_decode($result);

        foreach ($data as $row){

            $building = Building::where('property_id',$property_id)->where('ext_prpty_id',$row->id)->first();

            if (empty($building)){

                Building::insert(['property_id' => $property_id, 'name' => $row->name, 'description' => $row->name, 'ext_prpty_id' => $row->id]);
            //    CommonFloor::insert([''])

            }
        
        } 

        $build_type = LocationType::createOrFind('Building');


        $build_list = DB::table('common_building')
                    ->where('property_id', $property_id)
			        ->get();

        foreach($build_list as $row)
		{
			$location = Location::where('property_id', $property_id)
					->where('building_id', $row->id)
					->where('type_id', $build_type->id)
					->first();

			if( empty($location) )
			{
				$location = new Location();
				$location->type_id = $build_type->id;
				$location->property_id = $property_id;
				$location->building_id = $row->id;
			}		

			$location->name = $row->name;
			$location->desc = $row->description;
			$location->save();

            $floor = CommonFloor::where('bldg_id', $row->id)->first();

            if (empty($floor))
            {

                $floor = new CommonFloor();
                $floor->bldg_id = $row->id;
                $floor->floor = 'G';
                $floor->description = 'G-'.$row->name;
                $floor->save();

            }

			
		}

        $floor_list = DB::table('common_floor as cf')
				->join('common_building as cb','cf.bldg_id', '=', 'cb.id')
                ->where('cb.property_id', $property_id)
				->select(DB::raw('cf.*, cb.property_id'))
				->get();

        $floor_type = LocationType::createOrFind('Floor');

		foreach($floor_list as $row)
		{
			$location = Location::where('property_id', $row->property_id)
					->where('building_id', $row->bldg_id)
					->where('floor_id', $row->id)
					->where('type_id', $floor_type->id)
					->first();

			if( empty($location) )
			{
				$location = new Location();
				$location->type_id = $floor_type->id;
				$location->property_id = $row->property_id;
				$location->building_id = $row->bldg_id;
				$location->floor_id = $row->id;
			}		

			$location->name = $row->floor;
			$location->desc = $row->description;
			$location->save();

		}

        

    }

    private function getRoomTypeData($property_id)
    {


        $building = Building::where('property_id',$property_id)->select(DB::raw('id, ext_prpty_id'))->where('ext_prpty_id','!=',0)->get();

        foreach ($building as $row){
        
        
        $url = sprintf('https://restapi11.rmscloud.com/categories?propertyId=%s&modelType=basic' , $row->ext_prpty_id);

        $method = "GET";
        $result = $this->fetchDatafromRMS($url, '', $method);

    //    echo $result;
       
        $data = json_decode($result);

        foreach ($data as $row1){

            $room_type = DB::table('common_room_type')->where('bldg_id',$row->id)->where('type',$row1->name)->first();

            if (empty($room_type)){

                DB::table('common_room_type')->insert(['bldg_id' => $row->id, 'type' => $row1->name, 'description' => $row1->name, 'ext_type_id' => $row1->id, 'ext_prpty_id' => $row1->propertyId]);
            }
        
        } 

        }
    }

    private function getRoomData($property_id)
    {

   
        $building =DB::table('common_room_type')->select(DB::raw('ext_type_id'))->where('ext_type_id','!=',0)->get();
        foreach ($building as $row){

    


        $url = sprintf('https://restapi11.rmscloud.com/categories/%s/areas', $row->ext_type_id);

        $method = "GET";
        $result = $this->fetchDatafromRMS($url, '', $method);

    //    echo $result;
       
        $data = json_decode($result);

    

        foreach ($data as $row1){

            $room_type = DB::table('common_room_type')->where('ext_type_id',$row1->categoryId)->select(DB::raw('id'))->first();
            $floor = DB::table('common_floor as cf')
                        ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
                        ->where('cb.ext_prpty_id', $row1->propertyId)
                        ->select(DB::raw('cf.*'))
                        ->first();

            if ($row1->inactive == true)
                $enable = 0;
            else
                $enable = 1;
            

            $room = Room::where('room',$row1->name)->first();

                if (empty($room)){

                    Room::insert(['type_id' => $room_type->id,'flr_id' => $floor->id, 'room' => $row1->name, 'description' => $row1->name, 'ext_room_id' => $row1->id, 'enable' => $enable]);

                }
                else
                {
                    DB::table('common_room as cr')
                        ->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
			            ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
                        ->where('cb.ext_prpty_id', $row1->propertyId)
                        ->where('cr.room',$row1->name)
				        ->update(['cr.type_id' => $room_type->id,
                                  'cr.flr_id' => $floor->id,
						          'cr.description' => $row1->name, 
                                  'cr.ext_room_id' => $row1->id,
                                  'cr.enable' => $enable]);

                }

                $room_id = Room::where('ext_room_id', $row1->id)->select('id')->first();

                $hskproom_status = HskpRoomStatus::find($room_id->id);
                  if (empty($hskproom_status)) { 
				        $hskp_room_status = new HskpRoomStatus();

				        $hskp_room_status->id = $room_id->id;
				        $hskp_room_status->property_id = $property_id;   
					    $hskp_room_status->occupancy = 'Vacant';
                        $hskp_room_status->ov_occupancy = 'Vacant';
                        $hskp_room_status->rm_state = 'Dirty';
					    $hskp_room_status->arrival = 0;
					    $hskp_room_status->due_out = 0;
				        $hskp_room_status->working_status = 100;
				        $hskp_room_status->priority = 0;    // Highest
				        $hskp_room_status->save();
                  }

                  
        
            } 
   

        }

        $room_loc_type = LocationType::createOrFind('Room');

        $room_list = DB::table('common_room as cr')
					        ->join('common_floor as cf','cr.flr_id', '=', 'cf.id')
					        ->join('common_building as cb','cf.bldg_id', '=', 'cb.id')
                            ->where('cb.property_id', $property_id)
					        ->select(DB::raw('cr.*, cf.bldg_id, cb.property_id'))
					        ->get();

        foreach($room_list as $row)
		{
            if ($row->enable == 1)
                $disable = 0;
            else
                $disable = 1;

			$location = Location::where('property_id', $row->property_id)
					        ->where('building_id', $row->bldg_id)
					        ->where('floor_id', $row->flr_id)
					        ->where('room_id', $row->id)
					        ->where('type_id', $room_loc_type->id)
					        ->first();

			if( empty($location) )
			{
				$location = new Location();
				$location->type_id = $room_loc_type->id;
				$location->property_id = $row->property_id;
				$location->building_id = $row->bldg_id;
				$location->floor_id = $row->flr_id;
				$location->room_id = $row->id;
			}		

			$location->name = $row->room;
			$location->desc = $row->description;
            $location->disable = $disable;
			$location->save();	

            date_default_timezone_set(config('app.timezone'));
            $curdate = date("Y-m-d");
            $curtime = date("Y-m-d H:i:s");

            $equip_loc = Location::where('property_id', $row->property_id)
                            ->where('name', $row->room) 
                            ->first();

            $equipment =  EquipmentList::where('name', $row->room)->first();
            if (empty($equipment))
            {
                $equipment = new EquipmentList();
                $equipment->property_id = $row->property_id;
                $equipment->name = $row->room;
                $equipment->description = $row->room;
                $equipment->qr_code = '';
                $equipment->critical_flag = 0;
                $equipment->external_maintenance = 0;
                $equipment->external_maintenance_id = 0;
                $equipment->dept_id = 24;
                $equipment->life = 0;
                $equipment->life_unit = 'days';
                $equipment->equip_group_id = 2;
                $equipment->part_group_id = 0;
                $equipment->location_group_member_id = $equip_loc->id;
                $equipment->sec_loc_id = 0;
                $equipment->purchase_cost = 0;
                $equipment->purchase_date = $curdate;
                $equipment->manufacture = '';
                $equipment->status_id = 0;
                $equipment->model = 0;
                $equipment->barcode = 0;
                $equipment->warranty_start = $curdate;
                $equipment->warranty_end = $curdate;
                $equipment->supplier_id = 0;
                $equipment->image_url = '';
                $equipment->maintenance_date = $curtime;
                $equipment->category_id = 0;
                $equipment->residual_value = '';
                $equipment->current_value = 0;
                $equipment->save();

            }
            $max = EquipmentList::select(DB::raw('max(id) as max_id'))->first();

            EquipmentList::where('id', $max->max_id)->update(['equip_id' => $max->max_id ]);

		}

        
        
    }

    

     private function syncCommonGuestTable($property_id)
    {

        date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d 00:00:00");

        $curdate = date("Y-m-d");
        
        

        

        $building = Building::where('property_id',$property_id)->select(DB::raw('ext_prpty_id'))->where('ext_prpty_id','!=',0)->get();
        
        foreach($building as $row1)
        {
        $url = sprintf('https://restapi11.rmscloud.com/reservations/inhouse?propertyId=%s', $row1->ext_prpty_id);
        $method = "GET";
	
        
        

        $result = $this->fetchDatafromRMS($url, '', $method);

        echo $result;
    
        $data = json_decode($result);

        
        foreach ($data as $row){

            $url1 = sprintf('https://restapi11.rmscloud.com/guests/%s?modelType=full', $row->guestId);
           
            $method1 = 'GET'; 

            $result2 = $this->fetchDatafromRMS($url1, '', $method1);

            $result1 = json_decode(str_replace (array('[',']'), '' , $result2));
        

            $exist_guest = Guest::where('guest_id', $row->id)->where('property_id', $property_id)->where('checkout_flag','checkin')
                        ->first();

            $room_id = Room::where('ext_room_id', $row->areaId)->where('ext_room_id','!=', 0)->select('id')->first();

            if(empty($room_id))
                    $room = 0;
            else 
                    $room = $room_id->id;

            if (empty($exist_guest)){

                $newguest = new Guest();

                $newguest->guest_id = $row->id;
                $newguest->profile_id = $row->guestId;
		        $newguest->property_id = $property_id;
                $newguest->guest_group = 0;
                $newguest->guest_name = $result1->guestGiven . ' ' . $result1->guestSurname;
                $newguest->first_name = $result1->guestGiven;
                $newguest->title = $result1->title;
                $newguest->adult =  $row->adults;
                $newguest->chld =  $row->children + $row->infants;
                $newguest->email = $result1->email;
                $newguest->mobile = $result1->mobile;
                $newguest->room_id = $room;
                $newguest->arrival = date($row->arrivalDate);
                $newguest->departure = date($row->departureDate);
                $newguest->share = '0';
                $newguest->checkout_flag = 'checkin';
                $newguest->booking_src = $row->bookingSourceName;

                $newguest->save();

                // save to log
                $this->saveGuestLog('checkin', $newguest->id);
            
                
            }
        
            if (($room != 0)){

                if (date("Y-m-d",strtotime($row->departureDate)) == $curdate){
                    DB::table('services_room_status')
              	        ->where('id', $room)
					    ->update(['fo_state' => 'Due Out', 'due_out' => 1]);
                }else{
                    DB::table('services_room_status')
              	        ->where('id', $room)
					    ->update(['fo_state' => $row->status]);
                }
            }

            

        }
        


    }
        
    }


    private function getCommonRoomTable($property_id)
    {

        date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

        $building =DB::table('common_room_type')->select(DB::raw('ext_type_id'))->where('ext_type_id','!=',0)->get();
        foreach($building as $row)
        {

        $url = sprintf('https://restapi11.rmscloud.com/categories/%s/areas', $row->ext_type_id);

        $method = "GET";
        $result = $this->fetchDatafromRMS($url, '', $method);
       
    //    echo $result;
        $data = json_decode($result);

        foreach ($data as $row1){


            if ($row1->inactive == true)
                $enable = 0;
            else
                $enable = 1;

            DB::table('common_room')->where('room',$row1->name)->update(['enable' => $enable]);

            $status = explode(" ", $row1->cleanStatus);
            if (count($status) == 1){
                $status[1] = '';
            }


    
            $room_status =  DB::table('services_room_status as rs')
                            ->join('common_room as cr', 'rs.id','=','cr.id')
                            ->where('cr.ext_room_id',$row1->id)
                            ->where('rs.property_id',$property_id)
                            ->first();

            if (!empty($room_status)){

                if ($status[0] == 'Maintenance'){

                 DB::table('services_room_status as rs')
                            ->join('common_room as cr', 'rs.id','=','cr.id')
                            ->where('cr.ext_room_id',$row1->id)
                            ->where('rs.property_id',$property_id)
                            ->update(['rs.ov_occupancy' => 'Vacant','rs.occupancy' => 'Vacant',
						'rs.rm_state' => $status[0], 'rs.room_status' => $status[0], 'rs.service_state' => 'OOO']);
                }else{
                    DB::table('services_room_status as rs')
                            ->join('common_room as cr', 'rs.id','=','cr.id')
                            ->where('cr.ext_room_id',$row1->id)
                            ->where('rs.property_id',$property_id)
                            ->update(['rs.ov_occupancy' => $status[0],'rs.occupancy' => $status[0],
						'rs.rm_state' => $status[1], 'rs.room_status' => $status[1], 'rs.service_state' => 'Available']);

                }

               

            if ($status[0] == 'Vacant' || $status[0] == 'Maintenance' )
            {
                DB::table('services_room_status as rs')
                            ->join('common_room as cr', 'rs.id','=','cr.id')
                            ->where('cr.ext_room_id',$row1->id)
                            ->where('rs.property_id',$property_id)
                            ->update(['rs.due_out' => 0, 'rs.fo_state' => '']);

               

            }
            if ($room_status->fo_state == 'Arrived')
            {
                DB::table('services_room_status as rs')
                            ->join('common_room as cr', 'rs.id','=','cr.id')
                            ->where('cr.ext_room_id',$row1->id)
                            ->where('rs.property_id',$property_id)
                            ->update(['rs.due_out' => 0]);

               

            }
            if ($room_status->fo_state == 'Arrived' || $room_status->fo_state == 'Due Out')
            {
               
                DB::table('services_room_status as rs')
                            ->join('common_room as cr', 'rs.id','=','cr.id')
                            ->where('cr.ext_room_id',$row1->id)
                            ->where('rs.property_id',$property_id)
                            ->update(['rs.arrival' => 0]);
            }

            if ($status[1] == 'Dirty')
            {
               
               $room = DB::table('services_room_status as rs')
                            ->join('common_room as cr', 'rs.id','=','cr.id')
                            ->where('cr.ext_room_id',$row1->id)
                            ->where('rs.property_id',$property_id)
                            ->first();

                if ($room->attendant_id == 0){

                    DB::table('services_room_status as rs')
                            ->join('common_room as cr', 'rs.id','=','cr.id')
                            ->where('cr.ext_room_id',$row1->id)
                            ->where('rs.property_id',$property_id)
                            ->update(['rs.working_status' => 100]);
                }else{

                    DB::table('services_room_status as rs')
                            ->join('common_room as cr', 'rs.id','=','cr.id')
                            ->where('cr.ext_room_id',$row1->id)
                            ->where('rs.property_id',$property_id)
                            ->update(['rs.working_status' => 0]);

                }
            }

        }

        if ($status[0] == 'Vacant'){
            $hskp_info = DB::table('services_hskp_status as hs')
					->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
					->where('hs.status', $row1->cleanStatus)
					->where('cb.property_id', $property_id)
					->select(DB::raw('hs.*'))
					->first();

		    $room = Room::where('ext_room_id',$row1->id)->first();

		    $room->hskp_status_id = $hskp_info->id;

		    $room->save();
        }

        $room_loc_type = LocationType::createOrFind('Room');

         if ($row1->inactive == true)
                $disable = 1;
            else
                $disable = 0;

        $location = Location::where('property_id', $property_id)
					        ->where('name', $row1->name)
					        ->where('type_id', $room_loc_type->id)
					        ->update(['disable' => $disable]);

        
        
        } 
        }

        echo "RMS Room Status Synced at " . $cur_time;
    }

    


    private function getCommonGuestTable($property_id)
    {

        date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
        $curdate = date("Y-m-d");
        
        $guest = Guest::where('checkout_flag', 'checkin')->where('property_id', $property_id)->select('guest_id')->get();

    //    echo json_encode($guest);

        $building = Building::where('property_id',$property_id)->select(DB::raw('ext_prpty_id'))->where('ext_prpty_id','!=',0)->get();
        $perm1 = [];
        foreach($building as $row1)
        {
        $url = sprintf('https://restapi11.rmscloud.com/reservations/inhouse?propertyId=%s', $row1->ext_prpty_id);
        $method = "GET";
	
        
        

        $result = $this->fetchDatafromRMS($url, '', $method);

    //    echo $result;
    
        $data = json_decode($result);

        
        foreach ($data as $row){

        
            

            $perm1[] .= $row->id; 

            $url1 = sprintf('https://restapi11.rmscloud.com/guests/%s?modelType=full', $row->guestId);
           
            $method1 = 'GET'; 

            $result2 = $this->fetchDatafromRMS($url1, '', $method1);

            $result1 = json_decode(str_replace (array('[',']'), '' , $result2));
        

            $exist_guest = Guest::where('guest_id', $row->id)->where('property_id', $property_id)->where('checkout_flag','checkin')
                        ->first();

            $room_id = Room::where('ext_room_id', $row->areaId)->where('ext_room_id','!=', 0)->select('id')->first();

            if(empty($room_id))
                    $room = 0;
            else 
                    $room = $room_id->id;

            if (!empty($result1)){

            if (empty($exist_guest)){

                $newguest = new Guest();

                $newguest->guest_id = $row->id;
                $newguest->profile_id = $row->guestId;
		        $newguest->property_id = $property_id;
                $newguest->guest_group = 0;
                $newguest->guest_name = $result1->guestGiven . ' ' . $result1->guestSurname;
                $newguest->first_name = $result1->guestGiven;
                $newguest->title = $result1->title;
                $newguest->adult =  $row->adults;
                $newguest->chld =  $row->children+$row->infants;
                $newguest->email = $result1->email;
                $newguest->mobile = $result1->mobile;
                $newguest->room_id = $room;
                $newguest->arrival = date($row->arrivalDate);
                $newguest->departure = date($row->departureDate);
                $newguest->share = '0';
                $newguest->checkout_flag = 'checkin';
                $newguest->booking_src = $row->bookingSourceName;

                $newguest->save();

                $this->saveGuestLog('checkin', $newguest->id);
            
                
            }
            else{

                if ($exist_guest->room_id == $room)
                {

                    $exist_guest->guest_id = $row->id;
                    $exist_guest->profile_id = $row->guestId;
		            $exist_guest->property_id = $property_id;
                    $exist_guest->guest_group = 0;
                    $exist_guest->guest_name = $result1->guestGiven . ' ' . $result1->guestSurname;
                    $exist_guest->first_name = $result1->guestGiven;
                    $exist_guest->title = $result1->title;
                    $exist_guest->adult =  $row->adults;
                    $exist_guest->chld =  $row->children+$row->infants;
                    $exist_guest->email = $result1->email;
                    $exist_guest->mobile = $result1->mobile;
                    $exist_guest->room_id = $room;
                    $exist_guest->arrival = date($row->arrivalDate);
                    $exist_guest->departure = date($row->departureDate);
                    $exist_guest->share = '0';
                    $exist_guest->checkout_flag = 'checkin';
                    $exist_guest->booking_src = $row->bookingSourceName;

                    $exist_guest->save();
            
             

                    
                }
                else{

               

               

                    $exist_guest->checkout_flag = 'checkout';
                    $exist_guest->save();

                    

                    
                    $newguest = new Guest();

                    $newguest->guest_id = $row->id;
                    $newguest->profile_id = $row->guestId;
		            $newguest->property_id = $property_id;
                    $newguest->guest_group = 0;
                    $newguest->guest_name = $result1->guestGiven . ' ' . $result1->guestSurname;
                    $newguest->first_name = $result1->guestGiven;
                    $newguest->title = $result1->title;
                    $newguest->adult =  $row->adults;
                    $newguest->chld =  $row->children+$row->infants;
                    $newguest->email = $result1->email;
                    $newguest->mobile = $result1->mobile;
                    $newguest->room_id = $room;
                    $newguest->arrival = date($row->arrivalDate);
                    $newguest->departure = date($row->departureDate);
                    $newguest->share = '0';
                    $newguest->checkout_flag = 'checkin';
                    $newguest->booking_src = $row->bookingSourceName;

                    $newguest->save();

                    $this->saveGuestLog('roomchange', $newguest->id);


                }  
                
            }
        }

            
            
            if ($room != 0){
                $checkin = Guest::where('guest_id', $row->id)->first();
                if (!empty($checkin)){
                if (date("Y-m-d",strtotime($row->departureDate)) == $curdate && $checkin->checkout_flag == 'checkin'){
                    DB::table('services_room_status')
              	        ->where('id', $room)
					    ->update(['fo_state' => 'Due Out','due_out' => 1]);
                }else{
                    DB::table('services_room_status')
              	        ->where('id', $room)
					    ->update(['fo_state' => $row->status]);
                }
            }
            }

            

        }

        
        }

    

        foreach($guest as $data)
        {
            if (!in_array($data->guest_id, $perm1)){
               
                Guest::where('guest_id', $data->guest_id)->where('checkout_flag', 'checkin')->where('property_id',$property_id)
                     ->update(['checkout_flag' => 'checkout']);


                $checkout_guest = Guest::where('guest_id', $data->guest_id)->where('property_id',$property_id)->first();

                $this->saveGuestLog('checkout', $checkout_guest->id);

            }

        }
    
    echo "RMS Guest Synced at " . $cur_time;
        
    }

    private function getCommonGuestArrivingTable($property_id)
    {

        date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d 00:00:00");

        $building = Building::where('property_id',$property_id)->select(DB::raw('ext_prpty_id'))->where('ext_prpty_id','!=',0)->get();

        foreach($building as $row1){
        $url = sprintf('https://restapi11.rmscloud.com/reservations/arriving?propertyId=%s' , $row1->ext_prpty_id);


        $method = "GET";
        $result = $this->fetchDatafromRMS($url, '', $method);

    //    echo $result; 
        $data = json_decode($result);
        foreach ($data as $row){

            $room = Room::where('ext_room_id', $row->areaId)->where('ext_room_id','!=', 0)->select('id')->first();
   
            if (!empty($room)){
            $room_info = DB::table('services_room_status as r')
                    ->where('r.id', $room->id )
                    ->where('r.property_id', $property_id)
                    ->first();

            
            
            if (!empty($room_info)){

                if ($room_info->ov_occupancy == 'Occupied'){
                    $fo_state = $room_info->fo_state;
                    DB::table('services_room_status')
              	        ->where('id', $room_info->id)
					    ->update(['fo_state' => '']);
                    DB::table('services_room_status')
              	        ->where('id', $room_info->id)
					    ->update(['fo_state' => $row->status . '/' . $fo_state, 'arrival' => 1]);
                }else{
                    DB::table('services_room_status')
              	        ->where('id', $room_info->id)
					    ->update(['fo_state' => $row->status, 'arrival' => 1]);

                }
            }
        }
  
        }
    }
    
    echo "RMS Arrival Guest Synced at " . $cur_time;

    }

    private function getCommonGuestDepartingTable($property_id)
    {

        date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d 00:00:00");

        $building = Building::where('property_id',$property_id)->select(DB::raw('ext_prpty_id'))->where('ext_prpty_id','!=',0)->get();

        foreach($building as $row1)
        {
        
        $url = sprintf('https://restapi11.rmscloud.com/reservations/departing?propertyId=%s' , $row1->ext_prpty_id);

      
        $method = "GET";

        $result = $this->fetchDatafromRMS($url, '', $method);

    //    echo $result;
        $data = json_decode($result);
        foreach ($data as $row){
   
            $room = Room::where('ext_room_id', $row->areaId)->where('ext_room_id','!=', 0)->select('id')->first();
            if (!empty($room)){
            $room_info = DB::table('services_room_status as r')
                    ->where('r.id', $room->id )
                    ->where('r.property_id', $property_id)
                    ->first();
            
            if (!empty($room_info)){

                if ($room_info->ov_occupancy == 'Occupied'){

                    DB::table('services_room_status')
              	        ->where('id', $room_info->id)
					    ->update(['fo_state' => 'Due Out', 'due_out' => 1]);
                }
            }
        }
        
        }
    }
    
    echo "RMS Departing Guest Synced at " . $cur_time;

    }

    private function getReservationData($property_id)
    {

        date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");
		$cur_date = date("Y-m-d 00:00:00");

        $building = Building::where('property_id',$property_id)->select(DB::raw('ext_prpty_id'))->where('ext_prpty_id','!=',0)->get();

        foreach($building as $row1)
        {
        
        $url = 'https://restapi11.rmscloud.com/reservations/search?limit=500&modelType=full&offset=0';

      
        $method = "POST";

        $data = array("propertyIds" => [$row1->ext_prpty_id],
					  "createdFrom" => $cur_date,
					  );


		$data_string = json_encode($data);

        $result = $this->fetchDatafromRMS($url, $data_string, $method);

    //    echo $result;
        $data1 = json_decode($result);
        foreach ($data1 as $row){

            $exist_guest = DB::table('common_guest_reservation')->where('res_id', $row->id)->where('property_id', $property_id)
                        ->first();
   
            $room_id = Room::where('ext_room_id', $row->areaId)->select('id')->first();

            if(empty($room_id))
                    $room = 0;
            else 
                    $room = $room_id->id;
            if (empty($exist_guest))
            {

                 DB::table('common_guest_reservation') 
				        ->insert(['res_id' => $row->id,
                                  'property_id' => $property_id,
						          'room_id' => $room, 
                                  'guest_name' => $row->guestGiven . ' ' . $row->guestSurname,
                                'first_name' => $row->guestGiven,
                                'start_date' =>$row->arrivalDate,
                                'end_date' => $row->departureDate,
                                'adult' => $row->adults,
                                'chld' => $row->children,
                                'booking_src' => $row->bookingSourceName,
                                'booking_note' => $row->notes,
                                'status' => $row->status
                            ]);
        }
        else{
                DB::table('common_guest_reservation') 
                        ->where('property_id', $property_id)
                        ->where('res_id', $row->id)
				        ->update([
						        'room_id' => $room, 
                                'guest_name' => $row->guestGiven . ' ' . $row->guestSurname,
                                'first_name' => $row->guestGiven,
                                'start_date' =>$row->arrivalDate,
                                'end_date' => $row->departureDate,
                                'adult' => $row->adults,
                                'chld' => $row->children,
                                'booking_src' => $row->bookingSourceName,
                                'booking_note' => $row->notes,
                                'status' => $row->status
                            ]);

        }
        
        }
    }
    
    echo "RMS Reservation Guest Synced at " . $cur_time;

    }

    
    private function fetchDatafromRMS($url, $data_string, $method)
	{

        $authToken = DB::table('property_setting as ps')
						->select(DB::raw('ps.value'))
						->where('ps.settings_key', 'rms_authtoken')
						->first();

        $arr_header = "authtoken: " . $authToken->value;
		
		$ch = curl_init();

		$headers = [
			'Content-Type: application/json',
			$arr_header
		];


		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$output=curl_exec($ch);
		curl_close($ch);

		return $output;
	}

    private function saveGuestLog($action, $id) {
        // save to log
        $guest = Guest::where('id', $id)
            ->first();

        if( empty($guest) )
            return; 

        $log = $guest->toArray();    

        unset($log['id']);
        $log['action'] = $action;
        DB::table('common_guest_log')->insert($log);
    }

    // private function updateRoomStatus($property_id)
	// {

    //     // $authToken = DB::table('property_setting as ps')
	// 	// 				->select(DB::raw('ps.value'))
	// 	// 				->where('ps.settings_key', 'rms_authtoken')
	// 	// 				->first();

    // //    $arr_header = "authtoken: " . $authToken->value;

    // $data_string = '';
    		
	// 	$url = 'https://restapi11.rmscloud.com/areas/687/cleanStatus?status=vacantDirty';
	// 	$ch = curl_init();

	// 	$headers = [
	// 		'Content-Type: application/json',
	// 		'authtoken: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhZ2lkIjoiNjQ4IiwiY2xpZCI6IjEyNDQwIiwibmJmIjoxNjYwODI2MTc3LCJleHAiOjE2NjA5MTI1NzYsImlhdCI6MTY2MDgyNjE3NywiaXNzIjoid3d3LnJtc2Nsb3VkLmNvbSJ9.LGcrIIqgW8OhJ42WYfyQfKmBzQVFRTe8wGq5HcSe_1A'
	// 	];


	// 	curl_setopt($ch,CURLOPT_URL,$url);
	// 	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
	// 	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	// 	$output=curl_exec($ch);
	// 	curl_close($ch);

	// 	return $output;
	// }
}