{{-- Member profile → compact summary of the active hour package (soonest to expire first). --}}
@php
    $active = $packages->filter(fn ($p) => $p->status() === \App\Models\MemberPackage::STATUS_ACTIVE)->sortBy('expires_on')->values();
    $main = $active->first();
@endphp
@if ($main)
    @php $soon = $main->isExpiringSoon(); $pct = $main->progressPercent(); @endphp
    <a href="#packages" class="ls-pkg-summary {{ $soon ? 'is-soon' : '' }}" id="package-summary">
        <span class="ls-pkg-summary-icon" aria-hidden="true"><x-ui.icon name="clock" /></span>
        <span class="ls-pkg-summary-main">
            <span class="ls-pkg-summary-top">
                <span class="ls-pkg-summary-label">{{ __('app.packages.summary_title') }} · {{ $main->name }}</span>
                @if ($soon)<x-ui.badge tone="warn">{{ __('app.packages.expiring_soon') }}</x-ui.badge>@endif
                @if ($active->count() > 1)<span class="ls-pkg-summary-more">{{ __('app.packages.more_active', ['count' => $active->count() - 1]) }}</span>@endif
            </span>
            <span class="ls-pkg-summary-value">{{ __('app.packages.remaining_of', ['remaining' => $main->remainingLabel(), 'total' => $main->totalLabel()]) }}</span>
            <span class="ls-meter {{ $pct >= 80 ? 'is-warn' : '' }}"><i style="width: {{ $pct }}%"></i></span>
            <span class="ls-pkg-summary-meta">
                {{ __('app.packages.used') }} {{ $main->usedLabel() }} · {{ __('app.packages.expires', ['date' => $main->expires_on->translatedFormat('M j, Y')]) }}
            </span>
        </span>
    </a>
@endif
