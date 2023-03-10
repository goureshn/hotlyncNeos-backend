<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests;

use App\Models\Marketing\CampaignList;
use App\Models\Marketing\CampaignAddressbookMember;
use App\Models\Marketing\CampaignGuestMember;
use App\Models\Marketing\MarketingGuest;
use App\Models\Marketing\AddressBook;
use App\Models\Marketing\AddressbookMember;
use App\Models\Common\PropertySetting;


define("ADDRESS_BOOK", 'Address Book');
define("MANUALLY", 'Manually');


use DB;
use Illuminate\Http\Request;
use Response;
use Excel;
use Redis;

class CampaignController extends Controller
{
	public function getList(Request $request)
	{
		$start = microtime(true);

		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		$page = $request->get('page', 0);
		$pageSize = $request->get('pagesize', 20);
		$skip = $page;
		$orderby = $request->get('field', 'id');
		$sort = $request->get('sort', 'asc');
		$property_id = $request->get('property_id', '0');		
		// $filter = $request->get('filter');
		// $filter_value = $request->get('filter_value', '');
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$user_id = $request->get('user_id', 0);
		$property_ids_by_jobrole = $request->get('property_ids_by_jobrole', []);
		
		$last24 = date('Y-m-d H:i:s', strtotime("-1 days"));

		if ($pageSize < 0)
			$pageSize = 20;

		$ret = array();
		$query = DB::table('marketing_campaign_list as cl')
				->leftJoin('common_property as cp', 'cl.property_id', '=', 'cp.id');

		$query->whereIn('cl.property_id', $property_ids_by_jobrole);
		
		$data_query = clone $query;

		$data_list = $data_query
				->orderBy($orderby, $sort)
				->select(DB::raw('cl.*, cp.name as property_name'))
				->skip($skip)->take($pageSize)
				->get();

		foreach($data_list as $key => $row) 
		{
			if( $row->send_to == ADDRESS_BOOK )
			{
				$row->book_tags = CampaignList::getAddressbookList($row->id);
			}

			if( $row->send_to == MANUALLY )
			{
				$row->guest_tags = CampaignList::getGuestList($row->id);
			}
		}		

		$count_query = clone $query;
		$totalcount = $count_query->count();


		$ret['code'] = 200;
		$ret['message'] = '';
 		$ret['datalist'] = $data_list;
		$ret['totalcount'] = $totalcount;

		$end = microtime(true);	
		$ret['time'] = $end - $start;
		$ret['property_ids'] = $property_ids_by_jobrole;

		return Response::json($ret);
	}


	function create(Request $request) {
		$property_id = $request->get('property_id', 0);
		$name = $request->get('name', '');
		$active = $request->get('active', true);
		$type = $request->get('type', true);
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$send_to = $request->get('send_to', '');
		$sms_flag = $request->get('sms_flag', true);
		$email_flag = $request->get('email_flag', true);		
		$sms_content = $request->get('sms_content', '');
		$email_content = $request->get('email_content', '');
		$email_subject = $request->get('email_subject', '');
		$periodic = $request->get('periodic', '');
		$before_date = $request->get('before_date', 0);
		$every_date = $request->get('every_date', 0);
		$holiday = $request->get('holiday', '');
		$trigger_at = $request->get('trigger_at', '');
		$reject_flag = $request->get('reject_flag', true);
		$book_tags = $request->get('book_tags', []);
		$guest_tags = $request->get('guest_tags', []);
		

		$origin_send_to = $send_to . '';
		if( $send_to == 'Upload Excelsheet' )
			$send_to = ADDRESS_BOOK;

		$campaign = new CampaignList();

		$campaign->property_id = $property_id;
		$campaign->name = $name;
		$campaign->active = $active;
		$campaign->type = $type;
		$campaign->start_date = $start_date;
		$campaign->end_date = $end_date;
		$campaign->send_to = $send_to;
		$campaign->sms_flag = $sms_flag;
		$campaign->email_flag = $email_flag;
		$campaign->sms_content = $sms_content;
		$campaign->email_content = $email_content;
		$campaign->email_subject = $email_subject;
		$campaign->reject_flag = $reject_flag;

		$campaign->periodic = $periodic;
		$campaign->before_date = $before_date;
		$campaign->every_date = $every_date;
		$campaign->holiday = $holiday;
		$campaign->trigger_at = $trigger_at;

		$campaign->calcTriggerDateTime(false);

		$campaign->save();

		// save address book
		if( $origin_send_to == ADDRESS_BOOK )
		{
			foreach($book_tags as $row) {
				$pivot = new CampaignAddressbookMember();
				$pivot->campaign_id = $campaign->id;
				$pivot->book_id = $row['id'];

				$pivot->save();
			}	
		}

		// save guest ids
		if( $origin_send_to == MANUALLY )
		{
			foreach($guest_tags as $row) {
				$pivot = new CampaignGuestMember();
				$pivot->campaign_id = $campaign->id;
				$pivot->guest_id = $row['id'];

				$pivot->save();
			}	
		}

		if( $campaign->periodic == 'Immediately' && $origin_send_to != 'Upload Excelsheet')
			$this->sendPromotion($campaign);

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['campaign'] = $campaign;

		return Response::json($ret);
	}

	function update(Request $request) {
		$id = $request->get('id', 0);
		$property_id = $request->get('property_id', 0);
		$name = $request->get('name', '');
		$active = $request->get('active', true);
		$type = $request->get('type', true);
		$start_date = $request->get('start_date', '');
		$end_date = $request->get('end_date', '');
		$send_to = $request->get('send_to', '');
		$sms_flag = $request->get('sms_flag', true);
		$email_flag = $request->get('email_flag', true);		
		$sms_content = $request->get('sms_content', '');
		$email_content = $request->get('email_content', '');
		$email_subject = $request->get('email_subject', '');
		$periodic = $request->get('periodic', '');
		$before_date = $request->get('before_date', 0);
		$every_date = $request->get('every_date', 0);
		$holiday = $request->get('holiday', '');
		$trigger_at = $request->get('trigger_at', '');
		$reject_flag = $request->get('reject_flag', true);
		$book_tags = $request->get('book_tags', []);
		$guest_tags = $request->get('guest_tags', []);

		$origin_send_to = $send_to . '';
		if( $send_to == 'Upload Excelsheet' )
			$send_to = ADDRESS_BOOK;

		$campaign = CampaignList::find($id);

		$campaign->property_id = $property_id;
		$campaign->name = $name;
		$campaign->active = $active;
		$campaign->type = $type;
		$campaign->start_date = $start_date;
		$campaign->end_date = $end_date;
		$campaign->send_to = $send_to;
		$campaign->sms_flag = $sms_flag;
		$campaign->email_flag = $email_flag;
		$campaign->sms_content = $sms_content;
		$campaign->email_content = $email_content;
		$campaign->email_subject = $email_subject;
		$campaign->reject_flag = $reject_flag;

		$campaign->periodic = $periodic;
		$campaign->before_date = $before_date;
		$campaign->every_date = $every_date;
		$campaign->holiday = $holiday;
		$campaign->trigger_at = $trigger_at;

		$campaign->calcTriggerDateTime(false);

		$campaign->save();

		// remove old pivot table.
		CampaignAddressbookMember::where('campaign_id', $campaign->id)->delete();
		CampaignGuestMember::where('campaign_id', $campaign->id)->delete();

		// save address book
		if( $origin_send_to == ADDRESS_BOOK )
		{			
			foreach($book_tags as $row) {
				$pivot = new CampaignAddressbookMember();
				$pivot->campaign_id = $campaign->id;
				$pivot->book_id = $row['id'];

				$pivot->save();
			}	
		}

		// save guest ids
		if( $origin_send_to == MANUALLY )
		{
			foreach($guest_tags as $row) {
				$pivot = new CampaignGuestMember();
				$pivot->campaign_id = $campaign->id;
				$pivot->guest_id = $row['id'];

				$pivot->save();
			}	
		}

		if( $campaign->periodic == 'Immediately' && $origin_send_to != 'Upload Excelsheet')
			$this->sendPromotion($campaign);

		$ret = array();
		$ret['code'] = 200;
		$ret['message'] = '';
		$ret['campaign'] = $campaign;

		return Response::json($ret);
	}

	public function uploadAddressbookExcel(Request $request) {
 		$output_dir = "uploads/campaign/";
		
		$ret = array();

		$filekey = 'files';

		$id = $request->get('id', 0);
		$book_id = $request->get('book_id', 0);
		$name = $request->get('name', '');
		$client_id = $request->get('client_id', 0);
		$user_id = $request->get('user_id', 0);
		$periodic = $request->get('periodic', '');

		if( $book_id == 0 )
		{
			$address_book = AddressBook::where('name', $name)->first();
			// create new address book
			if( empty($address_book) )
				$address_book = new AddressBook();

			$address_book->client_id = $client_id;
			$address_book->name = $name;
			$address_book->created_by = $user_id;
			$address_book->public = 0;

			$address_book->save();

			$book_id = $address_book->id;
		}

		// remove old pivot table.
		CampaignAddressbookMember::where('campaign_id', $id)->delete();
		CampaignGuestMember::where('campaign_id', $id)->delete();

		// add address book to campaign
		if( $book_id > 0 )
		{
			$pivot = CampaignAddressbookMember::where('campaign_id', $id)->where('book_id', $book_id)->first();
			if( empty($pivot) )
				$pivot = new CampaignAddressbookMember();

			$pivot->campaign_id = $id;
			$pivot->book_id = $book_id;

			$pivot->save();
		}
		
		$fileCount = count($_FILES[$filekey]["name"]);

		$guest_error_list = [];
		for ($i = 0; $i < $fileCount; $i++)
		{
			$fileName = $_FILES[$filekey]["name"][$i];
			$ext = pathinfo($fileName, PATHINFO_EXTENSION);	
			$filename1 = "campaign_" . $id . '_' . $i . '_' . $fileName;
			
			$dest_path = $output_dir . $filename1;
			move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);						
		
			Excel::selectSheets('address')->load($dest_path, function($reader) use ($book_id, $id, $client_id, &$guest_error_list){
				$rows = $reader->all()->toArray();
				for($i = 0; $i < count($rows); $i++ )
				{
					$guest_ids = [];
					foreach( $rows[$i] as $data )
					{	
						$data['client_id'] = $client_id;
						// save marketing guest from excel				
						$guest = MarketingGuest::createWithCheck($data);

						if( $guest->error_type > 0 )	// when create, error is occur
							$guest_error_list[] = $guest;								
						
						if( $guest->id < 1 )
							continue;

						// add guest to address book
						$pivot = AddressbookMember::where('book_id', $book_id)->where('guest_id', $guest->id)->first();
						if( empty($pivot) )
							$pivot = new AddressbookMember();

						$pivot->book_id = $book_id;
						$pivot->guest_id = $guest->id;
						$pivot->save();
					}
				}							
			});		
		}

		$campaign = CampaignList::find($id);	
		if( $periodic == 'Immediately' )
			$this->sendPromotion($campaign);

		// get book list 
		$book_list = DB::table('marketing_addressbook')
						->where('client_id', $client_id)
						->get();

		// get book list 
		$guest_list = DB::table('marketing_guest')
						->where('client_id', $client_id)
						->get();	

		$ret = array();
		$ret['code'] = 200;
		$ret['book_list'] = $book_list;
		$ret['user_list'] = $guest_list;
		$ret['guest_error_list'] = $guest_error_list;
		$ret['book_id'] = $book_id;
		$ret['book_tags'] = CampaignList::getAddressbookList($id);

		return Response::json($ret);
 	}

 	public function changeGuest(Request $request) {
 		$campaign_id = $request->get('id', 0);
 		$book_id = $request->get('book_id', 0); 
 		$error_list = $request->get('error_list', []); 

 		foreach($error_list as $row) {
 			if( $row['modify_flag'] == 1 )
 			{
 				$new_guest = $row['new_guest'];
 				$guest = MarketingGuest::find($row['guest_id']);
 				if( !empty($guest) && !empty($new_guest) )
 				{
 					$guest->first_name = $new_guest['first_name'];
	 				$guest->last_name = $new_guest['last_name'];
	 				$guest->birthday = $new_guest['birthday']['date'];
	 				$guest->anniversary = $new_guest['anniversary']['date'];
	 				$guest->email = $new_guest['email'];
	 				$guest->mobile = $new_guest['mobile'];	

	 				$guest->save();
 				}
 			}
 		}

 		$ret = array();
		$ret['code'] = 200;
		$ret['error_list'] = $error_list;
		$ret['book_id'] = $book_id;

		return Response::json($ret);
 	}

 	public function getReceipientList(Request $request) 
 	{
 		$id = $request->get('id', 0);

 		$campaign = CampaignList::find($id);

 		$ret = array();

 		if( empty($campaign) )
 		{
 			$ret['code'] = 300;
 			return Response::json($ret);
 		}

 		$guest_list = $campaign->getGusetList();

 		$ret['code'] = 200;
 		$ret['datalist'] = $guest_list;

 		return Response::json($ret);
 	}

 	public function rejectPromotion(Request $request) {
 		$access_token = $request->get('access_token', '');
 		$guest_id = $request->get('guest_id', 0);

 		$guest = MarketingGuest::find($guest_id);
 		if( empty($guest) )
 		{
 			echo "Invalid Request";
 			return;
 		}

 		if( $guest->access_token != $access_token )
 		{
 			echo "Invalid Token";
 			return;
 		}

 		$guest->optout = 1;
 		$guest->access_token = '';

 		$guest->save();

 		echo 'Your reject request is accepted';
 	}  

 	// schedule check
 	function scheduleCheckCampaignList() { 		
 		$this->checkBirthdayAnniversaryCamapignList();
 		$this->checkHolidayCamapignList();
 		$this->checkPeriodicCamapignList();
 	}

 	private function checkBirthdayAnniversaryCamapignList() {
 		date_default_timezone_set(config('app.timezone'));
 		$cur_date = date("Y-m-d");
		$cur_time = date("H:i:00");
		$month_date = date("m-d");

		$query = DB::table('marketing_campaign_list as cl')
			->where('cl.active', 'true')				
			->where('cl.start_date', '<=', $cur_date)
			->where('cl.end_date', '>=', $cur_date)
			->where(function ($subquery) {	// email flag
					$subquery->where('cl.sms_flag', 'true')
							->orWhere('cl.email_flag', 'true');
				});

		$query->where('cl.trigger_at', $cur_time);

		$query->whereIn('cl.type', array('Birthday', 'Anniversary'));	

		$campaign_list = $query->get();

		// echo json_encode($campaign_list);

		foreach($campaign_list as $key => $row) 
		{
			if( $row->send_to == ADDRESS_BOOK)	// address book
			{
				$before_date = $row->before_date;

				// check campaign -> address book -> guest
				$query = DB::table('marketing_guest as gu')
					->join('marketing_addressbook_member as am', 'gu.id', '=', 'am.guest_id')
					// ->join('marketing_addressbook as ab', 'am.book_id', '=', 'ab.id')
					->join('marketing_campign_addressbook_member as cam', 'am.book_id', '=', 'cam.book_id')
					->where('cam.campaign_id', $row->id);

				if( $row->type == 'Birthday' )
					$where_sql = sprintf("SUBDATE(gu.birthday, INTERVAL %d DAY)", $before_date);
			
				if( $row->type == 'Anniversary' )
					$where_sql = sprintf("SUBDATE(gu.anniversary, INTERVAL %d DAY)", $before_date);
			
				$where_sql = "DATE_FORMAT(" . $where_sql . ", '%m-%d')";
				$where_sql .= sprintf(" = '%s'", $month_date);
				$query->whereRaw($where_sql);

				$guest_list = $query->groupBy('gu.id')
					->select(DB::raw('gu.*'))
					->get();

				foreach($guest_list as $row1) 
				{
					$this->sendSMS($row, $row1);
					$this->sendEmail($row, $row1);					
				}
			}
		}
 	}

 	private function checkHolidayCamapignList() {
 		date_default_timezone_set(config('app.timezone'));
 		$cur_date = date("Y-m-d");
		$cur_time = date("H:i:00");
		$month_date = date("m-d");

		$query = DB::table('marketing_campaign_list as cl')
			->where('cl.active', 'true')				
			->where('cl.start_date', '<=', $cur_date)
			->where('cl.end_date', '>=', $cur_date)
			->where(function ($subquery) {	// email flag
					$subquery->where('cl.sms_flag', 'true')
							->orWhere('cl.email_flag', 'true');
				});

		$query->where('cl.trigger_at', $cur_time);

		$query->where('cl.type', 'Holiday');	

		// Pre Deliver sql
		$where_sql = "SUBDATE(cl.holiday, INTERVAL cl.before_date DAY)";

		$where_sql = "DATE_FORMAT(" . $where_sql . ", '%m-%d')";
		$where_sql .= sprintf(" = '%s'", $month_date);
		$query->whereRaw($where_sql);

		$campaign_list = $query->get();

		foreach($campaign_list as $key => $row) 
		{
			$this->sendPromotion($row);
		}
 	}

 	private function checkPeriodicCamapignList() {
 		date_default_timezone_set(config('app.timezone'));
 		$cur_date = date("Y-m-d");
		$cur_time = date("H:i:00");
		$month_date = date("m-d");
		$cur_datetime = date("Y-m-d H:i:00");

		$query = DB::table('marketing_campaign_list as cl')
			->where('cl.active', 'true')				
			->where('cl.start_date', '<=', $cur_date)
			->where('cl.end_date', '>=', $cur_date)
			->where(function ($subquery) {	// email flag
					$subquery->where('cl.sms_flag', 'true')
							->orWhere('cl.email_flag', 'true');
				});

		$query->where('cl.trigger_datetime', '<=', $cur_datetime);

		$query->where('cl.type', 'Other')->where('cl.periodic', 'Periodic');	

		$campaign_list = $query->get();

		foreach($campaign_list as $key => $row) 
		{
			$this->sendPromotion($row);
			$campaign = CampaignList::find($row->id);
			$campaign->calcTriggerDateTime(true);
			$campaign->save();
		}
 	}

 	function sendPromotion($campaign) {
		if( $campaign->send_to == ADDRESS_BOOK)	// address book
		{
			// check campaign -> address book -> guest
			$query = DB::table('marketing_guest as gu')
				->join('marketing_addressbook_member as am', 'gu.id', '=', 'am.guest_id')
				->join('marketing_campign_addressbook_member as cam', 'am.book_id', '=', 'cam.book_id')
				->where('cam.campaign_id', $campaign->id);

			$guest_list = $query->groupBy('gu.id')
				->select(DB::raw('gu.*'))
				->get();

			foreach($guest_list as $row1) 
			{
				$this->sendSMS($campaign, $row1);
				$this->sendEmail($campaign, $row1);					
			}
		}

		if( $campaign->send_to == MANUALLY)	// manually
		{
			// check campaign -> guest
			$query = DB::table('marketing_guest as gu')
				->join('marketing_campign_guest_member as cgm', 'gu.id', '=', 'cgm.guest_id')
				->where('cgm.campaign_id', $campaign->id);

			$guest_list = $query->groupBy('gu.id')
				->select(DB::raw('gu.*'))
				->get();

			foreach($guest_list as $row1) 
			{
				$this->sendSMS($campaign, $row1);
				$this->sendEmail($campaign, $row1);					
			}
		}
	}

 	private function sendSMS($campaign, $guest) {
 		if( $campaign->sms_flag == false )
 			return;

 		if( $guest->optout == 1 ) // already rejected
 			return;
 		
 		$setting = PropertySetting::getSMSSetting($campaign->property_id, 'promotion_');

 		$message = array();
		$message['type'] = 'sms';
		$message['to'] = $guest->mobile;
		$message['subject'] = 'Hotlync Promotion';
		$message['setting'] = $setting;

		$sms_content = str_replace("{{guest_name}}", $guest->first_name . ' ' . $guest->last_name, $campaign->sms_content);

		if( $campaign->reject_flag == true )	// if have reject option
		{			
			$save_guest = MarketingGuest::updateToken($guest->id);
			$access_token = $save_guest->access_token;

			$reject_setting = PropertySetting::getPromotionRejectSetting($campaign->property_id);

			// append reject url
			$sms_content .= sprintf($reject_setting['promotion_sms_reject_content'], $access_token, $guest->id);
		}

		$message['content'] = $sms_content;

		
		// echo json_encode($message);
		
		Redis::publish('notify', json_encode($message));
 	}

 	private function sendEmail($campaign, $guest) {
 		if( $campaign->email_flag == false )
 			return;

 		if( $guest->optout == 1 ) // already rejected
 			return;

 		$smtp = PropertySetting::getMailSetting($campaign->property_id, 'promotion_');

 		$message = array();
		$message['type'] = 'email';
		$message['to'] = $guest->email;
		$message['subject'] = !empty($campaign->email_subject) ? $campaign->email_subject : 'Hotlync Promotion';
		$message['title'] = 'Hotlync Promotion';
		$email_content = str_replace("{{guest_name}}", $guest->first_name . ' ' . $guest->last_name, $campaign->email_content);

		if( $campaign->reject_flag == true )	// if have reject option
		{			
			$save_guest = MarketingGuest::updateToken($guest->id);
			$access_token = $save_guest->access_token;

			$reject_setting = PropertySetting::getPromotionRejectSetting($campaign->property_id);

			// append reject url
			$email_content .= sprintf($reject_setting['promotion_email_reject_content'], $access_token, $guest->id);
		}	

		$message['content'] = $email_content;
		$message['smtp'] = $smtp;

		// echo json_encode($message);
		
		Redis::publish('notify', json_encode($message));
 	}
}