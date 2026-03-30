@extends('layouts.app')

@section('content')
<div x-data="documentEditor()" class="space-y-4">
    {{-- ヘッダー --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('documents.index') }}" class="text-gray-500 hover:text-gray-700">← 一覧</a>
            <h2 class="text-xl font-bold text-gray-700">{{ $document->type_name }} {{ $document->document_number }}</h2>
            <span class="px-2 py-1 rounded text-xs
                {{ $document->status === 'draft' ? 'bg-gray-100 text-gray-600' : '' }}
                {{ $document->status === 'sent' ? 'bg-yellow-100 text-yellow-700' : '' }}
                {{ $document->status === 'paid' ? 'bg-green-100 text-green-700' : '' }}
                {{ $document->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}">
                {{ $document->status_name }}
            </span>
        </div>
        <div class="flex gap-2">
            <select @change="updateStatus($event.target.value)" class="border rounded px-3 py-1 text-sm">
                <option value="">ステータス変更</option>
                <option value="draft">下書き</option>
                <option value="sent">送付済</option>
                <option value="paid">入金済</option>
                <option value="cancelled">キャンセル</option>
            </select>
            <form method="POST" action="{{ route('documents.destroy', $document) }}"
                onsubmit="return confirm('削除しますか？')">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-500 hover:text-red-700 px-3 py-1 text-sm">削除</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- 左: 書類情報 --}}
        <div class="lg:col-span-1">
            <form method="POST" action="{{ route('documents.update', $document) }}" class="bg-white rounded shadow p-4 space-y-3">
                @csrf @method('PUT')
                <div>
                    <label class="block text-xs text-gray-500">発行日</label>
                    <input type="date" name="issue_date" value="{{ $document->issue_date->format('Y-m-d') }}"
                        class="border rounded px-2 py-1 w-full text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500">取引先</label>
                    <input type="text" name="client_name" value="{{ $document->client_name }}"
                        class="border rounded px-2 py-1 w-full text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500">住所</label>
                    <input type="text" name="client_address" value="{{ $document->client_address }}"
                        class="border rounded px-2 py-1 w-full text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500">件名</label>
                    <input type="text" name="subject" value="{{ $document->subject }}"
                        class="border rounded px-2 py-1 w-full text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500">期限/納期</label>
                    <input type="date" name="due_date" value="{{ $document->due_date?->format('Y-m-d') }}"
                        class="border rounded px-2 py-1 w-full text-sm">
                </div>
                <div>
                    <label class="block text-xs text-gray-500">備考</label>
                    <textarea name="notes" rows="3" class="border rounded px-2 py-1 w-full text-sm">{{ $document->notes }}</textarea>
                </div>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-1 rounded text-sm w-full hover:bg-indigo-700">
                    保存
                </button>
            </form>
        </div>

        {{-- 右: 明細 --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- 明細追加 --}}
            <div class="bg-indigo-50 rounded shadow p-3 border-2 border-dashed border-indigo-300">
                <form @submit.prevent="addItem()" class="flex items-center gap-2">
                    <div class="flex-1">
                        <input type="text" x-model="newItem.description" required placeholder="品目"
                            class="border rounded px-2 py-1.5 w-full text-sm">
                    </div>
                    <div class="w-16">
                        <input type="number" x-model="newItem.quantity" min="1" placeholder="数量"
                            class="border rounded px-2 py-1.5 w-full text-sm text-right">
                    </div>
                    <div class="w-16">
                        <input type="text" x-model="newItem.unit" placeholder="単位"
                            class="border rounded px-2 py-1.5 w-full text-sm text-center">
                    </div>
                    <div class="w-28">
                        <input type="number" x-model="newItem.unit_price" min="0" placeholder="単価"
                            class="border rounded px-2 py-1.5 w-full text-sm text-right">
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded text-sm hover:bg-indigo-700">
                        + 追加
                    </button>
                </form>
            </div>

            {{-- 明細一覧 --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs text-gray-600">品目</th>
                            <th class="px-3 py-2 text-right text-xs text-gray-600 w-20">数量</th>
                            <th class="px-3 py-2 text-center text-xs text-gray-600 w-16">単位</th>
                            <th class="px-3 py-2 text-right text-xs text-gray-600 w-28">単価</th>
                            <th class="px-3 py-2 text-right text-xs text-gray-600 w-28">金額</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <template x-for="item in items" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-sm" x-text="item.description"></td>
                                <td class="px-3 py-2 text-sm text-right" x-text="item.quantity"></td>
                                <td class="px-3 py-2 text-sm text-center" x-text="item.unit"></td>
                                <td class="px-3 py-2 text-sm text-right font-mono" x-text="'¥' + Number(item.unit_price).toLocaleString()"></td>
                                <td class="px-3 py-2 text-sm text-right font-mono font-bold" x-text="'¥' + Number(item.amount).toLocaleString()"></td>
                                <td class="px-3 py-2 text-center">
                                    <button @click="removeItem(item.id)" class="text-gray-400 hover:text-red-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- 合計 --}}
            <div class="bg-white rounded shadow p-4">
                <div class="flex justify-end">
                    <div class="w-64 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">小計</span>
                            <span class="font-mono" x-text="'¥' + subtotal.toLocaleString()"></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">消費税 (10%)</span>
                            <span class="font-mono" x-text="'¥' + tax.toLocaleString()"></span>
                        </div>
                        <div class="flex justify-between text-lg font-bold border-t pt-2">
                            <span>合計</span>
                            <span class="font-mono text-indigo-600" x-text="'¥' + total.toLocaleString()"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function documentEditor() {
    return {
        items: @json($document->items),
        subtotal: {{ $document->subtotal }},
        tax: {{ $document->tax }},
        total: {{ $document->total }},
        newItem: { description: '', quantity: 1, unit: '式', unit_price: '' },

        async addItem() {
            if (!this.newItem.description || !this.newItem.unit_price) return;
            const res = await fetch('{{ route("documents.addItem", $document) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(this.newItem),
            });
            const data = await res.json();
            if (data.success) {
                this.items = data.document.items;
                this.subtotal = data.document.subtotal;
                this.tax = data.document.tax;
                this.total = data.document.total;
                this.newItem = { description: '', quantity: 1, unit: '式', unit_price: '' };
            }
        },

        async removeItem(itemId) {
            if (!confirm('削除しますか？')) return;
            const res = await fetch(`{{ url('documents/' . $document->id . '/items') }}/${itemId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            });
            const data = await res.json();
            if (data.success) {
                this.items = data.document.items;
                this.subtotal = data.document.subtotal;
                this.tax = data.document.tax;
                this.total = data.document.total;
            }
        },

        async updateStatus(status) {
            if (!status) return;
            await fetch('{{ route("documents.updateStatus", $document) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ status }),
            });
            location.reload();
        },
    };
}
</script>
@endsection
