<?php

namespace App\Http\Controllers;

use App;
use App\Models\Common\CommonUser;
use App\Models\Common\PropertySetting;
use App\Modules\Functions;
use Artisan;
use DB;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Redirect;
use Response;
use View;

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

    public function db_install(Request $request)
    {
        //echo base64_encode(base64_encode("root"));
        //echo base64_encode(base64_encode("123456"));
        set_time_limit(0);
        ini_set('memory_limit', '20000M');
        $db_conf = $request->all();
        $values = $db_conf;
        $host_name = $db_conf["DB_HOST"];
        $user = $db_conf["DB_USERNAME"];
        $values['DB_USERNAME'] = base64_encode(base64_encode($db_conf["DB_USERNAME"]));
        $pass = $db_conf["DB_PASSWORD"];
        $values["DB_PASSWORD"] = base64_encode(base64_encode($db_conf["DB_PASSWORD"]));
        $db = $db_conf["DB_DATABASE"];
        $interface_db = $db_conf['DB_INTERFACE_DATABASE'];

        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);
        $str = substr($str, 0, -1);
        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {
                if (trim($envValue) == "" || $envValue === NULL)
                    continue;
                $str .= "\n"; // In case the searched variable is in the last line without \n
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                // If key does not exist, add it
                if (!$keyPosition || !$endOfLinePosition || !$oldLine) {
                    $str .= "{$envKey}={$envValue}\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                }

            }
        }

        if (!file_put_contents($envFile, $str)) {
            return "failed";
        }

        app('db')->purge(DB::connection()->getName());
        // Artisan::call('cache:clear');
        // Artisan::call('config:clear');

        // $db_file = base_path() . '/database/db_backup/ennovatech.sql';
        // $db_interface_file = base_path() . '/database/db_backup/ennovatech_interface.sql';
        // //DB::unprepared(file_get_contents($db_file));

        // if(trim($host_name) != "" &&  $host_name !== NULL && trim($db) != "" &&  $db !== NULL) {

        //     try {
        //         $dbh = new \PDO("mysql:host=".$host_name, $user, $pass);
        //         $dbh->exec("use mysql;");
        //         $dbh->exec("CREATE DATABASE $db;");
        //         $dbh->exec("use $db;");

        //         $sql_command = "mysql -h $host_name --user=$user --password=$pass --database=$db < $db_file";
        //         exec($sql_command);

        //         // echo $sql_command ."<br>";
        //         // $this->importSqlFile($dbh , $db_file);

        //     } catch (\PDOException $e) {
        //         die("DB ERROR: " . $e->getMessage());
        //     }

        // }
        // if(trim($host_name) != "" &&  $host_name !== NULL && trim($interface_db) != "" &&  $interface_db !== NULL) {

        //     try {
        //         $dbh = new \PDO("mysql:host=".$host_name, $user, $pass);
        //         $dbh->exec("use mysql;");
        //         $dbh->exec("CREATE DATABASE $interface_db;");
        //         $dbh->exec("use $interface_db;");

        //         $sql_command = "mysql -h $host_name --user=$user --password=$pass --database=$interface_db < $db_interface_file";
        //         exec($sql_command);

        //         // echo $sql_command ."<br>";
        //         // $this->importSqlFile($dbh , $db_interface_file);
        //     } catch (\PDOException $e) {
        //         die("DB ERROR: " . $e->getMessage());
        //     }
        // }

        return "Success";
        /*  try {
              //$sql_dump = File::get($db_file);
              //DB::connection()->getPdo()->exec($sql_dump);
              //DB::unprepared(file_get_contents('full/path/to/dump.sql'));
              if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                  $cmd = "mysqldump --opt -h -u".$user." -p".$pass." ".$db." < ".$db_file;
                  exec($cmd);
                  $cmd = "mysqldump --user=".$user." --password=".$pass." ".$interface_db." < ".$db_interface_file;
                  exec($cmd);
              } else {
                  $cmd = "mysqldump --user=".$user." --password=".$pass." ".$db." < ".$db_file;
                  exec($cmd);
                  $cmd = "mysqldump --user=".$user." --password=".$pass." ".$interface_db." < ".$db_interface_file;
                  exec($cmd);
              }

          } catch (\PDOException $e) {
              die("DB ERROR: " . $e->getMessage());
          }
          return "success";*/


    }

    public function guest(Request $request)
    {
        $config = PropertySetting::getServerConfig(0);

        $app_config = array();

        $app_config['site_url'] = Functions::getSiteURL();

        if (strpos($app_config['site_url'], $config['public_domain']) !== false)
            $app_config['live_server'] = $config['public_live_host'];
        else
            $app_config['live_server'] = $config['live_host'];

        $app_config['client_ip'] = Functions::get_client_ip();

        return view('frontend.guest', compact('app_config'));
    }

    public function guestSimulator(Request $request)
    {
        $config = PropertySetting::getServerConfig(0);

        $app_config = array();

        $app_config['site_url'] = Functions::getSiteURL();

        if (strpos($app_config['site_url'], $config['public_domain']) !== false)
            $app_config['live_server'] = $config['public_live_host'];
        else
            $app_config['live_server'] = $config['live_host'];

        $app_config['client_ip'] = Functions::get_client_ip();

        return view('frontend.guest_simulator', compact('app_config'));
    }

    public function briefing(Request $request)
    {
        $config = PropertySetting::getServerConfig(0);

        $app_config = array();

        $app_config['site_url'] = Functions::getSiteURL();

        if (strpos($app_config['site_url'], $config['public_domain']) !== false)
            $app_config['live_server'] = $config['public_live_host'];
        else
            $app_config['live_server'] = $config['live_host'];

        $app_config['client_ip'] = Functions::get_client_ip();

        return view('frontend.briefing', compact('app_config'));
    }

    public function briefingmng(Request $request)
    {
        $config = PropertySetting::getServerConfig(0);

        $app_config = array();

        $app_config['site_url'] = Functions::getSiteURL();

        if (strpos($app_config['site_url'], $config['public_domain']) !== false)
            $app_config['live_server'] = $config['public_live_host'];
        else
            $app_config['live_server'] = $config['live_host'];

        $app_config['client_ip'] = Functions::get_client_ip();

        return view('frontend.briefingmng', compact('app_config'));
    }

    public function facilities(Request $request)
    {
        $config = PropertySetting::getServerConfig(0);

        $app_config = array();

        $app_config['site_url'] = Functions::getSiteURL();

        if (strpos($app_config['site_url'], $config['public_domain']) !== false)
            $app_config['live_server'] = $config['public_live_host'];
        else
            $app_config['live_server'] = $config['live_host'];

        $app_config['client_ip'] = Functions::get_client_ip();

        return view('frontend.facilities', compact('app_config'));
    }

    public function getNotificationCount(Request $request)
    {
        $property_id = $request->get('property_id', 0);

        $count = DB::table('common_notification')
            ->where('property_id', $property_id)
            ->where('unread_flag', 1)
            ->count();

        return Response::json($count);
    }

    public function getNotificationList(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $property_id = $request->get('property_id', 0);
        $dept_id = CommonUser::getDeptID($user_id, Config::get('constants.GUESTSERVICE_DEPT_NOTIFY'));


        $user = DB::table('common_users as cu')
            ->join('common_department as cd', 'cu.dept_id', '=', 'cd.id')
            ->join('common_job_role as jr', 'cu.job_role_id', '=', 'jr.id')
            ->where('cu.id', $user_id)
            ->select(DB::raw('cu.*, jr.permission_group_id'))
            ->first();

        $ret = array();

        if (empty($user)) {
            return Response::json($ret);
        }
        if ($dept_id != 0) {
            if ($dept_id != $user->dept_id)
                return;
        }

        $permission = DB::table('common_permission_members as pm')
            ->join('common_page_route as pr', 'pm.page_route_id', '=', 'pr.id')
            ->where('pm.perm_group_id', $user->permission_group_id)
            ->select(DB::raw('pr.name'))
            ->get();

        $value_array = array();
        if (!empty($permission)) {
            foreach ($permission as $row)
                $value_array[] = $row->name;
        }


        $notifylist = DB::table('common_notification')
            ->where('property_id', $property_id)
            ->whereIn('type', $value_array)
            ->where('id', '>', $user->max_read_no)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $user = CommonUser::find($user_id);
        $user->unread = 0;
        $user->save();

        return Response::json($notifylist);
    }

    public function clearNotificationList(Request $request)
    {
        $user_id = $request->get('user_id', 0);
        $max_read_no = $request->get('max_read_no', 0);

        $user = CommonUser::find($user_id);
        if (!empty($user)) {
            $user->max_read_no = $max_read_no;
            $user->save();
        }

        return Response::json($user);
    }

    public function importSqlFile($pdo, $sqlFile, $tablePrefix = null, $InFilePath = null)
    {
        try {

            // Enable LOAD LOCAL INFILE
            $pdo->setAttribute(\PDO::MYSQL_ATTR_LOCAL_INFILE, true);

            $errorDetect = false;

            // Temporary variable, used to store current query
            $tmpLine = '';

            // Read in entire file
            $lines = file($sqlFile);

            // Loop through each line
            foreach ($lines as $line) {
                // Skip it if it's a comment
                if (substr($line, 0, 2) == '--' || trim($line) == '') {
                    continue;
                }

                // Read & replace prefix
                $line = str_replace(['<<prefix>>', '<<InFilePath>>'], [$tablePrefix, $InFilePath], $line);

                // Add this line to the current segment
                $tmpLine .= $line;

                // If it has a semicolon at the end, it's the end of the query
                if (substr(trim($line), -1, 1) == ';') {
                    try {
                        // Perform the Query
                        $pdo->exec($tmpLine);
                    } catch (\PDOException $e) {
                        echo "<br><pre>Error performing Query: '<strong>" . $tmpLine . "</strong>': " . $e->getMessage() . "</pre>\n";
                        $errorDetect = true;
                    }

                    // Reset temp variable to empty
                    $tmpLine = '';
                }
            }

            // Check if error is detected
            if ($errorDetect) {
                return false;
            }

        } catch (\Exception $e) {
            echo "<br><pre>Exception => " . $e->getMessage() . "</pre>\n";
            return false;
        }

        return true;
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

    public function addFavouriteMenus(Request $request)
    {
        $module_name = $request->get('module_link', "");
        $icon = $request->get('icon', "");
        $hint = $request->get('hint', "");
        $user_id = $request->get('user_id', 0);
        if ($module_name != "" && $icon != "" && $hint != "") {
            DB::table('common_favourite_menus')
                ->insert([
                    "module_link" => $module_name,
                    "menu_icon" => $icon,
                    "hint" => $hint,
                    "user_id" => $user_id,
                ]);
        }

        $menus = DB::table('common_favourite_menus as fm')
            ->select(DB::raw('fm.*'))
            ->get();
        $ret = array();
        $ret['list'] = $menus;
        return Response::json($ret);

    }

    public function removeFavouriteMenus(Request $request)
    {
        $menu_id = $request->get('menu_id', 0);
        $user_id = $request->get('user_id', 0);
        DB::table('common_favourite_menus')
            ->where('id', $menu_id)
            ->where('user_id', $user_id)
            ->delete();
        $menus = DB::table('common_favourite_menus as fm')
            ->select(DB::raw('fm.*'))
            ->get();
        $ret = array();
        $ret['list'] = $menus;
        return Response::json($ret);

    }
}
