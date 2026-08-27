@extends('layouts.app')

@section('page-title', __('app.staff.activity_for', ['name' => $staff->name]))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.staff.activity_for', ['name' => $staff->name]) }}</h1>
        <a href="/staff" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('app.common.back') }}</a>
    </div>

    <div class="flex gap-2 mb-6">
        @foreach (['week' => 'range_week', 'month' => 'range_month', 'all' => 'range_all'] as $value => $labelKey)
            <a href="{{ route('staff.activity', ['staff' => $staff->id, 'range' => $value]) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition {{ $range === $value ? 'bg-blue-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ __('app.staff.'.$labelKey) }}
            </a>
        @endforeach
    </div>

    @if ($counts->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6 text-center text-gray-500 text-sm">
            {{ __('app.staff.no_activity') }}
        </div>
    @else
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
            @foreach ($counts as $action => $total)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                    <p class="text-2xl font-bold text-gray-900">{{ $total }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ __('app.staff.events.'.$action) }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-4 py-3">{{ __('app.table.th.created') }}</th>
                    <th class="px-4 py-3">{{ __('app.common.action') }}</th>
                    <th class="px-4 py-3">{{ __('app.common.description') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($activity as $entry)
                    <tr>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $entry->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ __('app.staff.events.'.$entry->action) }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $entry->description }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">{{ __('app.staff.no_activity') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $activity->withQueryString()->links() }}
    </div>
@endsection
