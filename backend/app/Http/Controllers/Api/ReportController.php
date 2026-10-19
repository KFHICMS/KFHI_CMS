<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Child;
use App\Models\Event;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Benefit;
use App\Models\BenefitType;
use App\Models\FollowUp;
use Illuminate\Http\Request;
use App\Models\Program;

class ReportController extends Controller
{
    // GET /api/reports/summary
    public function summary()
    {
        return response()->json([
            'total_children'     => Child::count(),
            'active_programs'    => Program::where('status', 'active')->count(),
            'active_events'      => Event::where('status', 'active')->count(),
            'total_users'        => User::count(),
            'total_attendance'   => Attendance::count(),
            'total_benefits'     => Benefit::count(),
            'pending_follow_ups' => FollowUp::where('status', 'pending')->count(),
        ]);
    }

    // GET /api/reports/registrations?from=&to=
    public function registrations(Request $request)
    {
        $q = Child::query();
        if ($request->filled('from')) $q->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to'))   $q->whereDate('created_at', '<=', $request->to);

        return response()->json([
            'count'    => (clone $q)->count(),
            'children' => $q->latest()->get(['id', 'child_code', 'full_name', 'program', 'created_at']),
        ]);
    }

    // GET /api/reports/participation   (attendance count per event)
    public function participation()
    {
        return response()->json(
            Event::withCount('attendances')->get(['id', 'name', 'status'])
        );
    }

    // GET /api/reports/benefits   (count per gift type)
    public function benefits()
    {
        return response()->json(
            BenefitType::withCount('benefits')->get(['id', 'name'])
        );
    }

        // GET /api/reports/dashboard
    public function dashboard()
    {
        $now   = now();
        $today = today();

        // Events that are not cancelled/archived
        $events = fn () => Event::whereNotIn('status', ['cancelled', 'archived']);

        $totalEvents = $events()->count();

        // To do = active events that have not started yet
        $toDo = $events()->where('status', 'active')->where(function ($q) use ($now, $today) {
            $q->where('start_at', '>', $now)
              ->orWhere(fn ($q) => $q->whereNull('start_at')->whereDate('event_date', '>', $today));
        })->count();

        // In progress = active events happening right now
        $inProgress = $events()->where('status', 'active')->where(function ($q) use ($now, $today) {
            $q->where(fn ($q) => $q->where('start_at', '<=', $now)
                    ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', $now)))
              ->orWhere(fn ($q) => $q->whereNull('start_at')->whereDate('event_date', $today));
        })->count();

        // Completed = everything else (finished, closed, or undated), so nothing is counted twice
        $completed = max(0, $totalEvents - $toDo - $inProgress);

        return response()->json([
            'cards' => [
                'children' => Child::where('status', 'active')->count(),
                'programs' => Program::whereNotIn('status', ['cancelled', 'archived'])->count(),
                'events'   => $totalEvents,
                'users'    => User::where('is_active', true)->count(),
            ],
            'activities' => [
                'to_do'       => $toDo,
                'in_progress' => $inProgress,
                'completed'   => $completed,
            ],
            'categories' => $this->categoryCounts(),
            'pending' => [
                'follow_ups' => FollowUp::where('status', 'pending')->count(),
            ],
        ]);
    }

        // Each child is counted once per category they are assigned to. Archived children are excluded.
    private function categoryCounts(): array
    {
        $rows = \App\Models\ChildCategory::query()
            ->join('children', 'children.id', '=', 'child_categories.child_id')
            ->where('children.status', 'active')
            ->groupBy('child_categories.category')
            ->selectRaw('child_categories.category as category, COUNT(DISTINCT child_categories.child_id) as total')
            ->pluck('total', 'category');

        $out = [];
        foreach (\App\Models\ChildCategory::OPTIONS as $category) {
            $out[$category] = (int) ($rows[$category] ?? 0);
        }

        return $out;
    }

        // GET /api/reports/monthly?month=2026-10
    public function monthly(Request $request)
    {
        $data = $request->validate(['month' => ['sometimes', 'date_format:Y-m']]);

        $start = isset($data['month'])
            ? \Illuminate\Support\Carbon::createFromFormat('!Y-m', $data['month'])->startOfMonth()
            : now()->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $now   = now();
        $today = today();

        // Events dated in this month (not cancelled / archived).
        // An event's date is start_at, or event_date when it has no start time.
        $inMonth = fn () => Event::whereNotIn('status', ['cancelled', 'archived'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                  ->orWhere(fn ($q) => $q->whereNull('start_at')
                      ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()]));
            });

        $scheduled = $inMonth()->count();

        $upcoming = $inMonth()->where('status', 'active')->where(function ($q) use ($now, $today) {
            $q->where('start_at', '>', $now)
              ->orWhere(fn ($q) => $q->whereNull('start_at')->whereDate('event_date', '>', $today));
        })->count();

        $inProgress = $inMonth()->where('status', 'active')->where(function ($q) use ($now, $today) {
            $q->where(fn ($q) => $q->where('start_at', '<=', $now)
                    ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', $now)))
              ->orWhere(fn ($q) => $q->whereNull('start_at')->whereDate('event_date', $today));
        })->count();

        $completed = max(0, $scheduled - $upcoming - $inProgress);

        return response()->json([
            'month'               => $start->format('Y-m'),
            'label'               => $start->format('F Y'),
            'children_registered' => Child::whereBetween('created_at', [$start, $end])->count(),
            'programs_created'    => Program::whereBetween('created_at', [$start, $end])->count(),
            'programs_updated'    => Program::whereBetween('updated_at', [$start, $end])
                                        ->whereNotBetween('created_at', [$start, $end])
                                        ->whereColumn('updated_at', '>', 'created_at')->count(),
            'events_scheduled'    => $scheduled,
            'events_held'         => $inProgress + $completed,
            'attendance_records'  => Attendance::whereBetween('created_at', [$start, $end])->count(),
            'questionnaire_submissions' => null,   // module not built yet: reported as unavailable, never as 0
            'activities'          => ['completed' => $completed, 'outstanding' => $upcoming + $inProgress],
            'completion_rate'     => $scheduled > 0 ? (int) round($completed / $scheduled * 100) : null,
        ]);
    }
}
