{{--
    $groups: Collection<string groupLabel, Collection<Room|Product>>
    $name: 'room_ids' | 'product_ids'
    $selected: array<int> already-selected ids
    $labeller: fn($item): string
--}}
<div class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-64 overflow-y-auto">
    @forelse ($groups as $groupLabel => $items)
        <div class="p-3">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $groupLabel }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                @foreach ($items as $item)
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" name="{{ $name }}[]" value="{{ $item->id }}" {{ in_array($item->id, $selected) ? 'checked' : '' }}>
                        {{ $labeller($item) }}
                    </label>
                @endforeach
            </div>
        </div>
    @empty
        <p class="p-3 text-sm text-gray-400">{{ __('app.coupons.empty') }}</p>
    @endforelse
</div>
