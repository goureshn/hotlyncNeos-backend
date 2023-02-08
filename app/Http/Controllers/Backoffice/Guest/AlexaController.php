<?php

namespace App\Http\Controllers\Backoffice\Guest;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
//use App\Models\Service\AlexaRoomDevice;

use Response;
use Datatables;
use DB;

class AlexaController extends Controller
{
   	public function index(Request $request)
    {
       
        return view('backoffice.wizard.guestservice.alexa');
    }

    public function alexaCheck(Request $request){
        return response()->json([
            'Check' => 'ok'
        ]);
    }
}