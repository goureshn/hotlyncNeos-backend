<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class ComplaintCategory extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_category';
	public 		$timestamps = false;
}