<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class Permissions extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_permission_members';
	public 		$timestamps = false;
}