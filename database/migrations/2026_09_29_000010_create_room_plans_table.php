<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Custom Plans: owner-defined fixed-price packages for one room
     * ("Team Package — 10 people / 5 hours → 500"). They sit alongside the
     * room's normal pricing (rooms.pricing_model/pricing_rules), never replace
     * it. Priced only through RoomPricingService::quotePlan().
     *
     * A plan is for exactly `people` people and lasts `duration_minutes`, or a
     * Full Day (that date's working hours) when is_full_day is set.
     */
    public function up(): void
    {
        Schema::create('room_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('name', 80)->nullable();
            $table->unsignedInteger('people');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->boolean('is_full_day')->default(false);
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['room_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_plans');
    }
};
