<?php

namespace App\Http\Controllers;

use App\Exports\AttendanceReportExport;
use App\Exports\EventsReportExport;
use App\Exports\FeedbackReportExport;
use App\Models\Event\Event;
use Exception;
use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;



class ReportController extends Controller
{
    public function eventsReports()
    {
        try {
            // count how many one register , put feedback , make interview request , participate in job fair
            $events = Event::withCount([
                'registrations',
                'feedbackResponses',
                'interviewRequests',
                'jobFairParticipations',
            ])->get();
            //dd($events);
            return response()->json(([
                'message' => 'get report about events',
                'data' => $events
            ]));
        } catch (Exception $e) {
            \Log::error('Error fetching event reports: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error fetching event reports.'], 500);
        }
    }
    public function attendanceReports()
    {
        try {
            //load registrations and users for all events
            $events = Event::with(['registrations.user'])->get();

            if ($events->isEmpty()) {
                return response()->json(['message' => 'There are no events found.'], 404);
            }

            $data = $events->map(function ($event) {
                $validRegistrations = $event->registrations->filter(function ($r) {
                    return $r->user !== null;
                });

                return [
                    'event' => $event->title,
                    'total_registration' => $validRegistrations->count(),
                    'attendees' => $validRegistrations->map(function ($r) {
                        return [
                            'name' => $r->user->first_name,
                            'email' => $r->user->email,
                        ];
                    })->values(),
                ];
            });

            return response()->json([
                'message' => 'Info about all attendance',
                'data' => $data,
            ], 200);

        } catch (Exception $e) {
            \Log::error('Error fetching attendance reports: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage(), 'message' => 'Error fetching attendance.'], 500);
        }
    }


    public function feedbackReports()
    {
        try {
            $events = Event::with('feedbackResponses.user')->get();
            if ($events->isEmpty()) {
                return response()->json(['message' => 'There is no feedbacks in any event'], 404);
            }
            $data = $events->map(function ($event) {
                $responses = $event->feedbackResponses;
                $form = $event->feedbackForms()->first(); //each event have one form
                $questions = $form ? ($form->form_config['questions'] ?? []) : []; //get feedback form question


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
                        $answers = [];
                        foreach ($questions as $index => $question) {
                            $key = 'q' . ($index + 1);
                            $answers[] = [
                                'question' => $question,
                                'answer' => $response->responses[$key] ?? null,
                            ];
                        }
                        return [
                            'user' => $response->user?($response->user->first_name." ".$response->user->last_name): 'Unknown',
                            'email' => $response->user ? $response->user->email : 'N/A',
                            'rating' => $response->overall_rating,
                            'answers' => $answers,
                            'submitted_at' => $response->submitted_at->format('Y-m-d H:i:s'),
                        ];
                    })->values()
                ];
            });
            return response()->json(['message' => 'reports about feedbacks', 'data' => $data], 200);
        } catch (Exception $e) {
            \Log::error('Error fetching feedback reports: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage(), 'message' => 'Error fetching feedback.'], 500);
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
                return response()->json(['message' => 'export data in json format', 'data' => $export->collection()], 200);

            }
            $extension = $type === 'csv' ? 'csv' : 'xlsx';
            return Excel::download($export, "{$filename}.{$extension}");

        }catch (Exception $e) {
            \Log::error('Error exporting reports: ' . $e->getMessage());
            return response()->json(['message' => 'Error exporting report', 'error' => $e->getMessage()], 500);
        }
    }
}

