<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * bookings.status moves from a DB-enforced ENUM/CHECK to a plain string
 * column, ahead of adding 'checked_in' and 'no_show' (Phase 5 — shared-room
 * advance booking). The state machine is already fully owned by application
 * code (BookingController::updateStatus()'s transition table); the DB-level
 * constraint is redundant defense that has already caused one real
 * incident in this codebase (2026_07_01_000003_update_room_types.php —
 * MySQL rejected rows holding a value outside the enum mid-migration).
 * Converting once now avoids paying that risk again for every future status
 * value.
 *
 * This migration is purely a column-type change — no new status values are
 * written by any code path yet, so there is nothing to remap here (unlike
 * the room-type fix, which renamed existing data).
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // Existing enum values are always valid VARCHAR values, so this
            // is a single safe widen — no data touches the disk.
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending'");

            return;
        }

        if ($driver === 'sqlite') {
            // SQLite's enum() compiles to a CHECK constraint, which SQLite
            // cannot alter in place — same table-rebuild dance the room-type
            // fix used. Columns are named explicitly in the INSERT rather
            // than relying on `SELECT *` column-order matching: SQLite does
            // not honor Blueprint's after() on an ALTER-added column (it
            // always appends at the physical end), so the live column order
            // does not match the order columns were declared in across this
            // table's migration history — named columns sidestep that
            // entirely. PRAGMA foreign_keys off/on because
            // shared_sessions.booking_id and sales.booking_id both
            // reference this table.
            DB::statement('PRAGMA foreign_keys = OFF');

            Schema::create('bookings_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
                $table->foreignId('hotspot_user_id')->nullable()->constrained('hotspot_users')->nullOnDelete();
                $table->unsignedInteger('party_size')->default(1);
                $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
                $table->date('booking_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->decimal('price_per_hour', 8, 2);
                $table->decimal('total_hours', 5, 2);
                $table->decimal('total_price', 10, 2);
                $table->string('status')->default('pending');
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->index(['room_id', 'booking_date']);
                $table->index(['owner_id', 'booking_date']);
            });

            DB::statement('
                INSERT INTO bookings_new (
                    id, owner_id, hotspot_user_id, party_size, room_id, booking_date,
                    start_time, end_time, price_per_hour, total_hours, total_price,
                    status, notes, created_at, updated_at
                )
                SELECT
                    id, owner_id, hotspot_user_id, party_size, room_id, booking_date,
                    start_time, end_time, price_per_hour, total_hours, total_price,
                    status, notes, created_at, updated_at
                FROM bookings
            ');
            Schema::drop('bookings');
            Schema::rename('bookings_new', 'bookings');

            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        // Only safe to roll back while every row still holds one of the
        // original four values — true immediately after this migration
        // (nothing writes 'checked_in'/'no_show' yet), same assumption the
        // room-type migration's down() made about its own renamed values.
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','completed','cancelled') DEFAULT 'pending'");

            return;
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            Schema::create('bookings_old', function (Blueprint $table) {
                $table->id();
                $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
                $table->foreignId('hotspot_user_id')->nullable()->constrained('hotspot_users')->nullOnDelete();
                $table->unsignedInteger('party_size')->default(1);
                $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
                $table->date('booking_date');
                $table->time('start_time');
                $table->time('end_time');
                $table->decimal('price_per_hour', 8, 2);
                $table->decimal('total_hours', 5, 2);
                $table->decimal('total_price', 10, 2);
                $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->index(['room_id', 'booking_date']);
                $table->index(['owner_id', 'booking_date']);
            });

            DB::statement('
                INSERT INTO bookings_old (
                    id, owner_id, hotspot_user_id, party_size, room_id, booking_date,
                    start_time, end_time, price_per_hour, total_hours, total_price,
                    status, notes, created_at, updated_at
                )
                SELECT
                    id, owner_id, hotspot_user_id, party_size, room_id, booking_date,
                    start_time, end_time, price_per_hour, total_hours, total_price,
                    status, notes, created_at, updated_at
                FROM bookings
            ');
            Schema::drop('bookings');
            Schema::rename('bookings_old', 'bookings');

            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};
