@extends('layouts.app')

@section('content')
<div x-data="categoryApp()" class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 左: 科目一覧 --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-gray-700 text-white px-4 py-3 flex items-center justify-between">
                    <h2 class="text-lg font-bold">勘定科目一覧</h2>
                    <span class="text-sm text-gray-300">ドラッグで並び替え可能</span>
                </div>
                <div id="category-list">
                    @foreach($categories as $cat)
                    <div class="border-t flex items-center gap-3 px-4 py-3 hover:bg-gray-50 cursor-move"
                        draggable="true"
                        data-id="{{ $cat->id }}"
                        @dragstart="dragStart($event, {{ $cat->id }})"
                        @dragover.prevent="dragOver($event)"
                        @drop="drop($event, {{ $cat->id }})">
                        <span class="text-gray-400 cursor-move">☰</span>
                        <span class="text-sm text-gray-400 w-8">{{ $cat->sort_order }}</span>

                        {{-- 表示モード --}}
                        <span x-show="editingId !== {{ $cat->id }}" class="flex-1">{{ $cat->name }}</span>
                        <span x-show="editingId !== {{ $cat->id }}" class="text-xs text-gray-400">
                            {{ $cat->expenses_count ?? $cat->expenses()->count() }}件使用中
                        </span>
                        <button x-show="editingId !== {{ $cat->id }}"
                            @click="startEdit({{ $cat->id }}, '{{ addslashes($cat->name) }}')"
                            class="text-sm text-indigo-500 hover:text-indigo-700">編集</button>

                        {{-- 編集モード --}}
                        <input x-show="editingId === {{ $cat->id }}" x-model="editName"
                            @keydown.enter="saveEdit({{ $cat->id }})"
                            @keydown.escape="editingId = null"
                            class="flex-1 border rounded px-2 py-1 text-sm">
                        <button x-show="editingId === {{ $cat->id }}"
                            @click="saveEdit({{ $cat->id }})"
                            class="text-sm text-green-600 hover:text-green-800">保存</button>
                        <button x-show="editingId === {{ $cat->id }}"
                            @click="editingId = null"
                            class="text-sm text-gray-400 hover:text-gray-600">取消</button>

                        {{-- 削除 --}}
                        <form method="POST" action="{{ route('categories.destroy', $cat) }}"
                            onsubmit="return confirm('「{{ $cat->name }}」を削除しますか？')">
                            @csrf @method('DELETE')
                            <button class="text-sm text-red-400 hover:text-red-600">削除</button>
                        </form>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 右: 追加フォーム --}}
        <div>
            <div class="bg-white rounded shadow overflow-hidden">
                <div class="bg-gray-700 text-white px-4 py-3">
                    <h2 class="font-bold">科目追加</h2>
                </div>
                <form method="POST" action="{{ route('categories.store') }}" class="p-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm text-gray-600 mb-1">科目名 <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="例: 車両費"
                            class="border rounded px-3 py-2 w-full">
                    </div>
                    <button type="submit" class="w-full bg-gray-700 text-white py-2 rounded hover:bg-gray-800">追加</button>
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

                @if(session('error'))
                <div class="px-4 pb-4">
                    <div class="bg-red-50 text-red-600 text-sm p-3 rounded">
                        {{ session('error') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function categoryApp() {
    return {
        editingId: null,
        editName: '',
        draggedId: null,

        startEdit(id, name) {
            this.editingId = id;
            this.editName = name;
        },

        async saveEdit(id) {
            const res = await fetch(`/categories/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ name: this.editName }),
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            }
        },

        dragStart(event, id) {
            this.draggedId = id;
            event.dataTransfer.effectAllowed = 'move';
        },

        dragOver(event) {
            event.dataTransfer.dropEffect = 'move';
        },

        async drop(event, targetId) {
            if (this.draggedId === targetId) return;

            const list = document.getElementById('category-list');
            const items = [...list.querySelectorAll('[data-id]')];
            const ids = items.map(el => parseInt(el.dataset.id));

            const fromIdx = ids.indexOf(this.draggedId);
            const toIdx = ids.indexOf(targetId);
            ids.splice(fromIdx, 1);
            ids.splice(toIdx, 0, this.draggedId);

            await fetch('/categories/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ids }),
            });
            location.reload();
        },
    };
}
</script>
@endsection
