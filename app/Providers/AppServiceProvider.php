<?php

namespace App\Providers;

use App\Http\Middleware\CheckAnyRole;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\Event\Event; 
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;// Import the Event model for custom route binding

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        // Route::bind('event_flexible', function ($value) {
        //     return is_numeric($value)
        //         ? Event::findOrFail($value)
        //         : Event::where('slug', $value)->firstOrFail();
        // });
        Route::aliasMiddleware('role', RoleMiddleware::class);
        Route::aliasMiddleware('permission', PermissionMiddleware::class);
        Route::aliasMiddleware('role_or_permission', RoleOrPermissionMiddleware::class);
        Route::aliasMiddleware('check.any.role', CheckAnyRole::class);
        
    }
}
