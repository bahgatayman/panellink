<?php

namespace App\Services;

use App\Models\HotspotUser;
use App\Models\Owner;

class CustomerAnalyticsService
{
    public function totalCustomers(Owner $owner): int
    {
        return HotspotUser::where('owner_id', $owner->id)->count();
    }

    /**
     * HotspotUser rows created within the period. HotspotUser is also the
     * hotspot/network-login entity, not a booking-only customer list — this
     * count can include hotspot signups with no bookings/sales, so callers
     * should label it as "new people in the system," not "new paying
     * customers."
     */
    public function newCustomers(Owner $owner, AnalyticsPeriod $period): int
    {
        return HotspotUser::where('owner_id', $owner->id)
            ->whereBetween('created_at', [$period->start, $period->end])
            ->count();
    }
}
