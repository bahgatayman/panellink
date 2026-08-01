<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Default feature keys enabled for owners on this plan (hotspot|workspace|booking).
            $table->json('features')->nullable()->after('max_members');
            // Per-plan limits. 0 = unlimited (keeps existing plans unrestricted by default).
            $table->integer('max_workspaces')->default(0)->after('features');
            $table->integer('max_rooms')->default(0)->after('max_workspaces');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['features', 'max_workspaces', 'max_rooms']);
        });
    }
};
