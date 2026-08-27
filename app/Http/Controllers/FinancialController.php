<?php

namespace App\Http\Controllers;

use App\Exports\FinancialsExport;
use App\Models\Booking;
use App\Models\SharedSession;
use App\Models\StaffActivityLog;
use App\Services\AnalyticsPeriod;
use App\Services\RevenueAnalyticsService;
use App\Support\TenantContext;
use App\Support\TransactionsQuery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FinancialController extends Controller
{
    private const STATUSES = ['all', 'completed', 'pending', 'confirmed', 'checked_in', 'cancelled', 'no_show'];

    private const SOURCES = ['all', 'direct_booking', 'shared_session', 'with_products'];

    public function __construct(private RevenueAnalyticsService $revenueAnalytics) {}

    public function index(Request $request): View
    {
        $owner = TenantContext::user();
        [$period, $periodKey, $customStart, $customEnd] = $this->resolvePeriod($request);

        return view('financials.index', [
            'owner' => $owner,
            'period' => $period,
            'periodKey' => $periodKey,
            'customStart' => $customStart,
            'customEnd' => $customEnd,
            'revenueToday' => $this->revenueAnalytics->totalRevenue($owner, AnalyticsPeriod::today()),
            'revenueThisWeek' => $this->revenueAnalytics->totalRevenue($owner, AnalyticsPeriod::thisWeek()),
            'revenueThisMonth' => $this->revenueAnalytics->totalRevenue($owner, AnalyticsPeriod::thisMonth()),
            'comparison' => $this->revenueAnalytics->revenueWithComparison($owner, $period),
            'bookingRevenue' => $this->revenueAnalytics->bookingRevenue($owner, $period),
            'saleRevenue' => $this->revenueAnalytics->saleRevenue($owner, $period),
            'averageBookingValue' => $this->revenueAnalytics->averageBookingValue($owner, $period),
            'trend' => $this->revenueAnalytics->dailyRevenueTrend($owner, $period),
            'byRoom' => $this->revenueAnalytics->revenueByRoom($owner, $period),
            'byRoomType' => $this->revenueAnalytics->revenueByRoomType($owner, $period),
            'byProduct' => $this->revenueAnalytics->revenueByProduct($owner, $period),
        ]);
    }

    public function transactions(Request $request): View
    {
        $owner = TenantContext::user();
        [$period, $periodKey, $customStart, $customEnd] = $this->resolvePeriod($request);
        $status = $this->resolveStatus($request);
        $source = $this->resolveSource($request);

        $bookings = TransactionsQuery::build($owner->id, $period, $status, $source)
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate(20)
            ->withQueryString();

        return view('financials.transactions.index', [
            'bookings' => $bookings,
            'period' => $period,
            'periodKey' => $periodKey,
            'customStart' => $customStart,
            'customEnd' => $customEnd,
            'status' => $status,
            'source' => $source,
        ]);
    }

    public function show(int $id): View
    {
        $booking = Booking::where('owner_id', TenantContext::id())
            ->with(['room.workspace', 'hotspotUser', 'sale.items.product', 'sharedSession'])
            ->findOrFail($id);

        return view('financials.transactions.show', [
            'booking' => $booking,
            'createdBy' => $this->resolveCreatedBy($booking),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $owner = TenantContext::user();
        [$period] = $this->resolvePeriod($request);
        $status = $this->resolveStatus($request);
        $source = $this->resolveSource($request);

        $filename = 'financials-'.$period->label.'-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(
            new FinancialsExport($owner, $period, $status, $source, $this->revenueAnalytics),
            $filename
        );
    }

    /**
     * Best-effort attribution (V1-lite, see architecture doc §6/§15). A
     * direct booking resolves via its own booking.created log entry. A
     * session-spawned booking has no such entry (SharedSessionController::
     * close() only logs shared_session.closed against the session itself),
     * so it's traced through to the session's shared_session.opened entry
     * instead. Returns null when nothing was ever logged for either path
     * (e.g. data predating the audit log).
     */
    private function resolveCreatedBy(Booking $booking): ?array
    {
        $log = $booking->sharedSession
            ? StaffActivityLog::where('subject_type', SharedSession::class)
                ->where('subject_id', $booking->sharedSession->id)
                ->where('action', 'shared_session.opened')
                ->first()
            : StaffActivityLog::where('subject_type', Booking::class)
                ->where('subject_id', $booking->id)
                ->where('action', 'booking.created')
                ->first();

        if (! $log) {
            return null;
        }

        return ['name' => $log->actor_name, 'type' => $log->actor_type];
    }

    /** @return array{0: AnalyticsPeriod, 1: string, 2: ?string, 3: ?string} */
    private function resolvePeriod(Request $request): array
    {
        $key = in_array($request->get('period'), ['today', 'this_week', 'this_month', 'custom'], true)
            ? $request->get('period')
            : 'this_month';

        $customStart = $request->get('start');
        $customEnd = $request->get('end');

        if ($key === 'custom') {
            $start = $this->parseDate($customStart) ?? now()->startOfMonth();
            $end = $this->parseDate($customEnd) ?? now();
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }

            return [AnalyticsPeriod::custom($start, $end), $key, $start->toDateString(), $end->toDateString()];
        }

        $period = match ($key) {
            'today' => AnalyticsPeriod::today(),
            'this_week' => AnalyticsPeriod::thisWeek(),
            default => AnalyticsPeriod::thisMonth(),
        };

        return [$period, $key, $customStart, $customEnd];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function resolveStatus(Request $request): string
    {
        return in_array($request->get('status'), self::STATUSES, true) ? $request->get('status') : 'completed';
    }

    private function resolveSource(Request $request): string
    {
        return in_array($request->get('source'), self::SOURCES, true) ? $request->get('source') : 'all';
    }
}
