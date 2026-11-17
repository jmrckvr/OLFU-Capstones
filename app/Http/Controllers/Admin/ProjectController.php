<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Support\Activity;
use App\Support\ProjectSaver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Admin: every project in every status. Routes are guarded by role:administrator. */
class ProjectController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Projects', [
            'projects' => Project::with('owner')->latest('updated_at')->get(Project::LIST_COLUMNS),
            'statuses' => config('repository.statuses'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Submissions/Form', ['project' => null, 'options' => $this->options(), 'mode' => 'admin']);
    }

    /** Admin-added projects (e.g. older capstones) go straight into the repository when published. */
    public function store(ProjectRequest $request)
    {
        $project = new Project;
        $project->user_id = $request->user()->id;
        $project->is_restricted = $request->boolean('is_restricted');
        ProjectSaver::save($project, $request);

        if ($request->input('intent') === 'submit') {
            $project->forceFill(['status' => 'approved', 'submitted_at' => now(), 'approved_at' => now()])->save();
        }
        Activity::log('project.created', "Added the project “{$project->title}”");

        return $this->toWithToast('admin.projects.index', [], 'Project added.');
    }

    public function edit(Project $project)
    {
        return Inertia::render('Submissions/Form', [
            'project' => $project->load('files:id,project_id,original_name,mime_type,size'),
            'options' => $this->options(),
            'mode' => 'admin',
        ]);
    }

    /** Editing keeps the current status; status only changes through a review. */
    public function update(ProjectRequest $request, Project $project)
    {
        $project->is_restricted = $request->boolean('is_restricted');
        ProjectSaver::save($project, $request);
        // An admin's draft becomes approved when they press "Publish".
        if ($project->status === 'draft' && $request->input('intent') === 'submit') {
            $project->forceFill(['status' => 'approved', 'submitted_at' => now(), 'approved_at' => now()])->save();
        }
        Activity::log('project.updated', "Edited the project “{$project->title}”");

        return $this->toWithToast('admin.projects.index', [], 'Changes saved.');
    }

    public function destroy(Project $project)
    {
        Storage::disk('local')->deleteDirectory("projects/{$project->id}");
        $project->delete();
        Activity::log('project.deleted', "Deleted the project “{$project->title}”");

        return $this->backWithToast('Project deleted.');
    }

    /** Download the project list as a CSV file (opens in Excel). */
    public function export(Request $request): StreamedResponse
    {
        $statuses = config('repository.statuses');
        $rows = Project::with('owner')->orderBy('title')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->get(Project::LIST_COLUMNS);
        Activity::log('project.exported', 'Exported '.$rows->count().' projects to CSV');

        return response()->streamDownload(function () use ($rows, $statuses) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads the dashes (–) and ñ correctly
            fputcsv($out, ['Title', 'Proponents', 'Program', 'Academic year', 'Category', 'Technologies', 'Status', 'Restricted', 'Downloads', 'Submitted by', 'Approved on']);
            foreach ($rows as $p) {
                fputcsv($out, [
                    $p->title, $p->proponents, $p->program, $p->academic_year, $p->category, $p->technology,
                    $statuses[$p->status] ?? $p->status, $p->is_restricted ? 'Yes' : 'No', $p->downloads,
                    $p->owner?->name, $p->approved_at?->format('Y-m-d'),
                ]);
            }
            fclose($out);
        }, 'ccs-projects-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
