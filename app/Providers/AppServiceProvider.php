<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Models\Event\Event; // Import the Event model for custom route binding

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
        
    }
}
