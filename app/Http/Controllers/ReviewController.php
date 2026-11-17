<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Review;
use App\Support\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** Review queue for the Research Coordinator (and admin). */
class ReviewController extends Controller
{
    public function index()
    {
        return Inertia::render('Reviews/Index', [
            'pending' => Project::with('owner')->where('status', 'pending')->oldest('submitted_at')
                ->get(Project::LIST_COLUMNS),
            'history' => Review::with(['reviewer', 'project:id,title,proponents,status'])
                ->latest()->take(200)->get(),
        ]);
    }

    public function store(Request $request, Project $project)
    {
        Gate::authorize('review', $project);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'returned'])],
            // The template: "Feedback (required when requesting resubmission)".
            'feedback' => ['nullable', 'required_if:decision,returned', 'string', 'max:2000'],
            'is_restricted' => ['boolean'],
        ], ['feedback.required_if' => 'Write feedback so the students know what to fix.']);

        $approved = $data['decision'] === 'approved';
        $project->forceFill([
            'status' => $data['decision'],
            'feedback' => $data['feedback'] ?? null,
            'approved_at' => $approved ? now() : null,
            'is_restricted' => $approved ? ($data['is_restricted'] ?? $project->is_restricted) : $project->is_restricted,
        ])->save();

        $project->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'decision' => $data['decision'],
            'feedback' => $data['feedback'] ?? null,
        ]);

        $verb = $approved ? 'approved' : 'returned for resubmission';
        Activity::log("project.{$data['decision']}", ucfirst($verb)." “{$project->title}”");
        Activity::notify($project->user_id, $approved
            ? "Your submission “{$project->title}” was approved and is now in the repository."
            : "Your submission “{$project->title}” needs resubmission. Open it to read the feedback.", "/projects/{$project->id}");

        return $this->toWithToast('reviews.index', [], $approved ? 'Approved. It is now in the repository.' : 'Returned to the students with your feedback.');
    }
}
