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
    
// No additional code needed here, but update the checkIn method to use sendResponse and sendError:

    public function checkIn(Request $request, $eventId)
    {
    $user = auth()->user();
    $event = Event::findOrFail($eventId);

    $registration = EventRegistration::where('event_id', $event->id)
                                       ->where('user_id', $user->id)
                                       ->first();

        if ($registration) {
        if ($registration->status === 'attended') {
            return $this->sendResponse(null, 'You have already checked in.');
        }

        $registration->status = 'attended';
        $registration->checked_in_at = now();
        $registration->check_in_method = 'qr';
        $registration->save();

        return $this->sendResponse(null, 'You have been checked in successfully!');
        } else {
          return $this->sendError('You are not registered for this event.', [], 404);
        }
    }
}