<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class UserGroupMember extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_user_group_members';
	public 		$timestamps = false;
}