<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\API\BaseApiController;
use App\Models\RegistrationAndInterview\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckInController extends BaseApiController
{
    public function checkIn(Request $request)
    {
        $request->validate([
            'token' => 'required|string|exists:event_registrations,qr_code_token',
        ]);

        try {
            $registration = EventRegistration::with(['user', 'event'])
                ->where('qr_code_token', $request->token)
                ->firstOrFail();

            if ($registration->isCheckedIn()) {
                return $this->sendError('User has already been checked in.', [], 422);
            }

            // Check if the event status allows check-ins
            $disallowedStatuses = ['completed', 'archived', 'draft'];
            if (in_array($registration->event->status, $disallowedStatuses)) {
                return $this->sendError('Check-in is not allowed for events with status: ' . $registration->event->status, [], 403);
            }

            $registration->update([
                'status' => 'attended',
                'checked_in_at' => now(),
                'check_in_method' => 'qr',
            ]);

            $responseData = [
                'event_name' => $registration->event->name,
                'user_name' => $registration->user->first_name . ' ' . $registration->user->last_name,
                'checked_in_at' => $registration->checked_in_at,
            ];

            return $this->sendResponse($responseData, 'Check-in successful.');

        } catch (\Exception $e) {
            Log::error('Check-in failed: ' . $e->getMessage());
            return $this->sendError('Check-in failed. Please try again.', [$e->getMessage()], 500);
        }
    }
    public function getAttendanceList($eventId)
    {
        $registrations = EventRegistration::where('event_id', $eventId)
            ->where('status', 'attended')
            ->with(['user'])
            ->get();

        if ($registrations->isEmpty()) {
            return $this->sendError('No attendance records found for this event.', [], 404);
        }

        $attendanceList = $registrations->map(function ($registration) {
            return [
                'name' => $registration->user->first_name . ' ' . $registration->user->last_name,
                'Email' => $registration->user->email,
                
                'checked_in_at' => $registration->checked_in_at,
            ];
        });

        return $this->sendResponse($attendanceList, 'Attendance list retrieved successfully.');
    }
}
