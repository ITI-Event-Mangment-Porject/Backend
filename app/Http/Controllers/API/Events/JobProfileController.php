<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\Controller;
use App\Models\JobFair\JobFairParticipation;
use App\Models\JobFair\JobProfile;
use App\Models\JobFair\JobProfileTrack;
use Egulias\EmailValidator\Result\Reason\RFCWarnings;
use Illuminate\Http\Request;

class JobProfileController extends Controller
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
            return response()->json(['message' => 'Participation not found or not approved.'], 404);
        }

        return response()->json([
            'company_id' => $participation->company_id,
            'job_profiles' => $participation->jobProfiles
        ]);
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

        return response()->json([
            'job_profiles' => $jobProfiles
        ]);
    }

    public function store(Request $request, $jobFairId, $participationId)
    {
        $participation = JobFairParticipation::where('id', $participationId)
            ->where('event_id', $jobFairId)
            ->where('status', 'approved')
            ->first();

        if (!$participation) {
            return response()->json(['message' => 'Participation not found or not approved.'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'employment_type' => 'required|in:Full-time,Part-time,Internship,Contract',
            'location' => 'nullable|string|max:255',
            'positions_available' => 'required|integer|min:1',
            'tracks' => 'nullable|array',
            'tracks.*.track_id' => 'required|integer|exists:tracks,id',
            'tracks.*.preference_level' => 'required|in:required,preferred,acceptable'
        ]);

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

        return response()->json([
            'message' => 'Job profile created successfully.',
            'job_profile' => $jobProfile->load('trackPreferences.track') // eager load tracks
        ], 201);
    }
    public function update(Request $request, $jobProfileId)
    {
        // 1. Load the job profile with its participation + event
        $jobProfile = JobProfile::with('participation')
            ->where('id', $jobProfileId)
            ->first();

        if (!$jobProfile) {
            return response()->json(['message' => 'Job profile not found.'], 404);
        }

        $participation = $jobProfile->participation;

        // 2. Ensure participation is valid and approved
        if (!$participation || $participation->status !== 'approved') {
            return response()->json(['message' => 'Associated participation is not approved.'], 403);
        }

        // 3. Validate input
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'employment_type' => 'sometimes|in:Full-time,Part-time,Internship,Contract',
            'location' => 'nullable|string|max:255',
            'positions_available' => 'sometimes|integer|min:1',

            'track_preferences' => 'nullable|array',
            'track_preferences.*.track_id' => 'required|integer|exists:tracks,id',
            'track_preferences.*.preference_level' => 'required|in:required,preferred,acceptable'
        ]);

        // 4. Update the job profile base fields
        $jobProfile->update($validated);

        // 5. Handle track preferences if sent
        if (isset($validated['track_preferences'])) {
            $jobProfile->trackPreferences()->delete();

            foreach ($validated['track_preferences'] as $track) {
                $jobProfile->trackPreferences()->create([
                    'track_id' => $track['track_id'],
                    'preference_level' => $track['preference_level'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Job profile updated successfully.',
            'job_profile' => $jobProfile->load('trackPreferences.track'),
        ]);
    }
    public function show($jobProfileId)
    {
        $jobProfile = JobProfile::with([
            'participation.company',
            'trackPreferences.track'
        ])->find($jobProfileId);

        if (!$jobProfile) {
            return response()->json(['message' => 'Job profile not found.'], 404);
        }

        return response()->json([
            'job_profile' => $jobProfile
        ]);
    }
    public function destroy($jobProfileId)
    {
        $jobProfile = JobProfile::find($jobProfileId);

        if (!$jobProfile) {
            return response()->json(['message' => 'Job profile not found.'], 404);
        }

        $jobProfile->delete();

        return response()->json(['message' => 'Job profile deleted successfully.']);
    }




}
