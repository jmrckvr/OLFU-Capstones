<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Support\Activity;
use App\Support\ProjectSaver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/** A student's own submissions: list, add, edit, submit and cancel. */
class SubmissionController extends Controller
{
    public function index()
    {
        return Inertia::render('Submissions/Index', [
            'projects' => auth()->user()->projects()->latest('updated_at')
                ->get([...Project::LIST_COLUMNS, 'feedback']),
            'statuses' => config('repository.statuses'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Submissions/Form', ['project' => null, 'options' => $this->options(), 'mode' => 'student']);
    }

    public function store(ProjectRequest $request)
    {
        $project = new Project;
        $project->user_id = $request->user()->id;
        ProjectSaver::save($project, $request);

        return $this->afterSave($project, $request->input('intent'));
    }

    public function edit(Project $project)
    {
        Gate::authorize('update', $project);

        return Inertia::render('Submissions/Form', [
            'project' => $project->load('files:id,project_id,original_name,mime_type,size'),
            'options' => $this->options(),
            'mode' => 'student',
        ]);
    }

    public function update(ProjectRequest $request, Project $project)
    {
        Gate::authorize('update', $project);
        ProjectSaver::save($project, $request);

        return $this->afterSave($project, $request->input('intent'));
    }

    /** "Submit" / "Resubmit" button in the My Submissions table. */
    public function submit(Project $project)
    {
        Gate::authorize('update', $project);

        $missing = collect(['adviser' => 'adviser', 'abstract' => 'abstract'])
            ->filter(fn ($label, $field) => blank($project->$field))->values();
        if (! $project->files()->exists()) {
            $missing->push('a document');
        }
        if ($missing->isNotEmpty()) {
            return $this->toWithToast('submissions.edit', $project, 'Add the '.$missing->join(', ', ' and ').' first.', 'error');
        }

        return $this->afterSave($project, 'submit');
    }

    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);
        Storage::disk('local')->deleteDirectory("projects/{$project->id}");
        $project->delete();
        Activity::log('project.cancelled', "Cancelled the submission “{$project->title}”");

        return $this->toWithToast('submissions.index', [], 'Submission cancelled.');
    }

    private function afterSave(Project $project, string $intent)
    {
        if ($intent !== 'submit') {
            Activity::log('project.saved', "Saved the draft “{$project->title}”");

            return $this->toWithToast('submissions.index', [], 'Draft saved.');
        }

        $resubmit = $project->status === 'returned';
        $project->forceFill(['status' => 'pending', 'submitted_at' => now()])->save();
        Activity::log('project.submitted', ($resubmit ? 'Resubmitted' : 'Submitted')." “{$project->title}” for review");
        Activity::notifyStaff(($resubmit ? 'Resubmission' : 'New submission').": “{$project->title}” is awaiting review.", "/projects/{$project->id}");

        return $this->toWithToast('submissions.index', [], 'Submitted for review. The coordinator has been notified.');
    }
}
