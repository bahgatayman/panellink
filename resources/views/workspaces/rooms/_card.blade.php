@php
    $statusKey = $room->statusKey($room->occupied_seats);
@endphp
<article class="ls-product {{ $room->is_available ? '' : 'is-inactive' }}" aria-labelledby="room-{{ $room->id }}-name"
         data-ls-item="rooms" data-ls-text="{{ $room->name }} {{ $room->typeLabel() }}">
    <div class="ls-product-top">
        <span class="ls-product-icon is-product" aria-hidden="true"><x-ui.icon name="building" /></span>
        @if ($statusKey === 'available')
            <span class="ls-status"><span class="ls-dot"></span>{{ __('app.workspace.available') }}</span>
        @elseif ($statusKey === 'occupied')
            <x-ui.badge tone="info">{{ $room->statusLabel($room->occupied_seats) }}</x-ui.badge>
        @else
            <x-ui.badge tone="neutral" :dot="false">{{ __('app.workspace.unavailable') }}</x-ui.badge>
        @endif
    </div>

    <div class="ls-product-body">
        <a href="{{ route('rooms.edit', [$workspace, $room]) }}" class="ls-product-name ls-trunc" id="room-{{ $room->id }}-name" title="{{ $room->name }}">
            {{ $room->name }}
        </a>
        <div class="ls-product-meta">
            <span>{{ $room->typeLabel() }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $room->capacity }} {{ __('app.workspace.seats') }}</span>
        </div>
    </div>

    <div class="ls-product-price">
        {{ $room->pricingSummary() }}
        @if ($room->plans_count)
            <a href="{{ route('rooms.edit', [$workspace, $room]) }}#plans-title" class="ls-plan-tag">
                {{ trans_choice('app.plans.count', $room->plans_count, ['count' => $room->plans_count]) }}
            </a>
        @endif
        @if ($room->pricing_profiles_count ?? 0)
            <a href="{{ route('rooms.edit', [$workspace, $room]) }}#profiles-title" class="ls-plan-tag">
                {{ trans_choice('app.pricing_profiles.count', $room->pricing_profiles_count, ['count' => $room->pricing_profiles_count]) }}
            </a>
        @endif
    </div>

    @if ($room->description)
        <p class="ls-faint ls-trunc" style="font-size:13px">{{ $room->description }}</p>
    @endif

    <div class="ls-product-foot">
        <x-ui.button size="sm" :href="route('rooms.edit', [$workspace, $room])">{{ __('app.common.edit') }}</x-ui.button>
        <form method="POST" action="{{ route('rooms.toggle', [$workspace, $room]) }}">
            @csrf
            <x-ui.button type="submit" variant="ghost" size="sm">
                {{ $room->is_available ? __('app.workspace.mark_unavailable') : __('app.workspace.mark_available') }}
            </x-ui.button>
        </form>
        <form method="POST" action="{{ route('rooms.destroy', [$workspace, $room]) }}" class="ls-product-delete"
              onsubmit="return confirm(@js(__('app.workspace.delete_room_confirm')))">
            @csrf
            @method('DELETE')
            <x-ui.button type="submit" variant="danger-quiet" size="sm" icon="trash" :icon-only="true"
                :aria-label="__('app.common.delete').': '.$room->name" :title="__('app.common.delete')" />
        </form>
    </div>
</article>
