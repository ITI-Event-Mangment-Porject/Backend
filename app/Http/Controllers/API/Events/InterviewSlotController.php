<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use App\Models\Event\Event;
use App\Models\JobFair\InterviewSlot;
use App\Models\JobFair\JobFairParticipation;
use App\Http\Requests\Events\StoreInterviewSlotRequest;
use App\Http\Requests\Events\UpdateInterviewSlotRequest;

class InterviewSlotController extends BaseApiController
{
    // Admin only: List all slots for a job fair
    public function jobFairSlots($jobFairId)
    {
        $event = Event::where('id', $jobFairId)
            ->where('type', 'Job Fair')
            ->first();

        if (!$event) {
            return $this->sendError('Job Fair not found.', [], 404);
        }

        $slots = InterviewSlot::whereHas('participation', function ($query) use ($jobFairId) {
                $query->where('event_id', $jobFairId);
            })
            ->with('participation.company')
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        return $this->sendResponse([
            'job_fair_id' => $jobFairId,
            'slots' => $slots
        ], 'Interview slots retrieved successfully.');
    }

    // Admin, staff, or company rep (ownership checked below): List slots for a participation
    public function participationSlots($jobFairId, $participationId)
    {
        $participation = JobFairParticipation::where('id', $participationId)
            ->where('event_id', $jobFairId)
            ->first();

        if (!$participation) {
            return $this->sendError('Participation not found for this job fair.', [], 404);
        }

        $user = auth()->user();
        // Company rep can only view their own participation's slots
        if ($user->hasRole('company_representative') && $participation->submitted_by !== $user->id) {
            return $this->sendError('You are not authorized to view slots for this participation.', [], 403);
        }

        $slots = InterviewSlot::where('participation_id', $participationId)
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        return $this->sendResponse([
            'job_fair_id' => $jobFairId,
            'participation_id' => $participationId,
            'slots' => $slots
        ], 'Interview slots retrieved successfully.');
    }

    // Company rep (ownership checked): Create a slot
    public function store(StoreInterviewSlotRequest $request, $jobFairId, $participationId)
    {
        $validated = $request->validated();

        $participation = JobFairParticipation::where('id', $participationId)
            ->where('event_id', $jobFairId)
            ->first();

        if (!$participation) {
            return $this->sendError('Participation not found for this job fair.', [], 404);
        }

        $user = auth()->user();
        if ($user->hasRole('company_representative') && $participation->submitted_by !== $user->id) {
            return $this->sendError('You are not authorized to manage slots for this participation.', [], 403);
        }

        try {
            $slot = InterviewSlot::create([
                'participation_id' => $participation->id,
                'slot_date' => $validated['slot_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'duration_minutes' => $validated['duration_minutes'],
                'max_interviews_per_slot' => $validated['max_interviews_per_slot'],
                'is_break' => $validated['is_break'] ?? false,
                'break_reason' => $validated['break_reason'] ?? null,
                'is_available' => $validated['is_available'] ?? true,
            ]);

            return $this->sendResponse(
                $slot,
                'Interview slot created successfully.',
                201
            );
        } catch (\Exception $e) {
            return $this->sendError('Failed to create interview slot.', [$e->getMessage()], 500);
        }
    }

    // Admin, staff, or company rep (ownership checked): Show a slot
    public function show($jobFairId, $participationId, $slotId)
    {
        $slot = InterviewSlot::with('participation.company')
            ->where('id', $slotId)
            ->where('participation_id', $participationId)
            ->first();

        if (!$slot) {
            return $this->sendError('Interview slot not found for this participation.', [], 404);
        }

        if ($slot->participation->event_id != $jobFairId) {
            return $this->sendError('Interview slot does not belong to this job fair.', [], 403);
        }

        $user = auth()->user();
        // Company rep can only view their own participation's slots
        if ($user->hasRole('company_representative') && $slot->participation->submitted_by !== $user->id) {
            return $this->sendError('You are not authorized to view this slot.', [], 403);
        }

        return $this->sendResponse(
            $slot,
            'Interview slot retrieved successfully.'
        );
    }

    // Company rep (ownership checked): Update a slot
    public function update(UpdateInterviewSlotRequest $request, $jobFairId, $participationId, $slotId)
    {
        $validated = $request->validated();

        $slot = InterviewSlot::where('id', $slotId)
            ->where('participation_id', $participationId)
            ->first();

        if (!$slot) {
            return $this->sendError('Interview slot not found for this participation.', [], 404);
        }

        if ($slot->participation->event_id != $jobFairId) {
            return $this->sendError('Interview slot does not belong to this job fair.', [], 403);
        }

        $user = auth()->user();
        if ($user->hasRole('company_representative') && $slot->participation->submitted_by !== $user->id) {
            return $this->sendError('You are not authorized to update this slot.', [], 403);
        }

        try {
            $slot->update($validated);

            return $this->sendResponse(
                $slot->refresh(),
                'Interview slot updated successfully.'
            );
        } catch (\Exception $e) {
            return $this->sendError('Failed to update interview slot.', [$e->getMessage()], 500);
        }
    }

    // Company rep (ownership checked): Delete a slot
    public function destroy($jobFairId, $participationId, $slotId)
    {
        $slot = InterviewSlot::where('id', $slotId)
            ->where('participation_id', $participationId)
            ->first();

        if (!$slot) {
            return $this->sendError('Interview slot not found for this participation.', [], 404);
        }

        if ($slot->participation->event_id != $jobFairId) {
            return $this->sendError('Interview slot does not belong to this job fair.', [], 403);
        }

        $user = auth()->user();
        if ($user->hasRole('company_representative') && $slot->participation->submitted_by !== $user->id) {
            return $this->sendError('You are not authorized to delete this slot.', [], 403);
        }

        try {
            $slot->delete();

            return $this->sendResponse(
                null,
                'Interview slot deleted successfully.'
            );
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete interview slot.', [$e->getMessage()], 500);
        }
    }
}
