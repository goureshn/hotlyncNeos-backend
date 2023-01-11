<?php

namespace App\Models\Eng;

use Illuminate\Database\Eloquent\Model;

class EquipmentFile extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'eng_equip_file';
    public 		$timestamps = false;

    public function equip_group()
    {
        return $this->belongsTo(EquipmentList::class, 'equip_id');
    }
}