<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/** Browsing the repository and opening one project (all roles). */
class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->only('search', 'year', 'category', 'program', 'sort');

        $projects = Project::approved()
            ->search($filters['search'] ?? null)
            ->when($filters['year'] ?? null, fn ($q, $v) => $q->where('academic_year', $v))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['program'] ?? null, fn ($q, $v) => $q->where('program', $v))
            ->when($filters['sort'] ?? 'newest', fn ($q, $sort) => match ($sort) {
                'title' => $q->orderBy('title'),
                'popular' => $q->orderByDesc('downloads'),
                default => $q->orderByDesc('approved_at'),
            })
            ->paginate(8, [...Project::LIST_COLUMNS, 'description', 'adviser'])
            ->withQueryString();

        return Inertia::render('Repository/Index', [
            'projects' => $projects,
            'filters' => (object) $filters,
            'options' => $this->options(),
        ]);
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);
        $user = auth()->user();
        $canSeeFiles = $user->can('viewFiles', $project);
        $isOwnerOrStaff = $project->user_id === $user->id || $user->isStaff();

        $project->load('owner');
        if ($canSeeFiles) {
            $project->load('files:id,project_id,original_name,mime_type,size');
        }
        if ($isOwnerOrStaff) {
            $project->load(['reviews' => fn ($q) => $q->latest()->with('reviewer')]);
        }

        return Inertia::render('Projects/Show', [
            'project' => $project,
            'canSeeFiles' => $canSeeFiles,
            'can' => [
                'review' => $user->can('review', $project),
                'update' => $user->can('update', $project),
                'delete' => $user->can('delete', $project),
            ],
            'isOwner' => $project->user_id === $user->id,
        ]);
    }

    public function download(Project $project, ProjectFile $file)
    {
        $this->authorizeFile($project, $file);
        if ($project->user_id !== auth()->id()) {
            $project->increment('downloads');
        }

        return Storage::disk('local')->download($file->path, $file->original_name);
    }

    /** Shows a PDF inside the page instead of downloading it. */
    public function preview(Project $project, ProjectFile $file)
    {
        $this->authorizeFile($project, $file);
        abort_unless($file->isPdf(), 404);

        return Storage::disk('local')->response($file->path, $file->original_name, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
        ]);
    }

    public function cover(Project $project)
    {
        Gate::authorize('view', $project);
        abort_unless($project->cover_path && Storage::disk('local')->exists($project->cover_path), 404);

        return Storage::disk('local')->response($project->cover_path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function authorizeFile(Project $project, ProjectFile $file): void
    {
        abort_unless($file->project_id === $project->id, 404);
        Gate::authorize('viewFiles', $project);
        abort_unless(Storage::disk('local')->exists($file->path), 404, 'This file is missing from storage.');
    }
}
