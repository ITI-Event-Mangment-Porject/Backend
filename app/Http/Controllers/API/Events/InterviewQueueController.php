<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\Request;
use App\Models\RegistrationAndInterview\InterviewQueue;
use App\Models\JobFair\InterviewSlot;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
                'queue_position' => 'sometimes|integer|min:1',
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
}
