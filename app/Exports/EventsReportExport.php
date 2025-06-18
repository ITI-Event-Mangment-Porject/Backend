<?php

namespace App\Exports;

use Exception;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use App\Models\Event\Event;

class EventsReportExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        //
        try {
            return Event::withCount([
                'registrations',
                'feedbackResponses',
                'interviewRequests',
                'jobFairParticipations',
            ])->get()->map(function ($event) {
                return [
                    'event_name' => $event->title,
                    'registeration_count' => $event->registrations_count,
                    'feedback_response' => $event->feedback_responses_count,
                    'interview_request' => $event->interview_requests_count,
                    'job_fair' => $event->job_fair_participations_count,
                ];
            });
        } catch (Exception $e) {
            \Log::error('Error in EventsReportExport: ' . $e->getMessage());
            return collect(); 
        }

    }
    public function headings(): array
    {
        return [
            'Title',
            'Registrations',
            'Feedbacks',
            'Interview Requests',
            'Job Fair Participations'
        ];
    }
}
