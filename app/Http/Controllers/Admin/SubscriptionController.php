<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function renew(Request $request, $ownerId, NotificationService $notifications)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'months'  => 'nullable|integer|min:1|max:24',
            'expires_at' => 'nullable|date|after:today',
            'notes'   => 'nullable|string|max:500',
        ]);

        if (!$request->filled('months') && !$request->filled('expires_at')) {
            return back()->withInput()->with('error', 'Please provide either months or a custom expiry date.');
        }

        $owner = Owner::findOrFail($ownerId);
        $plan  = Plan::findOrFail($validated['plan_id']);

        $startsFrom = ($owner->subscription_expires_at && $owner->subscription_expires_at->isFuture())
            ? $owner->subscription_expires_at
            : now();

        if ($request->filled('expires_at')) {
            $newExpiry = \Carbon\Carbon::parse($validated['expires_at'])->endOfDay();
            $months = max(1, round($startsFrom->diffInMonths($newExpiry)));
        } else {
            $months = (int) $validated['months'];
            $newExpiry = $startsFrom->copy()->addMonths($months);
        }

        $amountPaid = $plan->price_per_month * $months;

        $owner->update([
            'plan_id'                    => $plan->id,
            'subscription_starts_at'     => $owner->subscription_starts_at ?? now(),
            'subscription_expires_at'    => $newExpiry,
            'is_active'                  => true,
        ]);

        Subscription::create([
            'owner_id'    => $owner->id,
            'admin_id'    => auth('admin')->id(),
            'plan_id'     => $plan->id,
            'months'      => $months,
            'amount_paid' => $amountPaid,
            'starts_at'   => $startsFrom,
            'expires_at'  => $newExpiry,
            'notes'       => $validated['notes'] ?? null,
        ]);

        // Notify the owner in-app that their subscription was renewed, and clear
        // any stale expiry/expired alerts so they don't linger after renewal.
        $notifications->notify($owner, [
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

        $msg = "Subscription renewed: {$plan->name} plan — expires {$newExpiry->format('Y-m-d')}. ";
        $msg .= $plan->isFree() ? "Free plan — no charge." : "Amount: ج.م " . number_format($amountPaid, 2);

        return redirect("/admin/owners/{$owner->id}")->with('success', $msg);
    }
}
