{{--
    Compact grid-based time picker. Renders a hidden <input> carrying the same
    name/value contract a native <select> of H:i options would, so server-side
    validation and controllers never see a difference.

    <x-ui.time-picker name="start_time" :value="$value" :values="array_keys($timeSlots)"
        placeholder="…" :disabled-up-to="$disabledUpTo" />

    $values must be raw 'H:i' strings (e.g. from GeneratesTimeSlots). Labels are
    computed here (not reused from the trait) so they can be localized to
    Arabic ص/م — see the design system's bilingual-label rule.
--}}
@props([
    'name',
    'id' => null,
    'value' => '',
    'values' => [],
    'placeholder' => null,
    'ariaLabel' => null,
    'columns' => 6,
    'disabledUpTo' => null,
])
@php
    $id = $id ?? $name;
    $isRtl = app()->getLocale() === 'ar';
    $options = collect($values)->mapWithKeys(function (string $hm) use ($isRtl) {
        $t = \Carbon\Carbon::createFromFormat('H:i', $hm);
        $suffix = $isRtl ? ($t->format('A') === 'AM' ? 'ص' : 'م') : $t->format('A');
        return [$hm => $t->format('g:i').' '.$suffix];
    });
    // Falls back to formatting $value directly (instead of leaving the trigger
    // blank) when it doesn't land on a grid slot — e.g. legacy/imported data
    // whose stored time isn't a multiple of the slot step. A native <select>
    // would have silently coerced this to its first option; the hidden input
    // here still submits the real stored value untouched either way.
    $currentLabel = null;
    if ($value !== '') {
        $currentLabel = $options[$value] ?? null;
        if ($currentLabel === null) {
            try {
                $t = \Carbon\Carbon::createFromFormat('H:i', $value);
                $suffix = $isRtl ? ($t->format('A') === 'AM' ? 'ص' : 'م') : $t->format('A');
                $currentLabel = $t->format('g:i').' '.$suffix;
            } catch (\Exception) {
                $currentLabel = null;
            }
        }
    }
@endphp
<div class="ls-time-field" data-time-field>
    <input type="hidden" name="{{ $name }}" id="{{ $id }}" value="{{ $value }}">
    <button type="button" id="{{ $id }}-trigger" class="ls-input ls-time-trigger"
            data-ls-menu="{{ $id }}-pop" aria-haspopup="true" aria-expanded="false">
        <span class="ls-time-trigger-text ls-num {{ $currentLabel ? '' : 'is-placeholder' }}" id="{{ $id }}-trigger-text">{{ $currentLabel ?? $placeholder }}</span>
        <x-ui.icon name="clock" />
    </button>
    <div id="{{ $id }}-pop" class="ls-pop ls-time-pop" role="menu" aria-label="{{ $ariaLabel }}" hidden
         data-options="{{ $options->toJson() }}">
        <div class="ls-time-grid" id="{{ $id }}-grid" style="--ls-time-cols: {{ $columns }}">
            @foreach ($options as $hm => $lbl)
                @if ($disabledUpTo !== null && $hm <= $disabledUpTo)
                    <span class="ls-time-cell is-disabled" aria-hidden="true">{{ $lbl }}</span>
                @else
                    <button type="button" role="menuitemradio" aria-checked="{{ $hm === $value ? 'true' : 'false' }}" class="ls-time-cell" data-value="{{ $hm }}">{{ $lbl }}</button>
                @endif
            @endforeach
        </div>
    </div>
</div>
