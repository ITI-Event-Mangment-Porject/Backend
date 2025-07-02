<?php

namespace App\Http\Requests;

use App\Models\Auth\Track;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateTrackRequest extends BaseApiRequest
{
    /**
     * The track model for validation.
     */
    protected $trackModel;

    /**
     * Set the track model for validation.
     */
    public function setTrackModel(Track $track)
    {
        $this->trackModel = $track;
        return $this;
    }

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
        // Get the track ID from the track model if available
        $trackId = $this->trackModel ? $this->trackModel->id : null;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes', 
                'string', 
                'max:255', 
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 
                Rule::unique('tracks')->ignore($trackId)
            ],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.string' => 'The track name must be a string.',
            'slug.unique' => 'This slug is already taken. Please choose a different one.',
            'slug.regex' => 'The slug must contain only lowercase letters, numbers, and hyphens.',
            'color.max' => 'The color code cannot exceed 50 characters.',
            'icon.max' => 'The icon name/path cannot exceed 100 characters.',
            'sort_order.integer' => 'The sort order must be a valid integer.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'track name',
            'slug' => 'track slug',
            'is_active' => 'active status',
            'sort_order' => 'sort order',
        ];
    }
}
