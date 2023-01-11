<?php

namespace App\Models\Eng;

use Illuminate\Database\Eloquent\Model;
use DB;

class EngRepairRequest extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'eng_repair_request';
    public 		$timestamps = false;

    public static function getDetail($id)
    {
        $data = DB::table('eng_repair_request as err')
            ->leftJoin('services_location as sl', 'err.location_id', '=', 'sl.id')
            ->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
            ->leftJoin('eng_request_category as erc', 'err.category_id', '=', 'erc.id')
            ->leftJoin('eng_request_subcategory as ers', 'err.sub_category_id', '=', 'ers.id')
            ->leftJoin('common_users as cu', 'err.requestor_id', '=', 'cu.id')
            ->leftJoin('common_users as cu1', 'err.assignee', '=', 'cu1.id')
            ->leftJoin('eng_equip_list as eq', 'err.equipment_id', '=', 'eq.id')   
            ->leftJoin('eng_contracts as ec', 'err.requestor_id', '=', 'ec.id')   
            ->leftJoin('eng_tenant as et', 'err.requestor_id', '=', 'et.id')          
            ->where('err.id', $id)
            ->select(DB::raw('err.*, sl.name as location_name, slt.type as location_type, cu.email, cu1.email as assignee_email,
                        eq.name as equip_name, eq.equip_id as equip, ec.leasor, ec.leasor_email, et.name as tenant_name, et.email as tenant_email,
                        CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename,
                        erc.name as category_name, ers.name as subcategory_name'))
            ->first();

            if ($data->requestor_type == 'User'){
                $data->wholename =  $data->wholename;
                $data->email = $data->email;
            }
            if ($data->requestor_type == 'Leasor'){
                $data->wholename =  $data->leasor;
                $data->email = $data->leasor_email;
            }

            if ($data->requestor_type == 'Tenant'){
                $data->wholename =  $data->tenant_name;
                $data->email = $data->tenant_email;
            }

            $data->staff_groups =  DB::table('eng_repair_staff as ers')            
                ->leftJoin('common_user_group as cug', 'ers.staff_id', '=', 'cug.id')
                ->leftJoin('common_users as cu', 'ers.staff_id', '=', 'cu.id')
                ->where('ers.request_id', $id)
                ->select(DB::raw("ers.staff_id as id, ers.staff_type as type, CONCAT(ers.staff_name, '-', ers.staff_type) as text,
                                (CASE WHEN ers.staff_type = 'group' THEN cug.name ELSE CONCAT_WS(\" \", cu.first_name, cu.last_name) END) as name"))
                ->get();    

            $data->assignee_name = implode(",", array_map(function($item) {
                return $item->name;
            }, $data->staff_groups->toArray()));
            
        return $data;    
    }

    public static function getStaffGroups(&$row)
    {
        $row->staff_groups =  DB::table('eng_repair_staff as ers')            
                ->leftJoin('common_user_group as cug', 'ers.staff_id', '=', 'cug.id')
                ->leftJoin('common_users as cu', 'ers.staff_id', '=', 'cu.id')
                ->where('ers.request_id', $row->id)
                ->select(DB::raw("ers.staff_id as id, ers.staff_type as type, CONCAT(ers.staff_name, '-', ers.staff_type) as text, ers.staff_name,
                                (CASE WHEN ers.staff_type = 'group' THEN cug.name ELSE CONCAT_WS(\" \", cu.first_name, cu.last_name) END) as name"))
                ->get();    

        $row->assignee_name = implode(",", array_map(function($item) {
            return $item->name;
        }, $row->staff_groups));
        $row->assignee_list = $row->staff_groups;
    }
}