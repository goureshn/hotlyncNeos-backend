<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Common\Chain;

use DB;
use Response;
use Redis;

use Redirect;

use App\Models\Common\PropertySetting;
use App\Modules\Functions;

class BackofficeController extends Controller
{
    private $request;
	
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
	
    public function index(Request $request)
    {
      $rules = array();
      $rules['size_gb'] = 0;
      $rules['free_gb'] = 0;

      $rules = PropertySetting::getPropertySettings(0, $rules);  
      $rules['used_gb'] = $rules['size_gb'] - $rules['free_gb'];

		  return view('backoffice.app', ['data' => $rules]);		
    }
    
	public function test(Request $request)
    {
		return view('backoffice.test');
    }

    public function signin(Request $request)
    {
        return view('backoffice.signin');
    }

    public function checkFreeSpace(Request $request)
    {
      $ret = array();
      
      $free = $request->get('free', 0);
      $size = $request->get('size', 0);

      $size_mb = $size / 1024 / 1024;
      $size_gb = $size_mb / 1024;
      $size_gb = round($size_gb, 2);

      $free_mb = $free / 1024 / 1024;
      $free_gb = $free_mb / 1024;
      $free_gb = round($free_gb, 2);

      $ret['free'] = $free;
      $ret['size'] = $size;
      $ret['free_mb'] = $free_mb;
      $ret['free_gb'] = $free_gb;
      $ret['size_mb'] = $size_mb;
      $ret['size_gb'] = $size_gb;

      $values = array('free_gb' => $free_gb, 'size_gb' => $size_gb);
      PropertySetting::savePropertySetting(0, $values);

      $rules = array();
      $rules['low_free_size'] = 500;
      $rules['low_free_notify'] = 1;
      $rules['low_free_emails'] = '';
      $rules['low_free_send_flag'] = 0;

      $rules = PropertySetting::getPropertySettings(0, $rules);    
      if( empty($rules['low_free_emails']) || 
              $rules['low_free_notify'] != 1 
              )
      {

      } 
      else
      {
        if( $free_mb > $rules['low_free_size'] )
        {
          $values = array('low_free_send_flag' => 0);
          PropertySetting::savePropertySetting(0, $values);
        }
        else
        {
          if( $rules['low_free_send_flag'] == 0 )
          {
            // set send flag
            $values = array('low_free_send_flag' => 1);
            PropertySetting::savePropertySetting(0, $values);

		        $smtp = Functions::getMailSetting(0, '');

            $message = array();
            $message['type'] = 'email';
            $message['to'] = $rules['low_free_emails'];
            $message['subject'] = "Disk has low free space";        
            $message['content'] = "Total Size $size_gb, Free Size $free_gb";
            $message['smtp'] = $smtp;
            $message['payload'] = array();
        
            Redis::publish('notify', json_encode($message));
          }
        }
      } 
      return Response::json($ret);
    }
}
