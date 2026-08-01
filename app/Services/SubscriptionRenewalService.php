<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Subscription;
use Carbon\Carbon;

/**
 * The single place a subscription gets extended.
 *
 * Used by the admin renew form and by approval of an owner's renewal request,
 * so both produce identical Subscription records, expiry maths and notifications.
 */
class SubscriptionRenewalService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * Extend (or start) an owner's subscription and record the payment.
     *
     * Renewing early stacks on the remaining time rather than discarding it:
     * the new term starts at the current expiry when that is still in the future.
     *
     * @param  int|null  $months     Term length. Ignored when $until is given.
     * @param  Carbon|null  $until   Explicit expiry date, for admin overrides.
     */
    public function renew(
        Owner $owner,
        Plan $plan,
        ?int $months = null,
        ?Admin $admin = null,
        ?string $notes = null,
        ?Carbon $until = null,
    ): Subscription {
        $startsFrom = ($owner->subscription_expires_at && $owner->subscription_expires_at->isFuture())
            ? $owner->subscription_expires_at
            : now();

        if ($until) {
            $newExpiry = $until->copy()->endOfDay();
            $months = max(1, (int) round($startsFrom->diffInMonths($newExpiry)));
        } else {
            $months = max(1, (int) $months);
            $newExpiry = $startsFrom->copy()->addMonths($months);
        }

        $amountPaid = $plan->price_per_month * $months;

        $owner->update([
            'plan_id'                 => $plan->id,
            'subscription_starts_at'  => $owner->subscription_starts_at ?? now(),
            'subscription_expires_at' => $newExpiry,
            'is_active'               => true,
        ]);

        $subscription = Subscription::create([
            'owner_id'    => $owner->id,
            'admin_id'    => $admin?->id,
            'plan_id'     => $plan->id,
            'months'      => $months,
            'amount_paid' => $amountPaid,
            'starts_at'   => $startsFrom,
            'expires_at'  => $newExpiry,
            'notes'       => $notes,
        ]);

        // Tell the owner in-app, and clear the now-stale expiry warnings so they
        // don't linger on the dashboard after a successful renewal.
        $this->notifications->notify($owner, [
            'type'       => 'subscription_renewed',
            'level'      => 'success',
            'reference'  => "subscription_renewed:{$newExpiry->toDateString()}",
            'title'      => __('app.notif.gen.sub_renewed_title'),
            'body'       => __('app.notif.gen.sub_renewed_body', [
                'plan' => $plan->name,
                'date' => $newExpiry->format('Y-m-d'),
            ]),
            'action_url' => '/profile',
        ]);

        $owner->notifications()
            ->whereIn('type', ['subscription_expiring', 'subscription_expired'])
            ->delete();

        return $subscription;
    }
}
