<?php

namespace Tests\Feature;

use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Room;
use App\Models\Sale;
use App\Models\SharedSession;
use App\Models\Workspace;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedSessionSalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
    }

    private function owner(array $planOverrides = []): Owner
    {
        $plan = Plan::create(array_merge([
            'name' => 'Test', 'slug' => 'test-'.uniqid(), 'max_members' => 100,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1,
            'features' => ['workspace', 'booking', 'sales'],
            'max_workspaces' => 0, 'max_rooms' => 0, 'max_products' => 0,
        ], $planOverrides));

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

    private function product(Owner $owner, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'owner_id' => $owner->id, 'name' => 'Coffee', 'type' => 'product',
            'price' => 25, 'is_active' => true,
        ], $overrides));
    }

    private function openSession(Owner $owner): SharedSession
    {
        $ws = Workspace::create(['owner_id' => $owner->id, 'name' => 'Main']);
        $room = Room::create([
            'owner_id' => $owner->id, 'workspace_id' => $ws->id, 'name' => 'Lounge',
            'type' => 'shared', 'capacity' => 8, 'price_per_hour' => 60,
        ]);
        $user = HotspotUser::create([
            'owner_id' => $owner->id, 'name' => 'Cust', 'phone' => '010'.rand(10000000, 99999999),
            'password' => 'pass1234',
        ]);

        return SharedSession::create([
            'owner_id' => $owner->id, 'room_id' => $room->id, 'hotspot_user_id' => $user->id,
            'session_date' => today()->toDateString(), 'start_time' => '10:00',
            'opened_at' => now()->subHour(), 'status' => 'open',
        ]);
    }

    public function test_items_can_be_added_to_an_open_session_tab(): void
    {
        $owner = $this->owner();
        $session = $this->openSession($owner);
        $product = $this->product($owner, ['price' => 25]);

        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/items", ['product_id' => $product->id, 'quantity' => 2])
            ->assertOk()
            ->assertJson(['success' => true]);

        $sale = Sale::where('shared_session_id', $session->id)->with('items')->firstOrFail();
        $this->assertSame('open', $sale->status);
        $this->assertNull($sale->booking_id);
        $this->assertEquals(50, (float) $sale->total);
        $this->assertSame(1, $sale->items->count());
    }

    public function test_close_preview_includes_items_and_grand_total(): void
    {
        $owner = $this->owner();
        $session = $this->openSession($owner);
        $product = $this->product($owner, ['price' => 25]);

        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/items", ['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($owner, 'owner')
            ->getJson("/shared-sessions/{$session->id}/close-preview")
            ->assertOk()
            ->assertJsonPath('items_total', '50.00')
            ->assertJsonCount(1, 'items');
    }

    public function test_closing_a_session_moves_the_tab_to_the_booking(): void
    {
        $owner = $this->owner();
        $session = $this->openSession($owner);
        $product = $this->product($owner, ['price' => 25]);

        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/items", ['product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/close", [
                'closed_at' => now()->toDateTimeString(),
                'total_minutes' => 60,
                'total_price' => 60, // one hour at 60/hr
            ])->assertOk()->assertJson(['success' => true]);

        $session->refresh();
        $booking = $session->booking;
        $this->assertNotNull($booking);
        // Time charge lives on the booking, untouched by items.
        $this->assertEquals(60, (float) $booking->total_price);

        // The tab moved onto the booking, keeping its line items.
        $sale = Sale::where('booking_id', $booking->id)->with('items')->firstOrFail();
        $this->assertNull($sale->shared_session_id);
        $this->assertSame('completed', $sale->status);
        $this->assertNotNull($sale->sold_at);
        $this->assertEquals(50, (float) $sale->total);
        $this->assertSame(1, $sale->items->count());
        $this->assertSame('Coffee', $sale->items->first()->name);

        // Grand total = room time + items.
        $this->assertEquals(110, $booking->grandTotal());
    }

    public function test_cross_tenant_product_cannot_be_added_to_a_session(): void
    {
        $owner = $this->owner();
        $session = $this->openSession($owner);
        $foreign = $this->product($this->owner(), ['name' => 'Foreign']);

        $this->actingAs($owner, 'owner')
            ->postJson("/shared-sessions/{$session->id}/items", ['product_id' => $foreign->id, 'quantity' => 1])
            ->assertNotFound();

        $this->assertDatabaseCount('sales', 0);
    }
}
