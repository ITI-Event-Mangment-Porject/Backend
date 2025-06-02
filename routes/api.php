<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. 
|
*/

// Public routes
Route::group([], function () {
    // Authentication routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    /*
    |--------------------------------------------------------------------------
    | Public Event Routes Example 
    |--------------------------------------------------------------------------
    |
    // Get list of active/upcoming events
    // Route::get('/events/active', [EventController::class, 'getActiveEvents']);
    // 
    // Search events
    // Route::get('/events/search', [EventController::class, 'search']);
    //
    // Get event details by slug
    // Route::get('/events/{slug}', [EventController::class, 'getEventBySlug']);
    //
    // Get event categories
    // Route::get('/categories', [CategoryController::class, 'index']);
    //
    // Contact form submission
    // Route::post('/contact', [ContactController::class, 'submit']);
    //
    // Newsletter subscription
    // Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
    */
});

// Protected routes (requires authentication)
Route::group(['middleware' => ['auth:sanctum']], function () {
    // User profile routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    /*
    |--------------------------------------------------------------------------
    | Example Event Routes (commented out)
    |--------------------------------------------------------------------------
    |
    // Route::prefix('events')->group(function () {
    //     Route::get('/', [EventController::class, 'index']);     // Get all events
    //     Route::post('/', [EventController::class, 'store']);    // Create event
    //     Route::get('/{event}', [EventController::class, 'show']);    // Get single event
    //     Route::put('/{event}', [EventController::class, 'update']);  // Update event
    //     Route::delete('/{event}', [EventController::class, 'destroy']); // Delete event
    //     
    //     // Event registration
    //     Route::post('/{event}/register', [EventController::class, 'register']);
    //     Route::delete('/{event}/cancel', [EventController::class, 'cancelRegistration']);
    // });

    |--------------------------------------------------------------------------
    | Example Category Routes (commented out)
    |--------------------------------------------------------------------------
    |
    // Route::apiResource('categories', CategoryController::class);

    |--------------------------------------------------------------------------
    | Example User Management Routes (commented out)
    |--------------------------------------------------------------------------
    |
    // Route::prefix('users')->middleware(['admin'])->group(function () {
    //     Route::get('/', [UserController::class, 'index']);          // List users
    //     Route::get('/{user}', [UserController::class, 'show']);     // Get user details
    //     Route::put('/{user}', [UserController::class, 'update']);   // Update user
    //     Route::delete('/{user}', [UserController::class, 'destroy']); // Delete user
    //     Route::patch('/{user}/role', [UserController::class, 'updateRole']); // Update role
    // });
    */
});