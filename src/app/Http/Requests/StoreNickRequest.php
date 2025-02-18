<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest,
    Illuminate\Validation\Rule,
    Illuminate\Validation\Validator;

use App\Models\Network;

/**
 * Request for storing a user's nickname, validating network existence and other data.
 */
class StoreNickRequest extends FormRequest
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
            'nick' => ['required', 'max:255', Rule::unique('nicks')->ignore($this->route()->parameter('id'), 'id')],
            'network' => 'required|numeric',
            'email' => 'nullable|email',
            'password' => 'nullable',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable> The validation callables.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
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
     * @param \Illuminate\Validation\Validator $validator The current validator instance.
     *
     * @return bool True if the network exists, otherwise false.
     */
    public function networkExists(Validator $validator): bool
    {
        // Directly accessing validated data for more efficiency
        $validated = $validator->validated();

        // Find the network directly via the validated network ID
        return Network::find($validated['network']) !== null;
    }
}
