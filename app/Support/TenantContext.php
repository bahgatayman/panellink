<?php

namespace App\Support;

use App\Models\Owner;

/**
 * Single choke point for "which Owner does this request belong to" — the
 * only thing that changed by introducing staff accounts is that an
 * authenticated actor can now be an Owner OR one of that Owner's Staff
 * members, both of which resolve to the same tenant scope.
 *
 * Every controller/service that used to call auth('owner')->id()/->user()
 * for tenant scoping should call TenantContext::id()/::user() instead, so
 * the same query-scoping code works unmodified for staff sessions.
 */
class TenantContext
{
    public static function user(): ?Owner
    {
        if (auth('staff')->check()) {
            return auth('staff')->user()->owner;
        }

        return auth('owner')->user();
    }

    public static function id(): ?int
    {
        return static::user()?->id;
    }
}
