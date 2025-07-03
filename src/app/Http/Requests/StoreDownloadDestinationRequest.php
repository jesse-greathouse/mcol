<?php

namespace App\Http\Requests;

use App\Models\Download;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * StoreDownloadDestinationRequest handles the validation for storing download destinations.
 */
class StoreDownloadDestinationRequest extends FormRequest
{
    /**
     * @var string Destination directory for the download.
     */
    public string $destinationDir;

    /**
     * @var int Download ID associated with the destination.
     */
    public int $download;

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
            'destination_dir' => 'required|max:255',
            'download' => 'required|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, \Closure> An array of validation callables to run after validation.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->downloadExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['download'];
                    $validator->errors()->add(
                        'download',
                        "Download with id: $id was not found."
                    );
                }
            },
        ];
    }

    /**
     * Check if the download exists.
     *
     * @param  Validator  $validator  The validator instance.
     * @return bool True if the download exists, otherwise false.
     */
    public function downloadExists(Validator $validator): bool
    {
        $validated = $validator->validated();
        $download = Download::find($validated['download']);

        return $download !== null;
    }
}
