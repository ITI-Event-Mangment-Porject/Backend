<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\Events\StoreInterviewRequest;
use App\Http\Requests\Events\ReviewInterviewRequest;
use App\Models\JobFair\JobFairParticipation;
use App\Models\RegistrationAndInterview\InterviewRequest;
use App\Models\JobFair\JobProfile;
use App\Models\Auth\User;

class InterviewRequestController extends BaseApiController
{
    // Student submits interview request
    public function store(StoreInterviewRequest $request, $jobFairId)
    {
        $student = User::findOrFail(15); // Replace with auth()->user() later

        $validated = $request->validated();
        $jobProfile = JobProfile::findOrFail($validated['job_profile_id']);

        // Check: student must have CV uploaded
        if (!$student->cv_path) {
            return $this->sendError('CV must be uploaded to apply.', [], 422);
        }

        // Check: job profile must belong to this job fair
        if (
            !$jobProfile->participation ||
            $jobProfile->participation->event_id != $jobFairId
        ) {
            return $this->sendError('Invalid job profile for this job fair.', [], 400);
        }

        // Check: student’s track must be allowed by the job profile
        $allowedTrackIds = $jobProfile->tracks()->pluck('tracks.id')->toArray();
        if (!in_array($student->track_id, $allowedTrackIds)) {
            return $this->sendError('Your track is not eligible for this job profile.', [], 403);
        }

        // Check for duplicate request
        $exists = InterviewRequest::where('event_id', $jobFairId)
            ->where('user_id', $student->id)
            ->where('job_profile_id', $jobProfile->id)
            ->exists();
        if ($exists) {
            return $this->sendError('You already applied for this job profile.', [], 409);
        }

        // Get company_id from participation
        $companyId = $jobProfile->participation->company_id;

        // Create interview request
        $interviewRequest = InterviewRequest::create([
            'event_id' => $jobFairId,
            'user_id' => $student->id,
            'job_profile_id' => $jobProfile->id,
            'company_id' => $companyId,
            'status' => 'pending',
            'message' => $validated['message'] ?? null,
            'requested_at' => now(),
        ]);

        return $this->sendResponse(
            $interviewRequest,
            'Interview request submitted successfully.',
            201
        );
    }

    // Company reviews request
    public function review(ReviewInterviewRequest $request, $requestId)
    {
        $validated = $request->validated();

        $reviewedById = 1; // Replace with auth()->id() later
        $reviewer = User::findOrFail($reviewedById);

        $interviewRequest = InterviewRequest::findOrFail($requestId);
        $participation = $interviewRequest->jobProfile->participation ?? null;

        if (
            !$participation ||
            $participation->status !== 'approved' ||
            $participation->submitted_by !== $reviewer->id
        ) {
            return $this->sendError('You are not authorized to review this request.', [], 403);
        }

        // Perform the review
        $interviewRequest->update([
            'status' => $validated['status'],
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return $this->sendResponse(
            $interviewRequest->fresh(),
            "Request {$validated['status']} successfully."
        );
    }

    // Student views all their submitted requests
    public function myRequests($jobFairId)
    {
        $student = User::findOrFail(15); // Replace with auth()->user() later

        $requests = InterviewRequest::with(['jobProfile', 'company'])
            ->where('event_id', $jobFairId)
            ->where('user_id', $student->id)
            ->get();

        return $this->sendResponse($requests, 'Interview requests retrieved successfully.');
    }

    // Get all interview requests for a specific job profile in a job fair
    public function jobProfileRequests($jobFairId, $jobProfileId)
    {
        // Optionally, you can check if the job profile belongs to the job fair:
        $jobProfile = JobProfile::findOrFail($jobProfileId);
        if (
            !$jobProfile->participation ||
            $jobProfile->participation->event_id != $jobFairId
        ) {
            return $this->sendError('Invalid job profile for this job fair.', [], 400);
        }

        $requests = InterviewRequest::with('user')
            ->where('event_id', $jobFairId)
            ->where('job_profile_id', $jobProfileId)
            ->get();
        if ($requests->isEmpty()) {
            return $this->sendError('No interview requests found for this job profile.', [], 404);
        }

        return $this->sendResponse($requests, 'Interview requests for this job profile retrieved successfully.');
    }
}