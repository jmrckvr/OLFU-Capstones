<?php

namespace App\Support;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Saves the Add/Edit project form: fields, uploaded documents, removed documents and the cover image. */
class ProjectSaver
{
    public static function save(Project $project, ProjectRequest $request): Project
    {
        $data = $request->validated();
        $disk = Storage::disk('local'); // = storage/app/private (not reachable from the web)

        return DB::transaction(function () use ($project, $request, $data, $disk) {
            $project->fill(collect($data)->only($project->getFillable())->all());
            $project->save(); // need an id before storing files under projects/{id}

            if (! empty($data['remove_files'])) {
                $project->files()->whereIn('id', $data['remove_files'])->get()->each(function ($file) use ($disk) {
                    $disk->delete($file->path);
                    $file->delete();
                });
            }

            foreach ($request->file('documents', []) as $upload) {
                // Same file picked twice in one form? Keep only one copy.
                if ($project->files()->where('original_name', $upload->getClientOriginalName())->where('size', $upload->getSize())->exists()) {
                    continue;
                }
                $project->files()->create([
                    'original_name' => $upload->getClientOriginalName(),
                    'path' => $upload->store("projects/{$project->id}", 'local'),
                    'mime_type' => $upload->getMimeType() ?? 'application/octet-stream',
                    'size' => $upload->getSize(),
                ]);
            }

            if ($cover = $request->file('cover')) {
                if ($project->cover_path) {
                    $disk->delete($project->cover_path);
                }
                $project->cover_path = $cover->store("projects/{$project->id}", 'local');
                $project->save();
            }

            if ($data['intent'] === 'submit' && ! $project->files()->exists()) {
                throw ValidationException::withMessages(['documents' => 'Attach at least one document before submitting.']);
            }

            return $project;
        });
    }
}
