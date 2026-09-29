<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $ownerId = TenantContext::id();

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name,NULL,id,owner_id,'.$ownerId,
        ]);

        ExpenseCategory::create($validated + ['owner_id' => $ownerId, 'is_active' => true]);

        return redirect()->back()->with('success', __('app.expenses.category_created'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $ownerId = TenantContext::id();
        $category = ExpenseCategory::where('owner_id', $ownerId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:expense_categories,name,'.$id.',id,owner_id,'.$ownerId,
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', __('app.expenses.category_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $category = ExpenseCategory::where('owner_id', TenantContext::id())->findOrFail($id);

        $inUse = Expense::where('expense_category_id', $category->id)->count();

        if ($inUse > 0) {
            return back()->with('error', __('app.expenses.category_delete_error', ['count' => $inUse]));
        }

        $category->delete();

        return redirect()->back()->with('success', __('app.expenses.category_deleted'));
    }
}
