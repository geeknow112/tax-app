@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded shadow p-6">
        <h2 class="text-xl font-bold text-gray-700 mb-4">
            {{ \App\Models\Document::TYPES[$type] ?? '書類' }}を作成
        </h2>

        <form method="POST" action="{{ route('documents.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="hidden" name="year" value="{{ $year }}">

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">書類番号</label>
                    <input type="text" name="document_number" value="{{ $documentNumber }}"
                        class="border rounded px-3 py-2 w-full bg-gray-50" readonly>
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">発行日 <span class="text-red-500">*</span></label>
                    <input type="date" name="issue_date" value="{{ date('Y-m-d') }}" required
                        class="border rounded px-3 py-2 w-full">
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">取引先名 <span class="text-red-500">*</span></label>
                <input type="text" name="client_name" required placeholder="株式会社〇〇"
                    class="border rounded px-3 py-2 w-full">
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">取引先住所</label>
                <input type="text" name="client_address" placeholder="東京都..."
                    class="border rounded px-3 py-2 w-full">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">件名</label>
                    <input type="text" name="subject" placeholder="〇〇開発費用"
                        class="border rounded px-3 py-2 w-full">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">支払期限/納期</label>
                    <input type="date" name="due_date" class="border rounded px-3 py-2 w-full">
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-600 mb-1">備考</label>
                <textarea name="notes" rows="3" class="border rounded px-3 py-2 w-full"
                    placeholder="振込先など"></textarea>
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700">
                    作成して明細を追加
                </button>
                <a href="{{ route('documents.index') }}" class="text-gray-600 px-4 py-2 hover:underline">キャンセル</a>
            </div>
        </form>
    </div>
</div>
@endsection
