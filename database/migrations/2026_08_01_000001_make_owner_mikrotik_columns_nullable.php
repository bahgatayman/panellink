<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MikroTik becomes an optional (hotspot-only) feature: an owner can exist
     * without router credentials. Make the three string columns nullable.
     * `mikrotik_port` keeps its default(8728) and stays non-null.
     */
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->string('mikrotik_host')->nullable()->change();
            $table->string('mikrotik_username')->nullable()->change();
            $table->string('mikrotik_password')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Best-effort revert; will fail if any owner has null credentials.
        Schema::table('owners', function (Blueprint $table) {
            $table->string('mikrotik_host')->nullable(false)->change();
            $table->string('mikrotik_username')->nullable(false)->change();
            $table->string('mikrotik_password')->nullable(false)->change();
        });
    }
};
