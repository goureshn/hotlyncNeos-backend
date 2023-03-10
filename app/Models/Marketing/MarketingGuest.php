<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use App\Modules\UUID;

class MarketingGuest extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'marketing_guest';
	public 		$timestamps = true;

	public static function createWithCheck($data) {
		$mobile = $data['mobile'];

		$mobile = preg_replace("/[^0-9]/", "", $mobile);
		$mobile = ltrim($mobile, '0');

		$data['mobile'] = $mobile;

		if( strlen($mobile) < 11 )
		{
			$guest = new MarketingGuest();
			$guest->id = 0;
			$guest->new_guest = $data;
			$guest->error_type = 1;		// minimum length
			return $guest;	
		}

		$guest = MarketingGuest::where('mobile', $mobile)->first();
		if( !empty($guest) )
		{
			if( $data['first_name'] != $guest->first_name || $data['last_name'] != $guest->last_name )
			{
				$guest->new_guest = $data;
				$guest->error_type = 2;		// duplicated mobile number
				return $guest;
			}
		}
		else
		{
			$guest = MarketingGuest::create($data);			
		}

		$guest->error_type = 0; // no error

		return $guest;
	}

	public static function updateToken($id) {
		$guest = MarketingGuest::find($id);
		if( empty($guest) )
			return;

		$access_token = $guest->access_token;
		if( empty($access_token) )
		{
			$uuid = new UUID();
			$access_token = $uuid->uuid;

			$guest->access_token = $access_token;			
			$guest->save();
		}

		return $guest;
	}
}