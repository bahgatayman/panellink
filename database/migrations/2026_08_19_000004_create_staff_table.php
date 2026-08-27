<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            $table->foreignId('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
