<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use Auth;
use Carbon\Carbon;
use DB;
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
                    'photo' => $candidate->brandingDaySpeakers->first()->photo ? asset('storage/' . $candidate->brandingDaySpeakers->first()->photo) : null,
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
                        'photo' => $slot->speaker->photo ? asset('storage/' . $slot->speaker->photo) : null,
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

        // Validate schedule for overlaps and conflicts
        foreach ($data['schedule'] as $slot) {
            $startTime = Carbon::parse($slot['branding_day_date'] . ' ' . $slot['start_time']);
            $endTime = Carbon::parse($slot['branding_day_date'] . ' ' . $slot['end_time']);
            
            // Validate time logic
            if ($endTime <= $startTime) {
                return $this->sendError('Invalid time slot: End time must be after start time for company ID ' . $slot['company_id'], [], 422);
            }
            
            // Check for overlaps with existing schedule
            $existingOverlap = BrandingDaySchedule::where('event_id', $jobFairId)
                ->where('branding_day_date', $slot['branding_day_date'])
                ->where(function ($query) use ($slot) {
                    $query->whereBetween('start_time', [$slot['start_time'], $slot['end_time']])
                          ->orWhereBetween('end_time', [$slot['start_time'], $slot['end_time']])
                          ->orWhere(function ($subQuery) use ($slot) {
                              $subQuery->where('start_time', '<=', $slot['start_time'])
                                       ->where('end_time', '>=', $slot['end_time']);
                          });
                })
                ->exists();
            
            if ($existingOverlap) {
                return $this->sendError('Schedule conflict: Time slot overlaps with existing schedule on ' . $slot['branding_day_date'], [], 422);
            }
            
            // Check for speaker double-booking if speaker is assigned
            if (!empty($slot['speaker_id'])) {
                $speakerConflict = BrandingDaySchedule::where('event_id', $jobFairId)
                    ->where('branding_day_date', $slot['branding_day_date'])
                    ->where('branding_day_speaker_id', $slot['speaker_id'])
                    ->where(function ($query) use ($slot) {
                        $query->whereBetween('start_time', [$slot['start_time'], $slot['end_time']])
                              ->orWhereBetween('end_time', [$slot['start_time'], $slot['end_time']])
                              ->orWhere(function ($subQuery) use ($slot) {
                                  $subQuery->where('start_time', '<=', $slot['start_time'])
                                           ->where('end_time', '>=', $slot['end_time']);
                              });
                    })
                    ->exists();
                
                if ($speakerConflict) {
                    return $this->sendError('Speaker conflict: Speaker is already scheduled for another session on ' . $slot['branding_day_date'], [], 422);
                }
            }
        }

        // Use database transaction to ensure data consistency
        DB::beginTransaction();
        
        try {
            BrandingDaySchedule::where('event_id', $jobFairId)
            ->where('company_id', $slot['company_id']) // or participation_id
            ->delete();

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
            $this->reorderSlotsForDate($jobFairId, $slot['branding_day_date']);

            DB::commit();
            return $this->sendResponse([], 'Branding day schedule saved successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to save branding day schedule. Please try again.', [], 500);
        }
    }

    /**
     * Update a single branding day slot.
     */
    public function update(UpdateBrandingDayScheduleRequest $request, $jobFairId, $scheduleId)
    {
        $slot = $request->validated();
        $schedule = BrandingDaySchedule::findOrFail($scheduleId);

        // Use fallback values for conflict checks
        $brandingDayDate = $slot['branding_day_date'] ?? $schedule->branding_day_date;
        $startTime = $slot['start_time'] ?? $schedule->start_time;
        $endTime = $slot['end_time'] ?? $schedule->end_time;

        // Convert to Carbon to check time logic
        if ($startTime && $endTime && $brandingDayDate) {
            $start = Carbon::parse("$brandingDayDate $startTime");
            $end = Carbon::parse("$brandingDayDate $endTime");

            if ($end <= $start) {
                return $this->sendError('Invalid time slot: End time must be after start time.', [], 422);
            }

            // Check time overlap (excluding self)
            $conflict = BrandingDaySchedule::where('event_id', $jobFairId)
                ->where('id', '!=', $scheduleId)
                ->where('branding_day_date', $brandingDayDate)
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->whereBetween('start_time', [$startTime, $endTime])
                        ->orWhereBetween('end_time', [$startTime, $endTime])
                        ->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                        });
                })
                ->exists();

            if ($conflict) {
                return $this->sendError("Schedule conflict: Time slot overlaps with another session on $brandingDayDate", [], 422);
            }
        }

        // Check speaker conflict if provided
        if (!empty($slot['branding_day_speaker_id'])) {
            $speakerConflict = BrandingDaySchedule::where('event_id', $jobFairId)
                ->where('id', '!=', $scheduleId)
                ->where('branding_day_date', $brandingDayDate)
                ->where('branding_day_speaker_id', $slot['branding_day_speaker_id'])
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->whereBetween('start_time', [$startTime, $endTime])
                        ->orWhereBetween('end_time', [$startTime, $endTime])
                        ->orWhere(function ($q) use ($startTime, $endTime) {
                            $q->where('start_time', '<=', $startTime)
                                ->where('end_time', '>=', $endTime);
                        });
                })
                ->exists();

            if ($speakerConflict) {
                return $this->sendError('Speaker conflict: This speaker is already scheduled during that time.', [], 422);
            }
        }

        // Only update what's present
        $updateData = [];
        foreach (['branding_day_date', 'start_time', 'end_time', 'order', 'branding_day_speaker_id'] as $field) {
            if ($request->has($field)) {
                $updateData[$field] = $slot[$field];
            }
        }

        $schedule->update($updateData);
        $schedule->refresh();
        $this->reorderSlotsForDate($jobFairId, $schedule->branding_day_date);

        return $this->sendResponse(['result' => $schedule], 'Schedule updated successfully.');
    }
    protected function reorderSlotsForDate($eventId, $brandingDayDate)
    {
        $slots = BrandingDaySchedule::where('event_id', $eventId)
            ->where('branding_day_date', $brandingDayDate)
            ->orderBy('start_time')
            ->get();

        $order = 1;

        foreach ($slots as $slot) {
            $slot->update(['order' => $order++]);
        }
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
        // Use transaction for delete
        DB::beginTransaction();
        
        try {
            $slot->delete();
            DB::commit();

            return $this->sendResponse([], 'Schedule slot deleted.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to delete schedule slot. Please try again.', [], 500);
        }
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
            $query = JobFairParticipation::where('event_id', $jobFairId)
                ->where('id', $participationId)
                ->where('need_branding', true); // Ensure participation needs branding

            // If the authenticated user is not an admin, restrict by submitted_by
            if (Auth::check() && !Auth::user()->hasRole('admin')) {
                $query->where('submitted_by', auth()->id());
            }

            $participation = $query->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Job Fair Participation not found, not submitted by you, or does not require branding.', [], 404);
        }

        // Check if a speaker already exists for this participation
        if ($participation->brandingDaySpeakers()->exists()) {
            return $this->sendError('A speaker already exists for this participation. Only one speaker allowed per participation.', [], 409);
        }

        $data = $request->validated();
         // Use transaction for speaker creation
        DB::beginTransaction();
        
        try {
            // Handle photo upload if present
            if ($request->hasFile('photo')) {
                $path = $request->file('photo')->store('speakers', 'public'); // Store in storage/app/public/speakers
                $data['photo'] = $path; // Store just the path: speakers/filename.jpg
            }

            // Assign the participation ID from the route parameter
            $data['job_fair_participation_id'] = $participationId;

            $speaker = BrandingDaySpeaker::create($data);
            DB::commit();

            if ($speaker->photo) {
                $speaker->photo = asset('storage/' . $speaker->photo);
            }
            return $this->sendResponse($speaker, 'Speaker added successfully.', 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to add speaker. Please try again.', [], 500);
        }
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
            $speaker->photo = asset('storage/' . $speaker->photo);
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
                $speaker->photo = asset('storage/' . $speaker->photo);
            }
            return $speaker;
        });

        return $this->sendResponse($speakers, 'Speakers retrieved successfully.');
    }
}
