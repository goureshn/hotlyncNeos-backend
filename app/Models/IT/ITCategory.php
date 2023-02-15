<?php

namespace App\Models\IT;

use Illuminate\Database\Eloquent\Model;

use DB;

class ITCategory extends Model {
	protected 	$guarded = [];
	protected 	$table = 'services_it_category';
	public 		$timestamps = false;

	public static function getCategory($category)
	{
		return ITCategory::where('category', $category)
			->first();
	}
}