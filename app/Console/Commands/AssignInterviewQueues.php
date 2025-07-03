<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobFair\InterviewSlot;
use App\Models\RegistrationAndInterview\InterviewRequest;
use App\Models\RegistrationAndInterview\InterviewQueue;

class AssignInterviewQueues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'interviews:assign-queues {event_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign approved interview requests to available interview slots, skipping breaks.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventId = $this->argument('event_id');

        $slots = InterviewSlot::where('is_break', false)
            ->when($eventId, function ($q) use ($eventId) {
                $q->whereHas('participation', function ($q2) use ($eventId) {
                    $q2->where('event_id', $eventId);
                });
            })
            ->with('participation')
            ->orderBy('slot_date')
            ->orderBy('start_time')
            ->get();

        $assignedCount = 0;

        // Group slots by company/event to assign queue_position globally per company/event
        $slotsByCompanyEvent = [];
        foreach ($slots as $slot) {
            $companyId = $slot->participation->company_id;
            $eventId = $slot->participation->event_id;
            $slotsByCompanyEvent[$eventId][$companyId][] = $slot;
        }

        foreach ($slotsByCompanyEvent as $eventId => $companies) {
            foreach ($companies as $companyId => $companySlots) {
                // Get all approved, unassigned requests for this company/event
                $requests = InterviewRequest::where('event_id', $eventId)
                    ->where('company_id', $companyId)
                    ->where('status', 'approved')
                    ->whereDoesntHave('queueEntry', function ($q) {
                        $q->whereNotNull('slot_id');
                    })
                    ->get();

                // Get the current max queue_position for this company/event
                $maxPosition = InterviewQueue::where('company_id', $companyId)
                    ->whereHas('slot.participation', function ($q) use ($eventId) {
                        $q->where('event_id', $eventId);
                    })
                    ->max('queue_position') ?? 0;

                $position = $maxPosition + 1;

                // Flatten all slots for this company/event, ordered by slot_date/start_time
                $companySlots = collect($companySlots)->sortBy([
                    ['slot_date', 'asc'],
                    ['start_time', 'asc'],
                ])->values();

                $slotCapacities = [];
                foreach ($companySlots as $slot) {
                    $alreadyAssigned = InterviewQueue::where('slot_id', $slot->id)->count();
                    $slotCapacities[$slot->id] = $slot->max_interviews_per_slot - $alreadyAssigned;
                }

                foreach ($requests as $request) {
                    // Find the first slot with available capacity
                    $assigned = false;
                    foreach ($companySlots as $slot) {
                        if ($slotCapacities[$slot->id] > 0) {
                            InterviewQueue::create([
                                'interview_request_id' => $request->id,
                                'company_id' => $companyId,
                                'user_id' => $request->user_id,
                                'slot_id' => $slot->id,
                                'queue_position' => $position++,
                                'status' => 'waiting',
                            ]);
                            $slotCapacities[$slot->id]--;
                            $assignedCount++;
                            $assigned = true;
                            break;
                        }
                    }
                    if (!$assigned) {
                        $this->info("No available slots for request {$request->id} (company $companyId, event $eventId).");
                    }
                }
            }
        }

        $this->info("Assignment complete. $assignedCount interview(s) assigned to slots.");
        return 0;
    }
}
