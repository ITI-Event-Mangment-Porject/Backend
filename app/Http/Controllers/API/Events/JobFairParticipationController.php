<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use App\Models\Company\Company;
use App\Models\Event\Event;
use App\Models\JobFair\JobFairParticipation;
use DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Events\StoreJobFairParticipationRequest;
use App\Http\Requests\Events\UpdateJobFairParticipationRequest;
use App\Http\Requests\Events\ReviewJobFairParticipationRequest;
use Illuminate\Http\Request;

class JobFairParticipationController extends BaseApiController
{
    // Company submits participation form for a Job Fair.
    public function store(StoreJobFairParticipationRequest $request, $jobFairId)
    {
        $validated = $request->validated();
        $companyData = $request->input('company');

        try {
            $participation = DB::transaction(function () use ($jobFairId, $companyData, $request) {
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
                    // Return error response from inside transaction
                    throw new \Exception('duplicate');
                }

                // Create participation
                return JobFairParticipation::create([
                    'event_id' => $jobFairId,
                    'company_id' => $company->id,
                    'status' => 'pending',
                    'special_requirements' => $request->input('special_requirements'),
                    'submitted_by' => 3, // Replace later with authenticated user
                    'submitted_at' => now(),
                    'need_branding' => $request->input('need_branding', false),
                ]);
            });

            if ($participation === null) {
                // This means duplicate was found
                return $this->sendError(
                    'This company has already submitted participation for this job fair.',
                    [],
                    409
                );
            }

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
                'reviewed_by' => 5, // Replace with actual user ID after authentication
                'reviewed_at' => now(),
            ]);

            // If approved, mark company as approved
            if ($validated['status'] === 'approved') {
                $participation->company->update([
                    'is_approved' => true,
                    'approved_by' => 3, // Replace with actual user ID after authentication
                    'approved_at' => now(),
                ]);

                // Auto-publish event if all selected logic applies
                $event = $participation->event;
                if ($event->type === 'Job Fair' && $event->status === 'draft') {
                    $event->update(['status' => 'published']);
                }
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

            return $this->sendResponse($participation, 'Participation retrieved successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Participation not found.', [], 404);
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve participation.', [$e->getMessage()], 500);
        }
    }
}
