<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class Comments extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_comments';
	public 		$timestamps = false;
}