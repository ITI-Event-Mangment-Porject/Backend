<?php

namespace App\Http\Controllers;

use App\Models\Event\Event;
use App\Models\RegistrationAndInterview\EventRegistration;
use App\Models\FeedbackAndAnalytics\FeedbackResponse;
use App\Models\JobFair\JobFairParticipation;
use App\Models\RegistrationAndInterview\InterviewRequest;
use Illuminate\Http\JsonResponse;
use App\Exports\EventAnalyticsExport;
use Maatwebsite\Excel\Facades\Excel;


class AnalyticsController extends Controller
{
    public function getEventAnalytics($eventId): JsonResponse
    {
        $event = Event::with(['registrations', 'feedbackResponses', 'jobFairParticipations', 'interviewRequests'])->find($eventId);

        if (!$event) {
            return response()->json(['error' => 'Event not found'], 404);
        }

        $registrations = $event->registrations;
        $checkedInCount = $registrations->whereNotNull('checked_in_at')->count();
        $feedbackResponses = $event->feedbackResponses;
        $interviewRequests = $event->interviewRequests;

        $averageRating = $feedbackResponses->whereNotNull('overall_rating')->avg('overall_rating');
        $participatingCompanies = $event->jobFairParticipations->count();

        return response()->json([
            'event_id' => $event->id,
            'event_title' => $event->title,
            'event_type' => $event->type,
            'total_registrations' => $registrations->count(),
            'checked_in_count' => $checkedInCount,
            'feedback_count' => $feedbackResponses->count(),
            'average_rating' => round($averageRating, 2),
            'interview_requests_count' => $interviewRequests->count(),
            'participating_companies' => $participatingCompanies,
        ]);
    }

    public function getDashboardAnalytics(): JsonResponse
    {
        return response()->json([
            'total_events' => \App\Models\Event\Event::count(),
            'active_events' => \App\Models\Event\Event::active()->count(),
            'total_users' => \App\Models\Auth\User::count(),
            'total_companies' => \App\Models\Company\Company::count(),
            'total_registrations' => EventRegistration::count(),
            'total_feedbacks' => FeedbackResponse::count(),
        ]);
    }

   public function exportEventAnalytics($eventId)
{
    $event = Event::with(['registrations', 'feedbackResponses', 'interviewRequests', 'jobFairParticipations'])
                  ->findOrFail($eventId);

    return Excel::download(new EventAnalyticsExport($event), 'event-analytics-' . $event->id . '.xlsx');
}

}
