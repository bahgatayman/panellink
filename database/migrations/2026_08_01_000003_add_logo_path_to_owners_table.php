<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Owners can upload a brand image (logo / avatar) shown in the panel header
     * and on their profile. Stores the path on the `public` disk, not the file.
     */
    public function up(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('business_name');
        });
    }

    public function down(): void
    {
        Schema::table('owners', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
