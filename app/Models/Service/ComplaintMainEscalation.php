<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Models\Service\ComplaintSublist;


class ComplaintMainEscalation extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'services_complaint_main_escalation';
    public 		$timestamps = false;    
}