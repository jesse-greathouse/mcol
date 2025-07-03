<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * TokenController handles the creation and renewal of API tokens for the authenticated user.
 */
class TokenController
{
    /**
     * @var string The token name from the request.
     */
    private $tokenName;

    /**
     * Create a new API token for the user.
     *
     * @param  Request  $request  The HTTP request containing token name.
     * @return array The generated API token.
     */
    public function create(Request $request): array
    {
        $token = $this->createTokenForUser($request);

        return ['token' => $token->plainTextToken];
    }

    /**
     * Renew an existing API token for the user.
     *
     * @param  Request  $request  The HTTP request containing token name.
     * @return Response The response indicating the outcome.
     */
    public function renew(Request $request): Response
    {
        // Check if the token name is provided in the route parameters
        if (! $request->route()->hasParameter('token_name')) {
            return $this->errorResponse('Needs Token Name');
        }

        // Create a new token for the user
        $token = $this->createTokenForUser($request);

        return ['token' => $token->plainTextToken];
    }

    /**
     * Generate a token for the authenticated user based on the request token name.
     *
     * @param  Request  $request  The HTTP request containing token name.
     * @return \Laravel\Sanctum\NewAccessToken The newly created token.
     */
    private function createTokenForUser(Request $request): \Laravel\Sanctum\NewAccessToken
    {
        $this->tokenName = $request->token_name;

        return $request->user()->createToken($this->tokenName);
    }

    /**
     * Generate a standard error response with message.
     *
     * @param  string  $message  The error message to return.
     * @return Response The error response.
     */
    private function errorResponse(string $message): Response
    {
        return response($message, Response::HTTP_BAD_REQUEST)
            ->header('Content-Type', 'text/plain')
            ->header('Access-Control-Allow-Origin', '*');
    }
}
