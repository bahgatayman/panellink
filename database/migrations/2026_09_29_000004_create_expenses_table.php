<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
            // nullOnDelete is only a DB-level safety net for historical rows —
            // ExpenseCategoryController::destroy() blocks deleting a category
            // that still has expenses attached, so this should rarely fire.
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['owner_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
