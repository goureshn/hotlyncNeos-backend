<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

use DB;

class GuestProfile extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_guest_profile';
	public 		$timestamps = false;
}