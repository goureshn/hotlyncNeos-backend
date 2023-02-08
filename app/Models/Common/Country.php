<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_country';
	public 		$timestamps = false;
}