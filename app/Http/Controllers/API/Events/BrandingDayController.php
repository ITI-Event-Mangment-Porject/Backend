<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\Request;
use App\Models\JobFair\JobFairParticipation;
use App\Models\JobFair\BrandingDaySchedule;
use App\Http\Requests\Events\StoreBrandingDayScheduleRequest;
use App\Http\Requests\Events\UpdateBrandingDayScheduleRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BrandingDayController extends BaseApiController
{
    /**
     * Get all companies needing branding for a job fair.
     */
    public function candidates($jobFairId)
    {
        $candidates = JobFairParticipation::with('company')
            ->where('event_id', $jobFairId)
            ->where('need_branding', true)
            ->where('status', '!=', 'rejected')
            ->get();

        if ($candidates->isEmpty()) {
            return $this->sendError('No branding candidates found for this job fair.', [], 404);
        }

        $result = $candidates->map(function ($candidate) {
            return [
                'id' => $candidate->id,
                'company_id' => $candidate->company_id,
                'company_name' => $candidate->company->name ?? null,
                'need_branding' => $candidate->need_branding,
                'status' => $candidate->status,
            ];
        });

        return $this->sendResponse($result, 'Branding candidates retrieved successfully.');
    }

    /**
     * Get the branding day schedule (agenda) for a job fair.
     */
    public function index($jobFairId)
    {
        $schedule = BrandingDaySchedule::with('company')
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
}
