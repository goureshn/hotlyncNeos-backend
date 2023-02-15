<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Service\ComplaintDivisionEscalation;
use App\Models\Common\CommonJobrole;


use Excel;
use Response;
use DB;
use Datatables;

class ComplaintDivisionEscalationController extends UploadController
{
   	
    public function index(Request $request)
    {
		$datalist = DB::table('common_division as ci')
						->join('services_complaint_type as sct', function($join) {
							$join->on(function ($q) {
								$q->whereRaw('sct.id > 0');
							});
						})						
						->select(DB::raw('ci.*, sct.id as severity_id, sct.type as severity'));

		return Datatables::of($datalist)
				->addColumn('levels', function ($data) {
					$division_id = $data->id;
					$severity_id = $data->severity_id;
					$list = DB::table('services_complaint_division_escalation')
						->where('division_id', $division_id)
						->where('severity_id', $severity_id)
						->select(DB::raw('GROUP_CONCAT(level) as field'))
						->first();
					
					return $list->field;
				})
				->addColumn('job_roles', function ($data) {
					$division_id = $data->id;
					$severity_id = $data->severity_id;
					$list = DB::table('services_complaint_division_escalation')
						->where('division_id', $division_id)
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
					$division_id = $data->id;
					$severity_id = $data->severity_id;
					$list = DB::table('services_complaint_division_escalation')
						->where('division_id', $division_id)
						->where('severity_id', $severity_id)
						->select(DB::raw('GROUP_CONCAT(max_time) as field'))
						->first();
					
					return $list->field;
				})
				->addColumn('notify_types', function ($data) {
					$division_id = $data->id;
					$severity_id = $data->severity_id;
					$list = DB::table('services_complaint_division_escalation')
						->where('division_id', $division_id)
						->where('severity_id', $severity_id)
						->select(DB::raw('GROUP_CONCAT(notify_type SEPARATOR "/") as field'))
						->first();
					
					return $list->field;
				})
				->rawColumns(['levels', 'job_roles', 'maxtimes', 'notify_types'])
				->make(true);
    }

	public function selectItem(Request $request)
    {
		$division_id = $request->get('division_id', 0);
		$severity_id = $request->get('severity_id', 0);
		
		$list = DB::table('services_complaint_division_escalation as cse')			
			->where('cse.division_id', $division_id)
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
		$division_id = $request->get('division_id', 0);
		$severity_id = $request->get('severity_id', 0);

		$model = ComplaintDivisionEscalation::find($id);

		if( empty($model) )
		{
			$model = new ComplaintDivisionEscalation();
			$model->division_id = $division_id;
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
		
		$model = ComplaintDivisionEscalation::find($id);


		if( !empty($model) )
		{
			$division_id = $model->division_id;
			$severity_id = $model->severity_id;
			$level = $model->level;
		
			$model->delete();	

			$sql = sprintf('UPDATE `services_complaint_division_escalation` SET `level` = `level` - 1 WHERE `division_id` = %d AND `severity_id` = %d AND `level` > %d',
								$division_id, $severity_id, $level);

			DB::select($sql);
		}

		return $this->selectItem($request);
	}
}
