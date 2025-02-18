<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest,
    Illuminate\Validation\Rule,
    Illuminate\Validation\Validator;

use App\Models\Network;

/**
 * StoreBotRequest handles the validation of the data for storing a bot.
 */
class StoreBotRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
            'nick' => ['required', 'max:255', Rule::unique('bots')->ignore($this->route()->parameter('id'), 'id')],
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
     * Check if the network exists.
     *
     * @param Validator $validator
     * @return bool
     */
    public function networkExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        // Directly accessing the network record in a more efficient manner.
        return Network::find($validated['network']) !== null;
    }
}
