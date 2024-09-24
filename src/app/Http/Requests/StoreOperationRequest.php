<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

use App\Models\Operation,
    App\Models\Instance;

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
            'command'   => 'required',
            'status'    => 'nullable|max:255',
            'enabled'   => 'nullable|in:0,1',
            'instance'  => 'required|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->instanceExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['instance'];
                    $validator->errors()->add(
                        'instance',
                        "Instance with id: $id was not found."
                    );
                }
            },
            function (Validator $validator) {
                if (!$this->isValidStatus($validator)) {
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
            }
        ];
    }

    public function instanceExists(Validator $validator): bool
    {
        $validated = $validator->validated();
        $instance = Instance::find($validated['instance']);
        return (null !== $instance);
    }

    public function isValidStatus(Validator $validator): bool
    {
        $validated = $validator->validated();

        if (!isset($validated['status']) || null === $validated['status']) return true;

        return in_array(strtoupper($validated['status']), [
            Operation::STATUS_PENDING,
            Operation::STATUS_COMPLETED,
            Operation::STATUS_FAILED,
        ]);
    }
}
