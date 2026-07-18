<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\Owner;

class NotificationService
{
    /**
     * Create a notification for an owner. When a reference is given the alert is
     * idempotent — it is created once per (owner, reference) and never duplicated
     * or re-marked unread on subsequent runs.
     */
    public function notify(Owner $owner, array $data): Notification
    {
        $attributes = [
            'type'       => $data['type'] ?? 'general',
            'level'      => $data['level'] ?? 'info',
            'title'      => $data['title'],
            'body'       => $data['body'] ?? null,
            'action_url' => $data['action_url'] ?? null,
        ];

        if (! empty($data['reference'])) {
            return Notification::firstOrCreate(
                ['owner_id' => $owner->id, 'reference' => $data['reference']],
                $attributes,
            );
        }

        return Notification::create($attributes + [
            'owner_id'  => $owner->id,
            'reference' => null,
        ]);
    }

    /**
     * Recompute all system-generated alerts for a single owner. Safe to call
     * repeatedly — every alert here is keyed by an idempotent reference.
     */
    public function refreshForOwner(Owner $owner): void
    {
        $this->checkSubscription($owner);
        $this->checkPlanUsage($owner);

        if ($owner->hasFeature('booking')) {
            $this->checkBookings($owner);
        }
    }

    protected function checkSubscription(Owner $owner): void
    {
        $status = $owner->subscriptionStatus();
        $date   = $owner->subscription_expires_at?->toDateString() ?? 'none';

        if ($status === 'expiring_soon') {
            $this->notify($owner, [
                'type'       => 'subscription_expiring',
                'level'      => 'warning',
                'reference'  => "subscription_expiring:{$date}",
                'title'      => __('app.notif.gen.sub_expiring_title'),
                'body'       => __('app.notif.gen.sub_expiring_body', ['days' => $owner->daysUntilExpiry()]),
                'action_url' => '/profile',
            ]);
        }

        if ($status === 'expired') {
            $this->notify($owner, [
                'type'       => 'subscription_expired',
                'level'      => 'danger',
                'reference'  => "subscription_expired:{$date}",
                'title'      => __('app.notif.gen.sub_expired_title'),
                'body'       => __('app.notif.gen.sub_expired_body'),
                'action_url' => '/subscription/expired',
            ]);
        }
    }

    protected function checkPlanUsage(Owner $owner): void
    {
        if (! $owner->plan) {
            return;
        }

        if ($owner->remainingUserSlots() === 0 && $owner->plan->max_members > 0) {
            $this->notify($owner, [
                'type'      => 'plan_limit',
                'level'     => 'warning',
                'reference' => "plan_limit:{$owner->plan_id}:{$owner->plan->max_members}",
                'title'     => __('app.notif.gen.plan_limit_title'),
                'body'      => __('app.notif.gen.plan_limit_body', ['max' => $owner->plan->max_members]),
            ]);
        }
    }

    protected function checkBookings(Owner $owner): void
    {
        // Bookings scheduled for today that are still upcoming/confirmed.
        $todays = Booking::where('owner_id', $owner->id)
            ->whereDate('booking_date', today())
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('room')
            ->get();

        foreach ($todays as $booking) {
            $this->notify($owner, [
                'type'       => 'booking_today',
                'level'      => 'info',
                'reference'  => "booking_today:{$booking->id}:" . today()->toDateString(),
                'title'      => __('app.notif.gen.booking_today_title'),
                'body'       => __('app.notif.gen.booking_today_body', [
                    'room' => $booking->room?->name ?? '—',
                    'time' => $booking->timeRange(),
                ]),
                'action_url' => "/bookings/{$booking->id}",
            ]);
        }

        // Pending bookings on a future date still need confirmation.
        $pending = Booking::where('owner_id', $owner->id)
            ->where('status', 'pending')
            ->whereDate('booking_date', '>', today())
            ->with('room')
            ->get();

        foreach ($pending as $booking) {
            $this->notify($owner, [
                'type'       => 'booking_pending',
                'level'      => 'warning',
                'reference'  => "booking_pending:{$booking->id}",
                'title'      => __('app.notif.gen.booking_pending_title'),
                'body'       => __('app.notif.gen.booking_pending_body', [
                    'room' => $booking->room?->name ?? '—',
                    'date' => $booking->booking_date->format('M j'),
                ]),
                'action_url' => "/bookings/{$booking->id}",
            ]);
        }
    }
}
