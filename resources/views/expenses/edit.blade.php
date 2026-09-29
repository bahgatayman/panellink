@extends('layouts.app')

@section('page-title', __('app.expenses.edit_expense'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('app.expenses.edit_expense') }}</h1>
        <a href="{{ route('expenses.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; {{ __('app.common.back') }}</a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('expenses.update', $expense->id) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.expenses.amount') }}</label>
                <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount', $expense->amount) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.expenses.category') }}</label>
                <select name="expense_category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">{{ __('app.expenses.uncategorized') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ old('expense_category_id', $expense->expense_category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.expenses.date') }}</label>
                <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->toDateString()) }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('app.expenses.note_optional') }}</label>
                <input type="text" name="note" value="{{ old('note', $expense->note) }}" maxlength="500"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition text-sm font-medium shadow-sm">
                {{ __('app.expenses.save') }}
            </button>
            <a href="{{ route('expenses.index') }}" class="px-5 py-2.5 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 transition">
                {{ __('app.expenses.cancel') }}
            </a>
        </div>
    </form>
@endsection
