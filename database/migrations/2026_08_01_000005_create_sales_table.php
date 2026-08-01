<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            // The attach point. Nullable so a sale can later stand alone (walk-in POS).
            $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->foreignId('hotspot_user_id')->nullable()->constrained('hotspot_users')->nullOnDelete();
            $table->enum('status', ['open', 'completed', 'cancelled'])->default('completed');
            $table->decimal('subtotal', 10, 2)->default(0);
            // Discount/tax are 0 in v1; present so the total formula is future-proof.
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            // Realization time — drives revenue period grouping independently of booking_date.
            $table->timestamp('sold_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'sold_at']);
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
