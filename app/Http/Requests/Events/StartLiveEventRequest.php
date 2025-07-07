<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StartLiveEventRequest extends BaseApiRequest
{    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Allow for now - you can add your own authorization logic here
        return true;
    }    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Optional: Update the actual start time when starting the event
            'update_start_time' => ['nullable', 'boolean'],
            // Add any additional parameters you might need for starting a live event
            'custom_start_message' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'custom_start_message.max' => 'The custom start message cannot exceed 500 characters.',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException('You are not authorized to start live events. Only administrators can perform this action.');
    }
}
