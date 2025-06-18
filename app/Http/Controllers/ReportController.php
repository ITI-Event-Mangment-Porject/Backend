<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceReportExport;
use App\Exports\EventsReportExport;
use App\Exports\FeedbackReportExport;
use App\Http\Controllers\API\BaseApiController;
use App\Models\Event\Event;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class ReportController extends BaseApiController
{
    public function eventsReports()
    {
        try {
            $events = QueryBuilder::for(Event::class)
                ->withCount(['registrations', 'feedbackResponses', 'interviewRequests', 'jobFairParticipations'])
                ->allowedFilters([
                    AllowedFilter::partial('title'),
                    AllowedFilter::scope('start_time'),
                    AllowedFilter::scope('end_time'),
                ])
                ->get();

            return $this->sendResponse($events, 'Event report retrieved successfully');
        } catch (Exception $e) {
            \Log::error('Error fetching event reports: ' . $e->getMessage());
            return $this->sendError('Failed to fetch event report', ['error' => $e->getMessage()], 500);
        }
    }

    public function attendanceReports(Request $request)
    {
        try {
            $events = QueryBuilder::for(Event::class)
                ->with(['registrations.user'])
                ->allowedFilters([
                    AllowedFilter::partial('title'),
                ])->paginate($request->get('per_page', 5));



            if ($events->isEmpty()) {
                return $this->sendError('There are no events found.', [], 404);
            }

            $data = $events->map(function ($event) {
                $validRegistrations = $event->registrations->filter(function ($r) {
                    return $r->user !== null;
                });

                return [
                    'event' => $event->title,
                    'event_date' => Carbon::parse($event->start_time)->format('Y-m-d H:i:s'),
                    'total_registration' => $validRegistrations->count(),
                    'attendees' => $validRegistrations->map(function ($r) {
                        return [
                            'name' => $r->user->first_name . " " . $r->user->last_name,
                            'email' => $r->user->email,
                            'phone' => $r->user->phone
                        ];
                    })->values(),
                ];
            });

            return $this->sendResponse($data, 'Attendance report retrieved successfully');

        } catch (Exception $e) {
            \Log::error('Error fetching attendance reports: ' . $e->getMessage());
            return $this->sendError('Failed to fetch attendance report', ['error' => $e->getMessage()], 500);
        }
    }

    public function feedbackReports()
    {
        try {
            $events = QueryBuilder::for(Event::class)
                ->with(['feedbackResponses.user', 'feedbackForms'])
                ->allowedFilters([
                    AllowedFilter::partial('title'),
                    AllowedFilter::partial('start_time'),
                    AllowedFilter::partial('end_time'),
                ])
                ->get();
            if ($events->isEmpty()) {
                return $this->sendError('There is no feedback available for any event.', [], 404);
            }

            $data = $events->map(function ($event) {
                $responses = $event->feedbackResponses;

                $form = $event->feedbackForms()->first();

                // Extract questions from form config
                $questions = [];
                if ($form && $form->form_config) {
                    // If form_config is JSON string, decode it
                    $formConfig = is_string($form->form_config)
                        ? json_decode($form->form_config, true)
                        : $form->form_config;
                    $questions = $formConfig['fields'] ?? [];
                }
               
                if ($responses->isEmpty()) {
                    return [
                        'event' => $event->title,
                        'feedback_count' => 0,
                        'average_rating' => 0,
                        'feedbacks' => []
                    ];
                }
                return [
                    'event' => $event->title,
                    'feedback_count' => $responses->count(),
                    'average_rating' => round($responses->avg('overall_rating'), 2),
                    'feedbacks' => $responses->map(function ($response) use ($questions) {
                        $answers = collect($questions)->map(function ($field) use ($response) {
                            $label = $field['label'] ?? 'Unnamed Question';
                            $labelKeyMap = [
                                'Overall Event Rating' => 'overall_rating',
                                'What did you like most?' => 'liked_most',
                                'What could be improved?' => 'improvements',
                                'Organization Rating' => 'organization_rating',
                            ];
                            $key = $labelKeyMap[$label] ?? \Str::snake(strtolower($label));
                            $answersArray = is_array($response->responses)
                                ? $response->responses
                                : json_decode($response->responses ?? '{}', true);
                            $answer = $answersArray[$key] ?? '[No Answer]';
                            return [
                                'question' => $label,
                                'answer' => $answer,
                            ];
                        })->toArray();
                        return [
                            'user' => $response->user
                                ? ($response->user->first_name . " " . $response->user->last_name)
                                : 'Unknown',
                            'email' => $response->user ? $response->user->email : 'N/A',
                            'phone' => $response->user->phone,
                            'rating' => $response->overall_rating,
                            'answers' => $answers,
                            'submitted_at' => $response->submitted_at->format('Y-m-d H:i:s'),
                        ];
                    })->values()
                ];
            });

            return $this->sendResponse($data, 'Feedback report retrieved successfully');

        } catch (Exception $e) {
            \Log::error('Error fetching feedback reports: ' . $e->getMessage());
            return $this->sendError('Failed to fetch feedback report', ['error' => $e->getMessage()], 500);
        }
    }

    public function exportReports(Request $request)
    {
        $type = $request->query('type', 'xlsx');
        $report = $request->query('report', 'events');

        try {
            switch ($report) {
                case 'attendance':
                    $export = new AttendanceReportExport();
                    $filename = 'attendance_report';
                    break;
                case 'feedback':
                    $export = new FeedbackReportExport();
                    $filename = 'feedback_report';
                    break;
                case 'events':
                default:
                    $export = new EventsReportExport();
                    $filename = 'events_report';
                    break;
            }

            if ($type === 'json') {
                return $this->sendResponse($export->collection(), 'Exported data in JSON format');
            }

            $extension = $type === 'csv' ? 'csv' : 'xlsx';
            return Excel::download($export, "{$filename}.{$extension}");

        } catch (Exception $e) {
            \Log::error('Error exporting reports: ' . $e->getMessage());
            return $this->sendError('Failed to export report', ['error' => $e->getMessage()], 500);
        }
    }
}
