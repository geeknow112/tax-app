@extends('layouts.app')

@section('content')
<div class="space-y-6">
    {{-- 年度選択 --}}
    <div class="bg-white rounded shadow p-4 flex items-center gap-4">
        <form method="GET" action="{{ route('bs.index') }}" class="flex items-center gap-4">
            <label class="text-sm text-gray-600">年度</label>
            <select name="year" class="border rounded px-3 py-2" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}年</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- B/S サマリーカード --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded shadow p-4 border-t-4 border-blue-500">
            <div class="text-sm text-gray-500">資産合計</div>
            <button onclick="copyVal('{{ $totalAssets }}')" class="text-xl font-mono font-bold text-blue-700 hover:bg-blue-50 px-1 rounded cursor-pointer">¥{{ number_format($totalAssets) }}</button>
        </div>
        <div class="bg-white rounded shadow p-4 border-t-4 border-red-500">
            <div class="text-sm text-gray-500">負債合計</div>
            <button onclick="copyVal('{{ $totalLiabilities }}')" class="text-xl font-mono font-bold text-red-700 hover:bg-red-50 px-1 rounded cursor-pointer">¥{{ number_format($totalLiabilities) }}</button>
        </div>
        <div class="bg-white rounded shadow p-4 border-t-4 border-green-500">
            <div class="text-sm text-gray-500">純資産</div>
            <button onclick="copyVal('{{ $totalEquity }}')" class="text-xl font-mono font-bold {{ $totalEquity >= 0 ? 'text-green-700 hover:bg-green-50' : 'text-red-700 hover:bg-red-50' }} px-1 rounded cursor-pointer">¥{{ number_format($totalEquity) }}</button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- 左: 資産の部 --}}
        <div class="space-y-4">
            {{-- 流動資産 --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-blue-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">資産の部</h2>
                </div>
                <table class="w-full">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-sm text-blue-700" colspan="2">流動資産</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets['current'] as $item)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $item['name'] }}</td>
                            <td class="px-4 py-2 text-right"><button onclick="copyVal('{{ $item['amount'] }}')" class="font-mono hover:bg-gray-100 px-1 rounded cursor-pointer">¥{{ number_format($item['amount']) }}</button></td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-blue-50">
                        <tr class="border-t font-bold">
                            <td class="px-4 py-2 text-sm text-blue-700">流動資産 小計</td>
                            <td class="px-4 py-2 text-right"><button onclick="copyVal('{{ $currentAssetTotal }}')" class="font-mono text-blue-700 hover:bg-blue-100 px-1 rounded cursor-pointer">¥{{ number_format($currentAssetTotal) }}</button></td>
                        </tr>
                    </tfoot>
                </table>

                {{-- 固定資産 --}}
                <table class="w-full">
                    <thead class="bg-blue-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-sm text-blue-700">固定資産</th>
                            <th class="text-right px-4 py-2 text-sm text-gray-500">取得価額</th>
                            <th class="text-right px-4 py-2 text-sm text-gray-500">累計償却</th>
                            <th class="text-right px-4 py-2 text-sm text-blue-700">帳簿価額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assets['fixed'] as $item)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm">{{ $item['name'] }}</td>
                            <td class="px-4 py-2 text-right"><button onclick="copyVal('{{ $item['acquisition_cost'] }}')" class="font-mono text-sm text-gray-500 hover:bg-gray-100 px-1 rounded cursor-pointer">¥{{ number_format($item['acquisition_cost']) }}</button></td>
                            <td class="px-4 py-2 text-right"><button onclick="copyVal('{{ $item['accumulated_depreciation'] }}')" class="font-mono text-sm text-red-500 hover:bg-red-50 px-1 rounded cursor-pointer">-¥{{ number_format($item['accumulated_depreciation']) }}</button></td>
                            <td class="px-4 py-2 text-right"><button onclick="copyVal('{{ $item['book_value'] }}')" class="font-mono text-sm hover:bg-gray-100 px-1 rounded cursor-pointer">¥{{ number_format($item['book_value']) }}</button></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400 text-sm">固定資産なし</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-blue-50">
                        <tr class="border-t font-bold">
                            <td class="px-4 py-2 text-sm text-blue-700">固定資産 小計</td>
                            <td class="px-4 py-2 text-right"><button onclick="copyVal('{{ $acquisitionTotal }}')" class="font-mono text-sm text-gray-500 hover:bg-gray-100 px-1 rounded cursor-pointer">¥{{ number_format($acquisitionTotal) }}</button></td>
                            <td class="px-4 py-2 text-right"><button onclick="copyVal('{{ $accumulatedDepTotal }}')" class="font-mono text-sm text-red-500 hover:bg-red-50 px-1 rounded cursor-pointer">-¥{{ number_format($accumulatedDepTotal) }}</button></td>
                            <td class="px-4 py-2 text-right"><button onclick="copyVal('{{ $fixedAssetTotal }}')" class="font-mono text-blue-700 hover:bg-blue-100 px-1 rounded cursor-pointer">¥{{ number_format($fixedAssetTotal) }}</button></td>
                        </tr>
                    </tfoot>
                </table>

                {{-- 資産合計 --}}
                <div class="bg-blue-600 text-white px-4 py-3 flex justify-between font-bold">
                    <span>資産合計</span>
                    <button onclick="copyVal('{{ $totalAssets }}')" class="font-mono text-lg hover:bg-blue-500 px-2 rounded cursor-pointer">¥{{ number_format($totalAssets) }}</button>
                </div>
            </div>
        </div>

        {{-- 右: 負債・純資産の部 --}}
        <div class="space-y-4">
            {{-- 負債 --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-red-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">負債の部</h2>
                </div>
                <table class="w-full">
                    <tbody>
                        @forelse($liabilities as $item)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $item['name'] }}</td>
                            <td class="px-4 py-2 text-right"><button onclick="copyVal('{{ $item['amount'] }}')" class="font-mono hover:bg-gray-100 px-1 rounded cursor-pointer">¥{{ number_format($item['amount']) }}</button></td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-4 py-4 text-center text-gray-400 text-sm">負債なし</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="bg-red-600 text-white px-4 py-3 flex justify-between font-bold">
                    <span>負債合計</span>
                    <button onclick="copyVal('{{ $totalLiabilities }}')" class="font-mono text-lg hover:bg-red-500 px-2 rounded cursor-pointer">¥{{ number_format($totalLiabilities) }}</button>
                </div>
            </div>

            {{-- 純資産 --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-green-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">純資産の部</h2>
                </div>
                <table class="w-full">
                    <tbody>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">当期純利益</td>
                            <td class="px-4 py-2 text-right">
                                <button onclick="copyVal('{{ $netIncome }}')" class="font-mono {{ $netIncome >= 0 ? 'text-green-700 hover:bg-green-50' : 'text-red-700 hover:bg-red-50' }} px-1 rounded cursor-pointer">¥{{ number_format($netIncome) }}</button>
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">繰越利益剰余金</td>
                            <td class="px-4 py-2 text-right">
                                <button onclick="copyVal('{{ $totalEquity - $netIncome }}')" class="font-mono {{ ($totalEquity - $netIncome) >= 0 ? 'hover:bg-gray-100' : 'text-red-700 hover:bg-red-50' }} px-1 rounded cursor-pointer">¥{{ number_format($totalEquity - $netIncome) }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="bg-green-600 text-white px-4 py-3 flex justify-between font-bold">
                    <span>純資産合計</span>
                    <button onclick="copyVal('{{ $totalEquity }}')" class="font-mono text-lg hover:bg-green-500 px-2 rounded cursor-pointer">¥{{ number_format($totalEquity) }}</button>
                </div>
            </div>

            {{-- バランスチェック --}}
            <div class="bg-white rounded shadow p-4">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">バランスチェック（資産 = 負債 + 純資産）</span>
                    @if($totalAssets === $totalLiabilities + $totalEquity)
                        <span class="text-green-600 text-sm font-bold">✅ 一致</span>
                    @else
                        <span class="text-red-600 text-sm font-bold">❌ 不一致（差額: ¥{{ number_format($totalAssets - $totalLiabilities - $totalEquity) }}）</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
