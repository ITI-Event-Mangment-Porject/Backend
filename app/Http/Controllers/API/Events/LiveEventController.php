<?php

namespace App\Http\Controllers\API\Events;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Requests\Events\StartLiveEventRequest;
use App\Http\Requests\Events\EndLiveEventRequest;
use App\Models\Event\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class LiveEventController extends BaseApiController
{
    /**
     * Get the live status of an event
     * 
     * @param int|string $id Event ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function status($id)
    {
        try {
            $event = $this->findEvent($id);

            if (!$event) {
                return $this->sendError('Event not found', ['error' => 'Event with ID ' . $id . ' not found'], 404);
            }            // Get attendees count for this event (only those who attended)
            $attendeesCount = DB::table('event_registrations')
                ->where('event_id', $event->id)
                ->where('status', 'attended') // only users who attended
                ->count();            // Return live event status based on event status
            return $this->sendResponse([                'isLive' => $event->status === 'ongoing',
                'status' => $event->status,
                'eventName' => $event->title,
                'startTime' => $event->start_time, // Now just time string like "14:30:00"
                'endTime' => $event->end_time,     // Now just time string like "16:30:00"
                'attendeesCount' => $attendeesCount,
                'eventDetails' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'location' => $event->location,
                    'type' => $event->type
                ]
            ], 'Live event status retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve live event status', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Start a live event (Admin only)
     * 
     * @param \App\Http\Requests\Events\StartLiveEventRequest $request
     * @param int|string $id Event ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function start(StartLiveEventRequest $request, $id)
    {
        try {
            $event = $this->findEvent($id);

            if (!$event) {
                return $this->sendError('Event not found', ['error' => 'Event with ID ' . $id . ' not found'], 404);
            }

            // Check if event is already ongoing
            if ($event->status === 'ongoing') {
                return $this->sendError('Event already live', ['error' => 'This event is already ongoing'], 400);
            }

            // Check if event can be started (not completed or archived)
            if (in_array($event->status, ['completed', 'archived'])) {
                return $this->sendError('Cannot start event', ['error' => 'Cannot start an event that is already completed or archived'], 400);
            }            // Update event status to 'ongoing' and set actual start time
            $event->status = 'ongoing';            // Get validated data from the request
            $validatedData = $request->validated();

            // Always update the start_time to current time (time only)
            $event->start_time = Carbon::now()->format('H:i:s');
            Log::info('Setting start_time to: ' . Carbon::now()->format('H:i:s'));

            $result = $event->save();
            Log::info('Event save result: ' . ($result ? 'success' : 'failed'));
            Log::info('Event start_time after save: ' . $event->start_time);// Get all attendees for this event (only those who attended)
            $attendees = DB::table('event_registrations')
                ->join('users', 'event_registrations.user_id', '=', 'users.id')
                ->where('event_registrations.event_id', $event->id)
                ->where('event_registrations.status', 'attended') // only users who attended
                ->select(
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                    'event_registrations.status as registration_status',
                    'event_registrations.registered_at'
                )
                ->get();

            // Here you would initialize WebSocket channel in a real implementation
            // For example: broadcast(new LiveEventStarted($event, $attendees));

            return $this->sendResponse([
                'event' => $event,
                'attendees' => $attendees,
                'attendeesCount' => $attendees->count(),
                'message' => 'Event is now live!'
            ], 'Live event started successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to start live event', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * End a live event (Admin only)
     * 
     * @param \App\Http\Requests\Events\EndLiveEventRequest $request
     * @param int|string $id Event ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function end(EndLiveEventRequest $request, $id)
    {
        try {
            $event = $this->findEvent($id);

            if (!$event) {
                return $this->sendError('Event not found', ['error' => 'Event with ID ' . $id . ' not found'], 404);
            }

            // Check if event is currently ongoing
            if ($event->status !== 'ongoing') {
                return $this->sendError('Event not live', ['error' => 'This event is not currently ongoing'], 400);
            }            // Update event status to 'completed'
            $event->status = 'completed';            // Get validated data from the request
            $validatedData = $request->validated();

            // Always update the end_time to current time (time only)
            $event->end_time = Carbon::now()->format('H:i:s');
            Log::info('Setting end_time to: ' . Carbon::now()->format('H:i:s'));

            $result = $event->save();
            Log::info('Event save result: ' . ($result ? 'success' : 'failed'));
            Log::info('Event end_time after save: ' . $event->end_time);// Get all attendees who attended this event
            $attendees = DB::table('event_registrations')
                ->join('users', 'event_registrations.user_id', '=', 'users.id')
                ->where('event_registrations.event_id', $event->id)
                ->where('event_registrations.status', 'attended') // only users who attended
                ->select(
                    'users.id',
                    'users.first_name',
                    'users.last_name',
                    'users.email',
                    'event_registrations.status as registration_status',
                    'event_registrations.registered_at'
                )
                ->get();

            // Here you would close WebSocket channels in a real implementation
            // For example: broadcast(new LiveEventEnded($event, $attendees));

            // Calculate duration if both start and end times are available
            $duration = null;
            $durationFormatted = null;
            if ($event->start_time && $event->end_time) {
                $startTime = Carbon::parse($event->start_time);
                $endTime = Carbon::parse($event->end_time);
                $duration = $startTime->diffInSeconds($endTime);
                $durationFormatted = $this->formatDuration($duration);
            }

            return $this->sendResponse([
                'event' => $event,
                'attendees' => $attendees,
                'attendeesCount' => $attendees->count(),
                'duration' => [
                    'seconds' => $duration,
                    'formatted' => $durationFormatted
                ],
                'message' => 'Event has been completed!'
            ], 'Live event ended successfully');
        } catch (\Exception $e) {
            return $this->sendError('Failed to end live event', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Find an event by ID or slug
     * 
     * @param int|string $id
     * @return \App\Models\Event\Event|null
     */
    protected function findEvent($id)
    {
        // Try to find by ID first
        $event = Event::find($id);

        // If not found by ID and it's not numeric, try to find by slug
        if (!$event && !is_numeric($id)) {
            $event = Event::where('slug', $id)->first();
        }

        return $event;
    }

    /**
     * Format duration in seconds to human-readable format
     * 
     * @param int $seconds
     * @return string
     */
    protected function formatDuration($seconds)
    {
        if (!$seconds) return '00:00:00';

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }
}
