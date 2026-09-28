{{--
    Modal or drawer, opened with LS.open('id') or any [data-ls-open="id"].
    <x-ui.modal id="checkout" title="…" subtitle="…" size="wide|narrow" drawer>
        body…
        <x-slot:note>Closes the session and frees the room.</x-slot:note>
        <x-slot:footer>…buttons…</x-slot:footer>
    </x-ui.modal>
    Title/subtitle elements get ids "{id}-title" / "{id}-sub" so scripts can update them.
--}}
@props(['id', 'title' => null, 'subtitle' => null, 'size' => null, 'drawer' => false])
<div id="{{ $id }}" class="ls-overlay{{ $drawer ? ' ls-overlay--drawer' : '' }}" aria-hidden="true">
    <div {{ $attributes->merge(['class' => 'ls-dialog'.($size ? ' ls-dialog--'.$size : '')]) }} role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
        <div class="ls-dialog-head">
            <div>
                <h3 class="ls-dialog-title" id="{{ $id }}-title">{{ $title }}</h3>
                <div class="ls-dialog-sub" id="{{ $id }}-sub">{{ $subtitle }}</div>
            </div>
            <button type="button" class="ls-btn ls-btn--ghost ls-btn--sm ls-btn--icon" data-ls-close aria-label="{{ __('app.common.close') }}"><x-ui.icon name="x" /></button>
        </div>
        <div class="ls-dialog-body">{{ $slot }}</div>
        @isset($note)<div class="ls-dialog-note"><x-ui.icon name="check-circle" /><span>{{ $note }}</span></div>@endisset
        @isset($footer)<div class="ls-dialog-foot">{{ $footer }}</div>@endisset
    </div>
</div>
