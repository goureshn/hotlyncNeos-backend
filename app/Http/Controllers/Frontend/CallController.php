<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Call\Destination;
use App\Models\Call\GuestExtension;
use App\Models\Call\StaffExternal;
use App\Models\Common\PropertySetting;
// use App\Models\IVR\IVRAgentStatus;
use App\Models\IVR\IVRCallProfile;
use Illuminate\Http\Request;
use DB;
use Response;

define("RINGING", 'Ringing');
define("ABANDONED", 'Abandoned');
define("ANSWERED", 'Answered');

define("MISSED", 'Missed');
define("CALLBACK", 'Callback');
define("FOLLOWUP", 'Modify');
define("HOLD", 'Hold');
define("TRANSFERRED", 'Transferred');
define("DROPPED", 'Dropped');

define("ONLINE", 'Online');
define("AVAILABLE", 'Available');
define("NOTAVAILABLE", 'Not Available');
define("BUSY", 'Busy');
define("ONBREAK", 'On Break');
define("IDLE", 'Idle');
define("WRAPUP", 'Wrapup');
define("OUTGOING", 'Outgoing');
define("LOGOUT", 'Log out');
define("AWAY", 'Away');

class CallController extends Controller
{
	public function generateLockFile(Request $request)
	{
		$lock_file = $_SERVER["DOCUMENT_ROOT"] . '/lock.txt';
		$lock_text = 'Lock File';
		file_put_contents($lock_file, $lock_text);

		return $lock_file . " is generated for locking.";
	}

	public function getAgentStatus(Request $request)
	{
		$agent_id = $request->get('agent_id', 0);

		$agent_status = $this->getAgentStatusData($agent_id);

		if( !empty($agent_status) )
		{
			$ticket = DB::table('ivr_voice_recording')
				->where('id', $agent_status->ticket_id)
				->orderBy('id', 'desc')
				->first();
		
			if( !empty($ticket) )
			{
				$agent_status->ticket = $ticket;
				$caller = $this->getCallerProfile($ticket);

				$agent_status->caller = $caller;
			}
			else
			{
				$agent_status->ticket = array('id' => 0);
				$agent_status->caller = array('id' => 0);
			}

			$rules = array();
			$rules['sip_server'] = 'developdxb.myhotlync.com';
			$rules = PropertySetting::getPropertySettings($agent_status->property_id, $rules);
			$agent_status->sip_server = $rules['sip_server'];
		}

		return Response::json($agent_status);
	}

	public function getAgentStatusData($agent_id)
	{
		$agent_status = DB::table('ivr_agent_status_log as asl')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->where('asl.user_id', $agent_id)
				->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cd.property_id'))
				->first();

		if( empty($agent_status) )
			return array();
		
		$ticket = DB::table('ivr_voice_recording')
			->where('id', $agent_status->ticket_id)
			->orderBy('id', 'desc')
			->first();

		$call_guest = array();
		if( !empty($ticket) )
		{
			$agent_status->ticket = $ticket;
			$adminext = StaffExternal::where('extension', $ticket->callerid)
						->where('bc_flag', 0)
						->where('enable',1)
						->first();
			$guestext = GuestExtension::where('extension', $ticket->callerid)
						->where('enable',1)
						->first();
			
			if (!empty($adminext))
			{
				$admincall = $this->getStaffProfile($adminext);
				$agent_status->origin = 'Internal';
				$agent_status->admincall = $admincall;
				$agent_status->check = 0;
			}
			elseif (!empty($guestext)) 
			{
				
				$call_guest = $this->getGuestProfile($guestext);
				$agent_status->check = 1;
				$agent_status->origin = 'Internal';
				$room =  DB::table('common_room')->where('id',$guestext->room_id)->select('room')->first();
				$agent_status->room = $room->room;
				$agent_status->guestcall = $call_guest;
			}
			else{
				$caller = $this->getCallerProfile($ticket);
				$agent_status->caller = $caller;
				$agent_status->check = 2;
			}
		}
		else
		{
			$agent_status->ticket = array('id' => 0);
			$agent_status->caller = array('id' => 0);
			$agent_status->guestcall = array('id' => 0);
			$agent_status->admincall = array('id' => 0);
		//	$agent_status->check = 1 ;
		}

		if( empty($agent_status) )
			return array();
		else {
			return $agent_status;
		}
	}

	private function getStaffProfile($adminext) {
		$cur_date = date("Y-m-d");

		$admin = DB::table('call_staff_extn as se')
				->join('call_section as cs', 'se.section_id', '=', 'cs.id')
				->join('common_department as cd', 'cs.dept_id', '=', 'cd.id')
				->select(DB::raw('se.*, cd.department'))
				->where('se.extension' , $adminext->extension)
				->first();
				
		
		return $admin;

	}

	private function getGuestProfile($guestext) {
		$cur_date = date("Y-m-d");

		$guest = DB::table('common_guest as cg')
				->join('common_room as cr', 'cg.room_id', '=', 'cr.id')
				->join('common_floor as cf', 'cr.flr_id', '=', 'cf.id')
				->join('common_building as cb', 'cf.bldg_id', '=', 'cb.id')
				->join('common_vip_codes as vc', 'vc.vip_code', '=', 'cg.vip')
				->leftJoin('common_guest_advanced_detail as gad', 'cg.id', '=', 'gad.id')
				->where('cg.room_id' , $guestext->room_id)
				->where('cg.checkout_flag', 'checkin')
				->where('cg.departure','>=', $cur_date)
				->select(DB::raw('cg.*, vc.name as vip_code'))
				->first();
				
		
		return $guest;

	}

	public function  getCallerProfile($ticket){
		$caller = IVRCallProfile::where('callerid', $ticket->callerid)
			->first();
		if(!empty($caller) && !empty($caller->national)) {
			return $caller;
		}

		$destination = Destination::find($ticket->call_origin);

		if( empty($caller) )
			$caller = new IVRCallProfile();

		if( $ticket->call_type == 'Internal' )
			$caller->national = 'Internal';
		else{
			if( !empty($destination) )
				$caller->national = $destination->country;
			else
				$caller->national = 'Unknown';
		}

		$caller->mobile = $ticket->callerid;
		$caller->phone = $ticket->callerid;

		if( $caller->id > 0 )
			$caller->save();

		return $caller;
	}
}
