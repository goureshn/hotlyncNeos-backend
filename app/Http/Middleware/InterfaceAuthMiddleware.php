<?php

namespace App\Http\Middleware;

use App\Modules\Functions;
use Closure;

use App\Models\Common\CommonUser;
use Response;
use App\Http\Requests;

class InterfaceAuthMiddleware
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
        // ===================  check license ================================
        $meta = Functions::CheckInterfaceLicense();

        if( is_numeric($meta) )       
            return response('You have been logged out due to invalid server device.', 401);           
        // ==============================================================================

        return $next($request);
    }
}
