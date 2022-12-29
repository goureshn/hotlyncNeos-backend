<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
class Test extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_test_log';
    public 		$timestamps = false;
}