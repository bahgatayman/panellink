<?php

namespace App\Console\Commands;

use App\Models\Owner;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Backfill: enable each owner's plan default features. Idempotent and enable-only
 * (never removes admin-granted extras), so it is safe to re-run any time.
 */
class SyncOwnerPlanFeatures extends Command
{
    protected $signature = 'owners:sync-plan-features';

    protected $description = "Enable each owner's plan default features";

    public function handle(): int
    {
        $owners = Owner::with('plan')->whereNotNull('plan_id')->get();

        foreach ($owners as $owner) {
            $planFeatures = $owner->plan?->defaultFeatures() ?? [];
            $owner->applyPlanFeatures();
            $enabled = $owner->features()->pluck('key')->all();

            $this->line(sprintf(
                '%-24s plan=%-10s plan-features=[%s] enabled=[%s]%s',
                Str::limit($owner->business_name ?? $owner->name, 22),
                $owner->plan?->name ?? '—',
                implode(',', $planFeatures),
                implode(',', $enabled),
                empty($planFeatures) ? '  ⚠ plan has no default features set' : '',
            ));
        }

        $this->info("Synced {$owners->count()} owner(s).");

        return self::SUCCESS;
    }
}
