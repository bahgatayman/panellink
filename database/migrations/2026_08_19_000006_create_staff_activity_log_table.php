<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_activity_log', function (Blueprint $table) {
            $table->id();
            // Denormalized (not just derivable via staff_id) so every query stays
            // a single indexed lookup and tenant isolation holds even if the
            // acting staff row is later soft-deleted.
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            // Snapshotted at write time so "who did this" reads correctly forever,
            // independent of later edits/deletion of the actor's own record.
            $table->string('actor_name');
            $table->string('actor_email');
            $table->enum('actor_type', ['owner', 'staff']);
            $table->string('action');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['owner_id', 'created_at']);
            $table->index(['owner_id', 'staff_id', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_activity_log');
    }
};
