{{--
    Member picker — search the registry, or add a member inline when the search
    comes up empty, without losing the half-filled booking form behind it.

    Shared by bookings/create and shared-sessions/create. Posts the chosen member
    as `hotspot_user_id`.

    Params:
      $label        — field label
      $selectedId   — pre-selected member id (optional)
      $inputClass   — classes for the search box (each screen has its own shape)
      $resultsClass — extra classes for the dropdown
--}}
@php
    $label        = $label        ?? __('app.booking.user');
    $selectedId   = $selectedId   ?? '';
    $inputClass   = $inputClass   ?? 'w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm';
    $resultsClass = $resultsClass ?? '';
@endphp

<label class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>

<input type="text" id="user-search" placeholder="{{ __('app.placeholder.search_name_phone') }}"
       autocomplete="off" class="{{ $inputClass }}">
<input type="hidden" name="hotspot_user_id" id="selected-user-id" value="{{ old('hotspot_user_id', $selectedId) }}">

<div id="search-results" class="hidden absolute z-10 bg-white border border-gray-200 rounded-lg shadow-lg mt-1 w-full {{ $resultsClass }}"></div>

<div id="selected-user-display" class="hidden mt-2 flex items-center gap-2 bg-blue-50 rounded-lg px-3 py-2">
    <span class="text-sm font-medium text-blue-800" id="selected-user-name"></span>
    <span class="text-xs text-blue-600" id="selected-user-phone"></span>
    <button type="button" onclick="clearUserSelection()" class="ms-auto text-xs text-gray-400 hover:text-red-500">&cross;</button>
</div>

{{-- Quick-add. Inputs carry no `name`, so they never post with the parent form. --}}
<div id="quick-add" class="hidden mt-2 rounded-lg border border-blue-100 bg-blue-50/60 p-3">
    <p class="text-xs font-semibold text-gray-700 mb-2">{{ __('app.user.quick_add_title') }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        <input type="text" id="quick-add-name" placeholder="{{ __('app.user.name') }}"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        <input type="text" id="quick-add-phone" placeholder="{{ __('app.user.phone') }}" inputmode="tel"
               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <p id="quick-add-error" class="hidden text-xs text-red-600 mt-2"></p>

    <div class="flex items-center gap-2 mt-2.5">
        <button type="button" id="quick-add-submit"
                class="bg-blue-600 text-white px-3 py-1.5 rounded-lg hover:bg-blue-700 transition text-xs font-medium disabled:opacity-60">
            {{ __('app.user.add_and_select') }}
        </button>
        <button type="button" id="quick-add-cancel" class="text-xs text-gray-500 hover:text-gray-700">
            {{ __('app.common.cancel') }}
        </button>
        <span class="text-xs text-gray-400 ms-auto">{{ __('app.user.quick_add_hint') }}</span>
    </div>
</div>

<script>
(function () {
    const TOKEN     = '{{ csrf_token() }}';
    const search    = document.getElementById('user-search');
    const results   = document.getElementById('search-results');
    const panel     = document.getElementById('quick-add');
    const nameInput = document.getElementById('quick-add-name');
    const phoneInput= document.getElementById('quick-add-phone');
    const errorBox  = document.getElementById('quick-add-error');
    const submitBtn = document.getElementById('quick-add-submit');

    let searchTimeout = null;

    // Built with the DOM API rather than innerHTML: member names are free text
    // and would otherwise break (or inject) markup — an apostrophe was enough.
    function resultRow(user) {
        const row = document.createElement('div');
        row.className = 'px-4 py-3 hover:bg-gray-50 cursor-pointer border-b last:border-0';

        const name = document.createElement('p');
        name.className = 'text-sm font-medium text-gray-900';
        name.textContent = user.name;

        const phone = document.createElement('p');
        phone.className = 'text-xs text-gray-500';
        phone.textContent = user.phone;

        row.append(name, phone);
        row.addEventListener('click', () => selectUser(user.id, user.name, user.phone));
        return row;
    }

    function addRow(query) {
        const row = document.createElement('button');
        row.type = 'button';
        row.className = 'w-full text-start px-4 py-3 text-sm font-medium text-blue-600 hover:bg-blue-50 flex items-center gap-2';
        row.innerHTML = '<svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>';

        const label = document.createElement('span');
        label.textContent = @json(__('app.user.add_new_member'));
        row.append(label);

        row.addEventListener('click', () => openQuickAdd(query));
        return row;
    }

    function openQuickAdd(query) {
        results.classList.add('hidden');
        panel.classList.remove('hidden');
        errorBox.classList.add('hidden');

        // Digits look like a phone number, anything else like a name.
        if (/^[0-9+\s-]+$/.test(query)) {
            phoneInput.value = query.trim();
            nameInput.value  = '';
            nameInput.focus();
        } else {
            nameInput.value  = query.trim();
            phoneInput.value = '';
            phoneInput.focus();
        }
    }

    function closeQuickAdd() {
        panel.classList.add('hidden');
        nameInput.value = phoneInput.value = '';
        errorBox.classList.add('hidden');
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.classList.remove('hidden');
    }

    function submitQuickAdd() {
        submitBtn.disabled = true;
        errorBox.classList.add('hidden');

        fetch('/users/quick', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': TOKEN,
            },
            body: JSON.stringify({ name: nameInput.value.trim(), phone: phoneInput.value.trim() }),
        })
            // Parsed defensively: an expired session redirects to the HTML login
            // page, which would otherwise blow up in response.json().
            .then(response => response.text().then(text => {
                let body = null;
                try { body = JSON.parse(text); } catch (e) { /* not JSON */ }
                return { ok: response.ok, body };
            }))
            .then(({ ok, body }) => {
                if (!body) {
                    showError(@json(__('app.user.quick_add_session_lost')));
                    return;
                }
                if (ok) {
                    selectUser(body.id, body.name, body.phone);
                    closeQuickAdd();
                    return;
                }
                // Field validation comes back as `errors`, rule failures (plan
                // limit, missing speed profile, router down) as `message`.
                const first = body.errors ? Object.values(body.errors)[0][0] : body.message;
                showError(first || @json(__('app.user.quick_add_failed')));
            })
            .catch(() => showError(@json(__('app.user.quick_add_failed'))))
            .finally(() => { submitBtn.disabled = false; });
    }

    search.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(searchTimeout);

        if (q.length < 2) {
            results.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(() => {
            fetch(`/users/search?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(users => {
                    results.replaceChildren();

                    if (users.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'px-4 py-3 text-sm text-gray-500';
                        empty.textContent = @json(__('app.user.no_match'));
                        results.append(empty);
                    } else {
                        users.forEach(u => results.append(resultRow(u)));
                    }

                    results.append(addRow(q));
                    results.classList.remove('hidden');
                });
        }, 300);
    });

    submitBtn.addEventListener('click', submitQuickAdd);
    document.getElementById('quick-add-cancel').addEventListener('click', closeQuickAdd);

    // Enter inside the quick-add fields must not submit the booking form behind it.
    [nameInput, phoneInput].forEach(input => {
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitQuickAdd();
            }
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#user-search') && !e.target.closest('#search-results')) {
            results.classList.add('hidden');
        }
    });

    window.selectUser = function (id, name, phone) {
        document.getElementById('selected-user-id').value          = id;
        document.getElementById('selected-user-name').textContent  = name;
        document.getElementById('selected-user-phone').textContent = phone;
        document.getElementById('selected-user-display').classList.remove('hidden');
        search.value = '';
        results.classList.add('hidden');
    };

    window.clearUserSelection = function () {
        document.getElementById('selected-user-id').value = '';
        document.getElementById('selected-user-display').classList.add('hidden');
        search.value = '';
        search.focus();
    };
})();
</script>
