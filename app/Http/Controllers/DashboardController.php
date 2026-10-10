<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Child;
use App\Models\Message;
use App\Models\QrToken;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Example data (replace with actual Eloquent queries like Child::count())
        $stats = [
            'total_children' => Child::count(),
            'active_programs' => Child::whereNotNull('program')->where('program', '!=', '')->distinct()->count('program'),
            'officers' => \App\Models\User::count(),
            'activities' => AuditLog::count(),
        ];

        $programs = Child::whereNotNull('program')->where('program', '!=', '')->select('program', \DB::raw('count(*) as count'))->groupBy('program')->pluck('count', 'program')->toArray();
        
        $chartData = [
            'pie_labels' => array_keys($programs) ?: ['No Data'],
            'pie_values' => array_values($programs) ?: [1],
            'bar_labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'bar_values' => [12, 19, 15, 25, 22, 30],
        ];

        // Fetch messages for the authenticated user (admin)
        $messages = Message::with('sender')->where('receiver_id', Auth::id())->orderBy('created_at', 'desc')->get();

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
            ->get(['id', 'created_at', 'full_name', 'child_code', 'photo_path']);

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
