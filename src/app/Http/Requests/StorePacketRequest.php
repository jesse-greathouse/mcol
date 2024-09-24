<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Validator;

use App\Models\Bot,
    App\Models\Channel,
    App\Models\Network;

class StorePacketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Currently there is no use-case for API to store and update these resources.
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
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
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->botExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['bot'];
                    $validator->errors()->add(
                        'bot',
                        "Bot with id: $id was not found."
                    );
                }
            },
            function (Validator $validator) {
                if (!$this->channelExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['channel'];
                    $validator->errors()->add(
                        'channel',
                        "Channel with id: $id was not found."
                    );
                }
            },
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

    public function botExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        $bot = Bot::find($validated['bot']);

        return (null !== $bot);
    }

    public function channelExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        $channel = Channel::find($validated['channel']);

        return (null !== $channel);
    }

    public function networkExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        $network = Network::find($validated['network']);

        return (null !== $network);
    }

}
