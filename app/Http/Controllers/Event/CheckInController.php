<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\API\BaseApiController;
use App\Http\Controllers\Controller;
use App\Models\Event\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RegistrationAndInterview\EventRegistration;

class CheckInController extends BaseApiController
{
    public function checkIn(Request $request, $eventId)
    {
        $user = Auth::user();
        $event = Event::findOrFail($eventId);

        $registration = EventRegistration::where('event_id', $event->id)
                                           ->where('user_id', $user->id)
                                           ->first();

        if ($registration) {
            if ($registration->status === 'attended') {
                return response()->json(['message' => 'You have already checked in.'], 200);
            }

            $registration->status = 'attended';
            $registration->checked_in_at = now();
            $registration->check_in_method = 'qr';
            $registration->save();

            return response()->json(['message' => 'You have been checked in successfully!'], 200);
        } else {
            return response()->json(['message' => 'You are not registered for this event.'], 404);
        }
    }
}
