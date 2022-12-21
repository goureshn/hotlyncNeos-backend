<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Hotlync Api",
 *      description="Ennovatech Solutions",
 *      @OA\Contact(
 *          email="peter.noronha@ennovatech.com"
 *      )
 * )
 * 
 * @OA\Get(
 *     path="/",
 *     description="Home page",
 *     tags={"Home"},
 *     @OA\Response(response="default", description="Welcome page")
 * )
 */

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
