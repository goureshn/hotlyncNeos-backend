<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

use DB;

class GuestAdvancedDetail extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'common_guest_advanced_detail';

	public function getTableColumns() {
        $db = DB::connection()->getPdo();
        $rs = $db->query("SELECT * FROM common_guest_advanced_detail LIMIT 0");
        for ($i = 0; $i < $rs->columnCount(); $i++) {
            $col = $rs->getColumnMeta($i);
            $columns[] = $col['name'];
        }

        return $columns;
    }
}