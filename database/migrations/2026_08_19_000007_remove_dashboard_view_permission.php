<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * /dashboard is never permission-gated (see routes/web.php) — it's the
     * mandatory post-login landing page and CheckPermission's own denial
     * fallback target, so a staff member missing this one permission was
     * hitting an infinite redirect loop. The permission never should have
     * gated anything; removing the row cascades it out of every role's and
     * staff member's grants (role_permissions/staff_permissions FKs are
     * cascadeOnDelete).
     */
    public function up(): void
    {
        DB::table('permissions')->where('key', 'dashboard.view')->delete();
    }

    public function down(): void
    {
        DB::table('permissions')->insert([
            'key' => 'dashboard.view',
            'name' => 'View Dashboard',
            'group' => 'dashboard',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
