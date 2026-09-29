@extends('layouts.app')

@section('page-title', $product->name)

@section('content')
    @php
        $staff = auth('staff')->user();
        $canManage = ! $staff || $staff->hasPermission('products.manage');
        $status = $product->stockStatus();
        $tracked = $status !== 'untracked';
        $margin = $product->marginPercent();
        $statusTone = ['in_stock' => 'ok', 'low' => 'warn', 'out' => 'danger'][$status] ?? 'neutral';
    @endphp

    <a href="{{ route('products.index') }}" class="ls-link" style="display:inline-block; margin-bottom: var(--space-4)">&larr; {{ __('app.sales.back_to_products') }}</a>

    <x-ui.page-header :title="$product->name" :subtitle="($product->isService() ? __('app.sales.service') : __('app.sales.product')).($product->sku ? ' · '.$product->sku : '')">
        <x-slot:actions>
            @if ($canManage)
                <x-ui.button :href="route('products.edit', $product)">{{ __('app.common.edit') }}</x-ui.button>
                @if ($tracked)
                    <x-ui.button variant="primary" data-ls-open="adjust-stock">{{ __('app.inventory.adjust') }}</x-ui.button>
                @endif
            @endif
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    {{-- Pricing + stock at a glance --}}
    <div class="ls-strip">
        @if ($tracked)
            <div>
                <span class="ls-strip-label">{{ __('app.inventory.current_stock') }}</span>
                <span class="ls-strip-value {{ $status === 'out' ? 'is-danger' : ($status === 'low' ? 'is-warn' : '') }}">{{ $product->stock_quantity }}</span>
                <span class="ls-strip-meta"><x-ui.badge :tone="$statusTone" :dot="false">{{ __('app.inventory.status.'.$status) }}</x-ui.badge></span>
            </div>
        @endif
        <div>
            <span class="ls-strip-label">{{ __('app.inventory.purchase_price') }}</span>
            <span class="ls-strip-value"><x-ui.money :amount="$product->purchase_price" /></span>
        </div>
        <div>
            <span class="ls-strip-label">{{ __('app.inventory.selling_price') }}</span>
            <span class="ls-strip-value"><x-ui.money :amount="$product->price" /></span>
        </div>
        <div>
            <span class="ls-strip-label">{{ __('app.inventory.profit_unit') }}</span>
            <span class="ls-strip-value {{ $product->profitPerUnit() < 0 ? 'is-danger' : 'is-revenue' }}"><x-ui.money :amount="$product->profitPerUnit()" /></span>
            <span class="ls-strip-meta">{{ __('app.inventory.margin') }} {{ $margin !== null ? $margin.'%' : '—' }}</span>
        </div>
        @if ($tracked)
            <div>
                <span class="ls-strip-label">{{ __('app.inventory.inventory_value') }}</span>
                <span class="ls-strip-value"><x-ui.money :amount="$product->inventoryValue()" /></span>
                <span class="ls-strip-meta">{{ __('app.inventory.inventory_value_hint') }}</span>
            </div>
        @endif
    </div>

    <div class="ls-inv-cols">
        {{-- Sales to date (profit from each line's own cost snapshot) --}}
        <section class="ls-card">
            <div class="ls-card-head"><h2 class="ls-card-title">{{ __('app.inventory.sales_to_date') }}</h2></div>
            <div class="ls-card-body">
                <dl class="ls-kv">
                    <dt>{{ __('app.inventory.sold_units') }}</dt><dd>{{ $sold['units'] }}</dd>
                    <dt>{{ __('app.inventory.sold_revenue') }}</dt><dd><x-ui.money :amount="$sold['revenue']" /></dd>
                    <dt>{{ __('app.inventory.sold_profit') }}</dt><dd class="ls-inv-profit"><x-ui.money :amount="$sold['profit']" /></dd>
                </dl>
                <p class="ls-hint" style="margin: var(--space-3) 0 0">{{ __('app.inventory.sales_basis') }}</p>
                @if ($sold['unknown_cost_units'] > 0)
                    <p class="ls-hint" style="margin: var(--space-3) 0 0">{{ trans_choice('app.inventory.unknown_cost', $sold['unknown_cost_units'], ['count' => $sold['unknown_cost_units']]) }}</p>
                @endif
            </div>
        </section>

        @if ($tracked)
            {{-- What the stock on hand is worth --}}
            <section class="ls-card">
                <div class="ls-card-head"><h2 class="ls-card-title">{{ __('app.inventory.inventory') }}</h2></div>
                <div class="ls-card-body">
                    <dl class="ls-kv">
                        <dt>{{ __('app.inventory.inventory_value') }}</dt><dd><x-ui.money :amount="$product->inventoryValue()" /></dd>
                        <dt>{{ __('app.inventory.potential_revenue') }}</dt><dd><x-ui.money :amount="$product->potentialRevenue()" /></dd>
                        <dt>{{ __('app.inventory.potential_profit') }}</dt><dd class="ls-inv-profit"><x-ui.money :amount="$product->potentialProfit()" /></dd>
                        @if ($product->low_stock_threshold !== null)
                            <dt>{{ __('app.inventory.low_alert') }}</dt><dd>{{ $product->low_stock_threshold }}</dd>
                        @endif
                    </dl>
                </div>
            </section>
        @endif
    </div>

    @unless ($tracked)
        <x-ui.banner tone="info">{{ __('app.inventory.not_tracked_hint') }}</x-ui.banner>
    @endunless

    {{-- Stock history --}}
    @if ($tracked || $movements->total())
        <section class="ls-card" style="margin-top: var(--space-5)">
            <div class="ls-card-head"><h2 class="ls-card-title">{{ __('app.inventory.history') }}</h2></div>
            @if ($movements->isEmpty())
                <div class="ls-card-body"><p class="ls-hint" style="margin:0">{{ __('app.inventory.history_empty') }}</p></div>
            @else
                <div class="ls-table-wrap" style="margin-top: var(--space-3)">
                    <table class="ls-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.inventory.col_date') }}</th>
                                <th class="is-num">{{ __('app.inventory.col_change') }}</th>
                                <th>{{ __('app.inventory.col_type') }}</th>
                                <th>{{ __('app.inventory.col_ref') }}</th>
                                <th>{{ __('app.inventory.col_by') }}</th>
                                <th class="is-num">{{ __('app.inventory.col_after') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($movements as $m)
                                <tr>
                                    <td class="ls-num" title="{{ $m->created_at->format('M d, Y H:i') }}">{{ $m->created_at->format('M d, H:i') }}</td>
                                    <td class="is-num"><span class="ls-inv-change {{ $m->quantity_change >= 0 ? 'is-in' : 'is-out' }}"><bdi dir="ltr">{{ $m->quantity_change > 0 ? '+' : '' }}{{ $m->quantity_change }}</bdi></span></td>
                                    <td>
                                        {{ $m->label() }}
                                        @if ($m->reason)<span class="ls-faint"> · {{ __('app.inventory.reasons.'.$m->reason) }}</span>@endif
                                        @if ($m->note)<div class="ls-faint" style="font-size:12.5px">{{ $m->note }}</div>@endif
                                    </td>
                                    <td>
                                        @if ($m->sale?->booking_id)
                                            <a class="ls-link" href="/bookings/{{ $m->sale->booking_id }}">#{{ str_pad($m->sale->booking_id, 4, '0', STR_PAD_LEFT) }}</a>
                                        @elseif ($m->sale?->shared_session_id)
                                            <a class="ls-link" href="{{ route('active-sessions.index') }}">{{ __('app.sales.walk_in') }}</a>
                                        @else
                                            <span class="ls-faint">—</span>
                                        @endif
                                    </td>
                                    <td class="ls-faint">{{ $m->actorName() ?? '—' }}</td>
                                    <td class="is-num">{{ $m->new_quantity }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="ls-card-body" style="padding-top: var(--space-3)">{{ $movements->links() }}</div>
            @endif
        </section>
    @endif

    {{-- Adjust stock: restock (+) or remove with a reason (−); never below zero (checked server-side). --}}
    @if ($tracked && $canManage)
        <x-ui.modal id="adjust-stock" :title="__('app.inventory.adjust')" :subtitle="$product->name" size="narrow">
            <form method="POST" action="{{ route('products.stock', $product) }}" id="adjust-form" class="ls-stack" data-ls-busy>
                @csrf
                <div class="ls-inv-seg" role="radiogroup" aria-label="{{ __('app.inventory.adjust') }}">
                    <label><input type="radio" name="direction" value="restock" @checked(old('direction', 'restock') === 'restock')><span>+ {{ __('app.inventory.restock') }}</span></label>
                    <label><input type="radio" name="direction" value="remove" @checked(old('direction') === 'remove')><span>− {{ __('app.inventory.remove') }}</span></label>
                </div>
                <div class="ls-field">
                    <label class="ls-label" for="adj-qty">{{ __('app.inventory.quantity') }}</label>
                    <input type="number" min="1" step="1" id="adj-qty" name="quantity" class="ls-input" inputmode="numeric" required value="{{ old('quantity') }}">
                    @error('quantity') <p class="ls-error">{{ $message }}</p> @enderror
                </div>
                <div class="ls-field" data-reason-field hidden>
                    <label class="ls-label" for="adj-reason">{{ __('app.inventory.reason') }}</label>
                    <select id="adj-reason" name="reason" class="ls-select">
                        @foreach (\App\Models\InventoryMovement::REASONS as $r)
                            <option value="{{ $r }}" @selected(old('reason') === $r)>{{ __('app.inventory.reasons.'.$r) }}</option>
                        @endforeach
                    </select>
                    @error('reason') <p class="ls-error">{{ $message }}</p> @enderror
                </div>
                <div class="ls-field">
                    <label class="ls-label" for="adj-note">{{ __('app.inventory.note') }}</label>
                    <input type="text" id="adj-note" name="note" maxlength="255" class="ls-input" value="{{ old('note') }}">
                </div>
                <div class="ls-paper">
                    <div class="ls-row-split"><span class="ls-muted">{{ __('app.inventory.current_stock') }}</span><b class="ls-num">{{ $product->stock_quantity }}</b></div>
                    <div class="ls-row-split"><span class="ls-muted">{{ __('app.inventory.new_stock') }}</span><b class="ls-num" data-new-stock>{{ $product->stock_quantity }}</b></div>
                </div>
            </form>
            <x-slot:footer>
                <x-ui.button variant="ghost" data-ls-close>{{ __('app.common.cancel') }}</x-ui.button>
                <span class="ls-push"><x-ui.button type="submit" variant="primary" form="adjust-form">{{ __('app.inventory.save') }}</x-ui.button></span>
            </x-slot:footer>
        </x-ui.modal>

        <script>
        (function () {
            const form = document.getElementById('adjust-form');
            const current = {{ (int) $product->stock_quantity }};
            const out = form.querySelector('[data-new-stock]'), qty = form.querySelector('#adj-qty');
            const reason = form.querySelector('[data-reason-field]');
            function refresh() {
                const dir = form.querySelector('input[name=direction]:checked').value;
                const n = parseInt(qty.value, 10) || 0;
                const next = dir === 'restock' ? current + n : current - n;
                out.textContent = next;
                out.classList.toggle('is-negative', next < 0);
                reason.hidden = dir !== 'remove';
            }
            form.addEventListener('input', refresh);
            form.addEventListener('change', refresh);
            refresh();
            @if ($errors->hasAny(['quantity', 'reason']) || session('error'))
                // panel.js is deferred: reopen the dialog (with its error) once it has loaded.
                document.addEventListener('DOMContentLoaded', () => LS.open('adjust-stock'));
            @endif
        })();
        </script>
    @endif
@endsection
