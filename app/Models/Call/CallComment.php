<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class CallComment extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'call_comments';
	public 		$timestamps = false;
}