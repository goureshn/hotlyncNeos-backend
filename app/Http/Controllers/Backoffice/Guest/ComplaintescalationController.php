<?php

namespace App\Http\Controllers\Backoffice\Guest;

use App\Models\Service\ComplaintMainStatus;
use App\Models\Service\ComplaintMainEscalation;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;


use Excel;
use DB;
use Datatables;
use Response;

class ComplaintescalationController extends UploadController
{
 

    public function index(Request $request)
    {
        $datalist = DB::table('services_complaint_status as scs')	
					->select(DB::raw('scs.*'));
					
		return Datatables::of($datalist)
				->addColumn('levels', function ($data) {
					$status = $data->status;
					$list = DB::table('services_complaint_main_escalation')
						->where('status', $status)
						->select(DB::raw('GROUP_CONCAT(level) as field'))
						->first();
					
					return $list->field;
				})
				->addColumn('job_roles', function ($data) {
					$status = $data->status;
					$list = DB::table('services_complaint_main_escalation')
						->where('status', $status)
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
					$status = $data->status;
					$list = DB::table('services_complaint_main_escalation')
						->where('status', $status)
						->select(DB::raw('GROUP_CONCAT(max_time) as field'))
						->first();
					
					return $list->field;
				})
				->addColumn('notify_types', function ($data) {
					$status = $data->status;					
					$list = DB::table('services_complaint_main_escalation')
						->where('status', $status)
						->select(DB::raw('GROUP_CONCAT(notify_type SEPARATOR "/") as field'))
						->first();
					
					return $list->field;
                })
                ->addColumn('edit', function ($data) {
                    return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="job_role!=\'SuperAdmin\'&&edit_flag==0" ng-click="onShowEditRow('.$data->id.')">
							<span class="glyphicon glyphicon-pencil"></span>
						</button></p>';
                })
				->rawColumns(['levels', 'job_roles', 'maxtimes', 'notify_types', 'edit'])
                ->make(true);                
    }
    
    public function update(Request $request, $id)
    {
        $model = ComplaintMainStatus::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "Complaint Escalation  does not exist.";
            return back()->with('error', $message)->withInput();
        }

        $max_time = $request->get('max_time', 100);
        $model->max_time = $max_time;
        $model->save();

        return Response::json($model);
    }

    public function selectItem(Request $request)
    {
		$status = $request->get('status', '');
		
		$list = DB::table('services_complaint_main_escalation as scme')			
			->where('scme.status', $status)
			->where('scme.level', '>', 0)
			->select(DB::raw('scme.*'))
			->orderBy('scme.level')
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
		$status = $request->get('status', '');

		$model = ComplaintMainEscalation::find($id);

		if( empty($model) )
		{
			$model = new ComplaintMainEscalation();
			$model->status = $status;
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
		$status = $request->get('status', '');
		
		$model = ComplaintMainEscalation::find($id);

		if( !empty($model) )
		{
			$status = $model->status;			
			$level = $model->level;
		
			$model->delete();	

			$sql = sprintf('UPDATE `services_complaint_main_escalation` SET `level` = `level` - 1 WHERE `status` = %d AND `level` > %d',
								$status, $level);

			DB::select($sql);
		}

		return $this->selectItem($request);
	}
}
