<?php

namespace App\Http\Middleware;

use App\Models\Announcement;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** Data every page receives. Toast messages travel separately via Inertia::flash(). */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            // Only safe fields (no password hash, no remember token, no timestamps).
            'auth' => fn () => ['user' => $user?->toPublic()],
            'unread' => fn () => $user ? $this->unreadCount($user) : 0,
            // Badge on "Review Queue" for the coordinator and admin.
            'pendingCount' => fn () => $user?->isStaff() ? Project::where('status', 'pending')->count() : 0,
        ];
    }

    private function unreadCount($user): int
    {
        $personal = $user->alerts()->whereNull('read_at')->count();
        $announcements = Announcement::query()
            ->when($user->announcements_read_at, fn ($q, $at) => $q->where('created_at', '>', $at))
            ->count();

        return $personal + $announcements;
    }
}
