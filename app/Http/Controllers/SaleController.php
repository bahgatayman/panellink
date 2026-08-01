<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        $ownerId = auth('owner')->id();

        $sales = Sale::where('owner_id', $ownerId)
            ->where('status', 'completed')
            ->with(['hotspotUser', 'booking'])
            ->withCount('items')
            ->latest('sold_at')
            ->paginate(20);

        $monthTotal = Sale::where('owner_id', $ownerId)
            ->where('status', 'completed')
            ->whereMonth('sold_at', now()->month)
            ->whereYear('sold_at', now()->year)
            ->sum('total');

        return view('sales.index', compact('sales', 'monthTotal'));
    }

    public function show(int $id): View
    {
        $sale = Sale::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->with(['items', 'hotspotUser', 'booking.room'])
            ->firstOrFail();

        return view('sales.show', compact('sale'));
    }
}
