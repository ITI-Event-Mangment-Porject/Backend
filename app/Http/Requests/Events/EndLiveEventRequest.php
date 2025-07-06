<?php

namespace App\Http\Requests\Events;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;

class EndLiveEventRequest extends BaseApiRequest
{    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Allow for now - you can add your own authorization logic here
        return true;
    }    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Optional: Update the actual end time when ending the event
            'update_end_time' => ['nullable', 'boolean'],
            // Add any additional parameters you might need for ending a live event
            'end_reason' => ['nullable', 'string', 'max:500'],
            'archive_immediately' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'end_reason.max' => 'The end reason cannot exceed 500 characters.',
        ];
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new \Illuminate\Auth\Access\AuthorizationException('You are not authorized to end live events. Only administrators can perform this action.');
    }
}
