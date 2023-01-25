<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Common\PropertySetting;
use App\Modules\Functions;
use Response;

class FrontendController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index(Request $request)
    {
        try {
            DB::connection()->getPdo();
            if (DB::connection()->getDatabaseName()) {
                //echo "Yes! Successfully connected to the DB: " . DB::connection()->getDatabaseName();
            } else {
                return view('frontend.db_install');
            }
        } catch (\Exception $e) {
            //die("Could not open connection to database server.  Please check your configuration.");
            return view('frontend.db_install');
        }

        $prod = $request->get('prod', 0);

        $config = PropertySetting::getServerConfig(0);

        $app_config = array();

        $app_config['site_url'] = Functions::getSiteURL();

        if (strpos($app_config['site_url'], $config['public_domain']) !== false)
            $app_config['live_server'] = $config['public_live_host'];
        else
            $app_config['live_server'] = $config['live_host'];

        $app_config['client_ip'] = Functions::get_client_ip();
        if (config('app.debug') == false || $prod == 1)
            return view('frontend.index_prod', compact('app_config'));
        else
            return view('frontend.index', compact('app_config'));
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
