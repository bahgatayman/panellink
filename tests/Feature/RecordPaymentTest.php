<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\Workspace;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BookingController::recordPayment() — the "collect the rest of the cash
 * later" action a booking's detail page needs so a booking that wasn't paid
 * in full at creation can still eventually be counted as fully paid revenue.
 */
class RecordPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
    }

    private function owner(): Owner
    {
        $plan = Plan::create([
            'name' => 'Test', 'slug' => 'test-'.uniqid(), 'max_members' => 100,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1,
            'features' => ['workspace', 'booking'],
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
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Room',
            'type' => 'meeting', 'capacity' => 4, 'price_per_hour' => 100,
        ]);
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Member', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    private function booking(Owner $owner, Room $room, array $overrides = []): Booking
    {
        return Booking::create(array_merge([
            'owner_id' => $owner->id, 'room_id' => $room->id,
            'hotspot_user_id' => $this->member($owner)->id,
            'party_size' => 1, 'booking_date' => today()->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 100, 'total_hours' => 2, 'total_price' => 200,
            'amount_paid' => 50, 'payment_status' => 'partial',
            'status' => 'confirmed',
        ], $overrides));
    }

    public function test_it_adds_to_the_amount_already_paid(): void
    {
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner));

        $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/payment", ['amount' => 100]);

        $booking->refresh();
        $this->assertSame(150.0, (float) $booking->amount_paid);
        $this->assertSame(Booking::PAYMENT_PARTIAL, $booking->payment_status);
    }

    public function test_paying_off_the_full_balance_flips_status_to_paid(): void
    {
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner));

        $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/payment", ['amount' => 150]);

        $booking->refresh();
        $this->assertSame(200.0, (float) $booking->amount_paid);
        $this->assertSame(Booking::PAYMENT_PAID, $booking->payment_status);
    }

    public function test_it_rejects_an_amount_above_the_balance_due(): void
    {
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner));

        $response = $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/payment", ['amount' => 151]);

        $response->assertSessionHas('error');
        $booking->refresh();
        $this->assertSame(50.0, (float) $booking->amount_paid);
    }

    public function test_it_rejects_a_zero_or_negative_amount(): void
    {
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner));

        $response = $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/payment", ['amount' => 0]);

        $response->assertSessionHasErrors('amount');
        $booking->refresh();
        $this->assertSame(50.0, (float) $booking->amount_paid);
    }

    public function test_it_refuses_to_record_payment_on_a_cancelled_booking(): void
    {
        $owner = $this->owner();
        $booking = $this->booking($owner, $this->room($owner), ['status' => 'cancelled']);

        $response = $this->actingAs($owner, 'owner')->post("/bookings/{$booking->id}/payment", ['amount' => 50]);

        $response->assertSessionHas('error');
        $booking->refresh();
        $this->assertSame(50.0, (float) $booking->amount_paid);
    }

    public function test_it_is_tenant_isolated(): void
    {
        $owner = $this->owner();
        $other = $this->owner();
        $booking = $this->booking($owner, $this->room($owner));

        $this->actingAs($other, 'owner')->post("/bookings/{$booking->id}/payment", ['amount' => 50])
            ->assertNotFound();

        $booking->refresh();
        $this->assertSame(50.0, (float) $booking->amount_paid);
    }
}
