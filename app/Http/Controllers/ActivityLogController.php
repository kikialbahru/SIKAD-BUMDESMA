<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:50'],
        ]);

        $query = ActivityLog::with('user')->latest();
        $query->when($filters['action'] ?? null, fn ($q, $a) => $q->where('action', $a));
        $query->when($filters['search'] ?? null, fn ($q, $s) => $q->whereHas('user',
            fn ($uq) => $uq->where('name', 'like', '%'.$s.'%')->orWhere('email', 'like', '%'.$s.'%')));

        return view('activity-logs.index', [
            'logs' => $query->paginate(20)->withQueryString(),
            'filters' => $filters,
            'actions' => ActivityLog::distinct()->orderBy('action')->pluck('action'),
            'title' => 'Log Aktivitas',
        ]);
    }
}
