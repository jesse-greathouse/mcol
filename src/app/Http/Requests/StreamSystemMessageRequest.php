<?php

namespace App\Http\Requests;

class StreamSystemMessageRequest extends SystemMessageRequest
{
    use StreamFailedValidationTrait;
}
