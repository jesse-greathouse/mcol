<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNickRequest;
use App\Http\Requests\UpdateNickRequest;
use App\Models\Nick;

class NickController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNickRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Nick $nick)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Nick $nick)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNickRequest $request, Nick $nick)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Nick $nick)
    {
        //
    }
}
