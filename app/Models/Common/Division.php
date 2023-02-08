<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use App\Models\Service\Location;
use App\Models\Service\LocationType;
use DB;

class Division extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_division';
	public 		$timestamps = false;
}