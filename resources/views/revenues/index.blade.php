@extends('layouts.app')

@section('content')
<div class="space-y-6">
    {{-- 年度選択 --}}
    <div class="bg-white rounded shadow p-4 flex items-center gap-4">
        <form method="GET" action="{{ route('revenues.index') }}" class="flex items-center gap-4">
            <label class="text-sm text-gray-600">年度</label>
            <select name="year" class="border rounded px-3 py-2" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}年</option>
                @endforeach
            </select>
        </form>
        <div class="ml-auto text-sm">
            <span class="text-gray-600">売上合計:</span>
            <span class="font-mono font-bold text-green-700">¥{{ number_format($totalSales) }}</span>
            @if($totalOther > 0)
                <span class="text-gray-400 mx-2">|</span>
                <span class="text-gray-600">雑収入:</span>
                <span class="font-mono font-bold text-blue-700">¥{{ number_format($totalOther) }}</span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 左: 売上一覧 --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-green-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">売上一覧 - {{ $currentYear }}年</h2>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-sm text-gray-600">日付</th>
                            <th class="text-left px-4 py-2 text-sm text-gray-600">取引先</th>
                            <th class="text-left px-4 py-2 text-sm text-gray-600">内容</th>
                            <th class="text-left px-4 py-2 text-sm text-gray-600">区分</th>
                            <th class="text-right px-4 py-2 text-sm text-gray-600">金額</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($revenues as $rev)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm">{{ $rev->date->format('m/d') }}</td>
                            <td class="px-4 py-2">{{ $rev->client_name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $rev->description }}</td>
                            <td class="px-4 py-2">
                                <span class="text-xs px-2 py-0.5 rounded {{ $rev->revenue_type === 'sales' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                    {{ $rev->revenue_type === 'sales' ? '売上' : '雑収入' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-right font-mono font-bold">¥{{ number_format($rev->amount) }}</td>
                            <td class="px-4 py-2">
                                <form method="POST" action="{{ route('revenues.destroy', $rev) }}" onsubmit="return confirm('削除しますか？')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-400 hover:text-red-600 text-sm">削除</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">売上データがありません</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr class="border-t-2">
                            <td colspan="4" class="px-4 py-3">合計</td>
                            <td class="px-4 py-3 text-right font-mono text-lg">¥{{ number_format($totalSales + $totalOther) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                <div class="p-4">{{ $revenues->appends(request()->query())->links() }}</div>
            </div>
        </div>

        {{-- 右: 売上登録フォーム --}}
        <div>
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-green-600 text-white px-4 py-3">
                    <h2 class="font-bold">売上登録</h2>
                </div>
                <form method="POST" action="{{ route('revenues.store') }}" class="p-4 space-y-4">
                    @csrf
                    <input type="hidden" name="year" value="{{ $currentYear }}">

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">日付 <span class="text-red-500">*</span></label>
                        <input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                            class="border rounded px-3 py-2 w-full">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">取引先 <span class="text-red-500">*</span></label>
                        <input type="text" name="client_name" value="{{ old('client_name') }}" required placeholder="例: 株式会社○○"
                            class="border rounded px-3 py-2 w-full">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">内容</label>
                        <input type="text" name="description" value="{{ old('description') }}" placeholder="例: Web制作費"
                            class="border rounded px-3 py-2 w-full">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">金額 <span class="text-red-500">*</span></label>
                        <input type="number" name="amount" value="{{ old('amount') }}" required min="1" placeholder="100000"
                            class="border rounded px-3 py-2 w-full">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">区分</label>
                        <select name="revenue_type" class="border rounded px-3 py-2 w-full">
                            <option value="sales">売上</option>
                            <option value="other">雑収入</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">メモ</label>
                        <textarea name="memo" rows="2" class="border rounded px-3 py-2 w-full">{{ old('memo') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700">登録</button>
                </form>

                @if($errors->any())
                <div class="px-4 pb-4">
                    <div class="bg-red-50 text-red-600 text-sm p-3 rounded">
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection