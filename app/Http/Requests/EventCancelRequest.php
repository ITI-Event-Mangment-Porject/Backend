<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class EventCancelRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // return auth()->check() && auth()->user()->can('cancel-event', $this->route('event'));
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'cancellation_reason' => ['required', 'string', 'max:255'],
        ];
    }
    public function messages(): array
    {
        return [
            'event_id.required' => 'Event ID is required.',
            'event_id.exists' => 'The selected event does not exist.',
            'user_id.required' => 'User ID is required.',
            'user_id.exists' => 'The selected user does not exist.',
            'cancellation_reason.required' => 'Cancellation reason is required.',
            'cancelled_at.required' => 'Cancellation date is required.',
        ];
    }
}
