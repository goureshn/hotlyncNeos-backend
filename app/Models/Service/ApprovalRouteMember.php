<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;

class ApprovalRouteMember extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'services_approval_route_members';
    public 		$timestamps = false;

    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function approvalroute()
    {
        return $this->belongsTo(ApprovalRoute::class,'approval_route_id');
    }

    public function jobrole()
    {
        return $this->belongsTo(JobRole::class,'job_role_id');
    }
}
