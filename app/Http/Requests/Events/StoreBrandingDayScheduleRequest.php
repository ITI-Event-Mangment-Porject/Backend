<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;

class StoreBrandingDayScheduleRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule' => 'required|array',
            'schedule.*.company_id' => 'required|exists:companies,id',
            'schedule.*.participation_id' => 'required|exists:job_fair_participations,id',
            'schedule.*.branding_day_date' => 'required|date',
            'schedule.*.start_time' => 'required|date_format:H:i',
            'schedule.*.end_time' => 'required|date_format:H:i|after:schedule.*.start_time',
            'schedule.*.order' => 'nullable|integer',
            'schedule.*.speaker_id' => 'nullable|exists:branding_day_speakers,id', // Add speaker_id to schedule
        ];
    }

    public function messages(): array
    {
        return [
            'schedule.required' => 'The schedule array is required.',
            'schedule.*.company_id.required' => 'Company ID is required for each slot.',
            'schedule.*.company_id.exists' => 'The selected company does not exist.',
            'schedule.*.participation_id.required' => 'Participation ID is required for each slot.',
            'schedule.*.participation_id.exists' => 'The selected participation does not exist.',
            'schedule.*.branding_day_date.required' => 'Branding day date is required.',
            'schedule.*.branding_day_date.date' => 'Branding day date must be a valid date.',
            'schedule.*.start_time.required' => 'Start time is required.',
            'schedule.*.start_time.date_format' => 'Start time must be in H:i format.',
            'schedule.*.end_time.required' => 'End time is required.',
            'schedule.*.end_time.date_format' => 'End time must be in H:i format.',
            'schedule.*.end_time.after' => 'End time must be after start time.',
            'schedule.*.order.integer' => 'Order must be an integer.',
            'schedule.*.speaker_id.exists' => 'The selected speaker does not exist.',
        ];
    }
}
