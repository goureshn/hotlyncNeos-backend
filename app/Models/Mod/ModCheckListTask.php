<?php

namespace App\Models\Mod;

use Illuminate\Database\Eloquent\Model;
use DB;

class ModCheckListTask extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'mod_checklist_task';
    public 		$timestamps = true;

    static function getChecklistItem($checklist_id)
    {
        return DB::table('mod_checklist_pivot as a')                            
                            ->join('mod_checklist_item as b', 'a.item_id', '=', 'b.id')
                            ->leftJoin('mod_checklist_category as c', 'b.category_id', '=', 'c.id')
                            ->where('a.checklist_id', $checklist_id)
                            ->select(DB::raw('b.*, c.name as category_name'))
                            ->get();
    }
}