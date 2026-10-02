<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * bookings.payment_method — 'cash' (everything that existed) or 'package'
     * (covered by a member's hour package). A package-covered booking records
     * the package value of the hours it used as total_price = amount_paid, so
     * revenue is recognised when hours are used, through the existing
     * amount_paid-based revenue reports — never as a second cash payment.
     *
     * member_package_id on bookings and shared_sessions — the package that
     * covers it (null on delete; package_usages keeps the full history).
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('payment_method', 16)->default('cash')->after('payment_status');
            $table->foreignId('member_package_id')->nullable()->after('payment_method')->constrained('member_packages')->nullOnDelete();
        });

        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->foreignId('member_package_id')->nullable()->constrained('member_packages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_package_id');
            $table->dropColumn('payment_method');
        });

        Schema::table('shared_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_package_id');
        });
    }
};
