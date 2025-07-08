<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest
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
            'setting_value' => 'required',
            'setting_type' => 'nullable|in:string,boolean,integer,json',
        ];
    }
    public function messages():array{
        return[
            'setting_value.required' => 'The setting value field is required.',
            'setting_type.in' => 'The setting type must be one of: string, boolean, integer, or json.',
        ];
    }
}