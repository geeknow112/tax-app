<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - 確定申告サポート</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-indigo-600 text-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('expenses.index') }}" class="text-lg font-bold">確定申告サポート</a>
            <div class="flex gap-4">
                <a href="{{ route('expenses.index') }}" class="hover:underline {{ request()->routeIs('expenses.*') ? 'font-bold underline' : '' }}">仕訳</a>
                <a href="{{ route('revenues.index') }}" class="hover:underline {{ request()->routeIs('revenues.*') ? 'font-bold underline' : '' }}">売上</a>
                <a href="{{ route('depreciations.index') }}" class="hover:underline {{ request()->routeIs('depreciations.*') ? 'font-bold underline' : '' }}">減価償却</a>
                <a href="{{ route('pl.index') }}" class="hover:underline {{ request()->routeIs('pl.*') ? 'font-bold underline' : '' }}">P/L</a>
                <a href="{{ route('bs.index') }}" class="hover:underline {{ request()->routeIs('bs.*') ? 'font-bold underline' : '' }}">B/S</a>
                <a href="{{ route('import.show') }}" class="hover:underline {{ request()->routeIs('import.*') ? 'font-bold underline' : '' }}">インポート</a>
                <a href="{{ route('categories.index') }}" class="hover:underline {{ request()->routeIs('categories.*') ? 'font-bold underline' : '' }}">科目管理</a>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto px-4 py-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
