<?php

namespace App\Http\Middleware;
use App\Modules\Functions;

use Closure;

use App\Models\Common\CommonUser;
use Response;
use App\Http\Requests;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
// use Log;

class AfterMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    protected $factory;

    public function __construct(ResponseFactory $factory)
    {
        $this->factory = $factory;
    }

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Do stuff
        $hash = $request->get('hash', '');
        $content = $response->content();        
        $result_hash = sha1($content);       

        // $ret = [];
        // $ret['hash'] = $hash;
        // $ret['new_hash'] = $result_hash;

        // return Response::json($ret); 

        if( $hash != $result_hash )
        {
            $response = $response->header('result_hash', $result_hash);
            return $response;
        }
        else
        {
            $ret = array();
            $ret['sync'] = 1;
            return Response::json($ret);
        }    
    }
}
