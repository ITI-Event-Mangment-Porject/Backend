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
        $user = auth()->user();
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
        // Diversify response based on user role
        $result = $jobProfiles->map(function ($jobProfile) use ($user) {
            $participation = $jobProfile->participation;
            $company = $participation ? $participation->company : null;

            $isAdminOrStaff = $user && ($user->hasRole('admin') || $user->hasRole('staff'));
            $isCreatorCompanyRep = $user && $user->hasRole('company_representative') && $participation && $participation->submitted_by === $user->id;

            if ($isAdminOrStaff || $isCreatorCompanyRep) {
                // Full details
                return $jobProfile;
            }

            // Limited info for others
            return [
                'id' => $jobProfile->id,
                'title' => $jobProfile->title,
                'description' => $jobProfile->description,
                'requirements' => $jobProfile->requirements,
                'employment_type' => $jobProfile->employment_type,
                'location' => $jobProfile->location,
                'positions_available' => $jobProfile->positions_available,
                'track_preferences' => $jobProfile->trackPreferences,
                'company' => $company ? [
                    'id' => $company->id,
                    'name' => $company->name,
                    'logo_path' => $company->logo_path,
                    'industry' => $company->industry,
                    'location' => $company->location,
                    'website' => $company->website,
                ] : null,
            ];
        });
        return $this->sendResponse([
            'job_profiles' => $result,
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

        // Ensure the authenticated user is the one who submitted the participation
        if ($participation->submitted_by !== auth()->id()) {
            return $this->sendError('You are not authorized to add job profiles for this participation.', [], 403);
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
        $jobProfile = JobProfile::with('participation')->find($jobProfileId);

        if (!$jobProfile) {
            return $this->sendError('Job profile not found.', [], 404);
        }

        $participation = $jobProfile->participation;

        if (!$participation || $participation->status !== 'approved') {
            return $this->sendError('Associated participation is not approved.', [], 403);
        }

        // Ensure the authenticated user is the one who submitted the participation
        if ($participation->submitted_by !== auth()->id()) {
            return $this->sendError('You are not authorized to update job profiles for this participation.', [], 403);
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

        $user = auth()->user();
        $participation = $jobProfile->participation;

        $isAdminOrStaff = $user && ($user->hasRole('admin') || $user->hasRole('staff'));
        $isCreatorCompanyRep = $user && $user->hasRole('company_representative') && $participation && $participation->submitted_by === $user->id;

        if ($isAdminOrStaff || $isCreatorCompanyRep) {
            // Return full details
            return $this->sendResponse($jobProfile, 'Job profile retrieved successfully.');
        }

        // For others: return limited info
        $company = $participation ? $participation->company : null;
        $result = [
            'id' => $jobProfile->id,
            'title' => $jobProfile->title,
            'description' => $jobProfile->description,
            'requirements' => $jobProfile->requirements,
            'employment_type' => $jobProfile->employment_type,
            'location' => $jobProfile->location,
            'positions_available' => $jobProfile->positions_available,
            'track_preferences' => $jobProfile->trackPreferences,
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'logo_path' => $company->logo_path,
                'industry' => $company->industry,
                'location' => $company->location,
                'website' => $company->website,
            ] : null,
        ];

        return $this->sendResponse($result, 'Job profile retrieved successfully.');
    }

    public function destroy($jobProfileId)
    {
        $jobProfile = JobProfile::with('participation')->find($jobProfileId);

        if (!$jobProfile) {
            return $this->sendError('Job profile not found.', [], 404);
        }

        $participation = $jobProfile->participation;

        // Ensure the authenticated user is the one who submitted the participation
        if ($participation->submitted_by !== auth()->id()) {
            return $this->sendError('You are not authorized to delete job profiles for this participation.', [], 403);
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
