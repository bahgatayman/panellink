@extends('layouts.app')

@section('page-title', __('app.section.staff'))

@section('content')
    {{--
        Staff as people cards: who they are → what they can do (role +
        permission count) → whether they're active and when they last signed in
        → Edit / Activity / Disable. Search + status filters are server-side so
        they work across pages. Same routes and forms as before.
    --}}
    <x-ui.page-header :title="__('app.section.staff')" :count="$counts['all']" :subtitle="__('app.staff.subtitle')">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="plus" href="/staff/create">{{ __('app.staff.add_staff') }}</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.flash />

    @if ($counts['all'] > 0)
        <div class="ls-toolbar">
            <nav class="ls-chips" aria-label="{{ __('app.table.th.status') }}">
                @foreach (['all' => null, 'active' => 'active', 'disabled' => 'disabled'] as $key => $value)
                    @php $isOn = $status === $value; @endphp
                    <a href="{{ request()->fullUrlWithQuery(['status' => $value, 'page' => null]) }}"
                       class="ls-chip {{ $isOn ? 'is-active' : '' }}" @if ($isOn) aria-current="page" @endif>
                        {{ __('app.staff.filter_'.$key) }} <span class="ls-chip-count">{{ $counts[$key] }}</span>
                    </a>
                @endforeach
            </nav>
            <span class="ls-toolbar-spacer"></span>
            <form method="GET" action="/staff" class="ls-search ls-staff-search" role="search">
                @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
                <x-ui.icon name="search" />
                <input type="search" name="search" value="{{ $search }}" class="ls-input" placeholder="{{ __('app.staff.search') }}" aria-label="{{ __('app.staff.search') }}" autocomplete="off">
            </form>
        </div>
    @endif

    @if ($staff->count() > 0)
        <div class="ls-staff-grid">
            @foreach ($staff as $member)
                @php
                    $roleLabel = $member->role ? __('app.role.'.$member->role->key) : __('app.staff.custom_permissions');
                @endphp
                <article class="ls-staff {{ $member->is_active ? '' : 'is-disabled' }}" aria-labelledby="staff-{{ $member->id }}-name">
                    <div class="ls-staff-head">
                        <x-ui.avatar :name="$member->name" />
                        <div class="ls-staff-who">
                            <a href="/staff/{{ $member->id }}/edit" class="ls-staff-name ls-trunc" id="staff-{{ $member->id }}-name">{{ $member->name }}</a>
                            <bdi dir="ltr" class="ls-staff-email ls-trunc" title="{{ $member->email }}">{{ $member->email }}</bdi>
                        </div>
                        @if ($member->is_active)
                            <span class="ls-status"><span class="ls-dot"></span>{{ __('app.status.active') }}</span>
                        @else
                            <x-ui.badge tone="neutral" :dot="false">{{ __('app.staff.disabled') }}</x-ui.badge>
                        @endif
                    </div>

                    <dl class="ls-staff-facts">
                        <div>
                            <dt><x-ui.icon name="lock" /><span class="ls-sr">{{ __('app.staff.role') }}</span></dt>
                            <dd>
                                <span class="ls-staff-role {{ $member->role ? '' : 'is-custom' }}">{{ $roleLabel }}</span>
                                <span class="ls-faint">· {{ trans_choice('app.staff.permissions_count', $member->permissions_count, ['count' => $member->permissions_count]) }}</span>
                            </dd>
                        </div>
                        <div>
                            <dt><x-ui.icon name="clock" /><span class="ls-sr">{{ __('app.staff.last_login') }}</span></dt>
                            <dd class="{{ $member->last_login_at ? '' : 'ls-faint' }}"
                                @if ($member->last_login_at) title="{{ $member->last_login_at->format('M d, Y H:i') }}" @endif>
                                {{ $member->last_login_at ? __('app.staff.last_seen', ['time' => $member->last_login_at->diffForHumans()]) : __('app.staff.never_logged_in') }}
                            </dd>
                        </div>
                    </dl>

                    <div class="ls-staff-foot">
                        <x-ui.button size="sm" href="/staff/{{ $member->id }}/edit">{{ __('app.common.edit') }}</x-ui.button>
                        <x-ui.button variant="ghost" size="sm" href="/staff/{{ $member->id }}/activity">{{ __('app.staff.view_activity') }}</x-ui.button>
                        <form method="POST" action="/staff/{{ $member->id }}/toggle-status" class="ls-staff-toggle"
                              @if ($member->is_active) onsubmit="return confirm(@js(__('app.staff.confirm_disable')))" @endif>
                            @csrf
                            <x-ui.button type="submit" :variant="$member->is_active ? 'danger-quiet' : 'tonal'" size="sm">
                                {{ $member->is_active ? __('app.staff.disable') : __('app.staff.enable') }}
                            </x-ui.button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $staff->withQueryString()->links() }}</div>
    @elseif ($search || $status)
        <div class="ls-card">
            <x-ui.empty-state illustration="search" :title="$search ? __('app.staff.no_match', ['q' => $search]) : __('app.staff.no_staff')" :text="__('app.staff.no_match_hint')">
                <x-ui.button href="/staff">{{ __('app.staff.clear_search') }}</x-ui.button>
            </x-ui.empty-state>
        </div>
    @else
        <div class="ls-card">
            <x-ui.empty-state illustration="people" :title="__('app.staff.no_staff')" :text="__('app.staff.empty_hint')">
                <x-ui.button variant="primary" icon="plus" href="/staff/create">{{ __('app.staff.add_staff') }}</x-ui.button>
            </x-ui.empty-state>
        </div>
    @endif
@endsection
