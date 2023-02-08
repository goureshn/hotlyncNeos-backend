<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class PermissionGroup extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_perm_group';
	public 		$timestamps = false;
	
	public function property()
    {
		return $this->belongsTo(Property::class, 'property_id');
    }	
}