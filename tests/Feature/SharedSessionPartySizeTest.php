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

class SharedSessionPartySizeTest extends TestCase
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

    /** An 8-seat shared room. */
    private function sharedRoom(Owner $owner, int $capacity = 8): Room
    {
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);

        return Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Lounge',
            'type' => 'shared', 'capacity' => $capacity, 'price_per_hour' => 60,
        ]);
    }

    private function member(Owner $owner, string $name = 'Cust'): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => $name, 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    public function test_opening_a_session_defaults_party_size_to_one(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner);
        $user = $this->member($owner);

        $this->actingAs($owner, 'owner')
            ->post('/shared-sessions', [
                'room_id' => $room->id, 'hotspot_user_id' => $user->id,
                'session_date' => today()->toDateString(), 'start_time' => '10:00',
            ])
            ->assertRedirect(route('shared-sessions.index'));

        $session = SharedSession::where('room_id', $room->id)->firstOrFail();
        $this->assertSame(1, $session->party_size);
    }

    public function test_a_party_can_be_opened_and_consumes_that_many_seats(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 8);
        $user = $this->member($owner, 'Ahmed');

        $this->actingAs($owner, 'owner')
            ->post('/shared-sessions', [
                'room_id' => $room->id, 'hotspot_user_id' => $user->id,
                'session_date' => today()->toDateString(), 'start_time' => '10:00',
                'party_size' => 3,
            ])
            ->assertRedirect(route('shared-sessions.index'));

        $session = SharedSession::where('room_id', $room->id)->firstOrFail();
        $this->assertSame(3, $session->party_size);
        // 8 - 3 = 5 seats left, not 7 (which a row-count-based check would wrongly report).
        $this->assertSame(5, $room->fresh()->availableSharedSlots());
    }

    public function test_party_larger_than_room_capacity_is_rejected(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 4);
        $user = $this->member($owner);

        $this->actingAs($owner, 'owner')
            ->post('/shared-sessions', [
                'room_id' => $room->id, 'hotspot_user_id' => $user->id,
                'session_date' => today()->toDateString(), 'start_time' => '10:00',
                'party_size' => 5,
            ])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(0, SharedSession::where('room_id', $room->id)->count());
    }

    public function test_party_larger_than_available_seats_is_rejected(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 8);

        // Two single-person sessions already open, plus one party of 5 = 7 seats used, 1 free.
        $this->openSession($owner, $room, $this->member($owner, 'A'), partySize: 1);
        $this->openSession($owner, $room, $this->member($owner, 'B'), partySize: 1);
        $this->openSession($owner, $room, $this->member($owner, 'C'), partySize: 5);

        $this->assertSame(1, $room->fresh()->availableSharedSlots());

        // A party of 2 doesn't fit in the 1 remaining seat.
        $this->actingAs($owner, 'owner')
            ->post('/shared-sessions', [
                'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner, 'D')->id,
                'session_date' => today()->toDateString(), 'start_time' => '10:00',
                'party_size' => 2,
            ])
            ->assertRedirect()->assertSessionHas('error');

        $this->assertSame(3, SharedSession::where('room_id', $room->id)->count());
    }

    public function test_occupied_seats_badge_matches_available_slots_math(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 8);

        $this->openSession($owner, $room, $this->member($owner, 'A'), partySize: 1);
        $this->openSession($owner, $room, $this->member($owner, 'B'), partySize: 5);

        $roomWithSum = Room::where('id', $room->id)
            ->withSum(['sharedSessions as occupied_seats' => fn ($q) => $q->where('status', 'open')], 'party_size')
            ->firstOrFail();

        // The badge's occupied_seats and availableSharedSlots() must agree: 6 used, 2 free.
        $this->assertSame(6, (int) $roomWithSum->occupied_seats);
        $this->assertSame(2, $room->fresh()->availableSharedSlots());
        $this->assertSame(2, $roomWithSum->capacity - (int) $roomWithSum->occupied_seats);
    }

    public function test_legacy_single_person_sessions_compute_identical_availability(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 5);

        // Three legacy-shaped sessions (party_size defaults to 1, as every row did
        // before this feature existed) — sum-based math must equal old count-based math.
        $this->openSession($owner, $room, $this->member($owner, 'A'));
        $this->openSession($owner, $room, $this->member($owner, 'B'));
        $this->openSession($owner, $room, $this->member($owner, 'C'));

        $this->assertSame(2, $room->fresh()->availableSharedSlots()); // 5 - 3 = 2, same as COUNT-based.
    }

    public function test_closing_a_session_copies_party_size_to_the_booking(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner);
        $session = $this->openSession($owner, $room, $this->member($owner, 'Ahmed'), partySize: 3);

        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close")
            ->assertOk()->assertJson(['success' => true]);

        $booking = $session->fresh()->booking;
        $this->assertNotNull($booking);
        $this->assertSame(3, $booking->party_size);
    }

    public function test_second_party_that_no_longer_fits_is_rejected_after_first_fills_the_room(): void
    {
        $owner = $this->owner();
        $room = $this->sharedRoom($owner, capacity: 4);

        // First request fills the room exactly.
        $this->actingAs($owner, 'owner')
            ->post('/shared-sessions', [
                'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner, 'First')->id,
                'session_date' => today()->toDateString(), 'start_time' => '10:00',
                'party_size' => 4,
            ])->assertRedirect(route('shared-sessions.index'));

        // A second request for the now-full room must fail, not silently overbook.
        $this->actingAs($owner, 'owner')
            ->post('/shared-sessions', [
                'room_id' => $room->id, 'hotspot_user_id' => $this->member($owner, 'Second')->id,
                'session_date' => today()->toDateString(), 'start_time' => '10:05',
                'party_size' => 1,
            ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame(1, SharedSession::where('room_id', $room->id)->where('status', 'open')->count());
        $this->assertSame(0, $room->fresh()->availableSharedSlots());
    }

    private function openSession(Owner $owner, Room $room, HotspotUser $user, int $partySize = 1): SharedSession
    {
        return SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'party_size' => $partySize,
            'session_date' => today()->toDateString(), 'start_time' => '09:00',
            'opened_at' => now(), 'status' => 'open',
        ]);
    }
}
