@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-700 mb-6">Excelインポート</h1>

    <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data"
        class="bg-white rounded shadow p-6 space-y-4">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">年度</label>
            <input type="number" name="year" value="{{ date('Y') }}" min="2020" max="2099"
                class="border rounded px-3 py-2 w-full">
            @error('year') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">種別</label>
            <select name="type" class="border rounded px-3 py-2 w-full">
                <option value="credit_card">クレジットカード明細</option>
                <option value="cash">現金・PayPay明細</option>
            </select>
            @error('type') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Excelファイル</label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv"
                class="border rounded px-3 py-2 w-full">
            @error('file') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
            class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700 transition">
            インポート実行
        </button>
    </form>

    <div class="mt-6 bg-white rounded shadow p-6">
        <h2 class="font-bold text-gray-700 mb-2">フォーマット説明</h2>
        <div class="text-sm text-gray-600 space-y-2">
            <p>クレカ明細: エポスカードのExcelそのまま（ヘッダー2行 + データ行）</p>
            <p>現金明細: 現金シートのExcelそのまま（ヘッダー2行 + データ行）</p>
            <p>科目列（I列）に勘定科目名があれば自動で紐付けます</p>
        </div>
    </div>
</div>
@endsection
