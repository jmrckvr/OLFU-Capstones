<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModerationController extends Controller
{
    public function index()
    {
        return Inertia::render('Moderation/Index', [
            'projects' => Project::with(['owner', 'files'])->whereIn('status', ['pending', 'returned', 'hidden'])->latest()->paginate(15),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'status' => ['required', 'in:published,returned,hidden'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        $project->update([
            'status' => $data['status'],
            'moderation_reason' => $data['reason'] ?? null,
            'published_at' => $data['status'] === 'published' ? now() : null,
        ]);
        $project->moderationHistory()->create([
            'moderator_id' => $request->user()->id,
            'action' => $data['status'],
            'reason' => $data['reason'] ?? null,
        ]);

        return back()->with('success', 'Moderation decision saved.');
    }
}
