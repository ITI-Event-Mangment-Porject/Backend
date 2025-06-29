<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use App\Models\Event\Event;

class EventAnalyticsExport implements FromArray
{
    protected $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function array(): array
    {
        $registrations = $this->event->registrations;
        $feedbackResponses = $this->event->feedbackResponses;
        $interviewRequests = $this->event->interviewRequests;
        $companies = $this->event->jobFairParticipations;

        return [
            ['Event Analytics Report'],
            ['Event Title', $this->event->title],
            ['Event Type', $this->event->type],
            ['Total Registrations', $registrations->count()],
            ['Checked In', $registrations->whereNotNull('checked_in_at')->count()],
            ['Feedback Count', $feedbackResponses->count()],
            ['Average Rating', round($feedbackResponses->whereNotNull('overall_rating')->avg('overall_rating'), 2)],
            ['Interview Requests', $interviewRequests->count()],
            ['Participating Companies', $companies->count()],
        ];
    }
}
