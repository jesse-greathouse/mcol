<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TokenController
{
    public function create(Request $request)
    {
        $token = $request->user()->createToken($request->token_name);
        return ['token' => $token->plainTextToken];
    }

    public function renew(Request $request)
    {
        if (!$request->route()->hasParameter('token_name')) {
            return response('Needs Token Name', Response::HTTP_BAD_REQUEST)
                ->header('Content-Type', 'text/plain')
                ->header('Access-Control-Allow-Origin', '*');
        } else {
            $token = $request->user()->createToken($request->token_name);
            return ['token' => $token->plainTextToken];
        }
    }
}
