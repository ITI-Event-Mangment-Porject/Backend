<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $eventId = $this->route('event')?->id ?? $this->route('event');

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('events', 'slug')->ignore($eventId)
            ],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', 'required', Rule::in(['Job Fair', 'Tech', 'Fun'])],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'ongoing', 'completed', 'archived'])],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i'],
            'banner_image' => ['nullable', 'string', 'max:500', 'url'],
            'registration_deadline' => [
                'nullable',
                'date',
                'before_or_equal:start_date'
            ],
            'visibility_type' => ['sometimes', Rule::in(['all', 'role_based', 'track_based'])],
            'visibility_config' => ['nullable', 'json'],
            'slido_qr_code' => ['nullable', 'string', 'max:500', 'url'],
            'slido_embed_url' => ['nullable', 'string', 'max:500', 'url'],
            'archived_at' => ['nullable', 'date'],
        ];
    }
    
    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The event title is required.',
            'slug.required' => 'The event slug is required.',
            'slug.unique' => 'This slug is already taken. Please choose a different one.',
            'slug.regex' => 'The slug must contain only lowercase letters, numbers, and hyphens.',
            'type.required' => 'Please select an event type.',
            'type.in' => 'The selected event type is invalid.',
            'start_date.required' => 'The start date is required.',
            'end_date.required' => 'The end date is required.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'start_time.required' => 'The start time is required.',
            'end_time.required' => 'The end time is required.',
            'registration_deadline.before_or_equal' => 'Registration deadline must be before or on the event start date.',
            'banner_image.url' => 'The banner image must be a valid URL.',
            'slido_qr_code.url' => 'The Slido QR code must be a valid URL.',
            'slido_embed_url.url' => 'The Slido embed URL must be a valid URL.',
        ];
    }
    
    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation for same-day events
            if ($this->filled(['start_date', 'end_date', 'start_time', 'end_time'])) {
                if ($this->start_date === $this->end_date && $this->start_time >= $this->end_time) {
                    $validator->errors()->add('end_time', 'For same-day events, end time must be after start time.');
                }
            }

            // Validate visibility config based on visibility type
            if ($this->filled('visibility_type') && $this->visibility_type !== 'all' && empty($this->visibility_config)) {
                $validator->errors()->add('visibility_config', 'Visibility configuration is required when visibility type is not "all".');
            }
        });
    }
}
