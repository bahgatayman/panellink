<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            // NULL = system role (available to every owner). Non-null is reserved
            // for a future per-tenant custom-role feature; unused in V1.
            $table->foreignId('owner_id')->nullable()->constrained('owners')->cascadeOnDelete();
            $table->string('key');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['owner_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
