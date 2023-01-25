<?php

namespace App\Http\Controllers\Backoffice\User;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\UploadController;

use Redirect;

use App\Models\Common\Chain;
use App\Models\Common\CommonUser;
use App\Models\Common\Department;
use App\Models\Common\Employee;
use App\Models\Common\PropertySetting;

use App\Models\Service\TaskGroup;
use App\Models\Service\LocationGroup;
use App\Models\Service\ShiftGroupMember;

use Excel;
use DB;
use Datatables;
use Response;
use Redis;
use App\Modules\Functions;


class UserWizardController extends UploadController
{
	/* React functions */

	public function userIndex(Request $request)
	{

		$platform = $request->get('platform');
		$user_id = $request->get('user_id', 0);
		$client_id = $request->get('client_id', 0);
		if ($client_id != 0) {
			$property_ids_by_jobrole = CommonUser::getProertyIdsByClient($client_id);
		}

		$limit = $request->get('limit', 0);
		$offset = $request->get('offset', 0);
		$search = $request->get('searchtext', "");
		$sortColumn = $request->get('sortcolumn', 'cu.id');
		$sortOrder = $request->get('sortorder', 'desc');
		$filter = json_decode($request->get("filter", ""), true);

		$datalist = DB::table('common_users as cu')
			->leftJoin('common_department as cd', 'cu.dept_id', '=', 'cd.id')
			->leftJoin('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
			->leftJoin('common_user_language as cul', 'cu.lang_id', '=', 'cul.id')
			->leftJoin('common_user_group_members as cugm', 'cu.id', '=', 'cugm.user_id')
			->leftJoin('common_user_group as cg', 'cugm.group_id', '=', 'cg.id')
			->groupBy('cu.id');

		// if ($status == 'Active')
		// 	$datalist->where('cu.deleted', 0);

		// if ($status == 'Disabled')
		// 	$datalist->where('cu.deleted', 1);

		if (!empty($filter)) {
			if (!empty($filter["status"])) {
				$datalist->whereIn('cu.deleted', $filter["status"]);
			}
		}

		if (!empty($sortColumn) && !empty($sortOrder)) {
			$datalist->orderBy($sortColumn, $sortOrder);
		}

		if (!empty($search)) {
			$datalist->where('cu.id', 'like', '%' . $search . '%')
				->orWhere('cu.first_name', 'like', '%' . $search . '%')
				->orWhere('cu.last_name', 'like', '%' . $search . '%')
				->orWhere('cu.username', 'like', '%' . $search . '%')
				->orWhere('cul.language', 'like', '%' . $search . '%')
				->orWhere('jr.job_role', 'like', '%' . $search . '%')
				->orWhere('cu.ivr_password', 'like', '%' . $search . '%')
				->orWhere('cd.department', 'like', '%' . $search . '%')
				->orWhere('cu.mobile', 'like', '%' . $search . '%')
				->orWhere('cu.email', 'like', '%' . $search . '%');
			// ->orWhere('cu.deleted', 'like', '%' . $search . '%')
			// ->orWhere('cu.active_status', 'like', '%' . $search . '%');
		}

		if ($client_id != 0) {
			$datalist->whereIn('cd.property_id', $property_ids_by_jobrole);
		}

		$total = count($datalist->get());

		if ($limit != 0) {
			$datalist->take($limit);
		}
		if ($offset != 0) {
			$datalist->skip($offset);
		}

		$users = $datalist->select(DB::raw('cu.*,IFNULL( cul.language, "English") as language,
			cd.department,cd.property_id,jr.job_role,
			GROUP_CONCAT(cg.name) as usergroup
			'))
			->get();

		foreach ($users as $key => $val) {

			// Building
			$ids = $val->building_ids;
			$list = DB::table('common_building')
				->whereRaw("FIND_IN_SET(id, '$ids')")
				->select(DB::raw('GROUP_CONCAT(name) as field'))
				->first();

			$val->cbname = $list->field;

			// Shift Group
			$user_id = $val->id;
			$group_data = DB::table('services_shift_group_members as sgm')
				->leftJoin('services_shift_group as cg', 'sgm.shift_group_id', '=', 'cg.id')
				->where('sgm.user_id', $user_id)
				->select(DB::raw('cg.*'))
				->first();
			$shiftgroup = '';
			if (!empty($group_data))
				$shiftgroup = $group_data->name;

			$val->shiftgroup = $shiftgroup;

			// Disable Flag
			$val->disabled_label = $val->deleted ? 'Yes' : 'No';

			// Online Flag
			$val->online_label = $val->active_status ? 'Yes' : 'No';
		}

		return Response::json(['data' => $users, 'recordsFiltered' => $total]);
	}

	/* React functions Ends */
}
