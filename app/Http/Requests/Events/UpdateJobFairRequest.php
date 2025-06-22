<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateJobFairRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('events', 'slug')->ignore($this->route('job_fair') ?? $this->route('id'))
            ],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', Rule::in(['Job Fair'])],
            'status' => ['sometimes', Rule::in(['draft', 'published', 'ongoing', 'completed', 'archived'])],
            'location' => ['nullable', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i', 'after:start_time'],
            'banner_image' => ['nullable', 'string', 'max:500', 'url'],
            'registration_deadline' => [
                'nullable',
                'date',
                'before_or_equal:start_date',
                'after_or_equal:now'
            ],
            'visibility_type' => ['sometimes', Rule::in(['all', 'role_based', 'track_based'])],
            'visibility_config' => ['nullable', 'array'],
            'slido_qr_code' => ['nullable', 'string', 'max:500', 'url'],
            'slido_embed_url' => ['nullable', 'string', 'max:500', 'url'],
            'created_by' => ['sometimes', 'exists:users,id'],
            'archived_at' => ['nullable', 'date'],
        ];
    }

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
            'start_date.after_or_equal' => 'The start date must be today or a future date.',
            'end_date.required' => 'The end date is required.',
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
            'start_time.required' => 'The start time is required.',
            'end_time.required' => 'The end time is required.',
            'end_time.after' => 'The end time must be after the start time.',
            'registration_deadline.before_or_equal' => 'Registration deadline must be before or on the event start date.',
            'registration_deadline.after_or_equal' => 'Registration deadline must be in the future.',
            'banner_image.url' => 'The banner image must be a valid URL.',
            'slido_qr_code.url' => 'The Slido QR code must be a valid URL.',
            'slido_embed_url.url' => 'The Slido embed URL must be a valid URL.',
            'created_by.required' => 'The creator is required.',
            'created_by.exists' => 'The selected creator does not exist.',
        ];
    }

    public function attributes(): array
    {
        return [
            'start_date' => 'start date',
            'end_date' => 'end date',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'banner_image' => 'banner image',
            'registration_deadline' => 'registration deadline',
            'visibility_type' => 'visibility type',
            'visibility_config' => 'visibility configuration',
            'slido_qr_code' => 'Slido QR code',
            'slido_embed_url' => 'Slido embed URL',
            'created_by' => 'creator',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Custom validation for same-day events
            if ($this->start_date === $this->end_date && $this->start_time >= $this->end_time) {
                $validator->errors()->add('end_time', 'For same-day events, end time must be after start time.');
            }

            // Validate visibility config based on visibility type
            if ($this->visibility_type !== 'all' && empty($this->visibility_config)) {
                $validator->errors()->add('visibility_config', 'Visibility configuration is required when visibility type is not "all".');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        // Auto-generate slug from title if not provided
        if (!$this->has('slug') && $this->has('title')) {
            $this->merge([
                'slug' => \Str::slug($this->title)
            ]);
        }
    }
}
