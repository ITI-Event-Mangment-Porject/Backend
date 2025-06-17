<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;

class UpdateInterviewSlotRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slot_date' => 'sometimes|date',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'duration_minutes' => 'sometimes|integer|min:5|max:120',
            'max_interviews_per_slot' => 'sometimes|integer|min:1|max:20',
            'is_break' => 'sometimes|boolean',
            'break_reason' => 'nullable|string|max:255',
            'is_available' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'End time must be after start time.',
            'duration_minutes.min' => 'Duration must be at least 5 minutes.',
            'duration_minutes.max' => 'Duration cannot exceed 120 minutes.',
            'max_interviews_per_slot.min' => 'At least 1 interview per slot is required.',
            'max_interviews_per_slot.max' => 'No more than 20 interviews per slot allowed.',
        ];
    }
}
