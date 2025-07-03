<?php

namespace App\Http\Requests;

use App\Media\Store;
use App\Settings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Class CreateDirectoryRequest
 * Handles the validation logic for creating a new directory.
 */
class CreateDirectoryRequest extends FormRequest
{
    /**
     * A Store object.
     */
    private ?Store $store = null;

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
            'uri' => 'required|max:255',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(Settings $settings): array
    {
        // Using a single closure with multiple validation checks for efficiency.
        return [
            function (Validator $validator) use ($settings) {
                $store = $this->getStore($settings);
                $validated = $validator->validated();
                $uri = $validated['uri'];

                // Check for illegal form like './' or '../' in URI.
                if ($store->hasDotSlash($uri)) {
                    $validator->errors()->add(
                        'uri',
                        "\"$uri\" has illegal form (./, ../)."
                    );
                }

                // Check if URI is branched from any media store.
                if (! $store->isBranchOfMediaStore($uri)) {
                    $validator->errors()->add(
                        'uri',
                        "\"$uri\" is not branched from any media store."
                    );
                }

                // Check if URI already exists as a directory.
                if (is_dir($uri)) {
                    $validator->errors()->add(
                        'uri',
                        "\"$uri\" already exists."
                    );
                }
            },
        ];
    }

    /**
     * Returns a Store object with the provided settings.
     */
    private function getStore(Settings $settings): Store
    {
        if (! $this->store) {
            $this->store = new Store($settings->media_store);
        }

        return $this->store;
    }
}
