<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - 確定申告サポート</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        freee: {
                            blue: '#2563eb',
                            dark: '#1e40af',
                            light: '#eff6ff',
                            sidebar: '#1e293b',
                            hover: '#334155',
                            active: '#0f172a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- 上部ナビ -->
    <header class="bg-freee-sidebar border-b border-gray-700">
        <div class="flex items-center justify-between px-4 py-3">
            <a href="{{ route('expenses.index') }}" class="text-xl font-bold text-white">確定申告サポート</a>
            <div class="flex items-center gap-4">
                <!-- 事業体セレクタ -->
                <form action="{{ route('entity.switch') }}" method="POST" class="flex items-center gap-2">
                    @csrf
                    <label class="text-gray-400 text-sm">事業体:</label>
                    <select name="entity_id" onchange="this.form.submit()" class="bg-freee-hover text-white border border-gray-600 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-freee-blue">
                        @foreach($entities ?? [] as $entity)
                            <option value="{{ $entity->id }}" {{ ($currentEntity->id ?? null) == $entity->id ? 'selected' : '' }}>
                                {{ $entity->name }}
                                @if($entity->isCorporation())
                                    ({{ $entity->fiscal_year_start }}月決算)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </header>

    <div class="flex" style="height: calc(100vh - 57px);">
        <!-- 左サイドバー -->
        <aside class="w-56 bg-freee-sidebar text-gray-300 flex-shrink-0 overflow-y-auto">
            <nav class="p-3 space-y-1">
                <a href="{{ route('expenses.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-freee-hover transition {{ request()->routeIs('expenses.*') ? 'bg-freee-active text-white font-semibold' : '' }}">
                    <span class="text-lg">📝</span>
                    <span>仕訳</span>
                </a>
                <a href="{{ route('revenues.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-freee-hover transition {{ request()->routeIs('revenues.*') ? 'bg-freee-active text-white font-semibold' : '' }}">
                    <span class="text-lg">💰</span>
                    <span>売上</span>
                </a>
                <a href="{{ route('depreciations.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-freee-hover transition {{ request()->routeIs('depreciations.*') ? 'bg-freee-active text-white font-semibold' : '' }}">
                    <span class="text-lg">🏢</span>
                    <span>減価償却</span>
                </a>
                <a href="{{ route('documents.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-freee-hover transition {{ request()->routeIs('documents.*') ? 'bg-freee-active text-white font-semibold' : '' }}">
                    <span class="text-lg">📄</span>
                    <span>書類</span>
                </a>

                <div class="border-t border-gray-600 my-3"></div>
                <p class="px-3 py-1 text-xs text-gray-500 uppercase tracking-wider">レポート</p>

                <a href="{{ route('pl.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-freee-hover transition {{ request()->routeIs('pl.*') ? 'bg-freee-active text-white font-semibold' : '' }}">
                    <span class="text-lg">📊</span>
                    <span>損益計算書</span>
                </a>
                <a href="{{ route('bs.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-freee-hover transition {{ request()->routeIs('bs.*') ? 'bg-freee-active text-white font-semibold' : '' }}">
                    <span class="text-lg">📈</span>
                    <span>貸借対照表</span>
                </a>

                <div class="border-t border-gray-600 my-3"></div>
                <p class="px-3 py-1 text-xs text-gray-500 uppercase tracking-wider">設定</p>

                <a href="{{ route('import.show') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-freee-hover transition {{ request()->routeIs('import.*') ? 'bg-freee-active text-white font-semibold' : '' }}">
                    <span class="text-lg">📥</span>
                    <span>インポート</span>
                </a>
                <a href="{{ route('categories.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-freee-hover transition {{ request()->routeIs('categories.*') ? 'bg-freee-active text-white font-semibold' : '' }}">
                    <span class="text-lg">⚙️</span>
                    <span>勘定科目</span>
                </a>
                <a href="{{ route('entities.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-freee-hover transition {{ request()->routeIs('entities.*') ? 'bg-freee-active text-white font-semibold' : '' }}">
                    <span class="text-lg">🏛️</span>
                    <span>事業体管理</span>
                </a>
            </nav>
        </aside>

        <!-- メインコンテンツ -->
        <main class="flex-1 p-6 overflow-y-auto bg-gray-50">
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
                    {{ session('success') }}
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <div id="copy-toast" class="fixed bottom-4 right-4 bg-freee-blue text-white px-4 py-2 rounded-lg shadow-lg hidden text-sm">
        コピーしました
    </div>
    <script>
    function copyVal(value) {
        navigator.clipboard.writeText(value);
        const t = document.getElementById('copy-toast');
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 1000);
    }
    </script>
</body>
</html>
