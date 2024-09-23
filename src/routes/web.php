<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

Route::post('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);

    return ['token' => $token->plainTextToken];
});

Route::get('/tokens/create/{token_name}', function (Request $request) {
    if (!$request->route()->hasParameter('token_name')) {
        return response('Needs Token Name', Response::HTTP_BAD_REQUEST)
            ->header('Content-Type', 'text/plain')
            ->header('Access-Control-Allow-Origin', '*');
    } else {
        $token = $request->user()->createToken($request->token_name);
        return ['token' => $token->plainTextToken];
    }
});
