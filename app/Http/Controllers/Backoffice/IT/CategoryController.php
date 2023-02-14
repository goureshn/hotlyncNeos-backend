<?php

namespace App\Http\Controllers\Backoffice\IT;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;
use App\Models\Common\CommonJobrole;
use App\Models\IT\ITCategory;
use App\Models\IT\ITApprovalRoute;


use Excel;
use Response;
use DB;
use Datatables;

class CategoryController extends UploadController
{   
    public function index(Request $request)
    {
		$datalist = DB::table('services_it_category as it')			
					->select(DB::raw('it.*'));
					
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
				->rawColumns(['checkbox', 'edit', 'delete'])				
				->make(true);
    }

	public function store(Request $request)
    {
    	$input = $request->except('id');
		if($input['category'] === null) $input['category'] = '';

		$model = ITCategory::create($input);
		
		return Response::json($model);			
    }


    public function update(Request $request, $id)
    {
		$model = ITCategory::find($id);	
		
        $input = $request->all();
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
        $model = ITCategory::find($id);
		$model->delete();

		return Response::json($model);
    }
	
	private function getLevelList($id)
	{
		$model = ITCategory::find($id);	

		$selected_jobrole = DB::table('services_it_approval_route as ar')
			->leftJoin('common_job_role as jr', 'jr.id', '=', 'ar.job_role_id')
			->where('ar.category_id', $id)
			->where('ar.mode', $model->approval_mode)
			->where('ar.level', '>', 0)
			->select('ar.*', 'jr.job_role')
			->orderBy('ar.level')
			->get();


		return $selected_jobrole;
	}

	public function selectGroup(Request $request)
    {
		$id = $request->get('id', 0);

		return $this->getLevelList($id);		
	}
	
	
	public function updateEscalationInfo(Request $request)
	{
		$id = $request->get('id', 0);
		$category_id = $request->get('category_id', 0);
		$job_role_id = $request->get('job_role_id', 0);
		$level = $request->get('level', 0);

        $category = ITCategory::find($category_id);

		$model = ITApprovalRoute::find($id);

		if( empty($model) )
		{
			$model = new ITApprovalRoute();
			$model->category_id = $category_id;
			$model->mode = $category->approval_mode;
		}

		$model->job_role_id = $job_role_id;
		$model->level = $level;
		$model->save();
		
		return $this->getLevelList($category_id);
	}

	public function deleteEscalationInfo(Request $request)
	{
		$id = $request->get('id', 0);
		
		$model = ITApprovalRoute::find($id);

		$selected_level = [];

		if( !empty($model) )
		{
			$category_id = $model->category_id;			
			$mode = $model->mode;			
			$level = $model->level;
		
			$model->delete();	

			$sql = sprintf("UPDATE `services_it_approval_route` SET `level` = `level` - 1 WHERE `category_id` = %d AND `mode` = '%s' AND `level` > %d",
							$category_id, $mode, $level);

			DB::select($sql);	

			$selected_level = $this->getLevelList($category_id);
		}
		
		return Response::json($selected_level);		
	}
	

}
