<?php
namespace Tests\Feature;

use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
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
        $plan = Plan::create(['name'=>'B','slug'=>'b','max_members'=>10,'price_per_month'=>0,'is_active'=>true,'sort_order'=>1]);
        $o = Owner::create(['name'=>'O','email'=>'w'.uniqid().'@t.local','password'=>'p','business_name'=>'B',
            'plan_id'=>$plan->id,'is_active'=>true,'subscription_starts_at'=>now(),
            'subscription_expires_at'=>now()->addMonth()])->fresh();
        $o->enableFeature('workspace');
        return $o->fresh();
    }

    public function test_room_cards_are_clickable_and_typed(): void
    {
        $o  = $this->owner();
        $ws = Workspace::create(['owner_id'=>$o->id,'name'=>'Hub','city'=>'Cairo','is_active'=>true]);
        $meeting = Room::create(['owner_id'=>$o->id,'workspace_id'=>$ws->id,'name'=>'Board A',
            'type'=>'meeting','capacity'=>8,'price_per_hour'=>120,'is_available'=>true]);
        $shared = Room::create(['owner_id'=>$o->id,'workspace_id'=>$ws->id,'name'=>'Open Floor',
            'type'=>'shared','capacity'=>10,'price_per_hour'=>40,'is_available'=>true]);

        $html = $this->actingAs($o,'owner')->get(route('workspaces.show', $ws))->assertOk()->getContent();

        // Each room card links to its edit screen.
        $this->assertStringContainsString('href="'.route('rooms.edit', [$ws, $meeting]).'"', $html);
        $this->assertStringContainsString('href="'.route('rooms.edit', [$ws, $shared]).'"', $html);

        // Translated type labels, not hardcoded English from the model.
        $this->assertStringContainsString('Meeting Room', $html);
        $this->assertStringContainsString('Shared Space', $html);

        // Stat strip figures: 2 rooms, 18 seats total, price range 40-120.
        $this->assertStringContainsString('Total capacity', $html);
        $this->assertStringContainsString('>18<', $html);
        $this->assertStringContainsString('40–120', $html);

        // Shared room shows live seat availability.
        $this->assertStringContainsString('10 of 10 seats free', $html);
    }

    public function test_room_types_translate_to_arabic(): void
    {
        $o  = $this->owner();
        $ws = Workspace::create(['owner_id'=>$o->id,'name'=>'Hub','is_active'=>true]);
        Room::create(['owner_id'=>$o->id,'workspace_id'=>$ws->id,'name'=>'R','type'=>'office',
            'capacity'=>2,'price_per_hour'=>50,'is_available'=>true]);

        $this->withSession(['locale' => 'ar'])
            ->actingAs($o,'owner')->get(route('workspaces.show', $ws))
            ->assertOk()
            ->assertSee('مكتب خاص');
    }
}
