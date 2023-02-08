<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Controllers\UploadController;

use App\Models\Common\Support;

use App\Models\Common\PropertySetting;
use App\Modules\Functions;

use Redirect;
use DB;
use Datatables;
use Response;
use Redis;

define("OPEN", 'Open');
define("ASSIGNED", 'Assigned');
define("PENDING", 'Pending');
define("CLOSE", 'Close');

define("HIGH", 'High');
define("MEDIUM", 'Medium');
define("LOW", 'Low');

class SupportWizardController extends UploadController
{
    public function showIndexPage($request, $model)
    {
        // delete action
        $ids = $request->input('ids');
        if( !empty($ids) )
        {
            DB::table('common_support')->whereIn('id', $ids)->delete();
            return back()->withInput();
        }

        $query = Support::where('id', '>', '0');

        $pagesize = $request->input('pagesize');
        if( empty($pagesize) )
            $pagesize = 10;

        $request->flashOnly('search');

        $datalist = $query->paginate($pagesize);

        //$mode = "read";
        $step = '1';
        return view('backoffice.wizard.property.support', compact('datalist', 'model', 'pagesize', 'step'));
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $datalist = DB::table('common_support as cs')
                ->leftJoin('common_property as cp', 'cs.property_id', '=', 'cp.id')
                ->leftJoin('common_module as cm', 'cs.module_id', '=', 'cm.id')
                ->leftJoin('common_users as cu', 'cs.user_id', '=', 'cu.id')
                ->select(['cs.*', 'cp.name as cpname','cm.name as cmname', 'cu.first_name','cu.last_name', 'cu.username as username']);

            return Datatables::of($datalist) 
                 ->editColumn('username', function($data) {
                    if($data->user_id == 0) {
                        return 'Super Admin';
                    }else {
                        return $data->first_name.' '.$data->last_name;
                    }
                  })
                  ->addColumn('message_list', function($data) {
                    $message_list = DB::table('common_support_message')
                        ->where('support_id', $data->id)
                        ->select(DB::raw('*'))
                        ->get();
                    return $message_list;  
                  })  
                  ->addColumn('edit', function ($data) {
                        return '<p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#addModal"   ng-disabled="viewclass" ng-click="onShowEditRow('.$data->id.')">
                            <span class="glyphicon glyphicon-pencil"></span>
                        </button></p>';
                  })                                                               
                 ->make(true);
        }
        else
        {
            $model = new Support();
            return $this->showIndexPage($request, $model);
        }
    }

    public function create()
    {
        $model = new Support();
        $client = Chain::lists('name', 'id');
        $step = '1';

        return view('backoffice.wizard.property.supportcreate', compact('model', 'client', 'step'));
    }

    public function store(Request $request)
    {
        $step = '1';
        $input = $request->except(['id']);
        $model = Support::create($input);

        $message = 'SUCCESS';

        if( empty($model) )
            $message = 'Internal Server error';

        //save log
        if( !empty($model) ) {
            $user_id = $request->get('user_id',0);  
            $id= $model->id;      
            DB::table('common_support_log')
                        ->insert(['user_id'=>$user_id ,'status' => OPEN, 'suppot_id'=> $id]);
        }

        return back()->with('error', $message)->withInput();
    }

    function createData(Request $request)
    {
        $input = $request->except(['id']);
        $property_id = $request->get('property_id', 0);


        $settings = PropertySetting::getCentralServerSetting($property_id);
        $to_email = $settings['support_email'];
        $input['to_email'] = $to_email;

        //refer testDeviceSerial()
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {        
            $mainboard_number = "0000-0011-1587-0224-7099-5471-11";
        } else {
            //0000-0011-1587-0224-7099-5471-09
            $mainboard_number = system('sudo dmidecode --string baseboard-serial-number');
        }
        $input['device_number'] = $mainboard_number;

        try {
            $model = Support::create($input);

            //save log
            if( !empty($model) ) {
                $user_id = $request->get('user_id',0);  
                $id= $model->id;      
                DB::table('common_support_log')
                            ->insert(['user_id'=>$user_id ,'status' => OPEN, 'support_id'=> $id]);
            }

        } catch(PDOException $e){
            return Response::json([
                'success' => false
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
        $model = Support::find($id);
        if( empty($model) )
        {
            return back();
        }

        return view('backoffice.wizard.property.supportcreate', compact('model', 'client', 'step'));

    }

    public function update(Request $request, $id)
    {
        $model = Support::find($id);

        $message = 'SUCCESS';

        if( empty($model) )
        {
            $message = "Support does not exist.";
            return back()->with('error', $message)->withInput();
        }
        $original_cc = $model->cc_email;
        $original_status = $model->status;

        $new_cc =  $request->get('cc_email','');
        $new_status =  $request->get('status','');
                
        $new_status = $request->get('status', OPEN);
        $input = array();    
        $input['cc_email'] = $new_cc;        
        $input['status'] = $new_status;
        $model->update($input);

        //save log
        if($original_cc != $new_cc || $original_status != $new_status ) { 
            $user_id = $request->get('user_id',0);        
             DB::table('common_support_log')
                        ->insert(['user_id'=>$user_id ,'status' => $new_status, 'support_id'=> $id]);
        }

        if( empty($model) )
            $message = 'Internal Server error';

        return $this->index($request);
    }


    public function updateData(Request $request, $id)
    {
        $input = $request->except(['id']);
        $property_id = $request->get('property_id', 0);

        $model = Support::find($id);        

        if( !empty($model) )
        {
           
            $original_cc = $model->cc_email;
            $original_status = $model->status;

            $new_cc =  $request->get('cc_email','');
            $new_status = $request->get('status', OPEN);

            $input_u = array();    

            $settings = PropertySetting::getCentralServerSetting($property_id);
            $to_email = $settings['support_email'];            
            $input['to_email'] = $to_email;
            $input_u['cc_email'] = $new_cc;        
            $input_u['status'] = $new_status;        
            $model->update($input_u);

            //save log
            if($original_cc != $new_cc || $original_status != $new_status) { 
                $user_id = $request->get('user_id',0);        
                 DB::table('common_support_log')
                            ->insert(['user_id'=>$user_id ,'status' => $new_status, 'support_id'=> $id]);
            }
        }

        return Response::json($input);
    }

    public function destroy(Request $request, $id)
    {
        $model = Support::find($id);
        $model->delete();

        return Response::json($model);
    }

    function uploadAttach(Request $request)
    {
        $output_dir = "uploads/support/";
        if(!file_exists($output_dir)) {
            mkdir($output_dir, 0777);
        }
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
            $filename1 = "support_".time().".".strtolower($ext);

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
                $filename1 = "support_".time(). '_' . ($i+1) . ".".strtolower($ext);

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

    public function getHistory($id) {
        
        $user = DB::table('common_support_log as cl')
             ->leftJoin('common_users as cu', 'cl.user_id', '=', 'cu.id')    
             ->where('cl.support_id', $id)            
             ->select(DB::raw('cl.*, CONCAT_WS(" ", cu.first_name, cu.last_name) as name'))
             ->get();        
             
        return Response::json($user);
    }

    public function supportSendMessage(Request $request) {

        $mainboard_number = '';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {        
            $mainboard_number = "0000-0011-1587-0224-7099-5471-11";
        } else {
            //0000-0011-1587-0224-7099-5471-09
            $mainboard_number = system('sudo dmidecode --string baseboard-serial-number');
        }
        
        $send_input = $request->all();
        $send_input['device_number'] = $mainboard_number;        
        $this->sendSocket($send_input);//request to central server

        //save message            
        $support_id = $request->get('id',0); 
        $user_id =  $request->get('user_id',0);
        if($user_id == 0)  {
            $first_name = 'Super';
            $last_name = 'Admin';
        }else {
            $users = DB::table('common_users')
                    ->where('id', $user_id)
                    ->select(DB::raw('*'))
                    ->first();
            $first_name = $users->first_name ;
            $last_name = $users->last_name;                
        }
        $option = 'user';
        $message = $request->get('message','');        
        $id = DB::table('common_support_message')
                    ->insertGetId(['support_id'=>$support_id,
                                'user_id'=>$user_id ,
                                'first_name' => $first_name,
                                'last_name'=> $last_name,
                                'option'=>$option,
                                'message'=>$message]);
        //send email to support and cc
        $this->sendEmail($request);
        $data = DB::table('common_support_message')
            ->where('support_id', $support_id)
            ->select(DB::raw('*'))
            ->get();
        $ret = array();
        $ret['data_list'] = $data;
        return Response::json($ret);
    }

    //test scoket to central server
    public function testSocketSupport()
    {
        $rules = array();
        $rules['central_server'] = '192.168.1.91';
        $rules['central_port'] = '8080';
        $rules['central_email'] = 'goldstarkyg91@gmail.com';
        $rules['central_flag'] = '1'; // you must change  after completed.
        $host = $rules['central_server'];
        $port = $rules['central_port'];
        $method = "GET";
        $path = "/auth/support";
        $username = "hotlync";
        $pass = "central";
        $index = 1;
        $client_id = 11;
        $client_name = 'test client';
        $client_name = str_replace(' ', '_', $client_name);
        $property_id = 11;
        $property_name = 'test property';
        $property_name = str_replace(' ', '_', $property_name);
        $module_id = 11;
        $module_name = 'test module';
        $module_name = str_replace(' ', '_', $module_name);
        $subject = 'This is subject for test';
        $subject = str_replace(' ', '_', $subject);
        $severity = 'High';
        $severity = str_replace(' ', '_', $severity);
        $status = 'Open';
        $status = str_replace(' ', '_', $status);
        $from_email = 'from@gmail.com';
        $from_email = str_replace(' ', '_', $from_email);
        $to_email = 'to@gmail.com';
        $to_email = str_replace(' ', '_', $to_email);
        $cc_email = 'cc@gmail.com';
        $cc_email = str_replace(' ', '_', $cc_email);
        $issue = 'This is issue';
        $issue = str_replace(' ', '_', $issue);
        $device_number = '1111-1111-1111';
        $user_id = '1';
        $user_name = 'Super Admin';
        $user_name = str_replace(' ', '_', $user_name);
        $first_name = 'First Name';
        $first_name = str_replace(' ', '_', $first_name);
        $last_name = 'Last Name';
        $last_name = str_replace(' ', '_', $last_name);
        $message = 'This is test message.........................';
        $message = str_replace(' ', '_', $message);


        $data = "username=" . $username;
        $data .= "&pass=" . $pass;
        $data .= "&index=" . $index;
        $data .= "&client_id=" . $client_id;
        $data .= "&client_name=" . $client_name;
        $data .= "&property_id=" . $property_id;
        $data .= "&property_name=" . $property_name;
        $data .= "&module_id=" . $module_id;
        $data .= "&module_name=" . $module_name;
        $data .= "&subject=" . $subject;
        $data .= "&severity=" . $severity;
        $data .= "&status=" . $status;
        $data .= "&from_email=" . $from_email;
        $data .= "&to_email=" . $to_email;
        $data .= "&cc_email=" . $cc_email;
        $data .= "&issue=" . $issue;
        $data .= "&device_number=" . $device_number;
        $data .= "&user_id=" . $user_id;
        $data .= "&user_name=" . $user_name;
        $data .= "&first_name=" . $first_name;
        $data .= "&last_name=" . $last_name;
        $data .= "&message=" . $message;


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
            $path = "/auth/support";
            $username = "hotlync";
            $pass = "central";
            $index = $input['id'];
            $client_id = $input['client_id'];
            $client = DB::table('common_chain')
                ->where('id', $client_id)
                ->select(DB::raw('*'))
                ->first();
            if(empty($client)) $client_name = 'no selected Client';
            else $client_name = $client->name;
            $client_name = str_replace(' ', '_', $client_name);                        
            $property_id = $input['property_id'];
            $property = DB::table('common_property')
                ->where('id', $property_id)
                ->select(DB::raw('*'))
                ->first();
            if(empty($property)) $property_name = 'no selected Property';
            else $property_name = $property->name;
            $property_name = str_replace(' ', '_', $property_name);
            $module_id = $input['module_id'];                        
            $module = DB::table('common_module')
                ->where('id', $module_id)
                ->select(DB::raw('*'))
                ->first();
            if(empty($module)) $module_name = 'no selected Module';
            else $module_name = $module->name;

            $module_name = str_replace(' ', '_', $module_name);            
            $subject = $input['subject'];
            $subject = str_replace(' ', '_', $subject);
            $severity = $input['severity'];
            $severity = str_replace(' ', '_', $severity);
            $status = $input['status'];
            $status = str_replace(' ', '_', $status);
            $from_email = $input['from_email'];
            $from_email = str_replace(' ', '_', $from_email);
            $to_email = $input['to_email'];
            $to_email = str_replace(' ', '_', $to_email);
            $cc_email = $input['cc_email'];
            $cc_email = str_replace(' ', '_', $cc_email);
            $issue = $input['issue'];
            $issue = str_replace(' ', '_', $issue);
            $device_number = $input['device_number'];
            $device_number = str_replace(' ', '_', $device_number);
            $message = $input['message'];
            $message = str_replace(' ', '_', $message);

            $user_id = $input['user_id'];
            $first_name = '';
            $last_name = '';
            if($user_id == '0') {
                $user_name = 'Super Admin';
                $first_name = 'Super';
                $last_name = 'Admin';
            }else {
                $users = DB::table('common_users')
                    ->where('id', $user_id)
                    ->select(DB::raw('*'))
                    ->first();
                if(empty($users)) {
                    $user_name = 'no selected user';
                }
                else {
                    $user_name = $users->first_name.' '.$users->last_name;
                    $first_name = $users->first_name;
                    $last_name = $users->last_name;
                }
            }
            $user_name = str_replace(' ', '_', $user_name);
            $first_name = str_replace(' ', '_', $first_name);
            $last_name = str_replace(' ', '_', $last_name);

            $data = "username=" . $username;
            $data .= "&pass=" . $pass;
            $data .= "&index=" . $index;
            $data .= "&client_id=" . $client_id;
            $data .= "&client_name=" . $client_name;
            $data .= "&property_id=" . $property_id;
            $data .= "&property_name=" . $property_name;
            $data .= "&module_id=" . $module_id;
            $data .= "&module_name=" . $module_name;
            $data .= "&subject=" . $subject;
            $data .= "&severity=" . $severity;
            $data .= "&status=" . $status;
            $data .= "&from_email=" . $from_email;
            $data .= "&to_email=" . $to_email;
            $data .= "&cc_email=" . $cc_email;
            $data .= "&issue=" . $issue;
            $data .= "&device_number=" . $device_number;
            $data .= "&user_id=" . $user_id;
            $data .= "&user_name=" . $user_name;
            $data .= "&first_name=" . $first_name;
            $data .= "&last_name=" . $last_name;
            $data .= "&message=" . $message;

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

    public function sendEmail($request)
    {
        $id = $request->get('id',0);
        $property_id = $request->get('property_id',0);
        $property = DB::table('common_property')
            ->where('id', $property_id)
            ->select(DB::raw('*'))
            ->first();
        if(empty($property)) $property_shortcode = 'no selected property shortcode';
        else $property_shortcode = $property->shortcode;
        $subject = $request->get('subject', '');
        $message = $request->get('message','');
        $client_id = $request->get('client_id', 0);
        $client = DB::table('common_chain')
            ->where('id', $client_id)
            ->select(DB::raw('*'))
            ->first();
        if(empty($client)) $client_name = 'no selected Client';
        else $client_name = $client->name;
        $property_id = $request->get('property_id',0);
        $property = DB::table('common_property')
            ->where('id', $property_id)
            ->select(DB::raw('*'))
            ->first();
        if(empty($property)) $property_name = 'no selected Property';
        else $property_name = $property->name;

        $module_id = $request->get('module_id',0);
        $module = DB::table('common_module')
            ->where('id', $module_id)
            ->select(DB::raw('*'))
            ->first();
        if(empty($module)) $module_name = 'no selected Module';
        else $module_name = $module->name;
        $severity = $request->get('severity','');
        $user_id = $request->get('user_id',0);
        $mobile = '';
        if($user_id == '0')
            $user_name = 'Super Admin';
        else {
            $users = DB::table('common_users')
                ->where('id', $user_id)
                ->select(DB::raw('*'))
                ->first();
            if(empty($users)) {
                $user_name = 'no selected user';
                $mobile = '';
            }
            else {
                $user_name = $users->first_name.' '.$users->last_name;
                $mobile = $users->mobile;
            }

        }
        $from_email = $request->get('from_email' ,'');
        $cc_email = $request->get('cc_email','');
        $issue = $request->get('issue' , '');
        $settings = PropertySetting::getCentralServerSetting($property_id);
        $server = $settings['central_server'];
        $port = $settings['central_port'];
        $to_email = $settings['support_email'];
        $info = array(
            'id' => $id,
            'property_shortcode' => $property_shortcode,
            'subject' => $subject,
            'message' => $message,
            'client' => $client_name,
            'property' => $property_name,
            'module' => $module_name,
            'severity' => $severity,
            'user_name' => $user_name,
            'mobile' => $mobile,
            'from_email' => $from_email,
            'cc_email' => $cc_email,
            'issue' => $issue,
            'link' => 'http://'.$server.':'.$port
        );


        $message = array();
        $smtp = Functions::getMailSetting($property_id, 'notification_');
        $message['smtp'] = $smtp;
        $message['type'] = 'email';
        $message['from'] = $from_email;
        $message['to'] = $to_email;
        $message['cc'] = $cc_email;
        $message['subject'] = 'HotLync Support';
        $message['title'] = 'A new support ticket has been raised';
        $message['content'] = view('emails.support', ['info' => $info])->render();
        Redis::publish('notify', json_encode($message));

    }

    public function saveMessage(Request $request) {

        $index = $request->get('index',0);
        $client_id = $request->get('client_id',0);
        $property_id = $request->get('property_id', 0);
        $module_id = $request->get('module_id',0);
        $device_number = $request->get('device_number','');
        $option = $request->get('option','support');//support
        $first_name = $request->get('first_name','');
        $last_name = $request->get('last_name','');
        $user_id = 0;
        $message = $request->get('message','');

        $data = DB::table('common_support')
            ->where('id', $index)
            ->where('client_id', $client_id)
            ->where('property_id', $property_id)
            ->where('module_id', $module_id)
            ->where('device_number', $device_number)
            ->select(DB::raw('*'))
            ->get();
        $ret = array();
        if(empty($data)) {
            $ret['data'] = $device_number;
            return Response::json($ret);
        }
        $id = DB::table('common_support_message')
            ->insertGetId(['support_id'=>$index,
                'user_id'=>$user_id ,
                'first_name' => $first_name,
                'last_name'=> $last_name,
                'option'=>$option,
                'message'=>$message]);

        $ret['data'] = $device_number;
        return Response::json($ret);
    }
}
