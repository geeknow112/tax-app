@extends('layouts.app')

@section('content')
<div class="space-y-6">
    {{-- 年度選択 --}}
    <div class="bg-white rounded shadow p-4 flex items-center gap-4">
        <form method="GET" action="{{ route('etax.index') }}" class="flex items-center gap-4">
            <label class="text-sm text-gray-600">年度</label>
            <select name="year" class="border rounded px-3 py-2" onchange="this.form.submit()">
                @foreach($years as $y)
                    <option value="{{ $y }}" {{ $currentYear == $y ? 'selected' : '' }}>{{ $y }}年</option>
                @endforeach
            </select>
        </form>
        <div class="ml-auto text-sm text-gray-500">数字をクリックでコピー</div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- 左: 収支内訳書 --}}
        <div class="space-y-4">
            {{-- 売上 --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-green-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">収入金額</h2>
                </div>
                <table class="w-full">
                    <tbody>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-600">ア 売上（収入）金額</td>
                            <td class="px-4 py-3 text-right">
                                <button onclick="copyToClipboard('{{ $salesTotal }}')"
                                    class="font-mono font-bold text-lg text-green-700 hover:bg-green-50 px-2 py-1 rounded cursor-pointer">
                                    {{ number_format($salesTotal) }}
                                </button>
                            </td>
                        </tr>
                        @if($otherIncomeTotal > 0)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-600">雑収入</td>
                            <td class="px-4 py-3 text-right">
                                <button onclick="copyToClipboard('{{ $otherIncomeTotal }}')"
                                    class="font-mono font-bold text-lg text-blue-700 hover:bg-blue-50 px-2 py-1 rounded cursor-pointer">
                                    {{ number_format($otherIncomeTotal) }}
                                </button>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- 経費 --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-red-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">必要経費</h2>
                </div>
                <table class="w-full">
                    <tbody>
                        @foreach($expenseItems as $item)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-600">{{ $item['name'] }}</td>
                            <td class="px-4 py-2 text-right">
                                <button onclick="copyToClipboard('{{ $item['amount'] }}')"
                                    class="font-mono text-sm hover:bg-red-50 px-2 py-1 rounded cursor-pointer">
                                    {{ number_format($item['amount']) }}
                                </button>
                            </td>
                        </tr>
                        @endforeach
                        @if($depreciationTotal > 0)
                        <tr class="border-t hover:bg-gray-50 bg-purple-50">
                            <td class="px-4 py-2 text-purple-700">減価償却費</td>
                            <td class="px-4 py-2 text-right">
                                <button onclick="copyToClipboard('{{ $depreciationTotal }}')"
                                    class="font-mono text-sm text-purple-700 hover:bg-purple-100 px-2 py-1 rounded cursor-pointer">
                                    {{ number_format($depreciationTotal) }}
                                </button>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr class="border-t-2">
                            <td class="px-4 py-3">経費合計</td>
                            <td class="px-4 py-3 text-right">
                                <button onclick="copyToClipboard('{{ $totalExpense }}')"
                                    class="font-mono text-lg hover:bg-gray-200 px-2 py-1 rounded cursor-pointer">
                                    {{ number_format($totalExpense) }}
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- 所得金額 --}}
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="{{ $income >= 0 ? 'bg-indigo-600' : 'bg-red-700' }} text-white px-4 py-3">
                    <h2 class="text-lg font-bold">所得金額</h2>
                </div>
                <table class="w-full">
                    <tbody>
                        <tr class="border-t">
                            <td class="px-4 py-3 text-gray-600">収入 - 経費</td>
                            <td class="px-4 py-3 text-right">
                                <button onclick="copyToClipboard('{{ $income }}')"
                                    class="font-mono font-bold text-xl {{ $income >= 0 ? 'text-indigo-700' : 'text-red-700' }} hover:bg-indigo-50 px-2 py-1 rounded cursor-pointer">
                                    {{ number_format($income) }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 右: 減価償却明細 --}}
        <div>
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-purple-600 text-white px-4 py-3">
                    <h2 class="text-lg font-bold">減価償却費の計算</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-3 py-2 text-gray-600">資産名</th>
                            <th class="text-right px-3 py-2 text-gray-600">取得価額</th>
                            <th class="text-center px-3 py-2 text-gray-600">耐用年数</th>
                            <th class="text-right px-3 py-2 text-purple-600">本年分償却費</th>
                            <th class="text-right px-3 py-2 text-gray-600">期末残高</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($depreciations as $dep)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-3 py-2">{{ $dep->asset_name }}</td>
                            <td class="px-3 py-2 text-right">
                                <button onclick="copyToClipboard('{{ $dep->acquisition_cost }}')"
                                    class="font-mono hover:bg-gray-100 px-1 rounded cursor-pointer">
                                    {{ number_format($dep->acquisition_cost) }}
                                </button>
                            </td>
                            <td class="px-3 py-2 text-center">{{ $dep->useful_life }}年</td>
                            <td class="px-3 py-2 text-right">
                                <button onclick="copyToClipboard('{{ $dep->depreciation_amount }}')"
                                    class="font-mono text-purple-700 hover:bg-purple-50 px-1 rounded cursor-pointer">
                                    {{ number_format($dep->depreciation_amount) }}
                                </button>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button onclick="copyToClipboard('{{ $dep->book_value }}')"
                                    class="font-mono hover:bg-gray-100 px-1 rounded cursor-pointer">
                                    {{ number_format($dep->book_value) }}
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-4 text-center text-gray-400">減価償却資産なし</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-100 font-bold">
                        <tr class="border-t-2">
                            <td class="px-3 py-2" colspan="3">合計</td>
                            <td class="px-3 py-2 text-right">
                                <button onclick="copyToClipboard('{{ $depreciationTotal }}')"
                                    class="font-mono text-purple-700 hover:bg-purple-100 px-1 rounded cursor-pointer">
                                    {{ number_format($depreciationTotal) }}
                                </button>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- コピー通知 --}}
<div id="copy-toast" class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded shadow-lg hidden text-sm">
    コピーしました
</div>

<script>
function copyToClipboard(value) {
    navigator.clipboard.writeText(value);
    const toast = document.getElementById('copy-toast');
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 1000);
}
</script>
@endsection
