<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    protected $fillable = ['original_name', 'path', 'mime_type', 'size'];

    // `path` is where the file sits on disk; the browser never needs it.
    protected $hidden = ['path'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
