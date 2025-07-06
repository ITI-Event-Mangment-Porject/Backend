<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\Request;
use App\Models\RegistrationAndInterview\InterviewQueue;
use App\Models\JobFair\InterviewSlot;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class InterviewQueueController extends BaseApiController
{
    // Admin, staff, company rep: Get the queue for a specific slot
    public function slotQueue($jobFairId, $slotId)
    {
        try {
            $slot = InterviewSlot::with('participation.company')
                ->where('id', $slotId)
                ->whereHas('participation', function ($q) use ($jobFairId) {
                    $q->where('event_id', $jobFairId);
                })
                ->firstOrFail();

            $user = auth()->user();
            // Company rep: only for their own company
            if ($user->hasRole('company_representative') && $slot->participation->company_id !== $user->company_id) {
                return $this->sendError('You are not authorized to view this slot queue.', [], 403);
            }

            $queue = InterviewQueue::with(['user.track', 'interviewRequest'])
                ->where('slot_id', $slotId)
                ->orderBy('queue_position')
                ->get()
                ->map(function ($entry) {
                    return [
                        'queue_id' => $entry->id,
                        'queue_position' => $entry->queue_position,
                        'status' => $entry->status,
                        'student' => [
                            'id' => $entry->user->id,
                            'first_name' => $entry->user->first_name,
                            'last_name' => $entry->user->last_name,
                            'email' => $entry->user->email,
                            'phone' => $entry->user->phone,
                            'profile_image' => $entry->user->profile_image,
                            'cv_path' => $entry->user->cv_path,
                            'track_id' => $entry->user->track_id,
                            'track_name' => optional($entry->user->track)->name,
                        ],
                        'interview_request_id' => $entry->interview_request_id,
                        'notes' => $entry->notes,
                    ];
                });

            return $this->sendResponse([
                'slot' => [
                    'id' => $slot->id,
                    'date' => $slot->slot_date,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'duration_minutes' => $slot->duration_minutes,
                    'max_interviews_per_slot' => $slot->max_interviews_per_slot,
                    'is_break' => $slot->is_break,
                    'company' => [
                        'id' => $slot->participation->company->id,
                        'name' => $slot->participation->company->name,
                        'logo_path' => $slot->participation->company->logo_path,
                    ],
                ],
                'queue' => $queue,
            ], 'Slot queue retrieved successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Interview slot not found.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while fetching the slot queue.', [$e->getMessage()], 500);
        }
    }

    // Admin, staff, company rep: Get all queues for a company in a job fair
    public function companyQueues($jobFairId, $companyId)
    {
        $user = auth()->user();
        // Company rep: only for their own company
        if ($user->hasRole('company_representative') && $user->company_id != $companyId) {
            return $this->sendError('You are not authorized to view queues for this company.', [], 403);
        }

        try {
            $queues = InterviewQueue::with(['slot', 'user.track', 'interviewRequest'])
                ->where('company_id', $companyId)
                ->whereHas('slot.participation', function ($q) use ($jobFairId, $companyId) {
                    $q->where('event_id', $jobFairId)
                      ->where('company_id', $companyId);
                })
                ->orderBy('queue_position')
                ->get()
                ->map(function ($entry) {
                    return [
                        'queue_id' => $entry->id,
                        'queue_position' => $entry->queue_position,
                        'status' => $entry->status,
                        'student' => [
                            'id' => $entry->user->id,
                            'first_name' => $entry->user->first_name,
                            'last_name' => $entry->user->last_name,
                            'email' => $entry->user->email,
                            'phone' => $entry->user->phone,
                            'profile_image' => $entry->user->profile_image,
                            'cv_path' => $entry->user->cv_path,
                            'track_id' => $entry->user->track_id,
                            'track_name' => optional($entry->user->track)->name,
                        ],
                        'slot' => [
                            'id' => $entry->slot->id,
                            'date' => $entry->slot->slot_date,
                            'start_time' => $entry->slot->start_time,
                            'end_time' => $entry->slot->end_time,
                        ],
                        'interview_request_id' => $entry->interview_request_id,
                        'notes' => $entry->notes,
                    ];
                });

            return $this->sendResponse(
                ['queue' => $queues],
                'Company queue retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while fetching company queues.', [$e->getMessage()], 500);
        }
    }

    // Student: Get all queues for themselves in a job fair
    public function studentQueues($jobFairId, $studentId)
    {
        $user = auth()->user();
        // Student: only for themselves
        if ($user->hasRole('student') && $user->id != $studentId) {
            return $this->sendError('You are not authorized to view queues for this student.', [], 403);
        }

        try {
            $queues = InterviewQueue::with(['slot', 'company', 'user.track', 'interviewRequest'])
                ->where('user_id', $studentId)
                ->whereHas('slot.participation', function ($q) use ($jobFairId) {
                    $q->where('event_id', $jobFairId);
                })
                ->orderBy('queue_position')
                ->get()
                ->map(function ($entry) {
                    return [
                        'queue_id' => $entry->id,
                        'queue_position' => $entry->queue_position,
                        'status' => $entry->status,
                        'company' => [
                            'id' => $entry->company->id,
                            'name' => $entry->company->name,
                            'logo_path' => $entry->company->logo_path,
                        ],
                        'slot' => [
                            'id' => $entry->slot->id,
                            'date' => $entry->slot->slot_date,
                            'start_time' => $entry->slot->start_time,
                            'end_time' => $entry->slot->end_time,
                        ],
                        'interview_request_id' => $entry->interview_request_id,
                        'notes' => $entry->notes,
                    ];
                });

            return $this->sendResponse(
                ['queue' => $queues],
                'Student queue retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while fetching student queues.', [$e->getMessage()], 500);
        }
    }

    // Admin, staff: Get all queues for a job fair
    public function jobFairQueues($jobFairId)
    {
        // No extra check needed, middleware restricts to admin/staff
        try {
            $queues = InterviewQueue::with(['slot', 'company', 'user.track', 'interviewRequest'])
                ->whereHas('slot.participation', function ($q) use ($jobFairId) {
                    $q->where('event_id', $jobFairId);
                })
                ->orderBy('queue_position')
                ->get()
                ->map(function ($entry) {
                    return [
                        'queue_id' => $entry->id,
                        'queue_position' => $entry->queue_position,
                        'status' => $entry->status,
                        'company' => [
                            'id' => $entry->company->id,
                            'name' => $entry->company->name,
                            'logo_path' => $entry->company->logo_path,
                        ],
                        'student' => [
                            'id' => $entry->user->id,
                            'first_name' => $entry->user->first_name,
                            'last_name' => $entry->user->last_name,
                            'email' => $entry->user->email,
                            'phone' => $entry->user->phone,
                            'profile_image' => $entry->user->profile_image,
                            'cv_path' => $entry->user->cv_path,
                            'track_id' => $entry->user->track_id,
                            'track_name' => optional($entry->user->track)->name,
                        ],
                        'slot' => [
                            'id' => $entry->slot->id,
                            'date' => $entry->slot->slot_date,
                            'start_time' => $entry->slot->start_time,
                            'end_time' => $entry->slot->end_time,
                        ],
                        'interview_request_id' => $entry->interview_request_id,
                        'notes' => $entry->notes,
                    ];
                });

            return $this->sendResponse(
                ['queue' => $queues],
                'Job fair queues retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while fetching job fair queues.', [$e->getMessage()], 500);
        }
    }

    // Admin, staff: Update queue position or status
    public function updateQueue(Request $request, $jobFairId, $queueId)
    {
        try {
            $queue = InterviewQueue::where('id', $queueId)
                ->whereHas('slot.participation', function ($q) use ($jobFairId) {
                    $q->where('event_id', $jobFairId);
                })
                ->firstOrFail();

            $data = $request->validate([
                'queue_position' => 'sometimes|integer|min:0',
                'status' => 'sometimes|in:waiting,in_interview,completed,skipped,cancelled',
                'notes' => 'nullable|string',
            ]);

            $queue->update($data);

            return $this->sendResponse(
                [
                    'queue_id' => $queue->id,
                    'queue_position' => $queue->queue_position,
                    'status' => $queue->status,
                    'notes' => $queue->notes,
                ],
                'Queue entry updated successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Queue entry not found.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while updating the queue entry.', [$e->getMessage()], 500);
        }
    }

    // Admin: Remove a student from a queue
    public function removeFromQueue($jobFairId, $queueId)
    {
        try {
            $queue = InterviewQueue::where('id', $queueId)
                ->whereHas('slot.participation', function ($q) use ($jobFairId) {
                    $q->where('event_id', $jobFairId);
                })
                ->firstOrFail();

            $queue->delete();

            return $this->sendResponse([], 'Queue entry removed successfully.');
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Queue entry not found.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while removing the queue entry.', [$e->getMessage()], 500);
        }
    }

    public function pending(Request $request, $jobFairId, $queueId)
    {
        return $this->updateStatus($jobFairId, $queueId, 'pending');
    }

    public function resume(Request $request, $jobFairId, $queueId)
    {
        return $this->updateStatus($jobFairId, $queueId, 'waiting');
    }

    public function next(Request $request, $jobFairId, $slotId)
    {
        // Start a database transaction to ensure all or nothing is updated.
        DB::beginTransaction();

        try {
            // 1. Find the current student in the interview for this slot (if any)
            $currentInterview = InterviewQueue::where('slot_id', $slotId)
                ->where('status', 'in_interview')
                ->first();

            // 2. If a student is already in an interview, mark them as completed.
            if ($currentInterview) {
                $currentInterview->update([
                    'status' => 'completed',
                    'queue_position' => 0, // Reset their position to 0 since they are now in interview
                    'notes' => 'Interview completed.',
                    'interview_ended_at' => now(),
                ]);
            }

            // 3. Find the next student in the queue for this slot.
            $nextStudent = InterviewQueue::where('slot_id', $slotId)
                ->where('status', 'waiting')
                ->orderBy('queue_position', 'asc')
                ->first();

            // If there is no one left in the queue, commit and return.
            if (!$nextStudent) {
                DB::commit();
                return $this->sendResponse([], 'No students left in the queue.');
            }

            // 4. Update the next student's status to 'in_interview'.
            $nextStudent->update([
                'status' => 'in_interview',
                'queue_position' => 0, // Reset their position to 0 since they are now in interview
                'interview_started_at' => now(),
            ]);

            // 5. Decrement the queue position for all other waiting students in this slot.
            InterviewQueue::where('slot_id', $slotId)
                ->where('status', 'waiting')
                ->where('id', '!=', $nextStudent->id) // Exclude the student now in interview
                ->decrement('queue_position');


            DB::commit();

            // After the transaction, you would broadcast the queue update event here.
            // For example: broadcast(new QueueUpdated($slotId))->toOthers();

            return $this->sendResponse(
                [
                    'now_interviewing' => $nextStudent,
                ],
                'Next student called successfully. The queue has been updated.'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('An error occurred while advancing the queue.', [$e->getMessage()], 500);
        }
    }

    
    

    public function requeueLast(Request $request, $jobFairId, $queueId)
    {
        try {
            $queueEntry = InterviewQueue::where('id', $queueId)
                ->whereHas('slot.participation', function ($q) use ($jobFairId) {
                    $q->where('event_id', $jobFairId);
                })
                ->firstOrFail();

            // Find the last position in the queue for this specific slot
            $lastPosition = InterviewQueue::where('slot_id', $queueEntry->slot_id)->max('queue_position');

            $queueEntry->update([
                'status' => 'waiting',
                'queue_position' => $lastPosition + 1,
            ]);

            return $this->sendResponse(
                [
                    'queue_id' => $queueEntry->id,
                    'status' => $queueEntry->status,
                    'queue_position' => $queueEntry->queue_position,
                ],
                'Student has been moved to the end of the queue.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Queue entry not found.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while moving the student.', [$e->getMessage()], 500);
        }
    }

    private function updateStatus($jobFairId, $queueId, $status)
    {
        try {
            $queue = InterviewQueue::where('id', $queueId)
                ->whereHas('slot.participation', function ($q) use ($jobFairId) {
                    $q->where('event_id', $jobFairId);
                })
                ->firstOrFail();

            $queue->update(['status' => $status]);

            return $this->sendResponse(
                [
                    'queue_id' => $queue->id,
                    'status' => $queue->status,
                ],
                'Queue entry updated successfully.'
            );
        } catch (ModelNotFoundException $e) {
            return $this->sendError('Queue entry not found.');
        } catch (\Exception $e) {
            return $this->sendError('An error occurred while updating the queue entry.', [$e->getMessage()], 500);
        }
    }
}
