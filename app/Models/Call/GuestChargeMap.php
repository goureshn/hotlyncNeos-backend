<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class GuestChargeMap extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_guest_charge_map';
	public 		$timestamps = false;
}