<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per owner per day-of-week (Carbon's native 0=Sunday..6=Saturday —
 * the one day-of-week convention already used elsewhere in this app, e.g.
 * Carbon::MONDAY/SUNDAY in the week-view calendar), describing that day's
 * open/closed status and hours. An owner with zero rows here is treated as
 * fully unrestricted by BusinessHoursService (feature not configured yet)
 * — the Settings save always writes all 7 rows atomically, so an owner is
 * only ever at 0 or 7 rows, never a partial state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->boolean('is_open')->default(false);
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('working_hours');
    }
};
