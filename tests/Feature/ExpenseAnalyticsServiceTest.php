<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Owner;
use App\Models\Plan;
use App\Models\Sale;
use App\Services\AnalyticsPeriod;
use App\Services\ExpenseAnalyticsService;
use App\Services\RevenueAnalyticsService;
use Carbon\Carbon;
use Database\Seeders\FeatureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseAnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseAnalyticsService $expenses;

    private RevenueAnalyticsService $revenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FeatureSeeder::class);
        $this->expenses = app(ExpenseAnalyticsService::class);
        $this->revenue = app(RevenueAnalyticsService::class);
    }

    private function owner(): Owner
    {
        $plan = Plan::create([
            'name' => 'Test', 'slug' => 'test-'.uniqid(), 'max_members' => 100,
            'price_per_month' => 0, 'is_active' => true, 'sort_order' => 1,
            'features' => ['workspace', 'booking', 'sales'],
            'max_workspaces' => 0, 'max_rooms' => 0, 'max_products' => 0,
        ]);

        $owner = Owner::create([
            'name' => 'Owner', 'email' => 'o'.uniqid().'@t.local', 'password' => 'secret123',
            'business_name' => 'Space', 'plan_id' => $plan->id, 'is_active' => true,
            'subscription_starts_at' => now(), 'subscription_expires_at' => now()->addMonth(),
        ]);

        foreach ($plan->features as $key) {
            $owner->enableFeature($key);
        }

        return $owner;
    }

    private function category(Owner $owner, string $name = 'Electricity'): ExpenseCategory
    {
        return ExpenseCategory::create(['owner_id' => $owner->id, 'name' => $name.'-'.uniqid()]);
    }

    private function expense(Owner $owner, string $date, float $amount, ?ExpenseCategory $category = null): Expense
    {
        return Expense::create([
            'owner_id' => $owner->id,
            'expense_category_id' => $category?->id,
            'amount' => $amount,
            'expense_date' => $date,
            'note' => null,
        ]);
    }

    public function test_total_expenses_only_counts_the_owners_rows_in_period(): void
    {
        $owner = $this->owner();
        $other = $this->owner();

        $this->expense($owner, '2026-08-10', 100.0);
        $this->expense($owner, '2026-09-01', 999.0); // outside period
        $this->expense($other, '2026-08-10', 500.0); // other owner

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertSame(100.0, $this->expenses->totalExpenses($owner, $period));
    }

    public function test_total_expenses_filters_by_expense_date_not_created_at(): void
    {
        $owner = $this->owner();

        $inWindow = $this->expense($owner, '2026-08-10', 100.0);
        $inWindow->forceFill(['created_at' => Carbon::parse('2026-01-01')])->save();

        $outsideWindow = $this->expense($owner, '2026-01-05', 50.0);
        $outsideWindow->forceFill(['created_at' => Carbon::parse('2026-08-15')])->save();

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertSame(100.0, $this->expenses->totalExpenses($owner, $period));
    }

    public function test_expenses_by_category_groups_and_falls_back_to_uncategorized(): void
    {
        $owner = $this->owner();
        $electricity = $this->category($owner, 'Electricity');

        $this->expense($owner, '2026-08-10', 100.0, $electricity);
        $this->expense($owner, '2026-08-11', 50.0, $electricity);
        $this->expense($owner, '2026-08-12', 20.0, null);

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));
        $byCategory = $this->expenses->expensesByCategory($owner, $period);

        $this->assertCount(2, $byCategory);

        $named = collect($byCategory)->firstWhere('category_id', $electricity->id);
        $uncategorized = collect($byCategory)->firstWhere('category_id', null);

        $this->assertSame(150.0, $named['amount']);
        $this->assertSame(20.0, $uncategorized['amount']);
        $this->assertSame(__('app.expenses.uncategorized'), $uncategorized['name']);
    }

    /**
     * Matches the feature request's own worked example: Revenue 25,000 -
     * Expenses 7,500 = Net 17,500 for the same period.
     */
    public function test_net_matches_the_realistic_worked_example(): void
    {
        $owner = $this->owner();

        Sale::create([
            'owner_id' => $owner->id, 'status' => 'completed',
            'subtotal' => 25000.0, 'total' => 25000.0, 'sold_at' => '2026-08-10 09:00:00',
        ]);

        $this->expense($owner, '2026-08-05', 3000.0);
        $this->expense($owner, '2026-08-15', 4500.0);

        $period = AnalyticsPeriod::custom(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $totalRevenue = $this->revenue->totalRevenue($owner, $period);
        $totalExpenses = $this->expenses->totalExpenses($owner, $period);

        $this->assertSame(25000.0, $totalRevenue);
        $this->assertSame(7500.0, $totalExpenses);
        $this->assertSame(17500.0, $totalRevenue - $totalExpenses);
    }
}
