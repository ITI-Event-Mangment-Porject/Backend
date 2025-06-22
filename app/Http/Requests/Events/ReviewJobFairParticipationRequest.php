<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;

class ReviewJobFairParticipationRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'The status is required.',
            'status.in' => 'Status must be either approved or rejected.',
        ];
    }

    public function attributes(): array
    {
        return [
            'review_notes' => 'review notes',
        ];
    }
}
