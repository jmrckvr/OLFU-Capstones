<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $stats = [
            'projects' => Project::count(),
            'published' => Project::where('status', 'published')->count(),
            'pending' => Project::where('status', 'pending')->count(),
            'users' => User::count(),
        ];

        return Inertia::render('Dashboard', [
            'role' => $user->role,
            'stats' => $stats,
            'recentProjects' => Project::with('owner')->where('status', 'published')->latest()->take(6)->get(),
            'myProjects' => $user->projects()->latest()->take(6)->get(),
        ]);
    }
}
