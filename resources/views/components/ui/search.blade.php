{{-- Client-side search over [data-ls-item="{group}"] with a "/" shortcut and a clear button. --}}
@props(['group', 'placeholder' => '', 'width' => '240px'])
<div class="ls-search" style="width: {{ $width }}; max-width: 100%">
    <x-ui.icon name="search" />
    <input type="search" class="ls-input" data-ls-search="{{ $group }}" placeholder="{{ $placeholder }}" aria-label="{{ $placeholder }}" autocomplete="off">
    <span class="ls-kbd">/</span>
    <button type="button" class="ls-clear" aria-label="{{ __('app.ui.clear_search') }}"><x-ui.icon name="x" /></button>
</div>
