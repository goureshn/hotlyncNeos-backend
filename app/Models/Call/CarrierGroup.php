<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class CarrierGroup extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_carrier_groups';
	public 		$timestamps = false;
	
	
	protected $typelist = [
        '1' => 'Local',
        '2' => 'International',
		'3' => 'National',
		'4' => 'Mobile',
		'5' => 'Toll Free'
    ];
	
	public function getTypeList()
    {
        return $this->typelist;
    }
	
	public function getCallType()
	{
		$calltype_id = array_search($this->call_type, $this->typelist);
		
		return $calltype_id;		
	}
	
	
}