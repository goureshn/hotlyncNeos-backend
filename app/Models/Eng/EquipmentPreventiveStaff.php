<?php

namespace App\Models\Eng;

use Illuminate\Database\Eloquent\Model;

use DB;

class EquipmentPreventiveStaff extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'eng_preventive_staff';
    public 		$timestamps = false;

    public static function addStaffGroupData($preventive_id, $staffs)
    {
        DB::table('eng_preventive_staff')
                ->where('preventive_id', $preventive_id)
                ->delete();

        $staff_group = array();

        for ($i = 0; $i < count($staffs); $i++) 
        {            
            $staff_id = 0;
            $staff_type = "";
            $staff_name = "";
            $staff_cost = 0;
            if(!empty($staffs[$i]['id']))
                $staff_id = $staffs[$i]['id'];
            if(!empty($staffs[$i]['type']))
                $staff_type = $staffs[$i]['type'];
            if(!empty($staffs[$i]['name']))
                $staff_name = $staffs[$i]['name'];
            if(!empty($staffs[$i]['cost']))
                $staff_cost = $staffs[$i]['cost'];

            $staff_group[$i] = array();
            $staff_group[$i]['staff_type'] = $staff_type;
            $staff_group[$i]['staff_id'] = $staff_id;
            $staff_group[$i]['staff_name'] =  $staff_name;
            $staff_group[$i]['staff_cost'] =  $staff_cost;
            
            DB::table('eng_preventive_staff')->insert(['preventive_id' => $preventive_id,
                'staff_id' => $staff_id,
                'staff_type' => $staff_type,
                'staff_name' => $staff_name
            ]);
        }

        return $staff_group;
    }

    public static function getStaffGroupData($preventive_id)
    {
        $list = DB::table('eng_preventive_staff as eps')            
                        ->where('eps.preventive_id', $preventive_id)                        
                        ->get();             

        $staff_group = array();

        foreach($list as $row)
        {
            $data = array();

            $data['staff_type'] = $row->staff_type;
            $data['staff_id'] = $row->staff_id;
            $data['staff_name'] =  $row->staff_name;

            $staff_cost = 0;

            if($row->staff_type == 'group') 
            {
                $group_staff = DB::table('common_user_group as cg')
                        ->leftJoin('common_user_group_members as cgm', 'cg.id', '=', 'cgm.group_id')
                        ->leftJoin('common_users as cu', 'cgm.user_id', '=', 'cu.id')
                        ->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
                        ->where('cg.id', $row->staff_id)
                        ->select(DB::raw('sum(jr.cost) as cost'))                        
                        ->groupBy('cg.id')
                        ->first();
                if( !empty($group_staff) )          
                    $staff_cost =  $group_staff->cost;
            }
            else    // single
            { 
                $staff = DB::table('common_users as cu')
                    ->leftJoin('common_job_role as jr', 'jr.id', '=', 'cu.job_role_id')
                    ->leftJoin('common_department as de','cu.dept_id','=','de.id')                
                    ->where('cu.id', $row->staff_id)                    
                    ->select(DB::raw(' jr.cost as cost'))
                    ->first();

                if( !empty($group_staff) )          
                    $staff_cost =  $staff->cost;
            }

            $data['staff_cost'] =  $staff_cost;

            $staff_group[] = $data;
        }                        
     
        return $staff_group;
    }
}