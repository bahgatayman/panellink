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
 * Feature-level coverage for Phase 2: day/week calendar views. The
 * booked/available math itself is AvailabilityService's job and is already
 * covered by AvailabilityServiceTest — these tests check that the calendar
 * controller/views package that data correctly (right rooms, right day,
 * filters applied, existing bookings link to their detail page).
 */
class BookingCalendarTest extends TestCase
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

    private function workspace(Owner $owner, string $name = 'Main'): Workspace
    {
        return Workspace::create(['owner_id' => $owner->id, 'name' => $name]);
    }

    private function room(Owner $owner, Workspace $workspace, string $type = 'meeting', int $capacity = 4, string $name = 'Room A'): Room
    {
        return Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $workspace->id, 'name' => $name,
            'type' => $type, 'capacity' => $capacity, 'price_per_hour' => 50,
        ]);
    }

    private function member(Owner $owner, string $name = 'Member'): HotspotUser
    {
        return HotspotUser::create([
            'owner_id' => $owner->id, 'name' => $name, 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);
    }

    public function test_calendar_defaults_to_day_view_for_today(): void
    {
        $owner = $this->owner();

        $response = $this->actingAs($owner, 'owner')->get('/bookings/calendar');

        $response->assertOk();
        $response->assertViewIs('bookings.calendar');
        $response->assertViewHas('view', 'day');
        $response->assertViewHas('date', now()->format('Y-m-d'));
        $response->assertViewHas('dayRooms');
    }

    public function test_day_view_reports_booked_and_available_blocks_for_a_room(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'meeting', 1);
        $member = $this->member($owner);

        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'booking_date' => today()->toDateString(), 'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 100, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->get('/bookings/calendar?view=day&date='.today()->toDateString());

        $response->assertOk();
        $response->assertViewHas('dayRooms', function ($dayRooms) use ($room) {
            $entry = collect($dayRooms)->firstWhere('room.id', $room->id);
            $blocks = $entry['blocks'];

            $bookedBlock = collect($blocks)->firstWhere('start', '10:00');
            $freeBlock = collect($blocks)->first(fn ($b) => $b['start'] < '10:00');

            return $bookedBlock['used'] === 1
                && $bookedBlock['available'] === 0
                && $freeBlock['used'] === 0
                && $freeBlock['available'] === 1;
        });
    }

    public function test_day_view_lists_the_booking_linking_to_its_detail_page(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace);
        $member = $this->member($owner, 'Jane Visitor');

        $booking = $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'booking_date' => today()->toDateString(), 'start_time' => '14:00', 'end_time' => '15:00',
            'price_per_hour' => 50, 'total_hours' => 1, 'total_price' => 50, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->get('/bookings/calendar?view=day&date='.today()->toDateString());

        $response->assertOk();
        $response->assertSee('Jane Visitor');
        $response->assertSee('href="/bookings/'.$booking->id.'"', false);
    }

    public function test_room_filter_narrows_day_view_to_that_room(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $roomA = $this->room($owner, $workspace, name: 'Room A');
        $roomB = $this->room($owner, $workspace, name: 'Room B');

        $response = $this->actingAs($owner, 'owner')
            ->get('/bookings/calendar?view=day&room_id='.$roomA->id);

        $response->assertOk();
        $response->assertViewHas('dayRooms', function ($dayRooms) use ($roomA, $roomB) {
            $ids = collect($dayRooms)->pluck('room.id');

            return $ids->contains($roomA->id) && ! $ids->contains($roomB->id);
        });
    }

    public function test_workspace_filter_narrows_day_view_to_that_workspaces_rooms(): void
    {
        $owner = $this->owner();
        $workspaceA = $this->workspace($owner, 'Branch A');
        $workspaceB = $this->workspace($owner, 'Branch B');
        $roomA = $this->room($owner, $workspaceA, name: 'A-Room');
        $roomB = $this->room($owner, $workspaceB, name: 'B-Room');

        $response = $this->actingAs($owner, 'owner')
            ->get('/bookings/calendar?view=day&workspace_id='.$workspaceA->id);

        $response->assertOk();
        $response->assertViewHas('dayRooms', function ($dayRooms) use ($roomA, $roomB) {
            $ids = collect($dayRooms)->pluck('room.id');

            return $ids->contains($roomA->id) && ! $ids->contains($roomB->id);
        });
    }

    public function test_week_view_covers_seven_days_starting_monday(): void
    {
        $owner = $this->owner();
        $this->workspace($owner);

        // A known Wednesday, so the resulting week is unambiguous regardless of when the test runs.
        $wednesday = '2026-08-19';

        $response = $this->actingAs($owner, 'owner')
            ->get('/bookings/calendar?view=week&date='.$wednesday);

        $response->assertOk();
        $response->assertViewHas('days', function ($days) {
            if ($days->count() !== 7) {
                return false;
            }

            return $days->first()['date']->format('Y-m-d') === '2026-08-17' // Monday
                && $days->last()['date']->format('Y-m-d') === '2026-08-23'; // Sunday
        });
    }

    public function test_week_view_reflects_a_booking_on_its_specific_day(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'meeting', 1);
        $member = $this->member($owner);

        $tuesday = '2026-08-18';
        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'booking_date' => $tuesday, 'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 100, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->get('/bookings/calendar?view=week&date=2026-08-19');

        $response->assertOk();
        $response->assertViewHas('days', function ($days) use ($room, $tuesday) {
            $tuesdayEntry = $days->first(fn ($d) => $d['date']->format('Y-m-d') === $tuesday);
            $roomEntry = collect($tuesdayEntry['rooms'])->firstWhere('room.id', $room->id);
            $otherDayEntry = $days->first(fn ($d) => $d['date']->format('Y-m-d') === '2026-08-17');
            $roomOnOtherDay = collect($otherDayEntry['rooms'])->firstWhere('room.id', $room->id);

            return $roomEntry['bookings']->count() === 1
                && $roomOnOtherDay['bookings']->count() === 0;
        });
    }

    public function test_month_view_still_works_and_day_cells_link_into_day_view(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace);
        $member = $this->member($owner);

        $room->bookings()->create([
            'owner_id' => $owner->id, 'hotspot_user_id' => $member->id,
            'booking_date' => today()->toDateString(), 'start_time' => '10:00', 'end_time' => '12:00',
            'price_per_hour' => 50, 'total_hours' => 2, 'total_price' => 100, 'status' => 'confirmed',
        ]);

        $response = $this->actingAs($owner, 'owner')
            ->get('/bookings/calendar?view=month&date='.today()->toDateString());

        $response->assertOk();
        $response->assertViewHas('bookings', function ($bookings) {
            return isset($bookings[today()->format('Y-m-d')]);
        });
        $response->assertSee('view=day', false);
    }

    public function test_shared_room_shows_live_occupancy_only_for_today(): void
    {
        $owner = $this->owner();
        $workspace = $this->workspace($owner);
        $room = $this->room($owner, $workspace, 'shared', 10);
        $member = $this->member($owner);

        SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'party_size' => 4, 'session_date' => today()->toDateString(), 'start_time' => now()->format('H:i'),
            'opened_at' => now(), 'status' => 'open',
        ]);

        $todayResponse = $this->actingAs($owner, 'owner')
            ->get('/bookings/calendar?view=day&date='.today()->toDateString());
        $todayResponse->assertViewHas('dayRooms', function ($dayRooms) use ($room) {
            $entry = collect($dayRooms)->firstWhere('room.id', $room->id);

            return $entry['live']['occupied'] === 4 && $entry['live']['capacity'] === 10;
        });

        $futureResponse = $this->actingAs($owner, 'owner')
            ->get('/bookings/calendar?view=day&date='.today()->addDays(3)->toDateString());
        $futureResponse->assertViewHas('dayRooms', function ($dayRooms) use ($room) {
            $entry = collect($dayRooms)->firstWhere('room.id', $room->id);

            return $entry['live'] === null;
        });
    }
}
