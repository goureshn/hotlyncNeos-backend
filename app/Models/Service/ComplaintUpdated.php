<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class ComplaintUpdated extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_updated';
	public 		$timestamps = false;

	public static function viewByUser($complaint_id, $user_id) {
		$model = ComplaintUpdated::where('complaint_id', $complaint_id)
			->where('user_id', $user_id)
			->first();

		if( empty($model) )
		{
			$model = new ComplaintUpdated();
			$model->complaint_id = $complaint_id;
			$model->user_id = $user_id;

			$model->save();
		}
	}

	public static function modifyByUser($complaint_id, $user_id) {
		ComplaintUpdated::where('complaint_id', $complaint_id)			
			->delete();

		$model = new ComplaintUpdated();
		$model->complaint_id = $complaint_id;
		$model->user_id = $user_id;

		$model->save();
	}

}