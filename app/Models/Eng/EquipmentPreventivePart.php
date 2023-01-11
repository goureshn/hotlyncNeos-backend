<?php

namespace App\Models\Eng;

use Illuminate\Database\Eloquent\Model;

use DB;

class EquipmentPreventivePart extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'eng_preventive_part';
    public 		$timestamps = false;

    public static function addPartGroupData($preventive_id, $parts)
    {
        // remove preventive parts
        DB::table('eng_preventive_part')
            ->where('preventive_id', $preventive_id)
            ->delete();

        // add preventive parts
        if (!empty($parts)) 
        {
            for ($i = 0; $i < count($parts); $i++) {                
                $part_id = $parts[$i]['id'];
                $part_type = $parts[$i]['type'];
                $part_quantity = $parts[$i]['quantity'];
                DB::table('eng_preventive_part')->insert(['preventive_id' => $preventive_id,
                    'part_id' => $part_id,
                    'part_type' => $part_type,
                    'part_quantity' =>$part_quantity
                ]);
            }
        }

    }

    public static function getPartGroupData($preventive_id)
    {
        $part_data = DB::table('eng_preventive_part as epp')
                ->leftJoin('eng_part_list as epl', 'epp.part_id', '=', 'epl.id')
                ->where('epp.preventive_id', $preventive_id)
                ->select(DB::raw("epp.part_id, epl.name as part_name, epl.purchase_cost as part_cost, epp.part_quantity as part_number, epl.quantity as part_stock"))
                ->get();                         
        $part_group = array();
        for($p = 0; $p < count($part_data) ; $p++ ) {
            $part_group[$p]['part_id'] = $part_data[$p]->part_id;
            $part_group[$p]['part_name'] = $part_data[$p]->part_name;
            $part_group[$p]['part_cost'] = $part_data[$p]->part_cost;
            $part_group[$p]['part_number'] = $part_data[$p]->part_number;
            $part_group[$p]['part_stock'] = $part_data[$p]->part_stock;
            $part_group[$p]['part_number_original'] = 0;
        }

        return $part_group;
    }
}