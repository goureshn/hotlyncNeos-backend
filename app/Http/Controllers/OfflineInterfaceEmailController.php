<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

class OfflineInterfaceEmailController extends Controller
{
    private $request;
    public $Json;
    public $Source;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function inboundTest(Request $request)
    {
        $emailFromName = explode("@", $request->From);
        $emailFromString = str_replace(".","_", $emailFromName[0]);
        $propertyName = "";

        if($request->Attachments){
            foreach ($request->Attachments as $value) {
                $request_attachments_content = $value["Content"];
                $request_attachments_name = $value["Name"];
                $myfile = fopen("./filestorage/".$emailFromString."-".$request_attachments_name, "wb");
                fwrite($myfile, base64_decode($request_attachments_content));
                fclose($myfile);
            }
        }

        return response()->json(['success' => 'success'], 200);
    }
}
