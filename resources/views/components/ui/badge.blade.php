{{-- Status badge. Colour carries meaning only: ok · info (live) · warn · danger · neutral. --}}
@props(['tone' => 'neutral', 'dot' => true, 'pulse' => false])
<span {{ $attributes->merge(['class' => 'ls-badge ls-badge--'.$tone]) }}>
    @if ($dot)<span class="ls-dot {{ $pulse ? 'is-pulse' : '' }}"></span>@endif
    {{ $slot }}
</span>
