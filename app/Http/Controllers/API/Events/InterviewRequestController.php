<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\Events\StoreInterviewRequest;
use App\Http\Requests\Events\ReviewInterviewRequest;
use App\Models\JobFair\JobFairParticipation;
use App\Models\RegistrationAndInterview\InterviewRequest;
use App\Models\JobFair\JobProfile;

class InterviewRequestController extends BaseApiController
{
    // Student submits interview request
    public function store(StoreInterviewRequest $request, $jobFairId)
    {
        $student = auth()->user();
        $validated = $request->validated();

        $jobProfile = JobProfile::with('participation')->findOrFail($validated['job_profile_id']);

        // Ensure job profile belongs to this job fair
        if (
            !$jobProfile->participation ||
            $jobProfile->participation->event_id != $jobFairId
        ) {
            return $this->sendError('Invalid job profile for this job fair.', [], 400);
        }

        // Ensure student has uploaded a CV
        if (empty($student->cv_path)) {
            return $this->sendError('You must upload your CV before applying.', [], 422);
        }

        // Ensure student's track is allowed by the job profile (if you have such logic)
        // $allowedTrackIds = $jobProfile->trackPreferences->pluck('track_id')->toArray();
        // if (!in_array($student->track_id, $allowedTrackIds)) {
        //     return $this->sendError('Your track is not eligible for this job profile.', [], 403);
        // }

        // Prevent duplicate requests
        $exists = InterviewRequest::where('event_id', $jobFairId)
            ->where('user_id', $student->id)
            ->where('job_profile_id', $jobProfile->id)
            ->exists();
        if ($exists) {
            return $this->sendError('You have already applied for this job profile.', [], 409);
        }

        $companyId = $jobProfile->participation->company_id;

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

    // Student views all their submitted requests for a job fair
    public function myRequests($jobFairId)
    {
        $student = auth()->user();

        $requests = InterviewRequest::with(['jobProfile', 'company'])
            ->where('event_id', $jobFairId)
            ->where('user_id', $student->id)
            ->get();

        return $this->sendResponse($requests, 'Interview requests retrieved successfully.');
    }

    // Admin, staff, or company rep: Get all interview requests for a specific job profile in a job fair
    public function jobProfileRequests($jobFairId, $jobProfileId)
    {
        $user = auth()->user();

        $jobProfile = JobProfile::with('participation')->findOrFail($jobProfileId);

        if (
            !$jobProfile->participation ||
            $jobProfile->participation->event_id != $jobFairId
        ) {
            return $this->sendError('Invalid job profile for this job fair.', [], 400);
        }

        // If company rep, only allow if they own the participation
        if (
            $user->hasRole('company_representative') &&
            $jobProfile->participation->submitted_by !== $user->id
        ) {
            return $this->sendError('You are not authorized to view requests for this job profile.', [], 403);
        }

        $requests = InterviewRequest::with('user')
            ->where('event_id', $jobFairId)
            ->where('job_profile_id', $jobProfileId)
            ->get();

        return $this->sendResponse($requests, 'Interview requests for this job profile retrieved successfully.');
    }

    // Company representative reviews request
    public function review(ReviewInterviewRequest $request, $requestId)
    {
        $reviewer = auth()->user();
        $validated = $request->validated();

        $interviewRequest = InterviewRequest::with('jobProfile.participation')->findOrFail($requestId);
        $participation = $interviewRequest->jobProfile->participation ?? null;

        // Only the company rep who submitted the participation can review
        if (
            !$participation ||
            $participation->status !== 'approved' ||
            $participation->submitted_by !== $reviewer->id
        ) {
            return $this->sendError('You are not authorized to review this request.', [], 403);
        }

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
}