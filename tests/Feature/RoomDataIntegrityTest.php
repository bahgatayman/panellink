<?php

namespace Tests\Feature;

use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\SharedSession;
use App\Models\Workspace;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4: RoomController guards that protect committed booking/occupancy
 * state from an incompatible room config change. Both guards run before
 * Room::effectiveCapacity() (the actual enforcement engine) can ever be
 * pointed at a value that contradicts data that already exists.
 */
class RoomDataIntegrityTest extends TestCase
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

    private function workspace(Owner $owner): Workspace
    {
        return Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);
    }

    private function room(Owner $owner, Workspace $workspace, string $type = 'shared', int $capacity = 10): Room
    {
        return Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $workspace->id, 'name' => 'Room A',
            'type' => $type, 'capacity' => $capacity, 'price_per_hour' => 50,
        ]);
    }

    private function member(Owner $owner): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Member', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    private function updateUrl(Workspace $workspace, Room $room): string
    {
        return "/workspaces/{$workspace->id}/rooms/{$room->id}";
    }

    private function payload(Room $room, array $overrides = []): array
    {
        return array_merge([
            'name' => $room->name,
            'type' => $room->type,
            'capacity' => $room->capacity,
            'price_per_hour' => (string) $room->price_per_hour,
            'description' => null,
        ], $overrides);
    }

    // --- Type change vs. open SharedSessions ---

    public function test_type_change_is_rejected_while_a_shared_session_is_open(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'party_size' => 2, 'session_date' => today()->toDateString(), 'start_time' => now()->format('H:i'),
            'opened_at' => now(), 'status' => 'open',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['type' => 'meeting', 'capacity' => 1]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('shared', $room->fresh()->type);
    }

    public function test_type_change_is_allowed_with_no_open_session(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['type' => 'meeting', 'capacity' => 1]));

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('meeting', $room->fresh()->type);
    }

    public function test_type_change_is_rejected_by_a_closed_session_only_if_capacity_shrinks_below_usage(): void
    {
        // A CLOSED session doesn't block the type-change guard (only OPEN
        // sessions do) — this exercises that a closed one is a non-issue.
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'party_size' => 1, 'session_date' => today()->toDateString(), 'start_time' => now()->format('H:i'),
            'opened_at' => now()->subHour(), 'closed_at' => now(), 'status' => 'closed',
            'total_minutes' => 60, 'total_price' => 50,
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['type' => 'meeting', 'capacity' => 1]));

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame('meeting', $room->fresh()->type);
    }

    // --- Capacity decrease vs. open SharedSessions ---

    public function test_capacity_decrease_below_open_session_occupancy_is_rejected(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'party_size' => 6, 'session_date' => today()->toDateString(), 'start_time' => now()->format('H:i'),
            'opened_at' => now(), 'status' => 'open',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['capacity' => 5]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(10, $room->fresh()->capacity);
    }

    public function test_capacity_decrease_to_exactly_open_session_occupancy_is_allowed(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'party_size' => 6, 'session_date' => today()->toDateString(), 'start_time' => now()->format('H:i'),
            'opened_at' => now(), 'status' => 'open',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['capacity' => 6]));

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame(6, $room->fresh()->capacity);
    }

    // --- Capacity decrease vs. future non-cancelled bookings ---

    public function test_capacity_decrease_below_future_booking_usage_is_rejected(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'party_size' => 8, 'booking_date' => today()->addDays(3)->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 400, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['capacity' => 5]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame(10, $room->fresh()->capacity);
    }

    public function test_capacity_decrease_above_future_booking_usage_is_allowed(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'party_size' => 3, 'booking_date' => today()->addDays(3)->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 150, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['capacity' => 5]));

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame(5, $room->fresh()->capacity);
    }

    public function test_a_past_dated_booking_does_not_block_a_capacity_decrease(): void
    {
        // booking_date is in the past — stale history, not ongoing
        // commitment, so it must not hold capacity hostage. Inserted
        // directly (bypassing store()'s after_or_equal:today validation)
        // since this represents a booking that was legitimately future when
        // created and has since fallen into the past.
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'party_size' => 9, 'booking_date' => today()->subDays(5)->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 450, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['capacity' => 2]));

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame(2, $room->fresh()->capacity);
    }

    public function test_cancelled_future_booking_does_not_block_a_capacity_decrease(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'party_size' => 9, 'booking_date' => today()->addDays(2)->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 450, 'status' => 'cancelled',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['capacity' => 2]));

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame(2, $room->fresh()->capacity);
    }

    // --- Capacity increase always allowed ---

    public function test_capacity_increase_is_always_allowed(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'party_size' => 10, 'booking_date' => today()->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 500, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['capacity' => 20]));

        $response->assertRedirect()->assertSessionHas('success');
        $this->assertSame(20, $room->fresh()->capacity);
    }

    // --- Converting shared -> exclusive is also blocked by future party-size usage ---

    public function test_shared_to_exclusive_conversion_is_rejected_when_a_future_booking_has_party_over_one(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'party_size' => 3, 'booking_date' => today()->addDay()->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 150, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, ['type' => 'meeting', 'capacity' => 1]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertSame('shared', $room->fresh()->type);
    }

    // --- Existing exclusive-room behavior is unchanged ---

    public function test_ordinary_exclusive_room_update_still_works_unaffected(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'meeting', 1);
        $member = $this->member($owner);

        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'party_size' => 1, 'booking_date' => today()->addDay()->toDateString(),
            'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 100, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->put($this->updateUrl($workspace, $room), $this->payload($room, [
                'name' => 'Renamed Room', 'price_per_hour' => '75', 'capacity' => 3,
            ]));

        $response->assertRedirect()->assertSessionHas('success');
        $fresh = $room->fresh();
        $this->assertSame('Renamed Room', $fresh->name);
        $this->assertSame('75.00', (string) $fresh->price_per_hour);
        // Capacity is decorative for exclusive rooms — effectiveCapacity() stays 1 regardless.
        $this->assertSame(1, $fresh->effectiveCapacity());
    }
}
