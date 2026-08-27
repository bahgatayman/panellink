<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStaffActive
{
    /**
     * Re-verifies, on every request, that a logged-in staff session is still
     * allowed in: the staff account itself must be active, and so must its
     * owner (both the owner's own is_active flag and their subscription).
     * Laravel re-fetches the authenticatable model from the DB each request,
     * so disabling a staff member (or their owner) takes effect on the very
     * next request — no session polling/push mechanism needed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $staff = auth('staff')->user();

        if (! $staff) {
            return $next($request);
        }

        if ($request->routeIs('owner.logout')) {
            return $next($request);
        }

        if (! $staff->is_active || ! $staff->owner || ! $staff->owner->is_active) {
            return $this->forceLogout($request, 'Your account has been disabled. Contact your manager.');
        }

        if (! $staff->owner->isSubscriptionActive()) {
            return $this->forceLogout($request, 'Your workspace\'s subscription is inactive. Contact your manager.');
        }

        return $next($request);
    }

    private function forceLogout(Request $request, string $message): Response
    {
        auth('staff')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
