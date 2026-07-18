@extends('layouts.admin')

@section('page-title', __('app.section.features'))

@section('content')
    <h1 class="text-2xl font-bold text-gray-900 mb-6">{{ __('app.section.features') }}</h1>

    <!-- Global Features Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="px-4 lg:px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">{{ __('app.admin.global_features') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 bg-gray-50 border-b border-gray-100">
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.admin.feature') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.admin.key') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.admin.description') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.admin.owners_using') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.admin.global_status') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($features as $feature)
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                            <td class="px-4 lg:px-6 py-3">
                                <div class="flex items-center gap-3">
                                    @include('admin.features._icon', ['icon' => $feature->icon])
                                    <span class="font-medium text-gray-900">{{ $feature->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 lg:px-6 py-3 text-gray-500 font-mono text-xs">{{ $feature->key }}</td>
                            <td class="px-4 lg:px-6 py-3 text-gray-500 max-w-[250px]">{{ $feature->description }}</td>
                            <td class="px-4 lg:px-6 py-3 text-gray-700">{{ $feature->owners_count }}</td>
                            <td class="px-4 lg:px-6 py-3">
                                @if ($feature->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('app.status.active') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ __('app.status.disabled') }}</span>
                                @endif
                            </td>
                            <td class="px-4 lg:px-6 py-3">
                                <form method="POST" action="/admin/features/{{ $feature->id }}/toggle-global">
                                    @csrf
                                    <button type="submit"
                                        class="text-sm font-medium {{ $feature->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}"
                                        onclick="return confirm('{{ $feature->is_active ? __('app.admin.disable_globally') : __('app.admin.enable_globally') }} \'{{ $feature->name }}\'?')">
                                        {{ $feature->is_active ? __('app.admin.disable_globally') : __('app.admin.enable_globally') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Features per Owner -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-4 lg:px-6 py-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">{{ __('app.admin.feature_access_by_owner') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 bg-gray-50 border-b border-gray-100">
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.admin.owner') }}</th>
                        <th class="px-4 lg:px-6 py-3 font-medium">{{ __('app.admin.business') }}</th>
                        @foreach ($features as $feature)
                            <th class="px-4 lg:px-6 py-3 font-medium text-center">{{ $feature->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($owners as $owner)
                        <tr class="border-b border-gray-50 hover:bg-gray-50 transition">
                            <td class="px-4 lg:px-6 py-3">
                                <a href="/admin/owners/{{ $owner->id }}" class="text-blue-600 hover:underline font-medium">{{ $owner->name }}</a>
                            </td>
                            <td class="px-4 lg:px-6 py-3 text-gray-700">{{ $owner->business_name }}</td>
                            @foreach ($features as $feature)
                                <td class="px-4 lg:px-6 py-3 text-center">
                                    @if (!$feature->is_active)
                                        <span class="text-xs text-gray-400">—</span>
                                    @elseif ($owner->features->contains('id', $feature->id))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('app.status.enabled') }}</span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + $features->count() }}" class="px-4 lg:px-6 py-8 text-center text-gray-500">{{ __('app.empty.no_owners') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
