<?php

namespace App\Models\Eng;

use Illuminate\Database\Eloquent\Model;
use DB;

class EquipmentCheckListCategory extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'eng_checklist_category';
    public 		$timestamps = false;

    static function getCategoryList($checklist_id)
    {
        $list = DB::table('eng_checklist_category as c')                            
                            ->where('c.checklist_id', $checklist_id)
                            ->select(DB::raw('c.*'))
                            ->orderBy('c.order_id')
                            ->orderBy('c.name')
                            ->get();

        return $list;                            
    }
}