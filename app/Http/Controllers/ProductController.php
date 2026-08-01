<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::where('owner_id', auth('owner')->id())
            ->orderBy('name')
            ->paginate(15);

        return view('sales.products.index', compact('products'));
    }

    public function create(): View
    {
        return view('sales.products.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! auth('owner')->user()->canAddMoreProducts()) {
            return back()->withInput()->with('error', __('app.plan_limit.products'));
        }

        $data = $this->validateProduct($request);

        Product::create(array_merge($data, [
            'owner_id' => auth('owner')->id(),
        ]));

        return redirect('/products')->with('success', __('app.sales.product_created'));
    }

    public function edit(int $id): View
    {
        $product = Product::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        return view('sales.products.edit', compact('product'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $product = Product::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $product->update($this->validateProduct($request));

        return redirect('/products')->with('success', __('app.sales.product_updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $product = Product::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $product->delete();

        return redirect('/products')->with('success', __('app.sales.product_deleted'));
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $product = Product::where('id', $id)
            ->where('owner_id', auth('owner')->id())
            ->firstOrFail();

        $product->update(['is_active' => ! $product->is_active]);

        return back()->with('success', __('app.sales.product_updated'));
    }

    private function validateProduct(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:product,service',
            'price' => 'required|numeric|min:0',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:1000',
        ]);

        // Unchecked checkboxes are absent from the request, so resolve explicitly.
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
