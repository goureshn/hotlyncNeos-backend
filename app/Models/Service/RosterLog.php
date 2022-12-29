<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
class RosterLog extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_roster_log';
    public 		$timestamps = false;
}