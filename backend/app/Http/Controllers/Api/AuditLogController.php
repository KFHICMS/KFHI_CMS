<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    // GET /api/audit-logs?user_id=&action=&from=&to=&per_page=
    public function index(Request $request)
    {
        $q = AuditLog::with('user:id,name,email');

        if ($request->filled('user_id')) $q->where('user_id', $request->user_id);
        if ($request->filled('action'))  $q->where('action', $request->action);
        if ($request->filled('from'))    $q->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to'))      $q->whereDate('created_at', '<=', $request->to);

        return response()->json(
            $q->latest('id')->paginate($request->integer('per_page', 25))
        );
    }
}
