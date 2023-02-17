<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Models\Service\ComplaintSublist;


class ComplaintDivisionEscalation extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'services_complaint_division_escalation';
    public 		$timestamps = false;    
}