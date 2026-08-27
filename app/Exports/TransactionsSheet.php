<?php

namespace App\Exports;

use App\Models\Owner;
use App\Services\AnalyticsPeriod;
use App\Support\TransactionsQuery;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * One row per Booking — same TransactionsQuery the Transactions list page
 * uses, so the export can never show a different set of rows (or a second
 * row for a session-derived booking / a booking's attached sale) than what
 * the Owner sees on screen for the same filters.
 */
class TransactionsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private Owner $owner,
        private AnalyticsPeriod $period,
        private string $status,
        private string $source,
    ) {}

    public function headings(): array
    {
        return [
            'Date', 'Booking #', 'Customer', 'Room', 'Room Type',
            'Start Time', 'End Time', 'Hours',
            'Room Revenue', 'Products Revenue', 'Discount', 'Tax', 'Grand Total',
            'Status', 'Origin', 'Party Size',
        ];
    }

    public function collection(): Collection
    {
        $bookings = TransactionsQuery::build($this->owner->id, $this->period, $this->status, $this->source)
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        return $bookings->map(function ($booking) {
            $sale = $booking->sale && $booking->sale->status === 'completed' ? $booking->sale : null;

            return [
                $booking->booking_date->toDateString(),
                '#'.str_pad((string) $booking->id, 4, '0', STR_PAD_LEFT),
                $booking->hotspotUser?->name ?? '—',
                $booking->room?->name ?? '—',
                $booking->room?->typeLabel() ?? '—',
                $booking->start_time,
                $booking->end_time,
                (float) $booking->total_hours,
                (float) $booking->total_price,
                $sale ? (float) $sale->total : 0.0,
                $sale ? (float) $sale->discount_total : 0.0,
                $sale ? (float) $sale->tax_total : 0.0,
                $booking->grandTotal(),
                $booking->statusLabel(),
                $booking->sharedSession ? 'Shared Session #'.$booking->sharedSession->id : 'Direct Booking',
                $booking->party_size,
            ];
        });
    }

    public function title(): string
    {
        return 'Transactions';
    }
}
