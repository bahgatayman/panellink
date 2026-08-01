<?php
namespace Tests\Feature;

use App\Models\Booking;
use App\Models\HotspotUser;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Room;
use App\Models\Workspace;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClickableRowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_rows_are_clickable_and_view_link_is_gone(): void
    {
        $this->seed(FeatureSeeder::class);
        $plan = Plan::create(['name'=>'B','slug'=>'b','max_members'=>10,'price_per_month'=>0,'is_active'=>true,'sort_order'=>1]);
        $o = Owner::create(['name'=>'O','email'=>'b@t.local','password'=>'p','business_name'=>'B',
            'plan_id'=>$plan->id,'is_active'=>true,'subscription_starts_at'=>now(),
            'subscription_expires_at'=>now()->addMonth()])->fresh();
        $o->enableFeature('booking'); $o->enableFeature('workspace');
        $o = $o->fresh();

        $ws = Workspace::create(['owner_id'=>$o->id,'name'=>'Hub','is_active'=>true]);
        $room = Room::create(['owner_id'=>$o->id,'workspace_id'=>$ws->id,'name'=>'A','type'=>'meeting',
            'capacity'=>4,'price_per_hour'=>100,'is_available'=>true]);
        $member = HotspotUser::create(['owner_id'=>$o->id,'name'=>'Sara','phone'=>'0100','password'=>'0100',
            'speed_download'=>'10M','speed_upload'=>'5M','status'=>'active']);
        $pending = Booking::create(['owner_id'=>$o->id,'room_id'=>$room->id,'hotspot_user_id'=>$member->id,
            'booking_date'=>now()->addDay()->toDateString(),'start_time'=>'10:00','end_time'=>'12:00',
            'price_per_hour'=>100,'total_hours'=>2,'total_price'=>200,'status'=>'pending']);
        $done = Booking::create(['owner_id'=>$o->id,'room_id'=>$room->id,'hotspot_user_id'=>$member->id,
            'booking_date'=>now()->subDay()->toDateString(),'start_time'=>'10:00','end_time'=>'11:00',
            'price_per_hour'=>100,'total_hours'=>1,'total_price'=>100,'status'=>'completed']);

        $html = $this->actingAs($o,'owner')->get('/bookings')->assertOk()->getContent();

        // Rows carry the click target, and the date cell is still a real link.
        $this->assertStringContainsString('data-href="/bookings/'.$pending->id.'"', $html);
        $this->assertStringContainsString('data-href="/bookings/'.$done->id.'"', $html);
        $this->assertSame(2, substr_count($html, 'data-href="/bookings/'), 'one row-link per booking');

        // The redundant "View" action is gone; Edit survives only where editable.
        $this->assertStringNotContainsString('>View</a>', $html);
        $this->assertStringContainsString('/bookings/'.$pending->id.'/edit', $html);
        $this->assertStringNotContainsString('/bookings/'.$done->id.'/edit', $html);

        // The member link inside the row is untouched (handler must not swallow it).
        $this->assertStringContainsString('href="/users/'.$member->id.'"', $html);

        // Handler now lives in the layout, available to every page.
        $this->assertStringContainsString("querySelectorAll('tr.row-link')", $html);
    }

    public function test_users_page_still_gets_the_shared_handler(): void
    {
        $this->seed(FeatureSeeder::class);
        $plan = Plan::create(['name'=>'B','slug'=>'b2','max_members'=>10,'price_per_month'=>0,'is_active'=>true,'sort_order'=>1]);
        $o = Owner::create(['name'=>'O','email'=>'u@t.local','password'=>'p','business_name'=>'B',
            'plan_id'=>$plan->id,'is_active'=>true,'subscription_starts_at'=>now(),
            'subscription_expires_at'=>now()->addMonth()])->fresh();
        $o->enableFeature('booking');
        $o = $o->fresh();
        HotspotUser::create(['owner_id'=>$o->id,'name'=>'Sara','phone'=>'0101','password'=>'0101',
            'speed_download'=>'10M','speed_upload'=>'5M','status'=>'active']);

        $html = $this->actingAs($o,'owner')->get('/users')->assertOk()->getContent();
        $this->assertStringContainsString('class="row-link', $html);
        $this->assertSame(1, substr_count($html, "querySelectorAll('tr.row-link')"), 'handler defined once');
    }
}
