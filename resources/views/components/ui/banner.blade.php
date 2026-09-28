{{-- Inline banner for things that need reading: tone ok · info · warn · danger --}}
@props(['tone' => 'info', 'title' => null])
<div {{ $attributes->merge(['class' => 'ls-banner ls-banner--'.$tone]) }} role="{{ $tone === 'danger' ? 'alert' : 'status' }}">
    <x-ui.icon :name="$tone === 'ok' ? 'check-circle' : 'alert'" />
    <div>@if ($title)<b>{{ $title }}</b> @endif{{ $slot }}</div>
</div>
