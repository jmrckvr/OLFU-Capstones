<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Inertia\Inertia;

/** Admin: who did what, newest first (last 500 entries). */
class ActivityController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Activity', [
            'logs' => ActivityLog::with('user')->latest('created_at')->latest('id')->take(500)->get(),
        ]);
    }
}
