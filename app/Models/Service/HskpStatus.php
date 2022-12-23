<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;

class HskpStatus extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'services_hskp_status';
	public 		$timestamps = false;
	
	protected $typelist = [
        '1' => 'Supervisor',
        '2' => 'System',
		'3' => 'Attendant'		
    ];
	
	public function getTypeList()
    {
        return $this->typelist;
    }

    public static function getHskpStatusIDs($property_id) {
    	$ret = array();

    	$vacant_dirty = DB::table('services_hskp_status as hs')
            ->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
            ->where('cb.property_id', $property_id)
            ->where('hs.status', 'Vacant Dirty')
            ->select(DB::raw('hs.*'))
            ->first();

        $occupied_dirty = DB::table('services_hskp_status as hs')
            ->join('common_building as cb', 'hs.bldg_id', '=', 'cb.id')
            ->where('cb.property_id', $property_id)
            ->where('hs.status', 'Occupied Dirty')
            ->select(DB::raw('hs.*'))
            ->first();

        if( !empty($vacant_dirty) )
	        $ret[0] = $vacant_dirty->id;
	    else
	    	$ret[0] = 0;

	    if( !empty($occupied_dirty) )
	        $ret[1] = $occupied_dirty->id;
	    else
	    	$ret[1] = 0;

        return $ret;	    
    }
}