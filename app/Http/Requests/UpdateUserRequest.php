<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Http\Requests\BaseApiRequest;
use App\Models\Auth\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends BaseApiRequest
{
    /**
     * The user model for validation.
     */
    protected $userModel;

    /**
     * Set the user model for validation.
     */
    public function setUserModel(User $user)
    {
        $this->userModel = $user;
        return $this;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */    
    public function rules(): array
    {
        // Get the user ID from the user model if available
        $userId = $this->userModel ? $this->userModel->id : null;

        return [
            'email' => ['sometimes', 'email', 'unique:users,email,' . $userId],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string'],
            'track_id' => ['nullable', 'exists:tracks,id'],
            'intake_year' => ['nullable', 'integer'],
            'graduation_year' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'bio' => ['nullable', 'string'],
            'linkedin_url' => ['nullable', 'url'],
            'github_url' => ['nullable', 'url'],
            'portfolio_url' => ['nullable', 'url'],
            'profile_image' => ['nullable', 'file', 'image', 'max:2048'], // Allow image files up to 2MB
            'cv_path' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'], // Allow PDF/Word files up to 5MB
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
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'track_id.exists' => 'The selected track does not exist.',
            'linkedin_url.url' => 'Please enter a valid LinkedIn URL.',
            'github_url.url' => 'Please enter a valid GitHub URL.',
            'portfolio_url.url' => 'Please enter a valid portfolio URL.',
            'profile_image.image' => 'The profile image must be an image file.',
            'profile_image.max' => 'The profile image must not be larger than 2MB.',
            'cv_path.mimes' => 'The CV must be a PDF or Word document.',
            'cv_path.max' => 'The CV must not be larger than 5MB.',
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
            'is_active' => 'active status',
            'phone' => 'phone number',
            'email' => 'email address',
        ];
    }
}
