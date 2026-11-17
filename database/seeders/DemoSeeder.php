<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Demo data: run with `npm run seed:demo` (safe to run again; it rebuilds the demo records).
 *
 * Only records that belong to the demo accounts (listed in database/demo/projects.json) are
 * removed and recreated. Anything real users made is left alone.
 * All dates are "N days ago", so the dashboard chart always shows a lively last week.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('demo/projects.json')), true);
        $disk = Storage::disk('local');

        DB::transaction(function () use ($data, $disk) {
            // 1. Accounts (created or updated by email).
            $users = collect($data['users'])->mapWithKeys(function ($u) use ($data) {
                $user = User::firstOrNew(['email' => $u['email']]);
                $user->name = $u['name'];
                $user->password = $data['password'];
                $user->forceFill([
                    'role' => $u['role'],
                    'is_active' => empty($u['inactive']),
                    'announcements_read_at' => null,
                    'email_verified_at' => now(),
                    'created_at' => $user->created_at ?? now()->subDays(120),
                ])->save();

                return [$u['email'] => $user];
            });
            $ids = $users->pluck('id');
            $coordinator = $users['msantos@olfu.edu.ph'];
            $admin = $users['jcalim@olfu.edu.ph'];

            // 2. Clear old demo records (files on disk too).
            Project::whereIn('user_id', $ids)->pluck('id')->each(fn ($id) => $disk->deleteDirectory("projects/{$id}"));
            Project::whereIn('user_id', $ids)->delete();
            UserNotification::whereIn('user_id', $ids)->delete();
            Announcement::whereIn('user_id', $ids)->delete();
            ActivityLog::whereIn('user_id', $ids)->delete();

            $log = fn (User $by, string $action, string $text, Carbon $at) => (new ActivityLog)
                ->forceFill(['user_id' => $by->id, 'action' => $action, 'description' => $text, 'created_at' => $at])->save();
            $notify = fn (User $to, string $msg, ?string $link, Carbon $at, bool $read) => (new UserNotification)
                ->forceFill(['user_id' => $to->id, 'message' => $msg, 'link' => $link, 'created_at' => $at, 'updated_at' => $at, 'read_at' => $read ? $at->copy()->addHour() : null])->save();
            $ago = fn (int $days, int $hour) => now()->subDays($days)->setTime($hour, ($days * 17) % 60)->min(now()->subMinutes(20));

            // 3. Projects with cover, documentation PDF, reviews, notifications and log entries.
            foreach ($data['projects'] as $i => $p) {
                $owner = $users[$p['owner']];
                $submitted = isset($p['submitted']) ? $ago($p['submitted'], 9 + $i % 7) : null;
                $reviewed = isset($p['reviewed']) ? $ago($p['reviewed'], 10 + $i % 6) : null;

                $project = new Project(collect($p)->only((new Project)->getFillable())->all());
                $project->forceFill([
                    'user_id' => $owner->id,
                    'status' => $p['status'],
                    'is_restricted' => ! empty($p['restricted']),
                    'feedback' => $p['feedback'] ?? null,
                    'downloads' => $p['downloads'] ?? 0,
                    'submitted_at' => $submitted,
                    'approved_at' => $p['status'] === 'approved' ? $reviewed : null,
                    'created_at' => ($submitted ?? now()->subDay())->copy()->subDays(2),
                    'updated_at' => $reviewed ?? $submitted ?? now()->subHours(5),
                ])->save();

                $cover = database_path("demo/covers/{$p['slug']}.png");
                if (is_file($cover)) {
                    $disk->put("projects/{$project->id}/cover.png", file_get_contents($cover));
                    $project->forceFill(['cover_path' => "projects/{$project->id}/cover.png"])->saveQuietly();
                }
                $doc = database_path("demo/docs/{$p['slug']}.pdf");
                if (is_file($doc)) {
                    $path = "projects/{$project->id}/{$p['slug']}-documentation.pdf";
                    $disk->put($path, file_get_contents($doc));
                    $project->files()->create([
                        'original_name' => "{$p['title']} – Documentation.pdf",
                        'path' => $path, 'mime_type' => 'application/pdf', 'size' => filesize($doc),
                    ]);
                }

                $link = "/projects/{$project->id}";
                if ($submitted) {
                    $log($owner, 'project.submitted', "Submitted “{$p['title']}” for review", $submitted);
                    $notify($coordinator, "New submission: “{$p['title']}” is awaiting review.", $link, $submitted, $p['status'] !== 'pending');
                }
                if ($reviewed && in_array($p['status'], ['approved', 'returned'], true)) {
                    (new Review)->forceFill([
                        'project_id' => $project->id, 'reviewer_id' => $coordinator->id, 'decision' => $p['status'],
                        'feedback' => $p['feedback'] ?? null, 'created_at' => $reviewed, 'updated_at' => $reviewed,
                    ])->save();
                    $approved = $p['status'] === 'approved';
                    $log($coordinator, "project.{$p['status']}", ($approved ? 'Approved' : 'Returned for resubmission')." “{$p['title']}”", $reviewed);
                    $notify($owner, $approved
                        ? "Your submission “{$p['title']}” was approved and is now in the repository."
                        : "Your submission “{$p['title']}” needs resubmission. Open it to read the feedback.", $link, $reviewed, $p['reviewed'] > 3);
                }
                if ($p['status'] === 'draft') {
                    $log($owner, 'project.saved', "Saved the draft “{$p['title']}”", now()->subHours(6));
                }
            }

            // 4. Announcements and a few admin actions, spread over the week.
            foreach ($data['announcements'] as $a) {
                $at = $ago($a['days'], 8);
                (new Announcement(['message' => $a['message']]))->forceFill(['user_id' => $users[$a['by']]->id, 'created_at' => $at, 'updated_at' => $at])->save();
                $log($users[$a['by']], 'announcement.posted', 'Posted an announcement: '.$a['message'], $at);
            }
            $log($admin, 'user.deactivated', 'Deactivated paolo.rivera@olfu.edu.ph', $ago(4, 15));
            $log($admin, 'user.created', 'Created the account msantos@olfu.edu.ph (coordinator)', $ago(6, 9));
            $log($admin, 'project.exported', 'Exported 10 projects to CSV', $ago(1, 16));
        });

        $this->command?->info('Demo data ready. Log in with any demo email and the password "password".');
    }
}
