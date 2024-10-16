<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

use App\Models\Download;

class StoreDownloadDestinationRequest extends FormRequest
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
            'destination_dir'   => 'required|max:255',
            'download'          => 'required|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->downloadExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['download'];
                    $validator->errors()->add(
                        'download',
                        "Download with id: $id was not found."
                    );
                }
            }
        ];
    }

    public function downloadExists(Validator $validator): bool
    {
        $validated = $validator->validated();

        $download = Download::find($validated['download']);

        return (null !== $download);
    }
}
