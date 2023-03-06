<?php

namespace App\Http\Controllers;

use App;
use App\Models\Common\CommonUser;
use App\Models\Common\PropertySetting;
use App\Models\Service\HskpStatusLog;
use App\Modules\Functions;
use Artisan;
use DB;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Redirect;
use Response;
use View;
use DateTime;

class ReviewproController extends Controller
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function index(Request $request)
    {

    }

    public function sendSurvey($property_id, $guest_id, $type){

        //check Review Pro is enabled

        $reviewpro_enabled = DB::table('property_setting')
            ->where('settings_key', 'reviewpro_enable_api')
            ->where('property_id', $property_id)
            ->first();

        if(empty($reviewpro_enabled)){ // no setting available
            return;
        }
        if($reviewpro_enabled->value == '0'){ // reviewpro disabled
            return;
        }

        //guest details

        $guest_details = DB::table('common_guest')
            ->where('property_id', $property_id)
            ->where('guest_id', $guest_id)
            ->first();

        //survey list

        $survey_list = DB::table('reviewpro_survey_list')
            ->where('enable','1')
            ->where('type', $type)
            ->where('property_id', $property_id)
            ->get();

        //send surveys

        $api_key = DB::table('property_setting')
            ->where('settings_key', 'reviewpro_api_key')
            ->where('property_id', $property_id)
            ->first();

        $api_secret = DB::table('property_setting')
            ->where('settings_key', 'reviewpro_api_secret')
            ->where('property_id', $property_id)
            ->first();

        $url = DB::table('property_setting')
            ->where('settings_key', 'reviewpro_url')
            ->where('property_id', $property_id)
            ->first();

        foreach ($survey_list as $survey) {
            $this->sendToAPI($api_key->value, $api_secret->value, $url->value, $survey->pms_id, $survey->survey_id, $guest_details);
        }
        

    }

    public function sendToAPI($api_key, $api_secret, $url, $pms_id, $survey_id, $guest_details){

        $pmsId = $pms_id;
        $surveyId = $survey_id;

        $apiKey = $api_key;
        $apiSecret = $api_secret;
        $URL = $url;

        $timestamp = time();
        $stringToSign = $apiKey . $apiSecret . $timestamp;
        $hash = hash('sha256', $stringToSign, true);
        $signature = bin2hex($hash);

        $apiURL = $URL."/v1/pms/guests/".$surveyId."?api_key=".$apiKey."&sig=".$signature;

        $headers = [
            'Content-Type: application/json'
        ];

        $reqBody = (object)[
            "pmsId" => $pmsId,
            "firstName" => $guest_details->first_name,
            "checkin" => $guest_details->arrival,
            "checkout" => $guest_details->departure,
            "language" => "en",
            "email" => $guest_details->email
        ];
        $reqObj = [$reqBody];

        $postData = json_encode($reqObj);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST' );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $server_response = curl_exec($ch);

        curl_close($ch);

        $json_survey_response = json_decode($server_response, true);

        if( isset( $json_survey_response['id'] ) ){

            DB::table('reviewpro_survey_log')
            ->insert([
                'guest_id' => $guest_details->id,
                'survey_id' => $survey_id
            ]);
        }

        return true;
    }

    public function getAPISurveyList(Request $request)
    {
        $endpoint = 'https://connect.reviewpro.com/v1/surveys';
        $params = array('api_key' => 'ju96czyfqz6rge39z3bx29puaeu3j2wqk2wqkv5z');
        $url = $endpoint . '?' . http_build_query($params);

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        $reqObj = (object)[
            "api_key" => "ju96czyfqz6rge39z3bx29puaeu3j2wqk2wqkv5z"
        ];

        $postData = json_encode($reqObj);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET' );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        $server_response = curl_exec($ch);

        curl_close($ch);

        $json_survey_response = json_decode($server_response, true);
        return $server_response;
    }
}