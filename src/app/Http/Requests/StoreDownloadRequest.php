<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

use App\Models\Download,
    App\Models\Packet;

class StoreDownloadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'file_uri'          => 'required|max:255',
            'status'            => 'nullable|max:255',
            'enabled'           => 'nullable|in:0,1',
            'file_size_bytes'   => 'nullable|numeric',
            'progress_bytes'    => 'nullable|numeric',
            'queued_total'      => 'nullable|numeric',
            'queued_status'     => 'nullable|numeric',
            'packet'            => 'required|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->packetExists($validator)) {
                    $validated = $validator->validated();
                    $id = $validated['packet'];
                    $validator->errors()->add(
                        'packet',
                        "Packet with id: $id was not found."
                    );
                }
            },
            function (Validator $validator) {
                if (!$this->isValidStatus($validator)) {
                    $validated = $validator->validated();
                    $status = $validated['status'];
                    $incomplete = Download::STATUS_INCOMPLETE;
                    $completed = Download::STATUS_COMPLETED;
                    $queued = Download::STATUS_QUEUED;
                    $validStatuses = "$incomplete, $completed and $queued";
                    $validator->errors()->add(
                        'status',
                        "Status: $status is not valid. (Valid statuses are: $validStatuses)"
                    );
                }
            }
        ];
    }

    public function packetExists(Validator $validator): bool
    {
        $validated = $validator->validated();
        $packet = Packet::find($validated['packet']);
        return (null !== $packet);
    }

    public function isValidStatus(Validator $validator): bool
    {
        $validated = $validator->validated();

        if (!isset($validated['status']) || null === $validated['status']) return true;

        return in_array(strtoupper($validated['status']), [
            Download::STATUS_INCOMPLETE,
            Download::STATUS_COMPLETED,
            Download::STATUS_QUEUED,
        ]);
    }
}
