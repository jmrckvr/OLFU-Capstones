<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\UserNotification;
use App\Support\Activity;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Personal notifications + announcements for everyone. */
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $seen = $user->announcements_read_at;

        $personal = $user->alerts()->latest()->take(100)->get()->map(fn ($n) => [
            'id' => "n{$n->id}", 'kind' => 'personal', 'message' => $n->message, 'link' => $n->link,
            'read' => (bool) $n->read_at, 'at' => $n->created_at,
        ]);
        $announcements = Announcement::with('author')->latest()->take(50)->get()->map(fn ($a) => [
            'id' => "a{$a->id}", 'kind' => 'announcement', 'message' => $a->message, 'link' => null,
            'read' => $seen && $a->created_at <= $seen, 'at' => $a->created_at,
            'author' => $a->author?->name, 'can_delete' => $user->isAdministrator() || $a->user_id === $user->id,
        ]);

        return Inertia::render('Notifications/Index', [
            'items' => $personal->concat($announcements)->sortByDesc('at')->values(),
            'canAnnounce' => $user->isStaff(),
        ]);
    }

    /** Mark one item read. Ids look like "n12" (personal) or "a3" (announcement). */
    public function read(Request $request, string $id)
    {
        $user = $request->user();
        if (str_starts_with($id, 'n')) {
            $user->alerts()->whereKey((int) substr($id, 1))->update(['read_at' => now()]);
        } elseif (str_starts_with($id, 'a')) {
            $announcement = Announcement::findOrFail((int) substr($id, 1));
            if (! $user->announcements_read_at || $user->announcements_read_at < $announcement->created_at) {
                $user->forceFill(['announcements_read_at' => $announcement->created_at])->save();
            }
        }

        return back();
    }

    public function readAll(Request $request)
    {
        $user = $request->user();
        $user->alerts()->whereNull('read_at')->update(['read_at' => now()]);
        $user->forceFill(['announcements_read_at' => now()])->save();

        return $this->backWithToast('All caught up.');
    }

    public function announce(Request $request)
    {
        abort_unless($request->user()->isStaff(), 403);
        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);
        $announcement = new Announcement($data);
        $announcement->user_id = $request->user()->id;
        $announcement->save();
        Activity::log('announcement.posted', 'Posted an announcement: '.$data['message']);

        return $this->backWithToast('Announcement posted to everyone.');
    }

    public function destroyAnnouncement(Request $request, Announcement $announcement)
    {
        $user = $request->user();
        abort_unless($user->isAdministrator() || $announcement->user_id === $user->id, 403);
        $announcement->delete();

        return $this->backWithToast('Announcement removed.');
    }
}
