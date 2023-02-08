<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class MailServer extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_mailserver';
	public 		$timestamps = false;
}