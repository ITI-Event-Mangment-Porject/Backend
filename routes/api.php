<?php

use App\Http\Controllers\API\Events\BrandingDayController;
use App\Http\Controllers\API\Events\InterviewQueueController;
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
use App\Http\Controllers\LiveQueueController;
use Spatie\Permission\Middleware\RoleMiddleware;
use App\Http\Controllers\AnalyticsController;

use App\Models\Auth\User;


use App\Http\Controllers\AIInsightsController;


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
        Route::get('/{event_flexible}/publish', [EventController::class, 'publish'])->middleware('role:admin'); // Publish an event (admin only)
        Route::get('/{event_flexible}/unpublish', [EventController::class, 'unpublish'])->middleware('role:admin'); // Unpublish an event (admin only)
        Route::get('/{event_flexible}/archive', [EventController::class, 'archive'])->middleware('role:admin');
        Route::get('/{event_flexible}/banner', [EventController::class, 'getBanner']);
        Route::post('/{event_flexible}/banner', [EventController::class, 'uploadBanner'])->middleware('role:admin');
        Route::put('/{event_flexible}/banner', [EventController::class, 'uploadBanner'])->middleware('role:admin');
        
        
        
        /* Event Sessions */
        Route::get('/{event_flexible}/sessions', [EventSessionController::class, 'index']);
        Route::post('/{event_flexible}/sessions', [EventSessionController::class, 'createSession'])->middleware('check.any.role:admin,staff'); // Create a new session (admin/staff only)
        Route::get('/{event_flexible}/sessions/{session}', [EventSessionController::class, 'show']);
        
        Route::put('/{event_flexible}/sessions/{session}', [EventSessionController::class, 'update'])->middleware('role:admin'); // Update a session (admin/staff only)
        Route::delete('/{event_flexible}/sessions/{session}', [EventSessionController::class, 'destroy'])->middleware('role:admin'); // Delete a session (admin/staff only)

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
        Route::put('/{event_flexible}', [EventController::class, 'update'])->middleware('role:admin');
        Route::delete('/{event_flexible}', [EventController::class, 'destroy'])->middleware('role:admin');
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
    Route::middleware(['auth:api'])->prefix('dashboard')->group(function () {
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
Route::middleware(['auth:api'])->prefix('job-fairs')->group(function(){
    Route::get('/', [JobFairController::class, 'index']); 
    Route::get('/{jobFairId}', [JobFairController::class, 'show']);
    Route::post('/', [JobFairController::class, 'store'])->middleware('role:admin');
    Route::put('/{jobFairId}', [JobFairController::class, 'update'])->middleware('role:admin');
    Route::delete('/{jobFairId}', [JobFairController::class, 'destroy'])->middleware('role:admin');
    Route::get('/{jobFairId}/companies', [JobFairController::class, 'Companies'])->middleware('check.any.role:admin,staff');
    Route::get('/{jobFairId}/statistics', [JobFairController::class, 'statistics'])->middleware('check.any.role:admin,staff');

    Route::post('/{jobFairId}/participate', [JobFairParticipationController::class, 'store'])->middleware('role:company_representative');
    Route::get('/{jobFairId}/participations', [JobFairParticipationController::class, 'index'])->middleware('check.any.role:admin,staff');
    Route::get('/{jobFairId}/participations/{participationId}', [JobFairParticipationController::class, 'show'])->middleware( 'check.any.role:admin,staff,company_representative');
    Route::put('/{jobFairId}/participations/{participationId}', [JobFairParticipationController::class, 'review'])->middleware('role:admin');

    Route::get('/{jobFairId}/participations/{participationId}/job-profiles', [JobProfileController::class, 'jobProfilesPerParticipation']);
    Route::get('{jobFairId}/job-profiles', [JobProfileController::class, 'jobProfilesPerJobFair']);
    Route::post('/{jobFairId}/participations/{participationId}/job-profiles', [JobProfileController::class, 'store'])->middleware('role:company_representative');
    Route::get('/job-profiles/{jobProfileId}', [JobProfileController::class, 'show']);
    Route::put('/job-profiles/{jobProfileId}', [JobProfileController::class, 'update'])->middleware('role:company_representative');
    Route::delete('/job-profiles/{jobProfileId}', [JobProfileController::class, 'destroy'])->middleware('role:company_representative');

    Route::get('/{jobFairId}/interview-slots', [InterviewSlotController::class, 'jobFairSlots'])->middleware('role:admin');
    Route::get('/{jobFairId}/participations/{participationId}/interview-slots', [InterviewSlotController::class, 'participationSlots'])->middleware('check.any.role:admin,staff,company_representative');
    Route::post('/{jobFairId}/participations/{participationId}/interview-slots', [InterviewSlotController::class, 'store'])->middleware('role:company_representative');
    Route::get('/{jobFairId}/participations/{participationId}/interview-slots/{slotId}', [InterviewSlotController::class, 'show'])->middleware('check.any.role:admin,staff,company_representative');
    Route::put('/{jobFairId}/participations/{participationId}/interview-slots/{slotId}', [InterviewSlotController::class, 'update'])->middleware('role:company_representative');
    Route::delete('/{jobFairId}/participations/{participationId}/interview-slots/{slotId}', [InterviewSlotController::class, 'destroy'])->middleware('role:company_representative');

    Route::post('{jobFairId}/interview-requests', [InterviewRequestController::class, 'store'])->middleware('role:student');
    Route::get('{jobFairId}/interview-requests/my', [InterviewRequestController::class, 'myRequests'])->middleware('role:student');
    Route::get('{jobFairId}/job-profiles/{jobProfileId}/interview-requests', [InterviewRequestController::class, 'jobProfileRequests'])->middleware('check.any.role:admin,staff,company_representative');
    Route::put('interview-requests/{requestId}/review', [InterviewRequestController::class, 'review'])->middleware('role:company_representative');

    Route::get('/{jobFairId}/branding-day/candidates', [BrandingDayController::class, 'candidates'])->middleware('check.any.role:admin,staff');
    Route::get('/{jobFairId}/branding-day/schedule', [BrandingDayController::class, 'index']);
    Route::post('/{jobFairId}/branding-day/schedule', [BrandingDayController::class, 'store'])->middleware('role:admin');
    Route::put('/{jobFairId}/branding-day/schedule/{scheduleId}', [BrandingDayController::class, 'update'])->middleware('role:admin');
    Route::delete('/{jobFairId}/branding-day/schedule/{scheduleId}', [BrandingDayController::class, 'destroy'])->middleware('role:admin');

    Route::get('{jobFairId}/queues/slot/{slotId}', [InterviewQueueController::class, 'slotQueue'])->middleware('check.any.role:admin,staff,company_representative');
    Route::get('{jobFairId}/queues/company/{companyId}', [InterviewQueueController::class, 'companyQueues'])->middleware('check.any.role:admin,staff,company_representative');
    Route::get('{jobFairId}/queues/student/{studentId}', [InterviewQueueController::class, 'studentQueues'])->middleware('role:student');
    Route::get('{jobFairId}/queues/', [InterviewQueueController::class, 'jobFairQueues'])->middleware('check.any.role:admin,staff');
    Route::put('{jobFairId}/queues/{queueId}', [InterviewQueueController::class, 'updateQueue'])->middleware('check.any.role:admin,staff');
    Route::delete('{jobFairId}/queues/{queueId}', [InterviewQueueController::class, 'removeFromQueue'])->middleware('role:admin');
    
    
    Route::get('{jobFairId}/queues/', [InterviewQueueController::class, 'jobFairQueues']);
    Route::put('{jobFairId}/queues/{queueId}/pending', [InterviewQueueController::class, 'pending']);
    Route::put('{jobFairId}/queues/{queueId}/resume', [InterviewQueueController::class, 'resume']);
    Route::put('{jobFairId}/queues/{queueId}/requeue-last', [InterviewQueueController::class, 'requeueLast']);
    Route::put('{jobFairId}/queues/{queueId}/next', [InterviewQueueController::class, 'next']);
    Route::delete('{jobFairId}/queues/{queueId}', [InterviewQueueController::class, 'removeFromQueue']);

});

// Feedback Routes
Route::prefix('feedback')->middleware(['auth:api'])->group(function () {

    // Get feedback forms for an event (all users)
    Route::get('/events/{eventId}/forms', [FeedbackController::class, 'getEventFeedbackForms']);
    // Create feedback form (admin only)
    Route::post('/events/{eventId}/forms', [FeedbackController::class, 'createFeedbackForm'])->middleware(RoleMiddleware::class.':admin');;
    // Submit feedback response (students)
    Route::post('/forms/{formId}/responses', [FeedbackController::class, 'submitFeedbackResponse']);
    // Get feedback responses (admin only)
    Route::get('/forms/{formId}/responses', [FeedbackController::class, 'getFeedbackResponses'])->middleware(RoleMiddleware::class.':admin');;
    // Toggle form status (admin only)
    Route::patch('/forms/{formId}/toggle', [FeedbackController::class, 'toggleFeedbackForm'])->middleware(RoleMiddleware::class.':admin');;
    // AI-powered feedback analysis
    Route::post('/events/{eventId}/analyze', [AIInsightsController::class, 'generateInsights'])
        ->middleware('check.any.role:admin');
});

// Notifications Routes 

Route::prefix('notifications')->middleware('auth:api')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::post('/admin-send', [NotificationController::class, 'storeByAdmin'])->middleware(RoleMiddleware::class.':admin') ;
    Route::get('/all', [NotificationController::class, 'allNotifications'])->middleware(['auth:api', RoleMiddleware::class.':admin']);
});

// Bulk Messages Routes 
Route::prefix('bulk-messages')->middleware('auth:api')->group(function () {
    Route::get('/', [BulkMessageController::class, 'index']);
    Route::get('/{id}/status', [BulkMessageController::class,'status']);
    Route::post('/', [BulkMessageController::class, 'store'])->middleware('role:admin');
    Route::post('/{id}/send', [BulkMessageController::class, 'send'])->middleware('role:admin');
});


Route::prefix('analytics')->middleware(['auth:api', RoleMiddleware::class . 'role:admin'])->group(function () {
    Route::get('/dashboard', [AnalyticsController::class, 'getDashboardAnalytics']);
    Route::get('/events/{eventId}', [AnalyticsController::class, 'getEventAnalytics']);
    Route::get('/export/{eventId}', [AnalyticsController::class, 'exportEventAnalytics']);
});


Route::prefix('events/{event}/insights')->middleware(['auth', 'role:admin'])->group(function () {
    Route::post('generate', [AIInsightsController::class, 'generate']);
    Route::get('/', [AIInsightsController::class, 'index']);
});

// AI Insights Routes
Route::prefix('ai-insights')->middleware(['auth:api'])->group(function () {
    
    // Generate AI insights for event feedback (Admin only)
    Route::post('/events/{eventId}/generate', [AIInsightsController::class, 'generateInsights'])
        ->middleware('check.any.role:admin')
        ->name('ai.insights.generate');
    
    // Get AI insights for specific event (Admin only)
    Route::get('/events/{eventId}', [AIInsightsController::class, 'getInsights'])
        ->middleware('check.any.role:admin')
        ->name('ai.insights.show');

    // Get detailed AI insights with full analysis data
    Route::get('/events/{eventId}/detailed', [AIInsightsController::class, 'getDetailedInsights'])
        ->middleware('check.any.role:admin')
        ->name('ai.insights.detailed');
    
    // Get all AI insights (Admin only)
    Route::get('/', [AIInsightsController::class, 'getAllInsights'])
        ->middleware('check.any.role:admin')
        ->name('ai.insights.index');
    
    // Delete AI insights for specific event (Admin only)
    Route::delete('/events/{eventId}', [AIInsightsController::class, 'deleteInsights'])
        ->middleware('check.any.role:admin')
        ->name('ai.insights.delete');

    // Auto-generate insights for all events (Admin only)
    Route::post('/auto-generate-all', [AIInsightsController::class, 'autoGenerateAll'])
        ->middleware('check.any.role:admin')
        ->name('ai.insights.auto.generate.all');

    // Get events that need insights (Admin only)
    Route::get('/events-needing-insights', [AIInsightsController::class, 'getEventsNeedingInsights'])
        ->middleware('check.any.role:admin')
        ->name('ai.insights.events.needing');
});

