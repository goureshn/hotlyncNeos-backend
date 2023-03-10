<?php

namespace App\Http\Middleware;


use Closure;

use App\Models\Common\CommonUser;
use Response;
use App\Http\Requests;

class MobileAuthMiddleware
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
        $auth_info = $request->get('access_token');
        $auth_info = base64_decode($auth_info);

        $auth_array = explode(':', $auth_info);
        if( count($auth_array) != 3 )
        {
            return response('Your device has no login session.', 402);
        }
        $user_id = $auth_array[0];
        $access_token = $auth_array[1];
        $device_id = $auth_array[2];
        $auth = CommonUser::find($user_id);
        if( empty($auth) || ($auth->device_id != $device_id ))
        {
            return response('You have been logged out due to another session being opened with the same user.', 402);
        }
        // if(($auth->device_id != $device_id ))
        // {
        //     return response('You have been logged out.', 402);
        // }
        $request->attributes->add(['user_id' => $user_id]);
        $request->attributes->add(['device_id' => $device_id]);
        $request->attributes->add(['auth_info' => $auth_info]);
        $request->attributes->add(['source' => 1]);

        return $next($request);
    }
}
