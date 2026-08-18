<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Room;
use App\Models\Sale;
use App\Models\SharedSession;
use App\Models\SpeedProfile;
use App\Models\Workspace;
use App\Services\BusinessHoursService;
use App\Services\HotspotSyncService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private HotspotSyncService $sync, private BusinessHoursService $businessHours) {}

    public function index(): View
    {
        $owner = Auth::guard('owner')->user();
        $ownerId = $owner->id;

        $totalUsers = HotspotUser::where('owner_id', $ownerId)->count();
        $activeUsers = HotspotUser::where('owner_id', $ownerId)->where('status', 'active')->count();
        $totalProfiles = SpeedProfile::where('owner_id', $ownerId)->count();

        $activeSessions = 0;
        $mikrotikError = null;

        try {
            $activeSessions = count($this->sync->activeUsers($owner));
        } catch (Exception $e) {
            $mikrotikError = $e->getMessage();
        }

        $viewData = [
            'owner' => $owner,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'totalProfiles' => $totalProfiles,
            'activeSessions' => $activeSessions,
            'mikrotikError' => $mikrotikError,
            'todayBookings' => 0,
            'pendingBookings' => 0,
            'monthRevenue' => 0,
            'productRevenue' => 0,
        ];

        // Working-hours "open now" badge — only meaningful once an owner has
        // actually configured hours; a badge for an unconfigured owner would
        // just be noise (BusinessHoursService treats them as unrestricted).
        if ($owner->hasFeature('workspace') || $owner->hasFeature('booking')) {
            $viewData['hasConfiguredWorkingHours'] = $this->businessHours->hasConfiguredHours($owner);
            $viewData['isOpenNow'] = $this->businessHours->isOpenNow($owner);
        }

        if ($owner->hasFeature('workspace')) {
            $viewData['totalWorkspaces'] = Workspace::where('owner_id', $ownerId)->count();
            $viewData['totalRooms'] = Room::where('owner_id', $ownerId)->count();
            $viewData['availableRooms'] = Room::where('owner_id', $ownerId)->where('is_available', true)->count();
        }

        if ($owner->hasFeature('booking')) {
            $viewData['todayBookings'] = Booking::where('owner_id', $ownerId)
                ->where('booking_date', today())
                ->where('status', '!=', 'cancelled')
                ->count();
            $viewData['pendingBookings'] = Booking::where('owner_id', $ownerId)
                ->where('status', 'pending')
                ->count();
            $viewData['monthRevenue'] = Booking::where('owner_id', $ownerId)
                ->where('status', 'completed')
                ->whereMonth('booking_date', now()->month)
                ->whereYear('booking_date', now()->year)
                ->sum('total_price');
            $viewData['openSharedSessions'] = SharedSession::where('owner_id', $ownerId)
                ->where('status', 'open')
                ->count();
        }

        // Product sales are a separate additive revenue stream (never folded into
        // bookings.total_price, so summing both here does not double-count).
        if ($owner->hasFeature('sales')) {
            $viewData['productRevenue'] = Sale::where('owner_id', $ownerId)
                ->where('status', 'completed')
                ->whereMonth('sold_at', now()->month)
                ->whereYear('sold_at', now()->year)
                ->sum('total');
        }

        return view('dashboard.index', $viewData);
    }
}
