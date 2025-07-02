<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;

class StoreInterviewSlotRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slot_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'required|integer|min:5|max:120',
            'max_interviews_per_slot' => 'required|integer|min:1|max:20',
            'is_break' => 'boolean',
            'break_reason' => 'nullable|string|max:255',
            'is_available' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'slot_date.required' => 'Slot date is required.',
            'start_time.required' => 'Start time is required.',
            'end_time.required' => 'End time is required.',
            'end_time.after' => 'End time must be after start time.',
            'duration_minutes.required' => 'Duration is required.',
            'duration_minutes.min' => 'Duration must be at least 5 minutes.',
            'duration_minutes.max' => 'Duration cannot exceed 120 minutes.',
            'max_interviews_per_slot.required' => 'Max interviews per slot is required.',
            'max_interviews_per_slot.min' => 'At least 1 interview per slot is required.',
            'max_interviews_per_slot.max' => 'No more than 20 interviews per slot allowed.',
        ];
    }
}
