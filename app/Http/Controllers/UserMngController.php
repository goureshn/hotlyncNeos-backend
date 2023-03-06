<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Common\CommonUser;
use App\Models\Common\UserGroup;
use DB;

use Response;

class UserMngController extends Controller
{
    private $request;
	
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
	
    public function getManagerList()
    {
		$users = DB::table('common_users as u')
            ->join('common_user_group_members as ugm', 'u.id', '=', 'ugm.user_id')
            ->join('common_user_group as ug', 'ugm.group_id', '=', 'ug.id')
			->where('ug.access_level', 'like', '%Manager%')
            ->select('u.id', 'u.username as name')
            ->get();
	
		return Response::json($users);
    }
    
	public function getList()
    {
		$model = CommonUser::all();
	
		return Response::json($model);
    }
	
	public function getGroupList()
    {
		$model = UserGroup::all();
	
		return Response::json($model);
    }
	
}
