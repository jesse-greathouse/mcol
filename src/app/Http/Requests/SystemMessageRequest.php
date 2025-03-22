<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SystemMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define the validation rules.
     */
    public function rules(): array
    {
        return [
            'queue' => 'required|string',
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'queue.required' => 'queue is a required parameter.',
            'queue.string'   => 'queue must be a string.',
        ];
    }
}
