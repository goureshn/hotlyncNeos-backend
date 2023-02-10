<?php

namespace App\Models\Common;
use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Modules\Functions;

class AlexaLogs extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_alexa_logs';
	public 		$timestamps = false;
}
