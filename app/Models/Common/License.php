<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'common_property_license';
    public 		$timestamps = false;
}