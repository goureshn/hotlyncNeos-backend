<?php

namespace App\Models\IT;

use Illuminate\Database\Eloquent\Model;

use DB;

class ITType extends Model {
	protected 	$guarded = [];
	protected 	$table = 'services_it_type';
	public 		$timestamps = false;

	public static function getType($type)
	{
		return ITType::where('type', $type)
			->first();
	}
}