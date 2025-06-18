<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Controllers\Controller;
use App\Http\Requests\EventRegisterRequest;
use App\Models\Event\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\BaseApiRequest;
use App\Http\Requests\EventCancelRequest;
use App\Models\JobFair\JobFairParticipation;
use App\Models\JobFair\JobProfile;
use App\Models\NotificationsAndMessaging\Notification;
use App\Models\RegistrationAndInterview\EventRegistration;
use App\Models\RegistrationAndInterview\InterviewRequest;
use App\Notifications\EventRegistrationSuccess;
use Illuminate\Support\Facades\Log;

class EventRegistrationController extends BaseApiController
{
    //
    public function register(EventRegisterRequest $request, Event $event_flexible)
    {
        $userId =  auth()->id();
            if (!$userId) {
                return $this->sendError('User not authenticated.', [], 401);
            }
            $validatedData = $request->validated();
        
        try {
            
            DB::beginTransaction();

            // Check registration deadline
            if ($event_flexible->registration_deadline && now() > $event_flexible->registration_deadline) {
                return $this->sendError('Registration deadline has passed.', [ 
                    'registration_deadline' => $event_flexible->registration_deadline,
                    'current_time' => now()
                ], 422);
            }
            
            // Check existing registration
            $existingRegistration = $event_flexible->registrations()
                ->where('user_id', $userId)
                ->first();
                
            if ($existingRegistration) {
                return $this->sendError('User is already registered for this event.', [], 422);
            }
            
            // Create the registration
            $registration = new EventRegistration([
                'event_id' => $event_flexible->id,
                'user_id' => $userId,
                'status' => $validatedData['status'] ?? 'registered',
                'registration_type' => $validatedData['registration_type'] ?? 'manual',
                'registered_at' => now(),
            ]);

            $registration->save(); // Make sure to save the registration
            $registration->load(['user', 'event']);
            
            // Trigger notification (this handles email sending)
            // Create custom notification in your table
            $this->createCustomNotification($userId, $event_flexible, $registration);

            // Send email notification (queued)
            $user = auth()->user();
            $user->notify(new EventRegistrationSuccess($event_flexible, $registration));

            // Handle job fair specific logic
            $jobFairResponse = null;
            if (strtolower($event_flexible->type) === 'job fair') {
                $jobFairResponse = $this->handleJobFairRegistration($event_flexible, $userId);
            }

            DB::commit();

            $responseData = [
                'registration' => $registration,
                'job_fair_info' => $jobFairResponse, // Return the full response, not just message
            ];

            return $this->sendResponse(
                $responseData,
                'Registration successful for event: ' . $event_flexible->title,
                201
            );
        
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return $this->sendError(
                'Registration failed. Please try again.', [],
                500
            );
        }
    }
    public function registrations(Event $event_flexible)
    {
        // Logic to get all registrations for an event
        $registrations = $event_flexible->registrations()
            ->with(['user', 'event'])
            ->get();

        if ($registrations->isEmpty()) {
            return $this->sendError('No registrations found for this event.', [], 404);
        }

        return $this->sendResponse($registrations, 'Registrations retrieved successfully',200);
    }
    /**
     * Cancel a user's registration for an event
     */
    
    public function cancelMyRegistration(EventCancelRequest $request, Event $event_flexible)
    {
        $userId = auth()->id();
        
        if (!$userId) {
            return $this->sendError('User not authenticated.', [], 401);
        }

        try {
            DB::beginTransaction();

            // Find the user's active registration for this event
            $registration = $event_flexible->registrations()
                ->where('user_id', $userId)
                ->where('status', 'registered')
                ->first();

            if (!$registration) {
                return $this->sendError('No active registration found for this event.', [], 404);
            }

            // Check for interview requests
            if (class_exists('\App\Models\RegistrationAndInterview\InterviewRequest')) {
                $interviewRequest = \App\Models\RegistrationAndInterview\InterviewRequest::where('event_id', $event_flexible->id)
                    ->where('user_id', $userId)
                    ->first();

                if ($interviewRequest && $interviewRequest->queue_position !== null) {
                    return $this->sendError('Cannot cancel registration while you are in the interview queue. Please exit the queue first.', [], 422);
                }

                if ($interviewRequest) {
                    $interviewRequest->update(['status' => 'cancelled']);
                }
            }

            // Update the registration status
            $validatedData = $request->validated();
            $registration->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $validatedData['cancellation_reason'] ?? null,
            ]);

            DB::commit();

            return $this->sendResponse($registration->fresh(), 'Registration cancelled successfully.', 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Cancellation failed: ' . $e->getMessage(), [
                'user_id' => $userId,
                'event_id' => $event_flexible->id,
                'error' => $e->getTraceAsString()
            ]);
            return $this->sendError('Cancellation failed. Please try again.', [], 500);
        }
    }

    private function handleJobFairRegistration(Event $event, int $userId)
    {
        try {
            Log::info('Starting job fair registration handling', [
                'event_id' => $event->id,
                'user_id' => $userId,
                'event_type' => $event->type
            ]);

            // Debug: Check if we have any participations
            $allParticipations = JobFairParticipation::where('event_id', $event->id)->get();
            Log::info('All participations for event', [
                'event_id' => $event->id,
                'total_participations' => $allParticipations->count(),
                'participations' => $allParticipations->toArray()
            ]);

            // Get approved job fair participations for this event
            $approvedParticipations = JobFairParticipation::where('event_id', $event->id)
                ->where('status', 'approved')
                ->get();

            Log::info('Approved participations', [
                'count' => $approvedParticipations->count(),
                'participations' => $approvedParticipations->toArray()
            ]);

            if ($approvedParticipations->isEmpty()) {
                return [
                    'message' => 'No approved companies for this job fair yet.',
                    'action_required' => false,
                    'available_job_profiles' => [],
                    'debug_info' => [
                        'total_participations' => $allParticipations->count(),
                        'approved_participations' => 0
                    ]
                ];
            }

            // Get job profiles for approved participations
            $participationIds = $approvedParticipations->pluck('id');
            Log::info('Looking for job profiles', ['participation_ids' => $participationIds->toArray()]);
            
            $availableJobProfiles = JobProfile::whereIn('participation_id', $participationIds)->get();
            
            Log::info('Found job profiles', [
                'count' => $availableJobProfiles->count(),
                'profiles' => $availableJobProfiles->toArray()
            ]);

            if ($availableJobProfiles->isEmpty()) {
                return [
                    'message' => 'No job profiles available for this job fair yet.',
                    'action_required' => false,
                    'available_job_profiles' => [],
                    'approved_companies_count' => $approvedParticipations->count(),
                    'debug_info' => [
                        'participation_ids' => $participationIds->toArray(),
                        'job_profiles_count' => 0
                    ]
                ];
            }

            // Load company information separately to avoid relationship issues
            $profilesWithCompanies = $availableJobProfiles->map(function($profile) use ($approvedParticipations) {
                $participation = $approvedParticipations->firstWhere('id', $profile->participation_id);
                
                return [
                    'id' => $profile->id,
                    'title' => $profile->title,
                    'company_id' => $participation ? $participation->company_id : null,
                    'participation_id' => $profile->participation_id,
                    'description' => $profile->description,
                    'requirements' => $profile->requirements,
                    'employment_type' => $profile->employment_type,
                    'location' => $profile->location,
                    'positions_available' => $profile->positions_available
                ];
            });

            return [
                'message' => 'Registration successful! Here are the available job opportunities.',
                'available_job_profiles' => $profilesWithCompanies,
                'action_required' => true,
                'next_step' => 'You can now submit interview requests for positions you\'re interested in.',
                'total_profiles' => $availableJobProfiles->count(),
                'total_companies' => $approvedParticipations->count()
            ];

        } catch (\Exception $e) {
            Log::error('Job fair registration handling failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_id' => $event->id,
                'user_id' => $userId
            ]);
            
            return [
                'message' => 'Registration successful, but there was an issue loading job fair details.',
                'action_required' => false,
                'error' => 'Failed to load job opportunities. Error: ' . $e->getMessage(),
                'available_job_profiles' => []
            ];
        }
    }
    /**
     * Create notification in your custom notifications table
     */
    private function createCustomNotification($userId, Event $event, EventRegistration $registration)
    {
        try {
            $message = "You have successfully registered for {$event->title}. ";
            
            if (strtolower($event->type) === 'job fair') {
                $message .= "You can now browse job opportunities and submit interview requests.";
            } else {
                $message .= "We look forward to seeing you at the event!";
            }

            Notification::create([
                'user_id' => $userId,
                'title' => 'Event Registration Successful',
                'message' => $message,
                'type' => 'registration',
                'related_id' => $registration->id,
                'related_type' => EventRegistration::class,
                'sent_via' => ['database', 'email'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create notification: ' . $e->getMessage(), [
                'user_id' => $userId,
                'event_id' => $event->id,
                'registration_id' => $registration->id
            ]);
            // We don't throw the exception here to prevent registration failure
            // Just log it since notification is not critical to registration
        }
    }
}
