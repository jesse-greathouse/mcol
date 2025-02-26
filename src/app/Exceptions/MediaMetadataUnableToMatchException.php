<?php

namespace App\Exceptions;

use Illuminate\Support\Facades\Log;

use Exception;

class MediaMetadataUnableToMatchException extends Exception
{
/**
     * Report or log an exception.
     *
     * @return bool
     */
    public function report(): bool
    {
        // Log the message without the stack trace
        Log::info($this->getMessage());

        // Returning true indicates that the exception has been handled
        return true;
    }
}
