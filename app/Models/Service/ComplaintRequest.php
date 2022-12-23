<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;

class ComplaintRequest extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_request';
	public 		$timestamps = true;

	public static function getSubDeptList($complaint_id) {
		$sql = 'Select * From common_department as cd Where exists(Select 1 FROM services_complaint_sublist as scs WHERE scs.parent_id = ' . $complaint_id . ' and cd.id = scs.dept_id)';
		$dept_list = DB::select($sql);						
			
		return $dept_list;	
	}
	
	public static function deptList($complaint_id) {
		$deptlist = array();
		$complaint = DB::table('services_complaint_request as scr')
			->select(DB::raw('scr.send_flag, scr.sent_ids'))
			->where('scr.id', $complaint_id)
			->first();
		if($complaint->send_flag ==1)
			$depts = explode(",", $complaint->sent_ids);
		else
			$depts=0;

		$i=0;
		if($depts)
		{
			foreach ($depts as $id)
			{
				$deptlist[$i++]=DB::table('common_department as cd')
									->select(DB::raw('cd.*'))
									->where('cd.id', $id)
									->first();
			}
		}

		 
		return $deptlist;
	}

	public static function updateSubcompTotal($id)
	{
		$complaint = ComplaintRequest::find($id);

		if( empty($complaint) )
			return;

		$sub_compen = DB::table('services_complaint_sublist_compensation as scsc')
			->join('services_complaint_sublist as scs', 'scsc.sub_id', '=', 'scs.id')
			->where('scs.parent_id', $id)
			->where('delete_flag',0)
			->select(DB::raw('sum(scsc.cost)  as total'))
			->first(); 

		if( empty($sub_compen->total) )	
			$complaint->subcomp_total = 0;	
		else
			$complaint->subcomp_total = $sub_compen->total;


		$complaint->save();
	}

	public static function updateSubcompTotalForAll()
	{
		$list = ComplaintRequest::all();

		foreach($list as $complaint)
		{
			$sub_compen = DB::table('services_complaint_sublist_compensation as scsc')
				->join('services_complaint_sublist as scs', 'scsc.sub_id', '=', 'scs.id')
				->where('scs.parent_id', $complaint->id)
				->select(DB::raw('sum(scsc.cost)  as total'))
				->first(); 

			if( empty($sub_compen->total) )	
				$complaint->subcomp_total = 0;	
			else
				$complaint->subcomp_total = $sub_compen->total;

			$complaint->save();
		}

		return count($list);
	}
}