{{-- $periodKey, $customStart, $customEnd — shared by the Overview and Transactions pages. --}}
<div class="flex flex-wrap items-center gap-3">
    <div class="inline-flex items-center gap-1 bg-white border border-gray-100 rounded-lg p-1 shadow-sm">
        @foreach (['today' => 'app.financials.period_today', 'this_week' => 'app.financials.period_week', 'this_month' => 'app.financials.period_month'] as $key => $labelKey)
            <a href="{{ request()->fullUrlWithQuery(['period' => $key]) }}"
               class="px-3 py-1.5 rounded-md text-sm font-medium transition {{ $periodKey === $key ? 'bg-brand-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">
                {{ __($labelKey) }}
            </a>
        @endforeach
    </div>
    <form method="GET" class="flex items-center gap-2">
        <input type="hidden" name="period" value="custom">
        @foreach(request()->except(['period', 'start', 'end', 'page']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <input type="date" name="start" value="{{ $customStart }}" class="border border-gray-200 rounded-md text-sm px-2 py-1.5 text-gray-700">
        <span class="text-gray-400 text-sm">&ndash;</span>
        <input type="date" name="end" value="{{ $customEnd }}" class="border border-gray-200 rounded-md text-sm px-2 py-1.5 text-gray-700">
        <button type="submit" class="px-3 py-1.5 rounded-md text-sm font-medium transition {{ $periodKey === 'custom' ? 'bg-brand-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            {{ __('app.financials.apply') }}
        </button>
    </form>
</div>
