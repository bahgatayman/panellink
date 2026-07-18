<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE rooms MODIFY COLUMN type ENUM('meeting','training','shared','office') DEFAULT 'shared'");
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            Schema::create('rooms_new', function ($table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
                $table->string('name');
                $table->string('type')->default('shared');
                $table->integer('capacity')->default(1);
                $table->decimal('price_per_hour', 8, 2)->default(0);
                $table->string('description')->nullable();
                $table->boolean('is_available')->default(true);
                $table->timestamps();
            });

            DB::statement('INSERT INTO rooms_new SELECT * FROM rooms');
            Schema::drop('rooms');
            Schema::rename('rooms_new', 'rooms');

            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE rooms MODIFY COLUMN type ENUM('hot_desk','private_office','meeting_room','event_space') DEFAULT 'hot_desk'");
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            Schema::create('rooms_old', function ($table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
                $table->foreignId('owner_id')->constrained('owners')->cascadeOnDelete();
                $table->string('name');
                $table->string('type')->default('hot_desk');
                $table->integer('capacity')->default(1);
                $table->decimal('price_per_hour', 8, 2)->default(0);
                $table->string('description')->nullable();
                $table->boolean('is_available')->default(true);
                $table->timestamps();
            });

            DB::statement('INSERT INTO rooms_old SELECT * FROM rooms');
            Schema::drop('rooms');
            Schema::rename('rooms_old', 'rooms');

            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
};
