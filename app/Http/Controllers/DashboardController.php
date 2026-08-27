<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Notification;
use App\Models\SpeedProfile;
use App\Services\AnalyticsPeriod;
use App\Services\BookingAnalyticsService;
use App\Services\BusinessHoursService;
use App\Services\CustomerAnalyticsService;
use App\Services\HotspotSyncService;
use App\Services\OccupancyAnalyticsService;
use App\Services\RevenueAnalyticsService;
use App\Support\TenantContext;
use Exception;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private HotspotSyncService $sync,
        private BusinessHoursService $businessHours,
        private RevenueAnalyticsService $revenueAnalytics,
        private OccupancyAnalyticsService $occupancyAnalytics,
        private BookingAnalyticsService $bookingAnalytics,
        private CustomerAnalyticsService $customerAnalytics,
    ) {}

    public function index(): View
    {
        $owner = TenantContext::user();
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

        // Dashboard route is never permission-gated (it's the mandatory
        // post-login landing page), but individual sections on it still
        // respect the same permissions their full pages would.
        $staff = auth('staff')->user();
        $canViewRevenue = ! $staff || $staff->hasPermission('reports.view');
        $canViewWorkspaces = ! $staff || $staff->hasPermission('workspaces.view');

        $periodKey = in_array(request('period'), ['today', 'week', 'month'], true) ? request('period') : 'today';
        $period = match ($periodKey) {
            'week' => AnalyticsPeriod::thisWeek(),
            'month' => AnalyticsPeriod::thisMonth(),
            default => AnalyticsPeriod::today(),
        };

        $viewData = [
            'owner' => $owner,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'totalProfiles' => $totalProfiles,
            'activeSessions' => $activeSessions,
            'mikrotikError' => $mikrotikError,
            'canViewRevenue' => $canViewRevenue,
            'canViewWorkspaces' => $canViewWorkspaces,
            // The plan/feature list is billing-facing tenant info, not an
            // operational concern for staff — Owner-only, no permission to grant.
            'isStaff' => (bool) $staff,
            'periodKey' => $periodKey,
        ];

        // Working-hours "open now" badge — only meaningful once an owner has
        // actually configured hours; a badge for an unconfigured owner would
        // just be noise (BusinessHoursService treats them as unrestricted).
        if ($owner->hasFeature('workspace') || $owner->hasFeature('booking')) {
            $viewData['hasConfiguredWorkingHours'] = $this->businessHours->hasConfiguredHours($owner);
            $viewData['isOpenNow'] = $this->businessHours->isOpenNow($owner);
        }

        // Revenue combines booking + product/service sales (see
        // RevenueAnalyticsService); meaningless for an owner with neither
        // feature enabled, so the whole block is skipped rather than showing
        // an always-zero card.
        $viewData['showRevenue'] = $canViewRevenue && ($owner->hasFeature('booking') || $owner->hasFeature('sales'));
        if ($viewData['showRevenue']) {
            $viewData['revenueToday'] = $this->revenueAnalytics->totalRevenue($owner, AnalyticsPeriod::today());
            $viewData['revenueThisMonth'] = $this->revenueAnalytics->totalRevenue($owner, AnalyticsPeriod::thisMonth());
            $viewData['revenueComparison'] = $this->revenueAnalytics->revenueWithComparison($owner, $period);
            $viewData['revenueTrend'] = $this->revenueAnalytics->dailyRevenueTrend($owner, $period);
        }

        if ($owner->hasFeature('booking')) {
            $viewData['todayBookings'] = $this->bookingAnalytics->bookingsCount($owner, AnalyticsPeriod::today(), excludeCancelled: true);
            $viewData['statusBreakdown'] = $this->bookingAnalytics->statusBreakdown($owner, $period);
            $viewData['peakHours'] = $this->bookingAnalytics->peakHours($owner, $period);
            $viewData['todaysSchedule'] = $this->bookingAnalytics->todaysSchedule($owner);
        }

        $viewData['showWorkspace'] = $owner->hasFeature('workspace') && $canViewWorkspaces;
        if ($viewData['showWorkspace']) {
            $viewData['occupancy'] = $this->occupancyAnalytics->currentOccupancy($owner);
            $viewData['availableRoomsNow'] = $this->occupancyAnalytics->availableRoomsNow($owner);

            if ($owner->hasFeature('booking')) {
                // Ranked highest-utilization-first, the same ordering rule
                // BookingAnalyticsService uses internally for most/least
                // utilized room, so the view never re-derives a sort key.
                $viewData['roomUtilization'] = $this->bookingAnalytics
                    ->roomUtilization($owner, $period, $this->businessHours)
                    ->sortByDesc(fn (array $r) => $r['utilization_percent'] ?? $r['hours_booked'])
                    ->values();
            }
        }

        // "Customers" here is HotspotUser (the merged member entity) — shown
        // wherever that entity is otherwise reachable (hotspot or booking),
        // matching the existing /users nav visibility rule.
        if ($owner->hasFeature('hotspot') || $owner->hasFeature('booking')) {
            $viewData['newCustomers'] = $this->customerAnalytics->newCustomers($owner, $period);
        }

        // Reuses the existing Notification feed (generated by
        // NotificationService, refreshed by the layout's own view composer)
        // rather than recomputing alert logic here.
        $viewData['needsAttentionCount'] = Notification::forOwner($ownerId)->unread()->count();
        $viewData['needsAttentionItems'] = Notification::forOwner($ownerId)->unread()->latest()->take(10)->get();

        return view('dashboard.index', $viewData);
    }
}
