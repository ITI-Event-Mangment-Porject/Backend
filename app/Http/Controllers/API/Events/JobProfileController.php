<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use App\Models\JobFair\JobFairParticipation;
use App\Models\JobFair\JobProfile;
use App\Models\JobFair\JobProfileTrack;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Events\StoreJobProfileRequest;
use App\Http\Requests\Events\UpdateJobProfileRequest;
use Illuminate\Http\Request;

class JobProfileController extends BaseApiController
{
    public function jobProfilesPerParticipation($jobFairId, $participationId)
    {
        $participation = JobFairParticipation::with([
            'jobProfiles.trackPreferences.track'
        ])
        ->where('id', $participationId)
        ->where('event_id', $jobFairId)
        ->where('status', 'approved')
        ->first();

        if (!$participation) {
            return $this->sendError('Participation not found or not approved.', [], 404);
        }

        return $this->sendResponse([
            'company_id' => $participation->company_id,
            'job_profiles' => $participation->jobProfiles
        ], 'Job profiles retrieved successfully.');
    }

    public function jobProfilesPerJobFair($jobFairId)
    {
        $participations = JobFairParticipation::where('event_id', $jobFairId)
            ->where('status', 'approved')
            ->pluck('id');

        $jobProfiles = JobProfile::with([
            'participation.company',
            'trackPreferences.track'
        ])
        ->whereIn('participation_id', $participations)
        ->latest()
        ->get();

        return $this->sendResponse([
            'job_profiles' => $jobProfiles
        ], 'Job profiles retrieved successfully.');
    }

    public function store(StoreJobProfileRequest $request, $jobFairId, $participationId)
    {
        $participation = JobFairParticipation::where('id', $participationId)
            ->where('event_id', $jobFairId)
            ->where('status', 'approved')
            ->first();

        if (!$participation) {
            return $this->sendError('Participation not found or not approved.', [], 403);
        }

        $validated = $request->validated();

        try {
            $jobProfile = new JobProfile($validated);
            $jobProfile->participation_id = $participation->id;
            $jobProfile->save();

            // Handle tracks
            if (isset($validated['tracks'])) {
                foreach ($validated['tracks'] as $track) {
                    JobProfileTrack::create([
                        'job_profile_id' => $jobProfile->id,
                        'track_id' => $track['track_id'],
                        'preference_level' => $track['preference_level']
                    ]);
                }
            }

            return $this->sendResponse(
                $jobProfile->load('trackPreferences.track'),
                'Job profile created successfully.',
                201
            );
        } catch (\Exception $e) {
            return $this->sendError('Failed to create job profile.', [$e->getMessage()], 500);
        }
    }

    public function update(UpdateJobProfileRequest $request, $jobProfileId)
    {
        $jobProfile = JobProfile::with('participation')
            ->where('id', $jobProfileId)
            ->first();

        if (!$jobProfile) {
            return $this->sendError('Job profile not found.', [], 404);
        }

        $participation = $jobProfile->participation;

        if (!$participation || $participation->status !== 'approved') {
            return $this->sendError('Associated participation is not approved.', [], 403);
        }

        $validated = $request->validated();

        try {
            $jobProfile->update($validated);

            if (isset($validated['track_preferences'])) {
                $jobProfile->trackPreferences()->delete();

                foreach ($validated['track_preferences'] as $track) {
                    $jobProfile->trackPreferences()->create([
                        'track_id' => $track['track_id'],
                        'preference_level' => $track['preference_level'],
                    ]);
                }
            }

            return $this->sendResponse(
                $jobProfile->load('trackPreferences.track'),
                'Job profile updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->sendError('Failed to update job profile.', [$e->getMessage()], 500);
        }
    }

    public function show($jobProfileId)
    {
        $jobProfile = JobProfile::with([
            'participation.company',
            'trackPreferences.track'
        ])->find($jobProfileId);

        if (!$jobProfile) {
            return $this->sendError('Job profile not found.', [], 404);
        }

        return $this->sendResponse(
            $jobProfile,
            'Job profile retrieved successfully.'
        );
    }

    public function destroy($jobProfileId)
    {
        $jobProfile = JobProfile::find($jobProfileId);

        if (!$jobProfile) {
            return $this->sendError('Job profile not found.', [], 404);
        }

        try {
            $jobProfile->delete();
            return $this->sendResponse(
                null,
                'Job profile deleted successfully.'
            );
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete job profile.', [$e->getMessage()], 500);
        }
    }
}
