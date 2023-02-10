<?php

namespace App\Models\Mod;

use Illuminate\Database\Eloquent\Model;
use DB;

class ModCheckList extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'mod_checklist';
    public 		$timestamps = true;

    static function getChecklistItem($checklist_id)
    {
        return DB::table('mod_checklist_pivot as a')
                            ->join('mod_checklist_item as b', 'a.item_id', '=', 'b.id')
                            ->leftJoin('mod_checklist_category as c', 'b.category_id', '=', 'c.id')
                            ->where('a.checklist_id', $checklist_id)
                            ->select(DB::raw('b.*, c.name as category_name, c.order_id'))
                            ->orderBy('c.order_id')
                            ->orderBy('b.created_at')
                            ->get();
    }

    static function getCategoryList($checklist_id)
    {
        $list = DB::table('mod_checklist_category as c')
                            ->where('c.checklist_id', $checklist_id)
                            ->select(DB::raw('c.*'))
                            ->orderBy('c.order_id')
                            ->orderBy('c.name')
                            ->get();

        return $list;
    }

    static function getNextDate($model, $start_time)
    {
        $next_date = date('Y-m-d H:i:s', strtotime("$model->frequency $model->freq_unit", strtotime($start_time)));
        switch($model->freq_unit)
        {
            case 'Hours':
                $next_date = date('Y-m-d H:00:00', strtotime($next_date));
                break;
            case 'Days':
                $next_date = date('Y-m-d 00:00:00', strtotime($next_date));
                break;
        }

        return $next_date;
    }

    static function getChecklistResult($task_id)
    {
        return DB::table('mod_checklist_result as a')
                            ->join('mod_checklist_item as b', 'a.item_id', '=', 'b.id')
                            ->leftJoin('mod_checklist_category as c', 'b.category_id', '=', 'c.id')
                            ->where('a.task_id', $task_id)
                            ->select(DB::raw('a.*, c.name as category_name, b.category_id as real_category_id, c.order_id,
                                    b.name as item_name, b.type as item_type'))
                            // ->orderBy('a.check_flag')
                            ->orderBy('c.order_id')
                            ->orderBy('b.created_at')
                            ->get();

    }

    static function getChecklistOther($task_id)
    {
        return DB::table('mod_checklist_result as a')
                            ->leftJoin('mod_checklist_category as c', 'a.category_id', '=', 'c.id')
                            ->where('a.task_id', $task_id)
                            ->where('a.item_id', 0)
                            ->select(DB::raw('a.*, c.name as category_name, c.order_id,
                                    a.type as item_type'))
                            // ->orderBy('a.check_flag')
                            ->orderBy('c.order_id')
                            ->orderBy('a.created_at')
                            ->get();

    }
}
