@extends('layouts.app')

@section('content')
<div class="space-y-4">
    {{-- フィルター --}}
    <div class="bg-white rounded shadow p-4">
        <form method="GET" class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm text-gray-600">年度</label>
                <select name="year" class="border rounded px-3 py-2" onchange="this.form.submit()">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}年</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600">種類</label>
                <select name="type" class="border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>すべて</option>
                    <option value="estimate" {{ $type === 'estimate' ? 'selected' : '' }}>見積書</option>
                    <option value="order" {{ $type === 'order' ? 'selected' : '' }}>発注書</option>
                    <option value="invoice" {{ $type === 'invoice' ? 'selected' : '' }}>請求書</option>
                    <option value="delivery" {{ $type === 'delivery' ? 'selected' : '' }}>納品書</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-gray-600">ステータス</label>
                <select name="status" class="border rounded px-3 py-2" onchange="this.form.submit()">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>すべて</option>
                    <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>下書き</option>
                    <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>送付済</option>
                    <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>入金済</option>
                    <option value="cancelled" {{ $status === 'cancelled' ? 'selected' : '' }}>キャンセル</option>
                </select>
            </div>
            <div class="ml-auto flex gap-2">
                <a href="{{ route('documents.create', ['type' => 'estimate', 'year' => $currentYear]) }}"
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">+ 見積書</a>
                <a href="{{ route('documents.create', ['type' => 'order', 'year' => $currentYear]) }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">+ 発注書</a>
                <a href="{{ route('documents.create', ['type' => 'invoice', 'year' => $currentYear]) }}"
                    class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm">+ 請求書</a>
                <a href="{{ route('documents.create', ['type' => 'delivery', 'year' => $currentYear]) }}"
                    class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 text-sm">+ 納品書</a>
            </div>
        </form>
    </div>

    {{-- 一覧 --}}
    <div class="bg-white rounded shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">書類番号</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">種類</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">取引先</th>
                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-600">発行日</th>
                    <th class="px-4 py-3 text-right text-sm font-medium text-gray-600">合計</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-gray-600">ステータス</th>
                    <th class="px-4 py-3 text-center text-sm font-medium text-gray-600">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($documents as $doc)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-mono">{{ $doc->document_number }}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 py-1 rounded text-xs
                            {{ $doc->type === 'estimate' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $doc->type === 'order' ? 'bg-blue-100 text-blue-700' : '' }}
                            {{ $doc->type === 'invoice' ? 'bg-indigo-100 text-indigo-700' : '' }}
                            {{ $doc->type === 'delivery' ? 'bg-purple-100 text-purple-700' : '' }}">
                            {{ $doc->type_name }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">{{ $doc->client_name }}</td>
                    <td class="px-4 py-3 text-sm">{{ $doc->issue_date->format('Y/m/d') }}</td>
                    <td class="px-4 py-3 text-sm text-right font-mono font-bold">¥{{ number_format($doc->total) }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded text-xs
                            {{ $doc->status === 'draft' ? 'bg-gray-100 text-gray-600' : '' }}
                            {{ $doc->status === 'sent' ? 'bg-yellow-100 text-yellow-700' : '' }}
                            {{ $doc->status === 'paid' ? 'bg-green-100 text-green-700' : '' }}
                            {{ $doc->status === 'cancelled' ? 'bg-red-100 text-red-700' : '' }}">
                            {{ $doc->status_name }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('documents.edit', $doc) }}" class="text-indigo-600 hover:underline text-sm">編集</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">書類がありません</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div>{{ $documents->appends(request()->query())->links() }}</div>
</div>
@endsection
