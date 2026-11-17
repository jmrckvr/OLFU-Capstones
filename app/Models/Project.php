<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    // `status`, `is_restricted`, `feedback`, `downloads` and the dates are set by code, never by a form.
    protected $fillable = [
        'title', 'proponents', 'program', 'academic_year', 'category',
        'adviser', 'technology', 'description', 'abstract', 'source_url',
    ];

    protected function casts(): array
    {
        return [
            'is_restricted' => 'boolean',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /** The columns a list or table needs. Keeps list responses small (no abstract, no files). */
    public const LIST_COLUMNS = [
        'id', 'user_id', 'title', 'proponents', 'program', 'academic_year', 'category',
        'technology', 'status', 'is_restricted', 'cover_path', 'downloads',
        'submitted_at', 'approved_at', 'created_at', 'updated_at',
    ];

    public function owner()
    {
        // Only the name: never send other people's emails to the browser.
        return $this->belongsTo(User::class, 'user_id')->select(['id', 'name']);
    }

    public function files()
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /** Search title, proponents, technology and adviser. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term = trim((string) $term)) {
            return $query;
        }

        return $query->where(fn ($q) => $q
            ->where('title', 'like', "%{$term}%")
            ->orWhere('proponents', 'like', "%{$term}%")
            ->orWhere('technology', 'like', "%{$term}%")
            ->orWhere('adviser', 'like', "%{$term}%"));
    }

    public function isEditableByOwner(): bool
    {
        return in_array($this->status, ['draft', 'returned'], true);
    }

    public function statusLabel(): string
    {
        return config("repository.statuses.{$this->status}", $this->status);
    }
}
