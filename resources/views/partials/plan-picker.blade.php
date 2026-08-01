{{--
    Plan grid + renewal request form. Shared by the expired screen (standalone
    page) and /subscription/plans (inside the owner layout).

    There is no payment gateway: submitting raises a request an admin approves
    after payment is settled out of band.
--}}

@if ($pendingRequest)
    {{-- A request is open: show its state instead of a second form. --}}
    <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-5 text-start">
        <div class="flex items-start gap-3">
            <span class="mt-0.5 shrink-0 w-8 h-8 rounded-full bg-yellow-100 text-yellow-700 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-gray-900">{{ __('app.subscription.request_pending_title') }}</p>
                <p class="text-sm text-gray-600 mt-1">
                    {{ $pendingRequest->plan->name }} ·
                    {{ $pendingRequest->months }} {{ __('app.subscription.months') }} ·
                    <span class="font-medium">ج.م {{ number_format($pendingRequest->amount, 2) }}</span>
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('app.subscription.requested_on') }} {{ $pendingRequest->created_at->format('d M Y, H:i') }}
                </p>
                <p class="text-sm text-gray-600 mt-3">{{ __('app.subscription.request_pending_hint') }}</p>

                <form method="POST" action="{{ route('subscription.request.cancel', $pendingRequest->id) }}" class="mt-3"
                      onsubmit="return confirm('{{ __('app.subscription.cancel_request_confirm') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700">
                        {{ __('app.subscription.cancel_request') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@elseif ($plans->isEmpty())
    <p class="text-sm text-gray-500 text-center py-8">{{ __('app.subscription.no_plans') }}</p>
@else
    <form method="POST" action="{{ route('subscription.request') }}" id="plan-form" class="text-start">
        @csrf

        {{-- ── Plan cards ──────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($plans as $plan)
                @php
                    $isCurrent = $owner->plan_id === $plan->id;
                    // 0 / null on a plan limit means unlimited (see Owner::canAddMore*).
                    $limits = [
                        ['label' => __('app.common.members'),   'value' => $plan->max_members,    'unlimited' => false],
                        ['label' => __('app.nav.workspaces'),   'value' => $plan->max_workspaces, 'unlimited' => true],
                        ['label' => __('app.plan.max_rooms'),   'value' => $plan->max_rooms,      'unlimited' => true],
                        ['label' => __('app.plan.max_products'),'value' => $plan->max_products,   'unlimited' => true],
                    ];
                @endphp

                <label class="plan-option group relative flex flex-col cursor-pointer rounded-2xl border-2 border-gray-200 bg-white overflow-hidden transition hover:border-blue-300 hover:shadow-md focus-within:ring-2 focus-within:ring-blue-500">
                    <input type="radio" name="plan_id" value="{{ $plan->id }}" class="sr-only"
                           data-price="{{ $plan->price_per_month }}" data-name="{{ $plan->name }}"
                           {{ old('plan_id', $owner->plan_id) == $plan->id ? 'checked' : '' }} required>

                    {{-- Selected tick, revealed by the script below. --}}
                    <span class="plan-check hidden absolute top-4 end-4 w-6 h-6 rounded-full bg-blue-600 text-white items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    </span>

                    <div class="p-5 pb-4">
                        @if ($isCurrent)
                            <span class="inline-block mb-2 text-[10px] font-semibold uppercase tracking-wide bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                                {{ __('app.subscription.your_plan') }}
                            </span>
                        @endif

                        <p class="font-semibold text-gray-900">{{ $plan->name }}</p>

                        <div class="mt-3 flex items-baseline gap-1.5">
                            @if ($plan->isFree())
                                <span class="text-3xl font-bold text-gray-900">{{ __('app.subscription.free') }}</span>
                            @else
                                <span class="text-sm text-gray-500">ج.م</span>
                                <span class="text-3xl font-bold text-gray-900">{{ number_format($plan->price_per_month, 0) }}</span>
                                <span class="text-xs text-gray-500">{{ __('app.subscription.per_month_each') }}</span>
                            @endif
                        </div>

                        {{-- Live total for the chosen duration. --}}
                        <p class="plan-card-total text-xs text-gray-400 mt-1 h-4"></p>
                    </div>

                    <div class="border-t border-gray-100 p-5 pt-4 flex-1">
                        <ul class="space-y-2 text-sm">
                            @foreach ($limits as $limit)
                                @continue($limit['value'] === null && ! $limit['unlimited'])
                                <li class="flex items-center gap-2 text-gray-600">
                                    <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    <span>
                                        <span class="font-medium text-gray-900">
                                            {{ $limit['unlimited'] && ! $limit['value'] ? __('app.subscription.unlimited') : $limit['value'] }}
                                        </span>
                                        {{ $limit['label'] }}
                                    </span>
                                </li>
                            @endforeach

                            @foreach ($plan->defaultFeatures() as $feature)
                                <li class="flex items-center gap-2 text-gray-600">
                                    <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    {{-- Unknown keys fall back to the raw name rather than printing "app.feature.x". --}}
                                    {{ Lang::has('app.feature.' . $feature) ? __('app.feature.' . $feature) : ucfirst($feature) }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </label>
            @endforeach
        </div>

        {{-- ── Duration ────────────────────────────────────────────────── --}}
        <div class="mt-8">
            <p class="text-sm font-medium text-gray-700 mb-2">{{ __('app.subscription.duration') }}</p>
            <div class="inline-flex flex-wrap gap-2" role="radiogroup">
                @foreach ([1, 3, 6, 12] as $m)
                    <label class="month-option cursor-pointer">
                        <input type="radio" name="months" value="{{ $m }}" class="sr-only"
                               {{ (int) old('months', 1) === $m ? 'checked' : '' }} required>
                        <span class="month-pill inline-flex items-center justify-center min-w-[5.5rem] px-4 py-2 rounded-lg border-2 border-gray-200 bg-white text-sm font-medium text-gray-600 transition hover:border-blue-300">
                            {{ $m }} {{ __('app.subscription.months') }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- ── Summary + submit ────────────────────────────────────────── --}}
        <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-5">
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('app.subscription.summary') }}</p>
                    <p id="summary-line" class="text-sm text-gray-700 mt-1">—</p>
                </div>
                <div class="sm:text-end">
                    <p class="text-xs text-gray-500">{{ __('app.subscription.billed_total') }}</p>
                    <p id="plan-total" class="text-2xl font-bold text-gray-900">—</p>
                </div>
            </div>

            <div class="mt-4 pt-4 border-t border-gray-200">
                <label for="note" class="block text-xs font-medium text-gray-600 mb-1.5">
                    {{ __('app.common.notes') }} <span class="text-gray-400 font-normal">({{ __('app.admin_notif.optional') }})</span>
                </label>
                <input type="text" name="note" id="note" maxlength="500" value="{{ old('note') }}"
                       placeholder="{{ __('app.subscription.note_placeholder') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit"
                    class="mt-4 w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-blue-600 text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition font-medium text-sm shadow-sm">
                {{ __('app.subscription.request_renewal') }}
                <svg class="w-4 h-4 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
            </button>

            <p class="text-xs text-gray-400 mt-3">{{ __('app.subscription.no_online_payment_hint') }}</p>
        </div>
    </form>

    <script>
    (function () {
        const form    = document.getElementById('plan-form');
        const total   = document.getElementById('plan-total');
        const summary = document.getElementById('summary-line');

        const FREE     = @json(__('app.subscription.free'));
        const MONTHS   = @json(__('app.subscription.months'));
        const CURRENCY = 'ج.م ';

        function money(amount) {
            return CURRENCY + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function months() {
            const picked = form.querySelector('input[name="months"]:checked');
            return picked ? Number(picked.value) : 1;
        }

        function paint() {
            const term    = months();
            const checked = form.querySelector('input[name="plan_id"]:checked');

            // Plan cards: selected outline + tick, and each card's total for this term.
            form.querySelectorAll('.plan-option').forEach(card => {
                const input = card.querySelector('input');
                const on    = input.checked;

                card.classList.toggle('border-blue-600', on);
                card.classList.toggle('shadow-md', on);
                card.classList.toggle('border-gray-200', !on);

                const tick = card.querySelector('.plan-check');
                tick.classList.toggle('hidden', !on);
                tick.classList.toggle('flex', on);

                const price = Number(input.dataset.price || 0);
                const line  = card.querySelector('.plan-card-total');
                line.textContent = (price === 0 || term === 1) ? '' : money(price * term) + ' / ' + term + ' ' + MONTHS;
            });

            // Duration pills.
            form.querySelectorAll('.month-option').forEach(option => {
                const on = option.querySelector('input').checked;
                const pill = option.querySelector('.month-pill');
                pill.classList.toggle('border-blue-600', on);
                pill.classList.toggle('bg-blue-50', on);
                pill.classList.toggle('text-blue-700', on);
                pill.classList.toggle('border-gray-200', !on);
                pill.classList.toggle('text-gray-600', !on);
            });

            if (!checked) {
                summary.textContent = '—';
                total.textContent   = '—';
                return;
            }

            const price  = Number(checked.dataset.price || 0);
            const amount = price * term;

            summary.textContent = checked.dataset.name + ' · ' + term + ' ' + MONTHS
                + (price > 0 ? ' × ' + money(price) : '');
            total.textContent = amount === 0 ? FREE : money(amount);
        }

        form.addEventListener('change', paint);
        paint();
    })();
    </script>
@endif

@if ($recentRequests->isNotEmpty())
    <div class="mt-8 text-start">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('app.subscription.your_requests') }}</h3>
        <ul class="divide-y divide-gray-100 rounded-xl border border-gray-100 bg-white">
            @foreach ($recentRequests as $req)
                @php $tone = ['green' => 'bg-green-100 text-green-700', 'red' => 'bg-red-100 text-red-700', 'yellow' => 'bg-yellow-100 text-yellow-800', 'gray' => 'bg-gray-100 text-gray-600'][$req->statusColor()]; @endphp
                <li class="flex items-center gap-3 px-4 py-3 text-sm">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-gray-900 truncate">
                            {{ $req->plan?->name }} · {{ $req->months }} {{ __('app.subscription.months') }}
                        </p>
                        <p class="text-xs text-gray-400">{{ $req->created_at->format('d M Y') }}</p>
                        @if ($req->admin_note)
                            <p class="text-xs text-gray-500 mt-0.5">{{ $req->admin_note }}</p>
                        @endif
                    </div>
                    <span class="text-gray-600">ج.م {{ number_format($req->amount, 2) }}</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tone }}">
                        {{ __('app.subscription.status_' . $req->status) }}
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
