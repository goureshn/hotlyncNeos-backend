<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class CarrierCharges extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_carrier_charges';
	public 		$timestamps = false;
	
	protected $typelist = [
        '1' => 'Minute',
        '2' => 'Per Call',
		'3' => 'Pulse',
		'4' => 'Second',
		'5' => '30 Seconds'		
    ];
	
	public function getTypeList()
    {
        return $this->typelist;
    }
	
	public function getChargeType()
	{
		$id = array_search($this->method, $this->typelist);
		
		return $id;		
	}
}