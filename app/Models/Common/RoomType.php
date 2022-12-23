<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class RoomType extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_room_type';
	public 		$timestamps = false;
	
	public function building()
    {
		return $this->belongsTo(Building::class, 'bldg_id');
    }		
}