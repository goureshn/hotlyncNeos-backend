<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use DB;

class Building extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_building';
	public 		$timestamps = false;
}