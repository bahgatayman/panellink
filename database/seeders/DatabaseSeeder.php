<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            FeatureSeeder::class,
            PlanSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            DemoUserSeeder::class,
        ]);
    }
}
