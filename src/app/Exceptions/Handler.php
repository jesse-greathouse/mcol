<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            if ($e instanceof ModelNotFoundException) {
                return $this->jsonModelNotFound($request);
            }
        }

        return parent::render($request, $e);
    }

    /**
     * Form the Json messaging for a Model not found response.
     */
    protected function jsonModelNotFound($request)
    {
        $message = 'Resource was not found.';

        $id = $request->route()->parameter('id');
        if (null !== $id) {
            $message = "Resource with id: $id was not found.";
        }

        return response()->json([
            'message' => $message,
        ], 404);
    }
}
