<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\Request;
use App\Models\JobFair\JobFairParticipation;
use App\Models\JobFair\BrandingDaySchedule;
use App\Models\JobFair\BrandingDaySpeaker;
use App\Http\Requests\Events\StoreBrandingDayScheduleRequest;
use App\Http\Requests\Events\UpdateBrandingDayScheduleRequest;
use App\Http\Requests\Events\StoreBrandingDaySpeakerRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class BrandingDayController extends BaseApiController
{
    /**
     * Get all companies needing branding for a job fair.
     */
    /**
     * Get all companies needing branding for a job fair, along with their speakers.
     * This is for admin to view candidates for scheduling.
     */
    public function candidates($jobFairId)
    {
        $candidates = JobFairParticipation::with(['company', 'brandingDaySpeakers'])
            ->where('event_id', $jobFairId)
            ->where('need_branding', true)
            ->where('status', 'approved') // Only approved participations for scheduling
            ->get();

        if ($candidates->isEmpty()) {
            return $this->sendError('No approved branding candidates found for this job fair.', [], 404);
        }

        $result = $candidates->map(function ($candidate) {
            return [
                'id' => $candidate->id,
                'job_fair_participation_id' => $candidate->id,
                'company_id' => $candidate->company_id,
                'company_name' => $candidate->company->name ?? null,
                'need_branding' => $candidate->need_branding,
                'status' => $candidate->status,
                'speaker' => $candidate->brandingDaySpeakers->first() ? [
                    'id' => $candidate->brandingDaySpeakers->first()->id,
                    'speaker_name' => $candidate->brandingDaySpeakers->first()->speaker_name,
                    'position' => $candidate->brandingDaySpeakers->first()->position,
                    'mobile' => $candidate->brandingDaySpeakers->first()->mobile,
                    'photo' => $candidate->brandingDaySpeakers->first()->photo ? asset($candidate->brandingDaySpeakers->first()->photo) : null,
                ] : null,
            ];
        });

        return $this->sendResponse($result, 'Branding candidates with speakers retrieved successfully.');
    }

    /**
     * Get the branding day schedule (agenda) for a job fair.
     */
    public function index($jobFairId)
    {
        $schedule = BrandingDaySchedule::with(['company', 'speaker']) // Eager load the 'speaker' relationship
            ->where('event_id', $jobFairId)
            ->orderBy('branding_day_date')
            ->orderBy('start_time')
            ->orderBy('order')
            ->get()
            ->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'company_id' => $slot->company_id,
                    'company_name' => $slot->company->name ?? null,
                    'participation_id' => $slot->participation_id,
                    'branding_day_date' => $slot->branding_day_date,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'order' => $slot->order,
                    'speaker' => $slot->speaker ? [
                        'id' => $slot->speaker->id,
                        'speaker_name' => $slot->speaker->speaker_name,
                        'position' => $slot->speaker->position,
                        'mobile' => $slot->speaker->mobile,
                        'photo' => $slot->speaker->photo ? asset($slot->speaker->photo) : null,
                    ] : null,
                ];
            });
            if ($schedule->isEmpty()) {
            return $this->sendError('No branding day schedule found for this job fair.', [], 404);
        }

        return $this->sendResponse($schedule, 'Branding day schedule retrieved successfully.');
    }

    /**
     * Store the full branding day schedule (admin sets the agenda).
     */
    public function store(StoreBrandingDayScheduleRequest $request, $jobFairId)
    {
        $data = $request->validated();

        // Delete existing schedule entries for this job fair
        BrandingDaySchedule::where('event_id', $jobFairId)->delete();

        foreach ($data['schedule'] as $slot) {
            BrandingDaySchedule::create([
                'event_id' => $jobFairId,
                'company_id' => $slot['company_id'],
                'participation_id' => $slot['participation_id'],
                'branding_day_date' => $slot['branding_day_date'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'order' => $slot['order'] ?? null,
                'branding_day_speaker_id' => $slot['speaker_id'] ?? null, // Store speaker_id
            ]);
        }

        return $this->sendResponse([], 'Branding day schedule saved successfully.');
    }

    /**
     * Update a single branding day slot.
     */
    public function update(UpdateBrandingDayScheduleRequest $request, $jobFairId, $scheduleId)
    {
        try {
            $slot = BrandingDaySchedule::where('event_id', $jobFairId)->findOrFail($scheduleId);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Schedule slot not found.', [], 404);
        }

        $slot->update($request->validated());

        return $this->sendResponse([
            'id' => $slot->id,
            'company_id' => $slot->company_id,
            'participation_id' => $slot->participation_id,
            'branding_day_date' => $slot->branding_day_date,
            'start_time' => $slot->start_time,
            'end_time' => $slot->end_time,
            'order' => $slot->order,
            'branding_day_speaker_id' => $slot->branding_day_speaker_id,
        ], 'Schedule slot updated.');
    }

    /**
     * Delete a branding day slot.
     */
    public function destroy($jobFairId, $scheduleId)
    {
        try {
            $slot = BrandingDaySchedule::where('event_id', $jobFairId)->findOrFail($scheduleId);
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Schedule slot not found.', [], 404);
        }
        $slot->delete();

        return $this->sendResponse([], 'Schedule slot deleted.');
    }

    /**
     * Store a new branding day speaker for a participation that needs branding.
     * Only one speaker can be stored per participation.
     */
    public function storeSpeaker(StoreBrandingDaySpeakerRequest $request, $jobFairId, $participationId)
    {
        try {
            // Find the job fair participation for the given jobFairId and participationId
            // And ensure the authenticated user is the 'submitted_by' user for this participation
            $participation = JobFairParticipation::where('event_id', $jobFairId)
                ->where('id', $participationId)
                ->where('submitted_by', auth()->id()) // Crucial: ensure authenticated user submitted this participation
                ->where('need_branding', true) // Ensure participation needs branding
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Job Fair Participation not found, not submitted by you, or does not require branding.', [], 404);
        }

        // Check if a speaker already exists for this participation
        if ($participation->brandingDaySpeakers()->exists()) {
            return $this->sendError('A speaker already exists for this participation. Only one speaker allowed per participation.', [], 409);
        }

        $data = $request->validated();
        // Handle photo upload if present
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('speakers','public'); // Store in storage/app/public/speakers
            $data['photo'] = Storage::url($path); // Get public URL
        }

        // Assign the participation ID from the route parameter
        $data['job_fair_participation_id'] = $participationId;

        $speaker = BrandingDaySpeaker::create($data);

        return $this->sendResponse($speaker, 'Speaker added successfully.', 201);
    }

    /**
     * Get the speaker for a specific job fair participation.
     */
    public function showSpeakerForParticipation($jobFairId, $participationId)
    {
        try {
            $participation = JobFairParticipation::with('brandingDaySpeakers')
                ->where('event_id', $jobFairId)
                ->where('id', $participationId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Job Fair Participation not found.', [], 404);
        }

        $speaker = $participation->brandingDaySpeakers->first();

        if (is_null($speaker)) {
            return $this->sendError('No speaker found for this participation.', [], 404);
        }

        // Prepend base URL to photo if it exists
        if ($speaker->photo) {
            $speaker->photo = asset($speaker->photo);
        }

        return $this->sendResponse($speaker, 'Speaker retrieved successfully.');
    }

    /**
     * Get all speakers for a specific job fair.
     */
    public function indexAllSpeakersForJobFair($jobFairId)
    {
        $speakers = BrandingDaySpeaker::whereHas('jobFairParticipation', function ($query) use ($jobFairId) {
            $query->where('event_id', $jobFairId);
        })->get();

        if ($speakers->isEmpty()) {
            return $this->sendError('No speakers found for this job fair.', [], 404);
        }

        $speakers->map(function ($speaker) {
            if ($speaker->photo) {
                $speaker->photo = asset($speaker->photo);
            }
            return $speaker;
        });

        return $this->sendResponse($speakers, 'Speakers retrieved successfully.');
    }
}
