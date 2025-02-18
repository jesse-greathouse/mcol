<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest,
    Illuminate\Validation\Validator;

use App\Models\Bot,
    App\Models\Channel,
    App\Models\Network;

use \InvalidArgumentException;

class StorePacketRequest extends FormRequest
{
    /**
     * The validation rules for the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'number' => 'required|numeric',
            'gets' => 'numeric',
            'size' => 'required|max:255',
            'file_name' => 'required|max:255',
            'bot' => 'required|numeric',
            'channel' => 'required|numeric',
            'network' => 'required|numeric',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Currently there is no use-case for API to store and update these resources.
        return false;
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<callable>
     */
    public function after(): array
    {
        return [
            // Callable for bot existence check
            fn (Validator $validator) => $this->validateResourceExists($validator, 'bot'),

            // Callable for channel existence check
            fn (Validator $validator) => $this->validateResourceExists($validator, 'channel'),

            // Callable for network existence check
            fn (Validator $validator) => $this->validateResourceExists($validator, 'network'),
        ];
    }

    /**
     * Helper function to validate if a resource (bot, channel, or network) exists.
     *
     * @param Validator $validator
     * @param string    $resourceType
     * @return void
     */
    private function validateResourceExists(Validator $validator, string $resourceType): void
    {
        $validated = $validator->validated();
        $id = $validated[$resourceType];

        // Dynamically resolve the model based on the resource type (bot, channel, network)
        $modelClass = match ($resourceType) {
            'bot' => Bot::class,
            'channel' => Channel::class,
            'network' => Network::class,
            default => throw new InvalidArgumentException("Invalid resource type: $resourceType"),
        };

        // Check if the resource exists
        if (null === $modelClass::find($id)) {
            $validator->errors()->add($resourceType, ucfirst($resourceType) . " with id: $id was not found.");
        }
    }
}
