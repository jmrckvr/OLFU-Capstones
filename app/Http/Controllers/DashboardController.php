<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();

        $recent = Project::approved()->latest('approved_at')->take(6)
            ->get(['id', 'title', 'academic_year', 'category', 'cover_path', 'approved_at']);

        if ($user->isStudent()) {
            // Students see their own numbers, not everyone's.
            $mine = $user->projects()->get(['id', 'title', 'academic_year', 'status', 'updated_at']);

            return Inertia::render('Dashboard', [
                'tiles' => [
                    ['label' => 'Total projects', 'value' => Project::approved()->count()],
                    ['label' => 'My approved', 'value' => $mine->where('status', 'approved')->count()],
                    ['label' => 'Pending review', 'value' => $mine->where('status', 'pending')->count()],
                    ['label' => 'Needs resubmission', 'value' => $mine->where('status', 'returned')->count(), 'alert' => true],
                    ['label' => 'My submissions', 'value' => $mine->count()],
                ],
                'recent' => $recent,
                'mine' => $mine->sortByDesc('updated_at')->values()->take(6),
            ]);
        }

        $tiles = $user->isAdministrator()
            ? [
                ['label' => 'Total projects', 'value' => Project::count()],
                ['label' => 'BSIT projects', 'value' => Project::where('program', 'BSIT')->count()],
                ['label' => 'BSCS projects', 'value' => Project::where('program', 'BSCS')->count()],
                ['label' => 'Active users', 'value' => User::where('is_active', true)->count()],
            ]
            : [
                ['label' => 'In repository', 'value' => Project::approved()->count()],
                ['label' => 'Pending review', 'value' => Project::where('status', 'pending')->count(), 'alert' => true],
                ['label' => 'Need resubmission', 'value' => Project::where('status', 'returned')->count()],
                ['label' => 'Reviewed this week', 'value' => Review::where('created_at', '>=', now()->subDays(6)->startOfDay())->count()],
            ];

        return Inertia::render('Dashboard', [
            'tiles' => $tiles,
            'recent' => $recent,
            'chart' => $this->weeklyChart(),
            'avgReviewHours' => $this->averageReviewHours(),
            'queue' => Project::where('status', 'pending')->oldest('submitted_at')->take(6)
                ->get(['id', 'title', 'proponents', 'submitted_at']),
            'activity' => $user->isAdministrator()
                ? ActivityLog::with('user')->latest('created_at')->take(8)->get()
                : [],
        ]);
    }

    /**
     * Submissions and reviews for each of the last 7 days.
     * Dates are grouped in the app timezone (Asia/Manila), so "today" matches the clock on your PC.
     */
    private function weeklyChart(): array
    {
        $start = now()->subDays(6)->startOfDay();
        $submitted = Project::where('submitted_at', '>=', $start)->pluck('submitted_at')
            ->countBy(fn (Carbon $d) => $d->toDateString());
        $reviewed = Review::where('created_at', '>=', $start)->pluck('created_at')
            ->countBy(fn (Carbon $d) => $d->toDateString());

        return collect(range(0, 6))->map(function ($i) use ($start, $submitted, $reviewed) {
            $day = $start->copy()->addDays($i);

            return [
                'label' => $day->format('D'),
                'date' => $day->format('M j'),
                'submitted' => $submitted[$day->toDateString()] ?? 0,
                'reviewed' => $reviewed[$day->toDateString()] ?? 0,
            ];
        })->all();
    }

    /** Average hours between "submitted" and the review decision, over the last 30 days. */
    private function averageReviewHours(): ?float
    {
        $hours = Review::with('project:id,submitted_at')
            ->where('created_at', '>=', now()->subDays(30))->get(['project_id', 'created_at'])
            ->filter(fn ($r) => $r->project?->submitted_at && $r->project->submitted_at <= $r->created_at)
            ->map(fn ($r) => $r->project->submitted_at->diffInMinutes($r->created_at) / 60);

        return $hours->isEmpty() ? null : round($hours->avg(), 1);
    }
}
