<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest,
    Illuminate\Validation\Validator;

use App\Models\Download,
    App\Models\Packet;

class StoreDownloadRequest extends FormRequest
{
    /** @var string Maximum length for file URI. */
    protected $maxFileUriLength = 255;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Always false for this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /** @var array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> Validation rules for the request. */
    public function rules(): array
    {
        return [
            'file_uri'        => "required|max:{$this->maxFileUriLength}",
            'status'          => 'nullable|max:255',
            'enabled'         => 'nullable|in:0,1',
            'file_size_bytes' => 'nullable|numeric',
            'progress_bytes'  => 'nullable|numeric',
            'queued_total'    => 'nullable|numeric',
            'queued_status'   => 'nullable|numeric',
            'packet'          => 'required|numeric',
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, \Closure> After validation actions.
     */
    public function after(): array
    {
        return [
            fn(Validator $validator) => $this->validatePacket($validator),
            fn(Validator $validator) => $this->validateStatus($validator),
        ];
    }

    /**
     * Validate if the packet exists.
     *
     * @param Validator $validator The validator instance.
     * @return void
     */
    protected function validatePacket(Validator $validator): void
    {
        if (!$this->packetExists($validator)) {
            $validated = $validator->validated();
            $id = $validated['packet'];
            $validator->errors()->add(
                'packet',
                "Packet with id: $id was not found."
            );
        }
    }

    /**
     * Validate if the status is valid.
     *
     * @param Validator $validator The validator instance.
     * @return void
     */
    protected function validateStatus(Validator $validator): void
    {
        if (!$this->isValidStatus($validator)) {
            $validated = $validator->validated();
            $status = $validated['status'];
            $validStatuses = implode(', ', [
                Download::STATUS_INCOMPLETE,
                Download::STATUS_COMPLETED,
                Download::STATUS_QUEUED,
            ]);
            $validator->errors()->add(
                'status',
                "Status: $status is not valid. (Valid statuses are: $validStatuses)"
            );
        }
    }

    /**
     * Check if the packet exists.
     *
     * @param Validator $validator The validator instance.
     * @return bool True if the packet exists, false otherwise.
     */
    protected function packetExists(Validator $validator): bool
    {
        $validated = $validator->validated();
        return Packet::find($validated['packet']) !== null;
    }

    /**
     * Check if the status is valid.
     *
     * @param Validator $validator The validator instance.
     * @return bool True if the status is valid, false otherwise.
     */
    protected function isValidStatus(Validator $validator): bool
    {
        $validated = $validator->validated();
        $status = $validated['status'] ?? null;

        if ($status === null) {
            return true;
        }

        return in_array(strtoupper($status), [
            Download::STATUS_INCOMPLETE,
            Download::STATUS_COMPLETED,
            Download::STATUS_QUEUED,
        ]);
    }
}
