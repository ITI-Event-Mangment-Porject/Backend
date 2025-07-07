<?php

namespace App\Exports;

use App\Models\Event\Event;
use Exception;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class FeedbackReportExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        //
        try {
            $rows = collect();
            $events = Event::with('feedbackResponses.user', 'feedbackForms')->get();
            foreach ($events as $event) {
                $responses = $event->feedbackResponses;
                $form = $event->feedbackForms()->first();
                $questions = $form ? ($form->form_config['questions'] ?? []) : [];

                if ($responses->isEmpty()) {
                    $rows->push([
                        'Event' => $event->title,
                        'User' => '',
                        'Email' => '',
                        'Rating' => '',
                        'Question' => '',
                        'Answer' => '',
                        'Submitted At' => '',
                    ]);
                    continue;
                }

                foreach ($responses as $response) {
                    foreach ($questions as $index => $question) {
                        $key = 'q' . ($index + 1);

                        $rows->push([
                            'Event' => $event->title,
                            'User' => $response->user ? $response->user->first_name : 'Unknown',
                            'Email' => $response->user ? $response->user->email : 'N/A',
                            'Rating' => $response->overall_rating,
                            'Question' => $question,
                            'Answer' => $response->responses[$key] ?? null,
                            'Submitted At' => optional($response->submitted_at)->format('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            return $rows;
        } catch (Exception $e) {
            \Log::info('Generating feedback export', ['rows_count' => $rows->count()]);

            \Log::error('Error generating feedback export: ' . $e->getMessage());
            return collect(); //return empty collection 
        }

    }
    //headings in excel file
    public function headings(): array
    {
        return [
            'Event',
            'User',
            'Email',
            'Rating',
            'Question',
            'Answer',
            'Submitted At'
        ];
    }
}

