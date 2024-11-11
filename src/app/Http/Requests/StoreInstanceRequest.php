<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

use App\Models\Client,
    App\Models\Instance;

class StoreInstanceRequest extends FormRequest
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
            'log_uri'           => 'required',
            'pid'               => 'nullable|numeric',
            'enabled'           => 'nullable|in:0,1',
            'desired_status'    => 'nullable|max:255',
            'client'            => 'required|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->clientExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['client'];
                    $validator->errors()->add(
                        'client',
                        "Client with id: $id was not found."
                    );
                }
            },
            function (Validator $validator) {
                if (!$this->isValidDesiredStatus($validator)) {
                    $validated = $validator->validated();
                    $status = $validated['desired_status'];
                    $up = Instance::STATUS_UP;
                    $down = Instance::STATUS_DOWN;
                    $validStatuses = "$up and $down";
                    $validator->errors()->add(
                        'desired_status',
                        "Desired status: $status is not valid. (Valid statuses are: $validStatuses)"
                    );
                }
            }
        ];
    }

    public function clientExists(Validator $validator): bool
    {
        $validated = $validator->validated();
        $client = Client::find($validated['client']);
        return (null !== $client);
    }

    public function isValidDesiredStatus(Validator $validator): bool
    {
        $validated = $validator->validated();

        if (!isset($validated['desired_status']) || null === $validated['desired_status']) return true;

        return in_array(strtoupper($validated['desired_status']), [
            Instance::STATUS_UP,
            Instance::STATUS_DOWN,
        ]);
    }
}
