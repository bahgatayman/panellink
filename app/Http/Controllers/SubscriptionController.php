<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SubscriptionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    /**
     * Shown by CheckSubscription when the owner's subscription lapsed. Lists the
     * plans so they can ask to renew instead of hitting a dead end.
     */
    public function expired(): View|RedirectResponse
    {
        if (auth('owner')->user()->isSubscriptionActive()) {
            return redirect('/dashboard');
        }

        return view('subscription.expired', $this->planPickerData());
    }

    /**
     * Same plan picker for owners whose subscription is still running — renew
     * early or move to a different plan.
     */
    public function plans(): View
    {
        return view('subscription.plans', $this->planPickerData());
    }

    public function requestRenewal(Request $request): RedirectResponse
    {
        $owner = auth('owner')->user();

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'months' => 'required|integer|min:1|max:24',
            'note' => 'nullable|string|max:500',
        ]);

        $plan = Plan::where('id', $validated['plan_id'])
            ->where('is_active', true)
            ->firstOrFail();

        // Free plan is self-serve: no payment, so no admin approval and no paid
        // Subscription record — just activate it and drop the owner into the panel.
        // Renewing early stacks on any remaining time.
        if ($plan->isFree()) {
            $startsFrom = ($owner->subscription_expires_at && $owner->subscription_expires_at->isFuture())
                ? $owner->subscription_expires_at
                : now();

            $owner->update([
                'plan_id' => $plan->id,
                'subscription_starts_at' => $owner->subscription_starts_at ?? now(),
                'subscription_expires_at' => $startsFrom->copy()->addMonths((int) $validated['months']),
                'is_active' => true,
            ]);
            $owner->applyPlanFeatures();

            return redirect('/dashboard')->with('success', __('app.subscription.free_activated'));
        }

        // Paid plans go through admin approval — one open request at a time, else an
        // impatient owner queues several up and an admin approves the same one twice.
        if ($this->pendingRequest()) {
            return back()->with('error', __('app.subscription.request_already_pending'));
        }

        SubscriptionRequest::create([
            'owner_id' => $owner->id,
            'plan_id' => $plan->id,
            'months' => $validated['months'],
            'amount' => $plan->price_per_month * $validated['months'],
            'status' => SubscriptionRequest::STATUS_PENDING,
            'note' => $validated['note'] ?? null,
        ]);

        return back()->with('success', __('app.subscription.request_sent'));
    }

    public function cancelRequest(int $id): RedirectResponse
    {
        $pending = SubscriptionRequest::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->pending()
            ->firstOrFail();

        $pending->update(['status' => SubscriptionRequest::STATUS_CANCELLED]);

        return back()->with('success', __('app.subscription.request_cancelled'));
    }

    /** Shared payload for both plan screens. */
    private function planPickerData(): array
    {
        $owner = auth('owner')->user()->load('plan');

        return [
            'owner' => $owner,
            'plans' => Plan::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('price_per_month')
                ->get(),
            'pendingRequest' => $this->pendingRequest(),
            'recentRequests' => SubscriptionRequest::where('owner_id', $owner->id)
                ->with('plan')
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

    private function pendingRequest(): ?SubscriptionRequest
    {
        return SubscriptionRequest::where('owner_id', auth('owner')->id())
            ->with('plan')
            ->pending()
            ->latest()
            ->first();
    }
}
