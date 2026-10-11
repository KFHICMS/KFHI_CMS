<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Models\Event;
use App\Models\Program;

class HomeController extends Controller
{
    public function index()
    {
        try {
            $stats = [
                'children' => Child::where('status', 'active')->count(),
                'events'   => Event::count(),
                'areas'    => 0,   // no areas/regions table yet – replace when you add one
                'projects' => Program::where('status', 'active')->count(),
            ];
        } catch (\Throwable $e) {
            // Public page must never crash if the database is unavailable
            $stats = ['children' => 0, 'events' => 0, 'areas' => 0, 'projects' => 0];
        }

        return view('home', ['stats' => $stats]);
    }
}