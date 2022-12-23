<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_user_group';
	public 		$timestamps = false;
	
	public function property()
    {
		return $this->belongsTo('App\Models\Common\Property', 'property_id');
    }	
	
	public function pmgroup()
    {
		return $this->belongsTo('App\Models\Common\PermissionGroup', 'perm_group');
    }
	
	protected $typelist = [
        '1' => 'IVR',
        '2' => 'User',
		'3' => 'Dispatcher',
		'4' => 'Reports',
		'5' => 'Manager',
		'6' => 'Supervisor',
    ];
	
	public function getTypeList()
    {
        return $this->typelist;
    }
}