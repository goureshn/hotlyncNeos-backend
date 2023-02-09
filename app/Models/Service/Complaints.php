<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class Complaints extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'services_complaints';
    public 		$timestamps = false;

    public function type()
    {
        return $this->belongsTo(ComplaintType::class, 'type_id');
    }

}