<?php

namespace App\Http\Controllers\Backoffice\Configuration;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Http\Controllers\UploadController;

use App\Models\Common\AdminArea;
use App\Models\Common\Building;
use App\Models\Common\CommonFloor;
use App\Models\Common\PropertySetting;
use Excel;
use DB;
use Datatables;
use Response;

class HelpdeskController extends Controller
{
   
    public function saveItImapConfig(Request $request) {
        $input = $request->except(['property_id']);
        $property_id = $request->get('property_id', 0);
        
        $sub_domain = '';
        PropertySetting::savePropertySettings($property_id, $input, $sub_domain);

        return Response::json($input);
    }

    function getItImapConfig(Request $request) {
        $property_id = $request->get('property_id' , 0);
        
        $rules = array();
        $rules['it_imap_host'] = '';
        $rules['it_imap_user'] = '';
        $rules['it_imap_pass'] = '';
        $rules['it_imap_port'] = 993;
        $rules['it_imap_tls'] = true;
        $rules['it_helpdesk_update_notify'] = true;

        $rules = PropertySetting::getPropertySettings($property_id, $rules);

        return Response::json($rules);
    }


    

   


}
