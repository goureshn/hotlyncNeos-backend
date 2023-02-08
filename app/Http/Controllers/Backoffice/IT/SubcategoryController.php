<?php

namespace App\Http\Controllers\Backoffice\IT;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Common\CommonJobrole;
use App\Models\IT\ITCategory;
use App\Models\IT\ITSubcategory;
use App\Models\IT\ITApprovalRoute;
use App\Models\IT\ITSubcategoryApprovalRoute;

use Excel;
use Response;
use DB;
use Datatables;

class SubcategoryController extends UploadController
{   
    public function index(Request $request)
    {
		$datalist = DB::table('services_it_subcategory as sub')	
					->join('services_it_category as ca', 'sub.cat_id', '=', 'ca.id')		
					->select(DB::raw('sub.*, ca.category'));
					
		return Datatables::of($datalist)
				->addColumn('checkbox', function ($data) {
					return '<input type="checkbox" class="checkthis" />';
				})
				->addColumn('edit', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"  ng-disabled="job_role!=\'SuperAdmin\'&&edit_flag==0" ng-click="onShowEditRow('.$data->id.')">
						<span class="glyphicon glyphicon-pencil"></span>
					</button></p>';
				})
				->addColumn('delete', function ($data) {
					return '<p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#deleteModal" ng-disabled="job_role!=\'SuperAdmin\'" ng-click="onDeleteRow('.$data->id.')">
						<span class="glyphicon glyphicon-trash"></span>
					</button></p>';
				})				
				->make(true);
	}
	
	public function getApprovalList(Request $request)
    {
		$central_mode = $request->get('central_mode', 1);

		$query = DB::table('services_it_subcategory as sub')
			->join('services_it_category as ca', 'sub.cat_id', '=', 'ca.id');

		if( $central_mode == 1 )
		{
			$datalist = $query
				->where('sub.approval_mode', 'Centralized')	
				->select(DB::raw('sub.*, ca.category, 0 as dept_id, "" as department'));
		}
		else
		{
			$datalist = $query
				->join('common_department as cd', function($join) {
					$join->on(DB::raw('cd.id > 0'),DB::raw(''),DB::raw(''));
				})
				->where('sub.approval_mode', 'Decentralized')	
				->select(DB::raw('sub.*, ca.category, cd.id as dept_id, cd.department'));
		}

		return Datatables::of($datalist)
				->addColumn('levels', function ($data) {
					$subcategory_id = $data->id;					
					$dept_id = $data->dept_id;
					$list = DB::table('services_it_subcategory_approval_route')
						->where('subcategory_id', $subcategory_id)
						->where('dept_id', $dept_id)
						->select(DB::raw('GROUP_CONCAT(level) as field'))
						->first();
					
					return $list->field;
				})
				->addColumn('job_roles', function ($data) {
					$subcategory_id = $data->id;					
					$dept_id = $data->dept_id;
					$list = DB::table('services_it_subcategory_approval_route')
						->where('subcategory_id', $subcategory_id)
						->where('dept_id', $dept_id)
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
				->addColumn('notify_types', function ($data) {
					$subcategory_id = $data->id;					
					$dept_id = $data->dept_id;
					$list = DB::table('services_it_subcategory_approval_route')
						->where('subcategory_id', $subcategory_id)
						->where('dept_id', $dept_id)
						->select(DB::raw('GROUP_CONCAT(notify_type SEPARATOR "/") as field'))
						->first();
					
					return $list->field;
				})				
				->make(true);
    }

	public function store(Request $request)
    {
    	$input = $request->except(['id', 'category']);
		$model = ITSubcategory::create($input);
		
		return Response::json($model);			
    }


    public function update(Request $request, $id)
    {
		$model = ITSubcategory::find($id);	
		
        $input = $request->except(['id', 'category']);
		$model->update($input);
		
		return Response::json($model);	
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $model = ITSubcategory::find($id);
		$model->delete();

		return Response::json($model);
	}	
	
	public function selectItem(Request $request)
    {
		$subcategory_id = $request->get('subcategory_id', 0);
		$dept_id = $request->get('dept_id', 0);
		
		$list = DB::table('services_it_subcategory_approval_route')			
			->where('subcategory_id', $subcategory_id)
			->where('dept_id', $dept_id)
			->select(DB::raw('*'))
			->orderBy('level')
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
		$notify_type = $request->get('notify_type', '');
		$subcategory_id = $request->get('subcategory_id', 0);
		$dept_id = $request->get('dept_id', 0);

		$model = ITSubcategoryApprovalRoute::find($id);

		if( empty($model) )
		{
			$model = new ITSubcategoryApprovalRoute();
			$model->subcategory_id = $subcategory_id;
			$model->dept_id = $dept_id;
		}

		$model->job_role_ids = $job_role_ids;
		$model->level = $level;
		$model->notify_type = $notify_type;

		$model->save();
		
		return $this->selectItem($request);
	}

	public function deleteEscalationInfo(Request $request)
	{
		$id = $request->get('id', 0);
		
		$model = ITSubcategoryApprovalRoute::find($id);


		if( !empty($model) )
		{
			$subcategory_id = $model->subcategory_id;
			$dept_id = $model->dept_id;
			$level = $model->level;
		
			$model->delete();	

			$sql = sprintf('UPDATE `services_it_subcategory_approval_route` SET `level` = `level` - 1 WHERE `subcategory_id` = %d AND `dept_id` = %d AND `level` > %d',
								$subcategory_id, $dept_id, $level);

			DB::select($sql);
		}

		return $this->selectItem($request);
	}
	
}
