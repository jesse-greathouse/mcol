<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest,
    Illuminate\Validation\Validator;

use App\Models\Client,
    App\Models\Instance;

/**
 * Class StoreInstanceRequest
 * Handles the validation logic for storing an instance.
 */
class StoreInstanceRequest extends FormRequest
{
    /**
     * @var string The client ID key in the request data.
     */
    private string $clientKey = 'client';

    /**
     * @var string The desired status key in the request data.
     */
    private string $desiredStatusKey = 'desired_status';

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
            'log_uri'        => 'required',
            'pid'            => 'nullable|numeric',
            'enabled'        => 'nullable|in:0,1',
            'desired_status' => 'nullable|max:255',
            'client'         => 'required|numeric',
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
             * Validate if the client exists.
             * Adds an error if the client does not exist.
             */
            function (Validator $validator) {
                $validated = $validator->validated();
                $clientId = $validated[$this->clientKey] ?? null;

                if ($clientId && !$this->clientExists($clientId)) {
                    $validator->errors()->add(
                        $this->clientKey,
                        "Client with id: $clientId was not found."
                    );
                }
            },

            /**
             * Validate if the desired status is valid.
             * Adds an error if the desired status is invalid.
             */
            function (Validator $validator) {
                $validated = $validator->validated();
                $desiredStatus = $validated[$this->desiredStatusKey] ?? null;

                if ($desiredStatus && !$this->isValidDesiredStatus($desiredStatus)) {
                    $validStatuses = implode(' and ', [Instance::STATUS_UP, Instance::STATUS_DOWN]);
                    $validator->errors()->add(
                        $this->desiredStatusKey,
                        "Desired status: $desiredStatus is not valid. (Valid statuses are: $validStatuses)"
                    );
                }
            }
        ];
    }

    /**
     * Checks if the client exists in the database.
     *
     * @param int $clientId The client ID to check.
     * @return bool
     */
    private function clientExists(int $clientId): bool
    {
        return Client::find($clientId) !== null;
    }

    /**
     * Checks if the desired status is valid.
     *
     * @param string $desiredStatus The desired status to validate.
     * @return bool
     */
    private function isValidDesiredStatus(string $desiredStatus): bool
    {
        return in_array(strtoupper($desiredStatus), [
            Instance::STATUS_UP,
            Instance::STATUS_DOWN,
        ]);
    }
}
