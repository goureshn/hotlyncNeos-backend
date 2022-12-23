<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;
use DB;

class CommonUserGroup extends Model
{
    protected 	$guarded = [];
    protected 	$table = 'common_user_group';
    public 		$timestamps = false;

    public static function getCI() {
		$user = DB::table('common_user_group as cug')
			->where('cug.check_in_notify', 'Y')
			->select(DB::raw('cug.*'))
			->get();
		if(!empty($user)) return $user;
		else return false;
	}
	public static function getNoPost() {
		$user = DB::table('common_user_group as cug')
			->where('cug.no_post', 'Y')
			->select(DB::raw('cug.*'))
			->get();
		if(!empty($user)) return $user;
		else return false;
    }
     public static function getCO() {
		$user = DB::table('common_user_group as cug')
			->where('cug.check_out_notify', 'Y')
			->select(DB::raw('cug.*'))
			->get();
		if(!empty($user)) return $user;
		else return false;
	}

	 public static function getRC() {
		$user = DB::table('common_user_group as cug')
			->where('cug.room_change', 'Y')
			->select(DB::raw('cug.*'))
			->get();
		if(!empty($user)) return $user;
		else return false;
	}
	public static function getComplaintNotify(){
		$user = DB::table('common_user_group as cug')
			->where('cug.complaint_notify', 'Y')
			->select(DB::raw('cug.*'))
			->get();
		if(!empty($user)) return $user;
		else return false;
	}

	public static function getSubComplaintNotifyUserGroup(){
		$user_group_list = DB::table('common_user_group as cug')
			->where('cug.subcomplaint_notify', 'Y')
			->select(DB::raw('cug.*'))
			->get();
		
		return $user_group_list;
	}
}