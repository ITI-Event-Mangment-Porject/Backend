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

            $waitingCounter = 0;
            $queue = InterviewQueue::with(['user.track', 'interviewRequest'])
                ->where('slot_id', $slotId)
                ->orderBy('order_key')
                ->get()
                ->map(function ($entry) use (&$waitingCounter) {
                    $position = 0;
                    if ($entry->status === 'waiting') {
                        $waitingCounter++;
                        $position = $waitingCounter;
                    }
                    return [
                        'queue_id' => $entry->id,
                        'queue_position' => $position,
                        'order_key' => $entry->order_key,
                        'status' => $entry->status,
                    'student' => [
                        'id' => optional($entry->user)->id,
                        'first_name' => optional($entry->user)->first_name,
                        'last_name' => optional($entry->user)->last_name,
                        'email' => optional($entry->user)->email,
                        'phone' => optional($entry->user)->phone,
                        'profile_image' => optional($entry->user)->profile_image,
                        'cv_path' => optional($entry->user)->cv_path,
                        'track_id' => optional($entry->user)->track_id,
                        'track_name' => optional(optional($entry->user)->track)->name,
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
            // Fetch all relevant queues to calculate statistics
            $allQueues = InterviewQueue::with(['user', 'user.track'])
                ->where('company_id', $companyId)
                ->whereHas('slot.participation', function ($q) use ($jobFairId, $companyId) {
                    $q->where('event_id', $jobFairId)
                      ->where('company_id', $companyId);
                })
                ->get();

            $totalStudents = $allQueues->count();
            $waitingStudentsCount = $allQueues->where('status', 'waiting')->count();
            $completedStudentsCount = $allQueues->where('status', 'completed')->count();
            $inInterviewStudentEntry = $allQueues->where('status', 'in_interview')->first();

            $currentIntervieweeName = null;
            if ($inInterviewStudentEntry && $inInterviewStudentEntry->user) {
                $currentIntervieweeName = $inInterviewStudentEntry->user->first_name . ' ' . $inInterviewStudentEntry->user->last_name;
            }

            $waitingCounter = 0;
            $queues = $allQueues->map(function ($entry) use (&$waitingCounter) {
                $position = 0;
                if ($entry->status === 'waiting') {
                    $waitingCounter++;
                    $position = $waitingCounter;
                }
                return [
                    'queue_id' => $entry->id,
                    'queue_position' => $position,
                    'order_key' => $entry->order_key,
                    'status' => $entry->status,
                    'student' => [
                        'id' => optional($entry->user)->id,
                        'first_name' => optional($entry->user)->first_name,
                        'last_name' => optional($entry->user)->last_name,
                        'email' => optional($entry->user)->email,
                        'phone' => optional($entry->user)->phone,
                        'profile_image' => optional($entry->user)->profile_image,
                        'cv_path' => optional($entry->user)->cv_path,
                        'track_id' => optional($entry->user)->track_id,
                        'track_name' => optional(optional($entry->user)->track)->name,
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
                [
                    'queue' => $queues,
                    'summary' => [
                        'total' => $totalStudents,
                        'waiting' => $waitingStudentsCount,
                        'completed' => $completedStudentsCount,
                        'in_interview_student_name' => $currentIntervieweeName,
                    ],
                ],
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
            $waitingCounter = 0;
            $queues = InterviewQueue::with(['slot', 'company', 'user.track', 'interviewRequest'])
                ->where('user_id', $studentId)
                ->whereHas('slot.participation', function ($q) use ($jobFairId) {
                    $q->where('event_id', $jobFairId);
                })
                ->orderBy('order_key')
                ->get()
                ->map(function ($entry) use (&$waitingCounter) {
                    $position = 0;
                    if ($entry->status === 'waiting') {
                        $waitingCounter++;
                        $position = $waitingCounter;
                    }
                    return [
                        'queue_id' => $entry->id,
                        'queue_position' => $position,
                        'order_key' => $entry->order_key,
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
            $waitingCounter = 0;
            $queues = InterviewQueue::with(['slot', 'company', 'user.track', 'interviewRequest'])
                ->whereHas('slot.participation', function ($q) use ($jobFairId) {
                    $q->where('event_id', $jobFairId);
                })
                ->orderBy('order_key')
                ->get()
                ->map(function ($entry) use (&$waitingCounter) {
                    $position = 0;
                    if ($entry->status === 'waiting') {
                        $waitingCounter++;
                        $position = $waitingCounter;
                    }
                    return [
                        'queue_id' => $entry->id,
                        'queue_position' => $position,
                        'order_key' => $entry->order_key,
                        'status' => $entry->status,
                        'company' => [
                            'id' => $entry->company->id,
                            'name' => $entry->company->name,
                            'logo_path' => $entry->company->logo_path,
                        ],
                    'student' => [
                        'id' => optional($entry->user)->id,
                        'first_name' => optional($entry->user)->first_name,
                        'last_name' => optional($entry->user)->last_name,
                        'email' => optional($entry->user)->email,
                        'phone' => optional($entry->user)->phone,
                        'profile_image' => optional($entry->user)->profile_image,
                        'cv_path' => optional($entry->user)->cv_path,
                        'track_id' => optional($entry->user)->track_id,
                        'track_name' => optional(optional($entry->user)->track)->name,
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
                'order_key' => 'sometimes|numeric',
                'status' => 'sometimes|in:waiting,in_interview,completed,skipped,cancelled',
                'notes' => 'nullable|string',
            ]);

            $queue->update($data);

            return $this->sendResponse(
                [
                    'queue_id' => $queue->id,
                    'order_key' => $queue->order_key,
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
                    'order_key' => 0, // Reset their key to 0 as they are no longer in the active queue
                    'notes' => 'Interview completed.',
                    'interview_ended_at' => now(),
                ]);
            }

            // 3. Find the next student in the queue for this slot.
            $nextStudent = InterviewQueue::where('slot_id', $slotId)
                ->where('status', 'waiting')
                ->orderBy('order_key', 'asc')
                ->first();

            // If there is no one left in the queue, commit and return.
            if (!$nextStudent) {
                DB::commit();
                return $this->sendResponse([], 'No students left in the queue.');
            }

            // 4. Update the next student's status to 'in_interview'.
            $nextStudent->update([
                'status' => 'in_interview',
                'order_key' => 0, // Reset their key to 0 as they are now in interview
                'interview_started_at' => now(),
            ]);

            // 5. No need to decrement anything. The ordering is now handled by the order_key.


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
            $lastOrderKey = InterviewQueue::where('slot_id', $queueEntry->slot_id)->max('order_key');

            $queueEntry->update([
                'status' => 'waiting',
                'order_key' => $lastOrderKey + 1,
            ]);

            return $this->sendResponse(
                [
                    'queue_id' => $queueEntry->id,
                    'status' => $queueEntry->status,
                    'order_key' => $queueEntry->order_key,
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
