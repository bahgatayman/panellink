@extends('layouts.app')

@section('page-title', __('app.speed.speed_profiles'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.speed.speed_profiles') }}</h1>
        <a href="/speed-profiles/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
            {{ __('app.speed.add_profile') }}
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">{{ __('app.speed.name') }}</th>
                        <th class="px-4 py-3">{{ __('app.speed.download') }}</th>
                        <th class="px-4 py-3">{{ __('app.speed.upload') }}</th>
                        <th class="px-4 py-3">{{ __('app.speed.default') }}</th>
                        <th class="px-4 py-3">{{ __('app.speed.users') }}</th>
                        <th class="px-4 py-3">{{ __('app.common.actions') }}</th>
                    </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($profiles as $profile)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $profile->name }}</td>
                        <td class="px-4 py-3">{{ $profile->speed_download }}</td>
                        <td class="px-4 py-3">{{ $profile->speed_upload }}</td>
                        <td class="px-4 py-3">
                            @if ($profile->is_default)
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">{{ __('app.speed.default') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $profile->hotspot_users_count }}</td>
                        <td class="px-4 py-3 flex gap-2">
                            <a href="/speed-profiles/{{ $profile->id }}/edit" class="text-blue-600 hover:underline text-sm font-medium">{{ __('app.common.edit') }}</a>
                            <form method="POST" action="/speed-profiles/{{ $profile->id }}" onsubmit="return confirm('Delete this profile?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline text-sm font-medium">{{ __('app.common.delete') }}</button>
                            </form>
                            @if (!$profile->is_default)
                                <form method="POST" action="/speed-profiles/{{ $profile->id }}/set-default">
                                    @csrf
                                    <button type="submit" class="text-blue-600 hover:underline text-sm font-medium">{{ __('app.speed.set_as_default') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-4">{{ __('app.speed.default_profile_note') }}</p>
@endsection
