<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class HotelCharges extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_hotel_charges';
	public 		$timestamps = false;
	
	protected $typelist = [
        '1' => 'Duration',
        '2' => 'Per Call',
		'3' => 'Percentage',
		'4' => 'Pulse'		
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