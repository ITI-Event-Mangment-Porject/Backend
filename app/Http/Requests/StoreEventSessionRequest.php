<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() ;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'speaker_name' => ['nullable', 'string', 'max:255'],
            'speaker_bio' => ['nullable', 'string'],
            'speaker_image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'session_order' => ['nullable', 'integer', 'min:1'],
            'is_break' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Session title is required.',
            'title.max' => 'Session title must not exceed 255 characters.',
            'speaker_name.max' => 'Speaker name must not exceed 255 characters.',
            'speaker_image.max' => 'Speaker image URL must not exceed 500 characters.',
            'start_time.required' => 'Start time is required.',
            'start_time.date_format' => 'Start time must be in the format HH:MM:SS.',
            'end_time.required' => 'End time is required.',
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
