<?php

use App\Http\Controllers\API\Events\InterviewRequestController;
use App\Http\Controllers\API\Events\InterviewSlotController;
use App\Http\Controllers\API\Events\JobFairController;
use App\Http\Controllers\API\Events\JobFairParticipationController;
use App\Http\Controllers\API\Events\LiveEventController;

use App\Http\Controllers\API\Events\JobProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MediaController;
use Illuminate\Http\Request;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\API\Users\UserController;
use App\Http\Controllers\API\Users\TrackController;

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\BulkMessageController;
use App\Http\Controllers\FeedbackController;

use App\Http\Controllers\Event\EventController;
use App\Http\Controllers\Event\EventRegistrationController;
use App\Http\Controllers\Event\EventSessionController;
use App\Http\Controllers\Event\EventStaffController;
use Spatie\Permission\Middleware\RoleMiddleware;

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

// Test routes for User API endpoints (no authentication required)
Route::prefix('test/users')->group(function () {
    Route::get('/', [UserController::class, 'index']);                // Test listing users with filters & pagination
    Route::post('/', [UserController::class, 'store']);               // Test creating a user
    Route::get('/{id}', [UserController::class, 'show']);             // Test showing a user
    Route::put('/{id}', [UserController::class, 'update']);           // Test updating a user
    Route::delete('/{id}', [UserController::class, 'destroy']);       // Test deleting a user
});

// Test routes for Track API endpoints (no authentication required)
Route::prefix('test/tracks')->group(function () {
    Route::get('/', [TrackController::class, 'index']);                // Test listing tracks with filters & pagination
    Route::post('/', [TrackController::class, 'store']);               // Test creating a track
    Route::get('/{id}', [TrackController::class, 'show']);             // Test showing a track
    Route::put('/{id}', [TrackController::class, 'update']);           // Test updating a track
    Route::delete('/{id}', [TrackController::class, 'destroy']);       // Test deleting a track
});

// Test routes for LiveEvent API endpoints (no authentication required for testing)
Route::prefix('test/live')->group(function () {
    Route::get('/events/{id}/status', [LiveEventController::class, 'status']);     // Get live status of an event
    Route::post('/events/{id}/start', [LiveEventController::class, 'start']);      // Start a live event
    Route::post('/events/{id}/end', [LiveEventController::class, 'end']);          // End a live event
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
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');;
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');;
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/profile', [AuthController::class, 'profile']);
    });
});

    // Protected routes (requires authentication and role-based access)
    // Route::group( [],function () {    // User profile routes
    // Route::middleware(['jwt.auth'])->get('/profile', [AuthController::class, 'profile']);
    // Route::post('/logout', [AuthController::class, 'logout']);
    // Route::post('/refresh', [AuthController::class, 'refresh']);

    /*
    |--------------------------------------------------------------------------
    | Example Event Routes (commented out)
    |--------------------------------------------------------------------------
    |
    */
    Route::middleware(['auth:api'])->prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index']); // List all events
        Route::post('/', [EventController::class, 'store']);
        
        // Specific routes FIRST (these need to come before the generic /{event_flexible})
        Route::get('/{event_flexible}/publish', [EventController::class, 'publish']);
        Route::get('/{event_flexible}/archive', [EventController::class, 'archive']);
        Route::get('/{event_flexible}/banner', [EventController::class, 'getBanner']);
        Route::post('/{event_flexible}/banner', [EventController::class, 'uploadBanner']);
        
        
        /* Event Sessions */
        Route::get('/{event_flexible}/sessions', [EventSessionController::class, 'index']);
        Route::post('/{event_flexible}/sessions', [EventSessionController::class, 'create']);
        Route::put('/{event_flexible}/sessions/{session}', [EventSessionController::class, 'update']);
        Route::delete('/{event_flexible}/sessions/{session}', [EventSessionController::class, 'destroy']);

        // Live Event routes
        Route::prefix('{event_flexible}/live')->group(function () {
            Route::get('/status', [LiveEventController::class, 'status']);                      // Get live status (public/company)
            Route::post('/start', [LiveEventController::class, 'start'])->middleware('role:admin');    // Start live event (admin only)
            Route::post('/end', [LiveEventController::class, 'end'])->middleware('role:admin');        // End live event (admin only)
        });

        
         /* Event Registration */
        Route::post('/{event_flexible}/register', [EventRegistrationController::class, 'register']);
        Route::get('/{event_flexible}/registrations', [EventRegistrationController::class, 'registrations']);
        Route::patch('/{event_flexible}/cancel-registration', [EventRegistrationController::class, 'cancelMyRegistration']);
        
        // Generic routes LAST
        Route::get('/{event_flexible}', [EventController::class, 'show']);
        Route::put('/{event_flexible}', [EventController::class, 'update']);
        Route::delete('/{event_flexible}', [EventController::class, 'destroy'])->middleware(RoleMiddleware::class.':admin');
    });
    /*
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
    // });
    */
    // });

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
    }); // <-- This closes the Route::middleware(['auth'])->prefix('dashboard')->group

 // Job Fair Routes No Middleware Yet
Route::prefix('job-fairs')->group(function(){
    Route::get('/', [JobFairController::class, 'index']);
    Route::get('/{jobFairId}', [JobFairController::class, 'show']);
    Route::post('/', [JobFairController::class, 'store']);
    Route::put('/{jobFairId}', [JobFairController::class, 'update']);
    Route::delete('/{jobFairId}', [JobFairController::class, 'destroy']);
    Route::get('/{jobFairId}/companies', [JobFairController::class, 'Companies']);
    Route::get('/{jobFairId}/statistics', [JobFairController::class, 'statistics']);

    Route::post('/{jobFairId}/participate', [JobFairParticipationController::class, 'store']);
    Route::get('/{jobFairId}/participations', [JobFairParticipationController::class, 'index']);
    Route::get('/{jobFairId}/participations/{participationId}', [JobFairParticipationController::class, 'show']);
    Route::put('/{jobFairId}/participations/{participationId}', [JobFairParticipationController::class, 'review']);

    Route::get('/{jobFairId}/participations/{participationId}/job-profiles', [JobProfileController::class, 'jobProfilesPerParticipation']);
    Route::get('{jobFairId}/job-profiles', [JobProfileController::class, 'jobProfilesPerJobFair']);
    Route::post('/{jobFairId}/participations/{participationId}/job-profiles', [JobProfileController::class, 'store']);
    Route::get('/job-profiles/{jobProfileId}', [JobProfileController::class, 'show']);
    Route::put('/job-profiles/{jobProfileId}', [JobProfileController::class, 'update']);
    Route::delete('/job-profiles/{jobProfileId}', [JobProfileController::class, 'destroy']);

    Route::get('/{jobFairId}/interview-slots', [InterviewSlotController::class, 'jobFairSlots']);
    Route::get('/{jobFairId}/participations/{participationId}/interview-slots', [InterviewSlotController::class, 'participationSlots']);
    Route::post('/{jobFairId}/participations/{participationId}/interview-slots', [InterviewSlotController::class, 'store']);
    Route::get('/{jobFairId}/participations/{participationId}/interview-slots/{slotId}', [InterviewSlotController::class, 'show']);
    Route::put('/{jobFairId}/participations/{participationId}/interview-slots/{slotId}', [InterviewSlotController::class, 'update']);
    Route::delete('/{jobFairId}/participations/{participationId}/interview-slots/{slotId}', [InterviewSlotController::class, 'destroy']);

    Route::post('{jobFairId}/interview-requests', [InterviewRequestController::class, 'store']);
    Route::get('{jobFairId}/interview-requests/my', [InterviewRequestController::class, 'myRequests']);
    Route::get('{jobFairId}/job-profiles/{jobProfileId}/interview-requests', [InterviewRequestController::class, 'jobProfileRequests']);
    Route::put('interview-requests/{requestId}/review', [InterviewRequestController::class, 'review']);
});

// Feedback Routes
Route::prefix('feedback')->middleware(['auth:sanctum'])->group(function () {

    // Get feedback forms for an event (all users)
    Route::get('/events/{eventId}/forms', [FeedbackController::class, 'getEventFeedbackForms']);
    // Create feedback form (admin only)
    Route::post('/events/{eventId}/forms', [FeedbackController::class, 'createFeedbackForm'])->middleware('role:admin');
    // Submit feedback response (students)
    Route::post('/forms/{formId}/responses', [FeedbackController::class, 'submitFeedbackResponse']);
    // Get feedback responses (admin only)
    Route::get('/forms/{formId}/responses', [FeedbackController::class, 'getFeedbackResponses'])->middleware('role:admin');
    // Toggle form status (admin only)
    Route::patch('/forms/{formId}/toggle', [FeedbackController::class, 'toggleFeedbackForm'])->middleware('role:admin');
});


// Notifications Routes 
Route::prefix('notifications')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});



// Bulk Messages Routes 
Route::prefix('bulk-messages')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/', [BulkMessageController::class, 'index']);
    Route::post('/', [BulkMessageController::class, 'store']);
    Route::post('/{id}/send', [BulkMessageController::class, 'send']);
    Route::get('/{id}/status', [BulkMessageController::class, 'status']);
});
