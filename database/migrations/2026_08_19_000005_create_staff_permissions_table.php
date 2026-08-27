<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamp('granted_at');
            $table->foreignId('granted_by_owner_id')->nullable()->constrained('owners')->nullOnDelete();
            $table->timestamps();

            $table->unique(['staff_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_permissions');
    }
};
