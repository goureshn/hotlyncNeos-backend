<?php

namespace App\Models\Eng;

use Illuminate\Database\Eloquent\Model;
use App\Models\Eng\PreventiveList;
use App\Models\Common\PropertySetting;
use DB;

class EquipmentPreventiveEquipStatus extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'eng_preventive_equip_status';
    public 		$timestamps = false;

    static function updateNextDate($workorder)
    {
        if( empty($workorder) )
            return;

        if( $workorder->request_flag != 3 )    // not preventive
            return;

        $property_id = $workorder->property_id;    
        $preventive_id = $workorder->request_id;    
        $equip_id = $workorder->equipment_id;
        $preventive = PreventiveList::find($preventive_id);

        if( empty($preventive) )
            return;

        $equip_status = EquipmentPreventiveEquipStatus::where('preventive_id', $preventive_id)
                                        ->where('equip_id', $equip_id)
                                        ->first();      
        if( empty($equip_status))                                        
        {
            $equip_status = new EquipmentPreventiveEquipStatus();
            $equip_status->preventive_id = $preventive_id;
            $equip_status->equip_id = $equip_id;
        }

        $equip_status->last_date = date('Y-m-d', strtotime($workorder->end_date));

        $start_mode = $preventive->start_mode;

        $due_date = date('Y-m-d', strtotime("$preventive->frequency $preventive->frequency_unit", strtotime($workorder->end_date)));
        
        if( $start_mode == 'Due Date' )
        {            
            $equip_status->next_date = $due_date;
        }   
        
        if( $start_mode == 'Beginning of Week' )
        {
            $rules = array();
            $rules['preventive_week_start'] = 0;        
            $rules = PropertySetting::getPropertySettings($property_id, $rules);

            $this_week_start = date('Y-m-d', strtotime('Last Saturday', strtotime($due_date)));
            $plus_dates = 1 + $rules['preventive_week_start'];
            $this_week_start = date('Y-m-d', strtotime("$plus_dates days", strtotime($this_week_start)));
            $equip_status->next_date = $this_week_start;
        }
        
        if( $start_mode == 'Beginning of Month' )
        {
            $this_month_start = date('Y-m-01', strtotime($due_date)); 
            $equip_status->next_date = $this_month_start;
        }

        $equip_status->save();

        return $equip_status;
    }
}