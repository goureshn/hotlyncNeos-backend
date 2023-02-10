<?php

namespace App\Models\Common;
use DateInterval;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Modules\Functions;

class LinenChange extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_room_linen_chng_status';
	public 		$timestamps = false;

	static function getLinenType($room) {

		$data = DB::table('common_room as cr')
		->join('common_room_type as rt', 'cr.type_id', '=', 'rt.id')
		->where('cr.id','=',$room->id)->select(DB::raw('rt.linen_change, rt.id as room_type_id'))->first();



		return $data;
	}
}