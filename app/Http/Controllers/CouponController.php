<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Owner;
use App\Models\Product;
use App\Models\Room;
use App\Services\CouponService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function __construct(private CouponService $coupons) {}

    public function index(Request $request): View
    {
        $owner = TenantContext::user();
        $search = $request->query('search');
        $status = $request->query('status');

        $coupons = Coupon::where('owner_id', $owner->id)
            ->withCount('usages')
            ->when($search, fn ($q) => $q->where('code', 'like', '%'.$this->coupons->normalizeCode($search).'%'))
            ->when($status, fn ($q) => $q->withStatus($status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $staff = auth('staff')->user();
        $canCreate = ! $staff || $staff->hasPermission('coupons.create');

        return view('coupons.index', [
            'coupons' => $coupons,
            'search' => $search,
            'status' => $status,
            'canCreate' => $canCreate,
            'canEdit' => ! $staff || $staff->hasPermission('coupons.edit'),
            'canDelete' => ! $staff || $staff->hasPermission('coupons.delete'),
            'newCoupon' => $canCreate ? new Coupon(['applies_to' => Coupon::SCOPE_BOTH, 'discount_type' => Coupon::TYPE_PERCENTAGE, 'is_active' => true]) : null,
            'roomGroups' => $canCreate ? $this->ownerRoomsGrouped($owner) : collect(),
            'productGroups' => $canCreate ? $this->ownerProductsGrouped($owner) : collect(),
            'selectedRoomIds' => [],
            'selectedProductIds' => [],
        ]);
    }

    public function create(): View
    {
        $owner = TenantContext::user();

        return view('coupons.create', [
            'coupon' => new Coupon(['applies_to' => Coupon::SCOPE_BOTH, 'discount_type' => Coupon::TYPE_PERCENTAGE, 'is_active' => true]),
            'roomGroups' => $this->ownerRoomsGrouped($owner),
            'productGroups' => $this->ownerProductsGrouped($owner),
            'selectedRoomIds' => [],
            'selectedProductIds' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $owner = TenantContext::user();
        $validated = $this->validated($request, $owner);

        $coupon = new Coupon(['owner_id' => $owner->id]);
        $this->persist($coupon, $validated, $request);

        return redirect()->route('coupons.index')->with('success', __('app.coupons.created'));
    }

    public function edit(int $id): View
    {
        $owner = TenantContext::user();
        $coupon = Coupon::where('owner_id', $owner->id)->with(['rooms', 'products'])->findOrFail($id);

        return view('coupons.edit', [
            'coupon' => $coupon,
            'roomGroups' => $this->ownerRoomsGrouped($owner),
            'productGroups' => $this->ownerProductsGrouped($owner),
            'selectedRoomIds' => $coupon->rooms->pluck('id')->all(),
            'selectedProductIds' => $coupon->products->pluck('id')->all(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $owner = TenantContext::user();
        $coupon = Coupon::where('owner_id', $owner->id)->findOrFail($id);
        $validated = $this->validated($request, $owner, $coupon->id);

        $this->persist($coupon, $validated, $request);

        return redirect()->route('coupons.index')->with('success', __('app.coupons.updated'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $coupon = Coupon::where('owner_id', TenantContext::id())->findOrFail($id);

        if ($coupon->usages()->exists()) {
            return back()->with('error', __('app.coupons.delete_blocked'));
        }

        $coupon->delete();

        return redirect()->route('coupons.index')->with('success', __('app.coupons.deleted'));
    }

    public function toggleActive(int $id): RedirectResponse
    {
        $coupon = Coupon::where('owner_id', TenantContext::id())->findOrFail($id);
        $coupon->update(['is_active' => ! $coupon->is_active]);

        return back()->with('success', __('app.coupons.toggled'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, Owner $owner, ?int $id = null): array
    {
        $request->merge(['code' => $this->coupons->normalizeCode($request->input('code'))]);

        $discountType = $request->input('discount_type');

        return $request->validate([
            'code' => [
                'required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupons', 'code')->where('owner_id', $owner->id)->ignore($id),
            ],
            'discount_type' => 'required|in:'.Coupon::TYPE_PERCENTAGE.','.Coupon::TYPE_FIXED,
            'discount_value' => $discountType === Coupon::TYPE_PERCENTAGE
                ? 'required|numeric|gt:0|max:100'
                : 'required|numeric|gt:0|max:999999.99',
            'applies_to' => 'required|in:'.Coupon::SCOPE_ROOMS.','.Coupon::SCOPE_PRODUCTS.','.Coupon::SCOPE_BOTH,
            'room_scope' => 'required_if:applies_to,'.Coupon::SCOPE_ROOMS.','.Coupon::SCOPE_BOTH.'|in:all,specific',
            'product_scope' => 'required_if:applies_to,'.Coupon::SCOPE_PRODUCTS.','.Coupon::SCOPE_BOTH.'|in:all,specific',
            'room_ids' => 'required_if:room_scope,specific|array',
            'room_ids.*' => ['integer', 'distinct', Rule::exists('rooms', 'id')->where('owner_id', $owner->id)],
            'product_ids' => 'required_if:product_scope,specific|array',
            'product_ids.*' => ['integer', 'distinct', Rule::exists('products', 'id')->where('owner_id', $owner->id)],
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'usage_limit' => 'nullable|integer|min:0',
            'per_customer_limit' => 'nullable|integer|min:0',
            'minimum_spend' => 'nullable|numeric|min:0',
        ]);
    }

    private function persist(Coupon $coupon, array $validated, Request $request): void
    {
        $coupon->fill([
            'code' => $validated['code'],
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'applies_to' => $validated['applies_to'],
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'usage_limit' => $validated['usage_limit'] ?? null,
            'per_customer_limit' => $validated['per_customer_limit'] ?? null,
            'minimum_spend' => $validated['minimum_spend'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);
        $coupon->save();

        $roomIds = $coupon->appliesTo(Coupon::SCOPE_ROOMS) && ($validated['room_scope'] ?? null) === 'specific'
            ? ($validated['room_ids'] ?? [])
            : [];
        $productIds = $coupon->appliesTo(Coupon::SCOPE_PRODUCTS) && ($validated['product_scope'] ?? null) === 'specific'
            ? ($validated['product_ids'] ?? [])
            : [];

        $coupon->rooms()->sync($roomIds);
        $coupon->products()->sync($productIds);
    }

    /** @return Collection<string, Collection<int, Room>> */
    private function ownerRoomsGrouped(Owner $owner): Collection
    {
        return Room::where('owner_id', $owner->id)
            ->with('workspace')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Room $room) => $room->workspace?->name ?? __('app.workspace.rooms'));
    }

    /** @return Collection<string, Collection<int, Product>> */
    private function ownerProductsGrouped(Owner $owner): Collection
    {
        return Product::where('owner_id', $owner->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Product $product) => $product->typeLabel());
    }
}
