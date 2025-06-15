<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\Controller;
use App\Models\Event\Event;
use App\Models\JobFair\InterviewSlot;
use App\Models\JobFair\JobFairParticipation;
use Illuminate\Http\Request;

class InterviewSlotController extends Controller
{
    public function jobFairSlots($jobFairId)
    {
        // Make sure it's a real Job Fair event
        $event = Event::where('id', $jobFairId)
            ->where('type', 'Job Fair')
            ->first();

        if (!$event) {
            return response()->json([
                'message' => 'Job Fair not found.'
            ], 404);
        }

        // Fetch all interview slots for this job fair (via participation)
        $slots = InterviewSlot::whereHas('participation', function ($query) use ($jobFairId) {
                $query->where('event_id', $jobFairId);
            })
            ->with('participation.company') // to show company names if needed
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'job_fair_id' => $jobFairId,
            'slots' => $slots
        ]);
    }

    
    public function participationSlots($jobFairId, $participationId)
    {
        // Check if the participation belongs to the given job fair
        $participation = JobFairParticipation::where('id', $participationId)
            ->where('event_id', $jobFairId)
            ->first();

        if (!$participation) {
            return response()->json([
                'message' => 'Participation not found for this job fair.'
            ], 404);
        }

        // Fetch all slots for this participation
        $slots = InterviewSlot::where('participation_id', $participationId)
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'job_fair_id' => $jobFairId,
            'participation_id' => $participationId,
            'slots' => $slots
        ]);
    }
    public function store(Request $request, $jobFairId, $participationId)
    {
        $validated = $request->validate([
            'slot_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'required|integer|min:5|max:120',
            'max_interviews_per_slot' => 'required|integer|min:1|max:20',
            'is_break' => 'boolean',
            'break_reason' => 'nullable|string|max:255',
            'is_available' => 'boolean',
        ]);

        // Confirm the participation exists and belongs to this job fair
        $participation = JobFairParticipation::where('id', $participationId)
            ->where('event_id', $jobFairId)
            ->first();

        if (!$participation) {
            return response()->json([
                'message' => 'Participation not found for this job fair.'
            ], 404);
        }

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

        return response()->json([
            'message' => 'Interview slot created successfully.',
            'slot' => $slot
        ], 201);
    }

    public function update(Request $request, $slotId)
    {
        $validated = $request->validate([
            'slot_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'duration_minutes' => 'sometimes|integer|min:5|max:120',
            'max_interviews_per_slot' => 'sometimes|integer|min:1|max:20',
            'is_break' => 'sometimes|boolean',
            'break_reason' => 'nullable|string|max:255',
            'is_available' => 'sometimes|boolean',
        ]);

        $slot = InterviewSlot::find($slotId);

        if (!$slot) {
            return response()->json(['message' => 'Interview slot not found.'], 404);
        }

        $slot->update($validated);

        return response()->json([
            'message' => 'Interview slot updated successfully.',
            'slot' => $slot
        ]);
    }
    public function destroy($slotId)
    {
        $slot = InterviewSlot::find($slotId);

        if (!$slot) {
            return response()->json(['message' => 'Interview slot not found.'], 404);
        }

        $slot->delete(); // permanently deletes the record

        return response()->json([
            'message' => 'Interview slot deleted successfully.'
        ]);
    }
    public function show($id)
    {
        $slot = InterviewSlot::with([
            'participation.company', // Show company info
        ])->find($id);

        if (!$slot) {
            return response()->json([
                'message' => 'Interview slot not found.'
            ], 404);
        }

        return response()->json([
            'slot' => $slot
        ]);
    }

}
