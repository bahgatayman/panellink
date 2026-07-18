<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Owner;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeOwner(array $overrides = []): Owner
    {
        return Owner::create(array_merge([
            'name'                    => 'Test Owner',
            'email'                   => 'owner@example.com',
            'password'                => 'password',
            'business_name'           => 'Test Space',
            'mikrotik_host'           => '127.0.0.1',
            'mikrotik_port'           => 8728,
            'mikrotik_username'       => 'admin',
            'mikrotik_password'       => 'secret',
            'is_active'               => true,
            'subscription_starts_at'  => now()->subMonth(),
            'subscription_expires_at' => now()->addDays(3), // expiring_soon
        ], $overrides));
    }

    public function test_service_generates_expiry_alert_and_is_idempotent(): void
    {
        $owner = $this->makeOwner();
        $service = app(NotificationService::class);

        $service->refreshForOwner($owner);
        $service->refreshForOwner($owner); // second run must not duplicate

        $this->assertSame(1, Notification::forOwner($owner->id)->count());
        $this->assertDatabaseHas('notifications', [
            'owner_id' => $owner->id,
            'type'     => 'subscription_expiring',
            'level'    => 'warning',
        ]);
    }

    public function test_owner_can_view_notifications_page(): void
    {
        $owner = $this->makeOwner();
        app(NotificationService::class)->refreshForOwner($owner);

        $this->actingAs($owner, 'owner')
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Subscription expiring soon');
    }

    public function test_owner_can_mark_all_notifications_read(): void
    {
        $owner = $this->makeOwner();
        app(NotificationService::class)->refreshForOwner($owner);
        $this->assertSame(1, Notification::forOwner($owner->id)->unread()->count());

        $this->actingAs($owner, 'owner')
            ->post('/notifications/read-all')
            ->assertRedirect();

        $this->assertSame(0, Notification::forOwner($owner->id)->unread()->count());
    }

    public function test_notifications_are_scoped_to_the_owner(): void
    {
        $owner = $this->makeOwner(['email' => 'a@example.com']);
        $other = $this->makeOwner(['email' => 'b@example.com']);
        app(NotificationService::class)->refreshForOwner($owner);

        $note = Notification::forOwner($owner->id)->firstOrFail();

        // Another owner must not be able to read or delete someone else's alert.
        $this->actingAs($other, 'owner')
            ->delete("/notifications/{$note->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('notifications', ['id' => $note->id]);
    }
}
