<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

use DB;

class CampaignList extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'marketing_campaign_list';
	public 		$timestamps = true;

	public function calcTriggerDateTime($next_flag) {
		date_default_timezone_set(config('app.timezone'));
		$cur_time = date("Y-m-d H:i:s");

		switch($this->type) {
			case 'Holiday':
				$before_date = date("Y-m-d", strtotime("-" . $this->before_date . " days", strtotime($this->holiday)));
				if( $next_flag )
					$before_date = date("Y-m-d", strtotime("1 years", strtotime($before_date)));

				$this->trigger_datetime = $before_date . ' ' . $this->trigger_at . ':00';
				break;
			case 'Other':
				if( $this->periodic == 'Periodic' )
				{
					if( $next_flag )
						$next_date = date("Y-m-d", strtotime($this->every_date . " days"));
					else
						$next_date = $this->start_date;

					$this->trigger_datetime = $next_date . ' ' . $this->trigger_at . ':00';	
				}
				
				break;					
		}
	}

	public function getGusetList() {
		$guest_list = [];
		if( $this->send_to == ADDRESS_BOOK)	// address book
		{
			// check campaign -> address book -> guest
			$query = DB::table('marketing_guest as gu')
				->join('marketing_addressbook_member as am', 'gu.id', '=', 'am.guest_id')
				->join('marketing_campign_addressbook_member as cam', 'am.book_id', '=', 'cam.book_id')
				->where('cam.campaign_id', $this->id);

			$guest_list = $query->groupBy('gu.id')
				->select(DB::raw('gu.*'))
				->get();
		}

		if( $this->send_to == MANUALLY)	// manually
		{
			// check campaign -> guest
			$query = DB::table('marketing_guest as gu')
				->join('marketing_campign_guest_member as cgm', 'gu.id', '=', 'cgm.guest_id')
				->where('cgm.campaign_id', $this->id);

			$guest_list = $query->groupBy('gu.id')
				->select(DB::raw('gu.*'))
				->get();
		}

		return $guest_list;
	}

	public static function getAddressbookList($id) {
		$list = DB::table('marketing_addressbook as ab')
			->join('marketing_campign_addressbook_member as cam', 'ab.id', '=', 'cam.book_id')
			->where('cam.campaign_id', $id)
			->select(DB::raw('ab.*'))
			->groupBy('ab.id')
			->get();

		return $list;	
	}

	public static function getGuestList($id) {
		$list = DB::table('marketing_guest as mg')
			->join('marketing_campign_guest_member as cgm', 'mg.id', '=', 'cgm.guest_id')
			->where('cgm.campaign_id', $id)
			->select(DB::raw('mg.*, CONCAT_WS(" ", mg.first_name, mg.last_name) as wholename'))
			->groupBy('mg.id')
			->get();

		return $list;	
	}

}