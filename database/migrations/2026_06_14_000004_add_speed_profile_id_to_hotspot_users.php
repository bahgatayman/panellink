<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotspot_users', function (Blueprint $table) {
            $table->foreignId('speed_profile_id')->nullable()->constrained('speed_profiles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('hotspot_users', function (Blueprint $table) {
            $table->dropForeign(['speed_profile_id']);
            $table->dropColumn('speed_profile_id');
        });
    }
};
