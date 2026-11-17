<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    // SECURITY: `role` and `is_active` are NOT in this list on purpose.
    // They can only be changed with explicit code ($user->role = ...) in the admin
    // controller, so no form can sneak in "role=administrator".
    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    // Same defaults as the database, so a freshly created user object already has them.
    protected $attributes = ['role' => 'student', 'is_active' => true];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'announcements_read_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function alerts()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isCoordinator(): bool
    {
        return $this->role === 'coordinator';
    }

    public function isAdministrator(): bool
    {
        return $this->role === 'administrator';
    }

    /** Coordinator or administrator: can review and see restricted files. */
    public function isStaff(): bool
    {
        return $this->isCoordinator() || $this->isAdministrator();
    }

    /** The small, safe version of a user that is sent to the browser. */
    public function toPublic(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'is_active' => $this->is_active,
        ];
    }
}
