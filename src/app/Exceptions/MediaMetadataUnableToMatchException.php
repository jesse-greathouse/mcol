<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class MediaMetadataUnableToMatchException extends Exception
{
    /**
     * Report or log an exception.
     */
    public function report(): bool
    {
        // Log the message without the stack trace
        Log::info($this->getMessage());

        // Returning true indicates that the exception has been handled
        return true;
    }
}
