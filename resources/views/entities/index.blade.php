@extends('layouts.app')

@section('content')
<div class="max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">事業体管理</h1>
        <a href="{{ route('entities.create') }}" class="bg-freee-blue hover:bg-freee-dark text-white px-4 py-2 rounded-lg text-sm font-medium transition">
            + 新規作成
        </a>
    </div>

    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">名前</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">種別</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">決算開始月</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">経費件数</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">売上件数</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">操作</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($entities as $entity)
                <tr class="hover:bg-gray-50 {{ ($currentEntity->id ?? null) == $entity->id ? 'bg-blue-50' : '' }}">
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-800">{{ $entity->name }}</span>
                        @if(($currentEntity->id ?? null) == $entity->id)
                            <span class="ml-2 text-xs bg-freee-blue text-white px-2 py-0.5 rounded">選択中</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        @if($entity->isIndividual())
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">個人事業</span>
                        @else
                            <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-xs">法人</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $entity->fiscal_year_start }}月</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $entity->expenses()->count() }}件</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $entity->revenues()->count() }}件</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('entities.edit', $entity) }}" class="text-freee-blue hover:underline text-sm">編集</a>
                            <form action="{{ route('entities.destroy', $entity) }}" method="POST" class="inline" onsubmit="return confirm('本当に削除しますか？')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:underline text-sm">削除</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="font-semibold text-blue-800 mb-2">💡 事業体について</h3>
        <ul class="text-sm text-blue-700 space-y-1">
            <li>• <strong>個人事業</strong>: 決算期は1月〜12月（暦年）</li>
            <li>• <strong>法人</strong>: 決算期は任意（例: 4月〜3月）</li>
            <li>• ヘッダーのセレクタで事業体を切り替えると、各画面のデータがフィルタされます</li>
        </ul>
    </div>
</div>
@endsection
