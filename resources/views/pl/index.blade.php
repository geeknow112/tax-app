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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 左: P/L科目別 --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-indigo-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">損益計算書 (P/L) - {{ $currentYear }}年</h2>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-sm text-gray-600">勘定科目</th>
                            <th class="text-right px-4 py-2 text-sm text-gray-600">金額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plItems as $item)
                            @if($item['amount'] > 0)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $item['name'] }}</td>
                                <td class="px-4 py-2 text-right font-mono">¥{{ number_format($item['amount']) }}</td>
                            </tr>
                            @endif
                        @endforeach

                        @if($unclassifiedTotal > 0)
                        <tr class="border-t bg-orange-50">
                            <td class="px-4 py-2 text-orange-600">未仕訳</td>
                            <td class="px-4 py-2 text-right font-mono text-orange-600">¥{{ number_format($unclassifiedTotal) }}</td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr class="border-t-2">
                            <td class="px-4 py-3">経費合計</td>
                            <td class="px-4 py-3 text-right font-mono text-lg">¥{{ number_format($expenseTotal) }}</td>
                        </tr>
                        @if($unclassifiedTotal > 0)
                        <tr class="border-t">
                            <td class="px-4 py-3 text-sm text-gray-500">経費合計（未仕訳含む）</td>
                            <td class="px-4 py-3 text-right font-mono text-sm text-gray-500">¥{{ number_format($expenseTotal + $unclassifiedTotal) }}</td>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>

            {{-- 0円の科目一覧 --}}
            <div class="bg-white rounded shadow p-4 mt-4">
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

        {{-- 右: 支払方法別 + 月別推移 --}}
        <div class="space-y-6">
            {{-- 支払方法別内訳 --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-green-600 text-white px-4 py-3">
                    <h2 class="font-bold">支払方法別内訳</h2>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-sm text-gray-600">支払方法</th>
                            <th class="text-right px-4 py-2 text-sm text-gray-600">件数</th>
                            <th class="text-right px-4 py-2 text-sm text-gray-600">金額</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentSummary as $key => $pm)
                            @if($pm['count'] > 0)
                            <tr class="border-t hover:bg-gray-50">
                                <td class="px-4 py-2">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs
                                        {{ $key === 'credit_card' ? 'bg-blue-100 text-blue-700' : ($key === 'paypay' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                        {{ $pm['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right text-sm">{{ number_format($pm['count']) }}件</td>
                                <td class="px-4 py-2 text-right font-mono">¥{{ number_format($pm['total']) }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr class="border-t-2">
                            <td class="px-4 py-2">合計</td>
                            <td class="px-4 py-2 text-right text-sm">{{ number_format(collect($paymentSummary)->sum('count')) }}件</td>
                            <td class="px-4 py-2 text-right font-mono">¥{{ number_format(collect($paymentSummary)->sum('total')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-indigo-600 text-white px-4 py-3">
                    <h2 class="font-bold">月別経費推移</h2>
                </div>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-2 text-sm text-gray-600">月</th>
                            <th class="text-right px-4 py-2 text-sm text-gray-600">金額</th>
                            <th class="px-4 py-2 text-sm text-gray-600 w-24"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $maxMonthly = max($monthly) ?: 1; @endphp
                        @for($m = 1; $m <= 12; $m++)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2 text-sm">{{ $m }}月</td>
                            <td class="px-4 py-2 text-right font-mono text-sm">
                                {{ $monthly[$m] > 0 ? '¥' . number_format($monthly[$m]) : '-' }}
                            </td>
                            <td class="px-4 py-1">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div class="bg-indigo-500 h-2 rounded-full"
                                        style="width: {{ round($monthly[$m] / $maxMonthly * 100) }}%"></div>
                                </div>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr class="border-t-2">
                            <td class="px-4 py-2 text-sm">合計</td>
                            <td class="px-4 py-2 text-right font-mono text-sm">¥{{ number_format(array_sum($monthly)) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
