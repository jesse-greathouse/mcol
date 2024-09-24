<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

use App\Models\Channel,
    App\Models\Network;

class StoreChannelRequest extends FormRequest
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
            'name' => 'required|max:255',
            'topic' => 'required|min:3',
            'network' => 'required|numeric',
            'users' => 'nullable|numeric',
            'parent' => 'nullable|numeric',
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
            },
            function (Validator $validator) {
                if (!$this->parentExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['parent'];
                    $validator->errors()->add(
                        'parent',
                        "Parent channel with id: $id was not found."
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

    public function parentExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        // If Parent does not exist then simply return true
        if (!isset($validated['parent']) || null === $validated['parent']) {
            return true;
        }

        $channel = Channel::find($validated['parent']);

        return (null !== $channel);
    }
}
