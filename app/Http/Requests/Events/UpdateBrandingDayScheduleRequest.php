<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;

class UpdateBrandingDayScheduleRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branding_day_date' => 'sometimes|date',
            'start_time' => 'required_with:end_time|date_format:H:i',
            'end_time' => 'required_with:start_time|date_format:H:i|after:start_time',
            'order' => 'nullable|integer',
        ];
    }

    public function messages(): array
    {
        return [
            'branding_day_date.date' => 'Branding day date must be a valid date.',
            'start_time.date_format' => 'Start time must be in H:i format.',
            'end_time.date_format' => 'End time must be in H:i format.',
            'end_time.after' => 'End time must be after start time.',
            'order.integer' => 'Order must be an integer.',
        ];
    }
}
