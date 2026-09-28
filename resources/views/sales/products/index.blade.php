@extends('layouts.app')

@section('page-title', __('app.sales.products'))

@section('content')
    {{--
        Products as a card grid: name and price first (what staff scan for),
        type + status as quiet secondary info, actions in the card footer.
        Same routes/forms as before (edit, toggle, destroy) — layout only.
    --}}
    <x-ui.page-header :title="__('app.sales.products')" :count="$products->total()">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" :href="route('products.create')">{{ __('app.sales.add_product') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    @if ($products->isEmpty())
        <div class="ls-card">
            <x-ui.empty-state illustration="box" :title="__('app.sales.no_products')">
                <x-ui.button variant="primary" icon="plus" :href="route('products.create')">{{ __('app.sales.add_product') }}</x-ui.button>
            </x-ui.empty-state>
        </div>
    @else
        <div class="ls-product-grid">
            @foreach ($products as $product)
                @php $isService = $product->isService(); @endphp
                <article class="ls-product {{ $product->is_active ? '' : 'is-inactive' }}" aria-labelledby="product-{{ $product->id }}-name">
                    <div class="ls-product-top">
                        <span class="ls-product-icon {{ $isService ? 'is-service' : 'is-product' }}" aria-hidden="true">
                            <x-ui.icon :name="$isService ? 'bolt' : 'box'" />
                        </span>
                        @if ($product->is_active)
                            <span class="ls-status"><span class="ls-dot"></span>{{ __('app.status.active') }}</span>
                        @else
                            <x-ui.badge tone="neutral" :dot="false">{{ __('app.status.inactive') }}</x-ui.badge>
                        @endif
                    </div>

                    <div class="ls-product-body">
                        <h3 class="ls-product-name ls-trunc" id="product-{{ $product->id }}-name" title="{{ $product->name }}">{{ $product->name }}</h3>
                        <div class="ls-product-meta">
                            <span>{{ $isService ? __('app.sales.service') : __('app.sales.product') }}</span>
                            @if ($product->sku)
                                <span aria-hidden="true">·</span>
                                <span class="ls-mono ls-trunc">{{ $product->sku }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="ls-product-price"><x-ui.money :amount="$product->price" /></div>

                    <div class="ls-product-foot">
                        <x-ui.button size="sm" :href="route('products.edit', $product)">{{ __('app.common.edit') }}</x-ui.button>
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
