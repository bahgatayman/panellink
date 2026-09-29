@extends('layouts.app')

@section('page-title', __('app.expenses.title'))

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.expenses.title') }}</h1>
        <div class="flex items-center gap-2">
            <a href="{{ route('financials.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                {{ __('app.financials.overview') }}
            </a>
            @if ($canCreate)
                <button type="button" data-ls-open="add-expense" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
                    <x-ui.icon name="plus" class="w-4 h-4" />
                    {{ __('app.expenses.add_expense') }}
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm">
            <x-ui.icon name="check-circle" class="w-4 h-4 shrink-0" />
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
            <x-ui.icon name="alert" class="w-4 h-4 shrink-0" />
            {{ session('error') }}
        </div>
    @endif

    {{-- Total Expenses — Revenue/Net live on the Financials page, not here. --}}
    <div class="max-w-xs mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6">
            <div class="flex items-center justify-between">
                <div class="min-w-0">
                    <p class="text-xs lg:text-sm font-medium text-gray-500">{{ __('app.expenses.total_expenses') }}</p>
                    <p class="text-2xl lg:text-3xl font-bold text-red-600 mt-1 whitespace-nowrap">ج.م {{ number_format($totalExpenses, 2) }}</p>
                </div>
                <div class="w-10 h-10 lg:w-12 lg:h-12 bg-red-50 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 lg:w-6 lg:h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6">
        @include('financials._period-filter')
    </div>

    @if ($canManageCategories)
        <div class="mb-6">
            <details class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 lg:p-6 max-w-md">
                <summary class="flex items-center justify-between gap-2 font-semibold text-gray-900 cursor-pointer select-none marker:text-gray-400">
                    <span class="flex items-center gap-2">
                        <x-ui.icon name="gear" class="w-4 h-4 text-gray-500" />
                        {{ __('app.expenses.categories') }}
                    </span>
                    <span class="text-xs font-normal text-gray-400">{{ $categories->count() }}</span>
                </summary>

                <form method="POST" action="{{ route('expense-categories.store') }}" class="flex gap-2 mt-4 mb-3">
                    @csrf
                    <input type="text" name="name" placeholder="{{ __('app.expenses.category_name') }}" required maxlength="100"
                           class="min-w-0 flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <button type="submit" class="shrink-0 px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition text-sm font-medium">
                        {{ __('app.expenses.add_category') }}
                    </button>
                </form>

                @if ($categories->isEmpty())
                    <p class="text-sm text-gray-400">{{ __('app.expenses.no_categories') }}</p>
                @else
                    <ul class="space-y-2 max-h-72 overflow-y-auto">
                        @foreach ($categories as $category)
                            <li class="flex flex-wrap items-center gap-2 text-sm border-b border-gray-50 pb-2 last:border-0 last:pb-0">
                                <form method="POST" action="{{ route('expense-categories.update', $category->id) }}" class="flex items-center gap-2 min-w-0 flex-1 basis-40">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $category->name }}" maxlength="100"
                                           class="min-w-0 flex-1 border border-gray-200 rounded-md px-2 py-1 text-sm">
                                    <button type="submit" class="text-blue-600 hover:underline text-xs font-medium shrink-0">{{ __('app.expenses.save') }}</button>
                                </form>
                                <div class="flex items-center gap-2 shrink-0 ms-auto">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 text-gray-500">{{ $category->expenses_count }}</span>
                                    <form method="POST" action="{{ route('expense-categories.destroy', $category->id) }}" onsubmit="return confirm('{{ __('app.expenses.delete_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline text-xs font-medium">{{ __('app.expenses.delete') }}</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </details>
        </div>
    @endif

    @if (! empty($byCategory))
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 lg:p-6 mb-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('app.expenses.by_category') }}</h3>
            <div class="space-y-3">
                @php $maxAmount = max(1, ...array_column($byCategory, 'amount')); @endphp
                @foreach ($byCategory as $row)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600 truncate">{{ $row['name'] }}</span>
                            <span class="font-medium text-gray-900 shrink-0 ms-2">ج.م {{ number_format($row['amount'], 2) }}</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                            <div class="h-full bg-red-400 rounded-full" style="width: {{ ($row['amount'] / $maxAmount) * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($expenses->isEmpty())
        <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
            <div class="w-12 h-12 mx-auto rounded-full bg-gray-50 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6"/>
                </svg>
            </div>
            <p class="text-gray-500 text-sm">{{ __('app.expenses.no_expenses') }}</p>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 border-b border-gray-100 bg-gray-50">
                            <th class="px-6 py-3 font-medium">{{ __('app.expenses.date') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.expenses.category') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.expenses.note') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('app.expenses.amount') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('app.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($expenses as $expense)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-gray-600 whitespace-nowrap">{{ $expense->expense_date->format('M d, Y') }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        {{ $expense->category?->name ?? __('app.expenses.uncategorized') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-500 max-w-[240px] truncate" title="{{ $expense->note }}">{{ $expense->note ?? '—' }}</td>
                                <td class="px-6 py-4 text-right font-medium text-gray-900 whitespace-nowrap">ج.م {{ number_format($expense->amount, 2) }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($canEdit)
                                            <a href="{{ route('expenses.edit', $expense->id) }}" class="text-blue-600 hover:underline text-sm font-medium">{{ __('app.expenses.edit') }}</a>
                                        @endif
                                        @if ($canDelete)
                                            <form method="POST" action="{{ route('expenses.destroy', $expense->id) }}" onsubmit="return confirm('{{ __('app.expenses.delete_confirm') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline text-sm font-medium">{{ __('app.expenses.delete') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6">{{ $expenses->links() }}</div>
    @endif

    @if ($canCreate)
        <x-ui.modal id="add-expense" :title="__('app.expenses.add_expense')">
            <form id="add-expense-form" method="POST" action="{{ route('expenses.store') }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.expenses.amount') }} <span class="text-gray-400 font-normal">(ج.م)</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" placeholder="0.00" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.expenses.category') }}</label>
                    <select name="expense_category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">{{ __('app.expenses.uncategorized') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ old('expense_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.expenses.date') }}</label>
                    <input type="date" name="expense_date" value="{{ old('expense_date', now()->toDateString()) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.expenses.note_optional') }}</label>
                    <input type="text" name="note" value="{{ old('note') }}" maxlength="500"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </form>

            @if ($errors->has('amount') || $errors->has('expense_category_id') || $errors->has('expense_date') || $errors->has('note'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mt-4">
                    <ul class="list-disc list-inside text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-slot:footer>
                <button type="button" data-ls-close class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
                    {{ __('app.expenses.cancel') }}
                </button>
                <button type="submit" form="add-expense-form" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
                    {{ __('app.expenses.save') }}
                </button>
            </x-slot:footer>
        </x-ui.modal>

        @if ($errors->has('amount') || $errors->has('expense_category_id') || $errors->has('expense_date') || $errors->has('note'))
            <script>document.addEventListener('DOMContentLoaded', () => LS.open('add-expense'));</script>
        @endif
    @endif
@endsection
