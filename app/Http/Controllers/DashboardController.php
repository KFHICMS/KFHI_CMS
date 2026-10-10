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
            ->groupBy('program_name')
            ->orderBy('program_name')
            ->get();

        $monthExpression = match (DB::connection()->getDriverName()) {
            'mysql', 'mariadb', 'sqlsrv' => 'MONTH(created_at)',
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            'pgsql' => 'CAST(EXTRACT(MONTH FROM created_at) AS INTEGER)',
            default => throw new \RuntimeException('Unsupported database driver for dashboard chart data.'),
        };

        $monthlyActions = AuditLog::query()
            ->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
            ->selectRaw("{$monthExpression} AS month, COUNT(*) AS total")
            ->groupBy('month')
            ->pluck('total', 'month');

        $chartData = [
            'pie_labels' => $programCounts->pluck('program_name')->all(),
            'pie_values' => $programCounts->pluck('total')->map(fn ($total): int => (int) $total)->all(),
            'bar_labels' => collect(range(1, 12))
                ->map(fn (int $month): string => now()->startOfYear()->addMonths($month - 1)->format('M'))
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

    public function childOfficer(): View
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $stats = [
            'active_children' => Child::query()->where('status', 'active')->count(),
            'active_qr_codes' => QrToken::query()->where('is_active', true)->count(),
            'qr_scans' => AuditLog::query()
                ->where('action', 'SCAN_QR')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->count(),
            'updated_children' => Child::query()
                ->whereBetween('updated_at', [$monthStart, $monthEnd])
                ->count(),
        ];

        $recentChildren = Child::query()
            ->select('created_at')
            ->latest()
            ->limit(5)
            ->get();

        return view('child_officer.childdashboard', compact('stats', 'recentChildren'));
    }

    public function fieldOfficer(): View
    {
        $todayStart = today();
        $todayEnd = today()->endOfDay();

        $stats = [
            'programs' => Child::query()
                ->whereNotNull('program')
                ->where('program', '<>', '')
                ->distinct()
                ->count('program'),
            'children_registered_today' => Child::query()
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->count(),
            'qr_scans_today' => AuditLog::query()
                ->where('action', 'SCAN_QR')
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->count(),
            'qr_codes_generated_today' => AuditLog::query()
                ->where('action', 'GENERATE_QR')
                ->whereBetween('created_at', [$todayStart, $todayEnd])
                ->count(),
        ];

        $recentChildren = Child::query()
            ->select('created_at')
            ->latest()
            ->limit(5)
            ->get();

        return view('field_officer.fieldofficerdashboard', compact('stats', 'recentChildren'));
    }
}
