<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use App\Models\Company\Company;
use App\Models\Event\Event;
use App\Models\JobFair\JobFairParticipation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Events\StoreJobFairParticipationRequest;
use App\Http\Requests\Events\UpdateJobFairParticipationRequest;
use App\Http\Requests\Events\ReviewJobFairParticipationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Auth\User;
use App\Notifications\JobFairParticipationApproved;
use Illuminate\Support\Facades\Notification;

class JobFairParticipationController extends BaseApiController
{
    // Company submits participation form for a Job Fair.
    public function store(StoreJobFairParticipationRequest $request, $jobFairId)
    {
        $validated = $request->validated();
        $companyData = $request->input('company');

        try {
            $participation = \DB::transaction(function () use ($jobFairId, $companyData, $request) {
                // Create or get company
                $company = Company::firstOrCreate(
                    ['contact_email' => $companyData['contact_email']],
                    $companyData
                );

                // Prevent duplicate participation
                $existing = JobFairParticipation::where('event_id', $jobFairId)
                    ->where('company_id', $company->id)
                    ->first();

                if ($existing) {
                    throw new \Exception('duplicate');
                }

                // Create participation
                return JobFairParticipation::create([
                    'event_id' => $jobFairId,
                    'company_id' => $company->id,
                    'status' => 'pending',
                    'special_requirements' => $request->input('special_requirements'),
                    'submitted_by' => auth()->id(),
                    'submitted_at' => now(),
                    'need_branding' => $request->input('need_branding', false),
                ]);
            });

            return $this->sendResponse($participation, 'Participation submitted successfully.', 201);
        } catch (\Exception $e) {
            if ($e->getMessage() === 'duplicate') {
                return $this->sendError(
                    'This company has already submitted participation for this job fair.',
                    [],
                    409
                );
            }
            return $this->sendError('Failed to submit participation.', [$e->getMessage()], 500);
        }
    }

    // Admin approves or rejects participation.
    public function review(ReviewJobFairParticipationRequest $request, $jobFairId, $participationId)
    {
        $validated = $request->validated();

        try {
            $participation = JobFairParticipation::where('event_id', $jobFairId)->findOrFail($participationId);
            $participation->update([
                'status' => $validated['status'],
                'review_notes' => $validated['review_notes'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            // If approved, mark company as approved
            if ($validated['status'] === 'approved') {
                $participation->company->update([
                    'is_approved' => true,
                    'status' => 'approved', // Update the status field in the Company model
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                // Auto-publish event if all selected logic applies
                $event = $participation->event;
                $originalStatus = $event->status; // Store original status

                if ($event->type === 'Job Fair' && $originalStatus === 'draft') {
                    $event->update(['status' => 'published']);

                    if ($event->status === 'published') {
                        // Notify ALL students
                        $students = User::whereHas('roles', function ($query) {
                            $query->where('name', 'student');
                        })->get();

                        foreach ($students as $student) {
                            $student->notify(new JobFairParticipationApproved($event));
                        }
                    }
                }
            } elseif ($validated['status'] === 'rejected') {
                $participation->company->update([
                    'is_approved' => false,
                    'status' => 'rejected', // Update the status field in the Company model to rejected
                    'approved_by' => null, // Clear approved_by
                    'approved_at' => null, // Clear approved_at
                ]);
            }
          
            return $this->sendResponse(
                $participation->fresh(),
                "Participation {$validated['status']} successfully."
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Participation not found.', [], 404);
        } catch (\Exception $e) {
            return $this->sendError('Failed to review participation.', [$e->getMessage()], 500);
        }
    }

    // List all participations for a Job Fair (admin view).
    public function index(Request $request, $jobFairId)
    {
        try {
            $event = Event::where('type', 'Job Fair')->findOrFail($jobFairId);

            $query = JobFairParticipation::with([
                'company',
                'submittedBy:id,first_name,last_name,email',
                'reviewedBy:id,first_name,last_name,email'
            ])->where('event_id', $event->id);

            if ($request->has('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
                $query->where('status', $request->status);
            }

            $participations = $query->get();
            return $this->sendResponse($participations, 'Participations retrieved successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Job Fair not found.', [], 404);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve participations.', [$e->getMessage()], 500);
        }
    }

    // View one participation (admin or company).
    public function show($jobFairId, $participationId)
    {
        try {
            $participation = JobFairParticipation::where('event_id', $jobFairId)
                ->with('company', 'submittedBy', 'reviewedBy')
                ->findOrFail($participationId);

            $user = auth()->user();

            // If the user is a company representative, only allow access to their own participation
            if ($user->hasRole('company_representative') && $participation->submitted_by !== $user->id) {
                return $this->sendError('You are not authorized to view this participation.', [], 403);
            }

            return $this->sendResponse($participation, 'Participation retrieved successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Participation not found.', [], 404);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve participation.', [$e->getMessage()], 500);
        }
    }
}
