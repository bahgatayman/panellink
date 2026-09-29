@extends('layouts.app')

@section('page-title', __('app.sales.products'))

@section('content')
    {{--
        Products as a card grid: name and selling price first (what staff scan
        for), then cost/profit, then stock with its status — Low and Out stand
        out, untracked products show no stock at all. Filters are server-side.
    --}}
    <x-ui.page-header :title="__('app.sales.products')" :count="$counts['all']">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" :href="route('products.create')">{{ __('app.sales.add_product') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    @if ($counts['all'] > 0 && ($counts['low'] || $counts['out'] || $stock))
        <nav class="ls-chips" style="margin-bottom: var(--space-5)" aria-label="{{ __('app.inventory.stock') }}">
            @foreach (['all' => null, 'low' => 'low', 'out' => 'out'] as $key => $value)
                @php $on = $stock === $value; @endphp
                <a href="{{ request()->fullUrlWithQuery(['stock' => $value, 'page' => null]) }}" class="ls-chip {{ $on ? 'is-active' : '' }}" @if ($on) aria-current="page" @endif>
                    {{ __('app.inventory.filter_'.$key) }} <span class="ls-chip-count">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    @if ($products->isEmpty())
        <div class="ls-card">
            @if ($stock)
                <x-ui.empty-state illustration="box" :title="__('app.inventory.filter_'.$stock)" :text="__('app.sales.no_products')">
                    <x-ui.button :href="route('products.index')">{{ __('app.inventory.filter_all') }}</x-ui.button>
                </x-ui.empty-state>
            @else
                <x-ui.empty-state illustration="box" :title="__('app.sales.no_products')">
                    <x-ui.button variant="primary" icon="plus" :href="route('products.create')">{{ __('app.sales.add_product') }}</x-ui.button>
                </x-ui.empty-state>
            @endif
        </div>
    @else
        <div class="ls-product-grid">
            @foreach ($products as $product)
                @php
                    $isService = $product->isService();
                    $status = $product->stockStatus();
                    $profit = $product->profitPerUnit();
                @endphp
                <article class="ls-product {{ $product->is_active ? '' : 'is-inactive' }} {{ $status === 'out' ? 'is-out' : '' }}" aria-labelledby="product-{{ $product->id }}-name">
                    <div class="ls-product-top">
                        <span class="ls-product-icon {{ $isService ? 'is-service' : 'is-product' }}" aria-hidden="true">
                            <x-ui.icon :name="$isService ? 'bolt' : 'box'" />
                        </span>
                        @if (! $product->is_active)
                            <x-ui.badge tone="neutral" :dot="false">{{ __('app.status.inactive') }}</x-ui.badge>
                        @elseif ($status === 'out')
                            <x-ui.badge tone="danger">{{ __('app.inventory.status.out') }}</x-ui.badge>
                        @elseif ($status === 'low')
                            <x-ui.badge tone="warn">{{ __('app.inventory.status.low') }}</x-ui.badge>
                        @else
                            <span class="ls-status"><span class="ls-dot"></span>{{ __('app.status.active') }}</span>
                        @endif
                    </div>

                    <div class="ls-product-body">
                        <a href="{{ route('products.show', $product) }}" class="ls-product-name ls-trunc" id="product-{{ $product->id }}-name" title="{{ $product->name }}">{{ $product->name }}</a>
                        <div class="ls-product-meta">
                            <span>{{ $isService ? __('app.sales.service') : __('app.sales.product') }}</span>
                            @if ($product->sku)
                                <span aria-hidden="true">·</span>
                                <span class="ls-mono ls-trunc">{{ $product->sku }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="ls-product-price"><x-ui.money :amount="$product->price" /></div>

                    <dl class="ls-inv-facts">
                        @if ((float) $product->purchase_price > 0)
                            <div><dt>{{ __('app.inventory.cost') }}</dt><dd class="ls-num"><x-ui.money :amount="$product->purchase_price" /></dd></div>
                            <div><dt>{{ __('app.inventory.profit_unit') }}</dt><dd class="ls-num {{ $profit < 0 ? 'is-negative' : 'is-profit' }}"><x-ui.money :amount="$profit" /></dd></div>
                        @endif
                        @if ($status !== 'untracked')
                            <div><dt>{{ __('app.inventory.stock') }}</dt>
                                <dd class="ls-num ls-inv-stock is-{{ $status }}">{{ trans_choice('app.inventory.units', $product->stock_quantity, ['count' => $product->stock_quantity]) }}</dd></div>
                        @endif
                    </dl>

                    <div class="ls-product-foot">
                        <x-ui.button size="sm" :href="route('products.edit', $product)">{{ __('app.common.edit') }}</x-ui.button>
                        <x-ui.button size="sm" variant="ghost" :href="route('products.show', $product)">{{ __('app.inventory.view') }}</x-ui.button>
                        <form method="POST" action="{{ route('products.toggle', $product) }}">
                            @csrf
                            <x-ui.button type="submit" variant="ghost" size="sm">
                                {{ $product->is_active ? __('app.btn.deactivate') : __('app.btn.activate') }}
                            </x-ui.button>
                        </form>
                        <form method="POST" action="{{ route('products.destroy', $product) }}" class="ls-product-delete"
                              onsubmit="return confirm(@js(__('app.sales.delete_confirm')))">
                            @csrf
                            @method('DELETE')
                            <x-ui.button type="submit" variant="danger-quiet" size="sm" icon="trash" :icon-only="true"
                                :aria-label="__('app.common.delete').': '.$product->name" :title="__('app.common.delete')" />
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $products->links() }}</div>
    @endif
@endsection
