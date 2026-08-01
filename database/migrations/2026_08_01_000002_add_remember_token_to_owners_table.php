<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `owners` is an Authenticatable guard but was created without the column
     * Laravel writes on "remember me" login and on password reset, so both
     * paths threw "no such column: remember_token". `admins` already has it.
     */
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->rememberToken()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });
    }
};
