<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffActivityLog;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffActivityController extends Controller
{
    public function show(Request $request, Staff $staff): View
    {
        abort_unless($staff->owner_id === TenantContext::id(), 404);

        $range = $request->get('range', 'month');
        $from = match ($range) {
            'week' => now()->startOfWeek(),
            'all' => null,
            default => now()->startOfMonth(),
        };

        $query = StaffActivityLog::where('staff_id', $staff->id);
        if ($from) {
            $query->where('created_at', '>=', $from);
        }

        // Every number on this page is a live COUNT/GROUP BY against the
        // append-only log — nothing here is a stored, incrementable counter.
        $counts = (clone $query)
            ->selectRaw('action, count(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action');

        $activity = (clone $query)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('staff.activity', compact('staff', 'counts', 'activity', 'range'));
    }
}
