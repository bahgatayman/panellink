<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Grant access when the acting staff member has ANY of the given
     * permission keys. Mirrors CheckFeature's OR semantics for multi-key
     * usage (`permission:a,b`). An Owner session always passes — this gate
     * only ever restricts staff, never the tenant root.
     */
    public function handle(Request $request, Closure $next, string ...$permissionKeys): Response
    {
        if (auth('owner')->check()) {
            return $next($request);
        }

        $staff = auth('staff')->user();

        if (! $staff) {
            return redirect()->route('login');
        }

        foreach ($permissionKeys as $key) {
            if ($staff->hasPermission($key)) {
                return $next($request);
            }
        }

        return back()->with('permission_denied', __('app.msg.permission_denied'));
    }
}
