<?php

namespace App\Http\Controllers\Backoffice\Property;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Functions;
use Illuminate\Support\Facades\Response;

class LicenseWizardController extends Controller
{
    function checkLicense(Request $request)
	{
		// ===================  check license ================================
        $meta = Functions::getLicenseInfo();
        
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
