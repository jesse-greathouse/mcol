<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class DashboardController
{
    public function index()
    {
        return Inertia::render('Dashboard');
    }
}
