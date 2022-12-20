<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Response;

class FrontendController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getFavouriteMenus(Request $request)
    {
        $module_name = $request->get('module_link', "");
        $user_id = $request->get('user_id', 0);
        if ($module_name == "") {
            $menu = DB::table('common_favourite_menus as fm')
                ->where('user_id', $user_id)
                ->select(DB::raw('fm.*'))
                ->get();
        } else {
            $menu = DB::table('common_favourite_menus as fm')
                ->where('fm.module_link', 'LIKE', $module_name)
                ->where('user_id', $user_id)
                ->select(DB::raw('fm.*'))
                ->get();
        }


        $ret = array();
        $ret['list'] = $menu;
        return Response::json($ret);

    }
}
