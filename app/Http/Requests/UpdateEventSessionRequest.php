<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'speaker_name' => ['nullable', 'string', 'max:255'],
            'speaker_bio' => ['nullable', 'string'],
            'speaker_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'start_time' => ['sometimes', 'date_format:H:i:s'],
            'end_time' => ['sometimes', 'date_format:H:i:s', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'session_order' => ['nullable', 'integer', 'min:1'],
            'is_break' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'Session title must not exceed 255 characters.',
            'speaker_name.max' => 'Speaker name must not exceed 255 characters.',
            'speaker_image.image' => 'Speaker image must be an image file.',
            'speaker_image.mimes' => 'Speaker image must be a file of type: jpeg, png, jpg.',
            'speaker_image.max' => 'Speaker image must not exceed 2MB.',
            'start_time.date_format' => 'Start time must be in the format HH:MM:SS.',
            'end_time.date_format' => 'End time must be in the format HH:MM:SS.',
            'end_time.after' => 'End time must be after the start time.',
            'location.max' => 'Location must not exceed 255 characters.',
            'session_order.integer' => 'Session order must be an integer.',
            'session_order.min' => 'Session order must be at least 1.',
            'is_break.boolean' => 'Is break must be true or false.',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('is_break')) {
            $this->merge([
                'is_break' => filter_var($this->input('is_break'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
