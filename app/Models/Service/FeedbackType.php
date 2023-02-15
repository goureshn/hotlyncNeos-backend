<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class FeedbackType extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_feedback_type';
	public 		$timestamps = false;
}