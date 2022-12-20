<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CommonUserTransaction extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'common_user_transaction';
    public 		$timestamps = false;
    protected $hidden = [
        'password'
    ];


    public static function saveTransaction($user_id, $action, $detail) {
        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");

        $trans = DB::table('common_user_transaction')
            ->insert(
                ['user_id' => $user_id, 'action' => $action, 'detail' => $detail, 'created_at' =>$cur_time]
            );
    }
}