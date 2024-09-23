<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule,
    Illuminate\Validation\Validator;

use App\Models\Network;

class StoreBotRequest extends FormRequest
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
            'nick' => [ 'required', 'max:255', Rule::unique('bots')->ignore($this->route()->parameter('id'), 'id')],
            'network' => 'required|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
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

    public function networkExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        $network = Network::find($validated['network']);

        return (null !== $network);
    }
}
