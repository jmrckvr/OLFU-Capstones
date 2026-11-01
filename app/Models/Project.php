<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'program', 'academic_year', 'category',
        'tags', 'technology', 'status', 'moderation_reason', 'featured', 'published_at',
    ];

    protected function casts(): array
    {
        return ['tags' => 'array', 'featured' => 'boolean', 'published_at' => 'datetime'];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function moderationHistory()
    {
        return $this->hasMany(ModerationHistory::class);
    }
}
