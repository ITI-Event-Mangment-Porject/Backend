<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'website' => 'nullable|url',
            'industry' => 'nullable|string|max:255',
            'size' => 'nullable|in:startup,small,medium,large,enterprise',
            'location' => 'nullable|string|max:255',
            'contact_phone' => [
                'required',
                'regex:/^(\+20|0020|20)?(01[0-9]{9}|0[2-9][0-9]{7,8})$/'
            ],
            'linkedin_url' => 'nullable|url'
        ];

        // Handle email validation differently for create vs update
        if ($this->isMethod('post')) {
            $rules['contact_email'] = 'required|email|unique:companies,contact_email';
        } elseif ($this->isMethod('put') || $this->isMethod('patch')) {
            
            $companyId = $this->route('company') ?? $this->route('id');
            $rules['contact_email'] = [
                'nullable',
                'email',
                Rule::unique('companies', 'contact_email')->ignore($companyId)
            ];
        }

        return $rules;
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
            'contact_email.required' => 'Please Enter Your Contact Email',
            'linkedin_url.url' => 'Please Enter a Valid LinkedIn URL',
            'size.in' => 'Company size must be one of: startup, small, medium, large, enterprise'
        ];
    }
}