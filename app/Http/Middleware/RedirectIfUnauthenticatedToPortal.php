<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Models\Auth\User;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Config;


class RedirectIfUnauthenticatedToPortal
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Attempt to parse and authenticate the token
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            // Token is missing or invalid — redirect to portal
            $redirectBack = urlencode(route('sso.login'));
            $portalLoginUrl = config('services.portal.login_url') . '?redirect=' . $redirectBack;

            return redirect()->away($portalLoginUrl);
        }

        return $next($request);
    }
}
