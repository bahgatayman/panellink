@extends('layouts.app')

@section('page-title', $user->name)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h1>
        <div class="flex gap-2">
            <a href="/users/{{ $user->id }}/edit" class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition text-sm font-medium">
                Edit
            </a>
            <form method="POST" action="/users/{{ $user->id }}" onsubmit="return confirm('Delete this user?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium">
                    Delete
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <dl class="space-y-4">
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Name</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $user->name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Phone (Username)</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $user->phone }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Download Speed</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $user->speed_download }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Upload Speed</dt>
                    <dd class="text-sm font-medium text-gray-900">{{ $user->speed_upload }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Status</dt>
                    <dd>
                        @if ($user->status === 'active')
                            <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-medium">Active</span>
                        @else
                            <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs font-medium">Inactive</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-sm text-gray-500">Created</dt>
                    <dd class="text-sm text-gray-900">{{ $user->created_at->format('M d, Y h:i A') }}</dd>
                </div>
            </dl>

            <div class="mt-6 pt-4 border-t">
                <form method="POST" action="/users/{{ $user->id }}/toggle-status">
                    @csrf
                    <button type="submit" class="w-full {{ $user->status === 'active' ? 'bg-orange-500 hover:bg-orange-600' : 'bg-green-500 hover:bg-green-600' }} text-white px-4 py-2 rounded-lg transition text-sm font-medium">
                        {{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Change Speed</h2>

            <form method="POST" action="/users/{{ $user->id }}/speed">
                @csrf

                <div class="mb-4">
                    <label for="speed_profile_id" class="block text-sm font-medium text-gray-700 mb-1">Select Speed Profile</label>
                    <select name="speed_profile_id" id="speed_profile_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Select a profile --</option>
                        @foreach ($speedProfiles as $profile)
                            <option value="{{ $profile->id }}" {{ $user->speed_profile_id == $profile->id ? 'selected' : '' }}>
                                {{ $profile->name }} (&darr;{{ $profile->speed_download }} / &uarr;{{ $profile->speed_upload }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <p class="text-xs text-gray-400 mb-4">Selecting a profile will update the user's speed on MikroTik immediately.</p>

                <button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg hover:bg-blue-700 transition font-medium shadow-sm">
                    Update Speed on MikroTik
                </button>
            </form>
        </div>
    </div>
@endsection
