{{--
    Member profile → Hour Packages: every package as its own card (meter,
    used / remaining / total, price, dates, status), the usage history, and
    the Add / Cancel package modals. Balances only change through
    HourPackageService; this view just reads them.
--}}
@php
    $fmtDate = fn ($d) => $d->translatedFormat('M j, Y');
    $currency = app()->getLocale() === 'ar' ? 'ج.م' : 'EGP';
@endphp
<section class="ls-card ls-pkg-section" id="packages" aria-labelledby="packages-title">
    <div class="ls-card-head">
        <h2 class="ls-card-title" id="packages-title">{{ __('app.packages.section') }}</h2>
        @if ($canAssignPackages)
            <div class="ls-actions">
                <x-ui.button size="sm" icon="plus" data-ls-open="add-package">{{ __('app.packages.add') }}</x-ui.button>
            </div>
        @endif
    </div>
    <div class="ls-card-body">
        @if ($packages->isEmpty())
            <p class="ls-pkg-none">{{ __('app.packages.none') }}</p>
        @else
            <div class="ls-pkg-list">
                @foreach ($packages as $pkg)
                    @php
                        $status = $pkg->status();
                        $soon = $pkg->isExpiringSoon();
                        $pct = $pkg->progressPercent();
                    @endphp
                    <article class="ls-pkg is-{{ $status }}" id="member-package-{{ $pkg->id }}">
                        <div class="ls-pkg-head">
                            <div class="ls-pkg-title">
                                <h3>{{ $pkg->name }}</h3>
                                <span class="ls-pkg-dates">
                                    @if ($status === 'scheduled')
                                        {{ __('app.packages.starts', ['date' => $fmtDate($pkg->starts_on)]) }} ·
                                    @endif
                                    {{ $status === 'expired' ? __('app.packages.expired_on', ['date' => $fmtDate($pkg->expires_on)]) : __('app.packages.expires', ['date' => $fmtDate($pkg->expires_on)]) }}
                                </span>
                            </div>
                            @if ($soon)
                                <x-ui.badge tone="warn">{{ __('app.packages.expiring_soon') }}</x-ui.badge>
                            @else
                                <x-ui.badge :tone="$pkg->statusTone()">{{ __('app.packages.status.'.$status) }}</x-ui.badge>
                            @endif
                        </div>

                        {{-- One bar: how much of the total has been consumed. --}}
                        <div class="ls-pkg-progress {{ $pkg->remainingMinutes() <= 0 ? 'is-full' : ($pct >= 80 ? 'is-high' : '') }}" role="progressbar"
                             aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}"
                             aria-label="{{ __('app.packages.used_of', ['used' => $pkg->usedLabel(), 'total' => $pkg->totalLabel()]) }}">
                            <i style="width: {{ $pct }}%"></i>
                        </div>
                        <div class="ls-pkg-progress-legend">
                            <span>{{ __('app.packages.used_of', ['used' => $pkg->usedLabel(), 'total' => $pkg->totalLabel()]) }} <span class="ls-pkg-pct">· {{ $pct }}%</span></span>
                            <b>{{ __('app.packages.left', ['time' => $pkg->remainingLabel()]) }}</b>
                        </div>

                        <div class="ls-pkg-meta">
                            <span><x-ui.money :amount="$pkg->price_paid" />@if ($pkg->cancelled_reason) · {{ $pkg->cancelled_reason }}@endif</span>
                            @if ($canAssignPackages && ! $pkg->cancelled_at && $status !== 'expired')
                                <button type="button" class="ls-link ls-pkg-cancel" data-pkg-cancel="{{ route('member-packages.cancel', $pkg->id) }}" data-pkg-name="{{ $pkg->name }}">{{ __('app.packages.cancel_pkg') }}</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Usage history as a short list: what happened · when · how many hours. --}}
    @if ($packageUsages->isNotEmpty())
        @php $manyPackages = $packages->count() > 1; @endphp
        <details class="ls-pkg-usage" @if ($packageUsages->count() <= 5) open @endif>
            <summary class="ls-pkg-usage-title">{{ __('app.packages.usage_title') }} <span class="ls-pkg-usage-count">{{ $packageUsages->count() }}</span><x-ui.icon name="chevron-right" class="ls-pkg-usage-chev" /></summary>
            <ul class="ls-pkg-log">
                @foreach ($packageUsages as $u)
                    <li>
                        <span class="ls-pkg-log-main">
                            <b>{{ $u->label() }}@if ($u->booking) · <a href="/bookings/{{ $u->booking->id }}" class="ls-link">#{{ $u->booking->id }}</a>@endif</b>
                            <small>{{ $u->created_at->translatedFormat('M j, g:i A') }}@if ($manyPackages) · {{ $u->memberPackage?->name }}@endif</small>
                        </span>
                        <span class="ls-pkg-change {{ $u->minutes > 0 ? 'is-out' : 'is-in' }}">{{ $u->changeLabel() }}</span>
                    </li>
                @endforeach
            </ul>
        </details>
    @endif
</section>

@if ($canAssignPackages)
    {{-- Add package: from a template (terms prefilled, still editable) or custom. --}}
    <x-ui.modal id="add-package" :title="__('app.packages.add_title')" :subtitle="$user->name">
        <form method="POST" action="{{ route('member-packages.store', $user->id) }}" id="add-package-form" class="ls-pkg-form">
            @csrf
            <div class="ls-inv-seg" role="radiogroup">
                <label><input type="radio" name="pkg_mode" value="template" data-pkg-mode @checked(old('pkg_mode', $packageTemplates->isNotEmpty() ? 'template' : 'custom') === 'template')><span>{{ __('app.packages.from_template') }}</span></label>
                <label><input type="radio" name="pkg_mode" value="custom" data-pkg-mode @checked(old('pkg_mode', $packageTemplates->isNotEmpty() ? 'template' : 'custom') === 'custom')><span>{{ __('app.packages.custom') }}</span></label>
            </div>

            <div data-pkg-template-field>
                @if ($packageTemplates->isEmpty())
                    <p class="ls-hint">{{ __('app.packages.no_templates') }}</p>
                @else
                    <div class="ls-field">
                        <label class="ls-label" for="f-package_template_id">{{ __('app.packages.choose_template') }}</label>
                        <select id="f-package_template_id" name="package_template_id" class="ls-select" data-pkg-template>
                            <option value="">—</option>
                            @foreach ($packageTemplates as $t)
                                <option value="{{ $t->id }}" data-name="{{ $t->name }}" data-hours="{{ rtrim(rtrim(number_format($t->total_minutes / 60, 2, '.', ''), '0'), '.') }}"
                                        data-price="{{ $t->price }}" data-days="{{ $t->validity_days }}" @selected((string) old('package_template_id') === (string) $t->id)>
                                    {{ $t->name }} · {{ $t->hoursLabel() }} · {{ $currency }} {{ number_format($t->price, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('package_template_id') <span class="ls-error"><x-ui.icon name="alert" />{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>

            <x-ui.input name="name" :label="__('app.packages.name')" :value="old('name')" :placeholder="__('app.packages.name_ph')" maxlength="80" required />
            <div class="ls-pkg-form-row">
                <x-ui.input name="hours" type="number" :label="__('app.packages.hours')" :value="old('hours')" step="0.25" min="0.25" inputmode="decimal" required />
                <div class="ls-field">
                    <label class="ls-label" for="f-price_paid">{{ __('app.packages.price_paid') }} <span class="ls-req" aria-hidden="true">*</span></label>
                    <div class="ls-input-affix"><span aria-hidden="true">{{ $currency }}</span>
                        <input type="number" step="0.01" min="0" id="f-price_paid" name="price_paid" class="ls-input {{ $errors->has('price_paid') ? 'is-invalid' : '' }}" inputmode="decimal" required value="{{ old('price_paid') }}">
                    </div>
                    @error('price_paid') <span class="ls-error"><x-ui.icon name="alert" />{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="ls-pkg-form-row">
                <x-ui.input name="starts_on" type="date" :label="__('app.packages.starts_on')" :value="old('starts_on', today()->toDateString())" required />
                <x-ui.input name="expires_on" type="date" :label="__('app.packages.expires_on')" :value="old('expires_on', today()->addDays(29)->toDateString())" required />
            </div>
            <x-ui.input name="notes" :label="__('app.packages.notes')" :value="old('notes')" :placeholder="__('app.packages.notes_ph')" maxlength="500" optional />
        </form>
        <x-slot:footer>
            <x-ui.button variant="ghost" data-ls-close>{{ __('app.packages.cancel') }}</x-ui.button>
            <x-ui.button variant="primary" type="submit" form="add-package-form">{{ __('app.packages.assign_cta') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Cancel package: one modal; the clicked card sets its action. --}}
    <x-ui.modal id="cancel-package" :title="__('app.packages.cancel_title')" size="narrow">
        <form method="POST" action="" id="cancel-package-form" class="ls-pkg-form">
            @csrf
            <p class="ls-pkg-cancel-body"><b data-pkg-cancel-name></b><br>{{ __('app.packages.cancel_body') }}</p>
            <x-ui.input name="reason" :label="__('app.packages.cancel_reason')" maxlength="255" optional />
        </form>
        <x-slot:footer>
            <x-ui.button variant="ghost" data-ls-close>{{ __('app.packages.keep') }}</x-ui.button>
            <x-ui.button variant="danger" type="submit" form="cancel-package-form">{{ __('app.packages.cancel_confirm') }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    <script>
        (function () {
            const form = document.getElementById('add-package-form');
            const tplField = form.querySelector('[data-pkg-template-field]');
            const tpl = form.querySelector('[data-pkg-template]');
            const f = (n) => form.querySelector(`[name="${n}"]`);
            const iso = (d) => d.toISOString().slice(0, 10);
            let days = null;

            const syncExpiry = () => {
                if (!days || !f('starts_on').value) return;
                const d = new Date(f('starts_on').value + 'T00:00:00Z');
                d.setUTCDate(d.getUTCDate() + days - 1); // valid N days, inclusive
                f('expires_on').value = iso(d);
            };
            const syncMode = () => {
                const custom = form.querySelector('[data-pkg-mode]:checked').value === 'custom';
                tplField.hidden = custom;
                if (custom && tpl) { tpl.value = ''; days = null; }
            };
            form.querySelectorAll('[data-pkg-mode]').forEach((r) => r.addEventListener('change', syncMode));
            tpl?.addEventListener('change', () => {
                const o = tpl.selectedOptions[0];
                if (!o || !o.value) { days = null; return; }
                f('name').value = o.dataset.name;
                f('hours').value = o.dataset.hours;
                f('price_paid').value = o.dataset.price;
                days = parseInt(o.dataset.days, 10);
                syncExpiry();
            });
            f('starts_on').addEventListener('change', syncExpiry);
            syncMode();

            document.querySelectorAll('[data-pkg-cancel]').forEach((b) => b.addEventListener('click', () => {
                const cf = document.getElementById('cancel-package-form');
                cf.action = b.dataset.pkgCancel;
                cf.querySelector('[data-pkg-cancel-name]').textContent = b.dataset.pkgName;
                LS.open('cancel-package');
            }));

            @if ($errors->hasAny(['name', 'hours', 'price_paid', 'starts_on', 'expires_on', 'notes', 'package_template_id']))
                window.addEventListener('load', () => LS.open('add-package'));
            @endif
        })();
    </script>
@endif
