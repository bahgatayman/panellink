<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A pure addition (unlike 2026_07_01_000003_update_room_types, which was
     * a rename and needed a widen->remap->narrow dance) — 'studio' is simply
     * added to the allowed set, permanently. SQLite (dev) stores `type` as a
     * bare string with no engine-level enum, so this is a no-op there;
     * MySQL (production) enforces a real ENUM and needs the column widened.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rooms MODIFY COLUMN type ENUM('meeting','training','shared','office','studio') DEFAULT 'shared'");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rooms MODIFY COLUMN type ENUM('meeting','training','shared','office') DEFAULT 'shared'");
        }
    }
};
