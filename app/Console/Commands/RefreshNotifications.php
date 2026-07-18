<?php

namespace App\Console\Commands;

use App\Models\Owner;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class RefreshNotifications extends Command
{
    protected $signature = 'notifications:refresh';

    protected $description = 'Generate system alerts (subscription expiry, booking reminders, plan limits) for all active owners';

    public function handle(NotificationService $service): int
    {
        $count = 0;

        Owner::where('is_active', true)
            ->with('plan')
            ->chunk(100, function ($owners) use ($service, &$count) {
                foreach ($owners as $owner) {
                    $service->refreshForOwner($owner);
                    $count++;
                }
            });

        $this->info("Refreshed notifications for {$count} owner(s).");

        return self::SUCCESS;
    }
}
