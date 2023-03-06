<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Modules\Functions;

use Response;

use Obfuscator;
use Storage;
use Redis;


class EncryptGateway extends Controller
{
    private $request;
	
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function file_encrypt(Request $request)
    {   
        $app_path = app_path();
        $file_list = [
            "\Http\Controllers\UserController.php",
            "\Http\Middleware\AuthMiddleware.php",
            "\Http\Middleware\InterfaceAuthMiddleware.php",
            "\Http\Controllers\EncryptGateway.php",
            "\Http\Controllers\Backoffice\Property\LicenseWizardController.php",
            "\Modules\Functions.php",
            "\Http\\routes.php",
        ];

        foreach($file_list as $item)
        {
            $filepath = $app_path . $item;
        
            Obfuscator::obfuscateFileFromTo($filepath, $filepath);                    
        }

        echo "Obfuscator is done successfully";
    }

    public function encrypt(Request $request)
    {   
        $device_id =  Redis::get('device_id');          
        echo $device_id;
        echo '<br/>';
        
        $meta = array();
        $meta['identity'] = "Ennovatech";
        $meta['device_id'] = $device_id;        
        $meta['start_day'] = '2019-09-24';
        $meta['end_day'] = '2025-10-24';
        $message = json_encode($meta);

        $key = md5($device_id . 'EnnovaTech');

        $encrypter = new \Illuminate\Encryption\Encrypter( $key, "AES-256-CBC" );
    
        $ciphertext = $encrypter->encrypt( $message );
        echo '<br/>';
        echo $ciphertext;

        $license_path = Functions::GetLicensePath();
        file_put_contents($license_path, $ciphertext);

        $ciphertext = file_get_contents($license_path);

        $plaintext = $encrypter->decrypt( $ciphertext );
        echo '<br/>';
        echo $plaintext;

        $meta = json_decode($plaintext);
        echo '<br/>';
        echo $meta->start_day;
    }

    public function testDBConnect(Request $request)
    {
        echo 'Username: ' . base64_encode(base64_encode('root')) . '<br>';
        echo 'Password: ' . base64_encode(base64_encode('')) . '<br>';
    }

}