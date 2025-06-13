<?php

use App\Http\Controllers\API\Events\InterviewRequestController;
use App\Http\Controllers\API\Events\InterviewSlotController;
use App\Http\Controllers\API\Events\JobFairController;
use App\Http\Controllers\API\Events\JobFairParticipationController;
use App\Http\Controllers\API\Events\JobProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use Illuminate\Http\Request;
use Illuminate\Queue\Jobs\Job;
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
    Route::post('/', [CompanyController::class, 'store']);
    Route::get('/', [CompanyController::class, 'index']);
    Route::get('/{id}', [CompanyController::class, 'show']);
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
    Route::delete('/{id}', [MediaController::class, 'destroy']); //by admin
});

// dashboard controller
Route::middleware(['auth'])->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/admin', [DashboardController::class, 'adminDashboard']);
    Route::get('/company', [DashboardController::class, 'companyDashboard'])->middleware('role:company');
    Route::get('/staff', [DashboardController::class, 'staffDashboard'])->middleware('role:staff');
    
    // admin subroutes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/overview', [DashboardController::class, 'adminOverview']);
        Route::get('/events', [DashboardController::class, 'adminEvents']);
        Route::get('/users', [DashboardController::class, 'adminUsers']);
        Route::get('/companies', [DashboardController::class, 'adminCompanies']);
        Route::get('/live-events', [DashboardController::class, 'adminLiveEvents']);
    });

});

 // Job Fair Routes No Middleware Yet
Route::prefix('job-fairs')->group(function(){
    Route::get('/', [JobFairController::class, 'index']);
    Route::get('/{job_fair_id}', [JobFairController::class, 'show']);
    Route::post('/', [JobFairController::class, 'store']);
    Route::put('/{job_fair_id}', [JobFairController::class, 'update']);
    Route::delete('/{job_fair_id}', [JobFairController::class, 'destroy']);
    Route::get('/{job_fair_id}/companies', [JobFairController::class, 'Companies']);
    Route::get('/{job_fair_id}/statistics', [JobFairController::class, 'statistics']);

    Route::post('/{job_fair_id}/participate', [JobFairParticipationController::class, 'store']);
    Route::get('/{job_fair_id}/participations', [JobFairParticipationController::class, 'index']);
    Route::get('/{job_fair_id}/participations/{participation_company_id}', [JobFairParticipationController::class, 'show']);
    Route::put('/{job_fair_id}/participations/{participation_company_id}', [JobFairParticipationController::class, 'review']);

    Route::get('/{job_fair_id}/participations/{participation_company_id}/job-profiles', [JobProfileController::class, 'jobProfilesPerParticipation']);
    Route::get('{job_fair_id}/job-profiles', [JobProfileController::class, 'jobProfilesPerJobFair']);
    Route::post('/{job_fair_id}/participations/{participation_company_id}/job-profiles', [JobProfileController::class, 'store']);
    Route::get('/job-profiles/{job_profile_id}', [JobProfileController::class, 'show']);
    Route::put('/job-profiles/{job_profile_id}', [JobProfileController::class, 'update']);
    Route::delete('/job-profiles/{job_profile_id}', [JobProfileController::class, 'destroy']);

    Route::get('/{job_fair_id}/interview-slots', [InterviewSlotController::class, 'jobFairSlots']);
    Route::get('/{job_fair_id}/participations/{participation_id}/interview-slots', [InterviewSlotController::class, 'participationSlots']);
    Route::post('/{job_fair_id}/participations/{participation_id}/interview-slots', [InterviewSlotController::class, 'store']);
    Route::get('/interview-slots/{slot_id}', [InterviewSlotController::class, 'show']);
    Route::put('/interview-slots/{slot_id}', [InterviewSlotController::class, 'update']);
    Route::delete('/interview-slots/{slot_id}', [InterviewSlotController::class, 'destroy']);

    Route::post('{job_fair_id}/interview-requests', [InterviewRequestController::class, 'store']);
    Route::get('{job_fair_id}/interview-requests/my', [InterviewRequestController::class, 'myRequests']);
    Route::get('{job_fair_id}/interview-requests/company', [InterviewRequestController::class, 'companyRequests']);
    Route::put('interview-requests/{request_id}/review', [InterviewRequestController::class, 'review']);
});