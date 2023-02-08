<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class JobRole extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'common_job_role';
    public 		$timestamps = false;


    public function department()
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    public function pmgroup()
    {
        return $this->belongsTo(PermissionGroup::class, 'permission_group_id');
    }
}