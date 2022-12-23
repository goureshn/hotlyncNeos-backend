<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
class VIPCodes extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'common_vip_codes';
	public 		$timestamps = false;
	
	
     static function getcodes($vip_list)
     {

         $data = DB::table('common_vip_codes as cvc')
				 //->whereRaw("sr.begin_date_time <='" . $begin_date_time . "' AND sr.end_date_time >= '" . $begin_date_time . "'")
				// whereRaw("DATE(created_at) = '" . $cur_date . "'")
	 				->whereIn('cvc.id', $vip_list)
	 				->select(DB::raw('cvc.vip_code'))
                     ->get();
                     

				$vip_codes=[];
			foreach($data as $row)
            $vip_codes[] = $row->vip_code;
            
            if( empty($data) )
	 		return 0;

	 	return $vip_codes;
	 }
	  static function getVIPname($vip)
     {

         $data = DB::table('common_vip_codes as cvc')
				 //->whereRaw("sr.begin_date_time <='" . $begin_date_time . "' AND sr.end_date_time >= '" . $begin_date_time . "'")
				// whereRaw("DATE(created_at) = '" . $cur_date . "'")
	 				->where('cvc.vip_code', $vip)
	 				->select(DB::raw('cvc.name'))
                     ->first();
                     

			// 	$vip_codes=[];
			// foreach($data as $row)
            // $vip_codes[] = $row->vip_code;
            
            if( empty($data) )
	 		return 0;

	 	return $data->name;
     }
 }
