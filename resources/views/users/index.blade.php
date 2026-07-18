@extends('layouts.app')

@section('page-title', __('app.user.hotspot_users'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.user.hotspot_users') }}</h1>
        <a href="/users/create" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
            {{ __('app.btn.add_user') }}
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif

    <form method="GET" action="/users" class="mb-6">
        <div class="flex gap-2 max-w-md">
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('app.placeholder.search_name_phone') }}"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition text-sm">
                {{ __('app.common.search') }}
            </button>
            @if ($search)
                <a href="/users" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                    {{ __('app.common.clear') }}
                </a>
            @endif
        </div>
    </form>

    @if ($users->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="px-4 py-3">{{ __('app.table.th.name') }}</th>
                        <th class="px-4 py-3">{{ __('app.table.th.phone') }}</th>
                        <th class="px-4 py-3">{{ __('app.table.th.download') }}</th>
                        <th class="px-4 py-3">{{ __('app.table.th.upload') }}</th>
                        <th class="px-4 py-3">{{ __('app.table.th.status') }}</th>
                        <th class="px-4 py-3">{{ __('app.table.th.created') }}</th>
                        <th class="px-4 py-3">{{ __('app.table.th.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($users as $user)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->phone }}</td>
                            <td class="px-4 py-3">{{ $user->speed_download }}</td>
                            <td class="px-4 py-3">{{ $user->speed_upload }}</td>
                            <td class="px-4 py-3">
                                @if ($user->status === 'active')
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">{{ __('app.status.active') }}</span>
                                @else
                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-medium">{{ __('app.status.inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-3 flex gap-2">
                                <a href="/users/{{ $user->id }}" class="text-blue-600 hover:underline text-sm font-medium">{{ __('app.common.view') }}</a>
                                <a href="/users/{{ $user->id }}/edit" class="text-blue-600 hover:underline text-sm font-medium">{{ __('app.common.edit') }}</a>
                                <form method="POST" action="/users/{{ $user->id }}" onsubmit="return confirm('Delete this user?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline text-sm font-medium">{{ __('app.common.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->withQueryString()->links() }}
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
            <p class="text-gray-500 text-lg">{{ __('app.empty.no_users') }}</p>
        </div>
    @endif
@endsection
