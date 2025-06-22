<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;

class StoreJobProfileRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'requirements' => 'nullable|string',
            'employment_type' => 'required|in:Full-time,Part-time,Internship,Contract',
            'location' => 'nullable|string|max:255',
            'positions_available' => 'required|integer|min:1',
            'tracks' => 'nullable|array',
            'tracks.*.track_id' => 'required|integer|exists:tracks,id',
            'tracks.*.preference_level' => 'required|in:required,preferred,acceptable'
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'The job title is required.',
            'employment_type.required' => 'The employment type is required.',
            'employment_type.in' => 'Employment type must be one of: Full-time, Part-time, Internship, Contract.',
            'positions_available.required' => 'Positions available is required.',
            'positions_available.integer' => 'Positions available must be an integer.',
            'positions_available.min' => 'Positions available must be at least 1.',
            'tracks.*.track_id.required' => 'Each track must have a track ID.',
            'tracks.*.track_id.exists' => 'Selected track does not exist.',
            'tracks.*.preference_level.required' => 'Each track must have a preference level.',
            'tracks.*.preference_level.in' => 'Preference level must be one of: required, preferred, acceptable.',
        ];
    }

    public function attributes(): array
    {
        return [
            'positions_available' => 'positions available',
            'employment_type' => 'employment type',
            'tracks.*.track_id' => 'track',
            'tracks.*.preference_level' => 'track preference level',
        ];
    }
}
