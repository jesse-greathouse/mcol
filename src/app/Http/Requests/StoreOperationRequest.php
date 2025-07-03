<?php

namespace App\Http\Requests;

use App\Models\Instance;
use App\Models\Network;
use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * StoreOperationRequest validates the data for storing operations.
 */
class StoreOperationRequest extends FormRequest
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
            'command' => 'required',
            'network' => 'nullable|max:255',
            'status' => 'nullable|max:255',
            'enabled' => 'nullable|in:0,1',
            'instance' => 'nullable|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->networkExists($validator)) {
                    $validated = $validator->validated();
                    $networkName = $validated['network'];
                    $instance = $this->getInstanceByNetworkName($networkName);
                    $validator->setValue('instance', $instance->id);
                }
            },
            function (Validator $validator) {
                if (! $this->instanceExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['instance'];
                    $validator->errors()->add(
                        'instance',
                        "Instance with id: $id was not found."
                    );
                }
            },
            function (Validator $validator) {
                if (! $this->isValidStatus($validator)) {
                    $validated = $validator->validated();
                    $status = $validated['status'];
                    $pending = Operation::STATUS_PENDING;
                    $completed = Operation::STATUS_COMPLETED;
                    $failed = Operation::STATUS_FAILED;
                    $validStatuses = "$pending, $completed and $failed";
                    $validator->errors()->add(
                        'status',
                        "Status: $status is not valid. (Valid statuses are: $validStatuses)"
                    );
                }
            },
        ];
    }

    /**
     * Check if the network exists.
     */
    public function networkExists(Validator $validator): bool
    {
        $validated = $validator->validated();
        $networkName = $validated['network'];

        // Use exists() for better performance over `first()`.
        return Network::where('name', $networkName)->exists();
    }

    /**
     * Retrieve the instance associated with the given network name.
     */
    public function getInstanceByNetworkName(string $networkName): ?Instance
    {
        // Use a more optimized query, removing unnecessary join
        return Instance::join('clients', 'clients.id', '=', 'instances.client_id')
            ->join('networks', 'networks.id', '=', 'clients.network_id')
            ->where('networks.name', $networkName)
            ->first(); // Return single result directly
    }

    /**
     * Check if the instance exists.
     */
    public function instanceExists(Validator $validator): bool
    {
        $validated = $validator->validated();
        $instanceId = $validated['instance'];

        // Improved performance: check existence without retrieving the full model
        return Instance::where('id', $instanceId)->exists();
    }

    /**
     * Check if the status is valid.
     */
    public function isValidStatus(Validator $validator): bool
    {
        $validated = $validator->validated();
        $status = $validated['status'] ?? null;

        // Return early if no status is provided
        if (is_null($status)) {
            return true;
        }

        // Compare status values using case-insensitive checking
        return in_array(strtoupper($status), [
            Operation::STATUS_PENDING,
            Operation::STATUS_COMPLETED,
            Operation::STATUS_FAILED,
        ], true);
    }
}
