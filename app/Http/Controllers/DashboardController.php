<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Child;
use App\Models\Message;
use App\Models\QrToken;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_children' => Child::query()->count(),
            'active_programs' => Child::query()
                ->whereNotNull('program')
                ->where('program', '<>', '')
                ->distinct()
                ->count('program'),
            'officers' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('name', 'field_officer'))
                ->count(),
            'recorded_actions' => AuditLog::query()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];

        $programCounts = Child::query()
            ->selectRaw("COALESCE(NULLIF(program, ''), 'Unassigned') AS program_name, COUNT(*) AS total")
            ->groupByRaw("COALESCE(NULLIF(program, ''), 'Unassigned')")
            ->orderBy('program_name')
            ->get();

        $monthExpression = match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb', 'sqlsrv' => 'MONTH(created_at)',
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            'pgsql' => 'CAST(EXTRACT(MONTH FROM created_at) AS INTEGER)',
            default => throw new \RuntimeException('Unsupported database driver for dashboard chart data.'),
        };

        $yearStart = now()->startOfYear();
        $monthlyActions = AuditLog::query()
            ->where('created_at', '>=', $yearStart)
            ->where('created_at', '<', $yearStart->copy()->addYear())
            ->selectRaw("{$monthExpression} AS month, COUNT(*) AS total")
            ->groupByRaw($monthExpression)
            ->pluck('total', 'month');

        $chartData = [
            'pie_labels' => $programCounts->pluck('program_name')->all(),
            'pie_values' => $programCounts->pluck('total')->map(fn ($total): int => (int) $total)->all(),
            'bar_labels' => collect(range(1, 12))
                ->map(fn (int $month): string => $yearStart->copy()->addMonths($month - 1)->format('M'))
                ->all(),
            'bar_values' => collect(range(1, 12))
                ->map(fn (int $month): int => (int) $monthlyActions->get($month, 0))
                ->all(),
        ];

        $messages = Message::query()
            ->with('sender:id,name')
            ->where('receiver_id', Auth::id())
            ->latest()
            ->get();

        return view('admin.dashboard', compact('stats', 'chartData', 'messages'));
    }

    public function childOfficerDashboard(): View
    {
        $monthStart = now()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonth();

        $stats = [
            'active_children' => Child::query()->where('status', 'active')->count(),
            'active_qr_codes' => QrToken::query()->where('is_active', true)->count(),
            'qr_scans' => AuditLog::query()
                ->where('action', 'SCAN_QR')
                ->where('created_at', '>=', $monthStart)
                ->where('created_at', '<', $nextMonthStart)
                ->count(),
            'updated_children' => Child::query()
                ->where('updated_at', '>=', $monthStart)
                ->where('updated_at', '<', $nextMonthStart)
                ->count(),
        ];

        $recentChildren = Child::query()
            ->latest()
            ->limit(5)
            ->get(['id', 'created_at', 'full_name', 'child_code']);

        return view('child_officer.childdashboard', compact('stats', 'recentChildren'));
    }

    public function fieldOfficerDashboard(): View
    {
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();

        $stats = [
            'programs' => Child::query()
                ->whereNotNull('program')
                ->where('program', '<>', '')
                ->distinct()
                ->count('program'),
            'children_registered_today' => Child::query()
                ->where('created_at', '>=', $today)
                ->where('created_at', '<', $tomorrow)
                ->count(),
            'qr_scans_today' => AuditLog::query()
                ->where('action', 'SCAN_QR')
                ->where('created_at', '>=', $today)
                ->where('created_at', '<', $tomorrow)
                ->count(),
            'qr_codes_generated_today' => QrToken::query()
                ->where('created_at', '>=', $today)
                ->where('created_at', '<', $tomorrow)
                ->count(),
        ];

        $recentChildren = Child::query()
            ->latest()
            ->limit(5)
            ->get(['id', 'created_at', 'full_name', 'child_code', 'photo_path']);

        return view('field_officer.fieldofficerdashboard', compact('stats', 'recentChildren'));
    }
}
