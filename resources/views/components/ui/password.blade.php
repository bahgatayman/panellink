{{--
    Password input with a show/hide toggle (panel.js: [data-ls-reveal]).
    <x-ui.password name="password" required minlength="8" class="…input classes…" />
    Extra attributes go to the <input>. The toggle is a real, focusable button
    (aria-pressed + a label that says what it will do), so it works by keyboard
    and screen reader; the field goes back to hidden when the form submits.
--}}
@props(['name', 'id' => null])
@php $inputId = $id ?? 'pw-'.$name.'-'.substr(md5(uniqid('', true)), 0, 6); @endphp
<div class="ls-password">
    <input type="password" name="{{ $name }}" id="{{ $inputId }}" autocomplete="new-password" {{ $attributes }}>
    <button type="button" class="ls-password-toggle" data-ls-reveal="{{ $inputId }}" aria-controls="{{ $inputId }}" aria-pressed="false"
            aria-label="{{ __('app.password_field.show') }}" title="{{ __('app.password_field.show') }}"
            data-label-show="{{ __('app.password_field.show') }}" data-label-hide="{{ __('app.password_field.hide') }}">
        <x-ui.icon name="eye" class="ls-password-eye" />
        <x-ui.icon name="eye-off" class="ls-password-eye-off" />
    </button>
</div>
