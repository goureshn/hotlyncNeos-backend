<?php

namespace App\Models\Eng;

use Illuminate\Database\Eloquent\Model;
use App\Modules\Functions;
use DB;

class WorkOrder extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'eng_workorder';
    public 		$timestamps = false;

    public static function getAssigneList($workorder, $staff_group)    
    {
        if( empty($staff_group) )
        {
            $staff_group = DB::table('eng_workorder_staff')
                ->where('workorder_id', $workorder->id)
                ->select(DB::raw("workorder_id, staff_id, staff_name, staff_type, staff_cost"))
                ->get();    
        }
        
        $assign_list = [];

        // if( $workorder->work_order_type == 'Preventive' )
        {
            foreach($staff_group as $row)
            {
                if($row->staff_type == 'group')
                {
                    $user_list = DB::table('common_user_group as cg')
                        ->join('common_user_group_members as cgm', 'cg.id', '=', 'cgm.group_id')
                        ->join('common_users as cu', 'cgm.user_id', '=', 'cu.id')
                        ->where('cg.id', $row->staff_id)
                        ->groupBy('cu.id')
                        ->select(DB::raw('cu.*, cu.id as assignee, CONCAT_WS(" ", cu.first_name, cu.last_name) as assignee_name'))
                        ->get();

                    $assign_list = array_merge($assign_list, $user_list);    
                }
                else
                {
                    $user_list = DB::table('common_users as cu')
                        ->where('cu.id', $row->staff_id)
                        ->select(DB::raw('cu.*, cu.id as assignee, CONCAT_WS(" ", cu.first_name, cu.last_name) as assignee_name'))
                        ->get();

                    $assign_list = array_merge($assign_list, $user_list);       
                }
            }
        }

        return $assign_list;
    }

    public static function getAssigneListNames($workorder, $staff_group)    
    {
        if( empty($staff_group) )
        {
            $staff_group = DB::table('eng_workorder_staff')
                ->where('workorder_id', $workorder->id)
                ->select(DB::raw("workorder_id, staff_id, staff_name, staff_type, staff_cost"))
                ->get();    
        }
        
        $assign_list = '';
     
        // if( $workorder->work_order_type == 'Preventive' )
        {
            foreach($staff_group as $row)
            {
                if($row->staff_type == 'group')
                {
                    $user_list = DB::table('common_user_group as cg')
                        ->join('common_user_group_members as cgm', 'cg.id', '=', 'cgm.group_id')
                        ->join('common_users as cu', 'cgm.user_id', '=', 'cu.id')
                        ->where('cg.id', $row->staff_id)
                        ->groupBy('cu.id')
                        ->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as assignee_name'))
                        ->get();

                        $user_group = implode(",", array_map(function($item) {
                            return $item->assignee_name;
                        }, $user_list));

                    $assign_list .= $user_group . ',';    
                  
                }
                else
                {
                    $user_list = DB::table('common_users as cu')
                        ->where('cu.id', $row->staff_id)
                        ->select(DB::raw('CONCAT_WS(" ", cu.first_name, cu.last_name) as assignee_name'))
                        ->get();

                        $user_group = implode(",", array_map(function($item) {
                            return $item->assignee_name;
                        }, $user_list));
                  
                    $assign_list .= $user_group . ','; 
                        
                }
            }
        }

        return $assign_list;
        
    }


    public static function getWorkorderDetail(&$workorder)
    {
        // part group
        $part_group = DB::table('eng_workorder_part')
                ->where('workorder_id',$workorder->id)
                ->select(DB::raw("workorder_id, part_id, part_name, part_number, part_cost, part_stock, part_number as part_number_original"))
                ->get();                    
        $workorder->part_group = $part_group;
       
        // staff group
        $staff_group = DB::table('eng_workorder_staff')
            ->where('workorder_id',$workorder->id)
            ->select(DB::raw("workorder_id, staff_id, staff_name, staff_type, staff_cost"))
            ->get();
        $workorder->staff_group = $staff_group;            
        $workorder->assigne_list = WorkOrder::getAssigneList($workorder, $staff_group);
        $workorder->assigne_list_names = WorkOrder::getAssigneListNames($workorder, $staff_group);


        // get equipment group
        $equip_group = DB::table('eng_equip_group_member as eegm')
            ->join('eng_equip_group as eeg', 'eegm.group_id', '=', 'eeg.id')
            ->where('eegm.equip_id', $workorder->equipment_id)
            ->select(DB::Raw('eeg.*'))                
            ->groupBy('eegm.group_id')
            ->first();

        $workorder->equip_group = $equip_group;

        $workorder->time_spent = gmdate('H:i:s', $workorder->duration);        
        
        // get file list
        $workorder->filelist = DB::table('eng_workorder_files')->where('workorder_id', $workorder->id)->get();
        
        // inspected result
        $workorder->inspected = WorkOrder::isChecklistCompleted($workorder);

        // get check list item count
        $workorder->checklist_item_count = DB::table('eng_checklist_pivot as a')                            
                            ->join('eng_checklist_item as b', 'a.item_id', '=', 'b.id')
                            ->leftJoin('eng_checklist_category as c', 'b.category_id', '=', 'c.id')
                            ->where('a.checklist_id', $workorder->checklist_id)                            
                            ->count();

        $workorder->ticket_id = WorkOrder::getDailyId($workorder);    
        
        $status_log = DB::table('eng_workorder_status_log')
                ->where('workorder_id',$workorder->id)
                ->where('status', 'On Hold')
                ->select(DB::raw("setdate"))
                ->first();   
        if(!empty($status_log)){
       
            $workorder->hold_time = $status_log->setdate;
        }
        else{
            $workorder->hold_time = '';
        }

        $workorder->actual_time = (strtotime($workorder->hold_time) - strtotime($workorder->start_date)) + (strtotime($workorder->end_date) - strtotime($workorder->hold_time));
    }

    static function getWorkorderDetailWithStaff(&$workorder, $staff_id)
    {
        WorkOrder::getWorkorderDetail($workorder);

        $staff_status = DB::table('eng_workorder_staff_status')
                ->where('workorder_id', $workorder->id)
                ->where('staff_id', $staff_id)
                ->first();

        if( empty($staff_status) )  
            $workorder->staff_status = 'Not Assigned';
        else    
            $workorder->staff_status = $staff_status->status;
    }

    static function isChecklistCompleted($workorder)
    {
        if( empty($workorder) )
            return false;

        $checklist_item_list = WorkOrder::getChecklistItem($workorder->checklist_id);
        
        $exists = true;
        foreach($checklist_item_list as $row)
        {
            $exists = DB::table('eng_workorder_checklist')
                ->where('workorder_id', $workorder->id)
                ->where('item_id', $row->id)
                ->exists();

            if( $exists == false )
            {               
                break;
            }
        }    

        $inspected = false;
        if( $exists == false )
            $inspected = false;
        else
        {
            $inspected = !(DB::table('eng_workorder_checklist')                            
                                    ->where('workorder_id', $workorder->id)
                                    ->where('check_flag', 0)                       
                                    ->exists());
        }

        return $inspected;
    }

    static function getChecklistItem($checklist_id)
    {
        return DB::table('eng_checklist_pivot as a')                            
                            ->join('eng_checklist_item as b', 'a.item_id', '=', 'b.id')
                            ->leftJoin('eng_checklist_category as c', 'b.category_id', '=', 'c.id')
                            ->where('a.checklist_id', $checklist_id)
                            ->select(DB::raw('b.*, c.name as category_name, c.order_id'))
                            ->orderBy('c.order_id')
                            ->orderBy('b.name')
                            ->get();
    }

    static function getChecklistForWorkorder($workorder)
    {
        return WorkOrder::getChecklistItem($workorder->checklist_id);
    }

    static function getChecklistResult($workorder_id)
    {
        $checklist_result =  DB::table('eng_workorder_checklist as a')                            
                                ->join('eng_checklist_item as b', 'a.item_id', '=', 'b.id')
                                ->leftJoin('eng_checklist_category as c', 'b.category_id', '=', 'c.id')
                                ->where('a.workorder_id', $workorder_id)
                                ->select(DB::raw('a.*, c.name as category_name, c.order_id,
                                        b.name as item_name, b.type as item_type'))
                                ->orderBy('c.order_id')
                                ->orderBy('b.name')
                                ->get();


            $site_url = Functions::getSiteURL();

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

            return $checklist_result;
    }

    static function getMaxDailyID($property_id, $cur_date)
    {
        // calculate max daily id for selected date
        $daily = DB::table('eng_workorder')
            ->where('property_id', $property_id)
            ->whereRaw("DATE(created_date) = '$cur_date'")
            ->select(DB::raw('max(daily_id) as max_daily_id'))
            ->first();

        $daily_id = 1;    
        if( !empty($daily->max_daily_id) )
            $daily_id = $daily->max_daily_id + 1;           
            
        return $daily_id;    
    }

    static function getDailyId($workorder)
    {
        if( empty($workorder) )
            return 'WO' . date('Ymd00');
        
        return 'WO' . sprintf('%s%02d', date('Ymd', strtotime($workorder->created_date)), $workorder->daily_id);  
    }

}