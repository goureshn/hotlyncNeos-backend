<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class FeedbackSource extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_feedback_source';
	public 		$timestamps = false;
}