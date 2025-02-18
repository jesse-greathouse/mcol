<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest,
    Illuminate\Validation\Rule,
    Illuminate\Validation\Validator;

use App\Models\Network;

/**
 * Class StoreServerRequest
 *
 * Handles validation logic for storing a server.
 * Ensures the 'host' is unique and 'network' exists in the database.
 */
class StoreServerRequest extends FormRequest
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
            'host' => [
                'required',
                'max:255',
                Rule::unique('servers')->ignore($this->route()->parameter('id'), 'id')
            ],
            'network' => 'required|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->networkExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['network'];
                    $validator->errors()->add(
                        'network',
                        "Network with id: $id was not found."
                    );
                }
            }
        ];
    }

    /**
     * Check if the network exists in the database.
     *
     * @param Validator $validator The validator instance.
     * @return bool True if network exists, false otherwise.
     */
    private function networkExists(Validator $validator): bool
    {
        $validated = $validator->validated();
        return Network::whereKey($validated['network'])->exists();
    }
}
