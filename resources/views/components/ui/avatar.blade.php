{{-- Customer / staff initials. Two letters from the first two words of the name.
     Each name gets a stable identity tint (3 restrained brand-adjacent hues, never status colours). --}}
@props(['name' => '', 'size' => null])
@php
    $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    $initials = mb_strtoupper(collect($words)->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
    $tone = crc32(mb_strtolower(trim((string) $name))) % 3 + 1;
@endphp
<span {{ $attributes->merge(['class' => 'ls-avatar'.($tone > 1 ? ' ls-avatar--t'.$tone : '').($size ? ' ls-avatar--'.$size : '')]) }} aria-hidden="true">{{ $initials ?: '?' }}</span>
