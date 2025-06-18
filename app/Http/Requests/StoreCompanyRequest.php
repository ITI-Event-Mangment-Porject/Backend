<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'website' => 'nullable|url',
            'industry' => 'nullable|string|max:255',
            'size' => 'nullable|in:startup,small,medium,large,enterprise',
            'location' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|unique:companies,contact_email',
            'contact_phone' => [
                'required',
                'regex:/^(\+20|0020|20)?(01[0-9]{9}|0[2-9][0-9]{7,8})$/'
            ],
            'linkedin_url' => 'nullable|url'
        ];
    }
    public function messages(): array
    {
        return [
            'name.required' => 'Please Enter Name of Your Company',
            'description.required' => 'Please Enter Company Description',
            'contact_phone.required' => 'Please Enter Your Contact Phone Number',
            'contact_phone.regex' => 'Please Enter a Valid Egyptian Phone Number',
            'website.url' => 'Please Enter a Valid Website URL',
            'contact_email.email' => 'Please Enter a Valid Email Address',
            'contact_email.unique' => 'This Email Already Exist',
            'linkedin_url.url' => 'Please Enter a Valid LinkedIn URL',
            'size.in' => 'Company size must be one of: startup, small, medium, large, enterprise'
        ];
    }
}
