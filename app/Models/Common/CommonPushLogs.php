<?php
namespace App\Models\Common;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Modules\Functions;
 class CommonPushLogs extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_push_logs';
	public 		$timestamps = false;
	
}