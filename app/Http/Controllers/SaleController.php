<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

/**
 * Retired in favor of the Financials module — kept only as a permanent
 * redirect so old /sales links (e.g. past notification action_url values)
 * never 404. Route names sales.index/sales.show are preserved pointing here.
 */
class SaleController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect('/financials/transactions');
    }

    public function show(int $id): RedirectResponse
    {
        $sale = Sale::where('id', $id)
            ->where('owner_id', TenantContext::id())
            ->first();

        if ($sale?->booking_id) {
            return redirect("/financials/transactions/{$sale->booking_id}");
        }

        return redirect('/financials/transactions');
    }
}
