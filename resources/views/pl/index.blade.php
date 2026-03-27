@extends('layouts.app')

@section('content')
<div class="space-y-6">
    {{-- 年度選択 --}}
    <div class="bg-white rounded shadow p-4 flex items-center gap-4">
        <form method="GET" action="{{ route('pl.index') }}" class="flex items-center gap-4">
            <label class="text-sm text-gray-600">年度</label>
            <select name="year" class="border rounded px-3 py-2" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}年</option>
                @endforeach
            </select>
        </form>
        <div class="ml-auto text-sm text-gray-500">
            仕訳済み: {{ $classifiedCount }} / {{ $totalCount }} 件
            @if($totalCount > 0 && $classifiedCount < $totalCount)
                <span class="text-orange-500 ml-2">※未仕訳 {{ $totalCount - $classifiedCount }} 件あり</span>
            @endif
        </div>
    </div>

    {{-- P/L サマリーカード --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white rounded shadow p-4 border-t-4 border-green-500">
            <div class="text-sm text-gray-500">売上高</div>
            <div class="text-xl font-mono font-bold text-green-700">¥{{ number_format($revenueTotal) }}</div>
        </div>
        <div class="bg-white rounded shadow p-4 border-t-4 border-red-500">
            <div class="text-sm text-gray-500">経費合計</div>
            <div class="text-xl font-mono font-bold text-red-700">¥{{ number_format($expenseTotal) }}</div>
        </div>
        <div class="bg-white rounded shadow p-4 border-t-4 border-purple-500">
            <div class="text-sm text-gray-500">減価償却費</div>
            <div class="text-xl font-mono font-bold text-purple-700">¥{{ number_format($depreciationTotal) }}</div>
        </div>
        <div class="bg-white rounded shadow p-4 border-t-4 border-blue-500">
            <div class="text-sm text-gray-500">雑収入</div>
            <div class="text-xl font-mono font-bold text-blue-700">¥{{ number_format($otherIncomeTotal) }}</div>
        </div>
        <div class="bg-white rounded shadow p-4 border-t-4 {{ $netIncome >= 0 ? 'border-green-600' : 'border-red-600' }}">
            <div class="text-sm text-gray-500">当期純利益</div>
            <div class="text-xl font-mono font-bold {{ $netIncome >= 0 ? 'text-green-700' : 'text-red-700' }}">
                ¥{{ number_format($netIncome) }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 左: P/L本体 --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- 売上セクション --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-green-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">売上高</h2>
                </div>
                <table class="w-full">
                    <tbody>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">売上高</td>
                            <td class="px-4 py-2 text-right font-mono font-bold text-green-700">¥{{ number_format($revenueTotal) }}</td>
                        </tr>
                        @if($otherIncomeTotal > 0)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2">雑収入</td>
                            <td class="px-4 py-2 text-right font-mono font-bold text-blue-700">¥{{ number_format($otherIncomeTotal) }}</td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot class="bg-green-50 font-bold">
                        <tr class="border-t-2">
                            <td class="px-4 py-3">収入合計</td>
                            <td class="px-4 py-3 text-right font-mono text-lg text-green-700">¥{{ number_format($revenueTotal + $otherIncomeTotal) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- 経費セクション --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-indigo-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">経費内訳</h2>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-sm text-gray-600">勘定科目</th>
                            <th class="text-right px-4 py-2 text-sm text-blue-600">クレカ</th>
                            <th class="text-right px-4 py-2 text-sm text-yellow-600">現金</th>
                            @if($totalByMethod['paypay'] > 0)
                            <th class="text-right px-4 py-2 text-sm text-red-600">PayPay</th>
                            @endif
                            <th class="text-right px-4 py-2 text-sm text-gray-600">合計</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plItems as $item)
                            @if($item['amount'] > 0)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $item['name'] }}</td>
                                <td class="px-4 py-2 text-right font-mono text-sm text-blue-700">
                                    {{ $item['credit_card'] > 0 ? '¥' . number_format($item['credit_card']) : '-' }}
                                </td>
                                <td class="px-4 py-2 text-right font-mono text-sm text-yellow-700">
                                    {{ $item['cash'] > 0 ? '¥' . number_format($item['cash']) : '-' }}
                                </td>
                                @if($totalByMethod['paypay'] > 0)
                                <td class="px-4 py-2 text-right font-mono text-sm text-red-700">
                                    {{ $item['paypay'] > 0 ? '¥' . number_format($item['paypay']) : '-' }}
                                </td>
                                @endif
                                <td class="px-4 py-2 text-right font-mono font-bold">¥{{ number_format($item['amount']) }}</td>
                            </tr>
                            @endif
                        @endforeach

                        @if($depreciationTotal > 0)
                        <tr class="border-t hover:bg-gray-50 bg-purple-50">
                            <td class="px-4 py-2 text-purple-700">減価償却費</td>
                            <td class="px-4 py-2" colspan="{{ $totalByMethod['paypay'] > 0 ? 3 : 2 }}"></td>
                            <td class="px-4 py-2 text-right font-mono font-bold text-purple-700">¥{{ number_format($depreciationTotal) }}</td>
                        </tr>
                        @endif

                        @if($unclassifiedTotal > 0)
                        <tr class="border-t bg-orange-50">
                            <td class="px-4 py-2 text-orange-600">未仕訳</td>
                            <td class="px-4 py-2" colspan="{{ $totalByMethod['paypay'] > 0 ? 3 : 2 }}"></td>
                            <td class="px-4 py-2 text-right font-mono text-orange-600">¥{{ number_format($unclassifiedTotal) }}</td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr class="border-t-2">
                            <td class="px-4 py-3">経費合計（減価償却含む）</td>
                            <td class="px-4 py-3" colspan="{{ $totalByMethod['paypay'] > 0 ? 3 : 2 }}"></td>
                            <td class="px-4 py-3 text-right font-mono text-lg">¥{{ number_format($expenseTotal + $depreciationTotal) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- 損益サマリー --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="{{ $netIncome >= 0 ? 'bg-green-700' : 'bg-red-700' }} text-white px-4 py-3">
                    <h2 class="text-lg font-bold">損益サマリー</h2>
                </div>
                <table class="w-full">
                    <tbody>
                        <tr class="border-t">
                            <td class="px-4 py-2 text-gray-600">売上高</td>
                            <td class="px-4 py-2 text-right font-mono text-green-700">¥{{ number_format($revenueTotal) }}</td>
                        </tr>
                        <tr class="border-t">
                            <td class="px-4 py-2 text-gray-600">経費合計</td>
                            <td class="px-4 py-2 text-right font-mono text-red-700">- ¥{{ number_format($expenseTotal) }}</td>
                        </tr>
                        <tr class="border-t">
                            <td class="px-4 py-2 text-gray-600">減価償却費</td>
                            <td class="px-4 py-2 text-right font-mono text-purple-700">- ¥{{ number_format($depreciationTotal) }}</td>
                        </tr>
                        <tr class="border-t bg-gray-50 font-bold">
                            <td class="px-4 py-2">営業利益</td>
                            <td class="px-4 py-2 text-right font-mono {{ $grossProfit >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                ¥{{ number_format($grossProfit) }}
                            </td>
                        </tr>
                        @if($otherIncomeTotal > 0)
                        <tr class="border-t">
                            <td class="px-4 py-2 text-gray-600">雑収入</td>
                            <td class="px-4 py-2 text-right font-mono text-blue-700">+ ¥{{ number_format($otherIncomeTotal) }}</td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot class="font-bold {{ $netIncome >= 0 ? 'bg-green-50' : 'bg-red-50' }}">
                        <tr class="border-t-2">
                            <td class="px-4 py-3 text-lg">当期純利益</td>
                            <td class="px-4 py-3 text-right font-mono text-xl {{ $netIncome >= 0 ? 'text-green-700' : 'text-red-700' }}">
                                ¥{{ number_format($netIncome) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- 0円の科目一覧 --}}
            <div class="bg-white rounded shadow p-4">
                <h3 class="text-sm font-bold text-gray-500 mb-2">0円の科目</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($plItems as $item)
                        @if($item['amount'] === 0)
                            <span class="text-xs px-2 py-1 bg-gray-100 rounded text-gray-400">{{ $item['name'] }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 右: 月別推移 --}}
        <div class="space-y-4">
            {{-- 月別売上 --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-green-600 text-white px-4 py-3">
                    <h2 class="font-bold">月別売上推移</h2>
                </div>
                <table class="w-full">
                    <tbody>
                        @php $maxRevMonth = max($monthlyRevenue) ?: 1; @endphp
                        @for($m = 1; $m <= 12; $m++)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-1 text-sm">{{ $m }}月</td>
                            <td class="px-4 py-1 text-right font-mono text-sm">
                                {{ $monthlyRevenue[$m] > 0 ? '¥' . number_format($monthlyRevenue[$m]) : '-' }}
                            </td>
                            <td class="px-4 py-1 w-20">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ round($monthlyRevenue[$m] / $maxRevMonth * 100) }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>

            {{-- 月別経費 --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-indigo-600 text-white px-4 py-3">
                    <h2 class="font-bold">月別経費推移</h2>
                </div>
                <table class="w-full">
                    <tbody>
                        @php $maxMonthly = max($monthly) ?: 1; @endphp
                        @for($m = 1; $m <= 12; $m++)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-1 text-sm">{{ $m }}月</td>
                            <td class="px-4 py-1 text-right font-mono text-sm">
                                {{ $monthly[$m] > 0 ? '¥' . number_format($monthly[$m]) : '-' }}
                            </td>
                            <td class="px-4 py-1 w-20">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: {{ round($monthly[$m] / $maxMonthly * 100) }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
