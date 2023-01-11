<?php

namespace App\Models\Eng;

use Illuminate\Database\Eloquent\Model;

class EquipmentList extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'eng_equip_list';
    public 		$timestamps = false;

    public function equip_group()
    {
        dd("dd from App/Models/End/EquipmentList");
        return $this->belongsTo('App\Models\Eng\Equipment', 'equip_group_id');
    }
}