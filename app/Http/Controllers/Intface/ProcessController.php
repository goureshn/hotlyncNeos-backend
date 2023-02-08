<?php

namespace App\Http\Controllers\Intface;

use App\Models\Call\AdminCall;
use App\Models\Call\AdminChargeMap;
use App\Models\Call\CarrierCharges;
use App\Models\Call\CarrierGroup;
use App\Models\Call\Destination;
use App\Models\Call\GroupDestination;
use App\Models\Call\GuestCall;
use App\Models\Call\BCCall;
use App\Models\Call\GuestChargeMap;
use App\Models\Call\HotelCharges;
use App\Models\Call\StaffExternal;
use App\Models\Call\Tax;
use App\Models\Call\TimeSlab;
use App\Models\Call\Whitelist;
use App\Models\IVR\IVRAgentStatus;
use App\Models\Common\Property;
use App\Models\Common\Room;
// use App\Models\Common\LinenChange;
use App\Models\Service\VIPCodes;
use App\Models\Service\HskpStatus;
use App\Models\Service\HskpRoomStatus;
use App\Models\Service\RoomServiceGroup;
use App\Models\Service\RoomServiceItem;
use App\Models\Call\Allowance;
use App\Models\Intface\Protocol;
use App\Models\IVR\IVRUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;

use App\Models\Common\Guest;
use App\Models\Common\GuestAdvancedDetail;
use App\Models\Call\GuestExtension;
use App\Models\Intface\Alarm;
use App\Models\Intface\Parser;
use App\Models\Intface\Formatter;
use App\Models\Call\CallTemp;
use App\Models\Common\PropertySetting;
use App\Models\Service\HskpStatusLog;
use App\Modules\Functions;

use App\Models\Service\Wakeup;
use App\Models\Service\WakeupLog;


use Response;
use DateTime;
use DateInterval;
use Redis;
use Log;
use Illuminate\Contracts\Encryption\DecryptException;

header('Content-type: application/json; charset=utf-8');
//use Request;

define("P_SUCCESS", "200");            // successfully

define("MISSING_PARAMETER", "100"); // Parameter missing
define("INVALID_PARAMETER", "101"); // Parameter is invacheckUserValiditylid

define("DEVICE_IPHONE", "iphone");    // device type iPhone
define("DEVICE_ANDROID", "android");// device type Android
define("LIMITNUM", "10");    // device type iPhone

define('AES_256_CBC', 'aes-256-cbc');
define('AES_KEY', 'bf3c199c2470cb477d907b1e0917c17b');
define('AES_IV', '5183666c72eec9e4');

function isNullOrEmptyString($question)
{
    return (!isset($question) || trim($question) === '');
}

class ProcessController extends Controller
{
    function process(Request $request, $action)
    {        
        switch ($action) {
            case 'channellist':
                $this->getChannelList($request);
                break;
            case 'setchannel':
                $this->setChannel($request);
                break;
            case 'alarmlist':
                $this->getAlarmList($request);
                break;
            case 'parserformatterlist':
                $this->getParserFormatterList($request);
                break;
            case 'formatterlist':
                $this->getFormatterList($request);
                break;
            case 'db_swap_internal':
                    $this->dbSwapInternal($request);
                    break;
            case 'checkin':
            case 'checkin_a0a1':
            case 'checkin_gva0a1':
            case 'checkinnew':
            case 'checkinnogvgg':
                $this->checkin($request);
                break;
            case 'checkin_adv':
                $this->checkin_adv($request);
                break;
            case 'checkin_swap_nogv':
            // case 'checkin_swap_a0a1':
            // case 'checkin_swap_gva0a1':
            case 'checkin_swap':
                $this->checkin_swap($request);
                break;
            case 'checkout':
                $this->checkout($request);
                break;
            case 'checkout_swap':
                $this->checkout_swap($request);
                break;
            case 'checkoutnoguest_swap':
                $this->checkoutnoguest_swap($request);
                break;
            case 'guestinfo':
            case 'guestinfogv':
                $this->guestinfo($request);
                break;
            case 'roomchange':
                $this->roomchange($request);
                break;
            case 'donotdisturb':
                $this->donotdisturb($request);
                break;
            case 'classofservice':
           
                $this->classofservice($request);
                break;
            case 'messagelamp':
                $this->messagelamp($request);
                break;
            case 'mlcsdn_checkin':
                $this->mlcsdn_checkin($request);
                break;
            case 'mlcsdn_checkout':
                $this->mlcsdn_checkout($request);
                break;
            case 'nightaudit':
                $this->nightaudit($request);
                break;
            case 'wakeupcall':
                $this->wakeupcall($request);
                break;
            case 'databaseswap':
                $this->databaseswap($request);
                break;
            case 'databaseswapfromhotlync':
                $this->databaseswapFromHotlync($request);
                break;
            case 'callcharge_incoming':
                $this->callcharge($request, 0); // incoming
                break;
            case 'callcharge_internal':
                $this->callcharge($request, 1); // internal
                break;
            case 'callcharge_missed':
                $this->callcharge($request, 2); // missed
                break;
            case 'callcharge_outgoing':
                $this->callcharge($request, 3); // outgoing
                break;
            case 'callcharge_trans_out_s':
                $this->callcharge_trans_start($request, 3); // outgoing
                break;
            case 'callcharge_trans_out_e':
                $this->callcharge_trans_end($request, 3); // outgoing
                break;
            case 'callcharge_trans_in_s':
                $this->callcharge_trans_start($request, 0); // incoming
                break;
            case 'callcharge_trans_in_e':
                $this->callcharge_trans_end($request, 0); // incoming
                break;
            case 'callcharge_manual':
                $this->callcharge_manual($request); // internal
                break;            
            case 'roomstatus':
                $this->roomstatus($request);
                break;
            case 'minibarpost':
                $this->minibarpost($request);
                break;
        }
    }

    public function getChannelList($request)
    {
        $channellist = DB::connection('interface')->table('channel as cn')->get();

        for ($i = 0; $i < count($channellist); $i++) {
            $channellist[$i]->property_name = Property::find($channellist[$i]->property_id)->name;
            $channellist[$i]->protocol_data = Protocol::find($channellist[$i]->protocol_id);
        }

        echo json_encode($channellist, JSON_UNESCAPED_UNICODE);
    }

    public function setChannel($request)
    {
        $channel = $request->input('id', '0');
        Redis::set('channel', $channel);

        echo json_encode(Redis::get('channel'), JSON_UNESCAPED_UNICODE);
    }


    public function getAlarmList()
    {
        echo json_encode(Alarm::all(), JSON_UNESCAPED_UNICODE);
    }

    public function getParserFormatterList($request)
    {
        $id = $request->input('id', '0');
        $property_id = $request->input('property_id', '0');
        $protocol_id = $request->input('protocol_id', '0');

        $ret = array();
        $parserlist = Parser::where('protocol_id', $protocol_id)->get();

        $ret['parser'] = $parserlist;


        $ret['formatter'] = Formatter::where('protocol_id', $protocol_id)->get();
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

    public function getFormatterList($request)
    {
        $protocollist = Protocol::all();

        for ($i = 0; $i < count($protocollist); $i++) {
            $protocol = $protocollist[$i];

            $protocollist[$i]->formatter = Formatter::where('protocol_id', $protocol->id)->get();

        }
        echo json_encode($protocollist, JSON_UNESCAPED_UNICODE);
    }
    public function dbSwapInternal($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
       
        $src_config = $request->input('src_config');
        $cur_date = date("Y-m-d");
        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];
        
     

        
        // $propertylist = Property::all();

        // for ($i = 0; $i < count($propertylist); $i++) {
        //     $protocol = $protocollist[$i];
            $params = [];
            // $buildings=Building::where('property_id',$property_id)->get();
            // foreach($buildings as $key=>$value)
            // {
            // $floors=Floor::where('bldg_id',$value->id)->get();
            // foreach($floors as $keyf=>$valuef)
            // {

            $rooms= DB::table('common_room as r')
            ->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
            ->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
            ->leftJoin('common_guest as cg', function($join) use ($cur_date) {
                $join->on('cr.id', '=', 'cg.room_id');
                $join->on('cg.departure','>=',DB::raw($cur_date));
                $join->on('cg.checkout_flag','=', DB::raw("'checkin'"));
            })->where('cb.property_id', $property_id)
            ->select(DB::raw("cr.*,cf.floor,cg.guest_id,cg.language, cg.guest_name,cg.arrival,cg.departure,cg.pref,cg.adult,cg.chld,cg.share,cg.checkout_flag"))
            ->get();

            foreach($rooms as $key=>$val)
            {
                $params[0] = $val->room;
                $params[1] = $val->guest_id;
                $params[2] = '';
                $params[3] = $val->language;
                $params[4] = $val->vip;
                $params[5] = $val->guest_group;
                $params[6] = ($val->share == 1) ? 'Y':'N' ;
                $params[7] = $val->arrival;
                $params[8] = $val->departure;
		 //$title = '';
        // if( count($params) > 9 )
        // {
        //      $guest_name =  $params[10].' '.$params[2];
        //     $first_name = $params[9];
	    // $title = $params[10]; 
              
        // }
        // else
        // {
        //     $guest_name = $params[2];
        //     $first_name = '';
        //     $title = '';    
        // }
        
        // if ($share == 'Y')
        //     $share = 1;
        // else
        //     $share = 0;

            
        //     for ($i=0;$i<count($params);$i++) {
        //         if($params[$i]=='undefined')
        //         {
        //             if($i==4||$i==11||$i==12||$i==14||$i==16)
        //             $params[$i]=0;
        //             else 
        //             $params[$i]='';
        //         }
        //      }
        //     }
            //}
            $ret = $this->checkin_proc($channel_id, $property_id, $params, $src_config);
           }
       // echo json_encode($protocollist, JSON_UNESCAPED_UNICODE);
    }

    private function getRoomInfo($property_id, $room_number)
    {
        $room_info = DB::table('common_room as r')
            ->join('common_floor as f', 'r.flr_id', '=', 'f.id')
            ->join('common_building as b', 'f.bldg_id', '=', 'b.id')
            ->where('r.room', '=', $room_number)
            ->where('b.property_id', '=', $property_id)
            ->select(DB::raw('r.id, f.bldg_id,r.room'))->first();

        return $room_info;
	}
	private function getRoomInfowdChannel($property_id, $room_number, $src_config)
    {        
        $query = DB::table('common_room as r')
            ->join('common_floor as f', 'r.flr_id', '=', 'f.id')
            ->join('common_building as b', 'f.bldg_id', '=', 'b.id');
            
        if( !empty($src_config) && !empty($src_config['src_build_id']) && $src_config['src_build_id'] > 0 )
            $query->where('b.id', '=', $src_config['src_build_id']);

        $room_info = $query->where('r.room', '=', $room_number)
            ->where('b.property_id', '=', $property_id)
            ->select(DB::raw('r.id, f.bldg_id,r.room'))->first();

        return $room_info;
	}	   

    private function getGuestResponse($property_id, $guest, $room_number, $channel_id)
    {
        $extensions = GuestExtension::where('room_id', '=', $guest->room_id)->get();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;

        $msg = "|GN" . $guest->guest_name;
        $msg .= '|RN' . $room_number;        // room number
        $msg .= '|ET';

        for ($i = 0; $i < count($extensions); $i++) {
            $ext = $extensions[$i]->extension;
            $msg .= $ext . ":";
        }

        $msg .= '|';

        $ret['msg'] = $msg;

        return $ret;
    }

    private function sendAlarm($ret, $alarm)
    {
        // send alarm
        $ret['alarm'] = $alarm;
       
        Functions::sendMessageToInterface('interface_alarm', $ret);    

        return $this->outputResult(INVALID_PARAMETER, $ret);
    }

    private function sendCheckinResponse($ret, $room_id, $guest_name)
    {        
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");

        $room = Room::find($room_id);

        $guest = DB::table('common_guest as cg')
				->where('cg.room_id', $room_id)
				->where('cg.arrival', '=', $cur_date)
				->where('cg.checkout_flag', 'checkin')
				->first();

        if (!empty($guest)){

        $vip = DB::table('common_vip_codes as vc')
                ->select(DB::raw('vc.id'))
                ->where('vc.vip_code', 'like', $guest->vip)
                ->first();

        if (!empty($vip)){
        $rule = DB::table('services_hskp_rules')
						->where('room_type_id', $room->type_id)
						->where('vip_id', $vip->id)
						->select(DB::raw('days'))
						->first();
        }
        }

        if (!empty($rule)){
					
					$days = $rule->days;
					$next_full = date('Y-m-d', strtotime("$days days", strtotime($guest->arrival)));

		}
        else{

            $next_full = NULL;
        }


        
        HskpRoomStatus::where('id', $room_id)->update(
                ['occupancy' => 'Occupied', 'linen_date' => $cur_date, 'full_clean_date' => $next_full]
            );
            
        // 5. return extension array

        $extensions = GuestExtension::where('room_id', '=', $room_id)->get();
        
        for ($i = 0; $i < count($extensions); $i++) {
 
            $ret['msg'] = sprintf("|NAM1|GN%s|ET%d|", $guest_name, $extensions[$i]->extension);

            Functions::sendMessageToInterface('interface_hotlync', $ret);

            if(!empty($extensions[$i]->guid))
            {
                $ret['msg'] = sprintf("|NAM1|GN%s|GUID%s|", $guest_name, $extensions[$i]->guid);

                Functions::sendMessageToInterface('interface_hotlync', $ret);    
            }    

            usleep(500000);
        }

      //  $ret['msg'] = sprintf("|TV|CHK1|GN%s|RN%d|", $guest_name, $room->room);    

      //  Functions::sendMessageToInterface('interface_hotlync', $ret);    
    }

    private function sendCheckoutResponse($ret, $room_id, $guest_name)
    {
     //   HskpRoomStatus::where('id', $room_id)->update(['occupancy' => 'Vacant', 'schedule' => '']);
        $extensions = GuestExtension::where('room_id', '=', $room_id)->get();

        for ($i = 0; $i < count($extensions); $i++) {
            $ret['msg'] = sprintf("|CHK0|ET%d|", $extensions[$i]->extension);

            Functions::sendMessageToInterface('interface_hotlync', $ret);    
            usleep(500000);
            $extntosend = $extensions[$i]->extension;
            $messageoff = array();
            $messageoff['type'] = 'messagelampoff';
            $messageoff['extension'] = $extntosend;
            Redis::publish('notify', json_encode($messageoff));
        }

        $room = Room::find($room_id);
        
        //$ret['msg'] = sprintf("|TV|CHK0||RN%d|", $room->room);    
      
       // Functions::sendMessageToInterface('interface_hotlync', $ret);    
    }

    private function sendGuestchangeResponse($ret, $room_id, $guest_name)
    {
        usleep(500000);
        $extensions = GuestExtension::where('room_id', '=', $room_id)->get();
        
        for ($i = 0; $i < count($extensions); $i++) {
        
            $ret['msg'] = sprintf("|NAM2|GN%s|ET%d|", $guest_name, $extensions[$i]->extension);

            Functions::sendMessageToInterface('interface_hotlync', $ret);   

            usleep(500000);
        }

    }

    private function sendFlagvalueResponse($ret, $prefix, $value, $room_id)
    {
        
        $extensions = GuestExtension::where('room_id', '=', $room_id)->get();

        for ($i = 0; $i < count($extensions); $i++) {
            usleep(5000000);
            $ret['msg'] = sprintf("|" . $prefix . "%d|ET%d|", $value, $extensions[$i]->extension);

            Functions::sendMessageToInterface('interface_hotlync', $ret);    
        }
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

    private function isDuringDBSwap() {
        // check db swap
        $swap_start = Redis::get('db_swap');
        if( empty($swap_start) || $swap_start == 0 )
            return false;

        $diff = time() - $swap_start;
        $timeout = config('app.db_swap_timeout') * 60;
        if($diff > $timeout )   // timeout
        {
            Redis::set('db_swap', 0);
            return false;
        }

        return true;
    }

    private function getParam($param)
    {        
        try {
            // Log::info($ciphertext);
            $decrypted = openssl_decrypt($param, 'aes-256-cbc', AES_KEY, 0, AES_IV);
            Log::info($decrypted);            
            if(!empty($decrypted) )
                return $decrypted;                                    
        } catch (DecryptException $e) {
            Log::info('Decryption Failed');
        }

        return $param;
    }

    private function checkin($request)
    {        
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $params = explode('|', $this->getParam($request->input('param', '0|0||EN|0|0|Y|160610|000000')));        
        $src_config = $request->input('src_config');
        
        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];
        for ($i=0;$i<count($params);$i++) {
                if($params[$i]=='undefined')
                {
                    if($i==4||$i==11||$i==12||$i==14||$i==16)
                    $params[$i]=0;
                    else 
                    $params[$i]='';
                }
             }
     

        if( count($params) > 9 && $params[10] == 'undefined' )  // re
            $params[10] = '';
       
        $guest_name = $params[2];
        for($i=0; $i < strlen($guest_name); $i++)
        {
            if (!preg_match("#^[a-zA-Z0-9 \.]+$#", $guest_name[$i])) {
                $guest_name[$i]=' ';   
            }
        }     
        $params[2] = $guest_name;
        
        $ret = $this->checkin_proc($channel_id, $property_id, $params, $src_config);
        
        // check flagged guest
        $guest_id = $params[1];
        app('App\Http\Controllers\Frontend\ComplaintController')->checkFlagGuest($property_id, $guest_id);
        $room = $params[0];
        $guest_name = $params[2];
        $guest_vip = $params[4];
        if(!empty($params[9]))
         $tag_no = $params[9];
         if(!empty($params[10]))
         $tag_pieces = $params[10];
        // guest sms
        $room_info = Room::where('room','=',$room)->first();
        //$linen = LinenChange::where('room_id','=',$room_info->id)->first();
        //$type=LinenChange::getLinenType($room_info);
        //if(empty($linen))
        //{
            
         //   $linen = new LinenChange();
          //  $linen->room_id = $room_info->id;
           // $linen->room_type_id = $type->room_type_id ;
            //$linen->guest_id = $guest_id;
           
        //}
        //$linen->countdown_days = $type->linen_change;
        //$linen->last_changed = date("Y-m-d");
        //$linen->save();
        
        

        return $ret;
    }

    private function checkin_adv($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $params = explode('|', $this->getParam($request->input('param', '0|0||EN|0|0|Y|160610|000000')));
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $ret = $this->checkin_proc($channel_id, $property_id, $params, $src_config);

        $this->saveGuestAdvancedDetail($channel_id, $property_id, $params, $src_config);

        // check flagged guest
        $guest_id = $params[1];
       // app('App\Http\Controllers\Frontend\ComplaintController')->checkFlagGuest($property_id, $guest_id);

        // guest sms
       // $info = app('App\Http\Controllers\Frontend\GuestserviceController')->sendToGuestSMS($guest_id, $property_id, 0);

        return $ret;
    }

    private function checkin_proc($channel_id, $property_id, $params, $src_config) {
        $room_number = $params[0];
        $guest_id = $params[1];
       
        $language = $params[3];
        $vip = $params[4];
        $guest_group = $params[5];
        $share = $params[6];
        $arrival = $params[7];
        $departure = $params[8];
		 //$title = '';
        if( count($params) > 9 )
        {
            
                $guest_name = $params[2];

            $first_name = $params[9];
	        $title = $params[10];               
        }
        else
        {
            $guest_name = $params[2];
            $first_name = '';
            $title = '';    
        }
        
        if ($share == 'Y')
            $share = 1;
        else
            $share = 0;
        if( count($params) > 10 )
            $profileid = $params[11];
        else    
            $profileid = 0;
        $no_post = $params[12];
        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['src_config'] = $src_config;
        $ret['room_number'] = $room_number;

        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid checkin request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in database';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        $exists = GuestExtension::where('room_id', '=', $room_info->id)->exists();
        if (!$exists) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid checkin request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        // checkin internal procsss
        // remove all info for this guest
        Guest::where('guest_id', $guest_id)
                ->where('property_id', $property_id)
                ->delete();

        $guest = new Guest();

        if( $share == 0 )       // primarty guest
        {
            // check exist primary guest in room
            $primary_guest = Guest::where('room_id', $room_info->id)
                ->where('share', 0)
                // ->where('checkout_flag', 'checkin')
                ->orderBy('departure', 'desc')
                ->orderBy('arrival', 'desc')
                ->first();

            if( !empty($primary_guest) && $primary_guest->checkout_flag == 'checkin')     // already primary guest
            {
                // checkout old guest
                $this->checkout_process($room_info); // checkout old primary guest

                // trigger alarm for this exception
                $data = array();
                $data['type'] = 'warn';
                $data['msg'] = 'Invalid checkin request for Room %3$s received from %2$s in %1$s. Cause: Duplicated primary guest';
                array_push($alarm, $data);
            }
        }

        // 2. Add guest info to common_guest table.
        // $guest->id = $guest_id;
        $guest->property_id = $property_id;
        $guest->room_id = $room_info->id;
        $guest->guest_id = $guest_id;
        $guest->guest_group = $guest_group;
        $guest->guest_name = $guest_name;
        $guest->first_name = $first_name;
        $guest->title = $title;
        $guest->arrival = $arrival;
        $guest->departure = $departure;
        $guest->checkout_flag = 'checkin';
        $guest->share = $share;
        $guest->vip = $vip;
        $guest->language = $language;
        $guest->profile_id = $profileid;
        $guest->no_post = $no_post;
        $today = date("ymd");
        if ($guest->arrival > $today)
            $guest->pre_checkin = '1';
        else
            $guest->pre_checkin = '0';

        $guest->save();

        $this->updateGuestReservationStatus($guest_id, 'In-House');

        // save to log
        $this->saveGuestLog('checkin', $guest->id);

        $ret['guest_unique_id'] = $guest_id;

        // 3. Room status should change to Occupies dirty
        $room = Room::find($room_info->id);
        if (!empty($room)) {
            $building_id = $room->floor->building->id;
            $hskp_status = HskpStatus::where('bldg_id', $building_id)->where('status', 'Occupied Dirty')->first();
            if (!empty($hskp_status)) {
                $room->hskp_status_id = $hskp_status->id;
                $room->save();
            }
        }

        // 4. there is a Voicemail box that needs to be assigned to room
        $mailbox = IVRUser::where('room_id', $room_info->id)->first();
        if (empty($mailbox)) // there is no mailbox assigned to this room
        {
            $mailbox = IVRUser::where('room_id', '<', '1')->first();
            if (empty($mailbox))    // There is no valid mail box
            {
                $data = array();
                $data['type'] = 'warn';
                $data['msg'] = 'Invalid assign mailbox request for Room %3$s received from %2$s in %1$s. Cause: There is no mailbox for %3$s in Property %1$s';
                array_push($alarm, $data);
            } else  // there is valid mailbox.
            {
                $mailbox->room_id = $room_info->id;
                $mailbox->save();
            }
        }

        // 5. return extension array
        $this->sendCheckinResponse($ret, $room_info->id, $guest->guest_name);

        return $this->sendAlarm($ret, $alarm);
    }

    private function saveGuestAdvancedDetail($channel_id, $property_id, $params, $src_config) {
        $room_number = $params[0];
        $guest_id = $params[1];
        $guest_name = $params[2];
        $language = $params[3];
        $vip = $params[4];
        $guest_group = $params[5];
        $share = $params[6];
        $arrival = $params[7];
        $departure = $params[8];
        $first_name = $params[9];

        if( count($params) < 33 + 11 )
        {
            $length = count($params);
            for($i = 0; $i < 33 + 11 - $length; $i++)
                $params[] = '';
        }

        $guest = Guest::where('guest_id', $guest_id)
                        ->where('property_id', $property_id)
                        ->first();

        if( empty($guest) )
            return;

        $detail = GuestAdvancedDetail::find($guest->id);                    

        if( empty($detail) )
            $detail = new GuestAdvancedDetail();

        $detail->id = $guest->id;
        $detail->property_id = $guest->property_id;
        $detail->guest_id = $guest->guest_id;
        $detail->first_name = $guest->first_name;

        $attrs = array_slice($detail->getTableColumns(), 4, -2);

        $detail->id = $guest_id;
        foreach($attrs as $i => $key)
        {
            $detail->$key = $params[$i + 9];
        }
        $detail->save();
    }

    private function checkin_swap($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|0||EN|0|0|Y|160610|000000')));
        for ($i=0;$i<count($params);$i++) {
            if($params[$i]=='undefined')
            {
                if($i==4||$i==11||$i==12||$i==14||$i==16)
                $params[$i]=0;
                else 
                $params[$i]='';
            }
         }
        $room_number = $params[0];
        $guest_id = $params[1];
        $guest_name = $params[2];
        $language = $params[3];
        $vip = $params[4];
        $guest_group = $params[5];
        $share = $params[6];
        $arrival = $params[7];
        $departure = $params[8];
        if( count($params) > 9 )
        {
             $guest_name =  $params[2];
            $first_name = $params[9];
            $title = $params[10]; 
              
        }
        else
        {
            $guest_name = $params[2];
            $first_name = '';
            $title = '';    
        }
     
        if ($share == 'Y')
            $share = '1';
        else
            $share = '0';

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;

        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid checkin request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in databse';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        // data is equal
        $guest = Guest::where('room_id', $room_info->id)
            ->where('guest_id', $guest_id)
            ->where('guest_group', $guest_group)
            ->where('guest_name', $guest_name)
            ->where('arrival', $arrival)
            ->where('departure', $departure)
            ->where('share', $share)
            ->where('vip', $vip)
            ->where('language', $language)
            ->where('checkout_flag', 'checkin')
            ->first();

        if( empty($guest) ) // not exist same
            return $this->checkin($request);

        // 5. return extension array
        $this->sendCheckinResponse($ret, $room_info->id, $guest->guest_name);

        return $this->sendAlarm($ret, $alarm);
    }


    private function checkout_process($room_info)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        if (empty($room_info))
            return;

        // checkout all guest for this room
        Guest::where('room_id', $room_info->id)
            ->update(['checkout_flag' => 'checkout', 'created_at' => $cur_time]);


        // empty mailbox and alarm for this room
        IVRUser::where('room_id', $room_info->id)
            ->update(['room_id' => '0']);
    }


    private function checkout($request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|0')));

        $room_number = $params[0];
        $guest_id = $params[1];

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;
        system('sudo rm -rf /var/spool/asterisk/voicemail/default/'.$room_number);
        
        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid checkout request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in databse';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;
        
        $exists = GuestExtension::where('room_id', '=', $room_info->id)->exists();
        if (!$exists) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid checkout request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        // checkout internal procsss

        // 1. common_guest should be change into checkout
        // delete all guest info beside for this room
        Guest::where('room_id', '<>' ,$room_info->id)
            ->where('guest_id', $guest_id)
            ->delete();

        $guest = Guest::where('room_id', $room_info->id)
            ->where('guest_id', $guest_id)
            ->first();
        
        if (empty($guest)) {
//            $data = array();
//            $data['type'] = 'error';
//            $data['msg'] = 'Invalid checkout request for Room %3$s received from %2$s in %1$s. Cause: Guest does not exist yet';
//            array_push($alarm, $data);

            $this->sendCheckoutResponse($ret, $room_info->id, ' ');

            return $this->sendAlarm($ret, $alarm);
        }

        $guest->departure = date("ymd");
        $guest->checkout_flag = 'checkout';
        $guest->created_at = $cur_time;
        $guest->save();

        $this->updateGuestReservationStatus($guest_id, 'Departed');
        

        // save to log
        $this->saveGuestLog('checkout', $guest->id);

        if ($guest->share == '0') // primary guest checkout
        {

        } else                        // secondary guest checkout
        {

        }

        // if there is no guest, room state must be changed and free mailbox.
        
        $this->freeRoom($room_info);

        // check whether guest exist with checkin
        $exist = Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')// checkin
            ->exists();

        if ($exist == false){

            HskpRoomStatus::where('id', $room_info->id)->update(['occupancy' => 'Vacant', 'schedule' => '']);

        }
       
        $this->sendCheckoutResponse($ret, $room_info->id, ' ');
        
        
        return $this->sendAlarm($ret, $alarm);
    }

    private function checkout_swap($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|0')));

        $room_number = $params[0];
        $guest_id = $params[1];

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;
        system('sudo rm -rf /var/spool/asterisk/voicemail/default/'.$room_number);

        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid checkout request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in databse';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        // check exist same record
        $guest = Guest::where('room_id', $room_info->id)
            ->where('guest_id', $guest_id)
            ->where('checkout_flag', 'checkout')
            ->first();

        if (empty($guest))
            return $this->checkout($request);

        $exist = Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')// checkin
            ->exists();

        if ($exist == false){

            HskpRoomStatus::where('id', $room_info->id)->update(['occupancy' => 'Vacant', 'schedule' => '']);

        }

        $this->sendCheckoutResponse($ret, $room_info->id, ' ');

        return $this->sendAlarm($ret, $alarm);
    }

    private function checkoutnoguest_swap($request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0')));

        $room_number = $params[0];

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);


        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;
        system('sudo rm -rf /var/spool/asterisk/voicemail/default/'.$room_number);
        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid checkout request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in databse';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        $exists = GuestExtension::where('room_id', '=', $room_info->id)->exists();
        if (!$exists) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid checkout request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        // // 1. common_guest should be change into checkout
        // $guest = Guest::where('room_id', $room_info->id)
        //     ->where('checkout_flag', 'checkin')
        //     ->first();

        $ids = Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')
            ->select('id')->get()->pluck('id');

        Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')
            ->update(['checkout_flag' => 'checkout', 'departure' => date("ymd"), 'created_at' => $cur_time]);

        // save to log
        for($i = 0; $i < count($ids); $i++)
            $this->saveGuestLog('checkout', $ids[$i]);

        // if there is no guest, room state must be changed and free mailbox.
        $this->freeRoom($room_info);

        $exist = Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')// checkin
            ->exists();

        if ($exist == false){

            HskpRoomStatus::where('id', $room_info->id)->update(['occupancy' => 'Vacant', 'schedule' => '']);

        }

        $this->sendCheckoutResponse($ret, $room_info->id, ' ');

        return $this->sendAlarm($ret, $alarm);
    }

    private function freeRoom($room_info)
    {
        // check whether guest exist with checkin
        $exist = Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')// checkin
            ->exists();
        if ($exist == false) // There is no checkin guest
        {
            // 2. Room status should change to Vacant Dirty
            $room = Room::find($room_info->id);
            if (!empty($room)) {
                $building_id = $room->floor->building->id;
                $hskp_status = HskpStatus::where('bldg_id', $building_id)->where('status', 'Vacant Dirty')->first();
                if (!empty($hskp_status)) {
                    $room->hskp_status_id = $hskp_status->id;
                    $room->save();
                }
            }

            // empty mailbox and alarm for this room
            IVRUser::where('room_id', $room_info->id)
                ->update(['room_id' => '0']);
        }

        return $exist;
    }

    private function guestinfo($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|0||EN')));
        for ($i=0;$i<count($params);$i++) {
            if($params[$i]=='undefined')
            {
                if($i==4||$i==11||$i==12||$i==14||$i==16)
                $params[$i]=0;
                else 
                $params[$i]='';
            }
         }
        $room_number = $params[0];
        $guest_id = $params[1];
        $guest_name = $params[2];
        $language = $params[3];
        $vip = $params[4];
        $guest_group = $params[5];
        $share = $params[6];
        $arrival = $params[7];
        $departure = $params[8];
	//	$title = '';  
        if( count($params) > 9 )
        {
            $first_name = $params[9];
            $title = $params[10];   
           
            
        }
        else
		{
            $first_name = '';
            $title = '';
            
        }

        if( count($params) > 10 ){
            $profileid = $params[11];
            $no_post = $params[12];
        }
        else    
            $profileid = 0;
        
        
        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);


        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Channel Id: '.$channel_id.' Invalid guestinfo request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in databse';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        $exists = GuestExtension::where('room_id', '=', $room_info->id)->exists();
        if (!$exists) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid guest info request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

//        if( $this->isDuringDBSwap() )
//        {
//            $guest = Guest::where('room_id', $room_info->id)
//                ->where('guest_id', $guest_id)
//                ->where('guest_name', $guest_name)
//                ->where('language', $language)
//                ->where('checkout_flag', 'checkin')
//                ->first();
//            if( !empty($guest) )
//            {
//                $this->sendGuestchangeResponse($ret, $guest->room_id, $guest->guest_name);
//                return $this->sendAlarm($ret, $alarm);
//            }
//        }

        // 1. check guest is checked into room.
        $guest = Guest::where('room_id', $room_info->id)
            ->where('guest_id', $guest_id)
            ->where('checkout_flag', 'checkin')
            ->first();

        if (empty($guest)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid guestinfo request for Room %3$s received from %2$s in %1$s. Cause: Guest does not checkin yet';
            array_push($alarm, $data);

            $this->sendAlarm($ret, $alarm);

            return $this->checkin($request);
        }

        $guest->guest_name = $guest_name;
        $guest->first_name = $first_name;
        $guest->title = $title;
        $guest->language = $language;
        $guest->arrival = $arrival;
        $guest->departure = $departure;
        $guest->profile_id = $profileid;
        $guest->no_post = $no_post;
        $guest->save();

        // save to log
        $this->saveGuestLog('guestinfo', $guest->id);

        $this->sendGuestchangeResponse($ret, $room_info->id, $guest->guest_name);

        return $this->sendAlarm($ret, $alarm);
    }

    /**
     * @param $request
     */
    private function roomchange($request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|Y|0|0|N')));

        $room_number = $params[0];
        $newroom_occupied = $params[1];
        $guest_id = $params[2];
        $oldroom_number = $params[3];
        $oldroom_occupied = $params[4];

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;

        if ($oldroom_number == $room_number) {
            $ret = array();
            $ret['property_id'] = $property_id;
            $ret['room_number'] = $room_number;
            $ret['future_process'] = 0;

            return $this->outputResult(INVALID_PARAMETER, $ret);
        }


        $oldroom_info = $this->getRoomInfowdChannel($property_id, $oldroom_number, $src_config);


        // 1. check old room exist
        if (empty($oldroom_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid room change request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in databse';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        // 2. check guest is already checkin on old room
        $guest = Guest::where('room_id', $oldroom_info->id)
            ->where('guest_id', $guest_id)
            ->where('checkout_flag', 'checkin')
            ->first();

        if (empty($guest)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid guestinfo request for Room %3$s received from %2$s in %1$s. Cause: No Guest Checkin';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        // 3. get new room id
        $newroom_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);



        if (empty($newroom_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid room change request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in database';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $newroom_info->bldg_id;
        $ret['src_config'] = $src_config;

        $exists = GuestExtension::where('room_id', '=', $newroom_info->id)->exists();
        if (!$exists) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid room change request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $newguest = new Guest();
        
        $newguest->guest_id = $guest->guest_id;
		$newguest->property_id = $guest->property_id;
        $newguest->guest_group = $guest->guest_group;
        $newguest->guest_name = $guest->guest_name;
        $newguest->first_name = $guest->first_name;
        $newguest->title = $guest->title;
        $newguest->email = $guest->email;
        $newguest->mobile = $guest->mobile;
        $newguest->room_id = $newroom_info->id;
        $newguest->arrival = $guest->arrival;
        $newguest->departure = $guest->departure;
        $newguest->vip = $guest->vip;
        if ($newroom_occupied == 'Y')
            $newguest->share = '1';
        else
            $newguest->share = '0';
        $newguest->language = $guest->language;
        $newguest->pre_checkin = '0';
        $newguest->is_archived = '0';
        $newguest->dnd = $guest->dnd;
        $newguest->cos = $guest->cos;
        $newguest->ml = $guest->ml;
        $newguest->checkout_flag = 'checkin';

        Guest::where('guest_id', $guest_id)
            ->delete();

        if ($oldroom_occupied == 'N')    // Old room has been empty
        {
            $exist = Guest::where('room_id', $guest->room_id)
            ->where('checkout_flag', 'checkin')// checkin
            ->exists();

            if ($exist == false){

                HskpRoomStatus::where('id', $guest->room_id)->update(['occupancy' => 'Vacant', 'schedule' => '']);

            }
            $this->sendCheckoutResponse($ret, $guest->room_id, ' ');
        }

        // checkout old guest in new room
        if ($newroom_occupied == 'N') { // new room is empty
            // find old guest in new room
            $oldguest_newroom = Guest::where('room_id', $newroom_info->id)
                ->where('checkout_flag', 'checkin')->first();

            if (!empty($oldguest_newroom))        // old guest exist in new room
            {
                // checkout old guest in new room
                Guest::where('room_id', $newroom_info->id)
                    ->where('checkout_flag', 'checkin')
                    ->update(['checkout_flag' => 'checkout', 'created_at' => $cur_time]);

                $exist = Guest::where('room_id', $oldguest_newroom->room_id)
                    ->where('checkout_flag', 'checkin')// checkin
                    ->exists();

                if ($exist == false){

                    HskpRoomStatus::where('id', $oldguest_newroom->room_id)->update(['occupancy' => 'Vacant', 'schedule' => '']);

                }

                $this->sendCheckoutResponse($ret, $oldguest_newroom->room_id, ' ');
            }
        }

        $oldroom_mailbox = IVRUser::where('room_id', $oldroom_info->id)->first();
        $newroom_mailbox = IVRUser::where('room_id', $newroom_info->id)->first();

        // if there is no guest, room state must be changed and free mailbox.
        $this->freeRoom($oldroom_info);

        if (empty($newroom_mailbox))    // there is no mailbox for new room
        {
            if (!empty($oldroom_mailbox))    // there is mailbox for old room
            {
                // move mailbox from old room to new room
                $oldroom_mailbox->room_id = $newroom_info->id;
                $oldroom_mailbox->save();
            } else    // there is no mailbox for old room
            {
                // find valid mailbox
                $newroom_mailbox = IVRUser::where('room_id', '<', '1')->first();
                if (empty($newroom_mailbox))    // There is no valid mail box
                {
                    $data = array();
                    $data['type'] = 'warn';
                    $data['msg'] = 'Invalid assign mailbox request for Room %3$s received from %2$s in %1$s. Cause: There is no mailbox for %3$s in Property %1$s';
                    array_push($alarm, $data);
                } else  // there is valid mailbox.
                {
                    // assign valid mail box to new room
                    $newroom_mailbox->room_id = $newroom_info->id;
                    $newroom_mailbox->save();
                }
            }
        }

        // checkin on new room
        $newguest->save();


        $this->saveGuestLog('roomchange', $newguest->id);

        $this->sendCheckinResponse($ret, $newguest->room_id, $newguest->guest_name);

        if ($newguest->dnd == '1')
            $this->sendFlagvalueResponse($ret, 'DND', 1, $newguest->room_id);

        $this->sendFlagvalueResponse($ret, 'RST', $newguest->cos, $newguest->room_id);
        
       
        // 2. return extension array.
        return $this->sendAlarm($ret, $alarm);
    }

    private function donotdisturb($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|Y')));

        $room_number = $params[0];
        $dnd_flag = $params[1];

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);



        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Do not disturb request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in databse';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        $exists = GuestExtension::where('room_id', '=', $room_info->id)->exists();
        if (!$exists) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Do not disturb request request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        if ($dnd_flag == 'Y')
            $dnd = 1;
        else
            $dnd = 0;
        // checkout internal procsss

        // 1. common_guest should be change into checkout
        $guest = Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')
            ->first();
        if (empty($guest)) {
//            $data = array();
//            $data['type'] = 'error';
//            $data['msg'] = 'Invalid  Do not disturb request for Room %3$s received from %2$s in %1$s. Cause: Guest does not checkin yet';
//            array_push($alarm, $data);

            $this->sendFlagvalueResponse($ret, 'DND', $dnd, $room_info->id);

            return $this->sendAlarm($ret, $alarm);
        }

        Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')
            ->update(['dnd' => $dnd]);

        $guest->dnd = $dnd;
        $guest->save();

        $this->saveGuestLog('dnd', $guest->id);

        $this->sendFlagvalueResponse($ret, 'DND', $dnd, $room_info->id);

        return $this->sendAlarm($ret, $alarm);
    }

    private function classofservice($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|0')));

        $room_number = $params[0];
        $cos = $params[1];

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);

        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Class of service request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in databse';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        $exists = GuestExtension::where('room_id', '=', $room_info->id)->exists();
        if (!$exists) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Class of service request request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        // checkout internal procsss

        // 1. class of service should be change into checkout
        $guest = Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')
            ->first();
        if (empty($guest)) {
//            $data = array();
//            $data['type'] = 'error';
//            $data['msg'] = 'Invalid  Class of service request for Room %3$s received from %2$s in %1$s. Cause: Guest does not checkin yet';
//            array_push($alarm, $data);

            $this->sendFlagvalueResponse($ret, 'RST', $cos, $room_info->id);

            return $this->sendAlarm($ret, $alarm);
        }

        Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')
            ->update(['cos' => $cos]);

        $guest->cos = $cos;
        $this->saveGuestLog('cos', $guest->id);

        $this->sendFlagvalueResponse($ret, 'RST', $cos, $guest->room_id);

        return $this->sendAlarm($ret, $alarm);
    }

    private function messagelamp($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|N|0')));

        $room_number = $params[0];
        $ml_flag = $params[1];
        $guest_id = $params[2];

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);


        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Message lamp request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in database';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        // checkout internal procsss
        $exists = GuestExtension::where('room_id', '=', $room_info->id)->exists();
        if (!$exists) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Message lamp request request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        if ($ml_flag == 'Y')
            $ml = 1;
        else
            $ml = 0;

        $guest = Guest::where('room_id', $room_info->id)
            ->where('guest_id', $guest_id)
            ->where('checkout_flag', 'checkin')
            ->first();
        if (empty($guest)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid  Message lamp request for Room %3$s received from %2$s in %1$s. Cause: Guest does not checkin yet';
            array_push($alarm, $data);

            $this->sendFlagvalueResponse($ret, 'MW', $ml, $room_info->id);

            return $this->sendAlarm($ret, $alarm);
        }

        $guest->ml = $ml;
        $guest->save();

        $this->saveGuestLog('messagelamp', $guest->id);

        $this->sendFlagvalueResponse($ret, 'MW', $ml, $guest->room_id);

        return $this->sendAlarm($ret, $alarm);
    }

    private function mlcsdn_checkin($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|0|N|0|N')));

        $room_number = $params[0];
        $guest_id = $params[1];
        $ml_flag = $params[2];
        $cos = $params[3];
        $dnd_flag = $params[4];

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);

        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid ML CS DND request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in database';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        $exists = GuestExtension::where('room_id', '=', $room_info->id)->exists();
        if (!$exists) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid ML CS DND request request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        if ($ml_flag == 'Y')
            $ml = 1;
        else
            $ml = 0;

        if ($dnd_flag == 'Y')
            $dnd = 1;
        else
            $dnd = 0;

        $guest = Guest::where('room_id', $room_info->id)
            ->where('guest_id', $guest_id)
            ->where('checkout_flag', 'checkin')
            ->first();
        if (empty($guest)) {
//            $data = array();
//            $data['type'] = 'error';
//            $data['msg'] = 'Invalid  Class of service request for Room %3$s received from %2$s in %1$s. Cause: Guest does not checkin yet';
//            array_push($alarm, $data);

            $this->sendFlagvalueResponse($ret, 'MW', $ml, $room_info->id);
            $this->sendFlagvalueResponse($ret, 'RST', $cos, $room_info->id);
            $this->sendFlagvalueResponse($ret, 'DND', $dnd, $room_info->id);

            return $this->sendAlarm($ret, $alarm);
        }


        $guest->ml = $ml;
        $guest->cos = $cos;

        Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')
            ->update(['dnd' => $dnd]);

        $guest->dnd = $dnd;

        $guest->save();

        $this->saveGuestLog('mlcsdn_checkin', $guest->id);

        $this->sendFlagvalueResponse($ret, 'MW', $ml, $guest->room_id);
        $this->sendFlagvalueResponse($ret, 'RST', $cos, $guest->room_id);
        $this->sendFlagvalueResponse($ret, 'DND', $dnd, $guest->room_id);

        return $this->sendAlarm($ret, $alarm);
    }

    private function mlcsdn_checkout($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|N|0|N')));

        $room_number = $params[0];
        $ml_flag = $params[1];
        $cos = $params[2];
        $dnd_flag = $params[3];

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);

        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid ML CS DND request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in database';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        $exists = GuestExtension::where('room_id', '=', $room_info->id)->exists();
        if (!$exists) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid ML CS DND request request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        if ($ml_flag == 'Y')
            $ml = 1;
        else
            $ml = 0;

        if ($dnd_flag == 'Y')
            $dnd = 1;
        else
            $dnd = 0;

        $guest = Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkout')
            ->first();
        if (empty($guest)) {
//            $data = array();
//            $data['type'] = 'error';
//            $data['msg'] = 'Invalid  Class of service request for Room %3$s received from %2$s in %1$s. Cause: Guest does not checkin yet';
//            array_push($alarm, $data);

            $this->sendFlagvalueResponse($ret, 'MW', $ml, $room_info->id);
            $this->sendFlagvalueResponse($ret, 'RST', $cos, $room_info->id);
            $this->sendFlagvalueResponse($ret, 'DND', $dnd, $room_info->id);

            return $this->sendAlarm($ret, $alarm);
        }

        $guest->ml = $ml;
        $guest->cos = $cos;

        Guest::where('room_id', $room_info->id)
            ->where('checkout_flag', 'checkin')
            ->update(['dnd' => $dnd]);

        $guest->dnd = $dnd;

        $guest->save();

        $this->saveGuestLog('mlcsdn_checkout', $guest->id);

        $this->sendFlagvalueResponse($ret, 'MW', $ml, $guest->room_id);
        $this->sendFlagvalueResponse($ret, 'RST', $cos, $guest->room_id);
        $this->sendFlagvalueResponse($ret, 'DND', $dnd, $guest->room_id);

        return $this->sendAlarm($ret, $alarm);
    }


    private function nightaudit($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', 'S|000000|000000')));

        $flag = $params[0];
        $date = $params[1];
        $time = $params[2];

        if( $flag == 'E')  // Night audit end
        {
            $info = app('App\Http\Controllers\Backoffice\Guest\HSKPController')->nightAudit($property_id);
        }

        if( $flag == 'S')  // Night audit start
        {
            $request = new Request();
            $build_id = $src_config['src_build_id'];
            $build_ids = [$build_id];
            $src_channel_id = $src_config['src_channel_id'];
            app('App\Http\Controllers\Frontend\ReportController')->sendNightAuditReport($property_id, $src_channel_id, $build_ids);
        }
        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['src_config'] = $src_config;

        return $this->sendAlarm($ret, $alarm);
    }

    private function wakeupcall($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', 'R|0|000000|000000')));

        $flag = $params[0];
        $room_number = $params[1];
        $date = $params[2];
        $time = $params[3];

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['room_number'] = $room_number;
        $ret['src_config'] = $src_config;

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);

        if (empty($room_info)) {
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Wake up call service request for Room %3$s received from %2$s in %1$s. Cause: Room No does not exist in database';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $room_info->bldg_id;
        $ret['src_config'] = $src_config;

        $extension = GuestExtension::where('room_id', '=', $room_info->id)
                        ->where('primary_extn', 'Y')
                        ->first();
        if (empty($extension)) {
            $extension = GuestExtension::where('room_id', '=', $room_info->id)
                ->first();
        }

        if (empty($extension)) {

            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Wake up call service request for Room %3$s received from %2$s in %1$s. Cause: There is no extension for this room';
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $month = '';
        sscanf($date, "%02d%02d%02d", $year, $month, $date);

        $wakeup_time = sprintf('%02d-%02d-%02d %s', $year, $month, $date, $time);

        // check time
        $time = new DateTime($wakeup_time);

        if( $flag == 'C' )        // cancel
        {
            $wakeup = Wakeup::where('room_id', $room_info->id)
                ->where('time', $time->format('Y-m-d H:i:s'))
//                ->where('set_by', 'Opera')
                ->first();

            if(empty($wakeup) )
            {
                $data = array();
                $data['type'] = 'error';
                $data['msg'] = 'Invalid Wake up call service cancel for Room %3$s received from %2$s in %1$s. Cause: Room or Time is not correct';
                array_push($alarm, $data);

                return $this->sendAlarm($ret, $alarm);
            }

            $wakeup->status = 'Canceled';

            $wakeup->save();

            $log = new WakeupLog();
            $log->awu_id = $wakeup->id;
            $log->status = $wakeup->status;
            $log->set_by = 'Opera';
            $log->set_by_id = 0;

            $log->save();

            $this->sendUpdatedWakeup($wakeup);
        }
        else                    // start
        {
            $guest = DB::table('common_guest')
                ->where('room_id', $extension->room_id)
                ->where('checkout_flag', 'checkin')
				->orderBy('id', 'desc')
                ->orderBy('arrival', 'desc')
                ->first();

            // check guest is checkin
            if( empty($guest) || $guest->checkout_flag != 'checkin' )
            {
                $data = array();
                $data['type'] = 'error';
                $data['msg'] = 'Invalid Wake up call service request for Room %3$s received from %2$s in %1$s. Cause: Guest does not checkin';
                array_push($alarm, $data);

                return $this->sendAlarm($ret, $alarm);
            }

            if( $cur_time >= $time->format('Y-m-d H:i:s') )	// past time
            {
                $data = array();
                $data['type'] = 'error';
                $data['msg'] = 'Invalid Wake up call request for Room %3$s received from %2$s in %1$s. Cause: Wakeup time is old';
                array_push($alarm, $data);

                return $this->sendAlarm($ret, $alarm);
            }

            $awu = new Wakeup();

            $awu->property_id = $property_id;
            $awu->room_id = $room_info->id;
            $awu->guest_id = $guest->guest_id;
            $awu->extension_id = $extension->id;
            $awu->time = $time->format('Y-m-d H:i:s');
            $awu->set_time = $time->format('H:i:s');
            $awu->status = 'Pending';
            $awu->set_by = 'Opera';
            $awu->set_by_id = 0;
            $awu->attempts = 0;
            $awu->repeat_flag = 0;

            $awu->save();

            $log = new WakeupLog();
            $log->awu_id = $awu->id;
            $log->status = $awu->status;
            $log->set_by = 'Opera';
            $log->set_by_id = 0;

            $log->save();

            $this->sendUpdatedWakeup($awu);
        }

        return $this->sendAlarm($ret, $alarm);
    }

    private function sendUpdatedWakeup($wakeup) {
        $message = array();
        $message['type'] = 'wakeup_event';
        $message['data'] = $wakeup;

        Redis::publish('notify', json_encode($message));
    }

    private function databaseswap($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', 'S|000000|000000')));

        $flag = $params[0];
        $date = $params[1];
        $time = $params[2];

        if( $flag == 'S')  // Database swap start
        {
            Redis::set('db_swap', time());
        }
        if( $flag == 'E' )  // database swap end
        {
            Redis::set('db_swap', 0);
        }

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['src_config'] = $src_config;

        return $this->sendAlarm($ret, $alarm);
    }

    private function databaseswapFromHotlync($request)
    {
        $property_id = $request->get('property_id', 0);
        $building_id = $request->get('building_id', 0);
        
        $src_config = array();
        $src_config['src_property_id'] = $property_id;
        $src_config['src_build_id'] = $building_id;
        $src_config['accept_build_id'] = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['src_config'] = $src_config;

        date_default_timezone_set(config('app.timezone'));

        $ret['msg'] = sprintf("DR|DA%06s|TI%06s|",
            date('ymd'), date('His'));

        Functions::sendMessageToInterface('interface_hotlync', $ret);

        $alarm = array();

        return $this->sendAlarm($ret, $alarm);
    }

    private function callcharge($request, $flag)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '06/06|10:05|F0000:10:18|1234|8829912345|1234|307')));

        $date = $params[0];
        $time = $params[1];
        $duration = $params[2];
        $calleeid = $params[3];
        $extension = $params[4];
        $trunk = $params[5];
        $transfer = $params[6];
        $access_code = $params[7];
        $pulse = 0;
        $year = date('y');
        $duration_flag = substr($duration, 0, 1);
        if($duration_flag == 'S')
        {
            $elapse_seconds = round(substr($duration, 1 - strlen($duration)));
        }
         else if($duration_flag == 'E')
        {
            $duration_temp=$year.'-'.substr($duration, 1,2).'-'.substr($duration, 3,2).' '.substr($duration, 5,2).':'.substr($duration, 7,2).':'.substr($duration, 9,2);
			$time_temp=$year.'-'.substr($time, 0,2).'-'.substr($time, 2,2).' '.substr($time, 4,2).':'.substr($time, 6,2).':'.substr($time, 8,2);
            $durations= date_diff( new DateTime($duration_temp), new DateTime($time_temp) );
            //$start_time=$params[7];
           	//$start_time=new DateTime($start_time);
		   // $duration=new DateTime($duration);
            //$durations= date_diff( $start_time, $duration );
            $elapse_seconds = ((($durations->h)*3600)+(($durations->m)*60)+$durations->s);
            
            $date=substr($time, 0,2).'/'.substr($time, 2,2);
            $time=(substr($time, 4,2).':'.substr($time, 6,2).':'.substr($time, 8,2));

        }
        else
        {
            $normal_duration = substr($duration, 1 - strlen($duration));

            $normal_duration = str_replace(' ', '0', $normal_duration);

            $elapse_seconds = $this->hoursToSeconds($normal_duration);
        }

        // sample ip format http://27.0.0.1

        $rules = array();

		$rules['cc_redirect_ip'] = '';
		$rules['cc_redirect_flag'] = 0;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);

        if (($flag == 3) && ($rules['cc_redirect_flag'] == 1)){

            $ext = DB::table('call_center_extension as cce')
				->where('cce.extension', $extension)
				->first();

		    if (!empty($ext)){

			    $agent = IVRAgentStatus::where('extension', $ext->extension)->first();

			    if (!empty($agent) && !empty($rules['cc_redirect_ip'])){

				    $ch = curl_init();

				    $data = array("extension" => $ext->extension
						);


				    $data_string = json_encode($data);
				    $url = sprintf('%s/api/directoutgoing',$rules['cc_redirect_ip']);
			


				    $headers = [
					    'Content-Type: application/json',
				    ];


				    curl_setopt($ch,CURLOPT_URL,$url);
				    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
				    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
				    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
				    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

				    $output=curl_exec($ch);

				    curl_close($ch);


			    }

		    }
        }


        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;

        $this->saveCallchargeRequest($date, $time, $extension, $trunk, $transfer,$access_code, $elapse_seconds, $calleeid, $flag, $pulse, $src_config, $ret, $alarm );
    }

    private function callcharge_manual($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = $this->getParam($request->input('param', ''));

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;

        $ret['src_config'] = $src_config;

        $ret['msg'] = $params;

        Functions::sendMessageToInterface('interface_hotlync', $ret);    

        $alarm = array();
        return $this->sendAlarm($ret, $alarm);
    }

    private function getChargeMode($adminext, $guestext, $bcext, &$ret, &$alarm, $extension) {
        $result = array();

        if (!empty($adminext) && !empty($guestext) )        // there exist extension in both admin and guest
        {
            // should be exception
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Incoming call service request for received from %2$s in %1$s. Cause: There exist extension for both admin and guest, extension = ' . $extension;
            array_push($alarm, $data);

            $result['code'] = -1;
            $result['data'] = $this->sendAlarm($ret, $alarm);
        }
        else if (empty($adminext) && empty($guestext)&& empty($bcext))        // there is no extension in both admin and guest
        {
            // process as admin mode
            $chargemode = 'admin';

            // should be alarm
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Incoming call service request for received from %2$s in %1$s.. Cause: There is no extension for this incoming call, extension = ' . $extension;
            array_push($alarm, $data);

            $result['code'] = -1;
            $result['data'] = $this->sendAlarm($ret, $alarm);
        }
        else if (!empty($adminext))    // exist on admin
        {
			$result['code'] = 0;
            $result['chargemode'] = 'admin';
            $result['room_id'] = 0;
            $result['guest_id'] = 0;
            $result['src_build_id'] = 0;
        }
		else if (!empty($bcext))    // exist on bc
        {
			
            $result['chargemode'] = 'bc';
            $result['room_id'] = 0;
            $result['guest_id'] = 0;
            $result['src_build_id'] = 0;
			$result['code'] = 0;
        }
        else                            // exist on guest
        {
            $room_id = $guestext->room_id;
            $guest = Guest::where('room_id', $room_id)
                ->where('checkout_flag', 'checkin')
                ->first();
            if (!empty($guest)) {
                $guest_id = $guest->guest_id;
            } else {
                $guest_id = 0;
            }
            $result['code'] = 0;
            $result['chargemode'] = 'guest';
            $result['room_id'] = $room_id;
            $result['guest_id'] = $guest_id;
            $result['src_build_id'] = $guestext->bldg_id;
        }

        return $result;
    }

    private function getCallerInfo($calleeid, &$ret, &$alarm) {
        $result = array();

        // get destination id
        for( $i = strlen( $calleeid ); $i > 0; --$i ) {
            $searchstr = substr($calleeid, 0, $i);
            $destination = Destination::where('code', $searchstr)->first();
            if(!empty($destination))
                break;
        }

        if (empty($destination)) // if there is no destination
        {
            // should be exception
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid call charge ' . $calleeid . ' request for received from %2$s in %1$s.. Cause: There is no destination';
            array_push($alarm, $data);

            $result['code'] = -1;
            $result['data'] = $this->sendAlarm($ret, $alarm);

            return $result;
        }

        $dest_group = GroupDestination::where('destination_id', $destination->id)->first();
        if (empty($dest_group))        // There is no destination group
        {
            // should be exception
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Incoming call service request for received from %2$s in %1$s.. Cause: There is no destination group for this id: ' . $destination->id;
            array_push($alarm, $data);

            $result['code'] = -1;
            $result['data'] = $this->sendAlarm($ret, $alarm);

            return $result;
        }

        $carrier_group = CarrierGroup::find($dest_group->carrier_group_id);
        if (empty($carrier_group)) {
            // should be exception
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Incoming call service request for received from {1} in %1$s.. Cause: There is no carrier group';
            array_push($alarm, $data);
        }

        $call_type = 'Received';
        $sales_outlet = 0;
        if (!empty($carrier_group)) {
            $call_type = $carrier_group->call_type;
            $sales_outlet = $carrier_group->sales_outlet;
        }

        $result['code'] = 0;
        $result['destination'] = $destination;
        $result['dest_group'] = $dest_group;
        $result['call_type'] = $call_type;
        $result['sales_outlet'] = $sales_outlet;

        return $result;
    }

    private function getTimeslab($date, $time, &$ret, &$alarm) {
        $result = array();

        $datetime = new DateTime($date);
        $dayofweek = $datetime->format('w');

        $daylist = [
            '0' => 'Sunday',
            '1' => 'Monday',
            '2' => 'Tuesday',
            '3' => 'Wednesday',
            '4' => 'Thursday',
            '5' => 'Friday',
            '6' => 'Saturday',
        ];

        $day = $daylist[$dayofweek];

        $timeslab = TimeSlab::where('start_time', '<', $time)->where('end_time', '>=', $time)
            ->where('days_of_week', 'LIKE', '%' . $day . '%')->first();

        if (empty($timeslab))    // there is no time slab
        {
            // should be exception
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Incoming call service request for received from %2$s in %1$s.. Cause: There is no time slab';
            array_push($alarm, $data);

            $result['code'] = -1;
            $result['data'] = $this->sendAlarm($ret, $alarm);

            return $result;
        }

        $result['code'] = 0;
        $result['data'] = $timeslab;

        return $result;
    }

private function getChargeValue($chargemode, $timeslab, $dest_group, $elapse, $elapse_seconds, &$ret, &$alarm, $room_id, $calleeid) {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");
        $result = array();

        if ($chargemode == 'admin') {
            $adminrate = AdminChargeMap::where('time_slab_group', $timeslab->id)
                ->where('carrier_group_id', $dest_group->carrier_group_id)
                ->first();
            if (empty($adminrate)) {
                // should be exception
                $data = array();
                $data['type'] = 'error';
                $data['msg'] = 'TimeSlab = ' . $timeslab->id . ' Dest Group = ' . $dest_group->carrier_group_id .  ' Invalid Incoming call service request for received from %2$s in %1$s.. Cause: There is no admin rate map';
//                $data['msg'] = 'Invalid Incoming call service request for received from %2$s in %1$s.. Cause: There is no admin rate map';
                array_push($alarm, $data);

                $result['code'] = -1;
                $result['data'] = $this->sendAlarm($ret, $alarm);

                return $result;
            }

            $result['rate_id'] = $adminrate->id;

            $hotel_charge = new HotelCharges();
            $hotel_charge->charge = 0;
            $carrier_charge = CarrierCharges::find($adminrate->carrier_charges);
            $tax = new Tax();
            $tax->value = 0;
            $call_allowance = Allowance::find($adminrate->call_allowance);
        } 
		else if($chargemode == 'bc'){
		$guestrate = GuestChargeMap::where('time_slab', $timeslab->id)
                ->where('carrier_group_id', $dest_group->carrier_group_id)
                ->first();

            if (empty($guestrate)) {
                // should be exception
                $data = array();
                $data['type'] = 'error';
//                $data['msg'] = 'Invalid Incoming call service request for received from %2$s in %1$s.. Cause: There is no guest rate map';
                $data['msg'] = 'TimeSlab = ' . $timeslab->id . ' Dest Group = ' . $dest_group->carrier_group_id .  ' Invalid Incoming call service request for received from %2$s in %1$s.. Cause: There is no guest rate map';
                array_push($alarm, $data);

                $result['code'] = -1;
                $result['data'] = $this->sendAlarm($ret, $alarm);

                return $result;
            }

            $result['rate_id'] = $guestrate->id;

            $hotel_charge = HotelCharges::find($guestrate->hotel_charges);
            $carrier_charge = CarrierCharges::find($guestrate->carrier_charges);
            $tax = Tax::find($guestrate->tax);
            $call_allowance = Allowance::find($guestrate->call_allowance);	
			
		}
		
		else {
            $guest = Guest::where('room_id', $room_id)->where('departure', '>=', $cur_date)
				    ->where('checkout_flag', 'checkin')
				    ->first();
            if (!empty($guest)){

                $vip = VIPCodes::where('vip_code', 'like', $guest->vip)->first();   
            }

            if (!empty($vip)){
                if ($vip->id != 0)
                    $vip_code = $vip->id;
                else
                    $vip_code = 0;
            }
            else
                $vip_code = 0;


            $slab = 0;
            $carrier = 0;
            $rate_vip = 0;
            if($vip_code != 0){
                $guestratevip = GuestChargeMap::where('time_slab', $timeslab->id)->where('carrier_group_id', $dest_group->carrier_group_id)->get();
              
                 $rate_vip = 0;
                foreach( $guestratevip as $row){

                    $rate_vip = $row->vip_ids;
                    $rate_vip = explode(',', $rate_vip);

                    if(in_array($vip_code,$rate_vip)){

                        $slab = $row->time_slab;
                        $carrier = $row->carrier_group_id;

                        break;
                    }

                }
           

            }
       
            if ($slab != 0 && $carrier != 0){
                $guestrate = GuestChargeMap::where('time_slab', $slab)
                    ->where('carrier_group_id', $carrier)
                    ->where('vip_ids',$rate_vip)
                    ->first();
            }else{

                $guestrate = GuestChargeMap::where('time_slab', $timeslab->id)
                ->where('carrier_group_id', $dest_group->carrier_group_id)
                ->where('vip_ids','=', '')
                ->first();
            }
       
            

            // $guestrate = GuestChargeMap::where('time_slab', $timeslab->id)
            //     ->where('carrier_group_id', $dest_group->carrier_group_id)
            //     ->first();

            if (empty($guestrate)) {
                // should be exception
                $data = array();
                $data['type'] = 'error';
//                $data['msg'] = 'Invalid Incoming call service request for received from %2$s in %1$s.. Cause: There is no guest rate map';
                $data['msg'] = 'TimeSlab = ' . $timeslab->id . ' Dest Group = ' . $dest_group->carrier_group_id .  ' Invalid Incoming call service request for received from %2$s in %1$s.. Cause: There is no guest rate map';
                array_push($alarm, $data);

                $result['code'] = -1;
                $result['data'] = $this->sendAlarm($ret, $alarm);

                return $result;
            }

            $result['rate_id'] = $guestrate->id;

            $hotel_charge = HotelCharges::find($guestrate->hotel_charges);
            $carrier_charge = CarrierCharges::find($guestrate->carrier_charges);
            $tax = Tax::find($guestrate->tax);
            $call_allowance = Allowance::find($guestrate->call_allowance);
        }

        $allowance_value = 0;
        if (!empty($call_allowance))
            $allowance_value = $call_allowance->Value;

        $carrier_charge_value = 0;
        if( !empty($carrier_charge->method) ) {
            switch ($carrier_charge->method) {
                case 'Minute':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $carrier_charge_value = $carrier_charge->charge * $elapse;
                    break;
                case 'Per Call':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $carrier_charge_value = $carrier_charge->charge;
                    break;
                case 'Pulse':
                    $carrier_charge_value = $carrier_charge->charge;
                    break;
                case 'Second':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $carrier_charge_value = $carrier_charge->charge * $elapse_seconds;
                    break;
                case '30 Seconds':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $carrier_charge_value = $carrier_charge->charge * (ceil($elapse_seconds / 30));
                    break;
            }
        }

        $hotel_charge_value = 0;
        if( !empty($hotel_charge->method) ) {
            switch ($hotel_charge->method) {
                case 'Duration':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $hotel_charge_value = $hotel_charge->charge * $elapse;
                    break;
                case 'Per Call':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $hotel_charge_value = $hotel_charge->charge;
                    break;
                case 'Percentage':
                    $hotel_charge_value = $hotel_charge->charge * $carrier_charge_value / 100;
                    break;
                case 'Pulse':
                    $hotel_charge_value = $hotel_charge->charge;
                    break;
            }
        }

        $tax_value = $tax->value * $carrier_charge_value / 100;
        $total_value = $carrier_charge_value + $hotel_charge_value + $tax_value;

        $result['code'] = 0;
        $result['carrier_charge_value'] = $carrier_charge_value;
        $result['tax_value'] = $tax_value;

        // check whitelist number
        $whitelist = Whitelist::where('caller_id', $calleeid)
                      ->first();
        if(($chargemode == 'guest') && (!empty($whitelist)))
        {
            $result['hotel_charge_value'] = 0;
            $result['total_value'] = 0;
        }
        else
        {
            $result['hotel_charge_value'] = $hotel_charge_value;
            $result['total_value'] = $total_value;
        }

        return $result;
    }

    public function getChargeValue1(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));

        $dest_id = $request->get('dest_id', 0);
        $type = $request->get('extension_type', 'Admin Call');
        $elapse_seconds = $request->get('time_duration', 0);

        $ret = array();
        $ret['content'] = 100;

        $result = array();

        $dayofweek = date("w");
        $time = date("H:i:s");

        $daylist = [
            '0' => 'Sunday',
            '1' => 'Monday',
            '2' => 'Tuesday',
            '3' => 'Wednesday',
            '4' => 'Thursday',
            '5' => 'Friday',
            '6' => 'Saturday',
        ];

        $day = $daylist[$dayofweek];

        $timeslab = TimeSlab::where('start_time', '<', $time)->where('end_time', '>=', $time)
            ->where('days_of_week', 'LIKE', '%' . $day . '%')->first();

        $dest_group = GroupDestination::where('destination_id', $dest_id)->first();

        if ($type == 'Admin Call') {
            $adminrate = AdminChargeMap::where('time_slab_group', $timeslab->id)
                ->where('carrier_group_id', $dest_group->carrier_group_id)
                ->first();
            if (empty($adminrate)) {
                $ret['content'] = 'Invalid Admin Call';
                return Response::json($ret);
            }
            
            $call_allowance = Allowance::find($adminrate->call_allowance);
            $hotel_charge = new HotelCharges();
            $hotel_charge->charge = 0;
            $carrier_charge = CarrierCharges::find($adminrate->carrier_charges);
            $tax = new Tax();
            $tax->value = 0;            
        } 		
		else {
            $guestrate = GuestChargeMap::where('time_slab', $timeslab->id)
                ->where('carrier_group_id', $dest_group->carrier_group_id)
                ->first();

            if (empty($guestrate)) {
                // should be exception
                $ret['content'] = 'Invalid Guest Call Call';
                return Response::json($ret);
            }

            $hotel_charge = HotelCharges::find($guestrate->hotel_charges);
            $carrier_charge = CarrierCharges::find($guestrate->carrier_charges);
            $tax = Tax::find($guestrate->tax);
            $call_allowance = Allowance::find($guestrate->call_allowance);
        }

        $allowance_value = 0;
        if (!empty($call_allowance))
            $allowance_value = $call_allowance->Value;

        $normal_duration = Functions::getHHMMSSFormatFromSecond($elapse_seconds);
        $elapse = $this->hoursToMinute($normal_duration);

        $carrier_charge_value = 0;

        if( !empty($carrier_charge->method) ) {
            switch ($carrier_charge->method) {
                case 'Minute':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $carrier_charge_value = $carrier_charge->charge * $elapse;
                    break;
                case 'Per Call':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $carrier_charge_value = $carrier_charge->charge;
                    break;
                case 'Pulse':
                    $carrier_charge_value = $carrier_charge->charge;
                    break;
                case 'Second':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $carrier_charge_value = $carrier_charge->charge * $elapse_seconds;
                    break;
                case '30 Seconds':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $carrier_charge_value = $carrier_charge->charge * (ceil($elapse_seconds / 30));
                    break;
            }
        }


        $hotel_charge_value = 0;
        if( !empty($hotel_charge->method) ) {
            switch ($hotel_charge->method) {
                case 'Duration':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $hotel_charge_value = $hotel_charge->charge * $elapse;
                    break;
                case 'Per Call':
                    if ($elapse_seconds > $allowance_value)        // overflow allowance
                        $hotel_charge_value = $hotel_charge->charge;
                    break;
                case 'Percentage':
                    $hotel_charge_value = $hotel_charge->charge * $carrier_charge_value / 100;
                    break;
                case 'Pulse':
                    $hotel_charge_value = $hotel_charge->charge;
                    break;
            }
        }

        $tax_value = $tax->value * $carrier_charge_value / 100;
        $total_value = $carrier_charge_value + $hotel_charge_value + $tax_value;

        $ret['content'] = $total_value;

        return Response::json($ret);
    }

    private function saveCallchargeInfo($date, $time, $chargemode, $room_id, $guest_id, $guestext, $adminext, $bcext,
                                        $calleeid, $destination, $call_type, $trunk, $transfer,$access_code, $elapse_seconds, $pulse,
                                        $charge_value, $normal_duration, $sales_outlet, &$ret, &$alarm ) {
        $calldate = new DateTime($date);

        $starttime = new DateTime($time);
        $endtime = $starttime->add(new DateInterval('PT' . $elapse_seconds . 'S'));

        $msg = 'PS';
        $verify = 'PA';
        $verify_exp = 'PA';
        if ($chargemode == 'guest') {
            // check duplicated call
            //  02/14,14:57,0000:00:03,00966553015631,62501,T7009
            $guest_call = GuestCall::where('call_date', $calldate->format('Y-m-d'))
                            ->where('start_time', $time)
                            ->where('duration', $elapse_seconds)
                            ->where('called_no', $calleeid)
                            ->where('extension_id', $guestext->id)                            
                            ->where('trunk', $trunk)                            
                            ->where('call_type', $call_type)                            
                            ->first();
            if( !empty($guest_call) )
            {
                // send duplicated error
                $room = Room::find($room_id);

                $ret['msg'] = sprintf("Duplicated Guest Call = RN%s|DA%06s|TI%06s|DD%s|DU%s|ET%s|TA%s|CT%s",
                    $room->room, $calldate->format('ymd'), $starttime->format('His'), $calleeid,
                    $normal_duration, $guestext->extension, $trunk, $call_type);

                Functions::sendMessageToInterface('interface_hotlync', $ret);    

                return;                
            }               
            
            $guest_call = new GuestCall();
            $guest_call->room_id = $room_id;
            $guest_call->guest_id = $guest_id;
            $guest_call->extension_id = $guestext->id;
            $guest_call->call_date = $calldate->format('Y-m-d');
            $guest_call->called_no = $calleeid;
            $guest_call->call_type = $call_type;
            $guest_call->trunk = $trunk;
            $guest_call->transfer = $transfer;
            $guest_call->start_time = $time;
            $guest_call->end_time = $endtime->format('H:i:s');
            $guest_call->duration = $elapse_seconds;
            $guest_call->pulse = $pulse;
            if( empty($destination ))
                $guest_call->destination_id = 0;
            else
                $guest_call->destination_id = $destination->id;
            $guest_call->carrier_charges = $charge_value['carrier_charge_value'];
            $guest_call->tax = $charge_value['tax_value'];
            $guest_call->hotel_charges = $charge_value['hotel_charge_value'];
            $guest_call->total_charges = $charge_value['total_value'];

            $guest_call->guest_charge_rate_id = $charge_value['rate_id'];

            $guest_call->save();

            $this->makeReceiveCallFromGuestCall($guest_call->id);

            if( $guest_call->total_charges > 0 )     // outgoing and valid value
            {
                $room = Room::find($room_id);

                $seqnum = Redis::get('seqnum') + 1;
                Redis::set('seqnum', $seqnum);
                $hours = 0;
                $minutes = 0;
                $seconds = 0;
                sscanf($normal_duration, "%d:%d:%d", $hours, $minutes, $seconds);

                $ret['msg'] = sprintf("PS|RN%s|PTC|DA%06s|TI%06s|P#%d|DD%s|DU%02d%02d%02d|PCI|TA%02d|SO%d",
                    $room->room, $calldate->format('ymd'), $starttime->format('His'), $seqnum, $calleeid,
                    $hours, $minutes, $seconds, round($guest_call->total_charges * 100), $sales_outlet);

                Functions::sendMessageToInterface('interface_hotlync', $ret);    
            }
        }
        if ($chargemode == 'bc') {
            $bc_call = BCCall::where('call_date', $calldate->format('Y-m-d'))
                ->where('start_time', $time)
                ->where('duration', $elapse_seconds)
                ->where('called_no', $calleeid)
                ->where('extension_id', $bcext->id)                            
                ->where('trunk', $trunk)                            
                ->where('call_type', $call_type)                            
                ->first();
            if( !empty($bc_call) )
            {
                $ret['msg'] = sprintf("Duplicated Business Call = DA%06s|TI%06s|DD%s|DU%s|ET%s|TA%s|CT%s",
                    $calldate->format('ymd'), $starttime->format('His'), $calleeid,
                    $normal_duration, $bcext->extension, $trunk, $call_type);

                Functions::sendMessageToInterface('interface_hotlync', $ret);    

                return;                
            }               

            $bc_call = new BCCall();
            
            $bc_call->extension_id = $bcext->id;
            $bc_call->call_date = $calldate->format('Y-m-d');
            $bc_call->called_no = $calleeid;
            $bc_call->call_type = $call_type;
            $bc_call->trunk = $trunk;
            $bc_call->transfer = $transfer;
            $bc_call->start_time = $time;
            $bc_call->end_time = $endtime->format('H:i:s');
            $bc_call->duration = $elapse_seconds;
            $bc_call->pulse = $pulse;
            if( empty($destination ))
                $bc_call->destination_id = 0;
            else
                $bc_call->destination_id = $destination->id;
            $bc_call->carrier_charges = $charge_value['carrier_charge_value'];
            $bc_call->tax = $charge_value['tax_value'];
            $bc_call->hotel_charges = $charge_value['hotel_charge_value'];
            $bc_call->total_charges = $charge_value['total_value'];

            $bc_call->guest_charge_rate_id = $charge_value['rate_id'];

            $bc_call->save();

            $this->makeReceiveCallFromBCCall($bc_call->id);            
        }
        if ($chargemode == 'admin') {
            $query = AdminCall::where('call_date', $calldate->format('Y-m-d'))
                ->where('start_time', $time)
                ->where('duration', $elapse_seconds)
                ->where('called_no', $calleeid);
            $extension = 'Unknown';    
            if (!empty($adminext))                 
            {
                $query->where('extension_id', $adminext->id);
                $extension = $adminext->extension;
            }
            else
                $query->where('extension_id', 0); 
                
            $admin_call = $query->where('extension_id', $adminext->id)                            
                ->where('trunk', $trunk)                            
                ->where('call_type', $call_type)                            
                ->first();

            if( !empty($admin_call) )
            {
                $ret['msg'] = sprintf("Duplicated Admin Call = DA%06s|TI%06s|DD%s|DU%s|ET%s|TA%s|CT%s",
                    $calldate->format('ymd'), $starttime->format('His'), $calleeid,
                    $normal_duration, $extension, $trunk, $call_type);

                Functions::sendMessageToInterface('interface_hotlync', $ret);    

                return;                
            }               

            $admin_call = new AdminCall();
            if (!empty($adminext)) {
                $admin_call->extension_id = $adminext->id;
                $admin_call->user_id = $adminext->user_id;
            } else {
                $admin_call->extension_id = 0;
                $admin_call->user_id = 0;
            }

            $admin_call->call_date = $calldate->format('Y-m-d');
            $admin_call->start_time = $time;
            $admin_call->end_time = $endtime->format('H:i:s');
            $admin_call->duration = $elapse_seconds;
            $admin_call->called_no = $calleeid;
            $admin_call->trunk = $trunk;
            $admin_call->transfer = $transfer;
            $admin_call->access_code = $access_code;
            $admin_call->call_type = $call_type;
            if( empty($destination ))
                $admin_call->destination_id = 0;
            else
                $admin_call->destination_id = $destination->id;

            $admin_call->carrier_charges = $charge_value['carrier_charge_value'];

            $rules = PropertySetting::getClassifyRuleSetting($ret['property_id']);


            $rule_calltypes = array();
            
            $rule_types = array();
            $rule_durations = array();
            if(!empty($rules['pre_approved_call_types'])) {
                //$rule_calltypes = explode("," , $rules['pre_approved_call_types']);
                $rule_types = explode("," , $rules['pre_approved_call_types']);
                foreach($rule_types as $type){
                    $calltypetemps = explode(":" , $type);
                    $rule_calltypes[] = $calltypetemps[0];
                    $rule_durations[] = $calltypetemps[1];
                }
            }
            if( !empty($rule_calltypes) && in_array($call_type ,$rule_calltypes)) {
                $index = 0;
                foreach($rule_calltypes as $key => $type){
                    if($type == $call_type){
                        $index = $key;
                        break;
                    }
                }
                if($rule_durations[$index] == 0){
                    $admin_call->approval = 'Pre-Approved';
                    $admin_call->classify = 'Business';
                }else if($elapse_seconds < $rule_durations[$index] * 60){
                    $admin_call->approval = 'Pre-Approved';
                    $admin_call->classify = 'Business';
                }else{
                    $admin_call->approval = 'Unclassified';
                    $admin_call->classify = 'Unclassified';
                }
                /*$admin_call->approval = 'Pre-Approved';
                $admin_call->classify = 'Business';*/
          
            }else {

                if($admin_call->carrier_charges <= 0) {
                    $admin_call->approval = 'No Approval';
                    $admin_call->classify = 'No Classify';
                }else {
                    if($elapse_seconds > $rules['min_approval_duration'] * 60 && $admin_call->carrier_charges > $rules['min_approval_amount'] ) {
                        $admin_call->approval = 'Unclassified';
                        $admin_call->classify = 'Unclassified';
                    }else {
                        $admin_call->approval = 'Pre-Approved';
                        $admin_call->classify = 'Business';
                    }
                }

            }


//            if(( !empty($rule_calltypes) && in_array($call_type ,$rule_calltypes)) ||
//                ( $elapse_seconds <= $rules['min_approval_duration'] * 60 && $admin_call->carrier_charges <= $rules['min_approval_amount'] )) {
//                if($admin_call->carrier_charges <= 0) {
//                    $admin_call->approval = 'No Approval';
//                    $admin_call->classify = 'No Classify';
//                }else {
//                    $admin_call->approval = 'Pre-Approved';
//                    $admin_call->classify = 'Business';
//                }
//                //$admin_call->classify = 'Business';
//            }
//            else {
//                if($admin_call->carrier_charges > 0) {
//                    $admin_call->approval = 'Unclassified';
//                    $admin_call->classify = 'Unclassified';
//                }else {
//                    $admin_call->approval = 'No Approval';
//                    $admin_call->classify = 'No Classify';
//                }
//                // $admin_call->approval = 'Waiting For Approval';
//                //$admin_call->classify = 'Unclassified';
//            }

            $admin_call->admin_charge_rate_id = $charge_value['rate_id'];

            $admin_call->save();

            $this->makeReceiveCallFromAdminCall($admin_call->id);
            $this->updatePropertyBuildingDeptSectionForAdminCall($admin_call->id);
        }
    }

    public function testPreapprovedCallTypes(Request $request){
        //https://192.168.1.91/testpreapproved?call_type=Mobile&carrier_charges=10&elapse_seconds=5
        $call_type = $request->get('call_type', '');
        $carrier_charges = $request->get('carrier_charges', 0);
        $elapse_seconds = $request->get('elapse_seconds', 0);
        $property_id = 4;
        $rules = PropertySetting::getClassifyRuleSetting($property_id);
        $rule_calltypes = array();
        $rule_types = array();
        $rule_durations = array();
        if(!empty($rules['pre_approved_call_types'])) {
            $rule_types = explode("," , $rules['pre_approved_call_types']);
            foreach($rule_types as $type){
                $calltypetemps = explode(":" , $type);
                $rule_calltypes[] = $calltypetemps[0];
                $rule_durations[] = $calltypetemps[1];
            }
        }
        if( !empty($rule_calltypes) && in_array($call_type ,$rule_calltypes)) {
            $index = 0;
            foreach($rule_calltypes as $key => $type){
                if($type == $call_type){
                    $index = $key;
                    break;
                }
            }
            if($rule_durations[$index] == 0){
                $approval = 'Pre-Approved';
                $classify = 'Business';
            }else if($elapse_seconds < $rule_durations[$index] * 60){
                $approval = 'Pre-Approved';
                $classify = 'Business';
            }else{
                $approval = 'Unclassified';
                $classify = 'Unclassified';
            }
        }else {
            if($carrier_charges <= 0) {
                $approval = 'No Approval';
                $classify = 'No Classify';
            }else {
                if($elapse_seconds > $rules['min_approval_duration'] * 60 && $carrier_charges > $rules['min_approval_amount'] ) {
                    $approval = 'Unclassified';
                    $classify = 'Unclassified';
                }else {
                    $approval = 'Pre-Approved';
                    $classify = 'Business';
                }
            }
        }
        echo $approval.'::::'.$classify;
    }
    
    private function makeReceiveCallFromGuestCall($id) {
        
    }

    private function makeReceiveCallFromAdminCall($id) {
        
    }

    private function makeReceiveCallFromBCCall($id) {
       
    }

    private function updatePropertyBuildingDeptSectionForAdminCall($id) {
        DB::select("UPDATE call_admin_calls AS ac 
            INNER JOIN call_staff_extn AS se ON ac.extension_id = se.id 
            INNER JOIN call_section AS cs ON se.section_id = cs.id 
            INNER JOIN common_building AS cb ON cs.building_id = cb.id 
            INNER JOIN common_department AS dept ON cs.dept_id = dept.id 
            SET ac.property_id = dept.property_id, ac.building_id = cs.building_id, ac.dept_id = cs.dept_id, ac.section_id = se.section_id  
            where ac.id = ?", [$id]);
    }

    public function testUpdate(Request $request) {
        $this->updatePropertyBuildingDeptSectionForAdminCall(1);
    }

    private function callcharge_trans_start($request, $flag) {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '11/16|00:01:19|000018.0|1234|8829912345|1234|307')));

        $date = $params[0];
        $time = $params[1];
        $duration = $params[2];
        $calleeid = $params[3];
        $extension = $params[4];
        $trunk = $params[5];
        $transfer = $params[6];

        $duration_flag = substr($duration, 0, 1);

        if($duration_flag == 'S')
        {
            $elapse_seconds = round(substr($duration, 1 - strlen($duration)));
        }
        else
        {
            $normal_duration = substr($duration, 1 - strlen($duration));
            $elapse_seconds = $this->hoursToSeconds($normal_duration);
        }

        CallTemp::where('trunk', $trunk)->delete();

        $call = new CallTemp();

        $calldate = new DateTime($date);
        $call->call_date = $calldate->format('Y-m-d');
        $call->start_time = $time;
        $call->duration = $elapse_seconds;
        $call->extension = $extension;
        $call->called_no = $calleeid;
        $call->trunk = $trunk;
        $call->call_direction = $flag;

        $call->save();
    }

    private function callcharge_trans_end($request, $flag) {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '11/16|00:01:19|000018.0|1234|1234|307')));

        $date = $params[0];
        $time = $params[1];
        $duration = $params[2];
        $calleeid = $params[3];
        $extension = $params[4];
        $trunk = $params[5];
        $transfer = $params[6];
        $pulse = 0;

        $duration_flag = substr($duration, 0, 1);

        if($duration_flag == 'S')
        {
            $elapse_seconds = round(substr($duration, 1 - strlen($duration)));
        }
        else
        {
            $normal_duration = substr($duration, 1 - strlen($duration));
            $elapse_seconds = $this->hoursToSeconds($normal_duration);
        }

        $alarm = array();
        $ret = array();

        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;

        $call_end_setting = DB::table('property_setting as ps')
            ->where('ps.property_id', $property_id)
            ->where('ps.settings_key', 'call_end_setting')
            ->select(DB::raw('ps.*'))
            ->first();

        $call_end = 'S';
        if( !empty($call_end_setting) )
            $call_end = $call_end_setting->value;

        $start_call = CallTemp::where('trunk', $trunk)->first();
        if( empty($start_call) )
        {
            // should be exception
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid Incoming call service request for received from %2$s in %1$s. Cause: There is no start call for this trunk ' . $trunk;
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $start_duration = $start_call->duration;

        if( $calleeid == 'S' )
            $calleeid = $start_call->called_no;

        if( $call_end == 'S' )
        {
            $elapse_seconds += $start_duration;
            $this->saveCallchargeRequest($start_call->call_date, $start_call->start_time, $start_call->extension, $trunk, '',NULL, $elapse_seconds, $calleeid, $start_call->call_direction, $pulse, $src_config, $ret, $alarm );
        }

        if( $call_end == 'E' )
        {
            $elapse_seconds += $start_duration;
            $this->saveCallchargeRequest($start_call->call_date, $start_call->start_time, $extension, $trunk, '',NULL, $elapse_seconds, $calleeid, $flag, $pulse, $src_config, $ret, $alarm  );
        }

        if( $call_end == 'SE' )
        {
            $this->saveCallchargeRequest($start_call->call_date, $start_call->start_time, $start_call->extension, $trunk, '',NULL, $start_call->duration, $start_call->called_no, $start_call->call_direction, $pulse, $src_config, $ret, $alarm  );
            $alarm = array();
            $this->saveCallchargeRequest($date, $time, $extension, $trunk, '',NULL, $elapse_seconds, $calleeid, $flag, $pulse, $src_config, $ret, $alarm  );
        }

        CallTemp::where('trunk', $trunk)->delete();
    }

    private function saveCallchargeRequest($date, $time, $extension, $trunk, $transfer,$access_code, $elapse_seconds, $calleeid, $flag, $pulse, $src_config, &$ret, &$alarm) {
        // check admin or guest extension
        $adminext = StaffExternal::where('extension', $extension)
		                         ->where('bc_flag', 0)
                                 ->first();

        $guestext_query = GuestExtension::where('extension', $extension);
        if( !empty($src_config) && !empty($src_config['src_build_id']) && $src_config['src_build_id'] > 0 )
            $guestext_query->where('bldg_id', $src_config['src_build_id']);
        $guestext = $guestext_query->first();

		$bcext = StaffExternal::where('extension', $extension)
		                        ->where('bc_flag', 1)
                                ->first();
                                
        if (empty($adminext) && empty($guestext)&& empty($bcext))        // there is no extension in both admin and guest
        {
            $adminext = new StaffExternal();
            $adminext->description = 'Unknown';
            $adminext->section_id = 1000;
            $adminext->extension= $extension;
            $adminext->save();

        }
        $charge_info = $this->getChargeMode($adminext, $guestext, $bcext, $ret, $alarm, $extension);
        if( $charge_info['code'] != 0 )
            return $charge_info['data'];

        $chargemode = $charge_info['chargemode'];
        $room_id = $charge_info['room_id'];
        $guest_id = $charge_info['guest_id'];
        $src_config['src_build_id'] = $charge_info['src_build_id'];
        $ret['src_config'] = $src_config;

        $normal_duration = Functions::getHHMMSSFormatFromSecond($elapse_seconds);
        $elapse = $this->hoursToMinute($normal_duration);

        $sales_outlet = 1;

        $destination = null;

        if( $flag != 1 && $flag != 0 )  // Internel nor Incoming 
        {
            if( strlen($calleeid) > 5 )
            {
                $call_info = $this->getCallerInfo($calleeid, $ret, $alarm);
                if( $call_info['code'] != 0 )
                    return $call_info['data'];

                $destination = $call_info['destination'];
            }
            else // if length < = 5, it is internal call
            {
                if( $flag > 2 )
                    $flag = 1;
            }
        }

        if( $flag > 2 )
        {
            $dest_group = $call_info['dest_group'];
            $call_type = $call_info['call_type'];
            $sales_outlet = $call_info['sales_outlet'];


            $result = $this->getTimeslab($date, $time, $ret, $alarm);
            if( $result['code'] != 0 )
                return $result['data'];

            $timeslab = $result['data'];

            $charge_value = $this->getChargeValue($chargemode, $timeslab, $dest_group, $elapse, $elapse_seconds, $ret, $alarm,$room_id ,$calleeid);
            if( $charge_value['code'] != 0 )
                return $charge_value['data'];
        }
        else
        {
            if( $flag == 0 )
                $call_type = 'Received';
            else if( $flag == 1 )
                $call_type = 'Internal';
            else if( $flag == 2 )
                $call_type = 'Missed';

            $charge_value = array();
            $charge_value['carrier_charge_value'] = 0;
            $charge_value['hotel_charge_value'] = 0;
            $charge_value['tax_value'] = 0;
            $charge_value['total_value'] = 0;
            $charge_value['rate_id'] = 0;
        }

        $this->saveCallchargeInfo($date, $time, $chargemode, $room_id, $guest_id, $guestext, $adminext, $bcext,
            $calleeid, $destination, $call_type, $trunk, $transfer,$access_code, $elapse_seconds, $pulse,
            $charge_value, $normal_duration, $sales_outlet, $ret, $alarm );

        return $this->sendAlarm($ret, $alarm);
    }

    public function fixCallChargeDestId(Request $request) {
        ini_set('memory_limit','-1');
        ini_set('max_execution_time', 600);

        $call_date = $request->get('call_date', '2016-11-24');

        $admin_call_list = AdminCall::where('call_type', '!=', 'Internal')
            ->where('destination_id', 0)
            ->where('call_date', '>', $call_date)
            ->get();

        foreach($admin_call_list as $key => $call) {
            $calleeid = $call->called_no;
            for( $i = strlen( $call->called_no ); $i > 0; --$i ) {
                $searchstr = substr($calleeid, 0, $i);
                $destination = Destination::where('code', $searchstr)->first();
                if(!empty($destination))
                    break;
            }

            if (empty($destination)) // if there is no destination
                continue;

            $call->destination_id = $destination->id;
            $call->save();

            echo $calleeid . ' ';
        }

        $guest_call_list = GuestCall::where('call_type', '!=', 'Internal')
            ->where('destination_id', 0)
            ->where('call_date', '>', $call_date)
            ->get();

        foreach($guest_call_list as $key => $call) {
            $calleeid = $call->called_no;
            for( $i = strlen( $call->called_no ); $i > 0; --$i ) {
                $searchstr = substr($calleeid, 0, $i);
                $destination = Destination::where('code', $searchstr)->first();
                if(!empty($destination))
                    break;
            }

            if (empty($destination)) // if there is no destination
                continue;

//            $call_log = GuestCall::find($call->id);
            $call->destination_id = $destination->id;
            $call->save();

            echo $calleeid . ' ';
        }
		$bc_call_list = BCCall::where('call_type', '!=', 'Internal')
            ->where('destination_id', 0)
            ->where('call_date', '>', $call_date)
            ->get();

        foreach($bc_call_list as $key => $call) {
            $calleeid = $call->called_no;
            for( $i = strlen( $call->called_no ); $i > 0; --$i ) {
                $searchstr = substr($calleeid, 0, $i);
                $destination = Destination::where('code', $searchstr)->first();
                if(!empty($destination))
                    break;
            }

            if (empty($destination)) // if there is no destination
                continue;

//            $call_log = GuestCall::find($call->id);
            $call->destination_id = $destination->id;
            $call->save();

            echo $calleeid . ' ';
        }
    }

    public function fixRateId(Request $request) {
        ini_set('memory_limit','-1');
        ini_set('max_execution_time', 600);

        $call_date = $request->get('call_date', '2016-08-24');

        $admin_call_list = AdminCall::where('call_date', '>', $call_date)
            ->where('admin_charge_rate_id', 0)
            ->whereNotIn('call_type', array('Received', 'Internal', 'Missed'))
            ->get();

        $daylist = [
            '0' => 'Sunday',
            '1' => 'Monday',
            '2' => 'Tuesday',
            '3' => 'Wednesday',
            '4' => 'Thursday',
            '5' => 'Friday',
            '6' => 'Saturday',
        ];

        foreach($admin_call_list as $key => $call) {
            $date = $call->call_date;
            $time = $call->start_time;
            $datetime = new DateTime($date);
            $dayofweek = $datetime->format('w');

            $day = $daylist[$dayofweek];

            $timeslab = TimeSlab::where('start_time', '<', $time)->where('end_time', '>=', $time)
                ->where('days_of_week', 'LIKE', '%' . $day . '%')->first();

            if (empty($timeslab))    // there is no time slab
            {
                continue;
            }

            $dest_group = GroupDestination::where('destination_id', $call->destination_id)->first();
            if (empty($dest_group))        // There is no destination group
                continue;

            $adminrate = AdminChargeMap::where('time_slab_group', $timeslab->id)
                ->where('carrier_group_id', $dest_group->carrier_group_id)
                ->first();
            if (empty($adminrate))
                continue;


            $call->admin_charge_rate_id = $adminrate->id;
            $call->save();

            echo $adminrate->id . ' ';
        }

        $guest_call_list = GuestCall::where('call_date', '>', $call_date)
            ->where('guest_charge_rate_id', 0)
            ->whereNotIn('call_type', array('Received', 'Internal', 'Missed'))
            ->get();

        foreach($guest_call_list as $key => $call) {
            $date = $call->call_date;
            $time = $call->start_time;
            $datetime = new DateTime($date);
            $dayofweek = $datetime->format('w');

            $day = $daylist[$dayofweek];

            $timeslab = TimeSlab::where('start_time', '<', $time)->where('end_time', '>=', $time)
                ->where('days_of_week', 'LIKE', '%' . $day . '%')->first();

            if (empty($timeslab))    // there is no time slab
            {
                continue;
            }

            $dest_group = GroupDestination::where('destination_id', $call->destination_id)->first();
            if (empty($dest_group))        // There is no destination group
                continue;

            $guestrate = GuestChargeMap::where('time_slab', $timeslab->id)
                ->where('carrier_group_id', $dest_group->carrier_group_id)
                ->first();
            if( empty($guestrate) )
                continue;

            $call->guest_charge_rate_id = $guestrate->id;
            $call->save();

            echo $guestrate->id . ' ';
        }
$bc_call_list = BCCall::where('call_date', '>', $call_date)
            ->where('guest_charge_rate_id', 0)
            ->whereNotIn('call_type', array('Received', 'Internal', 'Missed'))
            ->get();

        foreach($bc_call_list as $key => $call) {
            $date = $call->call_date;
            $time = $call->start_time;
            $datetime = new DateTime($date);
            $dayofweek = $datetime->format('w');

            $day = $daylist[$dayofweek];

            $timeslab = TimeSlab::where('start_time', '<', $time)->where('end_time', '>=', $time)
                ->where('days_of_week', 'LIKE', '%' . $day . '%')->first();

            if (empty($timeslab))    // there is no time slab
            {
                continue;
            }

            $dest_group = GroupDestination::where('destination_id', $call->destination_id)->first();
            if (empty($dest_group))        // There is no destination group
                continue;

            $guestrate = GuestChargeMap::where('time_slab', $timeslab->id)
                ->where('carrier_group_id', $dest_group->carrier_group_id)
                ->first();
            if( empty($guestrate) )
                continue;

            $call->guest_charge_rate_id = $guestrate->id;
            $call->save();

            echo $guestrate->id . ' ';
        }
    }

    private function roomstatus($request)
    {
        $channel_id = $request->input('channel_id', '0');
        $property_id = $request->input('property_id', '0');
        $src_config = $request->input('src_config');

        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $params = explode('|', $this->getParam($request->input('param', '0|00000')));

        $ivr_code = $params[0];
        $extension = $params[1];

        $alarm = array();

        $ret = array();
        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['src_config'] = $src_config;

        // check admin or guest extension
        $guestext = GuestExtension::where('extension', $extension)->first();

        if (empty($guestext))        // there exist extension in both admin and guest
        {
            // should be exception
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid room status change request for received from %2$s in %1$s. Cause: There is no data for this extension ' . $extension;
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $src_config['src_build_id'] = $guestext->bldg_id;
        $ret['src_config'] = $src_config;

        $room_id = $guestext->room_id;

        $hskp = DB::table('services_hskp_status as hs')
            ->where('bldg_id', $guestext->bldg_id)
            ->where('ivr_code', $ivr_code)
            ->first();

        if (empty($hskp))    // there is no hskp
        {
            // should be exception
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid room status change request for received from %2$s in %1$s. Cause: There is no hskp status info in db for this ivr code ' . $ivr_code . ' and Building ' . $guestext->bldg_id;
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $room = Room::find($room_id);
        if( empty($room) )
        {
            // should be exception
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid room status change request for received from %2$s in %1$s. Cause: There is no room in db for this room_id ' . $room_id;
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $room->hskp_status_id = $hskp->id;
        $room->save();

        // save hskp log
        $hskp_log = new HskpStatusLog();

        $hskp_log->room_id = $room_id;
        $hskp_log->hskp_id = $hskp->id;
        $hskp_log->user_id = 0;
        $hskp_log->state = 0;

        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");
        $hskp_log->created_at = $cur_time;

        $hskp_log->save();

        $ret['msg'] = sprintf("RE|RN%d|RS%d",
            $room->room, $hskp->pms_code);

        Functions::sendMessageToInterface('interface_hotlync', $ret);    

        // $this->sendAlarm($ret, $alarm);
    }

    private function minibarpost($request)
    {
        $ret = array();
        $channel_id = 0;
        $src_config = $request->input('src_config');

        $property_id = $request->input('property_id', '0');
        
        if( !empty($src_config) )
            $property_id = $src_config['src_property_id'];

        $ivr_code = $request->input('ivr_code', '0');
        $quantity = $request->input('quantity', '0');
        $room_number = $request->input('room_number', '0');

        $alarm = array();

        $room_info = $this->getRoomInfowdChannel($property_id, $room_number, $src_config);

        $ret['property_id'] = $property_id;
        $ret['channel_id'] = $channel_id;
        $ret['src_config'] = $src_config;

        if( !empty($room_info) ){
            $src_config['src_build_id'] = $room_info->bldg_id;
            $ret['src_config'] = $src_config;
        }

        // find room service item
        $room_service_item = RoomServiceItem::where('ivr_code', $ivr_code)->first();
        if (empty($room_service_item))    // there is no hskp
        {
            // should be exception
            $data = array();
            $data['type'] = 'error';
            $data['msg'] = 'Invalid minibar posting request for received from %2$s in %1$s. Cause: There is no room service item info in db for this ivr code ' . $ivr_code;
            array_push($alarm, $data);

            return $this->sendAlarm($ret, $alarm);
        }

        $pms_code = $room_service_item->pms_code;

        $seqnum = Redis::get('minibar_seqnum') + 1;
        Redis::set('minibar_seqnum', $seqnum);

        date_default_timezone_set(config('app.timezone'));
        $now = date("Y-m-d H:i:s");

        $total_flag = 1;
        if ($total_flag == 0) {
            $ret['msg'] = sprintf("PS|RN%s|PTM|P#%d|M#%d|MA%d|DA%s|TI%s",
                $room_number, $seqnum, $quantity, $pms_code, date('ymd'), date('His'));
        } else {
            $room_service_group = RoomServiceGroup::find($room_service_item->room_service_group);
            if (empty($room_service_group))    // there is no hskp
            {
                // should be exception
                $data = array();
                $data['type'] = 'error';
                $data['msg'] = 'Invalid minibar posting request for received from %2$s in %1$s. Cause: There is no room service group info in db for this ivr code ' . $ivr_code;
                array_push($alarm, $data);

                return $this->sendAlarm($ret, $alarm);
            }

            $sales_outlet = $room_service_group->sales_outlet;

            $ret['msg'] = sprintf("PS|RN%s|PTM|P#%d|TA%d|SO%s|DA%s|TI%s",
                $room_number, $seqnum, $quantity, $sales_outlet, date('ymd'), date('His'));
        }

        Functions::sendMessageToInterface('interface_hotlync', $ret);    

        return $this->sendAlarm($ret, $alarm);
    }

    private function hoursToMinute($hour)
    { // $hour must be a string type: "HH:mm:ss"

//		$parse = array();
//		if (!preg_match ('#^(?<hours>[\d]%3$s):(?<mins>[\d]%3$s):(?<secs>[\d]%3$s)$#',$hour,$parse)) {
//			// Throw error, exception, etc
//			return "";
//		}
        $minutes = 0;
        $seconds = 0;
        sscanf($hour, "%d:%d:%d", $hours, $minutes, $seconds);

        $time_minutes = $hours * 60 + $minutes;
        if ($seconds > 0)
            $time_minutes += 1;

        return $time_minutes;
    }

    private function hoursToSeconds($hour)
    { // $hour must be a string type: "HH:mm:ss"
        $minutes = 0;
        $seconds = 0;
        sscanf($hour, "%d:%d:%d", $hours, $minutes, $seconds);

        $time_seconds = $hours * 3600 + $minutes * 60 + $seconds;

        return $time_seconds;
    }

    private function outputResult($retcode, $content = '', $error_msg = null)
    {
        if ($error_msg == null) {
            switch ($retcode) {
                case P_SUCCESS:
                    $error_msg = '';
                    break;
                case MISSING_PARAMETER:
                    $error_msg = 'Parameter is missing.';
                    break;
                case INVALID_PARAMETER:
                    $error_msg = 'Parameter is invalid.';
                    break;
                default :
                    $error_msg = '';
                    break;
            }
        }

        $response = array('retcode' => $retcode, 'content' => $content, 'error_msg' => $error_msg);

        return json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    public function postManual(Request $request) {
        $property_id = $request->get('property_id', 0);
        $user_id = $request->get('user_id', 0);
        $action = $request->get('action', 0);
        //$building_id = $request->get('building_id', 0);

        $room_id = $request->get('room_id', 0);
        $room = $request->get('room', 0);
        $guest_name = $request->get('guest_name', '');
        $value = $request->get('value', '');
        $save_flag = $request->get('save_flag', '');
        $rooms = $request->get('select_id', '');

        $group_bar = $request->get('selected', 'false');

        
        
    
        $ret = array();        

        $ret['property_id'] = $property_id;
        $src_config = array();
        $src_config['src_property_id'] = $property_id;
        $src_config['accept_build_id'] = array();

        if ($group_bar == false)
        {
            switch($action)
            {
                case 'Checkin':                
                case 'Checkout':                
                case 'Guest Change':                                
                case 'Message Lamp':                
                case 'Do Not Disturb':                
                case 'Class Of Service':
                    $room_info = DB::table('common_room as r')
                        ->join('common_floor as f', 'r.flr_id', '=', 'f.id')            
                        ->where('r.id', '=', $room_id)            
                        ->select(DB::raw('r.id, r.room, f.bldg_id'))
                        ->first();

                    if (empty($room_info)) {
                        $ret['code'] = 201;
                        $ret['messsage'] = 'Invalid Room';
                        return Response::json($ret);
                    }

                    $src_config['src_build_id'] = $room_info->bldg_id;

                    $ret['room_number'] = $room_info->room;

                    break;            
            }        


            $ret['src_config'] = $src_config;
            
            if( $save_flag == false )
            {
                switch($action)
                {
                    case 'Checkin':
                        $this->sendCheckinResponse($ret, $room_id, $guest_name);
                        break;
                    case 'Checkout':
                        $exist = Guest::where('room_id', $room_id)
                            ->where('checkout_flag', 'checkin')// checkin
                            ->exists();

                        if ($exist == false){

                                HskpRoomStatus::where('id', $room_id)->update(['occupancy' => 'Vacant', 'schedule' => '']);

                        }
                        $this->sendCheckoutResponse($ret, $room_id, $guest_name);
                        break;    
                    case 'Guest Change':                
                        $this->sendGuestchangeResponse($ret, $room_id, $guest_name);
                        break;        
                    case 'Message Lamp':
                        $this->sendFlagvalueResponse($ret, 'MW', $value, $room_id);
                        break;    
                    case 'Do Not Disturb':
                        $this->sendFlagvalueResponse($ret, 'DND', $value, $room_id);
                        break;        
                    case 'Class Of Service':
                        $this->sendFlagvalueResponse($ret, 'RST', $value, $room_id);
                        break;            
                }
                $ret['messsage'] = 'Only Sent Message';

            }
            else    // store mode        
            {
                $request->request->add(['channel_id' => 0]);
                $request->request->add(['src_config' => $src_config]);

                switch($action)
                {
                    case 'Checkin':                                    
                        $guest_id = $request->get('guest_id', 0);
                        $language = $request->get('language', 'EN');
                        $vip = $request->get('vip', 0);
                        $guest_group = $request->get('guest_group', 0);
                        $share = $request->get('share', 0);
                        $share = $share ? 'Y' : 'N';
                        $arrival = date('Ymd', strtotime($request->get('arrival', '')));
                        $departure = date('Ymd', strtotime($request->get('departure', '')));
                        $first_name = $request->get('first_name', '');
                        $title = $request->get('title', '');
                        $profile_id = $request->get('profile_id', 0);

                        $params_array = [$ret['room_number'], $guest_id, $guest_name, $language, $vip, $guest_group, $share, $arrival, $departure, $first_name, $title, $profile_id, 'N'];  
                        $params = implode('|', $params_array);     
                        
                        $request->request->add(['param' => $params]);                    
                        
                        $this->checkin($request);                    
                        break;
                    case 'Checkout':
                        $guest_id = $request->get('guest_id', 0);
                        $params_array = [$ret['room_number'], $guest_id];  
                        $params = implode('|', $params_array);
                        $request->request->add(['param' => $params]);

                        $this->checkout($request);
                        break;    
                    case 'Guest Change':                
                        $guest_id = $request->get('guest_id', 0);
                        $language = $request->get('language', 'EN');
                        $vip = $request->get('vip', 0);
                        $guest_group = $request->get('guest_group', 0);
                        $share = $request->get('share', 0);
                        $share = $share ? 'Y' : 'N';
                        $arrival = date('Ymd', strtotime($request->get('arrival', '')));
                        $departure = date('Ymd', strtotime($request->get('departure', '')));
                        $first_name = $request->get('first_name', '');
                        $title = $request->get('title', '');

                        $params_array = [$ret['room_number'], $guest_id, $guest_name, $language, $vip, $guest_group, $share, $arrival, $departure, $first_name, $title];  
                        $params = implode('|', $params_array);                         
                        $request->request->add(['param' => $params]);                    
                        
                        $this->guestinfo($request);                    
                        break;        
                    case 'Message Lamp':
                        $guest_id = $request->get('guest_id', 0);
                        $ml_flag = $value > 0 ? 'Y' : 'N';               

                        $params_array = [$ret['room_number'], $ml_flag, $guest_id];  
                        $params = implode('|', $params_array);                         
                        $request->request->add(['param' => $params]);                    

                        $this->messagelamp($request);
                        break;    
                    case 'Do Not Disturb':                
                        $dnd_flag = $value > 0 ? 'Y' : 'N';                  

                        $params_array = [$ret['room_number'], $dnd_flag];  
                        $params = implode('|', $params_array);                         
                        $request->request->add(['param' => $params]);                    

                        $this->donotdisturb($request);
                        break;        
                    case 'Class Of Service':
                        $params_array = [$ret['room_number'], $value];  
                        $params = implode('|', $params_array);                         
                        $request->request->add(['param' => $params]);                    

                        $this->classofservice($request);
                        break;         

                    case 'Call Charge':
                        $call_type = $request->get('call_type', 0);
                        $call_date = date('m/d', strtotime($request->get('call_date', '')));
                        $call_time = date('H:i', strtotime($request->get('call_time', '')));

                        $duration = 'F' . $request->get('duration', '');
                        $extension = $request->get('extension', '');                    

                        $trunk = $request->get('trunk', '');
                        $transfer = $request->get('transfer', '');     

                        $calleeid = $request->get('calleeid', '');     

                        $params_array = [$call_date, $call_time, $duration, $calleeid, $extension, $trunk, $transfer];  
                        $params = implode('|', $params_array);                         
                        $request->request->add(['param' => $params]);        

                        $ret['input'] = $request->all();           
                        
                        $this->callcharge($request, $call_type);                    

                        break;   
                    case 'Room Status':
                        $ivr_code = $request->get('ivr_code', '');
                        $extension = $request->get('extension', '');
                        $params_array = [$ivr_code, $extension];  
                        $params = implode('|', $params_array);                         
                        $request->request->add(['param' => $params]);                    
                        $ret['input'] = $request->all();
                        $this->roomstatus($request);

                        break;                               
                }
                $ret['messsage'] = 'Save DB and sent message';
            }

            
            $ret['code'] = 200;
            $ret['message'] = 'Post is sent to Interface successfully';

            return Response::json($ret);

        }
        else
        {
            $roomlist = DB::table('common_room')
                    ->whereIn('room', $rooms)
                    ->select('id')
                    ->get(); 
            
            $ret = array();         
            
            for($i = 0; $i < count($roomlist); $i++)
            {  
            
                $room_id = $roomlist[$i]->id;

                $room_info = DB::table('common_room as r')
                        ->join('common_floor as f', 'r.flr_id', '=', 'f.id')            
                        ->where('r.id', '=', $room_id)            
                        ->select(DB::raw('r.id, r.room, f.bldg_id'))
                        ->first();
                $src_config['src_build_id'] = $room_info->bldg_id;
                $ret['src_config'] = $src_config;
                $this->sendFlagvalueResponse($ret, 'RST', $value, $room_id);
                
            }
        
            $ret['code'] = 200;
            $ret['message'] = 'Post is sent to Interface group successfully';

            return Response::json($ret);
        }
    }

    public function testOpenSSl(Request $request)
    {
        $param = $this->getParam('Hello World');   

        echo $param;
    }

    private function updateGuestReservationStatus($guest_id, $status)
    {
        $input = array();
        $input['status'] = $status;

        DB::table('common_guest_reservation')
				->where('res_id', $guest_id)
				->update($input);
    }
}
