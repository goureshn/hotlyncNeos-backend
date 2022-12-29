<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

use DB;

class GuestLogin extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_guest_login';
	public 		$timestamps = false;
}