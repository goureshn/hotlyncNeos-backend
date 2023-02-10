<?php

namespace App\Models\Call;

use Illuminate\Database\Eloquent\Model;

class PhonebookList extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_phonebook_list';
	public 		$timestamps = false;
}