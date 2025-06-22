<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;

class UpdateJobProfileRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'employment_type' => 'sometimes|in:Full-time,Part-time,Internship,Contract',
            'location' => 'nullable|string|max:255',
            'positions_available' => 'sometimes|integer|min:1',
            'track_preferences' => 'nullable|array',
            'track_preferences.*.track_id' => 'required|integer|exists:tracks,id',
            'track_preferences.*.preference_level' => 'required|in:required,preferred,acceptable'
        ];
    }

    public function messages(): array
    {
        return [
            'employment_type.in' => 'Employment type must be one of: Full-time, Part-time, Internship, Contract.',
            'positions_available.integer' => 'Positions available must be an integer.',
            'positions_available.min' => 'Positions available must be at least 1.',
            'track_preferences.*.track_id.required' => 'Each track must have a track ID.',
            'track_preferences.*.track_id.exists' => 'Selected track does not exist.',
            'track_preferences.*.preference_level.required' => 'Each track must have a preference level.',
            'track_preferences.*.preference_level.in' => 'Preference level must be one of: required, preferred, acceptable.',
        ];
    }

    public function attributes(): array
    {
        return [
            'positions_available' => 'positions available',
            'employment_type' => 'employment type',
            'track_preferences.*.track_id' => 'track',
            'track_preferences.*.preference_level' => 'track preference level',
        ];
    }
}
