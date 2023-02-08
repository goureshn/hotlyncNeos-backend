<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Common\Property;
use App\Models\Common\License;
use App\Models\Common\PropertySetting;
use App\Modules\Functions;

use DB;
use Redis;
use Datatables;
use Response;

class LicenseWizardController extends Controller
{
    public function showIndexPage($request, $model)
    {
        // delete action
        $ids = $request->input('ids');
        if( !empty($ids) )
        {
            DB::table('common_property_license')->whereIn('id', $ids)->delete();
            return back()->withInput();
        }

        $query = License::where('id', '>', '0');

        $pagesize = $request->input('pagesize');
        if( empty($pagesize) )
            $pagesize = 10;

        $request->flashOnly('search');

        $property = Property::lists('name', 'id');
        $datalist = $query->orderby('name')->paginate($pagesize);

        //$mode = "read";
        $step = '2';
        return view('backoffice.wizard.property.license', compact('datalist', 'model', 'pagesize', 'property', 'step'));
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $datalist = DB::table('common_property_license as cl')
                ->leftJoin('common_chain as cc', 'cl.client_id', '=', 'cc.id')
                ->leftJoin('common_property as cp', 'cl.property_id', '=', 'cp.id')
                ->select(['cl.*', 'cp.name as cpname','cc.name as ccname']);
            return Datatables::of($datalist)
                // ->addColumn('modules', function ($data) {
                //     $property_id = $data->property_id;
                //     $module_data = DB::table('common_module_property as cmp')
                //         ->leftJoin('common_module as cm','cmp.module_id','=','cm.id')
                //         ->where('cmp.property_id', $property_id)
                //         ->select(DB::raw('cm.*'))
                //         ->get();
                //     $module = '';
                //     for($j=0; $j < count($module_data) ;$j++) {
                //         $module .= ''. $module_data[$j]->name . ',' ;
                //     }
                //     return $module;
                // })
                ->addColumn('checkbox', function ($data) {
                    return '<input type="checkbox" class="checkthis" />';
                })
                ->addColumn('down_csr_path', function ($data) {
                    return '<a href="'.$data->csr_path . '" download/>'.$data->csr_path . '</a>';
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
                ->rawColumns(['checkbox', 'down_csr_path', 'edit', 'delete'])
                ->make(true);
        }
        else
        {
            $model = new License();
            return $this->showIndexPage($request, $model);
        }
    }


    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");

        $property_id = $request->get('property_id' ,0);
        $condition = $request->condition ?? "";

        $input = $request->except(['id', 'modules_ids', 'modules','module_type', 'condition', 'down_csr_path']);
        $checkdata = DB::table('common_property_license')
            ->where('property_id', $input['property_id'])
            ->first();
        if(!empty($checkdata) ) {
            $message = ' This value already exist';
            $ret = array();
            $ret['code'] = '401';
            $ret['message'] = $message;
            return Response::json($ret);
        }
        
        $mainboard_number = Functions::getDeviceId();
        $input['device_number'] = $mainboard_number;
        $input['created_at'] = $cur_date;
        $input['updated_at'] = $cur_date;

       
        $settings = PropertySetting::getCentralServerSetting($property_id);
        $flag = $settings['central_flag'];        

        $model = License::create($input);

        $property_id = $model->property_id;
        
        if($flag == 1 && $condition == 'request') {
            
        }

        $message = 'SUCCESS';

        if ($request->ajax())
            return Response::json($model);
        else
            return back()->with('error', $message)->withInput();
    }

    function createData(Request $request)
    {
        $input = $request->except(['id']);

        try {
            $model = License::create($input);
        } catch(PDOException $e){
            return Response::json([
                'success' => false,
                'message' => 'Hello'
            ], 422);
        }

        return Response::json($model);
    }

    public function getLicenseList(Request $request)
    {
        $property_id = $request->get('property_id', '0');

        if( $property_id > 0 )
        {
            $model = DB::table('common_property_license')->where('property_id', $property_id)->get();
        }
        else
        {
            $model = DB::table('common_property_license')->get();
        }

        return Response::json($model);
    }

    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $model = License::find($id);
        if( empty($model) )
            $model = new License();

        return $this->showIndexPage($request, $model);
    }
    //seril number test
    public  function testDeviceSerial() {
        //$mainboard_number = shell_exec('wmic baseboard get serialnumber');
        //$mainboard_number = shell_exec('dmidecode --string baseboard-serial-number');
        //$mainboard_number = system('wmic baseboard get serialnumber');
        //#############################################################
        // after run this line, you can run shell command on web
        //sudo vi /etc/sudoers
        //in above file, insert like that command at last line.
        //  www-data ALL=NOPASSWD: ALL
        //if you wnat to run special command, you can like that
        // www-data ALL=NOPASSWD: /sbin/iptables, /usr/bin/du
        //##############################################################
        $mainboard_number = system('sudo dmidecode --string baseboard-serial-number');
        echo $mainboard_number;
        echo 'test::::';
    }

    public function test(Request $request){
        return "testing";
    }
    public function updatelic(Request $request){
        $id = 7;
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");

        $condition = $request->get('condition','update');
        $property_id = $request->get('property_id',0);

        $input = $request->except(['id', 'modules_ids', 'modules','condition','down_csr_path']);
        //main board serail number
        $mainboard_number = Functions::getDeviceId();
        
        $input['device_number'] = $mainboard_number;
        $input['updated_at'] = $cur_date;

        $settings = PropertySetting::getCentralServerSetting($property_id);
        $flag = $settings['central_flag'];           

        $model = License::find($id);
        $model->update($input);

        $model->csr_path = "/uploads/request_" . $cur_date . ".csr";
        $model->save();

        $property = Property::find($property_id);


        // Request license
        if($condition == 'request') {
            $meta = array();
            $meta['email'] = $model->email;
            $meta['property'] = $property->name;
            $meta['device_id'] = '263d5771084ab7ae67c241225a855ce529e8005a872a4aaba234713fc70943ec';//$mainboard_number;     
            $meta['start_day'] = $cur_date;   
            //return $meta;
        
            $message = json_encode($meta);

            // $key = md5('Hotlync_Request');
            $key = md5(config('app.key') . 'Request');

            $encrypter = new \Illuminate\Encryption\Encrypter( $key, "AES-256-CBC" );
        
            $ciphertext = $encrypter->encrypt( $message );
         
            $license_path = public_path() . $model->csr_path;
            
            file_put_contents($license_path, $ciphertext);

            $input['server_path'] = $license_path;
            $input['ciphertext'] = $ciphertext;
            
            // $model->save();
        }
       
        if($condition == 'register') {
            $this->getLicense($input);
        }

        return Response::json($input);
    }

    public function storelic(Request $request)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");

        $property_id = $request->get('property_id' ,0);

        $input = $request->except(['id', 'modules_ids', 'modules','module_type', 'condition', 'down_csr_path']);
        $checkdata = DB::table('common_property_license')
            ->where('property_id', $input['property_id'])
            ->first();
        if(!empty($checkdata) ) {
            $message = ' This value already exist';
            $ret = array();
            $ret['code'] = '401';
            $ret['message'] = $message;
            return Response::json($ret);
        }
        
        $mainboard_number = Functions::getDeviceId();
        $input['device_number'] = $mainboard_number;
        $input['created_at'] = $cur_date;
        $input['updated_at'] = $cur_date;

       
        $settings = PropertySetting::getCentralServerSetting($property_id);
        $flag = $settings['central_flag'];        

        $model = License::create($input);

        $property_id = $model->property_id;
        
        if($flag == 1 && $condition == 'request') {
            
        }

        $message = 'SUCCESS';

        if ($request->ajax())
            return Response::json($model);
        else
            return back()->with('error', $message)->withInput();
    }
    static function createDeviceInfo()
	{
		$device_info_path = config_path() . "/device_info.lic";
        print_r($device_info_path);

		
		if (!file_exists($device_info_path)) 
		{
			$device_id =  Redis::get('device_id');  
            print_r($device_id);
			if( !empty($device_id))
				file_put_contents($device_info_path, $device_id);
		}
		else
		{
			$device_id = file_get_contents($device_info_path);
			if( empty($device_id) )
			{
				$device_id =  Redis::get('device_id');
				if( !empty($device_id))
				file_put_contents($device_info_path, $device_id);
			}
		}
		
		return $device_id;
	}

    public function update(Request $request, $id)
    {
        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");

        $condition = $request->get('condition','update');
        $property_id = $request->get('property_id',0);

        $input = $request->except(['id', 'modules_ids', 'modules','condition','down_csr_path']);
        //main board serail number
        $mainboard_number = Functions::getDeviceId();
        
        $input['device_number'] = $mainboard_number;
        $input['updated_at'] = $cur_date;

        $settings = PropertySetting::getCentralServerSetting($property_id);
        $flag = $settings['central_flag'];           

        $model = License::find($id);
        $model->update($input);

        $model->csr_path = "/uploads/request_" . $cur_date . ".csr";
        $model->save();

        $property = Property::find($property_id);


        // Request license
        if($condition == 'request') {
            $meta = array();
            $meta['email'] = $model->email;
            $meta['property'] = $property->name;
            $meta['device_id'] = $mainboard_number;     
            $meta['start_day'] = $cur_date;   
        
            $message = json_encode($meta);

            // $key = md5('Hotlync_Request');
            $key = md5(config('app.key') . 'Request');

            $encrypter = new \Illuminate\Encryption\Encrypter( $key, "AES-256-CBC" );
        
            $ciphertext = $encrypter->encrypt( $message );
         
            $license_path = public_path() . $model->csr_path;
            
            file_put_contents($license_path, $ciphertext);

            $input['server_path'] = $license_path;
            $input['ciphertext'] = $ciphertext;
            
            // $model->save();
        }
       
        if($condition == 'register') {
            $this->getLicense($input);
        }

        return Response::json($input);
    }

    public function destroy(Request $request, $id)
    {
        $model = License::find($id);
        $model->delete();

        return $this->index($request);
    }

    public function get_client_ip() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    //test scoket to central server
    public function testSocket()
    {
        $rules = array();
        $rules['central_server'] = '192.168.1.91';
        $rules['central_port'] = '8080';
        $rules['central_email'] = 'goldstarkyg91@gmail.com';
        $rules['central_flag'] = '1'; // you must change  after completed.
            $host = $rules['central_server'];
            $port = $rules['central_port'];
            $method = "GET";
            $path = "/auth/request";
            $username = "hotlync";
            $pass = "central";
            $index = 1;
            $client_id = 11;
            $client_name = 'test client';
            $client_name = str_replace(' ', '_', $client_name);
            $address = 'test address';
            $address = str_replace(' ', '_', $address);
            $company = 'test company';
            $company = str_replace(' ', '_', $company);
            $phone = '123456';
            $phone = str_replace(' ', '_', $phone);
            $email = 'goldstar@gmail.com';
            $email = str_replace(' ', '_', $email);
            $property_id = '1';
            $property_name = 'test property';
            $property_name = str_replace(' ', '_', $property_name);
            //$modules_ids = $input['modules_ids'];
            $user_count = '30';
            $room_count = '20';
            $device_number = '1111-1111-1111';
            $request_ip = $this->get_client_ip();

            $data = "username=" . $username;
            $data .= "&pass=" . $pass;
            $data .= "&index=" . $index;
            $data .= "client_id=" . $client_id;
            $data .= "&client_name=" . $client_name;
            $data .= "&address=" . $address;
            $data .= "&company=" . $company;
            $data .= "&phone=" . $phone;
            $data .= "&email=" . $email;
            $data .= "&property_id=" . $property_id;
            $data .= "&property_name=" . $property_name;
           // $data .= "&modules_ids=" . json_encode($modules_ids);
            $data .= "&user_count=" . $user_count;
            $data .= "&room_count=" . $room_count;
            $data .= "&device_number=" . $device_number;
            $data .= "&request_ip=" . $request_ip;

            $buffer = "";
            $method = strtoupper($method);
            if ($method = "GET") {
                $path .= '?' . $data;
            }

            $fp = fsockopen($host, $port, $errno, $errstr);
            if (!$fp) {
                echo "$errstr ($errno)<br>\n";
            } else {
                $out = "$method $path HTTP/1.1\r\n";
                $out .= "Host: $host\r\n";
                $out .= "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
                $out .= "Content-type: application/x-www-form-urlencoded\r\n";
                $out .= "Content-length: " . strlen($data) . "\r\n";
                $out .= "Connection: close\r\n\r\n";
                $out .= "$data\r\n\r\n";
                fwrite($fp, $out);
                while (!feof($fp)) {
                    $line = fgets($fp, 1024);
                    $buffer .= $line;
                }
                fclose($fp);
            }
            return $buffer;

    }
    //send socket  to central server
    public function sendSocket($input)
    {
        $property_id = $input['property_id'];

        $rules = PropertySetting::getCentralServerSetting($property_id);
        
        if ($rules['central_flag'] == 1) {
            $host = $rules['central_server'];
            $port = $rules['central_port'];
            $method = "GET";
            $path = "/auth/request";
            $username = "hotlync";
            $pass = "central";
            $index = $input['id'];
            $client_id = $input['client_id'];
            $client = DB::table('common_chain')
                ->where('id', $client_id)
                ->select(DB::raw('*'))
                ->first();
            $client_name = $client->name;
            $client_name = str_replace(' ', '_', $client_name);
            $address = $input['address'];
            $address = str_replace(' ', '_', $address);
            $company = $input['company'];
            $company = str_replace(' ', '_', $company);
            $phone = $input['phone'];
            $phone = str_replace(' ', '_', $phone);
            $email = $input['email'];
            $email = str_replace(' ', '_', $email);
            $property_id = $input['property_id'];
            $property = DB::table('common_property')
                ->where('id', $property_id)
                ->select(DB::raw('*'))
                ->first();
            $property_name = $property->name;
            $property_name = str_replace(' ', '_', $property_name);
            $modules = $input['modules_ids'];
            $modules_ids = array();
            for($i = 0; $i< count($modules) ; $i++) {
                $module_id = $modules[$i];
                $module = DB::table('common_module')
                    ->where('id', $module_id)
                    ->select(DB::raw('*'))
                    ->first();
                $module_name = $module->name;
                $module_name = str_replace(' ', '_', $module_name);
                $modules_ids[$module_id] = $module_name;
            }
            $user_count = $input['user_count'];
            $room_count = $input['room_count'];
            $device_number = $input['device_number'];
            $request_ip = $this->get_client_ip();

            $data = "username=" . $username;
            $data .= "&pass=" . $pass;
            $data .= "&index=" . $index;
            $data .= "&client_id=" . $client_id;
            $data .= "&client_name=" . $client_name;
            $data .= "&address=" . $address;
            $data .= "&company=" . $company;
            $data .= "&phone=" . $phone;
            $data .= "&email=" . $email;
            $data .= "&property_id=" . $property_id;
            $data .= "&property_name=" . $property_name;
            $data .= "&modules_ids=" . json_encode($modules_ids);
            $data .= "&user_count=" . $user_count;
            $data .= "&room_count=" . $room_count;
            $data .= "&device_number=" . $device_number;
            $data .= "&request_ip=" . $request_ip;

            $buffer = "";
            $method = strtoupper($method);
            if ($method = "GET") {
                $path .= '?' . $data;
            }

            $fp = fsockopen($host, $port, $errno, $errstr);
            if (!$fp) {
                echo "$errstr ($errno)<br>\n";
            } else {
                $out = "$method $path HTTP/1.1\r\n";
                $out .= "Host: $host\r\n";
                $out .= "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
                $out .= "Content-type: application/x-www-form-urlencoded\r\n";
                $out .= "Content-length: " . strlen($data) . "\r\n";
                $out .= "Connection: close\r\n\r\n";
                $out .= "$data\r\n\r\n";
                fwrite($fp, $out);
                while (!feof($fp)) {
                    $line = fgets($fp, 1024);
                    $buffer .= $line;
                }
                fclose($fp);
            }
             //return $buffer;
        }
    }

    function scheduleCentral(Request $request) {

        $rules = array();
        $rules['central_server'] = '192.168.1.91';
        $rules['central_port'] = '80';
        $rules['central_email'] = 'goldstarkyg91@gmail.com';
        $rules['central_flag'] = '0'; // you must change  after completed.

        foreach ($rules as $key => $value) {
            $data = DB::table('property_setting as ps')
                ->where('ps.settings_key', $key)
                ->select(DB::raw('ps.value'))
                ->first();

            if (empty($data))
                continue;

            $rules[$key] = $data->value;
        }
        if ($rules['central_flag'] == 1) {
            $host = $rules['central_server'];
            $port = $rules['central_port'];
            $method = "GET";
            $path = "/auth/confirmclient";
            $username = "hotlync";
            $pass = "central";

            $mainboard_number = '';
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $mainboard_number = "0000-0011-1587-0224-7099-5471-11";
            } else {
                $mainboard_number = system('sudo dmidecode --string baseboard-serial-number');
            }
            $client = DB::table('common_property_license')
                ->select(DB::raw('*'))
                ->first();
            for($i=0; $i < count($client) ;$i++) {
                $index = $client->id;
                $client_id = $client->client_id;
                $property_id = $client->property_id;
                $data = "username=" . $username;
                $data .= "&pass=" . $pass;
                $data .= "&index=" . $index;
                $data .= "&client_id=" . $client_id;
                $data .= "&property_id=" . $property_id;
                $data .= "&device_number=" . $mainboard_number;

                $buffer = "";
                $method = strtoupper($method);
                if ($method = "GET") {
                    $path .= '?' . $data;
                }
                //request to central server
                $fp = fsockopen($host, $port, $errno, $errstr);
                if (!$fp) {
                    echo "$errstr ($errno)<br>\n";
                } else {
                    $out = "$method $path HTTP/1.1\r\n";
                    $out .= "Host: $host\r\n";
                    $out .= "User-Agent: " . $_SERVER['HTTP_USER_AGENT'] . "\r\n";
                    $out .= "Content-type: application/x-www-form-urlencoded\r\n";
                    $out .= "Content-length: " . strlen($data) . "\r\n";
                    $out .= "Connection: close\r\n\r\n";
                    $out .= "$data\r\n\r\n";
                    fwrite($fp, $out);
                    while (!feof($fp)) {
                        $line = fgets($fp, 1024);
                        $buffer .= $line;
                    }
                    fclose($fp);
                }
                //get license data from response data
                $str_leng = strlen($buffer);
                $str_pos = strpos($buffer, 'Content-Type: application/json');
                $data = substr($buffer, $str_pos + 33, $str_leng);
                $input = json_decode($data);
                $this->getLicense((array)$input->data);//update
            }
        }
    }

    function getLicense($input) {
        $license_val = $this->decodeLicense($input['serial_number']);
        if (!empty($license_val)) {
            $license = array();
            $index = $license_val['index'];
            $license['client_id'] = $license_val['client_id'];
            $license['property_id'] = $license_val['property_id'];
            $license['user_count'] = $license_val['user_count'];
            $license['room_count'] = $license_val['room_count'];
            $device_number = $license_val['device_number'];
            $module_ids = $license_val['module_ids'];
            $license['expiry_date'] = $license_val['expiry_date'];
            DB::table('common_property_license')
                ->where('property_id', $license['property_id'])
                ->where('id', $index)
                ->where('device_number', $device_number)
                ->update($license);
            $module_array = array();
            for ($i = 0; $i < count($module_ids); $i++) {
                $module_array[$i]['property_id'] = $license['property_id'];
                $module_array[$i]['module_id'] = $module_ids[$i];
            }
            if (!empty($module_array)) {
                DB::table('common_module_property')->where('property_id', $license['property_id'])->delete();
                DB::table('common_module_property')->insert($module_array);
            }
        }
    }

    public function decodeLicense($license) {
        $ret = array();
        $license = base64_decode($license);
        $find = 'oooo1';
        $pos = strpos($license, $find);
        if ($pos === false){
          return $ret;
        }else {
            $groups = explode($find, $license);
            $group = $groups[2];
            $details = explode(":", $group);
            $ret['index'] = $details[0];
            $ret['client_id'] = $details[1];
            $ret['property_id'] = $details[2];
            $ret['user_count'] = $details[3];
            $ret['room_count'] = $details[4];
            $ret['device_number'] = $details[5];
            $ret['module_ids'] = json_decode($details[6]);
            $ret['expiry_date'] = $details[7];
            return $ret;
        }
    }

    public function getDeviceId(Request $request)
    {
        $device_id =  Redis::get('device_id');

        $ret = array();
        $ret['device_id'] = Functions::getDeviceId();
       
        return Response::json($ret);
    }

    public function uploadLicense(Request $request)
    {
        $property_id = $request->get('property_id' , 0);
        $ret = array();
        if($request->hasFile('file') === false )
        {            
            $ret['code'] = '201';
            return Response::json($ret);
        }

        $fileName = $_FILES['file']["name"];

        $dest_path = Functions::GetLicensePath();
        move_uploaded_file($_FILES['file']["tmp_name"], $dest_path);

        $meta = Functions::getLicenseInfo();
        if( is_numeric($meta) )
        {
            $ret['code'] = 201;
            $ret['message'] = "Invalid License";
        }

        $ret['meta'] = $meta;

        $license = License::first();
        $license->expiry_date = $meta->end_day;
        $license->save();
            
        return Response::json($ret);
    }

    function checkLicense(Request $request)
	{
		// ===================  check license ================================
        $meta = Functions::getLicenseInfo();

        return;
        
        $ret['code'] = $meta;

        $ret = array();
        
        
        if( is_numeric($meta) )
        {
            if( $meta == 1 )       
                $ret['message'] = "Server Device is Invalid";
            else if( $meta == 2 )       
                $ret['message'] = "License file does not exist";
            else if( $meta == 3 )       
                $ret['message'] = "Device is not valid";
        }
        else
        {
            date_default_timezone_set(config('app.timezone'));
            $cur_day = date('Y-m-d');
            $deadline_day = date("Y-m-d", strtotime('30 days', strtotime($meta->end_day)));	

            // $deadline_day = '2019-09-24';
            $ret['deadline_day'] = $deadline_day;
        
            if( $cur_day <= $deadline_day )
            {
                if( $meta->end_day < $cur_day ) // License expired
                {
                    $ts1 = strtotime($cur_day);
                    $ts2 = strtotime($deadline_day);
                
                    $seconds_diff = $ts2 - $ts1;
                    $day_diff = $seconds_diff / 3600 / 24;
                    
                    $message = 'Hotlync license has been expired on ' . $meta->end_day . '. you will be unable to login in ' . $day_diff . ' days';             
                    $ret['message'] = $message;   
                }
                // else
                //     $ret['message'] = 'You are available';
            }		
            else
            {
                $ts1 = strtotime($cur_day);
                $ts2 = strtotime($deadline_day);

                $seconds_diff = $ts1 - $ts2;
                $day_diff = $seconds_diff / 3600 / 24;

                if( $day_diff <= 30 )
                    $ret['message'] = "License Expired. Please contact system administrator. Interfaces will stop in " . (30 - $day_diff) . " days.";
                else
                    $ret['message'] = "Your interface is stopped";
            }
        }
        // else
		// 	$ret['message'] = "Hello";
		// ==============================================================================

		return Response::json($ret);
	}
}
