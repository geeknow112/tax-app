@extends('layouts.app')

@section('content')
<div class="max-w-xl">
    <div class="mb-6">
        <a href="{{ route('entities.index') }}" class="text-freee-blue hover:underline text-sm">← 事業体一覧に戻る</a>
    </div>

    <h1 class="text-2xl font-bold text-gray-800 mb-6">事業体を編集</h1>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form action="{{ route('entities.update', $entity) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">事業体名 <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $entity->name) }}" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-freee-blue focus:border-transparent">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">種別 <span class="text-red-500">*</span></label>
                <select name="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-freee-blue focus:border-transparent">
                    <option value="individual" {{ old('type', $entity->type) == 'individual' ? 'selected' : '' }}>個人事業</option>
                    <option value="corporation" {{ old('type', $entity->type) == 'corporation' ? 'selected' : '' }}>法人</option>
                </select>
                @error('type')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">決算開始月 <span class="text-red-500">*</span></label>
                <select name="fiscal_year_start" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-freee-blue focus:border-transparent">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" {{ old('fiscal_year_start', $entity->fiscal_year_start) == $m ? 'selected' : '' }}>{{ $m }}月</option>
                    @endfor
                </select>
                @error('fiscal_year_start')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-freee-blue hover:bg-freee-dark text-white px-4 py-2.5 rounded-lg font-medium transition">
                    更新する
                </button>
            </div>
        </form>
    </div>

    <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <h3 class="font-semibold text-gray-700 mb-2">関連データ</h3>
        <ul class="text-sm text-gray-600 space-y-1">
            <li>• 経費: {{ $entity->expenses()->count() }}件</li>
            <li>• 売上: {{ $entity->revenues()->count() }}件</li>
            <li>• 減価償却: {{ $entity->depreciations()->count() }}件</li>
        </ul>
    </div>
</div>
@endsection
