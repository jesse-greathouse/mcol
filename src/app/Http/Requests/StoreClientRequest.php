<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Validator;

use App\Models\Client,
    App\Models\Nick,
    App\Models\Network;

/**
 * Class StoreClientRequest
 *
 * This request validates data for creating or updating a client.
 */
class StoreClientRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to make this request.
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
            'enabled' => 'nullable|in:0,1',
            'network' => 'required|numeric',
            'nick' => 'required|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            /**
             * After validation, check if the Nick exists.
             */
            function (Validator $validator) {
                $this->validateNick($validator);
            },

            /**
             * After validation, check if the Network exists.
             */
            function (Validator $validator) {
                $this->validateNetwork($validator);
            },

            /**
             * After validation, check if the combination of Nick and Network already exists.
             */
            function (Validator $validator) {
                $this->validateCombination($validator);
            },
        ];
    }

    /**
     * Validate if the Nick exists.
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateNick(Validator $validator): void
    {
        if (!$this->nickExists($validator)) {
            $validated = $validator->validated();
            $id = $validated['nick'];
            $validator->errors()->add(
                'nick',
                "Nick with id: $id was not found."
            );
        }
    }

    /**
     * Validate if the Network exists.
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateNetwork(Validator $validator): void
    {
        if (!$this->networkExists($validator)) {
            $validated = $validator->validated();
            $id = $validated['network'];
            $validator->errors()->add(
                'network',
                "Network with id: $id was not found."
            );
        }
    }

    /**
     * Validate if the combination of Nick and Network already exists.
     *
     * @param Validator $validator
     * @return void
     */
    protected function validateCombination(Validator $validator): void
    {
        if ($this->combinationExists($validator)) {
            $validated = $validator->validated();
            $nickId = $validated['nick'];
            $networkId = $validated['network'];
            $validator->errors()->add(
                'nick',
                "Nick with id: $nickId already has a client with Network: $networkId."
            );
            $validator->errors()->add(
                'network',
                "Network with id: $networkId already has a client with Nick: $nickId."
            );
        }
    }

    /**
     * Check if the Network exists in the database.
     *
     * @param Validator $validator
     * @return bool
     */
    protected function networkExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        // Check if network exists
        return Network::find($validated['network']) !== null;
    }

    /**
     * Check if the Nick exists in the database.
     *
     * @param Validator $validator
     * @return bool
     */
    protected function nickExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        // Check if nick exists
        return Nick::find($validated['nick']) !== null;
    }

    /**
     * Check if a combination of Nick and Network already exists in the database.
     *
     * @param Validator $validator
     * @return bool
     */
    protected function combinationExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        // Validate network and nick existence
        $network = Network::find($validated['network']);
        if ($network === null) {
            return false;
        }

        $nick = Nick::find($validated['nick']);
        if ($nick === null) {
            return false;
        }

        // Check if the combination exists
        return Client::where('network_id', $network->id)
            ->where('nick_id', $nick->id)
            ->exists();
    }
}
