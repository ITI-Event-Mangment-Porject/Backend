<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;

class StoreInterviewRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'job_profile_id' => 'required|exists:job_profiles,id',
            'message' => 'nullable|string|max:1000',
        ];
    }
}
