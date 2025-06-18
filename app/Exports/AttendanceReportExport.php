<?php

namespace App\Exports;

use App\Models\Event\Event;
use Exception;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AttendanceReportExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        //
        $rows = collect();
        try {
            $events = Event::with('registrations.user')->get();
            foreach ($events as $event) {
                $validRegistrations = $event->registrations->filter(function ($r) {
                    return $r->user !== null;
                });
                if ($validRegistrations->isEmpty()) {
                    $rows->push([
                        'Event' => $event->title,
                        'Total Registrations' => 0,
                        'Attendee Name' => '',
                        'Attendee Email' => '',
                    ]);
                    continue;
                }
                foreach ($validRegistrations as $registration) {
                    $rows->push([
                        'Event' => $event->title,
                        'Total Registrations' => $validRegistrations->count(),
                        'Attendee Name' => $registration->user->first_name." ".$registration->user->last_name,
                        'Attendee Email' => $registration->user->email,
                        'Attendee phone'=>$registration->user->phone,
                        
                    ]);
                }

            }

        } catch (Exception $e) {
            \Log::error('Error generating attendance export: ' . $e->getMessage());
            return collect(); // return empty collection on error
        }
        return $rows;
    }
    public function headings(): array
    {
        return [
            'Event',
            'Total Registrations',
            'Attendee Name',
            'Attendee Email'
        ];
    }
}
