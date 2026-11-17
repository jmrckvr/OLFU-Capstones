<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Who may do what with a project. Laravel finds this class automatically
 * (Project -> ProjectPolicy), and controllers call it with Gate::authorize('view', $project).
 */
class ProjectPolicy
{
    /** Approved projects are visible to everyone logged in; others only to the owner and staff. */
    public function view(User $user, Project $project): bool
    {
        return $project->status === 'approved' || $this->ownsOrStaff($user, $project);
    }

    /** Files: restricted projects are for the coordinator and admin only (and the owner). */
    public function viewFiles(User $user, Project $project): bool
    {
        if (! $this->view($user, $project)) {
            return false;
        }

        return ! $project->is_restricted || $this->ownsOrStaff($user, $project);
    }

    /** Students edit their own drafts and returned projects. Admins can edit anything. */
    public function update(User $user, Project $project): bool
    {
        return $user->isAdministrator()
            || ($project->user_id === $user->id && $project->isEditableByOwner());
    }

    /** Students cancel their own drafts or pending submissions. Admins can delete anything. */
    public function delete(User $user, Project $project): bool
    {
        return $user->isAdministrator()
            || ($project->user_id === $user->id && in_array($project->status, ['draft', 'pending'], true));
    }

    /** Only pending submissions can be reviewed, and only by staff. */
    public function review(User $user, Project $project): bool
    {
        return $user->isStaff() && $project->status === 'pending';
    }

    private function ownsOrStaff(User $user, Project $project): bool
    {
        return $project->user_id === $user->id || $user->isStaff();
    }
}
