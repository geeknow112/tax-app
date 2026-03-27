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
            <div class="text-xl font-mono font-bold text-blue-700">¥{{ number_format($totalAssets) }}</div>
        </div>
        <div class="bg-white rounded shadow p-4 border-t-4 border-red-500">
            <div class="text-sm text-gray-500">負債合計</div>
            <div class="text-xl font-mono font-bold text-red-700">¥{{ number_format($totalLiabilities) }}</div>
        </div>
        <div class="bg-white rounded shadow p-4 border-t-4 border-green-500">
            <div class="text-sm text-gray-500">純資産</div>
            <div class="text-xl font-mono font-bold {{ $totalEquity >= 0 ? 'text-green-700' : 'text-red-700' }}">¥{{ number_format($totalEquity) }}</div>
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
                            <td class="px-4 py-2 text-right font-mono">¥{{ number_format($item['amount']) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-blue-50">
                        <tr class="border-t font-bold">
                            <td class="px-4 py-2 text-sm text-blue-700">流動資産 小計</td>
                            <td class="px-4 py-2 text-right font-mono text-blue-700">¥{{ number_format($currentAssetTotal) }}</td>
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
                            <td class="px-4 py-2 text-right font-mono text-sm text-gray-500">¥{{ number_format($item['acquisition_cost']) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-sm text-red-500">-¥{{ number_format($item['accumulated_depreciation']) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-sm">¥{{ number_format($item['book_value']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400 text-sm">固定資産なし</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-blue-50">
                        <tr class="border-t font-bold">
                            <td class="px-4 py-2 text-sm text-blue-700">固定資産 小計</td>
                            <td class="px-4 py-2 text-right font-mono text-sm text-gray-500">¥{{ number_format($acquisitionTotal) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-sm text-red-500">-¥{{ number_format($accumulatedDepTotal) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-blue-700">¥{{ number_format($fixedAssetTotal) }}</td>
                        </tr>
                    </tfoot>
                </table>

                {{-- 資産合計 --}}
                <div class="bg-blue-600 text-white px-4 py-3 flex justify-between font-bold">
                    <span>資産合計</span>
                    <span class="font-mono text-lg">¥{{ number_format($totalAssets) }}</span>
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
                            <td class="px-4 py-2 text-right font-mono">¥{{ number_format($item['amount']) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="px-4 py-4 text-center text-gray-400 text-sm">負債なし</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="bg-red-600 text-white px-4 py-3 flex justify-between font-bold">
                    <span>負債合計</span>
                    <span class="font-mono text-lg">¥{{ number_format($totalLiabilities) }}</span>
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
                            <td class="px-4 py-2 text-right font-mono {{ $netIncome >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                ¥{{ number_format($netIncome) }}
                            </td>
                        </tr>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">繰越利益剰余金</td>
                            <td class="px-4 py-2 text-right font-mono {{ ($totalEquity - $netIncome) >= 0 ? '' : 'text-red-700' }}">
                                ¥{{ number_format($totalEquity - $netIncome) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="bg-green-600 text-white px-4 py-3 flex justify-between font-bold">
                    <span>純資産合計</span>
                    <span class="font-mono text-lg">¥{{ number_format($totalEquity) }}</span>
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
