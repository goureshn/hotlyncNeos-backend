<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class AdminChargeMap extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_admin_charge_map';
	public 		$timestamps = false;
	
	public function carriergroup()
    {
		return $this->belongsTo(CarrierGroup::class, 'carrier_groups');
    }	
	public function timeslabgroup()
    {
		return $this->belongsTo(TimeSlab::class, 'time_slab_group');
    }	
	public function carriercharge()
    {
		return $this->belongsTo(CarrierCharges::class, 'carrier_charges');
    }		
	public function call_allowance()
    {
		return $this->belongsTo(Allowance::class, 'call_allowance');
    }
}