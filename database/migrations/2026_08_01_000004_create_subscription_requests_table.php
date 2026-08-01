<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Owner-initiated renewal requests.
     *
     * There is no payment gateway: an owner picks a plan + duration from the
     * expired/plans screen, an admin confirms payment out of band and approves,
     * and approval runs the same renewal path as the admin-side renew form.
     */
    public function up(): void
    {
        Schema::create('subscription_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->unsignedSmallInteger('months');
            // Quoted price at request time, so a later plan price change does not
            // silently alter what the owner asked to pay.
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending | approved | rejected | cancelled
            $table->text('note')->nullable();             // owner's message
            $table->text('admin_note')->nullable();       // reason on reject
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_requests');
    }
};
