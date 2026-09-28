{{-- Empty state: what will appear here, and the next step. --}}
@props(['illustration' => 'quiet', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'ls-empty']) }}>
    <x-ui.illustration :name="$illustration" />
    <h4>{{ $title }}</h4>
    @if ($text)<p>{{ $text }}</p>@endif
    @if (! $slot->isEmpty())<div class="ls-actions">{{ $slot }}</div>@endif
</div>
