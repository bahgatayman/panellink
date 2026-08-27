<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\Sale;
use App\Models\SharedSession;
use App\Models\Workspace;
use App\Services\AnalyticsPeriod;
use App\Services\RevenueAnalyticsService;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevenueAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private RevenueAnalyticsService $revenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
        $this->revenue = app(RevenueAnalyticsService::class);
    }

    private function owner(): Owner
    {
        $plan = Plan::create([
            'name' => 'Test', 'slug' => 'test-'.uniqid(), 'max_members' => 100,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1,
            'features' => ['workspace', 'booking', 'sales'],
            'max_workspaces' => 0, 'max_rooms' => 0, 'max_products' => 0,
        ]);

        $owner = Owner::create([
            'name' => 'Owner', 'email' => 'o'.uniqid().'@t.local', 'password' => 'secret123',
            'business_name' => 'Space', 'plan_id' => $plan->id, 'is_active' => true,
            'subscription_starts_at' => now(), 'subscription_expires_at' => now()->addMonth(),
        ]);

        foreach ($plan->features as $key) {
            $owner->enableFeature($key);
        }

        return $owner;
    }

    private function room(Owner $owner): Room
    {
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);

        return Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Room A',
            'type' => 'meeting', 'capacity' => 1, 'price_per_hour' => 50,
        ]);
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Member', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    private function booking(Owner $owner, Room $room, string $date, float $totalPrice, string $status = 'completed'): Booking
    {
        return Booking::create([
            'owner_id' => $owner->id, 'room_id' => $room->id,
            'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 1,
            'booking_date' => $date, 'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => $totalPrice / 2, 'total_hours' => 2, 'total_price' => $totalPrice,
            'status' => $status,
        ]);
    }

    private function sale(Owner $owner, string $soldAt, float $total, string $status = 'completed', ?int $bookingId = null): Sale
    {
        return Sale::create([
            'owner_id' => $owner->id, 'booking_id' => $bookingId,
            'status' => $status, 'subtotal' => $total, 'total' => $total, 'sold_at' => $soldAt,
        ]);
    }

    // --- bookingRevenue / saleRevenue / totalRevenue ---

    public function test_booking_revenue_only_counts_completed_bookings_in_period(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);

        $this->booking($owner, $room, '2026-08-10', 100.0, 'completed');
        $this->booking($owner, $room, '2026-08-10', 50.0, 'pending');
        $this->booking($owner, $room, '2026-08-10', 75.0, 'cancelled');
        $this->booking($owner, $room, '2026-08-10', 30.0, 'no_show');
        $this->booking($owner, $room, '2026-09-01', 999.0, 'completed'); // outside period

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertSame(100.0, $this->revenue->bookingRevenue($owner, $period));
    }

    public function test_sale_revenue_only_counts_completed_sales_in_period(): void
    {
        $owner = $this->owner();

        $this->sale($owner, '2026-08-10 09:00:00', 20.0, 'completed');
        $this->sale($owner, '2026-08-10 09:00:00', 15.0, 'open');
        $this->sale($owner, '2026-08-10 09:00:00', 5.0, 'cancelled');

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertSame(20.0, $this->revenue->saleRevenue($owner, $period));
    }

    public function test_total_revenue_adds_booking_and_sale_revenue(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);

        $this->booking($owner, $room, '2026-08-10', 100.0);
        $this->sale($owner, '2026-08-10 09:00:00', 20.0);

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertSame(120.0, $this->revenue->totalRevenue($owner, $period));
    }

    public function test_revenue_is_scoped_to_the_owner(): void
    {
        $owner = $this->owner();
        $other = $this->owner();
        $room = $this->room($owner);
        $otherRoom = $this->room($other);

        $this->booking($owner, $room, '2026-08-10', 100.0);
        $this->booking($other, $otherRoom, '2026-08-10', 500.0);
        $this->sale($owner, '2026-08-10 09:00:00', 20.0);
        $this->sale($other, '2026-08-10 09:00:00', 200.0);

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertSame(120.0, $this->revenue->totalRevenue($owner, $period));
    }

    // --- Double-counting: closed SharedSession vs its auto-created Booking ---

    /**
     * Simulates exactly what SharedSessionController::close() produces: a
     * closed SharedSession and a completed Booking carrying the identical
     * total_price, linked via shared_sessions.booking_id. RevenueAnalyticsService
     * must report only the Booking's amount — never that figure twice.
     */
    public function test_closed_shared_session_revenue_is_not_counted_twice(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $member = $this->member($owner);

        $booking = Booking::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'party_size' => 1, 'booking_date' => '2026-08-10', 'start_time' => '10:00', 'end_time' => '10:30',
            'price_per_hour' => 60, 'total_hours' => 0.5, 'total_price' => 30.0,
            'status' => 'completed', 'notes' => 'Auto-created from shared session.',
        ]);

        SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'session_date' => '2026-08-10', 'start_time' => '10:00',
            'opened_at' => '2026-08-10 10:00:00', 'closed_at' => '2026-08-10 10:30:00',
            'total_minutes' => 30, 'total_price' => 30.0, 'status' => 'closed',
            'booking_id' => $booking->id,
        ]);

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertSame(30.0, $this->revenue->bookingRevenue($owner, $period));
        $this->assertSame(30.0, $this->revenue->totalRevenue($owner, $period));
    }

    // --- revenueWithComparison ---

    public function test_revenue_with_comparison_computes_delta_and_percent(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);

        $this->booking($owner, $room, '2026-08-15', 150.0); // this month
        $this->booking($owner, $room, '2026-07-15', 100.0); // previous month (equal length shift)

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));
        $result = $this->revenue->revenueWithComparison($owner, $period);

        $this->assertSame(150.0, $result['current']);
        $this->assertSame(100.0, $result['previous']);
        $this->assertSame(50.0, $result['change']);
        $this->assertSame(50.0, $result['changePercent']);
    }

    public function test_revenue_with_comparison_percent_is_null_when_previous_is_zero(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $this->booking($owner, $room, '2026-08-15', 150.0);

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));
        $result = $this->revenue->revenueWithComparison($owner, $period);

        $this->assertSame(0.0, $result['previous']);
        $this->assertNull($result['changePercent']);
    }

    // --- averageBookingValue ---

    public function test_average_booking_value_is_null_with_no_completed_bookings(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $this->booking($owner, $room, '2026-08-10', 100.0, 'pending');

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertNull($this->revenue->averageBookingValue($owner, $period));
    }

    public function test_average_booking_value_divides_revenue_by_completed_count(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $this->booking($owner, $room, '2026-08-10', 100.0);
        $this->booking($owner, $room, '2026-08-11', 50.0);
        $this->booking($owner, $room, '2026-08-12', 999.0, 'cancelled');

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertSame(75.0, $this->revenue->averageBookingValue($owner, $period));
    }

    // --- dailyRevenueTrend ---

    public function test_daily_revenue_trend_zero_fills_and_combines_both_sources(): void
    {
        $owner = $this->owner();
        $room = $this->room($owner);
        $this->booking($owner, $room, '2026-08-10', 100.0);
        $this->sale($owner, '2026-08-12 09:00:00', 20.0);

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-10'), Carbon::parse('2026-08-12'));
        $trend = $this->revenue->dailyRevenueTrend($owner, $period);

        $this->assertSame([
            '2026-08-10' => 100.0,
            '2026-08-11' => 0.0,
            '2026-08-12' => 20.0,
        ], $trend);
    }
}
