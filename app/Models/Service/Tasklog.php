<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
class Tasklog extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_task_log';
	public 		$timestamps = false;


	static function checkUserTask($task_id)
    {
        $data = DB::table('services_task_log as stl')
					->where('stl.task_id', $task_id)
					->select(DB::raw('stl.user_id'))
					->orderBy('stl.id', 'desc')
					->first();

		if( empty($data) )
			return 0;

		return $data->user_id;
    }
}
