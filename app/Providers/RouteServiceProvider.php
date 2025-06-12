<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use App\Models\Event; // Import the Event model for custom route binding
use Illuminate\Http\Request;
use App\Http\Controllers\Event\EventController;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        
        
        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
        
        // Custom route model binding
    Route::bind('event_flexible', function ($value) {
        return is_numeric($value)
            ? Event::findOrFail($value)
            : Event::where('slug', $value)->firstOrFail();
    });

        
    }
}
