<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\SharedSession;
use App\Models\Workspace;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceRoomsUiTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): Owner
    {
        $this->seed(FeatureSeeder::class);
        $slug = 'b-'.uniqid();
        $plan = Plan::create(['name' => 'B', 'slug' => $slug, 'max_members' => 10, 'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1]);
        $o = Owner::create(['name' => 'O', 'email' => 'w'.uniqid().'@t.local', 'password' => 'p', 'business_name' => 'B',
            'plan_id' => $plan->id, 'is_active' => true, 'subscription_starts_at' => now(),
            'subscription_expires_at' => now()->addMonth()])->fresh();
        $o->enableFeature('workspace');

        return $o->fresh();
    }

    public function test_single_workspace_shows_rooms_directly_without_a_selector(): void
    {
        $o = $this->owner();
        $ws = Workspace::create(['owner_id' => $o->id, 'name' => 'Hub', 'city' => 'Cairo', 'is_active' => true]);
        $meeting = Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'Board A',
            'type' => 'meeting', 'capacity' => 8, 'price_per_hour' => 120, 'is_available' => true]);
        $shared = Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'Open Floor',
            'type' => 'shared', 'capacity' => 10, 'price_per_hour' => 40, 'is_available' => true]);

        $html = $this->actingAs($o, 'owner')->get(route('workspaces.index'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('rooms.edit', [$ws, $meeting]).'"', $html);
        $this->assertStringContainsString('href="'.route('rooms.edit', [$ws, $shared]).'"', $html);
        $this->assertStringContainsString('Meeting Room', $html);
        $this->assertStringContainsString('Shared Space', $html);

        // No workspace selector when there's only one.
        $this->assertStringNotContainsString('data-ls-remember-workspace', $html);
    }

    public function test_room_types_translate_to_arabic(): void
    {
        $o = $this->owner();
        $ws = Workspace::create(['owner_id' => $o->id, 'name' => 'Hub', 'is_active' => true]);
        Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'R', 'type' => 'office',
            'capacity' => 2, 'price_per_hour' => 50, 'is_available' => true]);

        $this->withSession(['locale' => 'ar'])
            ->actingAs($o, 'owner')->get(route('workspaces.index'))
            ->assertOk()
            ->assertSee('مكتب');
    }

    public function test_zero_workspaces_shows_create_workspace_empty_state(): void
    {
        $o = $this->owner();

        $html = $this->actingAs($o, 'owner')->get(route('workspaces.index'))->assertOk()->getContent();

        $this->assertStringContainsString(route('workspaces.create'), $html);
        $this->assertStringNotContainsString('ls-strip', $html);
    }

    public function test_multiple_workspaces_show_a_selector_and_default_to_the_first_by_name(): void
    {
        $o = $this->owner();
        $b = Workspace::create(['owner_id' => $o->id, 'name' => 'Branch B', 'is_active' => true]);
        $a = Workspace::create(['owner_id' => $o->id, 'name' => 'Branch A', 'is_active' => true]);
        Room::create(['owner_id' => $o->id, 'workspace_id' => $a->id, 'name' => 'A Room', 'type' => 'office', 'capacity' => 2, 'price_per_hour' => 50, 'is_available' => true]);
        Room::create(['owner_id' => $o->id, 'workspace_id' => $b->id, 'name' => 'B Room', 'type' => 'office', 'capacity' => 2, 'price_per_hour' => 50, 'is_available' => true]);

        $html = $this->actingAs($o, 'owner')->get(route('workspaces.index'))->assertOk()->getContent();

        $this->assertStringContainsString('data-ls-remember-workspace', $html);
        // Ordered by name: "Branch A" comes first and is the default active workspace.
        $this->assertStringContainsString('A Room', $html);
        $this->assertStringNotContainsString('B Room', $html);
    }

    public function test_workspace_query_param_switches_the_active_workspace(): void
    {
        $o = $this->owner();
        $a = Workspace::create(['owner_id' => $o->id, 'name' => 'Branch A', 'is_active' => true]);
        $b = Workspace::create(['owner_id' => $o->id, 'name' => 'Branch B', 'is_active' => true]);
        Room::create(['owner_id' => $o->id, 'workspace_id' => $a->id, 'name' => 'A Room', 'type' => 'office', 'capacity' => 2, 'price_per_hour' => 50, 'is_available' => true]);
        Room::create(['owner_id' => $o->id, 'workspace_id' => $b->id, 'name' => 'B Room', 'type' => 'office', 'capacity' => 2, 'price_per_hour' => 50, 'is_available' => true]);

        $html = $this->actingAs($o, 'owner')->get(route('workspaces.index', ['workspace' => $b->id]))->assertOk()->getContent();

        $this->assertStringContainsString('B Room', $html);
        $this->assertStringNotContainsString('A Room', $html);
    }

    public function test_foreign_workspace_id_falls_back_to_owners_own_first_workspace(): void
    {
        $ownerA = $this->owner();
        $ownerB = $this->owner();
        $a1 = Workspace::create(['owner_id' => $ownerA->id, 'name' => 'A1', 'is_active' => true]);
        Workspace::create(['owner_id' => $ownerA->id, 'name' => 'A2', 'is_active' => true]);
        $bWs = Workspace::create(['owner_id' => $ownerB->id, 'name' => 'B1', 'is_active' => true]);
        Room::create(['owner_id' => $ownerA->id, 'workspace_id' => $a1->id, 'name' => 'A Room', 'type' => 'office', 'capacity' => 2, 'price_per_hour' => 50, 'is_available' => true]);
        Room::create(['owner_id' => $ownerB->id, 'workspace_id' => $bWs->id, 'name' => 'B Room', 'type' => 'office', 'capacity' => 2, 'price_per_hour' => 50, 'is_available' => true]);

        $html = $this->actingAs($ownerA, 'owner')
            ->get(route('workspaces.index', ['workspace' => $bWs->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('A Room', $html);
        $this->assertStringNotContainsString('B Room', $html);
    }

    public function test_room_status_precedence_unavailable_beats_occupied(): void
    {
        $o = $this->owner();
        $ws = Workspace::create(['owner_id' => $o->id, 'name' => 'Hub', 'is_active' => true]);
        $member = HotspotUser::create(['owner_id' => $o->id, 'name' => 'M', 'phone' => '010'.rand(10000000, 99999999), 'password' => 'pass1234']);

        $room = Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'Inactive Busy',
            'type' => 'meeting', 'capacity' => 4, 'price_per_hour' => 100, 'is_available' => false]);
        Booking::create([
            'owner_id' => $o->id, 'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'party_size' => 1, 'booking_date' => now()->format('Y-m-d'),
            'start_time' => now()->subMinutes(10)->format('H:i:s'), 'end_time' => now()->addMinutes(50)->format('H:i:s'),
            'price_per_hour' => 100, 'total_hours' => 1, 'total_price' => 100,
            'amount_paid' => 0, 'payment_status' => 'unpaid', 'status' => 'confirmed',
        ]);

        $html = $this->actingAs($o, 'owner')->get(route('workspaces.index'))->assertOk()->getContent();

        $this->assertStringContainsString(__('app.workspace.unavailable'), $html);
    }

    public function test_occupied_exclusive_room_shows_occupied_status(): void
    {
        $o = $this->owner();
        $ws = Workspace::create(['owner_id' => $o->id, 'name' => 'Hub', 'is_active' => true]);
        $member = HotspotUser::create(['owner_id' => $o->id, 'name' => 'M', 'phone' => '010'.rand(10000000, 99999999), 'password' => 'pass1234']);

        $room = Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'Busy Room',
            'type' => 'meeting', 'capacity' => 4, 'price_per_hour' => 100, 'is_available' => true]);
        Booking::create([
            'owner_id' => $o->id, 'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'party_size' => 1, 'booking_date' => now()->format('Y-m-d'),
            'start_time' => now()->subMinutes(10)->format('H:i:s'), 'end_time' => now()->addMinutes(50)->format('H:i:s'),
            'price_per_hour' => 100, 'total_hours' => 1, 'total_price' => 100,
            'amount_paid' => 0, 'payment_status' => 'unpaid', 'status' => 'confirmed',
        ]);

        $html = $this->actingAs($o, 'owner')->get(route('workspaces.index'))->assertOk()->getContent();

        $this->assertStringContainsString(__('app.workspace.occupied'), $html);
    }

    public function test_shared_room_shows_x_of_y_occupied(): void
    {
        $o = $this->owner();
        $ws = Workspace::create(['owner_id' => $o->id, 'name' => 'Hub', 'is_active' => true]);
        $member = HotspotUser::create(['owner_id' => $o->id, 'name' => 'M', 'phone' => '010'.rand(10000000, 99999999), 'password' => 'pass1234']);

        $room = Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'Open Floor',
            'type' => 'shared', 'capacity' => 10, 'price_per_hour' => 40, 'is_available' => true]);
        SharedSession::create([
            'owner_id' => $o->id, 'room_id' => $room->id, 'hotspot_user_id' => $member->id,
            'party_size' => 4, 'session_date' => today()->toDateString(), 'start_time' => now()->format('H:i'),
            'opened_at' => now(), 'status' => 'open', 'billing_unit' => 'minute', 'billed_price_per_hour' => 40,
        ]);

        $html = $this->actingAs($o, 'owner')->get(route('workspaces.index'))->assertOk()->getContent();

        $this->assertStringContainsString(__('app.workspace.occupied_shared', ['used' => 4, 'total' => 10]), $html);
    }

    public function test_stat_strip_counts_match_room_mix(): void
    {
        $o = $this->owner();
        $ws = Workspace::create(['owner_id' => $o->id, 'name' => 'Hub', 'is_active' => true]);
        Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'Free', 'type' => 'office', 'capacity' => 2, 'price_per_hour' => 50, 'is_available' => true]);
        Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'Off', 'type' => 'office', 'capacity' => 2, 'price_per_hour' => 50, 'is_available' => false]);

        $this->actingAs($o, 'owner')->get(route('workspaces.index'))
            ->assertOk()
            ->assertSeeInOrder([
                __('app.workspace.total_rooms'), '2',
                __('app.workspace.available'), '1',
                __('app.workspace.occupied'), '0',
            ]);
    }

    public function test_type_filter_only_shows_that_type(): void
    {
        $o = $this->owner();
        $ws = Workspace::create(['owner_id' => $o->id, 'name' => 'Hub', 'is_active' => true]);
        Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'Meeting One', 'type' => 'meeting', 'capacity' => 4, 'price_per_hour' => 100, 'is_available' => true]);
        Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'Shared One', 'type' => 'shared', 'capacity' => 10, 'price_per_hour' => 40, 'is_available' => true]);

        $html = $this->actingAs($o, 'owner')->get(route('workspaces.index', ['type' => 'shared']))->assertOk()->getContent();

        $this->assertStringContainsString('Shared One', $html);
        $this->assertStringNotContainsString('Meeting One', $html);
    }

    public function test_room_mutations_redirect_to_the_unified_rooms_page(): void
    {
        $o = $this->owner();
        $ws = Workspace::create(['owner_id' => $o->id, 'name' => 'Hub', 'is_active' => true]);

        $this->actingAs($o, 'owner')->post(route('rooms.store', $ws), [
            'name' => 'New Room', 'type' => 'office', 'capacity' => 2, 'price_per_hour' => 50,
        ])->assertRedirect(route('workspaces.index', ['workspace' => $ws->id]));

        $room = Room::where('name', 'New Room')->firstOrFail();

        $this->actingAs($o, 'owner')->put(route('rooms.update', [$ws, $room]), [
            'name' => 'New Room', 'type' => 'office', 'capacity' => 3, 'price_per_hour' => 60,
        ])->assertRedirect(route('workspaces.index', ['workspace' => $ws->id]));

        $this->actingAs($o, 'owner')->delete(route('rooms.destroy', [$ws, $room]))
            ->assertRedirect(route('workspaces.index', ['workspace' => $ws->id]));
    }

    public function test_custom_plans_badge_links_to_room_edit_anchor(): void
    {
        $o = $this->owner();
        $ws = Workspace::create(['owner_id' => $o->id, 'name' => 'Hub', 'is_active' => true]);
        $room = Room::create(['owner_id' => $o->id, 'workspace_id' => $ws->id, 'name' => 'Planned Room',
            'type' => 'meeting', 'capacity' => 4, 'price_per_hour' => 100, 'is_available' => true]);
        $room->plans()->create(['owner_id' => $o->id, 'name' => 'Half day', 'people' => 4, 'is_full_day' => false, 'duration_minutes' => 240, 'price' => 300, 'sort_order' => 0]);

        $html = $this->actingAs($o, 'owner')->get(route('workspaces.index'))->assertOk()->getContent();

        $this->assertStringContainsString('href="'.route('rooms.edit', [$ws, $room]).'#plans-title"', $html);
    }
}
