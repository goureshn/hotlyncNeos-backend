<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class EscalationGroup extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_escalation_group';
	public 		$timestamps = false;
}