<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Member Hour Packages — prepaid booking time sold to members.
     *
     * package_templates  — the owner's reusable offers ("30 Hours Monthly").
     * member_packages    — what one member actually bought: every term is
     *                      SNAPSHOTTED from the template (name, minutes, price,
     *                      dates, rooms), so editing or deleting a template
     *                      never rewrites a member's package.
     * package_usages     — immutable audit trail of every balance change
     *                      (before/after, minutes, recognised revenue value).
     *
     * Balances are minutes (integers) — no floating-point hours. Status
     * (scheduled/active/expired/exhausted/cancelled) is derived, never stored.
     * Only HourPackageService changes used_minutes.
     */
    public function up(): void
    {
        Schema::create('package_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->string('name', 80);
            $table->unsignedInteger('total_minutes');
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('validity_days');
            $table->json('room_ids')->nullable(); // null = all rooms
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['owner_id', 'is_active']);
        });

        Schema::create('member_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignId('hotspot_user_id')->constrained('hotspot_users')->cascadeOnDelete();
            $table->foreignId('package_template_id')->nullable()->constrained('package_templates')->nullOnDelete();
            $table->string('name', 80);
            $table->unsignedInteger('total_minutes');
            $table->unsignedInteger('used_minutes')->default(0);
            $table->decimal('price_paid', 10, 2);
            $table->date('starts_on');
            $table->date('expires_on');
            $table->json('room_ids')->nullable(); // null = all rooms (snapshot)
            $table->string('notes', 500)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason', 255)->nullable();
            $table->string('created_by_type', 16)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'hotspot_user_id']);
            $table->index(['owner_id', 'expires_on']);
        });

        Schema::create('package_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignId('member_package_id')->constrained('member_packages')->cascadeOnDelete();
            $table->foreignId('hotspot_user_id')->constrained('hotspot_users')->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('shared_session_id')->nullable()->constrained('shared_sessions')->nullOnDelete();
            $table->string('action', 24);
            $table->integer('minutes');              // + used, − returned
            $table->integer('balance_before');       // remaining minutes
            $table->integer('balance_after');
            $table->decimal('value', 10, 2)->default(0); // revenue recognised (signed)
            $table->string('note', 255)->nullable();
            $table->string('created_by_type', 16)->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'member_package_id']);
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_usages');
        Schema::dropIfExists('member_packages');
        Schema::dropIfExists('package_templates');
    }
};
