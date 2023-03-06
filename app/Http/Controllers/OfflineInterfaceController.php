<?php

namespace App\Http\Controllers;

use App;
use App\Models\Common\CommonUser;
use App\Models\Common\PropertySetting;
use App\Models\Service\HskpStatusLog;
use App\Modules\Functions;
use Artisan;
use DB;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Redirect;
use Response;
use View;
use DateTime;

class OfflineInterfaceController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index(Request $request)
    {

    }

    public function getCommonRoomTable(Request $request)
    {
        $property_id = $request->get('property_id', 4);
        $common_room = DB::table('common_room as cr')
            ->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
            ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
            ->where('cb.property_id', $property_id)
            ->select('cr.*')
			->get();
        return Response::json($common_room);
    }

    public function setServicesRoomStatus(Request $request)
    {
        $room_status_data = $request->all();
        $services_room = DB::table('services_room_status')->select('id')->get();

        foreach($services_room as $room){
            foreach ($room_status_data as $value) {
                if($value['id'] == $room->id){
                    

                    $currentdt = new DateTime("now");
                    $dtval = new DateTime($value['update_time']);

                    $currentdt_str = $currentdt->format('Y-m-d H:i:s');
                    $dtval_str = $dtval->format('Y-m-d H:i:s');

                    $currentdt_epoch = strtotime($currentdt_str);
                    $dtval_epoch = strtotime($dtval_str);
                    $dtInterval = round( ($currentdt_epoch-$dtval_epoch)/60 );

                    //if data is less than 5 min old
                    if($dtInterval <= 1){

                        $room_status_check = DB::table('services_room_status')
                        ->where('id', $room->id)
                        ->first();
                        
                        if($room_status_check->rm_state != $value['rm_state']){
                            
                            // save hskp log
                            $hskp_log = new HskpStatusLog();

                            $hskp_log->room_id = $room->id;
                            $hskp_log->hskp_id = 0;
                            $hskp_log->user_id = 0;
                            $hskp_log->method = "opera";

                            if($value['rm_state'] == "Out Of Order"){
                                $hskp_log->state = 9;
                            }
                            elseif ($value['rm_state'] == "Out Of Service") {
                                $hskp_log->state = 10;
                            }
                            elseif ($value['rm_state'] == "Clean") {
                                $hskp_log->state = 2;
                            }
                            elseif ($value['rm_state'] == "Dirty") {
                                $hskp_log->state = 0;
                            }
                            elseif ($value['rm_state'] == "Inspected") {
                                $hskp_log->state = 7;
                            }
                            else {
                                $hskp_log->state = 2;
                            }

                            //date_default_timezone_set(config('app.timezone'));
                            $cur_time = date("Y-m-d H:i:s");
                            $hskp_log->created_at = $cur_time;

                            $hskp_log->save();

                            $update = DB::table('services_room_status')
                            ->where('id', $room->id)
                            ->update(['room_status' => $value['rm_state']]);
                        }

                        $update = DB::table('services_room_status')
                        ->where('id', $room->id)
                        ->update(['rm_state' => $value['rm_state'] ,'fo_state' => $value['fo_state'] , 'ov_occupancy' => $value['ov_occupancy'] , 'room_status' => $value['rm_state']]);

                    
                    if ($value['rm_state'] == 'IP'){
                        DB::table('services_room_status')->where('id',$room->id)->update(['working_status' => 7]);
                    }
                    if ($value['rm_state'] == 'CL'){
                        DB::table('services_room_status')->where('id',$room->id)->update(['working_status' => 2]);
                    }

                    if ($value['rm_state'] == 'DI'){

                        $room_data = DB::table('services_room_status')->where('id',$room->id)->first();
                        if ($room_data->attendant_id == 0)
                            DB::table('services_room_status')->where('id',$room->id)->update(['working_status' => 100]);
                        else
                            DB::table('services_room_status')->where('id',$room->id)->update(['working_status' => 0]);
                    }

                    }

                }
            }
        }
        

        return Response::json("Updated");
        
    }
}