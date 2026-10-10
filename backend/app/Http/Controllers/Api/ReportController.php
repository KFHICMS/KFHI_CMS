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
}
