<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; 

class EventRegisterRequest extends BaseApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() ;
        // && auth()->user()->can('register-event');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            //
            'status' => ['sometimes', Rule::in(['registered', 'cancelled', 'attended', 'no_show'])],
            'registration_type' => ['sometimes', Rule::in(['auto', 'manual'])],
            'check_in_method' => ['nullable', Rule::in(['qr', 'manual'])],
        ];
    }
    
    
     public function messages(): array
    {
        return [
            'status.in' => 'Status must be either registered or waitlisted.',
            'registration_type.in' => 'Registration type must be either manual or automatic.',
        ];
    }
}
