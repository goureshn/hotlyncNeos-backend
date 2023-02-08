<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;

use App\Models\Mod\ModCheckList;
use App\Models\Mod\ModCheckListTask;
use App\Models\Common\CommonUser;
use App\Models\Common\PropertySetting;
use App\Models\Service\Location;
use App\Modules\Functions;
use Redis;

use DB;
use Illuminate\Http\Request;
use Response;

use Log;

define("PENDING", 'Pending');
define("IN_PROGRESS", 'In Progress');
define("DONE", 'Done');

class ModController extends Controller
{
    public function getCheckList(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $property_id = CommonUser::getPropertyID($user_id);
        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $searchtext = $request->get('searchtext', '');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');

        $query = DB::table('mod_checklist as mc')
                    ->where('mc.property_id', $property_id);


        if($searchtext != '') {
            $where = sprintf("mc.name like '%%%s%%'",
                $searchtext
            );

            $query->whereRaw($where);
        }

        $data_query = clone $query;
        $data_list = $data_query
                        ->orderBy($orderby, $sort)
                        ->select(DB::raw("mc.*"));
        if( $pageSize > 0 )
            $data_query->skip($skip)->take($pageSize);

        $data_list = $data_query
                        ->get();

        foreach($data_list as $row)
        {
            if( !empty($row->job_role_ids) )
            {
                $row->job_role_tags = DB::table('common_job_role')
                    ->whereRaw("id IN ($row->job_role_ids)")
                    ->get();
                
                $row->assigner = implode(",", array_map(function($item) {
                    return $item->job_role;
                }, $row->job_role_tags->toArray()));
            }
            else
            {
                $row->job_role_tags = [];
                $row->assigner = '';
            }

            if( !empty($row->user_group_ids) )
            {
                $row->user_group_tags = DB::table('common_job_role')
                    ->whereRaw("id IN ($row->user_group_ids)")
                    ->get();

                $row->report_list = implode(",", array_map(function($item) {
                    return $item->job_role;
                }, $row->user_group_tags->toArray()));
            }
            else
            {
                $row->user_group_tags = [];
                $row->report_list = '';
            }

            if( !empty($row->notify_group_ids) )
            {
                $row->notify_group_tags = DB::table('common_job_role')
                    ->whereRaw("id IN ($row->notify_group_ids)")
                    ->get();

                $row->notify_list = implode(",", array_map(function($item) {
                    return $item->job_role;
                }, $row->notify_group_tags->toArray()));
            }
            else
            {
                $row->notify_group_tags = [];
                $row->notify_list = '';
            }

            if( !empty($row->location_ids) )
            {
                $row->location_tags = DB::table('services_location')
                    ->whereRaw("id IN ($row->location_ids)")
                    ->get();

                $row->location_list = implode(",", array_map(function($item) {
                    return $item->name;
                }, $row->location_tags->toArray()));
            }
            else
            {
                $row->location_tags = [];
                $row->location_list = '';
            }
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    public function getCheckListForMobile(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $property_id = CommonUser::getPropertyID($user_id);

        // get job role od
        $user = CommonUser::find($user_id);
        $job_role_id = $user->job_role_id;

        $query = DB::table('mod_checklist as mc')
                    ->where('mc.property_id', $property_id)
                    ->whereRaw('FIND_IN_SET('.$job_role_id.', mc.job_role_ids)');

        $data_query = clone $query;
        $data_list = $data_query
                        ->select(DB::raw("mc.*"))
                        ->get();

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    private function sendWebpushUpdate($property_id, $task_id) {
        $message = [];
        $message['type'] = 'webpush';
        $message['to'] = $property_id;
        $content = [
            'type' => 'app.checklisttask.updated',
            'task_id' => $task_id
        ];

        $message['content'] = $content;

        Redis::publish('notify', json_encode($message));
    }

    private function sendWebpushUpdateStatus($property_id, $task_id) {
        $message = [];
        $message['type'] = 'webpush';
        $message['to'] = $property_id;
        $content = [
            'type' => 'app.checklisttask.updatestatus',
            'task_id' => $task_id
        ];

        $message['content'] = $content;

        Redis::publish('notify', json_encode($message));
    }

    private function sendWebpush($property_id) {
        $message = [];
        $message['type'] = 'webpush';
        $message['to'] = $property_id;
        $content = [
            'type' => 'app.checklisttask.created'
        ];

        $message['content'] = $content;

        Redis::publish('notify', json_encode($message));
    }

    public function createChecklistTaskFromMobile(Request $request)
    {
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);

        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d H:i:s");

        $model = ModCheckList::find($id);

        $model->freq_unit = 'None';

        $this->createChecklistTask($model, $cur_date, $user_id, 0);

        $property_id = $model->property_id;

        $this->sendWebpush($property_id);

        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function createChecklistTaskFromWeb(Request $request) {
        $id = $request->get('id', 0);
        $user_id = $request->get('user_id', 0);
        $location_id = $request->get('location_id', 0);

        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d H:i:s");

        $model = ModCheckList::find($id);

        $model->freq_unit = 'None';

        $this->createChecklistTask($model, $cur_date, $user_id, 0, $location_id);

        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

    private function createChecklistTask($checklist, $start_time, $user_id, $auto_flag = 1, $location_id = 0)
    {
        if( empty($checklist) )
            return;

        switch($checklist->freq_unit)
        {
            case 'Hours':
                $start_time = date('Y-m-d H:00:00', strtotime($start_time));
                break;
            case 'Days':
                $start_time = date('Y-m-d 00:00:00', strtotime($start_time));
                break;
        }


        // create check list result with pending
        if( $checklist->location_mode != 'Admin' )
        {
            $task = new ModCheckListTask();

            $task->checklist_id = $checklist->id;
            $task->status = PENDING;
            $task->start_date = $start_time;
            $task->end_date = '';
            $task->completed_by = 0;
            $task->location_id = $location_id;
            $task->auto_flag = $auto_flag;

            $task->save();

            $this->sendNotificationForChecklist($task, $user_id);
        }
        else
        {
            $location_ids = explode(",", $checklist->location_ids);

            foreach($location_ids as $loc_id)
            {
                if( !empty($loc_id) && $loc_id > 0 )
                {
                    $task = new ModCheckListTask();

                    $task->checklist_id = $checklist->id;
                    $task->status = PENDING;
                    $task->start_date = $start_time;
                    $task->end_date = '';
                    $task->completed_by = 0;
                    $task->location_id = $loc_id;
                    $task->auto_flag = $auto_flag;

                    $task->save();

                    $this->sendNotificationForChecklist($task, $user_id);
                }
            }
        }

        $this->generateChecklistResult($task);

    }

    public function createCheckList(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d H:i:s");

        $user_id = $request->get('user_id', 0);
        $property_id = CommonUser::getPropertyID($user_id);

        $id = $request->get('id', 0);
        $name = $request->get('name', '');
        $job_role_ids = $request->get('job_role_ids', '');
        $user_group_ids = $request->get('user_group_ids', '');
        $notify_group_ids = $request->get('notify_group_ids', '');
        $location_mode = $request->get('location_mode', 'None');
        $location_ids = $request->get('location_ids', '');
        $report_completor = $request->get('report_completor', 0);

        $frequency = $request->get('frequency', 0);
        $freq_unit = $request->get('freq_unit', 'Days');
        $start_date = $request->get('start_date', '');

        $model = ModCheckList::find($id);
        $new_flag = empty($model) ? true : false;

        if( empty($model))
            $model = new ModCheckList();

        $model->property_id = $property_id;
        $model->submitter_id = $user_id;
        $model->name = $name;
        $model->job_role_ids = $job_role_ids;
        $model->user_group_ids = $user_group_ids;
        $model->notify_group_ids = $notify_group_ids;
        $model->report_completor = $report_completor;
        $model->frequency = $frequency;
        $model->freq_unit = $freq_unit;
        $model->start_date = $start_date;
        $model->next_date = ModCheckList::getNextDate($model, $cur_date);

        $model->location_mode = $location_mode;
        if( $location_mode == 'Admin' )
            $model->location_ids = $location_ids;
        else
            $model->location_ids = '';

        $model->save();

        if( $new_flag == true )
            $this->createChecklistTask($model, $cur_date, $user_id, 1);

        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function activeCheckList(Request $request)
    {
        $id = $request->get('id', 0);
        $disabled = $request->get('disabled', 0);

        DB::table('mod_checklist')
            ->where('id', $id)
            ->update(
                [
                    'disabled' => $disabled
                ]
            );

        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function deleteCheckList(Request $request)
    {
        $id = $request->get('id', 0);

        DB::table('mod_checklist')
            ->where('id', $id)
            ->delete();

        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function getCategoryList(Request $request)
    {
        $checklist_id = $request->get('checklist_id', 0);

        $list = ModCheckList::getCategoryList($checklist_id);
        $ret = array();
        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function createChecklistCategory(Request $request)
    {
        $checklist_id = $request->get('checklist_id', 0);
        $name = $request->get('name', '');
        $order_id = $request->get('order_id', 0);

        $ret = array();
        $ret['code'] = 200;
        if( empty($name) )
        {
            $ret['code'] = 201;
            $ret['message'] = 'Category name cannot be empty';
            return Response::json($ret);
        }

        // check duplicated name
        $exist = DB::table('mod_checklist_category as c')
                            ->where('c.checklist_id', $checklist_id)
                            ->where('c.name', $name)
                            ->select(DB::raw('c.*'))
                            ->exists();

        if( $exist )
        {
            $ret['code'] = 202;
            $ret['message'] = 'Category name cannot be duplicated';
            return Response::json($ret);
        }

        $input = array();

        $input['name'] = $name;
        $input['order_id'] = $order_id;
        $input['checklist_id'] = $checklist_id;

        $id = DB::table('mod_checklist_category')->insertGetId($input);

        $ret['list'] = ModCheckList::getCategoryList($checklist_id);
        $ret['id'] = $id;
        $ret['name'] = $name;

        return Response::json($ret);
    }

    public function createChecklistItem(Request $request)
    {
        $id = $request->get('id', 0);
        $category_id = $request->get('category_id', 0);
        $name = $request->get('name', '');
        $type = $request->get('type', 1);
        $checklist_id = $request->get('checklist_id', 0);
        $order_id = $request->get('order_id', 0);

        $input = array();

        $input['category_id'] = $category_id;
        $input['name'] = $name;
        $input['type'] = $type;

        if( $id > 0 )
            DB::table('mod_checklist_item')->where('id', $id)->update($input);
        else
            $id = DB::table('mod_checklist_item')->insertGetId($input);

        $input = array();
        $input['checklist_id'] = $checklist_id;
        $input['item_id'] = $id;

        $exists = DB::table('mod_checklist_pivot')
            ->where('checklist_id', $checklist_id)
            ->where('item_id', $id)
            ->exists();

        if( $exists == false )
            DB::table('mod_checklist_pivot')->insertGetId($input);

        // set category order
        $input = array();
        $input['order_id'] = $order_id;
        DB::table('mod_checklist_category')
            ->where('id', $category_id)
            ->update($input);

        $ret = array();

        $ret['list'] = ModCheckList::getChecklistItem($checklist_id);
        $ret['category_list'] = ModCheckList::getCategoryList($checklist_id);

        $ret['code'] = 200;
        $ret['id'] = $id;
        $ret['name'] = $name;

        return Response::json($ret);
    }

    public function getCheckListItemList(Request $request)
    {
        $checklist_id = $request->get('checklist_id', 0);

        $ret = array();

        $ret['code'] = 200;
        $ret['list'] = ModCheckList::getChecklistItem($checklist_id);

        return Response::json($ret);
    }


    public function deleteCheckListItem(Request $request)
    {
        $id = $request->get('id', 0);
        $checklist_id = $request->get('checklist_id', 0);

        DB::table('mod_checklist_pivot')
                ->where('checklist_id', $checklist_id)
                ->where('item_id', '=', $id)
                ->delete();

        $ret = array();

        $ret['code'] = 200;
        $ret['list'] = ModCheckList::getChecklistItem($checklist_id);

        return Response::json($ret);
    }


    public function generateChecklistTask(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d H:i:s");

        $list = ModCheckList::where('next_date', '<=', $cur_date)
            ->where('disabled', 0)
            ->get();

        foreach($list as $row)
        {
            // check if daily check list is completed
            $non_done_count = ModCheckListTask::where('checklist_id', $row->id)
                ->where('status', '!=', 'Done')
                ->count();

            if( $non_done_count > 0 )   // skip if not completed
                continue;

            $this->createChecklistTask($row, $cur_date, 0, 1);

            $row->next_date = ModCheckList::getNextDate($row, $row->next_date);

            $row->save();
        }

        return Response::json($list);
    }

    public function getCheckListTask(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $property_id = CommonUser::getPropertyID($user_id);
        $page = $request->get('page', 0);
        $pageSize = $request->get('pagesize', 20);
        $skip = $page;
        $orderby = $request->get('field', 'id');
        $sort = $request->get('sort', 'asc');
        $searchtext = $request->get('searchtext', '');
        $start_date = $request->get('start_date', '');
        $end_date = $request->get('end_date', '');
        $completed_by_ids = $request->get('completed_by_ids', '');
        $checklist_name = $request->get('checklist_name', '');
        $status_array = $request->get('status_array', '');

        date_default_timezone_set(config('app.timezone'));
        $cur_time = date("Y-m-d H:i:s");


        $query = DB::table('mod_checklist_task as mct')
                    ->leftJoin('mod_checklist as mc', 'mct.checklist_id', '=', 'mc.id')
                    ->leftJoin('common_users as cu', 'mct.completed_by', '=', 'cu.id')
                    ->leftJoin('services_location as sl', 'mct.location_id', '=', 'sl.id')
                    ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
                    ->whereRaw(sprintf("DATE(mct.start_date) >= '%s' and DATE(mct.start_date) <= '%s'", $start_date, $end_date))
                    ->where('mct.deleted', 0)
                    ->where('mc.property_id', $property_id);

        if($searchtext != '') {
            $where = sprintf("(mc.name like '%%%s%%' or 
            sl.name like '%%%s%%' or
            cu.first_name like '%%%s%%' or
            cu.last_name like '%%%s%%')",
                $searchtext,$searchtext,
                $searchtext,$searchtext
            );

            $query->whereRaw($where);
        }

        if( !empty($completed_by_ids) )
        {
            $query->whereRaw("(mct.completed_by IN ($completed_by_ids))");
        }

        if( !empty($status_array) )
        {
            $status_list = explode(",", $status_array);
            $query->whereIn('mct.status', $status_list);
        }

        if( !empty($checklist_name) )
        {
            $name_list = explode(",", $checklist_name);
            $query->whereIn('mc.name', $name_list);
        }

        $data_query = clone $query;
        $data_list = $data_query
                        ->orderBy($orderby, $sort)
                        ->select(DB::raw('mct.*, mc.name, mc.job_role_ids, mc.location_mode, sl.name as location_name, slt.type as location_type,
                                mc.freq_unit,
                                CASE 
                                    WHEN mc.freq_unit = \'Minutes\' THEN TIMEDIFF(\''. $cur_time . '\', mct.start_date)
                                    WHEN mc.freq_unit = \'Hours\' THEN TIMEDIFF(\''. $cur_time . '\', mct.start_date)
                                    ELSE DATEDIFF(\''. $cur_time . '\', mct.start_date) + 1
                                END as duration,
                                CASE 
                                    WHEN mc.freq_unit = \'Minutes\' THEN \'\'
                                    WHEN mc.freq_unit = \'Hours\' THEN \'\'
                                    ELSE \'Days\'
                                END as duration_unit,
                                CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                        ->skip($skip)->take($pageSize)
                        ->get();

        foreach($data_list as $index => $row)
        {

            $roleQuery  = DB::table('common_job_role');

            if(!empty($row->job_role_ids)) {
                $roleQuery->whereRaw("id IN ($row->job_role_ids)");
            }
            $row->job_role_tags = $roleQuery->get();

            $row->assigner = implode(",", array_map(function($item) {
                return $item->job_role;
            }, $row->job_role_tags->toArray()));

            $checklist_result = DB::table('mod_checklist_result as a')
                            ->join('mod_checklist_item as b', 'a.item_id', '=', 'b.id')
                            ->leftJoin('mod_checklist_category as c', 'b.category_id', '=', 'c.id')
                            ->leftJoin('common_users as cu', 'a.modified_by', '=', 'cu.id')
                            ->where('a.task_id', $row->id)
                            ->select(DB::raw('a.*, c.name as category_name,
                                    b.name as item_name, b.type as item_type,
                                    CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                            ->orderBy('c.order_id')
                            ->orderBy('b.created_at')
                            ->get();

//            $checklist_result_other = DB::table('mod_checklist_result as a')
//                                ->leftJoin('mod_checklist_category as c', 'a.category_id', '=', 'c.id')
//                                ->leftJoin('common_users as cu', 'a.modified_by', '=', 'cu.id')
//                                ->where('a.task_id', $row->id)
//                                ->where('a.item_id', 0)
//                                ->select(DB::raw('a.*, c.name as category_name,
//                                        a.item_name, a.type as item_type,
//                                        CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
//                                ->orderBy('c.order_id')
//                                ->orderBy('a.created_at')
//                                ->get();


//            $checklist_result = array_merge($checklist_result, $checklist_result_other);

            $yes = 0;
            $no = 0;
            $pending = 0;
            $comment_count = 0;
            foreach( $checklist_result as $row1)
            {
                if ($row1->item_type == 'Comment') {
                    $comment_count ++;
                } else {
                    if ($row1->check_flag == 1) {
                        if($row1->yes_no == 1)
                            $yes = $yes + 1;
                        else
                            $no = $no + 1;
                    } else {
                        $pending++;
                    }
                }
            }
            $row->yes_count = $yes;
            $row->no_count = $no;
            $row->pending_count = $pending;
            $row->comment_count = $comment_count;
        }

        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    public function getMyCheckListTaskCount(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $last_month = date("Y-m-d", strtotime("-1 months"));

        $user_id = $request->get('user_id', 0);
        $property_id = CommonUser::getPropertyID($user_id);
        $user = CommonUser::find($user_id);
        $job_role_id = $user->job_role_id;

        $select_sql = 'count(*) as total';

        $status_list = ['Pending', 'In Progress', 'Done'];
        foreach($status_list as $key => $row)
        {
            $select_sql .= ",COALESCE(sum(mct.status = '$row'), 0) as cnt$key";
        }

        $data = DB::table('mod_checklist_task as mct')
                    ->leftJoin('mod_checklist as mc', 'mct.checklist_id', '=', 'mc.id')
                    ->leftJoin('common_users as cu', 'mct.completed_by', '=', 'cu.id')
                    ->whereRaw("DATE(mct.start_date) >= '$last_month'")
                    ->whereRaw("FIND_IN_SET($job_role_id, mc.job_role_ids)")
                    ->where('mc.property_id', $property_id)
                        ->select(DB::raw($select_sql))
                        ->first();


        $list = array();

        $list[] = [
            'name' => 'All',
            'count' => $data->total,
        ];

        foreach($status_list as $key => $row)
        {
            $list[] = [
                'name' => $row,
                'count' => (int)($data->{"cnt$key"}),
            ];
        }

        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function getMyCheckListTaskCountByDate(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $last_week = date("Y-m-d", strtotime("-6 days"));

        $user_id = $request->get('user_id', 0);
        $property_id = CommonUser::getPropertyID($user_id);
        $user = CommonUser::find($user_id);
        $job_role_id = $user->job_role_id;

        $select_sql = 'count(*) as total';

        for($i = 0; $i < 7; $i++)
        {
            $sel_date = date('Y-m-d', strtotime("-$i days"));
            $select_sql .= ",COALESCE(sum(DATE(mct.start_date) = '$sel_date'), 0) as cnt$i";
        }

        $data = DB::table('mod_checklist_task as mct')
                    ->leftJoin('mod_checklist as mc', 'mct.checklist_id', '=', 'mc.id')
                    ->whereRaw("DATE(mct.start_date) >= '$last_week'")
                    ->whereRaw("FIND_IN_SET($job_role_id, mc.job_role_ids)")
                    ->where('mc.property_id', $property_id)
                        ->select(DB::raw($select_sql))
                        ->first();

        $list = array();

        $total = 0;
        for($i = 0; $i < 7; $i++)
        {
            $sel_date = date('Y-m-d', strtotime("-$i days"));
            $label = date('d M Y', strtotime("-$i days"));
            if( $i == 0 )
                $label = 'Today';
            if( $i == 1 )
                $label = 'Yesterday';

            $list[] = [
                'date' => $sel_date,
                'name' => $label,
                'count' => (int)($data->{"cnt$i"}),
            ];

            $total += $data->{"cnt$i"};
        }

        array_unshift($list, [
            'date' => '',
            'name' => 'All',
            'count' => $total,
        ]);

        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    public function getMyCheckListTask(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $last_month = date("Y-m-d", strtotime("-1 months"));

        $user_id = $request->get('user_id', 0);
        $property_id = CommonUser::getPropertyID($user_id);
        $user = CommonUser::find($user_id);
        $job_role_id = $user->job_role_id;
        $status_list = $request->get('status_list', "");
        $sel_date = $request->get('sel_date', "");

        $last_id = $request->get('last_id', -1);
        $pageSize = $request->get('pagesize', 20);
        // $orderby = $request->get('field', 'mct.id');
        // $sort = $request->get('sort', 'asc');

        $query = DB::table('mod_checklist_task as mct')
                    ->leftJoin('mod_checklist as mc', 'mct.checklist_id', '=', 'mc.id')
                    ->leftJoin('common_users as cu', 'mct.completed_by', '=', 'cu.id')
                    ->leftJoin('common_users as cu1', 'mc.submitter_id', '=', 'cu1.id')
                //    ->join('mod_checklist_category as mcc', 'mct.checklist_id', '=', 'mcc.checklist_id')
                    ->leftJoin('services_location as sl', 'mct.location_id', '=', 'sl.id')
                    ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
                    // ->whereRaw("DATE(mct.start_date) >= '$last_month'")
                    ->whereRaw("FIND_IN_SET($job_role_id, mc.job_role_ids)")
                    ->where('mct.deleted', 0)
                    ->where('mc.property_id', $property_id);

        if( empty($sel_date) )
        {
            $today = date("Y-m-d");
            $last_week = date("Y-m-d", strtotime("-6 days"));
            if( $status_list == "Done" )
            {
                $last_week = date("Y-m-d", strtotime("-15 days"));
                $query->whereRaw("DATE(mct.end_date) BETWEEN '$last_week' AND '$today'");
            }
            else
                $query->whereRaw("DATE(mct.start_date) BETWEEN '$last_week' AND '$today'");
        }
        else
        {
            if( $status_list == "Done" )
                $query->whereRaw("DATE(mct.end_date) = '$sel_date'");
            else
                $query->whereRaw("DATE(mct.start_date) = '$sel_date'");
        }

        if( $last_id > 0 )
            $query->where('mct.id', '<', $last_id);

        // status filter
        if( !empty($status_list) )
        {
            $status_array = explode(",", $status_list);
            $query->whereIn('mct.status', $status_array);
        }

        $data_query = clone $query;
        $data_list = $data_query
                        ->orderBy('mct.id', 'desc')
                        ->select(DB::raw('mct.*, mc.name, mc.job_role_ids, mc.location_mode,
                                DATEDIFF(CURTIME(), mct.start_date) as duration,
                                sl.name as location_name, slt.type as location_type,
                                CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, CONCAT_WS(" ", cu1.first_name, cu1.last_name) as creator'))
                        ->take($pageSize)
                        ->get();


                        foreach($data_list as $index => $row)
                        {

                                            $roleQuery  = DB::table('common_job_role');

                                            if(!empty($row->job_role_ids)) {
                                                $roleQuery->whereRaw("id IN ($row->job_role_ids)");
                                            }
                                            $row->job_role_tags = $roleQuery->get();

                                            $row->assigner = implode(",", array_map(function($item) {
                                                return $item->job_role;
                                            }, $row->job_role_tags));

                                            $checklist_request = DB::table('services_task as st')
                                                                ->where('st.checklist_id', $row->id)
                                                                ->get();

                                            if (!empty($checklist_request)){

                                                    $row->pending_request = 'Yes';
                                                    $row->request_count = count($checklist_request);
                                            }
                                            else
                                            {
                                                    $row->pending_request = 'No';
                                                    $row->request_count = 0;
                                            }

                                            $checklist_result = DB::table('mod_checklist_result as a')
                                                            ->join('mod_checklist_item as b', 'a.item_id', '=', 'b.id')
                                                            ->leftJoin('mod_checklist_category as c', 'b.category_id', '=', 'c.id')
                                                            ->leftJoin('common_users as cu', 'a.modified_by', '=', 'cu.id')
                                                            ->where('a.task_id', $row->id)
                                                            ->select(DB::raw('a.*, c.name as category_name,
                                                                    b.name as item_name, b.type as item_type,
                                                                    CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                                                            ->orderBy('c.order_id')
                                                            ->orderBy('b.created_at')
                                                            ->get();

                                //            $checklist_result_other = DB::table('mod_checklist_result as a')
                                //                                ->leftJoin('mod_checklist_category as c', 'a.category_id', '=', 'c.id')
                                //                                ->leftJoin('common_users as cu', 'a.modified_by', '=', 'cu.id')
                                //                                ->where('a.task_id', $row->id)
                                //                                ->where('a.item_id', 0)
                                //                                ->select(DB::raw('a.*, c.name as category_name,
                                //                                        a.item_name, a.type as item_type,
                                //                                        CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                                //                                ->orderBy('c.order_id')
                                //                                ->orderBy('a.created_at')
                                //                                ->get();


                                //            $checklist_result = array_merge($checklist_result, $checklist_result_other);

                                            $yes = 0;
                                            $no = 0;
                                            $pending = 0;
                                            $comment_count = 0;
                                            foreach( $checklist_result as $row1)
                                            {
                                                if ($row1->item_type == 'Comment') {
                                                    $comment_count ++;
                                                } else {
                                                    if ($row1->check_flag == 1) {
                                                        if($row1->yes_no == 1)
                                                            $yes = $yes + 1;
                                                        else
                                                            $no = $no + 1;
                                                    } else {
                                                        $pending++;
                                                    }
                                                }
                                            }
                                            $row->yes_count = $yes;
                                            $row->no_count = $no;
                                            $row->pending_count = $pending;
                                            $row->comment_count = $comment_count;
                        }


        $count_query = clone $query;
        $totalcount = $count_query->count();

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $data_list;
        $ret['totalcount'] = $totalcount;

        return Response::json($ret);
    }

    private function generateChecklistResult($task)
    {
        if( empty($task) )
            return;

        $checklist_item_list = ModCheckList::getChecklistItem($task->checklist_id);

        foreach($checklist_item_list as $row)
        {
            $exist = DB::table('mod_checklist_result')
                ->where('task_id', $task->id)
                ->where('item_id', $row->id)
                ->exists();

            if( $exist == true )
                continue;

            $input = array();

            $input['task_id'] = $task->id;
            $input['item_id'] = $row->id;

            DB::table('mod_checklist_result')->insertGetId($input);
        }
    }

    public function addChecklistItem(Request $request) {

        $checklist_id = $request->get('checklist_id', 0);
        $task_id = $request->get('task_id', 0);
        $category_id = $request->get('category_id', 0);
        $check_flag = $request->get('check_flag', 0);
        $yes_no = $request->get('yes_no', 0);
        $comment = $request->get('comment', "");
        $attached = $request->get('attached', "");
        $item_name = $request->get('name', "");

        $category_name = $request->get('category_name', "");

        if (empty($category_id)) {

//            create new category
            $input['name'] = $category_name;
            $input['order_id'] = 1;
            $input['checklist_id'] = $checklist_id;

            $category_id = DB::table('mod_checklist_category')->insertGetId($input);
        }

        // insert into checklist item
        $input = [];
        $input['name'] = $item_name;
        $input['category_id'] = $category_id;
        $input['type'] = 'Yes/No';

        $item_id = DB::table('mod_checklist_item')->insertGetId($input);

//        insert into pivot table
        $input = [];
        $input['checklist_id'] = $checklist_id;
        $input['item_id'] = $item_id;

        $exists = DB::table('mod_checklist_pivot')
            ->where('checklist_id', $checklist_id)
            ->where('item_id', $item_id)
            ->exists();

        if( $exists == false )
            DB::table('mod_checklist_pivot')->insertGetId($input);

        // insert into checklist result
        $input = [];
        $input['task_id'] = $task_id;
        $input['category_id'] = $category_id;
        $input['item_id'] = $item_id;
        $input['check_flag'] = $check_flag;
        $input['yes_no'] = $yes_no;
        $input['comment'] = $comment;
        $input['type'] = 'Yes/No';
        $input['attached'] = $attached;

        DB::table('mod_checklist_result')->insert($input);

        $ret = [
            'code' => 200
        ];

        return Response::json($ret);
    }

    public function getChecklistResult(Request $request)
    {
        $task_id = $request->get('task_id', 0);
        
        $task = ModCheckListTask::find($task_id);

        $this->generateChecklistResult($task);

        $ret = array();

        $ret['code'] = 200;

        $data = array();
        $data['inspected'] = $this->isInspectAllChecklist($task_id) ? 1 : 0;
        $data['list'] = ModCheckList::getChecklistResult($task_id);

        // get other list
        $data['other_list'] = ModCheckList::getChecklistOther($task_id);
        
        $checklist = ModCheckList::find($task->checklist_id);

        $data['location_mode'] = $checklist->location_mode;
        $data['location'] = DB::table('services_location as sl')
                                ->join('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
                                ->where('sl.id', $task->location_id)
                                ->select(DB::raw('sl.id, sl.name, slt.type'))
                                ->first();

        $ret['content'] = $data;

        return Response::json($ret);
    }

    private function isInspectAllChecklist($task_id)
    {
        $exist = DB::table('mod_checklist_result as a')
                            ->join('mod_checklist_item as b', 'a.item_id', '=', 'b.id')
                            ->leftJoin('mod_checklist_category as c', 'b.category_id', '=', 'c.id')
                            ->where('a.task_id', $task_id)
                            ->where('a.check_flag', 0)
                            ->select(DB::raw('a.*, c.name as category_name,
                                    b.name as item_name, b.type as item_type'))
                            ->exists();

        return !$exist;
    }

    public function updateChecklistStatus(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d H:i:s");

        $user_id = $request->input('user_id', 0);
        $task_id = $request->input('task_id', 0);
        $status = $request->input('status', 'Pending');

        $ret = array();

        $task = ModCheckListTask::find($task_id);
        if( !empty($task) )
        {

            if( $status == DONE )
            {
                $uncheck_list = DB::table('mod_checklist_result as a')
                            ->join('mod_checklist_item as b', 'a.item_id', '=', 'b.id')
                            ->leftJoin('mod_checklist_category as c', 'b.category_id', '=', 'c.id')
                            ->where('a.task_id', $task_id)
                            ->where('b.type', 'Yes/No')
                            ->where('a.check_flag', 0)
                            ->select(DB::raw('a.*, c.name as category_name,
                                    b.name as item_name, b.type as item_type'))
                            ->orderBy('c.name')
                            ->get();

                if(count($uncheck_list) > 0)
                {
                    $ret['code'] = 201;
                    $name_list = implode(",", array_map(function($item) {
                        return $item->item_name;
                    }, $uncheck_list->toArray()));

                    $ret['message'] = "$name_list is not still unchecked";

                    return Response::json($ret);
                }

                $task->end_date = $cur_date;
                $task->completed_by = $user_id;

                $ret['user_list'] = $this->sendChecklistPDFToUsergroup($task);

            }

            $task->status = $status;
            $task->save();

            if( $status == DONE ) {
                $this->sendNotificationForChecklist($task, $user_id);
                $checklist = ModCheckList::find($task->checklist_id);

                $property_id = $checklist->property_id;
                $this->sendWebpushUpdateStatus($property_id, $task_id);
            }
        }

        $ret['code'] = 200;

        return Response::json($ret);
    }

    public function updateChecklistLocation(Request $request)
    {
        $task_id = $request->get('task_id', 0);
        $location_id = $request->get('location_id', 0);

        $ret = array();

        $task = ModCheckListTask::find($task_id);
        if( !empty($task) )
        {
            $task->location_id = $location_id;
            $task->save();
        }

        $ret['code'] = 200;

        return Response::json($ret);
    }

    private function sendChecklistPDFToUsergroup($task)
    {
        if( empty($task) )
            return;

        $checklist = ModCheckList::find($task->checklist_id);

        if( empty($checklist) )
            return;

        if ($checklist->report_completor == 0){

            if( empty($checklist->user_group_ids) )
                return;

            $user_list = DB::table('common_users as cu')
                ->whereRaw("cu.job_role_id IN ($checklist->user_group_ids)")
                ->select(DB::raw('cu.*'))
                ->groupBy('cu.email')
                ->get();

            foreach($user_list as $row)
            {
                $this->sendChecklistReportEmail($checklist, $task, $row);
            }

            return $user_list;
        }
        else{

            $completed_by = DB::table('common_users')
                        ->where('id', $task->completed_by)
                        ->first();

            if( !empty($completed_by) ){

                $this->sendChecklistReportEmail($checklist, $task, $completed_by);
            }

            return $completed_by;

        }
    }

    public function updateChecklistResultFromWeb(Request $request)
    {
        $updated_checklist = $request->get('task_checklist', []);

        $user_id = $request->get('user_id', 0);

        foreach ($updated_checklist as $item) {
            $input = [];
            if ($item['item_type'] === 'Yes/No') {
                $input['yes_no'] = isset($item['yes_no']) ? $item['yes_no'] : 0;
            }

            $id = isset($item["id"]) && !empty($item["id"]) ? $item["id"] : 0;
            $task_id = isset($item["task_id"]) && !empty($item["task_id"]) ? $item["task_id"] : 0;
            $item_id = isset($item["item_id"]) && !empty($item["item_id"]) ? $item["item_id"] : 0;

            $input['check_flag'] = $item['check_flag'];
            $input['attached'] = isset($item['attached']) ? $item['attached'] : '';
            $input['comment'] = isset($item['comment']) && !empty($item['comment']) ? $item['comment'] : '';
            $input['modified_by'] = $user_id;

            if( $id == 0 )
            {
                DB::table('mod_checklist_result')
                    ->where('task_id', $task_id)
                    ->where('item_id', $item_id)
                    ->update($input);
            }
            else
            {
                DB::table('mod_checklist_result')
                    ->where('id', $id)
                    ->update($input);
            }
        }

        return $this->updateChecklistStatus($request);
    }

    public function updateChecklistResult(Request $request)
    {

        $user_id = $request->get('user_id', 0);
        $id = $request->get('id', 0);
        $task_id = $request->get('task_id', 0);
        $item_id = $request->get('item_id', 0);

        $yes_no = $request->get('yes_no', 0);
        $comment = $request->get('comment', '');
        $attached = $request->get('attached', '');

        if( $comment == 'null' )
            $comment = '';

        $ret = array();

        $input = array();

        $input['yes_no'] = $yes_no;
        $input['check_flag'] = 1;
        $input['attached'] = $attached;
        $input['comment'] = $comment;
        $input['modified_by'] = $user_id;

        if( $id == 0 )
        {
            DB::table('mod_checklist_result')
                ->where('task_id', $task_id)
                ->where('item_id', $item_id)
                ->update($input);
        }
        else
        {
            DB::table('mod_checklist_result')
                ->where('id', $id)
                ->update($input);
        }

        // check inspected result
        $inspected = $this->isInspectAllChecklist($task_id);

        $task = ModCheckListTask::find($task_id);
        if( empty($task) )
        {
            $ret['code'] = 201;
            return Response::json($ret);
        }

        $start_flag = $task->status == PENDING;

        $task->status = IN_PROGRESS;
        $task->save();

        $task->inspected = $inspected ? 1 : 0;

        if( $start_flag == true )
            $this->sendNotificationForChecklist($task, $user_id);

        $checklist = ModCheckList::find($task->checklist_id);

        $property_id = $checklist->property_id;

        $this->sendWebpushUpdate($property_id, $task_id);
        $ret['code'] = 200;
        $ret['content'] = $task;

        return Response::json($ret);
    }


    public function createChecklistOther(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $id = $request->get('id', 0);
        // $checklist_id = $request->get('checklist_id', 0);
        $category_id = $request->get('category_id', 0);
        $order_id = $request->get('order_id', 0);
        $task_id = $request->get('task_id', 0);
        $item_name = $request->get('item_name', '');
        $item_type = $request->get('item_type', 'Yes/No');

        $task = DB::table('mod_checklist_task')
            ->where('id', $task_id)
            ->first();

        $checklist_id = $task->checklist_id;


        // check category is selected
        if( $category_id == 0 )
        {
            // check Other category name
            $category = DB::table('mod_checklist_category as c')
                                ->where('c.checklist_id', $checklist_id)
                                ->where('c.name', 'Other')
                                ->select(DB::raw('c.*'))
                                ->first();

            if( empty($category) )
            {
                $category = DB::table('mod_checklist_category as c')
                                ->where('c.checklist_id', $checklist_id)
                                ->select(DB::raw('c.order_id'))
                                ->orderBy('c.order_id', 'desc')
                                ->first();

                $order_id = 1;
                if( !empty($category) )
                    $order_id = $category->order_id + 1;

                $input = array();

                $input['name'] = 'Other';
                $input['order_id'] = $order_id;
                $input['checklist_id'] = $checklist_id;

                $category_id = DB::table('mod_checklist_category')->insertGetId($input);
            }
            else
            {
                $category_id = $category->id;
                if( $order_id == 0 )
                    $order_id = $category->order_id;
            }
        }



        $ret = array();

        $input = array();

        $input['task_id'] = $task_id;
        $input['item_id'] = 0;
        $input['item_name'] = $item_name;
        $input['category_id'] = $category_id;
        $input['type'] = $item_type;

        if( $id == 0 )
        {
            DB::table('mod_checklist_result')
                    ->insert($input);
        }
        else
        {
            DB::table('mod_checklist_result')
                ->where('id', $id)
                ->update($input);
        }

        // set category order
        $input = array();
        $input['order_id'] = $order_id;
        DB::table('mod_checklist_category')
            ->where('id', $category_id)
            ->update($input);

        $data = array();
        $data['other_list'] = ModCheckList::getChecklistOther($task_id);

        $ret['code'] = 200;
        $ret['content'] = $data;


        return Response::json($ret);
    }


    public function deleteChecklistResult(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $id = $request->get('id', 0);
        $task_id = $request->get('task_id', 0);

        $ret = array();

        DB::table('mod_checklist_result')
                ->where('id', $id)
                ->delete();

        $data = array();
        $data['other_list'] = ModCheckList::getChecklistOther($task_id);

        $ret['code'] = 200;
        $ret['content'] = $data;

        return Response::json($ret);
    }


    public function updateChecklistAttach(Request $request)
    {
        $id = $request->get('id', 0);
        $task_id = $request->get('task_id', 0);
        $item_id = $request->get('item_id', 0);
        $attached = $request->get('attached', '');

        $input = array();

        $input['check_flag'] = 1;
        $input['attached'] = $attached;

        if( $id == 0 )
        {
            DB::table('mod_checklist_result')
                    ->where('task_id', $task_id)
                    ->where('item_id', $item_id)
                    ->update($input);
        }
        else
        {
            DB::table('mod_checklist_result')
                    ->where('id', $id)
                    ->update($input);
        }

        $ret = array();

        $ret['code'] = 200;

        return Response::json($ret);
    }


    public function uploadAttachForChecklist(Request $request)
    {
        $task_id = $request->get('task_id', 0);

        $filekey = 'files';
        $output_dir = "uploads/mod/";

        if( is_dir($output_dir) === false )
            mkdir($output_dir, 0777, true);

        $fileCount = count($_FILES[$filekey]["name"]);

        $list = [];

        for ($i = 0; $i < $fileCount; $i++)
        {
            $fileName = $_FILES[$filekey]["name"][$i];
            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
            $filename1 = "mod_" . $task_id . '_' . $i . '_' . date('Y_m_d_H_i_s') . '.' . $ext;

            $dest_path = $output_dir . $filename1;
            move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);

            $list[] = $dest_path;
        }

        $ret = array();

        $ret['code'] = 200;
        $ret['content'] = $list;

        return Response::json($ret);
    }

    private function getModChecklistReportDataProc($checklist, $task)
    {
        $data = array();

        if( empty($checklist) || empty($task) )
            return $data;

        $data['font-size'] = '7px';
        $data['property'] = DB::table('common_property')->where('id', $checklist->property_id)->first();
        $data['name'] = $checklist->name;
        $data['created_at'] = $task->end_date;
        $data['completed_by'] = '';

        $completed_by = DB::table('common_users')
            ->where('id', $task->completed_by)
            ->first();

        if( !empty($completed_by) )
            $data['completed_by'] = "$completed_by->first_name $completed_by->last_name";

        // Location
        $loc = DB::table('services_location as sl')
            ->where('sl.id', $task->location_id)
            ->first();

        if( !empty($loc) )
            $data['location']  = $loc->name;
        else
            $data['location']  = '';

        $checklist_result = DB::table('mod_checklist_result as a')
                            ->join('mod_checklist_item as b', 'a.item_id', '=', 'b.id')
                            ->leftJoin('mod_checklist_category as c', 'b.category_id', '=', 'c.id')
                            ->leftJoin('common_users as cu', 'a.modified_by', '=', 'cu.id')
                            ->where('a.task_id', $task->id)
                            ->select(DB::raw('a.*, c.name as category_name,
                                    b.name as item_name, b.type as item_type,
                                    CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                            ->orderBy('c.order_id')
                            ->orderBy('b.created_at')
                            ->get();

        $checklist_result_other = DB::table('mod_checklist_result as a')
                            ->leftJoin('mod_checklist_category as c', 'a.category_id', '=', 'c.id')
                            ->leftJoin('common_users as cu', 'a.modified_by', '=', 'cu.id')
                            ->where('a.task_id', $task->id)
                            ->where('a.item_id', 0)
                            ->select(DB::raw('a.*, c.name as category_name,
                                    a.item_name, a.type as item_type,
                                    CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename'))
                            ->orderBy('c.order_id')
                            ->orderBy('a.created_at')
                            ->get();

        $checklist_result = array_merge($checklist_result, $checklist_result_other);

        $site_url = Functions::getSiteURL();
        // get attach list based on base64
        foreach( $checklist_result as $row)
        {
            $attach_list = [];
            if( !empty($row->attached) )
                $attach_list = explode("&", $row->attached);
            $base64_image_list = [];
            foreach($attach_list as $row1)
            {
                $path = $_SERVER["DOCUMENT_ROOT"] . "/" . $row1;
                if( file_exists($path) )
                {
                    $ext = pathinfo($path, PATHINFO_EXTENSION);

                    $base64 = "data:image/$ext;base64," . Functions::saveImageWidthBase64Resize($path, 50, 50);
                    $base64_image_list[] = array(
                        'url' => $site_url . $row1,
                        'base64' => $base64,
                    );
                }
            }

            $row->base64_image_list = $base64_image_list;
        }

        // group by category
        $checklist_category = [];

        $prev_category_name = '';
        $sublist = [];
        $yes = 0;
        $no = 0;
        foreach( $checklist_result as $row)
        {
            if( $prev_category_name != $row->category_name )
            {
                if( !empty($prev_category_name) )
                {
                    $checklist_category[] = [
                        'category' => $prev_category_name,
                        'sublist' => $sublist,
                        'yes' => $yes,
                        'no' => $no,
                    ];
                }

                $prev_category_name = $row->category_name;
                $sublist = [];
                $yes = 0;
                $no = 0;
            }

            $sublist[] = $row;

            if($row->yes_no == 1)
                $yes = $yes + 1;
            else
                $no = $no + 1;
        }

        if( !empty($prev_category_name) )
        {
            $checklist_category[] = [
                'category' => $prev_category_name,
                'sublist' => $sublist,
                'yes' => $yes,
                'no' => $no,
            ];
        }


        $data['checklist_category'] = $checklist_category;

        $path1 = $_SERVER["DOCUMENT_ROOT"] . '/images/tick.png';
        $data1 = file_get_contents($path1);
        $base64 = 'data:image/png;base64,' . base64_encode($data1);

        $data['tick_icon_base64'] = $base64;

        $path1 = $_SERVER["DOCUMENT_ROOT"] . '/images/cancel.png';
        $data1 = file_get_contents($path1);
        $base64 = 'data:image/png;base64,' . base64_encode($data1);
        $data['cancel_icon_base64'] = $base64;

        return $data;
    }

    public function getModChecklistReportData(Request $request)
    {
        $task_id = $request->get('task_id', 0) ;

        $task = ModCheckListTask::find($task_id);
        $checklist = ModCheckList::find($task->checklist_id);

        return $this->getModChecklistReportDataProc($checklist, $task);
    }

    private function sendChecklistReportEmail($checklist, $task, $user)
    {
        if( empty($checklist) )
            return;

        ob_start();

        $filename = 'MOD Checklist_' . date('d_M_Y_H_i') . '_' . $checklist->name;
        $folder_path = public_path() . '/uploads/reports/';
        $path = $folder_path . $filename . '.html';

        $pdf_path = $folder_path . $filename . '.pdf';

        $data = $this->getModChecklistReportDataProc($checklist, $task);

        $content = view('frontend.report.mod_checklist_pdf', compact('data'))->render();
        echo $content;
        file_put_contents($path, ob_get_contents());

        ob_clean();

        $info = array();

        $info['mod'] = "$user->first_name $user->last_name";
        $info['creator'] = $data['completed_by'];
        $info['name'] = $checklist->name;
        $request = array();

        $subject = 'MOD Checklist ' . $checklist->name;
        $email_content = view('emails.mod_checklist', ['info' => $info])->render();

        $request['to'] = $user->email;

        $request['subject'] = $subject;
        $request['html'] = $subject;
        $request['filename'] = $filename . '.pdf';
        $request['content'] = $email_content;

        $smtp = Functions::getMailSetting($checklist->property_id, '');

        $request['smtp'] = $smtp;

        $options = array();
        $options['html'] = $path;
        $options['pdf'] = $pdf_path;
        $options['paperSize'] = array('format' => 'A4', 'orientation' => 'portrait');
        $request['options'] = $options;

        $message = array();
        $message['type'] = 'report_pdf';
        $message['content'] = $request;

        Redis::publish('notify', json_encode($message));

        return Response::json($request);
    }

    private function sendNotificationForChecklist($task, $user_id)
    {
        if( empty($task) )
            return;

        $checklist = ModCheckList::find($task->checklist_id);

        if( empty($checklist) )
            return;

        if( empty($checklist->notify_group_ids) )
            return;


        $user_list = DB::table('common_users as cu')
            ->whereRaw("cu.job_role_id IN ($checklist->notify_group_ids)")
            ->select(DB::raw('cu.*'))
            ->groupBy('cu.email')
            ->get();

        $created_by = CommonUser::find($user_id);

        $actor_name = 'System';
        if( !empty($created_by) )
            $actor_name = "$created_by->first_name $created_by->last_name";

        foreach($user_list as $row)
        {
            $this->sendChecklistEmail($checklist, $task, $row, $actor_name);
        }

        $user_list = DB::table('common_users as cu')
            ->whereRaw("cu.job_role_id IN ($checklist->notify_group_ids)")
            ->select(DB::raw('cu.*'))
            ->get();

        foreach($user_list as $row)
        {
            $this->sendMobilePushNotification($checklist, $task, $row, $actor_name);
        }

        return $user_list;
    }

    private function sendChecklistEmail($checklist, $task, $user, $actor_name)
    {
        if( empty($checklist) )
            return;

        $info = array();

        $info['wholename'] = $user->first_name . ' ' . $user->last_name;

        if( $task->status == PENDING )
            $info['title'] = $checklist->name . ' has been generated';

        if( $task->status == IN_PROGRESS )
            $info['title'] = $checklist->name . ' has been started by ' . $actor_name;

        if( $task->status == DONE )
            $info['title'] = $checklist->name . ' has been completed by ' . $actor_name;

        if( $checklist->location_id > 0 )
        {
            $location = Location::getLocationInfo($checklist->location_id);
            if( !empty($location) )
                $info['title'] .= " For $location->name $location->type";
        }

        $subject = 'MOD Checklist ' . $checklist->name;
        $email_content = view('emails.mod_checklist_notification', ['info' => $info])->render();

        $smtp = Functions::getMailSetting($checklist->property_id, '');

        $message = array();
        $message['type'] = 'email';
        $message['to'] = $user->email;
        $message['subject'] = $subject;
        $message['content'] = $email_content;
        $message['smtp'] = $smtp;

        Redis::publish('notify', json_encode($message));

        // echo $email_content;

        return $message;
    }

    private function sendMobilePushNotification($checklist, $task, $user, $actor_name)
    {
        $payload = array();
        $payload['table_id'] = $task->id;
        $payload['table_name'] = 'mod_checklist_task';
        $payload['property_id'] = $checklist->property_id;
        $payload['notify_type'] = 'mod_checklist';

        $notify_type = $task->status;

        $payload['type'] = 'Checklists ' . $notify_type;
        $payload['header'] = 'Checklists';

        if( $task->status == PENDING )
            $message = $checklist->name . ' has been generated';

        if( $task->status == IN_PROGRESS )
            $message = $checklist->name . ' has been started by ' . $actor_name;

        if( $task->status == DONE )
            $message = $checklist->name . ' has been completed by ' . $actor_name;

        Functions::sendPushMessgeToDeviceWithRedisNodejs(
                    $user, 0, 'MOD Checklist', $message, $payload
            );
    }

    public function testResize(Request $request)
    {
        $path = $_SERVER["DOCUMENT_ROOT"] . "/uploads/mod/mod_4310_0_2020_10_07_16_59_44.jpg";
        $ret = Functions::saveImageWidthBase64Resize($path);
        echo $ret;
    }

    public function deletechecklistTask(Request $request)
    {
        $id = $request->get('id', 0);

        DB::table('mod_checklist_task')
            ->where('id', $id)
            ->update(['deleted' => '1']);


        $ret = array();
        $ret['code'] = 200;

        return Response::json($ret);
    }
}
