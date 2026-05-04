@extends('layouts.app')

@section('content')
<div x-data="expenseApp()" class="space-y-4">
    {{-- フィルター --}}
    <div class="bg-white rounded shadow p-4 flex flex-wrap gap-4 items-end">
        <form method="GET" action="{{ route('expenses.index') }}" class="flex flex-wrap gap-4 items-end w-full">
            <div>
                <label class="block text-sm text-gray-600">年度</label>
                <select name="year" class="border rounded px-3 py-2" onchange="this.form.submit()">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}年</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600">フィルタ</label>
                <select name="filter" class="border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="all" {{ $filter === 'all' ? 'selected' : '' }}>すべて</option>
                    <option value="unclassified" {{ $filter === 'unclassified' ? 'selected' : '' }}>未仕訳</option>
                    <option value="classified" {{ $filter === 'classified' ? 'selected' : '' }}>仕訳済み</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600">支払方法</label>
                <select name="payment_method" class="border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="all" {{ $paymentMethod === 'all' ? 'selected' : '' }}>すべて</option>
                    <option value="credit_card" {{ $paymentMethod === 'credit_card' ? 'selected' : '' }}>クレカ</option>
                    <option value="cash" {{ $paymentMethod === 'cash' ? 'selected' : '' }}>現金</option>
                    <option value="paypay" {{ $paymentMethod === 'paypay' ? 'selected' : '' }}>PayPay</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm text-gray-600">利用場所で検索</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="利用場所を入力..."
                    class="border rounded px-3 py-2 w-full">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">検索</button>
        </form>
    </div>

    {{-- 進捗バー --}}
    <div class="bg-white rounded shadow p-4">
        <div class="flex justify-between text-sm text-gray-600 mb-1">
            <span>仕訳進捗</span>
            <span>{{ $classifiedCount }} / {{ $totalCount }} 件</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-indigo-600 h-3 rounded-full transition-all"
                style="width: {{ $totalCount > 0 ? round($classifiedCount / $totalCount * 100) : 0 }}%"></div>
        </div>
    </div>

    {{-- 一括適用バー（選択時に表示） --}}
    <div x-show="selectedIds.length > 0" x-cloak
        class="bg-indigo-600 text-white rounded shadow p-3 flex items-center gap-4 sticky top-0 z-10">
        <span class="text-sm" x-text="selectedIds.length + '件選択中'"></span>
        <select x-model="bulkCategoryId" class="border rounded px-3 py-1 text-sm text-gray-800">
            <option value="">-- 科目を選択 --</option>
            @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
        <button @click="bulkClassify()" class="bg-white text-indigo-700 px-4 py-1 rounded text-sm font-bold hover:bg-indigo-100">
            一括適用
        </button>
        <span class="text-indigo-200">|</span>
        <select x-model="bulkEntityId" class="border rounded px-3 py-1 text-sm text-gray-800">
            <option value="">-- 事業体を選択 --</option>
            @foreach($allEntities as $ent)
                <option value="{{ $ent->id }}">{{ $ent->name }}</option>
            @endforeach
        </select>
        <button @click="bulkChangeEntity()" class="bg-yellow-400 text-yellow-900 px-4 py-1 rounded text-sm font-bold hover:bg-yellow-300">
            事業体変更
        </button>
        <button @click="selectedIds = []" class="text-indigo-200 hover:text-white text-sm ml-auto">選択解除</button>
    </div>

    {{-- メインエリア: 左=今年 / 右=去年参照 --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- 左: 今年の明細 --}}
        <div class="lg:col-span-2 space-y-2">
            <div class="flex items-center gap-3">
                <h2 class="text-lg font-bold text-gray-700">{{ $currentYear }}年 経費明細</h2>
                <label class="text-sm text-gray-500 flex items-center gap-1 cursor-pointer">
                    <input type="checkbox" @change="toggleAll($event.target.checked)" class="rounded">
                    全選択
                </label>
            </div>

            {{-- freee風インライン入力行 --}}
            <div class="bg-indigo-50 rounded shadow p-3 border-2 border-dashed border-indigo-300">
                <form @submit.prevent="addExpense()" class="flex items-center gap-2">
                    <div class="w-28">
                        <input type="date" x-model="newExpense.date" required
                            class="border rounded px-2 py-1.5 w-full text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400"
                            :max="'{{ $currentYear }}-12-31'" :min="'{{ $currentYear }}-01-01'">
                    </div>
                    <div class="flex-1 min-w-0">
                        <input type="text" x-model="newExpense.vendor_name" required
                            placeholder="利用場所（例：Amazon）"
                            @keydown.tab="$event.shiftKey || $refs.amountInput.focus()"
                            class="border rounded px-2 py-1.5 w-full text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                    </div>
                    <div class="w-28">
                        <input type="number" x-model="newExpense.amount" required x-ref="amountInput"
                            placeholder="金額" min="1"
                            class="border rounded px-2 py-1.5 w-full text-sm text-right font-mono focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                    </div>
                    <div class="w-24">
                        <select x-model="newExpense.payment_method"
                            class="border rounded px-2 py-1.5 w-full text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            <option value="credit_card">クレカ</option>
                            <option value="cash">現金</option>
                            <option value="paypay">PayPay</option>
                        </select>
                    </div>
                    <div class="w-36">
                        <select x-model="newExpense.account_category_id"
                            class="border rounded px-2 py-1.5 w-full text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            <option value="">-- 科目 --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" :disabled="addingExpense"
                        class="bg-indigo-600 text-white px-4 py-1.5 rounded text-sm font-bold hover:bg-indigo-700 disabled:opacity-50 whitespace-nowrap">
                        <span x-show="!addingExpense">+ 追加</span>
                        <span x-show="addingExpense">...</span>
                    </button>
                </form>
                <div class="text-xs text-gray-500 mt-1">
                    💡 Tabキーで次の項目へ、Enterで追加
                </div>
            </div>

            @forelse($expenses as $expense)
            <div class="bg-white rounded shadow p-3 flex items-center gap-3 hover:bg-gray-50 transition
                {{ $expense->isClassified() ? 'border-l-4 border-green-400' : 'border-l-4 border-orange-400' }}"
                :class="selectedIds.includes({{ $expense->id }}) ? 'ring-2 ring-indigo-400' : ''"
                id="expense-{{ $expense->id }}">
                <input type="checkbox" value="{{ $expense->id }}"
                    :checked="selectedIds.includes({{ $expense->id }})"
                    @change="toggleSelect({{ $expense->id }})"
                    class="rounded">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500">{{ $expense->date->format('m/d') }}</span>
                        <span class="font-medium truncate cursor-pointer hover:text-indigo-600"
                            @click="searchPrev('{{ addslashes($expense->vendor_name) }}')">
                            {{ $expense->vendor_name }}
                        </span>
                        <span class="text-xs px-2 py-0.5 rounded
                            {{ $expense->payment_method === 'credit_card' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $expense->payment_method === 'credit_card' ? 'クレカ' : ($expense->payment_method === 'paypay' ? 'PayPay' : '現金') }}
                        </span>
                    </div>
                    <div class="text-sm text-gray-500">{{ $expense->memo }}</div>
                </div>
                <div class="text-right font-mono font-bold whitespace-nowrap">
                    ¥{{ number_format($expense->amount) }}
                </div>
                <div class="w-28">
                    <select class="border rounded px-2 py-1 w-full text-xs
                        {{ $expense->entity_id == ($currentEntity->id ?? null) ? 'bg-gray-100' : 'bg-yellow-100 border-yellow-400' }}"
                        @change="changeEntity({{ $expense->id }}, $event.target.value)"
                        id="entity-select-{{ $expense->id }}">
                        @foreach($allEntities as $ent)
                            <option value="{{ $ent->id }}"
                                {{ $expense->entity_id == $ent->id ? 'selected' : '' }}>
                                {{ $ent->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-44">
                    <select class="border rounded px-2 py-1 w-full text-sm"
                        @change="classify({{ $expense->id }}, $event.target.value)">
                        <option value="">-- 未仕訳 --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}"
                                {{ $expense->account_category_id == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button @click="deleteExpense({{ $expense->id }})"
                    class="text-gray-400 hover:text-red-500 p-1" title="削除">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </div>
            @empty
            <div class="bg-white rounded shadow p-8 text-center text-gray-500">
                明細がありません。インポートしてください。
            </div>
            @endforelse
            <div class="mt-4">{{ $expenses->appends(request()->query())->links() }}</div>
        </div>

        {{-- 右: 去年の参照パネル --}}
        <div class="space-y-2">
            <h2 class="text-lg font-bold text-gray-700">{{ $currentYear - 1 }}年 参照</h2>
            <div class="bg-white rounded shadow p-3">
                <input type="text" x-model="prevSearchQuery" @input.debounce.300ms="searchPrev(prevSearchQuery)"
                    placeholder="利用場所で去年を検索..." class="border rounded px-3 py-2 w-full text-sm mb-2">
                <div x-show="prevResults.length === 0 && !prevLoading" class="text-sm text-gray-400 text-center py-4">
                    利用場所をクリックまたは検索すると去年の仕訳が表示されます
                </div>
                <div x-show="prevLoading" class="text-sm text-gray-400 text-center py-4">検索中...</div>
                <template x-for="item in prevResults" :key="item.id">
                    <div class="border-b py-2 last:border-0">
                        <div class="flex justify-between items-center">
                            <span class="text-sm" x-text="item.vendor_name"></span>
                            <span class="text-sm font-mono" x-text="'¥' + Number(item.amount).toLocaleString()"></span>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-xs text-gray-500" x-text="item.date?.substring(0, 10)"></span>
                            <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-700"
                                x-text="item.account_category?.name ?? '未分類'"></span>
                        </div>
                    </div>
                </template>
            </div>

            @if($prevExpenses->isNotEmpty())
            <div class="bg-white rounded shadow p-3 max-h-96 overflow-y-auto">
                <h3 class="text-sm font-bold text-gray-600 mb-2">去年の仕訳済み（{{ $search ?: '全件' }}）</h3>
                @foreach($prevExpenses->take(30) as $prev)
                <div class="border-b py-1 last:border-0 text-sm">
                    <div class="flex justify-between">
                        <span class="truncate">{{ $prev->vendor_name }}</span>
                        <span class="font-mono">¥{{ number_format($prev->amount) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>{{ $prev->date->format('m/d') }}</span>
                        <span class="text-green-600">{{ $prev->accountCategory?->name }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function expenseApp() {
    return {
        prevSearchQuery: '',
        prevResults: [],
        prevLoading: false,
        selectedIds: [],
        bulkCategoryId: '',
        bulkEntityId: '',
        addingExpense: false,
        newExpense: {
            date: new Date().toISOString().split('T')[0],
            vendor_name: '',
            amount: '',
            payment_method: 'credit_card',
            account_category_id: '',
        },

        async addExpense() {
            if (!this.newExpense.date || !this.newExpense.vendor_name || !this.newExpense.amount) {
                alert('日付、利用場所、金額は必須です');
                return;
            }
            this.addingExpense = true;
            try {
                const res = await fetch('/expenses', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        ...this.newExpense,
                        year: new URLSearchParams(window.location.search).get('year') || new Date().getFullYear(),
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    // リセットして再読み込み
                    this.newExpense = {
                        date: new Date().toISOString().split('T')[0],
                        vendor_name: '',
                        amount: '',
                        payment_method: 'credit_card',
                        account_category_id: '',
                    };
                    location.reload();
                } else {
                    alert(data.message || '追加に失敗しました');
                }
            } catch (e) {
                alert('エラーが発生しました');
            }
            this.addingExpense = false;
        },

        toggleSelect(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx === -1) { this.selectedIds.push(id); }
            else { this.selectedIds.splice(idx, 1); }
        },

        toggleAll(checked) {
            if (checked) {
                this.selectedIds = [...document.querySelectorAll('input[type="checkbox"][value]')]
                    .map(el => parseInt(el.value)).filter(v => !isNaN(v));
            } else {
                this.selectedIds = [];
            }
        },

        async classify(expenseId, categoryId) {
            const res = await fetch(`/expenses/${expenseId}/classify`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ account_category_id: categoryId || null }),
            });
            const data = await res.json();
            if (data.success) {
                const el = document.getElementById(`expense-${expenseId}`);
                if (categoryId) {
                    el.classList.remove('border-orange-400');
                    el.classList.add('border-green-400');
                } else {
                    el.classList.remove('border-green-400');
                    el.classList.add('border-orange-400');
                }
            }
        },

        async searchPrev(query) {
            if (!query) { this.prevResults = []; return; }
            this.prevSearchQuery = query;
            this.prevLoading = true;
            const year = new URLSearchParams(window.location.search).get('year') || new Date().getFullYear();
            const res = await fetch(`/expenses/search-prev?vendor_name=${encodeURIComponent(query)}&year=${year}`);
            this.prevResults = await res.json();
            this.prevLoading = false;
        },

        async bulkClassify() {
            if (!this.bulkCategoryId) {
                alert('科目を選択してください');
                return;
            }
            if (this.selectedIds.length === 0) return;

            const res = await fetch('/expenses/bulk-classify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    expense_ids: this.selectedIds,
                    account_category_id: this.bulkCategoryId,
                }),
            });
            const data = await res.json();
            if (data.success) {
                alert(`${data.updated_count}件を「${data.category_name}」に一括適用しました`);
                location.reload();
            }
        },

        async changeEntity(expenseId, entityId) {
            const res = await fetch(`/expenses/${expenseId}/change-entity`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ entity_id: entityId }),
            });
            const data = await res.json();
            if (data.success) {
                // 事業体が変更されたら行の背景色を更新
                const selectEl = document.getElementById(`entity-select-${expenseId}`);
                selectEl.classList.remove('bg-gray-100', 'bg-yellow-100', 'border-yellow-400');
                selectEl.classList.add('bg-yellow-100', 'border-yellow-400');
            }
        },

        async bulkChangeEntity() {
            if (!this.bulkEntityId) {
                alert('事業体を選択してください');
                return;
            }
            if (this.selectedIds.length === 0) return;

            if (!confirm(`選択した${this.selectedIds.length}件の経費を別の事業体に移動しますか？`)) return;

            const res = await fetch('/expenses/bulk-change-entity', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    expense_ids: this.selectedIds,
                    entity_id: this.bulkEntityId,
                }),
            });
            const data = await res.json();
            if (data.success) {
                alert(`${data.updated_count}件を「${data.entity_name}」に移動しました`);
                location.reload();
            }
        },

        async deleteExpense(id) {
            if (!confirm('この経費を削除しますか？')) return;
            const res = await fetch(`/expenses/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById(`expense-${id}`).remove();
            }
        },
    };
}
</script>
@endsection
