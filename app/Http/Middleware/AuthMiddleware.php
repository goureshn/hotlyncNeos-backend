<?php

namespace App\Http\Middleware;
use App\Modules\Functions;

use Closure;

use App\Models\Common\CommonUser;
use Response;

class AuthMiddleware
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
        $user_id = $auth_array[0];
        $access_token = $auth_array[1];

        if( $user_id == 0 && $access_token == config('app.super_access_token') )
        {
            $request->attributes->add(['user_id' => $user_id]);
            $request->attributes->add(['auth_info' => $auth_info]);

            return $next($request);
        }
        
        $auth = CommonUser::find($user_id);
        if( $user_id > 0 && (empty($auth) || $auth->access_token != $access_token) )
        {
            return response('You have been logged out due to another session being opened with the same user.', 401);
        }

        // ===================  check license ================================
        $meta = Functions::CheckLicense();

        if( is_numeric($meta) )
        {
            if( $meta == 1 )       
                return response('You have been logged out due to invalid server device.', 401);   
            else if( $meta == 2 )       
                return response('You have been logged out due to non exist license.', 401);  
            else if( $meta == 3 )       
                return response('You have been logged out due to invalid license.', 401);   
            else if( $meta > 3 )       
                return response('You have been logged out due to license expired.', 401);   
        }
        
        // ==============================================================================


        $property_ids_by_jobrole = CommonUser::getPropertyIdsByJobrole($user_id);

        $request->attributes->add(['user_id' => $user_id]);
        $request->attributes->add(['auth_info' => $auth_info]);
        $request->attributes->add(['property_ids_by_jobrole' => $property_ids_by_jobrole]);
        $request->attributes->add(['source' => 0]);

        return $next($request);
    }
}
