<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'common_module';
    public 		$timestamps = false;
}