<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Owner;
use App\Models\Plan;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UnifiedLoginTest extends TestCase
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

    private function makeOwner(): Owner
    {
        return Owner::create([
            'name' => 'Tenant', 'email' => 'owner@t.local', 'password' => 'secret123',
            'business_name' => 'Space', 'plan_id' => Plan::first()->id, 'is_active' => true,
            'subscription_starts_at' => now(), 'subscription_expires_at' => now()->addMonth(),
        ]);
    }

    private function makeAdmin(): Admin
    {
        return Admin::create([
            'name' => 'Operator', 'email' => 'admin@t.local',
            'password' => Hash::make('secret123'),
        ]);
    }

    public function test_the_single_login_page_is_public(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_admin_signs_in_through_the_shared_form(): void
    {
        $this->makeAdmin();

        $this->post('/login', ['email' => 'admin@t.local', 'password' => 'secret123'])
            ->assertRedirect('/admin/dashboard');

        $this->assertTrue(auth('admin')->check());
        $this->assertFalse(auth('owner')->check());
    }

    public function test_owner_signs_in_through_the_shared_form(): void
    {
        $this->makeOwner();

        $this->post('/login', ['email' => 'owner@t.local', 'password' => 'secret123'])
            ->assertRedirect('/dashboard');

        $this->assertTrue(auth('owner')->check());
        $this->assertFalse(auth('admin')->check());
    }

    public function test_bad_credentials_are_rejected(): void
    {
        $this->makeOwner();

        $this->from('/login')
            ->post('/login', ['email' => 'owner@t.local', 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertFalse(auth('owner')->check());
        $this->assertFalse(auth('admin')->check());
    }

    public function test_legacy_admin_login_url_redirects_to_unified_login(): void
    {
        $this->get('/admin/login')->assertRedirect(route('login'));
    }

    public function test_guests_are_sent_to_the_single_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
    }
}
