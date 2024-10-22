<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

use App\Media\Store,
    App\Settings;

class CreateDirectoryRequest extends FormRequest
{
    /**
     * A Store Object
     *
     * @var Store
     */
    private $store;

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
            'uri'   => 'required|max:255',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(Settings $settings): array
    {
        return [
            function (Validator $validator) use ($settings) {
                $store = $this->getStore($settings);
                $validated = $validator->validated();
                $uri = $validated['uri'];
                if ($store->hasDotSlash($uri)) {
                    $validator->errors()->add(
                        'uri',
                        "\"$uri\" has illegal form (./, ../)."
                    );
                }
            },
            function (Validator $validator) use ($settings) {
                $store = $this->getStore($settings);
                $validated = $validator->validated();
                $uri = $validated['uri'];
                if (!$store->isBranchOfMediaStore($uri)) {
                    $validator->errors()->add(
                        'uri',
                        "\"$uri\" is not branched from any media store."
                    );
                }
            },
            function (Validator $validator) {
                $validated = $validator->validated();
                $uri = $validated['uri'];
                if (is_dir($uri)) {
                    $validator->errors()->add(
                        'uri',
                        "\"$uri\" already exists."
                    );
                }
            }
        ];
    }

    /**
     * Returns a store object with the settings.
     *
     * @param Settings $settings
     * @return Store
     */
    private function getStore(Settings $settings): Store
    {
        if (null === $this->store) {
            $this->store = new Store($settings->media_store);
        }

        return $this->store;
    }
}
