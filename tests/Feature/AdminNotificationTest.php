<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Notification;
use App\Models\Owner;
use App\Models\Plan;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
        Plan::create([
            'name' => 'Basic', 'slug' => 'basic', 'max_members' => 100,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1,
        ]);
    }

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Op', 'email' => 'op@t.local', 'password' => Hash::make('secret123'),
        ]);
    }

    private function owner(string $email, bool $active = true): Owner
    {
        return Owner::create([
            'name' => 'Owner', 'email' => $email, 'password' => 'secret123',
            'business_name' => 'Space', 'plan_id' => Plan::first()->id, 'is_active' => $active,
            'subscription_starts_at' => now(), 'subscription_expires_at' => now()->addMonth(),
        ]);
    }

    public function test_compose_page_requires_admin(): void
    {
        $this->get('/admin/notifications')->assertRedirect(route('login'));
    }

    public function test_admin_can_broadcast_to_all_owners(): void
    {
        $this->owner('a@t.local');
        $this->owner('b@t.local', active: false);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/notifications', [
                'title'  => 'Scheduled maintenance',
                'body'   => 'The panel will be down tonight.',
                'level'  => 'warning',
                'target' => 'all',
            ])->assertRedirect();

        $this->assertSame(2, Notification::where('type', 'admin_message')->count());
        $this->assertDatabaseHas('notifications', [
            'type' => 'admin_message', 'level' => 'warning', 'title' => 'Scheduled maintenance', 'read_at' => null,
        ]);
    }

    public function test_active_only_target_skips_inactive_owners(): void
    {
        $this->owner('a@t.local');
        $this->owner('b@t.local', active: false);

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/notifications', [
                'title' => 'Hi active', 'level' => 'info', 'target' => 'active',
            ])->assertRedirect();

        $this->assertSame(1, Notification::where('type', 'admin_message')->count());
    }

    public function test_selected_target_sends_only_to_chosen_owners(): void
    {
        $a = $this->owner('a@t.local');
        $this->owner('b@t.local');

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/notifications', [
                'title' => 'Just you', 'level' => 'info', 'target' => 'selected', 'owner_ids' => [$a->id],
            ])->assertRedirect();

        $this->assertSame(1, Notification::where('type', 'admin_message')->count());
        $this->assertDatabaseHas('notifications', ['owner_id' => $a->id, 'title' => 'Just you']);
    }

    public function test_title_is_required(): void
    {
        $this->owner('a@t.local');

        $this->actingAs($this->admin(), 'admin')
            ->post('/admin/notifications', ['level' => 'info', 'target' => 'all'])
            ->assertSessionHasErrors('title');

        $this->assertSame(0, Notification::where('type', 'admin_message')->count());
    }
}
