<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { background: #0f172a; color: #e2e8f0; }
    </style>
    @stack('head')
</head>
<body class="min-h-screen antialiased">
    @php $isWide = $wide ?? false; @endphp
    <nav class="border-b border-slate-700 bg-slate-900/95 sticky top-0 z-50 backdrop-blur">
        <div class="mx-auto {{ $isWide ? 'w-full max-w-none px-2 sm:px-3' : 'max-w-5xl px-4' }} py-2 sm:py-3 flex flex-wrap items-center justify-between gap-2">
            <a href="{{ route('home') }}" class="text-base sm:text-lg font-bold text-pink-400 shrink-0">TIF Planner</a>
            <div class="flex flex-wrap gap-1 sm:gap-2 text-xs sm:text-sm">
                <a href="{{ route('live.index') }}" class="px-3 py-2 rounded-lg bg-red-500/20 text-red-300">当日</a>
                <a href="{{ route('plans.show') }}" class="px-3 py-2 rounded-lg hover:bg-slate-800">プラン結果</a>
                <a href="{{ route('plans.edit') }}" class="px-3 py-2 rounded-lg hover:bg-slate-800">推し編集</a>
                <a href="{{ route('timetable.index') }}" class="px-3 py-2 rounded-lg hover:bg-slate-800">タイムテーブル</a>
                <a href="{{ route('map.index') }}" class="px-3 py-2 rounded-lg hover:bg-slate-800">マップ</a>
                @auth
                    <span class="hidden sm:inline px-2 py-2 text-slate-400">{{ Auth::user()->name }}</span>
                    <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-lg hover:bg-slate-800">アカウント</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 rounded-lg hover:bg-slate-800 text-slate-300">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="px-3 py-2 rounded-lg hover:bg-slate-800">ログイン</a>
                @endauth
            </div>
        </div>
    </nav>

    <main class="mx-auto {{ $isWide ? 'w-full max-w-none px-2 sm:px-3 py-3 sm:py-4' : 'max-w-5xl px-4 py-6' }}">
        @if (session('status'))
            <div class="mb-4 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-slate-800 mt-10 py-6 text-center text-xs text-slate-500 px-4">
        非公式ツールです。公式情報と異なる場合は
        <a href="https://official.idolfes.com/s/tif2026/page/timetable" class="underline" target="_blank" rel="noopener">TIF 公式</a>
        を正としてください。
    </footer>
</body>
</html>
