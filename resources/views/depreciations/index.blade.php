@extends('layouts.app')

@section('content')
<div class="space-y-6">
    {{-- 年度選択 --}}
    <div class="bg-white rounded shadow p-4 flex items-center gap-4">
        <form method="GET" action="{{ route('depreciations.index') }}" class="flex items-center gap-4">
            <label class="text-sm text-gray-600">年度</label>
            <select name="year" class="border rounded px-3 py-2" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}年</option>
                @endforeach
            </select>
        </form>
        <div class="ml-auto text-sm">
            <span class="text-gray-600">当年償却額合計:</span>
            <span class="font-mono font-bold text-purple-700">¥{{ number_format($totalDepreciation) }}</span>
            <span class="text-gray-400 mx-2">|</span>
            <span class="text-gray-600">期末帳簿価額合計:</span>
            <span class="font-mono font-bold">¥{{ number_format($totalBookValue) }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 左: 固定資産一覧 --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-purple-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">固定資産・減価償却一覧 - {{ $currentYear }}年</h2>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-3 py-2 text-sm text-gray-600">資産名</th>
                            <th class="text-left px-3 py-2 text-sm text-gray-600">取得日</th>
                            <th class="text-right px-3 py-2 text-sm text-gray-600">取得価額</th>
                            <th class="text-center px-3 py-2 text-sm text-gray-600">耐用年数</th>
                            <th class="text-right px-3 py-2 text-sm text-purple-600">当年償却額</th>
                            <th class="text-right px-3 py-2 text-sm text-gray-600">累計償却額</th>
                            <th class="text-right px-3 py-2 text-sm text-gray-600">帳簿価額</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($depreciations as $dep)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-3 py-2">{{ $dep->asset_name }}</td>
                            <td class="px-3 py-2 text-sm">{{ $dep->acquisition_date->format('Y/m/d') }}</td>
                            <td class="px-3 py-2 text-right"><button onclick="copyVal('{{ $dep->acquisition_cost }}')" class="font-mono text-sm hover:bg-gray-100 px-1 rounded cursor-pointer">¥{{ number_format($dep->acquisition_cost) }}</button></td>
                            <td class="px-3 py-2 text-center text-sm">{{ $dep->useful_life }}年</td>
                            <td class="px-3 py-2 text-right"><button onclick="copyVal('{{ $dep->depreciation_amount }}')" class="font-mono font-bold text-purple-700 hover:bg-purple-50 px-1 rounded cursor-pointer">¥{{ number_format($dep->depreciation_amount) }}</button></td>
                            <td class="px-3 py-2 text-right"><button onclick="copyVal('{{ $dep->accumulated_depreciation }}')" class="font-mono text-sm text-gray-500 hover:bg-gray-100 px-1 rounded cursor-pointer">¥{{ number_format($dep->accumulated_depreciation) }}</button></td>
                            <td class="px-3 py-2 text-right"><button onclick="copyVal('{{ $dep->book_value }}')" class="font-mono text-sm hover:bg-gray-100 px-1 rounded cursor-pointer">¥{{ number_format($dep->book_value) }}</button></td>
                            <td class="px-3 py-2">
                                <form method="POST" action="{{ route('depreciations.destroy', $dep) }}" onsubmit="return confirm('削除しますか？')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-400 hover:text-red-600 text-sm">削除</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-400">固定資産データがありません</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr class="border-t-2">
                            <td colspan="4" class="px-3 py-3">合計</td>
                            <td class="px-3 py-3 text-right font-mono text-purple-700">¥{{ number_format($totalDepreciation) }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- 右: 固定資産登録フォーム --}}
        <div>
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-purple-600 text-white px-4 py-3">
                    <h2 class="font-bold">固定資産登録</h2>
                </div>
                <form method="POST" action="{{ route('depreciations.store') }}" class="p-4 space-y-4">
                    @csrf
                    <input type="hidden" name="year" value="{{ $currentYear }}">

                    <div>
                        <label class="block text-sm text-gray-600 mb-1">資産名 <span class="text-red-500">*</span></label>
                        <input type="text" name="asset_name" value="{{ old('asset_name') }}" required placeholder="例: MacBook Pro"
                            class="border rounded px-3 py-2 w-full">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">取得日 <span class="text-red-500">*</span></label>
                        <input type="date" name="acquisition_date" value="{{ old('acquisition_date') }}" required
                            class="border rounded px-3 py-2 w-full">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">取得価額 <span class="text-red-500">*</span></label>
                        <input type="number" name="acquisition_cost" value="{{ old('acquisition_cost') }}" required min="1" placeholder="300000"
                            class="border rounded px-3 py-2 w-full">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">耐用年数 <span class="text-red-500">*</span></label>
                        <select name="useful_life" class="border rounded px-3 py-2 w-full">
                            <option value="2">2年</option>
                            <option value="3">3年</option>
                            <option value="4" selected>4年 (PC等)</option>
                            <option value="5">5年</option>
                            <option value="6">6年</option>
                            <option value="8">8年</option>
                            <option value="10">10年</option>
                            <option value="15">15年</option>
                            <option value="20">20年</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">償却方法</label>
                        <select name="method" class="border rounded px-3 py-2 w-full">
                            <option value="straight_line">定額法</option>
                            <option value="declining_balance">定率法</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">メモ</label>
                        <textarea name="memo" rows="2" class="border rounded px-3 py-2 w-full">{{ old('memo') }}</textarea>
                    </div>
                    <button type="submit" class="w-full bg-purple-600 text-white py-2 rounded hover:bg-purple-700">登録</button>
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