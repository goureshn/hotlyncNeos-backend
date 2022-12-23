<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class TimeSlab extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_time_slab';
	public 		$timestamps = false;
	
	protected $daylist = [
        '1' => 'Sunday',
        '2' => 'Monday',
		'3' => 'Tuesday',
		'4' => 'Wednesday',
		'5' => 'Thursday',
		'6' => 'Friday',
		'7' => 'Saturday',
    ];
	
	public function getDayList()
	{
		return $this->daylist;
	}
	
	public function getDays()
	{
		$daydata = array();
		$data = explode(",", $this->days_of_week);
		foreach($data as $value)
		{
			$key = array_search($value, $this->daylist);
			array_push($daydata,$key);		
		}		
		
		return $daydata;
	}
	
	public function setDays($days)
	{
		$value = "";
		$i = 0;
		
		if( !empty($days) )
		{
			foreach( $days as $key => $id )
			{
				$value = $value . $this->daylist[$id];			
				$value = $value . ',';
				$i++;
			}
		}
		
		$this->days_of_week = $value;
		$this->save();
	}
	
}