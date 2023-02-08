<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class PermissionModule extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'common_page_route';
    public 		$timestamps = false;
}