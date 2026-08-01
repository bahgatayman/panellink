<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Old → new room type names. Existing rows must be remapped, not just the
     * column definition: MySQL rejects the ALTER with "Data truncated for column
     * 'type'" as soon as one row still holds an old value.
     */
    private const RENAMES = [
        'hot_desk'       => 'shared',
        'private_office' => 'office',
        'meeting_room'   => 'meeting',
        'event_space'    => 'training',
    ];

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // Three steps, because a row cannot be set to a value the current
            // enum does not allow: widen to accept both vocabularies, remap the
            // data, then narrow to the new one.
            DB::statement("ALTER TABLE rooms MODIFY COLUMN type ENUM(
                'hot_desk','private_office','meeting_room','event_space',
                'meeting','training','shared','office'
            ) DEFAULT 'shared'");

            foreach (self::RENAMES as $old => $new) {
                DB::table('rooms')->where('type', $old)->update(['type' => $new]);
            }

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

            // SQLite has no enum, so old values would otherwise survive as plain
            // strings and stop matching isShared() / typeLabel().
            foreach (self::RENAMES as $old => $new) {
                DB::table('rooms')->where('type', $old)->update(['type' => $new]);
            }

            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE rooms MODIFY COLUMN type ENUM(
                'hot_desk','private_office','meeting_room','event_space',
                'meeting','training','shared','office'
            ) DEFAULT 'hot_desk'");

            foreach (array_flip(self::RENAMES) as $new => $old) {
                DB::table('rooms')->where('type', $new)->update(['type' => $old]);
            }

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
