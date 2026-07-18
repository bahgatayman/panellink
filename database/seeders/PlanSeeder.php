<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Free',     'slug' => 'free',     'max_members' => 10,  'price_per_month' => 0,   'sort_order' => 1],
            ['name' => 'Starter',  'slug' => 'starter',  'max_members' => 50,  'price_per_month' => 99,  'sort_order' => 2],
            ['name' => 'Growth',   'slug' => 'growth',   'max_members' => 150, 'price_per_month' => 149, 'sort_order' => 3],
            ['name' => 'Business', 'slug' => 'business', 'max_members' => 500, 'price_per_month' => 299, 'sort_order' => 4],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
