<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Models\Service\ComplaintRequest;

class ComplaintSublist extends Model
{
    protected 	$guarded = [];
	protected 	$table = 'services_complaint_sublist';
	public 		$timestamps = true;

	public static function boot()
	{
		static::creating(function ($model) {
			$complaint = ComplaintRequest::find($model->parent_id);
			if( !empty($complaint) )	
				$complaint->touch();			
		});

		static::updating(function ($model) {
			$complaint = ComplaintRequest::find($model->parent_id);
			if( !empty($complaint) )	
				$complaint->touch();	
		});

		static::deleting(function ($model) {
			// bluh bluh
		});

		parent::boot();
	}

	public static function getCompleteInfo(&$datalist) {
		// get complaint ids
		$parent_ids = array();

		foreach($datalist as $row){
			$parent_ids[] = $row->id;
		}

		// find guests
		$query = DB::table('services_complaint_sublist')
				->where('delete_flag',0)
				->whereIn('parent_id', $parent_ids);

		$complete_list = $query->select(DB::raw('parent_id, sum(status = 2) as completed, sum(status != 4) as total'))
				->groupBy('parent_id')
				->get();

		// save guest with guest_id
		$complete_info = array();
		foreach($complete_list as $row){
			$complete_info[$row->parent_id] = $row;
		}

		// set call info by guest_id
		foreach($datalist as $key => $row){
			if( array_key_exists($row->id, $complete_info) )
			{
				$datalist[$key]->completed = $complete_info[$row->id]->completed;
				$datalist[$key]->total = $complete_info[$row->id]->total;
			}
			else
			{
				$datalist[$key]->completed = 0;
				$datalist[$key]->total = 0;
			}
		}
	}

	public static function getMaxSubID($complaint_id) {
		// calc sub max id for that complaint.
		$max_sub_info = DB::table('services_complaint_sublist')
			->where('parent_id', $complaint_id)
			->select(DB::raw('max(sub_id) as max_sub_id'))
			->first();

		$exist_flag = DB::table('services_complaint_sublist')
			->where('parent_id', $complaint_id)
			->exists();
		if( $exist_flag == false )
			return 0;
		
		$sub_max_id = -1;
		if( !empty($max_sub_info) )
			$sub_max_id = $max_sub_info->max_sub_id;

		return $sub_max_id + 1;
	}

	public static function getCompensationList($sub_id)
	{
		$comp_list = DB::table('services_complaint_sublist_compensation as scsc')
			->join('services_compensation as sc', 'scsc.compensation_id', '=', 'sc.id')
			->leftJoin('common_users as cu', 'scsc.sub_provider_id', '=', 'cu.id')
			->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->where('scsc.sub_id', $sub_id)
			->select(DB::raw('scsc.*, sc.compensation, CONCAT_WS(" ", cu.first_name, cu.last_name) as wholename, cd.department'))
			->get();

		return $comp_list;	
	}

	public static function getSubcomplaintDetail($id)
	{
		$sub_complaint = DB::table('services_complaint_sublist as cs')
			->leftJoin('services_complaints as item', 'cs.item_id', '=', 'item.id')
			->leftJoin('common_users as cu1', 'cs.assignee_id', '=', 'cu1.id')				
			->leftJoin('services_complaint_request as sc', 'cs.parent_id', '=', 'sc.id')
			->leftJoin('common_room as cr', 'sc.room_id', '=', 'cr.id')				
			->leftJoin('common_employee as ce', 'sc.requestor_id', '=', 'ce.id')
			->leftJoin('common_users as cu', 'ce.user_id', '=', 'cu.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->leftJoin('common_users as cu2', 'cs.submitter_id', '=', 'cu2.id')
			->leftJoin('common_guest_profile as gp', 'sc.guest_id', '=', 'gp.id')
			->leftJoin('common_country as cc', 'gp.nationality', '=', 'cc.id')
			->leftJoin('services_complaint_type as ct', 'cs.severity', '=', 'ct.id')
			->leftJoin('services_complaint_type as ct1', 'sc.severity', '=', 'ct1.id')
			->leftJoin('common_guest as cg', function($join) {
				$join->on('gp.guest_id', '=', 'cg.guest_id');
				$join->on('sc.property_id', '=', 'cg.property_id');
			})
			->leftJoin('services_complaint_category as scc', 'cs.category_id', '=', 'scc.id')	
			->leftJoin('services_complaint_subcategory as scs', 'cs.subcategory_id', '=', 'scs.id')	
			->leftJoin('services_location as sl', 'cs.location_id', '=', 'sl.id')
			->leftJoin('services_location_type as slt', 'sl.type_id', '=', 'slt.id')
			->leftJoin('common_department as cd', 'cs.dept_id', '=', 'cd.id')
			->leftJoin('common_house_complaints_category as hcc', 'sc.housecomplaint_id', '=', 'hcc.id')
			->leftJoin('common_department as cd1', 'cs.reassigne_dept_id', '=', 'cd1.id')	
			->leftJoin('services_complaint_feedback_source as scfs', 'sc.feedback_source_id', '=', 'scfs.id')
			->leftJoin('services_complaint_feedback_type as scft', 'sc.feedback_type_id', '=', 'scft.id')
			->where('cs.id', $id)
			->where('cs.delete_flag', 0)
			->select(DB::raw("cs.*, sc.id as p_id, sc.guest_id, sc.guest_type, sc.loc_id, sc.path as main_path, sc.compensation_total, sc.comment, sc.comment_highlight, sc.initial_response, sc.response_highlight, item.complaint, gp.guest_name, cr.room, CONCAT_WS(\" \", ce.fname, ce.lname) as wholename, cd.department, CONCAT_WS(\" \", cu1.first_name, cu1.last_name) as assignee_name, jr.job_role, CONCAT_WS(\" \", cu2.first_name, cu2.last_name) as created_by, jr.job_role, cd1.department as reassign_dept, hcc.name as house_complaint_name, 							
							scc.name as category_name, scs.name as subcategory_name,
							sl.name as location_name, slt.type as location_type, scft.name as feedback_type,
							TIMEDIFF(CONCAT(CURDATE(), ' ', CURTIME()), cs.created_at) as age, scfs.name as source, 	DATEDIFF(CURTIME(), cs.created_at) as age_days,
							gp.mobile, gp.phone, gp.email, gp.address, gp.gender, gp.nationality, cc.name as nationality_name, gp.passport, ct.type as severity_name, ct1.type as main_severity_name, cg.arrival, cg.departure, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(sub_id, '0', 'A')
								, '1', 'B')
								, '2', 'C')
								, '3', 'D')
								, '4', 'E')
								, '5', 'F')
								, '6', 'G')
								, '7', 'H')
								, '8', 'I')
								, '9', 'J') as sub_label"))
			->first();			

		return $sub_complaint;	
	}
}