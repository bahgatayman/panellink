<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Production only ever runs `php artisan migrate`, never `db:seed` — the
 * exact gap this app has hit twice before for permissions/roles. These
 * migrations alone (no seeder) must fully populate the coupons.* permission
 * catalog and grant them to the system roles, and running them twice must
 * never duplicate a row or error.
 */
class CouponPermissionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrating_alone_seeds_every_coupon_permission_and_role_grant(): void
    {
        $this->assertSame(5, DB::table('permissions')->where('key', 'like', 'coupons.%')->count());
        $this->assertDatabaseHas('permissions', ['key' => 'coupons.view']);
        $this->assertDatabaseHas('permissions', ['key' => 'coupons.create']);
        $this->assertDatabaseHas('permissions', ['key' => 'coupons.edit']);
        $this->assertDatabaseHas('permissions', ['key' => 'coupons.delete']);
        $this->assertDatabaseHas('permissions', ['key' => 'coupons.apply']);

        $manager = DB::table('roles')->whereNull('owner_id')->where('key', 'manager')->first();
        $this->assertNotNull($manager);
        $grantedKeys = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', $manager->id)
            ->where('permissions.key', 'like', 'coupons.%')
            ->pluck('permissions.key');
        $this->assertEqualsCanonicalizing(
            ['coupons.view', 'coupons.create', 'coupons.edit', 'coupons.delete', 'coupons.apply'],
            $grantedKeys->all()
        );

        $receptionist = DB::table('roles')->whereNull('owner_id')->where('key', 'receptionist')->first();
        $receptionistKeys = DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_permissions.role_id', $receptionist->id)
            ->where('permissions.key', 'like', 'coupons.%')
            ->pluck('permissions.key');
        $this->assertEqualsCanonicalizing(['coupons.apply'], $receptionistKeys->all());
    }

    public function test_running_the_coupon_permission_migrations_twice_is_idempotent(): void
    {
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_09_30_000006_seed_coupon_permissions.php',
            '--force' => true,
        ]);
        Artisan::call('migrate', [
            '--path' => 'database/migrations/2026_09_30_000007_add_coupons_to_system_roles.php',
            '--force' => true,
        ]);

        $this->assertSame(1, DB::table('permissions')->where('key', 'coupons.view')->count());

        $manager = DB::table('roles')->whereNull('owner_id')->where('key', 'manager')->first();
        $viewPermission = DB::table('permissions')->where('key', 'coupons.view')->first();
        $this->assertSame(
            1,
            DB::table('role_permissions')->where('role_id', $manager->id)->where('permission_id', $viewPermission->id)->count()
        );
    }
}
