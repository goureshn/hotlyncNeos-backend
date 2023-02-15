<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Models\Service\ComplaintSublist;


class ComplaintSublistLocEscalation extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'services_complaint_sublist_loc_escalation';
    public 		$timestamps = false;    
}