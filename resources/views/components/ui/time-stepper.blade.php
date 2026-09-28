{{--
    Compact "− time +" control wrapping the existing grid time-picker: the
    +/- buttons nudge by one slot without opening the popover; clicking the
    time itself still opens the full grid for a direct jump. No separate
    state — both act on the same hidden input/value the picker already owns.

    <x-ui.time-stepper name="start_time" :value="$value" :values="array_keys($timeSlots)" ... />
--}}
@props([
    'name',
    'id' => null,
    'value' => '',
    'values' => [],
    'placeholder' => null,
    'ariaLabel' => null,
])
@php $id = $id ?? $name; @endphp
<div class="ls-time-stepper" data-time-stepper="{{ $id }}">
    <button type="button" class="ls-stepper-btn" data-stepper-for="{{ $id }}" data-stepper-dir="-1" aria-label="{{ __('app.booking.stepper.earlier') }}">
        <x-ui.icon name="minus" />
    </button>
    <x-ui.time-picker :name="$name" :id="$id" :value="$value" :values="$values" :placeholder="$placeholder" :aria-label="$ariaLabel" />
    <button type="button" class="ls-stepper-btn" data-stepper-for="{{ $id }}" data-stepper-dir="1" aria-label="{{ __('app.booking.stepper.later') }}">
        <x-ui.icon name="plus" />
    </button>
</div>
