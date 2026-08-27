<?php

namespace App\Exports;

use App\Models\Owner;
use App\Services\AnalyticsPeriod;
use App\Services\RevenueAnalyticsService;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FinancialsExport implements WithMultipleSheets
{
    public function __construct(
        private Owner $owner,
        private AnalyticsPeriod $period,
        private string $status,
        private string $source,
        private RevenueAnalyticsService $revenueAnalytics,
    ) {}

    public function sheets(): array
    {
        return [
            new TransactionsSheet($this->owner, $this->period, $this->status, $this->source),
            new SummarySheet($this->owner, $this->period, $this->revenueAnalytics),
        ];
    }
}
