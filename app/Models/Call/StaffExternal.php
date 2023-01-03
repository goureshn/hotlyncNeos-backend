<?php

namespace App\Models\Call;

use App\Models\Common\CommonUser;
use Illuminate\Database\Eloquent\Model;
use DB;

class StaffExternal extends Model 
{
    protected 	$guarded = [];
	protected 	$table = 'call_staff_extn';
	public 		$timestamps = false;
	
	public function section()
    {
		return $this->belongsTo(Section::class, 'section_id');
    }	
	
	public function user()
    {
		return $this->belongsTo(CommonUser::class, 'user_id');
    }

	public static function getMyextIds($user_id) {
		$user_group = DB::table('common_user_group_members as ugm')
				->where('ugm.user_id', $user_id)
				->get();

		$group_ids = array();
		foreach($user_group as $row) {
			$group_ids[] = $row->group_id;
		}
		
		$ext_list = StaffExternal::where('bc_flag', 0)
				->where(function ($query) use ($user_id, $group_ids) {
					$query->where('user_id', $user_id)
							->orWhereIn('user_group_id', $group_ids);
				})
				->select(DB::raw('id'))
				->get()->pluck('id');
				
		return $ext_list;
	}

	public static function getExtIdsInDept($dept_id) {
		$ext_list = DB::table('call_staff_extn as se')
						->join('call_section as cs', 'se.section_id', '=', 'cs.id')
						->where('cs.dept_id', $dept_id)
						->select(DB::raw('se.id'))
						->get();

		$ids = [];
		foreach($ext_list as $row) {
			$ids[] = $row->id;
		}

		return $ids;

	}

}