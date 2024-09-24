<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Validation\Validator;

use App\Models\Client,
    App\Models\Nick,
    App\Models\Network;

class StoreClientRequest extends FormRequest
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
            'enabled' => 'nullable|in:0,1',
            'network' => 'required|numeric',
            'nick' => 'required|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->nickExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['nick'];
                    $validator->errors()->add(
                        'nick',
                        "Nick with id: $id was not found."
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
            },
            function (Validator $validator) {
                if ($this->combinationExists($validator)) {
                    $validated = $validator->validated();
                    $NickId = $validated['nick'];
                    $NetworkId = $validated['network'];
                    $validator->errors()->add(
                        'nick',
                        "Nick with id: $NickId already has a client with Network: $NetworkId."
                    );
                    $validator->errors()->add(
                        'network',
                        "Network with id: $NetworkId already has a client with Nick: $NickId."
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

    public function nickExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        $nick = Network::find($validated['nick']);

        return (null !== $nick);
    }

    public function combinationExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        $network = Network::find($validated['network']);
        if (null == $network) return false;

        $nick = Nick::find($validated['nick']);
        if (null == $nick) return false;

        $client = Client::where('network_id', $network->id)
            ->where('nick_id', $nick->id)
            ->first();

        return (null !== $client);
    }
}
