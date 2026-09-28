{{--
    Labelled form field with helper text and inline validation (reads $errors by name).
    <x-ui.input name="price" :label="__('…')" type="number" hint="…" optional :value="old('price', $p->price)" />
    Pass a slot instead of type to wrap a custom control (select, textarea…).
--}}
@props(['name', 'label' => null, 'type' => 'text', 'hint' => null, 'optional' => false, 'required' => false, 'value' => null])
@php $invalid = $errors->has($name); @endphp
<div class="ls-field">
    @if ($label)
        <label class="ls-label" for="f-{{ $name }}">{{ $label }}@if ($required)<span class="ls-req" aria-hidden="true">*</span><span class="ls-sr"> ({{ __('app.ui.required') }})</span>@elseif ($optional) <span class="ls-opt">({{ __('app.ui.optional') }})</span>@endif</label>
    @endif
    @if ($slot->isEmpty())
        <input id="f-{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value ?? old($name) }}"
               {{ $attributes->merge(['class' => 'ls-input'.($invalid ? ' is-invalid' : '')]) }}
               @if ($required) required aria-required="true" @endif
               @if ($invalid) aria-invalid="true" aria-describedby="f-{{ $name }}-err" @elseif ($hint) aria-describedby="f-{{ $name }}-hint" @endif>
    @else
        {{ $slot }}
    @endif
    @if ($invalid)
        <span class="ls-error" id="f-{{ $name }}-err"><x-ui.icon name="alert" />{{ $errors->first($name) }}</span>
    @elseif ($hint)
        <span class="ls-hint" id="f-{{ $name }}-hint">{{ $hint }}</span>
    @endif
</div>
