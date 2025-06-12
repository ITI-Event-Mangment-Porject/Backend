<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\CompanyController;


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

// This route is for testing the API connection
Route::get('/test-connection', function () {
    return response()->json(['status' => 'working']);
});

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
Route::group(['middleware' => ['auth:api']], function () {    // User profile routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

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

//companyController still need admin middleware 
Route::prefix('companies')->group(function () {
    Route::get('/', [CompanyController::class, 'index']);
    Route::get('/{id}', [CompanyController::class, 'show']);

    Route::post('/', [CompanyController::class, 'store']);
    Route::put('/{id}', [CompanyController::class, 'update']);
    Route::post('/{id}/approve', [CompanyController::class, 'approve']);
    Route::post('/{id}/reject', [CompanyController::class, 'reject']);
    Route::post('/{id}/logo', [CompanyController::class, 'uploadLogo']);


});

// media controller 
Route::prefix('media')->group(function () {
    Route::post('/upload', [MediaController::class, 'upload']);
    Route::get('/{id}', [MediaController::class, 'download']);
    Route::get('/{id}/public', [MediaController::class, 'publicAccess']);
    Route::middleware('role:admin')->delete('/{id}', [MediaController::class, 'destroy']); //by admin
});

// dashboard controller
Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/student', [DashboardController::class, 'studentDashboard']);
    Route::get('/company', [DashboardController::class, 'companyDashboard']);
    Route::get('/staff', [DashboardController::class, 'staffDashboard']);

    // admin subroutes
    Route::prefix('admin')->group(function () {
        Route::get('/overview', [DashboardController::class, 'adminOverview']);
        Route::get('/events', [DashboardController::class, 'adminEvents']);
        Route::get('/users', [DashboardController::class, 'adminUsers']);
        Route::get('/companies', [DashboardController::class, 'adminCompanies']);
        Route::get('/live-events', [DashboardController::class, 'adminLiveEvents']);
    });

});

//setting controller  missing middleware of admin
Route::prefix('settings')->group(function () {
    Route::get('/', [SettingController::class, 'index']);
    Route::put('/{key}', [SettingController::class, 'update']);
    Route::get('/public', [SettingController::class, 'getPublic']);
});

// missing middleware
Route::prefix('/reports')->group(function () {
    Route::get('/events', [ReportController::class, 'eventsReports']);
    Route::get('/attendance', [ReportController::class, 'attendanceReports']);
    Route::get('/feedback', [ReportController::class, 'feedbackReports']);
    Route::get('/export', [ReportController::class, 'exportReports']);
});