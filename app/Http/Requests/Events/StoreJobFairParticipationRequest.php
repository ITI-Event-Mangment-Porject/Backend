<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;

class StoreJobFairParticipationRequest extends BaseApiRequest
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
        return [
            'company.name' => 'required|string|max:255',
            'company.logo_path' => 'nullable|string|max:500',
            'company.description' => 'nullable|string',
            'company.website' => 'nullable|url|max:255',
            'company.industry' => 'nullable|string|max:255',
            'company.size' => 'nullable|in:startup,small,medium,large,enterprise',            'company.location' => 'nullable|string|max:255',
            'company.contact_email' => 'required|email|max:255',
            'company.contact_phone' => 'nullable|string|max:30',
            'company.linkedin_url' => 'nullable|url|max:255',
            'special_requirements' => 'nullable|string|max:1000',
            'need_branding' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'company.name.required' => 'The company name is required.',
            'company.contact_email.required' => 'The company contact email is required.',
            'company.contact_email.email' => 'The company contact email must be a valid email address.',
            'company.website.url' => 'The company website must be a valid URL.',
            'company.linkedin_url.url' => 'The company LinkedIn URL must be a valid URL.',
        ];
    }

    public function attributes(): array
    {
        return [
            'company.name' => 'company name',
            'company.logo_path' => 'company logo',
            'company.contact_email' => 'company contact email',
            'company.linkedin_url' => 'company LinkedIn URL',
        ];
    }
}
