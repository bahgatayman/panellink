<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();          // subscription_expiring, booking_today, ...
            $table->string('level')->default('info');  // info | success | warning | danger
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('action_url')->nullable();
            $table->string('reference')->nullable();   // idempotency key, scoped per owner
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'reference']);
            $table->index(['owner_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
