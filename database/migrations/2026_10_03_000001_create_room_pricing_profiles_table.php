<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Room Pricing Profiles: optional, owner-named alternative hourly rates for
     * one room ("Photography — 700/hr"), picked per booking/session. A room
     * with no profiles prices exactly as before.
     */
    public function up(): void
    {
        Schema::create('room_pricing_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('name', 60);
            $table->decimal('price_per_hour', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['room_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_pricing_profiles');
    }
};
