<?php

namespace App\Models\IVR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class IVRAgentStatus extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'ivr_agent_status_log';
	public 		$timestamps = false;

	public static function boot()
	{
		static::creating(function ($model) {
		});

		static::updating(function ($model) {
		});

		static::deleting(function ($model) {
			// bluh bluh
		});

		parent::boot();
	}

	public static function getAvailbleAgentList($property_id, $skill_group_id, $old_user_id)
	{
		// find Agent
		$query = DB::table('ivr_agent_status_log as asl')
				->leftJoin('call_center_skill as cs', 'cs.id', '=', 'asl.skill_id')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->leftJoin('ivr_agent_skill_level as sl', 'asl.id', '=', 'sl.agent_id')
				->where('asl.status', AVAILABLE)
				->where('cd.property_id', $property_id);

		if( $old_user_id > 0 )
			$query->where('asl.user_id','!=',$old_user_id);		

		$skill_group = DB::table('ivr_call_center_skill_group')
				->where('id', $skill_group_id)
				->first();

		if( !empty($skill_group) && !empty($skill_group->skill_ids) )
		{ 		
			$skill_ids = explode(",", $skill_group->skill_ids);
			$query->whereIn('sl.skill_id', $skill_ids);
		}
		
		$agentlist = $query
				->groupBy('asl.id')
				->orderBy('asl.created_at', 'asc')
				->orderBy('total_level', 'desc')
				->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cd.property_id, SUM(sl.level) as total_level'))
				->take(1)
				->get();

		return $agentlist;
	}
	public static function  checkAvailbleAgent($property_id, $skill_group_id)
	{
		$query = DB::table('ivr_agent_status_log as asl')
				//->leftJoin('call_center_skill as cs', 'cs.id', '=', 'asl.skill_id')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->leftJoin('ivr_agent_skill_level as sl', 'asl.id', '=', 'sl.agent_id')
				->whereIn('asl.status', array(BUSY, AVAILABLE, OUTGOING, WRAPUP))
				->where('cd.property_id', $property_id);

		$skill_group = DB::table('ivr_call_center_skill_group')
				->where('id', $skill_group_id)
				->first();

		if( !empty($skill_group) && !empty($skill_group->skill_ids) )
		{ 		
			$skill_ids = explode(",", $skill_group->skill_ids);
			$query->whereIn('sl.skill_id', $skill_ids);
		}
		
		$agentlist = $query
				->groupBy('asl.id')
				->orderBy('asl.created_at', 'asc')
				->orderBy('total_level', 'desc')
				->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cd.property_id, SUM(sl.level) as total_level'))
				->take(1)
				->get();

		return $agentlist;
		
	}
	public static function getAvailbleAgentListWithLock($property_id, $skill_group_id, $old_user_id)
	{
		// find Agent
		$query = DB::table('ivr_agent_status_log as asl')
				->leftJoin('call_center_skill as cs', 'cs.id', '=', 'asl.skill_id')
				->leftJoin('common_users as cu', 'asl.user_id', '=', 'cu.id')
				->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
				->leftJoin('ivr_agent_skill_level as sl', 'asl.id', '=', 'sl.agent_id')
				->where('asl.status', AVAILABLE)
				->where('cd.property_id', $property_id);

		if( $old_user_id > 0 )
			$query->where('asl.user_id','!=',$old_user_id);		

		$skill_group = DB::table('ivr_call_center_skill_group')
				->where('id', $skill_group_id)
				->first();

		if( !empty($skill_group) && !empty($skill_group->skill_ids) )
		{ 		
			$skill_ids = explode(",", $skill_group->skill_ids);
			$query->whereIn('sl.skill_id', $skill_ids);
		}
		
		$agentlist = $query
				->groupBy('asl.id')
				->orderBy('total_level', 'desc')
				->select(DB::raw('asl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cd.property_id, SUM(sl.level) as total_level'))
				->take(1)
				->lockForUpdate()
				->get();

		return $agentlist;
	}
}
