<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator,
    Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Provides default implementation of ValidatesWhenResolved contract.
 */
trait StreamFailedValidationTrait
{
    // Formulates the error response in a way that's congruent to the txt:txt format that the streaming endpoints use.
    public function failedValidation(Validator $validator)
    {
        $content = '';

        foreach($validator->errors()->getMessages() as $key => $msgs) {
            $content .= "[$key]:\n";
            foreach($msgs as $message) {
                $content .= "$message\n";
            }
        }

        throw new HttpResponseException(response()->make($content, 400));
    }
}
