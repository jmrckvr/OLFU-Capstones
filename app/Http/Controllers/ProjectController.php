<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::with('owner')->where('status', 'published')
            ->when($request->search, fn ($query, $search) => $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")
                ->orWhere('program', 'like', "%{$search}%")->orWhere('technology', 'like', "%{$search}%")))
            ->when($request->program, fn ($query, $program) => $query->where('program', $program))
            ->latest('published_at')->paginate(12)->withQueryString();

        return Inertia::render('Repository/Index', ['projects' => $projects, 'filters' => $request->only('search', 'program')]);
    }

    public function create()
    {
        return Inertia::render('Repository/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'program' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'string', 'max:20'],
            'category' => ['required', 'string', 'max:100'],
            'technology' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'string', 'max:500'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:51200', 'mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png,gif,txt,js,ts,php,py,java,html,css'],
        ]);
        $project = $request->user()->projects()->create([
            ...collect($data)->except('files', 'tags')->toArray(),
            'tags' => $data['tags'] ? array_values(array_filter(array_map('trim', explode(',', $data['tags']))) ) : [],
        ]);
        foreach ($request->file('files') as $file) {
            $project->files()->create([
                'original_name' => $file->getClientOriginalName(),
                'path' => $file->store("projects/{$project->id}", 'private'),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Your work was submitted for moderation.');
    }

    public function download(Project $project, string $file)
    {
        abort_unless($project->status === 'published' || $project->user_id === auth()->id() || auth()->user()->isCoordinator() || auth()->user()->isAdministrator(), 403);
        $projectFile = $project->files()->findOrFail($file);

        return Storage::disk('private')->download($projectFile->path, $projectFile->original_name);
    }
}
