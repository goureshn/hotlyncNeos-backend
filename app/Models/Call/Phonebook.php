<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class Phonebook extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'phonebook';
	public 		$timestamps = false;
}