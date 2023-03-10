<?php

namespace App\Http\Middleware;


use Closure;

use App\Models\Common\GuestLogin;
use Response;
use App\Http\Requests;

class GuestAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $auth_info = $request->header('Authorization');
        $auth_info = base64_decode(substr($auth_info, strlen('Basic ')));

        $auth_array = explode(':', $auth_info);
        if( count($auth_array) != 2 )
        {
            return response('You need to login again. header is not correct', 401);
        }

        $guest_id = $auth_array[0];
        $access_token = $auth_array[1];

        $auth = GuestLogin::find($guest_id);
        if( empty($auth) || $auth->access_token != $access_token )
        {
            return response('You have been logged out due to another session being opened with the same user.', 401);
        }

        $request->attributes->add(['guest_id' => $guest_id]);
        $request->attributes->add(['auth_info' => $auth_info]);

        return $next($request);
    }
}
