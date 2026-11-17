<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserNotification;

/**
 * Small helpers so controllers can write one line:
 *   Activity::log('project.approved', "Approved “{$project->title}”");
 *   Activity::notify($user, 'Your project was approved', "/projects/{$project->id}");
 */
class Activity
{
    public static function log(string $action, string $description, ?User $by = null): void
    {
        ActivityLog::create([
            'user_id' => ($by ?? auth()->user())?->id,
            'action' => $action,
            'description' => mb_strimwidth($description, 0, 500, '…'),
        ]);
    }

    public static function notify(User|int $user, string $message, ?string $link = null): void
    {
        (new UserNotification(['message' => $message, 'link' => $link]))
            ->forceFill(['user_id' => $user instanceof User ? $user->id : $user])
            ->save();
    }

    /** Notify every active coordinator and administrator. */
    public static function notifyStaff(string $message, ?string $link = null): void
    {
        User::whereIn('role', ['coordinator', 'administrator'])->where('is_active', true)
            ->pluck('id')->each(fn ($id) => self::notify($id, $message, $link));
    }
}
