<?php

namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Custom branded error pages (resources/views/errors/*.blade.php), reusing
 * layouts.auth so a 404/500/etc. gets the same polished, RTL-aware look as
 * login/register rather than Laravel's default plain fallback. Laravel picks
 * these up automatically by convention (errors/{status}.blade.php) — no
 * exception-handler wiring needed.
 */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unmatched_route_renders_the_branded_404_page(): void
    {
        $response = $this->get('/this-route-does-not-exist-xyz');

        $response->assertStatus(404);
        $response->assertSee(__('app.error.404_heading'));
        $response->assertSee('Go to Login', false);
    }

    public function test_404_page_offers_dashboard_link_when_authenticated(): void
    {
        $plan = Plan::create([
            'name' => 'Test', 'slug' => 'test-'.uniqid(), 'max_members' => 100,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1,
            'features' => [], 'max_workspaces' => 0, 'max_rooms' => 0, 'max_products' => 0,
        ]);
        $owner = Owner::create([
            'name' => 'Owner', 'email' => 'o'.uniqid().'@t.local', 'password' => 'secret123',
            'business_name' => 'Space', 'plan_id' => $plan->id, 'is_active' => true,
            'subscription_starts_at' => now(), 'subscription_expires_at' => now()->addMonth(),
        ]);

        $response = $this->actingAs($owner, 'owner')->get('/this-route-does-not-exist-xyz');

        $response->assertStatus(404);
        $response->assertSee(__('app.error.go_to_dashboard'));
        $response->assertSee('href="/dashboard"', false);
    }

    public function test_every_error_view_renders_without_error(): void
    {
        foreach (['403', '404', '419', '429', '500', '503'] as $code) {
            $html = view('errors.'.$code)->render();

            // e(): Blade's {{ }} HTML-escapes output (e.g. 503's "We'll" ->
            // "We&#039;ll"), so the raw translated string must be escaped
            // the same way before comparing against the rendered HTML.
            $this->assertStringContainsString(e(__('app.error.'.$code.'_heading')), $html);
        }
    }

    public function test_error_pages_render_in_arabic(): void
    {
        $html = $this->withSession(['locale' => 'ar'])->get('/this-route-does-not-exist-xyz')->getContent();

        $this->assertStringContainsString(__('app.error.404_heading', [], 'ar'), $html);
        $this->assertStringNotContainsString('Page Not Found', $html);
    }
}
