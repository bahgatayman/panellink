<?php

namespace App\Services;

use App\Models\StaffActivityLog;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * Append one immutable event to the tenant's activity log. Called
     * explicitly at the end of a controller action (not via a model
     * observer — see StaffActivityLog for why) so only genuine
     * user-initiated actions are recorded, never internal/system writes.
     */
    public function log(string $action, Model $subject, ?string $description = null, array $metadata = []): void
    {
        $staff = auth('staff')->user();
        $actor = $staff ?? TenantContext::user();

        if (! $actor) {
            return;
        }

        StaffActivityLog::create([
            'owner_id' => TenantContext::id(),
            'staff_id' => $staff?->id,
            'actor_type' => $staff ? 'staff' : 'owner',
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }
}
