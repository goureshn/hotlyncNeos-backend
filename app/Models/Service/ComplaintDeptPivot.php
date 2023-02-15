<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class ComplaintDeptPivot extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'services_complaint_dept_pivot';
    public 		$timestamps = false;

    public function complaint()
    {
        return $this->belongsTo(Complaints::class, 'complaint_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class,'dept_id');
    }
    
}
