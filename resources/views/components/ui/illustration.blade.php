{{-- Small line illustrations — used only for empty and success moments. quiet · search · calendar · receipt · box · people · done --}}
@props(['name' => 'quiet'])
@php
    $st = 'fill="none" stroke="var(--color-illo-ink)" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"';
    $g = '<ellipse cx="60" cy="72" rx="42" ry="4.5" fill="var(--color-illo-fill)"/>';
    $g .= match ($name) {
        'quiet' => '<path d="M38 38h36v12a14 14 0 0 1-14 14h-8a14 14 0 0 1-14-14z" fill="var(--color-illo-paper)" '.$st.'/><path d="M74 42h3.5a6 6 0 0 1 0 12H73" '.$st.'/><path class="ls-steam" d="M48 30c-3-4 3-6 0-10M56 28c-3-4 3-6 0-10M64 30c-3-4 3-6 0-10" fill="none" stroke="var(--color-illo-accent)" stroke-width="1.6" stroke-linecap="round"/>',
        'search' => '<rect x="28" y="18" width="48" height="44" rx="6" fill="var(--color-illo-paper)" '.$st.'/><path d="M36 30h26M36 38h18M36 46h22" stroke="var(--color-illo-fill)" stroke-width="4" stroke-linecap="round"/><circle cx="72" cy="46" r="12" fill="var(--color-illo-paper)" '.$st.'/><path d="m81 55 8 8" '.$st.' stroke-width="2.6"/>',
        'calendar' => '<rect x="34" y="18" width="52" height="46" rx="7" fill="var(--color-illo-paper)" '.$st.'/><rect x="35" y="19" width="50" height="11" rx="6" fill="var(--color-illo-fill)"/><path d="M34 30h52M46 13v10M74 13v10" '.$st.'/><path class="ls-draw" d="m50 47 7 7 13-14" fill="none" stroke="var(--color-illo-accent)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>',
        'receipt' => '<path d="M40 14h40v52l-5-3.5-5 3.5-5-3.5-5 3.5-5-3.5-5 3.5-5-3.5-5 3.5z" fill="var(--color-illo-paper)" '.$st.'/><path d="M48 26h24M48 34h16M48 42h20" stroke="var(--color-illo-fill)" stroke-width="4" stroke-linecap="round"/>',
        'box' => '<path d="M34 32 60 22l26 10v26L60 68 34 58z" fill="var(--color-illo-paper)" '.$st.'/><path d="M34 32l26 10 26-10M60 42v26" '.$st.'/>',
        'people' => '<circle cx="50" cy="32" r="10" fill="var(--color-illo-paper)" '.$st.'/><path d="M32 64c2-10 9-15 18-15s16 5 18 15" fill="var(--color-illo-paper)" '.$st.'/><circle cx="76" cy="36" r="8" fill="var(--color-illo-fill)" stroke="var(--color-illo-accent)" stroke-width="1.6"/>',
        default => '<circle cx="60" cy="38" r="24" fill="var(--color-illo-fill)"/><path class="ls-draw" d="m49 38.5 7.5 7.5L72 31" fill="none" stroke="var(--color-illo-ink)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>',
    };
@endphp
<div {{ $attributes->merge(['class' => 'ls-illo']) }}><svg viewBox="0 0 120 80" aria-hidden="true">{!! $g !!}</svg></div>
