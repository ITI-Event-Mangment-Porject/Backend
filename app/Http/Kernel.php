<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;

use App\Http\Middleware\RedirectIfUnauthenticatedToPortal;
use Tymon\JWTAuth\Http\Middleware\Authenticate as JwtAuthenticate;
use Tymon\JWTAuth\Http\Middleware\RefreshToken as JwtRefreshToken;
use Tymon\JWTAuth\Http\Middleware\CheckForToken as JwtCheckForToken;
use Tymon\JWTAuth\Http\Middleware\AuthenticateAndRenew as JwtAuthenticateAndRenew;
use Tymon\JWTAuth\Http\Middleware\AuthenticateWithBasicAuth as JwtAuthenticateWithBasicAuth;
use Tymon\JWTAuth\Http\Middleware\CheckForTokenInRequest as JwtCheckForTokenInRequest;
use Tymon\JWTAuth\Http\Middleware\RefreshTokenInRequest as JwtRefreshTokenInRequest;
use Tymon\JWTAuth\Http\Middleware\AuthenticateAndRenewInRequest as JwtAuthenticateAndRenewInRequest;
use Tymon\JWTAuth\Http\Middleware\AuthenticateWithBasicAuthInRequest as JwtAuthenticateWithBasicAuthInRequest;
use Tymon\JWTAuth\Http\Middleware\AuthenticateWithBearerToken as JwtAuthenticateWithBearerToken;
use Tymon\JWTAuth\Http\Middleware\CheckForBearerToken as JwtCheckForBearerToken;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        \Illuminate\Http\Middleware\HandleCors::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's middleware aliases.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'redirect.to.portal' => \App\Http\Middleware\RedirectIfUnauthenticatedToPortal::class,
        'jwt.auth' => \Tymon\JWTAuth\Http\Middleware\Authenticate::class,

        
    ];
}