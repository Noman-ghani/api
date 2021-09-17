<?php

namespace App\Http\Middleware;

use App\Helpers\Helpers;
use App\Models\Businesses;
use App\Models\BusinessUsers;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $auth_guard = $this->auth->guard($guard);
        
        if ($auth_guard->guest()) {
            return response('Unauthorized.', 401);
        }

        foreach ($request->input() as $key => $value) {
            if ($value === "") {
                $request->request->set($key, null);
            }
        }

        if ($request->has("phone_number")) {
            $request->request->set("phone_number", preg_replace("/[^0-9]/", '', $request->get("phone_number")));
        }

        $business = Businesses::with("timezone")->whereId(Helpers::getJWTData("business_id"))->first();

        if (!$business) {
            return response('Unauthorized.', 401);
        }

        $request->request->set("business", $business);

        return $next($request);
    }
}
