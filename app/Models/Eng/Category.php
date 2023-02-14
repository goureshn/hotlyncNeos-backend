<?php

namespace App\Models\Eng;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'eng_request_category';
    public 		$timestamps = false;
}