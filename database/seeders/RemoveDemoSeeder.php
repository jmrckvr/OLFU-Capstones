<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Project;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Undo for DemoSeeder: run with `npm run unseed:demo`.
 *
 * Removes everything that belongs to the demo accounts in database/demo/projects.json:
 * their projects (with uploaded files, reviews), notifications, announcements, activity
 * log entries, and the accounts themselves. Records made by real users are left alone.
 *
 * Safety: if the demo administrator is the ONLY active administrator, that account is kept
 * so nobody gets locked out of the admin pages. Its demo records are still removed.
 */
class RemoveDemoSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(file_get_contents(database_path('demo/projects.json')), true);
        $emails = collect($data['users'])->pluck('email');
        $disk = Storage::disk('local');

        $users = User::whereIn('email', $emails)->get();
        if ($users->isEmpty()) {
            $this->command?->info('No demo data found. Nothing to remove.');

            return;
        }
        $ids = $users->pluck('id');

        // Keep the demo admin if no real admin exists (otherwise nobody could manage the site).
        $otherAdmins = User::where('role', 'administrator')->where('is_active', true)->whereNotIn('id', $ids)->count();
        $keep = $otherAdmins > 0 ? collect() : $users->where('role', 'administrator')->pluck('id');

        $counts = DB::transaction(function () use ($ids, $keep, $disk) {
            // Uploaded covers and PDFs live on disk, not in the database, so delete those folders first.
            $projectIds = Project::whereIn('user_id', $ids)->pluck('id');
            $projectIds->each(fn ($id) => $disk->deleteDirectory("projects/{$id}"));

            $counts = [
                'projects' => Project::whereIn('user_id', $ids)->delete(), // files + reviews cascade
                'notifications' => UserNotification::whereIn('user_id', $ids)->delete(),
                'announcements' => Announcement::whereIn('user_id', $ids)->delete(),
                'logs' => ActivityLog::whereIn('user_id', $ids)->delete(),
            ];

            $remove = $ids->diff($keep);
            DB::table('sessions')->whereIn('user_id', $remove)->delete(); // log demo users out
            $counts['accounts'] = User::whereIn('id', $remove)->delete();

            return $counts;
        });

        $this->command?->info(sprintf(
            'Demo data removed: %d accounts, %d projects, %d notifications, %d announcements, %d log entries.',
            $counts['accounts'], $counts['projects'], $counts['notifications'], $counts['announcements'], $counts['logs'],
        ));
        if ($keep->isNotEmpty()) {
            $this->command?->warn('Kept the demo administrator account (jcalim@olfu.edu.ph) because it is the only admin. '
                .'Create a real admin, then run this again to remove it.');
        }
    }
}
