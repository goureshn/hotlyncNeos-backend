<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UploadController;

use App\Models\Common\Chain;
use App\Models\Common\Property;

use App\Models\Common\PropertySetting;
use App\Modules\Functions;
use App\Models\Service\Location;
use App\Models\Service\LocationType;
use App\Models\Common\CommonUser;

use Redirect;
use DB;
use Datatables;
use Response;

class PropertyWizardController extends UploadController
{

	public function getHardDisk() {
		$total = 0;
		$web_root = $_SERVER["DOCUMENT_ROOT"];
		$length = strlen($web_root) - 10;
		$web_root = substr($web_root,0,$length);
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$total = disk_total_space("E:");
			$free = disk_free_space($web_root);
			$used = $total - $free;

		} else {
			$total = disk_total_space("/");
			$free = disk_free_space($web_root);
			$used = $total - $free;
		}

		$ret = array();
		$ret['total'] = round((($total/1024)/1024)/1024 ,2);
		$ret['used'] = round((($used/1024)/1024)/1024,2);
		if($free == 0) $ret['free'] = 0;
		else $ret['free'] = round((($free/1024)/1024)/1024,2);
		$ret['webroot'] = $web_root;
		return Response::json($ret);
	}


    public function showIndexPage($request, $model)
	{
		// delete action
		$ids = $request->input('ids');
		if( !empty($ids) )
		{
			DB::table('common_property')->whereIn('id', $ids)->delete();			
			return back()->withInput();				
		} 

		$query = Property::where('id', '>', '0');

		$pagesize = $request->input('pagesize');
		if( empty($pagesize) )
			$pagesize = 10;		
		
		$request->flashOnly('search'); 
		
		$datalist = $query->paginate($pagesize);
		
		//$mode = "read";
		$step = '1';
		return view('backoffice.wizard.property.property', compact('datalist', 'model', 'pagesize', 'step'));				
	}	
    public function index(Request $request)
    {
		$user_id = $request->get('user_id','0');
		$client_id = $request->get('client_id', 0);
		
		if($user_id > 0)
			$property_list = CommonUser::getPropertyIdsByJobroleids($user_id);
		else
			$property_list = CommonUser::getProertyIdsByClient($client_id);	

		if ($request->ajax()) {
			
				$datalist = DB::table('common_property as cp')
					->leftJoin('common_chain as cc', 'cp.client_id', '=', 'cc.id')
					->select(['cp.*', 'cc.name as ccname']);

				$datalist->whereIn('cp.id', $property_list);							
						
			return Datatables::of($datalist)
					->addColumn('modules', function ($data) {
						$property_id = $data->id;
						$module_data = DB::table('common_module_property as cmp')
							->leftJoin('common_module as cm','cmp.module_id','=','cm.id')
							->where('cmp.property_id', $property_id)
							->select(DB::raw('cm.*'))
							->get();
						$module = '';
						for($j=0; $j < count($module_data) ;$j++) {
							$module .= ''. $module_data[$j]->name . ',' ;
						}
						return $module;
					})
					->addColumn('checkbox', function ($data) {
						return '<input type="checkbox" class="checkthis" />';
					})
					->addColumn('edit', function ($data) {
						return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal" ng-disabled="job_role!=\'SuperAdmin\'&&edit_flag==0" ng-click="onShowEditRow('.$data->id.')">
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
		else
		{
			$model = new Property();
			
			return $this->showIndexPage($request, $model);
		}
    }
	

  
    public function create()
    {
		$model = new Property();
		$client = Chain::lists('name', 'id');
		$step = '1';		
		
		return view('backoffice.wizard.property.propertycreate', compact('model', 'client', 'step'));
    }

    public function store(Request $request)
    {
		$step = '1';
	
		$module_ids = $request->get('modules_ids');
		$input = $request->except(['id', 'modules_ids']);
		$model = Property::create($input);
		$property_id = $model->id;
		//$model->setModules($module_ids);
		if(!empty($model)) {
			if(!empty($module_ids)) {
				$module_array =array();
				for($i = 0 ; $i < count($module_ids) ; $i++ ) {
					$module_array[$i]['property_id'] = $property_id;
					$module_array[$i]['module_id'] = $module_ids[$i];
				}
				if(!empty($module_array)) {
					DB::table('common_module_property')->where('property_id', $property_id)->delete();
					DB::table('common_module_property')->insert($module_array);
				}
			}
		}

		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		return back()->with('error', $message)->withInput();
    }

	// address: "Dubai"
	// city: "Dubai"
	// client_id: 4
	// contact: "Tejas"
	// country: "UAE"
	// description: ""
	// email: "peter.noronha@ennovatech.com"
	// id: -1
	// logo_path: ""
	// mobile: "0565935219"
	// modules_ids: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13]
	// name: "Property 3"
	// shortcode: "P3"
	// url: "staging3.myhotlync.com"
	public function storeProperty(Request $request)
    {
		$step = '1';
	
		$module_ids = $request->input('modules_ids');
		
		//return $module_ids;
		$input = $request->except(['id', 'modules_ids']);
		$model = Property::create($input);
		$property_id = $model->id;
		//$model->setModules($module_ids);
		if(!empty($model)) {
			if(!empty($module_ids)) {
				$module_array =array();
				for($i = 0 ; $i < count($module_ids) ; $i++ ) {
					$module_array[$i]['property_id'] = $property_id;
					$module_array[$i]['module_id'] = $module_ids[$i];
				}
				if(!empty($module_array)) {
					DB::table('common_module_property')->where('property_id', $property_id)->delete();
					DB::table('common_module_property')->insert($module_array);
				}
			}
		}

		$message = 'SUCCESS';	
		
		if( empty($model) )
			$message = 'Internal Server error';		
		
		//return back()->with('error', $message)->withInput();

		$pin = $request->get('pin');
		$device_id = $request->get('device_id');

		$this->sendLicReq(
			$model->name,
			$model->email,
			$device_id,
			$pin
		);

		return json_encode(array("message" => $message));
    }

	public function sendLicReq($property_name, $property_email, $device_id, $pin){

        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");

        $response = Http::post(config('app.secure_server_url') . "/api/requestlicense",[
            'property_name' => $property_name,
            'property_email' => $property_email,
            'device_id' => $device_id,
            'start_date' => $cur_date,
            'ip' => url('/'),
            'url' => url('/'),
            'pin' => $pin
        ]);

		return $response;
	}

	public function sendTestLicReq(Request $request){

        date_default_timezone_set(config('app.timezone'));
        $cur_date = date("Y-m-d");

		$property_name = $request->get('property_name');
		$property_email = $request->get('property_email');
		$device_id = $request->get('device_id');
		$start_date = $request->get('start_date');
		$ip = $request->get('ip');
		$url = $request->get('url');
		$pin = $request->get('pin');
		return;

        $data = [];
        $data['property_name'] = "Staging 4 P1";
        $data['property_email'] = "peter.noronha@ennovatech.com";
        $data['device_id'] = "263d5771084ab7ae67c241225a855ce529e8005a872a4aaba234713fc70943ec";
        $data['start_date'] = $cur_date;
        $data['ip'] = url('/');
        $data['url'] = url('/');
        $data['pin'] = "123321";

        $response = Http::post(config('app.secure_server_url') . "/api/requestlicense",[
            'property_name' => "Staging 4 P1",
            'property_email' => "peter.noronha@ennovatech.com",
            'device_id' => "263d5771084ab7ae67c241225a855ce529e8005a872a4aaba234713fc70943ec",
            'start_date' => $cur_date,
            'ip' => url('/'),
            'url' => url('/'),
            'pin' =>"123321"
        ]);

		return $response;
	}

	public function licenseKey(Request $request){
		$key = $request->get('licensekey');

		$name = "license.lic";

		$output_dir = base_path() . '/license';
        if (!file_exists($output_dir)) {
            mkdir($output_dir, 0777);
        }

		$upload_path = base_path() . '/license/' . $name ;

		$file_w = fopen($upload_path , 'w');
		fwrite($file_w, $key);
        fclose($file_w);

		$message = json_encode(array("message" => "Key uploaded"));
		return $message;
	}

    public function storeLicense(Request $request){

        if(!$request->hasFile('fileName')) {
            return response()->json(['upload_file_not_found'], 400);
        }

        $output_dir = base_path() . '/license';
        if (!file_exists($output_dir)) {
            mkdir($output_dir, 0777);
        }

        $allowedfileExtension=['lic'];
        $file = $request->file('fileName'); 
        $errors = [];

        $extension = $file->getClientOriginalExtension();
        $name = $file->getClientOriginalName();
        $check = in_array($extension,$allowedfileExtension);

        if($check){
            $upload_path = base_path() . '/license/' . $name ;
            file_put_contents($upload_path, file_get_contents($file));

            $message = json_encode(array("message" => "File Uploaded"));
        }else{
            $message = json_encode(array("message" => "Invalid file"));
        }

        return $message;

    }
	
	function createData(Request $request)
	{
		$module_ids = $request->get('modules_ids');
		$input = $request->except(['id','modules_ids','modules']);

		try {			
			$model = Property::create($input);
			Property::createLocation();
			$property_id = $model->id;
			if(!empty($model)) {
				if(!empty($module_ids)) {
					$module_array =array();
					for($i = 0 ; $i < count($module_ids) ; $i++ ) {
						$module_array[$i]['property_id'] = $property_id;
						$module_array[$i]['module_id'] = $module_ids[$i];
					}
					if(!empty($module_array)) {
						DB::table('common_module_property')->insert($module_array);
					}
				}
			}
		} catch(PDOException $e){
		   return Response::json([
				'success' => false,
				'message' => 'Hello'
				], 422);
		}	
		
		return Response::json($model);			
	}

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $model = Property::find($id);
		if( empty($model) )
		{
			return back();
		}
		$client = Chain::lists('name', 'id');
		$step = '1';		
		
		return view('backoffice.wizard.property.propertycreate', compact('model', 'client', 'step'));	

    }

    public function update(Request $request, $id)
    {
		$model = Property::find($id);
		
		$message = 'SUCCESS';
		
		if( empty($model) )
		{
			$message = "Property does not exist.";
			return back()->with('error', $message)->withInput();					
		}
		
        $module_ids = $request->get('modules_ids');
		$input = $request->except(['modules_ids']);		
		$model->update($input);
		$property_id = $model->id;
		//$model->setModules($module_ids);
		if(!empty($model)) {
			if(!empty($module_ids)) {
				$module_array =array();
				for($i = 0 ; $i < count($module_ids) ; $i++ ) {
					$module_array[$i]['property_id'] = $property_id;
					$module_array[$i]['module_id'] = $module_ids[$i];
				}
				if(!empty($module_array)) {
					DB::table('common_module_property')->where('property_id', $property_id)->delete();
					DB::table('common_module_property')->insert($module_array);
				}
			}
		}

		if( empty($model) )
			$message = 'Internal Server error';		
		
		return $this->index($request);
    }
	
	public function updateData(Request $request, $id)
    {
		$input = $request->except(['id', 'modules_ids','modules']);
		$module_ids = $request->get('modules_ids');

		$model = Property::find($id);
		
		if( !empty($model) )
		{
			$model->update($input);
			Property::createLocation();
			$property_id = $model->id;
			if(!empty($model)) {
				if(!empty($module_ids)) {
					$module_array =array();
					for($i = 0 ; $i < count($module_ids) ; $i++ ) {
						$module_array[$i]['property_id'] = $property_id;
						$module_array[$i]['module_id'] = $module_ids[$i];
					}
					if(!empty($module_array)) {
						DB::table('common_module_property')->where("property_id", $property_id)->delete();
						DB::table('common_module_property')->insert($module_array);
					}
				}
			}
		}
		
		return Response::json($input);			
    }

	public function destroy(Request $request, $id)
	{
		$model = Property::find($id);
		$model->delete();

		// delete location
		$loc_type = LocationType::createOrFind('Property');
		Location::where('property_id', $id)
				->where('type_id', $loc_type->id)
				->delete();
		
		// delete location group member
		DB::select('DELETE lgm 
					FROM services_location_group_members AS lgm 
					INNER JOIN services_location AS sl ON lgm.loc_id = sl.id 
					WHERE sl.property_id = ? 
					AND sl.type_id = ?', [$id, $loc_type->id]);		

		return Response::json($model);
	}
//    public function destroy($id)
//    {
//        $model = Property::find($id);
//		$model->delete();
//
//		return Redirect::to('/backoffice/property/wizard/property');
//    }

	function uploadlogo(Request $request)
	{
		$output_dir = "uploads/logo/";

		$ret = array();

		$filekey = 'myfile';

		if($request->hasFile($filekey) === false )
		{
			$ret['code'] = 201;
			$ret['message'] = "No input file";
			$ret['content'] = array();
			return Response::json($ret);
		}


		//You need to handle  both cases
		//If Any browser does not support serializing of multiple files using FormData()
		if (!is_array($_FILES[$filekey]["name"])) //single file
		{
			if ($request->file($filekey)->isValid() === false )
			{
				$ret['code'] = 202;
				$ret['message'] = "No valid file";
				return Response::json($ret);
			}

			$fileName = $_FILES[$filekey]["name"];
			$ext = pathinfo($fileName, PATHINFO_EXTENSION);
			$filename1 = "logo_".time().".".strtolower($ext);

			$dest_path = $output_dir . $filename1;

			move_uploaded_file($_FILES[$filekey]["tmp_name"], $dest_path);

			$ret['code'] = 200;
			$ret['message'] = "File is uploaded successfully";
			$ret['content'] = $dest_path;
			return Response::json($ret);
		}
		else  //Multiple files, file[]
		{
			$filename = array();
			$fileCount = count($_FILES[$filekey]["name"]);
			for ($i = 0; $i < $fileCount; $i++)
			{
				$fileName = $_FILES[$filekey]["name"][$i];
				$ext = pathinfo($fileName, PATHINFO_EXTENSION);
				$filename1 = "logo_".time(). '_' . ($i+1) . ".".strtolower($ext);

				$dest_path = $output_dir . $filename1;
				move_uploaded_file($_FILES[$filekey]["tmp_name"][$i], $dest_path);
				$filename[$i] = $dest_path;
			}

			$ret['code'] = 200;
			$ret['message'] = "File is uploaded successfully";
			$ret['content'] = $filename;
			return Response::json($ret);
		}
	}

	function getMobileSetting(Request $request) {
		$property_id = $request->get('property_id', 0);

		$setting = PropertySetting::getMobileSetting($property_id);

		$comp = parse_url($setting['mobile_app_url']);
		$url = $comp['scheme'] . '://' . $comp['host'];
		if( !empty($comp['port']) )
			$url .= ':' . $comp['port'];

		$url .= '/';

		$setting['mobile_app_url'] = $url;

		return Response::json($setting);
	}
    function getPinSetting(Request $request) {

        $setting = array();
        $setting_key = DB::table('property_setting')->where('settings_key', "app_pin")->first();
        $setting['app_pin'] = $setting_key->value;
        $setting_key = DB::table('property_setting')->where('settings_key', "central_server_domain")->first();
        $setting['central_server_domain'] = $setting_key->value;
        $setting_key = DB::table('property_setting')->where('settings_key', "central_port")->first();
        $setting['central_port'] = $setting_key->value;

        return Response::json($setting);
    }
    function updatePinSetting(Request $request) {
        $app_pin = $request->get('app_pin', '');
        try {
            DB::table('property_setting')->where('settings_key', "app_pin")->update(["value" => $app_pin]);
        }catch(PDOException $e){
            return Response::json([
                'code' => 0,
                'msg' => 'Failed to save app pin!'
            ]);
        }
        $ret = array();
        $ret['code'] = 200;
        $ret["msg"] = "Succesfully saved app pin!";
        return Response::json($ret);
    }

	function updateMobileSetting(Request $request)
	{
		$output_dir = "mobile/";
		
		$ret = array();
		
		$filekey = 'myfile';

		if($request->hasFile($filekey) === false )
		{
			$ret['code'] = 201;
			$ret['message'] = "No input file";
			$ret['content'] = array();
			return Response::json($ret);
		}

		$property_id = $request->get('property_id', 0);
		$app_version = $request->get('app_version', '1.0.0');
		$app_base_url = $request->get('app_base_url', Functions::getSiteURL());


		//You need to handle  both cases
		//If Any browser does not support serializing of multiple files using FormData() 
		if (!is_array($_FILES[$filekey]["name"])) //single file
		{
			if ($request->file($filekey)->isValid() === false )
			{
				$ret['code'] = 202;
				$ret['message'] = "No valid file";
				return Response::json($ret);
			}

			$fileName = $_FILES[$filekey]["name"];			
			$filename1 = 'hotlync.apk';		

			$dest_path = $output_dir . $filename1;
			
			move_uploaded_file($_FILES[$filekey]["tmp_name"], $dest_path);

			// save setting value
			$data = array();
			$data['mobile_app_url'] = $app_base_url . $dest_path;
			$data['mobile_app_version'] = $app_version;

			PropertySetting::savePropertySetting($property_id, $data);

			$ret['code'] = 200;
			$ret['message'] = "File is uploaded successfully";
			$ret['content'] = $dest_path;
			return Response::json($ret);
		}
	}
	public function tunnelClientStart(Request $request)
    {
        $app_pin = $request->get('app_pin', '');
        $central_server_domain = $request->get('central_server_domain', '');
        $central_port = $request->get('central_port', '');

        $port = $_SERVER["SERVER_PORT"];

        $command = "lt --port ".$port." --host ".$central_server_domain.":".$central_port." --subdomain ".$app_pin;
         // pm2 restart
        $ret  = shell_exec($command);

        return $ret;

    }
	public function getWebTerminalHost(){
		$hostlocation = DB::table('property_setting')->where('settings_key', "webterminal_host")->pluck('value');
		return $hostlocation;
	}

    public function getLiveServerSetting(Request $request)
    {
        $setting = array();
        $setting_key = DB::table('property_setting')->where('settings_key', "live_host")->first();
        if ($setting_key) {

            $setting['live_host'] = $setting_key->value;

        }
        $setting_key = DB::table('property_setting')->where('settings_key', "live_directory")->first();
        if ($setting_key) {

            $setting['live_directory'] = $setting_key->value;
        }

        /*
        $liveserver_status = 1;

        $address = "127.0.0.1";
        $service_port = 8001;
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            echo "socket_create()error :  " . socket_strerror(socket_last_error()) . "\n";
        } else {
            echo "OK 1.\n";
        }

        echo "Try connection '$address' on port '$service_port'... \n";
        $result = socket_connect($socket, $address, $service_port);
        if ($socket === false) {
            echo "socket_connect() error : ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
        } else {
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 1, "usec" => 0));
            echo "OK 2.\n";

        }
        return;

        $result = stream_get_meta_data($socket);

        $setting["liveserver_status"] = $result;
*/
        return Response::json($setting);
    }
    public function getInterfaceServerSetting(Request $request)
    {
        $setting = array();
        $setting_key = DB::table('property_setting')->where('settings_key', "interface_host")->first();
        if ($setting_key) {

            $setting['interface_host'] = $setting_key->value;

        }
        $setting_key = DB::table('property_setting')->where('settings_key', "interface_directory")->first();
        if ($setting_key) {

            $setting['interface_directory'] = $setting_key->value;
        }

        return Response::json($setting);
    }
    public function getMobileServerSetting(Request $request)
    {
        $setting = array();
        $setting_key = DB::table('property_setting')->where('settings_key', "mobileserver_host")->first();
        if ($setting_key) {

            $setting['mobileserver_host'] = $setting_key->value;

        }
        $setting_key = DB::table('property_setting')->where('settings_key', "mobileserver_directory")->first();
        if ($setting_key) {

            $setting['mobileserver_directory'] = $setting_key->value;
        }

        return Response::json($setting);
    }
    public function getExportServerSetting(Request $request)
    {
        $setting = array();
        $setting_key = DB::table('property_setting')->where('settings_key', "exportserver_host")->first();
        if ($setting_key) {

            $setting['exportserver_host'] = $setting_key->value;

        }
        $setting_key = DB::table('property_setting')->where('settings_key', "exportserver_directory")->first();
        if ($setting_key) {

            $setting['exportserver_directory'] = $setting_key->value;
        }

        return Response::json($setting);
    }
    public function updateLiveServer(Request $request)
    {
        $action = $request->get('action', '');
        $liveserver_directory = $request->get('liveserver_directory', '');

		$terminalHost = $this->getWebTerminalHost();

		if($action == "start")
        {
			$url = $terminalHost[0]."startliveserver";
        }
		if($action == "stop")
        {
			$url = $terminalHost[0]."stopliveserver";
        }
		if($action == "restart")
        {
			$url = $terminalHost[0]."restartliveserver";
        }
		if($action == "status")
        {
			$url = $terminalHost[0]."statusliveserver";
        }

		try{

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			// grab URL and pass it to the browser
			$response = curl_exec($ch);
			
			// close cURL resource, and free up system resources
			curl_close($ch);
			flush();

		}catch(Exception $e){
			return $e;
		}

        if($action == "status")
        {
			$json_response = json_decode($response);
			$ret = $json_response[0]->pm2_env->status;
		}else{
			$ret  = strval($response);
		}

        return $ret;
    }
    public function updateInterfaceServer(Request $request)
    {
        $action = $request->get('action', '');
        $interface_directory = $request->get('interface_directory', '');

		$terminalHost = $this->getWebTerminalHost();

		if($action == "start")
        {
			$url = $terminalHost[0]."startinterface";
        }
		if($action == "stop")
        {
			$url = $terminalHost[0]."stopinterface";
        }
		if($action == "restart")
        {
			$url = $terminalHost[0]."restartinterface";
        }
		if($action == "status")
        {
			$url = $terminalHost[0]."statusinterfaceserver";
        }

		try{

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			// grab URL and pass it to the browser
			$response = curl_exec($ch);
			
			// close cURL resource, and free up system resources
			curl_close($ch);
			flush();


		}catch(Exception $e){
			return $e;
		}

		if($action == "status")
        {
			$json_response = json_decode($response);
			$ret = $json_response[0]->pm2_env->status;
		}else{
			$ret  = strval($response);
		}

        return $ret;
    }
    public function updateMobileServer(Request $request)
    {
        $action = $request->get('action', '');
        $mobileserver_directory = $request->get('mobileserver_directory', '');

		$terminalHost = $this->getWebTerminalHost();

        if($action == "start")
        {
			$url = $terminalHost[0]."startmobileserver";
        }
		if($action == "stop")
        {
			$url = $terminalHost[0]."stopmobileserver";
        }
		if($action == "restart")
        {
			$url = $terminalHost[0]."restartmobileserver";
        }
		if($action == "status")
        {
			$url = $terminalHost[0]."statusmobileserver";
        }

		try{

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			// grab URL and pass it to the browser
			$response = curl_exec($ch);
			
			// close cURL resource, and free up system resources
			curl_close($ch);
			flush();

		}catch(Exception $e){
			return $e;
		}

        if($action == "status")
        {
			$json_response = json_decode($response);
			$ret = $json_response[0]->pm2_env->status;
		}else{
			$ret  = strval($response);
		}

        return $ret;
    }
    public function updateExportServer(Request $request)
    {
        $action = $request->get('action', '');
        $exportserver_directory = $request->get('exportserver_directory', '');

        if($exportserver_directory == "")
        {
            $exportserver_directory = base_path()."/../ExportServer/";
        }

        chdir($exportserver_directory);

        $command = "";
        if($action == "start")
        {
            $command = "forever start bin/exportserver -uid exportserver";
        }
        else if($action == "stop")
        {
            $command = "forever stop bin/exportserver";
        }
        else if($action == "restart")
        {
            $command = "forever restart bin/exportserver";
        }
        // pm2 restart
        $ret  = shell_exec($command);
        return $ret;
    }

}
