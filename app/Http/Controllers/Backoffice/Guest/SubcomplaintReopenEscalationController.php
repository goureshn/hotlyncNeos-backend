<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Service\ComplaintSublistReopenEscalation;
use App\Models\Common\CommonJobrole;


use Excel;
use Response;
use DB;
use Datatables;

class SubcomplaintReopenEscalationController extends UploadController
{
   	
    public function index(Request $request)
    {
		$datalist = DB::table('services_complaint_type as sct')													
					->select(DB::raw('sct.id, sct.id as severity_id, sct.type as severity'));
					
		return Datatables::of($datalist)
				->addColumn('levels', function ($data) {
					$severity_id = $data->severity_id;
					$list = DB::table('services_complaint_sublist_reopen_escalation')						
						->where('severity_id', $severity_id)
						->select(DB::raw('GROUP_CONCAT(level) as field'))
						->first();
					
					return $list->field;
				})
				->addColumn('job_roles', function ($data) {
					$severity_id = $data->severity_id;
					$list = DB::table('services_complaint_sublist_reopen_escalation')						
						->where('severity_id', $severity_id)
						->select(DB::raw('job_role_ids'))
						->get();

					$job_role_name_list = [];
					foreach($list as $row)
					{
						$job_role_ids = explode(',', $row->job_role_ids);
						$job_role_names = DB::table('common_job_role')
							->whereIn('id', $job_role_ids)
							->select(DB::raw('GROUP_CONCAT(job_role) as field'))
							->first();

						$job_role_name_list[] = $job_role_names->field;
					}	
					
					return implode('/', $job_role_name_list);
				})
				->addColumn('maxtimes', function ($data) {					
					$severity_id = $data->severity_id;
					$list = DB::table('services_complaint_sublist_reopen_escalation')						
						->where('severity_id', $severity_id)
						->select(DB::raw('GROUP_CONCAT(max_time) as field'))
						->first();
					
					return $list->field;
				})
				->addColumn('notify_types', function ($data) {					
					$severity_id = $data->severity_id;
					$list = DB::table('services_complaint_sublist_reopen_escalation')						
						->where('severity_id', $severity_id)
						->select(DB::raw('GROUP_CONCAT(notify_type SEPARATOR "/") as field'))
						->first();
					
					return $list->field;
				})
				->make(true);
    }

	public function selectItem(Request $request)
    {		
		$severity_id = $request->get('severity_id', 0);
		
		$list = DB::table('services_complaint_sublist_reopen_escalation as cse')						
			->where('cse.severity_id', $severity_id)
			// ->where('cse.level', '>', 0)
			->select(DB::raw('cse.*'))
			->orderBy('cse.level')
			->get();

		foreach($list as $row)
		{
			$job_role_ids = explode(',', $row->job_role_ids);
			$row->job_role_list = DB::table('common_job_role')
				->whereIn('id', $job_role_ids)
				->select(DB::raw('id, job_role'))
				->get();	
			
			$row->notify_type_list = explode(',', $row->notify_type);			
		}	
		
		return Response::json($list);		
	}
	
	public function updateEscalationInfo(Request $request)
	{
		$id = $request->get('id', 0);
		$job_role_ids = $request->get('job_role_ids', '');
		$level = $request->get('level', 0);
		$max_time = $request->get('max_time', 0);
		$notify_type = $request->get('notify_type', '');		
		$severity_id = $request->get('severity_id', 0);

		$model = ComplaintSublistReopenEscalation::find($id);

		if( empty($model) )
		{
			$model = new ComplaintSublistReopenEscalation();			
			$model->severity_id = $severity_id;
		}

		$model->job_role_ids = $job_role_ids;
		$model->level = $level;
		$model->max_time = $max_time;
		$model->notify_type = $notify_type;

		$model->save();
		
		return $this->selectItem($request);
	}

	public function deleteEscalationInfo(Request $request)
	{
		$id = $request->get('id', 0);
		
		$model = ComplaintSublistReopenEscalation::find($id);

		if( !empty($model) )
		{
			$type_id = $model->type_id;
			$severity_id = $model->severity_id;
			$level = $model->level;
		
			$model->delete();	

			$sql = sprintf('UPDATE `services_complaint_sublist_reopen_escalation` SET `level` = `level` - 1 WHERE `severity_id` = %d AND `level` > %d',
								$severity_id, $level);

			DB::select($sql);
		}

		return $this->selectItem($request);
	}
}
