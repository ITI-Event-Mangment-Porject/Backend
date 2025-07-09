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
}
