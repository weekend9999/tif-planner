@extends('layouts.tif')

@section('title', '推しリスト編集 - TIF Planner')

@section('content')
<div class="space-y-6 max-w-none" x-data="{ q: @js($query), planCount: {{ $context->performances->count() }} }" @plan-count-changed.window="planCount = $event.detail.count">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">推しリスト編集</h1>
            <p class="text-sm text-slate-400 mt-1">公式タイムテーブル形式 — 横に並んだステージから、同じ時間帯の出演を比較して選べます</p>
        </div>
        <a href="{{ route('plans.show') }}" class="rounded-xl bg-pink-500 px-5 py-2.5 text-sm font-semibold whitespace-nowrap">プラン結果を見る</a>
    </div>

    {{-- Day tabs --}}
    <div class="flex flex-wrap gap-2">
        @foreach ($days as $date => $label)
            <a href="{{ route('plans.edit', ['day' => $date, 'q' => $query]) }}"
               class="rounded-lg px-4 py-2 text-sm font-medium {{ $day === $date ? 'bg-pink-500 text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- Favorite artists (pre-registration) --}}
    <section class="rounded-xl border border-slate-700 bg-slate-900 p-4">
        <h2 class="font-semibold mb-1 text-sm text-slate-200">推しアイドル登録</h2>
        <p class="text-xs text-slate-400 mb-3">事前に登録すると、タイムテーブル上の該当出演枠が薄いピンクで表示されます（全DAY共通）</p>
        <form method="POST" action="{{ route('favorites.store') }}" class="flex flex-wrap gap-2 mb-3">
            @csrf
            <input type="text" name="artist_name" placeholder="アーティスト名（例: =LOVE）" required
                   class="flex-1 min-w-[180px] rounded-lg bg-slate-800 border border-slate-600 px-3 py-2 text-sm">
            <button type="submit" class="rounded-lg bg-pink-600 px-4 py-2 text-sm font-semibold whitespace-nowrap hover:bg-pink-500">登録</button>
        </form>
        @if (!empty($favoriteArtists))
            <div class="flex flex-wrap gap-2">
                @foreach ($favoriteArtists as $artist)
                    <form method="POST" action="{{ route('favorites.destroy') }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="artist_name" value="{{ $artist }}">
                        <button type="submit"
                                class="inline-flex items-center gap-1 rounded-full border border-pink-400/40 bg-pink-100/10 px-3 py-1 text-xs text-pink-200 hover:bg-pink-100/20">
                            {{ $artist }}
                            <span class="text-red-300">×</span>
                        </button>
                    </form>
                @endforeach
            </div>
        @else
            <p class="text-xs text-slate-500">まだ登録されていません</p>
        @endif
    </section>

    {{-- Search --}}
    <div class="flex flex-wrap gap-3 items-center">
        <input type="search" x-model="q" placeholder="アーティスト名で絞り込み（例: =LOVE, わーすた）"
               class="flex-1 min-w-[200px] rounded-xl bg-slate-800 border border-slate-600 px-4 py-3 text-sm">
        <span class="text-sm text-slate-400">選択中: <strong class="text-pink-300" x-text="planCount"></strong> 組</span>
    </div>

    {{-- Timetable grid --}}
    <section class="w-full">
        <h2 class="font-semibold mb-2 text-slate-200 text-sm sm:text-base">{{ $grid['day_label'] }} タイムテーブル</h2>
        <div x-effect="$root.querySelectorAll('[data-artist]').forEach(el => {
            const match = !q || el.dataset.artist.toLowerCase().includes(q.toLowerCase());
            el.classList.toggle('opacity-35', !match);
            el.classList.toggle('ring-2', match && q);
            el.classList.toggle('ring-yellow-300', match && q);
        })">
            <x-timetable-grid
                :grid="$grid"
                :selected-ids="$selectedIds"
                selectable
            />
        </div>
    </section>

    {{-- Settings (collapsed feel) --}}
    <details class="rounded-xl border border-slate-700 bg-slate-900">
        <summary class="cursor-pointer px-5 py-4 font-semibold text-slate-300">移動設定（バッファ・モード）</summary>
        <form method="POST" action="{{ route('plans.settings') }}" class="px-5 pb-5 space-y-4 border-t border-slate-800 pt-4">
            @csrf
            @method('PATCH')
            <div>
                <label class="block text-sm text-slate-400 mb-1">プラン名</label>
                <input type="text" name="name" value="{{ old('name', $context->name) }}" class="w-full rounded-lg bg-slate-800 border-slate-600 px-3 py-2">
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">移動モード</label>
                <select name="buffer_mode" class="w-full rounded-lg bg-slate-800 border-slate-600 px-3 py-2">
                    @foreach ($bufferModes as $mode)
                        <option value="{{ $mode->value }}" @selected(old('buffer_mode', $context->bufferMode->value) === $mode->value)>
                            {{ $mode->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm text-slate-400 mb-1">退場バッファ(分)</label>
                    <input type="number" name="exit_buffer" min="0" max="30" value="{{ old('exit_buffer', $context->exitBuffer) }}" class="w-full rounded-lg bg-slate-800 border-slate-600 px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">入場バッファ(分)</label>
                    <input type="number" name="entry_buffer" min="0" max="30" value="{{ old('entry_buffer', $context->entryBuffer) }}" class="w-full rounded-lg bg-slate-800 border-slate-600 px-3 py-2">
                </div>
            </div>
            <button type="submit" class="rounded-lg bg-slate-700 px-4 py-2 text-sm">設定を保存</button>
        </form>
    </details>
</div>
@endsection
