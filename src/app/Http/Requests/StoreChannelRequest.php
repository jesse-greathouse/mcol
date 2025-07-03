<?php

namespace App\Http\Requests;

use App\Models\Channel;
use App\Models\Network;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Request class for storing a channel.
 * Contains authorization and validation logic for channel creation.
 */
class StoreChannelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Always returns true for now.
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
     *
     * @return array<callable> The after validation logic.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $this->validateNetworkExistence($validator);
            },
            function (Validator $validator) {
                $this->validateParentExistence($validator);
            },
        ];
    }

    /**
     * Validate that the network exists in the database.
     *
     * @param  Validator  $validator  The validator instance.
     */
    private function validateNetworkExistence(Validator $validator): void
    {
        $validated = $validator->validated();
        $networkId = $validated['network'];

        if (! Network::find($networkId)) {
            $validator->errors()->add('network', "Network with ID: $networkId was not found.");
        }
    }

    /**
     * Validate that the parent channel exists in the database.
     *
     * @param  Validator  $validator  The validator instance.
     */
    private function validateParentExistence(Validator $validator): void
    {
        $validated = $validator->validated();
        $parentId = $validated['parent'];

        // Skip validation if parent is not set
        if (isset($parentId) && $parentId !== null && ! Channel::find($parentId)) {
            $validator->errors()->add('parent', "Parent channel with ID: $parentId was not found.");
        }
    }
}
