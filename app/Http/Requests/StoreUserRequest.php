<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends BaseApiRequest
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
            'portal_user_id' => ['required','max:255','unique:users,portal_user_id'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string'],
            'track_id' => ['nullable', 'exists:tracks,id'],
            'intake_year' => ['nullable', 'integer'],
            'graduation_year' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'bio' => ['nullable', 'string'],
            'linkedin_url' => ['nullable', 'url'],
            'github_url' => ['nullable', 'url'],
            'portfolio_url' => ['nullable', 'url'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'cv_path' => ['nullable','file', 'mimes:pdf,doc,docx', 'max:5120'],
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
            'portal_user_id.max' => 'The portal user ID may not be greater than 255 characters.',
            'portal_user_id.required' => 'The portal user ID is required.',
            'portal_user_id.unique' => 'This portal user ID is already registered.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'first_name.required' => 'The first name is required.',
            'last_name.required' => 'The last name is required.',
            'track_id.exists' => 'The selected track does not exist.',
            'linkedin_url.url' => 'Please enter a valid LinkedIn URL.',
            'github_url.url' => 'Please enter a valid GitHub URL.',
            'portfolio_url.url' => 'Please enter a valid portfolio URL.',
            'profile_image.image' => 'The profile image must be an image file.',
            'profile_image.max' => 'The profile image must not be larger than 2MB.',
            'profile_image.mimes' => 'The profile image must be a JPEG, PNG, JPG, or WebP file.',
            'cv_path.file' => 'The CV must be a file.',
            'cv_path.max' => 'The CV must not be larger than 5MB.',
            'cv_path.mimes' => 'The CV must be a PDF or Word document.',
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
            'portal_user_id' => 'portal user ID',
            'email' => 'email address',
            'first_name' => 'first name',
            'last_name' => 'last name',
            'track_id' => 'track',
            'intake_year' => 'intake year',
            'graduation_year' => 'graduation year',
            'linkedin_url' => 'LinkedIn URL',
            'github_url' => 'GitHub URL',
            'portfolio_url' => 'portfolio URL',
            'profile_image' => 'profile image',
            'cv_path' => 'CV',
            'bio' => 'bio',
            'phone' => 'phone number',
            'is_active' => 'active status',
        ];
    }
}
